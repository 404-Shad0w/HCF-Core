<?php
/**
 * Created by PhpStorm.
 * User: CortexPE
 * Date: 3/30/2019
 * Time: 12:48 AM
 */

namespace CortexPE\libSchematic;


use CortexPE\Linguist\chunkConverter\MCJavaAnvilConverter;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use RuntimeException;

final class Schematic {
	// TODO: Other fields, this is the implementation for a bare-bones schematic file loader.

	public const TAG_WIDTH = "Width";
	public const TAG_HEIGHT = "Height";
	public const TAG_LENGTH = "Length";
	public const TAG_BLOCKS = "Blocks";
	public const TAG_BLOCK_DATA = "Data";
	public const TAG_ENTITIES = "Entities";
	public const TAG_TILE_ENTITIES = "TileEntities";
	public const TAG_MATERIALS = "Materials";
	public const TAG_PLATFORM = "Platform";

	public const MATERIALS_CLASSIC = "Classic";
	public const MATERIALS_POCKET = "Pocket";
	public const MATERIALS_ALPHA = "Alpha";

	public const PLATFORM_NUKKIT = "nukkit";

	/** @var int */
	private $width;
	/** @var int */
	private $height;
	/** @var int */
	private $length;
	/** @var string */
	private $blocks;
	/** @var string */
	private $blocksData;
	/** @var CompoundTag[] */
	private $entities;
	/** @var CompoundTag[] */
	private $tileEntities;
	/** @var string */
	private $materials;
	/** @var string|null */
	private $platform = null;

	/** @var string */
	private $filePath = "";

	/** @var Schematic[] */
	private $rotatedCache = [];

	/** @var Block[] */
	private $cache = [];

	public static function fromFile(string $filePath): self {
		$s = new self();
		$s->filePath = $filePath;
		$stream = new BigEndianNbtSerializer();
		if(!is_file($filePath)){
			throw new RuntimeException("File $filePath not found");
		}
		$decompressed = @zlib_decode(file_get_contents($filePath));
		if($decompressed === false) {
			throw new RuntimeException("Failed to decompress schematic file contents");
		}
		$nbt = $stream->read($decompressed)->mustGetCompoundTag();

		if($nbt instanceof CompoundTag) {
			$s->width = $nbt->getShort(self::TAG_WIDTH);
			$s->height = $nbt->getShort(self::TAG_HEIGHT);
			$s->length = $nbt->getShort(self::TAG_LENGTH);
			$s->blocks = $nbt->getByteArray(self::TAG_BLOCKS);
			$s->blocksData = $nbt->getByteArray(self::TAG_BLOCK_DATA);
			$s->entities = null;
			//$s->entities = $nbt->getListTag(self::TAG_ENTITIES)->getValue() ?: null;
			$s->tileEntities = null; //$nbt->getListTag(self::TAG_TILE_ENTITIES)->getValue() ?: null;
			$s->materials = $nbt->getString(self::TAG_MATERIALS);
			$s->platform = $nbt->getString(self::TAG_PLATFORM, false) ?: null;
		} else {
			throw new RuntimeException("Invalid schematic file");
		}
		return $s;
	}

	/**
	 * @return string
	 */
	public function getFilePath(): string {
		return $this->filePath;
	}

	/**
	 * Check if the coordinates are within this schematic file
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return bool
	 */
	public function isWithinSchematic(int $x, int $y, int $z) {
		return (
			$x >= 0 &&
			$x < $this->width &&
			$z >= 0 &&
			$z < $this->length &&
			$y >= 0 &&
			$y < $this->height
		);
	}

	/**
	 * This is used to get the corresponding indices
	 * for coordinates from the Blocks and BlockData ByteArrays
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return float|int
	 */
	private function getIndex(int $x, int $y, int $z) {
		if(!$this->isWithinSchematic($x, $y, $z)) {
			throw new \OutOfBoundsException("Block position $x, $y, $z is out of bounds");
		}
		return $y * $this->width * $this->length + $z * $this->width + $x;
	}

	/**
	 * Get Block ID based on coordinates
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return int
	 */
	public function getBlockIdAt(int $x, int $y, int $z): int {
		return ord($this->blocks[$this->getIndex($x, $y, $z)]);
	}

	/**
	 * Get Block Data (a.k.a. meta) based on coordinate
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return int
	 */
	public function getBlockDataAt(int $x, int $y, int $z): int {
		return ord($this->blocksData[$this->getIndex($x, $y, $z)]);
	}

	/**
	 * Get the block on a coordinate
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return Block
	 */
	public function getBlockAt(int $x, int $y, int $z) {
		$id = $this->getBlockIdAt($x, $y, $z);
		$data = $this->getBlockDataAt($x, $y, $z);
		$kc = $id << 4 | $data;
		if(isset($this->cache[$kc])){
			return clone $this->cache[$kc];
		}
		if(
			$this->materials !== self::MATERIALS_POCKET && // convert if from bukkit
			$this->platform !== self::PLATFORM_NUKKIT // idk why nukkit devs use the "Alpha" materials tag REEEE use "Pocket"
		) {
			MCJavaAnvilConverter::convertRaw($id, $data);
		}
        return $this->cache[$kc] = $id;
        //return $this->cache[$kc] = BlockFactory::getInstance()->get($id, $data);
	}

	/**
	 * Get block, given Vector3 position
	 *
	 * @param Vector3 $pos
	 * @return Block
	 */
	public function getBlock(Vector3 $pos) {
		$pos = $pos->floor();
		return $this->getBlockAt($pos->x, $pos->y, $pos->z);
	}

	/**
	 * Get the schematic's width (size, running on X axis)
	 *
	 * @return int
	 */
	public function getWidth(): int {
		return $this->width;
	}

	/**
	 * Get the schematic's height (size, running on Y axis)
	 *
	 * @return int
	 */
	public function getHeight(): int {
		return $this->height;
	}

	/**
	 * Get the schematic's length (size, running on Z axis)
	 *
	 * @return int
	 */
	public function getLength(): int {
		return $this->length;
	}

	/**
	 * Gives an array of CompoundTags for all
	 * the TileEntities in the schematic
	 *
	 * @return CompoundTag[]
	 */
	public function getTileEntities(): array {
		return $this->tileEntities;
	}

	/**
	 * Gives an array of CompoundTags for all
	 * the Entities in the schematic
	 *
	 * @return CompoundTag[]
	 */
	public function getEntities(): array {
		return $this->entities;
	}

	/**
	 * @param int $direction how many clockwise 90 degree turns to rotate
	 * @return Schematic returns a rotated clone
	 */
	public function rotate(int $direction): self {
		$direction = $direction % 4;

		if(isset($this->rotatedCache[$direction])) return $this->rotatedCache[$direction];
		$base = clone $this;
		for($c = 0; $c < $direction; $c++) {
			$new = clone $base;
			$new->blocks = "";
			$new->blocksData = "";
			$new->width = $base->length;
			$new->length = $base->width;

			for($x = 0; $x < $base->width; $x++) {
				for($z = 0; $z < $base->length; $z++) {
					for($y = 0; $y < $base->height; $y++) {
						$i = $base->getIndex($base->width - $x - 1, $y, $z);
						$ni = $new->getIndex($z, $y, $x);
						$new->blocks[$ni] = $base->blocks[$i];
						$new->blocksData[$ni] = $base->blocksData[$i]; // todo: rotate data
					}
				}
			}
			$base = $new;
		}
		$this->rotatedCache[$direction] = $base;
		return $base;
	}
}