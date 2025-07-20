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

use hcf\timer\Timer;
use pocketmine\Server;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;

final class FreeKitsEvent extends Timer {

	public function __construct() {
		parent::__construct('FreeKits', 'Use timer to free kits event', '&9FreeKits ends in&r&7:', 60 * 60);
	}
    
    public function setEnabled(bool $enabled) : void {
        parent::setEnabled($enabled);
    }
    
    public function update() : void {
        if ($this->enabled) {
            if ($this->progress <= 0) {
                $this->setEnabled(false);
				$this->progress = $this->time;
                return;
            }
            foreach (Server::getInstance()->getOnlinePlayers() as $online_player) {
                $online_player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), 20 * 3, 1));
            }
            $this->progress--;
        }
    }
}

