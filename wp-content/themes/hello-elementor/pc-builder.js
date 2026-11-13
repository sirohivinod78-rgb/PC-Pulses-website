/**
 * PC Builder Interactive System
 * Handles component selection, compatibility checking, and build summary
 */

class PCBuilder {
    constructor(componentsData) {
        this.components = componentsData.categories || componentsData;
        this.currency = componentsData.currency || 'INR';
        this.priceNote = componentsData.price_note || '';
        this.priceSources = componentsData.price_sources || [];
        this.selectedBuild = {};
        this.compatibilityIssues = [];
        this.compatibilitySuggestions = [];
        this.init();
    }

    formatPrice(price) {
        return new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: this.currency,
            maximumFractionDigits: 0
        }).format(price);
    }

    init() {
        this.renderComponentCategories();
        this.attachEventListeners();
        this.updateCompatibilityUI();
        this.updateBuildSummary();
    }

    renderComponentCategories() {
        const container = document.getElementById('pc-builder-components');
        if (!container) return;

        if (this.priceNote) {
            const priceNote = document.createElement('p');
            priceNote.className = 'pc-builder-price-note';
            priceNote.textContent = this.priceNote;
            this.priceSources.forEach(source => {
                const separator = document.createTextNode(' ');
                const link = document.createElement('a');
                link.href = source.url;
                link.textContent = source.name;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                priceNote.append(separator, link);
            });
            container.appendChild(priceNote);
        }

        Object.entries(this.components).forEach(([categoryKey, categoryData]) => {
            const categorySection = this.createCategorySection(categoryKey, categoryData);
            container.appendChild(categorySection);
        });
    }

    createCategorySection(categoryKey, categoryData) {
        const section = document.createElement('div');
        section.className = 'pc-builder-category';
        section.id = `category-${categoryKey}`;

        const title = document.createElement('h3');
        title.className = 'category-title';
        title.textContent = categoryData.category;

        if (categoryData.description) {
            const description = document.createElement('p');
            description.className = 'category-description';
            description.textContent = categoryData.description;
            section.appendChild(description);
        }

        const filterControls = document.createElement('div');
        filterControls.className = 'component-filters';
        if (categoryData.components.length > 8) {
            const search = document.createElement('input');
            search.type = 'search';
            search.className = 'component-search';
            search.placeholder = `Search ${categoryData.category}`;
            search.setAttribute('aria-label', `Search ${categoryData.category}`);
            filterControls.appendChild(search);
        }

        if (categoryData.filter_key) {
            const values = [...new Set(categoryData.components.map(component => component[categoryData.filter_key]).filter(Boolean))];
            if (values.length > 1) {
                const filter = document.createElement('select');
                filter.className = 'component-filter';
                filter.setAttribute('aria-label', `Filter ${categoryData.category}`);
                filter.add(new Option('All options', ''));
                values.forEach(value => filter.add(new Option(value, value)));
                filterControls.appendChild(filter);
            }
        }

        const componentsGrid = document.createElement('div');
        componentsGrid.className = 'components-grid';

        categoryData.components.forEach(component => {
            const card = this.createComponentCard(categoryKey, component);
            componentsGrid.appendChild(card);
        });

        section.appendChild(title);
        if (filterControls.childElementCount) {
            section.appendChild(filterControls);
        }
        section.appendChild(componentsGrid);
        return section;
    }

    createComponentCard(categoryKey, component) {
        const card = document.createElement('div');
        card.className = 'component-card';
        card.dataset.componentId = component.id;
        card.dataset.categoryKey = categoryKey;

        const isSelected = this.selectedBuild[categoryKey]?.id === component.id;
        const compatibility = this.getCompatibility(categoryKey, component);
        if (isSelected) {
            card.classList.add('selected');
        }
        if (!compatibility.compatible && !isSelected) {
            card.classList.add('not-compatible');
        }

        card.innerHTML = `
            <div class="component-info">
                <h4 class="component-name">${component.name}</h4>
                <ul class="component-specs">
                    ${component.specs.map(spec => `<li>${spec}</li>`).join('')}
                </ul>
                <div class="component-price">${this.formatPrice(component.price)}</div>
                <a class="component-price-link" href="${component.price_url || `https://www.amazon.in/s?k=${encodeURIComponent(component.name)}`}" target="_blank" rel="noopener noreferrer">Check retailer price</a>
            </div>
            <div class="compatibility-label">Not Compatible</div>
            <button class="select-btn" data-component-id="${component.id}" ${!compatibility.compatible && !isSelected ? 'disabled' : ''}>
                ${isSelected ? 'Selected' : 'Select'}
            </button>
        `;

        return card;
    }

    attachEventListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('select-btn')) {
                const componentId = e.target.dataset.componentId;
                const categoryKey = e.target.closest('.component-card').dataset.categoryKey;
                const component = this.findComponent(componentId);
                
                if (component) {
                    if (!this.getCompatibility(categoryKey, component).compatible) {
                        this.showFriendlyMessage(categoryKey, component);
                        return;
                    }
                    this.selectComponent(categoryKey, component);
                }
            }
        });

        document.addEventListener('input', (e) => {
            if (e.target.matches('.component-search')) {
                this.filterCategoryCards(e.target.closest('.pc-builder-category'));
            }
        });

        document.addEventListener('change', (e) => {
            if (e.target.matches('.component-filter')) {
                this.filterCategoryCards(e.target.closest('.pc-builder-category'));
            }
        });
    }

    filterCategoryCards(section) {
        if (!section) return;
        const search = section.querySelector('.component-search')?.value.trim().toLowerCase() || '';
        const filter = section.querySelector('.component-filter')?.value || '';

        section.querySelectorAll('.component-card').forEach(card => {
            const component = this.findComponent(card.dataset.componentId);
            const category = this.components[card.dataset.categoryKey];
            const matchesSearch = !search || component.name.toLowerCase().includes(search);
            const matchesFilter = !filter || String(component[category.filter_key]) === filter;
            card.hidden = !matchesSearch || !matchesFilter;
        });
    }

    findComponent(componentId) {
        for (const categoryData of Object.values(this.components)) {
            const found = categoryData.components.find(c => c.id === componentId);
            if (found) return found;
        }
        return null;
    }

    selectComponent(categoryKey, component) {
        this.selectedBuild[categoryKey] = component;
        const clearedCategories = this.clearIncompatibleSelections();

        if (clearedCategories.length) {
            this.showSelectionResetMessage(categoryKey, clearedCategories);
        }

        // Update UI
        this.updateCategoryUI(categoryKey);
        this.updateCompatibilityUI();
        this.checkCompatibility();
        this.updateBuildSummary();

        // Scroll to summary
        const summary = document.getElementById('pc-builder-summary');
        if (summary) {
            summary.scrollIntoView({ behavior: 'smooth' });
        }
    }

    updateCategoryUI(categoryKey) {
        const cards = document.querySelectorAll(`.component-card[data-category-key="${categoryKey}"]`);
        cards.forEach(card => {
            const selectedId = this.selectedBuild[categoryKey]?.id;
            const isSelected = card.dataset.componentId === selectedId;

            if (isSelected) {
                card.classList.add('selected');
                card.querySelector('.select-btn').textContent = 'Selected';
            } else {
                card.classList.remove('selected');
                card.querySelector('.select-btn').textContent = 'Select';
            }
        });
    }

    getRequiredWattage(build = this.selectedBuild) {
        const cpuPower = build.cpu?.max_power || build.cpu?.tdp || 0;
        const gpuPower = build.gpu?.power_req || 0;
        return Math.ceil((cpuPower + gpuPower + 150) * 1.2);
    }

    getCompatibility(categoryKey, component, build = this.selectedBuild) {
        const cpu = build.cpu;
        const motherboard = build.motherboard;
        const reasons = [];

        if (categoryKey === 'motherboard' && cpu && component.socket !== cpu.socket) {
            reasons.push(`Choose a motherboard with the ${cpu.socket} socket for this CPU.`);
        }
        if (categoryKey === 'motherboard' && cpu && component.generation_support && !component.generation_support.includes(cpu.generation)) {
            reasons.push(`This motherboard does not list support for ${cpu.generation} processors.`);
        }
        if (categoryKey === 'cpu' && motherboard && component.socket !== motherboard.socket) {
            reasons.push(`This motherboard requires a ${motherboard.socket} CPU socket.`);
        }
        if (categoryKey === 'ram' && motherboard && component.type !== motherboard.ram_type) {
            reasons.push(`This motherboard supports ${motherboard.ram_type} memory.`);
        }
        if (categoryKey === 'motherboard' && build.ram && component.ram_type !== build.ram.type) {
            reasons.push(`Your selected RAM is ${build.ram.type}; choose a matching motherboard.`);
        }
        if (categoryKey === 'cooling' && cpu && !(component.socket_support || []).includes(cpu.socket)) {
            reasons.push(`Choose a cooler that supports the ${cpu.socket} socket.`);
        }
        if (categoryKey === 'psu' && component.wattage < this.getRequiredWattage(build)) {
            reasons.push(`Your build needs at least ${this.getRequiredWattage(build)}W of PSU capacity.`);
        }
        const supportedFormFactors = component.supported_form_factors || [component.form_factor];
        if (categoryKey === 'case' && motherboard && !supportedFormFactors.includes(motherboard.form_factor)) {
            reasons.push(`Choose a case that supports the ${motherboard.form_factor} motherboard size.`);
        }
        if (categoryKey === 'gpu' && motherboard && component.pcie && motherboard.pcie_slots && !motherboard.pcie_slots.includes(component.pcie)) {
            reasons.push(`This GPU needs a compatible PCIe slot on the motherboard.`);
        }
        if (categoryKey === 'storage' && motherboard && component.interface && motherboard.storage_interfaces && !motherboard.storage_interfaces.includes(component.interface)) {
            reasons.push(`This motherboard does not have a matching ${component.interface} storage slot.`);
        }

        return { compatible: reasons.length === 0, reason: reasons[0] || '' };
    }

    updateCompatibilityUI() {
        Object.entries(this.components).forEach(([categoryKey, categoryData]) => {
            categoryData.components.forEach(component => {
                const card = document.querySelector(`.component-card[data-category-key="${categoryKey}"][data-component-id="${component.id}"]`);
                if (!card) return;
                const button = card.querySelector('.select-btn');
                const isSelected = this.selectedBuild[categoryKey]?.id === component.id;
                const compatibility = this.getCompatibility(categoryKey, component);
                card.classList.toggle('not-compatible', !compatibility.compatible && !isSelected);
                card.classList.toggle('selected', isSelected);
                button.disabled = !compatibility.compatible && !isSelected;
                button.textContent = isSelected ? 'Selected' : 'Select';
            });
        });
    }

    clearIncompatibleSelections() {
        const clearedCategories = [];
        Object.entries(this.selectedBuild).forEach(([categoryKey, component]) => {
            if (!this.getCompatibility(categoryKey, component).compatible) {
                delete this.selectedBuild[categoryKey];
                clearedCategories.push(this.components[categoryKey]?.category || categoryKey);
            }
        });
        return clearedCategories;
    }

    showFriendlyMessage(categoryKey, component) {
        const compatibility = this.getCompatibility(categoryKey, component);
        const warningContainer = document.getElementById('compatibility-warnings');
        if (!warningContainer) return;
        warningContainer.innerHTML = `<div class="compatibility-warning error"><span class="warning-icon">⚠</span><span class="warning-text">${compatibility.reason || 'This component is not compatible with your current build. Please choose a compatible option.'}</span></div>`;
        warningContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    showSelectionResetMessage(changedCategory, clearedCategories) {
        const warningContainer = document.getElementById('compatibility-warnings');
        if (!warningContainer) return;
        warningContainer.innerHTML = `<div class="compatibility-warning warning"><span class="warning-icon">⚠</span><span class="warning-text">You changed your ${this.components[changedCategory]?.category || changedCategory}. Your ${clearedCategories.join(' and ')} selection${clearedCategories.length > 1 ? 's have' : ' has'} been cleared because ${clearedCategories.length > 1 ? 'they are' : 'it is'} not compatible. Please choose a compatible option.</span></div>`;
    }

    checkCompatibility() {
        this.compatibilityIssues = [];
        this.compatibilitySuggestions = [];

        // CPU Socket Compatibility
        const cpu = this.selectedBuild.cpu;
        const motherboard = this.selectedBuild.motherboard;

        if (cpu && motherboard && cpu.socket !== motherboard.socket) {
            this.compatibilityIssues.push({
                level: 'error',
                message: `CPU Socket Mismatch: ${cpu.socket} CPU requires ${cpu.socket} socket, but motherboard has ${motherboard.socket}`
            });
            const compatibleMotherboards = this.components.motherboard.components.filter(mb => mb.socket === cpu.socket).map(mb => mb.name);
            if (compatibleMotherboards.length) {
                this.compatibilitySuggestions.push(`Choose a motherboard that supports ${cpu.socket}: ${compatibleMotherboards.join(', ')}.`);
            }
        }

        // RAM Type Compatibility
        const ram = this.selectedBuild.ram;
        if (motherboard && ram && motherboard.ram_type !== ram.type) {
            this.compatibilityIssues.push({
                level: 'error',
                message: `RAM Compatibility Issue: Motherboard supports ${motherboard.ram_type}, but you selected ${ram.type}`
            });
            const compatibleRam = this.components.ram.components.filter(mem => mem.type === motherboard.ram_type).map(mem => mem.name);
            if (compatibleRam.length) {
                this.compatibilitySuggestions.push(`Select RAM compatible with ${motherboard.ram_type}: ${compatibleRam.join(', ')}.`);
            }
        }

        // Power Supply Wattage Check
        const psu = this.selectedBuild.psu;
        const gpu = this.selectedBuild.gpu;
        const cpuPower = cpu?.max_power || cpu?.tdp || 0;
        const gpuPower = gpu?.power_req || 0;
        const totalPower = cpuPower + gpuPower + 150; // 150W for other components

        if (psu && psu.wattage < totalPower * 1.2) { // 20% headroom recommended
            this.compatibilityIssues.push({
                level: 'warning',
                message: `PSU Capacity Warning: Your build requires ~${Math.round(totalPower)}W, but PSU is ${psu.wattage}W. Consider upgrading for stability.`
            });
            const higherPsu = this.components.psu.components.filter(power => power.wattage >= Math.ceil(totalPower * 1.2)).map(power => power.name);
            if (higherPsu.length) {
                this.compatibilitySuggestions.push(`Upgrade to a stronger PSU: ${higherPsu.join(', ')}.`);
            }
        }

        // CPU Cooler Socket Support
        const cooling = this.selectedBuild.cooling;
        if (cpu && cooling) {
            const coolerSupports = cooling.socket_support || [];
            if (!coolerSupports.includes(cpu.socket)) {
                this.compatibilityIssues.push({
                    level: 'error',
                    message: `Cooler Compatibility: Selected cooler doesn't support ${cpu.socket} socket`
                });
                const compatibleCoolers = this.components.cooling.components.filter(cool => cool.socket_support.includes(cpu.socket)).map(cool => cool.name);
                if (compatibleCoolers.length) {
                    this.compatibilitySuggestions.push(`Swap to a cooler that supports ${cpu.socket}: ${compatibleCoolers.join(', ')}.`);
                }
            }
        }

        this.displayCompatibilityWarnings();
    }

    displayCompatibilityWarnings() {
        const warningContainer = document.getElementById('compatibility-warnings');
        if (!warningContainer) return;

        warningContainer.innerHTML = '';

        if (this.compatibilityIssues.length === 0) {
            warningContainer.innerHTML = '<div class="compatibility-ok">✓ All components are compatible!</div>';
            return;
        }

        this.compatibilityIssues.forEach(issue => {
            const warning = document.createElement('div');
            warning.className = `compatibility-warning ${issue.level}`;
            warning.innerHTML = `
                <span class="warning-icon">⚠</span>
                <span class="warning-text">${issue.message}</span>
            `;
            warningContainer.appendChild(warning);
        });

        if (this.compatibilitySuggestions.length > 0) {
            const suggestionBlock = document.createElement('div');
            suggestionBlock.className = 'compatibility-suggestions';
            suggestionBlock.innerHTML = `
                <div class="suggestions-title">Suggested Fixes</div>
                <ul>${this.compatibilitySuggestions.map(suggestion => `<li>${suggestion}</li>`).join('')}</ul>
            `;
            warningContainer.appendChild(suggestionBlock);
        }
    }

    updateBuildSummary() {
        const summary = document.getElementById('build-summary-items');
        if (!summary) return;

        summary.innerHTML = '';
        let totalPrice = 0;
        let itemCount = 0;

        Object.entries(this.selectedBuild).forEach(([categoryKey, component]) => {
            const categoryName = this.components[categoryKey]?.category || categoryKey;
            totalPrice += component.price;
            itemCount++;

            const item = document.createElement('div');
            item.className = 'build-summary-item';
            item.innerHTML = `
                <div class="summary-item-info">
                    <div class="summary-category">${categoryName}</div>
                    <div class="summary-name">${component.name}</div>
                </div>
                <div class="summary-item-price">${this.formatPrice(component.price)}</div>
                <button class="remove-btn" data-category="${categoryKey}">Remove</button>
            `;

            item.querySelector('.remove-btn').addEventListener('click', () => {
                delete this.selectedBuild[categoryKey];
                this.updateCategoryUI(categoryKey);
                this.checkCompatibility();
                this.updateBuildSummary();
            });

            summary.appendChild(item);
        });

        // Update progress indicator
        const totalCategories = Object.keys(this.components).length;
        const progress = (itemCount / totalCategories) * 100;
        const progressBar = document.querySelector('.progress-bar-fill');
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }

        const progressText = document.querySelector('.progress-value');
        if (progressText) {
            progressText.textContent = `${itemCount}/${totalCategories} Components Selected`;
        }

        // Update total price
        const totalElement = document.getElementById('build-total-price');
        if (totalElement) {
            totalElement.textContent = this.formatPrice(totalPrice);
        }

        // Update empty state
        if (itemCount === 0) {
            summary.innerHTML = '<div class="empty-build">Select components to build your PC</div>';
        }
    }

    exportBuild() {
        const buildData = {
            timestamp: new Date().toISOString(),
            currency: this.currency,
            components: this.selectedBuild,
            totalPrice: this.getTotalPrice(),
            compatibility: this.compatibilityIssues
        };

        return JSON.stringify(buildData, null, 2);
    }

    getTotalPrice() {
        return Object.values(this.selectedBuild).reduce((sum, component) => sum + component.price, 0);
    }

    resetBuild() {
        this.selectedBuild = {};
        this.compatibilityIssues = [];
        
        // Update all category UIs
        Object.keys(this.components).forEach(categoryKey => {
            this.updateCategoryUI(categoryKey);
        });

        this.checkCompatibility();
        this.updateBuildSummary();
    }
}

// Initialize builder when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (window.pcBuilderComponents) {
        window.pcBuilder = new PCBuilder(window.pcBuilderComponents);
    }
});
