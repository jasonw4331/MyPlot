<?php
namespace MyPlot\subcommand;

use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use MyPlot\Plot;

class ListSubCommand implements SubCommand
{
    private $plugin;

    public function __construct(MyPlot $plugin) {
        $this->plugin = $plugin;
    }

    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.list");
    }

    public function getUsage() {
        return "";
    }

    public function getName() {
        return "list";
    }

    public function getDescription() {
        return "List all the plots you own";
    }

    public function getAliases() {
        return [];
    }

    public function execute(CommandSender $sender, array $args) {
        if (!empty($args)) {
            return false;
        }
        $player = $sender->getServer()->getPlayer($sender->getName());
        $levelName = $player->getLevel()->getName();
        $plots = $this->plugin->getProvider()->getPlotsByOwner($sender->getName());
        usort($plots, function($plot1, $plot2) {
            /** @var Plot $plot1 */
            /** @var Plot $plot2 */
            return strcmp($plot1->levelName, $plot2->levelName);
        });
        if (empty($plots)) {
            $sender->sendMessage("You do not own any plots");
            return true;
        }
        $sender->sendMessage("Plots you own:");
        for ($i = 0; $i < count($plots); $i++) {
            $plot = $plots[$i];
            $message = TextFormat::DARK_GREEN . ($i + 1) . ") ";
            $message .= TextFormat::WHITE . $levelName . ": " . $plot->X . ";" . $plot->Z;
            if ($plot->name !== "") {
                $message .= " aka " . $plot->name;
            }
            $sender->sendMessage($message);
        }
        return true;
    }
}