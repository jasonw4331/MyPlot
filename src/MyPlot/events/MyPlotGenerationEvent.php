<?php
declare(strict_types=1);
namespace MyPlot\events;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;

class MyPlotGenerationEvent extends Event implements Cancellable {
	/** @var string $levelName */
	private $levelName;
	/** @var string $generator */
	private $generator = "myplot";
	/** @var array $settings */
	private $settings = [];

	public function __construct(string $levelName, string $generator = "myplot", array $settings = []) {
		$this->levelName = $levelName;
		$this->generator = $generator;
		$this->settings = $settings;
	}

	/**
	 * @return string
	 */
	public function getLevelName() : string {
		return $this->levelName;
	}

	/**
	 * @param string $levelName
	 */
	public function setLevelName(string $levelName) : void {
		$this->levelName = $levelName;
	}

	/**
	 * @return string
	 */
	public function getGenerator() : string {
		return $this->generator;
	}

	/**
	 * @param string $generator
	 */
	public function setGenerator(string $generator) : void {
		$this->generator = $generator;
	}

	/**
	 * @return array
	 */
	public function getSettings() : array {
		return $this->settings;
	}

	/**
	 * @param array $settings
	 */
	public function setSettings(array $settings) : void {
		$this->settings = $settings;
		$this->settings["preset"] = json_encode($settings);
	}
}