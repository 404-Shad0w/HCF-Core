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
use hcf\faction\FactionFactory;
use function time;

final class Invite {

	public function __construct(
		private Faction $faction,
		private int     $time = 0
	) {
		$this->time = time() + 60;
	}

	public function getFaction() : Faction {
		return $this->faction;
	}

	public function exitsFaction() : bool {
		$faction = $this->faction;
		return FactionFactory::get($faction->getName()) !== null;
	}

	public function isExpired() : bool {
		return $this->time < time();
	}
}
