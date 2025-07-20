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

trait EnergyTrait {

	/** @var Energy[] */
	private array $energies = [];

	public function getEnergies() : array {
		return $this->energies;
	}

	public function addEnergy(string $name, string $format, int $maxEnergy) : void {
		$this->energies[$name] = new Energy($format, $maxEnergy);
	}

	public function removeEnergy(string $name) : void {
		if ($this->getEnergy($name) === null) {
			return;
		}
		unset($this->energies[$name]);
	}

	public function getEnergy(string $name) : ?Energy {
		return $this->energies[$name] ?? null;
	}

	public function updateEnergies() : void {
		foreach ($this->energies as $energy) {
			$energy->update();
		}
	}
}
