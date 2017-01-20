<?php
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\Plot;

class MySQLProvider extends DataProvider
{
    /** @var \MySQLi $db */
    private $db;
    /** @var string $lastSave */
    private $lastSave;
		/** @var MyPlot  */
		private $plugin;

    /**
     * MySQLProvider constructor.
     * @param MyPlot $plugin
     * @param int $cacheSize
     * @param array $settings
     */
    public function __construct(MyPlot $plugin, $cacheSize = 0, $settings) {
	      $this->plugin = $plugin;
	      parent::__construct($plugin, $cacheSize);
        $this->db = new \mysqli($settings['Host'], $settings['Username'], $settings['Password'], $settings['DatabaseName'], $settings['Port']);
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS plots
            (id INTEGER PRIMARY KEY AUTOINCREMENT, level TEXT, X INTEGER, Z INTEGER, name TEXT,
             owner TEXT, helpers TEXT, denied TEXT, biome TEXT)"
        );
	    $this->plugin->getLogger()->debug("MySQL data provider registered");
    }

    public function close() {
        $this->db->close();
	    $this->plugin->getLogger()->debug("MySQL database closed!");
    }

    public function savePlot(Plot $plot) : bool {
        if ($plot->id >= 0) {
            $stmt = $this->db->prepare(
                "UPDATE plots SET name = :name, owner = :owner, helpers = :helpers, denied = :denied, biome = :biome WHERE id = :id"
            );
        } else {
            $stmt = $this->db->prepare(
                "INSERT OR REPLACE INTO plots (id, level, X, Z, name, owner, helpers, denied, biome) VALUES
            ((select id from plots where level = :level AND X = :X AND Z = :Z),
             :level, :X, :Z, :name, :owner, :helpers, :denied, :biome);"
            );
        }
        $resulta = $stmt->execute();
        $resultb = $this->db->savepoint($this->lastSave = time());

        if ($resulta === false and $resultb == false) {
            return false;
        }
        $this->cachePlot($plot);
        return true;
    }

    public function deletePlot(Plot $plot) : bool {
        if ($plot->id >= 0) {
            $stmt = $this->db->prepare("DELETE FROM plots WHERE id = {$plot->id}");
        } else {
            $stmt = $this->db->prepare(
                "DELETE FROM plots WHERE level = {$plot->levelName} AND X = {$plot->X} AND Z = {$plot->Z}"
            );
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

    public function getPlot($levelName, $X, $Z) : Plot {
        if ($plot = $this->getPlotFromCache($levelName, $X, $Z)) {
            return $plot;
        }
        $result = $this->db->prepare(
            "SELECT id, name, owner, helpers, denied, biome FROM plots WHERE level = {$plot->levelName} AND X = {$plot->X} AND Z = {$plot->Z}"
        )->get_result();
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

    public function getPlotsByOwner($owner, $levelName = "") : array {
        if ($levelName === "") {
            $stmt = $this->db->prepare("SELECT * FROM plots WHERE owner = {$owner}");
        } else {
            $stmt = $this->db->prepare(
                "SELECT * FROM plots WHERE owner = :owner AND level = {$levelName}"
            );
        }
        $plots = [];
        $result = $stmt->get_result();
        while ($val = $result->fetch_array()) {
            $helpers = explode(",", (string)$val["helpers"]);
            $denied = explode(",", (string)$val["denied"]);
            $plots[] = new Plot((string)$val["level"], (int)$val["X"], (int)$val["Z"], (string)$val["name"],
                (string)$val["owner"], $helpers, $denied, (string)$val["biome"], (int)$val["id"]);
        }
        // Remove unloaded plots
        $plots = array_filter($plots, function($plot) {
            return $this->plugin->isLevelLoaded($plot->levelName);
        });
        // Sort plots by level
        usort($plots, function ($plot1, $plot2) {
            return strcmp($plot1->levelName, $plot2->levelName);
        });
        return $plots;
    }

    public function getNextFreePlot($levelName, $limitXZ = 0) {
        $i = 0;
        for (; $limitXZ <= 0 or $i < $limitXZ; $i++) {
            $result = $this->db->prepare(
                "SELECT X, Z FROM plots WHERE (
                level = {$levelName}
                AND (
                    (abs(X) == {$i} AND abs(Z) <= {$i}) OR
                    (abs(Z) == {$i} AND abs(X) <= {$i})
                )
            );"
            )->get_result();
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