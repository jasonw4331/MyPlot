<?php
declare(strict_types=1);
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use MyPlot\plot\MergedPlot;
use MyPlot\plot\SinglePlot;
use pocketmine\math\Facing;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

final class DataProvider {

	/** @var BasePlot[] $cache */
	private array $cache = [];
	private int $cacheSize;
	private DataConnector $database;

	public function __construct(private MyPlot $plugin) {
		$this->database = libasynql::create($plugin, $plugin->getConfig()->get("Database"), [
			'sqlite' => 'sqlite.sql',
			'mysql' => 'mysql.sql',
		]);
		$this->cacheSize = $plugin->getConfig()->get("PlotCacheSize", 2048);
	}

	private function cachePlot(BasePlot $plot) : void{
		if($this->cacheSize > 0){
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

	private function getPlotFromCache(string $levelName, int $X, int $Z) : ?BasePlot{
		if($this->cacheSize > 0){
			$key = $levelName . ';' . $X . ';' . $Z;
			if(isset($this->cache[$key])){
				#$this->plugin->getLogger()->debug("Plot {$X};{$Z} was loaded from the cache");
				return $this->cache[$key];
			}
		}
		return null;
	}

	/**
	 * @param SinglePlot $plot
	 *
	 * @return \Generator<bool>
	 */
	public function savePlot(SinglePlot $plot) : \Generator{
		[$insertId, $affectedRows] = yield $this->database->asyncInsert('myplot.add.plot', [
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
		if($plot instanceof MergedPlot) {
			$changedRows = yield $this->database->asyncChange('myplot.remove.merge.by-xz', [
				'level' => $plot->levelName,
				'X' => $plot->X,
				'Z' => $plot->Z,
			]);
		}else{
			$changedRows = yield $this->database->asyncChange('myplot.remove.plot.by-xz', [
				'level' => $plot->levelName,
				'X' => $plot->X,
				'Z' => $plot->Z,
			]);
		}
		if($changedRows < 1){
			return false;
		}
		$this->cachePlot(new BasePlot($plot->levelName, $plot->X, $plot->Z));
		return true;
	}

	/**
	 * @param string $levelName
	 * @param int    $X
	 * @param int    $Z
	 *
	 * @return \Generator<SinglePlot>
	 */
	public function getPlot(string $levelName, int $X, int $Z) : \Generator{
		$plot = $this->getPlotFromCache($levelName, $X, $Z);
		if($plot !== null){
			return $plot;
		}
		$row = yield $this->database->asyncSelect('myplot.get.plot.by-xz', [
			'level' => $levelName,
			'X' => $X,
			'Z' => $Z
		]);
		return new SinglePlot($levelName, $X, $Z, $row['name'], $row['owner'], explode(",", $row['helpers']), explode(",", $row['denied']), $row['biome'], $row['pvp'], $row['price']);
	}

	/**
	 * @param string $owner
	 * @param string $levelName
	 *
	 * @return \Generator<array<SinglePlot>>
	 */
	public function getPlotsByOwner(string $owner, string $levelName = "") : \Generator{
		if($levelName !== null){
			$rows = yield $this->database->asyncSelect('myplot.get.all-plots.by-owner-and-level', [
				'owner' => $owner,
				'level' => $levelName,
			]);
		}else{
			$rows = yield $this->database->asyncSelect('myplot.get.all-plots.by-owner', [
				'owner' => $owner,
			]);
		}
		$plots = [];
		foreach($rows as $row){
			$plots[] = new SinglePlot($row['level'], $row['X'], $row['Z'], $row['name'], $row['owner'], explode(",", $row['helpers']), explode(",", $row['denied']), $row['biome'], $row['pvp'], $row['price']);
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
			$rows = yield $this->database->asyncSelect('myplot.get.highest-existing.by-interval', [
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
	 * @param BasePlot $base
	 * @param BasePlot ...$plots
	 *
	 * @return \Generator<bool>
	 */
	public function mergePlots(BasePlot $base, BasePlot ...$plots) : \Generator{
		$ret = true;
		foreach($plots as $plot){
			[$insertId, $affectedRows] = yield $this->database->asyncInsert('myplot.add.merge', [
				'level' => $base->levelName,
				'originX' => $base->X,
				'originZ' => $base->Z,
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
	 * @param bool     $adjacent
	 *
	 * @return \Generator<array<SinglePlot>>
	 */
	public function getMergedPlots(BasePlot $plot, bool $adjacent = false) : \Generator{
		$origin = yield $this->getMergeOrigin($plot);
		$rows = $this->database->asyncSelect('myplot.get.merge-plots.by-origin', [
			'level' => $plot->levelName,
			'originX' => $plot->X,
			'originZ' => $plot->Z
		]);
		$plots = [$origin];
		foreach($rows as $row){
			$helpers = explode(",", $row["helpers"]);
			$denied = explode(",", $row["denied"]);
			$pvp = is_numeric($row["pvp"]) ? (bool) $row["pvp"] : null;
			$plots[] = new SinglePlot($row["level"], $row["X"], $row["Z"], $row["name"], $row["owner"], $helpers, $denied, $row["biome"], $pvp, $row["price"]);
		}
		if($adjacent)
			$plots = array_filter($plots, function(BasePlot $val) use ($plot) : bool{
				foreach(Facing::HORIZONTAL as $i){
					if($plot->getSide($i)->isSame($val))
						return true;
				}
				return false;
			});
		return $plots;
	}

	/**
	 * @param BasePlot $plot
	 *
	 * @return \Generator<MergedPlot>
	 */
	public function getMergeOrigin(BasePlot $plot) : \Generator{
		$row = yield $this->database->asyncSelect('myplot.get.merge-origin.by-merged', [
			'level' => $plot->levelName,
			'mergedX' => $plot->X,
			'mergedZ' => $plot->Z
		]);
		return new MergedPlot(
			$row['level'],
			$row['X'],
			$row['Z'],
			$row['name'],
			$row['owner'],
			explode(",", $row['helpers']),
			explode(",", $row['denied']),
			$row['biome'],
			$row['pvp'],
			$row['price'],
			$xWidth,
			$zWidth
		);
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
		if($b !== 0) {
			if(!isset($plots[-$b][$a]))
				return [-$b, $a];
			if(!isset($plots[$a][-$b]))
				return [$a, -$b];
		}
		if(($a | $b) === 0) {
			if(!isset($plots[-$a][-$b]))
				return [-$a, -$b];
			if(!isset($plots[-$b][-$a]))
				return [-$b, -$a];
		}
		return null;
	}
}