<?php


namespace CortexPE\Rave\constraint;


abstract class Constraint {
	public function getOpacity(float $x, float $y, float $z): float {
		return 1;
	}
}