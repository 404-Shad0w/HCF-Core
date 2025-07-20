<?php

namespace hcf\generator\overworld\biome;

use CortexPE\SynHCF\generator\overworld\populator\FlowerPatches;
use pocketmine\block\utils\TreeType;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\generator\populator\TallGrass;
use pocketmine\world\generator\populator\Tree;

class BaldSpruceBiome extends SpruceBiome {

	public function __construct(){
		$tallGrass = new TallGrass();
		$tallGrass->setBaseAmount(1);

		$this->addPopulator($tallGrass);

		$this->setElevation(63, 81);

		$fp = new FlowerPatches();
		$fp->addFlowerType(VanillaBlocks::POPPY());
		$fp->addFlowerType(VanillaBlocks::RED_TULIP());
		$this->addPopulator($fp);


		$this->setGroundCover([
			VanillaBlocks::GRASS(),
			VanillaBlocks::DIRT(),
			VanillaBlocks::DIRT(),
			VanillaBlocks::DIRT(),
			VanillaBlocks::DIRT()
		]);

		$this->temperature = 0.05;
		$this->rainfall = 0.8;
	}

	public function getName(): string {
		return "Bald Spruce";
	}
}