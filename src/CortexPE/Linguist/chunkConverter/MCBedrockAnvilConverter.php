<?php


namespace CortexPE\Linguist\chunkConverter;


use pocketmine\nbt\tag\IntTag;
use pocketmine\world\format\io\ChunkData;

class MCBedrockAnvilConverter extends ChunkConverter {
	public function convert(ChunkData $chunkData): void {
		// do fuckin nothing as we are already in bedrock, actually maybe convert some NBT to prevent crashes lol

		foreach($chunkData->getTileNBT() as $tag) {
			$id = $tag->getString("id");
			if($id === "minecraft:furnace" || $id === "Furnace") {
				foreach($tag->getValue() as $name => $_tag) {
					if(in_array($name, ["BurnTime", "CookTime"]) && $_tag instanceof IntTag) {
						$tag->removeTag($name);
						$tag->setShort($name, $_tag->getValue());
					}
				}
			}
		}
	}
}