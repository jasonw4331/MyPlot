<?php
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\Plot;

abstract class DataProvider
{
	/** @var Plot[] $cache */
	private $cache = [];
	/** @var int $cacheSize */
	private $cacheSize;
	/** @var MyPlot $plugin */
	protected $plugin;

	/**
	 * DataProvider constructor.
	 *
	 * @param MyPlot $plugin
	 * @param int $cacheSize
	 */
	public function __construct(MyPlot $plugin, int $cacheSize = 0) {
		$this->plugin = $plugin;
		$this->cacheSize = $cacheSize;
	}

	/**
	 * @param Plot $plot
	 */
	protected final function cachePlot(Plot $plot) {
		if($this->cacheSize > 0) {
			$key = $plot->levelName . ';' . $plot->X . ';' . $plot->Z;
			if(isset($this->cache[$key])) {
				unset($this->cache[$key]);
			}
			elseif($this->cacheSize <= count($this->cache)) {
				array_shift($this->cache);
			}
			$this->cache = array_merge([$key => clone $plot], $this->cache);
			$this->plugin->getLogger()->debug("Plot {$plot->X};{$plot->Z} has been cached");
		}
	}

	/**
	 * @param $levelName
	 * @param $X
	 * @param $Z
	 *
	 * @return Plot|null
	 */
	protected final function getPlotFromCache(string $levelName, int $X, int $Z) {
		if($this->cacheSize > 0) {
			$key = $levelName . ';' . $X . ';' . $Z;
			if(isset($this->cache[$key])) {
				#$this->plugin->getLogger()->debug("Plot {$X};{$Z} was loaded from the cache");
				return $this->cache[$key];
			}
		}
		return null;
	}

	/**
	 * @param Plot $plot
	 *
	 * @return bool
	 */
	public abstract function savePlot(Plot $plot);

	/**
	 * @param Plot $plot
	 *
	 * @return bool
	 */
	public abstract function deletePlot(Plot $plot);

	/**
	 * @param string $levelName
	 * @param int $X
	 * @param int $Z
	 *
	 * @return Plot
	 */
	public abstract function getPlot(string $levelName, int $X, int $Z);

	/**
	 * @param string $owner
	 * @param string $levelName
	 *
	 * @return Plot[]
	 */
	public abstract function getPlotsByOwner(string $owner, string $levelName = "");

	/**
	 * @param string $levelName
	 * @param int $limitXZ
	 *
	 * @return Plot|null
	 */
	public abstract function getNextFreePlot(string $levelName, int $limitXZ = 0);

	public abstract function close();

	/**
	 * @param int $a
	 * @param int $b
	 * @param array[] $plots
	 *
	 * @return array|null
	 */
	protected static function findEmptyPlotSquared(int $a, int $b, array $plots) {
		if(!isset($plots[$a][$b]))
			return [$a, $b];
		if(!isset($plots[$b][$a]))
			return [$b, $a];
		if($a !== 0) {
			if(!isset($plots[-$a][$b]))
				return [-$a, $b];
			if(!isset($plots[$b][-$a]))
				return [$b, -$a];
		}
		if($b !== 0) {
			if(!isset($plots[-$b][$a]))
				return [-$b, $a];
			if(!isset($plots[$a][-$b]))
				return [$a, -$b];
		}
		if($a | $b === 0) {
			if(!isset($plots[-$a][-$b]))
				return [-$a, -$b];
			if(!isset($plots[-$b][-$a]))
				return [-$b, -$a];
		}
		return null;
	}
}