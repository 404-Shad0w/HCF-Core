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

namespace hcf\enchantment;

use hcf\enchantment\type\ArmorEffectEnchantment;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\player\Player;
use function array_diff_key;
use function spl_object_hash;

final class EnchantmentHandler implements Listener {

	private array $trackedArmorEnchants = [];

	public function handleJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();

		$player->getArmorInventory()->getListeners()->add(CallbackInventoryListener::onAnyChange(fn(ArmorInventory $inventory) => $this->checkArmorInventory($player)));
		$this->checkArmorInventory($player);
	}

	private function checkArmorInventory(Player $player) : void {
		$trackedEnchants = [];

		foreach ($player->getArmorInventory()->getContents() as $item) {
			foreach ($item->getEnchantments() as $enchantment) {
				$type = $enchantment->getType();

				if ($type instanceof ArmorEffectEnchantment) {
					$type->handleAdd($player, $enchantment->getLevel());
					$trackedEnchants[spl_object_hash($type)] = $enchantment;
				}
			}
		}
		/** @var EnchantmentInstance[] $addedEnchants */
		$addedEnchants = array_diff_key($trackedEnchants, $this->trackedArmorEnchants[$player->getXuid()] ?? []);
		/** @var EnchantmentInstance[] $removedEnchants */
		$removedEnchants = array_diff_key($this->trackedArmorEnchants[$player->getXuid()] ?? [], $trackedEnchants);
		$this->trackedArmorEnchants[$player->getXuid()] = $trackedEnchants;

		foreach ($addedEnchants as $enchant) {
			$type = $enchant->getType();

			if ($type instanceof ArmorEffectEnchantment) {
				$type->handleAdd($player, $enchant->getLevel());
			}
		}

		foreach ($removedEnchants as $enchant) {
			$type = $enchant->getType();

			if ($type instanceof ArmorEffectEnchantment) {
				$type->handleRemove($player);
			}
		}
	}
}
