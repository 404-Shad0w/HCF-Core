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
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function array_filter;
use function array_map;
use function implode;
use const PHP_EOL;

final class FactionHelpCommand extends FactionSubcommand {

	public function __construct(
		private array $subcommands = []
	) {
		parent::__construct('help', 'Use command to help', 'h');
	}

	public function execute(CommandSender $sender, array $args) : void {
		$normalSubcommands = array_filter($this->subcommands, fn(FactionSubcommand $subCommand) => $subCommand->getPermission() === null);
		$adminSubcommands = array_filter($this->subcommands, fn(FactionSubcommand $subCommand) => $subCommand->getPermission() !== null);
		$sender->sendMessage(TextFormat::colorize(
			'&l&aFaction Comands&r' . PHP_EOL .
			implode(PHP_EOL, array_map(fn(FactionSubcommand $subcommand) => ' &g/faction ' . $subcommand->getName() . ($subcommand->getAlias() !== null ? ' [' . $subcommand->getAlias() . ']' : '') . ' &7- ' . $subcommand->getDescription() . '&r', $normalSubcommands)))
		);

		if ($sender->hasPermission('faction.permission')) {
			$sender->sendMessage(TextFormat::colorize('&l&gADMIN FACTION COMMAND&r' . PHP_EOL . implode(PHP_EOL, array_map(fn(FactionSubcommand $subcommand) => ' &g/faction ' . $subcommand->getName() . ($subcommand->getAlias() !== null ? ' [' . $subcommand->getAlias() . ']' : '') . ' &7- ' . $subcommand->getDescription() . '&r', $adminSubcommands))));
		}
	}
}
