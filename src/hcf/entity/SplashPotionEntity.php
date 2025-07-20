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

namespace hcf\entity;

use pocketmine\entity\effect\InstantEffect;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\player\Player;
use pocketmine\world\sound\PotionSplashSound;
use function count;
use function round;

final class SplashPotionEntity extends SplashPotion {

	protected const MAX = 1.0515;

	protected $gravity = 0.06;
	protected $drag = 0.0025;

	public function entityBaseTick(int $tickDiff = 1) : bool {
		if ($this->isCollided) {
			$this->flagForDespawn();
		}
		return parent::entityBaseTick($tickDiff);
	}

	public function canCollideWith(Entity $entity) : bool {
		$player = $this->getOwningEntity();

		if ($player instanceof Player && $entity instanceof Player && $player->getId() !== $entity->getId()) {
			return false;
		}
		return parent::canCollideWith($entity);
	}

	protected function onHit(ProjectileHitEvent $event) : void {
		$owner = $this->getOwningEntity();

		if (!$owner instanceof Player) {
			$this->flagForDespawn();
			return;
		}
		$this->broadcastSound(new PotionSplashSound());

		if (count($this->getPotionEffects()) > 0) {
			if ($event instanceof ProjectileHitEntityEvent) {
				$entityHit = $event->getEntityHit();

				if ($entityHit instanceof Player) {
					$entityHit->heal(new EntityRegainHealthEvent($entityHit, 1.45, EntityRegainHealthEvent::CAUSE_CUSTOM));
				}
			}

			foreach ($this->getWorld()->getNearbyEntities($this->getBoundingBox()->expand(1.85, 2.65, 1.85)) as $nearby) {
				if ($nearby instanceof Player) {
					if ($nearby->isAlive() && !$nearby->isImmobile()) {
						foreach ($this->getPotionEffects() as $effect) {
							if (!$effect->getType() instanceof InstantEffect) {
								$newDuration = (int) round($effect->getDuration() * 0.75 * self::MAX);

								if ($newDuration < 20) {
									continue;
								}
								$effect->setDuration($newDuration);
								$nearby->getEffects()->add($effect);
							} else {
								$effect->getType()->applyEffect($nearby, $effect, self::MAX, $this);
							}
						}
					}
				}
			}
		}
	}
}
