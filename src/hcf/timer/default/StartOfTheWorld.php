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
use hcf\session\SessionFactory;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;

final class StartOfTheWorld extends Timer {

	public function __construct() {
		parent::__construct('sotw', 'Use timer to Start Of The World', '&aSOTW end in&r&7:', 60 * 60);
	}

	public function setEnabled(bool $enabled) : void {
		parent::setEnabled($enabled);

		if ($enabled) {
            /*HCF::getInstance()->getServer()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), 'timer enable "' . 'Key All' . '" 600');
            HCF::getInstance()->getServer()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), 'timer enable "' . 'PackageAll' . '" 900');
            HCF::getInstance()->getServer()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), 'timer enable "' . 'BoxAll' . '" 1200');
            //HCF::getInstance()->getServer()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), 'timer enable "' . 'AirdropAll' . '" 1800');
            HCF::getInstance()->getServer()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), 'timer enable "' . 'Op Key All' . '" 1800');*/
            HCF::getInstance()->getServer()->getCommandMap()->register('HCF', new class extends Command {

				public function __construct() {
					parent::__construct('sotw', 'Use that command to disable your sotw timer.');
                    $this->setPermission('sotw_enable.permission');
				}

				public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
					if (!$sender instanceof Player) {
						return;
					}
                    
                    $session = SessionFactory::get($sender);
                    
                    if ($session === null) return;
                    
                    if (!isset($args[0])) {
                        $sender->sendMessage(TextFormat::colorize('&cUse /sotw enable|disable'));
                        return;
                    }
                    
                    if ($args[0] !== 'enable' || $args[0] !== 'disable') {
                        $sender->sendMessage(TextFormat::colorize('&cUse /sotw enable|disable'));
                        return;
                    }
                    
                    if ($args[0] === 'disable') {
                        if (!$session->hasSotwEnable()) {
                            $sender->sendMessage(TextFormat::colorize('&cYou do not have SOTW Enable activated'));
                            return;
                        }
                        
                        if ($sesssion->getTimer('spawn_tag') !== null) {
                            $sender->sendMessage(TextFormat::colorize('&cYou cannot disable SOTW Enable if you are in Combat.'));
                            return;
                        }
                        
                        $session->setSotwEnable(false);
                        $sender->sendMessage(TextFormat::colorize('&aYou have deactivated the SOTW Enable and you will no longer enter combat.'));
                        return;
                    }
                    
                    if ($session->hasSotwEnable()) {
                        $sender->sendMessage(TextFormat::colorize('&cYou cannot use the command again, you can only use it once during the SOTW.'));
                        return;
                    }
                    
                    $session->setSotwEnable(true);
                    $sender->setNameTag(TextFormat::colorize('&b[Sotw Enable]' . TextFormat::EOL . '&b' . $sender->getName()));
                    $sender->sendMessage(TextFormat::colorize('&aYou just deactivated the Sotw Timer and you can now enter pvp.'));
				}
			});
		} else {
            foreach(SessionFactory::getAll() as $session) {
                if ($session->hasSotwEnable()) {
                    $session->getPlayer()->setNameTag(TextFormat::colorize('&c' . $session->getPlayer()->getName()));
                    $session->setSotwEnable(false);
                }
            }
			$command = HCF::getInstance()->getServer()->getCommandMap()->getCommand('sotw');

			if ($command !== null) {
				HCF::getInstance()->getServer()->getCommandMap()->unregister($command);
			}
		}

		foreach (HCF::getInstance()->getServer()->getOnlinePlayers() as $player) {
			$player->getNetworkSession()->syncAvailableCommands();
		}
	}
}
