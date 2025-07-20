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

final class FactionPromoteCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('promote', 'Use command to promote a member.');
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

		if ($session->getFaction() === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction.'));
			return;
		}
		$senderMember = $faction->getMember($session);

		if ($senderMember === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction.'));
			return;
		}

		if ($senderMember->getRank() < FactionMember::RANK_COLEADER) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have permission to kick.'));
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /f kick [player]'));
			return;
		}
		$player = $sender->getServer()->getPlayerByPrefix($args[0]);
		$member = null;

		if ($player instanceof Player) {
			if ($player->getId() === $sender->getId()) {
				$sender->sendMessage(TextFormat::colorize('&cInvalid player.'));
				return;
			}
			$target = SessionFactory::get($player);

			if ($target === null || !$faction->equals($target->getFaction())) {
				$sender->sendMessage(TextFormat::colorize('&cMember not found.'));
				return;
			}
			$member = $faction->getMember($target);
		} else {
			foreach ($faction->getMembers() as $m) {
				if ($m->getSession()->getName() === $args[0]) {
					$member = $m;
					break;
				}
			}
		}

		if ($member === null) {
			$sender->sendMessage(TextFormat::colorize('&cMember not found.'));
			return;
		}
		$nextRank = $member->getRank() + 1;

		if ($senderMember->getRank() <= $nextRank) {
			$sender->sendMessage(TextFormat::colorize('&cYou cannot promote a member to same or a higher rank.'));
			return;
		}
		$rankName = match ($nextRank) {
			FactionMember::RANK_COLEADER => 'co-leader',
			FactionMember::RANK_OFFICER => 'officer',
			default => 'Error Rank'
		};
		$member->setRank($nextRank);
		$member->getSession()->getPlayer()?->sendMessage(TextFormat::colorize('&aYou have been promoted to ' . $rankName));

		$faction->announce('&e[Faction] Member ' . $member->getSession()->getName() . ' has been promote to ' . $rankName);

		$sender->sendMessage(TextFormat::colorize('&aYou have been promote a member to ' . $rankName));
	}
}
