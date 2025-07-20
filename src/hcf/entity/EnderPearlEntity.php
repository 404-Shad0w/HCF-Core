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

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Cobweb;
use pocketmine\block\FenceGate;
use pocketmine\block\Opaque;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\block\Wall;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\math\Facing;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use function count;
use function in_array;

final class EnderPearlEntity extends EnderPearl {

	protected float $gravity = 0.03;
	protected float $drag = 0.01;
    
    public int|float $width = 0.20;
    public int|float $height = 0.20;

	private const PRESSURE_PLATES = [
		BlockTypeIds::OAK_PRESSURE_PLATE,
		BlockTypeIds::STONE_PRESSURE_PLATE,
		BlockTypeIds::WEIGHTED_PRESSURE_PLATE_LIGHT,
		BlockTypeIds::WEIGHTED_PRESSURE_PLATE_HEAVY
	];

	private bool $alreadyPassed = false;
    
    protected function getInitialDragMultiplier() : float {
        return $this->drag;
    }
    
    protected function getInitialGravity() : float {
        return $this->gravity;
    }

	protected function calculateInterceptWithBlock(Block $block, Vector3 $start, Vector3 $end) : ?RayTraceResult {
		if (self::canPassThrough($block) && !$this->alreadyPassed) {
			$this->alreadyPassed = true;
			return null;
		}
		return $block->calculateIntercept($start, $end);
	}

	public static function canPassThrough(Block $block, ?Block $blockHit = null) : bool {
		if ($block instanceof FenceGate && $block->isOpen()) {
			return true;
		}

		if ($block instanceof Cobweb || $block instanceof Slab || $block instanceof Stair || $block instanceof Wall) {
			return true;
		}

		if ($block instanceof Air && count($block->getSide(Facing::UP)->getCollisionBoxes()) > 0 && count($block->getSide(Facing::DOWN)->getCollisionBoxes()) > 0) {
			return true;
		}

		if (in_array($block->getTypeId(), self::PRESSURE_PLATES, true)) {
			return true;
		}
		return false;
	}
    
    protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo($this->height, $this->width); }
    
}
