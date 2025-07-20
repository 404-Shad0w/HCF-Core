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

abstract class Timer implements TimerInterface {

	public function __construct(
		protected string $name,
		protected string $description,
		protected string $format = '',
		protected int $time = 0,
		protected int $progress = 0,
		protected bool $enabled = false
	) {
		$this->progress = $time;
	}

	public function getName() : string {
		return $this->name;
	}

	public function getDescription() : string {
		return $this->description;
	}

	public function getFormat() : string {
		return $this->format;
	}

	public function getTime() : int {
		return $this->time;
	}

	public function getProgress() : int {
		return $this->progress;
	}

	public function isEnabled() : bool {
		return $this->enabled;
	}

	public function setTime(int $time) : void {
		$this->time = $time;
		$this->progress = $time;
	}

	public function update() : void {
		if ($this->enabled) {
			if ($this->progress <= 0) {
				$this->setEnabled(false);
				$this->progress = $this->time;
				return;
			}
			$this->progress--;
		}
	}

	public function setEnabled(bool $enabled) : void {
		$this->enabled = $enabled;
	}

	public function handleJoin(PlayerJoinEvent $event) : void {}
	public function handleAddPoint(FactionAddPointEvent $event) : void {}
}
