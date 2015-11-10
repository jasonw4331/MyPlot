<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class AddHelperSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.addhelper");
    }

    public function getUsage() {
        return "<player>";
    }

    public function getName() {
        return "addhelper";
    }

    public function getDescription() {
        return $this->plugin->getMessage("messages.addhelper-desc");
    }

    public function getAliases() {
        return [$this->plugin->getMessage("messages.addhelper-alias")];
    }

    public function execute(CommandSender $sender, array $args) {
        if (count($args) !== 1) {
            return false;
        }
        $helper = $args[0];
        $player = $sender->getServer()->getPlayer($sender->getName());
        $plot = $this->getPlugin()->getPlotByPosition($player->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . $this->plugin->getMessage("messages.addhelper-notinplot"));
            return true;
        }
        if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.addhelper")) {
            $sender->sendMessage(TextFormat::RED . $this->plugin->getMessage("messages.addhelper-notowner"));
            return true;
        }
        if (!$plot->addHelper($helper)) {
            $sender->sendMessage($helper . $this->plugin->getMessage("messages.addhelper-alreadyone"));
            return true;
        }
        if ($this->getPlugin()->getProvider()->savePlot($plot)) {
            $sender->sendMessage(TextFormat::GREEN . $helper . $this->plugin->getMessage("messages.addhelper-success"));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->plugin->getMessage("messages.addhelper-error"));
        }
        return true;
    }
}
