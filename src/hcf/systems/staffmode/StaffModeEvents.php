<?php

namespace hcf\systems\staffmode;

use pocketmine\event\block\BlockItemPickupEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\Server;

class StaffModeEvents implements Listener
{
    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();

        foreach (StaffItemsManager::getInstance()->getVanish() as $staffs){
            $staff = Server::getInstance()->getPlayerExact($staffs);
            if ($staff === null) return;
            $player->hidePlayer($staff);
        }
    }
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
        
        if (StaffItemsManager::getInstance()->isFreeze($target)) {
            $event->cancel();
            $damager->sendMessage(str_replace('%p', $target->getName(), Messages::TARGET_FROZEN));
            return;
        }

        if (StaffItemsManager::getInstance()->isFreeze($damager)) {
            $event->cancel();
            $damager->sendMessage(Messages::YOU_ARE_FROZEN);
            return;
        }

        $damagerItems = $damager->getInventory()->getItemInHand();
        $ItemsTag = $damagerItems->getNamedTag()->getString("staff_items", "");

        match (strtolower($ItemsTag)) {
            'freeze' => StaffItemsManager::getInstance()->Freeze($damager, $target),
            'enderchest' => StaffItemsManager::getInstance()->PlayerEnderInventory($damager, $target),
            'inventory' => StaffItemsManager::getInstance()->PlayerInventory($damager, $target),
            default => null,
        };
    }

    public function onItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();

        if (!$player instanceof Player) return;

        if (StaffModeManager::getInstance()->isStaff($player)) {
            $item = $player->getInventory()->getItemInHand();
            $itemTag = $item->getNamedTag()->getString("staff_items", "");

            match (strtolower($itemTag)) {
                'vanish' => StaffItemsManager::getInstance()->Vanish($player),
                'compass' => StaffItemsManager::getInstance()->tpaPlayer($player),
                default => null,
            };
        }
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();

        if (StaffModeManager::getInstance()->isStaffChat($player)) {
            $event->cancel();
            $message = $event->getMessage();
            $msg = str_replace(['%staff%', '%message'], [$player->getName(), $message], Messages::STAFF_CHAT);
            
            foreach (StaffModeManager::getInstance()->getStaff() as $staffName) {
                $staffPlayer = Server::getInstance()->getPlayerExact($staffName);
                if ($staffPlayer !== null) {
                    $staffPlayer->sendMessage($msg);
                }
            }
        }
    }

    public function onPickup(BlockItemPickupEvent $event): void
    {
        $item = $event->getItem();
        $player = $event->getOrigin();

        if (!$player instanceof Player) return;
        if (StaffModeManager::getInstance()->isStaff($player)) {
            $event->cancel();
        }
    }

    public function onPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        if (StaffModeManager::getInstance()->isStaff($player)) {
            $event->cancel();
        }
    }
}