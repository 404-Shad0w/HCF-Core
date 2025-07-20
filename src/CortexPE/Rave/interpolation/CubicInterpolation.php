<?php


namespace CortexPE\Rave\interpolation;

// Smoothstep interpolation
class CubicInterpolation extends Interpolator {
	public function interpolate(float $step, float $start, float $end): float {
		return ($end - $start) * (3.0 - $step * 2.0) * $step * $step + $start;
	}
}