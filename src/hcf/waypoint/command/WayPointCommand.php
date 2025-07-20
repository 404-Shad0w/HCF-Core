<?php

namespace hcf\waypoint\command;    
  
use pocketmine\command\Command;

final class WayPointCommand extends Command {} 
        public function __construct() {
		parent::__construct('waypoint', 'Command for waypoints');
		$this->setPermission('waypoint.command');
        }

        public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}

		if (!$this->testPermission($sender)) {
			return;
		}
		$session = SessionFactory::get($sender);

		if ($session === null) {
			return;
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /waypoint [create|delete|list]'));
			return;
		}
	}
