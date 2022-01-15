<?php

namespace XPLevels;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\scheduler\Task;

use XPLevels\Main;

class CreditsTask extends Task{

    public $plugin;
    public $player;

    public function __construct(Main $plugin, Player $player){
        $this->plugin = $plugin;
        $this->player = $player;
    }

    public function onRun($currentTick){
        $player = $this->player;
        $main = $this->plugin;
        $main->checkCredits($player);
    }
}
