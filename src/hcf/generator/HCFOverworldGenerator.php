<?php


namespace hcf\generator;


use CortexPE\libSchematic\Schematic;
use CortexPE\Rave\fbm\NoiseGroup;
use CortexPE\Rave\filter\blur\BoxAverage2D;
use CortexPE\Rave\filter\blur\Gaussian2D;
use CortexPE\Rave\filter\blur\kernel\GaussianKernel;
use CortexPE\Rave\Noise;
use CortexPE\Rave\override\Override;
use CortexPE\Rave\SimplexNoiseAdapter;
use hcf\generator\overworld\AmplifiedGroundCover;
use hcf\generator\overworld\biome\ChristmasThemedBiomeSelector;
use hcf\generator\overworld\biome\IBiomeSelector;
use hcf\generator\overworld\biome\OverworldBiomeSelector;
use hcf\generator\overworld\populator\Caves;
use hcf\generator\overworld\populator\ExtendedPopulator;
use hcf\generator\overworld\populator\SchematicTreePopulator;
use hcf\generator\overworld\populator\SimpleRandomRoads;
use hcf\generator\schematic\SchematicRoadsPopulator;
use hcf\generator\schematic\SingleSchematicPopulator;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\item\StringToItemParser;
use pocketmine\math\Axis;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\utils\Random;
use pocketmine\world\biome\BiomeRegistry;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Gaussian;
use pocketmine\world\generator\Generator;
use pocketmine\world\generator\object\OreType;
use pocketmine\world\generator\populator\Ore;
use pocketmine\world\generator\populator\Populator;
use pocketmine\world\generator\populator\Tree;
use pocketmine\world\World;

class HCFOverworldGenerator extends Generator {

	/** @var Populator[] */
	private $populators = [];
	/** @var int */
	private $waterHeight = 62;

	/** @var Populator[] */
	private $generationPopulators = [];
	/** @var array */
	private $options;

	/** @var IBiomeSelector */
	private $biomeSelector;

	/** @var Gaussian */
	private $gaussian;

	/** @var NoiseGroup */
	private $noiseBase;

	/** @var Noise */
	private $textureNoise;

	/** @var NoiseGroup */
	private $mountainsNoise;

	/** @var NoiseGroup */
	private $mountainRidgeNoise;

	/** @var NoiseGroup */
	private $pondNoise;

	/** @var Override */
	private $regionsOverride;

	private $biomeOverride = null;


	/** @var int[][]|Block[][] */
	private $extraBlockToPlace = [];

	private $worldBorder = 2048;
	private $worldBorderCX = 2048 >> 4;

	/**
	 * @param int $seed
	 * @param string $preset
	 * @throws \JsonException
	 */
	public function __construct(int $seed, string $preset) {
		parent::__construct($seed, $preset);
        //$config = Utils::getGeneratorFile();
		$this->options = json_decode($preset, true, 512, JSON_THROW_ON_ERROR);
		if(isset($this->options["worldBorder"])) {
			$this->worldBorder = $this->options["worldBorder"];
			$this->worldBorderCX = $this->options["worldBorder"] >> 4;
		}

		if(isset($this->options["biomeOverride"])) $this->biomeOverride = $this->options["biomeOverride"];

		$this->gaussian = new Gaussian(2);

		$this->random->setSeed($this->seed);

		$perlin = new SimplexNoiseAdapter($this->random);
		$this->textureNoise = $perlin;
		$this->noiseBase = new NoiseGroup();
		//$this->noiseBase->addLayer(new NoiseLayer($perlin, 1/32));
		$this->noiseBase->addOctaves($perlin, 8, 1 / 16 /* this used to be 32 but apparently peeps want "ROUGHER" */, 1, 1, 8, 1 / 4, 1);
		//$this->regionsOverride = new OverworldRegionsOverride();
		//$this->noiseBase->addOverride($this->regionsOverride);
		$this->noiseBase->addFilter(new BoxAverage2D(2));

		$this->mountainsNoise = new NoiseGroup();
		$this->mountainsNoise->addOctaves($perlin, 6, 1 / 512, 1, 1, 6, 1 / 8, 1);
		$this->mountainsNoise->addFilter(new Gaussian2D(new GaussianKernel(2)));

		$this->mountainRidgeNoise = new NoiseGroup();
		$this->mountainRidgeNoise->addOctaves($perlin, 4, 1 / 128, 1, 1, 2, 1 / 4, 1);

		$this->pondNoise = new NoiseGroup();
		$this->pondNoise->addOctaves($perlin, 4, 1 / 32, 1, 1, 4, 1 / 8, 1);

		// todo: Add constraints that make it so that the central 0,0 point is always flat,
		//       and the 4 roads and KOTHs positioning... for HCF

		$this->biomeSelector = new OverworldBiomeSelector($this->random, $this->waterHeight, $this->options);

		$cover = new AmplifiedGroundCover();
		$this->generationPopulators[] = $cover;

		$roadType = $this->options["road"]["type"];
		$roadInfo = $this->options["road"][$roadType];
		if($roadType === "simpleRandom") {
			$roadBlocks = [];
			foreach($roadInfo["blocks"] as $blockInfo) {
				$block = BlockFactory::getInstance()->get($blockInfo["id"], $blockInfo["meta"] ?? 0);
				for($i = 0; $i < ($blockInfo["weight"] ?? 1); $i++) {
					$roadBlocks[] = $block;
				}
			}
			$roads = new SimpleRandomRoads($roadInfo["radius"], $roadInfo["edgeRadius"], $roadBlocks);
		} elseif($roadType === "schematic") {
			$roads = new SchematicRoadsPopulator(Schematic::fromFile($this->options["dataFolder"] . $roadInfo["file"]));
		}
		$this->generationPopulators[] = $roads;

		$caves = new Caves($this->random);
		$this->populators[] = $caves;

		$ores = new Ore();
		$stone = VanillaBlocks::STONE();
		/*$ores->setOreTypes([
			new OreType(VanillaBlocks::COAL_ORE(), $stone, 20, 16, 0, 128),
			new OreType(VanillaBlocks::IRON_ORE(), $stone, 20, 8, 0, 64),
			new OreType(VanillaBlocks::REDSTONE_ORE(), $stone, 8, 7, 0, 16),
			new OreType(VanillaBlocks::LAPIS_LAZULI_ORE(), $stone, 1, 6, 0, 32),
			new OreType(VanillaBlocks::GOLD_ORE(), $stone, 2, 8, 0, 32),
			new OreType(VanillaBlocks::DIAMOND_ORE(), $stone, 1, 7, 0, 16),
			new OreType(VanillaBlocks::DIRT(), $stone, 20, 32, 0, 128),
			new OreType(VanillaBlocks::GRAVEL(), $stone, 10, 16, 0, 128)
		]);*/
		$oreTypes = [];
		foreach($this->options["ores"] as $oreType) {
			$oreTypes[] = new OreType(
				StringToItemParser::getInstance()->parse(strtolower($oreType["id"]))->getBlock(), $stone,
				$oreType["clusterCount"], $oreType["clusterSize"],
				$oreType["minHeight"], $oreType["maxHeight"]
			);
		}
		$ores->setOreTypes($oreTypes);
		$this->populators[] = $ores;

		foreach($this->options["schematics"] as $schematicData) {
			switch($schematicData["type"]) {
				case "random":
					$files = array_values(array_diff(scandir($this->options["dataFolder"] . $schematicData["folder"]), [".", ".."]));
					$schemFile = $this->options["dataFolder"] . $schematicData["folder"] . DIRECTORY_SEPARATOR . $files[$this->random->nextInt() % count($files)];
					break;
				case "file":
					$schemFile = $this->options["dataFolder"] . $schematicData["file"];
					break;
				default:
					throw new \UnexpectedValueException("Unknown schematic type {$schematicData['type']}");
			}
			$this->populators[] = new SingleSchematicPopulator(Schematic::fromFile($schemFile), new Vector3($schematicData["center"]["x"], $schematicData["groundLevel"], $schematicData["center"]["z"]), $schematicData["ignoreYBounds"] ?? false);
		}
	}

	public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void {
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);

		$chunk = $world->getChunk($chunkX, $chunkZ);

		$bedrock = VanillaBlocks::BEDROCK()->getFullId();
		$stillWater = VanillaBlocks::WATER()->setStill(true)->getFullId();
		$stone = VanillaBlocks::STONE()->getFullId();
		$invisibleBedrock = VanillaBlocks::INVISIBLE_BEDROCK()->getFullId();

		$realX = $chunkX * 16;
		$realZ = $chunkZ * 16;


		if(abs($chunkX) >= $this->worldBorderCX || abs($chunkZ) >= $this->worldBorderCX) {

			for($cx = 0; $cx < 16; $cx++) {
				for($cz = 0; $cz < 16; $cz++) {
					for($cy = 0; $cy < World::Y_MAX; $cy++) {
						$chunk->setFullBlock($cx, $cy, $cz, $invisibleBedrock);
					}
				}
			}
			return;
		}

		for($cx = 0; $cx < 16; $cx++) {
			$ix = ($curX = $realX + $cx) + 20000000; // fix negative coords
			if(abs($curX) > $this->worldBorder) continue;
			for($cz = 0; $cz < 16; $cz++) {
				$iz = ($curZ = $realZ + $cz) + 20000000; // fix negative coords
				if(abs($curZ) > $this->worldBorder) continue;

				$normalized = $this->getTerrainHeightFor($ix, $iz);

				$forceBiome = null;
//				if((($curX * $curX) + ($curZ * $curZ)) < 10000) {
//					$forceBiome = BiomeIds::PLAINS;
//				}
				$biome = $this->biomeSelector->pickBiome($ix, $normalized, $iz, $forceBiome);
				$chunk->setBiomeId($cx, $cz, $biome->getId());

				for($cy = 0; $cy < max($this->waterHeight, $normalized); $cy++) {
					if($cy <= 3 && $this->random->nextRange(0, $cy) === 0) {
						$chunk->setFullBlock($cx, $cy, $cz, $bedrock);
						continue;
					}
					if($cy > $normalized && $normalized <= $this->waterHeight) {
						$chunk->setFullBlock($cx, $cy, $cz, $stillWater);
						continue;
					}
					$chunk->setFullBlock($cx, $cy, $cz, $stone);
				}
			}
		}

		if(abs($chunkX) > $this->worldBorderCX || abs($chunkZ) > $this->worldBorderCX) return;
		foreach($this->generationPopulators as $populator) {
			$populator->populate($world, $chunkX, $chunkZ, $this->random);
		}
	}

	private function getTerrainHeightFor(int $ix, int $iz): int {
		// todo: make this an OOP thing?
		$overrideWeight = 0;
		$override = 0;

		$realX = $ix - 20000000;
		$realZ = $iz - 20000000;

		$overrideWeights = [];
		$overrides = [];

		// spawn
		$distFrom0 = sqrt(($realX * $realX) + ($realZ * $realZ));
		if(($dV = $this->circleGrad($distFrom0, 100, 512, 1)) !== null) {
			$overrideWeights[] = $dV;
			$overrides[] = 64 / 127;
		}
		$xDistFrom0 = abs($realX);
		$zDistFrom0 = abs($realZ);

		// x roads
		if(($rxV = $this->circleGrad($xDistFrom0, 16, 128, 1)) !== null) {
			$overrideWeights[] = $rxV;
			$overrides[] = 64 / 127;
		}

		// z roads
		if(($rzV = $this->circleGrad($zDistFrom0, 16, 128, 1)) !== null) {
			$overrideWeights[] = $rzV;
			$overrides[] = 64 / 127;
		}

		// koth quadrants
		foreach([[500, 500], [-500, -500], [500, -500]] as $item) {
			$distFromPoint = sqrt((($realX - $item[0]) ** 2) + (($realZ - $item[1]) ** 2));
			if(($rpV = $this->circleGrad($distFromPoint, 64, 425, 1)) !== null){
				$overrideWeights[] = $rpV;
				$overrides[] = 64 / 127;
			}
		}
		
		//citadel quadrants 
		foreach([[-500, 500]] as $item) {
			$distFromPoint = sqrt((($realX - $item[0]) ** 2) + (($realZ - $item[1]) ** 2));
			if(($rpV = $this->circleGrad($distFromPoint, 64, 625, 1)) !== null){
				$overrideWeights[] = $rpV;
				$overrides[] = 64 / 127;
			}
		}
		foreach($this->options["flatten"] ?? [] as $flatLand) {
			$deltaX = $flatLand["centerX"] - $realX;
			$deltaZ = $flatLand["centerZ"] - $realZ;
			$dist = sqrt(($deltaX * $deltaX) + ($deltaZ * $deltaZ));
			if(($w = $this->circleGrad($dist, $flatLand["flatRadius"], $flatLand["totalRadius"], 1)) !== null) {
				$overrideWeights[] = $w;
				$overrides[] = 64 / 127;
			}
		}

		if(count($overrides) > 0) {
			$override = max($overrides);
			$overrideWeight = max($overrideWeights);
		}

		$noiseValue = $this->noiseBase->noise3D($ix, 0, $iz);

		$noiseValue = $noiseValue * 0.5 + 0.5;
		$noiseValue *= 0.1;

		$ridge = abs($this->mountainRidgeNoise->noise3D($ix, 420, $iz));
		$ridge *= -1;
		$ridge = $ridge * 0.5 + 0.5;
		$ridge *= $ridge;
		$ridge *= 2;

		$mountainExp = 16; // magic number
		$mountainMultiplier = (($this->mountainsNoise->noise3D((int)$ix, (int)69, (int)$iz) * 0.5 + 0.5) ** $mountainExp) * 0.5;
		$mountainMultiplier += (($this->mountainsNoise->noise3D((int)$ix, (int)69, (int)$iz) * 0.5 + 0.5) ** $mountainExp) * 0.5;
		$mountainMultiplier *= 16; // magic number
		$ridge = ($ridge * 0.7) + ($mountainMultiplier * 0.3);
		//$ridge *= 0.2;

		$mountainVsPlainsWeight = $this->mountainsNoise->noise3D(intval($ix / 3), (int)69420, intval($iz / 3)) * 0.5 + 0.5;
		$mountainVsPlainsWeight *= 0.5;

		$mountainVsPlainsWeight = ($mountainVsPlainsWeight * (1 - $overrideWeight)) + ($override * $overrideWeight);

		$noiseValue = ($noiseValue * (1 - $mountainVsPlainsWeight)) + ($ridge * $mountainVsPlainsWeight);

		$noiseValue += 61 / 127;

		/*$pondNoise = $this->pondNoise->noise3D($ix, 2, $iz) * 0.5 + 0.5;
		if($pondNoise > 0.64) {
			$pondNoise -= 0.64;
			$pondNoise /= 0.36;
			$noiseValue = ($noiseValue * (1 - $pondNoise)) + ((55 / 127) * $pondNoise);
		}*/


//		$noiseValue = ($noiseValue * 0.993) + ($this->textureNoise->noise3D($ix * (1 / 16), 69, $iz * (1 / 16)) * 0.007);
//		//$noiseValue += 4 / 127; // attempt to raise terrain by 4 blocks above sea level
//		$noiseValue += 80 / 127; // attempt to raise terrain by 80 blocks
//		if($noiseValue < 0) $noiseValue = (abs($noiseValue) ** 0.25 /*0.0001 = shallower, 2 = deeper*/) * -1; // make the rivers shallower
//		$noiseValue = $noiseValue * 0.5 + 0.5;
//
//		$mtValue = $this->mountainsNoise->noise3D($ix, 512, $iz);
//		//if($mtValue > 0) {
//		$mtValue = $mtValue * 0.5 + 0.5;
//		$mtValue *= $this->mountainsNoise->noise3D($ix / 2, 1024, $iz / 2) * 0.5 + 0.5;
//
//		$ridgeValue = $this->mountainRidgeNoise->getNoise3DValue($ix, 1024, $iz);
//
//		//$biggerRidgeValue = $this->mountainRidgeNoise->getNoise3DValue($ix * 0.5, 2048, $iz * 0.5);
//
//		$mtValue = ($mtValue * 0.8) + (($ridgeValue * $mtValue) * 0.2);
//		//$mtValue = ($mtValue * 0.5) + (($biggerRidgeValue * $mtValue) * 0.5);
//		if($mtValue < 0.4) $mtValue = 0;
//
//		$noiseValue = ($noiseValue * (1 - (0.5 * $mtValue * 2))) + ($mtValue * (0.5 * $mtValue * 2));
//		//}

		/*$ovVal = $this->regionsOverride->getValueAt($ix, 0, $iz);
		$noiseValue = (0.503 * $ovVal) + ($noiseValue * (1 - $ovVal));*/

		$normalized = (($noiseValue * (1 - $overrideWeight)) + ($override * $overrideWeight)) * 127;
		return (int)floor(min(World::Y_MAX - 1, $normalized));
	}

	private function circleGrad(float $dist, int $centerRadius, int $totalRadius, float $amplitude = 1): ?float {
		//return $dist > $totalRadius ? null : ($dist < $centerRadius ? $amplitude : (1 - (($dist - $centerRadius) / ($totalRadius - $centerRadius))) * $amplitude);

		if($dist > $totalRadius) return null;
		if($dist < $centerRadius) return $amplitude;
		if($centerRadius != $totalRadius) {
			return (1 - (($dist - $centerRadius) / ($totalRadius - $centerRadius))) * $amplitude;
		}
		return (1 - ($dist / $centerRadius)) * $amplitude;
	}

	private function checkExtraBlocks(ChunkManager $world, int $chunkX, int $chunkZ): void {
		foreach($this->extraBlockToPlace as $k => $block) {
			if(abs(($block[0] >> 4) - $chunkX) <= 1 && abs(($block[2] >> 4) - $chunkZ) <= 1) {
				$world->setBlockAt($block[0], $block[1], $block[2], $block[3]);
				unset($this->extraBlockToPlace[$k]);
			}
		}
	}

	public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void {
		if(abs($chunkX) > $this->worldBorderCX || abs($chunkZ) > $this->worldBorderCX) return;
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);
		foreach($this->populators as $populator) {
			if($populator instanceof Caves) {
				$acx = abs($chunkX);
				$acz = abs($chunkZ);
				if($acx <= 8 && $acz <= 8) continue;
				if($acx >= 8 && $acz <= (16 + 8)) continue;
				if($acx <= 2 || $acz <= 2) continue;
			}
			$populator->populate($world, $chunkX, $chunkZ, $this->random);
		}

		$chunk = $world->getChunk($chunkX, $chunkZ);
		$biome = BiomeRegistry::getInstance()->getBiome($chunk->getBiomeId(7, 7));
		//$biome->populateChunk($world, $chunkX, $chunkZ, $this->random);

		$this->checkExtraBlocks($world, $chunkX, $chunkZ);
		foreach($biome->getPopulators() as $populator) {
			if($populator instanceof SchematicTreePopulator || $populator instanceof Tree){
				$acx = abs($chunkX);
				$acz = abs($chunkZ);
				if($acx <= 8 && $acz <= 8) continue;
				if($acx >= 8 && $acz <= (16 + 8)) continue;
				if($acx <= 2 || $acz <= 2) continue;
			}
			$populator->populate($world, $chunkX, $chunkZ, $this->random);
			if($populator instanceof ExtendedPopulator) {
				foreach($populator->getExtraBlocks() as $extraBlock) {
					$this->extraBlockToPlace[] = $extraBlock;
				}
			}
		}

		if($this->biomeOverride !== null) {
			for($ix = 0; $ix < 16; $ix++) {
				for($iz = 0; $iz < 16; $iz++) {
					$chunk->setBiomeId($ix, $iz, $this->biomeOverride);
				}
			}
		}
		return;

		// test procedurally generated tree gen

		$origin = (new Vector3(($chunkX << 4) + 7, 128, ($chunkZ << 4) + 7))->add(0.49999, 0, 0.49999);
		$this->growTreeBranch($world, $this->random, $origin, 2);
	}

	private function growTreeBranch(ChunkManager $level, Random $random, Vector3 $origin, float $currentWidth): void {
		$vec = new Vector3(
			$random->nextFloat() * ($random->nextBoolean() ? -1 : 1),
			$random->nextFloat(),
			$random->nextFloat() * ($random->nextBoolean() ? -1 : 1)
		);
		$len = $random->nextFloat() * 4;
		$ovf = $origin->addVector($vec->multiply($len));
		$ov = $ovf->floor();
		$of = $origin->floor();

		if($currentWidth < 0.5) {
			foreach(Facing::ALL as $face) {
				if($random->nextBoolean()) continue;
				$side = $of->getSide($face);
				if($level->getBlockAt($side->x, $side->y, $side->z)->getId() === BlockLegacyIds::AIR) {
					$level->setBlockAt($side->x, $side->y, $side->z, VanillaBlocks::OAK_LEAVES());
				}
			}
		}

		$axi = [
			// intentionally flipped cuz idfk why it's flipped in-game
			Axis::Z => ($ovf->x - $origin->x),
			Axis::Y => ($ovf->y - $origin->y),
			Axis::X => ($ovf->z - $origin->z),
		];

		$log = VanillaBlocks::OAK_LOG();
		$log->setAxis(array_search(max($axi), $axi));

		foreach(VoxelRayTrace::inDirection($of, $vec, $len) as $point) {
			$pf = $point->floor();
			$level->setBlockAt($pf->x, $pf->y, $pf->z, $log);
		}

		$currentWidth -= $random->nextFloat() * 0.5;

		if($currentWidth > 0) {
			$branches = $random->nextBoundedInt(3);
			for($i = 0; $i < $branches; $i++) {
				$this->growTreeBranch($level, $random, $ov, $currentWidth);
			}
		}

		if($currentWidth < 0.5) {
			$b = new Vector3($ov->x, $ov->y, $ov->z);
			$radius = $random->nextRange(2, 4);
			$radiusY = $random->nextRange(1, 2);
			$rad2 = $radius * $radius;
			for($lx = -$radius; $lx <= $radius; $lx++) {
				for($lz = -$radius; $lz <= $radius; $lz++) {
					for($ly = -$radiusY; $ly <= $radiusY; $ly++) {
						$ix = $b->x + $lx;
						$iy = $b->y + $ly;
						$iz = $b->z + $lz;
						$dist2 = (($lx * $lx) + ($ly * $ly) + ($lz * $lz));
						if($dist2 >= $rad2) continue;
						if($dist2 >= ($rad2 - 2) && $random->nextBoolean()) continue;
						if($level->getBlockAt($ix, $iy, $iz)->getId() === BlockLegacyIds::AIR) {
							$level->setBlockAt($ix, $iy, $iz, VanillaBlocks::OAK_LEAVES());
						}
					}
				}
			}
		}
	}
}