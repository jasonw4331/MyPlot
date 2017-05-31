<?php
namespace MyPlot\events;

use MyPlot\MyPlot;

class MyPlotBiomeChangeEvent extends MyPlotEvent {
	/** @var int  */
	private $newBiome, $oldBiome;
	public function __construct(MyPlot $plugin, $issuer, int $newBiome, int $oldBiome) {
		$this->newBiome = $newBiome;
		$this->oldBiome = $oldBiome;
		parent::__construct($plugin, $issuer);
	}
	public function getNewBiome() : int{
		return $this->newBiome;
	}

	public function getOldBiome() : int{
		return $this->oldBiome;
	}
}