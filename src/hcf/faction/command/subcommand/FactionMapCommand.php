<?php

namespace hcf\faction\command\subcommand;

use hcf\claim\ClaimFactory;
use hcf\faction\FactionFactory;
use hcf\faction\command\FactionSubcommand;
use hcf\session\SessionFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\world\Position;
use pocketmine\Server;

final class FactionMapCommand extends FactionSubcommand {
    
    public function __construct() {
        parent::__construct('map', 'Use this command to see your Faction limits.');
    }
    
    public function execute(CommandSender $sender, array $args) : void {
        
        if (!$sender instanceof Player) return;
        
        $session = SessionFactory::get($sender);
        
        if ($session === null) return;
        
        $faction = $session->getFaction();
        
        if ($faction === null) {
            $sender->sendMessage(TextFormat::colorize('&cYou must have a faction created to use this command.'));
            return;
        }
        
      foreach(FactionFactory::getAll() as $faction) {
        $claim = ClaimFactory::get($faction->getName());
        
        if ($claim === null) {
            //$sender->sendMessage(TextFormat::colorize('&cYou can\'t use this command because you don\'t have a claim.'));
            return;
        }
        
        $x = $claim->getMaxX();
        $z = $claim->getMaxZ();
        if ($x === null||$z === null) {
            $sender->sendMessage(TextFormat::colorize('&cYou dont have a claim.'));
            return;
        }
        for ($y = $sender->getPosition()->getFloorY(); $y <= 127; $y++) {
            $sender->getNetworkSession()->sendDataPacket($this->sendFakeBlock(new Position($x, $y, $z, $sender->getWorld()), $y % 3 === 0 ? VanillaBlocks::GOLD() : VanillaBlocks::GLASS()));
        }
        
        $x = $claim->getMinX();
        $z = $claim->getMinZ();
        for ($y = $sender->getPosition()->getFloorY(); $y <= 127; $y++) {
            $sender->getNetworkSession()->sendDataPacket($this->sendFakeBlock(new Position($x, $y, $z, $sender->getWorld()), $y % 3 === 0 ? VanillaBlocks::GOLD() : VanillaBlocks::GLASS()));
        }
        
        $x = $claim->getMinX();
        $z = $claim->getMaxZ();
        for ($y = $sender->getPosition()->getFloorY(); $y <= 127; $y++) {
            $sender->getNetworkSession()->sendDataPacket($this->sendFakeBlock(new Position($x, $y, $z, $sender->getWorld()), $y % 3 === 0 ? VanillaBlocks::GOLD() : VanillaBlocks::GLASS()));
        }
        
        $x = $claim->getMaxX();
        $z = $claim->getMinZ();
        for ($y = $sender->getPosition()->getFloorY(); $y <= 127; $y++) {
            $sender->getNetworkSession()->sendDataPacket($this->sendFakeBlock(new Position($x, $y, $z, $sender->getWorld()), $y % 3 === 0 ? VanillaBlocks::GOLD() : VanillaBlocks::GLASS()));
        }
      }
      $sender->getWorld()->addSound($sender->getPosition()->asVector3(), new XpCollectSound(), [$sender]);
    }
    
    private function sendFakeBlock(Position $position, Block $block): UpdateBlockPacket {
        $pos = BlockPosition::fromVector3($position->asVector3());
        $id = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($block->getStateId());
        $pk = UpdateBlockPacket::create($pos, $id, UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL);
        return $pk;
    }
}