<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;

class MyPlotBiomeChangeEvent extends MyPlotPlotEvent {
	/** @var int  */
	private $newBiome, $oldBiome;
	public function __construct(MyPlot $plugin, string $issuer, Plot $plot, int $newBiome, int $oldBiome) {
		$this->newBiome = $newBiome;
		$this->oldBiome = $oldBiome;
		parent::__construct($plugin, $issuer, $plot);
	}
	public function getNewBiomeId() : int {
		return $this->newBiome;
	}
	public function setNewBiomeId(int $biome) {
		$this->newBiome = $biome;
	}
	public function getOldBiomeId() : int {
		return $this->oldBiome;
	}
}