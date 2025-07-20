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

namespace hcf\session;

use hcf\HCF;
use hcf\util\Utils;
use JsonException;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use function basename;
use function count;
use function glob;
use function is_dir;
use function mkdir;
use const DIRECTORY_SEPARATOR;

final class SessionFactory {

	/** @var Session[] */
	private static array $sessions = [];

	public static function get(Player|string $player) : ?Session {
		$guid = !$player instanceof Player ? $player : $player->getXuid();
		return self::$sessions[$guid] ?? null;
	}

	public static function create(Player $player) : void {
		self::$sessions[$player->getXuid()] = new Session($player->getXuid(), $player->getUniqueId()->getBytes(), $player->getName());
	}

	public static function loadAll() : void {
		$dir = HCF::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . 'sessions';

		if (!is_dir($dir)) {
			@mkdir($dir);
		}
		$files = glob($dir . DIRECTORY_SEPARATOR . '*.json');

		foreach ($files as $file) {
			$config = new Config($dir . DIRECTORY_SEPARATOR . basename($file), Config::JSON);
			self::$sessions[basename($file, '.json')] = Session::deserializeData(basename($file, '.json'), $config->getAll());
		}
	}

	/**
	 * @throws JsonException
	 */
	public static function saveAll() : void {
		$dir = HCF::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . 'sessions';

		if (!is_dir($dir)) {
			@mkdir($dir);
		}

		foreach (self::getAll() as $xuid => $session) {
			$file = new Config($dir . DIRECTORY_SEPARATOR . $xuid . '.json', Config::JSON);
			$file->setAll($session->serializeData());
			$file->save();
		}
	}

	public static function task() : void {
		HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () : void {
            foreach (self::getAll() as $session) {
                $session->update();
            }
		}), 2);
        $scoreAnimated = Utils::getScoreFile();
        if ($scoreAnimated->get("score-animated-active")) {
            $delay = $scoreAnimated->get("delay");
            $period = $scoreAnimated->get("period");
            HCF::getInstance()->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function(): void {
                foreach (self::getAll() as $session) {
                    $session->getScoreboard()->updateScoreboardAnimated();
                }
            }), $delay, $period);
        }
	}

	public static function getAll() : array {
		return self::$sessions;
	}
}
