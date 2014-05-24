<?php
namespace MyPlot;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use pocketmine\level\generator\Generator;
use pocketmine\utils\Config;
use pocketmine\event\EventPriority;
use pocketmine\plugin\MethodEventExecutor;

class MyPlot extends PluginBase implements Listener{
    private $server;
    static $config;
    public static $levelData = array(), $folder;

    public function onEnable(){
        $this->server = Server::getInstance();

        self::$folder = $this->getDataFolder();
        if(!is_dir(self::$folder))
            mkdir(self::$folder);

        if(!is_dir(self::$folder.'worlds'))
            mkdir(self::$folder.'worlds');

        $handle = opendir(self::$folder.'worlds');
        while(false !== ($fileName = readdir($handle))){
            if(substr($fileName, -5) !== '.data')
                continue;

            $levelName = substr($fileName, 0, -5);
            if($this->server->getLevel($levelName) === false){
                $this->getLogger()->log(TextFormat::RED.'Plotworld data found for level: '.$levelName.' ,but the level could not be loaded');
                continue;
            }
            self::$levelData[$levelName] = json_decode(file_get_contents(self::$folder.'worlds/'.$fileName), true);
        }
        closedir($handle);

        Generator::addGenerator("MyPlot\\MyPlot_Generator", 'MyPlot_Generator');

        $default = $settings = array(
            'MaxPlotsPerPlayer' => 1,
            'PlotSize' => 20,
            'RoadWidth' => 7,
            'Height' => 64,
            'PlotFloorBlockId' => [2,0], // grass
            'PlotFillingBlockId' => [3,0], // dirt
            'RoadBlockId' => [5,0], // wooden planks
            'WallBlockId' => [44,0], // stone slab
            'BottomBlockId' => [7,0] // bedrock
        );
        self::$config = new Config(self::$folder.'config.yml', Config::YAML, $default);
        $pluginManager = $this->server->getPluginManager();
        $pluginManager->registerEvent("pocketmine\\event\\block\\BlockBreakEvent", $this, EventPriority::HIGH, new MethodEventExecutor('onBlockBreak'), $this, false);
        $pluginManager->registerEvent("pocketmine\\event\\block\\BlockPlaceEvent", $this, EventPriority::HIGH, new MethodEventExecutor('onBlockPlace'), $this, false);
        $this->server->getCommandMap()->register("MyPlot\\MyPlot_Commands", new MyPlot_Commands());
    }

    public function onBlockBreak(BlockBreakEvent $event){
        $player = $event->getPlayer();
        $levelName = $player->getLevel()->getName();
        if(isset(self::$levelData[$levelName])){
            $block = $event->getBlock();
            $pos = new Position($block->x, $block->y, $block->z, $block->level);
            $plot = self::getPlotByPos($pos);
            if($plot !== false){
                $username = $player->getName();
                if(!($plot->owner === $username or $plot->isHelper($username)))
                    $event->setCancelled(true);
            }
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event){
        $player = $event->getPlayer();
        $levelName = $player->getLevel()->getName();
        if(isset(self::$levelData[$levelName])){
            $block = $event->getBlock();
            $pos = new Position($block->x, $block->y, $block->z, $block->level);
            $plot = self::getPlotByPos($pos);
            if($plot !== false){
                $username = $player->getName();
                if(!($plot->owner === $username or $plot->isHelper($username)))
                    $event->setCancelled(true);
            }
        }
    }

    public static function levelExist($levelName){
        return is_file(self::$folder.'worlds/'.$levelName.'/plots.data');
    }

    public static function getPlotByPos($position){
        $x = $position->x;
        $z = $position->z;
        $level = $position->level->getName();

        if(!(self::levelExist($level) and isset(self::$levelData[$level])))
            return false;


        $levelData = self::$levelData[$level];

        $plotSize = $levelData[0];
        $roadSize = $levelData[1];
        $totalSize = $plotSize + $roadSize;

        $id = array();

        if($x >= 0){
            $id[0] = floor($x/$totalSize);
            $X = $x % $totalSize;
        }else{
            $id[0] = (int)ceil(($x-$plotSize+1)/$totalSize);
            $X = abs(($x-$plotSize+1) % $totalSize);
        }

        if($z >= 0){
            $id[1] = floor($z/$totalSize);
            $Z = $z % $totalSize;
        }else{
            $id[1] = (int)ceil(($z-$plotSize+1)/$totalSize);
            $Z = abs(($z-$plotSize+1) % $totalSize);
        }

        if(($X > $plotSize - 1) or ($Z > $plotSize - 1)){
            return false;
        }

        try{
            $plot = new MyPlot_Plot($id, $level);
        }catch(\Exception $e){
            return false;
        }
        return $plot;
    }

    public static function getPlayerData($username){
        $username = strtolower($username);
        $folder = self::$folder.'players/'.$username[0].'/';
        if(!(is_dir($folder) and is_file($folder.$username.'.dat'))){
            return array(0, array());
        }

        return json_decode(file_get_contents($folder.$username.'.dat'), true);
    }

    public static function savePlayerData($username, array $data){
        $username = strtolower($username);
        $folder = self::$folder.'players/'.$username[0].'/';

        if(!is_dir($folder))
            mkdir($folder);

        file_put_contents($folder.$username.'.dat', json_encode($data));
    }

    public function onDisable(){

    }
}