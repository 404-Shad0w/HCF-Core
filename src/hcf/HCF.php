<?php
    
declare(strict_types=1);

namespace hcf;

use CortexPE\Rave\fbm\NoiseGroup;
use CortexPE\Rave\interpolation\LinearInterpolation;
use CortexPE\Rave\Perlin;
use CortexPE\std\FileSystemUtils;
use muqsit\simplepackethandler\SimplePacketHandler;
use pocketmine\item\PotionType;
use pocketmine\block\tile\TileFactory;
use CortexPE\std\AABBUtils;
use CortexPE\std\Vector3Utils;
use hcf\generator\HCFOverworldGenerator;
use hcf\item\RegisterItemFactory;
use hcf\world\WorldFactory;
use hcf\util\Utils;
use hcf\block\Tile;
use hcf\block\PotionGenerator;
use hcf\block\TileBrewingStand;
use hcf\block\BrewingStand;
use hcf\block\EndPortal;
use hcf\block\EndPortalFrame;
use hcf\block\NetherPortal;
use hcf\block\Obsidian;
use hcf\claim\ClaimFactory;
use hcf\claim\ClaimHandler;
use hcf\command\admin\ReloadCommand;
use hcf\command\admin\BossCommand;
use hcf\command\FreeRankCommand;
use hcf\command\admin\OwnerModeCommand;
use hcf\command\admin\KbModificateCommand;
use hcf\command\admin\TpAllCommand;
use hcf\command\admin\ListCommand;
use hcf\command\admin\SpawnnpcCommand;
use hcf\command\admin\BroadcastCommand;
use hcf\command\AutoFeedCommand;
use hcf\command\BalanceCommand;
use hcf\command\ClearLagCommand;
use hcf\command\CraftCommand;
use hcf\command\EnderChestCommand;
use hcf\command\FeedCommand;
use hcf\command\FixCommand;
use hcf\command\FloatingTextCommand;
use hcf\command\LogoutCommand;
use hcf\command\NearCommand;
use hcf\command\PayCommand;
use hcf\command\PingCommand;
use hcf\command\PlayersCommand;
use hcf\command\PvPCommand;
use hcf\command\RenameCommand;
use hcf\command\StatsCommand;
use hcf\command\TeammateLocationCommand;
use hcf\module\ModuleManager;
use hcf\item\ItemHandler;
use hcf\module\WallBarriersTrait;
use hcf\module\BossHandler;
use hcf\elevator\ElevatorHandler;
use hcf\faction\handler\UpgradeHandler;
use hcf\enchantment\command\CustomEnchantCommand;
use hcf\enchantment\EnchantmentFactory;
use hcf\enchantment\EnchantmentHandler;
use hcf\entity\CustomTextEntity;
use hcf\entity\DisconnectEntity;
use hcf\entity\SnowballEntity;
use hcf\entity\EnderPearlEntity;
use hcf\entity\SplashPotionEntity;
use hcf\entity\DeathsTopEntity;
use hcf\entity\KillsTopEntity;
use hcf\faction\command\FactionCommand;
use hcf\faction\FactionFactory;
use hcf\item\Snowball;
use hcf\item\EnderPearl;
use hcf\item\SplashPotion;
use hcf\item\EnderEye;
use hcf\item\GlassBottle;
use hcf\kit\class\ClassFactory;
use hcf\kit\command\GKitCommand;
use hcf\kit\command\KitCommand;
use hcf\vkit\command\vKitCommand;
use hcf\vkit\command\vKitsCommand;
use hcf\vkit\vKitFactory;
use hcf\kit\KitFactory;
use hcf\kit\KitHandler;
use hcf\session\SessionFactory;
use hcf\timer\command\TimerCommand;
use hcf\timer\TimerFactory;
use hcf\timer\TimerHandler;
use hcf\util\ClearLag;
use hcf\util\inventory\CraftingInventory;
use hcf\util\inventory\InventoryIds;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockToolType;
use pocketmine\block\DetectorRail;
use pocketmine\item\ToolTier;
use pocketmine\item\ItemIds;
use pocketmine\scheduler\ClosureTask;
use pocketmine\block\Block; 
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\event\Listener;
use pocketmine\world\WorldCreationOptions;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\player\Player;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\entity\projectile\EnderPearl as PMEnderPearl;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use function time;

final class HCF extends PluginBase implements Listener {
	use SingletonTrait;
	use WallBarriersTrait;
    
    private array $regions = [];
    private Block $barrierBlock;
    
    private bool $ignoreRevert = false;
    /** @var int[] */
    private $lastPearled = [];
    /** @var int[] */
    private $ignoreRecvC = [];
    /** @var float[][] */
    private $supposedXZ = [];
        /** @var ModuleManager */
    public ModuleManager $moduleManager;

	private static bool $development = false;
	private static int $time;
    
	public static function isUnderDevelopment() : bool {
		return self::$development;
	}

	public static function getTotalTime() : int {
		return self::$time;
	}

	protected function onLoad() : void {
		self::setInstance($this);
		self::$time = time();
        $this->saveDefaultConfig();
        $config = Utils::getConfig();
		$this->barrierRadius = 3;
        $this->barrierBlock = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED());
        $this->regions[$this->getConfig()->get("world")][] = AABBUtils::fromCoordinates(
            Vector3Utils::fromString($this->getConfig()->get("pos1")),
            Vector3Utils::fromString($this->getConfig()->get("pos2"))
        )->expand(0.0001, 0, 0.0001);
        if ($config->get("overworld_generator")) {
            $this->saveResource('OverworldGenerator.yml');
            $this->registerOverworld();
        }
	}

	protected function onEnable() : void {    
        $this->saveResource('kb-module.yml');
        $this->saveResource('score-module.yml');

		$this->getServer()->getNetwork()->setName(TextFormat::colorize($this->getConfig()->get('motd-text', '')));
		
		$this->registerHandlers();
        $config = Utils::getConfig();
        if ($config->get("boss") === true) {
            $this->getServer()->getCommandMap()->register('HCF', new BossCommand());
            $this->saveResource('boss-module.yml');
        }
		$this->registerCommands();
		$this->registerInventories();
		$this->registerEntities();
        $this->registerItemsAndBlocks();

        //WorldFactory::loadAll();
		ClaimFactory::loadAll();
        if ($config->get("custom-enchant") === true) {
            EnchantmentFactory::loadAll();
        }
		KitFactory::loadAll();
		ClassFactory::loadAll();
		FactionFactory::loadAll();
		SessionFactory::loadAll();
		TimerFactory::loadAll();

		TimerFactory::task();
		FactionFactory::task();
		SessionFactory::task();

		ClearLag::getInstance()->task();
	}

	private function registerHandlers() : void {
        $this->getServer()->getPluginManager()->registerEvents(new ClaimHandler(), $this);
        $config = Utils::getConfig();
        if ($config->get("boss") === true) {
            $this->getServer()->getPluginManager()->registerEvents(new BossHandler(), $this);
        }
		$this->getServer()->getPluginManager()->registerEvents(new ElevatorHandler(), $this);
		$this->getServer()->getPluginManager()->registerEvents(new ItemHandler(), $this);
                //$this->getServer()->getPluginManager()->registerEvents(new UpgradeHandler(), $this);
        $config = Utils::getConfig();
        if ($config->get("custom-enchant") === true) {
            $this->getServer()->getPluginManager()->registerEvents(new EnchantmentHandler(), $this);
        }
		$this->getServer()->getPluginManager()->registerEvents(new TimerHandler(), $this);
		$this->getServer()->getPluginManager()->registerEvents(new EventHandler(), $this);
		$this->getServer()->getPluginManager()->registerEvents(new KitHandler(), $this);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	private function registerCommands() : void {
		$this->getServer()->getCommandMap()->registerAll('HCF', [
			// Default command 
            new BroadcastCommand(),
			new AutoFeedCommand(),
			new BalanceCommand(),
			new ClearLagCommand(),
			new CraftCommand(),
			new EnderChestCommand(),
			new FeedCommand(),
			new FreeRankCommand(),
            //new FixCommand(),
            //new FloatingTextCommand(),
			new LogoutCommand(),
			new NearCommand(),
			new PayCommand(),
			new PingCommand(),
			new PlayersCommand(),
			new PvPCommand(),
			new RenameCommand(),
			new StatsCommand(),					 
			new TeammateLocationCommand(),
			// Custom Enchant
			new CustomEnchantCommand(),
			// Kit
			new GKitCommand(),
			new KitCommand(),
			// Faction
			new FactionCommand(),
			// Timer
			new TimerCommand(),
            // Admin 
            new OwnerModeCommand(),
            new ReloadCommand(),
            new KbModificateCommand(),
            new TpAllCommand(),					 
	    new ListCommand()					 
		]);
	}

	private function registerInventories() : void {
		if (!InvMenuHandler::isRegistered()) {
			InvMenuHandler::register($this);
		}
		InvMenuHandler::getTypeRegistry()->register(InventoryIds::CRAFTING_INVENTORY, new CraftingInventory());
	}

	private function registerEntities() : void {
        /*EntityFactory::getInstance()->register(CustomTextEntity::class, function (World $world, CompoundTag $nbt) : CustomTextEntity {
			return new CustomTextEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ['CustomTextEntity']);*/
		EntityFactory::getInstance()->register(DisconnectEntity::class, function (World $world, CompoundTag $nbt) : DisconnectEntity {
			$entity = new DisconnectEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
			$entity->flagForDespawn();
			return $entity;
		}, ['DisconnectEntity']);
		EntityFactory::getInstance()->register(EnderPearlEntity::class, function (World $world, CompoundTag $nbt) : EnderPearlEntity {
			return new EnderPearlEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ['ThrownEnderpearl', 'minecraft:ender_pearl'], EntityIds::ENDER_PEARL);
        EntityFactory::getInstance()->register(SnowballEntity::class, function (World $world, CompoundTag $nbt) : SnowballEntity {
			return new SnowballEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ['ThrownSnowball', 'minecraft:snowball'], EntityIds::SNOWBALL);
	}
    
	private function registerItemsAndBlocks() : void {
        RegisterItemFactory::registerOnAllThreads();
	}
    
    private function registerOverworld(): void {
        @mkdir($this->getDataFolder() . "hcf_gen" . DIRECTORY_SEPARATOR);
		@mkdir($this->getDataFolder() . "hcf_gen" . DIRECTORY_SEPARATOR . "spawns");
        
        $genMgr = GeneratorManager::getInstance();
		$genMgr->addGenerator(HCFOverworldGenerator::class, "hcf", function(string $preset){ return null; });
		$wMgr = $this->getServer()->getWorldManager();
        $config = Utils::getGeneratorFile();
        if(!$wMgr->loadWorld($name = $config->get("name"))) {
            $opts = new WorldCreationOptions();
			$opts->setGeneratorClass(HCFOverworldGenerator::class);
			$opts->setDifficulty(World::DIFFICULTY_HARD);
			$opts->setGeneratorOptions(json_encode(array_merge($config->get("generatorOptions"), ["dataFolder" => $this->getDataFolder() . "hcf_gen" . DIRECTORY_SEPARATOR])));
			$opts->setSpawnPosition(new Vector3(0, 127, 0));
			$opts->setSeed(/*Utils::javaStringHash("the quick brown fox jumps over the lazy dog")*/ time());
			$wMgr->generateWorld($name, $opts);
        }
        return;
        
        $h = 1024;
		$w = 1024;
        
        $rand = new Random(time());
		$perlin = new Perlin(new LinearInterpolation(), $rand);
		$noise = new NoiseGroup();
		$noise->addOctaves(
			$perlin, 2, 1 / 32, 1, 2, 2, 1 / 4, 1,
			function(float $x): float {
				return abs($x) * -1;
			}
		);
        
        $data = [];
		$time_rn = microtime(true);
		for($chunkZ = 0; $chunkZ < ($h >> 4); $chunkZ++) {
			$realZ = $chunkZ << 4;
			for($chunkX = 0; $chunkX < ($w >> 4); $chunkX++) {
				$realX = $chunkX << 4;
				for($cx = 0; $cx < 16; $cx++) {
					$ix = ($realX + $cx);
					for($cz = 0; $cz < 16; $cz++) {
						$iz = ($realZ + $cz);
						$n = $noise->noise3D($ix, 0, $iz);
						$n = $n * 0.5 + 0.5;
						$data[$ix][$iz] = $n;
					}
				}
			}
		}
        
        $time_taken = round((microtime(true) - $time_rn) * 1000, 2);
        var_dump("Generation took {$time_taken}ms to finish");
		file_put_contents("test.json", json_encode($data));
    }

	protected function onDisable() : void {
		TimerFactory::saveAll();
		FactionFactory::saveAll();
		KitFactory::saveAll();
		SessionFactory::saveAll();
        //sleep(5);
	}

	protected function isVisibleTo(Player $player): bool {
        $session = SessionFactory::get($player);
        return $session?->getTimer('spawn_tag') !== null;
    }
    
    protected function calculateNearestPointOutside(Player $p, Position $pos): Position {
        foreach($this->regions[$pos->world->getFolderName()] ?? [] as $region) {
            if(!$region->isVectorInside($p->getPosition())) continue;
            $vec = AABBUtils::getNearestStraightPathOutside($region, $pos);
            $pos->x = $vec->x;
            $pos->y = $vec->y;
            $pos->z = $vec->z;
        }
        return $pos;
    }
    
    #[Pure] protected function isInsideProhibitedArea(Player $p, Position $pos): bool {
        if(!isset($this->regions[$fName = $pos->world->getFolderName()])) return false;
        foreach($this->regions[$fName] as $region) {
            if($region->isVectorInside($pos)) return true;
        }
        return false;
    }
    
    protected function getBarrierBlock(): Block {
        return $this->barrierBlock;
    }
}
