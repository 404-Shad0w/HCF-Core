<?php
namespace hcf\module\lootbox;

use hcf\HCFLoader;
use hcf\module\lootbox\task\LBT;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use pocketmine\entity\Location;
use pocketmine\Server;
use pocketmine\world\particle\CriticalParticle;
use hcf\module\lootbox\entity\FloatingEntity;
use hcf\module\lootbox\entity\TestEntity;

class LootBoxRun
{
	/** @var int $ticks */
	private int $ticks = 0;

	private int $deployMoreEntitys = 1;

	/** @var int $swap */
	private int $swap = 0;

	/** @var array $entitys */
	private array $entities = [];

	/** @var FloatingEntity $floatingText */
	public FloatingEntity $floatingText;

	public function __construct(
		private Player $player,
		private Location $location,
		private bool $enabled = true,
	)
	{
		$entity = new TestEntity($this->location, $this->location);
		$entity->setItemInHand(VanillaItems::APPLE());
		$entity->setPose();
		$entity->spawnTo($this->player);
		$entity->getNetworkProperties()->setInt(EntityMetadataProperties::ARMOR_STAND_POSE_INDEX, 2);
		$this->entities[0] = $entity;
		$this->floatingText = new FloatingEntity(new Location($this->location->getX(),$this->location->getY() + 1, $this->location->getZ(),$this->location->getWorld(),0,0,0));
		$this->floatingText->setNameTag("§r§a§lSpring Mystery Box \n \n §r§ePurchased From §r§dstore.vipermc.net");
		$this->floatingText->spawnToAll();
		HCFLoader::getInstance()->getScheduler()->scheduleRepeatingTask(new LBT($this), 20);
	}

	/** @var int $amount */
	public int $amount = 5;

	public function tick(): void
	{
		$items = HCFLoader::getInstance()->getModuleManager()->getLootBoxManager()->getItems();
		if (!$this->enabled) {
			return;
		}
		$this->ticks++;
		$this->swap++;
		if ($this->ticks >= 3 && $this->deployMoreEntitys !== 6) {
			$entity = new TestEntity($this->location, $this->location);
			$entity->setBaseLoc($this->location);
			$entity->setPose();
			$entity->setItemInHand($items[array_rand($items)]);
			$entity->spawnTo($this->player);
			$this->deployMoreEntitys++;
			array_push($this->entities, $entity);
			$this->ticks = 1;
		}
		if ($this->swap >= 3) {
			foreach ($this->entities as $entH) {
				$entH->setItemInHand($items[array_rand($items)]);
				$this->swap = 0;
			}
		}
		if ($this->ticks >= 25) {
			$this->enabled = false;
			for ($i = 0; $i <= $this->amount; $i++) {
				$entity = $this->entities[$i];
				if ($entity instanceof TestEntity) {
					$entity->getEntity()->flagForDespawn();
					$this->player->getInventory()->addItem($entity->getItemInHand());
					$entity->flagForDespawn();
					$this->floatingText->flagForDespawn();
				}
			}
		}
	}

	public function getEntities(): array
	{
		return $this->entities;
	}

	public function getFloatingText(): FloatingEntity{
		return $this->floatingText;
	}

	public function getPlayer(): Player
	{
		return $this->player;
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function setEnabled(bool $value): void
	{
		$this->enabled = $value;
	}
}