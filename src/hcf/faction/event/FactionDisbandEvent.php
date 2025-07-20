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

namespace hcf\faction\event;

use hcf\faction\Faction;
use hcf\session\Session;

final class FactionDisbandEvent extends FactionEvent {

	public function __construct(
		private Faction $faction,
		private Session $session
	) {}

	public function getFaction() : Faction {
		return $this->faction;
	}

	public function getSession() : Session {
		return $this->session;
	}
}
