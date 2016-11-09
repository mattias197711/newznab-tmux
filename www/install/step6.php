<?php
require_once realpath(__DIR__ . DIRECTORY_SEPARATOR . 'install.php');

use nntmux\Install;
use nntmux\Users;

$page = new InstallPage();
$page->title = "Setup Admin User";

$cfg = new Install();

if (!$cfg->isInitialized()) {
	header("Location: index.php");
	die();
}

$cfg = $cfg->getSession();

if ($page->isPostBack()) {
	$cfg->doCheck = true;

	$cfg->ADMIN_USER = trim($_POST['user']);
	$cfg->ADMIN_PASS = trim($_POST['pass']);
	$cfg->ADMIN_EMAIL = trim($_POST['email']);

	if ($cfg->ADMIN_USER == '' || $cfg->ADMIN_PASS == '' || $cfg->ADMIN_EMAIL == '') {
		$cfg->error = true;
	} else {
		switch (DB_SYSTEM) {
			case 'mysql':
				$adapter = 'MySql';
				break;
			case 'pgsql':
				$adapter = 'PostgreSql';
				break;
			default:
				break;
		}

		if (isset($adapter)) {
			if (empty(DB_SOCKET)) {
				$host = empty(DB_PORT) ? DB_HOST : DB_HOST . ':' . DB_PORT;
			} else {
				$host = DB_SOCKET;
			}

			lithium\data\Connections::add('default',
				[
					'type'       => 'database',
					'adapter'    => $adapter,
					'host'       => $host,
					'login'      => DB_USER,
					'password'   => DB_PASSWORD,
					'database'   => DB_NAME,
					'encoding'   => 'UTF-8',
					'persistent' => false,
				]
			);
		}

		$user = new Users();
		if (!$user->isValidUsername($cfg->ADMIN_USER)) {
			$cfg->error = true;
			$cfg->ADMIN_USER = '';
		} else {
			$usrCheck = $user->getByUsername($cfg->ADMIN_USER);
			if ($usrCheck) {
				$cfg->error = true;
				$cfg->ADMIN_USER = '';
			}
		}
		if (!$user->isValidEmail($cfg->ADMIN_EMAIL)) {
			$cfg->error = true;
			$cfg->ADMIN_EMAIL = '';
		}

		if (!$cfg->error) {
			$cfg->adminCheck = $user->add($cfg->ADMIN_USER, $cfg->ADMIN_PASS, $cfg->ADMIN_EMAIL, 2, '', '');
			if (!is_numeric($cfg->adminCheck)) {
				$cfg->error = true;
			} else {
				$user->login($cfg->adminCheck, "", 1);
			}
		}
	}

	if (!$cfg->error) {
		$cfg->setSession();
		header("Location: ?success");
		die();
	}
}

$page->smarty->assign('cfg', $cfg);
$page->smarty->assign('page', $page);

$page->content = $page->smarty->fetch('step6.tpl');
$page->render();
