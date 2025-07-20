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

use hcf\HCF;
use hcf\kit\class\default\_trait\CooldownTrait;
use hcf\kit\class\KitClass;
use hcf\session\SessionFactory;
use hcf\util\Utils;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\utils\Limits;
use pocketmine\utils\TextFormat;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use function intval;
use function time;

final class ArcherClass extends KitClass {
	use CooldownTrait;

	/**
	 * @param TaskHandler[] $tasks
	 */
	public function __construct(
		private array $archerMark = [],
		private array $sugar = [],
		private array $feather = [],
		private array $tasks = []
	) {
		parent::__construct('Archer', true, [VanillaItems::LEATHER_CAP(), VanillaItems::LEATHER_TUNIC(), VanillaItems::LEATHER_PANTS(), VanillaItems::LEATHER_BOOTS()], [new EffectInstance(VanillaEffects::SPEED(), Limits::INT32_MAX, 2), new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), Limits::INT32_MAX), new EffectInstance(VanillaEffects::RESISTANCE(), Limits::INT32_MAX, 1)]);
	}
    
    public function handleAdd(Player $player) : void {
		parent::handleAdd($player);
		$session = SessionFactory::get($player);

		if ($session !== null && $session->getEnergy(strtolower($this->getName()) . '_energy') === null) {
			$session->addEnergy(strtolower($this->getName()) . '_energy', '&eArcher Energy&r&7:', 120);
		}
    }
    
    public function handleRemove(Player $player) : void {
		parent::handleRemove($player);
		$session = SessionFactory::get($player);

		if ($session !== null && $session->getEnergy(strtolower($this->getName()) . '_energy') !== null) {
			$session->removeEnergy(strtolower($this->getName()) . '_energy');
		}
    }

	public function handleDamage(EntityDamageEvent $event) : void {
		$player = $event->getEntity();

		if (!$player instanceof Player) {
			return;
		}
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
        
        if ($event instanceof EntityDamageByEntityEvent && !$event->isCancelled()) {
            $damager = $event->getDamager();
            if ($player instanceof Player && $damager instanceof Player) {
                if (isset($this->tasks[$player->getXuid()])) {
                    $baseDamage = $event->getBaseDamage();
                    $event->setBaseDamage($baseDamage + 1.5);
                }
            }
        }

		if ($event instanceof EntityDamageByChildEntityEvent) {
			$child = $event->getChild();
			$killer = $event->getDamager();

			if (!$child instanceof Arrow || !$killer instanceof Player) {
				return;
			}
			$target = SessionFactory::get($killer);

			if ($target === null) {
				return;
			}

			if (!$target->getKitClass() instanceof ArcherClass) {
				return;
			}

			if ($session->getKitClass() instanceof ArcherClass) {
				$killer->sendMessage(TextFormat::colorize('&cYou can\'t archer tag someone who has the same as you.'));
				return;
			}
			$killer->sendMessage(TextFormat::colorize('&e[&9Archer Range&e(&c' . intval($player->getPosition()->distance($killer->getPosition())) . '&e)] &6Marked player for 10 seconds.'));
			$player->sendMessage(TextFormat::colorize('&c&lMarked! &r&eAn archer has shot you and marked you (+15% damage) for 10 seconds.'));
            
			$player->setNameTag(TextFormat::colorize('&6' . $player->getName()));
			$session->addTimer('archer_mark', '&6Archer Mark&r&7:', 10);

			if (isset($this->tasks[$player->getXuid()])) {
				foreach ($player->getViewers() as $p) {
					$data = clone $player->getNetworkProperties();
					if($player->getEffects()->has(VanillaEffects::INVISIBILITY())){
						$data->setGenericFlag(EntityMetadataFlags::INVISIBLE, false, true);
					}
					$p->getNetworkSession()->sendDataPacket(SetActorDataPacket::create($player->getId(), $data->getAll(), new PropertySyncData([], []), 0));
				}
				$this->tasks[$player->getXuid()]?->cancel();
			}
			$this->tasks[$player->getXuid()] = HCF::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player) : void {
				if ($player->isOnline()) {
                    foreach ($player->getViewers() as $p) {
                        $data = clone $player->getNetworkProperties();
                        $p->getNetworkSession()->sendDataPacket(SetActorDataPacket::create($player->getId(), $data->getAll(), new PropertySyncData([], []), 0));
                    }
					$player->setNameTag(TextFormat::colorize('&c' . $player->getName()));
				}
			}), 20 * 10);
		}
	}

	public function handleItemUse(PlayerItemUseEvent $event) : void {
		$player = $event->getPlayer();
		$item = $event->getItem();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if (!$session->getKitClass() instanceof ArcherClass) {
			return;
		}
        
        if ($session->getTimer('archer_timer') !== null) return;
        
        $energy = $session->getEnergy(strtolower($this->getName()) . '_energy'); 
        
        if ($energy === null) return;

		if ($item->getTypeId() === ItemTypeIds::SUGAR) {
            if ($energy->getValue() < 20) {
                $player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
                return;
            }
            $energy->decreaseValue(20);

			$session->addTimer('archer_timer', '&eArcher Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 20 * 5, 3, false));
			$player->sendMessage(TextFormat::colorize('&aYou have Speed IV for 5 seconds'));
		} elseif ($item->getTypeId() === ItemTypeIds::FEATHER) {
            if ($energy->getValue() < 25) {
                $player->sendMessage(TextFormat::colorize('&cInsufficient energy.'));
                return;
            }
            $energy->decreaseValue(25);

			$session->addTimer('archer_timer', '&eArcher Effect&r&7:', 10);

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$player->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 20 * 5, 3, false));
			$player->sendMessage(TextFormat::colorize('&aYou have Jump IV for 5 seconds'));
		}
	}
}
