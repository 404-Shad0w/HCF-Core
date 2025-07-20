<?php


namespace hcf\generator\overworld\populator;


use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\populator\Populator;

class SimpleRandomRoads implements Populator {
	/** @var int */
	private $radius;
	/** @var int */
	private $chunkRadius;
	/** @var int */
	private $edgeRadius;
	/** @var int[] */
	private $fullIds = [];

	/**
	 * Roads constructor.
	 * @param int $radius
	 * @param int $edgeRadius
	 * @param Block[] $blocks
	 */
	public function __construct(int $radius, int $edgeRadius, array $blocks) {
		$this->radius = $radius;
		$this->chunkRadius = max(1, ($radius + $edgeRadius) >> 4);
		$this->edgeRadius = $edgeRadius;
		foreach($blocks as $block) {
			$this->fullIds[] = $block->getFullId();
		}
	}

	public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void {
		if(abs($chunkX) > $this->chunkRadius && abs($chunkZ) > $this->chunkRadius) return;
		$realX = $chunkX << 4;
		$realZ = $chunkZ << 4;
		$chunk = $world->getChunk($chunkX, $chunkZ);
		$factory = BlockFactory::getInstance();
		$totalRadius = $this->radius + $this->edgeRadius;
		for($cx = 0; $cx < 16; $cx++) {
			for($cz = 0; $cz < 16; $cz++) {
				if((abs($realX + $cx) > $totalRadius) && (abs($realZ + $cz) > $totalRadius)) continue;
				for($y = 127; $y > 0; $y--) {
					if(!$factory->fromFullBlock($chunk->getFullBlock($cx, $y, $cz))->isTransparent()) break;
				}
				if($y <= 0) continue;
				$block = $this->fullIds[$random->nextInt() % count($this->fullIds)];
				if((($distX = abs($realX + $cx)) > $this->radius) && (($distZ = abs($realZ + $cz)) > $this->radius)) {
					if($random->nextRange(0, $this->edgeRadius - ($totalRadius - min($distX, $distZ))) === 0) $chunk->setFullBlock($cx, $y, $cz, $block);
				} else {
					$chunk->setFullBlock($cx, $y, $cz, $block);
				}
			}
		}
	}
}