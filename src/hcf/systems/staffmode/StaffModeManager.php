<?php

namespace hcf\systems\staffmode;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

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

    public function Staff(Player $staff)
    {
        if ($this->isStaff($staff)) {
            $this->removeStaff($staff);
        }else{
            $this->setStaff($staff);
        }
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
        $items = [];

        $compass = VanillaItems::COMPASS()->setCustomName("§r§aTP Compass");
        $compass->getNamedTag()->setString('staff_items', 'compass');
        $items[0] = $compass;

        $enderChest = VanillaBlocks::ENDER_CHEST()->asItem()->setCustomName("§r§aPlayer EnderInventory");
        $enderChest->getNamedTag()->setString('staff_items', 'enderchest');
        $items[1] = $enderChest;

        $chest = VanillaBlocks::CHEST()->asItem()->setCustomName("§r§aPlayer Inventory");
        $chest->getNamedTag()->setString('staff_items', 'inventory');
        $items[4] = $chest;

        $ice = VanillaBlocks::ICE()->asItem()->setCustomName("§r§aFreeze Player");
        $ice->getNamedTag()->setString('staff_items', 'freeze');
        $items[7] = $ice;

        $dye = VanillaItems::DYE()->setCustomName("§r§aVanish");
        $dye->getNamedTag()->setString('staff_items', 'vanish');
        $items[8] = $dye;

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