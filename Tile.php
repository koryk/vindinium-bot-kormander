<?php
class Tile
{
	const GOLD = 1;
	const GOLD_OWNED = 2;
	const TILE_EMPTY = 3;
	const SPAWN_POINT = 4;
	const TAVERN = 5;
	const PLAYER = 6;
	const OBSTACLE = 7;

	public $location;
	public $occupied;
	public $passable;
	public $type;
	public $owner;

	public function __construct($type, $x, $y, $passable = TRUE, $occupied = FALSE, $owner=FALSE) {
		$this->location = new Position($x, $y);
		$this->type = $type;
		$this->passable = $passable;
		$this->occupied = $occupied;
		$this->owner = $owner;
	}

	public function __toString() {
		return $this->location->__toString();
	}

	public static function createTileFromString($charString, $x, $y) {

		$type = -1;
		$passable = FALSE;
		$occupied = FALSE;
		$owner = FALSE;

		if ($charString == "[]") {
			$type = self::TAVERN;
			$passable = TRUE;
		} else if ($charString == "$-") {
			$type =self::GOLD;
			$passable = 1;
		} else if (preg_match('/\$(\d)/',$charString,$matches)) {
			$type = self::GOLD_OWNED;
			$occupied = TRUE;
			$passable = 1;
			$owner = $matches[1];
		} else if (preg_match('/@(\d)/',$charString,$matches)) {
			$type = self::PLAYER;
			$occupied = TRUE;
			$owner = $matches[1];
		} else if ($charString == "  ") {
			$type = self::TILE_EMPTY;
			$passable = TRUE;
		} else if ($charString == "##"){
			$type = self::OBSTACLE;
		} else {
			print_r("Couldn't parse . $charString\n"."passable is " . print_r($passable) );
		}

		return new Tile($type, $x, $y, $passable, $occupied, $owner);
	}

}
