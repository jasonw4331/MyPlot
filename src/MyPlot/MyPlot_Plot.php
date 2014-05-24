<?php
namespace MyPlot;

use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\network\protocol\ChunkDataPacket;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

class MyPlot_Plot{
    private $filePath;
    public $id, $levelName, $owner, $helpers, $comments, $levelData;

    public function __construct($id, $levelName){
        if(!(count($id) === 2 and is_numeric($id[0]) and is_numeric($id[1])))
            throw new \Exception('Error: ID incorrect.');

        $dir = MyPlot::$folder.'worlds/'.$levelName.'/';
        if(!is_dir($dir))
            throw new \Exception('Error: Level not found.');

        $filePath = $dir.$id[0].'/'.$id[0].'.'.$id[1].'.data';
        if(is_dir($dir.$id[0]) and is_file($filePath)){
            $file = json_decode(file_get_contents($filePath), true);
            $this->owner = $file[0];
            $this->helpers = $file[1];
            $this->comments = $file[2];
        }else{
            $this->owner = false;
            $this->helpers = array();
            $this->comments = array();
        }

        $this->filePath = $filePath;
        $this->id = $id;
        $this->levelName = $levelName;
        $this->levelData = MyPlot::$levelData[$levelName];

    }

    public function addHelper($username){
        if(in_array($username, $this->helpers))
            return false;
        $this->helpers [] = $username;
        return true;
    }

    public function removeHelper($username){
        $key = array_search(strtolower($username), $this->helpers);
        if($key === false)
            return false;
        unset($this->helpers[$key]);
        return true;
    }

    public function isHelper($username){
        return in_array($username, $this->helpers);
    }

    public function teleport($username){
        $player = Server::getInstance()->getPlayer($username);
        $totalSize = $this->levelData[0] + $this->levelData[1];
        if($this->id[0] < 0){
            $xBegin = $totalSize * $this->id[0] + $this->levelData[1];
        }else{
            $xBegin = $totalSize * $this->id[0];
        }
        if($this->id[1] < 0){
            $z = $totalSize * $this->id[1] + $this->levelData[1];
        }else{
            $z = $totalSize * $this->id[1];
        }
        $x = $xBegin + floor($this->levelData[0]/2);
        $y = $this->levelData[2];
        $level = Server::getInstance()->getLevel($this->levelName);
        $player->teleport(new Position($x, $y, $z, $level));
    }

    public function save(){
        $data = array(
            $this->owner,
            $this->helpers,
            $this->comments
        );

        $dir = MyPlot::$folder.'worlds/'.$this->levelName.'/'.$this->id[0];
        if(!is_dir($dir))
            mkdir($dir);

        file_put_contents($this->filePath, json_encode($data));
        return true;
    }

    public function clear(){
        $level = Server::getInstance()->getLevel($this->levelName);
        $pos = new Vector3();
        $totalSize = $this->levelData[0] + $this->levelData[1];
        if($this->id[0] < 0){
            $xBegin = $totalSize * $this->id[0] + $this->levelData[1];
        }else{
            $xBegin = $totalSize * $this->id[0];
        }
        if($this->id[1] < 0){
            $zBegin = $totalSize * $this->id[1] + $this->levelData[1];
        }else{
            $zBegin = $totalSize * $this->id[1];
        }
        $xEnd = $xBegin + $this->levelData[0];
        $zEnd = $zBegin + $this->levelData[0];

        $blocks = array(
            Block::get($this->levelData[7][0], $this->levelData[7][1]),
            Block::get($this->levelData[4][0], $this->levelData[4][1]),
            Block::get($this->levelData[3][0], $this->levelData[3][1]),
            Block::get(0)
        );

        $height = $this->levelData[2];

        for($x=$xBegin; $x<$xEnd; $x++){
            $pos->x = $x;
            for($z=$zBegin; $z<$zEnd; $z++){
                $pos->z = $z;
                for($y=0; $y<128; $y++){
                    $pos->y = $y;
                    if($y === 0){
                        $block = $blocks[0];
                    }elseif($y < ($height - 1)){
                        $block = $blocks[1];
                    }elseif($y === ($height - 1)){
                        $block = $blocks[2];
                    }else{
                        $block = $blocks[3];
                    }
                    $level->setBlockRaw($pos, $block, false);
                }
            }
        }

        $XBegin = floor($xBegin / 16);
        $ZBegin = floor($zBegin / 16);
        $XEnd = ceil($xEnd / 16);
        $ZEnd = ceil($zEnd / 16);

        for($X=$XBegin; $X<$XEnd; $X++){
            for($Z=$ZBegin; $Z<$ZEnd; $Z++){
                $pk = new ChunkDataPacket;
                $pk->chunkX = $X;
                $pk->chunkZ = $Z;
                $pk->data = $level->getOrderedChunk($X, $Z, 0xFF);
                Player::broadcastPacket($level->players, $pk);
            }
        }
    }

    public function delete(){
        if(is_file($this->filePath))
            unlink($this->filePath);
    }
}