<?php

namespace Halite;

class Map {

    /**
     * Width of map
     * @var int
     */
    public $width;

    /**
     * Height of map
     * @var int
     */
    public $height;

    /**
     * Array of all blocks on map
     * @var Block[][]
     */
    public $blocks;

    /**
     * References to all blocks by a specific owner
     * @var Block[]
     */
    public $byOwner;

    /**
     * Reference to all border blocks
     * @var Block[]
     */
    public $borders;

    /**
     * My ID
     * @var int
     */
    public $me;

    /**
     * Enemy IDs
     * @var int[]
     */
    public $enemies;

    /*
     * Current frame #
     * Only populate after you declare you are ready
     * 
     * @var int
     */
    public $frame;

    /**
     * Debug mode, Useful for running the game outside of Halite.
     * Will automatically use debug move if running script outside of CLI
     * 
     * @var bool
     */
    public $debugMode;

    /**
     * Start time of frame
     * @var int
     */
    private $start;

    /**
     * Map constructor. 
     * If you are debugging, you can set the maximum step
     * 
     * @param int $maxSteps [debug only feature]
     */
    public function __construct($maxSteps = 20) {
        $this->frame = -1;
        $this->debugMode = !defined('STDIN');
        $this->enemies = [];
        if ($this->debugMode) {
            $this->maxSteps = $maxSteps;
        }
    }

    /**
     * Get all your own blocks as an array
     * @return Block[]
     */
    public function myBlocks()
    {
        return $this->byOwner[$this->me];
    }
    
    /**
     * Get all your own border blocks as an array
     * @return Block[]
     */
    public function myBorders()
    {
        return $this->borders[$this->me];
    }
    
    /**
     * Performs initial setup
     * Receives map from game
     * (In debug mode it generates a map)
     * 
     * @param string $name
     */
    public function init() {
        if ($this->debugMode) {
            return $this->debugInit();
        }

        // Receive player ID
        $this->me = $this->read();

        // Receive game Width and Height
        list($this->width, $this->height) = explode(" ", $this->read());

        // Initalise all blocks with Width and Height Parameters
        for ($x = 0; $x < $this->width; $x++) {
            $this->blocks[$x] = array();
            for ($y = 0; $y < $this->height; $y++) {
                $this->blocks[$x][$y] = new Block($this, $x, $y);
            }
        }

        // Receive Production Values
        $production = explode(" ", $this->read());

        // Populate Map with Production values
        for ($i = 0; $i < $this->width * $this->height; $i++) {
            $x = $i % $this->height;
            $y = floor($i / $this->height);
            $this->blocks[$x][$y]->prod = (int) $production[$i];
        }

        // Receive Initial Map State
        $this->receiveMap();

        // Start timer (useful for debugging timeouts)
        $this->timing("GAME INITIALISED", true);
    }

    /**
     * Lets the Game know you are ready to start sending moves;
     * By sending your name
     * 
     * @param type $name
     */
    public function ready($name) {
        
        if ($this->debugMode)
        {
            $this->frame = 0;
            return false;
        }
        
        // Send Bot name to Game
        $this->send($name);

        // Set Frame to Zero
        $this->frame = 0;

        // Receive map state again (for some unknown reason)
        $this->receiveMap();

        // Log time between init and ready 
        $this->timing("READY STATE DECLARED", true);
    }

    /**
     * Add a timing event to the Log
     * This will automatically include the current frame and the time elapsed
     * The data gets appended to timing.php
     * 
     * @param type $message
     */
    public function timing($message, $reset = false) {
        if (!is_null($this->start)) {
            $elapsed = round((microtime(true) - $this->start) * 1000);
            file_put_contents("timing.php", "[#" . $this->frame . ", " . $elapsed . " ms elapsed] " . $message . "\r\n", FILE_APPEND);
            if (($this->frame > 0 && $elapsed > 1000) || ($this->frame == 0 && $elapsed > 15000)) {
                echo "GAME AUTO CRASHED: Failed to respond in time. ";
                if ($this->debugMode) {
                    $this->displayFooter();
                }
                exit();
            }
        }
        
        if ($reset) {
            $this->start = microtime(true);
        }
    }

    /**
     * Returns a block at and X, Y location
     *
     * @param int $x
     * @param int $y
     *
     * @return Block
     */
    public function get($x, $y) {
        if ($x >= $this->width || $y >= $this->height) {
            return false;
        }

        return $this->blocks[$x][$y];
    }

    /**
     * Send moves and get next frame
     */
    public function update() {
        // If in debug mode, will actually perform the move on the map
        if ($this->debugMode) {
            return $this->debugUpdate();
        }

        // Send Queued Moves to Game
        $this->sendMoves();

        // Receives new state of map
        $this->receiveMap();

        // Increase Frame counter by one
        $this->frame++;

        $this->timing("SEND MOVES AND RECEIVED NEW MAP STATE", 1);
    }

    /**
     * Find the first block by an owner
     * 
     * @param int $owner
     * @return Block
     */
    public function first($owner) {
        if (count($this->byOwner[$owner]) == 0) {
            return false;
        }
        return $this->byOwner[$owner][0];
    }

    /**
     * Get the center block of a bunch of blocks
     *
     * @param array $blocks
     * @return Block
     */
    function center(array $blocks) {
        if (count($blocks) == 0) {
            die("No blocks to find center");
        }

        $smallestDistance = 100;
        $center = $blocks[0];
        foreach ($blocks as $block) {
            $maxDistance = 0;
            foreach ($blocks as $other) {
                $distance = $block->distance($other);
                if ($distance > $maxDistance) {
                    $maxDistance = $distance;
                }
            }
            if ($maxDistance < $smallestDistance) {
                $smallestDistance = $maxDistance;
                $center = $block;
            }
        }
        return $center;
    }

    /**
     * -----------------START GAME INTERACTION FUNCTIONALITY-------------------
     */
    private function sendMoves() {
        // Collect all My Moves
        // TODO: Debug mode should also register enemy moves
        $moves = [];
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $block = $this->blocks[$x][$y];
                if ($block->owner == $this->me) {
                    $moves[] = implode(" ", array($x, $y, $block->move));
                }
            }
        }

        $output = implode(" ", $moves);

        // Send Moves to Game
        $this->send($output);

        // Now moves are send to game, reset them in Map State
        $this->resetMoves();
    }

    /**
     * Receive an parse map data from Game
     */
    private function receiveMap() {
        // Get new Map State from Server
        $frame = explode(" ", $this->read());
        
        // Seperate Owners Data from Strength Data
        $ownersLength = count($frame) - $this->height * $this->width;

        // Owners Data is the first section
        $owners = array_slice($frame, 0, $ownersLength);

        // Populate Map with Owners
        $this->parseOwners($owners);

        // Strength Data is the second Section
        $strengths = array_slice($frame, -$this->height * $this->width);

        // Parse Strengths
        $this->parseStrengths($strengths);

        // Find all New Borders
        $this->registerBorders();
    }

    /**
     * Parse owners section of map data from game
     * 
     * @param type $owners
     */
    private function parseOwners($owners) {
        $this->byOwner = array_fill(0, 100, []);

        // parse owners
        $i = 0;
        while (count($owners) !== 0) {
            $count = (int) array_shift($owners);
            $owner = (int) array_shift($owners);
            if ($owner != 0 && $owner != $this->me && !
                    in_array($owner, $this->enemies)) {
                $this->enemies[] = $owner;
            }

            for ($c = 0; $c < $count; $c++) {
                $x = $i % $this->height;
                $y = floor($i / $this->height);
                $this->blocks[$x][$y]->owner = $owner;
                $this->byOwner[$owner][] = $this->blocks[$x][$y];

                $i++;
            }
        }
    }

    /**
     * Parse strength section of map data from game
     * 
     * @param type $strengths
     */
    private function parseStrengths($strengths) {
        
        for ($i = 0; $i < $this->width * $this->height; $i++) {
            $x = $i % $this->height;
            $y = floor($i / $this->height);
            $this->blocks[$x][$y]->str = (int) $strengths[$i];
        }
    }

    /**
     * Reset all the queued moves
     */
    private function resetMoves() {
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $this->blocks[$x][$y]->move = 0;
                $this->blocks[$x][$y]->reserved = false;
                $this->blocks[$x][$y]->stuck = false;
            }
        }
    }

    /**
     * Register which blocks are borders
     */
    private function registerBorders() {
        $this->borders = array_fill(0, 100, []);
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                if ($this->blocks[$x][$y]->isBorder()) {
                    $this->borders[$this->blocks[$x][$y]->owner][] = $this->blocks[$x][$y];
                }
            }
        }
    }

    /**
     * Read output data from game
     * 
     * @return type
     */
    private function read() {
        $input = fgets(STDIN);
        file_put_contents("transmissions.txt","FRAME: " . $this->frame ."\r\nRECEIVED: \r\n" . $input . "\r\n", FILE_APPEND);
        // game ends when the game finishes sending input
        if ($input === false) {
            exit();
        }

        return rtrim($input);
    }

    /**
     * Send data to game (eg. BotName or Moves)
     * 
     * @param type $data
     */
    private function send($data) {
        file_put_contents("transmissions.txt","FRAME: " . $this->frame ."\r\nSENT: \r\n" . $data . "\r\n", FILE_APPEND);
        fwrite(STDOUT, $data . "\n");
    }

    /**
     * -----------------------START DEBUG FUNCTIONALITY-------------------------
     */

    /**
     * Debugger initializes the game
     * 
     * Generates a map similar to that of the game of size 20x20
     * Gives you a single brick in the middle
     */
    private function debugInit() {
        $this->name = "DebugBot";
        $this->me = 1;
        $this->width = 20;
        $this->height = 20;
        $this->frame = 0;
        $minProd = 5;
        $maxProd = 10;
        $startProd = 7;
        $minStr = 50;
        $maxStr = 150;
        $startStr = 100;
        $increaseProd = true;
        $increaseStr = true;

        for ($x = 0; $x < $this->height; $x++) {
            $this->blocks[$x] = array();
            for ($y = 0; $y < $this->height; $y++) {
                $this->blocks[$x][$y] = new Block($this, $x, $y);
                $this->blocks[$x][$y]->prod = $increaseProd ? $startProd++ : $startProd--;
                $this->blocks[$x][$y]->str = $increaseStr ? $startStr++ : $startStr--;
                $this->blocks[$x][$y]->owner = 0;

                if ($startProd >= $maxProd) {
                    $increaseProd = false;
                } elseif ($startProd <= $minProd) {
                    $increaseProd = true;
                } elseif (rand(0, 10) == 0) {
                    $increaseProd != $increaseStr;
                }

                if ($startStr >= $maxStr) {
                    $increaseStr = false;
                } elseif ($startStr <= $minStr) {
                    $increaseStr = true;
                } elseif (rand(0, 10) == 0) {
                    $increaseStr != $increaseStr;
                }
            }
        }
        $myBlock = $this->blocks[$this->width / 2][$this->height / 2];
        $myBlock->owner = 1;

        $this->byOwner = array_fill(0, 100, []);

        // register owners owners
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $owner = $this->blocks[$x][$y]->owner;
                $this->byOwner[$owner][] = $this->blocks[$x][$y];
            }
        }
        $this->registerBorders();
    }

    private function debugUpdate() {
        echo $this->displayHTML();

        if ($this->frame >= $this->maxSteps) {
            $this->displayFooter();
            exit();
        }

        $blocks = $this->blocks;
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $original = $this->blocks[$x][$y];
                $result = $blocks[$x][$y];
                if ($original->move == 0 && $original->owner != 0) {
                    $result->str = $original->str + $original->prod;
                } elseif ($original->move != 0 && $original->owner != 0) {
                    $targetResult = $result->get($result->move);
                    $targetOriginal = $original->get($original->move);
                    if ($original->owner == $targetResult->owner) {
                        if ($targetOriginal->move == 0) {
                            $targetResult->str = $targetOriginal->str + $original->str;
                        } else {
                            $targetResult->str = $original->str;
                        }
                    } else {
                        if ($targetResult->str < $original->str) {
                            $targetResult->str = $original->str - $targetOriginal->str;
                            $targetResult->owner = $original->owner;
                        } else {
                            $targetResult->str = $targetResult->str - $original->str;
                        }
                    }

                    $result->str = 0;
                    $result->move = 0;
                }
            }
        }
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $result = $blocks[$x][$y];
                if ($result->str > 255) {
                    $result->str = 255;
                }
            }
        }

        $this->blocks = $blocks;

        $this->byOwner = array_fill(0, 100, []);
        $this->borders = array_fill(0, 100, []);

        // register owners owners
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $this->byOwner[$this->blocks[$x][$y]->owner][] = $this->blocks[$x][$y];
            }
        }

        $this->frame++;
        $this->registerBorders();
    }

    /**
     * Return a HTML version of the current state of the game map
     */
    public function displayHTML() {
        $this->displayHeader();
        // Owner colors
        $ownerColors = array(0 => "#999999", 1 => "#FF0000", 2 => "#00FF00", 3 => "#0000FF");

        $output = "<div class='frame' data-frame='" . $this->frame . "'>";
        $output .= "<div class='wrapper'>";
        $output .= "<h1>Frame: " . $this->frame . "</h1>";
        $output .= "<table width='500' height='500'>";

        foreach ($this->blocks as $x => $ys) {
            $output .= "<tr>";
            foreach ($ys as $y => $block) {
                $productionColour = ($block->owner == 1 ? 255 : 100) . "," . 100 . "," . 100 . "," . round($block->prod / 20, 1);
                $strengthSize = round(($block->str / 255) * 10);
                $ownerColor = $ownerColors[$block->owner];
                $output .= "<td title='$x:$y Str:" . $block->str . " Prod:" . $block->prod . "' style='background-color:rgba($productionColour)'>";
                $output .= "    <div style='padding:" . $strengthSize . "px; background-color:$ownerColor; border:" . ($block->str == 255 ? "1" : "0") . "px solid #FFF;'></div>";
                $output .= "</td>";
            }
            $output .= "</tr>";
        }

        $output .= "</table>";
        $output .= "</div>";
        $output .= "</div>";

        return $output;
    }

    /**
     * Whether or not the debug header has been sent to the server
     * @var int
     */
    private $sentHeader;

    /**
     * Display head for debugger
     */
    private function displayHeader() {
        if ($this->debugMode && !$this->sentHeader) {
            require("head.html");
            $this->sentHeader = true;
        }
    }

    /**
     * Whether or not the debug footer has been sent to the server
     * @var int
     */
    private $sentFooter;

    /**
     * Closes off debugger HTML tags
     * We can't be having malformed HTML.
     * What are we, animals!?
     */
    private function displayFooter() {
        if ($this->debugMode && !$this->sentFooter) {
            require("foot.html");
            $this->sentFooter = true;
        }
    }

}
