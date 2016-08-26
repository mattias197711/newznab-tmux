<?php

use nntmux\ReleaseRatings;
use nntmux\db\Settings;

// Page is accessible only by the rss token
if ($page->users->isLoggedIn()) {
	$rssToken = $page->userdata['rsstoken'];
} else {
	if ($page->settings->getSetting('registerstatus') == Settings::REGISTER_STATUS_API_ONLY) {
		if (!isset($_GET["apikey"])) {
			header("X-DNZB-RCode: 400");
			header("X-DNZB-RText: Bad request, please supply all parameters!");
			$page->show403();
		} else {
			$res = $page->users->getByRssToken($_GET['apikey']);
		}
	} else {
		if (!isset($_GET["apikey"])) {
			header("X-DNZB-RCode: 400");
			header("X-DNZB-RText: Bad request, please supply all parameters!");
			$page->show403();
		} else {
			$res = $page->users->getByRssToken($_GET['apikey']);
		}
	}
	if (!isset($res)) {
		header("X-DNZB-RCode: 401");
		header("X-DNZB-RText: Unauthorised, wrong user ID or rss key!");
		$page->show403();
	} else {
		$uid = $res['id'];
		$rssToken = $res['rsstoken'];
	}
}

if (isset($_GET['i']) && isset($rssToken) && isset($_GET['m'])) {

	(new ReleaseRatings(['Settings' => $page->settings]))->addRating($_GET['i'], $uid, $video, $audio, $vote, $passworded, $spam);

}
