<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\plot\BasePlot;
use MyPlot\plot\MergedPlot;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class MyPlotMergeEvent extends MyPlotPlotEvent implements Cancellable{
	use CancellableTrait;

	/** @var BasePlot[] $toMerge */
	private array $toMerge;
	private int $direction;


	/**
	 * MyPlotMergeEvent constructor.
	 *
	 * @param MergedPlot $plot
	 * @param int        $direction
	 * @param BasePlot[] $toMerge
	 */
	public function __construct(MergedPlot $plot, int $direction, array $toMerge){
		parent::__construct($plot);
		$this->direction = $direction;
		$this->toMerge = $toMerge;
	}

	/**
	 * @return BasePlot[]
	 */
	public function getToMergePairs() : array{
		return $this->toMerge;
	}


}