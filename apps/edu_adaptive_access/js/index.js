document.addEventListener('DOMContentLoaded', function () {
    const catalogNode = document.getElementById('ea-catalog-data')
    const filterInput = document.getElementById('ea-file-filter')
    const select = document.getElementById('ea-file-select')
    const hiddenPath = document.getElementById('ea-owner-path')
    const titleInput = document.getElementById('ea-resource-title')
    const selectedPathText = document.getElementById('ea-file-selected-text')
    const selectedFileNameText = document.getElementById('ea-file-name-preview')
    const selectedTitleText = document.getElementById('ea-resource-title-preview')

    const directionSelect = document.getElementById('ea-direction-select')
    const disciplineSelect = document.getElementById('ea-discipline-select')
    const newDisciplineInput = document.getElementById('ea-new-discipline')
    const profileRoleNode = document.getElementById('ea-profile-role')

    let titleTouchedByUser = false

    if (titleInput) {
	titleInput.addEventListener('input', function () {
	    titleTouchedByUser = titleInput.value.trim() !== ''
	})
    }

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

    const syncSelectedFile = () => {
	if (!filterInput || !select || !hiddenPath || !titleInput || !selectedPathText || !selectedFileNameText || !selectedTitleText) {
	    return
	}

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

    if (filterInput && select && hiddenPath && titleInput && selectedPathText && selectedFileNameText && selectedTitleText) {
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

	    syncSelectedFile()
	})

	select.addEventListener('change', syncSelectedFile)

	const firstVisible = Array.from(select.options).find(option => !option.hidden)
	if (firstVisible) {
	    select.value = firstVisible.value
	    syncSelectedFile()
	}
    }

    if (catalogNode && disciplineSelect) {
	let catalog = {}

	try {
	    catalog = JSON.parse(catalogNode.dataset.catalog || '{}')
	} catch (e) {
	    catalog = {}
	}

	const initialDiscipline = disciplineSelect.dataset.selected || ''
	const role = profileRoleNode ? profileRoleNode.dataset.role : ''

	const resolveDirectionCode = () => {
	    if (directionSelect) {
		return directionSelect.value
	    }
	    return disciplineSelect.dataset.direction || ''
	}

	const buildDisciplineOptions = (directionCode, preferredValue = '') => {
	    const disciplines = Array.isArray(catalog[directionCode]?.disciplines)
		? catalog[directionCode].disciplines
		: []

	    disciplineSelect.innerHTML = ''

	    const placeholder = document.createElement('option')
	    placeholder.value = ''
	    placeholder.textContent = 'Выберите дисциплину'
	    disciplineSelect.appendChild(placeholder)

	    disciplines.forEach((discipline) => {
		const option = document.createElement('option')
		option.value = discipline
		option.textContent = discipline
		if (discipline === preferredValue) {
		    option.selected = true
		}
		disciplineSelect.appendChild(option)
	    })

	    if (preferredValue && !disciplines.includes(preferredValue)) {
		const option = document.createElement('option')
		option.value = preferredValue
		option.textContent = preferredValue
		option.selected = true
		disciplineSelect.appendChild(option)
	    }
	}

	if (directionSelect) {
	    directionSelect.addEventListener('change', function () {
		buildDisciplineOptions(directionSelect.value, '')
	    })
	}

	if (newDisciplineInput && role !== 'student') {
	    newDisciplineInput.addEventListener('input', function () {
		if (newDisciplineInput.value.trim() !== '') {
		    disciplineSelect.value = ''
		}
	    })
	}

	buildDisciplineOptions(resolveDirectionCode(), initialDiscipline)
    }
})