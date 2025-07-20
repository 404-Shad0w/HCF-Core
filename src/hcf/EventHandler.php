<?php
    
/*
 * A PocketMine-MP plugin that implements Hard Core Factions.
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the Apache License, Version 2.0 (the 'License');
 * you may not use this file except in compliance with the License.
 *
 * @author JkqzDev
 */

declare(strict_types=1);

namespace hcf;

use hcf\waypoint\WayPoint;
use hcf\kit\class\default\PythonClass;
use hcf\kit\class\default\ArcherClass;
use hcf\kit\class\default\MinerClass;
use hcf\kit\class\default\BardClass;
use hcf\kit\class\default\RogueClass;
use hcf\kit\class\default\MageClass;
use itoozh\deathban\session\SessionManager;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use hcf\util\Utils;
use hcf\faction\FactionFactory;
use hcf\claim\ClaimHandler;
use hcf\entity\SnowballEntity;
use hcf\block\EndPortal;
use hcf\block\NetherPortal; 
use hcf\claim\Claim;
use hcf\item\SplashPotion;
use hcf\item\EnderPearl as PearlItem;
use hcf\entity\EnderPearlEntity;
use hcf\entity\SplashPotionEntity;
use hcf\faction\event\FactionAddPointEvent;
use hcf\faction\event\FactionRemovePointEvent;
use hcf\session\Session;
use hcf\session\SessionFactory;
use hcf\timer\TimerFactory;
use yeivwi\ce\enchantments\CustomEnchant;
use pocketmine\block\{FenceGate, Fence, Door, Trapdoor};
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\event\inventory\{CraftItemEvent,
    InventoryTransactionEvent};
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\Armor;
use pocketmine\item\Tool;
use pocketmine\block\BlockTypeIds;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\world\WorldSoundEvent;
use pocketmine\world\sound\{EntityAttackNoDamageSound, EntityAttackSound, ClickSound};
use pocketmine\item\PotionType;
use pocketmine\Server;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use ReflectionClass;
use function min;
use function time;

final class EventHandler implements Listener {
    
    private $supposedMotion = null;
	private $lastYDiff = -1;
    
    private PotionType $potionType;
    
    private bool $ignoreBlockBreak = false;

	private array $lastHit = [];

	public function handleDecay(LeavesDecayEvent $event) : void {
		$event->cancel();
	}
    
    public function handleEntitySpawn(EntitySpawnEvent $event): void {
        $e = $event->getEntity();
        $p = $e->getPosition();
        
        $insideClaim = ClaimHandler::insideClaim($p);
        
        if ($insideClaim !== null) {
            if ($insideClaim->getType() === Claim::SPAWN) {
                if ($e instanceof ItemEntity && $e->getItem()->getNamedTag()->getTag('mystery_crate') === null) {
                    $e->flagForDespawn();
                }
            }
        }
    }
    
    public function handlerSoundCancel(WorldSoundEvent $event): void {
         if ($event->getSound() instanceof EntityAttackSound) {
             $event->cancel();
         }
         if ($event->getSound() instanceof EntityAttackNoDamageSound) {
             $event->cancel();
         }
    }
    
    public function handleKbDamage(EntityDamageByEntityEvent $ev): void {
        if($ev->getKnockBack() <= 0) return;
        
        $kbRatio = $ev->getKnockBack() / 0.4; // we have 0.4 as default 
        
        $ent = $ev->getEntity();
		$dmg = $ev->getDamager();
        
        if (!$ent instanceof Player) return;
        
        $session = SessionFactory::get($ent);
        
        if ($session === null) return;
        
        if (TimerFactory::get('sotw') !== null && TimerFactory::get('sotw')->isEnabled() && $session->hasSotwEnable() === false) {
			$ev->cancel();
			return;
		}
        
        $entLoc = $ent->getLocation();
		$dmgLoc = $dmg->getLocation();
        
        $deltaX = $entLoc->x - $dmgLoc->x;
		$deltaZ = $entLoc->z - $dmgLoc->z;
        
        if($ev instanceof EntityDamageByChildEntityEvent) {
            $chd = $ev->getChild();
			if($chd === null) return;
            $chdMot = $chd->getMotion();
			$deltaX = $chdMot->x;
			$deltaZ = $chdMot->z;
        }
        
        $f = sqrt($deltaX * $deltaX + $deltaZ * $deltaZ);
        if($f <= 0) return;
        
        if(lcg_value() < ($ent->getAttributeMap()->get(Attribute::KNOCKBACK_RESISTANCE)?->getValue() ?? 0)) return;
        
        $this->lastYDiff = $ent->getPosition()->getY() - $dmg->getPosition()->getY();
        
        $motion = clone $ent->getMotion();
        
        $f = 1 / $f;
        
        $conf = Utils::getKbFile();
        
        $motionX = $motion->x / $conf->getNested("kbFriction.x");
		$motionY = $motion->y / $conf->getNested("kbFriction.y");
		$motionZ = $motion->z / $conf->getNested("kbFriction.z");
        
        $motionX += $deltaX * $f * ($conf->getNested("kbVector.x") * $kbRatio);
		$motionY += $yKb = ($conf->getNested("kbVector.y") * $kbRatio);
		$motionZ += $deltaZ * $f * ($conf->getNested("kbVector.z") * $kbRatio);
        
        if($motionY > $yKb) {$motionY = $yKb;}
        $this->supposedMotion = new Vector3($motionX, $motionY, $motionZ);
    }
    
    public function onMotion(EntityMotionEvent $ev): void {
        if($this->supposedMotion === null) return;
        
        static $lock = false;
		if($lock) return;
		$lock = true;
        
        $conf = Utils::getKbFile();
        if($conf->getNested("kbLimiter.enabled")) {
            $limitX = $conf->getNested("kbLimiter.x");
			$limitY = $conf->getNested("kbLimiter.y");
			$limitZ = $conf->getNested("kbLimiter.z");
			$yLimit = $conf->getNested("kbLimiter.yDiffLimit");
            
            $this->supposedMotion->x = self::clamp($this->supposedMotion->x, -$limitX, $limitX);
			$this->supposedMotion->z = self::clamp($this->supposedMotion->z, -$limitZ, $limitZ);
            if($this->lastYDiff > $yLimit) {
                $this->supposedMotion->y = $conf->getNested("kbLimiter.yCutoff");
            } else {
                $this->supposedMotion->y = self::clamp($this->supposedMotion->y, -$limitY, $limitY);
            }
        }
        
        $ev->getEntity()->setMotion($this->supposedMotion);
		$this->supposedMotion = null;
		$ev->cancel();
		$lock = false;
    }
    
    private static function clamp(float $v, float $min, float $max): float {
		if($v > $max) return $max;
		if($v < $min) return $min;
		return $v;
	}
    
    public function onDamage(EntityDamageByEntityEvent $ev): void {
		$p = $ev->getDamager();
        
        if (!$p instanceof Player) return;
        
        $session = SessionFactory::get($p);
        
        if ($session === null) return;
        
        if (TimerFactory::get('sotw') !== null && TimerFactory::get('sotw')->isEnabled() && $session->hasSotwEnable() === false) {
			$ev->cancel();
			return;
		}
        
		if(!$p instanceof Player) return;
		if(!Utils::getKbFile()->get("criticalsWhileSprinting")) return;
		// directly copied from PM, just reversed the `$p->isSprinting()` constraint
		if($p->isSprinting() and !$p->isFlying() and $p->fallDistance > 0 and !$p->getEffects()->has(VanillaEffects::BLINDNESS()) and !$p->isUnderwater()) {
			$ev->setModifier($ev->getFinalDamage() / 2, EntityDamageEvent::MODIFIER_CRITICAL);
		}
	}

	public function handleDamage(EntityDamageEvent $event) : void {
		$cause = $event->getCause();
		$player = $event->getEntity();

		if (!$player instanceof Player) {
			return;
		}
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if (TimerFactory::get('eotw') !== null && TimerFactory::get('eotw')->isEnabled()) {
			return;
		}

		if (TimerFactory::get('sotw') !== null && TimerFactory::get('sotw')->isEnabled() && !$session->hasSotwEnable()) {
			$event->cancel();
			return;
		}

		if ($session->getTimer('starting_timer') !== null && ($cause === EntityDamageEvent::CAUSE_ENTITY_ATTACK || $cause === EntityDamageEvent::CAUSE_PROJECTILE || $cause === EntityDamageEvent::CAUSE_LAVA || $cause === EntityDamageEvent::CAUSE_FALL || $cause === EntityDamageEvent::CAUSE_FIRE)) {
			$event->cancel();
			return;
		}
        
        if ($session->getTimer('pvp_timer') !== null && ($cause === EntityDamageEvent::CAUSE_ENTITY_ATTACK || $cause === EntityDamageEvent::CAUSE_PROJECTILE || $cause === EntityDamageEvent::CAUSE_LAVA || $cause === EntityDamageEvent::CAUSE_FALL || $cause === EntityDamageEvent::CAUSE_FIRE)) {
            $event->cancel();
            return;
        }
        
		$currentClaim = $session->getCurrentClaim();

		if ($currentClaim !== null && $currentClaim->getType() === Claim::SPAWN) {
			$event->cancel();
			return;
		}

		if ($event instanceof EntityDamageByEntityEvent) {
			$damager = $event->getDamager();

			if (!$damager instanceof Player) {
				return;
			}
			$target = SessionFactory::get($damager);

			if ($target === null) {
				return;
			}

			if ($target->getTimer('starting_timer') !== null || $target->getTimer('pvp_timer') !== null) {
                //$damager->sendMessage(TextFormat::colorize('&eThe player has &aPvP Timer &eActivated.'));
				$event->cancel();
				return;
			}
			
			if ($session->getTimer('starting_timer') !== null || $session->getTimer('pvp_timer') !== null) {
                $damager->sendMessage(TextFormat::colorize('&eThe player has &aPvP Timer &eActivated.'));
				$event->cancel();
				return;
			}
			
			$currentClaim = $target->getCurrentClaim();

			if ($currentClaim !== null && $currentClaim->getType() === Claim::SPAWN) {
				$event->cancel();
				return;
			}

			if ($target->getFaction() !== null && $target->getFaction()->equals($session->getFaction())) {
				$event->cancel();
				return;
			}
			
			if ($session->getTimer('spawn_tag') === null) {
				$player->sendMessage(TextFormat::colorize('&cYou are now Combat Tagged for &630 seconds'));
            }
			$session->addTimer('spawn_tag', '&cSpawn Tag&r&7:&f', 30);
			
			if ($target->getTimer('spawn_tag') === null) {
				$damager->sendMessage(TextFormat::colorize('&cYou are now Combat Tagged for &630 seconds'));
            }
			$target->addTimer('spawn_tag', '&cSpawn Tag&r&7:&f', 30);

			$this->lastHit[$session->getXuid()] = [time() + 30, $target];
		} 
        if ($event instanceof EntityDamageByChildEntityEvent) {
            $damager = $event->getDamager();
            $entity = $event->getEntity();
            $child = $event->getChild();
            $session = SessionFactory::get($entity);
            
            if($child instanceof Arrow) {
                if ($damager instanceof Player) {
                    Utils::play($damager, "random.orb");
                }
            }
            
            if ($child instanceof SnowballEntity && ($session->getKitClass() instanceof ArcherClass || $session->getKitClass() instanceof BardClass || $session->getKitClass() instanceof MinerClass || $session->getKitClass() instanceof RogueClass || $session->getKitClass() instanceof MageClass || $session->getKitClass() instanceof PythonClass)) {
                $damager->sendMessage(TextFormat::colorize("&c[&7!&c]&r You can't use this when the player is using a class"));
                return;
            }
            
            if (!$damager instanceof Player || !$child instanceof SnowballEntity) return;
            
            $entity->sendMessage(TextFormat::colorize('&c[&7!&c]&r You have been debuffed by &a' . $damager->getName()));
            $damager->sendMessage(TextFormat::colorize('&c[&7!&c]&r You used snowball debuff with &a' . $player->getName()));
            
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 10 * 10, 2));
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), 10 * 10, 2));
        }
	}

	public function handleItemPickup(EntityItemPickupEvent $event) : void {
		$entity = $event->getEntity();
		$origin = $event->getOrigin();

		if (!$entity instanceof Player) {
			return;
		}
		$session = SessionFactory::get($entity);

		if ($session === null) {
			return;
		}
		$owningEntity = $origin->getOwningEntity();

		if ($owningEntity === null || $owningEntity->getId() !== $entity->getId()) {
			if ($session->getTimer('starting_timer') !== null || $session->getTimer('pvp_timer') !== null) {
				$event->cancel();
			}
		}
	}

	public function handleChat(PlayerChatEvent $event) : void {
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		$kitHandler = $session->getKitHandler();

		if ($kitHandler !== null) {
			$kitHandler->handleChat($event);
			return;
		}
		$faction = $session->getFaction();

		if ($faction !== null && $session->hasFactionChat()) {
			$event->cancel();
			$faction->chat($player->getName(), $event->getMessage());
		}
	}

	public function handleDeath(PlayerDeathEvent $event) : void {
		$player = $event->getPlayer();
        $cause = $player->getLastDamageCause();
		$session = SessionFactory::get($player);
        
        Utils::deathParticleRed($player);
        //Utils::playLight($player->getPosition());

		if ($session === null) {
			return;
		}
        
        if (TimerFactory::get('eotw') !== null && TimerFactory::get('eotw')->isEnabled()) {
            if (!$player->hasPermission('eotw.skip')) {
                $player->kick(TextFormat::colorize('&r&c YOU WERE KICKED OUT BY THE EOTW'));
            }
        }
        
        /*if ($player->getWorld()->getFolderName() === 'deathban') {
            $event->setDrops([]);
            $killer = null;
            $session->removeTimer('spawn_tag');
            if ($killer === null) {
                $event->setDeathMessage(TextFormat::colorize('&c' . $player->getName() . '&4[' . $session->getKills() . '] &edied'));
            } else {
                $event->setDeathMessage(TextFormat::colorize('&c' . $player->getName() . '&4[' . $session->getKills() . '] &edied'));
            }
            return;
        }*/
        
		/** @var Session|null $killer */
		$killer = null;

		if (isset($this->lastHit[$session->getXuid()])) {
			$data = $this->lastHit[$session->getXuid()];
			$time = (int) $data[0];

			if ($time > time()) {
				$killer = $data[1];
             
                if($cause instanceof EntityDamageByEntityEvent){
                    $damager = $cause->getDamager();
                    /*if ($damager instanceof Player) {
                        Main::getInstance()->addKill($damager);
                    }*/
                }
				$killer->addKill();
				$killer->addKillstreak();

				if ($killer->getKillStreak() > $killer->getBestKillStreak()) {
					$killer->addBestKillstreak();
				}
				$faction = $killer->getFaction();

				if ($faction !== null) {
					$ev = new FactionAddPointEvent($faction, 1);
					$ev->call();
					$faction->setPoints($faction->getPoints() + $ev->getPoints());
				}
			}
		}
		$session->removeTimer('spawn_tag');
        //Main::getInstance()->addDeath($player);
		$session->addDeath();
		$session->removeKillstreak();
        $config = HCF::getInstance()->getConfig();
        if ($config->get("kitmap-mode") === false) {
            $session->addTimer('pvp_timer', '&aPvP Timer&r&7:&f', 60 * 60);
        }
        
		$faction = $session->getFaction();

		if ($faction !== null) {
			$points = $faction->getPoints();
           
            $deathsUntilRaidable = $faction->getDeathsUntilRaidable() - 1.0;
			$points--;

			if ($deathsUntilRaidable <= 0.00 && !$faction->isRaidable()) {
				$points -= 10;

				if ($killer !== null && $killer->getFaction() !== null) {
					$ev = new FactionAddPointEvent($killer->getFaction(), 3);
					$ev->call();

					$killer->getFaction()->setPoints($killer->getFaction()->getPoints() + $ev->getPoints());
					$killer->getFaction()->announce('&cThe faction &l' . $faction->getName() . '&r&c is now RAIDABLE!');
				}
			}
			$ev = new FactionRemovePointEvent($faction, $points);
			$ev->call();

            if ($deathsUntilRaidable !== -3.9) {
                $faction->setDeathsUntilRaidable($deathsUntilRaidable);
            }
			$faction->setPoints($ev->getPoints());

			if ($faction->isRaidable()) {
				$regenCooldown = $faction->getRegenCooldown() + 5 * 60;
				$faction->setRegenCooldown(min($regenCooldown, (int) HCF::getInstance()->getConfig()->get('faction.regen-cooldown', 1800)));
			} else {
				$faction->setRegenCooldown((int) HCF::getInstance()->getConfig()->get('faction.regen-cooldown', 1800));
			}

			foreach ($faction->getOnlineMembers() as $member) {
				$member->getSession()->getPlayer()?->setScoreTag(TextFormat::colorize('&6[&c' . $faction->getName() . ' &c' . $faction->getDeathsUntilRaidable() . '&6]'));
			}
		}

		if ($killer === null) {
			$event->setDeathMessage(TextFormat::colorize('&c' . $player->getName() . '&4[' . $session->getKills() . '] &edied'));
		} else {
			$item = null;

			if ($killer->isOnline()) {
				$item = $killer->getPlayer()->getInventory()->getItemInHand();
			}
			$event->setDeathMessage(TextFormat::colorize('&c' . $player->getName() . '&4[' . $session->getKills() . '] &ewas slain by &c' . $killer->getName() . '&4[' . $killer->getKills() . ']' . ($item !== null ? ' &cusing ' . $item->getName() : '')));
		}
	}

	public function handleExhaust(PlayerExhaustEvent $event) : void {
		$player = $event->getPlayer();

		if (!$player instanceof Player) {
			return;
		}
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		$currentClaim = $session->getCurrentClaim();

		if ($currentClaim !== null && $currentClaim->getType() === Claim::SPAWN) {
			$event->cancel();

			if ($player->getHungerManager()->getFood() < $player->getHungerManager()->getMaxFood()) {
				$player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());
			}
			return;
		}

		if ($session->hasAutoFeed()) {
			$event->cancel();
		}
	}

	public function handleItemConsume(PlayerItemConsumeEvent $event) : void {
		$item = $event->getItem();
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if ($item->getTypeId() === ItemTypeIds::GOLDEN_APPLE) {
			if ($session->getTimer('golden_apple') !== null) {
				$timer = $session->getTimer('golden_apple');
				$player->sendMessage(TextFormat::RED . "You can't use " . TextFormat::GOLD . "GOLDEN_APPLE " . TextFormat::RED . "because you have a cooldown of " . Utils::date($timer->getTime()));
				$event->cancel();
				return;
			}
            $player->sendMessage(TextFormat::colorize('&7[&6!&7] &a You just used a &6GOLDEN_APPLE'));
			$session->addTimer('golden_apple', '&eApple&r&7:&f', 15);
		} elseif ($item->getTypeId() === ItemTypeIds::ENCHANTED_GOLDEN_APPLE) {
			if ($session->getTimer('golden_apple_enchanted') !== null) {
				$timer = $session->getTimer('golden_apple_enchanted');
				$player->sendMessage(TextFormat::RED . "You can't use " . TextFormat::GOLD . "ENCHANTED_GOLDEN_APPLE " . TextFormat::RED . "because you have a cooldown of " . Utils::date($timer->getTime()));
				$event->cancel();
				return;
			}
            #message
            $player->sendMessage(TextFormat::colorize('&l&7█&f█████&7█'));
            $player->sendMessage(TextFormat::colorize('&l&f███&0█&f███'));
            $player->sendMessage(TextFormat::colorize('&l&f██&e███&f██'));
            $player->sendMessage(TextFormat::colorize('&l&f█&e█████&f█ '));
            $player->sendMessage(TextFormat::colorize('&l&f█&e█████&f█ '));
            $player->sendMessage(TextFormat::colorize('&l&f█&e█████&f█ &r&7Cooldown: &c59:00m'));
            $player->sendMessage(TextFormat::colorize('&l&f██&e███&f██ &r&7Haz usado la &eOpGapple'));
            $player->sendMessage(TextFormat::colorize('&l&7█&f█████&7█&r'));
            #cooldown
            $session->addTimer(name: 'golden_apple_enchanted', format: '', time: 3600, visible: false);
        } elseif ($item->getTypeId() === ItemTypeIds::CHORUS_FRUIT) {
			if ($session->getTimer('chorus_fruit') !== null) {
				$timer = $session->getTimer('chorus_fruit');
				$player->sendMessage(TextFormat::RED . "You can't use " . TextFormat::GOLD . "§dCHORUS_FRUIT " . TextFormat::RED . "because you have a cooldown of " . Utils::date($timer->getTime()));
				$event->cancel();
				return;
			}
            $player->sendMessage(TextFormat::colorize('&7[&6!&7] &a You just used a &dCHORUS_FRUIT'));
			$session->addTimer('chorus_fruit', '&dChorus&r&7:&f', 60);  		
	        }
    }

	public function handleItemUse(PlayerItemUseEvent $event) : void {
		$item = $event->getItem();
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if ($item instanceof Armor) {
			$event->cancel();
		} elseif ($item->getTypeId() === ItemTypeIds::ENDER_PEARL) {
			if ($session->getTimer('ender_pearl') !== null) {
				$timer = $session->getTimer('ender_pearl');
				$player->sendMessage(TextFormat::RED . "You can't use " . TextFormat::RED . "§bENDER_PEARL " . TextFormat::RED . "because you have a cooldown of " . Utils::date($timer->getTime()));
				$event->cancel();
				return;
			}
            $player->sendMessage(TextFormat::colorize('&7[&6!&7] &a You just used a &bENDER_PEARL'));
			$session->addTimer('ender_pearl', '&3EnderPearl&r&7:&f', 15);
        } elseif ($item->getTypeId() === ItemTypeIds::SNOWBALL) {
			if ($session->getTimer('snowball') !== null) {
				$timer = $session->getTimer('snowball');
				$player->sendMessage(TextFormat::RED . "You can't use " . TextFormat::GOLD . "§9SNOWBALL " . TextFormat::RED . "because you have a cooldown of " . Utils::date($timer->getTime()));
				$event->cancel();
				return;
			}
            $player->sendMessage(TextFormat::colorize('&7[&6!&7] &a You just used a &9SNOWBALL'));
			$session->addTimer('snowball', '&bSnowBall&r&7:&f', 5);
        }
	}

	public function handleJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);
        
		$session?->join();

		$event->setJoinMessage(TextFormat::colorize('&7[&a+&7] &a' . $player->getName()));
	}

	public function handleLogin(PlayerLoginEvent $event) : void {
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);
        
        if (TimerFactory::get('eotw') !== null && TimerFactory::get('eotw')->isEnabled()) {
            if (!$player->hasPermission('eotw.skip') || !$player->getServer()->isOp($player)) {
                //$player->kick(TextFormat::colorize('&r&4'));
                $player->kick(TextFormat::colorize('&r&c YOU WERE KICKED OUT BY THE EOTW'));
                /*$player->kick(TextFormat::colorize('&r&7 Go to the next map: play.infernalmc.xyz '));
                $player->kick(TextFormat::colorize('&r&c'));*/
            }
            return;
        }

		if ($session === null) {
			SessionFactory::create($player);
		} else {
			if ($session->getRawUuid() !== $player->getUniqueId()->getBytes()) {
				$session->setRawUuid($player->getUniqueId()->getBytes());
			}
		}
	}
    
    public function handleMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);

        if ($session === null) {
            return;
        }
        
        if($event->getTo()->distanceSquared($event->getFrom()) >= (Entity::MOTION_THRESHOLD * Entity::MOTION_THRESHOLD) && ($waypoint = $session->getWayPoint()) instanceof WayPoint){
            $waypoint->update($player);
        }
    }

	public function handleQuit(PlayerQuitEvent $event) : void {
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);
		$session->quit();

		$event->setQuitMessage(TextFormat::colorize('&7[&c-&7] &c' . $player->getName()));
	}
	
}
