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

namespace hcf\kit\class;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;

interface KitClassInterface {

	public function handleDamage(EntityDamageEvent $event) : void;
	public function handleItemHeld(PlayerItemHeldEvent $event) : void;
	public function handleItemUse(PlayerItemUseEvent $event) : void;
}
