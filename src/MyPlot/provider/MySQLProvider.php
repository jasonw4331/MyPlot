<?php
declare(strict_types=1);
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\math\Facing;
use pocketmine\Server;

class MySQLProvider extends DataProvider {
	protected MyPlot $plugin;
	protected \mysqli $db;
	/** @var mixed[] $settings */
	protected array $settings;
	protected \mysqli_stmt $sqlGetPlot;
	protected \mysqli_stmt $sqlSavePlot;
	protected \mysqli_stmt $sqlRemovePlot;
	protected \mysqli_stmt $sqlGetPlotsByOwner;
	protected \mysqli_stmt $sqlGetPlotsByOwnerAndLevel;
	protected \mysqli_stmt $sqlGetExistingXZ;
	protected \mysqli_stmt $sqlMergePlot;
	protected \mysqli_stmt $sqlGetMergeOrigin;
	protected \mysqli_stmt $sqlGetMergedPlots;
	protected \mysqli_stmt $sqlDisposeMergedPlot;

	/**
	 * MySQLProvider constructor.
	 *
	 * @param MyPlot  $plugin
	 * @param int     $cacheSize
	 * @param mixed[] $settings
	 *
	 * @throws \Exception
	 */
	public function __construct(MyPlot $plugin, int $cacheSize = 0, array $settings = []) {
		ini_set("mysqli.reconnect", "1");
		ini_set('mysqli.allow_persistent', "1");
		ini_set('mysql.connect_timeout', "300");
		ini_set('default_socket_timeout', "300");
		$this->plugin = $plugin;
		parent::__construct($plugin, $cacheSize);
		$this->settings = $settings;
		$this->db = new \mysqli($settings['Host'], $settings['Username'], $settings['Password'], $settings['DatabaseName'], $settings['Port']);
		if($this->db->connect_error !== null and $this->db->connect_error !== '')
			throw new \RuntimeException("Failed to connect to the MySQL database: " . $this->db->connect_error);
		$this->db->query("CREATE TABLE IF NOT EXISTS plotsV2 (level TEXT, X INT, Z INT, name TEXT, owner TEXT, helpers TEXT, denied TEXT, biome TEXT, pvp INT, price FLOAT, PRIMARY KEY (level, X, Z));");
		$res = $this->db->query("SELECT count(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='{$settings['DatabaseName']}' AND TABLE_NAME='plots'");
		if($res instanceof \mysqli_result and $res->fetch_array()[0] > 0)
			$this->db->query("INSERT IGNORE INTO plotsV2 (level, X, Z, name, owner, helpers, denied, biome, pvp, price) SELECT level, X, Z, name, owner, helpers, denied, biome, pvp, price FROM plots;");
		$this->db->query("CREATE TABLE IF NOT EXISTS mergedPlotsV2 (level TEXT, originX INT, originZ INT, mergedX INT, mergedZ INT, PRIMARY KEY(level, originX, originZ, mergedX, mergedZ));");
		$res = $this->db->query("SELECT count(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='{$settings['DatabaseName']}' AND TABLE_NAME='mergedPlots';");
		if($res instanceof \mysqli_result and $res->fetch_array()[0] > 0)
			$this->db->query("INSERT IGNORE INTO mergedPlotsV2 (level, originX, originZ, mergedX, mergedZ) SELECT r1.level, r1.X, r1.Z, r2.X, r2.Z FROM plots r1, mergedPlots JOIN plots r2 ON r1.id = mergedPlots.originId AND r2.id = mergedPlots.mergedId;");
		$this->prepare();
		$this->plugin->getLogger()->debug("MySQL data provider registered");
	}

	/**
	 * @throws \Exception
	 */
	public function savePlot(Plot $plot) : bool {
		$this->reconnect();
		$helpers = implode(',', $plot->helpers);
		$denied = implode(',', $plot->denied);
		$stmt = $this->sqlSavePlot;
		$stmt->bind_param('siisssssid', $plot->levelName, $plot->X, $plot->Z, $plot->name, $plot->owner, $helpers, $denied, $plot->biome, $plot->pvp, $plot->price);
		$result = $stmt->execute();
		if($result === false) {
			$this->plugin->getLogger()->error($stmt->error);
			return false;
		}
		$this->cachePlot($plot);
		return true;
	}

	/**
	 * @throws \Exception
	 */
	public function deletePlot(Plot $plot) : bool {
		$this->reconnect();
		$settings = MyPlot::getInstance()->getLevelSettings($plot->levelName);
		if($plot->isMerged()) {
			$stmt = $this->sqlDisposeMergedPlot;
			$restrictPVP = !$settings->restrictPVP;
			$stmt->bind_param('idsii', $restrictPVP, $settings->claimPrice,  $plot->levelName, $plot->X, $plot->Z);
			$result = $stmt->execute();
			if ($result === false) {
				$this->plugin->getLogger()->error($stmt->error);
				return false;
			}
			$plot = new Plot($plot->levelName, $plot->X, $plot->Z);
			$this->cachePlot($this->getMergeOrigin($plot));
		}else{
			$stmt = $this->sqlRemovePlot;
			$stmt->bind_param('sii', $plot->levelName, $plot->X, $plot->Z);
			$result = $stmt->execute();
			if($result === false){
				$this->plugin->getLogger()->error($stmt->error);
				return false;
			}
			$plot = new Plot($plot->levelName, $plot->X, $plot->Z);
			$this->cachePlot($plot);
		}
		return true;
	}

	/**
	 * @throws \Exception
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
			$plot = new Plot($levelName, $X, $Z);
			$this->cachePlot($plot);
			return $plot;
		}
		$result = $stmt->get_result();
		if($result !== false and ($val = $result->fetch_array(MYSQLI_ASSOC)) !== null) {
			if($val["helpers"] === '') {
				$helpers = [];
			}else{
				$helpers = explode(",", (string) $val["helpers"]);
			}
			if($val["denied"] === '') {
				$denied = [];
			}else{
				$denied = explode(",", (string) $val["denied"]);
			}
			$pvp = is_numeric($val["pvp"]) ? (bool)$val["pvp"] : null;
			$plot = new Plot($levelName, $X, $Z, (string) $val["name"], (string) $val["owner"], $helpers, $denied, (string) $val["biome"], $pvp, (float) $val["price"]);
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
	 * @throws \Exception
	 */
	public function getPlotsByOwner(string $owner, string $levelName = "") : array {
		$this->reconnect();
		if($levelName === '') {
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
		while($result !== false and ($val = $result->fetch_array()) !== null) {
			$helpers = explode(",", (string) $val["helpers"]);
			$denied = explode(",", (string) $val["denied"]);
			$pvp = is_numeric($val["pvp"]) ? (bool)$val["pvp"] : null;
			$plots[] = new Plot((string) $val["level"], (int) $val["X"], (int) $val["Z"], (string) $val["name"], (string) $val["owner"], $helpers, $denied, (string) $val["biome"], $pvp, (float) $val["price"]);
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

	/**
	 * @throws \Exception
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
			while($result !== false and ($val = $result->fetch_array(MYSQLI_NUM)) !== null) {
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

	public function mergePlots(Plot $base, Plot ...$plots) : bool {
        $stmt = $this->sqlMergePlot;
        $ret = true;
        foreach($plots as $plot) {
            $stmt->bind_param('siiii', $base->levelName, $base->X, $base->Z, $plot->X, $plot->Z);
            $result = $stmt->execute();
            if($result === false) {
                $this->plugin->getLogger()->error($stmt->error);
                $ret = false;
            }
        }
        return $ret;
	}

	public function getMergedPlots(Plot $plot, bool $adjacent = false) : array {
        $origin = $this->getMergeOrigin($plot);
        $stmt = $this->sqlGetMergedPlots;
        $stmt->bind_param('sii', $origin->levelName, $origin->X, $origin->Z);
        $result = $stmt->execute();
        $plots = [];
		$plots[] = $origin;
        if(!$result) {
            $this->plugin->getLogger()->error($stmt->error);
            return $plots;
        }
        $result = $stmt->get_result();
        while($result !== false and ($val = $result->fetch_array()) !== null) {
            $helpers = explode(",", (string) $val["helpers"]);
            $denied = explode(",", (string) $val["denied"]);
            $pvp = is_numeric($val["pvp"]) ? (bool)$val["pvp"] : null;
            $plots[] = new Plot((string) $val["level"], (int) $val["X"], (int) $val["Z"], (string) $val["name"], (string) $val["owner"], $helpers, $denied, (string) $val["biome"], $pvp, (float) $val["price"]);
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

	public function getMergeOrigin(Plot $plot) : Plot {
        $stmt = $this->sqlGetMergeOrigin;
        $stmt->bind_param('sii', $plot->levelName, $plot->X, $plot->Z);
        $result = $stmt->execute();
        if(!$result) {
            $this->plugin->getLogger()->error($stmt->error);
            return $plot;
        }
        $result = $stmt->get_result();
        if($result !== false and ($val = $result->fetch_array()) !== null) {
            $helpers = explode(",", (string) $val["helpers"]);
            $denied = explode(",", (string) $val["denied"]);
            $pvp = is_numeric($val["pvp"]) ? (bool)$val["pvp"] : null;
            return new Plot((string) $val["level"], (int) $val["X"], (int) $val["Z"], (string) $val["name"], (string) $val["owner"], $helpers, $denied, (string) $val["biome"], $pvp, (float) $val["price"]);
        }
        return $plot;
	}

	public function close() : void {
		if($this->db->close())
			$this->plugin->getLogger()->debug("MySQL database closed!");
	}

	/**
	 * @throws \Exception
	 */
	private function reconnect() : void{
		if(!$this->db->ping()) {
			$this->plugin->getLogger()->error("The MySQL server can not be reached! Trying to reconnect!");
			$this->close();
			$this->db->connect($this->settings['Host'], $this->settings['Username'], $this->settings['Password'], $this->settings['DatabaseName'], $this->settings['Port']);
			$this->prepare();
			if($this->db->ping()) {
				$this->plugin->getLogger()->notice("The MySQL connection has been re-established!");
			}else{
				$this->plugin->getLogger()->critical("The MySQL connection could not be re-established!");
				$this->plugin->getLogger()->critical("Closing level to prevent griefing!");
				foreach($this->plugin->getPlotLevels() as $levelName => $settings) {
					$level = $this->plugin->getServer()->getWorldManager()->getWorldByName($levelName);
					if($level !== null) {
						$level->save(); // don't force in case owner doesn't want it saved
						Server::getInstance()->getWorldManager()->unloadWorld($level, true); // force unload to prevent possible griefing
					}
				}
				if($this->db->connect_error !== null and $this->db->connect_error !== '')
					$this->plugin->getLogger()->critical("Failed to connect to the MySQL database: " . $this->db->connect_error);
				if($this->plugin->getConfig()->getNested("MySQLSettings.ShutdownOnFailure", false) === true) {
					$this->plugin->getServer()->shutdown();
				}
			}
		}
	}

	private function prepare() : void {
		$stmt = $this->db->prepare("SELECT name, owner, helpers, denied, biome, pvp, price FROM plotsV2 WHERE level = ? AND X = ? AND Z = ?;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlGetPlot = $stmt;
		$stmt = $this->db->prepare("INSERT INTO plotsV2 (`level`, `X`, `Z`, `name`, `owner`, `helpers`, `denied`, `biome`, `pvp`, `price`) VALUES(?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name = VALUES(name), owner = VALUES(owner), helpers = VALUES(helpers), denied = VALUES(denied), biome = VALUES(biome), pvp = VALUES(pvp), price = VALUES(price);");
		if($stmt === false)
			throw new \Exception();
		$this->sqlSavePlot = $stmt;
		$stmt = $this->db->prepare("DELETE FROM plotsV2 WHERE level = ? AND X = ? AND Z = ?;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlRemovePlot = $stmt;
		$stmt = $this->db->prepare("SELECT * FROM plotsV2 WHERE owner = ?;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlGetPlotsByOwner = $stmt;
		$stmt = $this->db->prepare("SELECT * FROM plotsV2 WHERE owner = ? AND level = ?;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlGetPlotsByOwnerAndLevel = $stmt;
		$stmt = $this->db->prepare("SELECT X, Z FROM plotsV2 WHERE (level = ? AND ((abs(X) = ? AND abs(Z) <= ?) OR (abs(Z) = ? AND abs(X) <= ?)));");
		if($stmt === false)
			throw new \Exception();
		$this->sqlGetExistingXZ = $stmt;

		$stmt = $this->db->prepare("INSERT IGNORE INTO mergedPlotsV2 (`level`, `originX`, `originZ`, `mergedX`, `mergedZ`) VALUES (?,?,?,?,?);");
		if($stmt === false)
			throw new \Exception();
		$this->sqlMergePlot = $stmt;
		$stmt = $this->db->prepare("SELECT plotsV2.level, X, Z, name, owner, helpers, denied, biome, pvp, price FROM plotsV2 LEFT JOIN mergedPlotsV2 ON mergedPlotsV2.level = plotsV2.level WHERE mergedPlotsV2.level = ? AND mergedX = ? AND mergedZ = ?;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlGetMergeOrigin = $stmt;
		$stmt = $this->db->prepare("SELECT plotsV2.level, X, Z, name, owner, helpers, denied, biome, pvp, price FROM plotsV2 LEFT JOIN mergedPlotsV2 ON mergedPlotsV2.level = plotsV2.level AND mergedPlotsV2.mergedX = plotsV2.X AND mergedPlotsV2.mergedZ = plotsV2.Z WHERE mergedPlotsV2.level = ? AND originX = ? AND originZ = ?;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlGetMergedPlots = $stmt;
		$stmt = $this->db->prepare("UPDATE plotsV2 SET name = '', owner = '', helpers = '', denied = '', biome = :biome, pvp = :pvp, price = :price WHERE level = :level AND X = :X AND Z = :Z;");
		if($stmt === false)
			throw new \Exception();
		$this->sqlDisposeMergedPlot = $stmt;
	}
}
