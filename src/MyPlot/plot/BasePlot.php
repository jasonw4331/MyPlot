<?php
declare(strict_types=1);
namespace MyPlot\plot;

use pocketmine\math\Facing;

class BasePlot {
	public function __construct(public string $levelName, public int $X, public int $Z) {}

	public function isSame(BasePlot $plot) : bool {
		return $this->levelName === $plot->levelName and $this->X === $plot->X and $this->Z === $plot->Z;
	}

	public function getSide(int $side, int $step = 1) : BasePlot {
		return match($side) {
			Facing::NORTH => new BasePlot($this->levelName, $this->X, $this->Z - $step),
			Facing::SOUTH => new BasePlot($this->levelName, $this->X, $this->Z + $step),
			Facing::EAST => new BasePlot($this->levelName, $this->X + $step, $this->Z),
			Facing::WEST => new BasePlot($this->levelName, $this->X - $step, $this->Z),
			default => throw new \InvalidArgumentException("Invalid side: ".Facing::toString($side))
		};
	}

	public function __toString() : string {
		return "(" . $this->X . ";" . $this->Z . ")";
	}
}