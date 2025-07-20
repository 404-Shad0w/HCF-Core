<?php 
 #code By ClouderIDev   
 namespace hcf\util;

#uses HCF 
use hcf\session\SessionFactory;
use pocketmine\player\Player;
use base\player\Base;

#uses Pocketmine
use pocketmine\utils\TextFormat;
use pocketmine\item\ItemFactory;

#uses Virion InvMenu
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;

class UpgradeMenu {
    
    static function create(Player $player): void {
        $glass = ItemFactory::getInstance()->get(351, 13);
        $glass->setCustomName(TextFormat::colorize(" "));
        $speed = ItemFactory::getInstance()->get(353, 0);
        $speed->setCustomName(TextFormat::colorize("&r&3Speed II\n&bPrice: &a20000"));
        $resis = ItemFactory::getInstance()->get(265, 0);
        $resis->setCustomName(TextFormat::colorize("&r&7Resistance I\n&bPrice: &a20000"));
        $fuerza = ItemFactory::getInstance()->get(377, 0);
        $fuerza->setCustomName(TextFormat::colorize("&r&gStrength I\n&bPrice: &a20000"));
        $salto = ItemFactory::getInstance()->get(288, 0);
        $salto->setCustomName(TextFormat::colorize("&r&4Jump Boost II\n&bPrice: &a20000"));
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName("§aFaction Upgrade");
        $menu->getInventory()->setContents([
            0 => $glass,
            1 => $glass,
            2 => $glass,
            3 => $glass,
            4 => $glass,
            5 => $glass,
            6 => $glass,
            7 => $glass,
            8 => $glass,
            9 => $glass,
            17 => $glass,
            18 => $glass,
            19 => $glass,
            20 => $glass,
            21 => $glass,
            22 => $glass,
            23 => $glass,
            24 => $glass,
            25 => $glass,
            26 => $glass,
            
            10 => $speed,
            12 => $resis,
            14 => $fuerza,
            16 => $salto
        ]);
        $menu->setListener(function (InvMenuTransaction $transaction): InvMenuTransactionResult {
            $player = $transaction->getPlayer();
            if ($transaction->getItemClicked()->getCustomName() === "§r§3Speed II\n§bPrice: §a20000") {
                $session = SessionFactory::get($player);
                $balance = $session->getBalance();
                $data = Base::getData($player->getName());
                if ($data->get("FacSpeed", true)) {
                $player->sendMessage(TextFormat::colorize("&cThis can only be used once."));
                return $transaction->discard();
            }
                if($balance < 20000){
                    $player->sendMessage(TextFormat::colorize("&cYou don't have enough money."));
                    return $transaction->discard();
                }
                if ($balance >= 20000) {
                    $session->setBalance($session->getBalance() - 20000);
                    $data->set("FacSpeed", true);
                    $data->save();
                    $player->sendMessage(TextFormat::colorize("&aUpgrade placed on your Faction correctly"));
                }
            }
            if ($transaction->getItemClicked()->getCustomName() === "§r§7Resistance I\n§bPrice: §a20000") {
                $session = SessionFactory::get($player);
                $balance = $session->getBalance();
                $data = Base::getData($player->getName());
                if ($data->get("FacResis", true)) {
                $player->sendMessage(TextFormat::colorize("&cThis can only be used once."));
                return $transaction->discard();
                }
                if($balance < 20000){
                    $player->sendMessage(TextFormat::colorize("&cYou don't have enough money."));
                    return $transaction->discard();
                }
                if ($balance >= 20000) {
                    $session->setBalance($session->getBalance() - 20000);
                    $data->set("FacResis", true);
                    $data->save();
                    $player->sendMessage(TextFormat::colorize("&aUpgrade placed on your Faction correctly"));
                }
            }
            if ($transaction->getItemClicked()->getCustomName() === "§r§gStrength I\n§bPrice: §a20000") {
                $session = SessionFactory::get($player);
                $balance = $session->getBalance();
                $data = Base::getData($player->getName());
                if ($data->get("FacFuerza", true)) {               
                    $player->sendMessage(TextFormat::colorize("&cThis can only be used once."));
                return $transaction->discard();
                }
                if($balance < 20000){
                    $player->sendMessage(TextFormat::colorize("&cYou don't have enough money."));
                    return $transaction->discard();
                }
                if ($balance >= 20000) {
                    $session->setBalance($session->getBalance() - 20000);
                    $data->set("FacFuerza", true);
                    $data->save();
                    $player->sendMessage(TextFormat::colorize("&aUpgrade placed on your Faction correctly"));
                }
            }
            if ($transaction->getItemClicked()->getCustomName() === "§r§4Jump Boost II\n§bPrice: §a20000") {
                $session = SessionFactory::get($player);
                $balance = $session->getBalance();
                $data = Base::getData($player->getName());
                if ($data->get("FacSalto", true)) {               
                    $player->sendMessage(TextFormat::colorize("&cThis can only be used once."));
                return $transaction->discard();
                }
                if($balance < 20000){
                    $player->sendMessage(TextFormat::colorize("&cYou don't have enough money."));
                    return $transaction->discard();
                }
                if ($balance >= 20000) {
                    $session->setBalance($session->getBalance() - 20000);
                    $data->set("FacSalto", true);
                    $data->save();
                    $player->sendMessage(TextFormat::colorize("&aUpgrade placed on your Faction correctly"));
                }
            }
            return $transaction->discard();
        });
        $menu->send($player);
    }
    
}
    