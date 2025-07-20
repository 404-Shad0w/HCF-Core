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

namespace hcf\claim;

use pocketmine\world\Position;
use pocketmine\world\World;
use function max;
use function min;

final class Claim {

	public const FACTION = 'faction';
	public const SPAWN = 'spawn';
	public const ROAD = 'road';
	public const KOTH = 'koth';

	private int $minX, $minZ, $maxX, $maxZ;

	public function __construct(
		private string   $name,
		private string   $type,
		private World    $world,
		private Position $firstPosition,
		private Position $secondPosition
	) {
		$this->minX = min($firstPosition->getFloorX(), $secondPosition->getFloorX());
		$this->maxX = max($firstPosition->getFloorX(), $secondPosition->getFloorX());
		$this->minZ = min($firstPosition->getFloorZ(), $secondPosition->getFloorZ());
		$this->maxZ = max($firstPosition->getFloorZ(), $secondPosition->getFloorZ());
	}

	public function getName(?string $factionName = null) : string {
		return match ($this->type) {
			self::SPAWN => '&e(&aNon-Deathban&e) &a' . $this->name,
			self::KOTH => '&9KoTH ' . $this->name . ' &e(&cDeathban&e)',
			self::ROAD => '&6' . $this->name . ' &e(&cDeathban&e)',
			default => ($this->name !== null && $factionName === $this->name ? '&a' : '&c') . $this->name . ' &e(&cDeathban&e)',
		};
	}
    
    public function getMaxX() : int {
        return $this->maxX;
    }
    
    public function getMaxZ() : int {
        return $this->maxZ;
    }
    
    public function getMinX() : int {
        return $this->minX;
    }
    
    public function getMinZ() : int {
        return $this->minZ;
    }

	public function getType() : string {
		return $this->type;
	}

	public function getFirstPosition() : Position {
		return $this->firstPosition;
	}

	public function getSecondPosition() : Position {
		return $this->secondPosition;
	}

	public function equals(?Claim $claim) : bool {
		return $claim !== null && $this->name === $claim->getDefaultName();
	}

	public function getDefaultName() : string {
		return $this->name;
	}

	public function inside(Position $position) : bool {
		return $this->world->getFolderName() === $position->getWorld()->getFolderName() && $this->minX <= $position->getX() && $this->maxX >= $position->getFloorX() && $this->minZ <= $position->getZ() && $this->maxZ >= $position->getFloorZ();
	}

	public function getWorld() : World {
		return $this->world;
	}
}
