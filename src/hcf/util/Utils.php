<?php

/*
 * A PocketMine-MP plugin that implements Hard Core Factions.
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 *
 * @author JkqzDev
 */

declare(strict_types=1);

namespace hcf\util;

use hcf\HCF;
use hcf\kit\Kit;
use hcf\kit\KitFactory;
use hcf\vkit\vKit;
use hcf\vkit\vKitFactory;
use hcf\claim\Claim;
use hcf\session\SessionFactory;
use InvalidArgumentException;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\item\ItemFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\utils\DyeColor;
use pocketmine\inventory\Inventory;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\player\Player;
use pocketmine\entity\Entity;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use RuntimeException;
use function array_map;
use function array_values;
use function count;
use function explode;
use function floor;
use function gmdate;
use function preg_match;
use function strlen;
use function strtotime;
use function substr;
use function time;
use function trim;

final class Utils {
    
    public static function getConfig(): Config {
        return new Config(HCF::getInstance()->getDataFolder()."config.yml", Config::YAML);
    }
    
    public static function getGeneratorFile(): Config {
        return new Config(HCF::getInstance()->getDataFolder()."OverworldGenerator.yml", Config::YAML);
    }
    
    public static function getScoreFile() : Config {
        return new Config(HCF::getInstance()->getDataFolder()."score-module.yml", Config::YAML);
    }
    
    public static function getKbFile() : Config {
        return new Config(HCF::getInstance()->getDataFolder()."kb-module.yml", Config::YAML);
    }
    
    public static function getBossFile(): Config {
        return new Config(HCF::getInstance()->getDataFolder()."boss-module.yml", Config::YAML);
    }
    
    public static function deathParticleRed(Player $p): void {
		$world = $p->getWorld();
		$pos = $p->getPosition()->floor();
		$block = VanillaBlocks::REDSTONE();

		for($y = 0; $y < 2; $y++) {
			$world->addParticle(new Vector3($pos->x, $pos->y + $y, $pos->z), new BlockBreakParticle($block));
		}
	}
    
    public static function playLight(Position $pos): void {
        $pk = new AddActorPacket();
        $pk->actorUniqueId = Entity::nextRuntimeId();
        $pk->actorRuntimeId = 1;
        $pk->position = $pos->asVector3();
        $pk->type = "minecraft:lightning_bolt";
        $pk->yaw = 0;
        $pk->syncedProperties = new PropertySyncData([], []);
        $sound = PlaySoundPacket::create("ambient.weather.thunder", $pos->getX(), $pos->getY(), $pos->getZ(), 1, 1);
        NetworkBroadcastUtils::broadcastPackets($pos->getWorld()->getPlayers(), [$pk, $sound]);
    }
    
    public static function play(Player $p, string $soundName, float $volume = 1, float $pitch = 1):void {
		$pk = new PlaySoundPacket();
		$pk->soundName = $soundName;
		$pk->pitch = $pitch;
		$pk->volume =$volume;
		$pos = $p->getEyePos();
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$p->getNetworkSession()->sendDataPacket($pk);
	}
    
    public static function claimToString(Claim $l, $separator = '-') : string {
        return $l->getMinX().$separator.$l->getMaxX().$separator.$l->getMinZ().$separator.$l->getMaxZ().$separator.null;
    }
    
    #[ArrayShape(["x" => "float|int", "y" => "float|int", "z" => "float|int", "world" => "int"])] public static function positionToArray(Position $l) : array {
        return ["x" => $l->x, "y" => $l->y, "z" => $l->z, "world" => $l->getWorld()->getId()];
    }

	public static function vectorToString(Vector3 $vector3, string $separator = '-') : string {
		return $vector3->getX() . $separator . $vector3->getZ();
		//return $vector3->getX() . $separator . $vector3->getY() . $separator . $vector3->getZ();
	}

    public static function fromString(Vector3 $vector3, string $separator = ':') : string {
        return $vector3->getX() . $separator . $vector3->getY() . $separator . $vector3->getZ();
    }

	public static function positionToString(Position $position) : string {
		[$world, $x, $y, $z] = [$position->getWorld()->getFolderName(), $position->getFloorX(), $position->getFloorY(), $position->getFloorZ()];
		return $world . ':' . $x . ':' . $y . ':' . $z;
	}

	public static function stringToVector(string $data) : Vector3 {
		$data = explode(":", $data);

		if (count($data) !== 3) {
			throw new RuntimeException("Expected string with format x:y:z. $data given");
		}
		return new Vector3((float) $data[0], (float) $data[1], (float) $data[2]);
	}

    
	public static function stringToPosition(string $data) : Position {
		$data = explode(':', $data);

		if (!Server::getInstance()->getInstance()->getWorldManager()->isWorldGenerated($data[0])) {
			throw new RuntimeException('World isn\'t generated');
		}

		if (!Server::getInstance()->getInstance()->getWorldManager()->isWorldLoaded($data[0])) {
			Server::getInstance()->getWorldManager()->loadWorld($data[0]);
		}
		return new Position((float) $data[1], (float) $data[2], (float) $data[3], Server::getInstance()->getWorldManager()->getWorldByName($data[0]));
	}
	
	public static function floatFormat(float $time) : string {
		if ($time < 60.00) {
			return $time . 's';
        } elseif ($time >= 3600.00) {
        	return gmdate('H:i:s', (int)$time);
        } else {
        	return gmdate('i:s', (int)$time);
        }
    }

	public static function timeFormat(int $time) : string {
		if ($time < 60) {
			return $time . 's';
		} elseif ($time >= 3600) {
			return gmdate('H:i:s', $time);
		} else {
			return gmdate('i:s', $time);
		}
	}

	public static function minecraftRomanNumerals(int $number) : string {
		static $romanNumerals = [
			1 => "I", 2 => "II", 3 => "III", 4 => "IV", 5 => "V",
			6 => "VI", 7 => "VII", 8 => "VII", 9 => "IX", 10 => "X"
		];
		return $romanNumerals[$number] ?? ((string) $number);
	}

	public static function stringToTime(string $duration) : int {
		$time_units = ['y' => 'year', 'M' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'm' => 'minute'];
		$regex = '/^([0-9]+y)?([0-9]+M)?([0-9]+w)?([0-9]+d)?([0-9]+h)?([0-9]+m)?$/';
		$matches = [];
		$is_matching = preg_match($regex, $duration, $matches);

		if (!$is_matching) {
			throw new InvalidArgumentException('Invalid duration. Please put numbers and letters');
		}
		$time = '';

		foreach ($matches as $index => $match) {
			if ($index === 0 || strlen($match) === 0) {
				continue;
			}
			$n = substr($match, 0, -1);
			$unit = $time_units[substr($match, -1)];
			$time .= "$n $unit ";
		}
		$time = trim($time);

		return $time === '' ? time() : strtotime($time);
	}

	public static function date(int|float $time) : string {
		$remaining = (int)$time;
		$s = $remaining % 60;

		$m = null;
		$h = null;
		$d = null;

		if ($remaining >= 60) {
			$m = floor(($remaining % 3600) / 60);

			if ($remaining >= 3600) {
				$h = floor(($remaining % 86400) / 3600);

				if ($remaining >= 3600 * 24) {
					$d = floor($remaining / 86400);
				}
			}
		}
		return ($m !== null ? ($h !== null ? ($d !== null ? "$d days " : "") . "$h hours " : "") . "$m minutes " : "") . "$s seconds";
	}

    public static function arrayToPosition(array $l): Position {
        return new Position(intval($l["x"]), intval($l["y"]), intval($l["z"]), isset($l["world"]) ? Server::getInstance()->getWorldManager()->getWorld(intval($l["world"])) : Server::getInstance()->getWorldManager()->getDefaultWorld());
    }
    
    public static function createvKitMenu(Player $player) : void {
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		$menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $glass = VanillaBlocks::DYE()->setColor(DyeColor::PURPLE()())->asItem();
        $glass->setCustomName(' ');
        for($i = 0; $i <= 53; $i++){
            $menu->getInventory()->setItem($i, $glass);
        }

		foreach (vKitFactory::getAll() as $kit) {
			if ($kit->getInventorySlot() === null) {
				continue;
			}
			$item = clone $kit->getItemDecorative();
			$item->setCustomName(TextFormat::colorize('&r' . $kit->getName()));
			$lore = ['&r', '&r&5Cooldown: &f' . ($kit->getCountdown() === null ? 'Unlimited' : self::date($kit->getCountdown()))];

			if ($session->getTimer($kit->getName() . '_kit') !== null) {
				$lore[] = '&r&5Available in: &f' . self::date($session->getTimer($kit->getName() . '_kit')->getTime());
			}
			$lore[] = '&r';
			$lore[] = '&7&oplay.Coronacraft.net';
			$item->setLore(array_map(fn(string $text) => TextFormat::colorize($text), $lore));
			$item->getNamedTag()->setString('kit_name', $kit->getName());

			$menu->getInventory()->setItem($kit->getInventorySlot(), $item);
		}

		$menu->setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
			$player = $transaction->getPlayer();
			$item = $transaction->getItemClicked();

			if ($item->getNamedTag()->getTag('kit_name') !== null) {
				$kit = vKitFactory::get($item->getNamedTag()->getString('kit_name'));
				$kit?->giveTo($player);
			}
			return $transaction->discard();
		});

		$menu->send($player, TextFormat::colorize('&6vKits'));
    }
    
    public static function createvKitOrganizationEditor(Player $player) : void {
		$menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
		$contents = array_map(function (vKit $kit) {
			$item = clone $kit->getItemDecorative();
            $item->setCustomName(TextFormat::colorize('&r' . $kit->getColorize()));
			$item->getNamedTag()->setString('kit_name', $kit->getColorize());
			return $item;
		}, array_values(vKitFactory::getAll()));
		$menu->getInventory()->setContents($contents);
		$menu->setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
			$item = $transaction->getItemClickedWith();

			if (!$item->isNull() && $item->getNamedTag()->getTag('kit_name') === null) {
				return $transaction->discard();
			}
			return $transaction->continue();
		});
		$menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) : void {
			$contents = $inventory->getContents();

			foreach ($contents as $slot => $item) {
				if ($item->getNamedTag()->getTag('kit_name') === null) {
					continue;
				}
				$kit = vKitFactory::get($item->getNamedTag()->getString('kit_name'));

				if ($kit === null) {
					continue;
				}
				$kit->setInventorySlot($slot);
			}
			$player->sendMessage(TextFormat::colorize('&aYou have been edited vkit organization'));
		});
		$menu->send($player, TextFormat::colorize('&dEdit vKit Organization'));
    }

	public static function createKitMenu(Player $player) : void {
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		$menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::PURPLE())->asItem();
        $glass->setCustomName(' ');
        for($i = 0; $i <= 53; $i++){
            $menu->getInventory()->setItem($i, $glass);
        }

		foreach (KitFactory::getAll() as $kit) {
			if ($kit->getInventorySlot() === null) {
				continue;
			}
			$item = clone $kit->getItemDecorative();
			$item->setCustomName(TextFormat::colorize('&r' . $kit->getColorize()));
			$lore = ['&r', '&r&5Cooldown: &f' . ($kit->getCountdown() === null ? 'Unlimited' : self::date($kit->getCountdown()))];

			if ($session->getTimer($kit->getName() . '_kit') !== null) {
				$lore[] = '&r&5Available in: &f' . self::date($session->getTimer($kit->getName() . '_kit')->getTime());
			}
			$lore[] = '&r';
			$lore[] = '&7&8Test.com.xyz';
			$item->setLore(array_map(fn(string $text) => TextFormat::colorize($text), $lore));
			$item->getNamedTag()->setString('kit_name', $kit->getName());

			$menu->getInventory()->setItem($kit->getInventorySlot(), $item);
		}

		$menu->setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
			$player = $transaction->getPlayer();
			$item = $transaction->getItemClicked();

			if ($item->getNamedTag()->getTag('kit_name') !== null) {
				$kit = KitFactory::get($item->getNamedTag()->getString('kit_name'));
				$kit?->giveTo($player);
			}
			return $transaction->discard();
		});

		$menu->send($player, TextFormat::colorize('&6Kit\'s'));
	}

	public static function createKitOrganizationEditor(Player $player) : void {
		$menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
		$contents = array_map(function (Kit $kit) {
			$item = clone $kit->getItemDecorative();
            $item->setCustomName(TextFormat::colorize('&r' . $kit->getName()));
			$item->getNamedTag()->setString('kit_name', $kit->getName());
			return $item;
		}, array_values(KitFactory::getAll()));
		$menu->getInventory()->setContents($contents);
		$menu->setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
			$item = $transaction->getItemClickedWith();

			if (!$item->isNull() && $item->getNamedTag()->getTag('kit_name') === null) {
				return $transaction->discard();
			}
			return $transaction->continue();
		});
		$menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) : void {
			$contents = $inventory->getContents();

			foreach ($contents as $slot => $item) {
				if ($item->getNamedTag()->getTag('kit_name') === null) {
					continue;
				}
				$kit = KitFactory::get($item->getNamedTag()->getString('kit_name'));

				if ($kit === null) {
					continue;
				}
				$kit->setInventorySlot($slot);
			}
			$player->sendMessage(TextFormat::colorize('&aYou have been edited kit organization'));
		});
		$menu->send($player, TextFormat::colorize('&dEdit Kit Organization'));
	}
}
