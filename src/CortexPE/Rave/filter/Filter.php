<?php


namespace CortexPE\Rave\filter;


use CortexPE\Rave\fbm\NoiseGroup;

abstract class Filter {
	/**
	 * @param NoiseGroup $noiseGroup
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 * @param float $value A value between [0, 1]
	 * @return float A value between [0, 1]
	 */
	abstract public function applyTo(NoiseGroup $noiseGroup, float $x, float $y, float $z, float $value) : float;
}