<?php


namespace hcf\generator\overworld\populator\misc;


use pocketmine\block\BlockTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\populator\Populator;

class BeachChairs implements Populator {

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void {
		//if($random->nextBoolean())return; // 1 out of (2^2 = 4) chance

		$x = ($chunkX << 4) + $random->nextBoundedInt(15);
		$z = ($chunkZ << 4) + $random->nextBoundedInt(15);
		$y = $this->getHighestWorkableBlock($world, $x, $z);

		$this->attemptBeachBench($world, $world->getChunk($chunkX, $chunkZ), $x, $y, $z, $random);
	}

	private function getHighestWorkableBlock(ChunkManager $world, int $x, int $z): int {
		for($y = 64; $y >= 0; --$y) {
			$b = $world->getBlockAt($x, $y, $z);
			if($b->getId() !== BlockLegacyIds::AIR) {
				return $y + 1;
			}
		}

		return -1;
	}

	private function attemptBeachBench(ChunkManager $world, Chunk $chunk, int $x, int $y, int $z, Random $random): void {
		if($y !== 62) return;

		$sand = VanillaBlocks::SAND()->getFullId();
		$water = VanillaBlocks::WATER()->setStill(true)->getFullId();

		$rcx = $x & 0x0f;
		$rcz = $z & 0x0f;

		if($chunk->getFullBlock($rcx, $y - 1, $rcz) !== $sand) return;

		for($iy = 0; $iy <= 4; $iy++) {
			for($ix = -1; $ix <= 1; $ix++) {
				for($iz = -1; $iz <= 1; $iz++) {
					if($world->getBlockAt($x + $ix, $y + $iy, $z + $iz)->getTypeId() !== BlockTypeIds::AIR) return;
				}
			}
		}

		$sides = [];
		$vec = new Vector3($x, $y - 1, $z);
		foreach(Facing::HORIZONTAL as $facing) {
			for($i = 1; $i <= 5; $i++) {
				$cVec = $vec->getSide($facing, $i);
				if($world->getBlockAt($cVec->x, $cVec->y, $cVec->z)->getFullId() === $water) {
					if($i < 3) {
						return; // we are too near the water
					}
					$sides[$facing] = $i;
					break;
				}
			}
		}
		if(count($sides) < 1) return;

		asort($sides);

		$facing = null;
		foreach($sides as $face => $distance) {
			$facing = Facing::opposite($face);
			break;
		}

		$stair = VanillaBlocks::OAK_STAIRS()->setFacing($facing);
		$slab = VanillaBlocks::OAK_SLAB();

		$rotated90 = Facing::rotateY($facing, true);

		$fVec = new Vector3($x, $y, $z);
		$stair1 = $fVec->getSide($rotated90);
		$world->setBlockAt($stair1->x, $stair1->y, $stair1->z, $stair);
		$s1Fs = $stair1->getSide(Facing::opposite($facing));
		$world->setBlockAt($s1Fs->x, $s1Fs->y, $s1Fs->z, $slab);

		$stair2 = $fVec->getSide(Facing::opposite($rotated90));
		$world->setBlockAt($stair2->x, $stair2->y, $stair2->z, $stair);
		$s2Fs = $stair2->getSide(Facing::opposite($facing));
		$world->setBlockAt($s2Fs->x, $s2Fs->y, $s2Fs->z, $slab);

		$randColor = DyeColor::getAll();
		$color = $randColor[array_keys($randColor)[$random->nextInt() % count($randColor)]];
		if($random->nextBoolean()) {
			$world->setBlockAt($x, $y, $z, VanillaBlocks::OAK_FENCE());
			$world->setBlockAt($x, $y + 1, $z, VanillaBlocks::CARPET()->setColor($color));
		} else {
			$ironBars = VanillaBlocks::IRON_BARS();
			$netherFence = VanillaBlocks::NETHER_BRICK_FENCE();
			$world->setBlockAt($x, $y, $z, $ironBars);
			$world->setBlockAt($x, $y + 1, $z, $ironBars);
			$world->setBlockAt($x, $y + 2, $z, $netherFence);
			$world->setBlockAt($x, $y + 3, $z, VanillaBlocks::SNOW_LAYER()->setLayers(2));
			$coloredCarpet = VanillaBlocks::CARPET()->setColor($color);
			$world->setBlockAt($x + 1, $y + 3, $z, $coloredCarpet);
			$world->setBlockAt($x - 1, $y + 3, $z, $coloredCarpet);
			$world->setBlockAt($x, $y + 3, $z - 1, $coloredCarpet);
			$world->setBlockAt($x, $y + 3, $z + 1, $coloredCarpet);
			$whiteCarpet = VanillaBlocks::CARPET()->setColor(DyeColor::WHITE());
			$world->setBlockAt($x + 1, $y + 3, $z + 1, $whiteCarpet);
			$world->setBlockAt($x - 1, $y + 3, $z - 1, $whiteCarpet);
			$world->setBlockAt($x + 1, $y + 3, $z - 1, $whiteCarpet);
			$world->setBlockAt($x - 1, $y + 3, $z + 1, $whiteCarpet);
		}
	}
}