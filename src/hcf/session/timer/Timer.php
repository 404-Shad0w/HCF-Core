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

use hcf\session\Session;
use pocketmine\utils\TextFormat;

final class Timer {

	public function __construct(
		private Session $session,
		private string  $name,
		private string  $format,
		private float     $time,
		private bool    $paused,
		private bool    $visible
	) {}

	public function getDefaultFormat() : string {
		return $this->format;
	}

	public function getFormat() : string {
		return TextFormat::colorize($this->format);
	}

	public function getTime() : float {
		return $this->time;
	}

	public function isPaused() : bool {
		return $this->paused;
	}

	public function isVisible() : bool {
		return $this->visible;
	}

	public function setPaused(bool $paused) : void {
		$this->paused = $paused;
	}

	public function update() : void {
		if (!$this->paused) {
			if ($this->isExpired()) {
				$this->session->removeTimer($this->name);
				return;
			}
			$this->time -= 1 / 10;
			//$this->time--;
		}
	}

	public function isExpired() : bool {
		return $this->time <= 0;
	}
}
