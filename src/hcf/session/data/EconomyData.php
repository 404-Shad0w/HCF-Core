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

namespace hcf\session\data;

trait EconomyData {

	private int $balance = 0;
    private int $killKey = 0;

    public function getKillKey(): int {
        return $this->killKey;
    }
    
    public function setKillKey(int $value): void {
        $this->killKey = $value;
    }
    
    public function addKillKey(int $value): void {
        $this->killKey += $value;
    }
    
    public function remvKillKey(int $value): void {
        $this->killKey -= $value;
    }
    
	public function getBalance() : int {
		return $this->balance;
	}

	public function setBalance(int $value) : void {
		$this->balance = $value;
	}

	public function increaseBalance(int $value) : void {
		$this->balance += $value;
	}

	public function decreaseBalance(int $value) : void {
		$this->balance -= $value;
	}
}
