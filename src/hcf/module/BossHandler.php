<?php

namespace hcf\module;

use hcf\util\Utils;
use hcf\entity\Dragon;
use hcf\entity\EndCrystalEntity;
use hcf\session\SessionFactory;
use pocketmine\Server;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;

final class BossHandler implements Listener {
    private $mostAttacks = [];
    private $detectClickFor = null;
    
    public function onDamage(EntityDamageByEntityEvent $ev): void {
        $e = $ev->getEntity();
        $config = Utils::getConfig();
        if ($config->get("boss") === false) return;
        
        if(!$e instanceof Dragon) return;
		$dmg = $ev->getDamager();
		$amt = $ev->getFinalDamage();
		if(!$dmg instanceof Player) return;
        $session = SessionFactory::get($dmg);
        $fac = null;
        if ($session->getFaction() !== null) {
            $fac = $session->getFaction();
            if(!isset($this->mostAttacks[$e->getId()][$fac->getName()])) {
					$this->mostAttacks[$e->getId()][$fac->getName()] = 0;
			}
            $this->mostAttacks[$e->getId()][$fac->getName()] += $amt;
        }
        if($amt > $e->getHealth() && $e->isAlive()) {
            $e->kill();
			$mostAttacks = "N/A";
            if(isset($this->mostAttacks[$e->getId()])) {
                $_mostAttacks = array_search($amt = max($this->mostAttacks[$e->getId()]), $this->mostAttacks[$e->getId()]);
				if($_mostAttacks !== false) $mostAttacks = $_mostAttacks;
            }
            $module = Utils::getBossFile();
            foreach($module->getNested("Dragon.deathCommands") as $command) {
                Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), str_replace(["{faction}", "{damage}", "{last_damager}"], [$mostAttacks, $amt, $dmg->getName()], $command));
            }
            $level = $e->getWorld();
            /*$pos = Utils::stringToVector($module->getNested("Dragon.dragonEggSpawnPosition"));
            $level->setBlock($pos, VanillaBlocks::DRAGON_EGG());
			$level->addParticle($pos->add(0.5, 0.5, 0.5), new HugeExplodeSeedParticle());
			$level->getBlock($pos)->onNearbyBlockChange();*/
        }
    }
    
    public function onProjectileHitEntity(ProjectileHitEntityEvent $ev): void {
        $e = $ev->getEntityHit();
        $config = Utils::getConfig();
        if ($config->get("boss") === false) return;
        
        if(!$e instanceof EndCrystalEntity) return;
		$dmg = $ev->getEntity()->getOwningEntity();
		if(!$dmg instanceof Player) return;
        $pos = Utils::fromString($e->getPosition()->floor()->subtract(0, 1, 0), ':');
        $module = Utils::getBossFile();
        //$cmd = $module->getNested("Dragon.crystals.{$pos}");
        $session = SessionFactory::get($dmg);
        $faction = $session?->getFaction();
        
        foreach($module->getNested("Dragon.crystals.{$pos}") as $cmd) {
            Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), str_replace(["{faction}", "{player}"], [$faction?->getName(), $dmg->getName()], $cmd));
        }
    }
}