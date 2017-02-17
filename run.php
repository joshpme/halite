<?php

function removeFile($filename) {
	if (file_exists($filename)) {
		unlink($filename);
	}
}

function getNum($key, $default = 0) {
	if (isset($_GET[$key]) && ctype_digit($_GET[$key])) {
		return $_GET['id'];
	}
	return $default;
}

$id = getNum("id", 1);
$size = getNum("size", 20);

removeFile("timing.php");
removeFile("debug.txt");

$results = array();
exec("runGame.bat " . $id . " \"" . $size . " " . $size . "\"", $results);

$bots = [];
$seed = 0;
$places = [];
$bot = true;
$errors = false;
$error = [];

foreach ($results as $result) {
    $line = trim($result);

    if ($line == "")
        continue;

    if (strpos($result, ".hlt") !== false) {
        list($output, $seed) = explode(" ", $line);
        $bot = false;
    } elseif ($bot) {
        $bots[] = $line;
    } elseif (!$errors) {
        $places[] = explode(" ", $line);
        $errors = count($places) == count($bots);
    } else {
        $error = explode(" ", $line);
    }
}

// Determine placings
$placings = [];
foreach ($places as $key => $place) {
    $placings[$place[1]] = array("name" => $bots[$key], "result" => $place[2]);
}

ksort($placings);
reset($placings);
$first = current($placings);
end($placings);
$last = current($placings);

$errorLogs = [];
foreach ($error as $file) {
    $errorLogs[] = file_get_contents($file);
}

echo json_encode(array(
    "seed" => $seed,
    "first" => $first,
    "last" => $last,
    "error" => $errorLogs,
    "filename" => $output,
    "data" => file_get_contents($output)
        ), JSON_PRETTY_PRINT
);

// Clean up files
foreach ($error as $file) {
	removeFile($file);
}

removeFile($output);
removeFile("timing.php");


