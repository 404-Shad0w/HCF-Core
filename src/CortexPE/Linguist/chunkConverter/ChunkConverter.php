<?php


namespace CortexPE\Linguist\chunkConverter;


use pocketmine\world\format\io\ChunkData;

abstract class ChunkConverter {
	abstract public function convert(ChunkData $chunkData):void;
}