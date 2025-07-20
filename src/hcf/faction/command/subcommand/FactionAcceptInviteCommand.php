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
use hcf\faction\event\FactionAcceptInviteEvent;
use hcf\faction\invite\Invite;
use hcf\faction\invite\InviteFactory;
use hcf\session\SessionFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

final class FactionAcceptInviteCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('accept', 'Use command to accept invite to join faction.', 'join');
	}

	public function execute(CommandSender $sender, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}
		$session = SessionFactory::get($sender);

		if ($session === null) {
			return;
		}

		if ($session->getFaction() !== null) {
			$sender->sendMessage(TextFormat::colorize('&cYou already have faction.'));
			return;
		}
		$invites = InviteFactory::get($session);

		if ($invites === null || count($invites) === 0) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have invites.'));
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction accept [faction]'));
			return;
		}
		$factionName = $args[0];

		if (!isset($invites[$factionName])) {
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have invite from this faction.'));
			return;
		}
		/** @var Invite $invite */
		$invite = $invites[$factionName];
		$faction = $invite->getFaction();

		if ($invite->isExpired()) {
			InviteFactory::removeFromFaction($session, $faction);
			$sender->sendMessage(TextFormat::colorize('&cInvite already expired.'));
			return;
		}

		if (!$invite->exitsFaction()) {
			InviteFactory::removeFromFaction($session, $faction);
			$sender->sendMessage(TextFormat::colorize('&cFaction invite not found.'));
			return;
		}

		if ($faction->isFull()) {
			InviteFactory::removeFromFaction($session, $faction);
			$sender->sendMessage(TextFormat::colorize('&cFaction is already full'));
			return;
		}

		if ($faction->getRegenCooldown() !== -1) {
			InviteFactory::removeFromFaction($session, $faction);
			$sender->sendMessage(TextFormat::colorize('&cFaction has regen cooldown.'));
			return;
		}
		$event = new FactionAcceptInviteEvent($faction, $session);
		$event->call();

		if ($event->isCancelled()) {
			return;
		}
		$faction->announce('&e[Faction] ' . $session->getName() . ' joined the faction.');
		$faction->addMember($session);
		$faction->setDeathsUntilRaidable(count($faction->getMembers()) + 0.1);
		$session->setFaction($faction);

		foreach ($faction->getOnlineMembers() as $member) {
			$member->getSession()->getPlayer()?->setScoreTag(TextFormat::colorize('&6[&c' . $faction->getName() . ' &c' . $faction->getDeathsUntilRaidable() . '&6]'));
		}
		$sender->setScoreTag(TextFormat::colorize('&6[&c' . $faction->getName() . ' &c' . $faction->getDeathsUntilRaidable() . '&6]'));
		$sender->sendMessage(TextFormat::colorize('&aYou have accepted faction invitation'));

		InviteFactory::remove($session);
	}
}
