<?php

namespace hcf\faction\handler;

use hcf\session\SessionFactory;
use base\player\Base;

use pocketmine\entity\effect\{Effect, EffectInstance};
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;

final class UpgradeHandler implements Listener {
    
    public function upgradeMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);
        $faction = $session->getFaction();
        $data = Base::getData($player->getName());
        
        if ($faction === null) return;
        
        $currentClaim = $session->getCurrentClaim();
        
        if ($currentClaim === null || $currentClaim->getDefaultName() !== $faction->getName()) return;
        
        if ($data->get("FacSpeed", true)) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 20 * 3, 1));
        }
        
        if ($data->get("FacResis", true)) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), 20 * 3, 0));
        }
        
        if ($data->get("FacFuerza", true)) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), 20 * 3, 0));
        }
        
        if ($data->get("FacSalto", true)) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 20 * 3, 1));
        }
    }
    
}