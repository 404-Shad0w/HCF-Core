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
use hcf\faction\member\FactionMember;
use hcf\session\SessionFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use RuntimeException;

final class FactionClaimCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('claim', 'Use command to claim for your faction.');
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
			$sender->sendMessage(TextFormat::colorize('&cYou are already in claim mode.'));
			return;
		}
		$member = $faction->getMember($session);

		if ($member === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction.'));
			return;
		}

		if ($member->getRank() !== FactionMember::RANK_LEADER) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have permission to claim.'));
			return;
		}

		if ($faction->getClaim() !== null) {
			$sender->sendMessage(TextFormat::colorize('&cYou faction already has claim.'));
			return;
		}
		$claimCreatorHandler = $session->startClaimCreatorHandler($session, $faction->getName());

		try {
			$claimCreatorHandler->prepare($sender);
			$sender->sendMessage(TextFormat::colorize('&eSelect the corner points. Left click for first corner and Right click for second corner.'));
		} catch (RuntimeException $exception) {
			$session->stopClaimCreatorHandler();
			$sender->sendMessage(TextFormat::colorize($exception->getMessage()));
		}
	}
}
