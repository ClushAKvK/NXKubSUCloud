document.addEventListener('DOMContentLoaded', function () {
    const filterInput = document.getElementById('ea-file-filter')
    const select = document.getElementById('ea-file-select')
    const hiddenPath = document.getElementById('ea-owner-path')
    const selectedText = document.getElementById('ea-file-selected-text')

    if (!filterInput || !select || !hiddenPath || !selectedText) {
	return
    }

    const syncSelected = () => {
	const selectedOption = select.options[select.selectedIndex]
	if (!selectedOption) {
	    hiddenPath.value = ''
	    selectedText.textContent = 'Файл не выбран'
	    return
	}

	hiddenPath.value = selectedOption.value
	selectedText.textContent = selectedOption.value
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