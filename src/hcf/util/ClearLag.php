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

namespace hcf\util;

use hcf\HCF;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\entity\object\ItemEntity;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

final class ClearLag {
	use SingletonTrait;

	private const TIME_EXECUTE = 15 * 60;

	public function clearEntities() : int {
		$world = HCF::getInstance()->getServer()->getWorldManager()->getDefaultWorld();
		$entities = 0;

		if ($world !== null) {
			foreach ($world->getEntities() as $entity) {
				if (!$entity instanceof ExperienceOrb && !$entity instanceof ItemEntity) {
					continue;
				}

				if ($entity->isFlaggedForDespawn() || $entity->isClosed()) {
					continue;
				}
                
                if ($entity instanceof ItemEntity && $entity->getItem()->getNamedTag()->getTag('mystery_crate') !== null) {
                    continue;
                }
                
                /*if ($entity->ticksLived < 10 * 80) {
					continue;
				}*/
				$entities++;
				$entity->flagForDespawn();
			}
		}
		return $entities;
	}

	public function task() : void {
		HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () : void {
			$entities = $this->clearEntities();
			HCF::getInstance()->getServer()->broadcastMessage(TextFormat::colorize('&cClear Lag: ' . $entities . ' entities'));
		}), self::TIME_EXECUTE * 10);
	}
}
