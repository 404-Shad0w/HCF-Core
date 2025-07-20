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

namespace hcf\session\handler;

use hcf\kit\KitFactory;
use hcf\session\Session;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use function explode;
use function strtolower;

final class KitHandler {

	public const CREATE = 0;
	public const EDIT = 1;

	private Item $itemDecorative;

	public function __construct(
		private Session $session,
		private string  $name,
        private ?string $colorize = null,
		private ?string $permission = null,
		private ?int    $countdown = null,
		private int     $mode = self::CREATE
	) {}
    
    public function setColorize(?string $colorize): void {
        $this->colorize = $colorize;
    }

	public function setPermission(?string $permission) : void {
		$this->permission = $permission;
	}

	public function setCountdown(?int $countdown) : void {
		$this->countdown = $countdown;
	}

	public function setItemDecorative(Item $itemDecorative) : void {
		$this->itemDecorative = $itemDecorative;
	}

	public function handleChat(PlayerChatEvent $event) : void {
		$message = $event->getMessage();
		$player = $event->getPlayer();
		$args = explode(' ', $message);

		if (strtolower($args[0]) === 'save') {
			$event->cancel();

			if ($this->mode === self::CREATE) {
				KitFactory::create($this->name, $this->itemDecorative, $this->colorize, $this->permission, $this->countdown, $player->getInventory()->getContents(), $player->getArmorInventory()->getContents());
				$player->sendMessage(TextFormat::colorize('&aKit ' . $this->name . ' has been created successfully'));

				$this->session->stopKitHandler();
			} elseif ($this->mode === self::EDIT) {
				$kit = KitFactory::get($this->name);

				if ($kit === null) {
					$this->session->stopKitHandler();
					$player->sendMessage(TextFormat::colorize('&cKit not exists.'));
					return;
				}
				$kit->setInventory($player->getInventory()->getContents());
				$kit->setArmorInventory($player->getArmorInventory()->getContents());

				$player->sendMessage(TextFormat::colorize('&aYou have edit contents of the kit successfully'));

				$this->session->stopKitHandler();
			}
		} elseif (strtolower($args[0]) === 'cancel') {
			$event->cancel();
			$this->session->stopKitHandler();
		}
	}
}
