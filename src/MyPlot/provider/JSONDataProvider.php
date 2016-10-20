<?php
namespace MyPlot\provider;
use MyPlot\MyPlot;
use MyPlot\Plot;
class JSONDataProvider extends DataProvider{
    /** @var Plot[] */
    private $cache = [];
    /** @var int */
    private $cacheSize;
    /** @var MyPlot */
    protected $plugin;
    public function __construct(MyPlot $plugin, $cacheSize = 0) {
        $this->plugin = $plugin;
        $this->cacheSize = $cacheSize;
    }
    /**
     * @param Plot $plot
     * @return bool
     */
    public function savePlot(Plot $plot){
      //filler
    }
    /**
     * @param Plot $plot
     * @return bool
     */
    public function deletePlot(Plot $plot){
      //filler
    }
    /**
     * @param string $levelName
     * @param int $X
     * @param int $Z
     * @return Plot
     */
    public function getPlot($levelName, $X, $Z){
      //filler
    }
    /**
     * @param string $owner
     * @param string $levelName
     * @return Plot[]
     */
    public function getPlotsByOwner($owner, $levelName = {
      //filler
    }
    /**
     * @param string $levelName
     * @param int $limitXZ
     * @return Plot|null
     */
    public function getNextFreePlot($levelName, $limitXZ = 0){
      //filler
    }
    public function close(){
      //filler
    }
}
