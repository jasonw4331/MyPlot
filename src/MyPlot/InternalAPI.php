<?php
declare(strict_types=1);
namespace MyPlot;

use jasonwynn10\MyPlot\utils\AsyncVariants;
use muqsit\worldstyler\Selection;
use muqsit\worldstyler\shapes\CommonShape;
use muqsit\worldstyler\shapes\Cuboid;
use muqsit\worldstyler\WorldStyler;
use MyPlot\events\MyPlotClearEvent;
use MyPlot\events\MyPlotCloneEvent;
use MyPlot\events\MyPlotDisposeEvent;
use MyPlot\events\MyPlotFillEvent;
use MyPlot\events\MyPlotMergeEvent;
use MyPlot\events\MyPlotResetEvent;
use MyPlot\events\MyPlotSaveEvent;
use MyPlot\events\MyPlotSettingEvent;
use MyPlot\events\MyPlotTeleportEvent;
use MyPlot\plot\BasePlot;
use MyPlot\plot\MergedPlot;
use MyPlot\plot\SinglePlot;
use MyPlot\provider\DataProvider;
use MyPlot\provider\InternalEconomyProvider;
use MyPlot\task\ClearBorderTask;
use MyPlot\task\ClearPlotTask;
use MyPlot\task\FillPlotTask;
use MyPlot\task\RoadFillTask;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\promise\PromiseResolver;
use pocketmine\utils\TextFormat as TF;
use pocketmine\world\biome\Biome;
use pocketmine\world\biome\BiomeRegistry;
use pocketmine\world\Position;
use pocketmine\world\World;
use SOFe\AwaitGenerator\Await;

final class InternalAPI{
	/** @var PlotLevelSettings[] $levels */
	private array $levels = [];
	private DataProvider $dataProvider;

	public function __construct(private MyPlot $plugin, private ?InternalEconomyProvider $economyProvider){
		$plugin->getLogger()->debug(TF::BOLD . "Loading Data Provider settings");
		$this->dataProvider = new DataProvider($plugin);
	}

	public function getEconomyProvider() : ?InternalEconomyProvider{
		return $this->economyProvider;
	}

	public function setEconomyProvider(?InternalEconomyProvider $economyProvider) : void{
		$this->economyProvider = $economyProvider;
	}

	public function getAllLevelSettings() : array{
		return $this->levels;
	}

	public function getLevelSettings(string $levelName) : ?PlotLevelSettings{
		return $this->levels[$levelName] ?? null;
	}

	public function addLevelSettings(string $levelName, PlotLevelSettings $settings) : void{
		$this->levels[$levelName] = $settings;
	}

	public function unloadLevelSettings(string $levelName) : bool{
		if(isset($this->levels[$levelName])){
			unset($this->levels[$levelName]);
			return true;
		}
		return false;
	}

	/**
	 * @param SinglePlot    $plot
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function savePlot(SinglePlot $plot, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generatePlotsToSave($plot),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generatePlotsToSave(SinglePlot $plot) : \Generator{
		$ev = new MyPlotSaveEvent($plot);
		$ev->call();
		$plot = $ev->getPlot();
		$failed = false;
		foreach((yield $this->dataProvider->getMergedPlots($plot)) as $merged){
			$savePlot = clone $plot;
			$savePlot->X = $merged->X;
			$savePlot->Z = $merged->Z;
			$savePlot->levelName = $merged->levelName;
			$saved = yield $this->dataProvider->savePlot($plot);
			if(!$saved){
				$failed = true;
			}
		}
		return !$failed;
	}

	/**
	 * @param string        $username
	 * @param string|null   $levelName
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(array<BasePlot>): void)|null $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null  $catches
	 */
	public function getPlotsOfPlayer(string $username, ?string $levelName, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generatePlotsOfPlayer($username, $levelName),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	/**
	 * @param string      $username
	 * @param string|null $levelName
	 *
	 * @return \Generator<array<BasePlot>>
	 */
	public function generatePlotsOfPlayer(string $username, ?string $levelName) : \Generator {
		return yield $this->dataProvider->getPlotsByOwner($username, $levelName);
	}

	/**
	 * @param string        $levelName
	 * @param int           $limitXZ
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(BasePlot): void)|null $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null    $catches
	 */
	public function getNextFreePlot(string $levelName, int $limitXZ, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateNextFreePlot($levelName, $limitXZ),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateNextFreePlot(string $levelName, int $limitXZ) : \Generator{
		return yield $this->dataProvider->getNextFreePlot($levelName, $limitXZ);
	}

	public function getPlotFast(float &$x, float &$z, PlotLevelSettings $plotLevel) : ?BasePlot{
		$plotSize = $plotLevel->plotSize;
		$roadWidth = $plotLevel->roadWidth;
		$totalSize = $plotSize + $roadWidth;
		if($x >= 0){
			$difX = $x % $totalSize;
			$x = (int) floor($x / $totalSize);
		}else{
			$difX = abs(($x - $plotSize + 1) % $totalSize);
			$x = (int) ceil(($x - $plotSize + 1) / $totalSize);
		}
		if($z >= 0){
			$difZ = $z % $totalSize;
			$z = (int) floor($z / $totalSize);
		}else{
			$difZ = abs(($z - $plotSize + 1) % $totalSize);
			$z = (int) ceil(($z - $plotSize + 1) / $totalSize);
		}
		if(($difX > $plotSize - 1) or ($difZ > $plotSize - 1))
			return null;

		return new BasePlot($plotLevel->name, $x, $z);
	}

	/**
	 * @param string        $worldName
	 * @param int           $X
	 * @param int           $Z
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(SinglePlot): void)|null   $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function getPlot(string $worldName, int $X, int $Z, ?callable $onComplete = null, ?callable $onFail = null) : void {
		Await::g2c(
			$this->generatePlot($worldName, $X, $Z),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	/**
	 * @param string $worldName
	 * @param int    $X
	 * @param int    $Z
	 *
	 * @return \Generator<SinglePlot>
	 */
	public function generatePlot(string $worldName, int $X, int $Z) : \Generator {
		// TODO: if merged return MergedPlot object
		return yield $this->dataProvider->getPlot($worldName, $X, $Z);
	}

	/**
	 * @param Position      $position
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(BasePlot): void)|null   $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function getPlotByPosition(Position $position, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generatePlotByPosition($position),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generatePlotByPosition(Position $position) : \Generator{
		$x = $position->x;
		$z = $position->z;
		$levelName = $position->getWorld()->getFolderName();
		if($this->getLevelSettings($levelName) === null)
			return null;
		$plotLevel = $this->getLevelSettings($levelName);

		$plot = $this->getPlotFast($x, $z, $plotLevel);
		if($plot instanceof SinglePlot)
			return yield $this->dataProvider->getMergeOrigin($plot);

		$basePlot = yield $this->dataProvider->getPlot($levelName, $x, $z);
		if(!$basePlot->isMerged())
			return null;

		// no plot found at current location yet, so search cardinal directions
		$plotN = $basePlot->getSide(Facing::NORTH);
		if($plotN->isSame($basePlot))
			return yield $this->dataProvider->getMergeOrigin($plotN);

		$plotS = $basePlot->getSide(Facing::SOUTH);
		if($plotS->isSame($basePlot))
			return yield $this->dataProvider->getMergeOrigin($plotS);

		$plotE = $basePlot->getSide(Facing::EAST);
		if($plotE->isSame($basePlot))
			return yield $this->dataProvider->getMergeOrigin($plotE);

		$plotW = $basePlot->getSide(Facing::WEST);
		if($plotW->isSame($basePlot))
			return yield $this->dataProvider->getMergeOrigin($plotW);

		return null;
	}

	/**
	 * @param SinglePlot    $plot
	 * @param bool          $mergeOrigin
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(Position): void)|null   $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function getPlotPosition(SinglePlot $plot, bool $mergeOrigin, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generatePlotPosition($plot, $mergeOrigin),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generatePlotPosition(BasePlot $plot, bool $mergeOrigin) : \Generator{
		$plotLevel = $this->getLevelSettings($plot->levelName);
		$origin = yield $this->dataProvider->getMergeOrigin($plot);
		$plotSize = $plotLevel->plotSize;
		$roadWidth = $plotLevel->roadWidth;
		$totalSize = $plotSize + $roadWidth;
		if($mergeOrigin){
			$x = $totalSize * $origin->X;
			$z = $totalSize * $origin->Z;
		}else{
			$x = $totalSize * $plot->X;
			$z = $totalSize * $plot->Z;
		}
		$level = $this->plugin->getServer()->getWorldManager()->getWorldByName($plot->levelName);
		return new Position($x, $plotLevel->groundHeight, $z, $level);
	}

	/**
	 * @param Position      $position
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function isPositionBorderingPlot(Position $position, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::f2c(
			function() use ($position){
				$plot = yield $this->generatePlotBorderingPosition($position);
				return $plot instanceof SinglePlot;
			},
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	/**
	 * @param Position      $position
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(BasePlot): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function getPlotBorderingPosition(Position $position, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generatePlotBorderingPosition($position),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generatePlotBorderingPosition(Position $position) : \Generator{
		if(!$position->isValid())
			return null;
		foreach(Facing::HORIZONTAL as $i){
			$pos = $position->getSide($i);
			$x = $pos->x;
			$z = $pos->z;
			$levelName = $pos->getWorld()->getFolderName();

			if($this->getLevelSettings($levelName) === null)
				return null;

			$plotLevel = $this->getLevelSettings($levelName);
			$plotSize = $plotLevel->plotSize;
			$roadWidth = $plotLevel->roadWidth;
			$totalSize = $plotSize + $roadWidth;
			if($x >= 0){
				$X = (int) floor($x / $totalSize);
				$difX = $x % $totalSize;
			}else{
				$X = (int) ceil(($x - $plotSize + 1) / $totalSize);
				$difX = abs(($x - $plotSize + 1) % $totalSize);
			}
			if($z >= 0){
				$Z = (int) floor($z / $totalSize);
				$difZ = $z % $totalSize;
			}else{
				$Z = (int) ceil(($z - $plotSize + 1) / $totalSize);
				$difZ = abs(($z - $plotSize + 1) % $totalSize);
			}
			if(($difX > $plotSize - 1) or ($difZ > $plotSize - 1)){
				$basePlot = yield $this->dataProvider->getPlot($levelName, $x, $z);
				if(!$basePlot->isMerged())
					return null;

				// no plot found at current location yet, so search cardinal directions
				$plotN = $basePlot->getSide(Facing::NORTH);
				if($plotN->isSame($basePlot))
					return yield $this->dataProvider->getMergeOrigin($plotN);

				$plotS = $basePlot->getSide(Facing::SOUTH);
				if($plotS->isSame($basePlot))
					return yield $this->dataProvider->getMergeOrigin($plotS);

				$plotE = $basePlot->getSide(Facing::EAST);
				if($plotE->isSame($basePlot))
					return yield $this->dataProvider->getMergeOrigin($plotE);

				$plotW = $basePlot->getSide(Facing::WEST);
				if($plotW->isSame($basePlot))
					return yield $this->dataProvider->getMergeOrigin($plotW);
				continue;
			}
			return yield $this->dataProvider->getPlot($levelName, $X, $Z);
		}
		return null;
	}

	/**
	 * @param SinglePlot    $plot
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(AxisAlignedBB): void)|null $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null    $catches
	 */
	public function getPlotBB(SinglePlot $plot, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generatePlotBB($plot),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generatePlotBB(BasePlot $plot) : \Generator{
		$plotLevel = $this->getLevelSettings($plot->levelName);
		$plotSize = $plotLevel->plotSize - 1;
		$pos = yield $this->generatePlotPosition($plot, false);
		$xMax = $pos->x + $plotSize;
		$zMax = $pos->z + $plotSize;
		foreach((yield $this->dataProvider->getMergedPlots($plot)) as $mergedPlot){
			$xplot = (yield $this->generatePlotPosition($mergedPlot, false))->x;
			$zplot = (yield $this->generatePlotPosition($mergedPlot, false))->z;
			$xMaxPlot = (int) ($xplot + $plotSize);
			$zMaxPlot = (int) ($zplot + $plotSize);
			if($pos->x > $xplot) $pos->x = $xplot;
			if($pos->z > $zplot) $pos->z = $zplot;
			if($xMax < $xMaxPlot) $xMax = $xMaxPlot;
			if($zMax < $zMaxPlot) $zMax = $zMaxPlot;
		}

		return new AxisAlignedBB(
			min($pos->x, $xMax),
			$pos->getWorld()->getMinY(),
			min($pos->z, $zMax),
			max($pos->x, $xMax),
			$pos->getWorld()->getMaxY(),
			max($pos->z, $zMax)
		);
	}

	/**
	 * @param SinglePlot    $plot
	 * @param int           $direction
	 * @param int           $maxBlocksPerTick
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function mergePlots(SinglePlot $plot, int $direction, int $maxBlocksPerTick, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateMergePlots($plot, $direction, $maxBlocksPerTick),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateMergePlots(SinglePlot $plot, int $direction, int $maxBlocksPerTick) : \Generator{
		if($this->getLevelSettings($plot->levelName) === null)
			return false;
		/** @var SinglePlot[][] $toMerge */
		$toMerge = [];
		/** @var SinglePlot[] $mergedPlots */
		$mergedPlots = yield $this->dataProvider->getMergedPlots($plot);
		$newPlot = $plot->getSide($direction);
		$alreadyMerged = false;
		foreach($mergedPlots as $mergedPlot){
			if($mergedPlot->isSame($newPlot)){
				$alreadyMerged = true;
			}
		}
		if($alreadyMerged === false and $newPlot->isMerged()){
			$this->plugin->getLogger()->debug("Failed to merge due to plot origin mismatch");
			return false;
		}
		$toMerge[] = [$plot, $newPlot];

		foreach($mergedPlots as $mergedPlot){
			$newPlot = $mergedPlot->getSide($direction);
			$alreadyMerged = false;
			foreach($mergedPlots as $mergedPlot2){
				if($mergedPlot2->isSame($newPlot)){
					$alreadyMerged = true;
				}
			}
			if($alreadyMerged === false and $newPlot->isMerged()){
				$this->plugin->getLogger()->debug("Failed to merge due to plot origin mismatch");
				return false;
			}
			$toMerge[] = [$mergedPlot, $newPlot];
		}
		/** @var BasePlot[][] $toFill */
		$toFill = [];
		foreach($toMerge as $pair){
			foreach($toMerge as $pair2){
				foreach(Facing::HORIZONTAL as $i){
					if($pair[1]->getSide($i)->isSame($pair2[1])){
						$toFill[] = [$pair[1], $pair2[1]];
					}
				}
			}
		}
		$ev = new MyPlotMergeEvent(yield $this->dataProvider->getMergeOrigin($plot), $toMerge);
		$ev->call();
		if($ev->isCancelled()){
			return false;
		}
		foreach($toMerge as $pair){
			if($pair[1]->owner === ""){
				$this->plugin->getLogger()->debug("Failed to merge due to plot not claimed");
				return false;
			}elseif($plot->owner !== $pair[1]->owner){
				$this->plugin->getLogger()->debug("Failed to merge due to owner mismatch");
				return false;
			}
		}

		// TODO: WorldStyler clearing

		foreach($toMerge as $pair)
			$this->plugin->getScheduler()->scheduleTask(new RoadFillTask($this->plugin, $pair[0], $pair[1], false, -1, $maxBlocksPerTick));

		foreach($toFill as $pair)
			$this->plugin->getScheduler()->scheduleTask(new RoadFillTask($this->plugin, $pair[0], $pair[1], true, $direction, $maxBlocksPerTick));

		return yield $this->dataProvider->mergePlots(yield $this->dataProvider->getMergeOrigin($plot), ...array_map(function(array $val) : SinglePlot{
			return $val[1];
		}, $toMerge));
	}

	/**
	 * @param Player        $player
	 * @param SinglePlot    $plot
	 * @param bool          $center
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function teleportPlayerToPlot(Player $player, SinglePlot $plot, bool $center, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generatePlayerTeleport($player, $plot, $center),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generatePlayerTeleport(Player $player, BasePlot $plot, bool $center) : \Generator{
		$ev = new MyPlotTeleportEvent($plot, $player, $center);
		$ev->call();
		if($ev->isCancelled())
			return false;

		if($center){
			$pos = $plot instanceof MergedPlot ? yield $this->getMergeMid($plot) : yield $this->getPlotMid($plot);
			return $player->teleport($pos);
		}

		if($plot instanceof MergedPlot){
			$plotLevel = $this->getLevelSettings($plot->levelName);

			$mergedPlots = yield $this->dataProvider->getMergedPlots($plot);
			$minx = (yield $this->generatePlotPosition(yield AsyncVariants::array_reduce($mergedPlots, function(SinglePlot $a, SinglePlot $b){
				return (yield $this->generatePlotPosition($a, false))->x < (yield $this->generatePlotPosition($b, false))->x ? $a : $b;
			}), false))->x;
			$maxx = (yield $this->generatePlotPosition(yield AsyncVariants::array_reduce($mergedPlots, function(SinglePlot $a, SinglePlot $b){
					return (yield $this->generatePlotPosition($a, false))->x > (yield $this->generatePlotPosition($b, false))->x ? $a : $b;
				}), false))->x + $plotLevel->plotSize;
			$minz = (yield $this->generatePlotPosition(yield AsyncVariants::array_reduce($mergedPlots, function(SinglePlot $a, SinglePlot $b){
				return (yield $this->generatePlotPosition($a, false))->z < (yield $this->generatePlotPosition($b, false))->z ? $a : $b;
			}), false))->z;

			$pos = new Position($minx, $plotLevel->groundHeight, $minz, $this->plugin->getServer()->getWorldManager()->getWorldByName($plot->levelName));
			$pos->x = floor(($minx + $maxx) / 2);
			$pos->y += 1.5;
			$pos->z -= 1;
			return $player->teleport($pos);
		}

		$plotLevel = $this->getLevelSettings($plot->levelName);
		$pos = yield $this->generatePlotPosition($plot, true);
		$pos->x += floor($plotLevel->plotSize / 2);
		$pos->y += 1.5;
		$pos->z -= 1;
		return $player->teleport($pos);
	}

	private function getPlotMid(BasePlot $plot) : \Generator{
		if($this->getLevelSettings($plot->levelName) === null)
			return null;

		$plotLevel = $this->getLevelSettings($plot->levelName);
		$plotSize = $plotLevel->plotSize;
		$pos = yield $this->generatePlotPosition($plot, false);
		return new Position($pos->x + ($plotSize / 2), $pos->y + 1, $pos->z + ($plotSize / 2), $pos->getWorld());
	}

	private function getMergeMid(SinglePlot $plot) : \Generator{
		$plotLevel = $this->getLevelSettings($plot->levelName);
		$plotSize = $plotLevel->plotSize;
		$mergedPlots = yield $this->dataProvider->getMergedPlots($plot);
		$minx = (yield $this->generatePlotPosition(
			yield AsyncVariants::array_reduce($mergedPlots, function(SinglePlot $a, SinglePlot $b){
				return (yield $this->generatePlotPosition($a, false))->x < (yield $this->generatePlotPosition($b, false))->x ? $a : $b;
			}),
			false
		))->x;
		$maxx = (yield $this->generatePlotPosition(
			yield AsyncVariants::array_reduce($mergedPlots, function(SinglePlot $a, SinglePlot $b){
				return (yield $this->generatePlotPosition($a, false))->x > (yield $this->generatePlotPosition($b, false))->x ? $a : $b;
			}),
			false
			))->x + $plotSize;
		$minz = (yield $this->generatePlotPosition(
			yield AsyncVariants::array_reduce($mergedPlots, function(SinglePlot $a, SinglePlot $b){
				return (yield $this->generatePlotPosition($a, false))->z < (yield $this->generatePlotPosition($b, false))->z ? $a : $b;
			}),
			false
		))->z;
		$maxz = (yield $this->generatePlotPosition(
				yield AsyncVariants::array_reduce($mergedPlots, function(SinglePlot $a, SinglePlot $b){
					return (yield $this->generatePlotPosition($a, false))->z > (yield $this->generatePlotPosition($b, false))->z ? $a : $b;
				}),
				false
			))->z + $plotSize;
		return new Position(($minx + $maxx) / 2, $plotLevel->groundHeight, ($minz + $maxz) / 2, $this->plugin->getServer()->getWorldManager()->getWorldByName($plot->levelName));
	}

	/**
	 * @param SinglePlot    $plot
	 * @param string        $claimer
	 * @param string        $plotName
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function claimPlot(SinglePlot $plot, string $claimer, string $plotName, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateClaimPlot($plot, $claimer, $plotName),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateClaimPlot(SinglePlot $plot, string $claimer, string $plotName) : \Generator{
		$newPlot = clone $plot;
		$newPlot->owner = $claimer;
		$newPlot->helpers = [];
		$newPlot->denied = [];
		if($plotName !== "")
			$newPlot->name = $plotName;
		$newPlot->price = 0;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled()){
			return false;
		}
		return yield $this->generatePlotsToSave($ev->getPlot());
	}

	/**
	 * @param SinglePlot    $plot
	 * @param string        $newName
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function renamePlot(SinglePlot $plot, string $newName, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateRenamePlot($plot, $newName),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateRenamePlot(SinglePlot $plot, string $newName) : \Generator{
		$newPlot = clone $plot;
		$newPlot->name = $newName;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled()){
			return false;
		}
		return yield $this->generatePlotsToSave($ev->getPlot());
	}

	/**
	 * @param SinglePlot    $plotFrom
	 * @param SinglePlot    $plotTo
	 * @param WorldStyler   $styler
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function clonePlot(SinglePlot $plotFrom, SinglePlot $plotTo, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateClonePlot($plotFrom, $plotTo),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateClonePlot(SinglePlot $plotFrom, SinglePlot $plotTo) : \Generator{
		$styler = $this->plugin->getServer()->getPluginManager()->getPlugin("WorldStyler");
		if(!$styler instanceof WorldStyler){
			return false;
		}
		$ev = new MyPlotCloneEvent($plotFrom, $plotTo);
		$ev->call();
		if($ev->isCancelled())
			return false;

		$plotFrom = $ev->getPlot();
		$plotTo = $ev->getClonePlot();
		if($this->getLevelSettings($plotFrom->levelName) === null or $this->getLevelSettings($plotTo->levelName) === null){
			return false;
		}

		$world = $this->plugin->getServer()->getWorldManager()->getWorldByName($plotTo->levelName);
		$aabb = yield $this->generatePlotBB($plotTo);
		foreach($world->getEntities() as $entity){
			if($aabb->isVectorInXZ($entity->getPosition())){
				if($entity instanceof Player){
					yield $this->generatePlayerTeleport($entity, $plotTo, false);
				}
			}
		}
		$plotLevel = $this->getLevelSettings($plotFrom->levelName);
		$plotSize = $plotLevel->plotSize - 1;
		$plotBeginPos = yield $this->generatePlotPosition($plotFrom, true);
		$level = $plotBeginPos->getWorld();
		$plotBeginPos = $plotBeginPos->subtract(1, 0, 1);
		$plotBeginPos->y = 0;
		$xMax = $plotBeginPos->x + $plotSize;
		$zMax = $plotBeginPos->z + $plotSize;
		foreach(yield $this->dataProvider->getMergedPlots($plotFrom) as $mergedPlot){
			$pos = (yield $this->generatePlotPosition($mergedPlot, false))->subtract(1, 0, 1);
			$xMaxPlot = $pos->x + $plotSize;
			$zMaxPlot = $pos->z + $plotSize;
			if($plotBeginPos->x > $pos->x) $plotBeginPos->x = $pos->x;
			if($plotBeginPos->z > $pos->z) $plotBeginPos->z = $pos->z;
			if($xMax < $xMaxPlot) $xMax = $xMaxPlot;
			if($zMax < $zMaxPlot) $zMax = $zMaxPlot;
		}
		$selection = $styler->getSelection(99997) ?? new Selection(99997);
		$selection->setPosition(1, $plotBeginPos);
		$vec2 = new Vector3($xMax + 1, $level->getMaxY() - 1, $zMax + 1);
		$selection->setPosition(2, $vec2);
		$cuboid = Cuboid::fromSelection($selection);
		//$cuboid = $cuboid->async(); // do not use async because WorldStyler async is very broken right now
		$cuboid->copy($level, $vec2, function(float $time, int $changed) : void{
			$this->plugin->getLogger()->debug(TF::GREEN . 'Copied ' . number_format($changed) . ' blocks in ' . number_format($time, 10) . 's to the MyPlot clipboard.');
		});

		$plotLevel = $this->getLevelSettings($plotTo->levelName);
		$plotSize = $plotLevel->plotSize - 1;
		$plotBeginPos = yield $this->generatePlotPosition($plotTo, true);
		$level = $plotBeginPos->getWorld();
		$plotBeginPos = $plotBeginPos->subtract(1, 0, 1);
		$plotBeginPos->y = 0;
		$xMax = $plotBeginPos->x + $plotSize;
		$zMax = $plotBeginPos->z + $plotSize;
		foreach(yield $this->dataProvider->getMergedPlots($plotTo) as $mergedPlot){
			$pos = (yield $this->generatePlotPosition($mergedPlot, false))->subtract(1, 0, 1);
			$xMaxPlot = $pos->x + $plotSize;
			$zMaxPlot = $pos->z + $plotSize;
			if($plotBeginPos->x > $pos->x) $plotBeginPos->x = $pos->x;
			if($plotBeginPos->z > $pos->z) $plotBeginPos->z = $pos->z;
			if($xMax < $xMaxPlot) $xMax = $xMaxPlot;
			if($zMax < $zMaxPlot) $zMax = $zMaxPlot;
		}
		$selection->setPosition(1, $plotBeginPos);
		$vec2 = new Vector3($xMax + 1, $level->getMaxY() - 1, $zMax + 1);
		$selection->setPosition(2, $vec2);
		$commonShape = CommonShape::fromSelection($selection);
		//$commonShape = $commonShape->async(); // do not use async because WorldStyler async is very broken right now
		$commonShape->paste($level, $vec2, true, function(float $time, int $changed) : void{
			$this->plugin->getLogger()->debug(TF::GREEN . 'Pasted ' . number_format($changed) . ' blocks in ' . number_format($time, 10) . 's from the MyPlot clipboard.');
		});
		$styler->removeSelection(99997);
		foreach((yield $this->generatePlotChunks($plotTo)) as [$chunkX, $chunkZ, $chunk]){
			$level->setChunk($chunkX, $chunkZ, $chunk);
		}
		return true;
	}

	/**
	 * @param SinglePlot    $plot
	 * @param int           $maxBlocksPerTick
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null    $catches
	 */
	public function clearPlot(BasePlot $plot, int $maxBlocksPerTick, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateClearPlot($plot, $maxBlocksPerTick),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateClearPlot(BasePlot $plot, int $maxBlocksPerTick) : \Generator{
		$ev = new MyPlotClearEvent($plot, $maxBlocksPerTick);
		$ev->call();
		if($ev->isCancelled()){
			return false;
		}
		$plot = $ev->getPlot();
		if($this->getLevelSettings($plot->levelName) === null){
			return false;
		}
		$maxBlocksPerTick = $ev->getMaxBlocksPerTick();

		$level = $this->plugin->getServer()->getWorldManager()->getWorldByName($plot->levelName);
		if($level === null){
			return false;
		}
		foreach($level->getEntities() as $entity){
			if((yield $this->generatePlotBB($plot))->isVectorInXZ($entity->getPosition())){
				if(!$entity instanceof Player){
					$entity->flagForDespawn();
				}else{
					$this->generatePlayerTeleport($entity, $plot, false);
				}
			}
		}
		$styler = $this->plugin->getServer()->getPluginManager()->getPlugin("WorldStyler");
		if($this->plugin->getConfig()->get("FastClearing", false) === true && $styler instanceof WorldStyler){
			$plotLevel = $this->getLevelSettings($plot->levelName);
			$plotSize = $plotLevel->plotSize - 1;
			$plotBeginPos = yield $this->generatePlotPosition($plot, true);
			$xMax = $plotBeginPos->x + $plotSize;
			$zMax = $plotBeginPos->z + $plotSize;
			foreach(yield $this->dataProvider->getMergedPlots($plot) as $mergedPlot){
				$xplot = (yield $this->generatePlotPosition($mergedPlot, false))->x;
				$zplot = (yield $this->generatePlotPosition($mergedPlot, false))->z;
				$xMaxPlot = (int) ($xplot + $plotSize);
				$zMaxPlot = (int) ($zplot + $plotSize);
				if($plotBeginPos->x > $xplot) $plotBeginPos->x = $xplot;
				if($plotBeginPos->z > $zplot) $plotBeginPos->z = $zplot;
				if($xMax < $xMaxPlot) $xMax = $xMaxPlot;
				if($zMax < $zMaxPlot) $zMax = $zMaxPlot;
			}
			// Above ground
			$selection = $styler->getSelection(99998) ?? new Selection(99998);
			$plotBeginPos->y = $plotLevel->groundHeight + 1;
			$selection->setPosition(1, $plotBeginPos);
			$selection->setPosition(2, new Vector3($xMax, World::Y_MAX, $zMax));
			$cuboid = Cuboid::fromSelection($selection);
			//$cuboid = $cuboid->async();
			$cuboid->set($plotBeginPos->getWorld(), VanillaBlocks::AIR()->getFullId(), function(float $time, int $changed) : void{
				$this->plugin->getLogger()->debug('Set ' . number_format($changed) . ' blocks in ' . number_format($time, 10) . 's');
			});
			$styler->removeSelection(99998);
			// Ground Surface
			$selection = $styler->getSelection(99998) ?? new Selection(99998);
			$plotBeginPos->y = $plotLevel->groundHeight;
			$selection->setPosition(1, $plotBeginPos);
			$selection->setPosition(2, new Vector3($xMax, $plotLevel->groundHeight, $zMax));
			$cuboid = Cuboid::fromSelection($selection);
			//$cuboid = $cuboid->async();
			$cuboid->set($plotBeginPos->getWorld(), $plotLevel->plotFloorBlock->getFullId(), function(float $time, int $changed) : void{
				$this->plugin->getLogger()->debug('Set ' . number_format($changed) . ' blocks in ' . number_format($time, 10) . 's');
			});
			$styler->removeSelection(99998);
			// Ground
			$selection = $styler->getSelection(99998) ?? new Selection(99998);
			$plotBeginPos->y = 1;
			$selection->setPosition(1, $plotBeginPos);
			$selection->setPosition(2, new Vector3($xMax, $plotLevel->groundHeight - 1, $zMax));
			$cuboid = Cuboid::fromSelection($selection);
			//$cuboid = $cuboid->async();
			$cuboid->set($plotBeginPos->getWorld(), $plotLevel->plotFillBlock->getFullId(), function(float $time, int $changed) : void{
				$this->plugin->getLogger()->debug('Set ' . number_format($changed) . ' blocks in ' . number_format($time, 10) . 's');
			});
			$styler->removeSelection(99998);
			// Bottom of world
			$selection = $styler->getSelection(99998) ?? new Selection(99998);
			$plotBeginPos->y = 0;
			$selection->setPosition(1, $plotBeginPos);
			$selection->setPosition(2, new Vector3($xMax, 0, $zMax));
			$cuboid = Cuboid::fromSelection($selection);
			//$cuboid = $cuboid->async();
			$cuboid->set($plotBeginPos->getWorld(), $plotLevel->bottomBlock->getFullId(), function(float $time, int $changed) : void{
				$this->plugin->getLogger()->debug('Set ' . number_format($changed) . ' blocks in ' . number_format($time, 10) . 's');
			});
			$styler->removeSelection(99998);
			foreach($this->plugin->getPlotChunks($plot) as [$chunkX, $chunkZ, $chunk]){
				$plotBeginPos->getWorld()->setChunk($chunkX, $chunkZ, $chunk);
			}
			$this->plugin->getScheduler()->scheduleDelayedTask(new ClearBorderTask($this->plugin, $plot), 1);
			return true;
		}
		$this->plugin->getScheduler()->scheduleTask(new ClearPlotTask($this->plugin, $plot, $maxBlocksPerTick));
		return true;
	}

	/**
	 * @param SinglePlot    $plot
	 * @param Block         $plotFillBlock
	 * @param int           $maxBlocksPerTick
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null    $catches
	 */
	public function fillPlot(BasePlot $plot, Block $plotFillBlock, int $maxBlocksPerTick, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateFillPlot($plot, $plotFillBlock, $maxBlocksPerTick),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateFillPlot(BasePlot $plot, Block $plotFillBlock, int $maxBlocksPerTick) : \Generator{
		$ev = new MyPlotFillEvent($plot, $maxBlocksPerTick);
		$ev->call();
		if($ev->isCancelled()){
			return false;
		}
		$plot = $ev->getPlot();
		if($this->getLevelSettings($plot->levelName) === null){
			return false;
		}
		$maxBlocksPerTick = $ev->getMaxBlocksPerTick();

		foreach($this->plugin->getServer()->getWorldManager()->getWorldByName($plot->levelName)->getEntities() as $entity){
			if((yield $this->generatePlotBB($plot))->isVectorInXZ($entity->getPosition()) && $entity->getPosition()->y <= $this->getLevelSettings($plot->levelName)->groundHeight){
				if(!$entity instanceof Player){
					$entity->flagForDespawn();
				}else{
					$this->generatePlayerTeleport($entity, $plot, false);
				}
			}
		}
		if($this->plugin->getConfig()->get("FastFilling", false) === true){
			$styler = $this->plugin->getServer()->getPluginManager()->getPlugin("WorldStyler");
			if(!$styler instanceof WorldStyler){
				return false;
			}
			$plotLevel = $this->getLevelSettings($plot->levelName);
			$plotSize = $plotLevel->plotSize - 1;
			$plotBeginPos = yield $this->generatePlotPosition($plot, false);
			// Ground
			$selection = $styler->getSelection(99998);
			$plotBeginPos->y = 1;
			$selection->setPosition(1, $plotBeginPos);
			$selection->setPosition(2, new Vector3($plotBeginPos->x + $plotSize, $plotLevel->groundHeight, $plotBeginPos->z + $plotSize));
			$cuboid = Cuboid::fromSelection($selection);
			//$cuboid = $cuboid->async();
			$cuboid->set($plotBeginPos->getWorld(), $plotFillBlock->getFullId(), function(float $time, int $changed) : void{
				$this->plugin->getLogger()->debug('Set ' . number_format($changed) . ' blocks in ' . number_format($time, 10) . 's');
			});
			$styler->removeSelection(99998);
			// Bottom of world
			$selection = $styler->getSelection(99998);
			$plotBeginPos->y = 0;
			$selection->setPosition(1, $plotBeginPos);
			$selection->setPosition(2, new Vector3($plotBeginPos->x + $plotSize, 0, $plotBeginPos->z + $plotSize));
			$cuboid = Cuboid::fromSelection($selection);
			//$cuboid = $cuboid->async();
			$cuboid->set($plotBeginPos->getWorld(), $plotLevel->bottomBlock->getFullId(), function(float $time, int $changed) : void{
				$this->plugin->getLogger()->debug('Set ' . number_format($changed) . ' blocks in ' . number_format($time, 10) . 's');
			});
			$styler->removeSelection(99998);
			foreach((yield $this->generatePlotChunks($plot)) as [$chunkX, $chunkZ, $chunk]){
				$plotBeginPos->getWorld()?->setChunk($chunkX, $chunkZ, $chunk);
			}
			return true;
		}
		$this->plugin->getScheduler()->scheduleTask(new FillPlotTask($this->plugin, $plot, $plotFillBlock, $maxBlocksPerTick));
		return true;
	}

	/**
	 * @param SinglePlot    $plot
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null    $catches
	 */
	public function disposePlot(BasePlot $plot, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateDisposePlot($plot),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateDisposePlot(BasePlot $plot) : \Generator{
		$ev = new MyPlotDisposeEvent($plot);
		$ev->call();
		if($ev->isCancelled())
			return false;
		$plot = $ev->getPlot();
		return yield $this->dataProvider->deletePlot($plot);
	}

	/**
	 * @param SinglePlot    $plot
	 * @param int           $maxBlocksPerTick
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function resetPlot(SinglePlot $plot, int $maxBlocksPerTick, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateResetPlot($plot, $maxBlocksPerTick),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateResetPlot(SinglePlot $plot, int $maxBlocksPerTick) : \Generator{
		$ev = new MyPlotResetEvent($plot);
		$ev->call();
		if($ev->isCancelled())
			return false;
		$plot = $ev->getPlot();
		if(!yield $this->generateDisposePlot($plot)){
			return false;
		}
		if(!yield $this->generateClearPlot($plot, $maxBlocksPerTick)){
			yield $this->generatePlotsToSave($plot);
			return false;
		}
		return true;
	}

	/**
	 * @param SinglePlot    $plot
	 * @param Biome         $biome
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null    $catches
	 */
	public function setPlotBiome(SinglePlot $plot, Biome $biome, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generatePlotBiome($plot, $biome),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generatePlotBiome(SinglePlot $plot, Biome $biome) : \Generator{
		$newPlot = clone $plot;
		$newPlot->biome = str_replace(" ", "_", strtoupper($biome->getName()));
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled())
			return false;
		$plot = $ev->getPlot();
		if(defined(BiomeIds::class . "::" . $plot->biome) and is_int(constant(BiomeIds::class . "::" . $plot->biome))){
			$biome = constant(BiomeIds::class . "::" . $plot->biome);
		}else{
			$biome = BiomeIds::PLAINS;
		}
		$biome = BiomeRegistry::getInstance()->getBiome($biome);
		if($this->getLevelSettings($plot->levelName) === null){
			return false;
		}

		$failed = false;
		foreach(yield $this->dataProvider->getMergedPlots($plot) as $merged){
			$merged->biome = $plot->biome;
			if(!yield $this->dataProvider->savePlot($merged))
				$failed = true;
		}
		$plotLevel = $this->getLevelSettings($plot->levelName);
		$level = $this->plugin->getServer()->getWorldManager()->getWorldByName($plot->levelName);
		if($level === null){
			return false;
		}
		foreach((yield $this->generatePlotChunks($plot)) as [$chunkX, $chunkZ, $chunk]){
			for($x = 0; $x < 16; ++$x){
				for($z = 0; $z < 16; ++$z){
					$pos = new Position(($chunkX << 4) + $x, $plotLevel->groundHeight, ($chunkZ << 4) + $z, $level);
					$chunkPlot = $this->getPlotFast($pos->x, $pos->z, $plotLevel);
					if($chunkPlot instanceof SinglePlot and $chunkPlot->isSame($plot)){
						$chunk->setBiomeId($x, $z, $biome->getId());
					}
				}
			}
			$level->setChunk($chunkX, $chunkZ, $chunk);
		}
		return !$failed;
	}

	/**
	 * @param SinglePlot    $plot
	 * @param bool          $pvp
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function setPlotPvp(SinglePlot $plot, bool $pvp, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generatePlotPvp($plot, $pvp),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generatePlotPvp(SinglePlot $plot, bool $pvp) : \Generator{
		$newPlot = clone $plot;
		$newPlot->pvp = $pvp;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled())
			return false;
		$plot = $ev->getPlot();
		return yield $this->dataProvider->savePlot($plot);
	}

	/**
	 * @param SinglePlot    $plot
	 * @param string        $player
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function addPlotHelper(SinglePlot $plot, string $player, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateAddPlotHelper($plot, $player),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateAddPlotHelper(SinglePlot $plot, string $player) : \Generator{
		$newPlot = clone $plot;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$newPlot->addHelper($player) ? $ev->uncancel() : $ev->cancel();
		$ev->call();
		if($ev->isCancelled())
			return false;
		$plot = $ev->getPlot();
		return yield $this->dataProvider->savePlot($plot);
	}

	/**
	 * @param SinglePlot    $plot
	 * @param string        $player
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function removePlotHelper(SinglePlot $plot, string $player, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateRemovePlotHelper($plot, $player),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateRemovePlotHelper(SinglePlot $plot, string $player) : \Generator{
		$newPlot = clone $plot;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$newPlot->removeHelper($player) ? $ev->uncancel() : $ev->cancel();
		$ev->call();
		if($ev->isCancelled())
			return false;
		$plot = $ev->getPlot();
		return yield $this->dataProvider->savePlot($plot);
	}

	/**
	 * @param SinglePlot    $plot
	 * @param string        $player
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function addPlotDenied(SinglePlot $plot, string $player, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateAddPlotDenied($plot, $player),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateAddPlotDenied(SinglePlot $plot, string $player) : \Generator{
		$newPlot = clone $plot;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$newPlot->denyPlayer($player) ? $ev->uncancel() : $ev->cancel();
		$ev->call();
		if($ev->isCancelled())
			return false;
		$plot = $ev->getPlot();
		return yield $this->dataProvider->savePlot($plot);
	}

	/**
	 * @param SinglePlot    $plot
	 * @param string        $player
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function removePlotDenied(SinglePlot $plot, string $player, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateRemovePlotDenied($plot, $player),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateRemovePlotDenied(SinglePlot $plot, string $player) : \Generator{
		$newPlot = clone $plot;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$newPlot->unDenyPlayer($player) ? $ev->uncancel() : $ev->cancel();
		$ev->call();
		if($ev->isCancelled())
			return false;
		$plot = $ev->getPlot();
		return yield $this->dataProvider->savePlot($plot);
	}

	/**
	 * @param SinglePlot    $plot
	 * @param int           $price
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function sellPlot(SinglePlot $plot, int $price, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateSellPlot($plot, $price),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateSellPlot(SinglePlot $plot, int $price) : \Generator{
		if($this->economyProvider === null or $price <= 0){
			return false;
		}

		$newPlot = clone $plot;
		$newPlot->price = $price;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled())
			return false;
		$plot = $ev->getPlot();
		return yield $this->dataProvider->savePlot($plot);
	}

	/**
	 * @param SinglePlot    $plot
	 * @param Player        $player
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(bool): void)|null       $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null $catches
	 */
	public function buyPlot(SinglePlot $plot, Player $player, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generateBuyPlot($plot, $player),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generateBuyPlot(SinglePlot $plot, Player $player) : \Generator{
		if($this->economyProvider === null){
			return false;
		}
		if(!yield $this->economyProvider->reduceMoney($player, $plot->price)){
			return false;
		}
		if(!yield $this->economyProvider->addMoney($this->plugin->getServer()->getOfflinePlayer($plot->owner), $plot->price)){
			yield $this->economyProvider->addMoney($player, $plot->price);
			return false;
		}

		return yield $this->generateClaimPlot($plot, $player->getName(), '');
	}

	/**
	 * @param SinglePlot    $plot
	 * @param callable|null $onComplete
	 * @phpstan-param (callable(array<int, Chunk>): void)|null $onComplete
	 * @param callable|null $onFail
	 * @phpstan-param (callable(\Throwable): void)|null        $catches
	 */
	public function getPlotChunks(SinglePlot $plot, ?callable $onComplete = null, ?callable $onFail = null) : void{
		Await::g2c(
			$this->generatePlotChunks($plot),
			$onComplete,
			$onFail === null ? [] : [$onFail]
		);
	}

	public function generatePlotChunks(BasePlot $plot) : \Generator{
		$plotLevel = $this->getLevelSettings($plot->levelName);
		if($plotLevel === null){
			return [];
		}
		$level = $this->plugin->getServer()->getWorldManager()->getWorldByName($plot->levelName);
		if($level === null){
			return [];
		}
		$plotSize = $plotLevel->plotSize;
		$chunks = [];
		foreach((yield $this->dataProvider->getMergedPlots($plot)) as $mergedPlot){
			$pos = yield $this->generatePlotPosition($mergedPlot, false);
			$xMax = ($pos->x + $plotSize) >> 4;
			$zMax = ($pos->z + $plotSize) >> 4;
			for($x = $pos->x >> 4; $x <= $xMax; $x++){
				for($z = $pos->z >> 4; $z <= $zMax; $z++){
					$chunks[] = [$x, $z, $level->getChunk($x, $z)];
				}
			}
		}
		return $chunks;
	}

	public function onDisable() : void {
		$this->dataProvider->close();
	}
}