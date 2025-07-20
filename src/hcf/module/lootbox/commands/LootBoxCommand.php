<?php

namespace hcf\module\lootbox\commands;

use hcf\HCFLoader;
use hcf\item\default\LootBox;
use hcf\item\default\PartnerPackage;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat as TE;
use pocketmine\player\Player;

class LootBoxCommand extends Command
{

    /**
     * ParterPackagesCommand constructor.
     */
    public function __construct()
    {
        parent::__construct('lootbox', 'lootbox commands');
        $this->setPermission('pkg.command');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (count($args) === 0) {
            $sender->sendMessage(
                TE::GRAY . ("----------------------------------------------------------\n") .
                TE::colorize("\n") .
                TE::BLUE . "/lootbox - " . TE::WHITE . ("use this command to get plugin information\n") .
                TE::BLUE . "/lootbox give [all/player] [amount] - " . TE::WHITE . ("uste this command to give lootbox\n") .
                TE::BLUE . "/lootbox editcontent-" . TE::WHITE . ("use this command to edit the lootbox content\n") .
                TE::colorize("\n") .
                TE::GRAY . ("----------------------------------------------------------\n")
            );
            return;
        }
        
        switch ($args[0]) {
            case "editcontent":
                if (!$sender->hasPermission("pkg.command")) {
                    $sender->sendMessage(TE::RED . "You don't have permissions");
                    return;
                }
                
                if (!$sender instanceof Player) {
                    $sender->sendMessage(TE::RED . "This message can only be executed in game!");
                    return;
                }
                $player = HCFLoader::getInstance()->getServer()->getPlayerExact($sender->getName());
                $items = [];
                for($i = 0; $i == 8; $i++)
                    $items[$i] = $sender->getInventory()->getItem($i);
                HCFLoader::getInstance()->getModuleManager()->getLootBoxManager()->setItems($items);
                $sender->sendMessage(TE::GREEN . "The content has been edited correctly");
                break;
                
            case "give":
                if (!$sender->hasPermission("pkg.command")) {
                    $sender->sendMessage(TE::RED . "You don't have permissions");
                    return;
                }
                if (empty($args[1])) {
                    $sender->sendMessage(TE::RED . "/lootbox give [all/player] [amount]");
                    return;
                }
                if (empty($args[2])) {
                    $sender->sendMessage(TE::RED . "/lootbox give [all/player] [amount]");
                    return;
                }
                $player = HCFLoader::getInstance()->getServer()->getPlayerExact($args[1]);
                if ($player !== null) {
                    $player->sendMessage(TE::colorize(HCFLoader::getInstance()->getConfig()->get('prefix'))."§7You have received §d§l" . $args[2] . " §r§7LootBox for §d§l" . $sender->getName());
                    LootBox::add($player, $args[2]);
                    return;
                }
                foreach (HCFLoader::getInstance()->getServer()->getOnlinePlayers() as $player) {
                    LootBox::add($player, $args[2]);
                }
                HCFLoader::getInstance()->getServer()->broadcastMessage(TE::colorize(HCFLoader::getInstance()->getConfig()->get('prefix'))."§7All online players have received §d§l" . $args[2] . " §r§7LootBox for §d§l" . $sender->getName());
                break;
        }
    }
}