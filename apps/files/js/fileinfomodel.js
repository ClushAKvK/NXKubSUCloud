/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function(OC, OCA) {

	/**
	 * @class OC.Files.FileInfo
	 * @classdesc File information
	 *
	 * @param {Object} attributes file data
	 * @param {number} attributes.id file id
	 * @param {string} attributes.name file name
	 * @param {string} attributes.path path leading to the file,
	 * without the file name and with a leading slash
	 * @param {number} attributes.size size
	 * @param {string} attributes.mimetype mime type
	 * @param {string} attributes.icon icon URL
	 * @param {number} attributes.permissions permissions
	 * @param {Date} attributes.mtime modification time
	 * @param {string} attributes.etag etag
	 * @param {string} mountType mount type
	 *
	 * @since 8.2
	 */
	var FileInfoModel = OC.Backbone.Model.extend({

		defaults: {
			mimetype: 'application/octet-stream',
			path: ''
		},

		_filesClient: null,

		initialize: function(data, options) {
			if (!_.isUndefined(data.id)) {
				data.id = parseInt(data.id, 10);
			}

			if( options ){
				if (options.filesClient) {
					this._filesClient = options.filesClient;
				}
			}
		},

		/**
		 * Returns whether this file is a directory
		 *
		 * @return {boolean} true if this is a directory, false otherwise
		 */
		isDirectory: function() {
			return this.get('mimetype') === 'httpd/unix-directory';
		},

		/**
		 * Returns whether this file is an image
		 *
		 * @return {boolean} true if this is an image, false otherwise
		 */
		isImage: function() {
			if (!this.has('mimetype')) {
				return false;
			}
			return this.get('mimetype').substr(0, 6) === 'image/'
				|| this.get('mimetype') === 'application/postscript'
				|| this.get('mimetype') === 'application/illustrator'
				|| this.get('mimetype') === 'application/x-photoshop';
		},

		/**
		 * Returns the full path to this file
		 *
		 * @return {string} full path
		 */
		getFullPath: function() {
			return OC.joinPaths(this.get('path'), this.get('name'));
		},

		/**
		 * Returns the mimetype of the file
		 *
		 * @return {string} mimetype
		 */
		getMimeType: function() {
			return this.get('mimetype');
		},

		/**
		 * Reloads missing properties from server and set them in the model.
		 * @param properties array of properties to be reloaded
		 * @return ajax call object
		 */
		reloadProperties: function(properties) {
			if( !this._filesClient ){
				return;
			}

			var self = this;
			var deferred = $.Deferred();

			var targetPath = OC.joinPaths(this.get('path') + '/', this.get('name'));

			this._filesClient.getFileInfo(targetPath, {
					properties: properties
				})
				.then(function(status, data) {
					// the following lines should be extracted to a mapper

					if( properties.indexOf(OC.Files.Client.PROPERTY_GETCONTENTLENGTH) !== -1
					||  properties.indexOf(OC.Files.Client.PROPERTY_SIZE) !== -1 ) {
						self.set('size', data.size);
					}

					deferred.resolve(status, data);
				})
				.fail(function(status) {
					OC.Notification.show(t('files', 'Could not load info for file "{file}"', {file: self.get('name')}), {type: 'error'});
					deferred.reject(status);
				});

			return deferred.promise();
		},

		canDownload: function() {
			for (const i in this.attributes.shareAttributes) {
				const attr = this.attributes.shareAttributes[i]
				if (attr.scope === 'permissions' && attr.key === 'download') {
					return attr.enabled
				}
			}

			return true
		},
	});

	if (!OCA.Files) {
		OCA.Files = {};
	}
	OCA.Files.FileInfoModel = FileInfoModel;

})(OC, OCA);

// ===== Кнопка для преподавателей =====
$(document).ready(function() {
    // Функция проверки, является ли пользователь преподавателем
    function isUserTeacher() {
        return OC.currentUser && OC.getCurrentUser().isAdmin;
        // ИЛИ, если проверка по группе:
        // return OC.getCurrentUser().groups.indexOf('Преподаватели') !== -1;
    }

    // Функция создания кнопки
    function createTeacherButton() {
        //if (!isUserTeacher()) return;
        
        // Создаем кнопку
        var $button = $('<div class="button">' +
            '<a href="#" id="create-lab-folder" class="button new">' +
            '<img src="' + OC.imagePath('core', 'actions/add') + '" class="icon">' +
            '<span>' + t('files', 'Создать лаб.работы') + '</span>' +
            '</a></div>');
        
        // Вставляем кнопку рядом с другими кнопками
        $('.files-controls .actions').prepend($button);
        
        // Обработчик клика
        $('#create-lab-folder').click(function(e) {
            e.preventDefault();
            OC.dialogs.alert(
                'Здесь будет форма для создания папки лабораторных работ:\n' +
                '1. Выбор учебного направления\n' +
                '2. Выбор дисциплины\n' +
                '3. Кнопка создания',
                'Создание папки для лаб.работ'
            );
        });
    }

    // Создаем кнопку после загрузки интерфейса
    setTimeout(createTeacherButton, 1000);
});