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
use function is_numeric;

final class PayCommand extends Command {

	public function __construct() {
		parent::__construct('pay', 'Use command to pay');
        $this->setPermission('pay.command.use');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}
		$session = SessionFactory::get($sender);

		if ($session === null) {
			return;
		}

		if (count($args) < 2) {
			$sender->sendMessage(TextFormat::colorize('&cUse /pay [player] [amount]'));
			return;
		}
		$player = $sender->getServer()->getPlayerByPrefix($args[0]);
		$money = $args[1];

		if (!$player instanceof Player) {
			$sender->sendMessage(TextFormat::colorize('&cPlayer is offline.'));
			return;
		}
		$target = SessionFactory::get($player);

		if ($target === null) {
			$sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
			return;
		}
		$balance = $session->getBalance();

		if (!is_numeric($money) || $money <= 0) {
			$sender->sendMessage(TextFormat::colorize('&cInvalid amount.'));
			return;
		}
		$money = (int) $money;

		if ($balance < $money) {
			$sender->sendMessage(TextFormat::colorize('&cAmount you entered exceeds your balance.'));
			return;
		}
		$session->decreaseBalance($money);
		$target->increaseBalance($money);

		$player->sendMessage(TextFormat::colorize('&aYou received $' . $money . ' from ' . $sender->getName()));
		$sender->sendMessage(TextFormat::colorize('&aYou have sent $' . $money . ' to ' . $player->getName()));
	}
}
