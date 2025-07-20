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

namespace hcf\faction\command\subcommand\admin;

use hcf\claim\Claim;
use hcf\faction\command\FactionSubcommand;
use hcf\faction\FactionFactory;
use hcf\session\SessionFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function count;

final class FactionOpClaimCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('opclaim', 'Use command to op claims', null, 'faction.opclaim.command');
	}

	public function execute(CommandSender $sender, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}
		$claims = [
			'Spawn' => Claim::SPAWN,
            'Nether Spawn' => Claim::SPAWN,
            'DeathBan Spawn' => Claim::SPAWN,
            'End' => Claim::ROAD,
            'DeathBan' => Claim::ROAD,
			'North Road' => Claim::ROAD,
			'South Road' => Claim::ROAD,
			'West Road' => Claim::ROAD,
			'East Road' => Claim::ROAD,
            'North_Road' => Claim::ROAD,
			'South_Road' => Claim::ROAD,
			'West_Road' => Claim::ROAD,
			'East_Road' => Claim::ROAD
		];
		$session = SessionFactory::get($sender);

		if (!$session === null) {
			return;
		}

		if ($session->getClaimCreatorHandler() !== null) {
			$sender->sendMessage(TextFormat::colorize('&cYou are already in claim mode.'));
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction opclaim [name]'));
			return;
		}
		$name = $args[0];

		if (!isset($claims[$name])) {
			$sender->sendMessage(TextFormat::colorize('&cInvalid opclaim.'));
			return;
		}
		$faction = FactionFactory::get($name);

		if ($faction === null) {
			FactionFactory::create($name);
		}
		$claimCreatorHandler = $session->startClaimCreatorHandler($session, $name, $claims[$name]);

		try {
			$claimCreatorHandler->prepare($sender);
			$sender->sendMessage(TextFormat::colorize('&eSelect the corner points. Left click for first corner and Right click for second corner.'));
		} catch (RuntimeException $exception) {
			$session->stopClaimCreatorHandler();
			$sender->sendMessage(TextFormat::colorize($exception->getMessage()));
		}
	}
}
