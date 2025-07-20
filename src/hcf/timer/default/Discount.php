<?php

declare(strict_types=1);

namespace hcf\timer\default;

use hcf\HCF;
use hcf\timer\Timer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class Discount extends Timer {

	public function __construct() {
		parent::__construct('Discount', 'Use timer to Discount', "&630% off sale&r&7:", 24 * 3600);
	}

	public function setEnabled(bool $enabled) : void {
		parent::setEnabled($enabled);
  }
}
