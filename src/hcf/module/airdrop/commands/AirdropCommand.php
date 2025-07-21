<?php

namespace hcf\module\airdrop\commands;

use hcf\HCF;
use hcf\module\airdrop\Airdrop;
use hcf\module\airdrop\AirdropManager;
use hcf\module\package\PackageManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TE;

class AirdropCommand extends Command
{
    public function __construct(){
        parent::__construct('airdrop', 'airdrop commands');
        $this->setPermission('airdrop cmds');
    }
    
    public function execute(CommandSender $player, string $label, array $args)
    {
        if (count($args) === 0) {
            $player->sendMessage(
                TE::GRAY . ("----------------------------------------------------------\n") .
                TE::colorize("\n") .
                TE::BLUE . "/pkg - " . TE::WHITE . ("use this command to get plugin information\n") .
                TE::BLUE . "/pkg give [all/player] [amount] - " . TE::WHITE . ("uste this command to give pkg\n") .
                TE::BLUE . "/pkg editcontent-" . TE::WHITE . ("use this command to edit the pkg content\n") .
                TE::colorize("\n") .
                TE::GRAY . ("----------------------------------------------------------\n")
            );
            return;
        }

        switch ($args[0]) {
            case "editcontent":
                if (!$player->hasPermission("airdrop.cmds")) {
                    $player->sendMessage(TE::RED . "You don't have permissions");
                    return;
                }

                if (!$player instanceof Player) {
                    $player->sendMessage(TE::RED . "This message can only be executed in game!");
                    return;
                }
                $player = HCF::getInstance()->getServer()->getPlayerExact($player->getName());
                AirdropManager::getAirdrop()->setItems($player->getInventory()->getContents());
                $player->sendMessage(TE::GREEN . "The content has been edited correctly");
                break;

            case "give":
                if (!$player->hasPermission("airdrop.cmds")) {
                    $player->sendMessage(TE::RED . "You don't have permissions");
                    return;
                }
                if (empty($args[1])) {
                    $player->sendMessage(TE::RED . "/pkg give [all/player] [amount]");
                    return;
                }
                if (empty($args[2])) {
                    $player->sendMessage(TE::RED . "/pkg give [all/player] [amount]");
                    return;
                }
                $player = HCF::getInstance()->getServer()->getPlayerExact($args[1]);
                if ($player !== null) {
                    $player->sendMessage(TE::colorize(HCF::getInstance()->getConfig()->get('prefix'))."§7You have received §d§l" . $args[2] . " §r§7PartnerPackages for §d§l" . $player->getName());
                    AirdropManager::addAirdrop($player, $args[2]);
                    return;
                }
                foreach (HCF::getInstance()->getServer()->getOnlinePlayers() as $player) {
                    AirdropManager::addAirdrop($player, $args[2]);
                }
                HCF::getInstance()->getServer()->broadcastMessage(TE::colorize(HCF::getInstance()->getConfig()->get('prefix'))."§7All online players have received §d§l" . $args[2] . " §r§7PartnerPackages for §d§l" . $player->getName());
                break;
                }
    }
}