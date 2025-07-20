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

namespace hcf\command;

use hcf\session\SessionFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function implode;
use function intval;
use function is_numeric;
use function strtolower;
use const PHP_EOL;

final class BalanceCommand extends Command {

	public function __construct() {
		parent::__construct('balance', 'Use command to balance', null, ['money']);
        $this->setPermission('balance.command.use');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if ($sender instanceof Player) {
			$session = SessionFactory::get($sender);

			if ($session === null) {
				return;
			}

			if (count($args) < 1 || !$this->testPermission($sender, 'balance.command')) {
				$sender->sendMessage(TextFormat::colorize('&eYour balance: &f$' . $session->getBalance()));
				return;
			}
		} else {
			if (count($args) < 1 || !$this->testPermission($sender, 'balance.command')) {
				return;
			}
		}

		switch (strtolower($args[0])) {
			case 'help':
			case '?':
				$messages = [
					'&l&eBALANCE COMMANDS&r',
					'&e/balance addbalance &7- Use command to add money',
					'&e/balance rembalance &7- Use command to remove money',
					'&e/balance setbalance &7- Use command to set new balance'
				];

				$sender->sendMessage(TextFormat::colorize(implode(PHP_EOL, $messages)));
				break;

			case 'addbalance':
			case 'ab':
				if (count($args) < 3) {
					$sender->sendMessage(TextFormat::colorize('&cUse /balance addbalance [player] [money]'));
					return;
				}
				$player = $sender->getServer()->getPlayerByPrefix($args[1]);

				if (!$player instanceof Player) {
					$sender->sendMessage(TextFormat::colorize('&cPlayer offline.'));
					return;
				}
				$target = SessionFactory::get($player);

				if ($target === null) {
					$sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
					return;
				}

				if (!is_numeric($args[2])) {
					$sender->sendMessage(TextFormat::colorize('&cNumber invalid.'));
					return;
				}
				$money = (int) $args[2];

				if ($money <= 0) {
					$sender->sendMessage(TextFormat::colorize('&cMoney is less than or equal to 0.'));
					return;
				}
				$target->increaseBalance(intval($money));
				$sender->sendMessage(TextFormat::colorize('&aYou have been increase money of ' . $player->getName()) . ' to $' . $target->getBalance());
				$player->sendMessage(TextFormat::colorize('&aYour balance has been increased to $' . $target->getBalance()));
				break;

			case 'rembalance':
			case 'rb':
				if (count($args) < 3) {
					$sender->sendMessage(TextFormat::colorize('&cUse /balance rembalance [player] [money]'));
					return;
				}
				$player = $sender->getServer()->getPlayerByPrefix($args[1]);

				if (!$player instanceof Player) {
					$sender->sendMessage(TextFormat::colorize('&cPlayer offline.'));
					return;
				}
				$target = SessionFactory::get($player);

				if ($target === null) {
					$sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
					return;
				}

				if (!is_numeric($args[2])) {
					$sender->sendMessage(TextFormat::colorize('&cNumber invalid.'));
					return;
				}
				$money = (int) $args[2];

				if ($money <= 0) {
					$sender->sendMessage(TextFormat::colorize('&cMoney is less than or equal to 0.'));
					return;
				}
				$balance = $target->getBalance() - $money;

				if ($balance < 0) {
					$sender->sendMessage(TextFormat::colorize('&cNew balance is lees to 0.'));
					return;
				}
				$target->decreaseBalance(intval($money));
				$sender->sendMessage(TextFormat::colorize('&cYou have been decrease money of ' . $player->getName() . ' to $' . $target->getBalance()));
				$player->sendMessage(TextFormat::colorize('&cYour balance has been decreased to $' . $target->getBalance()));
				break;

			case 'setbalance':
			case 'sb':
				if (count($args) < 3) {
					$sender->sendMessage(TextFormat::colorize('&cUse /balance setbalance [player] [money]'));
					return;
				}
				$player = $sender->getServer()->getPlayerByPrefix($args[1]);

				if (!$player instanceof Player) {
					$sender->sendMessage(TextFormat::colorize('&cPlayer offline.'));
					return;
				}
				$target = SessionFactory::get($player);

				if ($target === null) {
					$sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
					return;
				}

				if (!is_numeric($args[2])) {
					$sender->sendMessage(TextFormat::colorize('&cNumber invalid.'));
					return;
				}
				$money = (int) $args[2];

				if ($money < 0) {
					$sender->sendMessage(TextFormat::colorize('&cMoney is less to 0.'));
					return;
				}
				$target->setBalance(intval($money));
				$sender->sendMessage(TextFormat::colorize('&aYou have been set balance of ' . $player->getName() . ' to $' . $target->getBalance()));
				$player->sendMessage(TextFormat::colorize('&aYour balance has been set to $' . $target->getBalance()));
				break;

			default:
				$sender->sendMessage(TextFormat::colorize('&cSubcommand not exists. Use /balance help.'));
				break;
		}
	}
}
