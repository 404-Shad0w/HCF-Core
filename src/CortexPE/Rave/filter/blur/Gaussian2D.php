<?php

declare(strict_types=1);

namespace CortexPE\Rave\filter\blur;


use CortexPE\Rave\fbm\NoiseGroup;
use CortexPE\Rave\filter\blur\kernel\GaussianKernel;
use CortexPE\Rave\filter\blur\kernel\Kernel;

class Gaussian2D extends Blur {
	/** @var GaussianKernel */
	private $kernel;
	/** @var float[][] */
	private $cache = [];

	public function __construct(Kernel $kernel) {
		$this->kernel = $kernel;
		parent::__construct($kernel->getRadius());
	}

	public function applyTo(NoiseGroup $noiseGroup, float $x, float $y, float $z, float $value): float {
		$sum = 0;
		for($ix = -$this->radius; $ix <= $this->radius; $ix++) {
			for($iz = -$this->radius; $iz <= $this->radius; $iz++) {
				$cx = $x + $ix;
				$cz = $z + $iz;
				if(!isset($this->cache[(int)$cx][(int)$cz])){
					$this->cache[(int)$cx][(int)$cz] = $noiseGroup->getUnfilteredNoise((int)$cx, (int)$y, (int)$cz) * 0.5 + 0.5;
				}
				$sum += $this->cache[$cx][$cz] * $this->kernel->valueAt($ix + $this->radius, $iz + $this->radius);
			}
		}
		return $sum; // this should be [0,1] already as our kernel is normalized
	}
}