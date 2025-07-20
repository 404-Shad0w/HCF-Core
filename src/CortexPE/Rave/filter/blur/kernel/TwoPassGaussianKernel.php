<?php


namespace CortexPE\Rave\filter\blur\kernel;


class TwoPassGaussianKernel implements Kernel {
	// concept from https://learnopengl.com/Advanced-Lighting/Bloom

	/** @var int */
	private $size;
	/** @var float[][] */
	private $kernel = [];
	/** @var int */
	private $radius;

	public function __construct(int $radius) {
		$this->size = ($this->radius = $radius) * 2 + 1;
		$sum = 0;
		$kernel = [];
		for($y = -$radius; $y <= $radius; $y++) {
			$val = exp(-($y * $y) / $radius) / (M_PI * $radius);
			$kernel[$radius + $y] = $val;
			$sum += $val;
		}
		for($y = 0; $y < $this->size; $y++) {
			$kernel[$y] /= $sum * $this->size;
		}
		for($x = 0; $x < $this->size; $x++) {
			$this->kernel[$x] = $kernel;
		}
	}

	/**
	 * @return float[][]
	 */
	public function getKernel(): array {
		return $this->kernel;
	}

	/**
	 * @return int
	 */
	public function getSize(): int {
		return $this->size;
	}

	/**
	 * @return int
	 */
	public function getRadius(): int {
		return $this->radius;
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @return float
	 */
	public function valueAt(int $x, int $y): float {
		return $this->kernel[$x][$y];
	}
}