<?php

namespace hcf\systems\staffmode\commands;

use hcf\systems\staffmode\Messages;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;

class StaffTpCommand extends Command
{
    public function __construct()
    {
        parent::__construct('tp', 'teleport to a player');
        $this->setPermission('staffmode.perms');
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $player, string $label, array $args)
    {
        if (!$player instanceof Player) return;

        $target = Server::getInstance()->getPlayerExact($player->getName());
        if ($target === null || !$target->isOnline()) return;

        $player->teleport($target->getPosition());
    }
}