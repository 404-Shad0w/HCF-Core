<?php


namespace CortexPE\Rave\constraint;


class RadialGradient2D extends Constraint {
	/** @var float */
	private $centerX;
	/** @var float */
	private $centerZ;
	/** @var float */
	private $radius;
	/** @var float */
	private $curve;

	public function __construct(float $centerX, float $centerZ, float $radius, float $curve = 1) {
		$this->centerX = $centerX;
		$this->centerZ = $centerZ;
		$this->radius = $radius;
		$this->curve = $curve;
	}

	public function getOpacity(float $x, float $y, float $z): float {
		return max(0, ($this->radius - sqrt((($this->centerX - $x) ** 2) + (($this->centerZ - $z) ** 2))) / $this->radius) ** $this->curve;
	}
}