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

namespace hcf\faction\command\subcommand;

use hcf\HCF;
use hcf\util\Utils;
use hcf\waypoint\WayPoint;
use hcf\claim\ClaimFactory;
use hcf\faction\command\FactionSubcommand;
use hcf\faction\FactionFactory;
use hcf\faction\member\FactionMember;
use hcf\session\SessionFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\scheduler\ClosureTask;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\utils\TextFormat;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\world\sound\XpCollectSound;
use function count;
use function in_array;

final class FactionCampCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('camp', 'Use the command to go to the faction you want to camp');
	}

	public function execute(CommandSender $sender, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}
		$session = SessionFactory::get($sender);

		if ($session === null) {
			return;
		}
        
        if ($session->getTimer('starting_timer') !== null || $session->getTimer('pvp_timer') !== null) {
            $sender->sendMessage(TextFormat::colorize('&c You cannot use the command with PvP Timer/Starter Timer use /pvp enable'));
            return;
        }
        
        if ($session->getTimer('spawn_tag') !== null) {
            $sender->sendMessage(TextFormat::colorize('&cYou cannot use this command with spawn Tag.'));
            return;
        }
        
		$faction = $session->getFaction();

		if ($faction === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction.'));
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction camp [player|faction]'));
			return;
		}
		$player = $sender->getServer()->getPlayerByPrefix($args[0]);

		if ($player instanceof Player && $player->getId() !== $sender->getId()) {
			$target = SessionFactory::get($player);

			if ($target === null) {
				$sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
				return;
			}

			if ($target->getFaction() === null) {
				$sender->sendMessage(TextFormat::colorize('&cPlayer has no faction.'));
				return;
			}

			if ($faction->equals($target->getFaction())) {
				$sender->sendMessage(TextFormat::colorize('&cYou can\'t focus on members of your faction.'));
				return;
			}
			$campFaction = $target->getFaction();
		} else {
			$target = FactionFactory::get($args[0]);

			if ($target === null || in_array($args[0], ['Spawn', 'Nether-Spawn', 'End-Spawn'], true)) {
				$sender->sendMessage(TextFormat::colorize('&cFaction not exists.'));
				return;
			}

			if ($faction->equals($target)) {
				$sender->sendMessage(TextFormat::colorize('&cYou can\'t focus on your faction.'));
				return;
			}
			$campFaction = $target;
		}
        $claim = ClaimFactory::get($campFaction->getName());
        
        if ($claim === null) {
            $sender->sendMessage(TextFormat::colorize('&cThe faction does not have a claim and you cannot use the command.'));
            return;
        }
        
        if ($session->getTimer('faction_camp') !== null) {
			$sender->sendMessage(TextFormat::colorize('&cYou already in camp.'));
			return;
		}
        
        $session->addTimer('faction_camp', ' &l&5Camp&r&7:', (int) HCF::getInstance()->getConfig()->get('faction.camp-cooldown', 45));
        $sender->getWorld()->addSound($sender->getPosition()->asVector3(), new XpCollectSound(), [$sender]);
        //$sender->getWorld()->addSound($sender->getPosition(), new XpCollectSound());
        
        $position = $sender->getPosition();
        $handler = HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use (&$handler, &$sender, &$session, &$position, &$campFaction, &$claim) : void {
            if (!$sender->isOnline() || $session->getTimer('spawn_tag') !== null || $position->distance($sender->getPosition()) > 2) {
                $session->removeTimer('faction_camp');
				$handler->cancel();
                return;
            }
            
            if ($session->getTimer('faction_camp') === null) {
				$sender->teleport(self::outside($campFaction->getName(), $sender));             
				$handler->cancel();
			}
        }), 20);
        
		$sender->sendMessage(TextFormat::colorize('&aYou are championing the faction  ' . $campFaction->getName()));
	}
    
   public static function outside(string $claimPosition, Player $player) : Position {
        $position = $player->getPosition();
        $world = $player->getWorld();
        $vector = $player->getPosition()->asVector3();
       
        $vx = mt_rand($vector->getFloorX() - 100, $vector->getFloorX() + 100);
		$vz = mt_rand($vector->getFloorZ() - 100, $vector->getFloorZ() + 100);     
       
        $claim = ClaimFactory::get($claimPosition);
        $x = $position->getFloorX();
        $y = $world->getHighestBlockAt($vx, $vz);
        $z = $position->getFloorZ();

        list($xMin, $xMax, $zMin, $zMax) = explode(":", Utils::claimToString($claim, ':'));

        $xMin = intval($xMin);
        $xMax = intval($xMax);
        $zMin = intval($zMin);
        $zMax = intval($zMax);

        if($x >= $xMin && $z >= $zMin){
            $x = $xMin - 1;
        }

        if($x <= $xMax && $z <= $zMax){
            $z = $zMax + 1;
        }

        return new Position($x, $y, $z, $position->getWorld());
    }
}
