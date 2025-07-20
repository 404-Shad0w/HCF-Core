<?php


namespace CortexPE\Rave\interpolation;


abstract class Interpolator {
	/**
	 * @param float $start Start value
	 * @param float $end End value
	 * @param float $step Value between 0-1, the time step
	 * @return float
	 */
	abstract public function interpolate(float $step, float $start, float $end):float;
}