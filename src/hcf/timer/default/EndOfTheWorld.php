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

namespace hcf\timer\default;

use hcf\claim\Claim;
use hcf\claim\ClaimFactory;
use hcf\faction\FactionFactory;
use hcf\timer\Timer;

final class EndOfTheWorld extends Timer {

	/** @var Claim[] */
	private array $claims = [];

	public function __construct() {
		parent::__construct('eotw', 'Use timer to End Of The World', '&4EOTW end in&r&7:', 60 * 60);
	}

	public function setEnabled(bool $enabled) : void {
		parent::setEnabled($enabled);

		if ($enabled) {
			foreach (FactionFactory::getAll() as $faction) {
				if ($faction->getClaim() === null) {
					continue;
				}
				$claim = $faction->getClaim();

				if ($claim->getType() !== Claim::FACTION) {
					continue;
				}
				$this->claims[$faction->getName()] = $claim;
				ClaimFactory::remove($faction->getName());

				$faction->setClaim(null);
			}
		} else {
			foreach ($this->claims as $name => $claim) {
				FactionFactory::get((string)$name)?->setClaim($claim);
				ClaimFactory::create((string)$claim->getName(), $claim->getFirstPosition(), $claim->getSecondPosition(), $claim->getWorld());
			}
		}
	}
}
