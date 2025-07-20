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

use hcf\HCF;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;

final class ClaimFactory {

	/** @var Claim[] */
	private static array $claims = [];

	public static function getAll() : array {
		return self::$claims;
	}

	public static function remove(string $name) : void {
		if (self::get($name) === null) {
			return;
		}
		unset(self::$claims[$name]);
	}

	public static function get(string $name) : ?Claim {
		return self::$claims[$name] ?? null;
	}

	public static function loadAll() : void {
		if (HCF::isUnderDevelopment()) {
			$defaultWorld = Server::getInstance()->getWorldManager()->getDefaultWorld();
			self::create('Spawn', new Position(100, 0, 100, $defaultWorld), new Position(-100, 0, -100, $defaultWorld), $defaultWorld, Claim::SPAWN);
		}
	}

	public static function create(string $name, Position $firstPosition, Position $secondPosition, World $world, string $type = Claim::FACTION) : Claim {
		self::$claims[$name] = $claim = new Claim($name, $type, $world, $firstPosition, $secondPosition);
		return $claim;
	}
}
