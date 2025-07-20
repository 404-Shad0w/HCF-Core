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

final class LabelEntry implements CustomFormEntry {

	private string $title;

	public function __construct(string $title) {
		$this->title = $title;
	}

	public function jsonSerialize() : array {
		return [
			"type" => "label",
			"text" => $this->title
		];
	}
}
