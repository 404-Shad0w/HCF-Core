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

namespace hcf\session\timer;

trait TimerTrait {

	/** @var Timer[] */
	private array $timers = [];

	/**
	 * @return Timer[]
	 */
	public function getTimers() : array {
		return $this->timers;
	}

	public function getTimer(string $name) : ?Timer {
		return $this->timers[$name] ?? null;
	}

	public function addTimer(string $name, string $format, int|float $time, bool $paused = false, bool $visible = true) : void {
		$this->timers[$name] = new Timer($this, $name, $format, $time, $paused, $visible);
	}

	public function removeTimer(string $name) : void {
		if (!$this->existsTimer($name)) {
			return;
		}
		unset($this->timers[$name]);
	}

	public function existsTimer(string $name) : bool {
		return isset($this->timers[$name]);
	}

	private function updateTimers() : void {
		foreach ($this->timers as $timer) {
			$timer->update();
		}
	}
}
