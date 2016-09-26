<?php
namespace nntmux;

use nntmux\db\DB;
use nntmux\utility\Utility;

class ReleaseExtra
{
	/**
	 * @var \nntmux\db\Settings
	 */
	public $pdo;

	/**
	 * @param \nntmux\db\DB $settings
	 */
	public function __construct($settings = null)
	{
		$this->pdo = ($settings instanceof DB ? $settings : new DB());
	}

	/**
	 * @param $codec
	 *
	 * @return string
	 */
	public function makeCodecPretty($codec)
	{
		switch (true) {
			case preg_match('#(?:^36$|HEVC)#i', $codec):
				$codec = 'HEVC';
				break;
			case preg_match('#(?:^(?:7|27|H264)$|AVC)#i', $codec);
				$codec = 'h.264';
				break;
			case preg_match('#(?:^(?:20|FMP4|MP42|MP43|MPG4)$|ASP)#i', $codec):
				$codec = 'MPEG-4';
				break;
			case preg_match('#^2$#i', $codec);
				$codec = 'MPEG-2';
				break;
			case preg_match('#^MPEG$#', $codec);
				$codec = 'MPEG-1';
				break;
			case preg_match('#DX50|DIVX|DIV3#i', $codec):
				$codec = 'DivX';
				break;
			case preg_match('#XVID#i', $codec):
				$codec = 'XviD';
				break;
			case preg_match('#(?:wmv|WVC1)#i', $codec);
				$codec = 'wmv';
				break;
			default;
		}

		return $codec;
	}

	/**
	 * @param $id
	 *
	 * @return array|bool
	 */
	public function get($id)
	{
		// hopefully nothing will use this soon and it can be deleted
		return $this->pdo->queryOneRow(sprintf('SELECT * FROM video_data WHERE releases_id = %d', $id));
	}

	/**
	 * @param $id
	 *
	 * @return array|bool
	 */
	public function getVideo($id)
	{
		return $this->pdo->queryOneRow(sprintf('SELECT * from video_data WHERE releases_id = %d', $id));
	}

	/**
	 * @param $id
	 *
	 * @return array
	 */
	public function getAudio($id)
	{
		return $this->pdo->query(sprintf('SELECT * from audio_data WHERE releases_id = %d ORDER BY audioid ASC', $id));
	}

	/**
	 * @param $id
	 *
	 * @return array|bool
	 */
	public function getSubs($id)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT GROUP_CONCAT(subslanguage SEPARATOR ', ') AS subs FROM release_subtitles WHERE releases_id = %d ORDER BY subsid ASC", $id));
	}

	/**
	 * @param $guid
	 *
	 * @return array|bool
	 */
	public function getBriefByGuid($guid)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT containerformat, videocodec, videoduration, videoaspect, CONCAT(video_data.videowidth,'x',video_data.videoheight,' @',format(videoframerate,0),'fps') AS size, GROUP_CONCAT(DISTINCT audio_data.audiolanguage SEPARATOR ', ') AS audio, GROUP_CONCAT(DISTINCT audio_data.audioformat,' (',SUBSTRING(audio_data.audiochannels,1,1),' ch)' SEPARATOR ', ') AS audioformat, GROUP_CONCAT(DISTINCT audio_data.audioformat,' (',SUBSTRING(audio_data.audiochannels,1,1),' ch)' SEPARATOR ', ') AS audioformat, GROUP_CONCAT(DISTINCT release_subtitles.subslanguage SEPARATOR ', ') AS subs FROM video_data LEFT OUTER JOIN release_subtitles ON video_data.releases_id = release_subtitles.releases_id LEFT OUTER JOIN audio_data ON video_data.releases_id = audio_data.releases_id INNER JOIN releases r ON r.id = video_data.releases_id WHERE r.guid = %s GROUP BY r.id", $this->pdo->escapeString($guid)));
	}

	/**
	 * @param $guid
	 *
	 * @return array|bool
	 */
	public function getByGuid($guid)
	{
		return $this->pdo->queryOneRow(sprintf('SELECT video_data.* FROM video_data INNER JOIN releases r ON r.id = video_data.releases_id WHERE r.guid = %s', $this->pdo->escapeString($guid)));
	}

	/**
	 * @param $id
	 *
	 * @return bool|\PDOStatement
	 */
	public function delete($id)
	{
		$this->pdo->queryExec(sprintf('DELETE FROM audio_data WHERE releases_id = %d', $id));
		$this->pdo->queryExec(sprintf('DELETE FROM release_subtitles WHERE releases_id = %d', $id));
		return $this->pdo->queryExec(sprintf('DELETE FROM video_data WHERE releases_id = %d', $id));
	}

	/**
	 * @param $releaseID
	 * @param $xml
	 */
	public function addFromXml($releaseID, $xml)
	{
		$xmlObj = @simplexml_load_string($xml);
		$arrXml = Utility::objectsIntoArray($xmlObj);
		$containerformat = '';
		$overallbitrate = '';

		if (isset($arrXml['File']) && isset($arrXml['File']['track'])) {
			foreach ($arrXml['File']['track'] as $track) {
				if (isset($track['@attributes']) && isset($track['@attributes']['type'])) {


					if ($track['@attributes']['type'] == 'General') {
						if (isset($track['Format'])) {
							$containerformat = $track['Format'];
						}
						if (isset($track['Overall_bit_rate'])) {
							$overallbitrate = $track['Overall_bit_rate'];
						}
						if (isset($track['Unique_ID'])) {
							if(preg_match('/\(0x(?P<hash>[0-9a-f]{32})\)/i', $track['Unique_ID'], $matches)){
								$uniqueid = $matches['hash'];
								$this->addUID($releaseID, $uniqueid);
							}
						}
					} else if ($track['@attributes']['type'] == 'Video') {
						$videoduration = $videoformat = $videocodec = $videowidth = $videoheight = $videoaspect = $videoframerate = $videolibrary = '';
						if (isset($track['Duration'])) {
							$videoduration = $track['Duration'];
						}
						if (isset($track['Format'])) {
							$videoformat = $track['Format'];
						}
						if (isset($track['Codec_ID'])) {
							$videocodec = $track['Codec_ID'];
						}
						if (isset($track['Width'])) {
							$videowidth = preg_replace('/[^0-9]/', '', $track['Width']);
						}
						if (isset($track['Height'])) {
							$videoheight = preg_replace('/[^0-9]/', '', $track['Height']);
						}
						if (isset($track['Display_aspect_ratio'])) {
							$videoaspect = $track['Display_aspect_ratio'];
						}
						if (isset($track['Frame_rate'])) {
							$videoframerate = str_replace(' fps', '', $track['Frame_rate']);
						}
						if (isset($track['Writing_library'])) {
							$videolibrary = $track['Writing_library'];
						}
						$this->addVideo($releaseID, $containerformat, $overallbitrate, $videoduration, $videoformat, $videocodec, $videowidth, $videoheight, $videoaspect, $videoframerate, $videolibrary);
					} else if ($track['@attributes']['type'] == 'Audio') {
						$audioID = 1;
						$audioformat = $audiomode = $audiobitratemode = $audiobitrate = $audiochannels = $audiosamplerate = $audiolibrary = $audiolanguage = $audiotitle = '';
						if (isset($track['@attributes']['streamid'])) {
							$audioID = $track['@attributes']['streamid'];
						}
						if (isset($track['Format'])) {
							$audioformat = $track['Format'];
						}
						if (isset($track['Mode'])) {
							$audiomode = $track['Mode'];
						}
						if (isset($track['Bit_rate_mode'])) {
							$audiobitratemode = $track['Bit_rate_mode'];
						}
						if (isset($track['Bit_rate'])) {
							$audiobitrate = $track['Bit_rate'];
						}
						if (isset($track['Channel_s_'])) {
							$audiochannels = $track['Channel_s_'];
						}
						if (isset($track['Sampling_rate'])) {
							$audiosamplerate = $track['Sampling_rate'];
						}
						if (isset($track['Writing_library'])) {
							$audiolibrary = $track['Writing_library'];
						}
						if (isset($track['Language'])) {
							$audiolanguage = $track['Language'];
						}
						if (isset($track['Title'])) {
							$audiotitle = $track['Title'];
						}
						$this->addAudio($releaseID, $audioID, $audioformat, $audiomode, $audiobitratemode, $audiobitrate, $audiochannels, $audiosamplerate, $audiolibrary, $audiolanguage, $audiotitle);
					} else if ($track['@attributes']['type'] == 'Text') {
						$subsID = 1;
						$subslanguage = 'Unknown';
						if (isset($track['@attributes']['streamid'])) {
							$subsID = $track['@attributes']['streamid'];
						}
						if (isset($track['Language'])) {
							$subslanguage = $track['Language'];
						}
						$this->addSubs($releaseID, $subsID, $subslanguage);
					}
				}
			}
		}
	}

	/**
	 * @param $releaseID
	 * @param $containerformat
	 * @param $overallbitrate
	 * @param $videoduration
	 * @param $videoformat
	 * @param $videocodec
	 * @param $videowidth
	 * @param $videoheight
	 * @param $videoaspect
	 * @param $videoframerate
	 * @param $videolibrary
	 *
	 * @return bool|\PDOStatement
	 */
	public function addVideo($releaseID, $containerformat, $overallbitrate, $videoduration, $videoformat, $videocodec, $videowidth, $videoheight, $videoaspect, $videoframerate, $videolibrary)
	{
		$ckid = $this->pdo->queryOneRow(sprintf('SELECT releases_id FROM video_data WHERE releases_id = %s', $releaseID));
		if (!isset($ckid['releases_id'])) {
			return $this->pdo->queryExec(sprintf('INSERT INTO video_data (releases_id, containerformat, overallbitrate, videoduration, videoformat, videocodec, videowidth, videoheight, videoaspect, videoframerate, videolibrary) VALUES (%d, %s, %s, %s, %s, %s, %d, %d, %s, %d, %s)', $releaseID, $this->pdo->escapeString($containerformat), $this->pdo->escapeString($overallbitrate), $this->pdo->escapeString($videoduration), $this->pdo->escapeString($videoformat), $this->pdo->escapeString($videocodec), $videowidth, $videoheight, $this->pdo->escapeString($videoaspect), $videoframerate, $this->pdo->escapeString(substr($videolibrary, 0, 50))));
		}
	}

	/**
	 * @param $releaseID
	 * @param $audioID
	 * @param $audioformat
	 * @param $audiomode
	 * @param $audiobitratemode
	 * @param $audiobitrate
	 * @param $audiochannels
	 * @param $audiosamplerate
	 * @param $audiolibrary
	 * @param $audiolanguage
	 * @param $audiotitle
	 *
	 * @return bool|\PDOStatement
	 */
	public function addAudio($releaseID, $audioID, $audioformat, $audiomode, $audiobitratemode, $audiobitrate, $audiochannels, $audiosamplerate, $audiolibrary, $audiolanguage, $audiotitle)
	{
		$ckid = $this->pdo->queryOneRow(sprintf('SELECT releases_id FROM audio_data WHERE releases_id = %s', $releaseID));
		if (!isset($ckid['releases_id'])) {
			return $this->pdo->queryExec(sprintf('INSERT INTO audio_data (releases_id, audioid, audioformat, audiomode, audiobitratemode, audiobitrate, audiochannels, audiosamplerate, audiolibrary ,audiolanguage, audiotitle) VALUES (%d, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s)', $releaseID, $audioID, $this->pdo->escapeString($audioformat), $this->pdo->escapeString($audiomode), $this->pdo->escapeString($audiobitratemode), $this->pdo->escapeString(substr($audiobitrate, 0, 10)), $this->pdo->escapeString($audiochannels), $this->pdo->escapeString(substr($audiosamplerate, 0, 25)), $this->pdo->escapeString(substr($audiolibrary, 0, 50)), $this->pdo->escapeString($audiolanguage), $this->pdo->escapeString(substr($audiotitle, 0, 50))));
		}
	}

	/**
	 * @param $releaseID
	 * @param $subsID
	 * @param $subslanguage
	 *
	 * @return bool|\PDOStatement
	 */
	public function addSubs($releaseID, $subsID, $subslanguage)
	{
		$ckid = $this->pdo->queryOneRow(sprintf('SELECT releases_id FROM release_subtitles WHERE releases_id = %s', $releaseID));
		if (!isset($ckid['releases_id'])) {
			return $this->pdo->queryExec(sprintf('INSERT INTO release_subtitles (releases_id, subsid, subslanguage) VALUES (%d, %d, %s)', $releaseID, $subsID, $this->pdo->escapeString($subslanguage)));
		}
	}

	/**
	 * @param $releaseID
	 * @param $uniqueid
	 */
	public function addUID($releaseID, $uniqueid)
	{
		$dupecheck = $this->pdo->queryOneRow("
			SELECT releases_id
			FROM release_unique
			WHERE releases_id = {$releaseID}
			OR (
				releases_id = {$releaseID}
				AND uniqueid = UNHEX('{$uniqueid}')
			)"
		);

		if ($dupecheck === false) {
			$this->pdo->queryExec("
				INSERT INTO release_unique (releases_id, uniqueid)
				VALUES ({$releaseID}, UNHEX('{$uniqueid}'))"
			);
		}
	}

	/**
	 * @param $id
	 *
	 * @return array|bool
	 */
	public function getFull($id)
	{
		return $this->pdo->queryOneRow(sprintf('SELECT * FROM releaseextrafull WHERE releases_id = %d', $id));
	}

	/**
	 * @param $id
	 *
	 * @return bool|\PDOStatement
	 */
	public function deleteFull($id)
	{
		return $this->pdo->queryExec(sprintf('DELETE FROM releaseextrafull WHERE releases_id = %d', $id));
	}

	/**
	 * @param $id
	 * @param string $xml
	 */
	public function addFull($id, $xml)
	{
		$ckid = $this->pdo->queryOneRow(sprintf('SELECT releases_id FROM releaseextrafull WHERE releases_id = %s', $id));
		if (!isset($ckid['releases_id'])) {
			$this->pdo->queryExec(sprintf('INSERT INTO releaseextrafull (releases_id, mediainfo) VALUES (%d, %s)', $id, $this->pdo->escapeString($xml)));
		}
	}
}
