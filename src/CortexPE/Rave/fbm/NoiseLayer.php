<?php


namespace CortexPE\Rave\fbm;


use CortexPE\Rave\Noise;
use pocketmine\utils\Utils;

class NoiseLayer {
	/** @var Noise */
	private $noise;
	/** @var float */
	private $frequency;
	/** @var float */
	private $amplitude;
	/** @var float */
	private $exponent;
	/** @var float */
	private $preCallback;

	/**
	 * NoiseLayer constructor.
	 * @param Noise $noise Noise algorithm
	 * @param float $frequency Sampling rate, the higher the finer and sharper the changes are
	 * @param float $amplitude Maximum amplitude, 0-amplitude value
	 * @param float $exponent Exponential sharpness, 0-2, the higher the slower it peaks, the lower, the faster it peaks
	 * @param callable|null $preCallback Custom callback for processing before exponent and amplitude scaling
	 * @noinspection PhpInconsistentReturnPointsInspection
	 */
	public function __construct(Noise $noise, float $frequency, float $amplitude = 1, float $exponent = 1, ?callable $preCallback = null) {
		if($preCallback !== null){
			Utils::validateCallableSignature(function(float $x): float{}, $preCallback);
		}

		$this->noise = $noise;
		$this->frequency = $frequency;
		$this->amplitude = $amplitude;
		$this->exponent = $exponent;
		$this->preCallback = $preCallback;
	}

	/**
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 * @return float 0-1 float value
	 */
	public function getNoise3DValue(float $x, float $y, float $z):float {
		$val = $this->noise->noise3D($x * $this->frequency, $y * $this->frequency, $z * $this->frequency);
		if ($val < -1 || $val > 1) throw new \UnexpectedValueException("Expected a value between -1 to 1, got $val");
		if($this->preCallback !== null){
			$val = ($this->preCallback)($val);
		}

		// this is because we can't really raise negative numbers to exponents properly sometimes
		// https://www.desmos.com/calculator/2t9rlmaf7i
		$val = $val * 0.5 + 0.5; // [-1, 1] => [0, 1]
		$val **= $this->exponent;

		$val *= $this->amplitude;

		// not needed, we are expecting a [0, 1] value anyway for NoiseGroup->getUnfilteredNoise()
		// $val = $val * 2 - 1; // [0, 1] => [-1, 1]

		return $val;
	}

	/**
	 * @return float
	 */
	public function getAmplitude(): float {
		return $this->amplitude;
	}
}