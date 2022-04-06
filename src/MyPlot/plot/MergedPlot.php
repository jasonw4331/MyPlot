<?php
declare(strict_types=1);
namespace MyPlot\plot;

use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

class MergedPlot extends SinglePlot{

	public function __construct(
		string $levelName,
		int $X, // Must always be lowest X coordinate in merge array
		int $Z, // Must always be lowest Z coordinate in merge array
		public int $xWidth,
		public int $zWidth,
		string $name = "",
		string $owner = "",
		array $helpers = [],
		array $denied = [],
		string $biome = "PLAINS",
		?bool $pvp = null,
		int $price = -1
	){
		if($xWidth <= 0 and $zWidth <= 0){
			throw new \InvalidArgumentException("Plot merge width must be greater than 0");
		}
		parent::__construct($levelName, $X, $Z, $name, $owner, $helpers, $denied, $biome, $pvp, $price);
	}

	public static function fromSingle(SinglePlot $plot, int $xWidth, int $zWidth) : MergedPlot{
		return new MergedPlot(
			$plot->levelName,
			$plot->X,
			$plot->Z,
			$xWidth,
			$zWidth,
			$plot->name,
			$plot->owner,
			$plot->helpers,
			$plot->denied,
			$plot->biome,
			$plot->pvp,
			$plot->price
		);
	}

	/**
	 * TODO: description
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 *
	 * @return bool
	 */
	public function isSame(BasePlot $plot) : bool{
		if(parent::isSame($plot))
			return true;

		return (
		new AxisAlignedBB(
			$this->X,
			0,
			$this->Z,
			$this->X + $this->xWidth,
			1,
			$this->Z + $this->zWidth
		)
		)->isVectorInXZ(new Vector3($plot->X, 0, $plot->Z));
	}

	public function getSide(int $side, int $step = 1) : BasePlot{
		$axis = Facing::axis($side);
		$isPositive = Facing::isPositive($side);
		if($axis === Axis::X){
			if($isPositive){
				return parent::getSide($side, $step + $this->xWidth);
			}
			return parent::getSide($side, $step);
		}elseif($axis === Axis::Z){
			if($isPositive){
				return parent::getSide($side, $step + $this->zWidth);
			}
			return parent::getSide($side, $step);
		}
		throw new \InvalidArgumentException("Invalid Axis " . $axis);
	}
}