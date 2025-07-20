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

namespace hcf\kit\class;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;
use RuntimeException;
use function count;
use function spl_object_hash;

abstract class KitClass implements KitClassInterface {

	/**
	 * @param Item[]           $armor
	 * @param EffectInstance[] $effects
	 */
	public function __construct(
		private string $name,
		private bool   $energy = false,
		private array  $armor = [],
		private array  $effects = [],
		private array  $players = []
	) {
		if (count($armor) > 4) {
			throw new RuntimeException('Armor exceed count');
		}
	}

	public function getName() : string {
		return $this->name;
	}

	public function hasEnergy() : bool {
		return $this->energy;
	}

	public function isEnabled(Player $player) : bool {
		$armor = $this->armor;
		$inventory = $player->getArmorInventory();

		return $inventory->getHelmet()->getTypeId() === $armor[0]->getTypeId() && $inventory->getChestplate()->getTypeId() === $armor[1]->getTypeId() && $inventory->getLeggings()->getTypeId() === $armor[2]->getTypeId() && $inventory->getBoots()->getTypeId() === $armor[3]->getTypeId();
	}

	public function handleAdd(Player $player) : void {
		foreach ($this->effects as $effect) {
			$player->getEffects()->add($effect);
			$this->players[spl_object_hash($player)][spl_object_hash($effect)] = $effect;
		}
	}

	public function handleRemove(Player $player) : void {
		if (!isset($this->players[spl_object_hash($player)])) {
			return;
		}

		foreach ($this->players[spl_object_hash($player)] ?? [] as $effect) {
			$effectHash = spl_object_hash($effect);

			if (!$player->getEffects()->has($effect->getType())) {
				unset($this->players[spl_object_hash($player)][$effectHash]);
				continue;
			}

			if (spl_object_hash($effect) === $effectHash) {
				$player->getEffects()->remove($effect->getType());
				unset($this->players[spl_object_hash($player)][$effectHash]);
			}
		}
	}

	public function handleDamage(EntityDamageEvent $event) : void {}
	public function handleItemHeld(PlayerItemHeldEvent $event) : void {}
	public function handleItemUse(PlayerItemUseEvent $event) : void {}
}
