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

use hcf\claim\ClaimHandler;
use hcf\faction\command\FactionSubcommand;
use hcf\HCF;
use hcf\session\SessionFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;
use function mt_rand;

final class FactionStuckCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('stuck', 'Use command to leave claim zone');
	}

	public function execute(CommandSender $sender, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}
		$session = SessionFactory::get($sender);

		if ($session === null) {
			return;
		}

		if ($sender->getWorld()->getFolderName() !== $sender->getServer()->getWorldManager()->getDefaultWorld()->getFolderName()) {
			$sender->sendMessage(TextFormat::colorize('&cYou cannot use this command.'));
			return;
		}
		$currentClaim = $session->getCurrentClaim();

		if ($currentClaim === null) {
			$sender->sendMessage(TextFormat::colorize('&cYou cannot use this command.'));
			return;
		}
		$faction = $session->getFaction();

		if ($faction !== null && $faction->getClaim() !== null && $faction->getClaim()->equals($currentClaim)) {
			$sender->sendMessage(TextFormat::colorize('&cYou cannot use this command.'));
			return;
		}

		if ($session->getTimer('faction_stuck') !== null) {
			$sender->sendMessage(TextFormat::colorize('&cYou already in stuck.'));
			return;
		}
		$session->addTimer('faction_stuck', '&3Stuck&r&7:', (int) HCF::getInstance()->getConfig()->get('faction.stuck-cooldown', 60));

		$position = $sender->getPosition();
		$handler = HCF::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use (&$handler, &$sender, &$session, &$position) : void {
			if (!$sender->isOnline() || $session->getTimer('spawn_tag') !== null || $position->distance($sender->getPosition()) > 2) {
				$session->removeTimer('faction_stuck');
				$handler->cancel();
				return;
			}

			if ($session->getTimer('faction_stuck') === null) {
				$this->teleport($sender);
				$handler->cancel();
			}
		}), 20);
	}

	private function teleport(Player $player) : void {
		$world = $player->getWorld();
		$vector = $player->getPosition()->asVector3();

		$x = mt_rand($vector->getFloorX() - 100, $vector->getFloorX() + 100);
		$z = mt_rand($vector->getFloorZ() - 100, $vector->getFloorZ() + 100);

		if (!$world->isChunkLoaded($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE)) {
			$world->loadChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE);
		}
		$y = $world->getHighestBlockAt($x, $z);
		$pos = new Position($x, $y, $z, $world);
		$claim = ClaimHandler::insideClaim($pos);

		if ($claim !== null) {
			$this->teleport($player);
			return;
		}
		$player->teleport($pos->add(0, 1, 0), $player->getLocation()->getYaw(), $player->getLocation()->getPitch());
	}
}
