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

namespace hcf\entity;

use hcf\claim\Claim;
use hcf\disconnect\Disconnect;
use hcf\faction\event\FactionAddPointEvent;
use hcf\faction\event\FactionRemovePointEvent;
use hcf\HCF;
use hcf\session\Session;
use hcf\session\SessionFactory;
use hcf\timer\TimerFactory;
use hcf\util\Utils;
use pocketmine\entity\Location;
use pocketmine\entity\Villager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_merge;
use function min;

final class DisconnectEntity extends Villager {

	private ?Session $lastHit = null;

	public function __construct(
		Location $location,
		?CompoundTag $nbt = null,
		private ?Disconnect $disconnect = null
	) {
		parent::__construct($location, $nbt);
	}

	public function getDrops() : array {
		return [];
	}

	public function getXpDropAmount() : int {
		return 0;
	}

	public function attack(EntityDamageEvent $source) : void {
		parent::attack($source);
		$cause = $source->getCause();

		if ($this->disconnect === null) {
			return;
		}
		$session = $this->disconnect->getSession();

		if (TimerFactory::get('SOTW') !== null && TimerFactory::get('SOTW')->isEnabled()) {
			$source->cancel();
			return;
		}

		if ($session->getTimer('starting_timer') !== null || $cause === EntityDamageEvent::CAUSE_ENTITY_ATTACK && $session->getTimer('pvp_timer') !== null) {
			$source->cancel();
			return;
		}
		$currentClaim = $session->getCurrentClaim();

		if ($currentClaim !== null && $currentClaim->getType() === Claim::SPAWN) {
			$source->cancel();
			return;
		}

		if ($source instanceof EntityDamageByEntityEvent) {
			$damager = $source->getDamager();

			if (!$damager instanceof Player) {
				return;
			}
			$target = SessionFactory::get($damager);

			if ($target === null) {
				return;
			}

			if ($target->getTimer('starting_timer') !== null || $target->getTimer('pvp_timer') !== null) {
				$source->cancel();
				return;
			}
			$currentClaim = $target->getCurrentClaim();

			if ($currentClaim !== null && $currentClaim->getType() === Claim::SPAWN) {
				$source->cancel();
				return;
			}

			if ($target->getFaction() !== null && $target->getFaction()->equals($session->getFaction())) {
				$source->cancel();
				return;
			}
			$session->addTimer('spawn_tag', '&l&cSpawn Tag&r&7:', 30);
			$target->addTimer('spawn_tag', '&l&cSpawn Tag&r&7:', 30);

			$this->lastHit = $target;
		}
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool {
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if (!$this->isFlaggedForDespawn() || !$this->isClosed()) {
			if ($this->ticksLived >= 90 * 20 || $this->disconnect === null) {
				$this->flagForDespawn();
                return true;
			}
            $time = ((90 * 20) - $this->ticksLived) / 20;
            $this->setNameTag(TextFormat::colorize('&c' . $this->disconnect->getSession()->getName() . ' &7(Combat Logger)'.TextFormat::EOL.'&cDespawn in &7' . Utils::timeFormat(intval($time)) . TextFormat::EOL. '&c' . round($this->getHealth() + $this->getAbsorption(), 1). ' '));
            //$this->setScoreTag(TextFormat::colorize('&cDespawn in &7' . Utils::timeFormat(intval($time))));
		}
		return $hasUpdate;
	}

	protected function onDeath() : void {
		if ($this->disconnect !== null) {
			$session = $this->disconnect->getSession();
			$killer = $this->lastHit;

			if ($killer !== null) {
				$killer->addKill();
				$killer->addKillstreak();

				if ($killer->getKillStreak() > $killer->getBestKillStreak()) {
					$killer->addBestKillstreak();
				}
				$faction = $killer->getFaction();

				if ($faction !== null) {
					$ev = new FactionAddPointEvent($faction, 1);
					$ev->call();
					$faction->setPoints($faction->getPoints() + $ev->getPoints());
				}
			}       
            
			$session->removeTimer('spawn_tag');
			$session->addDeath();
			$session->removeKillstreak();
            $config = HCF::getInstance()->getConfig();
            if ($config->get("kitmap-mode") === false) {
                $session->addTimer('pvp_timer', '&aPvP Timer&r&7:', 60 * 60);
            }
			$faction = $session->getFaction();

			if ($faction !== null) {
				$points = $faction->getPoints();
				$deathsUntilRaidable = $faction->getDeathsUntilRaidable() - 1.0;
				$points--;

				if ($deathsUntilRaidable <= 0.00 && !$faction->isRaidable()) {
					$points -= 10;

					if ($killer !== null && $killer->getFaction() !== null) {
						$ev = new FactionAddPointEvent($killer->getFaction(), 3);
						$ev->call();

						$killer->getFaction()->setPoints($killer->getFaction()->getPoints() + $ev->getPoints());
						$killer->getFaction()->announce('&cThe faction &l' . $faction->getName() . '&r&c is now RAIDABLE!');
					}
				}
				$ev = new FactionRemovePointEvent($faction, $points);
				$ev->call();

				$faction->setDeathsUntilRaidable($deathsUntilRaidable);
				$faction->setPoints($ev->getPoints());

				if ($faction->isRaidable()) {
					$regenCooldown = $faction->getRegenCooldown() + 5 * 60;
					$faction->setRegenCooldown(min($regenCooldown, (int) HCF::getInstance()->getConfig()->get('faction.regen-cooldown', 1800)));
				} else {
					$faction->setRegenCooldown((int) HCF::getInstance()->getConfig()->get('faction.regen-cooldown', 1800));
				}

				foreach ($faction->getOnlineMembers() as $member) {
					$member->getSession()->getPlayer()?->setScoreTag(TextFormat::colorize('&6[&c' . $faction->getName() . ' &c' . $faction->getDeathsUntilRaidable() . '&6]'));
				}
			}
			$this->disconnect->setDeath(true);
            $drops = array_merge($this->disconnect->getInventory(), $this->disconnect->getArmorInventory());

            foreach ($drops as $drop) {
                $this->getWorld()->dropItem($this->getPosition(), $drop);
            }

			if ($killer === null) {
				HCF::getInstance()->getServer()->broadcastMessage(TextFormat::colorize('&c' . $session->getName() . '&4[' . $session->getKills() . '] &edied'));
			} else {
				$item = null;

				if ($killer->isOnline()) {
					$item = $killer->getPlayer()->getInventory()->getItemInHand();
				}
				HCF::getInstance()->getServer()->broadcastMessage(TextFormat::colorize('&c' . $session->getName() . '&4[' . $session->getKills() . '] &ewas slain by &c' . $killer->getName() . '&4[' . $killer->getKills() . ']' . ($item !== null ? ' &cusing ' . $item->getName() : '')));
			}
		}
	}
}
