<?php

namespace hcf\entity\effect;

use pocketmine\entity\effect\EffectManager;

class DummyEffectManager extends EffectManager {
	public function tick(int $tickDiff = 1): bool {
		return false; // noop
	}
}