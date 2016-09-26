<?php
namespace nntmux;

class Install
{
	public $DB_SYSTEM;
	public $DB_TYPE;
	public $DB_HOST = "127.0.0.1";
	public $DB_PORT;
	public $DB_SOCKET;
	public $DB_USER;
	public $DB_PASSWORD;
	public $DB_NAME = "nntmux";
	public $NNTP_USERNAME;
	public $NNTP_PASSWORD;
	public $NNTP_SERVER;
	public $NNTP_PORT;
	public $NNTP_SSLENABLED;
	public $NNTP_SOCKET_TIMEOUT;
	public $NNTP_USERNAME_A;
	public $NNTP_PASSWORD_A;
	public $NNTP_SERVER_A;
	public $NNTP_PORT_A;
	public $NNTP_SSLENABLED_A;
	public $NNTP_SOCKET_TIMEOUT_A;
	public $COVERS_PATH;
	public $CONFIG_PATH;
	public $coverPathCheck = false;
	public $SMARTY_DIR;
	public $DB_DIR;
	public $INSTALL_DIR;
	public $ADMIN_USER;
	public $ADMIN_FNAME;
	public $ADMIN_LNAME;
	public $ADMIN_PASS;
	public $ADMIN_EMAIL;
	public $NZB_PATH;
	public $TMP_PATH;
	public $UNRAR_PATH;
	public $WWW_TOP;
	public $COMPILED_CONFIG;
	public $doCheck = false;
	public $sha1Check;
	public $cryptCheck;
	public $iconvCheck;
	public $PDOCheck;
	public $gdCheck;
	public $curlCheck;
	public $cacheCheck;
	public $animeCoversCheck;
	public $audioCoversCheck;
	public $audiosampleCoversCheck;
	public $bookCoversCheck;
	public $consoleCoversCheck;
	public $movieCoversCheck;
	public $musicCoversCheck;
	public $previewCoversCheck;
	public $sampleCoversCheck;
	public $videoCoversCheck;
	public $configCheck;
	public $lockCheck;
	public $pearCheck;
	public $schemaCheck;

	/**
	 * @var bool Is the PHP version higher than NN_MINIMUM_PHP_VERSION?
	 */
	public $phpCheck;
	public $minPhpVersion = NN_MINIMUM_PHP_VERSION;

	public $timelimitCheck;
	public $memlimitCheck;
	public $rewriteCheck;
	public $opensslCheck;
	public $exifCheck;
	public $timezoneCheck;
	public $dbConnCheck;
	public $dbNameCheck;
	public $dbCreateCheck;
	public $emessage;
	public $nntpCheck;
	public $adminCheck;
	public $nzbPathCheck;
	public $saveConfigCheck;
	public $saveLockCheck;
	public $error = false;

	// Step 3 (openssl) properties.
	public $NN_SSL_CAFILE;
	public $NN_SSL_CAPATH;
	public $NN_SSL_VERIFY_PEER;
	public $NN_SSL_VERIFY_HOST;
	public $NN_SSL_ALLOW_SELF_SIGNED;

	// Does the sessions save path have RW permissions?
	public $sessionsPathPermissions;

	public function __construct()
	{
		$this->CONFIG_PATH = NN_CONFIGS;
		$this->COVERS_PATH = NN_RES . 'covers' . DS;
		$this->DB_DIR = NN_RES . 'db' . DS . 'schema' . DS;
		$this->SMARTY_COMPILED_TEMPLATES = NN_RES . 'smarty' . DS . 'templates_c' . DS;
		$this->INSTALL_DIR = NN_WWW . 'install';
		$this->NZB_PATH = NN_RES . 'nzb' . DS;
		$this->TMP_PATH = NN_RES . 'tmp' . DS;
		$this->UNRAR_PATH = $this->TMP_PATH . 'unrar' . DS;
		$this->WWW_TOP = NN_WWW;
	}

	public function setSession()
	{
		$_SESSION['cfg'] = serialize($this);
	}

	public function getSession()
	{
		$tmpCfg = unserialize($_SESSION['cfg']);
		$tmpCfg->error = false;
		$tmpCfg->doCheck = false;
		return $tmpCfg;
	}

	public function isInitialized()
	{
		return (isset($_SESSION['cfg']) && is_object(unserialize($_SESSION['cfg'])));
	}

	public function isLocked()
	{
		return (file_exists($this->INSTALL_DIR . '/install.lock') ? true : false);
	}

	public function setConfig($tmpCfg)
	{
		preg_match_all('/define\((.*?)\)/i', $tmpCfg, $matches);
		$defines = $matches[1];
		foreach ($defines as $define) {
			$define = str_replace('\'', '', $define);
			list($defName, $defVal) = explode(',', $define);
			$this->{$defName} = trim($defVal);
		}
	}

	public function saveConfig()
	{
		$tmpCfg = file_get_contents($this->INSTALL_DIR . DS . 'config.php.tpl');
		$tmpCfg = str_replace('%%DB_SYSTEM%%', $this->DB_SYSTEM, $tmpCfg);
		$tmpCfg = str_replace('%%DB_HOST%%', $this->DB_HOST, $tmpCfg);
		$tmpCfg = str_replace('%%DB_PORT%%', $this->DB_PORT, $tmpCfg);
		$tmpCfg = str_replace('%%DB_SOCKET%%', $this->DB_SOCKET, $tmpCfg);
		$tmpCfg = str_replace('%%DB_USER%%', $this->DB_USER, $tmpCfg);
		$tmpCfg = str_replace('%%DB_PASSWORD%%', $this->DB_PASSWORD, $tmpCfg);
		$tmpCfg = str_replace('%%DB_NAME%%', $this->DB_NAME, $tmpCfg);

		$tmpCfg = str_replace('%%NNTP_USERNAME%%', $this->NNTP_USERNAME, $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_PASSWORD%%', $this->NNTP_PASSWORD, $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_SERVER%%', $this->NNTP_SERVER, $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_PORT%%', $this->NNTP_PORT, $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_SSLENABLED%%', ($this->NNTP_SSLENABLED ? "true" : "false"), $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_SOCKET_TIMEOUT%%', $this->NNTP_SOCKET_TIMEOUT, $tmpCfg);

		$tmpCfg = str_replace('%%NNTP_USERNAME_A%%', $this->NNTP_USERNAME_A, $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_PASSWORD_A%%', $this->NNTP_PASSWORD_A, $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_SERVER_A%%', $this->NNTP_SERVER_A, $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_PORT_A%%', $this->NNTP_PORT_A, $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_SSLENABLED_A%%', ($this->NNTP_SSLENABLED_A ? "true" : "false"), $tmpCfg);
		$tmpCfg = str_replace('%%NNTP_SOCKET_TIMEOUT_A%%', $this->NNTP_SOCKET_TIMEOUT_A, $tmpCfg);

		$tmpCfg = str_replace('%%NN_SSL_CAFILE%%', $this->NN_SSL_CAFILE, $tmpCfg);
		$tmpCfg = str_replace('%%NN_SSL_CAPATH%%', $this->NN_SSL_CAPATH, $tmpCfg);
		$tmpCfg = str_replace('%%NN_SSL_VERIFY_PEER%%', $this->NN_SSL_VERIFY_PEER, $tmpCfg);
		$tmpCfg = str_replace('%%NN_SSL_VERIFY_HOST%%', $this->NN_SSL_VERIFY_HOST, $tmpCfg);
		$tmpCfg = str_replace('%%NN_SSL_ALLOW_SELF_SIGNED%%', $this->NN_SSL_ALLOW_SELF_SIGNED, $tmpCfg);

		$this->COMPILED_CONFIG = $tmpCfg;
		return @file_put_contents(NN_CONFIGS . DS . 'config.php', $tmpCfg);
	}

	public function saveInstallLock()
	{
		return @file_put_contents($this->INSTALL_DIR . DS . 'install.lock', '');
	}

}
