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

use hcf\claim\Claim;
use hcf\faction\command\FactionSubcommand;
use hcf\HCF;
use hcf\session\SessionFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

final class FactionHomeCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('home', 'Use command to teleport to your faction.');
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
        
		$faction = $session->getFaction();

		if ($faction === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction.'));
			return;
		}
		$factionHome = $faction->getHome();

		if ($factionHome === null) {
			$sender->sendMessage(TextFormat::colorize('&cYour faction has not home.'));
			return;
		}

		if ($session->getTimer('faction_home') !== null) {
			$sender->sendMessage(TextFormat::colorize('&cYou have already teleport to your faction home.'));
			return;
		}
		$currentClaim = $session->getCurrentClaim();

		if ($currentClaim !== null && $currentClaim->getType() === Claim::SPAWN) {
			$sender->teleport($factionHome);
			return;
		}
		$session->addTimer('faction_home', '&l&2Home&r&7:', (int) HCF::getInstance()->getConfig()->get('faction.home-cooldown', 15));
        $sender->getWorld()->addSound($sender->getPosition()->asVector3(), new XpCollectSound(), [$sender]);

		$position = $sender->getPosition();
		$handler = HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use (&$handler, &$sender, &$session, &$position) : void {
			if (!$sender->isOnline() || $session->getFaction() === null || $session->getFaction()->getHome() === null || $position->distance($sender->getPosition()) > 2 || $session->getTimer('spawn_tag') !== null) {
				$session->removeTimer('faction_home');
				$handler->cancel();
				return;
			}

			if ($session->getTimer('faction_home') === null) {
				$sender->teleport($session->getFaction()->getHome());
				$handler->cancel();
			}
		}), 20);
	}
}
