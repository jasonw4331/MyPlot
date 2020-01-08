<?php
declare(strict_types=1);
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\utils\Config;

class JSONDataProvider extends DataProvider {
	/** @var MyPlot $plugin */
	protected $plugin;
	/** @var Config $json */
	private $json;

	/**
	 * JSONDataProvider constructor.
	 *
	 * @param MyPlot $plugin
	 * @param int $cacheSize
	 */
	public function __construct(MyPlot $plugin, int $cacheSize = 0) {
		parent::__construct($plugin, $cacheSize);
		@mkdir($this->plugin->getDataFolder() . "Data");
		$this->json = new Config($this->plugin->getDataFolder() . "Data" . DIRECTORY_SEPARATOR . "plots.yml", Config::JSON, ["count" => -1, "plots" => []]);
	}

	/**
	 * @param Plot $plot
	 *
	 * @return bool
	 */
	public function savePlot(Plot $plot) : bool {
		$plots = $this->json->get("plots", []);
		if($plot->id > -1) {
			$plots[$plot->id] = ["level" => $plot->levelName, "x" => $plot->X, "z" => $plot->Z, "name" => $plot->name, "owner" => $plot->owner, "helpers" => $plot->helpers, "denied" => $plot->denied, "biome" => $plot->biome, "pvp" => $plot->pvp];
		}else{
			$id = $this->json->get("count", 0) + 1;
			$plot->id = $id;
			$plots[$id] = ["level" => $plot->levelName, "x" => $plot->X, "z" => $plot->Z, "name" => $plot->name, "owner" => $plot->owner, "helpers" => $plot->helpers, "denied" => $plot->denied, "biome" => $plot->biome, "pvp" => $plot->pvp];
			$this->json->set("count", $id);
		}
		$this->cachePlot($plot);
		return $this->json->save();
	}

	/**
	 * @param Plot $plot
	 *
	 * @return bool
	 */
	public function deletePlot(Plot $plot) : bool {
		$plots = $this->json->get("plots", []);
		unset($plots[$plot->id]);
		$this->json->set("plots", $plots);
		$plot = new Plot($plot->levelName, $plot->X, $plot->Z);
		$this->cachePlot($plot);
		return $this->json->save();
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
		$plots = $this->json->get("plots", []);
		$levelKeys = $xKeys = $zKeys = [];
		foreach($plots as $key => $plotData) {
			if($plotData["level"] === $levelName)
				$levelKeys[] = $key;
			if($plotData["x"] === $X)
				$xKeys[] = $key;
			if($plotData["z"] === $Z)
				$zKeys[] = $key;
		}
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
		if(is_int($key)) {
			$plotName = (string)$plots[$key]["name"];
			$owner = (string)$plots[$key]["owner"];
			$helpers = (array)$plots[$key]["helpers"];
			$denied = (array)$plots[$key]["denied"];
			$biome = strtoupper($plots[$key]["biome"]);
			$pvp = (bool)$plots[$key]["pvp"];
			return new Plot($levelName, $X, $Z, $plotName, $owner, $helpers, $denied, $biome, $pvp, $key);
		}
		return new Plot($levelName, $X, $Z);
	}

	/**
	 * @param string $owner
	 * @param string $levelName
	 *
	 * @return Plot[]
	 */
	public function getPlotsByOwner(string $owner, string $levelName = "") : array {
		$plots = $this->json->get("plots", []);
		$ownerPlots = [];
		if($levelName != "") {
			/** @var int[] $levelKeys */
			$levelKeys = array_keys($plots, $levelName);
			/** @var int[] $ownerKeys */
			$ownerKeys = array_keys($plots, $owner);
			foreach($levelKeys as $levelKey) {
				foreach($ownerKeys as $ownerKey) {
					if($levelKey === $ownerKey) {
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
			/** @var int[] $ownerKeys */
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
	public function getNextFreePlot(string $levelName, int $limitXZ = 0) : ?Plot {
		$plotsArr = $this->json->get("plots", []);
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
			foreach($existing as $XZ) {
				$plots[$XZ[0]][$XZ[1]] = true;
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
		$this->json->save();
		unset($this->json);
	}
}