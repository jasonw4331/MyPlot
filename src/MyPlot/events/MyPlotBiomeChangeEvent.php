<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;

class MyPlotBiomeChangeEvent extends MyPlotEvent {
	/** @var int  */
	private $newBiome, $oldBiome;
	/** @var Plot $plot */
	private $plot;
	public function __construct(MyPlot $plugin, string $issuer, Plot $plot, int $newBiome, int $oldBiome) {
		$this->newBiome = $newBiome;
		$this->oldBiome = $oldBiome;
		parent::__construct($plugin, $issuer);
	}
	public function getPlot() : Plot {
		return $this->plot;
	}
	public function setPlot(Plot $plot) {
		$this->plot = $plot;
	}
	public function getNewBiome() : int {
		return $this->newBiome;
	}
	public function setNewBiome(int $biome) {
		$this->newBiome = $biome;
	}
	public function getOldBiome() : int {
		return $this->oldBiome;
	}
}