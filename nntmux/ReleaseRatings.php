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
* @copyright 2016 newznab-tmux
*/
namespace nntmux;

use nntmux\db\Settings;




class ReleaseRatings {

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

	public function addRating($relID)
	{

	}

	public function getRating($relID)
	{

	}
}