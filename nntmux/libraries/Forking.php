<?php
namespace nntmux\libraries;

use app\models\Settings;
use nntmux\Category;
use nntmux\ColorCLI;
use nntmux\MiscSorter;
use nntmux\NameFixer;
use nntmux\Nfo;
use nntmux\NNTP;
use nntmux\NZB;
use nntmux\RequestID;
use nntmux\db\DB;
use nntmux\processing\PostProcess;

/**
 * Class Forking
 *
 * This forks various newznab scripts.
 *
 * For example, you get all the ID's of the active groups in the groups table, you then iterate over them and spawn
 * processes of misc/update_binaries.php passing the group ID's.
 *
 * @package nntmux\libraries
 */
class Forking extends \fork_daemon
{
	const OUTPUT_NONE     = 0; // Don't display child output.
	const OUTPUT_REALTIME = 1; // Display child output in real time.
	const OUTPUT_SERIALLY = 2; // Display child output when child is done.


	/**
	 * Setup required parent / self vars.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_colorCLI = new ColorCLI();

		$this->register_logging(
			[0 => $this, 1 => 'logger'],
			(defined('NN_MULTIPROCESSING_LOG_TYPE') ? NN_MULTIPROCESSING_LOG_TYPE : \fork_daemon::LOG_LEVEL_INFO)
		);

		if (defined('NN_MULTIPROCESSING_MAX_CHILD_WORK')) {
			$this->max_work_per_child_set(NN_MULTIPROCESSING_MAX_CHILD_WORK);
		} else {
			$this->max_work_per_child_set(1);
		}

		if (defined('NN_MULTIPROCESSING_MAX_CHILD_TIME')) {
			$this->child_max_run_time_set(NN_MULTIPROCESSING_MAX_CHILD_TIME);
		} else {
			$this->child_max_run_time_set(1800);
		}

		// Use a single exit method for all children, makes things easier.
		$this->register_parent_child_exit([0 => $this, 1 => 'childExit']);

		if (defined('NN_MULTIPROCESSING_CHILD_OUTPUT_TYPE')) {
			switch (NN_MULTIPROCESSING_CHILD_OUTPUT_TYPE) {
				case 0:
					$this->outputType = self::OUTPUT_NONE;
					break;
				case 1:
					$this->outputType = self::OUTPUT_REALTIME;
					break;
				case 2:
					$this->outputType = self::OUTPUT_SERIALLY;
					break;
				default:
					$this->outputType = self::OUTPUT_REALTIME;
			}
		} else {
			$this->outputType = self::OUTPUT_REALTIME;
		}

		$this->dnr_path = PHP_BINARY . ' ' . NN_MULTIPROCESSING . '.do_not_run' . DS . 'switch.php "php  ';
	}

	/**
	 * Setup the class to work on a type of work, then process the work.
	 * Valid work types:
	 *
	 * @param string $type    The type of multiProcessing to do : backfill, binaries, releases, postprocess
	 * @param array  $options Array containing arguments for the type of work.
	 *
	 * @throws ForkingException
	 */
	public function processWorkType($type, array $options = [])
	{
		// Set/reset some variables.
		$startTime = microtime(true);
		$this->workType = $type;
		$this->workTypeOptions = $options;
		$this->processAdditional = $this->processNFO = $this->processTV = $this->processMovies = $this->tablePerGroup = $this->ppRenamedOnly = false;
		$this->work = [];

		// Init Settings here, as forking causes errors when it's destroyed.
		$this->pdo = new DB();

		// Process extra work that should not be forked and done before forking.
		$this->processStartWork();

		// Get work to fork.
		$this->getWork();

		// Now we destroy settings, to prevent errors from forking.
		unset($this->pdo);

		// Process the work we got.
		$this->processWork();

		// Process extra work that should not be forked and done after.
		$this->processEndWork();

		if (NN_ECHOCLI) {
			$this->_colorCLI->doEcho(
				$this->_colorCLI->header(
					'Multi-processing for ' . $this->workType . ' finished in ' . (microtime(true) - $startTime) .
					' seconds at ' . date(DATE_RFC2822) . '.' . PHP_EOL
				)
			);
		}
	}

	/**
	 * Only post process renamed movie / tv releases?
	 * @var bool
	 */
	private $ppRenamedOnly;

	/**
	 * Get work for our workers to work on, set the max child processes here.
	 */
	private function getWork()
	{
		$maxProcesses = 0;

		switch ($this->workType) {

			case 'backfill':
				$maxProcesses = $this->backfillMainMethod();
				break;

			case 'binaries':
				$maxProcesses = $this->binariesMainMethod();
				break;

			case 'fixRelNames_standard':
			case 'fixRelNames_predbft':
				$maxProcesses = $this->fixRelNamesMainMethod();
				break;

			case 'releases':
				$maxProcesses = $this->releasesMainMethod();
				break;

			case 'postProcess_ama':
				$this->processSingle();
				break;

			case 'postProcess_add':
				$maxProcesses = $this->postProcessAddMainMethod();
				break;

			case 'postProcess_mov':
				$this->ppRenamedOnly = (isset($this->workTypeOptions[0]) && $this->workTypeOptions[0] === true ? true : false);
				$maxProcesses = $this->postProcessMovMainMethod();
				break;

			case 'postProcess_nfo':
				$maxProcesses = $this->postProcessNfoMainMethod();
				break;

			case 'postProcess_sha':
				$this->processSharing();
				break;

			case 'postProcess_tv':
				$this->ppRenamedOnly = (isset($this->workTypeOptions[0]) && $this->workTypeOptions[0] === true ? true : false);
				$maxProcesses = $this->postProcessTvMainMethod();
				break;

			case 'request_id':
				$maxProcesses = $this->requestIDMainMethod();
				break;

			case 'safe_backfill':
				$maxProcesses = $this->safeBackfillMainMethod();
				break;

			case 'safe_binaries':
				$maxProcesses = $this->safeBinariesMainMethod();
				break;

			case 'update_per_group':
				$maxProcesses = $this->updatePerGroupMainMethod();
				break;
		}

		$this->setMaxProcesses($maxProcesses);
	}

	/**
	 * Process work if we have any.
	 */
	private function processWork()
	{
		$this->_workCount = count($this->work);
		if ($this->_workCount > 0) {

			if (NN_ECHOCLI) {
				$this->_colorCLI->doEcho(
					$this->_colorCLI->header(
						'Multi-processing started at ' . date(DATE_RFC2822) . ' for ' . $this->workType . ' with ' . $this->_workCount .
						' job(s) to do using a max of ' . $this->maxProcesses . ' child process(es).'
					)
				);
			}

			$this->addwork($this->work);
			$this->process_work(true);
		} else {
			if (NN_ECHOCLI) {
				$this->_colorCLI->doEcho(
					$this->_colorCLI->header('No work to do!')
				);
			}
		}
	}

	/**
	 * Process any work that does not need to be forked, but needs to run at the end.
	 */
	private function processStartWork()
	{
		switch ($this->workType) {
			//case 'safe_backfill':
			case 'safe_binaries':
				$this->_executeCommand(
					PHP_BINARY . ' ' . NN_NIX . 'tmux/bin/update_groups.php'
				);
				break;
		}
	}

	/**
	 * Process any work that does not need to be forked, but needs to run at the end.
	 */
	private function processEndWork()
	{
		switch ($this->workType) {
			case 'releases':
				if ($this->tablePerGroup === true) {
					$this->_executeCommand(
						$this->dnr_path . 'releases  ' . count($this->work) . '_"'
					);
				}
				break;
			case 'update_per_group':
				$this->_executeCommand(
					$this->dnr_path . 'releases  ' . count($this->work) . '_"'
				);
				break;
			case 'safe_backfill':
				$this->_executeCommand(
					$this->dnr_path . 'backfill_all_quantity  ' . $this->safeBackfillGroup . '  1000' . '"'
				);
				break;
		}
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////// All backFill code here ////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * @return int
	 */
	private function backfillMainMethod()
	{
		$this->register_child_run([0 => $this, 1 => 'backFillChildWorker']);
		// The option for backFill is for doing up to x articles. Else it's done by date.
		$this->work = $this->pdo->query(
			sprintf(
				'SELECT name %s FROM groups WHERE backfill = 1',
				($this->workTypeOptions[0] === false ? '' : (', ' . $this->workTypeOptions[0] . ' AS max'))
			)
		);
		return Settings::value('..backfillthreads');
	}

	public function backFillChildWorker($groups, $identifier = '')
	{
		foreach ($groups as $group) {
			$this->_executeCommand(
				PHP_BINARY . ' ' . NN_UPDATE . 'backfill.php ' .
				$group['name'] . (isset($group['max']) ? (' ' . $group['max']) : '')
			);
		}
	}

	/**
	 * @return int
	 */
	private function safeBackfillMainMethod()
	{
		$this->register_child_run([0 => $this, 1 => 'safeBackfillChildWorker']);

		$run = $this->pdo->query("SELECT (SELECT value FROM tmux WHERE setting = 'backfill_qty') AS qty, (SELECT value FROM tmux WHERE setting = 'backfill') AS backfill, (SELECT value FROM tmux WHERE setting = 'backfill_order') AS orderby, (SELECT value FROM tmux WHERE setting = 'backfill_days') AS days, (SELECT value FROM settings WHERE setting = 'maxmssgs') AS maxmsgs");
		$threads = Settings::value('..backfillthreads');

		$orderby = "ORDER BY a.last_record ASC";
		switch ((int)$run[0]['orderby']) {
			case 1:
				$orderby = "ORDER BY first_record_postdate DESC";
				break;

			case 2:
				$orderby = "ORDER BY first_record_postdate ASC";
				break;

			case 3:
				$orderby = "ORDER BY name ASC";
				break;

			case 4:
				$orderby = "ORDER BY name DESC";
				break;

			case 5:
				$orderby = "ORDER BY a.last_record DESC";
				break;
		}

		$backfilldays = '';
		if ($run[0]['days'] == 1) {
			$backfilldays = "backfill_target";
		} elseif ($run[0]['days'] == 2) {
			$backfilldays = round(abs(strtotime(date("Y-m-d")) - strtotime(Settings::value('..safebackfilldate'))) / 86400);
		}

		$data = $this->pdo->queryOneRow(
			sprintf(
				"SELECT g.name,
				g.first_record AS our_first,
				MAX(a.first_record) AS their_first,
				MAX(a.last_record) AS their_last
				FROM groups g
				INNER JOIN short_groups a ON g.name = a.name
				WHERE g.first_record IS NOT NULL
				AND g.first_record_postdate IS NOT NULL
				AND g.backfill = 1
				AND (NOW() - INTERVAL %s DAY) < g.first_record_postdate
				GROUP BY a.name, a.last_record, g.name, g.first_record
				%s",
				$backfilldays,
				$orderby
			)
		);

		$count = 0;
		if ($data['name']) {
			$this->safeBackfillGroup = $data['name'];

			$count = ($data['our_first'] - $data['their_first']);
		}

		if ($count > 0) {
			if ($count > ($run[0]['qty'] * $threads)) {
				$geteach = ceil(($run[0]['qty'] * $threads) / $run[0]['maxmsgs']);
			} else {
				$geteach = $count / $run[0]['maxmsgs'];
			}

			$queue = [];
			for ($i = 0; $i <= $geteach - 1; $i++) {
				$queue[$i] = sprintf("get_range  backfill  %s  %s  %s  %s", $data['name'], $data['our_first'] - $i * $run[0]['maxmsgs'] - $run[0]['maxmsgs'], $data['our_first'] - $i * $run[0]['maxmsgs'] - 1, $i + 1);
			}
			$this->work = $queue;
		}

		return $threads;
	}

	public function safeBackfillChildWorker($ranges, $identifier = '')
	{
		foreach ($ranges as $range) {
			$this->_executeCommand(
				$this->dnr_path . $range . '"'
			);
		}
		return;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////// All binaries code here ////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	private function binariesMainMethod()
	{
		$this->register_child_run([0 => $this, 1 => 'binariesChildWorker']);
		$this->work = $this->pdo->query(
			sprintf(
				'SELECT name, %d AS max FROM groups WHERE active = 1',
				$this->workTypeOptions[0]
			)
		);
		return Settings::value('..binarythreads');
	}

	public function binariesChildWorker($groups, $identifier = '')
	{
		foreach ($groups as $group) {
			$this->_executeCommand(
				PHP_BINARY . ' ' . NN_UPDATE . 'update_binaries.php ' . $group['name'] . ' ' . $group['max']
			);
		}
	}

	/**
	 * @return int
	 */
	private function safeBinariesMainMethod()
	{
		$this->register_child_run([0 => $this, 1 => 'safeBinariesChildWorker']);

		$maxheaders = Settings::value('max.headers.iteration') ?: 1000000;
		$maxmssgs = Settings::value('..maxmssgs');
		$threads = Settings::value('..binarythreads');

		$groups = $this->pdo->query("
			SELECT g.name AS groupname, g.last_record AS our_last,
				a.last_record AS their_last
			FROM groups g
			INNER JOIN short_groups a ON g.active = 1 AND g.name = a.name
			ORDER BY a.last_record DESC"
		);

		if ($groups) {
			$i = 1;
			$queue = [];
			foreach ($groups as $group) {
				if ($group['our_last'] == 0) {
					$queue[$i] = sprintf("update_group_headers  %s", $group['groupname']);
					$i++;
				} else {
					//only process if more than 20k headers available and skip the first 20k
					$count = $group['their_last'] - $group['our_last'] - 20000;
					//echo "count: " . $count . "maxmsgs x2: " . ($maxmssgs * 2) . PHP_EOL;
					if ($count <= $maxmssgs * 2) {
						$queue[$i] = sprintf("update_group_headers  %s", $group['groupname']);
						$i++;
					} else {
						$queue[$i] = sprintf("part_repair  %s", $group['groupname']);
						$i++;
						$geteach = floor(min($count, $maxheaders) / $maxmssgs);
						$remaining = min($count, $maxheaders) - $geteach * $maxmssgs;
						//echo "maxmssgs: " . $maxmssgs . " geteach: " . $geteach . " remaining: " . $remaining . PHP_EOL;
						for ($j = 0; $j < $geteach; $j++) {
							$queue[$i] = sprintf("get_range  binaries  %s  %s  %s  %s", $group['groupname'], $group['our_last'] + $j * $maxmssgs + 1, $group['our_last'] + $j * $maxmssgs + $maxmssgs, $i);
							$i++;
						}
						//add remainder to queue
						$queue[$i] = sprintf("get_range  binaries  %s  %s  %s  %s", $group['groupname'], $group['our_last'] + ($j + 1) * $maxmssgs + 1, $group['our_last'] + ($j + 1) * $maxmssgs + $remaining + 1, $i);
						$i++;
					}
				}
			}
			//var_dump($queue);
			$this->work = $queue;
		}

		return $threads;
	}

	public function safeBinariesChildWorker($ranges, $identifier = '')
	{
		foreach ($ranges as $range) {
			$this->_executeCommand(
				$this->dnr_path . $range . '"'
			);
		}
		return;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////// All fix release names code here ///////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	private function fixRelNamesMainMethod()
	{
		$this->register_child_run([0 => $this, 1 => 'fixRelNamesChildWorker']);

		$threads = Settings::value('..fixnamethreads');
		$maxperrun = Settings::value('..fixnamesperrun');

		if ($threads > 16) {
			$threads = 16;
		} else if ($threads == 0) {
			$threads = 1;
		}

		$leftguids = ['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'];

		// Prevent PreDB FT from always running
		if ($this->workTypeOptions[0] === 'predbft') {
			$preCount = $this->pdo->queryOneRow(
				sprintf("
					SELECT COUNT(p.id) AS num
					FROM predb p
					WHERE LENGTH(p.title) >= 15
					AND p.title NOT REGEXP '[\"\<\> ]'
					AND p.searched = 0
					AND p.predate < (NOW() - INTERVAL 1 DAY)"
				)
			);
			if ($preCount['num'] > 0) {
				$leftguids = array_slice($leftguids, 0, (int)ceil($preCount['num'] / $maxperrun));
			} else {
				$leftguids = [];
			}
		}

		$count = 0;
		$queue = [];
		foreach ($leftguids as $leftguid) {
			$count++;
			if ($maxperrun > 0) {
				$queue[$count] = sprintf("%s %s %s %s", $this->workTypeOptions[0], $leftguid, $maxperrun, $count);
			}
		}
		$this->work = $queue;

		return $threads;
	}

	public function fixRelNamesChildWorker($guids, $identifier = '')
	{
		foreach ($guids as $guid) {
			$this->_executeCommand(
				PHP_BINARY . ' ' . NN_NIX . 'tmux/bin/groupfixrelnames.php "' . $guid . '"' . ' true'
			);
		}
		return;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////// All releases code here ////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	private function releasesMainMethod()
	{
		$this->register_child_run([0 => $this, 1 => 'releasesChildWorker']);

		$this->tablePerGroup = (Settings::value('..tablepergroup') == 1 ? true : false);
		if ($this->tablePerGroup === true) {

			$groups = $this->pdo->queryDirect('SELECT id FROM groups WHERE (active = 1 OR backfill = 1)');

			if ($groups instanceof \Traversable) {
				foreach ($groups as $group) {
					if ($this->pdo->queryOneRow(sprintf('SELECT id FROM collections_%d  LIMIT 1', $group['id'])) !== false) {
						$this->work[] = ['id' => $group['id']];
					}
				}
			}
		} else {
			$this->work = $this->pdo->query('SELECT name FROM groups WHERE (active = 1 OR backfill = 1)');
		}

		return Settings::value('..releasethreads');
	}

	public function releasesChildWorker($groups, $identifier = '')
	{
		foreach ($groups as $group) {
			if ($this->tablePerGroup === true) {
				$this->_executeCommand($this->dnr_path . 'releases  ' . $group['id'] . '"');
			} else {
				$this->_executeCommand(
					PHP_BINARY . ' ' . NN_UPDATE . 'update_releases.php 1 false ' . $group['name']
				);
			}
		}
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////// All post process code here /////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Only 1 exit method is used for post process, since they are all similar.
	 *
	 * @param        $groups
	 * @param string $identifier
	 */
	public function postProcessChildWorker($groups, $identifier = '')
	{
		foreach ($groups as $group) {
			$type = '';
			if ($this->processAdditional) {
				$type = 'pp_additional  ';
			} else if ($this->processNFO) {
				$type = 'pp_nfo  ';
			} else if ($this->processMovies) {
				$type = 'pp_movie  ';
			} else if ($this->processTV) {
				$type = 'pp_tv  ';
			}

			if ($type !== '') {
				$this->_executeCommand(
					$this->dnr_path . $type . $group['id'] . (isset($group['renamed']) ? ('  ' . $group['renamed']) : '') . '"'
				);
			}
		}
	}

	private $ppAddMinSize = '';
	private $ppAddMaxSize = '';

	/**
	 * Check if we should process Additional's.
	 * @return bool
	 */
	private function checkProcessAdditional()
	{
		$this->ppAddMinSize =
			(Settings::value('..minsizetopostprocess') != '') ? (int)Settings::value('..minsizetopostprocess') : 1;
		$this->ppAddMinSize = ($this->ppAddMinSize > 0 ? ('AND r.size > ' . ($this->ppAddMinSize * 1048576)) : '');
		$this->ppAddMaxSize =
			(Settings::value('..maxsizetopostprocess') != '') ? (int)Settings::value('..maxsizetopostprocess') : 100;
		$this->ppAddMaxSize = ($this->ppAddMaxSize > 0 ? ('AND r.size < ' . ($this->ppAddMaxSize * 1073741824)) : '');
		return (
		$this->pdo->queryOneRow(
			sprintf('
					SELECT r.id
					FROM releases r
					LEFT JOIN categories c ON c.id = r.categories_id
					WHERE r.nzbstatus = %d
					AND r.passwordstatus BETWEEN -6 AND -1
					AND r.haspreview = -1
					AND c.disablepreview = 0
					%s %s
					LIMIT 1',
				NZB::NZB_ADDED,
				$this->ppAddMaxSize,
				$this->ppAddMinSize
			)
		) === false ? false : true
		);
	}

	private function postProcessAddMainMethod()
	{
		$maxProcesses = 1;
		if ($this->checkProcessAdditional() === true) {
			$this->processAdditional = true;
			$this->register_child_run([0 => $this, 1 => 'postProcessChildWorker']);
			$this->work = $this->pdo->query(
				sprintf('
					SELECT leftguid AS id
					FROM releases r
					LEFT JOIN categories c ON c.id = r.categories_id
					WHERE r.nzbstatus = %d
					AND r.passwordstatus BETWEEN -6 AND -1
					AND r.haspreview = -1
					AND c.disablepreview = 0
					%s %s
					GROUP BY leftguid
					LIMIT 16',
					NZB::NZB_ADDED,
					$this->ppAddMaxSize,
					$this->ppAddMinSize
				)
			);
			$maxProcesses = Settings::value('..postthreads');
		}
		return $maxProcesses;
	}

	private $nfoQueryString = '';

	/**
	 * Check if we should process NFO's.
	 * @return bool
	 */
	private function checkProcessNfo()
	{
		if (Settings::value('..lookupnfo') == 1) {
			$this->nfoQueryString = Nfo::NfoQueryString($this->pdo);
			return (
			$this->pdo->queryOneRow(
				sprintf(
					'SELECT r.id FROM releases r WHERE 1=1 %s LIMIT 1',
					$this->nfoQueryString
				)
			) === false ? false : true
			);
		}
		return false;
	}

	private function postProcessNfoMainMethod()
	{
		$maxProcesses = 1;
		if ($this->checkProcessNfo() === true) {
			$this->processNFO = true;
			$this->register_child_run([0 => $this, 1 => 'postProcessChildWorker']);
			$this->work = $this->pdo->query(
				sprintf('
					SELECT leftguid AS id
					FROM releases r
					WHERE 1=1 %s
					GROUP BY leftguid
					LIMIT 16',
					$this->nfoQueryString
				)
			);
			$maxProcesses = Settings::value('..nfothreads');
		}
		return $maxProcesses;
	}

	/**
	 * Check if we should process Movies.
	 * @return bool
	 */
	private function checkProcessMovies()
	{
		if (Settings::value('..lookupimdb') > 0) {
			return (
			$this->pdo->queryOneRow(
				sprintf('
						SELECT id
						FROM releases
						WHERE nzbstatus = %d
						AND imdbid IS NULL
						AND categories_id BETWEEN %d AND %d
						%s %s
						LIMIT 1',
					NZB::NZB_ADDED,
					Category::MOVIE_ROOT,
					Category::MOVIE_OTHER,
					(Settings::value('..lookupimdb') == 2 ? 'AND isrenamed = 1' : ''),
					($this->ppRenamedOnly ? 'AND isrenamed = 1' : '')
				)
			) === false ? false : true
			);
		}
		return false;
	}

	private function postProcessMovMainMethod()
	{
		$maxProcesses = 1;
		if ($this->checkProcessMovies() === true) {
			$this->processMovies = true;
			$this->register_child_run([0 => $this, 1 => 'postProcessChildWorker']);
			$this->work = $this->pdo->query(
				sprintf('
					SELECT leftguid AS id, %d AS renamed
					FROM releases
					WHERE nzbstatus = %d
					AND imdbid IS NULL
					AND categories_id BETWEEN ' . Category::MOVIE_ROOT . ' AND ' . Category::MOVIE_OTHER . '
					%s %s
					GROUP BY leftguid
					LIMIT 16',
					($this->ppRenamedOnly ? 2 : 1),
					NZB::NZB_ADDED,
					(Settings::value('..lookupimdb') == 2 ? 'AND isrenamed = 1' : ''),
					($this->ppRenamedOnly ? 'AND isrenamed = 1' : '')
				)
			);
			$maxProcesses = Settings::value('..postthreadsnon');
		}
		return $maxProcesses;
	}

	/**
	 * Check if we should process TV's.
	 * @return bool
	 */
	private function checkProcessTV()
	{
		if (Settings::value('..lookuptvrage') > 0) {
			return (
			$this->pdo->queryOneRow(
				sprintf('
						SELECT id
						FROM releases
						WHERE nzbstatus = %d
						AND size > 1048576
						AND tv_episodes_id BETWEEN -2 AND 0
						AND categories_id BETWEEN %d AND %d
						%s %s
						LIMIT 1',
					NZB::NZB_ADDED,
					Category::TV_ROOT,
					Category::TV_OTHER,
					(Settings::value('..lookuptvrage') == 2 ? 'AND isrenamed = 1' : ''),
					($this->ppRenamedOnly ? 'AND isrenamed = 1' : '')
				)
			) === false ? false : true
			);
		}
		return false;
	}

	private function postProcessTvMainMethod()
	{
		$maxProcesses = 1;
		if ($this->checkProcessTV() === true) {
			$this->processTV = true;
			$this->register_child_run([0 => $this, 1 => 'postProcessChildWorker']);
			$this->work = $this->pdo->query(
				sprintf('
					SELECT leftguid AS id, %d AS renamed
					FROM releases
					WHERE nzbstatus = %d
					AND tv_episodes_id BETWEEN -2 AND 0
					AND size > 1048576
					AND categories_id BETWEEN %d AND %d
					%s %s
					GROUP BY leftguid
					LIMIT 16',
					($this->ppRenamedOnly ? 2 : 1),
					NZB::NZB_ADDED,
					Category::TV_ROOT,
					Category::TV_OTHER,
					(Settings::value('..lookuptvrage') == 2 ? 'AND isrenamed = 1' : ''),
					($this->ppRenamedOnly ? 'AND isrenamed = 1' : '')
				)
			);
			$maxProcesses = Settings::value('..postthreadsnon');
		}
		return $maxProcesses;
	}

	/**
	 * Process sharing.
	 *
	 * @return bool
	 */
	private function processSharing()
	{
		$sharing = $this->pdo->queryOneRow('SELECT enabled FROM sharing');
		if ($sharing !== false && $sharing['enabled'] == 1) {
			$nntp = new NNTP(['Settings' => $this->pdo]);
			if ((Settings::value('..alternate_nntp') == 1 ? $nntp->doConnect(true, true) : $nntp->doConnect()) === true) {
				(new PostProcess(['Settings' => $this->pdo, 'ColorCLI' => $this->_colorCLI]))->processSharing($nntp);
			}
			return true;
		}
		return false;
	}

	/**
	 * Process all that require a single thread.
	 */
	private function processSingle()
	{
		$postProcess = new PostProcess(['Settings' => $this->pdo, 'ColorCLI' => $this->_colorCLI]);
		//$postProcess->processAnime();
		$postProcess->processBooks();
		$postProcess->processConsoles();
		$postProcess->processGames();
		$postProcess->processMusic();
		$postProcess->processXXX();
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////// All requestID code goes here ////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	private function requestIDMainMethod()
	{
		$this->register_child_run([0 => $this, 1 => 'requestIDChildWorker']);
		$this->work = $this->pdo->query(
			sprintf('
				SELECT DISTINCT(g.id)
				FROM groups g
				INNER JOIN releases r ON r.groups_id = g.id
				WHERE (g.active = 1 OR g.backfill = 1)
				AND r.nzbstatus = %d
				AND r.predb_id = 0
				AND r.isrequestid = 1
				AND r.reqidstatus = %d',
				NZB::NZB_ADDED,
				RequestID::REQID_UPROC
			)
		);
		return Settings::value('..reqidthreads');
	}

	public function requestIDChildWorker($groups, $identifier = '')
	{
		foreach ($groups as $group) {
			$this->_executeCommand($this->dnr_path . 'requestid  ' . $group['id'] . '"');
		}
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////// All "update_per_Group" code goes here ////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	private function updatePerGroupMainMethod()
	{
		$this->register_child_run([0 => $this, 1 => 'updatePerGroupChildWorker']);
		$this->work = $this->pdo->query('SELECT id FROM groups WHERE (active = 1 OR backfill = 1)');
		return Settings::value('..releasethreads');
	}

	public function updatePerGroupChildWorker($groups, $identifier = '')
	{
		foreach ($groups as $group) {
			$this->_executeCommand(
				$this->dnr_path . 'update_per_group  ' . $group['id'] . '"'
			);
		}
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////// Various methods ///////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Execute a shell command, use the appropriate PHP function based on user setting.
	 *
	 * @param string $command
	 */
	protected function _executeCommand($command)
	{
		switch ($this->outputType) {
			case self::OUTPUT_NONE:
				exec($command);
				break;
			case self::OUTPUT_REALTIME:
				passthru($command);
				break;
			case self::OUTPUT_SERIALLY:
				echo shell_exec($command);
				break;
		}
	}

	/**
	 * Set the amount of max child processes.
	 * @param int $maxProcesses
	 */
	private function setMaxProcesses($maxProcesses)
	{
		// Check if override setting is on.
		if (defined('NN_MULTIPROCESSING_MAX_CHILDREN_OVERRIDE') && NN_MULTIPROCESSING_MAX_CHILDREN_OVERRIDE > 0) {
			$maxProcesses = NN_MULTIPROCESSING_MAX_CHILDREN_OVERRIDE;
		}

		if (is_numeric($maxProcesses) && $maxProcesses > 0) {
			switch ($this->workType) {
				case 'postProcess_tv':
				case 'postProcess_mov':
				case 'postProcess_nfo':
				case 'postProcess_add':
					if ($maxProcesses > 16) {
						$maxProcesses = 16;
					}
			}
			$this->maxProcesses = (int)$maxProcesses;
			$this->max_children_set($this->maxProcesses);
		} else {
			$this->max_children_set(1);
		}
	}

	/**
	 * Echo a message to CLI.
	 *
	 * @param string $message
	 */
	public function logger($message)
	{
		if (NN_ECHOCLI) {
			echo $message . PHP_EOL;
		}
	}

	/**
	 * This method is executed whenever a child is finished doing work.
	 *
	 * @param string $pid        The PID numbers.
	 * @param string $identifier Optional identifier to give a PID a name.
	 */
	public function childExit($pid, $identifier = '')
	{
		if (NN_ECHOCLI) {
			$this->_colorCLI->doEcho(
				$this->_colorCLI->header(
					'Process ID #' . $pid . ' has completed.' . PHP_EOL .
					'There are ' . ($this->forked_children_count - 1) . ' process(es) still active with ' .
					(--$this->_workCount) . ' job(s) left in the queue.' . PHP_EOL
				)
			);
		}
	}

	/**
	 *
	 */
	public function __destruct()
	{
		parent::__destruct();
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////// All class vars here /////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * @var ColorCLI
	 */
	public $_colorCLI;

	/**
	 * @var int The type of output
	 */
	protected $outputType;

	/**
	 * Path to do not run folder.
	 * @var string
	 */
	private $dnr_path = '';

	/**
	 * Work to work on.
	 * @var array
	 */
	private $work = [];

	/**
	 * How much work do we have to do?
	 * @var int
	 */
	public $_workCount = 0;

	/**
	 * The type of work we want to work on.
	 * @var string
	 */
	private $workType = '';

	/**
	 * List of passed in options for the current work type.
	 * @var array
	 */
	private $workTypeOptions = [];

	/**
	 * Max amount of child processes to do work at a time.
	 * @var int
	 */
	private $maxProcesses = 1;

	/**
	 * Are we using tablePerGroup?
	 * @var bool
	 */
	private $tablePerGroup = false;

	/**
	 * Group used for safe backfill.
	 * @var string
	 */
	private $safeBackfillGroup = '';

	/**
	 * @var \nntmux\db\Settings
	 */
	public $pdo;

	/**
	 * @var bool
	 */
	private $processAdditional = false; // Should we process additional?
	private $processNFO = false; // Should we process NFOs?
	private $processMovies = false; // Should we process Movies?
	private $processTV = false; // Should we process TV?
}
