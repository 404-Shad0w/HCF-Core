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

namespace hcf\elevator;

use hcf\claim\{Claim, ClaimFactory};
use hcf\faction\member\FactionMember;
use hcf\faction\FactionFactory;
use hcf\session\SessionFactory;
use pocketmine\block\BaseSign;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Sign;
use pocketmine\block\utils\SignText;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;
use function strtolower;

final class ElevatorHandler implements Listener {
	
	public function onBlockBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $subclaimResult = $this->isSubclaim($block->getPosition());
        if ($subclaimResult->isSubclaim() && !$subclaimResult->isPlayerAllowed($player)) {
            $event->cancel();
            $player->sendMessage(TextFormat::colorize('&cThe subclaim you are trying to open is not owned by you'));
        }
        if ($block instanceof BaseSign && $block->getText()->getLine(0) === TextFormat::colorize('&2[SubClaim]') && $block->getText()->getLine(1) !== $player->getName()) {
            $event->cancel();
            $player->sendMessage(TextFormat::colorize('&cThe subclaim you are trying to open is not owned by you'));
        }
    }
	
	public function onPlayerInteract(PlayerInteractEvent $event): void
    {
    	$action = $event->getAction();
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $position = $block->getPosition();
        $claim = self::insideClaim($block->getPosition());
        $session = SessionFactory::get($player);
        $faction = $session->getFaction();
        
        if ($action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
			return;
		}
        
        if ($claim === null) return;
        
        if ($faction === null) {
			return;
		}
		$member = $faction->getMember($session);
		
		if ($member === null) {
			return;
		}
		
		if ($member->getRank() === FactionMember::RANK_LEADER) {
			return;
        }
        
        if ($member->getRank() === FactionMember::RANK_COLEADER) {
			return;
        }
        $targetFaction = FactionFactory::get($claim->getDefaultName()); 
        
        if ($targetFaction !== null && $targetFaction->isRaidable()) return;
        
        $subclaimResult = $this->isSubclaim($block->getPosition());
        if ($subclaimResult->isSubclaim() && !$subclaimResult->isPlayerAllowed($player)) {
            $event->cancel();
            $player->sendMessage(TextFormat::colorize('&cThe subclaim you are trying to open is not owned by you'));
        }
    }
    
    public static function insideClaim(Position $position) : ?Claim {
		$claim = array_values(array_filter(ClaimFactory::getAll(), fn(Claim $claim) => $claim->inside($position)));
		return $claim[0] ?? null;
	}
    
    public function handleSubclaimChange(SignChangeEvent $event) : void {
        $text = $event->getNewText();
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);
        $faction = $session->getFaction();
        
        if (strtolower($text->getLine(0)) === '[subclaim]') {
            if ($faction !== null) {
                $event->setNewText(new SignText([TextFormat::colorize('&2[SubClaim]'), TextFormat::colorize('&8' . $player->getName())]));
            } else {
                $event->setNewText(new SignText([TextFormat::colorize('&c[SubClaim]'), TextFormat::colorize('&7You must have a Faction')]));
            }
        }
    }

	public function handleChange(SignChangeEvent $event) : void {
		$text = $event->getNewText();

		if (strtolower($text->getLine(0)) === '[elevator]') {
			if (strtolower($text->getLine(1)) === 'up') {
				$event->setNewText(new SignText([TextFormat::colorize('&e[Elevator]'), TextFormat::colorize('&7up')]));
			} elseif (strtolower($text->getLine(1)) === 'down') {
				$event->setNewText(new SignText([TextFormat::colorize('&e[Elevator]'), TextFormat::colorize('&7down')]));
			}
		}
	}

	public function handleInteract(PlayerInteractEvent $event) : void {
		$action = $event->getAction();
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$tile = $player->getWorld()->getTile($block->getPosition());

		if (!$tile instanceof Sign) {
			return;
		}
		$lines = $tile->getText()->getLines();

		if ($action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
			return;
		}
        
        $event->cancel();

		if ($lines[0] !== TextFormat::colorize('&e[Elevator]')) {
			return;
		}

		if ($lines[1] === TextFormat::colorize('&7up')) {
			$this->upTeleport($player, $block->getPosition());
		} elseif ($lines[1] === TextFormat::colorize('&7down')) {
			$this->downTeleport($player, $block->getPosition());
		}
	}
    
    private function isSubclaim(Position $position): SubclaimResult {
        $chest = $position->getWorld()->getTile($position->asVector3());
        if ($chest instanceof Chest) {
            $result = $this->chestSubclaim($chest);
            if ($result->isSubclaim()) {
                return $result;
            }
            
            if ($chest->isPaired()) {
                $result = $this->chestSubclaim($chest->getPair());
                if ($result->isSubclaim()) {
                    return $result;
                }
            }
        }
        return new SubclaimResult(false, []);
    }
    
    private function chestSubclaim(Chest $chest): SubclaimResult {
        for ($face = 2; $face <= 5; $face++) {
            $sign = $chest->getPosition()->getWorld()->getTile($chest->getBlock()->getSide($face)->getPosition());
            if ($sign instanceof Sign) {
                if ($sign->getText()->getLine(0) === TextFormat::colorize('&2[SubClaim]')) {
                    $lines = [$sign->getText()->getLine(1), $sign->getText()->getLine(2), $sign->getText()->getLine(3)];
                    return new SubclaimResult(
                        true,
                        array_filter($lines, function (string $line) {
                            return $line !== "";
                        })
                    );
                }
            }
        }
        return new SubclaimResult(false, []);
    }

	private function upTeleport(Player $player, Position $position) : void {
		for ($y = $position->getFloorY() + 1; $y <= World::Y_MAX; $y++) {
			if (
				empty($position->getWorld()->getBlockAt($position->getFloorX(), $y, $position->getFloorZ())->getCollisionBoxes()) &&
				empty($position->getWorld()->getBlockAt($position->getFloorX(), $y + 1, $position->getFloorZ())->getCollisionBoxes()) &&
				!empty($position->getWorld()->getBlockAt($position->getFloorX(), $y - 1, $position->getFloorZ())->getCollisionBoxes())
			) {
				$pos = new Vector3($position->getFloorX() + 0.5, $y, $position->getFloorZ() + 0.5);
				$player->teleport($pos, $player->getLocation()->getYaw(), $player->getLocation()->getPitch());
				break;
			}
		}
	}

	private function downTeleport(Player $player, Position $position) : void {
		for ($y = $position->getFloorY() - 1; $y >= World::Y_MIN; $y--) {
			if (
				empty($position->getWorld()->getBlockAt($position->getFloorX(), $y, $position->getFloorZ())->getCollisionBoxes()) &&
				empty($position->getWorld()->getBlockAt($position->getFloorX(), $y + 1, $position->getFloorZ())->getCollisionBoxes()) &&
				!empty($position->getWorld()->getBlockAt($position->getFloorX(), $y - 1, $position->getFloorZ())->getCollisionBoxes())
			) {
				$pos = new Vector3($position->getFloorX() + 0.5, $y, $position->getFloorZ() + 0.5);
				$player->teleport($pos, $player->getLocation()->getYaw(), $player->getLocation()->getPitch());
				break;
			}
		}
	}
}
