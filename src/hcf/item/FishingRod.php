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

namespace hcf\item;

use hcf\entity\FishingHook;
use hcf\session\SessionFactory;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Location;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\ThrowSound;

final class FishingRod extends \pocketmine\item\FishingRod {

	public function getMaxStackSize() : int {
		return 1;
	}

	public function getThrowForce() : float {
		return 2.1;
	}

	protected function createEntity(Location $location, Player $thrower) : FishingHook {
		return new FishingHook($location, $thrower);
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult {
		$location = $player->getLocation();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return ItemUseResult::FAIL();
		}

		if ($session->getFishingHook() !== null) {
			$fishingHook = $session->getFishingHook();

			if (!$fishingHook->isFlaggedForDespawn() && !$fishingHook->isClosed()) {
				$fishingHook->flagForDespawn();
				$player->broadcastAnimation(new ArmSwingAnimation($player), $player->getViewers());
			}
			$session->setFishingHook();
			return ItemUseResult::SUCCESS();
		}
		$projectile = $this->createEntity(Location::fromObject($player->getEyePos(), $player->getWorld(), $location->yaw, $location->pitch), $player);
		$projectile->setMotion($directionVector->multiply($this->getThrowForce())->add(0, 0.2, 0));

		$projectileEv = new ProjectileLaunchEvent($projectile);
		$projectileEv->call();

		if ($projectileEv->isCancelled()) {
			$projectile->flagForDespawn();
			return ItemUseResult::FAIL();
		}
		$session->setFishingHook($projectile);
		$projectile->spawnToAll();

		$location->getWorld()->addSound($location, new ThrowSound());
		$player->broadcastAnimation(new ArmSwingAnimation($player), $player->getViewers());

		$this->applyDamage(1);
		return ItemUseResult::SUCCESS();
	}
}
