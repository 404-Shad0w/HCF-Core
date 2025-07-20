<?php


namespace CortexPE\Rave\filter\blur\kernel;


interface Kernel {
	public function __construct(int $radius);
	public function getRadius(): int;
	public function valueAt(int $x, int $y): float;
}