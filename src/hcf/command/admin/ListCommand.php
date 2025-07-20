<?php



declare(strict_types=1);

namespace hcf\command\admin;
use hcf\HCF;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;



class ListCommand extends Command
{
    public function __construct() {
		parent::__construct('player', 'command to see players on the server');
		$this->setPermission('player.command.use');
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        $sender->sendMessage(TextFormat::colorize('&7' . PHP_EOL . '&ePlayers playing: &f' . count($sender->getServer()->getOnlinePlayers()) . PHP_EOL . '&e&r'));
    }
}
