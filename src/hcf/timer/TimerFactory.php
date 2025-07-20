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

namespace hcf\timer;

use hcf\HCF;
use hcf\timer\default\DoublePoints;
use hcf\timer\default\EndOfTheWorld;
use hcf\timer\default\PackageAll;
use hcf\timer\default\BoxAll;
use hcf\timer\default\KeyAll;
use hcf\timer\default\Discount;
use hcf\timer\default\OpKeyAll;
use hcf\timer\default\FreeKitsEvent;
use hcf\timer\default\PurgeEvent;
use hcf\timer\default\Ffa;
use hcf\timer\default\AirdropAll;
use hcf\timer\default\StartOfTheWorld;
use pocketmine\scheduler\ClosureTask;

final class TimerFactory {

	/** @var Timer[] */
	private static array $timers = [];

	public static function getAll() : array {
		return self::$timers;
	}

	public static function get(string $name) : ?Timer {
		return self::$timers[$name] ?? null;
	}

	private static function register(Timer $timer) : void {
		self::$timers[$timer->getName()] = $timer;
	}

	public static function loadAll() : void {
		self::register(new DoublePoints());
		self::register(new PurgeEvent());
        self::register(new FreeKitsEvent());
        self::register(new PackageAll());
        self::register(new BoxAll());
		self::register(new KeyAll());
		self::register(new OpKeyAll());
		self::register(new Discount());
		self::register(new StartOfTheWorld());
        self::register(new Ffa());
        self::register(new AirdropAll());
		self::register(new EndOfTheWorld());
	}

	public static function saveAll() : void {
		foreach (self::$timers as $timer) {
			if ($timer->isEnabled()) {
				$timer->setEnabled(false);
			}
		}
	}

	public static function task() : void {
		HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () : void {
			foreach (self::getAll() as $timer) {
				if ($timer->isEnabled()) {
					$timer->update();
				}
			}
		}), 20);
	}
}
