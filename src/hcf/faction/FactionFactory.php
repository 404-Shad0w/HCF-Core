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

namespace hcf\faction;

use hcf\faction\member\FactionMember;
use hcf\HCF;
use hcf\session\Session;
use JsonException;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use function basename;
use function file_exists;
use function glob;
use function is_dir;
use function mkdir;
use function unlink;
use const DIRECTORY_SEPARATOR;

final class FactionFactory {

	/** @var Faction[] */
	private static array $factions = [];

	public static function getAll() : array {
		return self::$factions;
	}

	public static function create(string $name, ?Session $leader = null) : Faction {
		$faction = new Faction(name: $name, balance: 0, points: 0, kothCaptures: 0, strikes: 0);

		if ($leader !== null) {
			$faction->addMember($leader, FactionMember::RANK_LEADER);
			$leader->setFaction($faction);
		}
		self::$factions[$name] = $faction;

		return $faction;
	}

	public static function remove(string $name) : void {
		if (self::get($name) === null) {
			return;
		}
		unset(self::$factions[$name]);

		if (file_exists(HCF::getInstance()->getDataFolder() . 'factions' . DIRECTORY_SEPARATOR . $name . '.json')) {
			@unlink(HCF::getInstance()->getDataFolder() . 'factions' . DIRECTORY_SEPARATOR . $name . '.json');
		}
	}

	public static function get(string $name) : ?Faction {
		return self::$factions[$name] ?? null;
	}

	public static function loadAll() : void {
		$dir = HCF::getInstance()->getDataFolder() . 'factions';

		if (!is_dir($dir)) {
			@mkdir($dir);
		}
		$files = glob($dir . DIRECTORY_SEPARATOR . '*.json');

		foreach ($files as $file) {
			$config = new Config($dir . DIRECTORY_SEPARATOR . basename($file), Config::JSON);
			self::$factions[basename($file, '.json')] = Faction::deserializeData(basename($file, '.json'), $config->getAll());
		}
	}

	/**
	 * @throws JsonException
	 */
	public static function saveAll() : void {
		$dir = HCF::getInstance()->getDataFolder() . 'factions';

		if (!is_dir($dir)) {
			@mkdir($dir);
		}

		foreach (self::getAll() as $name => $faction) {
			$file = new Config($dir . DIRECTORY_SEPARATOR . $name . '.json', Config::JSON);
			$file->setAll($faction->serializeData());
			$file->save();
		}
	}

	public static function task() : void {
		HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () : void {
			foreach (self::getAll() as $faction) {
				$faction->update();
			}
		}), 20);
	}
}
