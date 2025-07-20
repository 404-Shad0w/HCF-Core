<?php


namespace CortexPE\Rave\override;


abstract class Override {
	/**
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 * @return float|null Value between [-1, 1] or NULL
	 */
	abstract public function getValueAt(float $x, float $y, float $z): ?float;
}