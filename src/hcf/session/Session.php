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

namespace hcf\session;

use hcf\waypoint\WayPoint;
use hcf\HCF;
use hcf\util\Utils;
use hcf\claim\Claim;
use hcf\claim\ClaimHandler;
use hcf\disconnect\DisconnectFactory;
use hcf\faction\Faction;
use hcf\faction\FactionFactory;
use hcf\faction\member\FactionMember;
use hcf\kit\class\KitClass;
use hcf\session\data\EconomyData;
use hcf\session\data\PlayerData;
use hcf\session\energy\EnergyTrait;
use hcf\session\handler\HandlerTrait;
use hcf\session\scoreboard\ScoreboardBuilder;
use hcf\session\scoreboard\ScoreboardTrait;
use hcf\session\timer\TimerTrait;
use hcf\timer\TimerFactory;
use JetBrains\PhpStorm\ArrayShape;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\world\Position;
use pocketmine\scheduler\ClosureTask;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\math\Facing;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function array_filter;
use function array_map;
use function count;
use function intval;

final class Session {
	use PlayerData;
	use EconomyData;

	use HandlerTrait;
	use ScoreboardTrait;

	use EnergyTrait;
	use TimerTrait;

	public function __construct(
		private string $xuid,
		private string $rawUuid,
		private string $name,
		private bool $firstConnection = true,
		private ?Claim $currentClaim = null,
		private ?KitClass $kitClass = null,
		private ?Faction $faction = null,
        private ?WayPoint $wayPoint = null,
        private array $previousCombatBlocks = [],
        private array $previousTimerBlocks = []
	) {
         $score = Utils::getScoreFile();             
		$this->setScoreboard(new ScoreboardBuilder($this, $score->get("scoreboard-title")));

		if ($this->firstConnection) {
            $config = HCF::getInstance()->getConfig();
            if ($config->get("kitmap-mode") === false) {
                $this->addTimer('starting_timer', '&aStarting Timer&r&7:&f', 60 * 60);
            }
		}
	}
    
    public function checkTimerWall(Player $p): void{
        $locations = $this->getTimerWallBlocks($p);
        $removeBlocks = $this->previousTimerBlocks;        
        foreach ($locations as $location) {
            if (isset($removeBlocks[$location->__toString()])) {
                unset($removeBlocks[$location->__toString()]);
            }
            $pos = new BlockPosition($location->getFloorX(), $location->getFloorY(), $location->getFloorZ());
            $block = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::GREEN())->getStateId());
            $pk = UpdateBlockPacket::create($pos, $block, UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL);
            $p->getNetworkSession()->sendDataPacket($pk);
        }
        foreach ($removeBlocks as $location) {
            $location = $location->floor();
            $block = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($p->getWorld()->getBlock($location)->getStateId());
            $pk = UpdateBlockPacket::create(new BlockPosition($location->getFloorX(), $location->getFloorY(), $location->getFloorZ()), $block, UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL);
            $p->getNetworkSession()->sendDataPacket($pk);
        }
        $this->previousTimerBlocks = $locations;
    }
    
    private function getTimerWallBlocks(Player $p): array{
        $locations = [];
        if($this->getTimer('pvp_timer') === null) return $locations;
        $radius = 3;
        $l = $p->getPosition();
        $loc1 = clone $l->add($radius, 0, $radius);
        $loc2 = clone $l->subtract($radius, 0, $radius);
        $maxBlockX = max($loc1->getFloorX(), $loc2->getFloorX());
        $minBlockX = min($loc1->getFloorX(), $loc2->getFloorX());
        $maxBlockZ = max($loc1->getFloorZ(), $loc2->getFloorZ());
        $minBlockZ = min($loc1->getFloorZ(), $loc2->getFloorZ());
        for($x = $minBlockX; $x <= $maxBlockX; $x++){
            for($z = $minBlockZ; $z <= $maxBlockZ; $z++){
                $location = new Position($x, $l->getFloorY(), $z, $l->getWorld());
                $insideClaim = ClaimHandler::insideClaim($location);
                if ($insideClaim !== null) {
                    if ($insideClaim->getType() === Claim::FACTION) continue;
                }
                if(!$this->isTimerSurrounding($location)) continue;
                for($i = 0; $i <= $radius; $i++){
                    $loc = clone $location;
                    $new = Position::fromObject($loc->withComponents($loc->getX(), $loc->getY() + $i, $loc->getZ()), $loc->getWorld());
                    if($new->getWorld()->getBlock($new)->getTypeId() != BlockTypeIds::AIR) continue;
                    $locations[$new->__toString()] = $new;
                }
            }
        }
        return $locations;
    }
    
    public function isTimerSurrounding(Position $pos): bool{
        foreach (Facing::ALL as $i) {
            if (ClaimHandler::insideClaim($pos->getSide($i))?->getType() === Claim::FACTION) {
                return true;
            }
        }
        return false;
    }
    
    public function checkCombatWall(Player $p): void{
        $locations = $this->getCombatWallBlocks($p);
        $removeBlocks = $this->previousCombatBlocks;
        
        foreach ($locations as $location) {
            if (isset($removeBlocks[$location->__toString()])) {
                unset($removeBlocks[$location->__toString()]);
            }
            $pos = new BlockPosition($location->getFloorX(), $location->getFloorY(), $location->getFloorZ());
            $block = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED())->getStateId());
            $pk = UpdateBlockPacket::create($pos, $block, UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL);
            $p->getNetworkSession()->sendDataPacket($pk);
        }
        foreach ($removeBlocks as $location) {
            $location = $location->floor();
            $block = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($p->getWorld()->getBlock($location)->getStateId());
            $pk = UpdateBlockPacket::create(new BlockPosition($location->getFloorX(), $location->getFloorY(), $location->getFloorZ()), $block, UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL);
            $p->getNetworkSession()->sendDataPacket($pk);
        }
        $this->previousCombatBlocks = $locations;
    }
    
    private function getCombatWallBlocks(Player $p): array{
        $locations = [];
        if($this->getTimer('spawn_tag') === null) return $locations;
        $radius = 4;
        $l = $p->getPosition();
        $loc1 = clone $l->add($radius, 0, $radius);
        $loc2 = clone $l->subtract($radius, 0, $radius);
        $maxBlockX = max($loc1->getFloorX(), $loc2->getFloorX());
        $minBlockX = min($loc1->getFloorX(), $loc2->getFloorX());
        $maxBlockZ = max($loc1->getFloorZ(), $loc2->getFloorZ());
        $minBlockZ = min($loc1->getFloorZ(), $loc2->getFloorZ());
        for($x = $minBlockX; $x <= $maxBlockX; $x++){
            for($z = $minBlockZ; $z <= $maxBlockZ; $z++){
                $location = new Position($x, $l->getFloorY(), $z, $l->getWorld());
                $insideClaim = ClaimHandler::insideClaim($location);
                if ($insideClaim !== null) {
                    if ($insideClaim->getType() === Claim::SPAWN) continue;
                }
                if(!$this->isPvpSurrounding($location)) continue;
                for($i = 0; $i <= $radius; $i++){
                    $loc = clone $location;
                    $new = Position::fromObject($loc->withComponents($loc->getX(), $loc->getY() + $i, $loc->getZ()), $loc->getWorld());
                    if($new->getWorld()->getBlock($new)->getTypeId() != BlockTypeIds::AIR) continue;
                    $locations[$new->__toString()] = $new;
                }
            }
        }
        return $locations;
    }
    
    public function isPvpSurrounding(Position $pos): bool{
        foreach (Facing::ALL as $i) {
            if (ClaimHandler::insideClaim($pos->getSide($i))?->getType() === Claim::SPAWN) {
                return true;
            }
        }
        return false;
    }
    
    public function getWayPoint(): ?WayPoint {
        return $this->wayPoint;
    }
    
    public function setWayPoint(WayPoint $wayPoint = null): void {
        $this->wayPoint = $wayPoint;
    }

	public function getXuid() : string {
		return $this->xuid;
	}

	public function getRawUuid() : string {
		return $this->rawUuid;
	}

	public function getCurrentClaim() : ?Claim {
		return $this->currentClaim;
	}

	public function getKitClass() : ?KitClass {
		return $this->kitClass;
	}

	public function isOnline() : bool {
		return $this->getPlayer() !== null;
	}

	public function getPlayer() : ?Player {
		return Server::getInstance()->getPlayerByRawUUID($this->rawUuid);
	}

	public function setRawUuid(string $rawUuid) : void {
		$this->rawUuid = $rawUuid;
	}

	public function setCurrentClaim(?Claim $claim) : void {
		$this->currentClaim = $claim;
	}

	public function setKitClass(?KitClass $kitClass) : void {
		$this->kitClass = $kitClass;
	}

	public function setFaction(?Faction $faction) : void {
		$this->faction = $faction;
	}
    
    public function timerUpdate(): void {
        $this->updateEnergies();
        $this->updateTimers();
    }

	public function update() : void {
		$faction = $this->getFaction();
		$player = $this->getPlayer();

		$this->scoreboard->update();
		$this->updateTimers();
        $this->updateEnergies();

        if ($this->isOnline()) {
            $currentClaim = $this->getCurrentClaim();
            if ($currentClaim !== null) {if ($currentClaim->getType() === Claim::SPAWN) {if (!$player->getEffects()->has(VanillaEffects::NIGHT_VISION())) {$player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 20 * 5, 0));}}}
        	if ($faction !== null) {
                $player->setScoreTag(TextFormat::colorize('&6[&c' . $faction->getName() . ' &6| ' . $faction->getDtrWithColors() . ' &6]' . TextFormat::EOL . '&c' . round($player->getHealth() + $player->getAbsorption(), 1) . '  &7| &c' . round($player->getHungerManager()->getFood(), 1) . ' '));
            } else {
            	$player->setScoreTag(TextFormat::colorize('&c' . round($player->getHealth() + $player->getAbsorption(), 1) . '  &7| &c' . round($player->getHungerManager()->getFood(), 1) . ' '));
            }
        }

		if ($faction !== null && $this->isOnline()) {
			$members = array_filter($player->getViewers(), function (Player $target) use ($faction) : bool {
				$session = SessionFactory::get($target);
				return $faction->equals($session?->getFaction());
			});

			if (count($members) === 0) {
				return;
			}
			$metadata = clone $player->getNetworkProperties();
			$metadata->setString(EntityMetadataProperties::NAMETAG, TextFormat::colorize('&a' . $player->getName()));

			if ($player->getEffects()->has(VanillaEffects::INVISIBILITY())) {
				$metadata->setGenericFlag(EntityMetadataFlags::INVISIBLE, false);
				$metadata->setGenericFlag(EntityMetadataFlags::CAN_SHOW_NAMETAG, true);
			}
			$player->getNetworkSession()->getEntityEventBroadcaster()->syncActorData(array_map(fn(Player $target) => $target->getNetworkSession(), $members), $player, $metadata->getAll());
		}
	}

	public function join() : void {
		$player = $this->getPlayer();
		$faction = $this->getFaction();

		if ($player === null) {
			return;
		}
		$this->scoreboard->spawn();
        DisconnectFactory::get($this)?->join();

		$pk = GameRulesChangedPacket::create(['showCoordinates' => new BoolGameRule(true, false)]);
		$player->getNetworkSession()->sendDataPacket($pk);
        
		$player->setNameTag(TextFormat::colorize('&c' . $player->getName()));
		//$player->setScoreTag(TextFormat::colorize('&c' . $player->getHealth()));
		/*if ($faction !== null) {
			$player->setScoreTag(TextFormat::colorize('&6[&c' . $faction->getName() . ' &6| ' . $faction->getDtrWithColors() . '&6]' . TextFormat::EOL . '&c' . round($player->getHealth(), 2) . ' '));
		}*/
	}
    
    public function getScoreboard(): ?ScoreboardBuilder {
        return $this->scoreboard;
    }

	public function getFaction() : ?Faction {
		return $this->faction;
	}

	public function getName() : string {
		return $this->name;
	}

	public function quit() : void {
		$player = $this->getPlayer();

		if ($player !== null) {
			$this->getClaimCreatorHandler()?->finish($player);
			$currentClaim = $this->getCurrentClaim();

			if (!$this->hasLogout() && ($currentClaim === null || $currentClaim->getType() !== Claim::SPAWN)) {
				if (TimerFactory::get('sotw') !== null && !TimerFactory::get('sotw')->isEnabled()) {
					if (!$player->hasPermission('god.permission') || !$player->getGamemode()->equals(GameMode::CREATIVE())) {
                        if($player->getWorld()->getFolderName() !== "deathban") {
                            DisconnectFactory::create($this, $player->getLocation(), $player->getArmorInventory()->getContents(), $player->getInventory()->getContents());
                        }
					}
				}
			}
		}
		$this->stopClaimCreatorHandler();
		$this->stopKitHandler();

		$this->logout = false;
		$this->firstConnection = false;
	}

	public static function deserializeData(string $xuid, array $data) : Session {
		$session = new Session($xuid, '', $data['name'], false);
        $session->setBalance((int) $data['balance']);
        $session->setKillKey((int) $data['killKey']);
		$session->setKills((int) $data['kills']);
		$session->setKillStreak((int) $data['kill-streak']);
		$session->setBestKillStreak((int) $data['best-kill-streak']);
		$session->setDeaths((int) $data['deaths']);

		if ($data['faction'] !== null) {
			$faction = FactionFactory::get($data['faction']);

			if ($faction !== null) {
				$session->setFaction($faction);
				$faction->addMember($session, intval($data['faction-rank'] ?? FactionMember::RANK_MEMBER));
			}
		}

		foreach ($data['timers'] as $name => $timer) {
			$session->addTimer($name, $timer['format'], (int) $timer['time'], (bool) $timer['paused'], (bool) $timer['visible']);
		}
		return $session;
	}

	#[ArrayShape(['name' => "string", 'balance' => "int", 'kills' => "int", 'deaths' => "int", 'kill-streak' => "int", 'best-kill-streak' => "int", 'faction' => "null|string", 'timers' => "array"])] public function serializeData() : array {
		$faction = $this->faction;
		$data = ['name' => $this->name, 'balance' => $this->balance, 'killKey' => $this->killKey, 'kills' => $this->kills, 'deaths' => $this->deaths, 'kill-streak' => $this->killStreak, 'best-kill-streak' => $this->bestKillStreak, 'faction' => $faction?->getName(), 'timers' => []];

		if ($faction !== null) {
			$data['faction-rank'] = $faction->getMember($this)?->getRank();
		}

		foreach ($this->getTimers() as $timerName => $timer) {
			$data['timers'][$timerName] = ['format' => $timer->getDefaultFormat(), 'time' => $timer->getTime(), 'paused' => $timer->isPaused(), 'visible' => $timer->isVisible()];
		}
		return $data;
	}
}
