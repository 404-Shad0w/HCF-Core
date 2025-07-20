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

final class FactionRemoveStrikeCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('remstrike', 'Use subcommand to remove strike', null, 'faction.remstrike.command');
	}

	public function execute(CommandSender $sender, array $args) : void {
		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction remstrike [faction]'));
			return;
		}
		$faction = FactionFactory::get($args[0]);

		if ($faction === null) {
			$sender->sendMessage(TextFormat::colorize('&cFaction not exists.'));
			return;
		}
		$strikes = $faction->getStrikes();

		if ($strikes <= 0) {
			$sender->sendMessage(TextFormat::colorize('&cFaction no has strikes.'));
			return;
		}
		$faction->setStrikes($faction->getStrikes() - 1);
		$sender->sendMessage(TextFormat::colorize('&cYou have removed strike of ' . $faction->getName() . ' faction.'));
	}
}
