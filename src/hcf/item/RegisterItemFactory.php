<?php

/*
 * A PocketMine-MP plugin that implements UHC Game.
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 *
 * @author JkqzDev
 */

declare(strict_types=1);

namespace hcf\item;

use hcf\HCF;
use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\StringToItemParser;
use pocketmine\scheduler\AsyncTask;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use function assert;

final class RegisterItemFactory {

	public static function registerOnAllThreads() : void {
		$pool = HCF::getInstance()->getServer()->getAsyncPool();

		self::registerOnCurrentThread();
		$pool->addWorkerStartHook(function (int $worker) use ($pool) : void {
			$pool->submitTaskToWorker(new class extends AsyncTask {
				public function onRun() : void {
					RegisterItemFactory::registerOnCurrentThread();
				}
			}, $worker);
		});
	}

	public static function registerOnCurrentThread() : void {
		self::registerItems();
	}

	private static function registerItems() : void {
        self::registerItem(ItemTypeNames::FISHING_ROD, new FishingRod(new ItemIdentifier(ItemTypeIds::FISHING_ROD), 'Fishing Rod'), ['fishing_rod']);
		self::registerItem(ItemTypeNames::ENDER_PEARL, new EnderPearl(new ItemIdentifier(ItemTypeIds::ENDER_PEARL), 'Ender Pearl'), ['ender_pearl']);
        self::registerItem(ItemTypeNames::SNOWBALL, new Snowball(new ItemIdentifier(ItemTypeIds::SNOWBALL), 'Snowball'), ['snowball']);
        /*self::registerItem('minecraft:golden_head', new GoldenHead(new ItemIdentifier(ItemTypeIds::newId()), 'Golden Head'), ['golden_head', '322:10'],
			static function (Item $item) : SavedItemData {
				assert($item instanceof GoldenHead);
				return new SavedItemData(ItemTypeNames::GOLDEN_APPLE, 10);
			});*/
	}

	private static function registerItem(string $id, Item $item, array $stringToItemParserNames, ?\Closure $serializerCallback = null, ?\Closure $deserializerCallback = null) : void {
		$serializer = GlobalItemDataHandlers::getSerializer();
		$deserializer = GlobalItemDataHandlers::getDeserializer();

		(function () use ($id, $item, $serializerCallback) : void {
			$this->itemSerializers[$item->getTypeId()] = $serializerCallback !== null ? $serializerCallback : static fn() => new SavedItemData($id);
		})->call($serializer);

		(function () use ($id, $item, $deserializerCallback) : void {
			if (isset($this->deserializers[$id])) {
				unset($this->deserializers[$id]);
			}
			$this->map($id, $deserializerCallback !== null ? $deserializerCallback : static fn(SavedItemData $_) => clone $item);
		})->call($deserializer);

		foreach ($stringToItemParserNames as $name) {
			StringToItemParser::getInstance()->override($name, fn() => clone $item);
		}
	}
}
