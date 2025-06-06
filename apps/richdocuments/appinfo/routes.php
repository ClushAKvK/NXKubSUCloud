<?php
/**
 * @copyright Copyright (c) 2013-2014 Victor Dubiniuk <victor.dubiniuk@gmail.com>
 *
 * @author Victor Dubiniuk <victor.dubiniuk@gmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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

namespace OCA\Richdocuments\AppInfo;

return [
	'routes' => [
		// Page rendering of documents
		['name' => 'document#index', 'url' => 'index', 'verb' => 'GET'],
		['name' => 'document#remote', 'url' => 'remote', 'verb' => 'GET'],
		['name' => 'document#remotePost', 'url' => 'remote', 'verb' => 'POST'],
		['name' => 'document#createFromTemplate', 'url' => 'indexTemplate', 'verb' => 'GET'],
		['name' => 'document#publicPage', 'url' => '/public', 'verb' => 'GET'],
		['name' => 'document#token', 'url' => '/token', 'verb' => 'POST'],
		['name' => 'document#heartbeat', 'url' => '/heartbeat', 'verb' => 'GET'],

		['name' => 'document#editOnline', 'url' => 'editonline', 'verb' => 'GET'],
		['name' => 'document#editOnlineTarget', 'url' => 'editonline/{fileId}/{target}', 'verb' => 'GET'],

		// external api access
		['name' => 'document#extAppGetData', 'url' => '/ajax/extapp/data/{fileId}', 'verb' => 'POST'],

		// WOPI access
		['name' => 'wopi#checkFileInfo', 'url' => 'wopi/files/{fileId}', 'verb' => 'GET'],
		['name' => 'wopi#getFile', 'url' => 'wopi/files/{fileId}/contents', 'verb' => 'GET'],
		['name' => 'wopi#putFile', 'url' => 'wopi/files/{fileId}/contents', 'verb' => 'POST'],
		['name' => 'wopi#postFile', 'url' => 'wopi/files/{fileId}', 'verb' => 'POST'],
		['name' => 'wopi#getTemplate', 'url' => 'wopi/template/{fileId}', 'verb' => 'GET'],

		// Settings
		['name' => 'settings#setPersonalSettings', 'url' => 'ajax/personal.php', 'verb' => 'POST'],
		['name' => 'settings#setSettings', 'url' => 'ajax/admin.php', 'verb' => 'POST'],
		['name' => 'settings#getSettings', 'url' => 'ajax/settings.php', 'verb' => 'GET'],
		['name' => 'settings#updateWatermarkSettings', 'url' => 'settings/watermark', 'verb' => 'POST'],
		['name' => 'settings#checkSettings', 'url' => 'settings/check', 'verb' => 'GET'],
		['name' => 'settings#demoServers', 'url' => 'settings/demo', 'verb' => 'GET'],
		['name' => 'settings#getFontNames', 'url' => 'settings/fonts', 'verb' => 'GET'],
		['name' => 'settings#getJsonFontList', 'url' => 'settings/fonts.json', 'verb' => 'GET'],
		['name' => 'settings#getFontFile', 'url' => 'settings/fonts/{name}', 'verb' => 'GET'],
		['name' => 'settings#getFontFileOverview', 'url' => 'settings/fonts/{name}/overview', 'verb' => 'GET'],
		['name' => 'settings#deleteFontFile', 'url' => 'settings/fonts/{name}', 'verb' => 'DELETE'],
		['name' => 'settings#uploadFontFile', 'url' => 'settings/fonts', 'verb' => 'POST'],

		// Direct Editing: Webview
		['name' => 'directView#show', 'url' => '/direct/{token}', 'verb' => 'GET'],

		// Direct Editing: Assets
		['name' => 'assets#create', 'url' => 'assets', 'verb' => 'POST'],
		['name' => 'assets#get', 'url' => 'assets/{token}', 'verb' => 'GET'],

		// templates
		['name' => 'templates#getPreview', 'url' => '/template/preview/{fileId}', 'verb' => 'GET'],
		['name' => 'templates#add', 'url' => '/template', 'verb' => 'POST'],
		['name' => 'templates#delete', 'url' => '/template/{fileId}', 'verb' => 'DELETE'],
	],
	'ocs' => [
		// Public pages: new file creation
		['name' => 'documentAPI#create', 'url' => '/api/v1/file', 'verb' => 'POST'],
		['name' => 'documentAPI#openLocal', 'url' => '/api/v1/local', 'verb' => 'POST'],

		// Client API endpoints
		['name' => 'OCS#createDirect', 'url' => '/api/v1/document', 'verb' => 'POST'],
		['name' => 'OCS#createPublic', 'url' => '/api/v1/share', 'verb' => 'POST'],
		['name' => 'OCS#createPublicFromInitiator', 'url' => '/api/v1/direct/share/initiator', 'verb' => 'POST'],
		['name' => 'OCS#createFromTemplate', 'url' => '/api/v1/templates/new', 'verb' => 'POST'],
		['name' => 'OCS#getTemplates', 'url' => '/api/v1/templates/{type}', 'verb' => 'GET'],

		['name' => 'OCS#updateGuestName', 'url' => '/api/v1/wopi/guestname', 'verb' => 'POST'],

		['name' => 'Federation#index', 'url' => '/api/v1/federation', 'verb' => 'GET'],
		['name' => 'Federation#remoteWopiToken', 'url' => '/api/v1/federation', 'verb' => 'POST'],
		['name' => 'Federation#initiatorUser', 'url' => '/api/v1/federation/user', 'verb' => 'POST'],

		['name' => 'Target#getTargets', 'url' => '/api/v1/targets', 'verb' => 'GET'],
		['name' => 'Target#getPreview', 'url' => '/api/v1/targets/preview', 'verb' => 'GET'],

		['name' => 'Mention#mention', 'url' => '/api/v1/mention/{fileId}', 'verb' => 'POST'],
	],
];
