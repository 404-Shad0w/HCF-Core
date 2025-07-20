<?php

namespace hcf\systems\staffmode;

use hcf\systems\staffmode\Messages;
use pocketmine\player\Player;
use hcf\session\SessionFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;

class StaffModeManager
{
    private static ?StaffModeManager $instance = null;
    private array $staff = [];

    public static function getInstance(): StaffModeManager
    {
        if (self::$instance === null) {
            self::$instance = new StaffModeManager();
        }
        return self::$instance;
    }

    private function __construct()
    {
        self::$instance = $this;
    }

    public function setStaff(Player $player): void
    {
        if (!$player->hasPermission('staffmode')) {
            $player->sendMessage(Messages::NO_PERMISSION);
            return;
        }
        
        if ($this->isStaff($player)) {
            $player->sendMessage(Messages::ALREADY_IN_STAFF_MODE);
            return;
        }

        $this->staff[] = $player->getName();
        $player->sendMessage(Messages::ENTERED_STAFF_MODE);
    }

    public function removeStaff(Player $player): void
    {
        if (!$player->hasPermission('hcf.staffmode')) {
            $player->sendMessage(Messages::NO_PERMISSION);
            return;
        }

        if (!$this->isStaff($player)) {
            $player->sendMessage(Messages::NOT_IN_STAFF_MODE);
            return;
        }

        unset($this->staff[$player->getName()]);
        $player->sendMessage(Messages::LEFT_STAFF_MODE);
    }

    public function isStaff(Player $player): bool
    {
        return isset($this->staff[$player->getName()]);
    }
    
    public function getStaff(): array
    {
        return $this->staff;
    }

    public function Items(Player $player): void
    {
        $items = [
            0 => VanillaItems::COMPASS(),
            1 => VanillaItems::ENDER_CHEST(),
            4 => VanillaItems::CHEST(),
            7 => VanillaBlocks::ICE()->asItem(),
        ];

        foreach ($items as $slot => $item) {
            $player->getInventory()->setItem($slot, $item);
        }
    }

    public function getStaffList(): string
    {
        $staffs = $this->getStaff();
        if (empty($staffs)) {
            return Messages::LINES . "\n" . Messages::STAFF_LIST . "\n" . Messages::LINES;
        }

        $staffList = implode(", ", $staffs);
        return Messages::LINES . "\n" . str_replace('%staffs', $staffList, Messages::STAFF_LIST) . "\n" . Messages::LINES;
    }
}