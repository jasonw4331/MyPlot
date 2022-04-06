<?php
declare(strict_types=1);
namespace MyPlot\provider;

use MyPlot\InternalAPI;
use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use MyPlot\plot\MergedPlot;
use MyPlot\plot\SinglePlot;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlThread;
use SOFe\AwaitGenerator\Await;

final class DataProvider{

	/** @var BasePlot[] $cache */
	private array $cache = [];
	private int $cacheSize;
	private DataConnector $database;

	/** @noinspection PhpVoidFunctionResultUsedInspection */
	public function __construct(private MyPlot $plugin, InternalAPI $internalAPI){
		$this->database = libasynql::create($plugin, $plugin->getConfig()->get("Database"), [
			'sqlite' => 'sqlite.sql',
			'mysql' => 'mysql.sql',
		], true);
		$this->database->executeGeneric('myplot.init.plots');
		$this->database->executeGeneric('myplot.init.mergedPlots');
		$this->database->executeSelect(
			'myplot.test.table',
			['tableName' => 'plotsV2'],
			fn(array $rows) => $rows[0]['tables'] === 0 ?: $this->database->executeMulti('myplot.convert.tables', [], SqlThread::MODE_CHANGE)
		);
		$this->cacheSize = $plugin->getConfig()->get("PlotCacheSize", 2500);
		$maxPlotCoord = floor(sqrt($this->cacheSize) / 2);
		foreach($internalAPI->getAllLevelSettings() as $levelName => $settings){
			for($x = -$maxPlotCoord; $x < $maxPlotCoord; ++$x){
				for($z = -$maxPlotCoord; $z < $maxPlotCoord; ++$z){
					Await::g2c(
						$this->getMergedPlot(new BasePlot($levelName, $x, $z))
					);
				}
			}
		}
	}

	private function cachePlot(BasePlot $plot) : void{
		if($this->cacheSize > 0){
			if($plot instanceof MergedPlot){
				for($x = $plot->X; $x <= $plot->xWidth + $plot->X; ++$x){
					for($z = $plot->Z; $z <= $plot->zWidth + $plot->Z; ++$z){
						$key = $plot->levelName . ';' . $x . ';' . $z;
						if(isset($this->cache[$key])){
							unset($this->cache[$key]);
						}elseif($this->cacheSize <= count($this->cache)){
							array_shift($this->cache);
						}
						$this->cache = array_merge([$key => clone $plot], $this->cache);
						$this->plugin->getLogger()->debug("Plot $x;$z has been cached");
					}
				}
				return;
			}
			$key = $plot->levelName . ';' . $plot->X . ';' . $plot->Z;
			if(isset($this->cache[$key])){
				unset($this->cache[$key]);
			}elseif($this->cacheSize <= count($this->cache)){
				array_shift($this->cache);
			}
			$this->cache = array_merge([$key => clone $plot], $this->cache);
			$this->plugin->getLogger()->debug("Plot $plot->X;$plot->Z has been cached");
		}
	}

	public function getPlotFromCache(string $levelName, int $X, int $Z) : BasePlot{
		if($this->cacheSize > 0){
			$key = $levelName . ';' . $X . ';' . $Z;
			if(isset($this->cache[$key])){
				$this->plugin->getLogger()->debug("Plot {$X};{$Z} was loaded from the cache");
				return $this->cache[$key] ?? new BasePlot($levelName, $X, $Z);
			}
		}
		return new BasePlot($levelName, $X, $Z);
	}

	/**
	 * @param SinglePlot $plot
	 *
	 * @return \Generator<bool>
	 */
	public function savePlot(SinglePlot $plot) : \Generator{
		[$insertId, $affectedRows] = yield from $this->database->asyncInsert('myplot.add.plot', [
			'level' => $plot->levelName,
			'X' => $plot->X,
			'Z' => $plot->Z,
			'name' => $plot->name,
			'owner' => $plot->owner,
			'helpers' => implode(",", $plot->helpers),
			'denied' => implode(",", $plot->denied),
			'biome' => $plot->biome,
			'pvp' => $plot->pvp,
			'price' => $plot->price,
		]);
		if($affectedRows < 1){
			return false;
		}
		$this->cachePlot($plot);
		return true;
	}

	/**
	 * @param BasePlot $plot
	 *
	 * @return \Generator<bool>
	 */
	public function deletePlot(BasePlot $plot) : \Generator{
		if($plot instanceof MergedPlot){
			$plotLevel = $this->plugin->getLevelSettings($plot->levelName);

			$changedRows = yield from $this->database->asyncChange('myplot.remove.merge.by-xz', [
				'level' => $plot->levelName,
				'X' => $plot->X,
				'Z' => $plot->Z,
				'pvp' => !$plotLevel->restrictPVP,
				'price' => $plotLevel->claimPrice,
			]);
			$this->cachePlot(new MergedPlot($plot->levelName, $plot->X, $plot->Z, $plot->xWidth, $plot->zWidth, pvp: !$plotLevel->restrictPVP, price: $plotLevel->claimPrice));
		}else{
			$changedRows = yield from $this->database->asyncChange('myplot.remove.plot.by-xz', [
				'level' => $plot->levelName,
				'X' => $plot->X,
				'Z' => $plot->Z,
			]);
			$this->cachePlot(new BasePlot($plot->levelName, $plot->X, $plot->Z));
		}
		if($changedRows < 1){
			return false;
		}
		return true;
	}

	/**
	 * @param string $levelName
	 * @param int    $X
	 * @param int    $Z
	 *
	 * @return \Generator<BasePlot>
	 */
	public function getPlot(string $levelName, int $X, int $Z) : \Generator{
		$plot = $this->getPlotFromCache($levelName, $X, $Z);
		if($plot instanceof SinglePlot){
			return $plot;
		}
		$row = (yield from $this->database->asyncSelect('myplot.get.plot.by-xz', [
			'level' => $levelName,
			'X' => $X,
			'Z' => $Z
		]))[0];
		if(count($row) < 1)
			return $plot;

		return new SinglePlot($levelName, $X, $Z, $row['name'], $row['owner'], explode(",", $row['helpers']), explode(",", $row['denied']), $row['biome'], (bool) $row['pvp'], $row['price']);
	}

	/**
	 * @param string $owner
	 * @param string $levelName
	 *
	 * @return \Generator<array<SinglePlot>>
	 */
	public function getPlotsByOwner(string $owner, string $levelName = "") : \Generator{
		if($levelName !== ''){
			$rows = yield from $this->database->asyncSelect('myplot.get.all-plots.by-owner-and-level', [
				'owner' => $owner,
				'level' => $levelName,
			]);
		}else{
			$rows = yield from $this->database->asyncSelect('myplot.get.all-plots.by-owner', [
				'owner' => $owner,
			]);
		}
		$plots = [];
		foreach($rows as $row){
			$plots[] = yield from $this->getMergedPlot(new BasePlot($row['level'], $row['X'], $row['Z']));
		}
		return $plots;
	}

	/**
	 * @param string $levelName
	 * @param int    $limitXZ
	 *
	 * @return \Generator<BasePlot|null>
	 */
	public function getNextFreePlot(string $levelName, int $limitXZ = 0) : \Generator{
		for($i = 0; $limitXZ <= 0 or $i < $limitXZ; $i++){
			$rows = yield from $this->database->asyncSelect('myplot.get.highest-existing.by-interval', [
				'level' => $levelName,
				'number' => $i,
			]);
			$plots = [];
			foreach($rows as $row){
				$plots[$row['X']][$row['Z']] = true;
			}
			if(count($plots) === max(1, 8 * $i)){
				continue;
			}
			for($a = 0; $a <= $i; $a++){
				if(($ret = self::findEmptyPlotSquared($a, $i, $plots)) !== null){
					[$X, $Z] = $ret;
					return new BasePlot($levelName, $X, $Z);
				}
			}
		}
		return null;
	}

	/**
	 * @param MergedPlot $base
	 * @param BasePlot   ...$plots
	 *
	 * @return \Generator<bool>
	 */
	public function mergePlots(MergedPlot $base, BasePlot ...$plots) : \Generator{
		$minX = 0;
		$minZ = 0;
		foreach($plots as $plot){
			if(min($minX, $plot->X))
				$minX = $plot->X;
			if(min($minZ, $plot->Z))
				$minZ = $plot->Z;
		}

		$ret = true;
		foreach($plots as $plot){
			if($minX !== $base->X and $minZ !== $base->Z){
				$affectedRows = yield from $this->database->asyncChange('myplot.remove.merge-entry', [
					'level' => $plot->levelName,
					'originX' => $base->X,
					'originZ' => $base->Z,
					'mergedX' => $plot->X,
					'mergedZ' => $plot->Z
				]);
				if($affectedRows < 1){
					MyPlot::getInstance()->getLogger()->debug("Failed to delete merge entry for $plot with base $base");
					$ret = false;
				}
			}
			[$insertId, $affectedRows] = yield from $this->database->asyncInsert('myplot.add.merge', [
				'level' => $plot->levelName,
				'originX' => $minX,
				'originZ' => $minZ,
				'mergedX' => $plot->X,
				'mergedZ' => $plot->Z
			]);
			if($affectedRows < 1){
				MyPlot::getInstance()->getLogger()->debug("Failed to merge plot $plot into $base");
				$ret = false;
			}
		}
		return $ret;
	}

	/**
	 * @param BasePlot $plot
	 *
	 * @return \Generator<BasePlot>
	 */
	public function getMergedPlot(BasePlot $plot) : \Generator{
		$plot = $this->getPlotFromCache($plot->levelName, $plot->X, $plot->Z);
		if($plot instanceof MergedPlot)
			return $plot;

		$rows = yield from $this->database->asyncSelect('myplot.get.merge-plots.by-origin', [
			'level' => $plot->levelName,
			'originX' => $plot->X,
			'originZ' => $plot->Z
		]);
		if(count($rows) < 1){
			$rows = yield from $this->database->asyncSelect('myplot.get.merge-plots.by-merged', [
				'level' => $plot->levelName,
				'mergedX' => $plot->X,
				'mergedZ' => $plot->Z
			]);
			if(count($rows) < 1){
				return yield from $this->getPlot($plot->levelName, $plot->X, $plot->Z);
			}
		}
		$highestX = $highestZ = $lowestX = $lowestZ = 0;
		foreach($rows as $row){
			$highestX = max($highestX, $row['X']);
			$highestZ = max($highestZ, $row['Z']);
			$lowestX = min($lowestX, $row['X']);
			$lowestZ = min($lowestZ, $row['Z']);
		}

		$plot = new MergedPlot(
			$rows[0]["level"],
			$rows[0]["X"],
			$rows[0]["Z"],
			$highestX - $lowestX,
			$highestZ - $lowestZ,
			$rows[0]["name"],
			$rows[0]["owner"],
			explode(",", $rows[0]["helpers"]),
			explode(",", $rows[0]["denied"]),
			$rows[0]["biome"],
			is_numeric($rows[0]["pvp"]) ? (bool) $rows[0]["pvp"] : null,
			$rows[0]["price"] * ($highestX - $lowestX) * ($highestZ - $lowestZ)
		);
		$this->cachePlot($plot);
		return $plot;
	}

	public function close() : void{
		$this->database->close();
	}

	/**
	 * @param int      $a
	 * @param int      $b
	 * @param bool[][] $plots
	 *
	 * @return int[]|null
	 */
	private static function findEmptyPlotSquared(int $a, int $b, array $plots) : ?array{
		if(!isset($plots[$a][$b]))
			return [$a, $b];
		if(!isset($plots[$b][$a]))
			return [$b, $a];
		if($a !== 0){
			if(!isset($plots[-$a][$b]))
				return [-$a, $b];
			if(!isset($plots[$b][-$a]))
				return [$b, -$a];
		}
		if($b !== 0){
			if(!isset($plots[-$b][$a]))
				return [-$b, $a];
			if(!isset($plots[$a][-$b]))
				return [$a, -$b];
		}
		if(($a | $b) === 0){
			if(!isset($plots[-$a][-$b]))
				return [-$a, -$b];
			if(!isset($plots[-$b][-$a]))
				return [-$b, -$a];
		}
		return null;
	}
}