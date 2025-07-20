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

use hcf\util\inventory\InventoryIds;
use muqsit\invmenu\InvMenu;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class CraftCommand extends Command {

	public function __construct() {
		parent::__construct('craft', 'Use command to open portable craft.');
		$this->setPermission('craft.command.use');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}

		if (!$this->testPermission($sender)) {
			return;
		}
		$inventory = InvMenu::create(InventoryIds::CRAFTING_INVENTORY);
		$inventory->send($sender);
	}
}
