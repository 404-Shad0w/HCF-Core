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

use hcf\claim\ClaimFactory;
use hcf\faction\command\FactionSubcommand;
use hcf\faction\member\FactionMember;
use hcf\session\SessionFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class FactionUnclaimCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('unclaim', 'Use command to remove your faction claim');
	}

	public function execute(CommandSender $sender, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}
		$session = SessionFactory::get($sender);

		if ($session === null) {
			return;
		}
		$faction = $session->getFaction();

		if ($session->getFaction() === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction.'));
			return;
		}

		if ($session->getClaimCreatorHandler() !== null) {
			$sender->sendMessage(TextFormat::colorize('&cYou are in claim mode.'));
			return;
		}
		$member = $faction->getMember($session);

		if ($member === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction.'));
			return;
		}

		if ($member->getRank() !== FactionMember::RANK_LEADER) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have permission to unclaim.'));
			return;
		}

		if ($faction->getClaim() === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou faction no has claim.'));
			return;
		}
        
        if ($faction->getDeathsUntilRaidable() <= 0.00) {
			$sender->sendMessage(TextFormat::colorize('&cYour faction has regen cooldown.'));
			return;
		}
		ClaimFactory::remove($faction->getName());
		$faction->setClaim(null);

		$sender->sendMessage(TextFormat::colorize('&cYou have removed the claim from your faction.'));
	}
}
