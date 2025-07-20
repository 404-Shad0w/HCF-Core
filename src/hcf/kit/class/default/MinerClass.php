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

namespace hcf\kit\class\default;

use hcf\kit\class\KitClass;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Limits;

final class MinerClass extends KitClass {

	public function __construct() {
		parent::__construct('Miner', false, [
			VanillaItems::IRON_HELMET(),
			VanillaItems::IRON_CHESTPLATE(),
			VanillaItems::IRON_LEGGINGS(),
			VanillaItems::IRON_BOOTS()
		], [
			new EffectInstance(VanillaEffects::HASTE(), Limits::INT32_MAX, 1),
			new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), Limits::INT32_MAX),
			new EffectInstance(VanillaEffects::NIGHT_VISION(), Limits::INT32_MAX)
		]);
	}
}
