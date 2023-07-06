<?php

namespace AEDXDEV\FistX;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\plugin\PluginOwned;
use pocketmine\world\Position;
use pocketmine\entity\Location;
use pocketmine\utils\Config;
use pocketmine\player\Player;

use AEDXDEV\FistX\Main;

class FistXCommand extends Command implements PluginOwned{
	/** @var Main */
	public $plugin;
	
	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		parent::__construct("fist", "FIST Commands");
		$this->setPermission("fistx.player");
	}
	
	public function execute(CommandSender $sender, string $label, array $args) {
		if(!isset($args[1])){
			$sender->sendMessage("§cUsage: /" . $label . " help");
			return false;
		}
		switch ($args[0]){
			case "help":
				$sender->sendMessage("§e========================");
				if($sender->hasPermission("fistx.admin")){
					$sender->sendMessage("§a- /" . $label . " create");
					$sender->sendMessage("§a- /" . $label . " delete");
					$sender->sendMessage("§a- /" . $label . " setlobby");
					$sender->sendMessage("§a- /" . $label . " setrespawn");
					$sender->sendMessage("§a- /" . $label . " manage");
					$sender->sendMessage("§a- /" . $label . " list");
				}
				$sender->sendMessage("§a- /" . $label . " join");
				$sender->sendMessage("§a- /" . $label . " quit");
				$sender->sendMessage("§e========================");
			break;
			case "create":
			  if(!$sender instanceof Player){
			    $sender->sendMessage("run command in-game only");
			    return false;
			  }
				if(!$sender->hasPermission("fistx.admin"))return false;
				if(!isset($args[2])){
					$sender->sendMessage("§cUsage: /" . $label . " create <GameName>");
					return false;
				}
				$world = $sender->getWorld();
				if($world == $this->plugin->getServer()->getWorldManager()->getDefaultWorld()){
					$sender->sendMessage("§cYou can\'t create game in default world!");
					return false;
				}
				foreach ($this->plugin->games as $name => $game) {
          if ($game->getWorld() == $world) {
            $sender->sendMessage("§cGame already exist!");
            return false;
          }
				}
				if($this->plugin->GameManager->addGame($args[2], $world)){
					$sender->sendMessage("§eGame created!");
					return true;
				}
			break;
			case "setlobby":
			  if(!$sender instanceof Player){
			    $sender->sendMessage("run command in-game only");
			    return false;
			  }
				if(!$sender->hasPermission("fistx.admin"))return false;
				$world = $sender->getWorld();
				$game = null;
				$name = null;
				foreach ($this->plugin->getGames() as $aname => $agame){
					if($agame->getWorld() == $world){
						$name = $aname;
						$game = $agame;
					}
				}
				if($name == null){
					$sender->sendMessage("§cGame not exist, try create Usage: /" . $label . " create");
					return false;
				}
				$lobby = floor($sender->getPosition()->x) . ":" . floor($sender->getPosition()->y) . ":" . floor($sender->getPosition()->z) . ":" . $sender->getLocation()->yaw . ":" . $sender->getLocation()->pitch;
				$this->plugin->GameManager->setLobby($name, $lobby);
				$sender->sendMessage("§aLobby has been set!");
			break;
			case "setrespawn":
			  if(!$sender instanceof Player){
			    $sender->sendMessage("run command in-game only");
			    return false;
			  }
				if(!$sender->hasPermission("fistx.admin"))return false;
				$world = $sender->getWorld();
				$game = null;
				$name = null;
				foreach ($this->plugin->getGames() as $aname => $agame){
					if($agame->getWorld() == $world){
						$name = $aname;
						$game = $agame;
					}
				}
				if($name == null){
					$sender->sendMessage("§cGame not exist, try create Usage: /" . $label . " create");
					return false;
				} 
				$respawn = floor($sender->getPosition()->x) . ":" . floor($sender->getPosition()->y) . ":" . floor($sender->getPosition()->z) . ":" . $sender->getLocation()->yaw . ":" . $sender->getLocation()->pitch;
				$this->plugin->GameManager->setRespawn($name, $respawn);
				$sender->sendMessage("§aRespawn has been set!");
			break;
			case "delete":
				if(!$sender->hasPermission("fistx.admin"))return false;
				if(!isset($args[2])){
					$sender->sendMessage("§cUsage: /" . $label . " delete <GameName>");
					return false;
				}
				if(!isset($this->plugin->games[$args[2]])){
					$sender->sendMessage("§cGame not exist");
					return false;
				}
				if($this->plugin->GameManager->deleteGame($args[2])){
					$sender->sendMessage("§aGame deleted!");
					return true;
				}
			break;
			case "list":
				if(!$sender->hasPermission("fist.admin"))return false;
				if(count($this->plugin->getGames == 0)) {
				  $sender->sendMessage("§cNo Game Exist");
				  return false;
				}
				$sender->sendMessage("§aGames:");
				foreach ($this->plugin->getGames() as $name => $game){
				  $sender->sendMessage("§e- " . $name . " => " . count($game->getPlayers()));
				}
				return true;
			break;
			case "join":
			  if (!$sender instanceof Player) return false;
			  $this->plugin->JoinForm($sender);
				if(isset($args[1])){
				  foreach ($this->plugin->getGames() as $name => $game){
				    if ($name == $args[1]) {
				      $this->plugin->GameManager->JoinGame($sender, $args[1]);
				    } else {
				      $sender->sendMessage("§cGame not exist");
				    }
				  }
				}
			break;
			case "quit":
			  if (!$sender instanceof Player) return false;
			  foreach ($this->plugin->getGames() as $name => $game){
			    if (in_array($sender->getName(), $game->getPlayers())) {
			      $game->QuitPlayer($sender);
			    } else {
				  	$sender->sendMessage("You are not in a game!");
				  	return false;
			    }
			  }
			break;
			case "manage":
			  if (!$sender instanceof Player) return false;
			  $this->plugin->ManageForm($sender);
			break;
		}
	}
	
	public function getOwningPlugin() : Main{
		return $this->plugin;
	}
}
