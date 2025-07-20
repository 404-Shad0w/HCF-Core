<?php


namespace CortexPE\Rave\interpolation;


class LinearInterpolation extends Interpolator {
	public function interpolate(float $step, float $start, float $end): float {
		return ($end - $start) * $step + $start;
	}
}