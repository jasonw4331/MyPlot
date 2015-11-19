<?php

namespace MyPlot;              

use pocketmine\utils\Config;                                                    

class LangMsgs

#File originally created by #664400 for PurePerms. Full credit given to the original creator. This file may be temporary until we can create a new language system.
{

    private $language, $msgs;
    private $lnglst = [];
    
    public function __construct(MyPlot $plugin)
    {
        $this->plugin = $plugin;
        
        $this->compileLangPacks();
        
        $this->loadMsgs();
    }

    public function compileLangPacks()
    {
        $result = [];
        
        foreach($this->plugin->getResources() as $resource)
        {
            if(mb_strpos($resource, "lang-") !== false) $result[] = substr($resource, -6, -4);
        }
        
        $this->lngList = $result;
    }

    public function getMessage($node, $vars)
    {
        $msg = $this->msgs->getNested($node);
        
        if($msg != null)
        {
            $number = 0;
            
            foreach($vars as $v)
            {           
                $msg = str_replace("%var$number%", $v, $msg);
                
                $number++;
            }
            
            return $msg;
        }
        
        return null;
    }


    public function loadMsgs()
    {       
        $defaultRes = $this->plugin->getConfigValue("language");
        
        foreach($this->lngList as $resName)
        {
            if(strtolower($defaultRes) == $resName)
            {
                $this->language = $resName;
            }
        }
        
        $this->plugin->saveResource("lang-" . $this->language . ".yml");
        
        $this->msgs = new Config($this->plugin->getDataFolder() . "lang-" . $this->language . ".yml", Config::YAML, [
        ]);
        
        $this->plugin->getLogger()->info("The default language is '" . $defaultRes . "'");
    }
    
    public function reloadMessages()
    {
        $this->msgs->reload();
    }    
}
