<?php

namespace hcf\systems\staffmode;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;

class StaffModeEvents implements Listener
{
    public function onDamage(EntityDamageByEntityEvent $event): void
    {
        $damager = $event->getDamager();
        $target = $event->getEntity();
        if (!$damager instanceof Player || !$target instanceof Player) {
            return;
        }

        if (StaffModeManager::getInstance()->isStaff($damager)) {
            $event->cancel();
        }

        if (StaffItemsManager::getInstance()->isFreeze($damager)) {
            $event->cancel();
            $damager->sendMessage(Messages::ALREADY_FROZEN);
        }

        $damagerItems = $damager->getInventory()->getItemInHand();
        $ItemsTag = $damagerItems->getNamedTag()->getString("staff_items", "");

        match (strtolower($ItemsTag)) {
            'freeze' => StaffItemsManager::getInstance()->Freeze($damager, $target),
            default => null,
        };
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof Player) {
            return;
        }

        if (StaffModeManager::getInstance()->isStaff($player)) {
            $item = $player->getInventory()->getItemInHand();
            $itemTag = $item->getNamedTag()->getString("staff_items", "");

            match (strtolower($itemTag)) {
                'vanish' => StaffItemsManager::getInstance()->Vanish($player),
                default => null,
            };
        }
    }
}