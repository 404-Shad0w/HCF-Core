<?php

namespace hcf\world\generator;

use hcf\world\generator\populator\EndPilar;

use pocketmine\block\VanillaBlocks;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\item\Item;
use pocketmine\world\generator\Generator;
use pocketmine\world\generator\noise\Simplex;
use pocketmine\world\generator\populator\Populator;

class End extends Generator {

    public const MIN_BASE_ISLAND_HEIGHT = 54;
    public const MAX_BASE_ISLAND_HEIGHT = 55;
    public const NOISE_SIZE = 12;

    public const CENTER_X = 0;
    public const CENTER_Z = 0;
    public const ISLAND_RADIUS = 100;

    /** @var Simplex */
    protected Simplex $noiseBase;

    /** @var Populator[] */
    protected array $populators = [];

    public function __construct(int $seed, string $preset) {
        parent::__construct($seed, $preset);
        $this->noiseBase = new Simplex($this->random, 4, 1 / 16, 1 / 64);
        $this->populators[] = new EndPilar();
    }

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void {
        $this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);
        $chunk = $world->getChunk($chunkX, $chunkZ);
        $noise = $this->noiseBase->getFastNoise2D(16, 16, 2, $chunkX * 16, 0, $chunkZ * 16);

        $endStone = VanillaBlocks::END_STONE()->getStateId();

        $baseX = $chunkX * Chunk::EDGE_LENGTH;
        $baseZ = $chunkZ * Chunk::EDGE_LENGTH;

        for ($x = 0; $x < 16; ++$x) {
            $absoluteX = $baseX + $x;
            for ($z = 0; $z < 16; ++$z) {
                $absoluteZ = $baseZ + $z;
                $chunk->setBiomeId($x, 0, $z, BiomeIds::THE_END);

                if (($absoluteX - self::CENTER_X) ** 2 + ($absoluteZ - self::CENTER_Z) ** 2 > self::ISLAND_RADIUS ** 2) {
                    continue;
                }
                $noiseValue = (int) abs($noise[$x][$z] * self::NOISE_SIZE);

                for ($y = 0; $y < $noiseValue; ++$y) {
                    $chunk->setBlockStateId($x, self::MAX_BASE_ISLAND_HEIGHT + $y, $z, $endStone);
                }
                $reversedNoiseValue = self::NOISE_SIZE - $noiseValue;

                for ($y = 0; $y < $reversedNoiseValue; ++$y) {
                    $chunk->setBlockStateId($x, self::MIN_BASE_ISLAND_HEIGHT - $y, $z, $endStone);
                }

                for ($y = self::MIN_BASE_ISLAND_HEIGHT; $y < self::MAX_BASE_ISLAND_HEIGHT; ++$y) {
                    $chunk->setBlockStateId($x, $y, $z, $endStone);
                }
            }
        }
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void {
        foreach ($this->populators as $populator) {
            $this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);
            $populator->populate($world, $chunkX, $chunkZ, $this->random);
        }
    }
}