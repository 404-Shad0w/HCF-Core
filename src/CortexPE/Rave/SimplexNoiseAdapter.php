<?php


namespace CortexPE\Rave;


use pocketmine\utils\Random;
use pocketmine\world\generator\noise\Simplex;

class SimplexNoiseAdapter extends Noise {
	/** @var Simplex */
	private $encapsulated;

	public function __construct(Random $random) {
		$this->encapsulated = new Simplex($random, 0, 0, 0); // these other args are handled by us now
	}

	public function noise3D(float $x, float $y, float $z): float {
		return $this->encapsulated->getNoise3D($x, $y, $z);
	}
}