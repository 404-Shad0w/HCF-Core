<?php

namespace hcf\systems\ranks;

use hcf\HCF;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\chat\LegacyRawChatFormatter;
use hcf\session\SessionFactory;
use pocketmine\utils\TextFormat;

class RankEvents implements Listener
{

    public function onPlayerChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $rankManager = HCF::getInstance()->getRankManager();
        $rank = $rankManager->getPlayerRank($player);
        
        $factionName = "";
        $session = SessionFactory::get($player);
        if ($session !== null && $session->getFaction() !== null) {
            $faction = $session->getFaction();
            $factionName = TextFormat::WHITE . "[" . $faction->getName() . "]";
        }
        
        $event->setFormatter(new LegacyRawChatFormatter(
            $factionName .
            ($factionName !== "" ? " " : "") .
            ($rank->getPrefix() !== "" ? "" : $rank->getPrefix()). " " .
            TextFormat::WHITE . $player->getName() .
            TextFormat::GRAY . ": " .
            TextFormat::WHITE . $event->getMessage()
        ));
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $rankManager = HCF::getInstance()->getRankManager();
        $rankManager->getPlayerRank($player);
    }
}