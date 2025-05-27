$(document).ready(function() {
    function addLabFolderButton() {
        // Проверяем группу пользователя через API
        OC.AppConfig.getValue('core', 'usergroups', function(groups) {
            if (groups.includes('Преподаватели')) {
                // Создаем кнопку
                var button = $(
                    '<div class="button lab-folder-button">' +
                    '<a href="#" id="create-lab-folder" class="button new">' +
                    '<img src="' + OC.imagePath('files', 'add-folder.svg') + '" class="icon">' +
                    '<span>Создать лаб.работы</span>' +
                    '</a></div>'
                );
                
                // Вставляем кнопку в интерфейс
                $('.files-controls .actions.creatable').after(button);
                
                // Обработчик клика
                $('#create-lab-folder').click(function(e) {
                    e.preventDefault();
                    alert('Форма создания папки для лаб.работ');
                });
            }
        });
    }

    // Ждем загрузки интерфейса
    setTimeout(addLabFolderButton, 1000);
});