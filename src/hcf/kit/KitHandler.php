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

namespace hcf\kit;

use hcf\HCF;
use hcf\kit\class\ClassFactory;
use hcf\kit\class\KitClassInterface;
use hcf\session\Session;
use hcf\session\SessionFactory;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityEffectRemoveEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

final class KitHandler implements Listener, KitClassInterface {

	public function handleDamage(EntityDamageEvent $event) : void {
		ClassFactory::callEvent(__FUNCTION__, $event);
	}

	public function handleEffectRemove(EntityEffectRemoveEvent $event) : void {
		$entity = $event->getEntity();

		IF (!$entity instanceof Player) {
			return;
		}
		$session = SessionFactory::get($entity);

		if ($session === null) {
			return;
		}
		$effect = $event->getEffect();

		if ($effect->getDuration() > 0) {
			return;
		}
		HCF::getInstance()->getScheduler()->scheduleTask(new ClosureTask(function () use($session) : void {
			$this->checkClass($session);
		}));
	}

	public function handleItemHeld(PlayerItemHeldEvent $event) : void {
		ClassFactory::callEvent(__FUNCTION__, $event);
	}

	public function handleItemUse(PlayerItemUseEvent $event) : void {
		ClassFactory::callEvent(__FUNCTION__, $event);
	}

	public function handleJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		$this->checkClass($session);
		$player->getArmorInventory()->getListeners()->add(CallbackInventoryListener::onAnyChange(fn(ArmorInventory $inventory) => $this->checkClass($session)));
	}

	private function checkClass(Session $session) : void {
		$player = $session->getPlayer();
		$kitClass = $session->getKitClass();
        
        if ($player === null) {
            return;
        }

		if ($kitClass !== null) {
			if (!$kitClass->isEnabled($player)) {
				$kitClass->handleRemove($player);
				$session->setKitClass(null);
				$player->sendMessage(TextFormat::colorize('&gClass: &l' . $kitClass->getName() . ' &r&7---> &cDisable!'));
			} else {
				$kitClass->handleAdd($player);
			}
		} else {
			foreach (ClassFactory::getAll() as $kitClass) {
				if ($kitClass->isEnabled($player)) {
					$kitClass->handleAdd($player);
					$session->setKitClass($kitClass);
					$player->sendMessage(TextFormat::colorize('&gClass: &l' . $kitClass->getName() . ' &r&7---> &aEnabled!'));
					break;
				}
			}
		}
	}
}
