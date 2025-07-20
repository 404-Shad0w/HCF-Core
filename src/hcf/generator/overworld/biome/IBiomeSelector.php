<?php

namespace hcf\generator\overworld\biome;

use pocketmine\world\biome\Biome;

interface IBiomeSelector {
	public function pickBiome(float $x, float $y, float $z, ?int $forceBiome = null): Biome;
}