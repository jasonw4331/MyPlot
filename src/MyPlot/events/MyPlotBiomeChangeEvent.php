<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\event\Cancellable;

class MyPlotBiomeChangeEvent extends MyPlotPlotEvent implements Cancellable {
	public static $handlerList = null;
	/** @var int  */
	private $newBiome, $oldBiome;

	/**
	 * MyPlotBiomeChangeEvent constructor.
	 *
	 * @param MyPlot $plugin
	 * @param string $issuer
	 * @param Plot $plot
	 * @param int $newBiome
	 * @param int $oldBiome
	 */
	public function __construct(MyPlot $plugin, string $issuer, Plot $plot, int $newBiome, int $oldBiome) {
		$this->newBiome = $newBiome;
		$this->oldBiome = $oldBiome;
		parent::__construct($plugin, $issuer, $plot);
	}

	/**
	 * @return int
	 */
	public function getNewBiomeId() : int {
		return $this->newBiome;
	}

	/**
	 * @param int $biome
	 */
	public function setNewBiomeId(int $biome) {
		$this->newBiome = $biome;
	}

	/**
	 * @return int
	 */
	public function getOldBiomeId() : int {
		return $this->oldBiome;
	}
}