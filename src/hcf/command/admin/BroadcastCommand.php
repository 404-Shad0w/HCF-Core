<?php 

namespace hcf\command\admin;

use hcf\HCF;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BroadcastCommand extends Command {
    
    public function __construct() {
		parent::__construct('broadcast', 'command to send an announcement to the entire server');
		$this->setPermission('broadcast.command.use');
	}
    
    public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
        
        if (!$this->testPermission($sender)) return;
        
        $message = "";
        for($i = 0; $i < count($args); $i++){
			$message .= $args[$i];
			$message .= " ";
		}
        $message = substr($message, 0, strlen($message) - 1);
        
        if (empty($message)) {
            $sender->sendMessage(TextFormat::colorize('&cUse /broadcast {message}'));
            return;
        }
        
        $sender->getServer()->broadcastMessage(TextFormat::colorize($message));
    }    
}


