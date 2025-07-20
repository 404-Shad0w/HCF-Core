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

namespace hcf\kit\class\default\_trait;

use pocketmine\player\Player;
use function max;
use function time;

trait CooldownTrait {

	private array $cooldowns = [];

	public function getCooldown(Player $player) : int {
		if (!isset($this->cooldowns[$player->getXuid()])) {
			return 0;
		}
		return max(0, $this->cooldowns[$player->getXuid()] - time());
	}

	public function addCooldown(Player $player, int $time) : void {
		$this->cooldowns[$player->getXuid()] = time() + $time;
	}
}
