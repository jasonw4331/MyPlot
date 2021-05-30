<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class MyPlotMergeEvent extends MyPlotPlotEvent implements Cancellable {
	use CancellableTrait;

    /** @var Plot[][] $toMerge */
    private $toMerge;


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