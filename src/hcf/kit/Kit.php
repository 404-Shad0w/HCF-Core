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

use hcf\util\Serialize;
use hcf\timer\TimerFactory;
use hcf\session\SessionFactory;
use hcf\util\Utils;
use JetBrains\PhpStorm\ArrayShape;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function explode;

final class Kit {

    public function __construct(
        private string  $name,
        private ?string $colorize,
        private ?string $permission,
        private ?int    $countdown,
        private ?int    $inventorySlot,
        private Item    $itemDecorative,
        private array   $inventory = [],
        private array   $armorInventory = []
    ) {
        if ($this->permission !== null) {
            PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR)->addChild($this->permission, true);
            PermissionManager::getInstance()->addPermission(new Permission($this->permission));
        }
    }

    public function getName(): string {
        return $this->name;
    }
    
    public function getColorize(): string {
        return $this->colorize;
    }

    public function getItemDecorative(): ?Item {
        return $this->itemDecorative;
    }

    public function getPermission(): ?string {
        return $this->permission;
    }

    public function getInventorySlot(): ?int {
        return $this->inventorySlot;
    }
    
    public function getInventory(): array {
        return $this->inventory;
    }
    
    public function getArmorInventory(): array {
        return $this->armorInventory;
    }

    public function setItemDecorative(?Item $itemDecorative): void {
        $this->itemDecorative = $itemDecorative;
    }

    public function setInventorySlot(?int $inventorySlot): void {
        $this->inventorySlot = $inventorySlot;
    }

    public static function jsonDeserialize(string $name, array $data): self {
        $armorInventory = $data['armorInventory'];
        $inventory = $data['inventory'];

        if (isset($data['inventorySlot'])) {
            $inventorySlot = $data['inventorySlot'] !== null ? (int) $data['inventorySlot'] : null;
        } else {
            $inventorySlot = null;
        }

        if (isset($data['itemDecorative'])) {
            $itemData = explode(':', $data['itemDecorative']);
            $itemDecorative = Serialize::deserialize($data['itemDecorative']);
            //$itemDecorative = ItemFactory::getInstance()->get((int) $itemData[0], (int) $itemData[1] ?? 0);
        } else {
            $itemDecorative = VanillaItems::BOOK();
        }

        foreach ($armorInventory as $slot => $item) {
            $armorInventory[$slot] = Serialize::deserialize($item);
        }

        foreach ($inventory as $slot => $item) {
            $inventory[$slot] = Serialize::deserialize($item);
        }
        return new Kit($name, $data['colorize'], $data['permission'], $data['countdown'], $inventorySlot, $itemDecorative, $inventory, $armorInventory);
    }

    public function getCountdown(): ?int {
        return $this->countdown;
    }

    #[ArrayShape(['colorize' => "null|string", 'permission' => "null|string", 'countdown' => "int|null", 'inventory' => "array", 'armorInventory' => "array"])] public function jsonSerialize(): array {
        $data = [
            'colorize' => $this->colorize,
            'permission' => $this->permission,
            'countdown' => $this->countdown,
            'inventorySlot' => $this->inventorySlot,
            'itemDecorative' => Serialize::serialize($this->itemDecorative),
            'inventory' => [],
            'armorInventory' => []
        ];

        foreach ($this->armorInventory as $slot => $item) {
            $data['armorInventory'][$slot] = Serialize::serialize($item);
        }

        foreach ($this->inventory as $slot => $item) {
            $data['inventory'][$slot] = Serialize::serialize($item);
        }
        return $data;
    }

    public function setColorize(?string $colorize): void {
        $this->colorize = $colorize;
    }

    public function setPermission(?string $permission): void {
        $this->permission = $permission;
    }

    public function setInventory(array $contents): void {
        $this->inventory = $contents;
    }

    public function setArmorInventory(array $contents): void {
        $this->armorInventory = $contents;
    }

    public function giveTo(Player $player, bool $force = false): void {
        $armorItems = $this->armorInventory;
        $items = $this->inventory;
        $session = SessionFactory::get($player);

        if ($session === null) {
            return;
        }

        if (!$force) {
            if (TimerFactory::get('FreeKits') !== null && !TimerFactory::get('FreeKits')->isEnabled()) {
                if ($this->permission !== null && !$player->hasPermission($this->permission)) {
                    $player->sendMessage(TextFormat::colorize('&cYou can\'t use this kit.'));
                    return;
                }
            }


            if (!$player->getGamemode()->equals(GameMode::CREATIVE()) && $session->getTimer($this->name . '_kit') !== null) {
                if ($this->countdown === null) {
                    $session->removeTimer($this->name . '_kit');
                } else {
                    $timer = $session->getTimer($this->name . '_kit');
                    $player->sendMessage(TextFormat::colorize('&cYou have kit cooldown ' . Utils::date($timer->getTime())));
                    return;
                }
            }

            if ($this->countdown !== null) {
                $session->addTimer(name: $this->name . '_kit', format: '', time: $this->countdown, visible: false);
            }
        }

        if (count($armorItems) !== 0) {
            for ($i = 0; $i < 4; $i++) {
                if (isset($armorItems[$i])) {
                    if ($player->getArmorInventory()->getItem($i)->isNull()) {
                        $player->getArmorInventory()->setItem($i, $armorItems[$i]);
                    } else {
                        if ($player->getInventory()->canAddItem($armorItems[$i])) {
                            $player->getInventory()->addItem($armorItems[$i]);
                        } else {
                            $player->dropItem($armorItems[$i]);
                        }
                    }
                }
            }
        }

        if (count($items) !== 0) {
            foreach ($items as $item) {
                if ($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                } else {
                    $player->dropItem($item);
                }
            }
        }
        $player->sendMessage(TextFormat::colorize('&aYou have give the kit successfully.'));
    }
}
