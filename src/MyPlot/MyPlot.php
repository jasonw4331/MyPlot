<?php
namespace MyPlot;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\level\generator\Generator;
use pocketmine\event\EventPriority;
use pocketmine\plugin\MethodEventExecutor;
use pocketmine\Player;
use pocketmine\block\Block;
use SQLite3;

class MyPlot extends PluginBase implements Listener
{
    private $config;
    public static $levelData = array(), $folder;
    public $db;
    public $levels;
    private $sqlGetPlot, $sqlSavePlot, $sqlSavePlotById, $sqlRemovePlot, $sqlRemovePlotById;

    public function onEnable() {
        $folder = $this->getDataFolder();
        if (!is_dir($folder)) {
            mkdir($folder);
        }

        Generator::addGenerator(MyPlotGenerator::class, "myplot");

        $this->saveDefaultConfig();
        $this->reloadConfig();

        $pluginManager = $this->getServer()->getPluginManager();
        $pluginManager->registerEvent("pocketmine\\event\\block\\BlockBreakEvent", $this, EventPriority::HIGH, new MethodEventExecutor("onBlockBreak"), $this, false);
        $pluginManager->registerEvent("pocketmine\\event\\block\\BlockPlaceEvent", $this, EventPriority::HIGH, new MethodEventExecutor("onBlockPlace"), $this, false);

        $this->getServer()->getCommandMap()->register(Commands::class, new Commands($this));

        $this->reloadWorlds();
        $this->initDB();
    }

    public function onDisable() { }

    public function reloadWorlds() {
        foreach ($this->getServer()->getLevels() as $level) {
            if ($level->getProvider()->getGenerator() === "myplot") {
                $this->levels[$level->getName()] = $level->getProvider()->getGeneratorOptions();
            }
        }
    }

    private function initDB() {
        $this->db = new SQLite3($this->getDataFolder() . "plots.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS plots (id INT PRIMARY KEY, level TEXT, X INT, Z INT, owner TEXT, helpers TEXT)");
        $this->db->exec("CREATE TABLE IF NOT EXISTS comments (plotID INT, player TEXT, comment TEXT)");

        $this->sqlGetPlot = $this->db->prepare("SELECT id, owner, helpers FROM plots WHERE level = :level AND X = :X AND Z = :Z");
        $this->sqlSavePlot = $this->db->prepare(
            "UPDATE plots SET owner = :owner, helpers = :helpers WHERE level = :level AND X = :X AND Z = :Z;
            IF @@ROWCOUNT = 0
                INSERT INTO Table1 VALUES level = :level AND X = :X AND Z = :Z, owner = :owner, helpers = :helpers"
        );
        $this->sqlSavePlotById = $this->db->prepare("UPDATE plots SET owner = :owner, helpers = :helpers WHERE id = :id");
        $this->sqlRemovePlot = $this->db->prepare("DELETE FROM plots WHERE level = :level AND X = :X AND Z = :Z");
        $this->sqlRemovePlotById = $this->db->prepare("DELETE FROM plots WHERE id = :id");
    }

    public function onBlockBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $levelName = $player->getLevel()->getName();
        if (isset($this->levels[$levelName])) {
            $plot = $this->getPlotByPos($event->getBlock());
            if ($plot !== false) {
                $username = $player->getName();
                if (!($plot->owner === $username or $plot->isHelper($username))) {
                    $event->setCancelled(true);
                }
            }
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        $levelName = $player->getLevel()->getName();
        if (isset($this->levels[$levelName])) {
            $plot = $this->getPlotByPos($event->getBlock());
            if ($plot !== false) {
                $username = $player->getName();
                if (!($plot->owner === $username or $plot->isHelper($username))) {
                    $event->setCancelled(true);
                }
            }
        }
    }

    public function getPlot($levelName, $X, $Z) {
        $this->sqlGetPlot->bindValue(':level', $levelName, SQLITE3_TEXT);
        $this->sqlGetPlot->bindValue(':X', $X, SQLITE3_INTEGER);
        $this->sqlGetPlot->bindValue(':Z', $Z, SQLITE3_INTEGER);
        if ($result = $this->sqlGetPlot->execute()) {
            if ($result->numColumns()) {
                return $result->fetchArray(SQLITE3_ASSOC);
            }
        }
        return false;
    }

    public function levelExists($levelName) {
        return isset($this->levels[$levelName]);
    }

    public function getPlotByPos(Position $position) {
        $x = $position->x;
        $z = $position->z;
        $levelName = $position->level->getName();

        if (isset($this->levels[$levelName]) === false) {
            return false;
        }
        $levelData = $this->levels[$levelName];

        $plotSize = $levelData["PlotSize"];
        $roadWidth = $levelData["RoadWidth"];
        $totalSize = $plotSize + $roadWidth;

        if ($x >= 0) {
            $X = floor($x / $totalSize);
            $difX = $x % $totalSize;
        } else {
            $X = ceil(($x - $plotSize + 1) / $totalSize);
            $difX = abs(($x - $plotSize + 1) % $totalSize);
        }

        if ($z >= 0) {
            $Z = floor($z / $totalSize);
            $difZ = $z % $totalSize;
        } else {
            $Z = ceil(($z - $plotSize + 1) / $totalSize);
            $difZ = abs(($z - $plotSize + 1) % $totalSize);
        }

        if (($difX > $plotSize - 1) or ($difZ > $plotSize - 1)) {
            return false;
        }

        $plot = $this->getPlot($levelName, $X, $Z);
        if ($plot === false) {
            return new Plot($levelName, $X, $Z);
        } else {
            return $plot;
        }
    }

    public function savePlot(Plot $plot) {
        $helpers = implode(",", $plot->helpers);
        if ($plot->id >= 0) {
            $this->sqlSavePlotById->bindValue(":id", $plot->id, SQLITE3_INTEGER);
            $this->sqlSavePlotById->bindValue(":owner", $plot->owner, SQLITE3_TEXT);
            $this->sqlSavePlotById->bindValue(":helpers", $helpers, SQLITE3_TEXT);
            $result = $this->sqlSavePlotById->execute();
        } else {
            $this->sqlSavePlot->bindValue(":level", $plot->levelName, SQLITE3_TEXT);
            $this->sqlSavePlot->bindValue(":X", $plot->X, SQLITE3_INTEGER);
            $this->sqlSavePlot->bindValue(":Z", $plot->Z, SQLITE3_INTEGER);
            $this->sqlSavePlot->bindValue(":owner", $plot->X, SQLITE3_TEXT);
            $this->sqlSavePlot->bindValue(":helpers", $plot->Z, SQLITE3_TEXT);
            $result = $this->sqlSavePlot->execute();
        }
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    public function removePlot(Plot $plot) {
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

    public function getPlotPosition(Plot $plot) {
        if (isset($this->levels[$plot->levelName]) === false) {
            return false;
        }
        $levelData = $this->levels[$plot->levelName];

        $plotSize = $levelData["PlotSize"];
        $roadWidth = $levelData["RoadWidth"];
        $totalSize = $plotSize + $roadWidth;
        if ($plot->X < 0) {
            $x = $totalSize * $plot->X + $roadWidth;
        } else {
            $x = $totalSize * $plot->X;
        }
        if ($plot->Z < 0) {
            $z = $totalSize * $plot->Z + $roadWidth;
        } else {
            $z = $totalSize * $plot->Z;
        }
        $level = $this->getServer()->getLevel($plot->levelName);
        return new Position($x, $levelData["GroundHeight"], $z, $level);
    }

    public function teleportToPlot(Plot $plot, Player $player) {
        if (isset($this->levels[$plot->levelName]) === false) {
            return false;
        }
        $pos = $this->getPlotPosition($plot);
        $plotSize = $this->levels[$plot->levelName]["PlotSize"];
        $pos->x += floor($plotSize / 2);
        $pos->z += floor($plotSize / 2);
        $player->teleport($pos);
        return true;
    }

    public function clearPlot(Plot $plot) {
        if (isset($this->levels[$plot->levelName]) === false) {
            return false;
        }
        $levelData = $this->levels[$plot->levelName];

        $level = $this->getServer()->getLevel($plot->levelName);
        $pos1 = $pos2 = $this->getPlotPosition($plot);
        $plotSize = $levelData["PlotSize"];
        $pos2->x += $plotSize;
        $pos2->z += $plotSize;

        $height = $levelData["GroundHeight"];
        $bottomBlock = $levelData["BottomBlock"];
        $plotFillBlock = $levelData["PlotFillBlock"];
        $plotFloorBlock = $levelData["PlotFloorBlock"];
        $air = Block::get(0);
        $pos = new Position(0, 0, 0, $pos1->level);
        for ($x = $pos1->x; $x < $pos2->x; $x++) {
            $pos->x = $x;
            for ($z = $pos1->z; $z < $pos2->z; $z++) {
                $pos->z = $z;
                for ($y = 0; $y < 128; $y++) {
                    $pos->y = $y;
                    if ($y === 0) {
                        $block = $bottomBlock;
                    } elseif ($y < ($height - 1)) {
                        $block = $plotFillBlock;
                    } elseif ($y === ($height - 1)) {
                        $block = $plotFloorBlock;
                    } else {
                        $block = $air;
                    }
                    if ($level->setBlock($pos, $block, false, false) === false) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function changePlotBiome(Plot $plot, Block $floorBlock) {
        if (isset($this->levels[$plot->levelName]) === false) {
            return false;
        }
        $levelData = $this->levels[$plot->levelName];

        $level = $this->getServer()->getLevel($plot->levelName);
        $pos1 = $pos2 = $this->getPlotPosition($plot);
        $plotSize = $levelData["PlotSize"];
        $pos2->x += $plotSize;
        $pos2->z += $plotSize;

        $pos = new Position(0, $levelData["GroundHeight"], 0, $pos1->level);
        for ($x = $pos1->x; $x < $pos2->x; $x++) {
            $pos->x = $x;
            for ($z = $pos1->z; $z < $pos2->z; $z++) {
                $pos->z = $z;
                if ($level->setBlock($pos, $floorBlock, false, false) === false) {
                    return false;
                }
            }
        }
        return true;
    }
}