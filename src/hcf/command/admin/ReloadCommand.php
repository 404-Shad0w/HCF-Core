<?php

namespace hcf\command\admin;

use hcf\util\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ReloadCommand extends Command {
    
    public function __construct() {
        parent::__construct('hcfreload', 'update config information');
		$this->setPermission('config.command.use');
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$this->testPermission($sender)) return;
        
        Utils::getConfig()->reload();
		$sender->sendMessage(TextFormat::GREEN . "Reloaded Core Config configuration file");
    }
}