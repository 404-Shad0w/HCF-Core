<?php


namespace CortexPE\Linguist\chunkConverter;


use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeIds as BlockLegacyIds;
use pocketmine\world\generator\object\TreeType;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\World;

class MCJavaAnvilConverter extends ChunkConverter {
	public static $BLOCKS = null;

	private static function initBlockConversionTable(): void {
		if(self::$BLOCKS !== null) return;
		self::$BLOCKS = [
			// PC BLOCK ID => [PC META => [PE BLOCK ID, PE BLOCK META]]
			// null means it will preserve its meta value after replacement
			// -1 PC META means it will apply to all metas
			BlockTypeIds::INVISIBLE_BEDROCK => [-1 => [BlockTypeIds::STAINED_GLASS, null]],
            /*BlockTypeIds::DROPPER => [-1 => [BlockTypeIds::DOUBLE_WOODEN_SLAB, null]],
			BlockTypeIds::ACTIVATOR_RAIL => [-1 => [BlockTypeIds::WOODEN_SLAB, null]],
			BlockTypeIds::WOODEN_SLAB => [-1 => [BlockTypeIds::ACTIVATOR_RAIL, null]],
			BlockTypeIds::DOUBLE_WOODEN_SLAB => [-1 => [BlockTypeIds::DROPPER, null]],
			166 => [-1 => [*BlockLegacyIds::INVISIBLE_BEDROCK*
				BlockTypeIds::BARRIER, 0]], // (barrier) todo: actual MCPE barrier ID: 416 unsupported on 3.0.0
			BlockTypeIds::REPEATING_COMMAND_BLOCK => [-1 => [BlockTypeIds::FENCE, TreeType::OAK()->getMagicNumber()]],
			BlockTypeIds::CHAIN_COMMAND_BLOCK => [-1 => [BlockTypeIds::FENCE, TreeType::SPRUCE()->getMagicNumber()]],
			190 => [-1 => [BlockTypeIds::FENCE, TreeType::BIRCH()->getMagicNumber()]],*/ // ??
			191 => [-1 => [BlockTypeIds::SPRUCE_FENCE, TreeType::JUNGLE()->id()]], // ??
			192 => [-1 => [BlockTypeIds::SPRUCE_FENCE, TreeType::ACACIA()->id()]], // ??
            //BlockTypeIds::SPRUCE_DOOR_BLOCK => [-1 => [BlockTypeIds::SPRUCE_FENCE, TreeType::DARK_OAK()->getMagicNumber()]],
			BlockTypeIds::GRASS_PATH => [
				0 => [BlockTypeIds::END_ROD, 0],
				1 => [BlockTypeIds::END_ROD, 1],
				2 => [BlockTypeIds::END_ROD, 3],
				3 => [BlockTypeIds::END_ROD, 2],
				4 => [BlockTypeIds::END_ROD, 5],
				5 => [BlockTypeIds::END_ROD, 4]
			],
			BlockTypeIds::ITEM_FRAME => [-1 => [BlockTypeIds::CHORUS_PLANT, 0]],
			BlockTypeIds::PUMPKIN => [-1 => [BlockTypeIds::CARVED_PUMPKIN, NULL]],
			202 => [
				0 => [BlockTypeIds::PURPUR, 2],
				4 => [BlockTypeIds::PURPUR, 6],
				8 => [BlockTypeIds::PURPUR, 10]
			],
            //204 => [0 => [BlockTypeIds::DOUBLE_STONE_SLAB2, 1]],
			BlockTypeIds::END_ROD => [-1 => [BlockTypeIds::GRASS_PATH, 0]],
            //BlockTypeIds::OBSERVER => [-1 => [BlockTypeIds::CONCRETE, null]],
            //BlockTypeIds::STRUCTURE_BLOCK => [-1 => [BlockTypeIds::CONCRETE_POWDER, null]],

			BlockTypeIds::LEVER => [
				0 => [BlockTypeIds::LEVER, 7],
				5 => [BlockTypeIds::LEVER, 14],
				6 => [BlockTypeIds::LEVER, 5],
				7 => [BlockTypeIds::LEVER, 8],
				8 => [BlockTypeIds::LEVER, 15],
				13 => [BlockTypeIds::LEVER, 6],
				14 => [BlockTypeIds::LEVER, 13],
				15 => [BlockTypeIds::LEVER, 0]
			],

			BlockTypeIds::FROSTED_ICE => [-1 => [BlockTypeIds::BEETROOTS, NULL]],
            //BlockTypeIds::UNDYED_SHULKER_BOX => [0 => [BlockTypeIds::STONE_SLAB2, 1]],
            /*210 => [-1 => [BlockTypeIds::REPEATING_COMMAND_BLOCK, NULL]],
			211 => [-1 => [BlockTypeIds::CHAIN_COMMAND_BLOCK, NULL]],
			212 => [-1 => [BlockTypeIds::FROSTED_ICE, NULL]],
			BlockTypeIds::SHULKER_BOX => [-1 => [BlockTypeIds::OBSERVER, NULL]],
			BlockTypeIds::PURPLE_GLAZED_TERRACOTTA => [-1 => [BlockTypeIds::UNDYED_SHULKER_BOX, NULL]],
			BlockTypeIds::WHITE_GLAZED_TERRACOTTA => [-1 => [BlockLegacyIds::SHULKER_BOX, NULL]],
			BlockTypeIds::BLACK_GLAZED_TERRACOTTA => [
				0 => [BlockTypeIds::WHITE_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::WHITE_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::WHITE_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::WHITE_GLAZED_TERRACOTTA, 1]
			],
			BlockTypeIds::CONCRETE => [
				0 => [BlockTypeIds::ORANGE_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::ORANGE_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::ORANGE_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::ORANGE_GLAZED_TERRACOTTA, 1]
			],
			BlockTypeIds::CONCRETEPOWDER => [
				0 => [BlockTypeIds::MAGENTA_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::MAGENTA_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::MAGENTA_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::MAGENTA_GLAZED_TERRACOTTA, 1]
			],
			238 => [
				0 => [BlockTypeIds::LIGHT_BLUE_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::LIGHT_BLUE_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::LIGHT_BLUE_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::LIGHT_BLUE_GLAZED_TERRACOTTA, 1]
			],
			239 => [
				0 => [BlockTypeIds::YELLOW_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::YELLOW_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::YELLOW_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::YELLOW_GLAZED_TERRACOTTA, 1]
			],
			BlockTypeIds::CHORUS_PLANT => [
				0 => [BlockTypeIds::LIME_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::LIME_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::LIME_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::LIME_GLAZED_TERRACOTTA, 1]
			],
			BlockTypeIds::STAINED_GLASS => [
				0 => [BlockTypeIds::PINK_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::PINK_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::PINK_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::PINK_GLAZED_TERRACOTTA, 1]
			],
			242 => [
				0 => [BlockTypeIds::GRAY_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::GRAY_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::GRAY_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::GRAY_GLAZED_TERRACOTTA, 1]
			],
			BlockTypeIds::PODZOL => [
				0 => [BlockTypeIds::SILVER_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::SILVER_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::SILVER_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::SILVER_GLAZED_TERRACOTTA, 1]
			],
			BlockTypeIds::BEETROOT_BLOCK => [
				0 => [BlockTypeIds::CYAN_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::CYAN_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::CYAN_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::CYAN_GLAZED_TERRACOTTA, 1]
			],
			BlockTypeIds::STONECUTTER => [
				0 => [BlockTypeIds::PURPLE_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::PURPLE_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::PURPLE_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::PURPLE_GLAZED_TERRACOTTA, 1]
			],
			BlockTypeIds::GLOWINGOBSIDIAN => [
				0 => [BlockTypeIds::BLUE_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::BLUE_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::BLUE_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::BLUE_GLAZED_TERRACOTTA, 1]
			],
			BlockTypeIds::NETHERREACTOR => [
				0 => [BlockTypeIds::BROWN_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::BROWN_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::BROWN_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::BROWN_GLAZED_TERRACOTTA, 1]
			],
			BlockTypeIds::INFO_UPDATE => [
				0 => [BlockTypeIds::GREEN_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::GREEN_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::GREEN_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::GREEN_GLAZED_TERRACOTTA, 1]
			],
			BlockTypeIds::INFO_UPDATE2 => [
				0 => [BlockTypeIds::RED_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::RED_GLAZED_TERRACOTTA, 4],
				2 => [BlockTypeIds::RED_GLAZED_TERRACOTTA, 0],
				3 => [BlockTypeIds::RED_GLAZED_TERRACOTTA, 1]
			],
			BlockTypeIds::MOVINGBLOCK => [
				0 => [BlockTypeIds::BLACK_GLAZED_TERRACOTTA, 3],
				1 => [BlockTypeIds::BLACK_GLAZED_TERRACOTTA, 4],
				2 => [BlockLegacyIds::BLACK_GLAZED_TERRACOTTA, 0],
				3 => [BlockLegacyIds::BLACK_GLAZED_TERRACOTTA, 1]
			],
			BlockLegacyIds::RESERVED6 => [0 => [BlockLegacyIds::STRUCTURE_BLOCK, 1]],

			BlockLegacyIds::DOUBLE_STONE_SLAB => [
				6 => [BlockLegacyIds::DOUBLE_STONE_SLAB, 7],
				7 => [BlockLegacyIds::DOUBLE_STONE_SLAB, 6],
				8 => [BlockLegacyIds::SMOOTH_STONE, 0],
				9 => [BlockLegacyIds::SANDSTONE, 3],
				14 => [BlockLegacyIds::DOUBLE_STONE_SLAB, 15],
				15 => [BlockLegacyIds::DOUBLE_STONE_SLAB, 14]
			],*/
			BlockLegacyIds::STONE_SLAB => [
				6 => [BlockLegacyIds::STONE_SLAB, 7],
				7 => [BlockLegacyIds::STONE_SLAB, 6],
				14 => [BlockLegacyIds::STONE_SLAB, 15],
				15 => [BlockLegacyIds::STONE_SLAB, 14],
			],
            /*BlockLegacyIds::TRAPDOOR => [
				0 => [BlockLegacyIds::TRAPDOOR, 3],
				1 => [BlockLegacyIds::TRAPDOOR, 2],
				2 => [BlockLegacyIds::TRAPDOOR, 1],
				3 => [BlockLegacyIds::TRAPDOOR, 0],
				4 => [BlockLegacyIds::TRAPDOOR, 11],
				5 => [BlockLegacyIds::TRAPDOOR, 10],
				6 => [BlockLegacyIds::TRAPDOOR, 9],
				7 => [BlockLegacyIds::TRAPDOOR, 8],
				8 => [BlockLegacyIds::TRAPDOOR, 7],
				9 => [BlockLegacyIds::TRAPDOOR, 6],
				10 => [BlockLegacyIds::TRAPDOOR, 5],
				11 => [BlockLegacyIds::TRAPDOOR, 4],
				12 => [BlockLegacyIds::TRAPDOOR, 15],
				13 => [BlockLegacyIds::TRAPDOOR, 14],
				14 => [BlockLegacyIds::TRAPDOOR, 13],
				15 => [BlockLegacyIds::TRAPDOOR, 12]
			],*/
			BlockLegacyIds::IRON_TRAPDOOR => [
				0 => [BlockLegacyIds::IRON_TRAPDOOR, 3],
				1 => [BlockLegacyIds::IRON_TRAPDOOR, 2],
				2 => [BlockLegacyIds::IRON_TRAPDOOR, 1],
				3 => [BlockLegacyIds::IRON_TRAPDOOR, 0],
				4 => [BlockLegacyIds::IRON_TRAPDOOR, 11],
				5 => [BlockLegacyIds::IRON_TRAPDOOR, 10],
				6 => [BlockLegacyIds::IRON_TRAPDOOR, 9],
				7 => [BlockLegacyIds::IRON_TRAPDOOR, 8],
				8 => [BlockLegacyIds::IRON_TRAPDOOR, 7],
				9 => [BlockLegacyIds::IRON_TRAPDOOR, 6],
				10 => [BlockLegacyIds::IRON_TRAPDOOR, 5],
				11 => [BlockLegacyIds::IRON_TRAPDOOR, 4],
				12 => [BlockLegacyIds::IRON_TRAPDOOR, 15],
				13 => [BlockLegacyIds::IRON_TRAPDOOR, 14],
				14 => [BlockLegacyIds::IRON_TRAPDOOR, 13],
				15 => [BlockLegacyIds::IRON_TRAPDOOR, 12]
			],
			BlockLegacyIds::STONE_BUTTON => [
				1 => [BlockLegacyIds::STONE_BUTTON, 5],
				2 => [BlockLegacyIds::STONE_BUTTON, 4],
				3 => [BlockLegacyIds::STONE_BUTTON, 3],
				4 => [BlockLegacyIds::STONE_BUTTON, 2],
				5 => [BlockLegacyIds::STONE_BUTTON, 1],
			],
            /*BlockLegacyIds::WOODEN_BUTTON => [
				1 => [BlockLegacyIds::WOODEN_BUTTON, 5],
				2 => [BlockLegacyIds::WOODEN_BUTTON, 4],
				3 => [BlockLegacyIds::WOODEN_BUTTON, 3],
				4 => [BlockLegacyIds::WOODEN_BUTTON, 2],
				5 => [BlockLegacyIds::WOODEN_BUTTON, 1],
			],

			BlockLegacyIds::STICKY_PISTON => [
				2 => [BlockLegacyIds::STICKY_PISTON, 3],
				3 => [BlockLegacyIds::STICKY_PISTON, 2],
				4 => [BlockLegacyIds::STICKY_PISTON, 5],
				5 => [BlockLegacyIds::STICKY_PISTON, 4]
			],*/
			BlockLegacyIds::TALL_GRASS => [0 => [BlockLegacyIds::DEAD_BUSH, 0]],
            /*BlockLegacyIds::PISTON => [
				2 => [BlockLegacyIds::PISTON, 3],
				3 => [BlockLegacyIds::PISTON, 2],
				4 => [BlockLegacyIds::PISTON, 5],
				5 => [BlockLegacyIds::PISTON, 4]
			],*/
			BlockLegacyIds::DIRT => [2 => [BlockLegacyIds::PODZOL, 0]],
		];
	}

	public static function convertRaw(int &$id, int &$meta): bool {
		self::initBlockConversionTable();
		if(isset(self::$BLOCKS[$id])) {
			$replace = self::$BLOCKS[$id];
			if(isset($replace[-1]) || isset($replace[$meta])) {
				$metaKey = isset($replace[$meta]) ? $meta : -1;
				$id = $replace[$metaKey][0];
				if($replace[$metaKey][1] !== null) {
					$meta = $replace[$metaKey][1];
				}
			}
			return true;
		}
		return false;
	}

	private static function traverseJsonText(array $jsonData): string {
		$text = $jsonData["text"];
		if($jsonData["obfuscated"] ?? false) $text = TextFormat::OBFUSCATED . $text;
		if($jsonData["strikethrough"] ?? false) $text = TextFormat::STRIKETHROUGH . $text;
		if($jsonData["underlined"] ?? false) $text = TextFormat::UNDERLINE . $text;
		if($jsonData["italic"] ?? false) $text = TextFormat::ITALIC . $text;
		if($jsonData["bold"] ?? false) $text = TextFormat::BOLD . $text;
		if(isset($jsonData["color"])){
			$refClass = new \ReflectionClass(TextFormat::class);
			$text = $refClass->getConstant(strtolower($jsonData["color"])) . $text;
		}
		return $text;
	}

	public function convert(ChunkData $chunkData): void {
		$chunk = $chunkData->getChunk();
		for($y = 0; $y < World::Y_MAX; $y++) {
			for($x = 0; $x < 16; $x++) {
				for($z = 0; $z < 16; $z++) {
					$fbID = $chunk->getStateBlock($x, $y, $z);
					$id = $fbID >> 4;
					$meta = $fbID & 0xf;
					if(!self::convertRaw($id, $meta)) continue;
					$chunk->setStateBlock($x, $y, $z, ($id << 4) | $meta);
				}
			}
		}

		foreach($chunkData->getTileNBT() as $tag) {
			$id = $tag->getString("id");
			if($id === "minecraft:sign" || $id === "Sign") {
				for($i = 1; $i <= 4; $i++) {
					if(($line = $tag->getString("Text$i", "null")) === "null") {
						$tag->setString("Text$i", "");
						continue;
					}
					try {
						$jsonData = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
						if(!is_array($jsonData) && is_string($jsonData)){
							$tag->setString("Text$i", $jsonData);
							continue;
						}
						$text = self::traverseJsonText($jsonData);
						foreach($jsonData["extra"] ?? [] as $extraText) {
							if(!is_array($extraText) && is_string($extraText)){
								$text .= $extraText;
								continue;
							}
							$text .= self::traverseJsonText($extraText);
						}
						$tag->setString("Text$i", $text);
					} catch(\JsonException) {
						continue;
					}
				}
			} elseif($id === "minecraft:furnace" || $id === "Furnace") {
				foreach($tag->getValue() as $name => $_tag) {
					if(in_array($name, ["BurnTime", "CookTime"]) && $_tag instanceof IntTag){
						$tag->removeTag($name);
						$tag->setShort($name, $_tag->getValue());
					}
				}
			}
		}
	}
}