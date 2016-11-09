<?php
namespace nntmux;

use nntmux\db\DB;

class Groups
{
	/**
	 * @var \nntmux\db\Settings
	 */
	public $pdo;

	/**
	 * @var ColorCLI
	 */
	public $colorCLI;

	/**
	 * The table names for TPG children
	 *
	 * @var array
	 */
	protected $cbpm;

	/**
	 * @var array
	 */
	protected $cbppTableNames;

	/**
	 * Construct.
	 *
	 * @param array $options Class instances.
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null,
			'ColorCLI' => null
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof DB ? $options['Settings'] : new DB());
		$this->colorCLI = ($options['ColorCLI'] instanceof ColorCLI ? $options['ColorCLI'] : new ColorCLI());
		$this->cbpm = ['collections', 'binaries', 'parts', 'missed_parts'];
	}

	/**
	 * Returns an associative array of groups for list selection
	 *
	 * @return array
	 */
	public function getGroupsForSelect()
	{
		$groups = $this->getActive();
		$temp_array = [];

		$temp_array[-1] = "--Please Select--";

		if (is_array($groups)) {
			foreach ($groups as $group) {
				$temp_array[$group["name"]] = $group["name"];
			}
		}

		return $temp_array;
	}

	/**
	 * Get all properties of a single group by its ID
	 *
	 * @param $id
	 *
	 * @return array|bool
	 */
	public function getByID($id)
	{
		return $this->pdo->queryOneRow("
			SELECT g.*
			FROM groups g
			WHERE g.id = {$id}"
		);
	}

	/**
	 * Get all properties of all groups ordered by name ascending
	 *
	 * @return array
	 */
	public function getActive()
	{
		return $this->pdo->query(
			"SELECT g.* FROM groups g WHERE g.active = 1 ORDER BY g.name ASC",
			true,
			NN_CACHE_EXPIRY_SHORT
		);
	}

	/**
	 * Get active backfill groups ordered by name ascending
	 *
	 * @param string $order The type of operation designating the order
	 *
	 * @return array
	 */
	public function getActiveBackfill($order)
	{
		switch ($order) {
			case '':
			case 'normal':
				$orderBy = "g.name ASC";
				break;
			case 'date':
				$orderBy = "g.first_record_postdate DESC";
				break;
			default:
				return [];
		}

		return $this->pdo->query(
			"SELECT g.* FROM groups g WHERE g.backfill = 1 AND g.last_record != 0 ORDER BY {$orderBy}",
			true,
			NN_CACHE_EXPIRY_SHORT
		);
	}

	/**
	 * Get all active group IDs
	 *
	 * @return array
	 */
	public function getActiveIDs()
	{
		return $this->pdo->query("
			SELECT g.id
			FROM groups g
			WHERE g.active = 1
			ORDER BY g.name ASC",
			true,
			NN_CACHE_EXPIRY_SHORT
		);
	}

	/**
	 * Get all group columns by Name
	 *
	 * @param $grp
	 *
	 * @return array|bool
	 */
	public function getByName($grp)
	{
		return $this->pdo->queryOneRow("
			SELECT g.*
			FROM groups g
			WHERE g.name = {$this->pdo->escapeString($grp)}"
		);
	}

	/**
	 * Get a group name using its ID.
	 *
	 * @param int|string $id The group ID.
	 *
	 * @return string Empty string on failure, groupName on success.
	 */
	public function getNameByID($id)
	{
		$res = $this->pdo->queryOneRow("
			SELECT g.name
			FROM groups g
			WHERE g.id = {$id}"
		);

		return ($res === false ? '' : $res["name"]);
	}

	/**
	 * Get a group ID using its name.
	 *
	 * @param string $name The group name.
	 *
	 * @return string Empty string on failure, groups_id on success.
	 */
	public function getIDByName($name)
	{
		$res = $this->pdo->queryOneRow("
			SELECT g.id
			FROM groups g
			WHERE g.name = {$this->pdo->escapeString($name)}"
		);

		return ($res === false ? '' : $res["id"]);
	}

	/**
	 * Gets a count of all groups in the table limited by parameters
	 *
	 * @param string $groupname Constrain query to specific group name
	 * @param int    $active Constrain query to active status
	 *
	 * @return mixed
	 */
	public function getCount($groupname = "", $active = -1)
	{
		$res = $this->pdo->query(
			sprintf("
				SELECT COUNT(g.id) AS num
				FROM groups g
				WHERE 1=1 %s %s",
				($groupname !== ''
					?
					sprintf(
						"AND g.name %s",
						$this->pdo->likeString($groupname, true, true)
					)
					: ''
				),
				($active > -1 ? "AND g.active = {$active}" : '')
			), true, NN_CACHE_EXPIRY_MEDIUM
		);

		return (empty($res) ? 0 : $res[0]["num"]);
	}

	/**
	 * Gets all groups and associated release counts
	 *
	 * @param bool|int $start The offset of the query or false for no offset
	 * @param int $num The limit of the query
	 * @param string $groupname The groupname we want if any
	 * @param int $active The status of the group we want if any
	 *
	 * @return mixed
	 */
	public function getRange($start = false, $num = -1, $groupname = '', $active = -1)
	{
		return $this->pdo->query(
			sprintf("
				SELECT g.*,
				COALESCE(COUNT(r.id), 0) AS num_releases
				FROM groups g
				LEFT OUTER JOIN releases r ON r.groups_id = g.id
				WHERE 1=1 %s %s
				GROUP BY g.id
				ORDER BY g.name ASC
				%s",
				($groupname !== ''
					?
					sprintf(
						"AND g.name %s",
						$this->pdo->likeString($groupname, true, true)
					)
					: ''
				),
				($active > -1 ? "AND g.active = {$active}" : ''),
				($start === false ? '' : " LIMIT " . $num . " OFFSET " . $start)
			), true, NN_CACHE_EXPIRY_SHORT
		);
	}

	/**
	 * Update an existing group.
	 *
	 * @param array $group
	 *
	 * @return bool
	 */
	public function update($group)
	{

		$minFileString =
			(
			$group["minfilestoformrelease"] == ''
				? "minfilestoformrelease = NULL,"
				: sprintf(
				" minfilestoformrelease = %d,",
				$this->formatNumberString($group["minfilestoformrelease"], false)
			)
			);

		$minSizeString =
			(
			$group["minsizetoformrelease"] == ''
				? "minsizetoformrelease = NULL"
				: sprintf(
				" minsizetoformrelease = %d",
				$this->formatNumberString($group["minsizetoformrelease"], false)
			)
			);

		return $this->pdo->queryExec(
			sprintf(
				"UPDATE groups
				SET name = %s, description = %s, backfill_target = %s, first_record = %s, last_record = %s,
				last_updated = NOW(), active = %s, backfill = %s, %s %s
				WHERE id = %d",
				$this->pdo->escapeString(trim($group["name"])),
				$this->pdo->escapeString(trim($group["description"])),
				$this->formatNumberString($group["backfill_target"]),
				$this->formatNumberString($group["first_record"]),
				$this->formatNumberString($group["last_record"]),
				$this->formatNumberString($group["active"]),
				$this->formatNumberString($group["backfill"]),
				$minFileString,
				$minSizeString,
				$group["id"]
			)
		);
	}

	/**
	 * Checks group name is standard and replaces any shorthand prefixes
	 *
	 * @param string $groupName The full name of the usenet group being evaluated
	 *
	 * @return string|bool The name of the group replacing shorthand prefix or false if groupname was malformed
	 */
	public function isValidGroup($groupName)
	{
		if (preg_match('/^([\w-]+\.)+[\w-]+$/i', $groupName)) {

			return preg_replace('/^a\.b\./i', 'alt.binaries.', $groupName, 1);
		}

		return false;
	}

	/**
	 * Add a new group.
	 *
	 * @param array $group
	 *
	 * @return bool
	 */
	public function add($group)
	{
		$minFileString =
			(
			$group["minfilestoformrelease"] == ''
				? "NULL"
				: sprintf("%d", $this->formatNumberString($group["minfilestoformrelease"], false))
			);

		$minSizeString =
			(
			$group["minsizetoformrelease"] == ''
				? "NULL"
				: sprintf("%d", $this->formatNumberString($group["minsizetoformrelease"], false))
			);

		return $this->pdo->queryInsert(
			sprintf("
				INSERT INTO groups
					(name, description, backfill_target, first_record, last_record, last_updated,
					active, backfill, minfilestoformrelease, minsizetoformrelease)
				VALUES (%s, %s, %s, %s, %s, NOW(), %s, %s, %s, %s)",
				$this->pdo->escapeString(trim($group["name"])),
				(isset($group["description"]) ? $this->pdo->escapeString(trim($group["description"])) : "''"),
				(isset($group["backfill_target"]) ? $this->formatNumberString($group["backfill_target"]) : "1"),
				(isset($group["first_record"]) ? $this->formatNumberString($group["first_record"]) : "0"),
				(isset($group["last_record"]) ? $this->formatNumberString($group["last_record"]) : "0"),
				(isset($group["active"]) ? $this->formatNumberString($group["active"]) : "0"),
				(isset($group["backfill"]) ? $this->formatNumberString($group["backfill"]) : "0"),
				$minFileString,
				$minSizeString
			)
		);
	}

	/**
	 * Format numeric string when adding/updating groups.
	 *
	 * @param string $setting
	 * @param bool   $escape
	 *
	 * @return string|int
	 */
	protected function formatNumberString($setting, $escape = true)
	{
		$setting = trim($setting);
		if ($setting === "0" || !is_numeric($setting)) {
			$setting = '0';
		}

		return ($escape ? $this->pdo->escapeString($setting) : (int)$setting);
	}

	/**
	 * Delete a group.
	 *
	 * @param int|string $id ID of the group.
	 *
	 * @return bool
	 */
	public function delete($id)
	{
		$this->purge($id);

		return $this->pdo->queryExec("
			DELETE g
			FROM groups g
			WHERE g.id = {$id}"
		);
	}

	/**
	 * Reset a group.
	 *
	 * @param string|int $id The group ID.
	 *
	 * @return bool
	 */
	public function reset($id)
	{
		// Remove rows from collections / binaries / parts.
		(new Binaries(['Groups' => $this, 'Settings' => $this->pdo]))->purgeGroup($id);

		// Remove rows from part repair.
		$this->pdo->queryExec("
			DELETE mp
			FROM missed_parts mp
			WHERE mp.group_id = {$id}"
		);

		foreach ($this->cbpm AS $tablePrefix) {
			$this->pdo->queryExec(
				"DROP TABLE IF EXISTS {$tablePrefix}_{$id}"
			);
		}

		// Reset the group stats.
		return $this->pdo->queryExec("
			UPDATE groups
			SET backfill_target = 1, first_record = 0, first_record_postdate = NULL, last_record = 0,
				last_record_postdate = NULL, last_updated = NULL
			WHERE id = {$id}"
		);
	}

	/**
	 * Reset all groups.
	 *
	 * @return bool
	 */
	public function resetall()
	{
		foreach ($this->cbpm AS $tablePrefix) {
			$this->pdo->queryExec("TRUNCATE TABLE {$tablePrefix}");
		}

		$groups = $this->pdo->queryDirect("SELECT id FROM groups");

		if ($groups instanceof \Traversable) {
			foreach ($groups AS $group) {
				foreach ($this->cbpm AS $tablePrefix) {
					$this->pdo->queryExec("DROP TABLE IF EXISTS {$tablePrefix}_{$group['id']}");
				}
			}
		}

		// Reset the group stats.
		return $this->pdo->queryExec("
			UPDATE groups
			SET backfill_target = 1, first_record = 0, first_record_postdate = NULL,
				last_record = 0, last_record_postdate = NULL, last_updated = NULL, active = 0"
		);
	}

	/**
	 * Purge a single group or all groups.
	 *
	 * @param int|string|bool $id The group ID. If false, purge all groups.
	 */
	public function purge($id = false)
	{
		if ($id === false) {
			$this->resetall();
		} else {
			$this->reset($id);
		}

		$res = $this->pdo->queryDirect(
			sprintf("
				SELECT r.id, r.guid
				FROM releases r %s",
				($id === false ? '' : 'WHERE r.groups_id = ' . $id)
			)
		);

		if ($res instanceof \Traversable) {
			$releases     = new Releases(['Settings' => $this->pdo, 'Groups' => $this]);
			$nzb          = new NZB($this->pdo);
			$releaseImage = new ReleaseImage($this->pdo);
			foreach ($res AS $row) {
				$releases->deleteSingle(
					[
						'g' => $row['guid'],
						'i' => $row['id']
					],
					$nzb,
					$releaseImage
				);
			}
		}
	}

	/**
	 * Adds new newsgroups based on a regular expression match against USP available
	 *
	 * @param string $groupList
	 * @param int    $active
	 * @param int    $backfill
	 *
	 * @return array
	 */
	public function addBulk($groupList, $active = 1, $backfill = 1)
	{
		if (preg_match('/^\s*$/m', $groupList)) {
			$ret = "No group list provided.";
		} else {
			$nntp = new NNTP(['Echo' => false]);
			if ($nntp->doConnect() !== true) {
				return 'Problem connecting to usenet.';
			}
			$groups = $nntp->getGroups();
			$nntp->doQuit();

			if ($nntp->isError($groups)) {
				return 'Problem fetching groups from usenet.';
			}

			$regFilter = '/' . $groupList . '/i';

			$ret = [];

			foreach ($groups as $group) {
				if (preg_match($regFilter, $group['group']) > 0) {
					$res = $this->getIDByName($group['group']);
					if ($res === '') {
						$this->add(
							[
								'name'        => $group['group'],
								'active'      => $active,
								'backfill'    => $backfill,
								'description' => 'Added by bulkAdd',
							]
						);
						$ret[] = ['group' => $group['group'], 'msg' => 'Created'];
					}
				}
			}

			if (count($ret) === 0) {
				$ret = 'No groups found with your regex, try again!';
			}
		}

		return $ret;
	}

	/**
	 * Updates the group active/backfill status
	 *
	 * @param int $id Which group ID
	 * @param string $column Which column active/backfill
	 * @param int $status Which status we are setting
	 *
	 * @return string
	 */
	public function updateGroupStatus($id, $column, $status = 0)
	{
		$this->pdo->queryExec("
			UPDATE groups
			SET {$column} = {$status}
			WHERE id = {$id}"
		);

		return "Group {$id}: {$column} has been " . (($status == 0) ? 'deactivated' : 'activated') . '.';
	}

	/**
	 * Get the names of the collections/binaries/parts/part repair tables.
	 * If TPG is on, try to create new tables for the groups_id, if we fail, log the error and exit.
	 *
	 * @param bool $tpgSetting false, tpg is off in site setting, true tpg is on in site setting.
	 * @param int  $groupID    ID of the group.
	 *
	 * @return array The table names.
	 */
	public function getCBPTableNames($tpgSetting, $groupID)
	{
		$groupKey = ($groupID . '_' . (int)$tpgSetting);

		// Check if buffered and return. Prevents re-querying MySQL when TPG is on.
		if (isset($this->cbppTableNames[$groupKey])) {
			return $this->cbppTableNames[$groupKey];
		}

		$tables           = [];
		$tables['cname']  = 'collections';
		$tables['bname']  = 'binaries';
		$tables['pname']  = 'parts';
		$tables['prname'] = 'missed_parts';

		if ($tpgSetting === true) {
			if ($groupID == '') {
				exit('Error: You must use .../misc/update/nix/multiprocessing/releases.php since you have enabled TPG!');
			}

			if ($this->createNewTPGTables($groupID) === false && NN_ECHOCLI) {
				exit('There is a problem creating new TPG tables for this group ID: ' . $groupID . PHP_EOL);
			}

			$groupEnding = '_' . $groupID;
			$tables['cname'] .= $groupEnding;
			$tables['bname'] .= $groupEnding;
			$tables['pname'] .= $groupEnding;
			$tables['prname'] .= $groupEnding;
		}

		// Buffer.
		$this->cbppTableNames[$groupKey] = $tables;

		return $tables;
	}

	/**
	 * Check if the tables exist for the groups_id, make new tables for table per group.
	 *
	 * @param int $groupID
	 *
	 * @return bool
	 */
	public function createNewTPGTables($groupID)
	{
		foreach ($this->cbpm as $tablePrefix) {
			if ($this->pdo->queryExec(
					"CREATE TABLE IF NOT EXISTS {$tablePrefix}_{$groupID} LIKE {$tablePrefix}",
					true
				) === false
			) {

				return false;
			}
		}

		return true;
	}

	/**
	 * Disable group that does not exist on USP server
	 *
	 * @param int $id The Group ID to disable
	 */
	public function disableIfNotExist($id)
	{
		$this->updateGroupStatus($id, 'active', 0);
		$this->colorCLI->doEcho(
			$this->colorCLI->error(
				'Group does not exist on server, disabling'
			)
		);
	}
}
