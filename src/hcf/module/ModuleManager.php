
<?php

namespace hcf\module;

use hcf\module\blockshop\BlockShopManager;
use hcf\module\lootbox\LootBoxManager;
use hcf\module\package\PackageManager;

class ModuleManager {

    public LootBoxManager $lootBoxManager;
    public PackageManager $packageManager;
    public BlockShopManager $blockShopManager;

    public function __construct(){
        $this->packageManager = new PackageManager;
        $this->lootBoxManager = new LootBoxManager;
        $this->blockShopManager = new BlockShopManager;
    }

    public function getPackageManager(): PackageManager {
        return $this->packageManager;
    }

    public function getBlockShopManager(): BlockShopManager {
        return $this->blockShopManager;
    }

    public function getLootBoxManager(): LootBoxManager {
        return $this->lootBoxManager;
    }
    
}