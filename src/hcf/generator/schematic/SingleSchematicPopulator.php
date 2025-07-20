<?php


namespace hcf\generator\schematic;


use CortexPE\libSchematic\Schematic;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\populator\Populator;
use pocketmine\world\World;

class SingleSchematicPopulator implements Populator {
	/** @var Schematic */
	private $schematic;
	/** @var Vector3 */
	private $pos; // center pos, y = ground level
	/** @var int */
	private $minChunkX;
	/** @var int */
	private $minChunkZ;
	/** @var int */
	private $maxChunkX;
	/** @var int */
	private $maxChunkZ;
	/** @var int */
	private $midX;
	/** @var int */
	private $midZ;
	/** @var int */
	private $schemWidth;
	/** @var int */
	private $schemHeight;
	/** @var int */
	private $schemLength;
	/** @var int */
	private $groundOffset = 0;

	public function __construct(Schematic $schematic, Vector3 $pos, bool $ignoreYBounds) {
		$this->schematic = $schematic;
		$this->schemWidth = $schematic->getWidth();
		$this->schemHeight = $schematic->getHeight();
		$this->schemLength = $schematic->getLength();
		$chunkWidth = ($this->schemWidth >> 4);
		$this->pos = $pos;
		$chunkPosX = $pos->x >> 4;
		$chunkPosZ = $pos->z >> 4;
		$halfWidth = floor($chunkWidth / 2) + 1;
		$this->minChunkX = $chunkPosX - $halfWidth;
		$this->minChunkZ = $chunkPosZ - $halfWidth;
		$this->maxChunkX = $chunkPosX + $halfWidth;
		$this->maxChunkZ = $chunkPosZ + $halfWidth;
		$this->midX = (int)floor($this->schemWidth / 2);
		$this->midZ = (int)floor($this->schemLength / 2);
		if($ignoreYBounds) return;
		$bounds = SchematicBoundsFinder::from($schematic);
		// sometimes we might have a spawn without a straight edge
		$this->groundOffset = max($bounds->getGroundOffset(), $bounds->getConsistentXEdgeHeight());
		$this->pos->y -= $this->groundOffset;
	}

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void {
		if($chunkX < $this->minChunkX || $chunkX > $this->maxChunkX) return;
		if($chunkZ < $this->minChunkZ || $chunkZ > $this->maxChunkZ) return;
		$realX = $chunkX << 4;
		$realZ = $chunkZ << 4;
		$chunk = $world->getChunk($chunkX, $chunkZ);
		for($y = $this->groundOffset; $y < $this->schemHeight; $y++) {
			for($cx = 0; $cx < 16; $cx++) {
				$cxInSchem = ($realX - $this->pos->x) + $this->midX + $cx;
				for($cz = 0; $cz < 16; $cz++) {
					$czInSchem = ($realZ - $this->pos->z) + $this->midZ + $cz;
					if(!$this->schematic->isWithinSchematic($cxInSchem, $y, $czInSchem)) continue;
					$realY = $y + $this->pos->y;
					if($realY >= World::Y_MAX) continue 3;
					$chunk->setFullBlock($cx, $realY, $cz, $this->schematic->getBlockAt($cxInSchem, $y, $czInSchem)->getFullId());
				}
			}
		}
	}
}