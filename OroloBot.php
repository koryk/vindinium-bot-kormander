<?php

class Bot
{

}

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
		$adjTiles = $this->board->getAdjacentTiles($this->board->loadTile($them));
		foreach ($adjTiles as $tile)
			if ($tile->type == Tile::TAVERN) {
				$utility-=1/($utility+1);
				break;
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

class OroloBot extends Bot
{
	public $state = NULL;
	public $lastMove = "North";
	public $currentGoal = "Gold";
	public $currentTarget = NULL;
	public $goalSetTurn = -10;
	public $setGoalInterval = 1;
	public $healThresholdLastSet = 0;
	public $healThreshold = 0;
	public $aStar = NULL;
	public function move($state)
	{
		$this->parseGameState($state);
		echo $state['viewUrl'] . "\n";
		//echo $this->state->board . "\n";
		$move = $this->get_next_move();
		echo "MOVING $move\n";
		return $move;
	}

	public function get_next_move() {
		//is there an emergency?
		if ($this->interruptGoalForDefense()){
			$this->currentGoal = "Defense";
			$this->currentTarget = $this->findGoalTarget();
			$this->aStar = new AStar($this->state->board, $this->state->board->loadTile($this->state->hero->pos),$this->currentTarget);
			$this->goalSetTurn = $this->state->turn;
		} else
		if ($this->interruptGoalForAttack()) {
			$this->currentGoal = "Attack";
			$this->currentTarget = $this->findGoalTarget();
			$this->aStar = new AStar($this->state->board, $this->state->board->loadTile($this->state->hero->pos),$this->currentTarget);
			$this->goalSetTurn = $this->state->turn;
		} else 
		if ($this->interruptGoalForHeal()) {
			$this->currentGoal = "Heal";
			$this->currentTarget = $this->findGoalTarget();
			$this->aStar = new AStar($this->state->board, $this->state->board->loadTile($this->state->hero->pos),$this->currentTarget);
			$this->goalSetTurn = $this->state->turn;
		} 	//else continue with plan
		return $this->getNextPlannedDirection();;
	}

	public function interruptGoalForHeal() {

		if ($this->state->hero->life < 90) {
			$closestTiles = $this->state->board->getMovableTiles($this->state->board->loadTile($this->state->hero->pos));
			foreach ($closestTiles as $them) {
				if ($them->type == Tile::TAVERN) {
					return TRUE;
				}
				if ($this->state->hero->life < 40) {
					$closerTiles = $this->state->board->getAdjacentTiles($this->state->board->loadTile($this->state->hero->pos));
					foreach ($closerTiles as $tile) {
						if ($them->type == Tile::TAVERN) {
							return TRUE;
						}
					}
				}
			}
			$closestTile = $this->findClosestTileType($this->state->board->loadTile($this->state->hero->pos), array(Tile::PLAYER));
			if ($this->state->hero->life < 20 && !(AStar::trueDistance($this->state->board->loadTile($this->state->hero->pos),$closestTile,$this->state) <= 2 && $this->state->heroes[$closestTile->owner]->life <= 20)) {
				return TRUE;
			}
		}
		return FALSE;
	}
	public function interruptGoalForAttack() {

		$us = $this->state->board->loadTile($this->state->hero->pos);
		$closestTiles = $this->state->board->getAdjacentTiles($this->state->board->loadTile($this->state->hero->pos));
		foreach ($closestTiles as $them) {
			if ($them->type == Tile::PLAYER) {
				return TRUE;
			}
		}
		$closestTiles = $this->findTilesByDistance($this->state->board->loadTile($this->state->hero->pos), array(Tile::PLAYER));
		foreach ($closestTiles as $them) {
			$dist = AStar::trueDistance($us, $them, $this->state);
			$ownAllGold = FALSE;
			$goldTiles = $this->findTilesByDistance($this->state->board->loadTile($this->state->hero->pos), array(Tile::GOLD_OWNED));
			$goldCount = 0;
			$myGoldCount = 0;
			$closestTavern = AStar::trueDistance($this->findClosestTileType($this->state->board->loadTile($them), Tile::TAVERN), $this->state->board->loadTile($them),$this->state);
			foreach ($goldTiles as $tile) {
				if ($tile->owner != $them->owner) {
				} else {
					$myGoldCount++;
				}
				$goldCount++;
			}
			$threshold = ($this->state->hero->life-$this->state->heroes[$them->owner]->life)/20 + 5*($myGoldCount/($goldCount+1));
			$case_one = (($dist < $threshold || ($dist == 2 && $closestTavern > 2)) && ($this->state->heroes[$them->owner]->life<=21 || $this->state->hero->life - $this->state->heroes[$them->owner]->life > 19));//
			$case_two = (($this->state->hero->life - $this->state->heroes[$them->owner]->life) >= -19 && $dist==3);
			if (($case_one || $case_two) && $this->state->heroes[$them->owner]->life <90) {
				echo "Attacking because Dist is " . $dist . " threshold is" . $threshold . " health is " . $this->state->heroes[$them->owner]->life ."\n";
				return TRUE;
			} else {
				echo "Not interrupting for attacking \n";
			}
		}
		return FALSE;
	}

	public function interruptGoalForDefense() {
		$defenseHpDifference= 19;
		$us = $this->state->board->loadTile($this->state->hero->pos);
		$closestTiles = $this->findTilesByDistance($this->state->board->loadTile($this->state->hero->pos), array(Tile::PLAYER));
		$ret = TRUE;
		foreach ($closestTiles as $them) {
			if ((AStar::trueDistance($us, $them, $this->state) <= 4 ) && ($this->state->hero->life - $this->state->heroes[$them->owner]->life) < ((100-$this->state->hero->life)/100)*20) {
				return TRUE;
			}
		}
		return FALSE;
	}

	public function canWinFight($enemyId) {
		
	}

	public function getNextPlannedDirection() {
		if ($this->state->turn > $this->goalSetTurn+$this->setGoalInterval || $this->aStar == NULL) {
			$this->goalSetTurn = $this->state->turn;
			$this->createGoal();
		}
		$tile = $this->aStar->getNextStep($this->state->board->loadTile($this->state->board->hero->pos));
		echo "current goal: \n" ;
		echo ($this->currentGoal);
		echo "\n current target: \n" ;
		echo ($this->currentTarget);
		echo "\n\n";
		echo "current position: \n" ;
		echo ($this->state->hero->pos);
		echo "\n\n";
		echo "Next step: \n";
		echo ($tile);
		if ($tile == FALSE) {
			$this->goalSetTurn = $this->state->turn;
			$this->currentGoal = "Attack";
			$this->createGoal();
			$tile = $this->aStar->getNextStep($this->state->board->loadTile($this->state->board->hero->pos));
			if ($tile == FALSE) return "Stay";
		}
		return $this->getDirBetween($this->state->board->loadTile($this->state->hero->pos), $tile);
	}

	public function getDirBetween($start, $end) {

		$dx = $start->location->x - $end->location->x;
		$dy = $start->location->y - $end->location->y;
		$dir = "Stay";
		if ($dy == -1)
			$dir = "South";
		if ($dx == 1)
			$dir = "West";
		if ($dy == 1)
			$dir = "North";
		if ($dx == -1)
			$dir = "East";

		echo "\n".$dir ."\n";
		return $dir;
	}

	public function createGoal() {
		$this->currentGoal = "Gold";
		$heroTile = $this->state->board->loadTile($this->state->hero->pos);
		$distance = AStar::trueDistance($heroTile, $this->findClosestTileType($this->state->hero->pos, Tile::TAVERN), $this->state);
		$nearestEnemy = $this->findClosestTileType($heroTile, array(Tile::PLAYER));
		$nearestTavern = $this->findClosestTileType($heroTile, array(Tile::TAVERN));
		if ($this->state->turn > $this->healThresholdLastSet + 12) {
			$healThreshold = (AStar::trueDistance($nearestTavern, $nearestEnemy, $this->state) < AStar::trueDistance($heroTile, $nearestTavern, $this->state) || AStar::trueDistance($heroTile, $nearestEnemy, $this->state) < AStar::trueDistance($heroTile, $nearestTavern, $this->state))? 50 : 21;
			$this->healThreshold = $healThreshold;
			$this->healThresholdLastSet = $this->state->turn;
		}
		if ($this->state->hero->life < $this->healThreshold) {
			$this->currentGoal = "Heal";
		} else if ($this->state->hero->life < 80) {
			if ($distance < 3) {
				$this->currentGoal = "Heal";
			}

		} else if ($this->state->hero->life < 51) {
			if ($distance <= 2) {
				$this->currentGoal = "Heal";
			}
		}

		$this->currentTarget = $this->findGoalTarget();
		if ($this->currentGoal == "Gold") {
			$ownAllGold = FALSE;
			$goldTiles = $this->findTilesByDistance($this->state->board->loadTile($this->state->hero->pos), array(Tile::GOLD_OWNED));
			$goldCount = 0;
			$myGoldCount = 0;
			foreach ($goldTiles as $tile) {
				if ($tile->owner != $this->state->hero->id) {
				} else {
					$myGoldCount++;
				}
				$goldCount++;
				$ownAllGold = ($myGoldCount/$goldCount) > .63;
			}
			if ($ownAllGold) {
				echo "OWN THE GOLD\n";
				$this->currentGoal = "Heal";
				$this->currentTarget = $this->findGoalTarget();
			}
		}
		$this->aStar = new AStar($this->state->board, $this->state->board->loadTile($this->state->hero->pos),$this->currentTarget);
	}

	//add true distance for calcuating stuff
	public function findGoalTarget() {
		$closestTile = $this->state->board->loadTile($this->state->hero->pos);
		if ($this->currentGoal == "Gold") {
			$closestTile = $this->findClosestTileType($this->state->board->loadTile($this->state->hero->pos), array(Tile::GOLD, Tile::GOLD_OWNED));
		} else if ($this->currentGoal == "Heal") {
			$tavernTiles = $this->findTilesByDistance($this->state->board->loadTile($this->state->hero->pos), array(Tile::TAVERN));
			$tavernTile = $this->findClosestTileType($this->state->board->loadTile($this->state->hero->pos), array(Tile::TAVERN));
			$tileWeight = array();
			foreach ($tavernTiles as $tavernKey => $tavern) {
				$aStar = new AStar($this->state->board, $this->state->board->loadTile($this->state->hero->pos),$this->state->board->loadTile($tavern));
				if (count($aStar->path) == 0) {
					//change it!
					continue;
				}
				$tileWeight[$tavernKey] = count($aStar->path);
				foreach ($aStar->path as $tile) {
					foreach ($this->state->board->getAdjacentTiles($tile) as $enemyAdjKey => $nearby) {
						if ($nearby->type == Tile::PLAYER && $nearby->owner != $this->state->hero->id) {
							$tileWeight[$tavernKey] += .4;
						}
					}
				}
			}
			$returnTiles = array();
			foreach ($tileWeight as $key => $distance) {
				$returnTiles[$key] = $tavernTiles[$key];
			}
			$returnTiles[]=$this->state->board->loadTile($this->state->hero->pos);
			$closestTile = reset($returnTiles);
		} else if ($this->currentGoal == "Attack") {
			//get baddy hp
			//prioritize 
			//are there any baddies there? stay
			//get adjacent adjacent tiles (not any of the ones already looked at)
			$heroTile = $this->state->board->loadTile($this->state->hero->pos);
			$closestTile = $this->findClosestTileType($heroTile, array(Tile::PLAYER));
			$dist = AStar::trueDistance($heroTile, $closestTile, $this->state);
			if ($dist == 2) {
				foreach ($this->state->board->getAdjacentTiles($heroTile) as $tile) {
					if ($tile->type == Tile::TAVERN && $this->state->hero->life < 70) {
						$closestTile = $tile;
						break;
					}
				}
			}
		/*	$dist = AStar::trueDistance($heroTile, $closestTile, $this->state);
			if ($dist < 3) {
				//get adjacent tiles
				$closestTiles = $this->state->board->getMovableTiles($heroTile);
				$adjWeight = array();
				foreach ($closestTiles as $key=>$tile) {
					$adjWeight[$key] = 0;
					if ($tile->type == Tile::PLAYER && $tyle->owner != $this->state->hero->id) {
						$adjWeight[$key] -= 10;
					}
					$closerTiles = $this->state->board->getAdjacentTiles($tile);
					foreach ($closerTiles as $tileKey => $nearby) {
						if ($nearby->type == Tile::TAVERN) {
							$adjWeight[$key] += 20;
						}
					}
					$enemyTiles = $this->findTilesByDistance($this->state->board->loadTile($this->state->hero->pos), array(Tile::PLAYER));
					foreach ($enemyTiles as $enemykey=>$enemyTile) {
						$dist = AStar::trueDistance($tile,$enemyTile,$this->state);
						$adjWeight[$key] -= 2/($dist+1);
						foreach ($this->state->board->getAdjacentTiles($enemyTile) as $enemyAdjKey => $nearby) {
							if ($nearby->type == Tile::TAVERN) {
								$adjWeight[$key] += 20;
								break;
							}
						}
					}

				//	$nearbyTiles = $this->get
				}
				asort($adjWeight);
				$returnTiles = array();
				foreach ($adjWeight as $key => $distance) {
					$returnTiles[$key] = $closestTiles[$key];
				}
				$closestTile = reset($returnTiles);
				if (!$closestTile instanceof Tile) {
					$closestTile = $heroTile;
				}
			}
			*/
		} else if ($this->currentGoal == "Defense") {
			$heroTile = $this->state->board->loadTile($this->state->hero->pos);
			$closeTiles = $this->state->board->getAdjacentTiles($heroTile);
			//check distance from each adjacent tile to each enemy
			//take lowest - could also stay if adjacent to a tavern
			$adjWeight = array();
			foreach ($closeTiles as $key => $tile) {
				$adjWeight[$key] = 0;
				if ($tile->type == Tile::TAVERN && $this->state->hero->life < 70) {
					$adjWeight[$key] -= 10;
				}
			}

			$closestTiles = $this->state->board->getMovableTiles($heroTile);
			if (count($closestTiles)<=1) {
				$this->currentGoal = "Attack";
				return $this->findGoalTarget();
			}
			foreach ($closestTiles as $key=>$tile) {
				if (!isset($adjWeight[$key])) {
					$adjWeight[$key] = 0;
				}
				if ($tile->type == Tile::PLAYER && $tile->owner != $this->state->hero->id && $this->state->hero->life < $this->state->heroes[$tile->owner]->life) {
					$adjWeight[$key] += 10;
				}
				$movableTiles = $this->state->board->getMovableTiles($heroTile);
				if (count($movableTiles) <= 2) {
					$adjWeight[$key] += 8;
				}
				$enemyTiles = $this->findTilesByDistance($this->state->board->loadTile($this->state->hero->pos), array(Tile::PLAYER));
				foreach ($enemyTiles as $enemykey=>$enemyTile) {
					$dist = AStar::trueDistance($tile,$enemyTile,$this->state);
					$closeEnemyTiles = $this->state->board->getAdjacentTiles($enemyTile);
					foreach ($closeEnemyTiles as $closeEnemyKey=>$closeEnemyTile) {
						if ($closeEnemyTile->type == Tile::TAVERN) {
							$adjWeight[$key] += 1;
						}
					}
					$adjWeight[$key] += 2/($dist+1);
				}
				$tavernTiles = $this->findTilesByDistance($this->state->board->loadTile($this->state->hero->pos), array(Tile::TAVERN));
				foreach ($tavernTiles as $tileKey=>$tavernTile) {
					$dist = AStar::trueDistance($tile,$tavernTile,$this->state);
					$adjWeight[$key] -= 5/($dist+1);
				}
			}
			foreach ($closeTiles as $key=>$tile) {
				$closestTiles[$key] = $tile;
			}
			asort($adjWeight);
			$returnTiles = array();
			foreach ($adjWeight as $key => $distance) {
				$returnTiles[$key] = $closestTiles[$key];
			}
			$closestTile = reset($returnTiles);
		}
		return $closestTile;
	}


	public function findTilesByDistance($position, $type) {
		$closestTiles = array();
		$tileDistances = array();
		$closestDist = $this->state->board->size*$this->state->board->size;
		if (!is_array($type)) {
			$type = array($type);
		}
		foreach ($this->state->board->tiles as $x=>$row) {
			foreach ($row as $y=>$tile) {
				$bool = FALSE;
				foreach ($type as $typ) {
					$bool = $bool || $tile->type == $typ;
				}
				if ($bool && $tile->owner != $this->state->hero->id) {
					$dist = AStar::trueDistance($this->state->board->loadTile($this->state->hero->pos), $tile, $this->state);
					$closestTiles[$x.",".$y] = $tile;
					$tileDistances[$x.",".$y] = $dist;
				}
			}
			
		}
		asort($tileDistances);
		$returnTiles = array();
		foreach ($tileDistances as $key => $distance) {
			$returnTiles[$key] = $closestTiles[$key];
		}
		return $returnTiles;
	}

	public function findClosestTileType($position, $type) {
		$closestTile = $position;
		$closestDist = $this->state->board->size*$this->state->board->size;
		if (!is_array($type)) {
			$type = array($type);
		}
		foreach ($this->state->board->tiles as $x=>$row) {
			foreach ($row as $y=>$tile) {
				$bool = FALSE;
				foreach ($type as $typ) {
					$bool = $bool || $tile->type == $typ;
				}
				if ($bool && $tile->owner != $this->state->hero->id) {
					$dist = AStar::trueDistance($this->state->board->loadTile($this->state->hero->pos), $tile, $this->state);
					if ($dist < $closestDist) {
						$closestTile = $tile;
						$closestDist = $dist;
					}
				}
			}
		}
		return $closestTile;
	}


	public function getMoveDirection() {
		$maxdir = "North";
		$maxscore = -200;

		print_r($maxdir);
		return $maxdir;
	}

	public function parseGameState($state) {
		if ($this->state == NULL) {
			$this->state = new GameState();
		}
		$game = $state["game"];
		$this->state->hero = Hero::createHero($state["hero"]);
		foreach ($game as $key=>$value) {
			if ($key == "heroes") {
				if (!is_array($this->state->heroes)) {
					$this->state->heroes = array();
				}
				foreach ($game["heroes"] as $hero) {
					$hero = Hero::createHero($hero);
					$this->state->heroes[$hero->id] = $hero;
				}
				continue;
			} else if ($key == "board") {
				$this->state->board = new Board($game["board"]["size"], $game["board"]["tiles"], $this->state->hero, $this->state->heroes);
				continue;
			} else if ($key == "hero") {
				continue;
			}
			try {
				$this->state->$key=$value;
			} catch (Exception $e) {
				print_r($e);
			}
		}
	}
}

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
}

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

class Position
{
	public $x;
	public $y;
	public function __construct($x, $y) {
		$this->x = $x;
		$this->y = $y;
	}
	public function __toString() {
		return $this->x . ", " . $this->y;
	}
}

class Board
{
	public $size;
	public $tiles;
	public $hero;
	public $heroes;
	public function __construct($size, $tiles, $hero, $heroes) {
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

	public function weighTile($x, $y = FALSE, $depth = 0, $no_dir = FALSE) {
		$weight = 0;
		$tile = $this->loadTile($x, $y);
		$init_tiles = $this->getMovableTiles($tile);
		$outer_tiles = array();
		if ($depth > 0) {
			foreach ($init_tiles as $dir => $tile) {
				if ($no_dir !== $dir) continue;
				if ($no_dir !== FALSE) {
					if (($dir == "North" && $no_dir == "South") || ($dir == "South" && $no_dir == "North") || ($dir == "East" && $no_dir == "West") || ($dir == "West" && $no_dir == "East"))
						continue;
				}
				$weight += $this->weighTile($tile, FALSE, $depth-1, $no_dir);
			}
		}
		if ($tile->type == Tile::GOLD || $tile->type == Tile::GOLD_OWNED) {
			if ($tile->owner != $this->hero->id) {
				//echo "gold!";
				if ($this->hero->life <= 20) {
					$weight += 100;
				} else 
				$weight -= 70*$depth;
			}
		}
		if ($this->hero->life < 75 && $tile->type == Tile::TAVERN) {
			$weight -= 10*$depth;
		}

		if ($this->hero->life < 30 && $tile->type == Tile::TAVERN) {
			$weight -= 130*$depth;
		}
		if ($this->hero->life > 80 && $tile->type == Tile::TAVERN) {
			$weight += 20*$depth;
		}
		foreach ($init_tiles as $tile) {
			$outer_tiles = $this->getMovableTiles($tile->location->x, $tile->location->y);
			//echo "(". $tile->location->x , "," .$tile->location->y . ") ";
			if ($tile->type == Tile::PLAYER && $tile->owner != $this->hero->id) {
				//echo "p";
				$weight++;
				foreach ($outer_tiles as $outer_tile) {
					if ($outer_tile->type == Tile::TAVERN) {
						//echo " player with a tavern";
						$weight++;
					}
				}
			}
			if ($tile->type == Tile::GOLD || $tile->type == Tile::GOLD_OWNED) {
				if ($tile->owner != $this->hero->id) {
						//echo "g";
					//echo " gold nearby ";
					$weight -= 4;
				}
			}
			if ($tile->passable) {
				$weight -= 1*$depth;
				foreach ($outer_tiles as $outer_tile) {
					if (($outer_tile->type == Tile::GOLD_OWNED || $outer_tile->type == Tile::GOLD) && $outer_tile->owner != $this->hero->id) {
						//echo "g";
						//echo " near not my gold ";
						$weight-=8*$depth;
					}
				}
			}
			if ($tile->type != Tile::PLAYER && !$tile->passable) {
				//echo "not passable ";
						//echo "o";
				$weight+=3;
			}
			if ($tile->type == Tile::TAVERN) {
				if ($this->hero->life <= 50) {
					//echo "near tavern low on life";
						//echo "t";
					$weight-=4*$depth;
				} else {
						//echo "t";
					$weight -= 2*$depth;
				}
			}
			if ($this->hero->life <= 75) {
				foreach ($outer_tiles as $outer_tile) {
					if ($outer_tile->type == Tile::TAVERN) {
						//echo "t";
						//echo "near tavern low on life";
						$weight-=2;;
					}
				}
			}
		}
		//echo " " . $weight;
		return $weight;
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
		$retTiles["Stay"] = $this->loadTile($x,$y);
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
