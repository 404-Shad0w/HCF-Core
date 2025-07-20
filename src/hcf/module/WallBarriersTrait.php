<?php

namespace hcf\module;

use CortexPE\std\math\geometry\ArchimedeanSpiral;
use CortexPE\std\PositionUtils;
use CortexPE\std\Vector3Utils;
use muqsit\simplepackethandler\SimplePacketHandler;
use pocketmine\block\Block;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\player\Player;
use pocketmine\world\Position;

trait WallBarriersTrait {
	abstract protected function isVisibleTo(Player $player): bool;

	abstract protected function isInsideProhibitedArea(Player $p, Position $pos): bool;

	abstract protected function getBarrierBlock(): Block;

	private int $barrierRadius;
	private array $oldPos = [];

	private float $mvmntTrshld = 0.1 * 0.1; // 0.1 ^ 2 so that we don't have to squirt later

	public function onInitialize(): void {
		SimplePacketHandler::createInterceptor($this->getCore(), EventPriority::LOWEST)
			->interceptIncoming(function(InventoryTransactionPacket $pk, NetworkSession $src): bool {
				if(!$pk->trData instanceof UseItemTransactionData) return true;
				$p = $src->getPlayer();
				if(!$this->isVisibleTo($p)) return true;
				return !$this->isInsideProhibitedArea($p, Position::fromObject(Vector3Utils::fromBlockPosition($pk->trData->getBlockPosition()), $p->getWorld()));
			})
			->interceptIncoming(function(PlayerActionPacket $pk, NetworkSession $src): bool {
				if($pk->action === PlayerAction::ABORT_BREAK) {
					$p = $src->getPlayer();
					if(!$this->isVisibleTo($p)) return true;
					$this->displayWallBarrier($p);
					return false;
				}
				if($pk->action === PlayerAction::START_BREAK) {
					$p = $src->getPlayer();
					if(!$this->isVisibleTo($p)) return true;
					return !$this->isInsideProhibitedArea($p, Position::fromObject(Vector3Utils::fromBlockPosition($pk->blockPosition), $p->getWorld()));
				}
				return true;
			});
	}

	public function onTeleport(EntityTeleportEvent $ev): void {
		$p = $ev->getEntity();
		if(!$p instanceof Player) return;
		if(!$this->isVisibleTo($p)) return;
		if(!$this->isInsideProhibitedArea($p, PositionUtils::floor($ev->getTo()))) return;
		$ev->cancel();
	}

	abstract protected function calculateNearestPointOutside(Player $p, Position $pos):Position;

	public function onMove(PlayerMoveEvent $ev): void {
		$p = $ev->getPlayer();
		if(!$this->isVisibleTo($p)) {
			if(isset($this->oldPos[$k = $p->getId()])) {
				$this->hideWallBarrier($p, $this->oldPos[$k]);
				unset($this->oldPos[$k]);
			}
			return;
		}
		$fr = $ev->getFrom();
		$to = $ev->getTo();
		if($this->isInsideProhibitedArea($p, PositionUtils::floor($p->getPosition()))) {
			$ev->cancel();

			$p->teleport($this->calculateNearestPointOutside($p, $fr));
		}
		if($fr->distanceSquared($to) < $this->mvmntTrshld) return;
		if(!$this->isNearProhibitedArea($p, $to)) return;
		if(isset($this->oldPos[$k = $p->getId()])) {
			if($this->oldPos[$k]->distance($to) > 1) {
				$this->hideWallBarrier($p, $this->oldPos[$k]);
				$this->oldPos[$k] = $to;
			}
		} else {
			$this->oldPos[$k] = $to;
		}
		$this->displayWallBarrier($p);
	}

	public function onQuit(PlayerQuitEvent $ev): void {
		unset($this->oldPos[$ev->getPlayer()->getId()]);
	}

	private function hideWallBarrier(Player $p, ?Position $pos = null): void {
		$blocks = iterator_to_array($this->getNearbyBlocks($p, $pos ?? $p->getPosition(), 1), false);
		if(count($blocks) < 1) return;
        NetworkBroadcastUtils::broadcastPackets([$p], $p->getWorld()->createBlockUpdatePackets($blocks));
        //$p->getServer()->broadcastPackets([$p], $p->getWorld()->createBlockUpdatePackets($blocks));
	}

	/**
	 * @param Player $p
	 * @param Position $position
	 * @param int $expandedRadius
	 * @return \Generator
	 */
	private function getNearbyBlocks(Player $p, Position $position, int $expandedRadius = 0): \Generator {
		$fPos = PositionUtils::floor($position);
		$radius = $this->barrierRadius + $expandedRadius;
		for($cy = -$radius; $cy <= $radius; $cy++) {
			for($cx = -$radius; $cx <= $radius; $cx++) {
				for($cz = -$radius; $cz <= $radius; $cz++) {
					if(!$this->isInsideProhibitedArea($p, $cPos = PositionUtils::floor(PositionUtils::add($fPos, $cx, $cy, $cz)))) continue;
					yield $cPos;
				}
			}
		}
	}

	private function isNearProhibitedArea(Player $p, Position $pos): bool {
		$pos = PositionUtils::floor($pos);
		if($this->isInsideProhibitedArea($p, PositionUtils::add($pos, $this->barrierRadius, 0, $this->barrierRadius))) return true;
		if($this->isInsideProhibitedArea($p, PositionUtils::add($pos, -$this->barrierRadius, 0, -$this->barrierRadius))) return true;
		if($this->isInsideProhibitedArea($p, PositionUtils::add($pos, $this->barrierRadius, 0, -$this->barrierRadius))) return true;
		if($this->isInsideProhibitedArea($p, PositionUtils::add($pos, -$this->barrierRadius, 0, $this->barrierRadius))) return true;
		return false;
	}

	private function displayWallBarrier(Player $p): void {
		$w = $p->getWorld();
		foreach($this->getNearbyBlocks($p, $p->getPosition()) as $nearbyBlock) {
			if(!$w->getBlock($nearbyBlock)->isTransparent()) continue;
			FakeBlock::showBlock($p, $this->getBarrierBlock(), $nearbyBlock);
		}
	}
}