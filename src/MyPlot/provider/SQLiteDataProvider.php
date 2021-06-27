<?php
declare(strict_types=1);
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\math\Facing;

class SQLiteDataProvider extends DataProvider
{
	/** @var \SQLite3 $db */
	private $db;
	/** @var \SQLite3Stmt $sqlGetPlot */
	protected $sqlGetPlot;
	/** @var \SQLite3Stmt $sqlSavePlot */
	protected $sqlSavePlot;
	/** @var \SQLite3Stmt $sqlSavePlotById */
	protected $sqlSavePlotById;
	/** @var \SQLite3Stmt $sqlRemovePlot */
	protected $sqlRemovePlot;
    /** @var \SQLite3Stmt $sqlDisposeMergedPlot */
    protected $sqlDisposeMergedPlot;
	/** @var \SQLite3Stmt $sqlRemovePlotById */
	protected $sqlRemovePlotById;
    /** @var \SQLite3Stmt $sqlDisposeMergedPlotById */
    protected $sqlDisposeMergedPlotById;
	/** @var \SQLite3Stmt $sqlGetPlotsByOwner */
	protected $sqlGetPlotsByOwner;
	/** @var \SQLite3Stmt $sqlGetPlotsByOwnerAndLevel */
	protected $sqlGetPlotsByOwnerAndLevel;
	/** @var \SQLite3Stmt $sqlGetExistingXZ */
	protected $sqlGetExistingXZ;
	/** @var \SQLite3Stmt $sqlMergePlot */
	protected $sqlMergePlot;
	/** @var \SQLite3Stmt $sqlGetMergeOrigin */
	protected $sqlGetMergeOrigin;
	/** @var \SQLite3Stmt $sqlGetMergedPlots */
	protected $sqlGetMergedPlots;

	/**
	 * SQLiteDataProvider constructor.
	 *
	 * @param MyPlot $plugin
	 * @param int $cacheSize
	 */
	public function __construct(MyPlot $plugin, int $cacheSize = 0) {
		parent::__construct($plugin, $cacheSize);
		$this->db = new \SQLite3($this->plugin->getDataFolder() . "plots.db");
		$this->db->exec("CREATE TABLE IF NOT EXISTS plots
			(id INTEGER PRIMARY KEY AUTOINCREMENT, level TEXT, X INTEGER, Z INTEGER, name TEXT,
			 owner TEXT, helpers TEXT, denied TEXT, biome TEXT, pvp INTEGER, price FLOAT);");
		try{
			$this->db->exec("ALTER TABLE plots ADD pvp INTEGER;");
		}catch(\Exception $e) {
			// nothing :P
		}
		try{
			$this->db->exec("ALTER TABLE plots ADD price FLOAT;");
		}catch(\Exception $e) {
			// nothing :P
		}
		$stmt = $this->db->prepare("SELECT id, name, owner, helpers, denied, biome, pvp, price FROM plots WHERE level = :level AND X = :X AND Z = :Z;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlGetPlot = $stmt;
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO plots (id, level, X, Z, name, owner, helpers, denied, biome, pvp, price) VALUES
			((SELECT id FROM plots WHERE level = :level AND X = :X AND Z = :Z),
			 :level, :X, :Z, :name, :owner, :helpers, :denied, :biome, :pvp, :price);");
		if($stmt === false)
			throw new \Exception();
		$this->sqlSavePlot = $stmt;
		$stmt = $this->db->prepare("UPDATE plots SET name = :name, owner = :owner, helpers = :helpers, denied = :denied, biome = :biome, pvp = :pvp, price = :price WHERE id = :id;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlSavePlotById = $stmt;
		$stmt = $this->db->prepare("DELETE FROM plots WHERE level = :level AND X = :X AND Z = :Z;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlRemovePlot = $stmt;
		$stmt = $this->db->prepare("UPDATE plots SET name = '', owner = '', helpers = '', denied = '', biome = :biome, pvp = :pvp, price = :price WHERE level = :level AND X = :X AND Z = :Z;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlDisposeMergedPlot = $stmt;
		$stmt = $this->db->prepare("DELETE FROM plots WHERE id = :id;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlRemovePlotById = $stmt;
		$stmt = $this->db->prepare("UPDATE plots SET name = '', owner = '', helpers = '', denied = '', biome = :biome, pvp = :pvp, price = :price WHERE id = :id;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlDisposeMergedPlotById = $stmt;
		$stmt = $this->db->prepare("SELECT * FROM plots WHERE owner = :owner;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlGetPlotsByOwner = $stmt;
		$stmt = $this->db->prepare("SELECT * FROM plots WHERE owner = :owner AND level = :level;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlGetPlotsByOwnerAndLevel = $stmt;
		$stmt = $this->db->prepare("SELECT X, Z FROM plots WHERE (
				level = :level
				AND (
					(abs(X) = :number AND abs(Z) <= :number) OR
					(abs(Z) = :number AND abs(X) <= :number)
				)
			);");
		if($stmt === false)
			throw new \Exception();
		$this->sqlGetExistingXZ = $stmt;
		$this->db->exec("CREATE TABLE IF NOT EXISTS mergedPlots (originId INTEGER, mergedId INTEGER UNIQUE, PRIMARY KEY (originId, mergedId), FOREIGN KEY (originId) REFERENCES plots (id) ON DELETE CASCADE , FOREIGN KEY (mergedId) REFERENCES plots (id) ON DELETE CASCADE);");
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO mergedPlots (originId, mergedId) VALUES (:originId, :mergedId);");
		if($stmt === false)
			throw new \Exception();
		$this->sqlMergePlot = $stmt;
		$stmt = $this->db->prepare("SELECT plots.id, level, X, Z, name, owner, helpers, denied, biome, pvp, price FROM plots LEFT JOIN mergedPlots ON mergedPlots.originId = plots.id WHERE mergedId = :mergedId;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlGetMergeOrigin = $stmt;
		$stmt = $this->db->prepare("SELECT plots.id, level, X, Z, name, owner, helpers, denied, biome, pvp, price FROM plots LEFT JOIN mergedPlots ON mergedPlots.mergedId = plots.id WHERE originId = :originId;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlGetMergedPlots = $stmt;
		$this->plugin->getLogger()->debug("SQLite data provider registered");
	}

	public function savePlot(Plot $plot) : bool {
		$helpers = implode(",", $plot->helpers);
		$denied = implode(",", $plot->denied);
		if($plot->id >= 0) {
			$stmt = $this->sqlSavePlotById;
			$stmt->bindValue(":id", $plot->id, SQLITE3_INTEGER);
		}else{
			$stmt = $this->sqlSavePlot;
			$stmt->bindValue(":level", $plot->levelName, SQLITE3_TEXT);
			$stmt->bindValue(":X", $plot->X, SQLITE3_INTEGER);
			$stmt->bindValue(":Z", $plot->Z, SQLITE3_INTEGER);
		}
		$stmt->bindValue(":name", $plot->name, SQLITE3_TEXT);
		$stmt->bindValue(":owner", $plot->owner, SQLITE3_TEXT);
		$stmt->bindValue(":helpers", $helpers, SQLITE3_TEXT);
		$stmt->bindValue(":denied", $denied, SQLITE3_TEXT);
		$stmt->bindValue(":biome", $plot->biome, SQLITE3_TEXT);
		$stmt->bindValue(":pvp", $plot->pvp, SQLITE3_INTEGER);
		$stmt->bindValue(":price", $plot->price, SQLITE3_FLOAT);
		$stmt->reset();
		$result = $stmt->execute();
		if(!$result instanceof \SQLite3Result) {
			return false;
		}
		$this->cachePlot($plot);
		return true;
	}

	public function deletePlot(Plot $plot) : bool {
		if($plot->isMerged()){
			$settings = MyPlot::getInstance()->getLevelSettings($plot->levelName);
			if ($plot->id >= 0) {
				$stmt = $this->sqlDisposeMergedPlotById;
				$stmt->bindValue(":biome", $plot->biome, SQLITE3_TEXT);
				$stmt->bindValue(":pvp", !$settings->restrictPVP, SQLITE3_INTEGER);
				$stmt->bindValue(":price", $settings->claimPrice, SQLITE3_FLOAT);
				$stmt->bindValue(":id", $plot->id, SQLITE3_INTEGER);
			} else {
				$stmt = $this->sqlDisposeMergedPlot;
				$stmt->bindValue(":pvp", !$settings->restrictPVP, SQLITE3_INTEGER);
				$stmt->bindValue(":price", $settings->claimPrice, SQLITE3_FLOAT);
				$stmt->bindValue(":level", $plot->levelName, SQLITE3_TEXT);
				$stmt->bindValue(":X", $plot->X, SQLITE3_INTEGER);
				$stmt->bindValue(":Z", $plot->Z, SQLITE3_INTEGER);
			}
			$stmt->reset();
			$result = $stmt->execute();
			if(!$result instanceof \SQLite3Result) {
				return false;
			}
			$this->cachePlot($this->getMergeOrigin($plot));
		}else {
			if($plot->id >= 0) {
				$stmt = $this->sqlRemovePlotById;
				$stmt->bindValue(":id", $plot->id, SQLITE3_INTEGER);
			}else{
				$stmt = $this->sqlRemovePlot;
				$stmt->bindValue(":level", $plot->levelName, SQLITE3_TEXT);
				$stmt->bindValue(":X", $plot->X, SQLITE3_INTEGER);
				$stmt->bindValue(":Z", $plot->Z, SQLITE3_INTEGER);
			}
			$stmt->reset();
			$result = $stmt->execute();
			if(!$result instanceof \SQLite3Result) {
				return false;
			}
			$plot = new Plot($plot->levelName, $plot->X, $plot->Z);
			$this->cachePlot($plot);
		}
		return true;
	}

	public function getPlot(string $levelName, int $X, int $Z) : Plot {
		if(($plot = $this->getPlotFromCache($levelName, $X, $Z)) !== null) {
			return $plot;
		}
		$this->sqlGetPlot->bindValue(":level", $levelName, SQLITE3_TEXT);
		$this->sqlGetPlot->bindValue(":X", $X, SQLITE3_INTEGER);
		$this->sqlGetPlot->bindValue(":Z", $Z, SQLITE3_INTEGER);
		$this->sqlGetPlot->reset();
		$result = $this->sqlGetPlot->execute();
		if($result !== false and ($val = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
			if($val["helpers"] === null or $val["helpers"] === "") {
				$helpers = [];
			}else{
				$helpers = explode(",", (string) $val["helpers"]);
			}
			if($val["denied"] === null or $val["denied"] === "") {
				$denied = [];
			}else{
				$denied = explode(",", (string) $val["denied"]);
			}
			$pvp = is_numeric($val["pvp"]) ? (bool)$val["pvp"] : null;
			$plot = new Plot($levelName, $X, $Z, (string) $val["name"], (string) $val["owner"], $helpers, $denied, (string) $val["biome"], $pvp, (float) $val["price"], (int) $val["id"]);
		}else{
			$plot = new Plot($levelName, $X, $Z);
		}
		$this->cachePlot($plot);
		return $plot;
	}

	/**
	 * @param string $owner
	 * @param string $levelName
	 *
	 * @return Plot[]
	 */
	public function getPlotsByOwner(string $owner, string $levelName = "") : array {
		if($levelName === "") {
			$stmt = $this->sqlGetPlotsByOwner;
		}else{
			$stmt = $this->sqlGetPlotsByOwnerAndLevel;
			$stmt->bindValue(":level", $levelName, SQLITE3_TEXT);
		}
		$stmt->bindValue(":owner", $owner, SQLITE3_TEXT);
		$plots = [];
		$stmt->reset();
		$result = $stmt->execute();
		while($result !== false and ($val = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
			$helpers = explode(",", (string) $val["helpers"]);
			$denied = explode(",", (string) $val["denied"]);
			$pvp = is_numeric($val["pvp"]) ? (bool)$val["pvp"] : null;
			$plots[] = new Plot((string) $val["level"], (int) $val["X"], (int) $val["Z"], (string) $val["name"], (string) $val["owner"], $helpers, $denied, (string) $val["biome"], $pvp, (float) $val["price"], (int) $val["id"]);
		}
		// Remove unloaded plots
		$plots = array_filter($plots, function(Plot $plot) : bool {
			return $this->plugin->isLevelLoaded($plot->levelName);
		});
		// Sort plots by level
		usort($plots, function(Plot $plot1, Plot $plot2) : int {
			return strcmp($plot1->levelName, $plot2->levelName);
		});
		return $plots;
	}

	public function getNextFreePlot(string $levelName, int $limitXZ = 0) : ?Plot {
		$this->sqlGetExistingXZ->bindValue(":level", $levelName, SQLITE3_TEXT);
		$i = 0;
		$this->sqlGetExistingXZ->bindParam(":number", $i, SQLITE3_INTEGER);
		for(; $limitXZ <= 0 or $i < $limitXZ; $i++) {
			$this->sqlGetExistingXZ->reset();
			$result = $this->sqlGetExistingXZ->execute();
			$plots = [];
			while($result !== false and ($val = $result->fetchArray(SQLITE3_NUM)) !== false) {
				$plots[$val[0]][$val[1]] = true;
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

	public function close() : void {
		$this->db->close();
		$this->plugin->getLogger()->debug("SQLite database closed!");
	}

	public function mergePlots(Plot $base, Plot ...$plots) : bool {
		$stmt = $this->sqlMergePlot;
		$ret = true;
		foreach($plots as $plot) {
			$stmt->bindValue(":originId", $base->id);
			$stmt->bindValue(":mergedId", $plot->id);
			$stmt->reset();
			$result = $stmt->execute();
			if(!$result instanceof \SQLite3Result) {
				MyPlot::getInstance()->getLogger()->debug("Failed to merge plot id ".$plot->id." into ".$base->id);
				$ret = false;
				continue;
			}
		}
		return $ret;
	}

	/**
	 * @param Plot $plot
	 * @param bool $adjacent
	 *
	 * @return Plot[]
	 */
	public function getMergedPlots(Plot $plot, bool $adjacent = false) : array {
		$origin = $this->getMergeOrigin($plot);
		$stmt = $this->sqlGetMergedPlots;
		$stmt->bindValue(":originId", $origin->id);
		$stmt->reset();
		$result = $stmt->execute();
		$plots = [$origin];
		while($result !== false and $val = $result->fetchArray(SQLITE3_ASSOC)) {
			$helpers = explode(",", (string) $val["helpers"]);
			$denied = explode(",", (string) $val["denied"]);
			$pvp = is_numeric($val["pvp"]) ? (bool)$val["pvp"] : null;
			$plots[] = new Plot((string) $val["level"], (int) $val["X"], (int) $val["Z"], (string) $val["name"], (string) $val["owner"], $helpers, $denied, (string) $val["biome"], $pvp, (float) $val["price"], (int) $val["id"]);
		}
		if($adjacent)
			$plots = array_filter($plots, function(Plot $val) use ($plot) : bool {
				for($i = Facing::NORTH; $i <= Facing::EAST; ++$i) {
					if($plot->getSide($i)->isSame($val))
						return true;
				}
				return false;
			});
		return $plots;
	}

	/**
	 * @param Plot $plot
	 *
	 * @return Plot
	 */
	public function getMergeOrigin(Plot $plot) : Plot {
		$stmt = $this->sqlGetMergeOrigin;
		$stmt->bindValue(":mergedId", $plot->id);
		$stmt->reset();
		$result = $stmt->execute();
		if(!$result instanceof \SQLite3Result) {
			return $plot;
		}
		if($val = $result->fetchArray(SQLITE3_ASSOC)) {
			$helpers = explode(",", (string) $val["helpers"]);
			$denied = explode(",", (string) $val["denied"]);
			$pvp = is_numeric($val["pvp"]) ? (bool)$val["pvp"] : null;
			return new Plot((string) $val["level"], (int) $val["X"], (int) $val["Z"], (string) $val["name"], (string) $val["owner"], $helpers, $denied, (string) $val["biome"], $pvp, (float) $val["price"], (int) $val["id"]);
		}
		return $plot;
	}
}