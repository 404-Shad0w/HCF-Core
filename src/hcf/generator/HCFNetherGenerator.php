<?php


namespace CortexPE\SynHCF\generator;


use CortexPE\libSchematic\Schematic;
use CortexPE\SynHCF\generator\schematic\SingleSchematicPopulator;
use CortexPE\TeaSpoon\worldGenerator\populator\FlexibleGroundOre;
use CortexPE\TeaSpoon\worldGenerator\populator\FlexibleHangingOre;
use CortexPE\TeaSpoon\worldGenerator\populator\RandomGroundBlock;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\world\biome\BiomeRegistry;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;
use pocketmine\world\generator\noise\Simplex;
use pocketmine\world\generator\object\OreType;
use pocketmine\world\generator\populator\Populator;

class ROADNSHITPOPULATOR implements Populator {

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void {
		$realX = $chunkX << 4;
		$realZ = $chunkZ << 4;

		for($ix = 0; $ix < 16; $ix++) {
			$x = $realX + $ix;
			for($iz = 0; $iz < 16; $iz++) {
				$z = $realZ + $iz;

				$dist = sqrt(($x * $x) + ($z * $z));

				if($dist >= 128 && abs($x) > 8 && abs($z) > 8) continue;

				for($iy = 0; $iy <= 32; $iy++) {
					if($iy > 30 && (abs($x) <= 8 || abs($z) <= 8)){
						$world->setBlockAt($x, $iy, $z, VanillaBlocks::OBSIDIAN());
						continue;
					}
					$world->setBlockAt($x, $iy, $z, VanillaBlocks::NETHERRACK());
				}
			}
		}
	}
}

class HCFNetherGenerator extends Generator {

	/** @var array */
	private $options;

	public function __construct(int $seed, string $preset) {
		parent::__construct($seed, $preset);
		$this->options = json_decode($preset, true, 512, JSON_THROW_ON_ERROR);

		$this->random->setSeed($seed);
		$this->noiseBase = new Simplex($this->random, 4, 1 / 4, 1 / 64);
		$this->random->setSeed($seed);

		// ground fire
		$this->populators[] = $gFire = new RandomGroundBlock(
			VanillaBlocks::FIRE(), [BlockLegacyIds::NETHERRACK], [BlockLegacyIds::AIR], 64
		);
		$gFire->setBaseAmount(1);
		$gFire->setRandomAmount(1);
		$this->populators[] = $mushroom = new RandomGroundBlock(
			VanillaBlocks::BROWN_MUSHROOM(), [BlockLegacyIds::NETHERRACK], [BlockLegacyIds::AIR], 64
		);
		$mushroom->setBaseAmount(2);
		$mushroom->setRandomAmount(1);

		// ores
		$this->populators[] = $nOres = new FlexibleGroundOre([BlockLegacyIds::NETHERRACK]);
		$nOres->setOreTypes([
			new OreType(VanillaBlocks::NETHER_QUARTZ_ORE(), VanillaBlocks::NETHERRACK(), 20, 16, 0, 128),
			new OreType(VanillaBlocks::SOUL_SAND(), VanillaBlocks::NETHERRACK(), 5, 64, 0, 128),
			new OreType(VanillaBlocks::GRAVEL(), VanillaBlocks::NETHERRACK(), 5, 64, 0, 128),
			new OreType(VanillaBlocks::LAVA(), VanillaBlocks::NETHERRACK(), 1, 16, 0, 32),
		]);
		for($i = 0; $i <= 3; $i++) {
			$this->populators[] = $pop = new RandomGroundBlock(
				VanillaBlocks::NETHER_WART()->setAge($i), [BlockLegacyIds::SOUL_SAND], [BlockLegacyIds::AIR], 64
			);
			$pop->setBaseAmount(3);
			$pop->setRandomAmount(3);
		}
		$this->populators[] = $nOres = new FlexibleHangingOre([BlockLegacyIds::AIR]);
		$nOres->setOreTypes([
			new OreType(VanillaBlocks::GLOWSTONE(), VanillaBlocks::AIR(), 1, 20, 64, 126),
		]);
		// todo: down-pouring lava

		$this->generationPopulators[] = new ROADNSHITPOPULATOR();

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

	private function circleGrad(float $dist, int $centerRadius, int $totalRadius, float $amplitude = 1): ?float {
		//return $dist > $totalRadius ? null : ($dist < $centerRadius ? $amplitude : (1 - (($dist - $centerRadius) / ($totalRadius - $centerRadius))) * $amplitude);

		if($dist > $totalRadius) return null;
		if($dist < $centerRadius) return $amplitude;
		if($centerRadius != $totalRadius) {
			return (1 - (($dist - $centerRadius) / ($totalRadius - $centerRadius))) * $amplitude;
		}
		return (1 - ($dist / $centerRadius)) * $amplitude;
	}

	protected function getDensity(int $x, int $y, int $z): float {

		$default = 0.7;
		$weights = [0];

		$clearAmp = 1.25;

		$dist = sqrt(($x * $x) + ($z * $z));
		if(($spawnW = $this->circleGrad($dist, 128, 128, $clearAmp)) !== null) {
			$weights[] = $spawnW;
		}

		if($y < 64) $clearAmp = 1.5;
		$xDist = abs($x);
		$zDist = abs($z);

		if(($xRoad = $this->circleGrad($xDist, 4, 8, $clearAmp)) !== null) {
			$weights[] = $xRoad;
		}

		if(($zRoad = $this->circleGrad($zDist, 4, 8, $clearAmp)) !== null) {
			$weights[] = $zRoad;
		}

		foreach($this->options["flatten"] ?? [] as $flatLand) {
			$deltaX = $flatLand["centerX"] - $x;
			$deltaZ = $flatLand["centerZ"] - $z;
			$dist = sqrt(($deltaX * $deltaX) + ($deltaZ * $deltaZ));
			if(($w = $this->circleGrad($dist, $flatLand["flatRadius"], $flatLand["totalRadius"], $clearAmp)) !== null) {
				$weights[] = $w;
			}
		}

		return (1 - max($weights)) * $default;
	}
	/** @var Populator[] */
	protected $populators = [];
	/** @var int */
	protected $waterHeight = 32;
	/** @var int */
	protected $emptyHeight = 64;
	/** @var int */
	protected $emptyAmplitude = 1;
	/** @var float */
	protected $density = 0.7;
	/** @var Populator[] */
	protected $generationPopulators = [];
	/** @var Simplex */
	protected $noiseBase;

	public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void {
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);

		$noise = $this->noiseBase->getFastNoise3D(16, 128, 16, 4, 8, 4, $chunkX * 16, 0, $chunkZ * 16);

		$chunk = $world->getChunk($chunkX, $chunkZ);

		$bedrock = VanillaBlocks::BEDROCK()->getFullId();
		$netherrack = VanillaBlocks::NETHERRACK()->getFullId();
		$lava = VanillaBlocks::LAVA()->setStill(true)->getFullId();

		for($x = 0; $x < 16; ++$x) {
			for($z = 0; $z < 16; ++$z) {

				$biome = BiomeRegistry::getInstance()->getBiome(BiomeIds::HELL);
				$chunk->setBiomeId($x, $z, $biome->getId());

				for($y = 0; $y < 128; ++$y) {
					if($y === 0 or $y === 127) {
						$chunk->setFullBlock($x, $y, $z, $bedrock);
						continue;
					}
					$noiseValue = (abs($this->emptyHeight - $y) / $this->emptyHeight) * $this->emptyAmplitude - $noise[$x][$z][$y];
					$noiseValue -= 1 - $this->getDensity(($chunkX << 4) + $x, $y, ($chunkZ << 4) + $z);

					if($noiseValue > 0) {
						$chunk->setFullBlock($x, $y, $z, $netherrack);
					} elseif($y <= $this->waterHeight) {
						$chunk->setFullBlock($x, $y, $z, $lava);
					}
				}
			}
		}

		foreach($this->generationPopulators as $populator) {
			$populator->populate($world, $chunkX, $chunkZ, $this->random);
		}
	}

	public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void {
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);
		foreach($this->populators as $populator) {
			$populator->populate($world, $chunkX, $chunkZ, $this->random);
		}

		$chunk = $world->getChunk($chunkX, $chunkZ);
		$biome = BiomeRegistry::getInstance()->getBiome($chunk->getBiomeId(7, 7));
		$biome->populateChunk($world, $chunkX, $chunkZ, $this->random);
	}
}