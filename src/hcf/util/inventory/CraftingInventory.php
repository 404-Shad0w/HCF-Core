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

namespace hcf\util\inventory;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\graphic\InvMenuGraphic;
use muqsit\invmenu\type\InvMenuType;
use muqsit\invmenu\type\util\InvMenuTypeBuilders;
use pocketmine\block\inventory\CraftingTableInventory;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\player\Player;
use pocketmine\world\Position;

final class CraftingInventory implements InvMenuType {

	private InvMenuType $menuType;

	public function __construct() {
		$this->menuType = InvMenuTypeBuilders::BLOCK_FIXED()
			->setBlock(VanillaBlocks::CRAFTING_TABLE())
			->setSize(9)
			->setNetworkWindowType(WindowTypes::WORKBENCH)
			->build();
	}

	public function createGraphic(InvMenu $menu, Player $player) : ?InvMenuGraphic {
		return $this->menuType->createGraphic($menu, $player);
	}

	public function createInventory() : Inventory {
		return new CraftingTableInventory(Position::fromObject(Vector3::zero(), null));
	}
}
