<?php


namespace CortexPE\Rave;


use CortexPE\Rave\constraint\Constraint;

abstract class Noise {
	/** @var Constraint[] */
	private $constraints = [];

	/**
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 * @return float [-1, 1] value
	 */
	abstract public function noise3D(float $x, float $y, float $z): float;

	public function addConstraint(Constraint $constraint):void {
		$this->constraints[] = $constraint;
	}

	/**
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 * @param float $val a value between [-1, 1]
	 * @return float a value between [-1, 1]
	 */
	public function constrainNoiseValue(float $x, float $y, float $z, float $val):float {
		$val = $val * 0.5 + 0.5;
		foreach($this->constraints as $constraint){
			$val *= $constraint->getOpacity($x, $y, $z);
		}
		return $val * 2 - 1;
	}
}