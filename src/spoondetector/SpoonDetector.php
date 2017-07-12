<?php
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

/**
 * This class is deliberately meant to be silly
 * Class SpoonDetector
 * @package falkirks\simplewarp\utils
 */
class SpoonDetector{

    private static $subtleAsciiSpoon = "   
         ___ _ __   ___   ___  _ __  
        / __| '_ \\ / _ \\ / _ \\| '_ \\ 
        \\__ \\ |_) | (_) | (_) | | | |
        |___/ .__/ \\___/ \\___/|_| |_|
            | |                      
            |_|                      
    ";

    private static $spoonTxtContent = "
    The author of this plugin does not provide support for third-party builds of 
    PocketMine-MP (spoons). Spoons detract from the overall quality of the MCPE plugin environment, which is already 
    lacking in quality. They force plugin developers to waste time trying to support conflicting APIs.
    
    In order to begin using this plugin you must understand that you will be offered no support. 
    
    Furthermore, the GitHub issue tracker for this project is targeted at vanilla PocketMine only. Any bugs you create which don't affect vanilla PocketMine, will be deleted.
    
    Have you read and understood the above (type 'yes' after the question mark)?";

    private static $thingsThatAreNotSpoons = [
        'PocketMine-MP'
    ];

    public static function isThisSpoon() : bool {
        return !in_array(Server::getInstance()->getName(), self::$thingsThatAreNotSpoons);
    }

    private static function contentValid(string $content): bool {
        return (strpos($content, self::$spoonTxtContent) !== false) && (strrpos($content, "yes") > strrpos($content, "?"));
    }

    public static function printSpoon(PluginBase $pluginBase, $fileToCheck = "spoon.txt"){
        if(self::isThisSpoon()){
            if(!file_exists($pluginBase->getDataFolder() . $fileToCheck)){
                file_put_contents($pluginBase->getDataFolder() . $fileToCheck, self::$spoonTxtContent);
            }
            if(!self::contentValid(file_get_contents($pluginBase->getDataFolder() . $fileToCheck))) {
                $pluginBase->getLogger()->info(self::$subtleAsciiSpoon);
                $pluginBase->getLogger()->warning("You are attempting to run " . $pluginBase->getDescription()->getName() . " on a SPOON!");
                $pluginBase->getLogger()->warning("Before using the plugin you will need to open /plugins/" . $pluginBase->getDescription()->getName() . "/" . $fileToCheck . " in a text editor and agree to the terms.");
                $pluginBase->getServer()->getPluginManager()->disablePlugin($pluginBase);
            }
        }
    }

}
