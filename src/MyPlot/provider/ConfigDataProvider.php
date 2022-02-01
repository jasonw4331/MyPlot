<?php
declare(strict_types=1);
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\math\Facing;
use pocketmine\utils\Config;

class ConfigDataProvider extends DataProvider {
	protected MyPlot $plugin;
	private Config $config;

	/**
	 * JSONDataProvider constructor.
	 *
	 * @param MyPlot $plugin
	 * @param int $cacheSize
	 * @param bool $yaml
	 */
	public function __construct(MyPlot $plugin, int $cacheSize = 0, bool $yaml = false) {
		parent::__construct($plugin, $cacheSize);
		@mkdir($this->plugin->getDataFolder() . "Data");
		$this->config = new Config($this->plugin->getDataFolder() . "Data" . DIRECTORY_SEPARATOR . "plots".($yaml ? '.yml' : '.json'), $yaml ? Config::YAML : Config::JSON, ["plots" => [], "merges" => []]);
	}

	public function savePlot(Plot $plot) : bool {
		$plotId = $plot->levelName.';'.$plot->X.';'.$plot->Z;
		$plots = $this->config->get("plots", []);
		$plots[$plotId] = ["level" => $plot->levelName, "x" => $plot->X, "z" => $plot->Z, "name" => $plot->name, "owner" => $plot->owner, "helpers" => $plot->helpers, "denied" => $plot->denied, "biome" => $plot->biome, "pvp" => $plot->pvp, "price" => $plot->price];
		$this->config->set("plots", $plots);
		$this->cachePlot($plot);
		$this->config->save();
		return true;
	}

	public function deletePlot(Plot $plot) : bool {
		$plotId = $plot->levelName.';'.$plot->X.';'.$plot->Z;
		$plots = $this->config->get("plots", []);
		unset($plots[$plotId]);
		$this->config->set("plots", $plots);
		$plot = new Plot($plot->levelName, $plot->X, $plot->Z);
		$this->cachePlot($plot);
		$this->config->save();
		return true;
	}

	public function getPlot(string $levelName, int $X, int $Z) : Plot {
		if(($plot = $this->getPlotFromCache($levelName, $X, $Z)) !== null) {
			return $plot;
		}
		$plots = (array)$this->config->get("plots", []);
		$key = $levelName.';'.$X.';'.$Z;
		if(isset($plots[$key])) {
			$plotName = (string)$plots[$key]["name"];
			$owner = (string)$plots[$key]["owner"];
			$helpers = (array)$plots[$key]["helpers"];
			$denied = (array)$plots[$key]["denied"];
			$biome = strtoupper($plots[$key]["biome"]);
			$pvp = (bool)$plots[$key]["pvp"];
			$price = (float)$plots[$key]["price"];
			return new Plot($levelName, $X, $Z, $plotName, $owner, $helpers, $denied, $biome, $pvp, $price);
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
		$plots = $this->config->get("plots", []);
		$ownerPlots = [];
		/** @var string[] $ownerKeys */
		$ownerKeys = array_keys($plots, ["owner" => $owner], true);
		foreach($ownerKeys as $ownerKey) {
			if($levelName === "" or str_contains($ownerKey, $levelName)) {
				$X = $plots[$ownerKey]["x"];
				$Z = $plots[$ownerKey]["z"];
				$plotName = $plots[$ownerKey]["name"] == "" ? "" : $plots[$ownerKey]["name"];
				$owner = $plots[$ownerKey]["owner"] == "" ? "" : $plots[$ownerKey]["owner"];
				$helpers = $plots[$ownerKey]["helpers"] == [] ? [] : $plots[$ownerKey]["helpers"];
				$denied = $plots[$ownerKey]["denied"] == [] ? [] : $plots[$ownerKey]["denied"];
				$biome = strtoupper($plots[$ownerKey]["biome"]) == "PLAINS" ? "PLAINS" : strtoupper($plots[$ownerKey]["biome"]);
				$pvp = $plots[$ownerKey]["pvp"] == null ? false : $plots[$ownerKey]["pvp"];
				$price = $plots[$ownerKey]["price"] == null ? 0 : $plots[$ownerKey]["price"];
				$ownerPlots[] = new Plot($levelName, $X, $Z, $plotName, $owner, $helpers, $denied, $biome, $pvp, $price);
			}
		}
		return $ownerPlots;
	}

	public function getNextFreePlot(string $levelName, int $limitXZ = 0) : ?Plot {
		$plotsArr = $this->config->get("plots", []);
		for($i = 0; $limitXZ <= 0 or $i < $limitXZ; $i++) {
			$existing = [];
			foreach($plotsArr as $data) {
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
			if(($ret = self::findEmptyPlotSquared(0, $i, $plots)) !== null) {
				[$X, $Z] = $ret;
				$plot = new Plot($levelName, $X, $Z);
				$this->cachePlot($plot);
				return $plot;
			}
			for($a = 1; $a < $i; $a++) {
				if(($ret = self::findEmptyPlotSquared($a, $i, $plots)) !== null) {
					[$X, $Z] = $ret;
					$plot = new Plot($levelName, $X, $Z);
					$this->cachePlot($plot);
					return $plot;
				}
			}
			if(($ret = self::findEmptyPlotSquared($i, $i, $plots)) !== null) {
				[$X, $Z] = $ret;
				$plot = new Plot($levelName, $X, $Z);
				$this->cachePlot($plot);
				return $plot;
			}
		}
		return null;
	}

	public function mergePlots(Plot $base, Plot ...$plots) : bool {
		$originId = $base->levelName.';'.$base->X.';'.$base->Z;
		$mergedIds = $this->config->getNested("merges.$originId", []);
		$mergedIds = array_merge($mergedIds, array_map(function(Plot $val) : string {
			return $val->levelName.';'.$val->X.';'.$val->Z;
		}, $plots));
		$mergedIds = array_unique($mergedIds, SORT_NUMERIC);
		$this->config->setNested("merges.$originId", $mergedIds);
		$this->config->save();
		return true;
	}

	public function getMergedPlots(Plot $plot, bool $adjacent = false) : array {
		$originId = $plot->levelName.';'.$plot->X.';'.$plot->Z;
		$mergedIds = $this->config->getNested("merges.$originId", []);
		$plotDatums = $this->config->get("plots", []);
		$plots = [$plot];
		foreach($mergedIds as $mergedId) {
			if(!isset($plotDatums[$mergedId]))
				continue;
			$levelName = $plotDatums[$mergedId]["level"];
			$X = $plotDatums[$mergedId]["x"];
			$Z = $plotDatums[$mergedId]["z"];
			$plotName = $plotDatums[$mergedId]["name"] == "" ? "" : $plotDatums[$mergedId]["name"];
			$owner = $plotDatums[$mergedId]["owner"] == "" ? "" : $plotDatums[$mergedId]["owner"];
			$helpers = $plotDatums[$mergedId]["helpers"] == [] ? [] : $plotDatums[$mergedId]["helpers"];
			$denied = $plotDatums[$mergedId]["denied"] == [] ? [] : $plotDatums[$mergedId]["denied"];
			$biome = strtoupper($plotDatums[$mergedId]["biome"]) == "PLAINS" ? "PLAINS" : strtoupper($plotDatums[$mergedId]["biome"]);
			$pvp = $plotDatums[$mergedId]["pvp"] == null ? false : $plotDatums[$mergedId]["pvp"];
			$price = $plotDatums[$mergedId]["price"] == null ? 0 : $plotDatums[$mergedId]["price"];
			$plots[] = new Plot($levelName, $X, $Z, $plotName, $owner, $helpers, $denied, $biome, $pvp, $price);
		}
		if($adjacent)
			$plots = array_filter($plots, function(Plot $val) use ($plot) {
				for($i = Facing::NORTH; $i <= Facing::EAST; ++$i) {
					if($plot->getSide($i)->isSame($val))
						return true;
				}
				return false;
			});
		return $plots;
	}

	public function getMergeOrigin(Plot $plot) : Plot {
		$mergedIdString = $plot->levelName.';'.$plot->X.';'.$plot->Z;
		$allMerges = $this->config->get("merges", []);
		if(isset($allMerges[$mergedIdString]))
			return $plot;
		$originId = array_search($mergedIdString, $allMerges, true);
		$plotDatums = $this->config->get("plots", []);
		if(isset($plotDatums[$originId])) {
			$levelName = $plotDatums[$originId]["level"];
			$X = $plotDatums[$originId]["x"];
			$Z = $plotDatums[$originId]["z"];
			$plotName = $plotDatums[$originId]["name"] == "" ? "" : $plotDatums[$originId]["name"];
			$owner = $plotDatums[$originId]["owner"] == "" ? "" : $plotDatums[$originId]["owner"];
			$helpers = $plotDatums[$originId]["helpers"] == [] ? [] : $plotDatums[$originId]["helpers"];
			$denied = $plotDatums[$originId]["denied"] == [] ? [] : $plotDatums[$originId]["denied"];
			$biome = strtoupper($plotDatums[$originId]["biome"]) == "PLAINS" ? "PLAINS" : strtoupper($plotDatums[$originId]["biome"]);
			$pvp = $plotDatums[$originId]["pvp"] == null ? false : $plotDatums[$originId]["pvp"];
			$price = $plotDatums[$originId]["price"] == null ? 0 : $plotDatums[$originId]["price"];
			return new Plot($levelName, $X, $Z, $plotName, $owner, $helpers, $denied, $biome, $pvp, $price);
		}
		return $plot;
	}

	public function close() : void {
		$this->config->save();
		unset($this->config);
	}
}