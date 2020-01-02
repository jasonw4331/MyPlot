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
	 *
	 * @return self
	 */
	public function setLevelName(string $levelName) : self {
		$this->levelName = $levelName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getGenerator() : string {
		return $this->generator;
	}

	/**
	 * @param string $generator
	 *
	 * @return self
	 */
	public function setGenerator(string $generator) : self {
		$this->generator = $generator;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getSettings() : array {
		return $this->settings;
	}

	/**
	 * @param array $settings
	 *
	 * @return self
	 */
	public function setSettings(array $settings) : self {
		$this->settings = $settings;
		$this->settings["preset"] = json_encode($settings);
		return $this;
	}
}