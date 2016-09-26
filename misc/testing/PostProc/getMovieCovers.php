<?php
//This script will update all records in the movieinfo table where there is no cover
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use nntmux\db\DB;
use nntmux\Movie;

$pdo = new DB();

$movie = new Movie(['Echo' => true, 'Settings' => $pdo]);

$movies = $pdo->queryDirect("SELECT imdbid FROM movieinfo WHERE cover = 0 ORDER BY year ASC, id DESC");
if ($movies instanceof \Traversable) {
	echo $pdo->log->primary("Updating " . number_format($movies->rowCount()) . " movie covers.");
	foreach ($movies as $mov) {
		$starttime = microtime(true);
		$mov = $movie->updateMovieInfo($mov['imdbid']);

		// tmdb limits are 30 per 10 sec, not certain for imdb
		$diff = floor((microtime(true) - $starttime) * 1000000);
		if (333333 - $diff > 0) {
			echo "\nsleeping\n";
			usleep(333333 - $diff);
		}
	}
	echo "\n";
}
