<?php

namespace AEDXDEV\FistX\Game;

use AEDXDEV\FistX\Main;
use pocketmine\scheduler\Task;

class GameTask extends Task {
	
	/** @var Main */
	private $plugin;
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}
	
	public function onRun(): void{
		foreach ($this->plugin->getGames() as $name => $game){
			$game->tick();
		}
	}
}
