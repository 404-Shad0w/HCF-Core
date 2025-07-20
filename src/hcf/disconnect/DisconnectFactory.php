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

use hcf\session\Session;
use pocketmine\entity\Location;

final class DisconnectFactory {

	/** @var Disconnect[] */
	private static array $disconnects = [];

	public static function get(Session $session) : ?Disconnect {
		return self::$disconnects[$session->getXuid()] ?? null;
	}

	public static function create(Session $session, Location $location, array $armorInventory, array $inventory) : void {
		self::$disconnects[$session->getXuid()] = new Disconnect($session, $location, $armorInventory, $inventory);
	}

	public static function remove(Session $session) : void {
		if (self::get($session) === null) {
			return;
		}
		unset(self::$disconnects[$session->getXuid()]);
	}
}
