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
use hcf\faction\event\FactionDepositEvent;
use hcf\session\SessionFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function intval;
use function is_numeric;

final class FactionDepositCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('deposit', 'Use command to deposit money in your faction', 'd');
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

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction deposit [money]'));
			return;
		}
		$balance = $session->getBalance();
		$factionBalance = $faction->getBalance();
		$money = $args[0];

		if ($money === 'all') {
			if ($balance <= 0) {
				$sender->sendMessage(TextFormat::colorize('&cYou don\'t have money.'));
				return;
			}
			$event = new FactionDepositEvent($faction, $session, $factionBalance + $balance);
			$event->call();

			if ($event->isCancelled()) {
				return;
			}
			$faction->setBalance($event->getMoney());

			$session->setBalance(0);
			$sender->sendMessage(TextFormat::colorize('&aThe new balance of the faction is ' . $faction->getBalance() . '$'));
			return;
		}

		if (!is_numeric($money) || $money <= 0) {
			$sender->sendMessage(TextFormat::colorize('&cInvalid amount.'));
			return;
		}
		$money = (int) $money;

		if ($balance < $money) {
			$sender->sendMessage(TextFormat::colorize('&cAmount you entered exceeds your balance.'));
			return;
		}
		$event = new FactionDepositEvent($faction, $session, $factionBalance + $money);
		$event->call();

		if ($event->isCancelled()) {
			return;
		}
		$faction->setBalance($event->getMoney());

		$session->decreaseBalance($money);
		$sender->sendMessage(TextFormat::colorize('&aThe new balance of the faction is ' . $faction->getBalance() . '$'));
	}
}
