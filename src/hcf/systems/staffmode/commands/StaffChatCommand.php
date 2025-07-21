<?php

namespace hcf\systems\staffmode\commands;

use hcf\systems\staffmode\StaffModeManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class StaffChatCommand extends Command
{
    public function __construct()
    {
        parent::__construct('staffchat', 'staff chat');
        $this->setPermission('staffmode.perms');
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $player, string $label, array $args)
    {
        if (!$player instanceof Player) return;

        StaffModeManager::getInstance()->staffChat($player);
    }
}