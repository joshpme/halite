<?php
$id = rand(0, 1000000000);

if (isset($_GET['id']) && ctype_digit($_GET['id']))
    $id = $_GET['id'];

$size = 20;
if (isset($_GET['size']) && ctype_digit($_GET['size']))
    $size = $_GET['size'];

if (file_exists("timing.php")) {
    unlink("timing.php");
}

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
foreach ($error as $file)
    $errorLogs[] = file_get_contents($file);

echo json_encode(array(
    "seed" => $seed,
    "first" => $first,
    "last" => $last,
    "error" => $errorLogs,
    "filename" => $output,
    "data" => file_get_contents($output)
        ), JSON_PRETTY_PRINT
);

foreach ($error as $file) {
    if (file_exists($file)) {
        unlink($file);
    }
}

if (file_exists($output)) {
    unlink($output);
}

if (file_exists("timing.php")) {
    unlink("timing.php");
}