<?php

namespace hcf\module\airdrop;

use pocketmine\item\Item;

class Airdrop
{
    public array $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function getRandomItems(): Item
    {
        return $this->items[array_rand($this->items)];
    }
}