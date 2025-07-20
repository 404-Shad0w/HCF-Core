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

use hcf\util\ClearLag;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class ClearLagCommand extends Command {

	public function __construct() {
		parent::__construct('clearlag', 'Use command to clear lag');
		$this->setPermission('clearlag.command');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}

		if (!$this->testPermission($sender)) {
			return;
		}
		$entities = ClearLag::getInstance()->clearEntities();
		$sender->sendMessage(TextFormat::colorize('&aYou have been cleared ' . $entities . ' entities'));
	}
}
