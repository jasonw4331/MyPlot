<?php
namespace MyPlot\provider;

use MyPlot\Plot;

interface DataProvider
{
    public function close();

    /**
     * @param Plot $plot
     * @return bool
     */
    public function savePlot(Plot $plot);

    /**
     * @param Plot $plot
     * @return bool
     */
    public function deletePlot(Plot $plot);

    /**
     * @param string $levelName
     * @param int $X
     * @param int $Z
     * @return Plot|null
     */
    public function getPlot($levelName, $X, $Z);
}