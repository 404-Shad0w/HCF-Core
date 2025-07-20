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
use hcf\kit\KitFactory;
use hcf\session\SessionFactory;
use hcf\util\Utils;
use InvalidArgumentException;
use pocketmine\item\ItemFactory;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function explode;
use function is_numeric;
use function time;

final class KitCreateForm extends CustomForm {

	public function __construct(
		private ?string $name = null,
        private ?string $colorize = null,
		private ?string $permission = null,
		private ?int $countdown = null
	) {
		parent::__construct(TextFormat::colorize('&dKit Create'));
		$nameInput = new InputEntry('Kit Name');
        $colorizeInput = new InputEntry('Kit Colorize');
		$permissionInput = new InputEntry('Kit Permission');
		$countdownInput = new InputEntry('Kit Countdown');
        //$itemDecorativeInput = new InputEntry('Item Decorative');

		$this->addEntry($nameInput, function (Player $player, InputEntry $entry, string $value) : void {
			if (KitFactory::get($value) !== null) {
				$player->sendMessage(TextFormat::colorize('&cThe kit has already created'));
				return;
			}
			$this->name = $value;
		});
        $this->addEntry($colorizeInput, function (Player $player, InputEntry $entry, string $value) : void {
            if ($value !== '') {
                $this->colorize = $value;
            }
        });
		$this->addEntry($permissionInput, function (Player $player, InputEntry $entry, string $value) : void {
			if ($value !== '') {
				$this->permission = $value;
			}
		});
		$this->addEntry($countdownInput, function (Player $player, InputEntry $entry, string $value) : void {
			$countdown = null;
            
            $session = SessionFactory::get($player);
            
            if ($session === null) return;
            
            if ($this->name === null) return;

			if ($value !== '') {
				try {
					$countdown = Utils::stringToTime($value) - time();
				} catch (InvalidArgumentException $exception) {
					$player->sendMessage(TextFormat::colorize('&c' . $exception->getMessage()));
					return;
				}
			}
			$this->countdown = $countdown;
			$session->startKitHandler($session, $this->name);

			$kitHandler = $session->getKitHandler();
            $kitHandler->setColorize($this->colorize);
			$kitHandler->setPermission($this->permission);
			$kitHandler->setCountdown($countdown);

			$player->sendMessage(TextFormat::colorize('&aType in the chat &esave &ato save kit or &ecancel &ato cancel kit.'));
            $player->sendMessage(TextFormat::colorize('&aUse the &c/kit setdecorativeitem &acommand with the item in hand to place the decoration item.'));
		});
        /*
		$this->addEntry($itemDecorativeInput, function (Player $player, InputEntry $entry, string $value) : void {
			$session = SessionFactory::get($player);

			if ($session === null) {
				return;
			}

			if ($this->name === null) {
				return;
			}
			$itemDecorative = VanillaItems::BOOK();

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
			$session->startKitHandler($session, $this->name);

			$kitHandler = $session->getKitHandler();
            $kitHandler->setColorize($this->colorize);
			$kitHandler->setPermission($this->permission);
			$kitHandler->setCountdown($this->countdown);
			$kitHandler->setItemDecorative($itemDecorative);

			$player->sendMessage(TextFormat::colorize('&aType in the chat &esave &ato save kit or &ecancel &ato cancel kit.'));
		});*/
	}
}
