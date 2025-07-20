<?php 

namespace hcf\command\admin;

use hcf\HCF;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class TpAllCommand extends Command {
    
    public function __construct() {
		parent::__construct('tpall', 'command to teleport all players to you');
		$this->setPermission('tpall.command.use');
	}
    
    public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
        if (!$sender instanceof Player) return;
        
        if (!$this->testPermission($sender)) return;
        
        foreach(HCF::getInstance()->getServer()->getOnlinePlayers() as $players){
				$players->teleport($sender->getLocation());
		}
        $sender->sendMessage(TextFormat::colorize('&r&7[&c!&7] &aAll the players went to you'));
    }    
}


