<?php

namespace hcf\waypoint;

use hcf\session\SessionFactory;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

use pocketmine\entity\Entity;
use pocketmine\world\Position;

use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\convert\LegacySkinAdapter;
use pocketmine\network\mcpe\convert\SkinAdapter;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;

use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

use Ramsey\Uuid\Uuid;

class WayPoint {

    /** @var int */
    protected int $entityId;

    /** @var bool */
    protected bool $shouldDisplay = false;

    /**
     * WayPoint Constructor.
     * @param string $title
     * @param Position $position
     * @param int $followDistance
     */
    public function __construct(
        protected string $title,
        protected Position $position,
        protected int $followDistance,
    ){
        $this->entityId = Entity::nextRuntimeId();
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getDistance(Player $player) : int {
        return (int)$player->getPosition()->distance($this->position);
    }

    /**
     * @param Player $player
     * @return void
     */
    public function update(Player $player) : void {
        $networkSession = $player->getNetworkSession();

        $name = $this->title." "."[".$this->getDistance($player)."m]";

        $pk = new MovePlayerPacket();

        $pk->actorRuntimeId = $this->entityId;
        $pk->mode = $pk::MODE_NORMAL;
        $pk->position = $this->generateClientSidePositionFor($player);
        $pk->yaw = $pk->pitch = $pk->headYaw = 0;

        $networkSession->sendDataPacket($pk);

        $pk = new SetActorDataPacket();

        $pk->actorRuntimeId = $this->entityId;
        $pk->syncedProperties = new PropertySyncData([], []);

        $pk->metadata = [
            EntityMetadataProperties::NAMETAG => new StringMetadataProperty($name),
            EntityMetadataProperties::ALWAYS_SHOW_NAMETAG => new ByteMetadataProperty($this->shouldDisplay ? 1 : 0),
        ];

        $networkSession->sendDataPacket($pk);
    }

    /**
     * @param Player $player
     * @return WayPoint|null
     */
    public function showTo(Player $player) : ?self {
        $networkSession = $player->getNetworkSession();

        $uuid = Uuid::uuid4();
        $name = $this->title;

        try {
            $networkSession->sendDataPacket(PlayerListPacket::add([
                PlayerListEntry::createAdditionEntry(
                    $uuid, $this->entityId, $name,
                    TypeConverter::getInstance()->getSkinAdapter()->toSkinData(new Skin("Standard_Custom", str_repeat("\x00", 8192)))
                )
            ]));
        } catch (\JsonException $exception){

        }
        $pk = new AddPlayerPacket();
        $pk->uuid = $uuid;
        $pk->username = $name;
        $pk->actorRuntimeId = $this->entityId;
        $pk->gameMode = GameMode::CREATIVE;

        $pk->syncedProperties = new PropertySyncData([], []);

		$pk->abilitiesPacket = UpdateAbilitiesPacket::create(new AbilitiesData(0, 0, $this->entityId, []));
        $pk->position = $this->generateClientSidePositionFor($player)->subtract(0, 1.62, 0);
        $pk->item = ItemStackWrapper::legacy(ItemStack::null());

        $actorFlags = (
            1 << EntityMetadataFlags::IMMOBILE
        );

        $pk->metadata = [
            EntityMetadataProperties::FLAGS => new LongMetadataProperty($actorFlags),
            EntityMetadataProperties::SCALE => new FloatMetadataProperty(0.01), //zero causes problems on debug builds
        ];

        $networkSession->sendDataPacket($pk);
        $networkSession->sendDataPacket(PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($uuid)]));

        return $this;
    }

    /**
     * @param Player $player
     * @return void
     */
    public function hideFrom(Player $player) : void {
        $session = SessionFactory::get($player);
        $session->setWayPoint();
        $player->getNetworkSession()->sendDataPacket(RemoveActorPacket::create($this->entityId));
    }

    /**
     * @param bool $v
     * @return void
     */
    public function display(bool $v) : void {
        $this->shouldDisplay = $v;
    }

    /**
     * @param Player $player
     * @return Vector3
     */
    protected function generateClientSidePositionFor(Player $player) : Vector3 {
        $pos = $player->getEyePos();

        $newPos = $this->position->add(0, $player->getEyeHeight(), 0);
        if($pos->distance($newPos) <= $this->followDistance){
            return $newPos;
        }
        return $pos->addVector($newPos->subtractVector($pos)->normalize()->multiply($this->followDistance));
    }
}

?>