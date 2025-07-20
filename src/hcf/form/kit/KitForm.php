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

namespace hcf\form\kit;

use cosmicpe\form\entries\simple\Button;
use cosmicpe\form\SimpleForm;
use hcf\kit\KitFactory;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use const PHP_EOL;

final class KitForm extends SimpleForm {

	public function __construct(Player $player) {
		parent::__construct(TextFormat::colorize('&eKits'));

		foreach (KitFactory::getAll() as $name => $kit) {
			if ($kit->getPermission() !== null && $player->hasPermission($kit->getPermission())) {
				$button = new Button(TextFormat::colorize('&7' . $name . '&r' . PHP_EOL . '&aKit Unlocked'));
			} else {
				$button = new Button(TextFormat::colorize('&7' . $name . '&r' . PHP_EOL . '&cKit Locked'));
			}

			$this->addButton($button, function (Player $player, int $button_index) use ($name, $kit) : void {
				$kit->giveTo($player);
			});
		}
	}
}
