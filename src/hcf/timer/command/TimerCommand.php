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

namespace hcf\timer\command;

use hcf\timer\Timer;
use hcf\timer\TimerFactory;
use hcf\form\sotw\SotwStartForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function array_map;
use function count;
use function implode;
use function is_numeric;
use function strtolower;
use const PHP_EOL;

final class TimerCommand extends Command {

	public function __construct() {
		parent::__construct('timer', 'Use command to custom timers');
		$this->setPermission('timer.command');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$this->testPermission($sender)) {
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /timer help'));
			return;
		}
		$subCommand = strtolower($args[0]);

		switch ($subCommand) {
			case 'help':
				$texts = [
					'&l&eTIMER COMMAND&r',
					'&e/timer list &7- Use command to timer list',
					'&e/timer enable &7- Use command to enable any timer',
					'&e/timer disable &7- Use command to disable any timer'
				];
				$sender->sendMessage(TextFormat::colorize(implode(PHP_EOL, $texts)));
				break;

			case 'list':
				$timers = array_map(fn(Timer $timer) => '&e' . $timer->getName() . ' &7- ' . $timer->getDescription(), TimerFactory::getAll());
				$sender->sendMessage(TextFormat::colorize('&eTimer list' . PHP_EOL . implode(PHP_EOL, $timers)));
				break;

			case 'enable':
				if (count($args) < 2) {
					$sender->sendMessage(TextFormat::colorize('&cUse /timer enable [name] [time|optional]'));
					return;
				}
				$timer = TimerFactory::get($args[1]);

				if ($timer === null) {
					$sender->sendMessage(TextFormat::colorize('&cTimer not found.'));
					return;
				}

				if ($timer->isEnabled()) {
					$sender->sendMessage(TextFormat::colorize('&cTimer already enabled.'));
					return;
				}

				if (isset($args[2])) {
					if (!is_numeric($args[2])) {
						$sender->sendMessage(TextFormat::colorize('&cTime invalid.'));
						return;
					}
					$time = (int) $args[2];

					if ($time <= 0) {
						$sender->sendMessage(TextFormat::colorize('&cTime is less than or equal to 0'));
						return;
					}
				} else {
					$time = $timer->getTime();
				}
                
                if ($timer->getName() === 'sotw') {
                    $form = new SotwStartForm();
                    $sender->sendForm($form);
                    return;
                }
                
				$timer->setTime($time);
				$timer->setEnabled(true);

				$sender->sendMessage(TextFormat::colorize('&aTimer ' . $timer->getName() . ' has been enabled.'));
				break;

			case 'disable':
				if (count($args) < 2) {
					$sender->sendMessage(TextFormat::colorize('&cUse /timer disable [name]'));
					return;
				}
				$timer = TimerFactory::get($args[1]);

				if ($timer === null) {
					$sender->sendMessage(TextFormat::colorize('&cTimer not found.'));
					return;
				}

				if (!$timer->isEnabled()) {
					$sender->sendMessage(TextFormat::colorize('&cTimer already disabled.'));
					return;
				}
				$timer->setEnabled(false);

				$sender->sendMessage(TextFormat::colorize('&cTimer ' . $timer->getName() . ' has been disabled.'));
				break;

			default:
				$sender->sendMessage(TextFormat::colorize('&cCommand not exits.'));
				break;
		}
	}
}
