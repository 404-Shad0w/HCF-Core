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

namespace hcf\session\handler;

use hcf\claim\Claim;
use hcf\session\Session;

trait HandlerTrait {

	private ?ClaimCreatorHandler $claimCreatorHandler = null;
	private ?KitHandler $kitHandler = null;

	public function getClaimCreatorHandler() : ?ClaimCreatorHandler {
		return $this->claimCreatorHandler;
	}

	public function getKitHandler() : ?KitHandler {
		return $this->kitHandler;
	}

	public function startClaimCreatorHandler(Session $session, string $name, string $type = Claim::FACTION) : ClaimCreatorHandler {
		$this->claimCreatorHandler = $claimCreatorHandler = new ClaimCreatorHandler(session: $session, name: $name, type: $type);
		return $claimCreatorHandler;
	}

	public function startKitHandler(Session $session, string $kitName, int $mode = KitHandler::CREATE) : KitHandler {
		$this->kitHandler = $kitHandler = new KitHandler(session: $session, name: $kitName, mode: $mode);
		return $kitHandler;
	}

	public function stopKitHandler() : void {
		$this->kitHandler = null;
	}

	public function stopClaimCreatorHandler() : void {
		$this->claimCreatorHandler = null;
	}
}
