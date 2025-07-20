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

namespace hcf\session\scoreboard;

use hcf\claim\Claim;
use hcf\claim\ClaimHandler;
use hcf\HCF;
use hcf\util\Utils;
use hcf\faction\FactionFactory;
use hcf\session\Session;
use hcf\session\timer\Timer;
use hcf\timer\TimerFactory;
use pocketmine\Server;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use function array_filter;
use function array_merge;
use function count;
use function strtolower;

final class ScoreboardBuilder {

   public int $numb = 1;

	public function __construct(
		private Session $session,
		private string  $title = '',
		private array   $lines = [],
		private bool    $spawned = true,
		private int $count = 0
	) {}

	public function update() : void {
		$session = $this->session;
		$player = $session->getPlayer();
        $sclaim = $session->getCurrentClaim();

		if ($player === null || !$player->isOnline()) {
			return;
		}

        $lines = ['&7'];
        
        $server = $player->getServer();
        if ($session->hasOwnerMode()) {
            $tpsColor = TextFormat::GREEN;
            if($server->getTicksPerSecond() < 12){
                $tpsColor = TextFormat::RED;
            }elseif($server->getTicksPerSecond() < 17){
                $tpsColor = TextFormat::GOLD;
            }
	    $lines[] = '&l&4OWNER MODE';
            $lines[] = ' &l&6| &dCurrent TPS: '.$tpsColor.$server->getTicksPerSecond()."({$server->getTickUsage()}%)";
            $lines[] = ' &l&6| &dAverage TPS: '.$tpsColor.$server->getTicksPerSecondAverage()."({$server->getTickUsageAverage()}%)";
	    $lines[] = '&l&r&f ';
        }
        
        /*$v = Moderation::getInstance()->getStaffmode()->isInVanish($player);
        $s = Moderation::getInstance()->getStaffmode()->isInStaffMode($player);
        if ($s !== null) {
	    $lines[] = ' &l&6STAFF MODE';
            $lines[] = '  &&6&l| ■&3Online: &7'.count($server->getOnlinePlayers());
            if ($v === null) {
                $vanish = '&cDisable';
            } else {
                $vanish = '&aEnable';
            }
            $lines[] = '  &6&l| ■&3Vanish: &7'.$vanish;
            $lines[] = '  &6&l| ■&3Ping: &7'.$player->getNetworkSession()->getPing();
	    $lines[] = '&3&7&8&9';
        }*/
        
		$faction = $session->getFaction();
		$globalTimers = array_filter(TimerFactory::getAll(), fn(\hcf\timer\Timer $tt) => $tt->isEnabled());
		$currentClaim = $session->getCurrentClaim();
		$claim = !ClaimHandler::getAlignedBB()->isVectorInside($player->getPosition()) ? '&7Wilderness' : '&cWarzone';      

		if ($currentClaim !== null) {
            if ($currentClaim->getDefaultName() === $faction?->getName()) {
            //if ($currentClaim->equals($session->getCurrentClaim())) {
				$claim = '§a' . $currentClaim->getDefaultName();
			} else {
				$claim = match ($currentClaim->getType()) {
					Claim::FACTION => '§c' . $currentClaim->getDefaultName(),
					Claim::SPAWN => '§a' . $currentClaim->getDefaultName(),
					Claim::ROAD => '§6' . $currentClaim->getDefaultName(),
					Claim::KOTH => '§9KoTH ' . $currentClaim->getDefaultName()
				};
			}
		}
        $lines[] = ' &l&6| &dClaim&r&7: ' . $claim;
        
        $config = HCF::getInstance()->getConfig();
        if ($config->get("kitmap-mode")) {
            if ($sclaim !== null && $sclaim->getType() === Claim::SPAWN) {
                $lines[] = ' &l&6| &dBalance&r&7:&c ' . $session->getBalance();
                $lines[] = ' &l&6|  &dKill Key&r&7:&c ' . $session->getKillKey();
            }
        }
        
		if (count($globalTimers) !== 0) {
			foreach ($globalTimers as $globalTimer) {
				$lines[] = ' &l&6| ' . $globalTimer->getFormat() . ' ' . Utils::timeFormat($globalTimer->getProgress());
			}
		}
		$timers = array_filter($session->getTimers(), fn(Timer $timer) => !$timer->isExpired() && $timer->isVisible());

		foreach ($timers as $timer) {
			$lines[] = ' &l&6| ' . $timer->getFormat() . ' &c' . Utils::floatFormat(round($timer->getTime(), 2));
		}
        
        /*$sBounties = BountiesFactory::getInstance()->get($player);
        
        if (($target = $sBounties->getPlayerTracking()) !== null) {
            if ($target->isOnline()) {
                if (count($lines) > 1) {
                    $lines[] = '&r&r ';
                }
                $lines[] = ' &r&6Tracking&r&7:&c ' . $target->getName();
                $lines[] = ' &r&6Coords&r&7:&a ' . (int)(($pos = $target->getPosition())->getX()) . ', ' . (int)$pos->getY() . ', ' . (int)$pos->getZ();
            }
        }*/
        
        /*$deathban = SessionManager::getInstance()->getSession($player);
        if ($deathban !== null) {
            if($deathban->isInDeathBan()) {
                $lines[] = TextFormat::colorize(' &r&l&cDeathban&r&7: &c' . TimeUtils::secsToMMSS($time = $deathban->getDeathBanTimeLeft()));
                $lines[] = TextFormat::colorize(' &r&l&6Lives&r&7: &c' . $deathban->getLives());
            }
        }*/

		if ($this->session->getKitClass() !== null) {
			$kitClass = $this->session->getKitClass();

			if ($kitClass->hasEnergy() && $session->getEnergy(strtolower($kitClass->getName()) . '_energy') !== null) {
				$energy = $session->getEnergy(strtolower($kitClass->getName()) . '_energy');
				$lines[] = ' &l&6| ' . $energy->getFormat() . ' &c' . round($energy->getValue(), 2);
			}
		}

		if ($faction !== null) {
			$focusFaction = $faction->getFocusFaction();
			$rallyMember = $faction->getRallyMember();

			if ($focusFaction !== null) {
				if (FactionFactory::get($focusFaction->getName()) === null) {
					$faction->setFocusFaction(null);
					return;
				}

				if (count($lines) > 1) {
					$lines[] = ' &r &r ';
				}
				$lines = array_merge($lines, [
					' &l&6| &l*Focus ' . $focusFaction->getName(),
					' &l&6| &eHQ&r&7:&7 ' . ($focusFaction->getHome() !== null ? Utils::vectorToString($focusFaction->getHome()->asVector3(), ', ') : 'Has no home'),
					' &l&6| &eDTR&r&7:&a ' . $focusFaction->getDtrWithColors(),
					' &l&6| &eOnline&r&7:&c ' . count($focusFaction->getOnlineMembers())
				]);
			}

			if ($rallyMember !== null) {
				if (count($lines) > 1) {
					$lines[] = ' &r&r ';
				}
				$lines = array_merge($lines, [
					' &l&6| &l*Rally ' . $rallyMember->getSession()->getName(),
					' &l&6| &dCoords&r&7: ' . Utils::vectorToString($rallyMember->getLastPosition(), ', ')
				]);
			}
		}
		
       // $scoreAnimated = str_replace(["&"], ["§"], $scoreName[$this->numb]);
		$lines[] = '&r&r';
		$lines[] = ' &0&lTesg';

        /*$this->numb++;
        if(($this->numb) >= count($scoreName)){
            $this->numb = 0;
        }*/
        
		if (count($lines) === 3) {
			if ($this->spawned) {
				$this->despawn();
			}
			return;
		}
		
		/*$scoreAnimated = Utils::getScoreFile();
		if ($scoreAnimated->get("score-animated-active")) {
			$delay = $scoreAnimated->get("delay");
            $period = $scoreAnimated->get("period");
            $handler = HCF::getInstance()->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function(): void {
				$scoreAnimated = Utils::getScoreFile();
				$msgs = $scoreAnimated->get("titles");
				if($scoreAnimated->get("random")) {
					$i = array_rand($msgs);
                } else {
                	$i = $this->count;
				    $this->count++;
				    if($this->count === count($msgs)) $this->count = 0;
                }
                $this->title = $msgs[$i];
                if ($this->session->isOnline()) {
                    $this->setTitle($this->title);
                }
                //$this->setTitle($this->title);
            }), $delay, $period);
        }*/

		if ($this->spawned) {
			$this->clear();
		} else {
			$this->spawn();
		}

		foreach ($lines as $content) {
			$this->addLine(TextFormat::colorize($content));
		}
	}
	
	public function updateScoreboardAnimated() : void {
		$scoreAnimated = Utils::getScoreFile();
		$msgs = $scoreAnimated->get("titles");
		if($scoreAnimated->get("random")) {
					$i = array_rand($msgs);
                } else {
                	$i = $this->count;
				    $this->count++;
				    if($this->count === count($msgs)) $this->count = 0;
                }
                $this->title = $msgs[$i];
                if ($this->session->isOnline()) {
                    $this->setTitle($this->title);
                }
    }
	
	public function setTitle(string $newName, bool $update = true): void {
		$this->title = $newName;
		if($update) {
			$this->respawn();
		}
	}
    
    public function sendLines(?array $lines = null): void {
		$pk = new SetScorePacket();
		$pk->type = SetScorePacket::TYPE_CHANGE;
		$pk->entries = $lines !== null ? $lines : $this->lines;
        $this->session->getPlayer()->getNetworkSession()->sendDataPacket($pk);
	}
	
	public function respawn(): void {
		$this->despawn();
		$this->spawn();
        $this->sendLines();
		/*foreach ($this->lines as $content) {
			$this->addLine(TextFormat::colorize($content));
		}*/
	}

	public function despawn() : void {
		$pk = RemoveObjectivePacket::create(
			$this->session?->getPlayer()?->getName()
		);
		$this->session->getPlayer()?->getNetworkSession()->sendDataPacket($pk);
		$this->spawned = false;
	}

	public function clear() : void {
		$packet = new SetScorePacket();
		$packet->entries = $this->lines;
		$packet->type = SetScorePacket::TYPE_REMOVE;
		$this->session->getPlayer()?->getNetworkSession()->sendDataPacket($packet);
		$this->lines = [];
	}

	public function spawn() : void {
		$packet = SetDisplayObjectivePacket::create(
			SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR,
			$this->session->getPlayer()?->getName(),
			TextFormat::colorize($this->title),
			'dummy',
			SetDisplayObjectivePacket::SORT_ORDER_ASCENDING
		);
		$this->session->getPlayer()?->getNetworkSession()->sendDataPacket($packet);
		$this->spawned = true;
	}

	public function addLine(string $line, ?int $id = null) : void {
		$id = $id ?? count($this->lines);

		$entry = new ScorePacketEntry();
		$entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;

		if (isset($this->lines[$id])) {
			$pk = new SetScorePacket();
			$pk->entries[] = $this->lines[$id];
			$pk->type = SetScorePacket::TYPE_REMOVE;
			$this->session->getPlayer()?->getNetworkSession()->sendDataPacket($pk);
			unset($this->lines[$id]);
		}
		$entry->scoreboardId = $id;
		$entry->objectiveName = $this->session->getPlayer()?->getName();
		$entry->score = $id;
		$entry->actorUniqueId = $this->session->getPlayer()?->getId();
		$entry->customName = $line;
		$this->lines[$id] = $entry;

		$packet = new SetScorePacket();
		$packet->entries[] = $entry;
		$packet->type = SetScorePacket::TYPE_CHANGE;
		$this->session->getPlayer()?->getNetworkSession()->sendDataPacket($packet);
	}
}
