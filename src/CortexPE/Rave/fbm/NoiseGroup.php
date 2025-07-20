<?php

declare(strict_types=1);

namespace CortexPE\Rave\fbm;


use CortexPE\Rave\filter\Filter;
use CortexPE\Rave\Noise;
use CortexPE\Rave\override\Override;

class NoiseGroup extends Noise {
	/** @var NoiseLayer[] */
	private $layers = [];

	// todo: move "filters" and stuff to Noise abstract class, and make them all into one array...
	//       this way, we can layer multiple layers of filters and overrides in any order we want
	/** @var Filter[] */
	private $filters = [];
	/** @var Override[] */
	private $overrides = [];

	public function addLayer(NoiseLayer $layer): void {
		$this->layers[] = $layer;
	}

	public function addFilter(Filter $filter): void {
		$this->filters[] = $filter;
	}

	public function addOverride(Override $override): void {
		$this->overrides[] = $override;
	}

	/**
	 * @param Noise $algorithm Noise algorithm to use
	 * @param int $count Octave count
	 * @param float $baseFrequency Starting frequency
	 * @param float $baseAmplitude Starting amplitude
	 * @param float $baseCurve
	 * @param float $lacunarity How much each octave decreases / increases in frequency (fraction)
	 * @param float $persistence How much each octave decreases / increases in amplitude
	 * @param float $curveMovement
	 */
	public function addOctaves(Noise $algorithm, int $count, float $baseFrequency, float $baseAmplitude, float $baseCurve, float $lacunarity, float $persistence, float $curveMovement): void {
		$curAmplitude = $baseAmplitude;
		$curFreq = $baseFrequency;
		$curCurve = $baseCurve;
		for($o = 0; $o < $count; $o++) {
			$this->addLayer(new NoiseLayer($algorithm, $curFreq, $curAmplitude, $curCurve));
			$curFreq *= $lacunarity;
			$curAmplitude *= $persistence;
			$curCurve *= $curveMovement;
		}
	}

	public function noise3D(float $x, float $y, float $z): float {
		$val = $this->getUnfilteredNoise($x, $y, $z) * 0.5 + 0.5;
		if(count($this->filters) > 0){
			$sum = 0;
			foreach($this->filters as $filter) {
				$sum += $filter->applyTo($this, $x, $y, $z, $val);
			}
			$val = $sum / count($this->filters);
		}
		return $val * 2 - 1;
	}

	/**
	 * @internal Only used to prevent infinite recursions
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 * @return float
	 */
	public function getUnfilteredNoise(float $x, float $y, float $z): float {
		$overrideVal = 0;
		$overrideCount = 0;
		foreach($this->overrides as $override){
			$v = $override->getValueAt($x, $y, $z);
			if($v === null)continue;
			$overrideVal += $v * 0.5 + 0.5;
			$overrideCount++;
		}
		if($overrideCount > 0){
			$val = $overrideVal / $overrideCount;
		} else {
			$total = 0;
			$ampTotal = 0;
			foreach($this->layers as $layer) {
				$total += $layer->getNoise3DValue($x, $y, $z);
				$ampTotal += $layer->getAmplitude();
			}
			$val = ($total / $ampTotal);
		}
		return $this->constrainNoiseValue($x, $y, $z, $val * 2 - 1);
	}
}