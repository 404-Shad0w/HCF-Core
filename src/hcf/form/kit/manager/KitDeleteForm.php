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
use cosmicpe\form\entries\custom\DropdownEntry;
use hcf\kit\KitFactory;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_keys;

final class KitDeleteForm extends CustomForm {

	public function __construct() {
		parent::__construct(TextFormat::colorize('&dDelete Kit'));
		$kits = array_keys(KitFactory::getAll());
		$kitDropdown = new DropdownEntry('Kits', $kits);

		$this->addEntry($kitDropdown, function (Player $player, DropdownEntry $entry, int $value) use ($kits) : void {
			$kitName = $kits[$value];

			if (KitFactory::get($kitName) === null) {
				$player->sendMessage(TextFormat::colorize('&cKit not exists.'));
				return;
			}
			KitFactory::remove($kitName);
			$player->sendMessage(TextFormat::colorize('&aYou have deleted the kit successfully'));
		});
	}
}
