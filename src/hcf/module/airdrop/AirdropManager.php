<?php

namespace hcf\module\airdrop;

use hcf\HCF;
use hcf\module\airdrop\commands\AirdropCommand;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class AirdropManager
{
    public static $instance;
    public Config $config;


    private function __construct()
    {
        HCF::getInstance()->getServer()->getCommandMap()->register("airdrop", new AirdropCommand());
        HCF::getInstance()->getServer()->getPluginManager()->registerEvents(new AirdropListener(), HCF::getInstance());
        $this->config = new Config(HCF::getInstance()->getDataFolder(). "airdrops.json", Config::JSON);
        $this->init();
    }
    public function init(): void
    {
        if ($this->config->get('items') !== null) {
            self::$instance = new Airdrop($this->config->get('items'));
        }
    }

    public static function addAirdrop(Player $player, int $count = 1): void
    {
        $contents = [];
        $crateItems = AirdropManager::getAirdrop()->getItems();
        $ItemNames = [];

        foreach ($crateItems as $item) {
            $name = trim($item->getName());
            if ($name !== '') {
                $ItemNames[] = $name;
            }
        }

        $airdrop = VanillaBlocks::CHEST()->asItem();
        $airdrop->setCustomName("§l§3Airdrop");
        $airdrop->setCount($count);
        $lore = implode("\n", array_map([TextFormat::class, 'colorize'], $ItemNames));
        $airdrop->setLore([$lore]);
        $airdrop->getNamedTag()->setString("Airdrop_Item", "Airdrop");
        $player->getInventory()->addItem($airdrop);
    }

    public static function getAirdrop(): Airdrop {
        return self::$instance;
    }
}