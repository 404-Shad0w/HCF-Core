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
use function strtolower;

final class PvPCommand extends Command {

	public function __construct() {
		parent::__construct('pvp', 'Command for pvp');
        $this->setPermission('pvp.command.use');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}
		$session = SessionFactory::get($sender);

		if ($session === null) {
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /pvp enable'));
			return;
		}

		switch (strtolower($args[0])) {
			case 'enable':
				if ($session->getTimer('pvp_timer') === null && $session->getTimer('starting_timer') === null) {
					$sender->sendMessage(TextFormat::colorize('&cYou don\'t have pvp timer or starting timer.'));
					return;
				}

				if ($session->getTimer('starting_timer') !== null) {
					$session->removeTimer('starting_timer');
				}

				if ($session->getTimer('pvp_timer') !== null) {
					$session->removeTimer('pvp_timer');
				}
				$sender->sendMessage(TextFormat::colorize('&aYou successfully enabled your pvp.'));
				break;

			case 'force':
				if (!$this->testPermission($sender, 'pvp.command')) {
					return;
				}

				if (count($args) < 2) {
					$sender->sendMessage(TextFormat::colorize('&cUse /pvp force [player]'));
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

				if ($target->getTimer('pvp_timer') === null && $target->getTimer('starting_timer') === null) {
					$sender->sendMessage(TextFormat::colorize('&c' . $player->getName() . ' doesn\'t has pvp timer or starting timer.'));
					return;
				}

				if ($target->getTimer('pvp_timer') !== null) {
					$target->removeTimer('pvp_timer');
				}

				if ($target->getTimer('starting_timer') !== null) {
					$target->removeTimer('starting_timer');
				}
				$sender->sendMessage(TextFormat::colorize('&a' . $player->getName() . ' now has pvp.'));
				$player->sendMessage(TextFormat::colorize('&a' . $sender->getName() . ' has been enabled your pvp.'));
				break;

			default:
				$sender->sendMessage(TextFormat::colorize('&cUse /pvp enable'));
				break;
		}
	}
}
