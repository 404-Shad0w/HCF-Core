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

namespace hcf\enchantment\command;

use hcf\util\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_search;
use function count;
use function in_array;
use function is_numeric;

final class CustomEnchantCommand extends Command {

	public function __construct() {
		parent::__construct('customenchant', 'Use command to custom enchants');
		$this->setPermission('custom_enchant.command');
		$this->setAliases(['ce']);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}

		if (!$this->testPermission($sender)) {
			return;
		}
		$item = $sender->getInventory()->getItemInHand();
        
        /*if (!$item instanceof Durable) {
			$sender->sendMessage(TextFormat::colorize('&cInvalid item.'));
			return;
		}*/

		if (count($args) < 2) {
			$sender->sendMessage(TextFormat::colorize('&cUse /customenchant [enchant_id] [level]'));
			return;
		}

		if (!is_numeric($args[0]) || !is_numeric($args[1])) {
			$sender->sendMessage(TextFormat::colorize('&cInvalid enchant id or level.'));
			return;
		}
		$enchant_id = (int) $args[0];
		$level = (int) $args[1];

		$enchant = EnchantmentIdMap::getInstance()->fromId($enchant_id);

		if ($enchant_id < 128 && $enchant_id > 131 || $enchant === null) {
			$sender->sendMessage(TextFormat::colorize('&cCustom enchant no exists.'));
			return;
		}

		if ($level < 0 || $level > $enchant->getMaxLevel()) {
			$sender->sendMessage(TextFormat::colorize('&cLevel invalid.'));
			return;
		}
		$itemLore = $item->getLore();

		if (count($itemLore) === 0) {
			$itemLore[] = TextFormat::colorize('&r');
		}

		if ($item->hasEnchantment($enchant)) {
			$oldEnchant = $item->getEnchantment($enchant);

			if ($oldEnchant->getLevel() !== $level) {
				if (in_array(TextFormat::colorize('&r&4' . $enchant->getName() . ' ' . Utils::minecraftRomanNumerals($oldEnchant->getLevel())), $itemLore, true)) {
					$itemLore[array_search(TextFormat::colorize('&r&4' . $enchant->getName() . ' ' . Utils::minecraftRomanNumerals($oldEnchant->getLevel())), $itemLore, true)] = TextFormat::colorize('&r&4' . $enchant->getName() . ' ' . Utils::minecraftRomanNumerals($level));
				}
			} else {
				$sender->sendMessage(TextFormat::colorize('&cThis item already has ' . $enchant->getName() . ' enchant.'));
				return;
			}
		} else {
			$itemLore[] = TextFormat::colorize('&r&4' . $enchant->getName() . ' ' . Utils::minecraftRomanNumerals($level));
		}
		$item->addEnchantment(new EnchantmentInstance($enchant, $level));
		$item->setLore($itemLore);

		$sender->getInventory()->setItemInHand($item);
		$sender->sendMessage(TextFormat::colorize('&aYou have been added ' . $enchant->getName() . ' enchant'));
	}
}
