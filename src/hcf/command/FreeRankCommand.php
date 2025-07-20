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

use hcf\util\Utils;
use hcf\session\SessionFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function implode;
use function intval;
use function is_numeric;
use function strtolower;
use const PHP_EOL;

final class FreeRankCommand extends Command {

	public function __construct() {
		parent::__construct('freerank', 'Use the command to get a free rank for 1 day', null, ['rankfree']);
        $this->setPermission('freerank.command.use');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if ($sender instanceof Player) {
			$session = SessionFactory::get($sender);

			if ($session === null) {
				return;
			}
			
			$timer = $session->getTimer('free_rank');
			
			if ($timer !== null) {
				$sender->sendMessage(TextFormat::colorize('&cYou have command cooldown ' . Utils::date($timer->getTime())));
				return;
            }
            
                $session->addTimer(name: 'free_rank', format: '', time: 259200, visible: false);
                $sender->sendMessage(TextFormat::colorize("&aYou have claimed your rank for free"));
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), 'ranks setrank "' . $sender->getName()  . '" supreme 1d');
            }
	  }
}
