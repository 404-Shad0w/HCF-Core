<?php

namespace hcf\systems\staffmode\commands;

use hcf\systems\staffmode\StaffModeManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class StaffCommand extends Command
{
    public function __construct(){
        parent::__construct('staff', 'staff mode');
        $this->setPermission('staffmode.perms');
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $player, string $label, array $args)
    {
        if (!$player instanceof Player) return;

        StaffModeManager::getInstance()->Staff($player);
    }
}