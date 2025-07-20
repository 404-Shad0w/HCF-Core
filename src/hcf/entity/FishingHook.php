<?php

/*
 * A PocketMine-MP plugin that implements UHC Game.
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 *
 * @author JkqzDev
 */

declare(strict_types=1);

namespace hcf\entity;

use hcf\session\SessionFactory;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

final class FishingHook extends Throwable {

	protected function getInitialDragMultiplier() : float {
		return 0.05;
	}

	protected function getInitialGravity() : float {
		return 0.06;
	}

	protected function getInitialSizeInfo() : EntitySizeInfo {
		return new EntitySizeInfo(0.25, 0.25);
	}

	public static function getNetworkTypeId() : string {
		return EntityIds::FISHING_HOOK;
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool {
		$hasUpdate = parent::entityBaseTick($tickDiff);
		$owningEntity = $this->getOwningEntity();

		if (!$owningEntity instanceof Player) {
			$this->flagForDespawn();
			return true;
		}
		$session = SessionFactory::get($owningEntity);

		if (!$owningEntity->isOnline() || !$owningEntity->isAlive() || $owningEntity->isClosed()) {
			$this->flagForDespawn();
			$session?->setFishingHook();
			return true;
		}

		if (!$owningEntity->getInventory()->getItemInHand()->equals(VanillaItems::FISHING_ROD(), false, false)) {
			$this->flagForDespawn();
			$session?->setFishingHook();
			return true;
		}

		if ($owningEntity->getPosition()->distance($this->getPosition()) >= 25) {
			$this->flagForDespawn();
			$session?->setFishingHook();
			return true;
		}
		return $hasUpdate;
	}

	protected function onHit(ProjectileHitEvent $event) : void {
		$owningEntity = $this->getOwningEntity();

		if ($owningEntity instanceof Player) {
			$session = SessionFactory::get($owningEntity);
			$session?->setFishingHook();
		}
	}
}
