<?php


declare(strict_types=1);
namespace CortexPE\Rave;


use CortexPE\Rave\interpolation\Interpolator;
use pocketmine\utils\Random;

class Perlin extends Noise {
	// based on https://mrl.cs.nyu.edu/~perlin/noise/

	/** @var Interpolator */
	private $interpolator;
	/** @var Random */
	private $random;
	/** @var */
	private $table = [];

	public function __construct(Interpolator $interpolator, Random $random) {
		$this->interpolator = $interpolator;
		$this->random = $random;
		for($i = 0; $i < 512; $i++) {
			$this->table[$i] = $random->nextBoundedInt(255);
		}
	}

	private static function fade(float $t) {
		return $t * $t * $t * ($t * ($t * 6 - 15) + 10);
	}

	private static function grad(int $hash, float $x, float $y, float $z) {
		// CONVERT LO 4 BITS OF HASH CODE INTO 12 GRADIENT DIRECTIONS.
		$h = $hash & 15;
		$u = ($h < 8) ? $x : $y;
		$v = ($h < 4) ? $y : (($h == 12 || $h == 14) ? $x : $z);
		return ((($h & 1) == 0) ? $u : -$u) + ((($h & 2) == 0) ? $v : -$v);
	}

	public function noise3D(float $x, float $y, float $z): float {
		// FIND UNIT CUBE THAT CONTAINS POINT.
		$X = ($fx = (int)floor($ox = $x)) & 255;
		$Y = ($fy = (int)floor($oy = $y)) & 255;
		$Z = ($fz = (int)floor($oz = $z)) & 255;

		// FIND RELATIVE X,Y,Z OF POINT IN CUBE.
		$x -= $fx;
		$y -= $fy;
		$z -= $fz;

		// COMPUTE FADE CURVES FOR EACH OF X,Y,Z.
		$u = self::fade($x);
		$v = self::fade($y);
		$w = self::fade($z);

		// HASH COORDINATES OF THE 8 CUBE CORNERS,
		$A = $this->table[$X] + $Y;
		$AA = $this->table[$A] + $Z;
		$AB = $this->table[$A + 1] + $Z;
		$B = $this->table[$X + 1] + $Y;
		$BA = $this->table[$B] + $Z;
		$BB = $this->table[$B + 1] + $Z;

		// AND ADD BLENDED RESULTS FROM 8 CORNERS OF CUBE
		return $this->constrainNoiseValue($ox, $oy, $oz, $this->interpolator->interpolate($w,
			$this->interpolator->interpolate($v,
				$this->interpolator->interpolate($u,
					self::grad($this->table[$AA], $x, $y, $z), self::grad($this->table[$BA], $x - 1, $y, $z)
				),
				$this->interpolator->interpolate($u,
					self::grad($this->table[$AB], $x, $y - 1, $z), self::grad($this->table[$BB], $x - 1, $y - 1, $z))
			),
			$this->interpolator->interpolate($v,
				$this->interpolator->interpolate($u,
					self::grad($this->table[$AA + 1], $x, $y, $z - 1), self::grad($this->table[$BA + 1], $x - 1, $y, $z - 1)
				),
				$this->interpolator->interpolate($u,
					self::grad($this->table[$AB + 1], $x, $y - 1, $z - 1), self::grad($this->table[$BB + 1], $x - 1, $y - 1, $z - 1)
				)
			)
		));
	}
}