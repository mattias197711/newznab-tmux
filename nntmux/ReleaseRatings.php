<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 * @author    DariusIII
 * @copyright 2016 NNTmux
 */
namespace nntmux;

use nntmux\db\Settings;


class ReleaseRatings
{

	/**
	 * @var \nntmux\db\Settings
	 */
	public $pdo;

	/**
	 * @var array $options Class instances.
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
	}

	/**
	 * @param $relid
	 * @param $userid
	 * @param $video
	 * @param $audio
	 * @param $vote
	 * @param $passworded
	 * @param $spam
	 * @param $server
	 */
	public function addRating($relid, $userid, $video, $audio, $vote, $passworded, $spam, $server)
	{
		if (!empty($vote) && preg_match('/\b(up|down)\b/i', $vote)) {
			$voteplus = $vote === 'up' ? (sprintf('voteup = voteup + 1')) : '';
			$voteminus = $vote === 'down' ? (sprintf('votedown = votedown + 1')) : '';

		} else {
			$voteplus = '';
			$voteminus = '';
		}

		$check = $this->pdo->queryDirect(sprintf('
										SELECT audio, video, voteup, votedown, votes, passworded, spam
										FROM release_ratings
										WHERE releases_id = %d',
				$relid
			)
		);

		if ($check instanceof \Traversable) {
			foreach ($check as $dbl) {
				if ($dbl['releases_id'] == $relid) {
					$this->pdo->queryExec(sprintf('
									UPDATE release_ratings
									SET
									audio = %s,
									%d,
									video = %s,
									videocnt = %d,
									voteup = %s,
									votedown = %s,
									votes = votes +1,
									%s,
									%s',
							$audio,
							(!empty($dbl['audio'] ? sprintf('audiocnt = audiocnt + 1') : '')),
							$video,
							(!empty($dbl['video'] ? sprintf('videocnt = videocnt + 1') : '')),
							$voteplus,
							$voteminus,
							(!empty($dbl['passworded'] ? sprintf('passworded = passworded + 1') : '')),
							(!empty($dbl['spam'] ? sprintf('spam = spam + 1') : ''))
						)
					);
				} else {
					$this->pdo->queryExec(sprintf('
									INSERT INTO release_ratings
									releases_id = %d,
									audio = %s,
									%s,
									video = %s,
									%s,
									voteup = %s,
									votedown = %s,
									votes = votes + 1,
									%s,
									%s',
							$relid,
							$audio,
							(!empty($dbl['audio'] ? sprintf('audiocnt = audiocnt + 1') : '')),
							$video,
							(!empty($dbl['video'] ? sprintf('videocnt = videocnt + 1') : '')),
							$voteplus,
							$voteminus,
							(!empty($dbl['passworded'] ? sprintf('passworded = passworded + 1') : '')),
							(!empty($dbl['spam'] ? sprintf('spam = spam + 1') : ''))
						)
					);
				}
			}
		}

		$this->pdo->queryExec(sprintf('
		INSERT INTO users_release_ratings (releases_id, video, audio, users_id, votes, passworded, spam, server)
		VALUES (%d, %d, %d, %d, %s, %d, %d, %s)',
				$relid,
				!empty($video) ? $video : 0,
				!empty($audio) ? $audio : 0,
				$userid,
				sprintf('votes = votes +1'),
				$passworded,
				$spam,
				$this->pdo->escapeString($server)
			)
		);
	}

	/**
	 * @param $relid
	 *
	 * @return bool|\PDOStatement
	 */
	public function getRating($relid)
	{
		$result = $this->pdo->queryDirect(sprintf('SELECT * FROM release_ratings WHERE releases_id = %d', $relid));
		if ($result) {
			return $result;
		}

		return false;
	}
}
