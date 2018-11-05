<?php
declare(strict_types=1);
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\Plot;

class MySQLProvider extends DataProvider {
	/** @var MyPlot $plugin */
	protected $plugin;
	/** @var \mysqli $db */
	private $db;
	/** @var array $settings */
	private $settings;
	/** @var \mysqli_stmt */
	private $sqlGetPlot, $sqlSavePlot, $sqlSavePlotById, $sqlRemovePlot, $sqlRemovePlotById, $sqlGetPlotsByOwner, $sqlGetPlotsByOwnerAndLevel, $sqlGetExistingXZ;

	/**
	 * MySQLProvider constructor.
	 *
	 * @param MyPlot $plugin
	 * @param int $cacheSize
	 * @param array $settings
	 */
	public function __construct(MyPlot $plugin, int $cacheSize = 0, $settings) {
		ini_set("mysqli.reconnect", "1");
		ini_set('mysqli.allow_persistent', "1");
		ini_set('mysql.connect_timeout', "300");
		ini_set('default_socket_timeout', "300");
		$this->plugin = $plugin;
		parent::__construct($plugin, $cacheSize);
		$this->settings = $settings;
		$this->db = new \mysqli($settings['Host'], $settings['Username'], $settings['Password'], $settings['DatabaseName'], $settings['Port']);
		$this->db->query("CREATE TABLE IF NOT EXISTS plots (id INT PRIMARY KEY AUTO_INCREMENT, level TEXT, X INT, Z INT, name TEXT, owner TEXT, helpers TEXT, denied TEXT, biome TEXT, pvp INT);");
		try{
			$this->db->query("ALTER TABLE plots ADD COLUMN pvp INT AFTER biome;");
		}catch(\Exception $e) {
			// do nothing :P
		}
		$this->prepare();
		$this->plugin->getLogger()->debug("MySQL data provider registered");
	}

	/**
	 * @param Plot $plot
	 *
	 * @return bool
	 */
	public function savePlot(Plot $plot) : bool {
		$this->reconnect();
		$helpers = implode(',', $plot->helpers);
		$denied = implode(',', $plot->denied);
		if($plot->id >= 0) {
			$stmt = $this->sqlSavePlotById;
			$stmt->bind_param('isiisssssi', $plot->id, $plot->levelName, $plot->X, $plot->Z, $plot->name, $plot->owner, $helpers, $denied, $plot->biome, $plot->pvp);
		}else{
			$stmt = $this->sqlSavePlot;
			$stmt->bind_param('siisiisssssi', $plot->levelName, $plot->X, $plot->Z, $plot->levelName, $plot->X, $plot->Z, $plot->name, $plot->owner, $helpers, $denied, $plot->biome, $plot->pvp);
		}
		$result = $stmt->execute();
		if($result === false) {
			$this->plugin->getLogger()->error($stmt->error);
			return false;
		}
		$this->cachePlot($plot);
		return true;
	}

	/**
	 * @param Plot $plot
	 *
	 * @return bool
	 */
	public function deletePlot(Plot $plot) : bool {
		$this->reconnect();
		if($plot->id >= 0) {
			$stmt = $this->sqlRemovePlot;
			$stmt->bind_param('i', $plot->id);
		}else{
			$stmt = $this->sqlRemovePlotById;
			$stmt->bind_param('sii', $plot->levelName, $plot->X, $plot->Z);
		}
		$result = $stmt->execute();
		if($result === false) {
			$this->plugin->getLogger()->error($stmt->error);
			return false;
		}
		$plot = new Plot($plot->levelName, $plot->X, $plot->Z);
		$this->cachePlot($plot);
		return true;
	}

	/**
	 * @param string $levelName
	 * @param int $X
	 * @param int $Z
	 *
	 * @return Plot
	 */
	public function getPlot(string $levelName, int $X, int $Z) : Plot {
		$this->reconnect();
		if(($plot = $this->getPlotFromCache($levelName, $X, $Z)) != null) {
			return $plot;
		}
		$stmt = $this->sqlGetPlot;
		$stmt->bind_param('sii', $levelName, $X, $Z);
		$result = $stmt->execute();
		if($result === false) {
			$this->plugin->getLogger()->error($stmt->error);
			return null;
		}
		$result = $stmt->get_result();
		if($val = $result->fetch_array(MYSQLI_ASSOC)) {
			if(empty($val["helpers"])) {
				$helpers = [];
			}else{
				$helpers = explode(",", (string) $val["helpers"]);
			}
			if(empty($val["denied"])) {
				$denied = [];
			}else{
				$denied = explode(",", (string) $val["denied"]);
			}
			$plot = new Plot($levelName, $X, $Z, (string) $val["name"], (string) $val["owner"], $helpers, $denied, (string) $val["biome"], (bool) $val["pvp"], (int) $val["id"]);
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
	 * @return array
	 */
	public function getPlotsByOwner(string $owner, string $levelName = "") : array {
		$this->reconnect();
		if(empty($levelName)) {
			$stmt = $this->sqlGetPlotsByOwner;
			$stmt->bind_param('s', $owner);
		}else{
			$stmt = $this->sqlGetPlotsByOwnerAndLevel;
			$stmt->bind_param('ss', $owner, $levelName);
		}
		$plots = [];
		$result = $stmt->execute();
		if($result === false) {
			$this->plugin->getLogger()->error($stmt->error);
			return $plots;
		}
		$result = $stmt->get_result();
		while($val = $result->fetch_array()) {
			$helpers = explode(",", (string) $val["helpers"]);
			$denied = explode(",", (string) $val["denied"]);
			$plots[] = new Plot((string) $val["level"], (int) $val["X"], (int) $val["Z"], (string) $val["name"], (string) $val["owner"], $helpers, $denied, (string) $val["biome"], (bool) $val["pvp"], (int) $val["id"]);
		}
		// Remove unloaded plots
		$plots = array_filter($plots, function($plot) {
			return $this->plugin->isLevelLoaded($plot->levelName);
		});
		// Sort plots by level
		usort($plots, function($plot1, $plot2) {
			return strcmp($plot1->levelName, $plot2->levelName);
		});
		return $plots;
	}

	/**
	 * @param string $levelName
	 * @param int $limitXZ
	 *
	 * @return Plot|null
	 */
	public function getNextFreePlot(string $levelName, int $limitXZ = 0) : ?Plot {
		$this->reconnect();
		$i = 0;
		for(; $limitXZ <= 0 or $i < $limitXZ; $i++) {
			$stmt = $this->sqlGetExistingXZ;
			$stmt->bind_param('siiii', $levelName, $i, $i, $i, $i);
			$result = $stmt->execute();
			if($result === false) {
				$this->plugin->getLogger()->error($stmt->error);
				continue;
			}
			$result = $stmt->get_result();
			$plots = [];
			while($val = $result->fetch_array(MYSQLI_NUM)) {
				$plots[$val[0]][$val[1]] = true;
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
		if($this->db->close())
			$this->plugin->getLogger()->debug("MySQL database closed!");
	}

	/**
	 * @return bool
	 */
	private function reconnect() : bool {
		if(!$this->db->ping()) {
			$this->plugin->getLogger()->error("The MySQL server can not be reached! Trying to reconnect!");
			$this->close();
			$this->db->connect($this->settings['Host'], $this->settings['Username'], $this->settings['Password'], $this->settings['DatabaseName'], $this->settings['Port']);
			$this->prepare();
			if($this->db->ping()) {
				$this->plugin->getLogger()->notice("The MySQL connection has been re-established!");
				return true;
			}else{
				$this->plugin->getLogger()->critical("The MySQL connection could not be re-established!");
				$this->plugin->getLogger()->critical("Closing level to prevent griefing!");
				foreach($this->plugin->getPlotLevels() as $levelName => $settings) {
					$level = $this->plugin->getServer()->getLevelByName($levelName);
					$level->save(); // don't force in case owner doesn't want it saved
					$level->unload(true); // force unload to prevent possible griefing
				}
				if($this->plugin->getConfig()->getNested("MySQLSettings.ShutdownOnFailure", false)) {
					$this->plugin->getServer()->shutdown();
				}
				return false;
			}
		}
		return true;
	}

	private function prepare() : void {
		$this->sqlGetPlot = $this->db->prepare("SELECT id, name, owner, helpers, denied, biome FROM plots WHERE level = ? AND X = ? AND Z = ?;");
		$this->sqlSavePlot = $this->db->prepare("INSERT INTO plots (`id`, `level`, `X`, `Z`, `name`, `owner`, `helpers`, `denied`, `biome`, `pvp`) VALUES((SELECT id FROM plots p WHERE p.level = ? AND X = ? AND Z = ?),?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name = VALUES(name), owner = VALUES(owner), helpers = VALUES(helpers), denied = VALUES(denied), biome = VALUES(biome), pvp = VALUES(pvp);");
		$this->sqlSavePlotById = $this->db->prepare("UPDATE plots SET id = ?, level = ?, X = ?, Z = ?, name = ?, owner = ?, helpers = ?, denied = ?, biome = ?, pvp = ? WHERE id = VALUES(id);");
		$this->sqlRemovePlot = $this->db->prepare("DELETE FROM plots WHERE id = ?;");
		$this->sqlRemovePlotById = $this->db->prepare("DELETE FROM plots WHERE level = ? AND X = ? AND Z = ?;");
		$this->sqlGetPlotsByOwner = $this->db->prepare("SELECT * FROM plots WHERE owner = ?;");
		$this->sqlGetPlotsByOwnerAndLevel = $this->db->prepare("SELECT * FROM plots WHERE owner = ? AND level = ?;");
		$this->sqlGetExistingXZ = $this->db->prepare("SELECT X, Z FROM plots WHERE (level = ? AND ((abs(X) = ? AND abs(Z) <= ?) OR (abs(Z) = ? AND abs(X) <= ?)));");
	}
}
