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
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\Limits;
use pocketmine\utils\TextFormat;
use function array_filter;
use function count;
use function strtolower;

final class BardClass extends KitClass {

	public function __construct() {
		parent::__construct('Bard', true, [VanillaItems::GOLDEN_HELMET(), VanillaItems::GOLDEN_CHESTPLATE(), VanillaItems::GOLDEN_LEGGINGS(), VanillaItems::GOLDEN_BOOTS()], [new EffectInstance(VanillaEffects::SPEED(), Limits::INT32_MAX, 1), new EffectInstance(VanillaEffects::RESISTANCE(), Limits::INT32_MAX), new EffectInstance(VanillaEffects::REGENERATION(), Limits::INT32_MAX)]);
	}

	public function handleAdd(Player $player) : void {
		parent::handleAdd($player);
		$session = SessionFactory::get($player);

		if ($session !== null && $session->getEnergy(strtolower($this->getName()) . '_energy') === null) {
			$session->addEnergy(strtolower($this->getName()) . '_energy', '&9Bard Energy&r&7:', 120);
		}
	}

	public function handleRemove(Player $player) : void {
		parent::handleRemove($player);
		$session = SessionFactory::get($player);

		if ($session !== null && $session->getEnergy(strtolower($this->getName()) . '_energy') !== null) {
			$session->removeEnergy(strtolower($this->getName()) . '_energy');
		}
	}

	public function handleItemHeld(PlayerItemHeldEvent $event) : void {
		$player = $event->getPlayer();
		$item = $event->getItem();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if (!$session->getKitClass() instanceof BardClass) {
			return;
		}

		if ($item->getTypeId() === ItemTypeIds::BLAZE_POWDER) {
			$this->holdEffect($session, new EffectInstance(VanillaEffects::STRENGTH(), 5 * 20));
		} elseif ($item->getTypeId() === ItemTypeIds::IRON_INGOT) {
			$this->holdEffect($session, new EffectInstance(VanillaEffects::RESISTANCE(), 5 * 20));
		} elseif ($item->getTypeId() === ItemTypeIds::GHAST_TEAR) {
			$this->holdEffect($session, new EffectInstance(VanillaEffects::REGENERATION(), 5 * 60));
		}
	}

	public function handleItemUse(PlayerItemUseEvent $event) : void {
		$player = $event->getPlayer();
		$item = $event->getItem();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if (!$session->getKitClass() instanceof BardClass) {
			return;
		}

		if ($session->getTimer('bard_timer') !== null) {
			return;
		}
		$energy = $session->getEnergy(strtolower($this->getName()) . '_energy');

		if ($energy === null) {
			return;
		}

		if ($item->getTypeId() === ItemTypeIds::SUGAR) {
			if ($energy->getValue() < 20) {
				$player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
				return;
			}
			$energy->decreaseValue(20);
			$session->addTimer('bard_timer', '&eBard Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$this->holdEffect($session, new EffectInstance(VanillaEffects::SPEED(), 6 * 20, 2));
		} elseif ($item->getTypeId() === ItemTypeIds::FEATHER) {
			if ($energy->getValue() < 25) {
				$player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
				return;
			}
			$energy->decreaseValue(25);
			$session->addTimer('bard_timer', '&l&eBard Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$this->holdEffect($session, new EffectInstance(VanillaEffects::JUMP_BOOST(), 6 * 20, 6));
		} elseif ($item->getTypeId() === ItemTypeIds::BLAZE_POWDER) {
			if ($energy->getValue() < 40) {
				$player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
				return;
			}
			$energy->decreaseValue(40);
			$session->addTimer('bard_timer', '&eBard Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$this->holdEffect($session, new EffectInstance(VanillaEffects::STRENGTH(), 6 * 20, 1));
		} elseif ($item->getTypeId() === ItemTypeIds::IRON_INGOT) {
			if ($energy->getValue() < 40) {
				$player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
				return;
			}
			$energy->decreaseValue(40);
			$session->addTimer('bard_timer', '&eBard Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$this->holdEffect($session, new EffectInstance(VanillaEffects::RESISTANCE(), 6 * 20, 2));
		} elseif ($item->getTypeId() === ItemTypeIds::SPIDER_EYE) {
			if ($energy->getValue() < 30) {
				$player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
				return;
			}
			$energy->decreaseValue(30);
			$session->addTimer('bard_timer', '&eBard Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$this->holdEffect($session, new EffectInstance(VanillaEffects::WITHER(), 6 * 20, 1));
		} elseif ($item->getTypeId() === ItemTypeIds::GHAST_TEAR) {
			if ($energy->getValue() < 40) {
				$player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
				return;
			}
			$energy->decreaseValue(40);
			$session->addTimer('bard_timer', '&eBard Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$this->holdEffect($session, new EffectInstance(VanillaEffects::REGENERATION(), 6 * 60, 2));
		} elseif ($item->getTypeId() === ItemTypeIds::MAGMA_CREAM) {
			if ($energy->getValue() < 40) {
				$player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
				return;
			}
			$energy->decreaseValue(40);
			$session->addTimer('bard_timer', '&eBard Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$this->holdEffect($session, new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), 45 * 20, 2));
		}
	}

	private function holdEffect(Session $session, EffectInstance $effectInstance) : void {
		$player = $session->getPlayer();
		$faction = $session->getFaction();
		$player?->getEffects()->add($effectInstance);

		if ($player !== null && $faction !== null) {
			$players = array_filter($player->getWorld()->getPlayers(), function (Player $target) use ($player, $faction) : bool {
				$t = SessionFactory::get($target);

				if ($t !== null && !$faction->equals($t->getFaction())) {
					return false;
				}

				if ($t->getTimer('pvp_timer') !== null || $t->getTimer('starting_timer') !== null) {
					return false;
				}
				return $player->getPosition()->distance($target->getPosition()) <= 10;
			});

			if (count($players) !== 0) {
				foreach ($players as $target) {
					$target->getEffects()->add($effectInstance);
				}
			}
		}
	}
}
