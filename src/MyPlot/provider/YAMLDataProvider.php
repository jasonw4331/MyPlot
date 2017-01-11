<?php
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\utils\Config;

class YAMLDataProvider extends DataProvider{
	/** @var Plot[] */
	private $cache = [];
	/** @var int */
	private $cacheSize;
	/** @var MyPlot */
	protected $plugin;
	/** @var Config */
	private $yaml1, $yaml2;

	public function __construct(MyPlot $plugin, $cacheSize = 0) {
		parent::__construct($plugin, $cacheSize);
		$this->cacheSize = $cacheSize;
		$this->yaml1 = new Config($this->plugin->getDataFolder()."Data\\plots.yml", Config::YAML, []);
		$this->yaml2 = new Config($this->plugin->getDataFolder()."Data\\players.yml", Config::YAML, []);
	}
	/**
	 * @param Plot $plot
	 * @return bool
	 */
	public function savePlot(Plot $plot) : bool {
		$this->yaml->set("");
	}
	/**
	 * @param Plot $plot
	 * @return bool
	 */
	public function deletePlot(Plot $plot) : bool {
		$this->yaml->set("");
	}
	/**
	 * @param string $levelName
	 * @param int $X
	 * @param int $Z
	 * @return Plot
	 */
	public function getPlot($levelName, $X, $Z) : Plot {
		$this->yaml->getNested("");
	}
	/**
	 * @param string $owner
	 * @param string $levelName
	 * @return Plot[]
	 */
	public function getPlotsByOwner($owner, $levelName = "") : array {
		$this->yaml->get("");
	}
	/**
	 * @param string $levelName
	 * @param int $limitXZ
	 * @return Plot|null
	 */
	public function getNextFreePlot($levelName, $limitXZ = 0){
		$this->yaml->get("");
	}
	public function close(){
		unset($this->yaml);
	}
}