<?php


namespace CortexPE\Rave\filter\blur;


use CortexPE\Rave\filter\Filter;

abstract class Blur extends Filter {
	/** @var int */
	protected $radius;

	public function __construct(int $radius){
		$this->radius = $radius;
	}
}