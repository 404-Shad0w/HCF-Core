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

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function implode;

final class RenameCommand extends Command {

	public function __construct() {
		parent::__construct('rename', 'Use command to rename items.');
		$this->setPermission('rename.command.use');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}

		if (!$this->testPermission($sender)) {
			return;
		}
		$item = $sender->getInventory()->getItemInHand();

		if ($item->getNamedTag()->getTag('ability_name') !== null) {
			$sender->sendMessage(TextFormat::colorize('&cInvalid name.'));
			return;
		}
		$item->setCustomName(TextFormat::colorize(implode(' ', $args)));
		$sender->getInventory()->setItemInHand($item);
		$sender->sendMessage(TextFormat::colorize('&aYou have been rename your item.'));
	}
}
