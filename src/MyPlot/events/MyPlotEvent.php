<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use pocketmine\event\plugin\PluginEvent;

abstract class MyPlotEvent extends PluginEvent {
	/** @var string $issuer */
	protected $issuer;

	/**
	 * MyPlotEvent constructor.
	 *
	 * @param MyPlot $plugin
	 * @param string $issuer
	 */
	public function __construct(MyPlot $plugin, string $issuer) {
		$this->issuer = $issuer;
		parent::__construct($plugin);
	}

	/**
	 * @return string
	 */
	public function getIssuer() : string {
		return $this->issuer;
	}
}