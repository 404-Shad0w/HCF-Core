<?php


namespace hcf\generator\overworld\populator;


use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockTypeIds;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\populator\Populator;

class FlowerPatches implements Populator {
	/** @var float */
	private $probability = 0.9;
	/** @var int */
	private $maxRadius = 4;
	/** @var int */
	private $flowerFullness = 0.5;
	/** @var int */
	private $maxHeight = 127;
	/** @var int[] */
	private $flowerTypes = []; // full IDs

	public function setProbability(float $probability): void {
		$this->probability = $probability;
	}

	public function setMaxRadius(int $radius): void {
		$this->maxRadius = $radius;
	}

	public function setFlowerFullness(int $flowerFullness): void {
		$this->flowerFullness = $flowerFullness;
	}

	public function addFlowerType(Block $block): void {
		$this->flowerTypes[] = $block->getStateId();
	}

	/**
	 * @param int $maxHeight
	 */
	public function setMaxHeight(int $maxHeight): void {
		$this->maxHeight = $maxHeight;
	}

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void {
		if($random->nextFloat() > $this->probability) return;

		$x = ($chunkX << 4) + $random->nextRange(0, 16);
		$z = ($chunkZ << 4) + $random->nextRange(0, 16);

		$type = $this->flowerTypes[$random->nextInt() % count($this->flowerTypes)];

		$radius = floor($random->nextFloat() * $this->maxRadius);
		$rad2 = $radius * $radius;

		for($ix = -$radius; $ix <= $radius; $ix++) {
			for($iz = -$radius; $iz <= $radius; $iz++) {
				$cx = $x + $ix;
				$cz = $z + $iz;

				if((($ix + $ix) + ($iz * $iz)) > $rad2) continue;
				if($random->nextFloat() > $this->flowerFullness) continue;

				$y = $this->getHighestWorkableBlock($world, $cx, $cz);
				if(!$this->canFlowerStay($world, $cx, $y, $cz)) continue;
				$world->setBlockAt($cx, $y, $cz, BlockFactory::getInstance()->fromFullBlock($type));
			}
		}
	}

	private function getHighestWorkableBlock(ChunkManager $world, int $x, int $z): int {
		for($y = $this->maxHeight; $y >= 0; --$y) {
			$b = $world->getBlockAt($x, $y, $z)->getTypeId();
			if($b !== BlockTypeIds::AIR and $b !== BlockLegacyIds::LEAVES and $b !== BlockTypeIds::LEAVES2 and $b !== BlockTypeIds::SNOW_LAYER) {
				return $y + 1;
			}
		}

		return -1;
	}

	private function canFlowerStay(ChunkManager $world, int $x, int $y, int $z): bool {
		$b = $world->getBlockAt($x, $y, $z)->getId();
		return $b === BlockTypeIds::AIR and $world->getBlockAt($x, $y - 1, $z)->getTypeId() === BlockTypeIds::GRASS;
	}
}