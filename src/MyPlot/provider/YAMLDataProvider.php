<?php
declare(strict_types=1);
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\utils\Config;

class YAMLDataProvider extends DataProvider {
	/** @var MyPlot $plugin */
	protected $plugin;
	/** @var Config $yaml */
	private $yaml;

	/**
	 * YAMLDataProvider constructor.
	 *
	 * @param MyPlot $plugin
	 * @param int $cacheSize
	 */
	public function __construct(MyPlot $plugin, int $cacheSize = 0) {
		parent::__construct($plugin, $cacheSize);
		$this->yaml = new Config($this->plugin->getDataFolder() . "Data" . DIRECTORY_SEPARATOR . "plots.yml", Config::YAML, ["count" => 0, "plots" => []]);
	}

	/**
	 * @param Plot $plot
	 *
	 * @return bool
	 */
	public function savePlot(Plot $plot) : bool {
		$plots = $this->yaml->get("plots", []);
		$plots[$plot->id] = ["level" => $plot->levelName, "x" => $plot->X, "z" => $plot->Z, "name" => $plot->name, "owner" => $plot->owner, "helpers" => $plot->helpers, "denied" => $plot->denied, "biome" => $plot->biome, "pvp" => $plot->pvp];
		$this->yaml->set("plots", $plots);
		$this->cachePlot($plot);
		return $this->yaml->save();
	}

	/**
	 * @param Plot $plot
	 *
	 * @return bool
	 */
	public function deletePlot(Plot $plot) : bool {
		$plots = $this->yaml->get("plots", []);
		unset($plots[$plot->id]);
		$this->yaml->set("plots", $plots);
		$this->cachePlot($plot);
		return $this->yaml->save();
	}

	/**
	 * @param string $levelName
	 * @param int $X
	 * @param int $Z
	 *
	 * @return Plot
	 */
	public function getPlot(string $levelName, int $X, int $Z) : Plot {
		if(($plot = $this->getPlotFromCache($levelName, $X, $Z)) !== null) {
			return $plot;
		}
		$plots = $this->yaml->get("plots");
		$levelKeys = array_keys($plots, $levelName);
		$xKeys = array_keys($plots, $X);
		$zKeys = array_keys($plots, $Z);
		/** @var int|null $key */
		$key = null;
		foreach($levelKeys as $levelKey) {
			foreach($xKeys as $xKey) {
				foreach($zKeys as $zKey) {
					if($zKey == $xKey and $xKey == $levelKey and $zKey == $levelKey) {
						$key = $levelKey;
						break 3;
					}
				}
			}
		}
		if($key != null) {
			$plotName = $plots[$key]["name"] == "" ? "" : $plots[$key]["name"];
			$owner = $plots[$key]["owner"] == "" ? "" : $plots[$key]["owner"];
			$helpers = $plots[$key]["helpers"] == [] ? [] : $plots[$key]["helpers"];
			$denied = $plots[$key]["denied"] == [] ? [] : $plots[$key]["denied"];
			$biome = strtoupper($plots[$key]["biome"]) == "PLAINS" ? "PLAINS" : strtoupper($plots[$key]["biome"]);
			$pvp = $plot[$key]["pvp"] == null ? false : $plot[$key]["pvp"];
			return new Plot($levelName, $X, $Z, $plotName, $owner, $helpers, $denied, $biome, $pvp, $key);
		}
		$count = $this->yaml->get("count", 0);
		$this->yaml->set("count", (int) $count++);
		$this->yaml->save();
		return new Plot($levelName, $X, $Z, "", "", [], [], "PLAINS", true, (int) $count);
	}

	/**
	 * @param string $owner
	 * @param string $levelName
	 *
	 * @return Plot[]
	 */
	public function getPlotsByOwner(string $owner, string $levelName = "") : array {
		$plots = $this->yaml->get("plots", []);
		$ownerPlots = [];
		if($levelName != "") {
			$levelKeys = array_keys($plots, $levelName);
			$ownerKeys = array_keys($plots, $owner);
			foreach($levelKeys as $levelKey) {
				foreach($ownerKeys as $ownerKey) {
					if($levelKey == $ownerKey) {
						$X = $plots[$levelKey]["x"];
						$Z = $plots[$levelKey]["z"];
						$plotName = $plots[$levelKey]["name"] == "" ? "" : $plots[$levelKey]["name"];
						$owner = $plots[$levelKey]["owner"] == "" ? "" : $plots[$levelKey]["owner"];
						$helpers = $plots[$levelKey]["helpers"] == [] ? [] : $plots[$levelKey]["helpers"];
						$denied = $plots[$levelKey]["denied"] == [] ? [] : $plots[$levelKey]["denied"];
						$biome = strtoupper($plots[$levelKey]["biome"]) == "PLAINS" ? "PLAINS" : strtoupper($plots[$levelKey]["biome"]);
						$pvp = $plots[$levelKey]["pvp"] == null ? false : $plots[$levelKey]["pvp"];
						$ownerPlots[] = new Plot($levelName, $X, $Z, $plotName, $owner, $helpers, $denied, $biome, $pvp, $levelKey);
					}
				}
			}
		}else{
			$ownerKeys = array_keys($plots, $owner);
			foreach($ownerKeys as $key) {
				$levelName = $plots[$key]["level"];
				$X = $plots[$key]["x"];
				$Z = $plots[$key]["z"];
				$plotName = $plots[$key]["name"] == "" ? "" : $plots[$key]["name"];
				$owner = $plots[$key]["owner"] == "" ? "" : $plots[$key]["owner"];
				$helpers = $plots[$key]["helpers"] == [] ? [] : $plots[$key]["helpers"];
				$denied = $plots[$key]["denied"] == [] ? [] : $plots[$key]["denied"];
				$biome = strtoupper($plots[$key]["biome"]) == "PLAINS" ? "PLAINS" : strtoupper($plots[$key]["biome"]);
				$pvp = $plots[$key]["pvp"] == null ? false : $plots[$key]["pvp"];
				$ownerPlots[] = new Plot($levelName, $X, $Z, $plotName, $owner, $helpers, $denied, $biome, $pvp, $key);
			}
		}
		return $ownerPlots;
	}

	/**
	 * @param string $levelName
	 * @param int $limitXZ
	 *
	 * @return Plot|null
	 */
	public function getNextFreePlot(string $levelName, int $limitXZ = 0) : ?plot {
		$plotsArr = $this->yaml->get("plots", []);
		for($i = 0; $limitXZ <= 0 or $i < $limitXZ; $i++) {
			$existing = [];
			foreach($plotsArr as $id => $data) {
				if($data["level"] === $levelName) {
					if(abs($data["x"]) === $i and abs($data["z"]) <= $i) {
						$existing[] = [$data["x"], $data["z"]];
					}elseif(abs($data["z"]) === $i and abs($data["x"]) <= $i) {
						$existing[] = [$data["x"], $data["z"]];
					}
				}
			}
			$plots = [];
			foreach($existing as $arr) {
				$plots[$arr[0]][$arr[1]] = true;
			}
			if(count($plots) === max(1, 8 * $i)) {
				continue;
			}
			if($ret = self::findEmptyPlotSquared(0, $i, $plots)) {
				list($X, $Z) = $ret;
				$plot = new Plot($levelName, $X, $Z);
				$this->cachePlot($plot);
				return $plot;
			}
			for($a = 1; $a < $i; $a++) {
				if($ret = self::findEmptyPlotSquared($a, $i, $plots)) {
					list($X, $Z) = $ret;
					$plot = new Plot($levelName, $X, $Z);
					$this->cachePlot($plot);
					return $plot;
				}
			}
			if($ret = self::findEmptyPlotSquared($i, $i, $plots)) {
				list($X, $Z) = $ret;
				$plot = new Plot($levelName, $X, $Z);
				$this->cachePlot($plot);
				return $plot;
			}
		}
		return null;
	}

	public function close() : void {
		unset($this->yaml);
	}
}