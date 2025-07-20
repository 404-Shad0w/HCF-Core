<?php

namespace CortexPE\Linguist\task;

use CortexPE\Linguist\chunkConverter\MCJavaAnvilConverter;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\World;

class AsyncChunkConvertTask extends AsyncTask {
	private $worldID;
	private $chunkData;

	public function __construct(World $world, Chunk $chunk, private int $chunkX, private int $chunkZ) {
		$this->worldID = $world->getId();
		$chunk = clone $chunk;
		foreach($chunk->getTiles() as $tile) $chunk->removeTile($tile); // we can't serialize these yet
		$this->chunkData = serialize($chunk);
	}

	public function onRun(): void {
		$converter = new MCJavaAnvilConverter();
		$data = new ChunkData(unserialize($this->chunkData), [], []);
		$converter->convert($data);
		$this->chunkData = serialize(clone $data->getChunk());
	}

	public function onCompletion(): void {
		$w = Server::getInstance()->getWorldManager()->getWorld($this->worldID);
		if($w === null) {
			\GlobalLogger::get()->error("World with ID {$this->worldID} not found!");
			return;
		}
		/** @var Chunk $chunkData */
		$chunkData = unserialize($this->chunkData);
		$w->setChunk($this->chunkX, $this->chunkZ, $chunkData);
		\GlobalLogger::get()->error("Converted {$w->getFolderName()} {$this->chunkX} {$this->chunkZ}");
	}
}