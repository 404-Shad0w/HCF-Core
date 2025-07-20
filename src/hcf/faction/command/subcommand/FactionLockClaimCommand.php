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

namespace hcf\faction\command\subcommand;

use hcf\faction\command\FactionSubcommand;
use hcf\session\SessionFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class FactionLockClaimCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('lockclaim', 'Use the command to block enemies from entering your Faction', 'lock');
	}

	public function execute(CommandSender $sender, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}
		$session = SessionFactory::get($sender);

		if ($session === null) {
			return;
		}

		if ($session->getFaction() === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction.'));
			return;
		}
		
		$faction = $session->getFaction();
		
		if ($faction->getClaim() === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou must claim your protection first.'));
			return;
        }

		if ($faction->getLockClaim()) {
			$faction->setLockClaim(false);
			$sender->sendMessage(TextFormat::colorize('&cYou have just deactivated the protection of your claim.'));
		} else {
			$faction->setLockClaim(true);
			$sender->sendMessage(TextFormat::colorize('&aYou have just activated the protection of your claim.'));
		}
	}
}
