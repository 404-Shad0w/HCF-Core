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
use hcf\faction\Faction;
use hcf\faction\FactionFactory;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function array_chunk;
use function array_filter;
use function array_map;
use function count;
use function implode;
use function in_array;
use function is_numeric;
use const PHP_EOL;

final class FactionListCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('list', 'Use command to faction list.');
	}

	public function execute(CommandSender $sender, array $args) : void {
		$factions = array_filter(FactionFactory::getAll(), fn(Faction $faction) => !in_array($faction->getName(), ['Spawn', 'Nether-Spawn', 'End-Spawn'], true));

		if (count($factions) === 0) {
			$sender->sendMessage(TextFormat::colorize('&cNo factions.'));
			return;
		}
		$chunks = array_chunk($factions, 10);
		$page = 0;

		if (isset($args[0])) {
			if (!is_numeric($args[0])) {
				$sender->sendMessage(TextFormat::colorize('&cNumber invalid.'));
				return;
			}
			$number = (int) $args[0];

			if ($number <= 0 || !isset($chunks[$number - 1])) {
				$sender->sendMessage(TextFormat::colorize('&cPage invalid.'));
				return;
			}
			$page = $number - 1;
		}
		$sender->sendMessage(TextFormat::colorize('&gFaction List &7[' . $page + 1 . '/' . count($chunks) . ']'));
		$sender->sendMessage(TextFormat::colorize(implode(PHP_EOL, array_map(fn(Faction $faction) => '&g' . $faction->getName() . ' &7[' . count($faction->getOnlineMembers()) . '/' . count($faction->getMembers()) . ']', $chunks[$page]))));
	}
}
