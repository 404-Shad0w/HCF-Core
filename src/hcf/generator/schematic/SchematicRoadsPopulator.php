<?php


namespace hcf\generator\schematic;


use CortexPE\libSchematic\Schematic;
use pocketmine\block\BlockTypeIds;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\populator\Populator;
use pocketmine\world\World;

class SchematicRoadsPopulator implements Populator {
	/** @var int */
	private $chunkWidth;
	/** @var Schematic|null */
	private $schematic;

	public const DIR_N = 0;
	public const DIR_E = 1;
	public const DIR_S = 2;
	public const DIR_W = 3;
	public const DIR_C = -1; // center

	public static function getChunkDirection(int $chunkX, int $chunkZ): int {
		//N = X--
		//S = X++
		//E = Z--
		//W = Z++
		if($chunkX === 0 && $chunkZ === 0) {
			return self::DIR_C;
		}
		if(abs($chunkX) > abs($chunkZ)) { // it probably moves more on the X axis
			if($chunkX > 0) {
				return self::DIR_W;
			}
			return self::DIR_E;
		} elseif(abs($chunkX) < abs($chunkZ)) { // it probably moves more on the Z axis
			if($chunkZ > 0) {
				return self::DIR_S;
			}
			return self::DIR_N;
		}
		return self::DIR_C;
	}

	public function __construct(Schematic $schematic) {
		$this->schematic = $schematic;
		$this->chunkWidth = max(floor($this->schematic->getWidth() / 2) >> 4, 1);
	}

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void {
		if(
			!($chunkX >= -$this->chunkWidth && $chunkX <= $this->chunkWidth) &&
			!($chunkZ >= -$this->chunkWidth && $chunkZ <= $this->chunkWidth)
		) return;
		$realX = $chunkX << 4;
		$realZ = $chunkZ << 4;
		$dir = self::getChunkDirection($chunkX, $chunkZ);
		if($dir === self::DIR_C) return;
		$schem = $this->schematic->rotate($dir);

		$xOff = $zOff = 0;
		for($cX = 0; $cX < 16; $cX++) {
			for($cZ = 0; $cZ < 16; $cZ++) {
				$x = $realX + $cX;
				$z = $realZ + $cZ;

				switch($dir) {
					case self::DIR_E: // x--
					case self::DIR_W: // x++
						$l = $schem->getLength();
						$zOff = floor($halfZ = ($l / 2));
						if($z > $zOff || $z < -$zOff) {
							continue 2;
						}
						break;
					case self::DIR_N: // z--
					case self::DIR_S: // z++
						$w = $schem->getWidth();
						$xOff = floor($halfX = ($w / 2));
						if($x > $xOff || $x < -$xOff) {
							continue 2;
						}
						break;
					default:
						return;
				}
				// we only roll it around if it's the FORWARD direction the road is going
				$sX = abs($xOff > 0 ? $x + $xOff : $x % $schem->getWidth());
				$sZ = abs($zOff > 0 ? $z + $zOff : $z % $schem->getLength());

				for($bY = World::Y_MAX; $bY > 0; $bY--) {
					$bID = $world->getBlockAt($x, $bY, $z)->getTypeId();
					if($bID === BlockTypeIds::AIR || $bID === BlockTypeIds::SNOW_LAYER) continue;
					for($sY = 0; $sY < $schem->getHeight(); $sY++) {
						if($bY + $sY >= World::Y_MAX) break;
						if(!$schem->isWithinSchematic($sX, $sY, $sZ)) continue;
						$world->setBlockAt($x, $bY + $sY, $z, $schem->getBlockAt($sX, $sY, $sZ));
					}
					break;
				}
			}
		}
	}
}