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

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class CustomTextEntity extends Entity {
    
    protected float $drag = 1.1;
    protected float $gravity = 1.1;

	public function __construct(
		Location $location,
		?CompoundTag $nbt = null,
		private ?string $text = null
	) {
		parent::__construct($location, $nbt);
	}

	protected function getInitialSizeInfo() : EntitySizeInfo {
		return new EntitySizeInfo(0.1, 0.1);
	}
    
    protected function getInitialDragMultiplier() : float {
        return $this->drag;
    }
    
    protected function getInitialGravity() : float {
        return $this->gravity;
    }

	public static function getNetworkTypeId() : string {
		return EntityIds::NPC;
	}

	public function getText() : ?string {
		return $this->text;
	}

	protected function initEntity(CompoundTag $nbt) : void {
		parent::initEntity($nbt);
		$text = $this->text;

		if ($text === null && $nbt->getTag('custom_text') !== null) {
			$text = $nbt->getString('custom_text');
		}

		if ($text === null) {
			$this->flagForDespawn();
		} else {
			$this->setScale(0.0001);

			$this->setNameTag(TextFormat::colorize($text));
			$this->setNameTagVisible();
			$this->setNameTagAlwaysVisible();

			$this->setImmobile();
			$this->setHealth(200);

			$this->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0.0);
			$this->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0.0);
		}
		$this->text = $text;
	}

	public function attack(EntityDamageEvent $source) : void {
		$source->cancel();

		if ($source instanceof EntityDamageByEntityEvent) {
			$damager = $source->getDamager();

			if (!$damager instanceof Player) {
				return;
			}

			if ($damager->getGamemode()->equals(GameMode::CREATIVE()) && $damager->hasPermission('god.permission') && $damager->getInventory()->getItemInHand()->equals(VanillaItems::STICK())) {
				$this->flagForDespawn();
			}
		}
	}

	public function saveNBT() : CompoundTag {
		$nbt = parent::saveNBT();

		if ($this->text !== null) {
			$nbt->setString('custom_text', $this->text);
		} elseif ($nbt->getTag('custom_text')) {
			$nbt->removeTag('custom_text');
		}
		return $nbt;
	}
}
