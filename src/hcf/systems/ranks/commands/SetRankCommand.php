<?php

namespace hcf\systems\ranks\commands;

use hcf\HCF;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class SetRankCommand extends Command
{

    public function __construct()
    {
        parent::__construct("setrank", "Establece el rango de un jugador", "/setrank <jugador> <rango> [duracion]", ["sr"]);
        $this->setPermission("hcf.command.setrank");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "Este comando solo puede ser usado por un jugador.");
            return true;
        }

        if (!$this->testPermission($sender)) {
            return true;
        }

        if (count($args) < 2) {
            $sender->sendMessage(TextFormat::RED . $this->getUsage());
            return true;
        }

        $targetName = array_shift($args);
        $rankName = array_shift($args);
        $duration = implode(" ", $args); // Remaining args for duration

        $target = HCF::getInstance()->getServer()->getPlayerExact($targetName);
        if (!$target instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "El jugador '" . $targetName . "' no está en línea.");
            return true;
        }

        $rankManager = HCF::getInstance()->getRankManager();
        $rank = $rankManager->getRank($rankName);

        if ($rank === null) {
            $sender->sendMessage(TextFormat::RED . "El rango '" . $rankName . "' no existe.");
            return true;
        }

        $rankManager->setPlayerRank($target, $rank, $duration);
        $sender->sendMessage(TextFormat::GREEN . "Has establecido el rango " . $rank->getName() . " a " . $target->getName() . (!empty($duration) ? " por " . $duration : " permanentemente") . ".");
        $target->sendMessage(TextFormat::GREEN . "Tu rango ha sido establecido a " . $rank->getName() . (!empty($duration) ? " por " . $duration : " permanentemente") . ".");

        return true;
    }
}