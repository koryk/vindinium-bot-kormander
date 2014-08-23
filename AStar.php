<?php

class AStar
{
	public $board;
	public $startPos;
	public $goalPos;
	private $closedSet;
	private $openSet;
	private $cameFrom;

	public $path;
	private $pathIndex;
	public function __construct($board, $startPos, $goalPos) {
		$this->board = $board;
		$this->startPos = $startPos;
		$this->goalPos = $goalPos;
		$this->openSet = array();
		$this->closedSet = array();
		$this->cameFrom = array();
		$this->addOpenStep($startPos);
		$this->pathIndex = 0;
		$this->path = $this->findPath();
		//echo "PATH IS";
		//print_r($this->path);
		//die();
	}

	public function findPath() {
		$g_score = array();
		$f_score = array();
		$startIndex = self::getKeyFromPos($this->startPos);
		$goalIndex = self::getKeyFromPos($this->goalPos);
		$g_score[$startIndex] = 0;
		$f_score[$startIndex] = $g_score[$startIndex] + $this->heuristic($this->startPos, $this->goalPos);
		while (count($this->openSet) > 0) {
			$current = $this->findLowestF($f_score);
			$currentIndex = self::getKeyFromPos($current);
			if (self::getKeyFromPos($this->goalPos) == $currentIndex) {
				return $this->reconstructPath($this->cameFrom, $this->goalPos);
			}
			unset($this->openSet[$currentIndex]);
			$this->closedSet[$currentIndex] = $current;

			$neighborNodes = $this->board->getAdjacentTiles($current);
			foreach ($neighborNodes as $neighbor) {
				$neighborIndex = self::getKeyFromPos($neighbor);
				if (isset($closedSet[$neighborIndex])) {
					continue;
				}
				$tentativeGScore = $g_score[$currentIndex] + $this->distBetween($current,$neighbor);
				if (!isset($this->openSet[$neighborIndex]) || (isset($g_score[$neighborIndex]) && $tentativeGScore < $g_score[$neighborIndex])) {
					if (isset($this->cameFrom[$neighborIndex])) continue;
					if (($neighbor->type == Tile::OBSTACLE || $neighbor->type == Tile::GOLD || $neighbor->type == Tile::GOLD_OWNED ||  $neighbor->type == Tile::TAVERN || $neighbor->type == Tile::PLAYER) && $goalIndex != $neighborIndex) {
						//echo "skipping neighbor " . $neighborIndex . "of type" . $neighbor->type;
						continue;
					}
					$this->cameFrom[$neighborIndex] = $current;
					$g_score[$neighborIndex] = $tentativeGScore;
					$f_score[$neighborIndex] = $g_score[$neighborIndex] + $this->heuristic($neighbor, $this->goalPos);
					if (!isset($this->openSet[$neighborIndex])) {
						$this->openSet[$neighborIndex] = $neighbor;
					}
				}
			}
		}
		echo "No route from " . $this->startPos . " to " . $this->goalPos;
		return array();
	}

	public function heuristic($us, $them) {
		$utility = AStar::manhattanDistance($us, $them);
		if ($them->passable == FALSE || ($them->type == Tile::GOLD_OWNED && $them->owner == $this->board->hero->id) || ($them->type == Tile::OBSTACLE) || ($them->type == Tile::TAVERN)) {
			return 10*$utility;
		}
		if ($them->type == Tile::PLAYER && $them->owner != $this->board->hero->id) {
			$hero = $this->board->heroes[$them->owner];
			if ($hero->life - $this->board->hero->life < 10) {
				$utility-=1/$utility;
			} else {
				$utility +=1/$utility;
			}
		}
		return $utility;
	}

	public function distBetween($a, $b) {
		$dist = AStar::manhattanDistance($a, $b);
		return $dist;
	}

	public function reconstructPath($cameFrom, $currentNode) {
		$stepList = array();
		$currentIndex = self::getKeyFromPos($currentNode);
		$i = 0;
		while (isset($cameFrom[$currentIndex])) {
			$i++;
			array_unshift($stepList, $currentNode);
			$currentNode = $cameFrom[$currentIndex];
			$currentIndex = self::getKeyFromPos($currentNode);
			if ($currentIndex == self::getKeyFromPos($this->startPos))
				break;
			//echo "current index is " . $currentIndex;
		}
		array_unshift($stepList, $currentNode);
		return $stepList;
	}

	public function findLowestF($f_score) {
		$lowestF = 10000;
		$lowestTile;
		foreach ($this->openSet as $key => $tile) {
			if (isset($f_score[$key]) && $f_score[$key] < $lowestF) {
				$lowestF = $f_score[$key];
				$lowestTile = $tile;
			}
		}
		return $lowestTile;
	}

	public static function manhattanDistance($start, $end) {
		if (!$start instanceof Tile || !$end instanceof Tile)
			throw new \Exception();
		$dx = abs($start->location->x - $end->location->x);
		$dy = abs($start->location->y - $end->location->y);
		return ($dx+$dy);
	}

	public static function trueDistance($start, $end, $state) {
		if (!$start instanceof Tile || !$end instanceof Tile)
			throw new \Exception();
		$aStar = new AStar($state->board, $start, $end);
		$length = count($aStar->path);
		if ($length == 0)
			$length = $state->board->size;
		return $length;
	}

	public function getNextStep($location) {
		foreach ($this->path as $index => $loc) {
			if (self::getKeyFromPos($location) == self::getKeyFromPos($loc)) {
				$this->pathIndex = $index;
			}
		}
		$this->pathIndex++;
		if (isset($this->path[$this->pathIndex])) {
			$tile = $this->path[$this->pathIndex];
			return $tile;
		} else {
			return FALSE;
		}
	}
	public function addOpenStep($position) {
		$this->openSet[self::getKeyFromPos($position)] = $position;
	}
	public function addClosedStep($position) {
		$this->closedSet[self::getKeyFromPos($position)] = $position;
	}
	public function addVisitedStep($position) {
		$this->cameFrom[self::getKeyFromPos($position)] = $position;
	}

	public static function getKeyFromPos($position) {
		if ($position instanceof Tile) {
			$position = $position->location;
		}
		if (!$position instanceof Position) {
			throw new Exception(print_r($position,TRUE));
		}
		return $position->x .",". $position->y;
	}
}
