<?php
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\utils\Config;

class jsonDataProvider extends DataProvider{
    /** @var Plot[] */
    private $cache = [];
    /** @var int */
    private $cacheSize;
    /** @var MyPlot */
    protected $plugin;
    /** @var Config $json */
    private $json;

    public function __construct(MyPlot $plugin, $cacheSize = 0) {
        parent::__construct($plugin, $cacheSize);
        $this->plugin = $plugin;
        $this->cacheSize = $cacheSize;
        $this->json = new Config($this->plugin->getDataFolder()."plots.json", Config::JSON, []);
    }
    /**
     * @param Plot $plot
     * @return bool
     */
    public function savePlot(Plot $plot){
      $this->json->set("");
    }
    /**
     * @param Plot $plot
     * @return bool
     */
    public function deletePlot(Plot $plot){
      $this->json->set("");
    }
    /**
     * @param string $levelName
     * @param int $X
     * @param int $Z
     * @return Plot
     */
    public function getPlot($levelName, $X, $Z){
      $this->json->getNested("");
    }
    /**
     * @param string $owner
     * @param string $levelName
     * @return Plot[]
     */
    public function getPlotsByOwner($owner, $levelName = ""){
      $this->json->get("");
    }
    /**
     * @param string $levelName
     * @param int $limitXZ
     * @return Plot|null
     */
    public function getNextFreePlot($levelName, $limitXZ = 0){
      $this->json->get("");
    }
    public function close(){
      unset($this->json);
    }
}
