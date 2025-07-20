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

use hcf\HCF;
use hcf\session\SessionFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

final class LogoutCommand extends Command {

	public function __construct() {
		parent::__construct('logout', 'Use command to save logout.');
        $this->setPermission('logout.command.use');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}
		$session = SessionFactory::get($sender);

		if ($session === null) {
			return;
		}

		if ($session->getTimer('save_logout') !== null) {
			$sender->sendMessage(TextFormat::colorize('&cYou already in logout time.'));
			return;
		}
		$session->addTimer('save_logout', '&l&cLogout&r&7:', 30);

		$position = $sender->getPosition();
		$handler = HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use (&$handler, &$sender, &$session, &$position) : void {
			if (!$sender->isOnline() || $position->distance($sender->getPosition()) > 2 || $session->getTimer('spawn_tag') !== null) {
				$session->removeTimer('save_logout');
				$handler->cancel();
				return;
			}

			if ($session->getTimer('save_logout') === null) {
				$session->setLogout();

				$sender->kick(TextFormat::colorize('&l&cSave logout.'));
				$handler->cancel();
			}
		}), 20);
	}
}
