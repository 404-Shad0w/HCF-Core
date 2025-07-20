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
use function array_shift;
use function count;
use function implode;
use const PHP_EOL;

final class FactionAddStrikeCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('addstrike', 'Use subcommand to add strike', null, 'faction.addstrike.command');
	}

	public function execute(CommandSender $sender, array $args) : void {
		if (count($args) < 2) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction addstrike [faction] [reason]'));
			return;
		}
		$faction = FactionFactory::get($args[0]);

		if ($faction === null) {
			$sender->sendMessage(TextFormat::colorize('&cFaction not exists.'));
			return;
		}
		array_shift($args);
		$faction->setStrikes($faction->getStrikes() + 1);
		$sender->sendMessage(TextFormat::colorize('&aYou have added strike to ' . $faction->getName() . ' faction.'));

		foreach ($faction->getMembers() as $member) {
			$member->getSession()->getPlayer()?->sendMessage(TextFormat::colorize('&l&cFaction Strike&r' . PHP_EOL . '&cReason: &f' . implode(' ', $args)) . PHP_EOL . '&cStrikes: &f' . $faction->getStrikes());
		}
	}
}
