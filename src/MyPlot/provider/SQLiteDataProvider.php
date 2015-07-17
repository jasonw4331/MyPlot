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
    private $sqlGetPlot, $sqlSavePlot, $sqlSavePlotById, $sqlRemovePlot, $sqlRemovePlotById;

    public function __construct(MyPlot $plugin) {
        $this->plugin = $plugin;
        $this->db = new SQLite3($plugin->getDataFolder() . "plots.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS plots (id INTEGER PRIMARY KEY AUTOINCREMENT, level TEXT, X INTEGER, Z INTEGER, name TEXT, owner TEXT, helpers TEXT)");
        //$this->db->exec("CREATE TABLE IF NOT EXISTS comments (plotID INT, player TEXT, comment TEXT)");

        $this->sqlGetPlot = $this->db->prepare("SELECT id, name, owner, helpers FROM plots WHERE level = :level AND X = :X AND Z = :Z");
        $this->sqlSavePlot = $this->db->prepare(
            "INSERT OR REPLACE INTO plots (id, level, X, Z, name, owner, helpers) VALUES
((select id from plots where level = :level AND X = :X AND Z = :Z), :level, :X, :Z, :name, :owner, :helpers);"
        );
        $this->sqlSavePlotById = $this->db->prepare("UPDATE plots SET name = :name, owner = :owner, helpers = :helpers, name = :name WHERE id = :id");
        $this->sqlRemovePlot = $this->db->prepare("DELETE FROM plots WHERE level = :level AND X = :X AND Z = :Z");
        $this->sqlRemovePlotById = $this->db->prepare("DELETE FROM plots WHERE id = :id");
    }

    public function close() {
        $this->db->close();
    }

    public function savePlot(Plot $plot) {
        $helpers = implode(",", $plot->helpers);
        if ($plot->id >= 0) {
            $this->sqlSavePlotById->bindValue(":id", $plot->id, SQLITE3_INTEGER);
            $this->sqlSavePlotById->bindValue(":name", $plot->name, SQLITE3_TEXT);
            $this->sqlSavePlotById->bindValue(":owner", $plot->owner, SQLITE3_TEXT);
            $this->sqlSavePlotById->bindValue(":helpers", $helpers, SQLITE3_TEXT);
            $result = $this->sqlSavePlotById->execute();
        } else {
            $this->sqlSavePlot->bindValue(":level", $plot->levelName, SQLITE3_TEXT);
            $this->sqlSavePlot->bindValue(":X", $plot->X, SQLITE3_INTEGER);
            $this->sqlSavePlot->bindValue(":Z", $plot->Z, SQLITE3_INTEGER);
            $this->sqlSavePlot->bindValue(":name", $plot->name, SQLITE3_TEXT);
            $this->sqlSavePlot->bindValue(":owner", $plot->owner, SQLITE3_TEXT);
            $this->sqlSavePlot->bindValue(":helpers", $helpers, SQLITE3_TEXT);
            $result = $this->sqlSavePlot->execute();
        }
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    public function deletePlot(Plot $plot) {
        if ($plot->id >= 0) {
            $this->sqlRemovePlotById->bindValue(":id", $plot->id, SQLITE3_INTEGER);
            $result = $this->sqlRemovePlotById->execute();
        } else {
            $this->sqlRemovePlot->bindValue(":level", $plot->levelName, SQLITE3_TEXT);
            $this->sqlRemovePlot->bindValue(":X", $plot->X, SQLITE3_INTEGER);
            $this->sqlRemovePlot->bindValue(":Z", $plot->Z, SQLITE3_INTEGER);
            $result = $this->sqlRemovePlot->execute();
        }
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
        if ($result = $this->sqlGetPlot->execute()) {
            if ($val = $result->fetchArray(SQLITE3_ASSOC)) {
                $helpers = explode(",", (string) $val["helpers"]);
                return new Plot($levelName, $X, $Z, (string) $val["name"], (string) $val["owner"], $helpers, (int) $val["id"]);
            }
        }
        return null;
    }
}