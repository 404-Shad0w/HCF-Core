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

namespace hcf\faction\command;

use pocketmine\command\CommandSender;

abstract class FactionSubcommand {

	public function __construct(
		private string  $name,
		private string  $description = '',
		private ?string $alias = null,
		private ?string $permission = null
	) {}

	public function getName() : string {
		return $this->name;
	}

	public function getDescription() : string {
		return $this->description;
	}

	public function getAlias() : ?string {
		return $this->alias;
	}

	public function getPermission() : ?string {
		return $this->permission;
	}

	abstract public function execute(CommandSender $sender, array $args) : void;
}
