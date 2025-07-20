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

final class FactionAnnounceCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('announce', 'Use the command to give a message to all members of your Faction');
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
        
        if ($faction->getMember($session)->getRank() === FactionMember::RANK_MEMBER) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction rank for use this command.'));
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction announce [message]'));
			return;
		}
        
        $r = implode(" ", $args);
        $session->getFaction()->announce('&4   ANNOUNCE FACTION ');
        $session->getFaction()->announce('&c&f');
        $session->getFaction()->announce(' &7» '.$r);
        $session->getFaction()->announce('&c');
        $sender->getWorld()->addSound($sender->getPosition(), new XpCollectSound());
	}
}
