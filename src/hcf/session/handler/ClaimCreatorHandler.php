<?php

/*
 * A PocketMine-MP plugin that implements Hard Core Factions.
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 *
 * @author JkqzDev
 */

declare(strict_types=1);

namespace hcf\session\handler;

use betterkoths\koth\KothFactory;
use hcf\claim\Claim;
use hcf\claim\ClaimFactory;
use hcf\claim\ClaimHandler;
use hcf\faction\Faction;
use hcf\faction\FactionFactory;
use hcf\HCF;
use hcf\session\Session;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use RuntimeException;
use function abs;
use function intval;
use function max;
use function min;

final class ClaimCreatorHandler {

	public function __construct(
		private Session   $session,
		private string    $name,
		private string    $type,
		private ?Position $firstPosition = null,
		private ?Position $secondPosition = null
	) {}

	public function handleChat(PlayerChatEvent $event) : void {
		$player = $event->getPlayer();
		$message = $event->getMessage();

		if ($message === 'accept') {
			$event->cancel();

			try {
				$this->create();
                FactionFactory::saveAll();
			} catch (RuntimeException $exception) {
				$player->sendMessage(TextFormat::colorize($exception->getMessage()));
			}
		} elseif ($message === 'cancel') {
			$event->cancel();
			$this->finish($player);
		}
	}

	private function create() : void {
		if (ClaimFactory::get($this->name) !== null) {
			throw new RuntimeException('&cClaim already exists.');
		}

		if ($this->firstPosition === null || $this->secondPosition === null) {
			throw new RuntimeException('&cPositions not placed.');
		}

		if (!$this->checkZoneClear()) {
			throw new RuntimeException('&cYou can\'t claim zone already claimed.');
		}

		if ($this->type === Claim::KOTH) {
			$object = KothFactory::get($this->name);

			if ($object === null) {
				$this->session->stopClaimCreatorHandler();
				throw new RuntimeException('&cKoth not exits.');
			}

		} else {
			$object = FactionFactory::get($this->name);

			if ($object === null) {
				$this->session->stopClaimCreatorHandler();
				throw new RuntimeException('&cFaction not exits.');
			}
		}

		if ($this->type === Claim::FACTION && $object instanceof Faction) {
			$balance = $object->getBalance();
			$price = $this->zonePrice();

			if ($balance < $price) {
				throw new RuntimeException('&cYour faction don\'t have money for this claim.');
			}
			$object->setBalance($balance - $price);
		}
		$claim = ClaimFactory::create($this->name, $this->firstPosition, $this->secondPosition, $this->secondPosition->getWorld(), $this->type);
		$object->setClaim($claim);

		$this->finish($this->session->getPlayer());
		$this->session->getPlayer()->sendMessage(TextFormat::colorize('&aYou have claimed successfully.'));
	}

	private function checkZoneClear() : bool {
		$firstPosition = $this->firstPosition;
		$secondPosition = $this->secondPosition;

		if ($firstPosition !== null && $secondPosition !== null) {
			$increment = 2;

			if ($this->type !== Claim::FACTION) {
				$increment = 0;
			}
			$minX = min($firstPosition->getX(), $secondPosition->getX()) - $increment;
			$maxX = max($firstPosition->getX(), $secondPosition->getX()) + $increment;

			$minZ = min($firstPosition->getZ(), $secondPosition->getZ()) - $increment;
			$maxZ = max($firstPosition->getZ(), $secondPosition->getZ()) + $increment;

			for ($x = $minX; $x <= $maxX; $x++) {
				for ($z = $minZ; $z <= $maxZ; $z++) {
					$pos = new Position($x, 0, $z, $firstPosition->getWorld());

					if (ClaimHandler::insideClaim($pos) !== null) {
						return false;
					}
				}
			}
		}
		return true;
	}

	private function zonePrice() : int {
		$firstPosition = $this->firstPosition;
		$secondPosition = $this->secondPosition;

		$vector = new Vector2(
			abs($firstPosition->getX() - $secondPosition->getX()) + 1,
			abs($firstPosition->getY() - $secondPosition->getY()) + 1
		);
		return intval($vector->x * $vector->y * (int) HCF::getInstance()->getConfig()->get('faction.claim-price-per-block', 10));
	}

	public function finish(Player $player) : void {
		$this->deleteCorner($player);
		$this->deleteCorner($player, false);

		$item = VanillaItems::GOLDEN_HOE();
		$item->setCustomName(TextFormat::colorize('&r&6Claim Tool'));
		$item->getNamedTag()->setString('claim_tool', $player->getXuid());

		$player->getInventory()->remove($item);

		$this->session->stopClaimCreatorHandler();
	}

	private function deleteCorner(Player $player, bool $isFirstPosition = true) : void {
		$position = $isFirstPosition ? $this->firstPosition : $this->secondPosition;

		if ($position !== null) {
			for ($y = $position->getFloorY(); $y <= 127; $y++) {
				$player->getNetworkSession()->sendDataPacket($this->createFakeBlock(new Position($position->getFloorX(), $y, $position->getFloorZ(), $position->getWorld()), VanillaBlocks::AIR()));
			}
		}
	}

	private function createFakeBlock(Vector3 $position, Block $block) : UpdateBlockPacket {
		$pos = BlockPosition::fromVector3($position);
		$block = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($block->getStateId());
		return UpdateBlockPacket::create($pos, $block, UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL);
	}

	public function handleDropItem(PlayerDropItemEvent $event) : void {
		$item = $event->getItem();
		$player = $event->getPlayer();

		if ($item->getNamedTag()->getTag('claim_tool') !== null) {
			$guid = $item->getNamedTag()->getString('claim_tool');

			if ($guid !== $player->getXuid()) {
				$event->cancel();
			}
		}
	}

	public function handleInteract(PlayerInteractEvent $event) : void {
		$action = $event->getAction();
		$block = $event->getBlock();
		$item = $event->getItem();
		$player = $event->getPlayer();

		$position = $block->getPosition();
		$world = $position->getWorld();

		if ($item->getNamedTag()->getTag('claim_tool') === null) {
			return;
		}

		if ($action === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
			$event->cancel();

			if ($this->secondPosition !== null && $this->secondPosition->getWorld()->getFolderName() !== $world->getFolderName()) {
				$player->sendMessage(TextFormat::colorize('&cInvalid position.'));
				return;
			}

			if ($this->firstPosition !== null) {
				$this->deleteCorner($player);
			}
			$this->firstPosition = $position;

			if (!$this->checkZoneClear()) {
				$this->firstPosition = null;
				$player->sendMessage(TextFormat::colorize('&cYou can\'t claim zone already claimed.'));
				return;
			}
			$player->sendMessage(TextFormat::colorize('&eYou have select first position.'));

			if ($this->type === Claim::FACTION) {
				if ($this->secondPosition !== null) {
					$player->sendMessage(TextFormat::colorize('&aThe claim price is $' . $this->zonePrice() . '. Type in the chat &eaccept &ato accept or &ecancel &ato cancel.'));
				}
				$this->createCorner($player, $position);
			}
		} elseif ($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
			$event->cancel();

			if ($this->firstPosition !== null && $this->firstPosition->getWorld()->getFolderName() !== $world->getFolderName()) {
				$player->sendMessage(TextFormat::colorize('&cInvalid position.'));
				return;
			}

			if ($this->secondPosition !== null) {
				$this->deleteCorner($player, false);
			}
			$this->secondPosition = $position;

			if (!$this->checkZoneClear()) {
				$this->secondPosition = null;
				$player->sendMessage(TextFormat::colorize('&cYou can\'t claim zone already claimed.'));
				return;
			}
			$player->sendMessage(TextFormat::colorize('&eYou have select second position.'));

			if ($this->type === Claim::FACTION) {
				if ($this->firstPosition !== null) {
					$player->sendMessage(TextFormat::colorize('&aThe claim price is $' . $this->zonePrice() . '. Type in the chat &esave &ato accept or &ecancel &ato cancel.'));
				}
				$this->createCorner($player, $position, false);
			}
		}
	}

	private function createCorner(Player $player, Position $position, bool $isFirstPosition = true) : void {
		for ($y = $position->getFloorY(); $y <= 127; $y++) {
			$player->getNetworkSession()->sendDataPacket($this->createFakeBlock(new Position($position->getFloorX(), $y, $position->getFloorZ(), $position->getWorld()), $y % 3 === 0 ? VanillaBlocks::EMERALD() : VanillaBlocks::GLASS()));
		}
	}

	private function getClaimSize() : int {
		if ($this->firstPosition === null || $this->secondPosition === null) {
			return 0;
		}
		return (int) $this->firstPosition->distance($this->secondPosition);
	}

	public function prepare(Player $player) : void {
		$item = VanillaItems::GOLDEN_HOE();
		$item->setCustomName(TextFormat::colorize('&r&6Claim Tool'));
		$item->getNamedTag()->setString('claim_tool', $player->getXuid());

		if (!$player->getInventory()->canAddItem($item)) {
			throw new RuntimeException('Remove one item in your inventory.');
		}
		$player->getInventory()->addItem($item);
	}
}
