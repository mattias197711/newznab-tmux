<?php
namespace nntmux;

use nntmux\db\DB;

class SphinxSearch
{
	/**
	 * SphinxQL connection.
	 * @var \nntmux\db\DB
	 */
	public $sphinxQL = null;

	/**
	 * Establish connection to SphinxQL.
	 */
	public function __construct()
	{
		if (NN_RELEASE_SEARCH_TYPE == ReleaseSearch::SPHINX) {
			if (!defined('NN_SPHINXQL_HOST_NAME')) {
				define('NN_SPHINXQL_HOST_NAME', '0');
			}
			if (!defined('NN_SPHINXQL_PORT')) {
				define('NN_SPHINXQL_PORT', 9306);
			}
			if (!defined('NN_SPHINXQL_SOCK_FILE')) {
				define('NN_SPHINXQL_SOCK_FILE', '');
			}
			$this->sphinxQL = new DB(
				[
					'dbname' => '',
					'dbport' => NN_SPHINXQL_PORT,
					'dbhost' => NN_SPHINXQL_HOST_NAME,
					'dbsock' => NN_SPHINXQL_SOCK_FILE
				]
			);
		}
	}

	/**
	 * Insert release into Sphinx RT table.
	 * @param $parameters
	 */
	public function insertRelease($parameters)
	{
		if (!is_null($this->sphinxQL) && $parameters['id']) {
			$this->sphinxQL->queryExec(
				sprintf(
					'REPLACE INTO releases_rt (id, name, searchname, fromname, filename) VALUES (%d, %s, %s, %s, %s)',
					$parameters['id'],
					$this->sphinxQL->escapeString($parameters['name']),
					$this->sphinxQL->escapeString($parameters['searchname']),
					$this->sphinxQL->escapeString($parameters['fromname']),
					empty($parameters['filename']) ? "''" : $this->sphinxQL->escapeString($parameters['filename'])
				)
			);
		}
	}

	/**
	 * Delete release from Sphinx RT tables.
	 * @param array $identifiers ['g' => Release GUID(mandatory), 'id' => ReleaseID(optional, pass false)]
	 * @param \nntmux\db\DB $pdo
	 */
	public function deleteRelease($identifiers, DB $pdo)
	{
		if (!is_null($this->sphinxQL)) {
			if ($identifiers['i'] === false) {
				$identifiers['i'] = $pdo->queryOneRow(
					sprintf('SELECT id FROM releases WHERE guid = %s', $pdo->escapeString($identifiers['g']))
				);
				if ($identifiers['i'] !== false) {
					$identifiers['i'] = $identifiers['i']['id'];
				}
			}
			if ($identifiers['i'] !== false) {
				$this->sphinxQL->queryExec(sprintf('DELETE FROM releases_rt WHERE id = %d', $identifiers['i']));
			}
		}
	}

	public static function escapeString($string)
	{
		$from = [
			'\\', '(', ')', '|', '---', '--', '-', '!', '@', '~', '"', '&', '/', '^', '$', '=', "'",
			"\x00", "\n", "\r", "\x1a"
		];
		$to = [
			'\\\\\\\\', '\\\\\\\\(', '\\\\\\\\)', '\\\\\\\\|', '-', '-', '\\\\\\\\-', '\\\\\\\\!',
			'\\\\\\\\@', '\\\\\\\\~',
			'\\\\\\\\"', '\\\\\\\\&', '\\\\\\\\/', '\\\\\\\\^', '\\\\\\\\$', '\\\\\\\\=', "\\'",
			"\\x00", "\\n", "\\r", "\\x1a"
		];

		return str_replace($from, $to, $string);
	}

	/**
	 * Update Sphinx Relases index for given releaseid.
	 *
	 * @param int $releaseID
	 * @param \nntmux\db\DB $pdo
	 */
	public function updateRelease($releaseID, DB $pdo)
	{
		if (!is_null($this->sphinxQL)) {
			$new = $pdo->queryOneRow(
						sprintf('
							SELECT r.id, r.name, r.searchname, r.fromname, IFNULL(GROUP_CONCAT(rf.name SEPARATOR " "),"") filename
							FROM releases r
							LEFT JOIN release_files rf ON (r.id=rf.releases_id)
							WHERE r.id = %d
							GROUP BY r.id LIMIT 1',
							$releaseID
						)
			);
			if ($new !== false) {
				$this->insertRelease($new);
			}
		}
	}

	/**
	 * Truncate a RT index.
	 * @param string $indexName
	 */
	public function truncateRTIndex($indexName)
	{
		if (!is_null($this->sphinxQL)) {
			$this->sphinxQL->queryExec(sprintf('TRUNCATE RTINDEX %s', $indexName));
		}
	}

	/**
	 * Optimize a RT index.
	 * @param string $indexName
	 */
	public function optimizeRTIndex($indexName)
	{
		if (!is_null($this->sphinxQL)) {
			$this->sphinxQL->queryExec(sprintf('FLUSH RTINDEX %s', $indexName));
			$this->sphinxQL->queryExec(sprintf('OPTIMIZE INDEX %s', $indexName));
		}
	}
}
