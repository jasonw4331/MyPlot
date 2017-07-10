<?php
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\Plot;

class MySQLProvider extends DataProvider
{
	/** @var \mysqli $db */
	private $db;
	/** @var string $lastSave */
	private $lastSave = null;
	/** @var \mysqli_stmt  */
	private $sqlGetPlot, $sqlSavePlot, $sqlSavePlotById, $sqlRemovePlot,
		$sqlRemovePlotById, $sqlGetPlotsByOwner, $sqlGetPlotsByOwnerAndLevel,
		$sqlGetExistingXZ;
	/** @var MyPlot */
	protected $plugin;

	/**
	 * MySQLiProvider constructor.
	 * @param MyPlot $plugin
	 * @param int $cacheSize
	 * @param array $settings
	 */
	public function __construct(MyPlot $plugin, $cacheSize = 0, $settings) {
		$this->plugin = $plugin;
		parent::__construct($plugin, $cacheSize);

		$this->db = new \mysqli($settings['Host'], $settings['Username'], $settings['Password'], $settings['DatabaseName'], $settings['Port']);
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS plots (id INT PRIMARY KEY AUTO_INCREMENT, level TEXT, X INT, Z INT, name TEXT, owner TEXT, helpers TEXT, denied TEXT, biome TEXT);");
		$this->sqlGetPlot = $this->db->prepare("SELECT id, name, owner, helpers, denied, biome FROM plots WHERE level = ? AND X = ? AND Z = ?;");
		$this->sqlSavePlot = $this->db->prepare(
			"UPDATE plots SET name = ?, owner = ?, helpers = ?, denied = ?, biome = ? WHERE id = ?;"
		);
		$this->sqlSavePlotById = $this->db->prepare(
		//TODO duplicate check
		#"INSERT INTO plots (id, level, X, Z, name, owner, helpers, denied, biome) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), owner = VALUES(owner), helpers = VALUES(helpers), denied = VALUES(denied), biome = VALUES(biome);");
			"INSERT INTO plots (id, level, X, Z, name, owner, helpers, denied, biome) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?);"
		);
		$this->sqlRemovePlot = $this->db->prepare(
			"DELETE FROM plots WHERE id = ?;"
		);
		$this->sqlRemovePlotById = $this->db->prepare(
			"DELETE FROM plots WHERE level = ? AND X = ? AND Z = ?;"
		);
		$this->sqlGetPlotsByOwner = $this->db->prepare(
			"SELECT * FROM plots WHERE owner = ?;"
		);
		$this->sqlGetPlotsByOwnerAndLevel = $this->db->prepare(
			"SELECT * FROM plots WHERE owner = ? AND level = ?;"
		);
		$this->sqlGetExistingXZ = $this->db->prepare(
			"SELECT X, Z FROM plots WHERE (
				level = ? 
				AND (
					(abs(X) == ? AND abs(Z) <= ?) OR
					(abs(Z) == ? AND abs(X) <= ?)
				)
			);"
		);
		$this->plugin->getLogger()->debug("MySQL data provider registered");
	}

	public function close() {
		$this->db->close();
		$this->plugin->getLogger()->debug("MySQL database closed!");
	}

	public function savePlot(Plot $plot): bool{
		$helpers = implode(',', $plot->helpers);
		$denied = implode(',', $plot->denied);
		if ($plot->id >= 0) {
			$stmt = $this->sqlSavePlot;
			$stmt->bind_param('sssssi', $plot->name, $plot->owner, $helpers, $denied, $plot->biome, $plot->id);
		} else {
			$stmt = $this->sqlSavePlotById;
			$stmt->bind_param('isiisssss', $plot->id, $plot->levelName, $plot->X, $plot->Z, $plot->name, $plot->owner, $helpers, $denied, $plot->biome);
		}
		$resulta = $stmt->execute();
		$resultb = $this->db->savepoint($this->lastSave = time());

		if ($resulta === false) {
			return false;
		}
		$this->cachePlot($plot);
		return true;
	}
	public function getLastSave() {
		return $this->lastSave;
	}

	public function deletePlot(Plot $plot): bool{
		if ($plot->id >= 0) {
			$stmt = $this->sqlRemovePlot;
			$stmt->bind_param('i', $plot->id);
		} else {
			$stmt = $this->sqlRemovePlotById;
			$stmt->bind_param('sii', $plot->levelName, $plot->X, $plot->Z);
		}
		$result = $stmt->execute();
		if ($result === false) {
			return false;
		}
		$this->lastSave = null;
		$plot = new Plot($plot->levelName, $plot->X, $plot->Z);
		$this->cachePlot($plot);
		return true;
	}

	public function getPlot(string $levelName, int $X, int $Z): Plot{
		if (($plot = $this->getPlotFromCache($levelName, $X, $Z)) != null) {
			return $plot;
		}
		$stmt = $this->sqlGetPlot;
		$stmt->bind_param('sii', $levelName, $X, $Z);
		$result = $stmt->execute();
		if ($result === false) {
			$this->plugin->getLogger()->error($stmt->error);
		}
		$result = $stmt->get_result();
		if ($val = $result->fetch_array(MYSQLI_ASSOC)) {
			if ($val["helpers"] === null or $val["helpers"] === "") {
				$helpers = [];
			} else {
				$helpers = explode(",", (string)$val["helpers"]);
			}
			if ($val["denied"] === null or $val["denied"] === "") {
				$denied = [];
			} else {
				$denied = explode(",", (string)$val["denied"]);
			}
			$plot = new Plot($levelName, $X, $Z, (string)$val["name"], (string)$val["owner"],
				$helpers, $denied, (string)$val["biome"], (int)$val["id"]);
		} else {
			$plot = new Plot($levelName, $X, $Z);
		}
		$this->cachePlot($plot);
		return $plot;
	}

	public function getPlotsByOwner(string $owner, string $levelName = ""): array{
		if (empty($levelName)) {
			$stmt = $this->sqlGetPlotsByOwner;
			$stmt->bind_param('s', $owner);
		} else {
			$stmt = $this->sqlGetPlotsByOwnerAndLevel;
			$stmt->bind_param('ss', $owner, $levelName);
		}
		$plots = [];
		$result = $stmt->execute();
		if ($result === false) {
			return $plots;
		}
		$result = $stmt->get_result();
		while ($val = $result->fetch_array()) {
			$helpers = explode(",", (string)$val["helpers"]);
			$denied = explode(",", (string)$val["denied"]);
			$plots[] = new Plot((string)$val["level"], (int)$val["X"], (int)$val["Z"], (string)$val["name"],
				(string)$val["owner"], $helpers, $denied, (string)$val["biome"], (int)$val["id"]);
		}
		// Remove unloaded plots
		$plots = array_filter($plots, function ($plot) {
			return $this->plugin->isLevelLoaded($plot->levelName);
		});
		// Sort plots by level
		usort($plots, function ($plot1, $plot2) {
			return strcmp($plot1->levelName, $plot2->levelName);
		});
		return $plots;
	}

	public function getNextFreePlot(string $levelName, int $limitXZ = 0) {
		$i = 0;
		for (; $limitXZ <= 0 or $i < $limitXZ; $i++) {
			$stmt = $this->sqlGetExistingXZ;
			$stmt->bind_param('siiii', $levelName, $i, $i, $i, $i);
			$result = $stmt->execute();
			if ($result === false) {
				continue;
			}
			$result = $stmt->get_result();
			$plots = [];
			while ($val = $result->fetch_array(MYSQLI_NUM)) {
				$plots[$val[0]][$val[1]] = true;
			}
			if (count($plots) === max(1, 8 * $i)) {
				continue;
			}
			if ($ret = self::findEmptyPlotSquared(0, $i, $plots)) {
				list($X, $Z) = $ret;
				$plot = new Plot($levelName, $X, $Z);
				$this->cachePlot($plot);
				return $plot;
			}
			for ($a = 1; $a < $i; $a++) {
				if ($ret = self::findEmptyPlotSquared($a, $i, $plots)) {
					list($X, $Z) = $ret;
					$plot = new Plot($levelName, $X, $Z);
					$this->cachePlot($plot);
					return $plot;
				}
			}
			if ($ret = self::findEmptyPlotSquared($i, $i, $plots)) {
				list($X, $Z) = $ret;
				$plot = new Plot($levelName, $X, $Z);
				$this->cachePlot($plot);
				return $plot;
			}
		}
		return null;
	}
}