<?php
namespace MyPlot\provider;

use MyPlot\MyPlot;
use MyPlot\Plot;

abstract class DataProvider
{
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

    protected final function cachePlot(Plot $plot) {
        if ($this->cacheSize > 0) {
            $key = $plot->levelName . ';' . $plot->X . ';' . $plot->Z;
            if (isset($this->cache[$key])) {
                unset($this->cache[$key]);
            } elseif($this->cacheSize <= count($this->cache)) {
                array_pop($this->cache);
            }
            $this->cache = array_merge(array($key => clone $plot), $this->cache);
        }
    }

    protected final function getPlotFromCache($levelName, $X, $Z) {
        if ($this->cacheSize > 0) {
            $key = $levelName . ';' . $X . ';' . $Z;
            if (isset($this->cache[$key])) {
                return $this->cache[$key];
            }
        }
        return null;
    }

    /**
     * @param Plot $plot
     * @return bool
     */
    public abstract function savePlot(Plot $plot);

    /**
     * @param Plot $plot
     * @return bool
     */
    public abstract function deletePlot(Plot $plot);

    /**
     * @param string $levelName
     * @param int $X
     * @param int $Z
     * @return Plot
     */
    public abstract function getPlot($levelName, $X, $Z);

    /**
     * @param string $owner
     * @param string $levelName
     * @return Plot[]
     */
    public abstract function getPlotsByOwner($owner, $levelName = "");

    /**
     * @param string $levelName
     * @param int $limitXZ
     * @return Plot|null
     */
    public abstract function getNextFreePlot($levelName, $limitXZ = 0);

    public abstract function close();
}