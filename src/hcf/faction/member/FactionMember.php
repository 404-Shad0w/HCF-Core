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

namespace hcf\faction\member;

use hcf\session\Session;
use pocketmine\world\Position;

final class FactionMember {

	public const RANK_MEMBER = 0;
	public const RANK_OFFICER = 1;
	public const RANK_COLEADER = 2;
	public const RANK_LEADER = 3;

	public function __construct(
		private Session   $session,
		private int       $rank,
		private ?Position $lastPosition = null
	) {}

	public function getSession() : Session {
		return $this->session;
	}

	public function getRank() : int {
		return $this->rank;
	}

	public function getLastPosition() : ?Position {
		return $this->lastPosition;
	}

	public function setRank(int $rank) : void {
		$this->rank = $rank;
	}

	public function setLastPosition(?Position $lastPosition) : void {
		$this->lastPosition = $lastPosition;
	}
}
