<?php 

namespace hcf\item;   

use hcf\HCF;
use pocketmine\event\Listener;
use pocketmine\block\BlockTypeIds;
use pocketmine\item\ItemTypeIds;
use pocketmine\player\Player;
use pocketmine\nbt\tag\IntTag;
use pocketmine\item\Pickaxe;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\player\GameMode;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

class ItemHandler implements Listener {

  private $blocks = [];
  private $ids = [
    BlockTypeIds::COAL_ORE,
    BlockTypeIds::DIAMOND_ORE,
    BlockTypeIds::EMERALD_ORE,
    BlockTypeIds::GOLD_ORE,
    BlockTypeIds::LAPIS_LAZULI_ORE,
    BlockTypeIds::NETHER_QUARTZ_ORE,
    BlockTypeIds::IRON_ORE,
    BlockTypeIds::REDSTONE_ORE
  ];
  
  public function onPlayerInteract(PlayerItemUseEvent $event): void {
      $item = $event->getItem();
      $player = $event->getPlayer();
      $id = $item->getTypeId();
      
      if ($event->isCancelled()) return;
      
      if ($id === ItemTypeIds::EXPERIENCE_BOTTLE) {
          $xp = 0;
          for ($i = 0; $i <= $item->getCount(); ++$i) {
                $xp += mt_rand(6, 18);
          }
          $player->getXpManager()->addXp($xp);
          $player->getInventory()->clear($player->getInventory()->getHeldItemIndex());
          $event->cancel();
      }
  }  
    
  public function onBreakHandler(BlockBreakEvent $event): void {
    if ($event->isCancelled()) return;

    $item = $event->getItem();
    $player = $event->getPlayer();
    $block = $event->getBlock();
    if (!$player instanceof Player) return;

    $blockId = $block->getTypeId();
    $player->getXpManager()->addXp(intval($event->getXpDropAmount() * 2)); 
    $event->setXpDropAmount(0);
      
    if ($block->getTypeId() === BlockTypeIds::DIAMOND_ORE) {
        if (!isset($this->blocks[(string)$block->getPosition()])) {
            $count = 0;
            for ($x = $block->getPosition()->getX() - 4; $x <= $block->getPosition()->getX() + 4; $x++) {
                for ($z = $block->getPosition()->getZ() - 4; $z <= $block->getPosition()->getZ() + 4; $z++) {
                    for ($y = $block->getPosition()->getY() - 4; $y <= $block->getPosition()->getY() + 4; $y++) {
                        if ($player->getWorld()->getBlockAt($x, $y, $z)->getTypeId() === BlockTypeIds::DIAMOND_ORE) {
                            if (!isset($this->blocks[(string)new Vector3($x, $y, $z)])) {
                                $this->blocks[(string)new Vector3($x, $y, $z)] = true;
                                ++$count;
                            }
                        }
                    }
                }
            }
            $player->getServer()->broadcastMessage("§l§7[§3FD§7] - §r§5{$player->getName()} §ffound $count §fdiamonds.");
        }
    }
    if (!in_array($blockId, $this->ids)) return;

    if ($item instanceof Pickaxe) {
      switch ($blockId) {
        case BlockTypeIds::COAL_ORE:
          $tag = "Coal";
          break;
        case BlockTypeIds::REDSTONE_ORE:
          $tag = "Redstone";
          break;
        case BlockTypeIds::LAPIS_LAZULI_ORE:
          $tag = "Lapis";
          break;
        case BlockTypeIds::IRON_ORE:
          $tag = "Iron";
          break;
        case BlockTypeIds::GOLD_ORE:
          $tag = "Gold";
          break;
        case BlockTypeIds::DIAMOND_ORE:
          $tag = "Diamond";
          break;
        case BlockTypeIds::EMERALD_ORE:
          $tag = "Emerald";
          break;
        default:
          return;
      } 
      if ($item->getNamedTag()->getTag("custom") === null) {
        $nbt = $item->getNamedTag();
        $nbt->setTag("custom", new CompoundTag());
        $item->setNamedTag($nbt);
      }

      $compoundTag = $item->getNamedTag()->getTag("custom");
      if ($compoundTag->getTag($tag, IntTag::class) !== null) {
        $amount = $compoundTag->getInt($tag);
        $amount += 1;
        $compoundTag->setInt($tag, $amount);
      } else {
        if (!$compoundTag->getTag("Coal", IntTag::class) !== null) {
          $compoundTag->setInt("Coal", 0);
        }
        if (!$compoundTag->getTag("Redstone", IntTag::class) !== null) {
          $compoundTag->setInt("Redstone", 0);
        }
        if (!$compoundTag->getTag("Lapis", IntTag::class) !== null) {
          $compoundTag->setInt("Lapis", 0);
        }
        if (!$compoundTag->getTag("Gold", IntTag::class) !== null) {
          $compoundTag->setInt("Gold", 0);
        }
        if (!$compoundTag->getTag("Iron", IntTag::class) !== null) {
          $compoundTag->setInt("Iron", 0);
        }
        if (!$compoundTag->getTag("Diamond", IntTag::class) !== null) {
          $compoundTag->setInt("Diamond", 0);
        }
        if (!$compoundTag->getTag("Emerald", IntTag::class) !== null) {
          $compoundTag->setInt("Emerald", 0);
        }
        $compoundTag->setInt($tag, 1);
      }
      $oldLore = $item->getLore();
      $oldLore[2] = TextFormat::RESET.TextFormat::DARK_GRAY."Coal".TextFormat::WHITE.$compoundTag->getInt("Coal").PHP_EOL.TextFormat::RESET.TextFormat::RED."Redstone".TextFormat::WHITE.$compoundTag->getInt("Redstone").PHP_EOL.TextFormat::RESET.TextFormat::BLUE."Lapis".TextFormat::WHITE.$compoundTag->getInt("Lapis").PHP_EOL.TextFormat::RESET.TextFormat::GRAY."Iron".TextFormat::WHITE.$compoundTag->getInt("Iron").PHP_EOL.TextFormat::RESET.TextFormat::GOLD."Gold".TextFormat::WHITE.$compoundTag->getInt("Gold").PHP_EOL.TextFormat::RESET.TextFormat::AQUA."Diamond".TextFormat::WHITE.$compoundTag->getInt("Diamond").PHP_EOL.TextFormat::RESET.TextFormat::GREEN."Emerald".TextFormat::WHITE.$compoundTag->getInt("Emerald");
      $newLore = array_splice($oldLore, 0, 7, true);
      $event->getPlayer()->getInventory()->setItemInHand($item->setLore($newLore));
    }
  }

  public function onDeathHandler(PlayerDeathEvent $event): void {
    $entity = $event->getPlayer();

    if (!$entity instanceof Player) return;
      
    $cause = $entity->getLastDamageCause();
      
    if(!$cause instanceof EntityDamageByEntityEvent) return;
    
    $damager = $cause->getDamager();
      
    if(!$damager instanceof Player) return;

    $item = $damager->getInventory()->getItemInHand();
    $nbt = $item->getNamedTag();

    if ($nbt->getTag("kills") !== null) {
      $nbt->setInt("kills", $nbt->getTag("kills")->getValue() + 1);
    } else {
      $nbt->setInt("kills", 1);
    }
    $item->getNamedTag($nbt);
    $oldLore = $item->getLore();

    if ($item->getNamedTag()->getTag("kills") !== null) {
      $kills = $item->getNamedTag()->getInt("kills", 1);
    } else {
      $kills = 1;
    }

    $oldLore[2] = TextFormat::RED.$entity->getName().TextFormat::YELLOW." was slain by ".TextFormat::AQUA.$damager->getName();
    $newLore = array_splice($oldLore, 0, 7, true);
    $item->setLore($newLore);
    $damager->getInventory()->setItemInHand($item);
  }
}
