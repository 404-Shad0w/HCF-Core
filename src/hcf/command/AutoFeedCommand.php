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

final class AutoFeedCommand extends Command {

	public function __construct() {
		parent::__construct('autofeed', 'Command for autofeed');
		$this->setPermission('autofeed.command.use');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}

		if (!$this->testPermission($sender)) {
			return;
		}
		$session = SessionFactory::get($sender);

		if ($session === null) {
			return;
		}

		if ($session->hasAutoFeed()) {
			$sender->sendMessage(TextFormat::colorize('&cAuto feed has been disabled!'));
			$session->setAutoFeed(false);
			return;
		}
		$sender->sendMessage(TextFormat::colorize('&aAuto feed has been enabled!'));
		$sender->getHungerManager()->setFood($sender->getHungerManager()->getMaxFood());
		$session->setAutoFeed();
	}
}
