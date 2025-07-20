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

namespace hcf\kit;

use hcf\HCF;
use JsonException;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use function is_dir;
use function mkdir;
use const DIRECTORY_SEPARATOR;

final class KitFactory {

	private static array $kits = [];

	public static function create(string $name, Item $itemDecorative, ?string $colorize = null, ?string $permission = null, ?int $countdown = null, array $inventory = [], array $armorInventory = []) : void {
		self::$kits[$name] = new Kit($name, $colorize, $permission, $countdown, null, $itemDecorative, $inventory, $armorInventory);
	}

	public static function remove(string $name) : void {
		if (self::get($name) === null) {
			return;
		}
		unset(self::$kits[$name]);
	}

	public static function get(string $name) : ?Kit {
		return self::$kits[$name] ?? null;
	}

	public static function loadAll() : void {
		$dir = HCF::getInstance()->getDataFolder() . 'storage';

		if (!is_dir($dir)) {
			@mkdir($dir);
		}
		$kits = new Config($dir . DIRECTORY_SEPARATOR . 'kits.json', Config::JSON);

		foreach ($kits->getAll() as $name => $data) {
			self::$kits[$name] = Kit::jsonDeserialize($name, $data);
		}
	}

	/**
	 * @return Kit[]
	 */
	public static function getAll() : array {
		return self::$kits;
	}

	/**
	 * @throws JsonException
	 */
	public static function saveAll() : void {
		$dir = HCF::getInstance()->getDataFolder() . 'storage';

		if (!is_dir($dir)) {
			@mkdir($dir);
		}
		$kits = new Config($dir . DIRECTORY_SEPARATOR . 'kits.json', Config::JSON);
		$data = [];

		foreach (self::getAll() as $name => $kit) {
			$data[$name] = $kit->jsonSerialize();
		}
		$kits->setAll($data);
		$kits->save();
	}
}
