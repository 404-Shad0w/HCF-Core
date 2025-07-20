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
use const PHP_EOL;

final class StatsCommand extends Command {

	public function __construct() {
		parent::__construct('stats', 'Use command to player stats');
        $this->setPermission('stats.command.use');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (count($args) < 1) {
			if (!$sender instanceof Player) {
				$sender->sendMessage(TextFormat::colorize('&cUse /stats [player]'));
				return;
			}
			$session = SessionFactory::get($sender);

			if ($session === null) {
				return;
			}
		} else {
			$player = $sender->getServer()->getPlayerByPrefix($args[0]);

			if (!$player instanceof Player) {
				$sender->sendMessage(TextFormat::colorize('&cPlayer is offline.'));
				return;
			}
			$session = SessionFactory::get($player);

			if ($session === null) {
				$sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
				return;
			}
		}
		$message = [
			'&e' . $session->getName() . '\'s stats',
			'&e',
			'&eKills: &c' . $session->getKills(),
			'&eDeaths: &c' . $session->getDeaths(),
			'&eKill streak: &c' . $session->getKillStreak(),
			'&eBest kill streak: &c' . $session->getBestKillStreak(),
			'&eBalance: &c$' . $session->getBalance(),
			'&e&7'
		];
		$sender->sendMessage(TextFormat::colorize(implode(PHP_EOL, $message)));
	}
}
