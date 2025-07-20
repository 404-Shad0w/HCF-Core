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

namespace hcf\item;

use hcf\entity\EnderPearlEntity;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;

final class EnderPearl extends \pocketmine\item\EnderPearl {
    
    public function getMaxStackSize() : int {
        return 16;
    }

    protected function createEntity(Location $location, Player $thrower): Throwable {
        return new EnderPearlEntity($location, $thrower);
    }

    public function getThrowForce(): float {
        return 1.9;
    }

}
