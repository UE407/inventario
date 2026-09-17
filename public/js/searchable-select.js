/**
 * Transforma un <select> HTML en un combo box / selector con buscador en tiempo real.
 * Mantiene sincronizado el select original, sus atributos data-* y dispara el evento 'change' nativo.
 */
function initSearchableSelect(selectInput, options = {}) {
    const select = typeof selectInput === 'string' ? document.getElementById(selectInput) : selectInput;
    if (!select || select.dataset.searchableInitialized) return;
    select.dataset.searchableInitialized = 'true';

    const placeholder = options.placeholder || 'Escribe para buscar...';
    
    // Ocultar select original preservando su presencia en el DOM para envío de formulario
    select.style.display = 'none';

    // Contenedor principal
    const wrapper = document.createElement('div');
    wrapper.className = 'custom-searchable-select';

    // Botón / trigger visible
    const trigger = document.createElement('div');
    trigger.className = 'css-select-trigger';
    trigger.tabIndex = 0;
    
    const triggerText = document.createElement('span');
    triggerText.className = 'css-select-trigger-text';
    
    const triggerIcon = document.createElement('i');
    triggerIcon.className = 'fa-solid fa-chevron-down';
    triggerIcon.style.fontSize = '11px';
    triggerIcon.style.color = '#94a3b8';
    
    trigger.appendChild(triggerText);
    trigger.appendChild(triggerIcon);

    // Menú desplegable
    const dropdown = document.createElement('div');
    dropdown.className = 'css-select-dropdown';

    // Buscador
    const searchWrap = document.createElement('div');
    searchWrap.className = 'css-select-search-wrap';
    
    const searchIcon = document.createElement('i');
    searchIcon.className = 'fa-solid fa-magnifying-glass';
    
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = placeholder;
    searchInput.autocomplete = 'off';

    searchWrap.appendChild(searchIcon);
    searchWrap.appendChild(searchInput);
    dropdown.appendChild(searchWrap);

    // Lista de opciones
    const optionsList = document.createElement('ul');
    optionsList.className = 'css-select-options';
    dropdown.appendChild(optionsList);

    // Mensaje de sin resultados
    const noResults = document.createElement('div');
    noResults.className = 'css-select-no-results';
    noResults.textContent = 'Sin resultados coincidentes';
    noResults.style.display = 'none';
    dropdown.appendChild(noResults);

    wrapper.appendChild(trigger);
    wrapper.appendChild(dropdown);
    select.parentNode.insertBefore(wrapper, select.nextSibling);

    let itemsData = [];

    function updateItemsData() {
        itemsData = Array.from(select.options).map(opt => {
            const dataAttrs = Object.values(opt.dataset || {}).join(' ');
            return {
                value: opt.value,
                text: opt.text,
                element: opt,
                searchKey: (opt.text + ' ' + dataAttrs).toLowerCase()
            };
        });
    }

    function renderOptions(filterText = '') {
        optionsList.innerHTML = '';
        const query = filterText.toLowerCase().trim();
        let count = 0;

        itemsData.forEach(item => {
            const matches = !query || item.searchKey.includes(query);
            if (matches) {
                count++;
                const li = document.createElement('li');
                li.className = 'css-select-option' + (item.value === select.value ? ' selected' : '');
                
                // Formatear visualmente si es opción por defecto o cliente normal
                if (!item.value) {
                    li.innerHTML = `<span style="color:#94a3b8;font-style:italic;">${item.text}</span>`;
                } else {
                    li.textContent = item.text;
                }

                li.addEventListener('click', (e) => {
                    e.stopPropagation();
                    select.value = item.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    updateTrigger();
                    closeDropdown();
                });
                optionsList.appendChild(li);
            }
        });

        noResults.style.display = count === 0 ? 'block' : 'none';
    }

    function updateTrigger() {
        const selectedOpt = select.options[select.selectedIndex];
        if (selectedOpt) {
            triggerText.textContent = selectedOpt.text;
            if (!selectedOpt.value) {
                triggerText.style.color = '#94a3b8';
                triggerText.style.fontWeight = '400';
            } else {
                triggerText.style.color = '#1e293b';
                triggerText.style.fontWeight = '600';
            }
        } else {
            triggerText.textContent = select.options[0] ? select.options[0].text : '— Seleccionar —';
            triggerText.style.color = '#94a3b8';
            triggerText.style.fontWeight = '400';
        }
    }

    function openDropdown() {
        updateItemsData();
        wrapper.classList.add('open');
        renderOptions('');
        searchInput.value = '';
        setTimeout(() => searchInput.focus(), 50);
    }

    function closeDropdown() {
        wrapper.classList.remove('open');
    }

    trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        if (wrapper.classList.contains('open')) {
            closeDropdown();
        } else {
            document.querySelectorAll('.custom-searchable-select.open').forEach(el => el.classList.remove('open'));
            openDropdown();
        }
    });

    trigger.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
            e.preventDefault();
            openDropdown();
        }
    });

    searchInput.addEventListener('input', (e) => {
        renderOptions(e.target.value);
    });

    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeDropdown();
            trigger.focus();
        }
    });

    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            closeDropdown();
        }
    });

    select.addEventListener('change', () => {
        updateTrigger();
    });

    updateItemsData();
    updateTrigger();
}
