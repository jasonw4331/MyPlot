<?php

namespace MyPlot\events;

use MyPlot\Plot;

class MyPlotOwnerChangeEvent extends MyPlotPlotEvent {

    /** @var string */
    private $previousOwner;
    /** @var string */
    private $newOwner;

    public function __construct(Plot $plot, string $previousOwner, $newOwner) {
        $this->previousOwner = $previousOwner;
        $this->newOwner = $newOwner;

        parent::__construct($plot);
    }

    public function getPreviousOwner(): string {
        return $this->previousOwner;
    }

    public function getNewOwner(): string {
        return $this->newOwner;
    }
}