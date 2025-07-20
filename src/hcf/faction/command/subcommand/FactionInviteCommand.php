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
use hcf\faction\event\FactionInviteEvent;
use hcf\faction\invite\InviteFactory;
use hcf\faction\member\FactionMember;
use hcf\session\SessionFactory;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

final class FactionInviteCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('invite', 'Use command to invite player to your faction');
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
			$sender->sendMessage(TextFormat::colorize('&cYou don\'t have permission for invite player to your faction.'));
			return;
		}

		if ($faction->isFull()) {
			$sender->sendMessage(TextFormat::colorize('&cYour faction is full.'));
			return;
		}
        
        if ($faction->getDeathsUntilRaidable() <= 0.00) {
			$sender->sendMessage(TextFormat::colorize('&cYour faction has regen cooldown.'));
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction invite [player]'));
			return;
		}
		$player = $sender->getServer()->getPlayerByPrefix($args[0]);

		if (!$player instanceof Player || $player->getId() === $sender->getId()) {
			$sender->sendMessage(TextFormat::colorize('&cPlayer is offline.'));
			return;
		}
		$target = SessionFactory::get($player);

		if ($target === null) {
			$sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
			return;
		}

		if ($target->getFaction() !== null) {
			$sender->sendMessage(TextFormat::colorize('&cPlayer already has faction.'));
			return;
		}
		$event = new FactionInviteEvent($faction, $session, $target);
		$event->call();

		if ($event->isCancelled()) {
			return;
		}
		InviteFactory::create($target, $faction);

		$player->sendMessage(TextFormat::colorize('&a' . $sender->getName() . ' has invited you to join ' . $faction->getName() . ' faction. &7(/f join '. $faction->getName() . ')'));
        $sender->getWorld()->addSound($sender->getPosition()->asVector3(), new XpCollectSound(), [$sender]);
        $player->getWorld()->addSound($sender->getPosition()->asVector3(), new XpCollectSound(), [$sender]);
		$sender->sendMessage(TextFormat::colorize('&aYou have invited ' . $player->getName() . ' to join your faction.'));
	}
}
