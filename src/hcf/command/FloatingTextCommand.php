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

use hcf\entity\CustomTextEntity;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use function array_reverse;
use function array_shift;
use function count;
use function explode;
use function implode;
use function strtolower;

final class FloatingTextCommand extends Command {

	public function __construct() {
		parent::__construct('floatingtext', 'Use command to spawn floating texts');
		$this->setPermission('floatingtext.command');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}

		if (!$this->testPermission($sender)) {
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /floatingtext help'));
			return;
		}

		switch (strtolower($args[0])) {
			case 'create':
				if (count($args) < 2) {
					$sender->sendMessage(TextFormat::colorize('&cUse /floatingtext create [text]'));
					return;
				}
				array_shift($args);
				$text = implode(' ', $args);
				$this->spawnCustomText(Position::fromObject($sender->getPosition()->add(0, 1, 0), $sender->getWorld()), explode('\n', $text));
				break;

			default:
				$sender->sendMessage(TextFormat::colorize('&cSubcommand no exists. Use /floatingtext help'));
				break;
		}
	}

	private function spawnCustomText(Position $position, array $texts) : void {
		[$newX, $newY, $newZ] = [$position->x + 0.5, $position->y + 0.28, $position->z + 0.5];
		$lines = array_reverse($texts);

		foreach ($lines as $line) {
			$newY += 0.28;
			$customText = new CustomTextEntity(new Location($newX, $newY, $newZ, $position->getWorld(), 0, 0), null, $line);
			$customText->setCanSaveWithChunk(true);
			$customText->spawnToAll();
		}
	}
}
