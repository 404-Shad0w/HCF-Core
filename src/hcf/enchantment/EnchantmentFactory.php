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
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Rarity;

final class EnchantmentFactory implements EnchantmentIds {

	public static function loadAll() : void {
		$enchantmentMap = EnchantmentIdMap::getInstance();
		$enchantmentMap->register(
			self::FIRE_ASPECT,
			new ArmorEffectEnchantment(
				'Fire Resistance',
				Rarity::MYTHIC,
				ItemFlags::ARMOR,
				ItemFlags::NONE,
				1,
				[new EffectInstance(VanillaEffects::FIRE_RESISTANCE())]
			)
		);
		$enchantmentMap->register(
			self::INVISIBILITY,
			new ArmorEffectEnchantment(
				'Invisibility',
				Rarity::MYTHIC,
				ItemFlags::ARMOR,
				ItemFlags::NONE,
				1,
				[new EffectInstance(VanillaEffects::INVISIBILITY())]
			)
		);
		$enchantmentMap->register(
			self::SPEED,
			new ArmorEffectEnchantment(
				'Speed',
				Rarity::MYTHIC,
				ItemFlags::ARMOR,
				ItemFlags::NONE,
                2,
				[new EffectInstance(VanillaEffects::SPEED())]
			)
		);
		$enchantmentMap->register(
			self::HASTE,
			new ArmorEffectEnchantment(
				'Haste',
				Rarity::MYTHIC,
				ItemFlags::ARMOR,
				ItemFlags::NONE,
				1,
				[new EffectInstance(VanillaEffects::HASTE())]
			)
		);
        $enchantmentMap->register(
			self::NIGHT_VISION,
			new ArmorEffectEnchantment(
				'Nigth Vision',
				Rarity::MYTHIC,
				ItemFlags::ARMOR,
				ItemFlags::NONE,
				1,
				[new EffectInstance(VanillaEffects::NIGHT_VISION())]
			)
		);
        $enchantmentMap->register(
			self::WATER_BREATHING,
			new ArmorEffectEnchantment(
				'Water Breathing',
				Rarity::MYTHIC,
				ItemFlags::ARMOR,
				ItemFlags::NONE,
				1,
				[new EffectInstance(VanillaEffects::WATER_BREATHING())]
			)
		);
	}
}
