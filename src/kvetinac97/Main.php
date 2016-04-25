<?php

namespace kvetinac97;

use pocketmine\event\Listener;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {

    /** @var Config $config */
    public $config;

    /** @var int $players */
    public $players = 0;
    /** @var int $maxPlayers */
    public $maxPlayers = 0;

    public function onEnable() {
        $this->getLogger()->info("§bLinkSlots §aENABLED!");
        $this->getLogger()->info("§7Running version §e1.1.0...");
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->setPlayers();
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new RefreshTask($this), $this->config->get("interval")*60*20);
    }

    public function onDisable() {
        $this->getLogger()->info("§bLinkSlots §cDISABLED!");
    }


    public function setPlayers($task = false){
        $this->players = 0;
        $this->maxPlayers = 0;
        if (!$task && ($this->config->get("servers") === [] || !$this->config->get("servers"))){
            $this->getLogger()->critical("§4Could not load plugin: you haven't set any servers to config.yml!");
            $this->getLogger()->warning("§cDisabling §bLinkSlots...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
        foreach ($this->config->get("servers") as $ip => $port){
            /** @var int $onl */
            $onl = file_get_contents("https://www.minetox.cz/queryapi/online.php?ip=$ip&port=$port");
            /** @var int $max */
            $max = file_get_contents("https://www.minetox.cz/queryapi/maxonline.php?ip=$ip&port=$port");
            if ($onl == -1 || $max == -1){
                if (!$task){
                    $this->getLogger()->warning("§cCould not connect to §e$ip:§b$port; §cThe server is offline");
                }
                continue;
            }
            $this->players += $onl;
            $this->maxPlayers += $max;
        }
        if ($this->config->get("add_self_slots") == "true"){
            $this->players += \count($this->getServer()->getOnlinePlayers());
            $this->maxPlayers += $this->getServer()->getMaxPlayers();
        }
    }

    public function onQuery(QueryRegenerateEvent $e){
        $e->setPlayerCount($this->players);
        $e->setMaxPlayerCount($this->maxPlayers);
    }



}

class RefreshTask extends Task{

    public $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function onRun($currentTick) {
        $this->plugin->setPlayers(true);
    }
}
