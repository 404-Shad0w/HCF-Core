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

namespace hcf\command\admin;

use hcf\session\SessionFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class OwnerModeCommand extends Command {

	public function __construct() {
		parent::__construct('god', 'Command to activate Owner Mode');
		$this->setPermission('ownermode.command.use');
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

		if ($session->hasOwnerMode()) {
			$sender->sendMessage(TextFormat::colorize('&cOwner Mode has been disabled!'));
			$session->setOwnerMode(false);
			return;
		}
		$sender->sendMessage(TextFormat::colorize('&aOwner Mode has been enabled!'));
		$session->setOwnerMode();
	}
}
