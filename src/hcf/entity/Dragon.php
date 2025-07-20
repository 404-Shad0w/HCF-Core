<?php

namespace hcf\entity;

use hcf\util\Utils;
use hcf\entity\effect\DummyEffectManager;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\player\Player;

class Dragon extends Living {

	public static function getNetworkTypeId(): string {
		return EntityIds::ENDER_DRAGON;
	}

	protected function getInitialSizeInfo(): EntitySizeInfo {
		return new EntitySizeInfo(4, 8);
	}

	protected float $gravity = 0.0;

	private const STATE_STATIONARY = 0;
	private const STATE_WANDERING = 1;
	private const STATE_ATTACKING = 2;

	private $state = self::STATE_STATIONARY;
	private $stateTimer = 0;

	private $prevAnimTime;
	private $animTime;
	private $growlTime = 100;

	public bool $keepMovement = true; // no-clip

	protected int $maxDeadTicks = 200;

	/** @var Vector3[] */
	private $flyObjectives = [];
	/** @var Vector3|null */
	private $currentTarget;
	/** @var int */
	private $lastTargetTick = 0;

	/** @var Vector3 */
	private $center;
	/** @var EndCrystalEntity|null */
	private $currentCrystal;

	public function __construct(Location $location, ?CompoundTag $nbt = null) {
		$this->center = new Vector3(0, 32, 0);
		parent::__construct($location, $nbt);
		$this->setCanSaveWithChunk(false);
	}

	protected function initEntity(CompoundTag $nbt): void {
		parent::initEntity($nbt);
		$this->effectManager = new DummyEffectManager($this);
		$this->setMaxHealth(400);
		$this->setHealth($this->getMaxHealth());
	}

	public function getName(): string {
		return "Ender Dragon";
	}

	private function pickTarget(): void {
		$players = $this->getWorld()->getPlayers();
		do {
			if(mt_rand(1, 100) < 30 && count($players) > 0) {
				$this->currentTarget = $players[array_rand($players)]->location;
			} else {
				$this->currentTarget = array_pop($this->flyObjectives);
			}
		} while($this->currentTarget->y < 16);
		$this->lastTargetTick = $this->ticksLived;
	}

	public function entityBaseTick(int $tickDiff = 1): bool {
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if(!$this->isSilent()) {
			$f = cos($this->animTime * (M_PI * 2));
			$f1 = cos($this->prevAnimTime * (M_PI * 2));

			if($f1 <= -0.3 && $f >= -0.3) {
				$this->playSound("mob.enderdragon.flap", 5, 0.8 + lcg_value() * 0.3);
			}

			if($this->state !== self::STATE_STATIONARY && --$this->growlTime < 0) {
				$this->playSound("mob.enderdragon.growl", 5, 0.8 + lcg_value() * 0.3);
				$this->growlTime = 200 + mt_rand(0, 200);
			}
		}
		$this->prevAnimTime = $this->animTime;

		$justChangedTarget = false;

		if($this->stateTimer > 0) {
			$this->stateTimer--;

			$f11 = 0.2 / (sqrt($this->motion->x * $this->motion->x + $this->motion->z * $this->motion->z) * 10 + 1);
			$f11 = $f11 * (float)pow(2, $this->motion->y);

			if($this->state === self::STATE_STATIONARY) {
				$this->animTime += 0.2;
			}/* elseif(slowed) {
				$this->animTime = $f11 * 0.5;
			}*/ else {
				$this->animTime = $f11;
			}

			if($this->state === self::STATE_WANDERING) {
				if(count($this->flyObjectives) < 1) {
					$radius = 32;
					for($i = 0; $i < 360; $i += 30) {
						$pos = $this->center->add($radius * sin($i), 0, $radius * cos($i));
						$pos = $pos->add(mt_rand(-32, 32), mt_rand(0, 32), mt_rand(-32, 32));
						$this->flyObjectives[] = $pos;
					}
					$crystals = [];
					foreach($this->getWorld()->getEntities() as $entity) {
						if(!$entity instanceof EndCrystalEntity) continue;
						$crystals[] = $entity->location->add(mt_rand(-16, 16), mt_rand(-32, 8), mt_rand(-16, 16));
					}
					array_splice($this->flyObjectives, 0, count($crystals), $crystals);
					shuffle($this->flyObjectives);
				}
				if($this->currentTarget instanceof Player && $this->location->distance($this->currentTarget->location) < 8) {
                    NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [ActorEventPacket::create($this->getId(), ActorEvent::DRAGON_PUKE, 0)]);
                    //$this->getWorld()->getServer()->broadcastPackets($this->getViewers(), [ActorEventPacket::create($this->getId(), ActorEvent::DRAGON_PUKE, 0)]);
				}

				if($this->currentTarget === null || /*($this->ticksLived - $this->lastTargetTick) > 100 || */ ($this->currentTarget instanceof Vector3 && $this->getBoundingBox()->isVectorInside($this->currentTarget))) {
					$this->pickTarget();
					$justChangedTarget = true;
				}

				if($this->ticksLived % 10 === 0) {
					foreach($this->getWorld()->getNearbyEntities($this->boundingBox->expandedCopy(32, 32, 32), $this) as $nearbyEntity) {
						if(!$nearbyEntity instanceof EndCrystalEntity) continue;
						if($this->location->distance($nearbyEntity->location) > 32) continue;
						$this->heal(new EntityRegainHealthEvent($this, $tickDiff, EntityRegainHealthEvent::CAUSE_REGEN));
						$this->currentCrystal = $nearbyEntity;
						//$nearbyEntity->getDataPropertyManager()->setLong(Entity::DATA_TARGET_EID, $this->getId());
					}
				}
				if($this->currentCrystal !== null && $this->location->distance($this->currentCrystal->location) > 32) {
					$this->currentCrystal = null;
				}

				if($this->currentCrystal !== null && $this->currentCrystal->isClosed()) {
					$this->currentCrystal = null;
					$this->attack(new EntityDamageEvent($this, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, 10));
				}

				/*$healthPercent = $this->getHealth() / $this->getMaxHealth();
				if($healthPercent > 0.25 && $this->ticksLived % (200 * $healthPercent) === 0 && $this->getHealth() < $this->getMaxHealth()) {
					$this->heal(new EntityRegainHealthEvent($this, ($this->getMaxHealth() - $this->getHealth()) * (10 / mt_rand(50, 1000)), EntityRegainHealthEvent::CAUSE_REGEN));
				}*/
			}
		} elseif($this->stateTimer === 0) {
			if($this->state === self::STATE_STATIONARY) {
				$this->state = self::STATE_WANDERING;
				$this->stateTimer = 200;
			}
			if($this->state === self::STATE_WANDERING) {
				$this->state = self::STATE_ATTACKING;
				$this->stateTimer = 20;
			}
			if($this->state === self::STATE_ATTACKING) {
				$this->state = self::STATE_WANDERING;
				$this->stateTimer = 200;
			}

			if($this->stateTimer === 0) {
				throw new \RuntimeException("CORTEX FORGOT TO PUT A STATE TIMER.");
			}
		}

		if($this->attackTime == 0) {
			$aaBB = $this->getBoundingBox();
			$len = $aaBB->getAverageEdgeLength() / 4;
			$this->collideWithEntities($this->getWorld()->getNearbyEntities($aaBB->contractedCopy($len, 0, $len), $this));
		}

		if($this->currentTarget instanceof Vector3) {
			$this->setMotion($this->currentTarget->subtractVector($this->location)->normalize());
			$this->lookAt($this->currentTarget);
			$this->location->yaw = ((int)$this->location->yaw + 180) % 360;
		}

		if(($this->location->getFloorY() - $this->getWorld()->getHighestBlockAt($this->location->getFloorX(), $this->location->getFloorZ())) <= 5 && ($this->ticksLived % 200 == 0)) {
            NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [ActorEventPacket::create($this->getId(), ActorEvent::DRAGON_PUKE, 0)]);
            //$this->getWorld()->getServer()->broadcastPackets($this->getViewers(), [ActorEventPacket::create($this->getId(), ActorEvent::DRAGON_PUKE, 0)]);
		} elseif((($this->ticksLived % 300 == 0) || $justChangedTarget) && $this->motion->y < -0.01 && $this->currentTarget instanceof Player && lcg_value() > 0.5) {
			$e = new DragonFireball($this->getLocation(), $this);
			$e->setOwningEntity($this);
			$e->spawnToAll();
			$this->location->yaw = ($this->location->yaw + 180) % 360;
			$mot = $this->getDirectionVector()->multiply(2);
			$this->location->yaw = ($this->location->yaw + 180) % 360;
			$e->setMotion($mot->subtract(0, -0.05, 0));
		}

		return $hasUpdate;
	}

	protected function startDeathAnimation(): void {
        NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [ActorEventPacket::create($this->getId(), ActorEvent::ENDER_DRAGON_DEATH, 0)]);
        //$this->getWorld()->getServer()->broadcastPackets($this->getViewers(), [ActorEventPacket::create($this->getId(), ActorEvent::ENDER_DRAGON_DEATH, 0)]);

		$this->playSound("mob.enderdragon.death", 5);
	}

	private function playSound(string $soundName, float $volume = 1, float $pitch = 1): void {
		$pk = new PlaySoundPacket();
		$pk->soundName = $soundName;
		$pk->x = $this->location->x;
		$pk->y = $this->location->y;
		$pk->z = $this->location->z;
		$pk->volume = $volume;
		$pk->pitch = $pitch;
        NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [$pk]);
        //$this->getWorld()->getServer()->broadcastPackets($this->getViewers(), [$pk]);
	}

	public function canBeMovedByCurrents(): bool {
		return false;
	}

	protected function checkBlockCollision(): void {
		// noop : no "block collision" calls...
	}

	protected function checkGroundState(float $wantedX, float $wantedY, float $wantedZ, float $dx, float $dy, float $dz): void {
		// noop : no fall damage...
	}

	protected function updateFallState(float $distanceThisTick, bool $onGround): ?float {
		// noop : no fall damage...
		return null;
	}

	public function isInsideOfSolid(): bool {
		return false; // prevent suffocation damage
	}

	public function isFireProof(): bool {
		return true;
	}

	public function attack(EntityDamageEvent $source): void {
		$source->setBaseDamage($source->getBaseDamage() * 0.25);
		if($source instanceof EntityDamageByEntityEvent && $source->getDamager() === $this->currentTarget) {
			$this->pickTarget();
		}
		parent::attack($source);
	}

	/**
	 * @param Entity[] $entities
	 */
	public function collideWithEntities(array $entities): void {
		foreach($entities as $entity) {
			if(!$entity instanceof Player) continue;
			if($this->state === self::STATE_STATIONARY/* || ((EntityLivingBase)entity).getRevengeTimer() >= entity.ticksExisted - 2*/) continue;
			$ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, /*5*/ 10);
			$entity->attack($ev);
			if($ev->isCancelled()) continue;
			$centerX = ($this->boundingBox->minX + $this->boundingBox->maxX) / 2;
			$centerZ = ($this->boundingBox->minZ + $this->boundingBox->maxZ) / 2;

			$deltaX = $entity->location->x - $centerX;
			$deltaZ = $entity->location->z - $centerZ;
			$f = $deltaX * $deltaX + $deltaX * $deltaX;
			if($f < 0.000001) continue;
			$mot = new Vector3($deltaX / $f * 4.0, 0.20000000298023224, $deltaZ / $f * 4.0);
			$mot->x = min(20, $mot->x);
			$mot->z = min(20, $mot->z);
			$entity->setMotion($entity->getMotion()->addVector($mot));
		}
	}

	public function addObjective(Vector3 $pos): void {
		$this->flyObjectives[] = $pos;
	}

	public function clearObjectives(): void {
		$this->flyObjectives = [];
	}

	/**
	 * @param Vector3 $center
	 */
	public function setCenter(Vector3 $center): void {
		$this->center = $center;
	}

	public function setHealth(float $amount): void {
		parent::setHealth($amount);
		$this->broadcastBossEvent(BossEventPacket::TYPE_HEALTH_PERCENT);
	}

	public function spawnTo(Player $player): void {
		parent::spawnTo($player);
		$this->broadcastBossEvent(BossEventPacket::TYPE_SHOW);
	}

	public function despawnFrom(Player $player, bool $send = true): void {
		parent::despawnFrom($player, $send);
		$this->broadcastBossEvent(BossEventPacket::TYPE_HIDE);
	}

	public function isOnFire(): bool {
		return false;
	}

	private function broadcastBossEvent(int $eventType): void {
		$pk = new BossEventPacket();
		$pk->bossActorUniqueId = $this->id;
		$pk->eventType = $eventType;

		$pk->title = $this->getNameTag() !== "" ? $this->getNameTag() : $this->getName();
		$pk->healthPercent = $this->getHealth() / $this->getMaxHealth();

		$pk->color = 5;
        $pk->overlay = 0;
        $pk->darkenScreen = false;

        NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [$pk]);
        //$this->getWorld()->getServer()->broadcastPackets($this->getViewers(), [$pk]);
	}

	public function getDrops(): array {
		return [];
	}
}