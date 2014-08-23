<?php

class Hero
{
	public $id;
	public $name;
	public $userId;
	public $elo;
	public $pos;
	public $life;
	public $gold;
	public $mineCount;
	public $spawnPos;
	public $crashed;

	public static function createHero($hero) {
		$returnHero = new Hero();
		foreach ($hero as $key=>$value) {
			if ($key == "pos") {
				$returnHero->pos = new Position($hero["pos"]["y"],$hero["pos"]["x"]);
				continue;
			} else if ($key == "spawnPos") {
				$returnHero->spawnPos = new Position($hero["spawnPos"]["x"],$hero["spawnPos"]["y"]);
				continue;
			}
			try {
				$returnHero->$key=$value;
			} catch (Exception $e) {
				print_r($e);
			}
		}
		return $returnHero;
	}
}
