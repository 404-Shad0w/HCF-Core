<?php

namespace hcf\world;

use hcf\HCF;
use hcf\util\Utils;
use hcf\world\generator\End;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\generator\hell\Nether;
use pocketmine\world\WorldCreationOptions;

class WorldFactory {

    protected static string $netherName;
    protected static string $endName;

    public static function loadAll(): void {
        self::loadGenerators();

        if (!Server::getInstance()->getWorldManager()->loadWorld(self::$netherName = strtolower($worldName = HCF::getInstance()->getConfig()->get("world-config")["nether-name"]))) {
            Server::getInstance()->getWorldManager()->generateWorld($worldName, (new WorldCreationOptions())
                ->setGeneratorClass(Nether::class)
                ->setSpawnPosition(self::getDefaultSpawnNetherLocation())
            );
        }

        if (!Server::getInstance()->getWorldManager()->loadWorld(self::$endName = strtolower($worldName = HCF::getInstance()->getConfig()->get("world-config")["end-name"]))) {
            Server::getInstance()->getWorldManager()->generateWorld($worldName, (new WorldCreationOptions())
                ->setGeneratorClass(End::class)
                ->setSpawnPosition(self::getDefaultSpawnEndLocation())
            );
        }
    }

    protected static function loadGenerators(): void {
        GeneratorManager::getInstance()->addGenerator(End::class, "end", fn() => null, true);
    }

    public static function getDefaultSpawnEndLocation(): Vector3 {
        $data = [
            "x" => HCF::getInstance()->getConfig()->get("world-config")["default-spawn-end-location"][0],
            "y" => HCF::getInstance()->getConfig()->get("world-config")["default-spawn-end-location"][1],
            "z" => HCF::getInstance()->getConfig()->get("world-config")["default-spawn-end-location"][2],
        ];
        return Utils::arrayToPosition($data);
    }
    
    public static function getDefaultSpawnNetherLocation(): Vector3 {
        $data = [
            "x" => HCF::getInstance()->getConfig()->get("world-config")["default-spawn-nether-location"][0],
            "y" => HCF::getInstance()->getConfig()->get("world-config")["default-spawn-nether-location"][1],
            "z" => HCF::getInstance()->getConfig()->get("world-config")["default-spawn-nether-location"][2],
        ];
        return Utils::arrayToPosition($data);
    }

    public static function getNetherWorldName(): string {
        return strtolower(self::$netherName);
    }

    public static function getEndWorldName(): string {
        return strtolower(self::$endName);
    }
}