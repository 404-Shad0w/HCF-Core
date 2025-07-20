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

namespace hcf\kit\command;

use hcf\form\kit\manager\KitManagerForm;
use hcf\kit\KitFactory;
use hcf\session\SessionFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\item\VanillaItems;
use function count;
use function strtolower;

final class KitCommand extends Command {

	public function __construct() {
		parent::__construct('kit', 'Command for kit manager');
		$this->setPermission('kit.command');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			if (count($args) < 1) {
				return;
			}

			if (strtolower($args[0]) === 'give') {
				if (count($args) < 3) {
					return;
				}
				$player = $sender->getServer()->getPlayerExact($args[1]);
				$kitName = $args[2];

				if ($player === null) {
					return;
				}
				$kit = KitFactory::get($kitName);

				if ($kit === null) {
					return;
				}
				$kit->giveTo($player, true);
			} 
			return;
		}
        
        if (count($args) >= 1) {

        if (strtolower($args[0]) === 'setdecorativeitem') { 
            if (!$this->testPermission($sender)) return;
            
            $session = SessionFactory::get($sender);
            if ($session === null) return;
            
            $kitHandler = $session->getKitHandler();
            $itemDecorative = $sender->getInventory()->getItemInHand();
            
            if ($itemDecorative === null) {
                $itemDecorative = VanillaItems::BOOK();
            }
            
            $kitHandler->setItemDecorative($itemDecorative);
            $sender->sendMessage(TextFormat::colorize('&aDecorative item placed successfully'));
            }
            return;
       }
        
		if (!$this->testPermission($sender)) {
			return;
		}
		$form = new KitManagerForm();
		$sender->sendForm($form);
	}
}
