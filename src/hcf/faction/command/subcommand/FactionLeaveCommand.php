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

use hcf\faction\command\FactionSubcommand;
use hcf\faction\member\FactionMember;
use hcf\session\SessionFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

final class FactionLeaveCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('leave', 'Use command to leave the faction.');
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
		$member = $faction->getMember($session);

		if ($member === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction.'));
			return;
		}

		if ($member->getRank() === FactionMember::RANK_LEADER) {
			$sender->sendMessage(TextFormat::colorize('&cYou are the faction leader.'));
			return;
		}

        if ($faction->getDeathsUntilRaidable() <= 0.00) {
			$sender->sendMessage(TextFormat::colorize('&cYour faction has regen cooldown.'));
			return;
		}
		$faction->removeMember($session);
		$faction->setDeathsUntilRaidable(count($faction->getMembers()) + 0.1);
		$faction->announce('&e[Faction] ' . $sender->getName() . ' left the faction');

		foreach ($faction->getOnlineMembers() as $member) {
			$member->getSession()->getPlayer()?->setScoreTag(TextFormat::colorize('&6[&c' . $faction->getName() . ' &c' . $faction->getDeathsUntilRaidable() . '&6]'));
		}
		$session->setFactionChat(false);

		$sender->setNameTag(TextFormat::colorize('&c' . $sender->getName()));
		$sender->setScoreTag('');
		$sender->sendMessage(TextFormat::colorize('&cYou left your faction.'));
	}
}
