<?php
declare(strict_types=1);
namespace MyPlot\events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class MyPlotGenerationEvent extends Event implements Cancellable {
	use CancellableTrait;

	/** @var string $levelName */
	private $levelName;
	/** @var string $generator */
	private $generator = "myplot";
	/** @var mixed[] $settings */
	private $settings = [];

	/**
	 * MyPlotGenerationEvent constructor.
	 *
	 * @param string $levelName
	 * @param string $generator
	 * @param mixed[] $settings
	 */
	public function __construct(string $levelName, string $generator = "myplot", array $settings = []) {
		$this->levelName = $levelName;
		$this->generator = $generator;
		$this->settings = $settings;
	}

	public function getLevelName() : string {
		return $this->levelName;
	}

	public function setLevelName(string $levelName) : self {
		$this->levelName = $levelName;
		return $this;
	}

	public function getGenerator() : string {
		return $this->generator;
	}

	public function setGenerator(string $generator) : self {
		$this->generator = $generator;
		return $this;
	}

	/**
	 * @return mixed[]
	 */
	public function getSettings() : array {
		return $this->settings;
	}

	/**
	 * @param mixed[] $settings
	 *
	 * @return self
	 */
	public function setSettings(array $settings) : self {
		$this->settings = $settings;
		$this->settings["preset"] = json_encode($settings);
		return $this;
	}
}