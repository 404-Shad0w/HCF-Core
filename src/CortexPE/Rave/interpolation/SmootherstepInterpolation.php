<?php


namespace CortexPE\Rave\interpolation;


class SmootherstepInterpolation extends Interpolator {
	public function interpolate(float $step, float $start, float $end): float {
		return ($end - $start) * (($step * ($step * 6.0 - 15.0) + 10.0) * $step * $step * $step) + $start;
	}
}