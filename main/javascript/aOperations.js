let currentPage = 1;
let itemsPerPage = 5;
let totalPages = 1;

function getRequests() {
    return Array.isArray(window.requestsData) ? window.requestsData : [];
}

function getCollectors() {
    return Array.isArray(window.collectorsData) ? window.collectorsData : [];
}

function getVehicles() {
    return Array.isArray(window.vehiclesData) ? window.vehiclesData : [];
}

function getCentres() {
    return Array.isArray(window.centresData) ? window.centresData : [];
}

function getRecentAssignments() {
    return Array.isArray(window.recentAssignmentsData) ? window.recentAssignmentsData : [];
}

function getRequestIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('requestID');
}

// ==== Helper Functions ====
function determineEwasteType(items) {
    const itemString = (items || []).join(' ').toLowerCase();

    if (itemString.includes('battery') || itemString.includes('power bank')) {
        return 'batteries';
    } else if (
        itemString.includes('tv') ||
        itemString.includes('television') ||
        itemString.includes('refrigerator') ||
        itemString.includes('fridge') ||
        itemString.includes('washing machine') ||
        itemString.includes('electric kitchen appliances') ||
        itemString.includes('electric home appliances')
    ) {
        return 'appliances';
    } else {
        return 'electronics';
    }
}

function initCustomDropdowns() {
    setupCustomDropdown('collectorDropdown', 'collectorMenu', 'selectedCollectorText', getCollectors(), 'collector');
    setupCustomDropdown('vehicleDropdown', 'vehicleMenu', 'selectedVehicleText', getVehicles(), 'vehicle');
    setupCustomDropdown('centreDropdown', 'centreMenu', 'selectedCentreText', getCentres(), 'centre');
}

function setupCustomDropdown(dropdownId, menuId, textId, items, type) {
    const dropdown = document.getElementById(dropdownId);
    const menu = document.getElementById(menuId);
    const selectedText = document.getElementById(textId);

    if (!dropdown || !menu || !selectedText) return;

    const selectBtn = dropdown.querySelector('.custom-dropdown-select');
    if (!selectBtn) return;

    menu.innerHTML = '';

    items.forEach(item => {
        const menuItem = document.createElement('div');
        menuItem.className = 'custom-dropdown-item';
        menuItem.dataset.value = item.id;

        let isAvailable = true;
        let dotColor = 'green';

        if (type === 'collector') {
            isAvailable = !!item.available;
            dotColor = item.available ? 'green' : 'red';
            menuItem.innerHTML = `<span class="status-dot ${dotColor}"></span> ${item.name}`;
        } else if (type === 'vehicle') {
            isAvailable = item.status === 'Available';
            dotColor = isAvailable ? 'green' : 'red';
            menuItem.innerHTML = `<span class="status-dot ${dotColor}"></span> ${item.model}`;
        } else if (type === 'centre') {
            menuItem.textContent = item.name;
        }

        if (!isAvailable) {
            menuItem.style.opacity = '0.5';
            menuItem.style.pointerEvents = 'none';
            menuItem.style.cursor = 'not-allowed';
        }

        menuItem.addEventListener('click', function (e) {
            e.stopPropagation();

            if (!isAvailable) return;

            if (type === 'collector') {
                selectedText.innerHTML = `<span class="status-dot ${dotColor}"></span> ${item.name}`;
                const hint = document.getElementById('collectorHint');
                if (hint) {
                    hint.textContent = '';
                }
            } else if (type === 'vehicle') {
                selectedText.innerHTML = `<span class="status-dot ${dotColor}"></span> ${item.model}`;
                const hint = document.getElementById('vehicleHint');
                if (hint) hint.textContent = item.capacity || '';
            } else if (type === 'centre') {
                selectedText.textContent = item.name;
                updateCapacityCircle(Number(item.capacity) || 0);
            }

            dropdown.dataset.selectedValue = item.id;
            menu.classList.remove('show');
            checkRequiredFields();
        });

        menu.appendChild(menuItem);
    });

    selectBtn.addEventListener('click', function (e) {
        e.stopPropagation();

        document.querySelectorAll('.custom-dropdown-menu').forEach(m => {
            if (m !== menu) m.classList.remove('show');
        });

        menu.classList.toggle('show');
    });

    menu.addEventListener('click', function (e) {
        e.stopPropagation();
    });
}

document.addEventListener('click', function () {
    document.querySelectorAll('.custom-dropdown-menu').forEach(menu => {
        menu.classList.remove('show');
    });
});

function checkRequiredFields() {
    const collectorDropdown = document.getElementById('collectorDropdown');
    const vehicleDropdown = document.getElementById('vehicleDropdown');
    const centreDropdown = document.getElementById('centreDropdown');
    const datetime = document.getElementById('scheduledDateTime')?.value;

    const collector = collectorDropdown?.dataset.selectedValue;
    const vehicle = vehicleDropdown?.dataset.selectedValue;
    const centre = centreDropdown?.dataset.selectedValue;

    const allFieldsFilled = collector && vehicle && centre && datetime;
    const confirmBtn = document.getElementById('confirmAssignmentBtn');
    if (confirmBtn) confirmBtn.disabled = !allFieldsFilled;
}

// ===== CORE FUNCTIONS =====
function loadPendingRequests() {
    filterRequests();
}

function createRequestCard(request, index) {
    const card = document.createElement('div');
    card.className = 'request-card';
    card.dataset.id = request.id;
    card.dataset.index = index;

    const preferredDate = new Date(request.preferredDate);
    const formattedDate = isNaN(preferredDate)
        ? '-'
        : preferredDate.toLocaleString('en-MY', {
              day: 'numeric',
              month: 'short',
              hour: '2-digit',
              minute: '2-digit'
          });

    card.innerHTML = `
        <div class="card-header">
            <span class="request-id">#${request.id}</span>
        </div>
        <div class="card-body">
            <div class="provider-name">${request.provider}</div>
            <div class="e-waste-items">
                ${(request.items || []).map(item => `<span class="item-chip">${item}</span>`).join('')}
            </div>
            <div class="card-footer">
                <span>${request.weight || '0 kg'}</span>
                <span class="preferred-date">${formattedDate}</span>
            </div>
        </div>
    `;

    card.addEventListener('click', () => {
        document.querySelectorAll('.request-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');

        resetAssignmentForm(false);
        updateAssignmentPanel(request);
        updateSelectedRequestId(request);
    });

    return card;
}

function updateAssignmentPanel(request) {
    if (!request) return;

    updateSelectedRequestId(request);

    const summaryDiv = document.getElementById('selectedRequestSummary');
    if (!summaryDiv) return;

    const preferredDate = new Date(request.preferredDate);
    const dateStr = isNaN(preferredDate)
        ? '-'
        : preferredDate.toLocaleString('en-MY', {
              weekday: 'short',
              day: 'numeric',
              month: 'short',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit'
          });

    summaryDiv.innerHTML = `
        <div class="summary-row">
            <span class="summary-label">Provider:</span>
            <span class="summary-value">${request.provider}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Items:</span>
            <span class="summary-value">${(request.items || []).join(', ')} (${request.weight || '0 kg'})</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Preferred:</span>
            <span class="summary-value">${dateStr}</span>
        </div>
        <div class="summary-address">
            📍 ${request.address || '-'}
        </div>
    `;

    const confirmBtn = document.getElementById('confirmAssignmentBtn');
    if (confirmBtn) confirmBtn.disabled = true;
}

function loadCollectors() {
    const hint = document.getElementById('collectorHint');
    if (hint) hint.textContent = '';
}

function loadVehicles() {
    const hint = document.getElementById('vehicleHint');
    if (hint) hint.textContent = '';
}

function loadCentres() {
    updateCapacityCircle(0);
}

function updateCapacityCircle(capacity) {
    const circle = document.getElementById('capacityCircle');
    const percentageSpan = document.getElementById('capacityPercentage');

    if (!circle || !percentageSpan) return;

    const safeCapacity = Math.max(0, Math.min(100, Number(capacity) || 0));
    const degrees = (safeCapacity / 100) * 360;

    circle.style.background = `conic-gradient(var(--MainBlue) ${degrees}deg, var(--LightBlue) 0deg)`;
    percentageSpan.textContent = `${safeCapacity}%`;
}

function updateSelectedRequestId(request) {
    const badge = document.getElementById('selectedRequestId');
    if (!badge) return;

    if (request) {
        badge.textContent = `#${request.id}`;
        badge.style.display = 'inline-block';
    } else {
        badge.textContent = '';
        badge.style.display = 'none';
    }
}

function cleanTimelineEventText(text, requestId) {
    let cleaned = String(text || 'Assignment recorded');

    cleaned = cleaned.replace(/\s*\(ID:\s*\d+\)/gi, '');
    cleaned = cleaned.replace(/,\s*centre\s+/gi, ', ');
    cleaned = cleaned.replace(/,\s*vehicle\s+/gi, ', Vehicle ');
    cleaned = cleaned.replace(/^<strong>#\d+<\/strong>\s*/i, '');
    cleaned = cleaned.replace(/^#\d+\s*/i, '');

    if (requestId) {
        cleaned = `<strong>#REQ${requestId}</strong> ${cleaned}`;
    }

    return cleaned.trim();
}

function loadRecentAssignments() {
    const timeline = document.getElementById('recentTimeline');
    if (!timeline) return;

    const assignments = getRecentAssignments();

    if (assignments.length === 0) {
        timeline.innerHTML = `
            <div class="timeline-item">
                <span class="timeline-event">No recent assignments</span>
            </div>
        `;
        return;
    }

    timeline.innerHTML = assignments.map(a => `
        <div class="timeline-item">
            <span class="timeline-time">${a.time || '-'}</span>
            <span class="timeline-event">
                ${cleanTimelineEventText(a.event, a.requestID)}
            </span>
            <span class="timeline-date">${a.date || ''}</span>
        </div>
    `).join('');
}

function setMinDateTime() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

    const datetimeInput = document.getElementById('scheduledDateTime');
    if (datetimeInput) {
        datetimeInput.min = now.toISOString().slice(0, 16);
    }
}

function setupEventListeners() {
    const prevBtn = document.querySelector('.pagination-controls .c-btn-small:first-child');
    const nextBtn = document.querySelector('.pagination-controls .c-btn-small:last-child');

    if (prevBtn) {
        prevBtn.addEventListener('click', () => changePage('prev'));
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => changePage('next'));
    }

    document.getElementById('requestFilter')?.addEventListener('change', filterRequests);
    document.getElementById('requestSearch')?.addEventListener('input', filterRequests);
    document.getElementById('scheduledDateTime')?.addEventListener('change', checkRequiredFields);
    document.getElementById('confirmAssignmentBtn')?.addEventListener('click', confirmAssignment);
    document.getElementById('resetAssignmentBtn')?.addEventListener('click', () => resetAssignmentForm(true));

    document.getElementById('viewCollectorAvailability')?.addEventListener('click', () => {
        alert('Collector availability comes from the database records loaded on this page.');
    });

    document.getElementById('viewVehicleStatus')?.addEventListener('click', () => {
        alert('Vehicle status comes from the database records loaded on this page.');
    });
}

function getFilteredRequests() {
    const filter = document.getElementById('requestFilter')?.value || 'all';
    const search = (document.getElementById('requestSearch')?.value || '').toLowerCase().trim();

    let filteredRequests = [...getRequests()];

    if (filter !== 'all') {
        filteredRequests = filteredRequests.filter(req => req.type === filter);
    }

    if (search) {
        filteredRequests = filteredRequests.filter(req =>
            (req.provider || '').toLowerCase().includes(search) ||
            String(req.id || '').toLowerCase().includes(search) ||
            (req.items || []).some(item => item.toLowerCase().includes(search))
        );
    }

    return filteredRequests;
}

function filterRequests(resetToFirstPage = true) {
    if (resetToFirstPage) currentPage = 1;

    const filteredRequests = getFilteredRequests();
    const container = document.getElementById('requestCardsContainer');
    if (!container) return;

    if (filteredRequests.length === 0) {
        container.innerHTML = `
            <div class="empty-requests">
                <i class="fas fa-filter"></i>
                <h3>No approved requests</h3>
            </div>
        `;
        updateSelectedRequestId(null);

        const summaryDiv = document.getElementById('selectedRequestSummary');
        if (summaryDiv) summaryDiv.innerHTML = '';

        updatePagination(filteredRequests.length);
        return;
    }

    totalPages = Math.max(1, Math.ceil(filteredRequests.length / itemsPerPage));

    if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const paginatedRequests = filteredRequests.slice(start, end);

    container.innerHTML = '';
    paginatedRequests.forEach((req, index) => {
        const card = createRequestCard(req, start + index);
        container.appendChild(card);
    });

    let selectedRequest = paginatedRequests[0];

    const requestIdFromURL = getRequestIdFromURL();
    if (requestIdFromURL) {
        const urlRequest = paginatedRequests.find(r => String(r.id) === String(requestIdFromURL));
        if (urlRequest) {
            selectedRequest = urlRequest;
        }
    }

    if (selectedRequest) {
        const selectedCard = container.querySelector(`.request-card[data-id="${selectedRequest.id}"]`);
        if (selectedCard) selectedCard.classList.add('selected');
        updateAssignmentPanel(selectedRequest);
        updateSelectedRequestId(selectedRequest);
    } else {
        updateSelectedRequestId(null);
    }

    updatePagination(filteredRequests.length);
}

function autoSelectRequestFromURL() {
    const requestId = getRequestIdFromURL();
    if (!requestId) return;

    const request = getRequests().find(r => String(r.id) === String(requestId));
    if (!request) return;

    const filtered = getFilteredRequests();
    const requestIndex = filtered.findIndex(r => String(r.id) === String(requestId));
    if (requestIndex === -1) return;

    currentPage = Math.floor(requestIndex / itemsPerPage) + 1;
    filterRequests(false);

    setTimeout(() => {
        const card = document.querySelector(`.request-card[data-id="${requestId}"]`);
        if (!card) return;

        document.querySelectorAll('.request-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');

        updateAssignmentPanel(request);
        updateSelectedRequestId(request);
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
}

async function confirmAssignment() {
    const collectorDropdown = document.getElementById('collectorDropdown');
    const vehicleDropdown = document.getElementById('vehicleDropdown');
    const centreDropdown = document.getElementById('centreDropdown');
    const datetime = document.getElementById('scheduledDateTime')?.value;
    const notes = document.getElementById('assignmentNotes')?.value || '';

    const collector = collectorDropdown?.dataset.selectedValue;
    const vehicle = vehicleDropdown?.dataset.selectedValue;
    const centre = centreDropdown?.dataset.selectedValue;

    if (!collector) {
        alert('Please select a collector');
        return;
    }

    if (!vehicle) {
        alert('Please select a vehicle');
        return;
    }

    if (!centre) {
        alert('Please select a collection centre');
        return;
    }

    if (!datetime) {
        alert('Please select a scheduled date and time');
        return;
    }

    const selectedCard = document.querySelector('.request-card.selected');
    if (!selectedCard) {
        alert('Please select a request');
        return;
    }

    const requestId = selectedCard.dataset.id;
    const assignedRequest = getRequests().find(r => String(r.id) === String(requestId));

    if (!assignedRequest) {
        alert('Selected request not found.');
        return;
    }

    const confirmBtn = document.getElementById('confirmAssignmentBtn');
    const originalBtnText = confirmBtn ? confirmBtn.innerHTML : '';

    try {
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = 'Saving...';
        }

        const formData = new FormData();
        formData.append('action', 'assign_request');
        formData.append('requestID', requestId);
        formData.append('collectorID', collector);
        formData.append('vehicleID', vehicle);
        formData.append('centreID', centre);
        formData.append('scheduledDateTime', datetime);
        formData.append('notes', notes);

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to save assignment.');
        }

        const collectorText = document.getElementById('selectedCollectorText')?.textContent || 'Collector';
        const vehicleRawText = document.getElementById('selectedVehicleText')?.textContent || '';
        const centreText = document.getElementById('selectedCentreText')?.textContent || '';
        const vehicleText = vehicleRawText
        .replace(/[🟢🔴]/g, '')
        .trim()
        .replace(/^Vehicle\s*/i, '');

        alert(
            `✓ Assignment saved successfully!\n\nRequest: #${requestId}\nCollector: ${collectorText}\nVehicle: ${vehicleText}\nScheduled: ${new Date(datetime).toLocaleString()}`
        );

        addToTimeline(datetime, assignedRequest, collectorText, vehicleText, centreText);

        if (Array.isArray(window.requestsData)) {
            const requestIndex = window.requestsData.findIndex(r => String(r.id) === String(requestId));
            if (requestIndex !== -1) {
                window.requestsData.splice(requestIndex, 1);
            }
        }

        if (Array.isArray(window.collectorsData)) {
            const collectorObj = window.collectorsData.find(c => String(c.id) === String(collector));
            if (collectorObj) {
                collectorObj.available = false;
            }
        }

        if (Array.isArray(window.vehiclesData)) {   
            const vehicleObj = window.vehiclesData.find(v => String(v.id) === String(vehicle));  
            if (vehicleObj) {    
                vehicleObj.status = 'Pending';
            }
        }

        resetAssignmentForm(true);
        initCustomDropdowns();
        filterRequests(false);
    } catch (error) {
        alert(error.message || 'Error saving assignment.');
    } finally {
        if (confirmBtn) {
            confirmBtn.innerHTML = originalBtnText || '✓ Confirm';
            checkRequiredFields();
        }
    }
}

function resetAssignmentForm(clearSelectedRequest = false) {
    if (clearSelectedRequest) {
        updateSelectedRequestId(null);

        const summaryDiv = document.getElementById('selectedRequestSummary');
        if (summaryDiv) {
            summaryDiv.innerHTML = '';
        }

        document.querySelectorAll('.request-card').forEach(c => c.classList.remove('selected'));
    }

    const collectorText = document.getElementById('selectedCollectorText');
    const vehicleText = document.getElementById('selectedVehicleText');
    const centreText = document.getElementById('selectedCentreText');

    if (collectorText) collectorText.textContent = 'Select a collector';
    if (vehicleText) vehicleText.textContent = 'Select a vehicle';
    if (centreText) centreText.textContent = 'Select a collection centre';

    const collectorDropdown = document.getElementById('collectorDropdown');
    const vehicleDropdown = document.getElementById('vehicleDropdown');
    const centreDropdown = document.getElementById('centreDropdown');

    if (collectorDropdown) delete collectorDropdown.dataset.selectedValue;
    if (vehicleDropdown) delete vehicleDropdown.dataset.selectedValue;
    if (centreDropdown) delete centreDropdown.dataset.selectedValue;

    const scheduledDateTime = document.getElementById('scheduledDateTime');
    const assignmentNotes = document.getElementById('assignmentNotes');
    if (scheduledDateTime) scheduledDateTime.value = '';
    if (assignmentNotes) assignmentNotes.value = '';

    const collectorHint = document.getElementById('collectorHint');
    const vehicleHint = document.getElementById('vehicleHint');
    if (collectorHint) collectorHint.textContent = '';
    if (vehicleHint) vehicleHint.textContent = '';

    updateCapacityCircle(0);

    const confirmBtn = document.getElementById('confirmAssignmentBtn');
    if (confirmBtn) confirmBtn.disabled = true;
}

function addToTimeline(datetime, request, collectorName, vehicleName, centreName = '') {
    const timeline = document.getElementById('recentTimeline');
    if (!timeline) return;

    const now = new Date();
    const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const dateStr = now.toLocaleDateString('en-MY', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });

    const cleanCollector = (collectorName || '').replace(/[🟢🔴]/g, '').trim();
    const cleanVehicle = (vehicleName || '').replace(/[🟢🔴]/g, '').trim();
    const cleanCentre = (centreName || '').trim();

    const details = [
        `assigned to ${cleanCollector}`,
        cleanVehicle ? `Vehicle ${cleanVehicle}` : '',
        cleanCentre || ''
    ].filter(Boolean).join(', ');

    const newItem = document.createElement('div');
    newItem.className = 'timeline-item';
    newItem.innerHTML = `
        <span class="timeline-time">${timeStr}</span>
        <span class="timeline-event">
            <strong>#REQ${request?.id || ''}</strong> ${details}
        </span>
        <span class="timeline-date">${dateStr}</span>
    `;

    timeline.insertBefore(newItem, timeline.firstChild);

    while (timeline.children.length > 5) {
        timeline.removeChild(timeline.lastChild);
    }
}

function updatePagination(totalItemsOverride = null) {
    const totalItems = totalItemsOverride !== null ? totalItemsOverride : getFilteredRequests().length;
    totalPages = Math.max(1, Math.ceil(totalItems / itemsPerPage));

    if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    const prevBtn = document.querySelector('.pagination-controls .c-btn-small:first-child');
    const nextBtn = document.querySelector('.pagination-controls .c-btn-small:last-child');
    const pageIndicator = document.querySelector('.page-indicator');

    if (prevBtn && nextBtn && pageIndicator) {
        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages || totalItems === 0;
        pageIndicator.textContent = `Page ${currentPage} of ${totalPages}`;
    }
}

function changePage(direction) {
    if (direction === 'prev' && currentPage > 1) {
        currentPage--;
    } else if (direction === 'next' && currentPage < totalPages) {
        currentPage++;
    }

    filterRequests(false);
}

document.addEventListener('DOMContentLoaded', function () {
    currentPage = 1;
    itemsPerPage = 5;
    totalPages = 1;

    initCustomDropdowns();
    loadPendingRequests();
    autoSelectRequestFromURL();
    loadCollectors();
    loadVehicles();
    loadCentres();
    loadRecentAssignments();
    setupEventListeners();
    setMinDateTime();
});