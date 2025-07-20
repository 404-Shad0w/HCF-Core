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

namespace hcf\enchantment\type;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\player\Player;
use pocketmine\utils\Limits;
use function spl_object_hash;

class ArmorEffectEnchantment extends ArmorEnchantment {

	/**
	 * @param EffectInstance[] $effects
	 */
	public function __construct(
		string        $name,
		int           $rarity,
		int           $primaryItemFlags,
		int           $secondaryItemFlags,
		int           $maxLevel,
		private array $effects = [],
		private array $players = []
	) {
		parent::__construct($name, $rarity, $primaryItemFlags, $secondaryItemFlags, $maxLevel);
	}

	public function handleAdd(Player $player, int $level) : void {
		foreach ($this->effects as $effect) {
			$effect = clone $effect;
			$effect->setDuration(Limits::INT32_MAX);
			$effect->setAmplifier($level - 1);
			$player->getEffects()->add($effect);

			$this->players[spl_object_hash($player)][spl_object_hash($effect)] = $effect;
		}
	}

	public function handleRemove(Player $player) : void {
		if (!isset($this->players[spl_object_hash($player)])) {
			return;
		}

		foreach ($this->players[spl_object_hash($player)] as $effect) {
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
}
