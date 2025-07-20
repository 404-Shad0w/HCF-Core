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

namespace hcf\session\energy;

use pocketmine\utils\TextFormat;

final class Energy {

	public function __construct(
		private string $format,
		private int    $maxValue,
		private float    $value = 0.0
	) {}

	public function getFormat() : string {
		return TextFormat::colorize($this->format);
	}

	public function getValue() : float {
		return $this->value;
	}

	public function decreaseValue(int $count = 1) : void {
		$this->value -= $count;
	}

	public function update() : void {
		if ($this->value < $this->maxValue) {
            $this->value += 1 / 10;
            //$this->value++;
		}
	}
}
