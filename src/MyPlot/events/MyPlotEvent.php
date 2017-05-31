<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;

class MyPlotEvent extends PluginEvent implements Cancellable {
	/** @var string $issuer */
	private $issuer;
	public function __construct(MyPlot $plugin, string $issuer) {
		$this->issuer = $issuer;
		parent::__construct($plugin);
	}
	public function getIssuer() : string {
		return $this->issuer;
	}
}