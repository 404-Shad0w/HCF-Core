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
use hcf\faction\member\FactionMember;
use hcf\session\SessionFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class FactionUnfocusCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('unfocus', 'Use command to unfocus the faction');
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

		if ($faction->getFocusFaction() === null) {
			$sender->sendMessage(TextFormat::colorize('&cYour faction isn\'t focusing any faction.'));
			return;
		}
		$faction->setFocusFaction(null);
        $session = SessionFactory::get($sender);
        if(($waypoint = $session->getWayPoint()) instanceof WayPoint){
            $waypoint->hideFrom($sender);
        }
		$sender->sendMessage(TextFormat::colorize('&aYour faction no longer focus anyone now.'));
	}
}
