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
use function is_numeric;

final class FactionWithdrawCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('withdraw', 'Use command to withdraw money', 'w');
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

		if ($member->getRank() < FactionMember::RANK_COLEADER) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have permission faction for use this command.'));
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction withdraw [money|all]'));
			return;
		}
		$factionBalance = $faction->getBalance();
		$money = $args[0];

		if ($money === 'all') {
			if ($factionBalance <= 0) {
				$sender->sendMessage(TextFormat::colorize('&cFaction doesn\'t has money.'));
				return;
			}
			$faction->setBalance(0);
			$session->increaseBalance($factionBalance);
			return;
		}

		if (!is_numeric($money) || $money <= 0) {
			$sender->sendMessage(TextFormat::colorize('&cInvalid amount.'));
			return;
		}
		$money = (int) $money;

		if ($factionBalance < $money) {
			$sender->sendMessage(TextFormat::colorize('&cAmount you entered exceeds your faction balance.'));
			return;
		}
		$faction->setBalance($factionBalance - $money);

		$session->increaseBalance($money);
		$sender->sendMessage(TextFormat::colorize('&aYour new balance is ' . $session->getBalance() . '$'));
	}
}
