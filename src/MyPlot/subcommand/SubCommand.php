<?php
namespace MyPlot\subcommand;

use MyPlot\MyPlot;
use pocketmine\command\CommandSender;

abstract class SubCommand
{
    /** @var MyPlot */
    private $plugin;

    /**
     * @param MyPlot $plugin
     */
    public function __construct(MyPlot $plugin){
        $this->plugin = $plugin;
    }

    /**
     * @return MyPlot
     */
    public final function getPlugin(){
        return $this->plugin;
    }

    /**
     * @param CommandSender $sender
     * @return bool
     */
    public abstract function canUse(CommandSender $sender);

    /**
     * @return string
     */
    public abstract function getUsage();

    /**
     * @return string
     */
    public abstract function getName();

    /**
     * @return string
     */
    public abstract function getDescription();

    /**
     * @return string[]
     */
    public abstract function getAliases();

    /**
     * @param CommandSender $sender
     * @param string[] $args
     * @return bool
     */
    public abstract function execute(CommandSender $sender, array $args);
}
