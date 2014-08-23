<?php

require_once ('AStar.php');
require_once ('GameState.php');
require_once ('Hero.php');
require_once ('Position.php');
require_once ('Board.php');
require_once ('Tile.php');

class OroloBot
{
	public $state = NULL;
	public $lastMove = "North";
	public $currentGoal = "Gold";
	public $currentTarget = NULL;
	public $goalSetTurn = -10;
	public $setGoalInterval = 1;
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
		if ($this->interruptGoalForHeal()) {
			$this->currentGoal = "Heal";
			$this->currentTarget = $this->findGoalTarget();
			$this->aStar = new AStar($this->state->board, $this->state->board->loadTile($this->state->hero->pos),$this->currentTarget);
			$this->goalSetTurn = $this->state->turn;
		} else if ($this->interruptGoalForAttack()) {
			$this->currentGoal = "Attack";
			$this->currentTarget = $this->findGoalTarget();
			$this->aStar = new AStar($this->state->board, $this->state->board->loadTile($this->state->hero->pos),$this->currentTarget);
			$this->goalSetTurn = $this->state->turn;
		} elseif ($this->interruptGoalForDefense()){
			$this->currentGoal = "Defense";
			$this->currentTarget = $this->findGoalTarget();
			$this->aStar = new AStar($this->state->board, $this->state->board->loadTile($this->state->hero->pos),$this->currentTarget);
			$this->goalSetTurn = $this->state->turn;
		}

		//do A* search with depth limit, heuristic should be gold mines



		return $this->getNextPlannedDirection();;
	}

	public function interruptGoalForHeal() {

		if ($this->state->hero->life < 39) {
			$closestTiles = $this->state->board->getMovableTiles($this->state->board->loadTile($this->state->hero->pos));
			foreach ($closestTiles as $them) {
				if ($them->type == Tile::TAVERN) {
					return TRUE;
				}
				$closerTiles = $this->state->board->getAdjacentTiles($this->state->board->loadTile($this->state->hero->pos));
				foreach ($closerTiles as $tile) {
					if ($them->type == Tile::TAVERN) {
						return TRUE;
					}
				}
			}
			if ($this->state->hero->life < 20) {
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
			$threshold = ($this->state->hero->life-$this->state->heroes[$them->owner]->life)/10;
			if ($dist < $threshold || ($them->owner < $this->state->hero->id && $dist < 2)) {
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
			if ((AStar::trueDistance($us, $them, $this->state) == 3 ) && $this->state->hero->life - $this->state->heroes[$them->owner]->life > 19) {
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
		if ($tile == FALSE) return "Stay";
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
		$distance = AStar::trueDistance($heroTile, $this->state->board->findClosestTileType($this->state->hero->pos, Tile::TAVERN), $this->state);
		$nearestEnemy = $this->state->board->findClosestTileType($heroTile, array(Tile::PLAYER));
		$nearestTavern = $this->state->board->findClosestTileType($heroTile, array(Tile::TAVERN));
		$healThreshold = (AStar::trueDistance($heroTile, $nearestEnemy, $this->state) > AStar::trueDistance($heroTile, $nearestTavern, $this->state))? 50 : 20;
		if ($this->state->hero->life < $healThreshold) {
			$this->currentGoal = "Heal";
		} else if ($this->state->hero->life < 60) {
			if ($distance < 4) {
				$this->currentGoal = "Heal";
			}

		} else if ($this->state->hero->life < 90) {
			if ($distance < 2) {
				$this->currentGoal = "Heal";
			}
		}

		$this->currentTarget = $this->findGoalTarget();
		if ($this->currentGoal == "Gold") {
			$ownAllGold = TRUE;
			$goldTiles = $this->findTilesByDistance($this->state->board->loadTile($this->state->hero->pos), array(Tile::GOLD,Tile::GOLD_OWNED));
			foreach ($goldTiles as $tile) {
				if ($tile->owner != $this->state->hero->id) {
					$ownAllGold = FALSE;
					break;
				}
			}
			if ($ownAllGold) {
				$this->currentGoal = "Attack";
				$this->currentTarget = $this->findGoalTarget();
			}
		}
		$this->aStar = new AStar($this->state->board, $this->state->board->loadTile($this->state->hero->pos),$this->currentTarget);
	}

	//add true distance for calcuating stuff
	public function findGoalTarget() {
		$closestTile = $this->state->board->loadTile($this->state->hero->pos);
		if ($this->currentGoal == "Gold") {
			$closestTile = $this->state->board->findClosestTileType($this->state->board->loadTile($this->state->hero->pos), array(Tile::GOLD, Tile::GOLD_OWNED));
		} else if ($this->currentGoal == "Heal") {
			$tavernTiles = $this->findTilesByDistance($this->state->board->loadTile($this->state->hero->pos), array(Tile::TAVERN));
			$tavernTile = $this->state->board->findClosestTileType($this->state->board->loadTile($this->state->hero->pos), array(Tile::TAVERN));
			$tileWeight = array();
			foreach ($tavernTile as $tavernKey => $tavern) {
				$aStar = new AStar($this->state->board, $this->state->board->loadTile($this->state->hero->pos),$this->state->board->loadTile($tavern));
				if (count($aStar->path) == 0) {
					continue;
				}
				$tileWeight[$tavernKey] = count($aStar->path);
				foreach ($aStar->path as $tile) {
					foreach ($this->state->board->getAdjacentTiles($tile) as $enemyAdjKey => $nearby) {
						if ($tile->type == Tile::PLAYER && $tile->owner != $this->state->hero->id) {
							$tileWeight[$tavernKey] += 2;
						}
					}
				}
			}
			return $tavernTile;
		} else if ($this->currentGoal == "Attack") {
			//get baddy hp
			//prioritize 
			//are there any baddies there? stay
			//get adjacent adjacent tiles (not any of the ones already looked at)
			$heroTile = $this->state->board->loadTile($this->state->hero->pos);
			$closestTile = $this->state->board->findClosestTileType($heroTile, array(Tile::PLAYER));
			$dist = AStar::trueDistance($heroTile, $closestTile, $this->state);
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
		} else if ($this->currentGoal == "Defense") {
			$heroTile = $this->state->board->loadTile($this->state->hero->pos);
			$closestTiles = $this->state->board->getMovableTiles($heroTile);
			if (empty($closestTiles)) {
				$this->currentGoal = "Attack";
				return $this->findGoalTarget();
			}
			//check distance from each adjacent tile to each enemy
			//take lowest - could also stay if adjacent to a tavern
			$adjWeight = array();
			foreach ($closestTiles as $key=>$tile) {
				$adjWeight[$key] = 0;
				if ($tile->type == Tile::PLAYER && $tile->owner != $this->state->hero->id && $this->state->hero->life < $this->state->heroes[$tile->owner]->life) {
					$adjWeight[$key] += 10;
				}
				$enemyTiles = $this->findTilesByDistance($this->state->board->loadTile($this->state->hero->pos), array(Tile::PLAYER));
				foreach ($enemyTiles as $enemykey=>$enemyTile) {
					$dist = AStar::trueDistance($tile,$enemyTile,$this->state);
					$adjWeight[$key] += 2/($dist+1);
				}
				$tavernTiles = $this->findTilesByDistance($this->state->board->loadTile($this->state->hero->pos), array(Tile::TAVERN));
				foreach ($tavernTiles as $tileKey=>$tavernTile) {
					$dist = AStar::trueDistance($tile,$tavernTile,$this->state);
					$adjWeight[$key] -= 2/($dist+1);
				}
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
				$this->state->board = new Board($game["board"]["size"], $game["board"]["tiles"], $this->state->hero, $this->state->heroes, $this->state);
				continue;
			} else if ($key == "hero") {
				continue;
			}
			try {
				$this->state->$key=$value;
			} catch (Exception $e) {
				Logger::log(print_r($e,TRUE));
			}
		}
	}
}

