<?php

namespace Halite;

class Map
{

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

    /*
     * Current frame #
     * @var int
     */
    public $frame;

    /**
     * Debug mode
     * @var bool
     */
    public $debugMode;

    /**
     * Map constructor.
     * @param bool $debugMode
     */
    public function __construct($debugMode = false)
    {
        $this->debugMode = $debugMode;
    }

    /**
     * @param string $name
     */
    public function init($name)
    {
        if ($this->debugMode) {
            return $this->debugInit();
        }

        $this->me = $this->read();

        // get dimensions
        list($this->width, $this->height) = explode(" ", $this->read());

        // init blocks
        for ($x = 0; $x < $this->height; $x++) {
            $this->blocks[$x] = array();
            for ($y = 0; $y < $this->height; $y++) {
                $this->blocks[$x][$y] = new Block($this, $x, $y);
            }
        }

        // parse productions
        $production = explode(" ", $this->read());

        for ($i = 0; $i < $this->width * $this->height; $i++) {
            $x = $i % $this->height;
            $y = floor($i / $this->height);
            $this->blocks[$x][$y]->prod = (int)$production[$i];
        }

        $this->frame = 0;

        // receive first frame
        $this->frame();

        // send bot's name
        $this->send($name);
    }

    private function debugInit()
    {
        echo "<!doctype html><html>";
        echo "<style>html,body {margin:0;background-color:#000 !important; color:#FFF !important;}";
        echo ".frame { display:none;}";
        echo "pre { color:#FFF !important;}";
        echo ".wrapper { width:500px; margin: 0 auto;}";
        echo ".wrapper table td {text-align:center;width:25px; height:25px; }";
        echo ".wrapper table td div {margin:0 auto;width:0;height:0;}";
        echo "frame {}";
        echo "</style>";
        echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha.5/css/bootstrap.css' />";
        echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/css/tether.css' />";
        echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/9.5.4/css/bootstrap-slider.css' />";
        echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js'></script>";
        echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.js'></script>";
        echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha.5/js/bootstrap.js'></script>";
        echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/9.5.4/bootstrap-slider.js'></script>";
        echo "</head><body><div class='wrapper'>";
        $this->name = "DebugBot";
        $this->me = 1;
        $this->width = 20;
        $this->height = 20;
        $this->frame = 1;
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
        $this->borders = array_fill(0, 100, []);

        // register owners owners
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $owner = $this->blocks[$x][$y]->owner;
                $this->byOwner[$owner][] = $this->blocks[$x][$y];
            }
        }
    }

    /**
     * Get the block at and X, Y location
     *
     * @param int $x
     * @param int $y
     *
     * @return Block
     */
    public function get($x, $y)
    {
        if ($x >= $this->width || $y >= $this->height) {
            return false;
        }

        return $this->blocks[$x][$y];
    }

    /**
     * Send moves and get next frame
     */
    public function update()
    {
        // send moves

        if ($this->debugMode) {
            $this->display();
            if ($this->frame > 50) {
                $this->finish();
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
            return false;
        }

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

        // send moves
        $this->send($output);

        // get next frame
        $this->frame();
    }

    /**
     * Find the first block by an owner
     * @param int $owner
     * @return Block
     */
    public function first($owner)
    {
        if (count($this->byOwner[$owner]) == 0) {
            return false;
        }
        return $this->byOwner[$owner][0];
    }

    /**
     * Start time of frame
     * @var int
     */
    private $start;

    public function timing($message)
    {
        if (!is_null($this->start)) {
            $time = round((microtime(true) - $this->start) * 1000);
            file_put_contents("timing.php", "(" . $time . "ms)" . $message . "\r\n", FILE_APPEND);
            if ($time > 1000) {
                file_put_contents("Took to long to execute", $output, FILE_APPEND);
                exit();
            }
        }
    }

    private function frame()
    {

        timing("Finish step [" . $this->frame . "]");

        $this->frame++;

        // get frame contents
        $frame = explode(" ", $this->read());

        // get owners section
        $ownersLength = count($frame) - 1 - $this->height * $this->width;
        $owners = array_slice($frame, 0, $ownersLength);

        $this->byOwner = array_fill(0, 100, []);
        $this->borders = array_fill(0, 100, []);

        // parse owners
        $i = 0;
        while (count($owners) !== 0) {
            $count = (int)array_shift($owners);
            $owner = (int)array_shift($owners);
            for ($c = 0; $c < $count; $c++) {
                $x = $i % $this->height;
                $y = floor($i / $this->height);
                $this->blocks[$x][$y]->owner = $owner;
                $this->byOwner[$owner][] = $this->blocks[$x][$y];
                $i++;
            }
        }

        // parse strengths
        $strength = array_slice($frame, -$this->height * $this->width);

        for ($i = 0; $i < $this->width * $this->height; $i++) {
            $x = $i % $this->height;
            $y = floor($i / $this->height);
            $this->blocks[$x][$y]->str = (int)$strength[$i];

            // reset moves
            $this->blocks[$x][$y]->move = 0;
        }

        $this->start = microtime(true);

        $this->registerBorders();
    }

    public function registerBorders()
    {
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $block = $this->blocks[$x][$y];
                if ($block->isBorder()) {
                    $this->borders[$block->owner][] = $block;
                }
            }
        }

    }

    private function read()
    {
        $input = fgets(STDIN);

        // game ends when input is over
        if ($input === false) {
            exit();
        }

        return rtrim($input);
    }

    private function send($data)
    {
        fwrite(STDOUT, $data . "\n");
    }


    // useful for debugging map state
    public function display()
    {
        $ownerColors = array(0 => "#999999", 1 => "#FF0000", 2 => "#00FF00", 3 => "#0000FF");
        $output = "<div class='frame' data-frame='" . $this->frame . "'><div class='wrapper'><h1>Frame: " . $this->frame . "</h1><table width='500' height='500'>";
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
        $output .= "</table></div></div>";
        if ($this->debugMode) {
            echo $output;
        } else {
            file_put_contents("map-state.html", $output, FILE_APPEND);
        }

    }

    public function reset()
    {
        if (file_exists("map-state.html"))
            unlink("map-state.html");
    }

    /**
     * Get the center block of a bunch of blocks
     *
     * @param array $blocks
     * @return Block
     */
    function center(array $blocks)
    {
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

    public function finish()
    {
        echo "<input type='slider' style='width:500px;' class='slider' data-slider-min=\"1\" data-slider-max=\"" . $this->frame . "\" data-slider-step=\"1\" /></div>";
        echo "<script>";
        echo "$('.frame[data-frame=1]').show();\nvar mySlider = $(\"input.slider\").slider().on('slide', function(frame){\n  $('.frame').hide(); \n$('.frame[data-frame=' + frame.value + ']').show();\n });\n";
        echo "</script></body></html>";
    }
}
