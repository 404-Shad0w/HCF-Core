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

namespace hcf\faction\invite;

use hcf\faction\Faction;
use hcf\session\Session;

final class InviteFactory {

	private static array $invites = [];

	public static function create(Session $to, Faction $from) : void {
		self::$invites[$to->getXuid()][$from->getName()] = new Invite($from);
	}

	public static function removeFromFaction(Session $session, Faction $target) : void {
		if (self::get($session) === null) {
			return;
		}
		$invites = self::get($session);

		if (!isset($invites[$target->getName()])) {
			return;
		}
		unset(self::$invites[$session->getXuid()][$target->getName()]);
	}

	public static function get(Session $session) : ?array {
		return self::$invites[$session->getXuid()] ?? null;
	}

	public static function remove(Session $session) : void {
		if (self::get($session) === null) {
			return;
		}
		unset(self::$invites[$session->getXuid()]);
	}
}
