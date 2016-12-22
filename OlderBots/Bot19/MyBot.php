<?php

// TO DO: FOR ALL THE BLOCKS THAT I CAN HELP (CAUSE THEY ARE THE SAME DISTANCE TO EDGE)
// GO TO GREATEST VALUE TARGET


// TODO PRIORITY: VALUE OF TAKING A BLOCK SHOULD BE DEFINED AS 4 STEPS, WHILE UNDER 50 blocks, 3 STEPS UNDER 200 blocks, 2 STEPS under 500 blocks and 1 for the restore_error_handler

// TODO: DEFINITELY RUSH TO HIGH POP AREA

// TODO: If EXCESS VALUE CHOOSE PATH WITH GREATEST VALUE 
// (NOT SINGLE BLOCK WITH GREATEST VALUE)

// TODO: WHILE UNDER 20 BLOCKS
// CHECK ALL BLOCKS FOR GOAL THATS DOABLE
// START ON GOAL

error_reporting(E_ALL);

require_once 'hlt.php';
require_once 'networking.php';

list($myID, $gameMap) = getInit();
sendInit('Bot19');




// block structure
class Block {
	public $location;
	public $site;
	
	public $isBorder; // if it has a adjacent spare block
	public $plan;

	public $move;
	public $locked;
	public $assistRequests;
	
	public function __construct($location, $site)
	{
		$this->location = $location;
		$this->site = $site;
		$this->move = 0;
		$this->assistRequests = array();
		$this->locked = false;
		$this->isBorder = false;
		$this->isFrontLine = false;
	}
	
	public function getPlanLocation()
	{
		global $gameMap;
		return $gameMap->getLocation($this->location, $this->plan);
	}
}

function getBlockAtLocation($location)
{
	global $myBlocks;
	for($i = 0; $i < count($myBlocks); $i++)
	{
		if ($myBlock->location->isSame($location))
		{
			return $i;
		}
	}
	return -1;
}


class AssistRequest {
	public $value; // value of plan
	public $blocksContributing; // List of blocks involved (so they can be purged if required)
	public $plan; // location of place to move
	public function __construct($value,$blocksContributing,$plan)
	{
		$this->value = $value;
		$this->blocksContributing = $blocksContributing;
		$this->plan = $plan;
		
	}
	
	public function stillValid()
	{
		global $myBlocks;
		
		foreach ($this->blocksContributing as $index)
		{
			if ($myBlocks[$index]->locked || $myBlocks[$index]->move != 0)
			{
				return false;
			}
		}
		
		return true;
	}
}



// how much does it cost to take block
function costToTakeBlock($location)
{
	global $gameMap;
	global $myID;
	global $myBlocks;
	global $step;
	
	$site = $gameMap->getSite($location);
	
	$cost = $site->strength;
	// check for neighbouring enemies
	$foundEnemy = false;
	$neighboursChecked = [];
	foreach (CARDINALS as $direction)
	{
		$neighbourLocation = $gameMap->getLocation($location, $direction);
		$neighbour = $gameMap->getSite($neighbourLocation);
	
		if ($neighbour->owner != $myID && $neighbour->owner != 0)
		{
			$foundEnemy = true;
			$cost += $neighbour->strength + ($neighbour->production);
			$neighboursChecked[] = $neighbourLocation;
		}
		
		foreach (CARDINALS as $subdirection)
		{
			$subNeighbourLocation = $gameMap->getLocation($neighbourLocation, $subdirection);
			$subNeighbour = $gameMap->getSite($subNeighbourLocation);
		
			$alreadyChecked = false;
			
			foreach ($neighboursChecked as $checked)
			{
				if ($checked->isSame($subNeighbourLocation))
				{
					$alreadyChecked = true;
				}
			}
		
			if ($alreadyChecked == false && $subNeighbour->owner != $myID && $subNeighbour->owner != 0)
			{
				$foundEnemy = true;
				$cost += $subNeighbour->strength + ($subNeighbour->production);
				$neighboursChecked[] = $subNeighbourLocation;
			}
		}
		
	}
	



	if ($cost > 255)
	{
		return 255;
	}
	return $cost;
}

function howMuchDamage($strength, $location) {
    global $gameMap;
    global $myID;
    global $names;
    $damage = 0;
    $site = $gameMap->getSite($location);

    $originalBlockStrength = $site->strength;
    if ($site->owner != $myID && $site->owner != 0) {
        if ($site->strength > $strength) {
            $damage += $site->strength;
        } else {
            $damage += $strength;
        }
    }
    
    foreach (CARDINALS as $direction) {
        $neighbour = $gameMap->getLocation($location, $direction);
        $site = $gameMap->getSite($location, $direction);
        if ($site->owner != $myID && $site->owner != 0) {
            $damage += ($strength - $originalBlockStrength);
        }
    }
    
    
    return $damage;
}

// determine how much a block is worth
function valueOfBlock($strength, $location)
{
	global $gameMap;
	global $myID;
	$site = $gameMap->getSite($location);
	
	// TODO: how much damage will I take if i move here
	$damageTaken = 0;
	
	// TODO: How many enemies
	$howManyEnemies = 1;
	
	$damageDo = howMuchDamage($strength, $location) / $howManyEnemies;
	
	// how much damage will I do
	return (255 - costToTakeBlock($location)) + ($site->production * 10) + $damageDo - $damageTaken;
}


function awesomeAssist($location)
{
	global $gameMap;
	global $myBlocks;
	if (!isReserved($location))
	{
		// check if combined move, see if there is neighbouring blocks to that ideal location, so move could me made
		$onOffer = 0;
		foreach (CARDINALS as $direction)
		{
			$neighbourLocation = $gameMap->getLocation($location,$direction);
			$ni = getMapping($neighbourLocation);
			if ($ni !== -1)
			{
				$onOffer += $myBlocks[$ni]->site->strength;
			}
		}
		$required = costToTakeBlock($location);
		if ($onOffer > $required)
		{
			$requested = 0;
			$blocksContributing = array();
			$inverseDirections = array();
			foreach (CARDINALS as $direction)
			{
				$neighbourLocation = $gameMap->getLocation($location,$direction);
				$ni = getMapping($neighbourLocation);
				if ($ni !== -1)
				{
					$blocksContributing[] = $ni;
					$inverseDirections = INVERSE[$direction];
					$requested += $myBlocks[$ni]->site->strength;
					if ($requested >= $required)
					{
						break;
					}
				}
			}
			
			$value = valueOfBlock($onOffer, $location);
			
			foreach ($blocksContributing as $key=>$ni)
			{
				$myBlocks[$ni]->assistRequests[] = new AssistRequest($value,$blocksContributing,$location);
				// (ping them, with your value)
			}
		}
		
	}
	// for the target block (check if its reserved)
	
	// are there any other adcent blocks who could match its cost
	
	// if there are, add an assist request to each of them.
	
	// remove any other assists for the same block
}

function shitAssist($index)
{
	global $gameMap;
	global $myBlocks;
	
	$location = $myBlocks[$index]->getPlanLocation();

	// check if combined move, see if there is neighbouring blocks to that ideal location, so move could me made
	$onOffer = 0;
	foreach (CARDINALS as $direction)
	{
		$neighbourLocation = $gameMap->getLocation($myBlocks[$index]->location,$direction);
		$ni = getMapping($neighbourLocation);
		if ($ni !== -1)
		{
			$onOffer += $myBlocks[$ni]->site->strength;
		}
	}
	
	$required = costToTakeBlock($location);
	
	if ($onOffer + $myBlocks[$index]->site->strength >= $required)
	{

		// do it
		$requested = 0;
		$blocksContributing = array();
		$inverseDirections = array();
		
		$blocksContributing[] = $index;
		foreach (CARDINALS as $direction)
		{
			$neighbourLocation = $gameMap->getLocation($myBlocks[$index]->location,$direction);
			$ni = getMapping($neighbourLocation);
			if ($ni !== -1)
			{
				$blocksContributing[] = $ni;
				$requested += $myBlocks[$ni]->site->strength;
				if ($requested + $myBlocks[$index]->site->strength >= $required)
				{
					break;
				}
			}
		}

		
		$value = valueOfBlock($onOffer + $myBlocks[$index]->site->strength, $location);
		
		// move to location is actually the location of the original block
		
		foreach ($blocksContributing as $key=>$ni)
		{
			$myBlocks[$ni]->assistRequests[] = new AssistRequest($value,$blocksContributing,$myBlocks[$index]->location);
			// (ping them, with your value)
		}

	}
}



function reviewAssist($index)
{
	global $myBlocks;
	global $gameMap;
	
	$assists = $myBlocks[$index]->assistRequests;
	
	// for each of the blocks assists
	// pick the one with the best value
	
	$bestAssist = $assists[0];
	foreach ($assists as $key=>$assist)
	{
		if ($assist->value > $bestAssist->value && $assist->stillValid())
		{
			$bestAssist = $assist;
		}
	}
	
	if ($bestAssist->stillValid())
	{
		foreach ($bestAssist->blocksContributing as $index)
		{
			$myBlocks[$index]->assistRequests = [];
			$direction = $gameMap->getDirection($myBlocks[$index]->location,$bestAssist->plan);
			$myBlocks[$index]->move = $direction;
			$myBlocks[$index]->locked = true;
			
		}
	}
	
	// TODO: for the other requests for assistance, remove the other involved blocks.
	
	reserve($bestAssist->plan);
}




function reserve($location)
{
	global $reserved;
	$reserved[$location->x][$location->y] = true;
}

function isReserved($location)
{
	global $reserved;
	return $reserved[$location->x][$location->y];
}



function findBorders()
{
	global $borders;
	global $myBlocks;
	global $gameMap;
	
	$borders = [];
	
	for ($i = 0; $i < count($myBlocks);$i++)
	{

		if ($myBlocks[$i]->isBorder)
		{
			$borders[] = $i;
		}
	}
}

function findClosestFrontLine($location)
{
	$foundFrontLine = false;
	global $myBlocks;
	global $gameMap;
	global $borders;
	$closest = $gameMap->width * $gameMap->height;
	$closestLocation = new Location(0,0);
	$frontLines = 0;
	for ($i = 0; $i < count($borders);$i++)
	{
		if ($myBlocks[$borders[$i]]->isFrontLine)
		{
			$foundFrontLine = true;
			$frontLines++;
			$distance = $gameMap->getDistance($location,$myBlocks[$borders[$i]]->location);
			
			if ($distance < $closest)
			{
				$closestLocation = $myBlocks[$borders[$i]]->location;
				$closest = $distance;
			}
		}
	}
	
	if ($foundFrontLine && $frontLines < 10)
	{
		return $closestLocation;
	}
	else
	{
		return false;
	}
}

// find the closest border (useful for inside blocks)
function findClosestBorder($location)
{

	// for all the blocks we own, 
	global $myBlocks;
	global $gameMap;
	global $borders;
	
	$closest = $gameMap->width * $gameMap->height;
	$closestLocation = new Location(0,0);
	
	
	for ($i = 0; $i < count($borders);$i++)
	{
		if ($myBlocks[$borders[$i]]->isBorder)
		{
			$distance = $gameMap->getDistance($location,$myBlocks[$borders[$i]]->location);
			
			if ($distance < $closest)
			{
				$closestLocation = $myBlocks[$borders[$i]]->location;
				$closest = $distance;
				
				if ($distance < 3)
				{
					return $closestLocation;
				}
			}

		}
	}
	
	/*if ($closestLocation->x == 0 && $closestLocation->y == 0)
	{
		echo "Failed to find border\n";
		echo "Looking through " . count($borders) . "\n";
		echo "Closest distance is still " . $closest . "\n";
		exit();
	}*/
	
	// and for each of them that is a border
	return $closestLocation; // location if closest border block
}


function getMapping($location)
{
	global $mapping;
	return $mapping[$location->x][$location->y];
}


// COLLECT: collect all my blocks

// FIND MOVE: find out what ideally you would like to do
// (if there's only one option, thats obvious)
// (else)
// Out of options, it'll be the best growth to cost ratio

// CAN MAKE MOVE: check if that move can be made
	// lock in move
	// reserve square

// CAN PERFORM AWESOME ASSIST: if ideal move isnt reserved, and if move can't be made 
    // check if combined move, see if there is neighbouring blocks to that ideal location, so move could me made
	// (ping them, with your value)

// REVIEW ALL AWESOME ASSISTS: 
 // pick best
 // remove duplicates
 // reserve squares
	
// CAN PERFORM SHIT ASSIST if move isnt reserved, and this block isnt required to make a combined move, and if move can't be made 
	// see if neighbours would could assist (ping them, with your value)
	
// REVIEW ALL SHIT ASSISTS
 // pick best
 // remove duplicates
 // reserve square
	

// IF NOT MOVE IS QUEUED 
// if border path (5 blocks max) will build up 200+ go to border
// else grow.

$step = 0;

function microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

$borders = [];


while (true) {
	

	$time_start = microtime_float();

	$strength = [];	
	$log = [];
	$step ++;
    $moves = [];
	$myBlocks = [];
	$reserved = [];
	$mapping = [];
    $gameMap = getFrame();
	$behind = false;
	
	$log[] = array("START COLLECTING",microtime_float() - $time_start);
	
	// COLLECT: collect all blocks
    for ($x = 0; $x < $gameMap->width; ++$x) {
		$reserved[$x] = [];
		$mapping[$x] = [];
        for ($y = 0; $y < $gameMap->height; ++$y) {
			$reserved[$x][$y] = false;
			$mapping[$x][$y] = -1;
			$location = new Location($x, $y);
			$site = $gameMap->getSite($location);
			
			if ($site->owner !== 0)
			{
				if (!isset($strength[$site->owner]))
				{
					$strength[$site->owner] = $site->strength;
				}
				else
				{
					$strength[$site->owner] += $site->strength;
				}
			}
			
            if ($site->owner === $myID) {
				$myBlocks[] = new Block($location,$site);
				$mapping[$x][$y] = (count($myBlocks) - 1);
            }
        }
    }
	
	
	if ($strength[$myID] != max($strength))
	{
		$behind = true;
		$log[] = array("CURRENT STATUS", $behind ? "BEHIND" : "AHEAD");
	}
	
	$log[] = array("FIND PLAN",microtime_float() - $time_start);
	
	
	// FIND MOVE: find out what ideally you would like to do
	for ($i = 0; $i < count($myBlocks); $i++)
	{
		
		$neighbours = array();
		// get neighbouring blocks
		foreach (CARDINALS as $direction)
		{
			$neighbourLocation = $gameMap->getLocation($myBlocks[$i]->location, $direction);
			$neighbourDirection = $direction;
			$neighbourSite = $gameMap->getSite($neighbourLocation);
			if ($neighbourSite->owner != $myID)
			{
				$neighbours[] = array(
					"location"=>$neighbourLocation, 
					"direction"=>$neighbourDirection, 
					"site"=>$site
				);
			}
			
		}
		
		if (count($neighbours) == 0)
		{
			$myBlocks[$i]->isBorder = false;
		}
		elseif (count($neighbours) == 1) // if only one neighbouring block, pick that
		{
			$myBlocks[$i]->isBorder = true;
			$myBlocks[$i]->plan = $neighbours[0]['direction'];
			
			
					
			foreach (CARDINALS as $direction)
			{
				$neighbourSite = $gameMap->getSite($neighbours[0]["location"], $direction);
				if ($neighbourSite->owner != $myID && $neighbourSite->owner != 0)
				{
					
					$myBlocks[$i]->isFrontLine = true;
					break;
				}
			}
			
		}
		else // else pick the highest value neighbouring block
		{
			$myBlocks[$i]->isBorder = true;
			$highestValue = -1;
			$highestValueDirection = $neighbours[0]['direction'];
			$highestValueLocation = $neighbours[0]['location'];
			foreach ($neighbours as $neighbour)
			{
				$valueOfNeighbour = valueOfBlock($myBlocks[$i]->site->strength, $neighbour['location']);
				if ($valueOfNeighbour > $highestValue)
				{
					$highestValue = $valueOfNeighbour;
					$highestValueDirection = $neighbour['direction'];
					$highestValueLocation = $neighbour['location'];
				}
			}
			$myBlocks[$i]->plan = $highestValueDirection;
			
			foreach (CARDINALS as $direction)
			{
				
				$neighbourSite = $gameMap->getSite($highestValueLocation, $direction);
				if ($neighbourSite->owner != $myID && $neighbourSite->owner != 0)
				{
					$myBlocks[$i]->isFrontLine = true;
					break;
				}
			}
		
			
		}
	}
	
	
	
	$log[] = array("FIND BORDERS",microtime_float() - $time_start);
	
	findBorders();
	
	// find closest front line
	if (!$behind)
	{

		$frontLineNotSet = true;
		$frontLine = new Location(0,0);
		$distance = 100;
		for ($i = 0; $i < count($myBlocks); $i++)
		{
			if (!$myBlocks[$i]->isFrontLine)
			{
				if ($myBlocks[$i]->site->strength > $myBlocks[$i]->site->production)
				{
					if ($frontLineNotSet)
					{
						$frontLine = findClosestFrontLine($myBlocks[$i]->location);	
					}
					
					
					if ($frontLine !== false)
					{
						$distance = $gameMap->getDistance($myBlocks[$i]->location,$frontLine);
											
						if ($distance < 10)
						{
							$direction = $gameMap->getDirection($myBlocks[$i]->location,$frontLine);
							$site = $gameMap->getSite($myBlocks[$i]->location,$direction);
							if ($site->owner == $myID)
							{
								$myBlocks[$i]->move = $direction;
								$myBlocks[$i]->locked = true;
								$myBlocks[$i]->plan = null;
							}
						}
				
						
					}
				}
			}
		}
	}

	
	
	
	// move towards outer edge, if greater than 150
	for ($i = 0; $i < count($myBlocks); $i++)
	{
		if (!$myBlocks[$i]->isBorder && $myBlocks[$i]->move == 0)
		{
			if ($myBlocks[$i]->site->strength > 150)
			{
				$location = clone $myBlocks[$i]->location;
				$borderLocation = findClosestBorder($location);				
				$myBlocks[$i]->move = $gameMap->getDirection($myBlocks[$i]->location,$borderLocation);
				
				
			}
		}
		
	}
	
	
	$log[] = array("CAN MAKE MOVE",microtime_float() - $time_start);
	
	// CAN MAKE MOVE: check if that move can be mad
	for ($i = 0; $i < count($myBlocks); $i++)
	{
		if (!is_null($myBlocks[$i]->plan))
		{
			$planLocation = $myBlocks[$i]->getPlanLocation();
			$cost = costToTakeBlock($planLocation);
			if (!isReserved($planLocation) && 
				$myBlocks[$i]->site->strength > $cost)
			{
				if ($step == 11)
				{
					//print_r($myBlocks[$i]->location);
				//	echo "I am going to make this move " . $names[$myBlocks[$i]->plan] . "\n";
					//echo "I think its going to cost " . $cost . "\n";
					//echo "And my strength is " . $myBlocks[$i]->site->strength . "\n";
					//exit();
				}
				$myBlocks[$i]->move = $myBlocks[$i]->plan;
				reserve($planLocation);
			}
		}
	}
	
	$log[] = array("CAN PERFORM ASSIST",microtime_float() - $time_start);
	
	// CAN PERFORM AWESOME ASSIST: if ideal move isnt reserved, and if move can't be made 
	for ($i = 0; $i < count($myBlocks); $i++)
	{
		if ($myBlocks[$i]->move == 0 && !is_null($myBlocks[$i]->plan))
		{
			$planLocation = $myBlocks[$i]->getPlanLocation();
			awesomeAssist($planLocation);
		}
	}
	
	$log[] = array("REVIEW AWESOME ASSISTS",microtime_float() - $time_start);
	
    
	
	// REVIEW ALL AWESOME ASSISTS
	for ($i = 0; $i < count($myBlocks); $i++)
	{
		if ($myBlocks[$i]->move == 0 && count($myBlocks[$i]->assistRequests) > 0)
		{

			reviewAssist($i);
		}
	}
	
	
	if (count($myBlocks) < 200)
	{
		$log[] = array("CAN PERFORM SHIT ASSISTS",microtime_float() - $time_start);
	
		// CAN PERFORM SHIT ASSIST if move isnt reserved, and this block isnt required to make a combined move, and if move can't be made
		for ($i = 0; $i < count($myBlocks); $i++)
		{
			if ($myBlocks[$i]->move == 0 && !is_null($myBlocks[$i]->plan))
			{
				shitAssist($i);
			}
		}
		
		$log[] = array("REVIEW SHIT ASSISTS",microtime_float() - $time_start);
		// REVIEW ALL SHIT ASSISTS
		for ($i = 0; $i < count($myBlocks); $i++)
		{
			if ($myBlocks[$i]->move == 0 && count($myBlocks[$i]->assistRequests) > 0 && $myBlocks[$i]->locked == false)
			{
				reviewAssist($i);
			}
		}
	}
	
	
	$log[] = array("MOVE TO BORDER",microtime_float() - $time_start);
	
	// IF NOT MOVE IS QUEUED 
	$moveToBorder = 0;
	for ($i = 0; $i < count($myBlocks); $i++)
	{
		if ($myBlocks[$i]->move == 0 && $myBlocks[$i]->locked == false && $myBlocks[$i]->isBorder == false && $myBlocks[$i]->site->strength > ($behind ? ($myBlocks[$i]->site->production * 2) : 0))
		{
			// find border
			$location = clone $myBlocks[$i]->location;
			$borderLocation = findClosestBorder($location);
			$direction = $gameMap->getDirection($myBlocks[$i]->location,$borderLocation);
			
			// add up path			
			$d = 0;
			$totalValue = 0;
			$makeMove = false;
			$borderIndex = getMapping($borderLocation);

			if ($myBlocks[$borderIndex]->move === 0)
			{

				$minimumAmount = costToTakeBlock($gameMap->getLocation($myBlocks[$borderIndex]->location,$myBlocks[$borderIndex]->plan));
				while (!$location->isSame($borderLocation))
				{
					
					$site = $gameMap->getSite($location);
					
					if ($site->owner == $myID)
					{
						
						$totalValue += $site->strength + $site->production;
						if ($totalValue > $minimumAmount)
						{
							
							$makeMove = true;
							break;
						}
					}
					$direction = $gameMap->getDirection($location,$borderLocation);
					$location = $gameMap->getLocation($location,$direction);
				}
			
			}
			else
			{
				$makeMove = true;
			}
			
			// if bigger than 200
			if ($makeMove)
			{
				// move to border
				$direction = $gameMap->getDirection($myBlocks[$i]->location,$borderLocation);
				$myBlocks[$i]->move = $direction;
			}
			$moveToBorder++;
		}
	}
	
	$log[] = array("STOPPING WASTE",microtime_float() - $time_start);
	
	
	$log[] = array("PERFORM MOVE",microtime_float() - $time_start);
	for ($i = 0; $i < count($myBlocks); $i++)
	{
		$moves[] = new Move($myBlocks[$i]->location,$myBlocks[$i]->move);
	}
	$log[] = array("FINISH PERFORM MOVE",microtime_float() - $time_start);
	$log[] = array("TOTAL BLOCKS", count($myBlocks));
	

//	file_put_contents("step.txt",json_encode($log, JSON_PRETTY_PRINT));
	
    sendFrame($moves);
}
