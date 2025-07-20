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

namespace hcf\timer;

use hcf\faction\event\FactionAddPointEvent;
use pocketmine\event\player\PlayerJoinEvent;

interface TimerInterface {

	public function handleJoin(PlayerJoinEvent $event) : void;
	public function handleAddPoint(FactionAddPointEvent $event) : void;
}
