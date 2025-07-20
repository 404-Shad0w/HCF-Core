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

namespace hcf\timer\default;

use hcf\faction\event\FactionAddPointEvent;
use hcf\timer\Timer;

final class DoublePoints extends Timer {

	public function __construct() {
		parent::__construct('Double Points', 'Use timer to double points for the factions.', '&2x2 points&r&7:', 30 * 60);
	}

	public function handleAddPoint(FactionAddPointEvent $event) : void {
		$points = $event->getPoints();
		$event->setPoints($points * 2);
	}
}
