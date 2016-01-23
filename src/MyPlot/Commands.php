<?php
namespace MyPlot;

use pocketmine\utils\TextFormat;
use MyPlot\subcommand\SubCommand;
use MyPlot\subcommand\AddHelperSubCommand;
use MyPlot\subcommand\ClaimSubCommand;
use MyPlot\subcommand\ClearSubCommand;
use MyPlot\subcommand\DisposeSubCommand;
use MyPlot\subcommand\GenerateSubCommand;
use MyPlot\subcommand\HelpSubCommand;
use MyPlot\subcommand\HomeSubCommand;
use MyPlot\subcommand\InfoSubCommand;
use MyPlot\subcommand\HomesSubCommand;
use MyPlot\subcommand\ResetSubCommand;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use MyPlot\subcommand\RemoveHelperSubCommand;
use MyPlot\subcommand\AutoSubCommand;
use MyPlot\subcommand\BiomeSubCommand;
use MyPlot\subcommand\NameSubCommand;
use MyPlot\subcommand\GiveSubCommand;
use MyPlot\subcommand\WarpSubCommand;

class Commands extends PluginCommand
{
    /** @var SubCommand[] */
    private $subCommands = [];

    /** @var SubCommand[]  */
    private $aliasSubCommands = [];

    public function __construct(MyPlot $plugin) {
        parent::__construct($plugin->getLanguage()->get("command.name"), $plugin);
        $this->setPermission("myplot.command");
        $this->setAliases([$plugin->getLanguage()->get("command.alias")]);
        $this->setDescription($plugin->getLanguage()->get("command.desc"));

        $this->loadSubCommand(new HelpSubCommand($plugin, "help"));
        $this->loadSubCommand(new ClaimSubCommand($plugin, "claim"));
        $this->loadSubCommand(new GenerateSubCommand($plugin, "generate"));
        $this->loadSubCommand(new InfoSubCommand($plugin, "info"));
        $this->loadSubCommand(new AddHelperSubCommand($plugin, "addhelper"));
        $this->loadSubCommand(new RemoveHelperSubCommand($plugin, "removehelper"));
        $this->loadSubCommand(new AutoSubCommand($plugin, "auto"));
        $this->loadSubCommand(new ClearSubCommand($plugin, "clear"));
        $this->loadSubCommand(new DisposeSubCommand($plugin, "dispose"));
        $this->loadSubCommand(new ResetSubCommand($plugin, "reset"));
        $this->loadSubCommand(new BiomeSubCommand($plugin, "biome"));
        $this->loadSubCommand(new HomeSubCommand($plugin, "home"));
        $this->loadSubCommand(new HomesSubCommand($plugin, "homes"));
        $this->loadSubCommand(new NameSubCommand($plugin, "name"));
        $this->loadSubCommand(new GiveSubCommand($plugin, "give"));
        $this->loadSubCommand(new WarpSubCommand($plugin, "warp"));
    }

    /**
     * @return SubCommand[]
     */
    public function getCommands() {
        return $this->subCommands;
    }

    private function loadSubCommand(Subcommand $command) {
        $this->subCommands[$command->getName()] = $command;
        if ($command->getAlias() != "") {
            $this->aliasSubCommands[$command->getAlias()] = $command;
        }
    }

    public function execute(CommandSender $sender, $alias, array $args) {
        if (!isset($args[0])) {
            $sender->sendMessage(MyPlot::getInstance()->getLanguage()->get("command.usage"));
            return true;
        }

        $subCommand = strtolower(array_shift($args));
        if (isset($this->subCommands[$subCommand])) {
            $command = $this->subCommands[$subCommand];
        } elseif (isset($this->aliasSubCommands[$subCommand])) {
            $command = $this->aliasSubCommands[$subCommand];
        } else {
            $sender->sendMessage(TextFormat::RED . MyPlot::getInstance()->getLanguage()->get("command.unknown"));
            return true;
        }

        if ($command->canUse($sender)) {
            if (!$command->execute($sender, $args)) {
                $usage = MyPlot::getInstance()->getLanguage()->translateString("subcommand.usage", [$command->getUsage()]);
                $sender->sendMessage($usage);
            }
        } else {
            $sender->sendMessage(TextFormat::RED . MyPlot::getInstance()->getLanguage()->get("command.unknown"));
        }
        return true;
    }
}
