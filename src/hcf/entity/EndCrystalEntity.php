<?php

namespace hcf\entity;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityPreExplodeEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\world\Explosion;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;

class EndCrystalEntity extends Living {

	private bool $showBase = false;

	public static function getNetworkTypeId(): string {
		return EntityIds::ENDER_CRYSTAL;
	}

	protected function getInitialSizeInfo(): EntitySizeInfo {
		return new EntitySizeInfo(0.98, 0.98);
	}

	public function isShowingBase(): bool {
		return $this->showBase;
	}

	public function setShowBase(bool $showBase = true): void {
		$this->showBase = $showBase;
		$this->networkPropertiesDirty = true;
	}

	protected function syncNetworkData(EntityMetadataCollection $properties): void {
		parent::syncNetworkData($properties);
		$properties->setGenericFlag(EntityMetadataFlags::SHOWBASE, $this->showBase);
	}

	public function setOnFire(int $seconds): void {
		// noop
	}

	public function attack(EntityDamageEvent $source): void {
		if(!$source instanceof EntityDamageByEntityEvent && !$source instanceof EntityDamageByChildEntityEvent) return;
		if($this->isFlaggedForDespawn()) return;
		$this->flagForDespawn();
		$ev = new EntityPreExplodeEvent($this, 6);
		$ev->call();
		if($ev->isCancelled()){
			$this->getWorld()->addParticle($this->location, new HugeExplodeSeedParticle());
			$this->getWorld()->addSound($this->location, new ExplodeSound());
			return;
		}
		$explosion = new Explosion(Position::fromObject($this->getPosition()->add(0, $this->size->getHeight() / 2, 0), $this->getWorld()), $ev->getRadius(), $this);
		if($ev->isBlockBreaking()) $explosion->explodeA();
		$explosion->explodeB();
	}

	public function getName(): string {
		return "End Crystal";
	}
}