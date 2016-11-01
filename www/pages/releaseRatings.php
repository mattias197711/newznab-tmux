<?php

use nntmux\ReleaseRatings;
use nntmux\Releases;
use nntmux\db\Settings;

// Page is accessible only by the rss token, or logged in users.
if ($page->users->isLoggedIn()) {
	$uid = $page->users->currentUserId();
	$rssToken = $page->userdata['rsstoken'];
} else {
	if ($page->settings->getSetting('registerstatus') == Settings::REGISTER_STATUS_API_ONLY) {
		if (!isset($_GET["apikey"])) {
			header("X-DNZB-RCode: 400");
			header("X-DNZB-RText: Bad request, please supply all parameters!");
			$page->show403();
		} else {
			$res = $page->users->getByRssToken($_GET["apikey"]);
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

if (isset($_GET['i']) && isset($uid) && is_numeric($uid) && isset($rssToken) && isset($_GET['m'])) {
	$relid = (new Releases(['Settings' => $page->settings]))->getByGuid($_GET['i']);
	$rating = new ReleaseRatings(['Settings' => $page->settings]);

	switch ('m') {
		case 'rp':
			$rating->addRating($relid, $uid, '', '', '', $_GET['rp'], '', '');
			break;
		case 'rpr':
			$rating->addRating($relid, $uid, '', '', '', '', '', $_GET['pr']);
			break;
		case 'rs':
			$rating->addRating($relid, $uid, '', '', '', '', $_GET['rs'], '');
			break;
		case 'r':
			if (isset($_GET['r']) && $_GET['r'] === 'videoQuality' && isset($_GET['rn'])) {
				$rating->addRating($relid, $uid, $_GET['rn'], '', '', '', '', '');
			} elseif (isset($_GET['r']) && $_GET['r'] === 'audioQuality' && isset($_GET['rn'])) {
				$rating->addRating($relid, $uid, '', $_GET['rn'], '', '', '', '');
			}
			break;
		case 'v':
			if (isset($_GET['r']) && $_GET['r'] === 'overall' && isset($_GET['v'])) {
				$rating->addRating($relid, $uid, '', '', $_GET['v'], '', '', '');
			}
			break;
	}

	$ratings = $rating->getAllRatings();
}
