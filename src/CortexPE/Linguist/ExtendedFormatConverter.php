<?php


namespace CortexPE\Linguist;


use CortexPE\Linguist\chunkConverter\ChunkConverter;
use CortexPE\std\ReflectionUtils;
use CortexPE\SynCORE\utils\TimeUtils;
use pocketmine\utils\Filesystem;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\format\io\FormatConverter;
use pocketmine\world\format\io\WorldProvider;
use pocketmine\world\format\io\WritableWorldProvider;
use pocketmine\world\format\io\WritableWorldProviderManagerEntry;

class ExtendedFormatConverter extends FormatConverter {

	/** @var ChunkConverter */
	private ChunkConverter $converter;
	/** @var WorldProvider */
	private $oldProvider;
	/** @var WritableWorldProvider|string */
	private $newProvider;

	/** @var string */
	private $backupPath;

	/** @var \Logger */
	private $logger;

	/** @var int */
	private $chunksPerProgressUpdate;

	/**
	 * @phpstan-template TNewProvider of WritableWorldProvider
	 * @phpstan-param class-string<TNewProvider> $newProvider
	 * @param ChunkConverter $converter
	 * @param WorldProvider $oldProvider
	 * @param WritableWorldProviderManagerEntry $newProvider
	 * @param string $backupPath
	 * @param \Logger $logger
	 * @param int $chunksPerProgressUpdate
	 * @throws \ReflectionException
	 */
	public function __construct(ChunkConverter $converter, WorldProvider $oldProvider, WritableWorldProviderManagerEntry $newProvider, string $backupPath, \Logger $logger, int $chunksPerProgressUpdate = 256) {
		$this->converter = $converter;
		parent::__construct($oldProvider, $newProvider, $backupPath, $logger, $chunksPerProgressUpdate);
		$this->oldProvider = ReflectionUtils::getProperty(FormatConverter::class, $this, "oldProvider");
		$this->newProvider = ReflectionUtils::getProperty(FormatConverter::class, $this, "newProvider");
		$this->backupPath = ReflectionUtils::getProperty(FormatConverter::class, $this, "backupPath");
		$this->logger = ReflectionUtils::getProperty(FormatConverter::class, $this, "logger");
		$this->chunksPerProgressUpdate = ReflectionUtils::getProperty(FormatConverter::class, $this, "chunksPerProgressUpdate");
	}

	public function execute(): WritableWorldProvider {
		/** @var WritableWorldProvider $new */
		$new = ReflectionUtils::invoke(FormatConverter::class, $this, "generateNew");

		ReflectionUtils::invoke(FormatConverter::class, $this, "populateLevelData", $new->getWorldData());
		$this->convertTerrain($new);

		$path = $this->oldProvider->getPath();
		$this->oldProvider->close();
		$new->close();

		$this->logger->info("Backing up pre-conversion world to " . $this->backupPath);
		if(!@rename($path, $this->backupPath)){
			$this->logger->warning("Moving old world files for backup failed, attempting copy instead. This might take a long time.");
			Filesystem::recursiveCopy($path, $this->backupPath);
			Filesystem::recursiveUnlink($path);
		}
		if(!@rename($new->getPath(), $path)){
			//we don't expect this to happen because worlds/ should most likely be all on the same FS, but just in case...
			$this->logger->debug("Relocation of new world files to location failed, attempting copy and delete instead");
			Filesystem::recursiveCopy($new->getPath(), $path);
			Filesystem::recursiveUnlink($new->getPath());
		}

		$this->logger->info("Conversion completed");
		return $this->newProvider->fromPath($path);
	}

	private function convertTerrain(WritableWorldProvider $new): void {
		$this->logger->info("Calculating chunk count");
		$count = $this->oldProvider->calculateChunkCount();
		$this->logger->info("Discovered $count chunks");

		$counter = 0;

		$start = microtime(true);
		$thisRound = $start;
		/**
		 * @var int[] $coords
		 * @var ChunkData $chunkData
		 */
		foreach($this->oldProvider->getAllChunks(true, $this->logger) as $coords => $chunkData) {
			[$chunkX, $chunkZ] = $coords;
			$chunkData->getChunk()->setTerrainDirty();
			$this->converter->convert($chunkData);
			$new->saveChunk($chunkX, $chunkZ, $chunkData);
			$counter++;
			if(($counter % $this->chunksPerProgressUpdate) !== 0) continue;
			$time = microtime(true);
			$diff = $time - $thisRound;
			$thisRound = $time;

			$cPerSec = floor($this->chunksPerProgressUpdate / $diff);
			$humanizedTime = TimeUtils::humanizeDuration(($count - $counter) / $cPerSec);

			$this->logger->info("Converted $counter / $count chunks ($cPerSec chunks/sec, remaining time: $humanizedTime)");
		}
		$total = microtime(true) - $start;
		$this->logger->info("Converted $counter / $counter chunks in " . round($total, 3) . " seconds (" . floor($counter / $total) . " chunks/sec)");
	}
}