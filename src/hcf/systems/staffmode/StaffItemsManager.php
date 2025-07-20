<?php

namespace hcf\systems\staffmode;

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
        
        $staff->sendMessage(str_replace('%p', $target->getName(), Messages::SET_UNFROZEN));
        $target->sendMessage(str_replace('%p', $staff->getName(), Messages::UNFROZEN));
    }
    
    public function isFreeze(Player $player): bool
    {
        return isset($this->freeze[$player->getName()]);
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
    }

    public function isVanish(Player $player): bool
    {
        return isset($this->vanish[$player->getName()]);
    }
}