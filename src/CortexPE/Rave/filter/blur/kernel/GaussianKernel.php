<?php


namespace CortexPE\Rave\filter\blur\kernel;


class GaussianKernel implements Kernel {
	// https://www.geeksforgeeks.org/gaussian-filter-generation-c/
	// yes ik there is a gaussian kernel generator on PM, but since we're re-inventing the wheel, just re-invent it all

	/** @var int */
	private $size;
	/** @var float[][] */
	private $kernel = [];
	/** @var int */
	private $radius;

	public function __construct(int $radius) {
		$this->size = ($this->radius = $radius) * 2 + 1;
		$sum = 0;
		for($x = -$radius; $x <= $radius; $x++) {
			for($y = -$radius; $y <= $radius; $y++) {
				$val = exp(-($x * $x + $y * $y) / $radius) / (M_PI * $radius);
				$this->kernel[$radius + $x][$radius + $y] = $val;
				$sum += $val;
			}
		}
		for($x = 0; $x < $this->size; $x++) {
			for($y = 0; $y < $this->size; $y++) {
				$this->kernel[$x][$y] /= $sum;
			}
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