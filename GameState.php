<?php
class GameState
{
	public $id;
	public $turn;
	public $maxTurns;
	public $heroes;
	public $board;
	public $finished;
	public $hero;
	public $token;
	public $viewUrl;
	public $playUrl;
	public function  __construct() {
	}

	public function nextGameStates() {
		//256 positions to search each time (max), but limit branch factor to 4 (our directions, assume best direction for them)
		//find which spot is closer 
		$nextHeroes = array();
		foreach ($this->heroes as $id => $hero) {
			//$nextHeroes = 
		}
	}

	public function bestDirection($playerId) {
		//if near me
			//if less health they move away
			//if more health they move towards
			//if next to a tavern and < 90 stay
		//if not near
			//if low they move to heal
			//if
	}

	public function utility() {
		//gold -
		$greed_factor = .25;

		//health
		//death!
		$wuss_factor = .50;

		//environment
		$attack_factor = .25;

		
	}
}
