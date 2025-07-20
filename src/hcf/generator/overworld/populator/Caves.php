<?php

namespace hcf\generator\overworld\populator;

use CortexPE\Rave\interpolation\LinearInterpolation;
use CortexPE\Rave\Perlin;
use CortexPE\std\math\BezierCurve;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\populator\Populator;

class Caves implements Populator {
	private const WORMS = 8;
	private const WORM_MOVEMENT = 8;
	private const MAX_RADIUS = 6;
	private const MAX_ALTITUDE = 64;

	private $worldRandom;

	public function __construct(Random $worldRandom) {
		$this->worldRandom = $worldRandom;
	}

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void {
		$rand = new Random(0xB16B00B5 ^ ($chunkX << 8) ^ $chunkZ ^ $this->worldRandom->getSeed());
		$rand->setSeed($rand->nextSignedInt() ^ $rand->nextSignedInt() ^ $rand->nextSignedInt());
		$perlin = new Perlin(new LinearInterpolation(), $rand);

		$realX = $chunkX << 4;
		$realZ = $chunkZ << 4;
		$distNeeded = 16 * 16;

		for($i = 0; $i < self::WORMS; $i++) {
			$curve = $this->getBezierCurveFor($chunkX, $chunkZ, $i);
			for($j = 0; $j <= 1; $j += 1 / 100) {
				$pt = $curve->getPoint($j)->floor();
				$dist2 = (($pt->x - $realX) ** 2) + (($pt->z - $realZ) ** 2); // how far is this point from our chunk origin?
				if($dist2 > $distNeeded) continue;
				$radius = floor(self::MAX_RADIUS * ($perlin->noise3D(($realX + ($j * 10)) * (1 / 16), $i, 0) * 0.5 + 0.5));
				$rad2 = $radius * $radius;

				for($x = $pt->x - $radius; $x <= $pt->x + $radius; $x++) {
					for($y = $pt->y - $radius; $y <= $pt->y + $radius; $y++) {
						if($y <= 0) continue;
						for($z = $pt->z - $radius; $z <= $pt->z + $radius; $z++) {
							//if(($x >> 4) !== $chunkX || ($z >> 4) !== $chunkZ) continue; // ignore blocks outside this chunk

							$dist2 = (($x - $pt->x) ** 2) + (($y - $pt->y) ** 2) + (($z - $pt->z) ** 2);
							if($dist2 > $rad2) continue;
							if($dist2 == $rad2) continue;

							try {
								if($y <= 10) {
									$world->setBlockAt($x, $y, $z, VanillaBlocks::LAVA());
								} elseif($world->getBlockAt($x, $y, $z)->getTypeId() !== BlockTypeIds::WATER) {
									$world->setBlockAt($x, $y, $z, VanillaBlocks::AIR());
								}
							} catch(\InvalidArgumentException $e) {
								/*$cCx = $x >> 4;
								$cCz = $z >> 4;

								$cdx = $chunkX - $cCx;
								$cdz = $chunkZ - $cCz;

								var_dump("unable to set block $x $y $z, chunk diff {$cdx} {$cdz}");*/
							}
						}
					}
				}
			}
		}
	}

	public function getBezierCurveFor(int $chunkX, int $chunkZ, int $wormNumber): BezierCurve {
		$base = 0xB16B00B5 ^ ($chunkX << 8) ^ $chunkZ;
		$tmpRand = new Random($base);
		$rand = new Random($base ^ $tmpRand->nextSignedInt() ^ $tmpRand->nextSignedInt() ^ $tmpRand->nextSignedInt());
		for($i = 0; $i <= $wormNumber; $i++) {
			$rand->nextSignedInt(); // randomize it more
		}
		unset($tmpRand);

		$realX = $chunkX * 16;
		$realZ = $chunkZ * 16;

		$curve = new BezierCurve();
		$n = $rand->nextRange(4, 5);
		$lastPos = new Vector3(
			$realX + (16 * ($realX < 0 ? -1 : 1) * ($rand->nextSignedFloat() - 1)),
			self::MAX_ALTITUDE * (($rand->nextFloat() * 0.5) + (($rand->nextFloat() * $rand->nextFloat()) * 0.5)),
			$realZ + (16 * ($realZ < 0 ? -1 : 1) * ($rand->nextSignedFloat() - 1))
		);
		for($i = 0; $i < $n; $i++) {
			$curve->addPoint($lastPos = $lastPos->add(
				self::WORM_MOVEMENT * ($rand->nextSignedFloat() - 1),
				self::WORM_MOVEMENT * ($rand->nextSignedFloat() - 1) * (0.5 * $rand->nextFloat()),
				self::WORM_MOVEMENT * ($rand->nextSignedFloat() - 1)
			));
		}
		return $curve;
	}
}
