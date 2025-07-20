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

final class FactionRemoveDtrCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('removedtr', 'Use subcommand to remove dtr', null, 'faction.removedtr.command');
	}

	public function execute(CommandSender $sender, array $args) : void {
		if (count($args) < 2) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction removedtr [faction] [dtr]'));
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
        $deathsUntilRaidable = $faction->getDeathsUntilRaidable() - $dtr;
        $faction->setDeathsUntilRaidable($deathsUntilRaidable);
		$sender->sendMessage(TextFormat::colorize('&aYou have updated dtr of ' . $faction->getName() . ' faction.'));
	}
}
