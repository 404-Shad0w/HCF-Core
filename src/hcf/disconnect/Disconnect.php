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

namespace hcf\disconnect;

use hcf\entity\DisconnectEntity;
use hcf\session\Session;
use pocketmine\entity\Location;
use pocketmine\utils\TextFormat;

final class Disconnect {

	private DisconnectEntity $entity;

	public function __construct(
		private Session $session,
		private Location $location,
		private array $armorInventory,
		private array $inventory,
		private bool $death = false
	) {
		$this->entity = new DisconnectEntity($this->location, null, $this);
		$this->entity->setNameTag(TextFormat::colorize('&c' . $this->session->getName() . ' &7(Combat Logger)'));
		$this->entity->setNameTagVisible();
		$this->entity->setNameTagAlwaysVisible();
		$this->entity->spawnToAll();
	}

	public function getSession() : Session {
		return $this->session;
	}

	public function getLocation() : Location {
		return $this->location;
	}

	public function getArmorInventory() : array {
		return $this->armorInventory;
	}

	public function getInventory() : array {
		return $this->inventory;
	}

	public function isDeath() : bool {
		return $this->death;
	}

	public function setDeath(bool $death) : void {
		$this->death = $death;
	}

	public function join() : void {
		$player = $this->session->getPlayer();
		DisconnectFactory::remove($this->session);

		if ($player === null) {
			return;
		}

		if ($this->isDeath()) {
			$player->teleport($player->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
			$player->setHealth($player->getMaxHealth());
			$player->getInventory()->setContents([]);
			$player->getArmorInventory()->setContents([]);
			$player->getXpManager()->setXpAndProgress(0, 0.0);
			$player->getEffects()->clear();
			return;
		}

		if ($this->entity->isFlaggedForDespawn() || $this->entity->isClosed()) {
			return;
		}
		$player->setHealth($this->entity->getHealth());
		$player->teleport($this->entity->getLocation());
		$this->entity->flagForDespawn();
	}
}
