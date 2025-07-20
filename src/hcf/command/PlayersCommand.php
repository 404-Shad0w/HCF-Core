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

namespace hcf\command;

use hcf\HCF;
use hcf\util\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function count;
use function implode;
use function time;
use const PHP_EOL;

final class PlayersCommand extends Command {

	public function __construct() {
		parent::__construct('players', 'Use command to total players playing');
        $this->setPermission('player.command.use');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		$lines = [
			'&r&r',
			' &gTotal players: &f' . count($sender->getServer()->getOnlinePlayers()),
			' &gTotal time active: &f' . Utils::date(time() - HCF::getTotalTime()),
		];
		$server = $sender->getServer();
		$tpsColor = TextFormat::GREEN;

		if ($server->getTicksPerSecond() < 12) {
			$tpsColor = TextFormat::RED;
		} elseif ($server->getTicksPerSecond() < 17) {
			$tpsColor = TextFormat::GOLD;
		}

		if ($sender->hasPermission('god.permission')) {
			$lines[] = ' &gCurrent TPS: ' . $tpsColor . $server->getTicksPerSecond() . ' (' . $server->getTickUsage() . '%)';
			$lines[] = ' &gAverage TPS: ' . $tpsColor . $server->getTicksPerSecondAverage() . ' (' . $server->getTickUsageAverage() . '%)';
		}
		$lines[] = '&r&r';
		$sender->sendMessage(TextFormat::colorize(implode(PHP_EOL, $lines)));
	}
}
