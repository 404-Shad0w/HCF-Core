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

namespace hcf\faction;

use hcf\claim\Claim;
use hcf\claim\ClaimFactory;
use hcf\faction\member\FactionMember;
use hcf\HCF;
use hcf\session\Session;
use hcf\util\Utils;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as TE;
use pocketmine\world\Position;
use function array_filter;
use function count;

final class Faction {

	/**
	 * @param FactionMember[] $members
	 */
	public function __construct(
		private string         $name,
		private int            $balance,
		private int            $points,
		private int            $kothCaptures,
		private int            $strikes,
		private int            $regenCooldown = -1,
		private float          $deathsUntilRaidable = 1.1,
		private array          $members = [],
		private bool          $lockClaim = false,
		private ?Position      $home = null,
		private ?Claim         $claim = null,
		private ?Faction       $focusFaction = null,
		private ?FactionMember $rallyMember = null
	) {}
	
	public function getLockClaim() : bool {
		return $this->lockClaim;
    }
    
    public function setLockClaim(bool $lockClaim) : void {
    	$this->lockClaim = $lockClaim;
    }

	public function getBalance() : int {
		return $this->balance;
	}

	public function getPoints() : int {
		return $this->points;
	}

	public function getKothCaptures() : int {
		return $this->kothCaptures;
	}

	public function getStrikes() : int {
		return $this->strikes;
	}

	public function getRegenCooldown() : int {
		return $this->regenCooldown;
	}

	public function getDeathsUntilRaidable() : float {
		return $this->deathsUntilRaidable;
	}

	public function getMembers() : array {
		return $this->members;
	}

	/**
	 * @return FactionMember[]
	 */
	public function getOnlineMembers() : array {
		return array_filter($this->members, function (FactionMember $member) : bool {
			return $member->getSession()->isOnline();
		});
	}

	public function getHome() : ?Position {
		return $this->home;
	}

	public function getClaim() : ?Claim {
		return $this->claim;
	}

	public function getFocusFaction() : ?Faction {
		return $this->focusFaction;
	}

	public function getRallyMember() : ?FactionMember {
		return $this->rallyMember;
	}

	public function getMember(Session $session) : ?FactionMember {
		return $this->members[$session->getXuid()] ?? null;
	}

	public function isRaidable() : bool {
		return $this->deathsUntilRaidable <= 0.00;
	}

	public function isFull() : bool {
		return count($this->members) >= (int) HCF::getInstance()->getConfig()->get('faction.max-members', 8);
	}

	public function equals(?self $target) : bool {
		return $target !== null && $this->name === $target->getName();
	}

	public function getName() : string {
		return $this->name;
	}

	public function setStrikes(int $strikes) : void {
		$this->strikes = $strikes;
	}

	public function setRegenCooldown(int $regenCooldown) : void {
		$this->regenCooldown = $regenCooldown;
	}

	public function setKothCaptures(int $kothCaptures) : void {
		$this->kothCaptures = $kothCaptures;
	}

	public function setDeathsUntilRaidable(float $deathsUntilRaidable) : void {
		$this->deathsUntilRaidable = $deathsUntilRaidable;
	}

	public function setBalance(int $balance) : void {
		$this->balance = $balance;
	}

	public function setPoints(int $points) : void {
		$this->points = $points;
	}

	public function addMember(Session $session, int $rank = FactionMember::RANK_MEMBER) : void {
		$this->members[$session->getXuid()] = new FactionMember($session, $rank);
	}

	public function setHome(?Position $home) : void {
		$this->home = $home;
	}

	public function setClaim(?Claim $claim) : void {
		$this->claim = $claim;
	}

	public function setFocusFaction(?Faction $focusFaction) : void {
		$this->focusFaction = $focusFaction;
	}

	public function setRallyMember(?FactionMember $rallyMember) : void {
		$this->rallyMember = $rallyMember;
	}
	
	public function getDtrWithColors() : string {
        return match(true){
            $this->deathsUntilRaidable === count($this->members) + 0.1 => TE::GREEN.$this->deathsUntilRaidable . '  ',
            $this->deathsUntilRaidable <= 0 => TE::RED.$this->deathsUntilRaidable . '  ',
            default => TE::YELLOW.$this->deathsUntilRaidable . '  ',
        };
    }

	public function update() : void {
		$regenCooldown = $this->regenCooldown;

		if ($regenCooldown !== -1) {
			$this->regenCooldown--;

			if (--$this->regenCooldown <= 0) {
				$this->regenCooldown = -1;
				$this->deathsUntilRaidable = count($this->members) + 0.1;

				foreach ($this->getOnlineMembers() as $member) {
					$member->getSession()->getPlayer()?->setScoreTag(TextFormat::colorize('&6[&c' . $this->getName() . ' &c' . $this->getDeathsUntilRaidable() . '&6]'));
				}
			}
		}
	}

	public function announce(string $message) : void {
		foreach ($this->members as $member) {
			$member->getSession()->getPlayer()?->sendMessage(TextFormat::colorize($message));
		}
	}

	public function chat(string $sender, string $message) : void {
		foreach ($this->members as $member) {
			$member->getSession()->getPlayer()?->sendMessage(TextFormat::colorize('&e[Faction] ' . $sender . ': ' . $message));
		}
	}

	public function disband(bool $announce = true) : void {
		foreach ($this->members as $member) {
			$session = $member->getSession();
			$session->setFaction(null);
			$session->getPlayer()?->setNameTag(TextFormat::colorize('&c' . $member->getSession()->getName()));
			$session->getPlayer()?->setScoreTag('');
		}
		FactionFactory::remove($this->name);
	}

	public function removeMember(Session $session) : void {
		if (!$this->isMember($session)) {
			return;
		}
		$session->setFaction(null);
		unset($this->members[$session->getXuid()]);
	}

	public function isMember(Session|string $session) : bool {
		return isset($this->members[$session instanceof Session ? $session->getXuid() : $session]);
	}

	public static function deserializeData(string $name, array $data) : Faction {
		$faction = new Faction(
			$name,
			(int) $data['balance'],
			(int) $data['points'],
			(int) $data['koth-captures'],
			(int) $data['strikes'],
			(int) $data['regen-cooldown'],
			(float) $data['deaths-until-raidable']
		);

		if ($data['home'] !== null) {
			$faction->setHome(Utils::stringToPosition($data['home']));
		}

		if ($data['claim'] !== null) {
			$firstPosition = Utils::stringToPosition($data['claim']['firstPosition']);
			$secondPosition = Utils::stringToPosition($data['claim']['secondPosition']);

			$claimType = match ($name) {
				'Spawn', 'Nether Spawn', 'DeathBan Spawn' => Claim::SPAWN,
                'DeathBan' => Claim::ROAD,
				'North Road', 'South Road', 'West Road', 'East Road', 'North_Road', 'South_Road', 'West_Road', 'East_Road' => Claim::ROAD,
				default => Claim::FACTION
			};
			$faction->setClaim(ClaimFactory::create($name, $firstPosition, $secondPosition, $firstPosition->getWorld(), $claimType));
		}
		return $faction;
	}

	public function serializeData() : array {
		$data = [
			'balance' => $this->balance,
			'points' => $this->points,
			'koth-captures' => $this->kothCaptures,
			'strikes' => $this->strikes,
			'regen-cooldown' => $this->regenCooldown,
			'deaths-until-raidable' => $this->deathsUntilRaidable,
			'home' => null,
			'claim' => null
		];

		if ($this->home !== null) {
			$data['home'] = Utils::positionToString($this->home);
		}

		if ($this->claim !== null) {
			$data['claim'] = [
				'firstPosition' => Utils::positionToString($this->claim->getFirstPosition()),
				'secondPosition' => Utils::positionToString($this->claim->getSecondPosition())
			];
		}
		return $data;
	}
}
