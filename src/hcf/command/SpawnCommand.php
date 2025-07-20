<?php

namespace HCF\command;

use hcf\{HCF, session\SessionFactory, claim\Claim};
use pocketmine\player\Player;
use hcf\timer\TimerFactory;

use pocketmine\utils\TextFormat as TE;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\permission\DefaultPermissions;

class SpawnCommand extends Command {
	
	/**
	 * SpawnCommand Constructor.
	 */
	public function __construct(){
		parent::__construct('spawn', 'use this command to return to spawn');
		$this->setPermission('spawn.command');
	}
	
	/**
	 * @param CommandSender $sender
	 * @param String $label
	 * @param Array $args
     * @return void
	 */
	public function execute(CommandSender $sender, String $label, Array $args) : void {
        if(HCF::getInstance()->getServer()->isOp($sender->getName())){
            $sender->teleport(HCF::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
            return;
        }
        $session = SessionFactory::get($sender);
        $currentClaim = $session->getCurrentClaim();
        
		if($currentClaim !== null && $currentClaim->getType() === Claim::SPAWN){
			$sender->teleport(HCF::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
			return;
		}
		if(TimerFactory::get('SOTW') !== null && TimerFactory::get('SOTW')->isEnabled()){
            $sender->teleport(HCF::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
            //return;
		} else {
			$sender->sendMessage("".TE::RED."You can only use this Command in spawn or sotw");
            //return;
		}
        /*if(empty($args)){
			$sender->teleport(HCF::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
			return;
		}
		$player = HCF::getInstance()->getServer()->getPlayerExact($args[0]);
		if($player instanceof Player){
			$player->teleport(HCF::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
		}*/
	}
}

?>
