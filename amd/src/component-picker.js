/**
 * Dynamic component picker for the componentstoshow field.
 *
 * If loading component metadata fails, the plain text field stays usable.
 *
 * @module    qtype_logiccircuit/component-picker
 * @copyright  2026 Groupe Modulo
 * @license    CC BY-NC-SA
 */
define([], function () {
    const PICKER_CONTAINER_ID = 'qtype-logiccircuit-component-picker';

    const parseCsv = function (value) {
        if (!value) {
            return [];
        }

        return value
            .split(',')
            .map(function (item) {
                return item.trim();
            })
            // doing this prevents us from entering a trailing comma in the input field,
            // which is bad when typing in the text field directly
            // .filter(function (item) {
            //     return item.length > 0;
            // });
    };

    const stringifyCsv = function (items) {
        return items.join(',');
    };

    const getLang = function () {
        const htmlLang = document.documentElement.lang || '';

        if (htmlLang.toLowerCase().indexOf('fr') === 0) {
            return 'fr';
        }

        return 'en';
    };

    const getDisclosureTitle = function () {
        return getLang() === 'fr' ? 'Liste des composants' : 'Component list';
    };

    const setInputValue = function (input, selectedIds) {
        input.value = stringifyCsv(selectedIds);
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const setPickerDisabledState = function (container, disabled) {
        const buttons = container.querySelectorAll('button[data-component-id]');

        buttons.forEach(function (button) {
            button.disabled = disabled;
        });
    };

    const orderSelectedIds = function (selectedSet, rawIds, displayOrder, displayOrderSet) {
        const orderedShownIds = displayOrder.filter(function (id) {
            return selectedSet.has(id);
        });

        const extras = [];

        rawIds.forEach(function (id) {
            if (!selectedSet.has(id) || displayOrderSet.has(id) || extras.indexOf(id) !== -1) {
                return;
            }

            extras.push(id);
        });

        return orderedShownIds.concat(extras);
    };

    const tryLoadComponentMetadata = async (input, container, tryWait) => {
        if (!window.Logic || typeof window.Logic.getAllComponentTypes !== 'function') {
            if (tryWait) {
                console.log('Logic simulation lib not yet initialized. Waiting for it to be ready...');
                let loaded = false;
                window.addEventListener('logic-simulator-ready', function () {
                    loaded = true;
                    console.log('Logic simulation lib is now ready. Initializing component picker...');
                    tryLoadComponentMetadata(input, container, false);
                }, { once: true });
                setTimeout(function () {
                    if (!loaded) {
                        console.warn('Logic simulator lib could not be initialized in time. Component picker will not be available. Try reloading the page');
                    }
                }, 5000);
            } else {
                console.warn('Logic simulator lib could not be initialized. Component picker will not be available. Try reloading the page');
            }
            return;
        }

        let sections;

        try {
            sections = window.Logic.getAllComponentTypes(getLang());
        } catch (error) {
            // Graceful fallback: keep only the plain text field.
            return;
        }

        if (!Array.isArray(sections) || sections.length === 0) {
            return;
        }

        const buttonById = new Map();
        const displayOrder = [];
        const fragment = document.createDocumentFragment();

        sections.forEach(function (section) {
            if (!section || !Array.isArray(section.components) || section.components.length === 0) {
                return;
            }

            const sectionWrapper = document.createElement('div');
            sectionWrapper.className = 'qtype-logiccircuit-section';

            const sectionTitle = document.createElement('div');
            sectionTitle.className = 'qtype-logiccircuit-section-title';
            sectionTitle.textContent = section.sectionName || section.sectionId || '';
            sectionWrapper.appendChild(sectionTitle);

            const grid = document.createElement('div');
            grid.className = 'qtype-logiccircuit-grid';

            section.components.forEach(function (component) {
                if (!component || !component.id || buttonById.has(component.id)) {
                    return;
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'qtype-logiccircuit-component';
                button.dataset.componentId = component.id;
                button.setAttribute('aria-pressed', 'false');
                button.title = component.id;
                displayOrder.push(component.id);

                const icon = document.createElement('span');
                icon.className = 'qtype-logiccircuit-component-icon';
                icon.innerHTML = component.icon || '';

                const label = document.createElement('span');
                label.className = 'qtype-logiccircuit-component-name';
                label.textContent = component.name || component.id;

                button.appendChild(icon);
                button.appendChild(label);

                button.addEventListener('click', function () {
                    const rawIds = parseCsv(input.value);
                    const selectedSet = new Set(rawIds);

                    if (selectedSet.has(component.id)) {
                        selectedSet.delete(component.id);
                    } else {
                        selectedSet.add(component.id);
                    }

                    const orderedIds = orderSelectedIds(selectedSet, rawIds, displayOrder, displayOrderSet);
                    setInputValue(input, orderedIds);
                    syncButtons();
                });

                buttonById.set(component.id, button);
                grid.appendChild(button);
            });

            if (grid.childElementCount > 0) {
                sectionWrapper.appendChild(grid);
                fragment.appendChild(sectionWrapper);
            }
        });

        if (!fragment.childNodes.length) {
            return;
        }

        const displayOrderSet = new Set(displayOrder);

        const syncButtons = function () {
            const rawIds = parseCsv(input.value);
            const selectedSet = new Set(rawIds);
            const orderedIds = orderSelectedIds(selectedSet, rawIds, displayOrder, displayOrderSet);

            if (stringifyCsv(orderedIds) !== input.value) {
                input.value = stringifyCsv(orderedIds);
            }

            buttonById.forEach(function (button, id) {
                const pressed = selectedSet.has(id);
                button.setAttribute('aria-pressed', pressed ? 'true' : 'false');
            });
        };

        const disclosure = document.createElement('details');
        disclosure.className = 'qtype-logiccircuit-disclosure';
        disclosure.open = false;

        const summary = document.createElement('summary');
        summary.className = 'qtype-logiccircuit-disclosure-title';
        summary.textContent = getDisclosureTitle();
        disclosure.appendChild(summary);

        const disclosureBody = document.createElement('div');
        disclosureBody.className = 'qtype-logiccircuit-disclosure-body';
        disclosureBody.appendChild(fragment);
        disclosure.appendChild(disclosureBody);

        container.appendChild(disclosure);
        container.hidden = false;

        syncButtons();
        setPickerDisabledState(container, input.disabled);

        input.addEventListener('input', function () {
            syncButtons();
        });

        input.addEventListener('change', function () {
            syncButtons();
        });

        const observer = new MutationObserver(function () {
            setPickerDisabledState(container, input.disabled);
        });

        observer.observe(input, { attributes: true, attributeFilter: ['disabled'] });
    }

    const buildPicker = async function () {
        const inputElement = document.getElementById('id_componentstoshow') ||
            document.querySelector('input[name="componentstoshow"]');
        const container = document.getElementById(PICKER_CONTAINER_ID);

        if (!(inputElement instanceof HTMLInputElement) || !container) {
            return;
        }

        tryLoadComponentMetadata(inputElement, container, true);
    };

    return {
        init: buildPicker
    };
});
