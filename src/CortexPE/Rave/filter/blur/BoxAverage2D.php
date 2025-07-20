<?php


namespace CortexPE\Rave\filter\blur;


use CortexPE\Rave\fbm\NoiseGroup;

class BoxAverage2D extends Blur {
	public function applyTo(NoiseGroup $noiseGroup, float $x, float $y, float $z, float $value): float {
		$sum = $value;
		$count = ($this->radius * 2 + 1) ** 2; // pre-calculate it instead of looping over
		for($ix = -$this->radius; $ix <= $this->radius; $ix++) {
			for($iz = -$this->radius; $iz <= $this->radius; $iz++) {
				if($ix == 0 && $iz == 0)continue;
				$sum += $noiseGroup->getUnfilteredNoise($x + $ix, $y, $z + $iz) * 0.5 + 0.5;
			}
		}
		return $sum / $count; // [0, 1]
	}
}