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
use function array_filter;
use function array_map;
use function count;
use function implode;
use const PHP_EOL;

final class NearCommand extends Command {

	public function __construct() {
		parent::__construct('near', 'Command for near players');
		$this->setPermission('near.command');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}

		if (!$this->testPermission($sender)) {
			return;
		}
		$nearbyEntities = array_filter($sender->getWorld()->getPlayers(), fn(Player $target) => $target->getId() !== $sender->getId() && $target->getPosition()->distance($sender->getPosition()) <= 100);

		if (count($nearbyEntities) === 0) {
			$sender->sendMessage(TextFormat::colorize('&cNo players.'));
			return;
		}
		$sender->sendMessage(TextFormat::colorize('&cNear players' . PHP_EOL . implode(PHP_EOL, array_map(function (Player $player) use ($sender) {
				return '&f' . $player->getName() . ' &7(' . (int) $sender->getPosition()->distance($player->getPosition()) . 'm)';
			}, $nearbyEntities))));
	}
}
