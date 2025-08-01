<?php

namespace hcf\systems\staffmode;

use muqsit\invmenu\InvMenu;
use pocketmine\player\Player;

class StaffItemsManager
{
    private static ?StaffItemsManager $instance = null;
    private array $freeze = [];
    private array $vanish = [];
    
    public static function getInstance(): StaffItemsManager
    {
        if (self::$instance === null) {
            self::$instance = new StaffItemsManager();
        }
        return self::$instance;
    }

    private function __construct()
    {
        self::$instance = $this;
    }

    public function Freeze(Player $staff, Player $target): void
    {
        if (!$this->isFreeze($target)) {
            $this->setFreeze($staff, $target);
        } else {
            $this->removeFreeze($staff, $target);
        }
    }

    public function setFreeze(Player $staff, Player $target): void
    {
        if (!$staff->hasPermission('staffmode.items')) {
            $staff->sendMessage(Messages::NO_PERMISSION);
            return;
        }

        if ($this->isFreeze($target)) {
            $staff->sendMessage(Messages::ALREADY_FROZEN);
            return;
        }

        $this->freeze[$target->getName()] = true;
        $target->sendMessage(Messages::ALREADY_FROZEN);
        $staff->sendMessage(str_replace('%p', $target->getName(), Messages::SET_FROZEN));
    }
    
    public function removeFreeze(Player $staff, Player $target): void
    {
        if (!$staff->hasPermission('staffmode.items')) {
            $staff->sendMessage(Messages::NO_PERMISSION);
            return;
        }
        
        if (!$this->isFreeze($target)) {
            $staff->sendMessage(Messages::NOT_IN_FROZEN);
            return;
        }
        
        unset($this->freeze[$target->getName()]);
        $staff->sendMessage(str_replace('%p', $target->getName(), Messages::SET_UNFROZEN));
        $target->sendMessage(str_replace('%p', $staff->getName(), Messages::UNFROZEN));
    }
    
    public function isFreeze(Player $player): bool
    {
        return isset($this->freeze[$player->getName()]);
    }

    public function Vanish(Player $staff): void
    {
        if (!$this->isVanish($staff)){
            $this->setVanish($staff);
        }else{
            $this->removeVanish($staff);
        }
    }

    public function setVanish(Player $staff): void
    {
        if (!$staff->hasPermission('staffmode.items')) {
            $staff->sendMessage(Messages::NO_PERMISSION_ITEMS);
            return;
        }

        if ($this->isVanish($staff)) {
            $staff->sendMessage(Messages::SET_UNVANISH);
            unset($this->vanish[$staff->getName()]);
        } else {
            $this->vanish[$staff->getName()] = true;
            $staff->sendMessage(Messages::SET_VANISH);
            foreach ($staff->getServer()->getOnlinePlayers() as $player) {
                if ($player !== $staff) {
                    $player->hidePlayer($staff);
                }
            }
        }
    }

    public function removeVanish(Player $staff): void
    {
        if (!$staff->hasPermission('staffmode.items')) {
            $staff->sendMessage(Messages::NO_PERMISSION_ITEMS);
            return;
        }

        if (!$this->isVanish($staff)) {
            $staff->sendMessage(Messages::SET_UNVANISH);
            return;
        }

        unset($this->vanish[$staff->getName()]);
        $staff->sendMessage(Messages::SET_UNVANISH);
        foreach ($staff->getServer()->getOnlinePlayers() as $player) {
            if ($player !== $staff) {
                $player->showPlayer($staff);
            }
        }
    }

    public function PlayerInventory(Player $staff, Player $target) : void {
        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);

        $items = $target->getInventory()->getContents();
        $armor = $target->getArmorInventory()->getContents();

        foreach ($items as $item) {
            $menu->getInventory()->addItem($item);
        }

        foreach ($armor as $item) {
            $menu->getInventory()->addItem($item);
        }

        $menu->send($staff, $target->getName()."'s Inventory");
    }

    public function PlayerEnderInventory(Player $staff, Player $target) : void {
        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);

        $items = $target->getEnderInventory()->getContents();

        foreach ($items as $item) {
            $menu->getInventory()->addItem($item);
        }
        $menu->send($staff, $target->getName()."'s Ender Inventory");
    }

    public function tpaPlayer(Player $staff) : void
    {
        foreach ($staff->getServer()->getOnlinePlayers() as $player) {
            if ($player !== $staff) {
                $staff->teleport($player->getPosition());
                $staff->sendMessage(str_replace('%p', $player->getName(), Messages::TELEPORT_STAFF));
            }
        }
    }

    public function isVanish(Player $player): bool
    {
        return isset($this->vanish[$player->getName()]);
    }

    public function getFreeze(): array
    {
        return $this->freeze;
    }

    public function getVanish(): array
    {
        return $this->vanish;
    }
}