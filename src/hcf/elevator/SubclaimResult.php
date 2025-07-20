<?php

namespace hcf\elevator;

use pocketmine\player\Player;

class SubclaimResult
{
    protected bool $isSubclaim;

    /** @var string[] */
    protected array $playersAllowed;

    /**
     * @param bool $isSubclaim
     * @param string[] $playersAllowed
     */
    public function __construct(bool $isSubclaim, array $playersAllowed)
    {
        $this->isSubclaim = $isSubclaim;
        $this->playersAllowed = $playersAllowed;
    }

    public function isSubclaim(): bool
    {
        return $this->isSubclaim;
    }

    /**
     * @return string[]
     */
    public function getPlayersAllowed(): array
    {
        return $this->playersAllowed;
    }

    public function isPlayerAllowed(Player $player): bool
    {
        return !$this->isSubclaim || in_array($player->getName(), $this->playersAllowed);
    }
}