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

namespace cosmicpe\form\types;

use JsonSerializable;

final class Icon implements JsonSerializable {

	public const URL = "url";
	public const PATH = "path";

	private string $type;

	private string $data;

	public function __construct(string $type, string $data) {
		$this->type = $type;
		$this->data = $data;
	}

	public function jsonSerialize() : array {
		return [
			"type" => $this->type,
			"data" => $this->data
		];
	}
}
