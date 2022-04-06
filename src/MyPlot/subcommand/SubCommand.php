<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use MyPlot\InternalAPI;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;

abstract class SubCommand implements MyPlotSubCommand{
	public function __construct(protected MyPlot $plugin, protected InternalAPI $internalAPI, private string $name){ }

	protected function translateString(string $str, array $params = [], ?string $onlyPrefix = null) : string{
		return $this->plugin->getLanguage()->translateString($str, $params, $onlyPrefix);
	}

	public abstract function canUse(CommandSender $sender) : bool;

	public function getUsage() : string{
		$usage = $this->plugin->getFallBackLang()->get($this->name . ".usage"); // TODO: use normal language when command autofill gains support
		return ($usage == $this->name . ".usage") ? "" : $usage;
	}

	public function getName() : string{
		$name = $this->plugin->getLanguage()->get($this->name . ".name");
		return ($name == $this->name . ".name") ? "" : $name;
	}

	public function getDescription() : string{
		$desc = $this->plugin->getLanguage()->get($this->name . ".desc");
		return ($desc == $this->name . ".desc") ? "" : $desc;
	}

	public function getAlias() : string{
		$alias = $this->plugin->getLanguage()->get($this->name . ".alias");
		return ($alias == $this->name . ".alias") ? "" : $alias;
	}

	public function getFormClass() : ?string{
		return null;
	}

	public abstract function execute(CommandSender $sender, array $args) : bool;
}