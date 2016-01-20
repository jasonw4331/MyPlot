<?php
namespace MyPlot\subcommand;

use MyPlot\MyPlot;
use pocketmine\command\CommandSender;

abstract class SubCommand
{
    /** @var MyPlot */
    private $plugin;
    private $name;

    /**
     * @param MyPlot $plugin
     * @param string $name
     */
    public function __construct(MyPlot $plugin, $name) {
        $this->plugin = $plugin;
        $this->name = $name;
    }

    /**
     * @return MyPlot
     */
    public final function getPlugin(){
        return $this->plugin;
    }

    /**
     * @param string   $str
     * @param string[] $params
     *
     * @return string
     */
    protected function translateString($str, array $params = [], $onlyPrefix = null) {
        return $this->plugin->getLanguage()->translateString($str, $params, $onlyPrefix);
    }

    /**
     * @param CommandSender $sender
     * @return bool
     */
    public abstract function canUse(CommandSender $sender);

    /**
     * @return string
     */
    public final function getUsage() {
        $usage = $this->getPlugin()->getLanguage()->get($this->name . ".usage");
        return ($usage == $this->name . ".usage") ? "" : $usage;
    }

    /**
     * @return string
     */
    public final function getName() {
        $name = $this->getPlugin()->getLanguage()->get($this->name . ".name");
        return ($name == $this->name . ".name") ? "" : $name;
    }

    /**
     * @return string
     */
    public final function getDescription() {
        $desc = $this->getPlugin()->getLanguage()->get($this->name . ".desc");
        return ($desc == $this->name . ".desc") ? "" : $desc;
    }

    /**
     * @return string
     */
    public final function getAlias() {
        $alias = $this->getPlugin()->getLanguage()->get($this->name . ".alias");
        return ($alias == $this->name . ".alias") ? "" : $alias;
    }

    /**
     * @param CommandSender $sender
     * @param string[] $args
     * @return bool
     */
    public abstract function execute(CommandSender $sender, array $args);
}
