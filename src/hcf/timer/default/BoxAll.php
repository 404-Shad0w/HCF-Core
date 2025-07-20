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

namespace hcf\timer\default;

use hcf\HCF;
use hcf\timer\Timer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\utils\TextFormat;
use function count;
use function implode;
use function in_array;
use function strtolower;
use const PHP_EOL;

final class BoxAll extends Timer {

	/** @var string[] */
	private array $commands = [];

	public function __construct() {
		parent::__construct('BoxAll', 'Use timer to mystery crate all', '&bBox All&r&7:', 60 * 60);
	}

	public function setEnabled(bool $enabled) : void {
		parent::setEnabled($enabled);

		if ($enabled) {
			HCF::getInstance()->getServer()->getCommandMap()->register('HCF', new class($this) extends Command {

				public function __construct(
					private BoxAll $manager
				) {
					parent::__construct('boxall', 'Use command to mystery crate all');
					$this->setPermission('box_all.command');
				}

				public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
					if (!$this->testPermission($sender)) {
						return;
					}

					if (count($args) < 1) {
						$sender->sendMessage(TextFormat::colorize('&cUse /boxall help'));
						return;
					}

					switch (strtolower($args[0])) {
						case 'help':
							$text = [
								'&l&bBox All Commands&r',
								'&b/boxall add &7- Use command to add command',
								'&b/boxall remove &7- Use command to remove command',
								'&b/boxall list &7- Use command to view list'
							];

							$sender->sendMessage(TextFormat::colorize(implode(PHP_EOL, $text)));
							break;

						case 'add':
							if (count($args) < 2) {
								$sender->sendMessage(TextFormat::colorize('&cUse /boxall add [command]'));
								return;
							}
							$command = $args[1];

							if ($this->manager->existsCommand($command)) {
								$sender->sendMessage(TextFormat::colorize('&cCommand already exists.'));
								return;
							}
							$this->manager->addCommand($command);
							$sender->sendMessage(TextFormat::colorize('&aYou have been added the command to op key all.'));
							break;

						case 'remove':
							if (count($args) < 2) {
								$sender->sendMessage(TextFormat::colorize('&cUse /boxall remove [position]'));
								return;
							}
							$position = (int) $args[1];

							if (!$this->manager->existsPosition($position)) {
								$sender->sendMessage(TextFormat::colorize('&cPosition no exists.'));
								return;
							}
							$this->manager->removeCommand($position);
							$sender->sendMessage(TextFormat::colorize('&aYou have been removed the command.'));
							break;

						case 'list':
							if (count($this->manager->getCommands()) === 0) {
								$sender->sendMessage(TextFormat::colorize('&cNo commands.'));
								return;
							}
							$sender->sendMessage(TextFormat::colorize('&l&4Command List&r'));

							foreach ($this->manager->getCommands() as $position => $command) {
								$sender->sendMessage(TextFormat::colorize('&4' . $position . '. &f' . $command));
							}
							break;

						default:
							break;
					}
				}
			});
		} else {
			$command = HCF::getInstance()->getServer()->getCommandMap()->getCommand('boxall');

			if ($command !== null) {
				HCF::getInstance()->getServer()->getCommandMap()->unregister($command);
			}
		}

		foreach (HCF::getInstance()->getServer()->getOnlinePlayers() as $player) {
			$player->getNetworkSession()->syncAvailableCommands();
		}
	}

	public function getCommands() : array {
		return $this->commands;
	}

	public function existsPosition(int $position) : bool {
		return isset($this->commands[$position]);
	}

	public function existsCommand(string $command) : bool {
		return in_array($command, $this->commands, true);
	}

	public function addCommand(string $command) : void {
		$this->commands[] = $command;
	}

	public function removeCommand(int $pos) : void {
		if (!isset($this->commands[$pos])) {
			return;
		}
		unset($this->commands[$pos]);
	}

	public function update() : void {
		if ($this->enabled) {
			if ($this->progress <= 0) {
				$this->setEnabled(false);
				$this->progress = $this->time;

				foreach ($this->commands as $command) {
                    HCF::getInstance()->getServer()->getCommandMap()->dispatch(new ConsoleCommandSender(HCF::getInstance()->getServer(), HCF::getInstance()->getServer()->getLanguage()), $command);
				}
				return;
			}
			$this->progress--;
		}
	}
}
