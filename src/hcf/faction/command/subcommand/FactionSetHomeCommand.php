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

final class FactionSetHomeCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('sethome', 'Use command to set home in your faction.');
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

		if ($faction === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction.'));
			return;
		}
		$member = $faction->getMember($session);

		if ($member === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have faction.'));
			return;
		}

		if ($member->getRank() === FactionMember::RANK_MEMBER) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have permission for set home of your faction.'));
			return;
		}
		$currentClaim = $session->getCurrentClaim();

		if ($currentClaim === null || $currentClaim->getDefaultName() !== $faction->getName()) {
			$sender->sendMessage(TextFormat::colorize('&cYou cannot set home outside your claim.'));
			return;
		}
		$faction->setHome($sender->getPosition());
		$sender->sendMessage(TextFormat::colorize('&aYou established your sethome in your faction.'));
	}
}
