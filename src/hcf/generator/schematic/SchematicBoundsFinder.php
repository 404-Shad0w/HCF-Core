<?php


namespace hcf\generator\schematic;


use CortexPE\libSchematic\Schematic;

class SchematicBoundsFinder {
	/** @var self[] */
	private static $cache = [];
	/** @var int */
	private $groundOffset = 0;
	/** @var int */
	private $consistentXEdge = 0;

	private function __construct(Schematic $schematic) {
		$l = $schematic->getLength();
		$h = $schematic->getHeight();
		$w = $schematic->getWidth();
		$area = $l * $w;
		$areaHalf = $area / 2;

		$foundXEdge = false;
		$foundLargeAreaFromBottom = false;
		$layerArea = [];
		for($y = 0; $y < $h; $y++) {
			$layerArea[$y] = 0;
			$isXEdgeConsistent = true;
			for($x = 0; $x < $w; $x++) {
				for($z = 0; $z < $l; $z++) {
					if($schematic->getBlockAt($x, $y, $z)->isTransparent()) continue;
					$layerArea[$y]++;
					if($layerArea[$y] > $areaHalf && !$foundLargeAreaFromBottom) {
						$this->groundOffset = $y;
						$foundLargeAreaFromBottom = true;
					}
				}
				if(!$foundXEdge && $schematic->getBlockAt($x, $y, 0)->isTransparent() && $isXEdgeConsistent) {
					$isXEdgeConsistent = false;
				}
			}
			if($isXEdgeConsistent && !$foundXEdge) {
				$this->consistentXEdge = $y;
			}
		}
	}

	public static function from(Schematic $schematic): self {
		if(isset(self::$cache[$k = $schematic->getFilePath()])) return self::$cache[$k];
		return self::$cache[$k] = new self($schematic);
	}

	/**
	 * @return int
	 */
	public function getGroundOffset(): int {
		return $this->groundOffset;
	}

	/**
	 * @return int
	 */
	public function getConsistentXEdgeHeight(): int {
		return $this->consistentXEdge;
	}
}