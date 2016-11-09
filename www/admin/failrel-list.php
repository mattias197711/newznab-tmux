<?php

require_once("config.php");


use nntmux\DnzbFailures;

$page = new AdminPage();

$failed = new DnzbFailures(['Settings' => $page->settings]);

$page->title = "Failed Releases List";

$frelcount = $failed->getCount();

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$page->smarty->assign([
	'pagertotalitems'   => $frelcount,
	'pagerquerysuffix'  => "#results",
	'pageroffset'       => $offset,
	'pageritemsperpage' => ITEMS_PER_PAGE,
	'pagerquerybase'    => WWW_TOP."/failrel-list.php?offset=",
	]
);
$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$frellist = $failed->getFailedRange($offset, ITEMS_PER_PAGE);
$page->smarty->assign('releaselist', $frellist);

$page->content = $page->smarty->fetch('failrel-list.tpl');
$page->render();

