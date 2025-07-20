<?php


namespace hcf\generator\overworld\biome;


use CortexPE\Rave\fbm\NoiseGroup;
use CortexPE\Rave\filter\blur\Gaussian2D;
use CortexPE\Rave\filter\blur\kernel\TwoPassGaussianKernel;
use CortexPE\Rave\Perlin;
use CortexPE\Rave\SimplexNoiseAdapter;
use hcf\generator\overworld\populator\Cacti;
use hcf\generator\overworld\populator\DeadBush;
use hcf\generator\overworld\populator\DesertWell;
use hcf\generator\overworld\populator\DoublePlant;
use hcf\generator\overworld\populator\FlowerPatches;
use hcf\generator\overworld\populator\misc\BeachChairs;
use hcf\generator\overworld\populator\SchematicTreePopulator;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\Random;
use pocketmine\world\biome\Biome;
use pocketmine\world\biome\BiomeRegistry;
use pocketmine\world\biome\UnknownBiome;
use pocketmine\world\generator\populator\Tree;

class OverworldBiomeSelector implements IBiomeSelector {
	/** @var Perlin */
	private $moisture;
	/** @var int */
	private $waterHeight;
	/** @var BiomeRegistry */
	private $registry;

	/**
	 * @var Biome[]|\SplFixedArray
	 * @phpstan-var \SplFixedArray<Biome>
	 */
	private $map = null;

	private const SPRUCE_BIOME = 160; // M_SPRUCE_TAIGA

	private $generatorOptions;

	public function __construct(Random $random, int $waterHeight, array $generatorOptions) {
		$this->waterHeight = $waterHeight;
		$this->generatorOptions = $generatorOptions;
		//$this->moisture = new Simplex($random, 2, 1 / 2, 1 / 256);
		$random->setSeed($random->nextSignedInt() ^ $random->nextSignedInt() ^ $random->nextSignedInt());
		$perlin = new SimplexNoiseAdapter($random);
		$this->moisture = new NoiseGroup();
		$this->moisture->addOctaves($perlin, 4, 1 / 128, 1, 1, 3, 1 / 4, 1);
		$this->moisture->addFilter(new Gaussian2D(new TwoPassGaussianKernel(2)));
		$this->registry = BiomeRegistry::getInstance();

		$this->registry->register(self::SPRUCE_BIOME, new SpruceBiome());

		$plainsBiome = $this->registry->getBiome(BiomeIds::PLAINS);

		$fp = new FlowerPatches();
		$fp->addFlowerType(VanillaBlocks::DANDELION());
		$fp->addFlowerType(VanillaBlocks::ALLIUM());
		$fp->addFlowerType(VanillaBlocks::AZURE_BLUET());
		$fp->addFlowerType(VanillaBlocks::BLUE_ORCHID());
		$fp->addFlowerType(VanillaBlocks::CORNFLOWER());
		$fp->addFlowerType(VanillaBlocks::LILY_OF_THE_VALLEY());
		$fp->addFlowerType(VanillaBlocks::ORANGE_TULIP());
		$fp->addFlowerType(VanillaBlocks::OXEYE_DAISY());
		$fp->addFlowerType(VanillaBlocks::PINK_TULIP());
		$fp->addFlowerType(VanillaBlocks::POPPY());
		$fp->addFlowerType(VanillaBlocks::RED_TULIP());
		$fp->addFlowerType(VanillaBlocks::WHITE_TULIP());
		$plainsBiome->addPopulator($fp);

		$sunflower = new DoublePlant(VanillaBlocks::SUNFLOWER());
		$sunflower->setBaseAmount(1);
		$plainsBiome->addPopulator($sunflower);

		$dtg = new DoublePlant(VanillaBlocks::DOUBLE_TALLGRASS());
		$dtg->setBaseAmount(6);
		$plainsBiome->addPopulator($dtg);

		$desertBiome = $this->registry->getBiome(BiomeIds::DESERT);

		$bc = new BeachChairs();
		$desertBiome->addPopulator($bc);


		$cp = new Cacti();
		$cp->setBaseAmount(0);
		$cp->setRandomAmount(2);
		$desertBiome->addPopulator($cp);

		$dp = new DeadBush();
		$dp->setBaseAmount(2);
		$dp->setRandomAmount(1);
		$desertBiome->addPopulator($dp);

		$dw = new DesertWell();
		$desertBiome->addPopulator($dw);

		foreach(($generatorOptions["biomes"] ?? []) as $biomeID => $data) {
			$biome = $this->registry->getBiome($biomeID);
			if($biome instanceof UnknownBiome) continue;
			if(count($data["trees"] ?? []) > 0) {
				$new = [];
				$trees = $generatorOptions["biomes"][$biome->getId()]["trees"];
				$keys = array_keys($trees);
				$isFirstTimeDoneYet = false;
				foreach($biome->getPopulators() as $pop) {
					if($pop instanceof Tree || !$isFirstTimeDoneYet) {
						$isFirstTimeDoneYet = true;
						$i = $random->nextInt() % count($trees);
						$new[] = ($_pop = new SchematicTreePopulator(
							$generatorOptions["dataFolder"] . (is_numeric($keys[$i]) ? $trees[$i] : $keys[$i])
						));
						if(isset($trees[$i]["chance"])) $_pop->setChance($trees[$i]["chance"]);
						if(isset($trees[$i]["minDistance"])) $_pop->setMinDistance($trees[$i]["minDistance"]);
						if(isset($trees[$i]["jitter"])) $_pop->setJitter($trees[$i]["jitter"]);
						if(isset($trees[$i]["yOffset"])) $_pop->setYOffset($trees[$i]["yOffset"]);
						continue;
					}
					$new[] = clone $pop;
				}
				$biome->clearPopulators();
				foreach($new as $pop) {
					$biome->addPopulator($pop);
				}
			}
		}
	}

	public function pickBiome(float $x, float $y, float $z, ?int $forceBiome = null): Biome {
		return $this->registry->getBiome($forceBiome ?? $this->lookup(
				1 - ($y / 255),
				$this->getMoisture($x, $z),
				$this->getVegetation($x, $z)
			));
	}

	protected function lookup(float $temperature, float $rainfall, float $vegetation): int {
		// maps y => temp: https://www.desmos.com/calculator/yun95ozvke

		// todo: sparingly fix desert biomes

		if($temperature >= 0.818) { // y<=46.5
			return BiomeIds::OCEAN;
		}
		if($temperature >= 0.761) { // y<=62
			if($rainfall >= 0.4) {
				return BiomeIds::RIVER;
			} else {
				return BiomeIds::DESERT; // beach
			}
		}
		if($temperature >= 0.71) { // y<=75
			if($rainfall < 0.5 && $vegetation < 0.2) {
				return BiomeIds::DESERT;
			}
			return BiomeIds::PLAINS;
		}
		if($temperature < 0.502) { // y>=128
			if($vegetation >= 0.5) {
				return BiomeIds::TAIGA;
			}
			return BiomeIds::ICE_PLAINS;
		}
		if($temperature < 0.612) { // y>=100
			return BiomeIds::TAIGA;
		}
		if($temperature < 0.608) { // y.=100
			if($vegetation >= 0.5) {
				return self::SPRUCE_BIOME;
			}
			return BiomeIds::EXTREME_HILLS;
		}
		if($temperature < 0.667) { // y>=85
			if($vegetation > 0.4) {
				return self::SPRUCE_BIOME;
			}
			return BiomeIds::EXTREME_HILLS_EDGE;
		}
		if($temperature < 0.725 && $vegetation > 0.5) { // y>=70
			if($rainfall > 0.5) return BiomeIds::FOREST;
			if($rainfall > 0.3) return BiomeIds::BIRCH_FOREST;
			return BiomeIds::PLAINS;
		}
		return BiomeIds::PLAINS;

		/*if($rainfall < 0.25) {
			if($temperature < 0.7) {
				return BiomeIds::OCEAN;
			} elseif($temperature < 0.85) {
				return BiomeIds::RIVER;
			} else {
				return BiomeIds::SWAMP;
			}
		} elseif($rainfall < 0.60) {
			if($temperature < 0.25) {
				return BiomeIds::ICE_PLAINS;
			} elseif($temperature < 0.75) {
				return BiomeIds::PLAINS;
			} else {
				return BiomeIds::DESERT;
			}
		} elseif($rainfall < 0.80) {
			if($temperature < 0.25) {
				return BiomeIds::TAIGA;
			} elseif($temperature < 0.75) {
				return BiomeIds::FOREST;
			} else {
				return BiomeIds::BIRCH_FOREST;
			}
		} else {
			if($temperature < 0.20) {
				return BiomeIds::MOUNTAINS;
			} elseif($temperature < 0.40) {
				return BiomeIds::SMALL_MOUNTAINS;
			} else {
				return BiomeIds::RIVER;
			}
		}*/
	}

	public function getMoisture(float $x, float $z): float {
		// TODO: THIS IS FUCKING BROKEN,
		//       1. Noise->noise2D($x, $z, true) returns a value from [-0.1, 0.6] (which is why I had to cap it to 0)
		//       2. This noise is not so equally distributed, I don't fucking know why
		//       3. This is likely why PM's biome selector is so broken
		//       return max(0, $this->moisture->noise2D($x, $z, true));
		return $this->moisture->noise3D($x, 420, $z) * 0.5 + 0.5;
	}

	public function getVegetation(float $x, float $z): float {
		return $this->moisture->noise3D($x, 69, $z) * 0.5 + 0.5;
	}
}