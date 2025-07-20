<?php

namespace hcf\module\lootbox;

use hcf\HCFLoader;
use hcf\module\lootbox\entity\FloatingEntity;
use hcf\module\lootbox\entity\TestEntity;
use hcf\module\lootbox\commands\LootBoxCommand;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class LootBoxManager {

    public array $items = [];

    public function __construct(){
        $this->items = HCFLoader::getInstance()->getProvider()->getLootBox();
        $entityFactory = EntityFactory::getInstance();
		$entityFactory->register(TestEntity::class, function (World $world, CompoundTag $nbt): TestEntity {
			return new TestEntity(EntityDataHelper::parseLocation($nbt, $world), new Location(1, 1, 1, HCFLoader::getInstance()->getServer()->getWorldManager()->getDefaultWorld(), 0, 0));
		}, ["LOL"]);
		$entityFactory->register(TestEntity::class, function (World $world, CompoundTag $nbt): FloatingEntity {
			return new FloatingEntity(new Location(0,0,0,$world,0,0),$nbt);
		}, ["Hm"]);
        HCFLoader::getInstance()->getServer()->getCommandMap()->register("/lootbox", new LootBoxCommand());
    }

    public function getItems(): array {
        return $this->items;
    }

    public function setItems(array $items): self {
        $this->items = $items;
        return $this;
    }

    public function getRandomItem(): Item {
        $item = $this->items[array_rand($this->items)];
        return $item instanceof Item ? $item : VanillaItems::STONE_SWORD();
    }

}