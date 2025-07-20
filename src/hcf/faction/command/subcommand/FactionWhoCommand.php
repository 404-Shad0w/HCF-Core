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
use hcf\faction\FactionFactory;
use hcf\faction\member\FactionMember;
use hcf\session\SessionFactory;
use hcf\util\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_filter;
use function array_map;
use function count;
use function implode;
use function in_array;
use const PHP_EOL;

class FactionWhoCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('who', 'Use command to faction information');
	}

	public function execute(CommandSender $sender, array $args) : void {
		if (count($args) < 1) {
			if (!$sender instanceof Player) {
				return;
			}
			$session = SessionFactory::get($sender);

			if ($session === null) {
				return;
			}

			if ($session->getFaction() === null) {
				$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction.'));
				return;
			}
			$faction = $session->getFaction();
		} else {
			$player = $sender->getServer()->getPlayerByPrefix($args[0]);

			if ($player instanceof Player) {
				$target = SessionFactory::get($player);

				if ($target === null) {
					$sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
					return;
				}

				if ($target->getFaction() === null) {
					$sender->sendMessage(TextFormat::colorize('&cPlayer don\'t have faction.'));
					return;
				}
				$faction = $target->getFaction();
			} else {
				if (FactionFactory::get($args[0]) === null || in_array($args[0], ['Spawn', 'Nether-Spawn', 'End-Spawn'], true)) {
					$sender->sendMessage(TextFormat::colorize('&cFaction not found.'));
					return;
				}
				$faction = FactionFactory::get($args[0]);
			}
		}
		$leader = array_filter($faction->getMembers(), fn(FactionMember $member) => $member->getRank() === FactionMember::RANK_LEADER);
		$co_leaders = array_filter($faction->getMembers(), fn(FactionMember $member) => $member->getRank() === FactionMember::RANK_COLEADER);
		$officers = array_filter($faction->getMembers(), fn(FactionMember $member) => $member->getRank() === FactionMember::RANK_OFFICER);
		$members = array_filter($faction->getMembers(), fn(FactionMember $member) => $member->getRank() === FactionMember::RANK_MEMBER);

		$message = [
			'&8&f',
			'&9' . $faction->getName() . ' &7[' . count($faction->getOnlineMembers()) . '/' . count($faction->getMembers()) . '] &3- &eHQ: &f' . ($faction->getHome() !== null ? Utils::vectorToString($faction->getHome()->asVector3(), ', ') : 'No set') . '&r',
			' - &6Leader: &f' . implode(', ', array_map(function (FactionMember $member) {
				return ($member->getSession()->isOnline() ? '&a' : '&c') . $member->getSession()->getName() . ' &7[' . $member->getSession()->getKills() . ']';
			}, $leader)) . '&r',
			' - &6Coleaders: &f' . implode(', ', array_map(function (FactionMember $member) {
				return ($member->getSession()->isOnline() ? '&a' : '&c') . $member->getSession()->getName() . ' &7[' . $member->getSession()->getKills() . ']';
			}, $co_leaders)) . '&r',
			' - &6Officers: &f' . implode(', ', array_map(function (FactionMember $member) {
				return ($member->getSession()->isOnline() ? '&a' : '&c') . $member->getSession()->getName() . ' &7[' . $member->getSession()->getKills() . ']';
			}, $officers)) . '&r',
			' - &6Members: &f' . implode(', ', array_map(function (FactionMember $member) {
				return ($member->getSession()->isOnline() ? '&a' : '&c') . $member->getSession()->getName() . ' &7[' . $member->getSession()->getKills() . ']';
			}, $members)) . '&r',
			' - &6Balance: &9$' . $faction->getBalance() . '&r',
			' - &6Deaths Raidable: ' . $faction->getDtrWithColors() . '&r',
			' - &6Regen Cooldown: &9' . ($faction->getRegenCooldown() > 0 ? Utils::timeFormat($faction->getRegenCooldown()) : '00:00:00') . '&r',
			' - &ePoints: &c' . $faction->getPoints() . '&r',
            #' - &eKoTH Captures: &c' . $faction->getKothCaptures(),
			' - &eStrikes: &c' . $faction->getStrikes() . '&r',
			'&7&r'
		];

		$sender->sendMessage(TextFormat::colorize(implode(PHP_EOL, $message)));
	}
}
