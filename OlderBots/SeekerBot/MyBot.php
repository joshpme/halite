<?php

// TO DO: FOR ALL THE BLOCKS THAT I CAN HELP (CAUSE THEY ARE THE SAME DISTANCE TO EDGE)
// GO TO GREATEST VALUE TARGET
// TODO PRIORITY: VALUE OF TAKING A BLOCK SHOULD BE DEFINED AS 4 STEPS, WHILE UNDER 50 blocks, 3 STEPS UNDER 200 blocks, 2 STEPS under 500 blocks and 1 for the restore_error_handler
// TODO: If EXCESS VALUE CHOOSE PATH WITH GREATEST VALUE 
// (NOT SINGLE BLOCK WITH GREATEST VALUE)
// TODO: WHILE UNDER 20 BLOCKS
// CHECK ALL BLOCKS FOR GOAL THATS DOABLE
// START ON GOAL

error_reporting(E_ALL);

require_once 'hlt.php';
require_once 'networking.php';
require_once 'Model.php';

list($myID, $gameMap) = getInit();
sendInit('SeekerBot');

// block structure
class Block {

    public $location;
    public $site;
    public $isBorder; // if it has a adjacent spare block
    public $plan;
    public $move;
    public $locked;
    public $assistRequests;
    public $planValue;
    public $frontLineEnemy;

    public function __construct($location, $site) {
        $this->location = $location;
        $this->site = $site;
        $this->move = 0;
        $this->assistRequests = array();
        $this->locked = false;
        $this->isBorder = false;
        $this->isFrontLine = false;
    }

    public function getPlanLocation() {
        global $gameMap;
        return $gameMap->getLocation($this->location, $this->plan);
    }

}

function getBlockAtLocation($location) {
    global $myBlocks;
    for ($i = 0; $i < count($myBlocks); $i++) {
        if ($myBlock->location->isSame($location)) {
            return $i;
        }
    }
    return -1;
}

class AssistRequest {

    public $value; // value of plan
    public $blocksContributing; // List of blocks involved (so they can be purged if required)
    public $plan; // location of place to move

    public function __construct($value, $blocksContributing, $plan) {
        $this->value = $value;
        $this->blocksContributing = $blocksContributing;
        $this->plan = $plan;
    }

    public function stillValid() {
        global $myBlocks;

        foreach ($this->blocksContributing as $index) {
            if ($myBlocks[$index]->locked || $myBlocks[$index]->move != 0) {
                return false;
            }
        }

        return true;
    }

}

// how much does it cost to take block
function costToTakeBlock($location) {
    global $gameMap;
    global $myID;
    global $myBlocks;
    global $step;

    $site = $gameMap->getSite($location);

    $cost = $site->strength;
    // check for neighbouring enemies
    $foundEnemy = false;
    $neighboursChecked = [];
    foreach (CARDINALS as $direction) {
        $neighbourLocation = $gameMap->getLocation($location, $direction);
        $neighbour = $gameMap->getSite($neighbourLocation);

        if ($neighbour->owner != $myID && $neighbour->owner != 0) {
            $foundEnemy = true;
            $cost += $neighbour->strength + ($neighbour->production);
            $neighboursChecked[] = $neighbourLocation;
        }

        foreach (CARDINALS as $subdirection) {
            $subNeighbourLocation = $gameMap->getLocation($neighbourLocation, $subdirection);
            $subNeighbour = $gameMap->getSite($subNeighbourLocation);

            $alreadyChecked = false;

            foreach ($neighboursChecked as $checked) {
                if ($checked->isSame($subNeighbourLocation)) {
                    $alreadyChecked = true;
                }
            }

            if ($alreadyChecked == false && $subNeighbour->owner != $myID && $subNeighbour->owner != 0) {
                $foundEnemy = true;
                $cost += $subNeighbour->strength + ($subNeighbour->production);
                $neighboursChecked[] = $subNeighbourLocation;
            }
        }
    }




    if ($cost >= 255) {
        return 255;
    }
    return $cost;
}

// determine how much a block is worth
function valueOfBlock($strength, $location)
{
	global $gameMap;
	global $myID;
        global $step;
        
	$site = $gameMap->getSite($location);
	
	// TODO: how much damage will I take if i move here
	$damageTaken = 0;
	
	// TODO: How many enemies
	$howManyEnemies = 1;
	
        
	$damageDo = howMuchDamage($strength, $location);
	
        
       //if ($damageDo > 0)
       // {
                       
       /*     echo "On Step: " . $step . "\n";
            echo "For position:\n";
            print_r($location);
            echo "I will do this much damage: " . $damageDo . "\n";
            echo "It will cost me this much to take the block: " . costToTakeBlock($location) . "\n";
            echo "I will gain: " . $site->production . " production\n";
            echo "Final score of: ";
            echo ($site->production * 10) + $damageDo - $damageTaken - costToTakeBlock($location) . "\n";*/
       // }
	// how much damage will I do
	return ($site->production * 10) + $damageDo - $damageTaken - costToTakeBlock($location);
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

function awesomeAssist($location, $value) {
    global $gameMap;
    global $myBlocks;
	global $step;

    if (!isReserved($location)) {
        // check if combined move, see if there is neighbouring blocks to that ideal location, so move could me made
        $onOffer = 0;
        foreach (CARDINALS as $direction) {
            $neighbourLocation = $gameMap->getLocation($location, $direction);
            $ni = getMapping($neighbourLocation);
            if ($ni !== -1) {
                $onOffer += $myBlocks[$ni]->site->strength;
            }
        }
        $required = costToTakeBlock($location);
        if ($onOffer > $required) {

		/*if ($step == 19)
		{
					echo "This awesome assists plan is worth\n";
					print_r($value);
					echo "\n";
		}*/
            $requested = 0;
            $blocksContributing = array();
            $inverseDirections = array();
            foreach (CARDINALS as $direction) {
                $neighbourLocation = $gameMap->getLocation($location, $direction);
                $ni = getMapping($neighbourLocation);
                if ($ni !== -1) {
                    $blocksContributing[] = $ni;
                    $inverseDirections = INVERSE[$direction];
                    $requested += $myBlocks[$ni]->site->strength;
                    if ($myBlocks[$ni]->isFrontLine == false && $requested >= $required) {
                        break;
                    }
                }
            }

            $value = valueOfBlock($onOffer, $location);


            foreach ($blocksContributing as $key => $ni) {




                $myBlocks[$ni]->assistRequests[] = new AssistRequest($value, $blocksContributing, $location);
                // (ping them, with your value)
            }
        }
    }
    // for the target block (check if its reserved)
    // are there any other adcent blocks who could match its cost
    // if there are, add an assist request to each of them.
    // remove any other assists for the same block
}

function shitAssist($index) {
    global $gameMap;
    global $myBlocks;
    global $step;
    $location = $myBlocks[$index]->getPlanLocation();
    $value = $myBlocks[$index]->planValue;
    global $step;

    // check if combined move, see if there is neighbouring blocks to that ideal location, so move could me made
    $onOffer = 0;
    foreach (CARDINALS as $direction) {
        $neighbourLocation = $gameMap->getLocation($myBlocks[$index]->location, $direction);
        $ni = getMapping($neighbourLocation);
        if ($ni !== -1) {
            $onOffer += $myBlocks[$ni]->site->strength;
        }
    }

    $required = costToTakeBlock($location);

    if ($onOffer + $myBlocks[$index]->site->strength >= $required) {

        if ($step != 4 && $step != 8) {
            //echo "On step " . $step . "\n";
            // echo "This shit assists plan is worth: " . $value . "\n";
            //for ($i = 0; $i < count($myBlocks); $i++)
            //{
            //  echo "BLOCK[$i]: My plan is worth " . $myBlocks[$i]->planValue . "\n";
            //       }
            //echo "\n";
        }
        // do it
        $requested = 0;
        $blocksContributing = array();
        $inverseDirections = array();

        $blocksContributing[] = $index;
        foreach (CARDINALS as $direction) {
            $neighbourLocation = $gameMap->getLocation($myBlocks[$index]->location, $direction);
            $ni = getMapping($neighbourLocation);
            if ($ni !== -1) {
                $blocksContributing[] = $ni;
                $requested += $myBlocks[$ni]->site->strength;
                if ($requested + $myBlocks[$index]->site->strength >= $required) {
                    break;
                }
            }
        }

        // move to location is actually the location of the original block
		$value = valueOfBlock($onOffer + $myBlocks[$index]->site->strength, $location);
		
        $myBlocks[$index]->assistRequests[] = new AssistRequest($value, $blocksContributing, $myBlocks[$index]->location);
        //foreach ($blocksContributing as $key => $ni) {
        // (ping them, with your value)
        //  }
    }
}

function terribleAssist() {
    global $gameMap;
    global $myBlocks;
    global $step;

    $plans = [];
    for ($i = 0; $i < count($myBlocks); $i++) {
        if (!is_null($myBlocks[$i]->plan)) {
            $plans[$i] = $myBlocks[$i]->planValue;
        }
    }

    arsort($plans);


    // check if any of the plans are viable if they get help from far away
    foreach ($plans as $p => $value) {
        $production = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        $strength = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

        for ($i = 0; $i < count($myBlocks); $i++) {
            $distance = $gameMap->getDistance($myBlocks[$p]->location, $myBlocks[$i]->location);
            $production[$distance] += $myBlocks[$i]->site->production;
            $strength[$distance] += $myBlocks[$i]->site->strength;
        }

        $required = costToTakeBlock($myBlocks[$p]->getPlanLocation());
        // oh god its a polynomial
        $available = $strength[0] + ($production[0] * 2) + $strength[1] + $production[1] + $strength[3];

        if ($available > $required) {
            $blocksContributing = [];
            $letThisGrow = $strength[0] + ($production[0] * 2) + $strength[1];
            $collected = 0;
            for ($i = 0; $i < count($myBlocks); $i++) {
                $distance = $gameMap->getDistance($myBlocks[$p]->location, $myBlocks[$i]->location);
                if ($distance == 2 && $letThisGrow + $collected <= $required) {
                    $blocksContributing[] = $i;
                    $collected += $myBlocks[$i]->site->strength;
                }
            }
            // do an assist request

            $myBlocks[$p]->assistRequests[] = new AssistRequest($value, $blocksContributing, $myBlocks[$p]->location);
        }
    }
}

function reviewAssist($index) {
    global $myBlocks;
    global $gameMap;
    global $step;
    global $myID;
    $assists = $myBlocks[$index]->assistRequests;

    if (count($myBlocks[$index]->assistRequests) > 0) {
        // for each of the blocks assists
        // pick the one with the best value

        $bestAssist = $assists[0];

        foreach ($assists as $key => $assist) {
            if ($assist->value > $bestAssist->value && $assist->stillValid()) {
                $bestAssist = $assist;
            }
        }

        if ($bestAssist->stillValid()) {
            //if ($step > 108)
            //     {
            //         echo "I currently control " . count($myBlocks) . " blocks\n";
            //         echo "Proceeding with plan worth " . $bestAssist->value . "\n";
            //      }
            foreach ($bestAssist->blocksContributing as $index) {

                $direction = 0;
                $distance = $gameMap->getDistance($myBlocks[$index]->location, $bestAssist->plan);
                if ($distance > 1) {
                    $proceedDirection = 0;
                    foreach (CARDINALS as $direction) {
                        $directionLocation = $gameMap->getLocation($myBlocks[$index]->location, $direction);
                        $site = $gameMap->getSite($directionLocation);
                        $newDistance = $gameMap->getDistance($directionLocation, $bestAssist->plan);
                        if ($site->owner == $myID && $newDistance < $distance) {
                            $proceedDirection = $direction;
                        }
                    }
                    if ($proceedDirection == 0) {
                        return false;
                    }
                    $direction = $proceedDirection;
                } else {
                    $direction = $gameMap->getDirection($myBlocks[$index]->location, $bestAssist->plan);
                }

                $myBlocks[$index]->assistRequests = [];
                $myBlocks[$index]->move = $direction;
                $myBlocks[$index]->locked = true;
            }
            reserve($bestAssist->plan);
            return true;
        } else {

            return false;
        }
    }
    return false;


    // TODO: for the other requests for assistance, remove the other involved blocks.
}

function reserve($location) {
    global $reserved;
    $reserved[$location->x][$location->y] = true;
}

function isReserved($location) {
    global $reserved;
    return $reserved[$location->x][$location->y];
}

function findClosestFrontLine($location) {
    $foundFrontLine = false;
    global $myBlocks;
    global $gameMap;
    global $enemy;
    global $borders;
    $closest = $gameMap->width * $gameMap->height;
    $closestLocation = new Location(0, 0);
    $frontLines = 0;
    for ($i = 0; $i < count($borders); $i++) {
        if ($myBlocks[$borders[$i]]->isFrontLine) {
            $foundFrontLine = true;
            $frontLines++;
            $distance = $gameMap->getDistance($location, $myBlocks[$borders[$i]]->location);


            if ($distance < $closest) {
                $enemy = $myBlocks[$borders[$i]]->frontLineEnemy;
                $closestLocation = $myBlocks[$borders[$i]]->location;
                $closest = $distance;
            }
        }
    }

    if ($foundFrontLine && $frontLines < 5) {
        return $closestLocation;
    } else {
        return false;
    }
}

// find the closest border (useful for inside blocks)
function findClosestBorder($location) {

    // for all the blocks we own, 
    global $myBlocks;
    global $gameMap;
    global $borders;

    $closest = $gameMap->width * $gameMap->height;
    $closestLocation = new Location(0, 0);


    for ($i = 0; $i < count($borders); $i++) {
        if ($myBlocks[$borders[$i]]->isBorder) {
            $distance = $gameMap->getDistance($location, $myBlocks[$borders[$i]]->location);

            if ($distance < $closest) {
                $closestLocation = $myBlocks[$borders[$i]]->location;
                $closest = $distance;

                if ($distance < 3) {
                    return $closestLocation;
                }
            }
        }
    }

    /* if ($closestLocation->x == 0 && $closestLocation->y == 0)
      {
      echo "Failed to find border\n";
      echo "Looking through " . count($borders) . "\n";
      echo "Closest distance is still " . $closest . "\n";
      exit();
      } */

    // and for each of them that is a border
    return $closestLocation; // location if closest border block
}

function getMapping($location) {
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

function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float) $usec + (float) $sec);
}
$war = true;

$previousFrontLineStep = 0;
$previousFrontLine = false;

while (true) {


    $time_start = microtime_float();
    $borders = [];
    $strength = [];
    $log = [];
    $step ++;
    $enemy = 0;
    file_put_contents("upto.txt", $step);
    $moves = [];
    $myBlocks = [];
    $reserved = [];
    $mapping = [];
    $gameMap = getFrame();
    $behind = false;
    
    $frontLineEnemy = 0;
    $log[] = array("START COLLECTING", microtime_float() - $time_start);

    // COLLECT: collect all blocks
    for ($x = 0; $x < $gameMap->width; ++$x) {
        $reserved[$x] = [];
        $mapping[$x] = [];
        for ($y = 0; $y < $gameMap->height; ++$y) {
            $reserved[$x][$y] = false;
            $mapping[$x][$y] = -1;
            $location = new Location($x, $y);
            $site = $gameMap->getSite($location);

            if ($site->owner !== 0) {
                if (!isset($strength[$site->owner])) {
                    $strength[$site->owner] = $site->strength;
                } else {
                    $strength[$site->owner] += $site->strength;
                }
            }

            if ($site->owner === $myID) {
                $myBlocks[] = new Block($location, $site);
                $mapping[$x][$y] = (count($myBlocks) - 1);
            }
        }
    }






    $log[] = array("FIND PLAN", microtime_float() - $time_start);


    // FIND IF BORDER OR FRONT LINE
    for ($i = 0; $i < count($myBlocks); $i++) {
        $neighbours = array();
        // get neighbouring blocks
        foreach (CARDINALS as $direction) {
            $neighbourLocation = $gameMap->getLocation($myBlocks[$i]->location, $direction);
            $neighbourDirection = $direction;
            $neighbourSite = $gameMap->getSite($neighbourLocation);
            if ($neighbourSite->owner != $myID) {
                $neighbours[] = array(
                    "location" => $neighbourLocation,
                    "direction" => $neighbourDirection,
                    "site" => $site
                );
            }
        }

        if (count($neighbours) == 0) {
            $myBlocks[$i]->isBorder = false;
        } else {
            $borders[] = $i;
            $myBlocks[$i]->isBorder = true;
            $myBlocks[$i]->plan = $neighbours[0]['direction'];

                    
            foreach ($neighbours as $neighbour) {
                foreach (CARDINALS as $direction) {
                    $neighbourSite = $gameMap->getSite($neighbour['location'], $direction);
                    if ($neighbourSite->owner != $myID && $neighbourSite->owner != 0 && $neighbourSite->strength == 0) {
                        $myBlocks[$i]->isFrontLine = true;
                        $war = true;
                        $myBlocks[$i]->frontLineEnemy = $neighbourSite->owner;
                        break;
                    }
                }
            }
        }
    }

	
	
	
    // FIND PLANS (AND PLAN VALUES)
    // for each border
	
	
    // USE DAMAGE CALCULATIONS
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

            }
            elseif (count($neighbours) == 1) // if only one neighbouring block, pick that
            {
                    $myBlocks[$i]->plan = $neighbours[0]['direction'];
                    
                    
            }
            else // else pick the highest value neighbouring block
            {
                    $myBlocks[$i]->isBorder = true;
                    $highestValue = -100000000;
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
            }
            
            
            
    }

    
    

    /*
      // FIND MOVE: find out what ideally you would like to do
      for ($i = 0; $i < count($myBlocks); $i++) {

      $neighbours = array();
      // get neighbouring blocks
      foreach (CARDINALS as $direction) {
      $neighbourLocation = $gameMap->getLocation($myBlocks[$i]->location, $direction);
      $neighbourDirection = $direction;
      $neighbourSite = $gameMap->getSite($neighbourLocation);
      if ($neighbourSite->owner != $myID) {
      $neighbours[] = array(
      "location" => $neighbourLocation,
      "direction" => $neighbourDirection,
      "site" => $site
      );
      }
      }

      if (count($neighbours) == 0) {
      $myBlocks[$i]->isBorder = false;
      } elseif (count($neighbours) == 1) { // if only one neighbouring block, pick that
      $myBlocks[$i]->isBorder = true;
      $myBlocks[$i]->plan = $neighbours[0]['direction'];



      foreach (CARDINALS as $direction) {
      $neighbourSite = $gameMap->getSite($neighbours[0]["location"], $direction);
      if ($neighbourSite->owner != $myID && $neighbourSite->owner != 0) {

      $myBlocks[$i]->isFrontLine = true;
      break;
      }
      }
      }
      else
      { // else pick the highest value neighbouring block


      $location = $myBlocks[$i]->location;
      $site = $myBlocks[$i]->site;

      if ($myBlocks[$i]->isFrontLine)
      {
      // TODO: how much damage will I take if i move here
      $damageTaken = 0;

      // TODO: How many enemies
      $howManyEnemies = 1;

      $damageDo = howMuchDamage($strength, $location) / $howManyEnemies;

      // how much damage will I do
      return (255 - costToTakeBlock($location)) + ($site->production * 10) + $damageDo - $damageTaken;
      }
      else
      {
      $format = array(
      "strength"=>$site->strength,
      "production"=>$site->production,
      "options"=>array()
      );
      foreach (CARDINALS as $direction)
      {
      $sublocation = $gameMap->getLocation($location,$direction);
      $option = $gameMap->getSite($sublocation);
      if ($option->owner != $myID)
      {
      $format['options'][$direction] = array(
      "strength"=>$option->strength,
      "production"=>$option->production,
      "options"=>array()
      );

      foreach (CARDINALS as $subdirection)
      {
      $subsublocation = $gameMap->getLocation($sublocation,$subdirection);
      $suboption = $gameMap->getSite($subsublocation);
      if ($suboption->owner != $myID)
      {
      $format['options'][$direction]['options'][$subdirection] = array(
      "strength"=>$suboption->strength,
      "production"=>$suboption->production
      );
      }
      }
      }
      }
      }

      // FIND THE PATH WITH THE SHORTEST TURN AROUND




      if (count($format['options']) > 0)
      {

      $times = findTimes($format);
      $plan = findBest(array("value"=>0, "children"=>$times));
      $myBlocks[$i]->plan = $plan['path'][0];
      }
      foreach (CARDINALS as $direction) {

      $neighbourSite = $gameMap->getSite($highestValueLocation, $direction);
      if ($neighbourSite->owner != $myID && $neighbourSite->owner != 0) {
      $myBlocks[$i]->isFrontLine = true;
      break;
      }
      }
      }
      }
     */


    $log[] = array("FIND BORDERS", microtime_float() - $time_start);

    // find closest front line
    $behind = false;
    $frontLineNotSet = true;
    $frontLine = new Location(0, 0);
    $distance = 100;
    $charge = false;
    for ($i = 0; $i < count($myBlocks); $i++) {
        if (!$myBlocks[$i]->isFrontLine) {
            if ($myBlocks[$i]->site->strength > $myBlocks[$i]->site->production) {
                if ($frontLineNotSet) {
                    $newFrontLine = findClosestFrontLine($myBlocks[$i]->location);
                    $distanceToFrontLine = 0;
                    if ($previousFrontLine !== false && $newFrontLine !== false)
                    {
                        $distanceToFrontLine = $gameMap->getDistance($newFrontLine, $previousFrontLine);
                    }
                    
                    if ($step - $previousFrontLineStep > 5 || $distanceToFrontLine < 5)
                    {
                        $frontLine = $newFrontLine;
                        $frontLineNotSet = false;
                    }
                }


                if ($frontLine !== false) {
                    if ($strength[$myID] > $strength[$enemy]) {
                        $behind = false;
                        
                        //echo "Our current enemy is!" . $enemy;

                        $distance = $gameMap->getDistance($myBlocks[$i]->location, $frontLine);

                        if ($distance < 8 && $distance >= 1) {
                            $direction = $gameMap->getDirection($myBlocks[$i]->location, $frontLine);
                            $site = $gameMap->getSite($myBlocks[$i]->location, $direction);
                            $charge = true;
                            if ($site->owner == $myID) {
                                $myBlocks[$i]->move = $direction;
                                $myBlocks[$i]->locked = true;
                                $myBlocks[$i]->plan = null;
                            }
                        }
                    }
                } else {
                    $behind = true;
                }
            }
        }
    }

    $log[] = array("CURRENT STATUS", $behind ? "BEHIND" : "AHEAD");




    // move towards outer edge, if greater than 150
    for ($i = 0; $i < count($myBlocks); $i++) {
        if (!$myBlocks[$i]->isBorder && $myBlocks[$i]->move == 0) {
            if ($myBlocks[$i]->site->strength > 150) {
                $location = clone $myBlocks[$i]->location;
                $borderLocation = findClosestBorder($location);
                $myBlocks[$i]->move = $gameMap->getDirection($myBlocks[$i]->location, $borderLocation);
            }
        }
    }


    $log[] = array("CAN MAKE MOVE", microtime_float() - $time_start);

    // CAN MAKE MOVE: check if that move can be mad
    for ($i = 0; $i < count($myBlocks); $i++) {
        if (!is_null($myBlocks[$i]->plan)) {
            $planLocation = $myBlocks[$i]->getPlanLocation();
            $cost = costToTakeBlock($planLocation);
            if ((!isReserved($planLocation) &&
                    $myBlocks[$i]->site->strength > $cost) ||
                    howMuchDamage($myBlocks[$i]->site->strength, $planLocation) > $myBlocks[$i]->site->strength) 
                {
                
                
                if ($planLocation->isSame(new Location(7, 11)) && $step >= 111 && $step <= 113 && $myBlocks[$i]->site->strength == 2)
                {
                    echo "Why the fuck am I making this move?\n";
                    echo "Because of damage? " . (howMuchDamage($myBlocks[$i]->site->strength, $planLocation) > $myBlocks[$i]->site->strength ? "yes" : "no") . "\n";
                    echo "How much damage do you think you'll do? " . howMuchDamage($myBlocks[$i]->site->strength, $planLocation) . "\n";
                    echo "How much strength to do you think you have? " . $myBlocks[$i]->site->strength . "\n";
                    echo "Because I can afford to? " . ($myBlocks[$i]->site->strength > $cost ? "Yes " : "No") . "\n";
                    exit();
                }
                
                $myBlocks[$i]->move = $myBlocks[$i]->plan;
                $myBlocks[$i]->locked = true;
                reserve($planLocation);
            }
        }
    }

    $log[] = array("CAN PERFORM ASSIST", microtime_float() - $time_start);

    // CAN PERFORM AWESOME ASSIST: if ideal move isnt reserved, and if move can't be made 
    for ($i = 0; $i < count($myBlocks); $i++) {
        if ($myBlocks[$i]->move == 0 && !is_null($myBlocks[$i]->plan)) {
            $planLocation = $myBlocks[$i]->getPlanLocation();
            $value = $myBlocks[$i]->planValue;
            awesomeAssist($planLocation, $value);
        }
    }

    $log[] = array("REVIEW AWESOME ASSISTS", microtime_float() - $time_start);



    // REVIEW ALL AWESOME ASSISTS
    $reviewsPerformed = 0;
    $reviewsCompleted = 0;
    for ($i = 0; $i < count($myBlocks); $i++) {
        if ($myBlocks[$i]->move == 0 && count($myBlocks[$i]->assistRequests) > 0) {
            $reviewsPerformed++;
            if (reviewAssist($i)) {
                $reviewsCompleted++;
            }
        }
    }

    if (count($myBlocks) < 200) {
        $log[] = array("CAN PERFORM SHIT ASSISTS", microtime_float() - $time_start);

        // CAN PERFORM SHIT ASSIST if move isnt reserved, and this block isnt required to make a combined move, and if move can't be made
        for ($i = 0; $i < count($myBlocks); $i++) {
            if ($myBlocks[$i]->move == 0 && !is_null($myBlocks[$i]->plan)) {
                shitAssist($i);
            }
        }
        $log[] = array("REVIEW SHIT ASSISTS", microtime_float() - $time_start);
        // REVIEW ALL SHIT ASSISTS
        // EVALUTE PLANS IN ORDER OF VALUE
        $reviews = [];

        for ($i = 0; $i < count($myBlocks); $i++) {
            if ($myBlocks[$i]->move == 0 && count($myBlocks[$i]->assistRequests) > 0 && $myBlocks[$i]->locked == false) {

                $reviews[$i] = $myBlocks[$i]->assistRequests[0]->value;

                foreach ($myBlocks[$i]->assistRequests as $assistReview) {
                    if ($reviews[$i] < $assistReview->value) {
                        $reviews[$i] = $assistReview->value;
                    }
                }
            }
        }
        arsort($reviews);
        foreach ($reviews as $i => $value) {

            reviewAssist($i);
        }
    }


    if (count($myBlocks) < 30) {
        // do incredibly terrible assists
        // if no one has any moves
        $checkForTerribleAssist = true;
        for ($i = 0; $i < count($myBlocks); $i++) {
            if ($myBlocks[$i]->move != 0) {
                $checkForTerribleAssist = false;
            }
        }
        if ($checkForTerribleAssist) {
            //terribleAssist();
        }

        for ($i = 0; $i < count($myBlocks); $i++) {
            if ($myBlocks[$i]->move == 0 && count($myBlocks[$i]->assistRequests) > 0) {
                if (reviewAssist($i)) {
                    // echo "Im going to do a terrible assist!";
                }
            }
        }
    }


    $log[] = array("MOVE TO BORDER", microtime_float() - $time_start);

    // IF NOT MOVE IS QUEUED 
    $moveToBorder = 0;
    for ($i = 0; $i < count($myBlocks); $i++) {
        if ($myBlocks[$i]->move == 0 && $myBlocks[$i]->locked == false && $myBlocks[$i]->isBorder == false && $myBlocks[$i]->site->strength > 0) {
            // find border
            $location = clone $myBlocks[$i]->location;
            $borderLocation = findClosestBorder($location);
            $direction = $gameMap->getDirection($myBlocks[$i]->location, $borderLocation);

            // add up path			
            $d = 0;
            $totalValue = 0;
            $makeMove = false;
            $borderIndex = getMapping($borderLocation);

            if ($myBlocks[$borderIndex]->move === 0) {

                $minimumAmount = costToTakeBlock($gameMap->getLocation($myBlocks[$borderIndex]->location, $myBlocks[$borderIndex]->plan));
                while (!$location->isSame($borderLocation)) {

                    $site = $gameMap->getSite($location);

                    if ($site->owner == $myID) {

                        $totalValue += $site->strength + $site->production;
                        if ($totalValue > $minimumAmount) {

                            $makeMove = true;
                            break;
                        }
                    }
                    $direction = $gameMap->getDirection($location, $borderLocation);
                    $location = $gameMap->getLocation($location, $direction);
                }
            } else {
                $makeMove = true;
            }

            // if bigger than 200
            if ($makeMove) {
                // move to border
                $direction = $gameMap->getDirection($myBlocks[$i]->location, $borderLocation);
                $myBlocks[$i]->move = $direction;
            }
            $moveToBorder++;
        }
    }

	$log[] = array("STOPPING WASTE",microtime_float() - $time_start);
	
	// stop over use
	$changesMade = true;
	while ($changesMade)
	{
		$changesMade = false;
		for ($i = 0; $i < count($myBlocks); $i++)
		{
			$site = $gameMap->getSite($myBlocks[$i]->location);
			$destination = $gameMap->getSite($myBlocks[$i]->location,$myBlocks[$i]->move);
			if ($myBlocks[$i]->move != 0 && $site->owner == $myID && $destination->owner == $myID && $site->strength + $destination->strength > 255)
			{
				$destinationLocation = $gameMap->getLocation($myBlocks[$i]->location,$myBlocks[$i]->move);
				$infront = getMapping($borderLocation);
				if ($myBlocks[$infront]->move == 0)
				{
					$myBlocks[$i]->move = 0;
					$changesMade = true;
			
				}
			}
		}
	}
       
	
	$log[] = array("PERFORM MOVE",microtime_float() - $time_start);
	for ($i = 0; $i < count($myBlocks); $i++)
	{
		$moves[] = new Move($myBlocks[$i]->location,$myBlocks[$i]->move);
	}
	$log[] = array("FINISH PERFORM MOVE",microtime_float() - $time_start);
	$log[] = array("TOTAL BLOCKS", count($myBlocks));

    file_put_contents("step.txt",json_encode($log, JSON_PRETTY_PRINT));

    sendFrame($moves);
    
    if ($charge)
    {
        $previousFrontLineStep = $step;
        $previousFrontLine = $frontLine;
    }
    
}
