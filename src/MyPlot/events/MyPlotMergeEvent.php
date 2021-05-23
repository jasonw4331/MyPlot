<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;

class MyPlotMergeEvent extends MyPlotPlotEvent implements Cancellable {

    /** @var array $toMerge */
    private $toMerge;


    /**
     * MyPlotMergeEvent constructor.
     * @param Plot $plot
     * @param array $toMerge
     */
    public function __construct(Plot $plot, array $toMerge) {
        $this->toMerge = $toMerge;
		parent::__construct($plot);
	}

    /**
     * @return array
     */
    public function getToMergePairs() : array {
        return $this->toMerge;
    }


}