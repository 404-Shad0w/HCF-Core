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

namespace hcf\kit\class\default;

use hcf\kit\class\KitClass;
use hcf\session\Session;
use hcf\session\SessionFactory;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\Limits;
use pocketmine\utils\TextFormat;
use function array_filter;
use function count;
use function strtolower;

final class MageClass extends KitClass {

	public function __construct() {
		parent::__construct('Mage', true, [
			VanillaItems::GOLDEN_HELMET(),
			VanillaItems::CHAINMAIL_CHESTPLATE(),
			VanillaItems::CHAINMAIL_LEGGINGS(),
			VanillaItems::GOLDEN_BOOTS()
		], [
			new EffectInstance(VanillaEffects::SPEED(), Limits::INT32_MAX, 2),
			new EffectInstance(VanillaEffects::RESISTANCE(), Limits::INT32_MAX, 1),
			new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), Limits::INT32_MAX)
		]);
	}

	public function handleAdd(Player $player) : void {
		parent::handleAdd($player);
		$session = SessionFactory::get($player);

		if ($session !== null && $session->getEnergy(strtolower($this->getName()) . '_energy') === null) {
			$session->addEnergy(strtolower($this->getName()) . '_energy', '&dMage Energy&r&7:', 120);
		}
	}

	public function handleRemove(Player $player) : void {
		parent::handleRemove($player);
		$session = SessionFactory::get($player);

		if ($session !== null && $session->getEnergy(strtolower($this->getName()) . '_energy') !== null) {
			$session->removeEnergy(strtolower($this->getName()) . '_energy');
		}
	}

	public function handleItemUse(PlayerItemUseEvent $event) : void {
		$player = $event->getPlayer();
		$item = $event->getItem();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if (!$session->getKitClass() instanceof MageClass) {
			return;
		}

		if ($session->getTimer('mage_timer') !== null) {
			return;
		}
		$energy = $session->getEnergy(strtolower($this->getName()) . '_energy');

		if ($energy === null) {
			return;
		}

		if ($item->getTypeId() === ItemTypeIds::GOLD_NUGGET) {
			if ($energy->getValue() < 40) {
				$player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
				return;
			}
			$energy->decreaseValue(40);
			$session->addTimer('mage_timer', '&eMage Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$this->giveEffect($session, new EffectInstance(VanillaEffects::SLOWNESS(), 15 * 20, 1));
		} elseif ($item->getTypeId() === ItemTypeIds::SPIDER_EYE) {
			if ($energy->getValue() < 35) {
				$player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
				return;
			}
			$energy->decreaseValue(35);
			$session->addTimer('mage_timer', '&eMage Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$this->giveEffect($session, new EffectInstance(VanillaEffects::WITHER(), 15 * 20, 1));
		} elseif ($item->getTypeId() === ItemTypeIds::DYE && $item->getMeta() === 10) {
			if ($energy->getValue() < 45) {
				$player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
				return;
			}
			$energy->decreaseValue(45);
			$session->addTimer('mage_timer', '&eMage Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$this->giveEffect($session, new EffectInstance(VanillaEffects::POISON(), 15 * 20));
		} elseif ($item->getTypeId() === ItemTypeIds::COAL) {
			if ($energy->getValue() < 30) {
				$player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
				return;
			}
			$energy->decreaseValue(30);
			$session->addTimer('mage_timer', '&eMage Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$this->giveEffect($session, new EffectInstance(VanillaEffects::WEAKNESS(), 15 * 20));
		} elseif ($item->getTypeId() === ItemTypeIds::SEEDS) {
			if ($energy->getValue() < 40) {
				$player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
				return;
			}
			$energy->decreaseValue(40);
			$session->addTimer('mage_timer', '&eMage Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$this->giveEffect($session, new EffectInstance(VanillaEffects::NAUSEA(), 15 * 20));
		}
	}

	public function handleDamage(EntityDamageEvent $event) : void {
		$cause = $event->getCause();
		$player = $event->getEntity();

		if (!$player instanceof Player) {
			return;
		}
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if (!$session->getKitClass() instanceof MageClass) {
			return;
		}

		if ($cause === EntityDamageEvent::CAUSE_FALL) {
			$event->cancel();
		}
	}

	private function giveEffect(Session $session, EffectInstance $effectInstance) : void {
		$player = $session->getPlayer();
		$faction = $session->getFaction();

		if ($player !== null) {
			$players = array_filter($player->getWorld()->getPlayers(), function (Player $viewer) use ($session, $player) : bool {
				$target = SessionFactory::get($viewer);

				if ($target !== null && $target->getFaction() !== null && $target->getFaction()->equals($session->getFaction())) {
					return false;
				}

				if ($target->getTimer('pvp_timer') !== null || $target->getTimer('starting_timer') !== null) {
					return false;
				}
				return $player->getId() !== $viewer->getId() && $player->getPosition()->distance($viewer->getPosition()) <= 10;
			});

			if (count($players) !== 0) {
				foreach ($players as $target) {
					$target->getEffects()->add($effectInstance);
				}
			}
		}
	}
}
