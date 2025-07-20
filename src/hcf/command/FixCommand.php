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

use hcf\session\SessionFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Durable;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

final class FixCommand extends Command {

	public function __construct() {
		parent::__construct('fix', 'Command for fix items');
		$this->setPermission('fix.command');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}

		if (!$this->testPermission($sender)) {
			return;
		}
		$session = SessionFactory::get($sender);

		if ($session === null) {
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /fix [all|player]'));
			return;
		}

		if ($args[0] === 'all') {
			$this->fixItems($sender);
			$sender->sendMessage(TextFormat::colorize('&cYou have fix all your items.'));
			return;
		}

		if (!$this->testPermission($sender, 'fix.other.command')) {
			return;
		}
		$player = $sender->getServer()->getPlayerByPrefix($args[0]);

		if (!$player instanceof Player) {
			$sender->sendMessage(TextFormat::colorize('&cPlayer is offline.'));
			return;
		}
		$this->fixItems($player);
		$sender->sendMessage(TextFormat::colorize('&cYou have fixed all items to player ' . $player->getName()));
		$player->sendMessage(TextFormat::colorize('&a' . $sender->getName() . ' fixed all your items.'));
	}

	private function fixItems(Player $sender) : void {
		$inventory = $sender->getInventory();
		$armorInventory = $sender->getArmorInventory();

		foreach ($armorInventory->getContents() as $slot => $item) {
			if (!$item instanceof Durable || $item->getDamage() <= 0) {
				continue;
			}
			$armorInventory->setItem($slot, $item->setDamage(0));
		}

		foreach ($inventory as $slot => $item) {
			if (!$item instanceof Durable || $item->getDamage() <= 0) {
				continue;
			}
			$inventory->setItem($slot, $item->setDamage(0));
		}
	}
}
