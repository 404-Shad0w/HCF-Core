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
use hcf\faction\event\FactionCreateEvent;
use hcf\faction\FactionFactory;
use hcf\session\SessionFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function ctype_alnum;
use function in_array;
use function str_contains;
use function strlen;

final class FactionCreateCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('create', 'Use command to create new faction');
	}

	public function execute(CommandSender $sender, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}
		$session = SessionFactory::get($sender);

		if ($session === null) {
			return;
		}

		if ($session->getFaction() !== null) {
			$sender->sendMessage(TextFormat::colorize('&cYou already have faction'));
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction create [name]'));
			return;
		}
		$name = $args[0];

		if (strlen($name) < 4 || strlen($name) > 10 || str_contains($name, ' ') || in_array($name, ['Spawn', 'Nether-Spawn', 'End'], true)) {
			$sender->sendMessage(TextFormat::colorize('&cInvalid name.'));
			return;
		}

		if (!ctype_alnum($name)) {
			$sender->sendMessage(TextFormat::colorize('&cUse only numbers and letters.'));
			return;
		}

		if (FactionFactory::get($name) !== null) {
			$sender->sendMessage(TextFormat::colorize('&cFaction already exists.'));
			return;
		}
		$event = new FactionCreateEvent($session);
		$event->call();

		if ($event->isCancelled()) {
			return;
		}
		$faction = FactionFactory::create($name, $session);
		$sender->sendMessage(TextFormat::colorize('&aYou have create the faction!'));
		$sender->setScoreTag(TextFormat::colorize('&6[&c' . $faction->getName() . ' &c' . $faction->getDeathsUntilRaidable() . '&6]'));
		$sender->getServer()->broadcastMessage(TextFormat::colorize('&eFaction &9' . $name . ' &ehas been &acreated &eby &f' . $sender->getName()));
       FactionFactory::saveAll();
	}
}
