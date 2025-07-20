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

use cosmicpe\form\entries\simple\Button;
use cosmicpe\form\SimpleForm;
use hcf\util\Utils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class KitManagerForm extends SimpleForm {

	public function __construct() {
		parent::__construct(TextFormat::colorize('&dKit Manager'));
		$createButton = new Button('Create Kit');
		$deleteButton = new Button('Delete Kit');
		$editButton = new Button('Edit Kit');
		$editOrganizationButton = new Button('Edit Organization');

		$this->addButton($createButton, function (Player $player, int $button_index) : void {
			$form = new KitCreateForm();
			$player->sendForm($form);
		});
		$this->addButton($deleteButton, function (Player $player, int $button_index) : void {
			$form = new KitDeleteForm();
			$player->sendForm($form);
		});
		$this->addButton($editButton, function (Player $player, int $button_index) : void {
			$form = new KitEditForm();
			$player->sendForm($form);
		});
		$this->addButton($editOrganizationButton, function (Player $player, int $button_index) : void {
			Utils::createKitOrganizationEditor($player);
		});
	}
}
