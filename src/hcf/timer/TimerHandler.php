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
use pocketmine\event\Event;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

final class TimerHandler implements Listener, TimerInterface {

	public function handleJoin(PlayerJoinEvent $event) : void {
		$this->callEvent(__FUNCTION__, $event);
	}

	public function handleAddPoint(FactionAddPointEvent $event) : void {
		$this->callEvent(__FUNCTION__, $event);
	}

	private function callEvent(string $eventName, Event $event) : void {
		foreach (TimerFactory::getAll() as $timer) {
			if ($timer->isEnabled()) {
				$timer->$eventName($event);
			}
		}
	}
}
