document.addEventListener('DOMContentLoaded', function () {
    const filterInput = document.getElementById('ea-file-filter')
    const select = document.getElementById('ea-file-select')
    const hiddenPath = document.getElementById('ea-owner-path')
    const titleInput = document.getElementById('ea-resource-title')
    const selectedPathText = document.getElementById('ea-file-selected-text')
    const selectedFileNameText = document.getElementById('ea-file-name-preview')
    const selectedTitleText = document.getElementById('ea-resource-title-preview')

    if (!filterInput || !select || !hiddenPath || !titleInput || !selectedPathText || !selectedFileNameText || !selectedTitleText) {
	return
    }

    let titleTouchedByUser = false

    titleInput.addEventListener('input', function () {
	titleTouchedByUser = titleInput.value.trim() !== ''
    })

    const getFileName = (path) => {
	if (!path) return ''
	const parts = path.split('/')
	return parts[parts.length - 1] || ''
    }

    const getResourceTitle = (path) => {
	const fileName = getFileName(path)
	if (!fileName) return ''
	return fileName.replace(/\.[^.]+$/, '')
    }

    const syncSelected = () => {
	const selectedOption = select.options[select.selectedIndex]
	if (!selectedOption || selectedOption.hidden) {
	    hiddenPath.value = ''
	    selectedPathText.textContent = 'Файл не выбран'
	    selectedFileNameText.textContent = '—'
	    selectedTitleText.textContent = 'Файл не выбран'
	    return
	}

	const path = selectedOption.value
	const fileName = getFileName(path)
	const resourceTitle = getResourceTitle(path)

	hiddenPath.value = path
	selectedPathText.textContent = path
	selectedFileNameText.textContent = fileName || '—'
	selectedTitleText.textContent = resourceTitle || '—'

	if (!titleTouchedByUser || titleInput.value.trim() === '') {
	    titleInput.value = resourceTitle
	}
    }

    filterInput.addEventListener('input', function () {
	const needle = filterInput.value.trim().toLowerCase()

	Array.from(select.options).forEach(function (option) {
	    const text = option.text.toLowerCase()
	    option.hidden = needle !== '' && !text.includes(needle)
	})

	const firstVisible = Array.from(select.options).find(option => !option.hidden)
	if (firstVisible) {
	    select.value = firstVisible.value
	}

	syncSelected()
    })

    select.addEventListener('change', syncSelected)

    const firstVisible = Array.from(select.options).find(option => !option.hidden)
    if (firstVisible) {
	select.value = firstVisible.value
	syncSelected()
    }
})