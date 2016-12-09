<?php
namespace MyPlot\subcommand;

use MyPlot\MyPlotGenerator;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class GenerateSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return $sender->hasPermission("myplot.command.generate");
    }

    public function execute(CommandSender $sender, array $args) {
        if (count($args) == 0) {
            return false;
        }
        $levelName = $args[0];
        if(!empty($args[1])) {
	        $gen = strtolower($args[1]);
        }else{
        	$gen = MyPlotGenerator::$name;
        }
        if ($sender->getServer()->isLevelGenerated($levelName)) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("generate.exists", [$levelName]));
            return true;
        }
	    $gen = $this->getPlugin()->generatorExists($gen);
        if($gen == false) {
	        $sender->sendMessage($this->translateString("generate.gexists", [$gen]));
	        return true;
        }
        if ($this->getPlugin()->generateLevel($levelName,$gen)) {
            $sender->sendMessage($this->translateString("generate.success", [$levelName]));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->translateString("generate.error"));
        }
        return true;
    }
}
