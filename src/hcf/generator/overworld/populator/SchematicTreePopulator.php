<?php


namespace hcf\generator\overworld\populator;


use CortexPE\libSchematic\Schematic;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\populator\Populator;
use pocketmine\world\World;

// todo: rewrite this to work more like a grid...
class SchematicTreePopulator implements Populator, ExtendedPopulator {
	/** @var Schematic */
	private $schematic;
	/** @var int */
	private $length;
	/** @var int */
	private $width;
	/** @var int */
	private $height;
	/** @var int */
	private $midX;
	/** @var int */
	private $midZ;
	/** @var int[] */
	private $extraBlocks = [];
	/** @var float */
	private $chance = 0.5;
	/** @var int */
	private $minDistance = 24;
	/** @var int */
	private $jitter = 0;
	/** @var int */
	private $yOffset = -3;

	/**
	 * @param string $schematicFile
	 */
	public function __construct(string $schematicFile) {
		$this->schematic = Schematic::fromFile($schematicFile);
		$this->width = $this->schematic->getWidth();
		$this->length = $this->schematic->getLength();
		$this->height = $this->schematic->getHeight();
		$this->midX = floor($this->width / 2);
		$this->midZ = floor($this->length / 2);
	}

	/**
	 * @param float $chance
	 */
	public function setChance(float $chance): void {
		$this->chance = $chance;
	}

	/**
	 * @param int $minDistance
	 */
	public function setMinDistance(int $minDistance): void {
		$this->minDistance = $minDistance;
	}

	/**
	 * @param int $yOffset
	 */
	public function setYOffset(int $yOffset): void {
		$this->yOffset = $yOffset;
	}

	/**
	 * @param int $jitter
	 */
	public function setJitter(int $jitter): void {
		$this->jitter = $jitter;
	}

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void {
		$this->extraBlocks = [];

		$chunk = $world->getChunk($chunkX, $chunkZ);

		for($ix = 0; $ix < 16; $ix++) {
			if($this->jitter !== 0) $ix += $random->nextSignedInt() % $this->jitter;

			$rx = ($chunkX << 4) + $ix;
			if(($rx % $this->minDistance) !== 0) continue;

			for($iz = 0; $iz < 16; $iz++) {
				if($random->nextFloat() > $this->chance) continue;

				if($this->jitter !== 0) $iz += $random->nextSignedInt() % $this->jitter;

				$rz = ($chunkZ << 4) + $iz;
				if(($rz % $this->minDistance) !== 0) continue;

				for($iy = World::Y_MAX - 1; $iy > 0; $iy--) {
					$bID = $chunk->getFullBlock($ix, $iy, $iz) >> 4;
					if(($yOff = self::getYPassThroughOffset($bID)) === null) continue;
					if($yOff === -1) break; // we didn't land on air... nor snow layer... must be solid tbh

					$ry = $iy + $yOff + $this->yOffset;

					for($sx = 0; $sx < $this->width; $sx++){
						for($sz = 0; $sz < $this->length; $sz++){
							for($sy = 0; $sy < $this->height; $sy++){
								$block = $this->schematic->getBlockAt($sx, $sy, $sz);
								if($block->getId() === BlockTypeIds::AIR) continue;
								$cX = ($rx - $this->midX) + $sx;
								$cZ = ($rz - $this->midZ) + $sz;
								try {
									$world->setBlockAt($cX, $ry + $sy, $cZ, $block);
								} catch(\InvalidArgumentException $e){
									// this probably does not work,
									// there's no guarantees that the next chunk to generate will ever encounter these blocks
									// todo: work around this
									$this->extraBlocks[] = [$cX, $ry + $sy, $cZ, $block];
								}
							}
						}
					}

					break;
				}
			}
		}
	}

	/**
	 * @return int[][]
	 */
	public function getExtraBlocks(): array {
		return $this->extraBlocks;
	}

	private static function getYPassThroughOffset(int $blockID): ?int {
		// todo: use this along with highestBlockAt (uses subchunk height maps) for faster working-y finding
		if(
			$blockID === BlockTypeIds::DIRT ||
			$blockID === BlockTypeIds::GRASS ||
			$blockID === BlockTypeIds::TALL_GRASS ||
			$blockID === BlockTypeIds::DOUBLE_PLANT
		) {
			return 1;
		} elseif(
			$blockID !== BlockTypeIds::AIR &&
			$blockID !== BlockTypeIds::SNOW_LAYER
		) {
			return -1;
		}
		return null;
	}
}