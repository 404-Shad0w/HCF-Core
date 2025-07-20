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

namespace hcf\command;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\inventory\Inventory;
use pocketmine\world\sound\EnderChestOpenSound;
use pocketmine\world\sound\EnderChestCloseSound;

final class EnderChestCommand extends Command {

	public function __construct() {
		parent::__construct('enderchest', 'Use command to open ender chest', null, ['ec']);
		$this->setPermission('ender_chest.command');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}

		if (!$this->testPermission($sender)) {
            $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
            $menu->getInventory()->setContents($sender->getEnderInventory()->getContents());
            $sender->getWorld()->addSound($sender->getPosition(), new EnderChestOpenSound());
            //$sender->getWorld()->addSound($sender->getPosition()->asVector3(), new EnderChestOpenSound(), [$sender]);
            $menu->setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
                $action = $transaction->getAction();
			$item = $transaction->getItemClickedWith();
			$player = $transaction->getPlayer();
            //$player->getWorld()->addSound($player->getPosition()->asVector3(), new EnderChestCloseSound(), [$player]);
			$player->getEnderInventory()->setItem($action->getSlot(), $item);
			return $transaction->continue();
            });
            $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) use ($sender): void {
                $sender->getWorld()->addSound($sender->getPosition(), new EnderChestCloseSound());
            });
            $menu->send($sender, TextFormat::colorize('&5' . $sender->getName() . '\'s free ender chest'));
			return;
		}
		$menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
		$menu->getInventory()->setContents($sender->getEnderInventory()->getContents());
        $sender->getWorld()->addSound($sender->getPosition(), new EnderChestOpenSound());
        //$sender->getWorld()->addSound($sender->getPosition()->asVector3(), new EnderChestOpenSound(), [$sender]);
		$menu->setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
			$action = $transaction->getAction();
			$item = $transaction->getItemClickedWith();
			$player = $transaction->getPlayer();
            //$player->getWorld()->addSound($player->getPosition()->asVector3(), new EnderChestCloseSound(), [$player]);
			$player->getEnderInventory()->setItem($action->getSlot(), $item);
			return $transaction->continue();
		});
        $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) use ($sender): void {
                $sender->getWorld()->addSound($sender->getPosition(), new EnderChestCloseSound());
            });
		$menu->send($sender, TextFormat::colorize('&5' . $sender->getName() . '\'s ender chest'));
	}
}
