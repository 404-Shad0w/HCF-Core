<?php


namespace hcf\generator\overworld\biome;


use CortexPE\Rave\fbm\NoiseGroup;
use CortexPE\Rave\filter\blur\Gaussian2D;
use CortexPE\Rave\filter\blur\kernel\TwoPassGaussianKernel;
use CortexPE\Rave\Perlin;
use CortexPE\Rave\SimplexNoiseAdapter;
use CortexPE\SynHCF\generator\overworld\populator\Cacti;
use CortexPE\SynHCF\generator\overworld\populator\DeadBush;
use CortexPE\SynHCF\generator\overworld\populator\DesertWell;
use CortexPE\SynHCF\generator\overworld\populator\misc\BeachChairs;
use CortexPE\SynHCF\generator\overworld\populator\SchematicTreePopulator;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\Random;
use pocketmine\world\biome\Biome;
use pocketmine\world\biome\BiomeRegistry;
use pocketmine\world\biome\UnknownBiome;
use pocketmine\world\generator\populator\Tree;

class ChristmasThemedBiomeSelector implements IBiomeSelector {
	/** @var Perlin */
	private $moisture;
	/** @var BiomeRegistry */
	private $registry;

	/** @var BaldSpruceBiome */
	private $baldSpruce;

	private const SPRUCE_BIOME = 160; // M_SPRUCE_TAIGA

	public function __construct(Random $random, int $waterHeight, array $generatorOptions) {
		$this->baldSpruce = new BaldSpruceBiome();
		$this->baldSpruce->setId(self::SPRUCE_BIOME);

		//$this->moisture = new Simplex($random, 2, 1 / 2, 1 / 256);
		$random->setSeed($random->nextSignedInt() ^ $random->nextSignedInt() ^ $random->nextSignedInt());

		$perlin = new SimplexNoiseAdapter($random);
		$this->moisture = new NoiseGroup();
		$this->moisture->addOctaves($perlin, 4, 1 / 128, 1, 1, 3, 1 / 4, 1);
		$this->moisture->addFilter(new Gaussian2D(new TwoPassGaussianKernel(2)));

		$this->registry = BiomeRegistry::getInstance();
		$this->registry->register(self::SPRUCE_BIOME, new SpruceBiome());

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
		if(($x - 20000000) >= 750 && ($z - 20000000) >= 750) {
			return $this->registry->getBiome(BiomeIds::DESERT);
		}
		return $this->registry->getBiome($forceBiome ?? $this->lookup(
				1 - ($y / 255),
				$this->getVegetation($x, $z)
			));
	}

	protected function lookup(float $temperature, float $vegetation): int {
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

		return BiomeIds::ICE_PLAINS;
	}

	public function getVegetation(float $x, float $z): float {
		return $this->moisture->noise3D($x, 69, $z) * 0.5 + 0.5;
	}
}