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

namespace cosmicpe\form\entries\custom;

use cosmicpe\form\entries\ModifiableEntry;
use InvalidArgumentException;
use function is_bool;

final class ToggleEntry implements CustomFormEntry, ModifiableEntry {

	private string $title;

	private bool $default;

	public function __construct(string $title, bool $default = false) {
		$this->title = $title;
		$this->default = $default;
	}

	public function getValue() : bool {
		return $this->default;
	}

	public function setValue($value) : void {
		$this->default = $value;
	}

	public function validateUserInput(mixed $input) : void {
		if (!is_bool($input)) {
			throw new InvalidArgumentException("Failed to process invalid user input: " . $input);
		}
	}

	public function jsonSerialize() : array {
		return [
			"type" => "toggle",
			"text" => $this->title,
			"default" => $this->default
		];
	}
}
