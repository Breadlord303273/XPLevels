<?php

namespace XPLevels;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\scheduler\Task;

use XPLevels\Main;

class TierTask extends Task{
  
  public $plugin;
  public $player;
  
  public function __construct(Main $plugin, Player $player){
    $this->plugin = $plugin;
    $this->player = $player;
  }
  
  public function onRun($currentTick){
    $main = $this->plugin;
    $player = $this->player;
    $main->addTierPermissions($player);
  }
}
