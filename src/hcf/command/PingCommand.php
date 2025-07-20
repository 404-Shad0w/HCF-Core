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

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

final class PingCommand extends Command {

	public function __construct() {
		parent::__construct('ping', 'Command for check ping players');
        $this->setPermission('ping.command.use');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&eYour ping: &f' . $sender->getNetworkSession()->getPing() . 'ms'));
			return;
		}
		$player = $sender->getServer()->getPlayerByPrefix($args[0]);

		if (!$player instanceof Player) {
			$sender->sendMessage(TextFormat::colorize('&cPlayer offline.'));
			return;
		}
		$sender->sendMessage(TextFormat::colorize('&e' . $player->getName() . '\'s ping: &f' . $player->getNetworkSession()->getPing()));
	}
}
