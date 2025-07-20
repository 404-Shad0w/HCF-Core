<?php


namespace hcf\generator\overworld\populator;


use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\populator\Populator;

class DeadBush implements Populator {
	/** @var int */
	private $randomAmount = 1;
	/** @var int */
	private $baseAmount = 0;

	/**
	 * @param int $amount
	 *
	 * @return void
	 */
	public function setRandomAmount(int $amount) {
		$this->randomAmount = $amount;
	}

	/**
	 * @param int $amount
	 *
	 * @return void
	 */
	public function setBaseAmount(int $amount) {
		$this->baseAmount = $amount;
	}

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void {
		$amount = $random->nextRange(0, $this->randomAmount) + $this->baseAmount;
		for($i = 0; $i < $amount; ++$i) {
			$x = $random->nextRange($chunkX * 16, $chunkX * 16 + 15);
			$z = $random->nextRange($chunkZ * 16, $chunkZ * 16 + 15);
			$y = $this->getHighestWorkableBlock($world, $x, $z);

			if($y !== -1 and $this->canDeadBushStay($world, $x, $y, $z)) {
				$world->setBlockAt($x, $y, $z, VanillaBlocks::DEAD_BUSH());
			}
		}
	}

	private function getHighestWorkableBlock(ChunkManager $world, int $x, int $z): int {
		for($y = 127; $y >= 0; --$y) {
			$b = $world->getBlockAt($x, $y, $z);
			if($b->getTypeId() !== BlockTypeIds::AIR) {
				return $y + 1;
			}
		}

		return -1;
	}

	private function canDeadBushStay(ChunkManager $world, int $x, int $y, int $z): bool {
		return $world->getBlockAt($x, $y - 1, $z)->getTypeId() === BlockTypeIds::SAND;
	}
}