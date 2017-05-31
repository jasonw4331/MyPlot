<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;

class MyPlotEvent extends PluginEvent implements Cancellable {
	private $issuer;
	
	public function __construct(MyPlot $plugin, $issuer) {
		$this->issuer = $issuer;
		parent::__construct($plugin);
	}
	
	public function getIssuer() {
		return $this->issuer;
	}
}