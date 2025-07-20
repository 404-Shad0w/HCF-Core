<?php


namespace hcf\generator\overworld;


use CortexPE\Rave\filter\blur\kernel\TwoPassGaussianKernel;
use CortexPE\Rave\override\Override;

class Vec2 {
	public $x;
	public $z;

	public function __construct(int $x, int $z) {
		$this->x = $x;
		$this->z = $z;
	}
}

class Region {
	public $min;
	public $max;

	public function inside(int $x, int $z): bool {
		return ($x >= $this->min->x && $x <= $this->max->x) && ($z >= $this->min->z && $z <= $this->max->z);
	}
}

class OverworldRegionsOverride extends Override {
	private static $gaussianKernel;
	private $warzoneSize = 128;
	/** @var Region[] */
	private $regions = [];
	private $blurRadius = 8;

	public function __construct() {
		if(!isset(self::$gaussianKernel)) {
			var_dump("INIT KERNEL");
			self::$gaussianKernel = new TwoPassGaussianKernel($this->blurRadius);
		}
		$reg = new Region();
		$reg->min = new Vec2(-128, -128);
		$reg->max = new Vec2(128, 128);
		$this->regions[] = $reg;
	}

	public function getValueAt(float $x, float $y, float $z): ?float {
		// REEEE
		$x -= 20000000;
		$z -= 20000000;

		$sum = 0;
		for($ix = -$this->blurRadius; $ix <= $this->blurRadius; $ix++) {
			for($iz = -$this->blurRadius; $iz <= $this->blurRadius; $iz++) {
				$sum += $this->isInRegion($x + $ix, $z + $iz) * self::$gaussianKernel->valueAt($ix + $this->blurRadius, $iz + $this->blurRadius);
			}
		}
		$arr[$x][$z] = $sum;

		return $sum;
	}

	private function isInRegion(int $x, int $z): int {
		foreach($this->regions as $region) {
			if($region->inside($x, $z)) return 1;
		}
		return 0;
	}
}