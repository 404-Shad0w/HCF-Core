<?php


namespace hcf\generator\overworld\populator;

use pocketmine\block\DoublePlant as BlockDoublePlant;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\populator\Populator;

class DoublePlant implements Populator {
	/** @var int */
	private $randomAmount = 1;
	/** @var int */
	private $baseAmount = 0;
	/** @var BlockDoublePlant */
	private $plant;

	public function __construct(BlockDoublePlant $plant) {
		$this->plant = $plant;
	}

	/**
	 * @param int $amount
	 *
	 * @return void
	 */
	public function setRandomAmount(int $amount){
		$this->randomAmount = $amount;
	}

	/**
	 * @param int $amount
	 *
	 * @return void
	 */
	public function setBaseAmount(int $amount){
		$this->baseAmount = $amount;
	}

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random):void{
		$amount = $random->nextRange(0, $this->randomAmount) + $this->baseAmount;
		for($i = 0; $i < $amount; ++$i){
			$x = $random->nextRange($chunkX * 16, $chunkX * 16 + 15);
			$z = $random->nextRange($chunkZ * 16, $chunkZ * 16 + 15);
			$y = $this->getHighestWorkableBlock($world, $x, $z);

			if($y !== -1 and $this->canTallGrassStay($world, $x, $y, $z)){
				$world->setBlockAt($x, $y, $z, $this->plant);
				$world->setBlockAt($x, $y + 1, $z, (clone $this->plant)->setTop(true));
			}
		}
	}

	private function canTallGrassStay(ChunkManager $level, int $x, int $y, int $z) : bool{
		$b =$level->getBlockAt($x, $y, $z)->getId();
		return ($b === BlockTypeIds::AIR or $b === BlockTypeIds::SNOW_LAYER) and $level->getBlockAt($x, $y - 1, $z)->getId() === BlockTypeIds::GRASS;
	}

	private function getHighestWorkableBlock(ChunkManager $level, int $x, int $z) : int{
		for($y = 127; $y >= 0; --$y){
			$b = $level->getBlockAt($x, $y, $z)->getId();
			if($b !== BlockTypeIds::AIR and $b !== BlockTypeIds::LEAVES and $b !== BlockLegacyIds::LEAVES2 and $b !== BlockTypeIds::SNOW_LAYER){
				return $y + 1;
			}
		}

		return -1;
	}
}