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

use hcf\waypoint\WayPoint;
use hcf\faction\command\FactionSubcommand;
use hcf\faction\FactionFactory;
use hcf\faction\member\FactionMember;
use hcf\session\SessionFactory;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function in_array;

final class FactionFocusCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('focus', 'Use command to focus faction');
	}

	public function execute(CommandSender $sender, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}
		$session = SessionFactory::get($sender);

		if ($session === null) {
			return;
		}
		$faction = $session->getFaction();

		if ($faction === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction.'));
			return;
		}
        
        /*if ($faction->getMember($session)->getRank() === FactionMember::RANK_MEMBER) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction rank for use this command.'));
			return;
		}*/

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction focus [player|faction]'));
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
			$focusFaction = $target->getFaction();
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
			$focusFaction = $target;
		}
		$faction->setFocusFaction($focusFaction);
        
        $homeLocation = $focusFaction->getHome();
        
        /*foreach($faction->getOnlineMembers() as $onlinePlayers) {
            $session = $onlinePlayers;
            
            if(($waypoint = $session->getWayPoint()) instanceof WayPoint){
               $waypoint->hideFrom($sender);
            }
            
            if ($homeLocation !== null) {
            $wayPoint = new WayPoint(TextFormat::colorize("&a".$focusFaction->getName()."'s"), $homeLocation, 8);
            $this->displayWayPoint($sender, $wayPoint);
          }
        }*/
        
        if(($waypoint = $session->getWayPoint()) instanceof WayPoint){
               $waypoint->hideFrom($sender);
            }
        
        if ($homeLocation !== null) {
            $wayPoint = new WayPoint(TextFormat::colorize("&a".$focusFaction->getName()."'s"), $homeLocation, 8);
            $this->displayWayPoint($sender, $wayPoint);
        }
        $sender->getWorld()->addSound($sender->getPosition()->asVector3(), new XpCollectSound(), [$sender]);
        
		$sender->sendMessage(TextFormat::colorize('&aYour faction is focusing the faction ' . $focusFaction->getName()));
	}
    
    protected function displayWayPoint(Player $player, ?WayPoint $wayPoint) : void {
        $session = SessionFactory::get($player);
        
        if(!$wayPoint instanceof WayPoint) return; 
        
        $wayPoint->display(true);
        
        if(($oldWayPoint = $session->getWayPoint()) instanceof WayPoint){
            $oldWayPoint->hideFrom($player);
        }
        $session->setWayPoint($wayPoint->showTo($player));
    }
}
