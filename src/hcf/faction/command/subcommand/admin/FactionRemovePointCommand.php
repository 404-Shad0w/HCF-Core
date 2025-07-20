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

namespace hcf\faction\command\subcommand\admin;

use hcf\faction\command\FactionSubcommand;
use hcf\faction\FactionFactory;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function count;
use function is_numeric;

final class FactionRemovePointCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('rempoint', 'Use subcommand to remove points', null, 'faction.rempoint.command');
	}

	public function execute(CommandSender $sender, array $args) : void {
		if (count($args) < 2) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction rempoint [faction] [points]'));
			return;
		}
		$faction = FactionFactory::get($args[0]);

		if ($faction === null) {
			$sender->sendMessage(TextFormat::colorize('&cFaction not exists.'));
			return;
		}

		if (!is_numeric($args[1])) {
			$sender->sendMessage(TextFormat::colorize('&cInvalid number.'));
			return;
		}
		$points = (int) $args[1];

		if ($points <= 0) {
			$sender->sendMessage(TextFormat::colorize('&cNumber is less than or equal to 0'));
			return;
		}
		$faction->setPoints($faction->getPoints() - $points);
		$sender->sendMessage(TextFormat::colorize('&aYou have updated points of ' . $faction->getName() . ' faction.'));
	}
}
