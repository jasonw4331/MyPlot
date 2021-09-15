<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;

class MyPlotMergeEvent extends MyPlotPlotEvent implements Cancellable {

    /** @var Plot[][] $toMerge */
    private array $toMerge;


    /**
     * MyPlotMergeEvent constructor.
     * @param Plot $plot
     * @param Plot[][] $toMerge
     */
    public function __construct(Plot $plot, array $toMerge) {
        $this->toMerge = $toMerge;
		parent::__construct($plot);
	}

    /**
     * @return Plot[][]
     */
    public function getToMergePairs() : array {
        return $this->toMerge;
    }


}