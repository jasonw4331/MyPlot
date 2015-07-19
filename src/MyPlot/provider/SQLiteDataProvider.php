<?php
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\Plot;
use SQLite3;
use SQLite3Stmt;

class SQLiteDataProvider implements DataProvider
{
    private $plugin;

    /** @var SQLite3 */
    private $db;

    /** @var SQLite3Stmt */
    private $sqlGetPlot, $sqlSavePlot, $sqlSavePlotById, $sqlRemovePlot,
            $sqlRemovePlotById, $sqlGetPlotsByOwner, $sqlGetPlotsByOwnerAndLevel,
            $sqlGetExistingXZ;

    public function __construct(MyPlot $plugin) {
        $this->plugin = $plugin;
        $this->db = new SQLite3($plugin->getDataFolder() . "plots.db");
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS plots
            (id INTEGER PRIMARY KEY AUTOINCREMENT, level TEXT, X INTEGER, Z INTEGER, name TEXT,
             owner TEXT, helpers TEXT)"
        );
        //$this->db->exec("CREATE TABLE IF NOT EXISTS comments (plotID INT, player TEXT, comment TEXT)");

        $this->sqlGetPlot = $this->db->prepare(
            "SELECT id, name, owner, helpers FROM plots WHERE level = :level AND X = :X AND Z = :Z"
        );
        $this->sqlSavePlot = $this->db->prepare(
            "INSERT OR REPLACE INTO plots (id, level, X, Z, name, owner, helpers) VALUES
            ((select id from plots where level = :level AND X = :X AND Z = :Z),
             :level, :X, :Z, :name, :owner, :helpers);"
        );
        $this->sqlSavePlotById = $this->db->prepare(
            "UPDATE plots SET name = :name, owner = :owner, helpers = :helpers, name = :name WHERE id = :id"
        );
        $this->sqlRemovePlot = $this->db->prepare(
            "DELETE FROM plots WHERE level = :level AND X = :X AND Z = :Z"
        );
        $this->sqlRemovePlotById = $this->db->prepare("DELETE FROM plots WHERE id = :id");
        $this->sqlGetPlotsByOwner = $this->db->prepare("SELECT * FROM plots WHERE owner = :owner");
        $this->sqlGetPlotsByOwnerAndLevel = $this->db->prepare(
            "SELECT * FROM plots WHERE owner = :owner AND level = :level"
        );
        $this->sqlGetExistingXZ = $this->db->prepare(
            "SELECT X, Z FROM plots WHERE (
                level = :level
                AND abs(X) >= :min AND abs(X) <= :max
                AND abs(Z) >= :min AND abs(Z) <= :max
            )"
        );
    }

    public function close() {
        $this->db->close();
    }

    public function savePlot(Plot $plot) {
        $helpers = implode(",", $plot->helpers);
        // Need to be fixed
        //if ($plot->id >= 0) {
        //    $stmt = $this->sqlSavePlotById;
        //    $stmt->bindValue(":id", $plot->id, SQLITE3_INTEGER);
        //} else {
            $stmt = $this->sqlSavePlot;
            $stmt->bindValue(":level", $plot->levelName, SQLITE3_TEXT);
            $stmt->bindValue(":X", $plot->X, SQLITE3_INTEGER);
            $stmt->bindValue(":Z", $plot->Z, SQLITE3_INTEGER);
        //}
        $stmt->bindValue(":name", $plot->name, SQLITE3_TEXT);
        $stmt->bindValue(":owner", $plot->owner, SQLITE3_TEXT);
        $stmt->bindValue(":helpers", $helpers, SQLITE3_TEXT);

        $result = $this->sqlSavePlot->execute();
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    public function deletePlot(Plot $plot) {
        if ($plot->id >= 0) {
            $stmt = $this->sqlRemovePlotById;
            $stmt->bindValue(":id", $plot->id, SQLITE3_INTEGER);
        } else {
            $stmt = $this->sqlRemovePlot;
            $stmt->bindValue(":level", $plot->levelName, SQLITE3_TEXT);
            $stmt->bindValue(":X", $plot->X, SQLITE3_INTEGER);
            $stmt->bindValue(":Z", $plot->Z, SQLITE3_INTEGER);
        }

        $result = $stmt->execute();
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    public function getPlot($levelName, $X, $Z) {
        $this->sqlGetPlot->bindValue(":level", $levelName, SQLITE3_TEXT);
        $this->sqlGetPlot->bindValue(":X", $X, SQLITE3_INTEGER);
        $this->sqlGetPlot->bindValue(":Z", $Z, SQLITE3_INTEGER);
        $result = $this->sqlGetPlot->execute();
        if ($val = $result->fetchArray(SQLITE3_ASSOC)) {
            if ($val["helpers"] === null or $val["helpers"] === "") {
                $helpers = [];
            } else {
                $helpers = explode(",", (string) $val["helpers"]);
            }
            return new Plot($levelName, $X, $Z, (string) $val["name"], (string) $val["owner"],
                            $helpers, (int) $val["id"]);
        }
        return null;
    }

    public function getPlotsByOwner($owner, $levelName = "") {
        if ($levelName === "") {
            $stmt = $this->sqlGetPlotsByOwner;
        } else {
            $stmt = $this->sqlGetPlotsByOwnerAndLevel;
            $stmt->bindValue(":level", $levelName, SQLITE3_TEXT);
        }
        $stmt->bindValue(":owner", $owner, SQLITE3_TEXT);
        $plots = [];
        $result = $stmt->execute();
        while ($val = $result->fetchArray(SQLITE3_ASSOC)) {
            $helpers = explode(",", (string) $val["helpers"]);
            $plots[] = new Plot($val["level"], $val["X"], $val["Z"], (string) $val["name"],
                                (string) $val["owner"], $helpers, $val["id"]);
        }
        return $plots;
    }

    public function getNextFreePlot($levelName, $limitXZ = 20) {
        $this->sqlGetExistingXZ->bindValue(":level", $levelName, SQLITE3_TEXT);
        for ($i = 1; $i < 20; $i++) {
            $this->sqlGetExistingXZ->bindValue(":min", $i - 1, SQLITE3_INTEGER);
            $this->sqlGetExistingXZ->bindValue(":max", $i, SQLITE3_INTEGER);
            $result = $this->sqlGetExistingXZ->execute();
            $plots = [];
            while ($val = $result->fetchArray(SQLITE3_ASSOC)) {
                $plots[$val["X"]][$val["Z"]] = true;
            }
            if (empty($plots)) {
                continue;
            }
            for ($X = -$i; $X <= $i; $X++) {
                for ($Z = -$i; $Z <= $i; $Z++) {
                    if (!isset($plots[$X][$Z])) {
                        return new Plot($levelName, $X, $Z);
                    }
                }
            }
        }
        return null;
    }
}