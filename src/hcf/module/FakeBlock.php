<?php


namespace hcf\module;

use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;

final class FakeBlock {
	public static function showBlock(Player $player, Block $block, Vector3 $pos, bool $immediate = false) {
		$player->getNetworkSession()->sendDataPacket(self::showBlockPacket($block, $pos), $immediate);
	}

	public static function showBlockPacket(Block $block, Vector3 $pos) {
		return UpdateBlockPacket::create(
			new BlockPosition($pos->x, $pos->y, $pos->z),
            TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($block->getStateId()),
			UpdateBlockPacket::FLAG_NETWORK,
			UpdateBlockPacket::DATA_LAYER_NORMAL,
		);
	}
}