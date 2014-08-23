<?php

class Board
{
	public $size;
	public $tiles;
	public $hero;
	public $heroes;
	public $state;
	public function __construct($size, $tiles, $hero, $heroes,$state) {
		$this->size = $size;
		$this->hero = $hero;
		$this->heroes = $heroes;
		$this->tiles = array();
		for ($y = 0; $y < $size; $y++) {
			for ($x = 0; $x < $size; $x++) {
				$str = substr($tiles,$y*$size*2+$x*2,2);
				echo ($str);
				if (!isset($this->tiles[$x])) {
					$this->tiles[$x] = array();
				}
				$this->tiles[$x][$y] = Tile::createTileFromString($str,$x,$y);
			}
			echo "\n";
		}
	}

	public function  __toString() {
		$retStr = "";
		foreach ($this->tiles as $x=>$row) {
			foreach ($row as $y=>$item) {
				$retStr .= "($x , $y)";
				if ($y == $this->hero->pos->x && $y == $this->hero->pos->y)
					$retStr .= "X";
				else
					$retStr .= $this->tiles[$x][$y]->type;
			}
			$retStr .= "\n";
		}
		return $retStr;
	}

	public function getGoldCounts() {
		//$goldTile = 
		//foreach ($
	}

	public function findClosestTileType($position, $type) {
		$closestTile = $position;
		$closestDist = $this->size*$this->size;
		if (!is_array($type)) {
			$type = array($type);
		}
		foreach ($this->tiles as $x=>$row) {
			foreach ($row as $y=>$tile) {
				$bool = FALSE;
				foreach ($type as $typ) {
					$bool = $bool || $tile->type == $typ;
				}
				if ($bool && $tile->owner != $this->hero->id) {
					$dist = AStar::trueDistance($this->loadTile($this->hero->pos), $tile, $this->state);
					if ($dist < $closestDist) {
						$closestTile = $tile;
						$closestDist = $dist;
					}
				}
			}
		}
		return $closestTile;
	}

	public function getMovableTiles($x, $y = FALSE) {
		$tiles = $this->getAdjacentTiles($x, $y);
		$retTiles = array();
		foreach ($tiles as $dir=>$tile) {
			if ($tile->passable && !($tile->type == Tile::GOLD_OWNED && $tile->owner == $this->hero->id) && !($tile->type == Tile::TAVERN && $this->hero->life < 80)) {
				$retTiles[$dir]=$tile;
			}
			else {
			}
		}
		return $retTiles;
	}

	public function getAdjacentTiles($x, $y = FALSE) {
		if ($y !== false) 
			$tile = $this->loadTile($x, $y); 
		else
			$tile = $this->loadTile($x, $y);
		$tiles = array();
		$x = $tile->location->x;
		$y = $tile->location->y;
		for ($dx = -1; $dx < 2; $dx++) {
			for ($dy = -1; $dy < 2; $dy++) {
				if ($dx == 0 xor $dy == 0) {
					if (!$this->onMap($x+$dx, $y+$dy))
						continue;
					$dir = "East";
					if ($dx == 1)
						$dir = "South";
					if ($dy == -1)
						$dir = "West";
					if ($dx == -1)
						$dir = "North";
					$tiles[$dir] = $this->loadTile($x+$dx,$y+$dy);
				}
			}
		}

		return $tiles;
	}

	public function onMap($x, $y) {
		return ($x >= 0 && $y >= 0 && $x < $this->size && $y < $this->size);
	}

	public function loadTile($x, $y = FALSE) {
		$tile = $x;
		if ($tile instanceof Position) {
			$tile = $this->tiles[$x->x][$x->y];
		} else if (!$tile instanceof Tile) {
			$tile = $this->tiles[$x][$y];
		}
		return $tile;
	}
}
