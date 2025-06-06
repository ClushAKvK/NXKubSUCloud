<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Nextcloud GmbH
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\LogReader;

// !! Keep in sync with src/constants.ts
class Constants {
	// Used config Keys

	/**
	 * Logging levels to show, used for filtering
	 */
	public const CONFIG_KEY_SHOWNLEVELS = 'shownLevels';
	/**
	 * The backend logging level
	 */
	public const CONFIG_KEY_LOGLEVEL = 'logLevel';
	/**
	 * Display format of the timestamp
	 */
	public const CONFIG_KEY_DATETIMEFORMAT = 'dateTimeFormat';
	/**
	 * If relative dates should be shown for the timestamp (e.g. '3 hours ago')
	 */
	public const CONFIG_KEY_RELATIVEDATES = 'relativedates';
	/**
	 * If automatic updates of the UI are enabled (polling for new entries)
	 */
	public const CONFIG_KEY_LIVELOG = 'liveLog';

	/**
	 * All valid config keys
	 */
	public const CONFIG_KEYS = [
		self::CONFIG_KEY_SHOWNLEVELS,
		self::CONFIG_KEY_LOGLEVEL,
		self::CONFIG_KEY_DATETIMEFORMAT,
		self::CONFIG_KEY_RELATIVEDATES,
		self::CONFIG_KEY_LIVELOG,
	];

	// other constants
	public const LOGGING_LEVELS = [0, 1, 2, 3, 4];
	public const LOGGING_LEVEL_NAMES = [
		'debug',
		'info',
		'warn',
		'error',
		'fatal',
	];
}
