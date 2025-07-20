<?php

namespace hcf\module\lootbox\task;

use hcf\module\lootbox\entity\TestEntity;
use hcf\module\lootbox\LootBoxRun;
use pocketmine\scheduler\Task;

class LBT extends Task {

    public LootBoxRun $box;

	public function __construct(LootBoxRun $box)
	{
		$this->box = $box;
	}

	public function onRun(): void
	{
		if (!$this->box->isEnabled()) {
			$this->getHandler()->cancel();
			return;
		}
		if (!$this->box->getPlayer()->isOnline()) {
			foreach ($this->box->getEntities() as $entity) {
				if ($entity instanceof TestEntity) {
					$this->box->setEnabled(false);
					if ($entity->isFlaggedForDespawn()) {
						$entity->flagForDespawn();
						$entity->getEntity()->flagForDespawn();
						$this->box->getFloatingText()->flagForDespawn();
						$this->getHandler()->cancel();
					}
				}
			}
		}
		$this->box->tick();
	}

}