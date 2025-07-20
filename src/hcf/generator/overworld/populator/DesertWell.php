<?php


namespace hcf\generator\overworld\populator;


use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\populator\Populator;

class DesertWell implements Populator {

	/** @var ChunkManager */
	private $level;

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random):void {
		$this->level = $world;
		if($random->nextRange(0, 20) === 10) {
			$x = $random->nextRange($chunkX * 16, $chunkX * 16 + 15);
			$z = $random->nextRange($chunkZ * 16, $chunkZ * 16 + 15);
			$y = $this->getHighestWorkableBlock($x, $z);

			if($y !== -1 and $this->canWellStay($x, $y, $z)) {
				$sandStone = VanillaBlocks::SANDSTONE();
				$water = VanillaBlocks::WATER();
				$sandStoneSlab = VanillaBlocks::SANDSTONE_SLAB();

				for($iy = $y - 1; $iy <= $y; $iy++) {
					for($ix = $x - 2; $ix <= $x + 2; $ix++) {
						for($iz = $z - 2; $iz <= $z + 2; $iz++) {
							$world->setBlockAt($ix, $iy, $iz, $sandStone);
						}
					}
				}

				$world->setBlockAt($x, $y, $z, $water);
				$world->setBlockAt($x - 1, $y, $z, $water);
				$world->setBlockAt($x + 1, $y, $z, $water);
				$world->setBlockAt($x, $y, $z - 1, $water);
				$world->setBlockAt($x, $y, $z + 1, $water);

				for($ix = -2; $ix <= 2; $ix++) {
					for($iz = -2; $iz <= 2; $iz++) {
						if($ix == -2 || $ix == 2 || $iz == -2 || $iz == 2) {
							$world->setBlockAt($x + $ix, $y + 1, $z + $iz, $sandStone);
						}
					}
				}
				$world->setBlockAt($x - 2, $y + 1, $z, $sandStoneSlab);
				$world->setBlockAt($x + 2, $y + 1, $z, $sandStoneSlab);
				$world->setBlockAt($x, $y + 1, $z - 2, $sandStoneSlab);
				$world->setBlockAt($x, $y + 1, $z + 2, $sandStoneSlab);

				for ($ix = -1; $ix <= 1; $ix++) {
					for ($iz = -1; $iz <= 1; $iz++) {
						if ($ix == 0 && $iz == 0) {
							$world->setBlockAt($x + $ix, $y + 4, $z + $iz, $sandStone);
						} else {
							$world->setBlockAt($x + $ix, $y + 4, $z + $iz, $sandStoneSlab);
						}
					}
				}

				for ($iy = 1; $iy <= 3; $iy++) {
					$world->setBlockAt($x - 1, $y + $iy, $z - 1, $sandStone);
					$world->setBlockAt($x - 1, $y + $iy, $z + 1, $sandStone);
					$world->setBlockAt($x + 1, $y + $iy, $z - 1, $sandStone);
					$world->setBlockAt($x + 1, $y + $iy, $z + 1, $sandStone);
				}
			}
		}
	}

	private function canWellStay(int $x, int $y, int $z): bool {
		if($this->level->getBlockAt($x, $y - 1, $z)->getId() !== BlockTypeIds::SAND) {
			return false;
		}
		for($ix = $x - 2; $ix <= $x + 2; $ix++) {
			for($iz = $z - 2; $iz <= $z + 2; $iz++) {
				if($this->level->getBlockAt($ix, $y - 1, $iz)->getTypeId() === BlockTypeIds::AIR && $this->level->getBlockAt($ix, $y - 2, $iz)->getTypeId() === BlockTypeIds::AIR) {
					return false;
				}
			}
		}
		return true;
	}

	private function getHighestWorkableBlock(int $x, int $z): int {
		for($y = 127; $y >= 0; --$y) {
			$b = $this->level->getBlockAt($x, $y, $z)->getTypeId();
			if($b !== BlockTypeIds::AIR) {
				return $y;
			}
		}

		return -1;
	}
}