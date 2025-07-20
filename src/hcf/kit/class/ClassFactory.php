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

namespace hcf\kit\class;

use hcf\kit\class\default\ArcherClass;
use hcf\kit\class\default\BardClass;
use hcf\kit\class\default\MageClass;
use hcf\kit\class\default\MinerClass;
use hcf\kit\class\default\RogueClass;
use hcf\kit\class\default\PythonClass;
use pocketmine\event\Event;

final class ClassFactory {

	/** @var KitClass[] */
	private static array $classes = [];

	public static function getAll() : array {
		return self::$classes;
	}

	public static function get(string $name) : ?KitClass {
		return self::$classes[$name] ?? null;
	}

	public static function loadAll() : void {
		self::create(new ArcherClass());
		self::create(new BardClass());
		self::create(new MageClass());
        self::create(new MinerClass());
		self::create(new RogueClass());
        self::create(new PythonClass());
	}

	private static function create(KitClass $kitClass) : void {
		self::$classes[$kitClass->getName()] = $kitClass;
	}

	public static function callEvent(string $eventName, Event $event) : void {
		foreach (self::getAll() as $kitClass) {
			$kitClass->$eventName($event);
		}
	}
}
