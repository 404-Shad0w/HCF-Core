<?php

namespace hcf\command\admin;

use hcf\util\Utils;
use hcf\entity\Dragon;
use hcf\entity\EndCrystalEntity;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\Command;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\particle\HugeExplodeParticle;

class BossCommand extends Command {
    public function __construct() {
		parent::__construct('boss', 'command to see players on the server');
		$this->setPermission('boss.command.use');
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        $config = Utils::getBossFile();
        if (!$this->testPermission($sender)) return;
        
        $lvl = ($srv = $sender->getServer())->getWorldManager()->getWorldByName($config->getNested("Dragon.world"));
        foreach($lvl->getEntities() as $ent) {
			if(!$ent instanceof EndCrystalEntity) continue;
			$ent->flagForDespawn();
		}
        $center = new Vector3(0, 0, 0);
        $cCount = 0;
        foreach($config->getNested("Dragon.crystals") as $pos => $cmds) {
			$_pos = Utils::stringToVector($pos);
			$crystal = new EndCrystalEntity(Location::fromObject($_pos->add(0.5, 1, 0.5), $lvl));
			$lvl->setBlock($_pos, VanillaBlocks::BEDROCK());
			$lvl->setBlock($_pos->add(0, 1, 0), VanillaBlocks::FIRE());
			$crystal->setShowBase();
			$crystal->setCanSaveWithChunk(false);
			$cCount++;
			$center = $center->addVector($_pos);
			$crystal->spawnToAll();
			$lvl->addParticle($_pos->add(0.5, 1, 0.5), new HugeExplodeParticle());
		}
        $e = new Dragon(Location::fromObject(new Vector3(0.5, $lvl->getHighestBlockAt(0, 0), 0.5), $lvl));
        if($cCount > 0) {
			$center = $center->divide($cCount);
			$e->setCenter($center);
		}
        $e->spawnToAll();
        
        foreach($config->getNested("Dragon.startCommands") as $command) {
            Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), $command);
        }
    }
}