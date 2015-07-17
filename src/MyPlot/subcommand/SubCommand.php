<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;

interface SubCommand
{
    /**
     * @param CommandSender $sender
     * @return bool
     */
    public function canUse(CommandSender $sender);

    /**
     * @return string
     */
    public function getUsage();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @return string[]
     */
    public function getAliases();

    /**
     * @param CommandSender $sender
     * @param string[] $args
     * @return bool
     */
    public function execute(CommandSender $sender, array $args);
}