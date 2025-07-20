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
use pocketmine\math\Vector3;
use function intval;
use function time;

final class PythonClass extends KitClass {
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
		parent::__construct('Python', false, [VanillaItems::IRON_HELMET(), VanillaItems::DIAMOND_CHESTPLATE(), VanillaItems::IRON_LEGGINGS(), VanillaItems::DIAMOND_BOOTS()], [new EffectInstance(VanillaEffects::SPEED(), Limits::INT32_MAX, 1), new EffectInstance(VanillaEffects::JUMP_BOOST(), Limits::INT32_MAX, 1), new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), Limits::INT32_MAX, 1), new EffectInstance(VanillaEffects::INVISIBILITY(), Limits::INT32_MAX, 1)]);
	}
        
	public function handleItemUse(PlayerItemUseEvent $event) : void {
		$player = $event->getPlayer();
		$item = $event->getItem();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if (!$session->getKitClass() instanceof PythonClass) {
			return;
		}

		if ($this->getCooldown($player) !== 0) {
			$player->sendMessage(TextFormat::colorize('&cYou have global cooldown. &7(' . Utils::timeFormat($this->getCooldown($player)) . ')'));
			return;
		}

		if ($item->getTypeId() === ItemTypeIds::SUGAR) {
			if (isset($this->sugar[$player->getXuid()]) && $this->sugar[$player->getXuid()] > time()) {
				$player->sendMessage(TextFormat::colorize('&cYou have Speed cooldown, &7' . Utils::timeFormat($this->sugar[$player->getXuid()] - time())));
				return;
			}
			$this->addCooldown($player, 5);
			$this->sugar[$player->getXuid()] = time() + 20;

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 20 * 5, 3, false));
			$player->sendMessage(TextFormat::colorize('&aYou have Speed III for 5 seconds'));
		} elseif ($item->getTypeId() === ItemTypeIds::FEATHER) {
			if (isset($this->feather[$player->getXuid()]) && $this->feather[$player->getXuid()] > time()) {
				$player->sendMessage(TextFormat::colorize('&cYou have Fly cooldown, &7' . Utils::timeFormat($this->feather[$player->getXuid()] - time())));
				return;
			}
			$this->addCooldown($player, 5);
			$this->feather[$player->getXuid()] = time() + 20;

			$item->pop();
			$player->getInventory()->setItemInHand($item);

            $player->setMotion($player->getDirectionVector()->multiply(1.2)->add(0, 1.2, 0));
			$player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), 20 * 5, 3, false));
            $player->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), 20 * 5, 3, false));
			$player->sendMessage(TextFormat::colorize('&aYou have strength II and resistance II for 5 seconds'));
		}
	}
}
