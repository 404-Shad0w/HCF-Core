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

namespace hcf\form\kit\manager;

use cosmicpe\form\CustomForm;
use cosmicpe\form\entries\custom\InputEntry;
use cosmicpe\form\entries\custom\ToggleEntry;
use hcf\kit\Kit;
use hcf\kit\KitFactory;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function explode;
use function is_numeric;

final class KitEditForm extends CustomForm {

	public function __construct() {
		parent::__construct(TextFormat::colorize('&dEdit Kit'));
		$nameInput = new InputEntry('Kit Name');

		$this->addEntry($nameInput, function (Player $player, InputEntry $entry, string $value) : void {
			if (KitFactory::get($value) === null) {
				return;
			}
			$this->createKitInformation($player, KitFactory::get($value));
		});
	}

	private function createKitInformation(Player $player, Kit $kit) : void {
		$form = new class($kit) extends CustomForm {

			private ?string $permission = null;

			public function __construct(Kit $kit) {
				parent::__construct(TextFormat::colorize('&dEdit ' . $kit->getName()));
				$permissionInput = new InputEntry('Permission', null, $kit->getPermission());
				$decorativeItemInput = new InputEntry('Decorative Item', null, $kit->getItemDecorative()->getId() . ':' . $kit->getItemDecorative()->getMeta());

				$this->addEntry($permissionInput, fn(Player $player, InputEntry $entry, string $value) => $this->permission = $value);
				$this->addEntry($decorativeItemInput, function (Player $player, InputEntry $entry, string $value) use ($kit) : void {
					$itemDecorative = $kit->getItemDecorative();

					if ($value !== '') {
						$data = explode(':', $value);
						$meta = 0;

						if (!is_numeric($data[0])) {
							$player->sendMessage(TextFormat::colorize('&cInvalid item id.'));
							return;
						}

						if (isset($data[1]) && is_numeric($data[1])) {
							$meta = (int) $data[1];
						}
						$itemDecorative = ItemFactory::getInstance()->get((int) $data[0], $meta);
					}
                    $kit->setInventory($player->getInventory()->getContents());
                    $kit->setArmorInventory($player->getArmorInventory()->getContents());
					$kit->setPermission($this->permission);
					$kit->setItemDecorative($itemDecorative);

					$player->sendMessage(TextFormat::colorize('&aYou have been edited kit.'));
                    $player->sendMessage(TextFormat::colorize('&aType &esave &ato update the Kit inventory.'));
				});
			}
		};
		$player->sendForm($form);
	}
}
