<?php

namespace XPLevels;

use pocketmine\{Server,Player};

use pocketmine\plugin\PluginBase;

use pocketmine\command\{Command,CommandSenderConsole,CommandSender};

use pocketmine\event\{Listener,PlayerInteractEvent};

use pocketmine\event\player\{PlayerJoinEvent,PlayerDeathEvent,PlayerRespawnEvent,PlayerChatEvent,PlayerMoveEvent};

use pocketmine\event\block\{BlockPlaceEvent,BlockBreakEvent};

use pocketmine\event\entity\{EntityDamageEvent,EntityDeathEvent,EntityDamageByEntityEvent};

use pocketmine\scheduler\ClosureTask;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;

use _64FF00\PurePerms;
use jojoe77777\FormAPI;
use onebone\economyapi\EconomyAPI;

class Main extends PluginBase implements Listener {
	
	public array $hitter = [];
	
	public function onEnable() {
		$this->stats = new Config($this->getDataFolder() . "stats.yml", Config::YAML);
		$this->credits = new Config($this->getDataFolder() . "credits.yml", Config::YAML);
		@mkdir($this->getDataFolder());
		$this->saveResource("stats.yml");
		$this->saveResource("credits.yml");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->hitter = [];
	}
	
	//STATS CMD
	public function onCommand(CommandSender $sender, Command $command, String $label, Array $args) : bool {
		switch($command->getName()) {
			case "stats":
			if(!isset($args[0])) {
				if($sender instanceof Player) {
					$this->formStats($sender);
					return true;
				}
			}
			if(isset($args[0])) {
				$player = $this->getServer()->getPlayer($args[0]);
				if($player instanceof Player) {
					if($sender instanceof Player) {
						$this->formStatsOfAnotherPlayer($sender, $player);
						return true;
					}
				}
			}
			break;
		case "addcredits":
			if($sender instanceof Player) {
                  if($sender->hasPermission("addcredits.cmd")) {
						if((!isset($args[0]) and !isset($args[1])) or (!isset($args[0]) or !isset($args[1]))) {
							$sender->sendMessage("Usage: /addcredits <player> <credits>");
						}
						if(isset($args[0]) and isset($args[1])) {
							if(!is_int($args[1])) {
								$player->sendMessage("Usage: /addcredits <player> <credits>");
							} elseif(is_int($args[1])) {
								$target = $this->getServer()->getPlayer($args[0])->getName();
								$credits = $args[1];
								if($target instanceof Player){
							$this->addCredits($target, $credits);
							}
							}
						}
					} else {
						$sender->sendMessage("§cYou do not have permission to use this command.");
					}
				} else {
                   $sender->sendMessage("§6Please run this command in-game.");				}
			}
			break;
		}
		return true;
	}
	
	//ADD PROFILE
	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		if(!$this->stats->exists(strtolower($player->getName())) and !$this->credits->exists(strtolower($player->getName()))) {
			$this->addPlayer($player);
		}
		$this->addTierPermissions($player);
		$this->getScheduler()->scheduleRepeatingTask(new CreditsTask($this, $player), 20);
		$this->getScheduler()->scheduleRepeatingTask(new TierTask($this, $player), 20);
	}
	
	//ADD PROFILE INFO
	public function addPlayer($player) {
		$this->stats->setNested(strtolower($player->getName()) . ".kills", "0");
		$this->stats->setNested(strtolower($player->getName()) . ".deaths", "0");
		$this->stats->setNested(strtolower($player->getName()) . ".xp", "0");
		$this->credits->setNested(strtolower($player->getName()) . ".credits", "0");
		$this->credits->setNested(strtolower($player->getName()) . ".tier", "N/A");
		$this->stats->save();
		$this->credits->save();
	}
	
	//SAVE PLAYERS' XP LEVELS
	public function onPlayerDeath(PlayerDeathEvent $event) {
		$player = $event->getPlayer();
		$this->stats->setNested(strtolower($player->getName()) . ".xp", $player->getCurrentTotalXp());
		$this->stats->save();
		$event->setXpDropAmount(0);
	}
	
	//GIVE PLAYERS' BACK XP LEVELS
	public function onPlayerRespawn(PlayerRespawnEvent $event) {
		$player = $event->getPlayer();
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($player) : void {
			$player->getXpLevel();
				if ($player->isOnline()) {
					$player->addXp($this->stats->getAll()[strtolower($player->getName())]["xp"]);
					$this->stats->save();
				}
		}), 20);
		$player->setGamemode(0);
	}
	
	//STATS FORM
	public function formStats(Player $player) {
		
		$ppapi = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
		$ign = $player->getName();
		$rank = $ppapi->getUserDataMgr()->getGroup($player)->getName();
		$tier = $this->getTier($player);
		$level = $player->getXpLevel();
		$coins = EconomyAPI::getInstance()->myMoney($player);
		$credits = $this->getCredits($player);
		$kills = $this->getKill($player);
		$deaths = $this->getDeath($player);
		$kdr = round($deaths == 0 ? $kills / 1 : $kills / $deaths, 2);
		$totalxp = $player->getCurrentTotalXp();
		
		$formapi = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $formapi->createSimpleForm(function (Player $player, int $data = null) {
			$result = $data;
			if($result === null) {
				return true;
			}
			switch($result) {
				case 0:
				
				break;
			}
		});
		$form->setTitle("§l" . $ign . "'s Stats");
		$form->setContent(
			
		"§fRank: §a" . $rank . 
		"\n" . 
		"§fTier: §a" . ucfirst(strtolower($tier)) . 
		"\n" . 
		"§fLevel: §a" . $level . 
		"\n" . 
		"§fCoins: §a" . $coins . 
		"\n" . 
		"§fCredits: §a" . $credits . 
		"\n" . 
		"§fKills: §a" . $kills . 
		"\n" . 
		"§fDeaths: §a" . $deaths . 
		"\n" . 
		"§fKDR: §a" . $kdr . 
		"\n" . 
		"§fTotal EXP: §a" . $totalxp . 
		"\n"

		);
		$form->addButton("§l§cEXIT",0,"textures/blocks/barrier");
		$form->sendToPlayer($player);
		return $form;
	}
	
	//CHECK OTHERS STATS FORM
	public function formStatsOfAnotherPlayer(Player $sender, Player $player){
		
		$ppapi = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
		$ign = $player->getName();
		$rank = $ppapi->getUserDataMgr()->getGroup($player)->getName();
		$tier = $this->getTier($player);
		$level = $player->getXpLevel();
		$coins = EconomyAPI::getInstance()->myMoney($player);
		$credits = $this->getCredits($player);
		$kills = $this->getKill($player);
		$deaths = $this->getDeath($player);
		$kdr = round($deaths == 0 ? $kills / 1 : $kills / $deaths, 2);
		$totalxp = $player->getCurrentTotalXp();
		
		$formapi = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $formapi->createSimpleForm(function (Player $sender, int $data = null) {
			$result = $data;
			if($result === null){
				return true;
			}
			switch($result){
				case 0:
				
				break;
			}
		});
		$form->setTitle("§l" . $ign . "'s Stats");
		$form->setContent(
		 
		"§fRank: §a" . $rank . 
		"\n" . 
		"§fTier: §a" . ucfirst(strtolower($tier)) . 
		"\n" . 
		"§fLevel: §a" . $level . 
		"\n" . 
		"§fCoins: §a" . $coins . 
		"\n" . 
		"§fCredits: §a" . $credits . 
		"\n" . 
		"§fKills: §a" . $kills . 
		"\n" . 
		"§fDeaths: §a" . $deaths . 
		"\n" . 
		"§fKDR: §a" . $kdr . 
		"\n" . 
		"§fTotal EXP: §a" . $totalxp . 
		"\n"

		);
		$form->addButton("§l§cEXIT",0,"textures/blocks/barrier");
		$form->sendToPlayer($sender);
		return $form;
	}
	
	//ADD KILL ON CONFIG
	public function addKill($player) {
		$this->stats->setNested(strtolower($player->getName()) . ".kills", $this->stats->getAll()[strtolower($player->getName())]["kills"] + 1);
		$this->stats->save();
	}
	
	//ADD DEATH ON CONFIG
	public function addDeath($player) {
		$this->stats->setNested(strtolower($player->getName()) . ".deaths", $this->stats->getAll()[strtolower($player->getName())]["deaths"] + 1);
		$this->stats->save();
	}

	//ADD CREDITS ON CONFIG
	public function addCredits(Player $player, int $amount) {
		$this->credits->setNested(strtolower($player->getName()) . ".credits", $this->credits->getAll()[strtolower($player->getName())]["credits"] + $amount);
		$this->credits->save();
	}

	//ADD TIER PERMISSIONS ON PLAYER
	public function addTierPermissions(Player $player) { //FOR AN EXAMPLE CHECK L.194
		if(!$player->isClosed()){
			$ppapi = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
			if($this->getTier($player) == "N/A") return;
			if($this->getTier($player) == "SHADOW"){
				if(!$player->hasPermission("chatfx.cfx.red")){
					$ppapi->getUserDataMgr()->setPermission($player, "chatfx.cfx.red");
				}
				if(!$player->hasPermission("tags.perm.new")){
					$ppapi->getUserDataMgr()->setPermission($player, "tags.perm.new");
				}
			}
			if($this->getTier($player) == "LIGHT"){
				if(!$player->hasPermission("chatfx.cfx.darkgray")){
					$ppapi->getUserDataMgr()->setPermission($player, "chatfx.cfx.darkgray");
				}
				if(!$player->hasPermission("tags.perm.advanced")){
					$ppapi->getUserDataMgr()->setPermission($player, "tags.perm.advanced");
				}
			}
			if($this->getTier($player) == "MOON"){
				if(!$player->hasPermission("chatfx.cfx.gray")){
					$ppapi->getUserDataMgr()->setPermission($player, "chatfx.cfx.gray");
				}
				if(!$player->hasPermission("tags.perm.intermediate")){
					$ppapi->getUserDataMgr()->setPermission($player, "tags.perm.intermediate");
				}
			}
			if($this->getTier($player) == "SUN") {
				if(!$player->hasPermission("chatfx.cfx.blue")){
					$ppapi->getUserDataMgr()->setPermission($player, "chatfx.cfx.blue");
				}
				if(!$player->hasPermission("tags.perm.expert")){
					$ppapi->getUserDataMgr()->setPermission($player, "tags.perm.expert");
				}
			}
			}
		}
	}

	//CHANGE TIER ON CONFIG
	public function changeTier(Player $player, string $tier) {
		$this->credits->setNested(strtolower($player->getName()) . ".tier", $tier);
		$this->credits->save();
	}
	
	//GET KILL ON CONFIG
	public function getKill($player) {
		return $this->stats->getAll()[strtolower($player->getName())]["kills"];
	}
	
	//GET DEATH ON CONFIG
	public function getDeath($player) {
		return $this->stats->getAll()[strtolower($player->getName())]["deaths"];
	}

	//GET CREDITS FROM CONFIG
	public function getCredits(Player $player) {
		return $this->credits->getAll()[strtolower($player->getName())]["credits"];
	}

	//GET TIER FROM CONFIG
	public function getTier(Player $player) {
		return $this->credits->getAll()[strtolower($player->getName())]["tier"];
	}
	
	// public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) {
	// 	$victim = $event->getEntity();
	// 	if($victim instanceof Player) {
	// 		if($victim->getY() <= 0) {
	// 			$killer = $event->getDamager();
	// 			if($killer instanceof Player) {
	// 				$finalhealth = round($killer->getHealth(), 1);
	// 				$weapon = $killer->getInventory()->getItemInHand()->getName(); //TODO
	// 				$messages = ["quickied", "railed", "clapped", "given an L", "smashed", "botted", "utterly defeated", "swept off their feet", "sent to the heavens"];
	// 				$dm = "§7" . $victim->getDisplayName() . " was " . $messages[array_rand($messages)] . " by " . $killer->getDisplayName() . " §c[" . $finalhealth . " HP]";
	// 				$event->setDeathMessage($dm);
	// 				$this->addKill($killer);
	// 				$killer->addXp(1);
	// 				$killer->setHealth(20);
	// 				$this->addCredits($killer, 1);
	// 				$killer->sendPopup("§l§e+2\n§l§9+1 Credit\n§l§b+20§c❤");
	// 				EconomyAPI::getInstance()->addMoney($killer, 2);
	// 				$this->stats->save();
	// 			}
	// 		}
	// 	}
	// }

	public function onEntityDamage(EntityDamageEvent $event) {
	 	$victim = $event->getEntity();
	 	$lastDmg = $event->getLastDamageCause();
		if($victim instanceof Player){
	 		if($lastDmg instanceof EntityDamageByEntityEvent) {
	 			$damager = $lastDmg->getDamager();
				$this->hitter[$victim->getName()] = $damager;
	 			if($damager instanceof Player) {
	 				if($victim->getHealth() === 0){
						$e = new PlayerDeathEvent($this->hitter[$victim->getName()]);
						$e->call();
					}
	 			}
			} else {
				if($lastDmg === EntityDamageEvent::CAUSE_VOID){
					$victim->kill();
				}
			}
		}
	}

	// public function onEntityDamage(EntityDamageEvent $event) {
	// 	$victim = $event->getEntity();
	// 	$cause = $event->getCause();
	// 	$health = $player->getHealth() - $event->getFinalDamage();
	// 	if($health <= 10 || $cause === EntityDamageEvent::CAUSE_VOID) {
	// 		$event->setCancelled();
	// 	}
	// 	if($victim instanceof Player) {
	// 		if($victim->getY() <= -5) {
	// 			$victim->setGamemode(0);
	// 			$victim->setHealth(20);
	// 			$victim->setFood(20);
	// 			$victim->removeAllEffects();
	// 			$victim->getInventory()->clearAll();
	// 			$victim->getArmorInventory()->clearAll();
	// 			$victim->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
	// 			Hotbar::getHotbar($victim);
	// 			$this->addDeath($victim);
	// 			$this->stats->save();
	// 		}
	// 	}
	// }

	// public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) {
	// 	$victim = $event->getEntity();
	// 	$killer = $event->getDamager();
	// 	if($victim instanceof Player and $killer instanceof Player) {
	// 		if($victim->getY() <= 0) {
	// 			$this->kill($victim, $killer);
	// 			$finalhealth = round($killer->getHealth(), 1);
	// 			$weapon = $killer->getInventory()->getItemInHand()->getName(); //TODO
	// 			$messages = ["quickied", "railed", "clapped", "given an L", "smashed", "botted", "utterly defeated", "swept off their feet", "sent to the heavens"];
	// 			$dm = "§7" . $victim->getDisplayName() . " was " . $messages[array_rand($messages)] . " by " . $killer->getDisplayName() . " §c[" . $finalhealth . " HP]";
	// 			$event->setDeathMessage($dm);
	// 			$this->addKill($killer);
	// 			$killer->addXp(1);
	// 			$killer->setHealth(20);
	// 			$this->addCredits($killer, 1);
	// 			$killer->sendPopup("§l§e+2\n§l§9+1 Credit\n§l§b+20§c❤");
	// 			EconomyAPI::getInstance()->addMoney($killer, 2);
	// 			$this->stats->save();
	// 		}
	// 	}
	// }

	public function onDeath(PlayerDeathEvent $event) {
		$victim = $event->getPlayer();
		$finaldamagecause = $victim->getLastDamageCause();
		$event->setDeathMessage("§7" . $victim->getDisplayName() . " died");
		$cause = $victim->getLastDamageCause();
		if($cause instanceof EntityDamageByEntityEvent and $cause->getDamager() !== null) {
			$killer = $cause->getDamager();
			if($victim instanceof Player and $killer instanceof Player) {
				$finalhealth = round($killer->getHealth(), 1);
				$weapon = $killer->getInventory()->getItemInHand()->getName(); //TODO
				$messages = ["quickied", "railed", "clapped", "given an L", "smashed", "botted", "utterly defeated", "swept off their feet", "sent to the heavens"];
				$dm = "§7" . $victim->getDisplayName() . " was " . $messages[array_rand($messages)] . " by " . $killer->getDisplayName() . " §c[" . $finalhealth . " HP]";
				$event->setDeathMessage($dm);
				$this->addKill($killer);
				$killer->addXp(1);
				$killer->setHealth(20);
				$this->addCredits($killer, 1);
				$killer->sendPopup("§l§e+2\n§l§9+1 Credit\n§l§b+20§c❤");
				EconomyAPI::getInstance()->addMoney($killer, 2);
				$this->stats->save();
			}
		}
	}
	
	//CHECKS CREDITS (FOR THE TASK)
	public function checkCredits(Player $player) {
		$credits = $this->getCredits($player);
		$tier = $this->getTier($player);
		if($credits >= 2000 and $credits < 5000) {
			if($tier !== "SHADOW") {
				$this->changeTier($player, "SHADOW");
				$player->addTitle("§aTier Unlocked", "§l§cSHADOW");
			}
		}
		if($credits >= 10000 and $credits < 15000){
			if($tier !== "LIGHT") {
				$this->changeTier($player, "LIGHT");
				$player->addTitle("§aTier Unlocked", "§l§8LIGHT");
			}
		}
		if($credits >= 15000 and $credits < 22000){
			if($tier !== "MOON") {
				$this->changeTier($player, "MOON");
				$player->addTitle("§aTier Unlocked", "§l§7MOON");
			}
		}
		if($credits >= 22000 and $credits < 30000){
			if($tier !== "SUN") {
				$this->changeTier($player, "SUN");
				$player->addTitle("§aTier Unlocked", "§l§9SUN");
			}
		}
		}
	}
}
