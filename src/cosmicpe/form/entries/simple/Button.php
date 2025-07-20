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

namespace cosmicpe\form\entries\simple;

use cosmicpe\form\entries\FormEntry;
use cosmicpe\form\types\Icon;

final class Button implements FormEntry {

	private string $title;

	private ?Icon $icon;

	public function __construct(string $title, ?Icon $icon = null) {
		$this->title = $title;
		$this->icon = $icon;
	}

	public function jsonSerialize() : array {
		return [
			"text" => $this->title,
			"image" => $this->icon
		];
	}
}
