let currentPage = 1;
let itemsPerPage = 5;
let totalPages = 1;

function getRequests() {
    return Array.isArray(window.requestsData) ? window.requestsData : [];
}

function getCollectors() {
    return Array.isArray(window.collectorsData) ? window.collectorsData : [];
}

function getCollectorScheduledJobs() {
    return window.collectorScheduledJobsData && typeof window.collectorScheduledJobsData === 'object'
        ? window.collectorScheduledJobsData
        : {};
}

function getVehicles() {
    return Array.isArray(window.vehiclesData) ? window.vehiclesData : [];
}

function getVehicleMaintenance() {
    return window.vehicleMaintenanceData && typeof window.vehicleMaintenanceData === 'object'
        ? window.vehicleMaintenanceData
        : {};
}

function getVehicleScheduledJobs() {
    return window.vehicleScheduledJobsData && typeof window.vehicleScheduledJobsData === 'object'
        ? window.vehicleScheduledJobsData
        : {};
}

function getCentres() {
    return Array.isArray(window.centresData) ? window.centresData : [];
}

function getCentreAcceptedTypes() {
    return window.centreAcceptedTypesData && typeof window.centreAcceptedTypesData === 'object'
        ? window.centreAcceptedTypesData
        : {};
}

function getRecentAssignments() {
    return Array.isArray(window.recentAssignmentsData) ? window.recentAssignmentsData : [];
}

function getRequestIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('requestID');
}

function getSelectedRequest() {
    const selectedCard = document.querySelector('.request-card.selected');
    if (!selectedCard) return null;
    const requestId = selectedCard.dataset.id;
    return getRequests().find(r => String(r.id) === String(requestId)) || null;
}

function getSelectedDateOnly() {
    const datetime = document.getElementById('scheduledDateTime')?.value || '';
    return datetime ? datetime.split('T')[0] : '';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

function showCollectorError(message) {
    let notif = document.getElementById('collectorErrorNotif');

    if (!notif) {
        notif = document.createElement('div');
        notif.id = 'collectorErrorNotif';

        notif.style.position = 'fixed';
        notif.style.top = '20px';
        notif.style.right = '20px';
        notif.style.background = '#e74c3c';
        notif.style.color = '#fff';
        notif.style.padding = '10px 14px';
        notif.style.borderRadius = '10px';
        notif.style.fontSize = '0.85rem';
        notif.style.boxShadow = '0 6px 15px rgba(0,0,0,0.15)';
        notif.style.zIndex = '9999';

        document.body.appendChild(notif);
    }

    notif.textContent = message;
    notif.style.display = 'block';

    setTimeout(() => {
        notif.style.display = 'none';
    }, 2500);
}

function formatRequestCode(id) {
    if (!id && id !== 0) return '';
    return `#REQ${String(id).padStart(3, '0')}`;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    if (isNaN(date)) return dateStr;
    return date.toLocaleDateString('en-MY', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

function formatMaintenanceDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    if (isNaN(date)) return dateStr;
    return date.toLocaleDateString('en-MY', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

function getMaintenanceStatusClass(status) {
    const value = String(status || '').toLowerCase().replace(/\s+/g, '-');
    if (value === 'scheduled') return 'scheduled';
    if (value === 'in-progress' || value === 'in progress') return 'in-progress';
    if (value === 'completed') return 'completed';
    if (value === 'cancelled') return 'cancelled';
    return '';
}

function toDateOnly(dateStr) {
    if (!dateStr) return null;
    const date = new Date(`${dateStr}T00:00:00`);
    return isNaN(date) ? null : date;
}

function hasDateConflictWithinOneDay(existingDateStr, selectedDateStr) {
    const existingDate = toDateOnly(existingDateStr);
    const selectedDate = toDateOnly(selectedDateStr);

    if (!existingDate || !selectedDate) return false;

    const diffMs = Math.abs(existingDate - selectedDate);
    const diffDays = diffMs / (1000 * 60 * 60 * 24);

    return diffDays <= 1;
}

function collectorHasScheduledConflict(collectorId, scheduledDate) {
    if (!collectorId || !scheduledDate) return false;

    const jobsData = getCollectorScheduledJobs();
    const jobs = Array.isArray(jobsData[String(collectorId)]) ? jobsData[String(collectorId)] : [];

    return jobs.some(job => {
        const status = String(job.status || '').toLowerCase();
        return (status === 'scheduled' || status === 'pending') &&
            hasDateConflictWithinOneDay(job.scheduledDate, scheduledDate);
    });
}

function vehicleHasBlockingMaintenance(vehicleId, scheduledDate) {
    if (!vehicleId || !scheduledDate) return false;

    const maintenanceData = getVehicleMaintenance();
    const records = Array.isArray(maintenanceData[String(vehicleId)]) ? maintenanceData[String(vehicleId)] : [];

    const selectedDate = new Date(`${scheduledDate}T00:00:00`);
    if (isNaN(selectedDate)) return false;

    return records.some(record => {
        const status = String(record.status || '').toLowerCase();
        if (status !== 'scheduled' && status !== 'in progress') return false;

        const startDate = new Date(`${record.startDate}T00:00:00`);
        if (isNaN(startDate)) return false;

        return startDate <= selectedDate;
    });
}

function vehicleHasScheduledConflict(vehicleId, scheduledDate) {
    if (!vehicleId || !scheduledDate) return false;

    const jobsData = getVehicleScheduledJobs();
    const jobs = Array.isArray(jobsData[String(vehicleId)]) ? jobsData[String(vehicleId)] : [];

    return jobs.some(job =>
        String(job.status || '').toLowerCase() === 'scheduled' &&
        hasDateConflictWithinOneDay(job.scheduledDate, scheduledDate)
    );
}

// Validate that a centre accepts the given item type
function centreAcceptsItemType(centreId, itemTypeId, itemName = '') {
    if (!centreId || !itemTypeId) return false;
    const accepted = getCentreAcceptedTypes()[String(centreId)] || [];
    // "other electronics" always accepted
    if (itemName.toLowerCase() === 'other electronics') return true;
    return accepted.includes(Number(itemTypeId));
}

// Render per‑item centre selectors
function renderItemCentreSelectors(request) {
    const container = document.getElementById('itemCentreSelectors');
    if (!container) return;

    const items = request.itemDetails || [];
    if (items.length === 0) {
        container.innerHTML = '<div class="no-items">No items found</div>';
        return;
    }

    const allCentres = getCentres();
    // Pre‑filter eligible centres (Active only)
    const eligibleCentres = allCentres.filter(centre => centre.status.toLowerCase() === 'active');

    container.innerHTML = items.map(item => {
        // For each item, further filter centres that accept its type
        const itemName = item.name.toLowerCase();
        const acceptAll = itemName === 'other electronics'; // always accepted
        const itemTypeId = item.itemTypeID;

        const centreOptions = eligibleCentres.filter(centre => {
            if (acceptAll) return true;
            const acceptedTypes = getCentreAcceptedTypes()[centre.id] || [];
            return acceptedTypes.includes(itemTypeId);
        }).map(centre => `
            <option value="${centre.id}">
                ${escapeHtml(centre.name)}
            </option>
        `).join('');

        return `
            <div class="item-centre-row" data-item-id="${item.itemID}" data-item-type-id="${item.itemTypeID}">
                <div class="item-name">${escapeHtml(item.name)}</div>
                <div class="centre-select-wrapper">
                    <select class="centre-select" data-item-id="${item.itemID}" data-item-type-id="${item.itemTypeID}">
                        <option value="">-- Select centre --</option>
                        ${centreOptions}
                    </select>
                </div>
                <div class="centre-warning" style="display:none; color:#e74c3c; font-size:0.75rem;"></div>
            </div>
        `;
    }).join('');

    // Attach change event to each select (for extra validation like status, but already filtered)
    document.querySelectorAll('.centre-select').forEach(select => {
        select.addEventListener('change', function() {
            validateItemCentre(this);
            checkRequiredFields();
        });
    });
}

// Validate a single item's centre selection
function validateItemCentre(selectElement) {
    const selectedCentreId = selectElement.value;
    const row = selectElement.closest('.item-centre-row');
    const warningSpan = row.querySelector('.centre-warning');
    if (!warningSpan) return;

    if (!selectedCentreId) {
        warningSpan.style.display = 'none';
        return;
    }

    const itemTypeId = parseInt(selectElement.dataset.itemTypeId);
    const itemName = row.querySelector('.item-name').innerText;
    const centre = getCentres().find(c => c.id === selectedCentreId);
    if (!centre) {
        warningSpan.textContent = 'Centre not found';
        warningSpan.style.display = 'block';
        return;
    }

    if (centre.status.toLowerCase() !== 'active') {
        warningSpan.textContent = `Centre is ${centre.status}, cannot assign.`;
        warningSpan.style.display = 'block';
        selectElement.value = '';
        return;
    }

    if (!centreAcceptsItemType(selectedCentreId, itemTypeId, itemName)) {
        warningSpan.textContent = `This centre does not accept "${itemName}".`;
        warningSpan.style.display = 'block';
        selectElement.value = '';
        return;
    }

    warningSpan.style.display = 'none';
}

function getCollectorReason(collector, selectedDateOnly) {
    const collectorStatus = String(collector.status || '').toLowerCase();

    if (collectorStatus === 'suspended' || collectorStatus === 'inactive') {
        return 'Suspended / Inactive';
    }

    if (selectedDateOnly && collectorHasScheduledConflict(collector.id, selectedDateOnly)) {
        return 'Scheduled job within ±1 day';
    }

    return '';
}

function getVehicleReason(vehicle, selectedDateOnly) {
    const vehicleStatus = String(vehicle.status || '').toLowerCase();

    if (vehicleStatus === 'maintenance' || vehicleStatus === 'inactive') {
        return 'Maintenance / Inactive';
    }

    if (selectedDateOnly && vehicleHasBlockingMaintenance(vehicle.id, selectedDateOnly)) {
        return 'Maintenance before/on selected date';
    }

    if (selectedDateOnly && vehicleHasScheduledConflict(vehicle.id, selectedDateOnly)) {
        return 'Scheduled job within ±1 day';
    }

    return '';
}

function setMinDateTime() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const datetimeInput = document.getElementById('scheduledDateTime');
    if (datetimeInput) {
        datetimeInput.min = now.toISOString().slice(0, 16);
    }
}

function setMinPopupDates() {
    const today = new Date();
    today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
    const todayStr = today.toISOString().slice(0, 10);

    const vehicleDatePicker = document.getElementById('vehicleAvailabilityDatePicker');
    const collectorDatePicker = document.getElementById('availabilityDatePicker');

    if (vehicleDatePicker) vehicleDatePicker.min = todayStr;
    if (collectorDatePicker) collectorDatePicker.min = todayStr;
}

function checkRequiredFields() {
    const collector = document.getElementById('collectorDropdown')?.dataset.selectedValue || '';
    const vehicle = document.getElementById('vehicleDropdown')?.dataset.selectedValue || '';
    const datetime = document.getElementById('scheduledDateTime')?.value || '';
    const selectedRequest = getSelectedRequest();

    const itemSelects = document.querySelectorAll('.centre-select');
    const allItemsHaveCentre = Array.from(itemSelects).every(select => select.value !== '');

    const confirmBtn = document.getElementById('confirmAssignmentBtn');
    if (confirmBtn) {
        confirmBtn.disabled = !(collector && vehicle && datetime && selectedRequest && allItemsHaveCentre);
    }
}

function clearCollectorSelection() {
    const collectorDropdown = document.getElementById('collectorDropdown');
    const selectedCollectorText = document.getElementById('selectedCollectorText');
    const collectorHint = document.getElementById('collectorHint');

    if (collectorDropdown) delete collectorDropdown.dataset.selectedValue;
    if (selectedCollectorText) selectedCollectorText.textContent = 'Select a collector';
    if (collectorHint) collectorHint.textContent = '';
}

function clearVehicleSelection() {
    const vehicleDropdown = document.getElementById('vehicleDropdown');
    const selectedVehicleText = document.getElementById('selectedVehicleText');
    const vehicleHint = document.getElementById('vehicleHint');

    if (vehicleDropdown) delete vehicleDropdown.dataset.selectedValue;
    if (selectedVehicleText) selectedVehicleText.textContent = 'Select a vehicle';
    if (vehicleHint) vehicleHint.textContent = '';
}

function clearCentreSelections() {
    // Clear all item centre selectors
    const container = document.getElementById('itemCentreSelectors');
    if (container) container.innerHTML = '';
}

function refreshSelectionsAfterDateChange() {
    const selectedDateOnly = getSelectedDateOnly();

    const collectorId = document.getElementById('collectorDropdown')?.dataset.selectedValue || '';
    if (collectorId) {
        const collector = getCollectors().find(c => String(c.id) === String(collectorId));
        if (!collector || getCollectorReason(collector, selectedDateOnly)) {
            clearCollectorSelection();
        }
    }

    const vehicleId = document.getElementById('vehicleDropdown')?.dataset.selectedValue || '';
    if (vehicleId) {
        const vehicle = getVehicles().find(v => String(v.id) === String(vehicleId));
        if (!vehicle || getVehicleReason(vehicle, selectedDateOnly)) {
            clearVehicleSelection();
        }
    }

    // Centre selections are re‑rendered when a request is selected, no need to clear here
    checkRequiredFields();
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
            <span class="summary-value">${escapeHtml(request.provider)}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Items:</span>
            <span class="summary-value">${escapeHtml((request.items || []).join(', '))} (${escapeHtml(request.weight || '0 kg')})</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Preferred:</span>
            <span class="summary-value">${dateStr}</span>
        </div>
        <div class="summary-address">
            📍 ${escapeHtml(request.address || '-')}
        </div>
    `;

    // Render per‑item centre selectors
    renderItemCentreSelectors(request);
}

function updateSelectedRequestId(request) {
    const badge = document.getElementById('selectedRequestId');
    if (!badge) return;

    if (request) {
        badge.textContent = formatRequestCode(request.id);
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
        cleaned = `<strong>${formatRequestCode(requestId)}</strong> ${cleaned}`;
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
            <span class="timeline-event">${cleanTimelineEventText(a.event, a.requestID)}</span>
            <span class="timeline-date">${a.date || ''}</span>
        </div>
    `).join('');
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
            (req.items || []).some(item => String(item).toLowerCase().includes(search))
        );
    }

    return filteredRequests;
}

function filterRequests() {
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
        const centreContainer = document.getElementById('itemCentreSelectors');
        if (centreContainer) centreContainer.innerHTML = '';
        checkRequiredFields();
        return;
    }

    container.innerHTML = '';
    filteredRequests.forEach((req, index) => {
        container.appendChild(createRequestCard(req, index));
    });

    let selectedRequest = filteredRequests[0];
    const requestIdFromURL = getRequestIdFromURL();

    if (requestIdFromURL) {
        const urlRequest = filteredRequests.find(r => String(r.id) === String(requestIdFromURL));
        if (urlRequest) selectedRequest = urlRequest;
    }

    if (selectedRequest) {
        const selectedCard = container.querySelector(`.request-card[data-id="${selectedRequest.id}"]`);
        if (selectedCard) selectedCard.classList.add('selected');
        updateAssignmentPanel(selectedRequest);
        updateSelectedRequestId(selectedRequest);
    }

    checkRequiredFields();
}

function autoSelectRequestFromURL() {
    const requestId = getRequestIdFromURL();
    if (!requestId) return;

    const request = getRequests().find(r => String(r.id) === String(requestId));
    if (!request) return;

    filterRequests();

    setTimeout(() => {
        const card = document.querySelector(`.request-card[data-id="${requestId}"]`);
        if (!card) return;

        document.querySelectorAll('.request-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');

        clearCollectorSelection();
        clearVehicleSelection();
        clearCentreSelections();

        updateAssignmentPanel(request);
        updateSelectedRequestId(request);
        checkRequiredFields();
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
}

// --- Collector modal (unchanged) ---
let selectedAvailabilityDate = null;
let selectedVehicleAvailabilityDate = null;

function updateCollectorDateDisplay() {
    const dateDisplay = document.getElementById('selectedDateDisplay');
    if (!dateDisplay) return;

    if (selectedAvailabilityDate) {
        dateDisplay.innerHTML = `<i class="fas fa-calendar-day"></i> ${selectedAvailabilityDate.toLocaleDateString('en-MY', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        })}`;
    } else {
        dateDisplay.innerHTML = 'No date selected';
    }
}

function closeCollectorAvailabilityModal() {
    const modal = document.getElementById('collectorAvailabilityModal');
    if (modal) modal.style.display = 'none';
}

function renderAllCollectorsAvailability(selectedDate = null) {
    const contentDiv = document.getElementById('collectorAvailabilityContent');
    const countBadge = document.getElementById('availableCountBadge');
    if (!contentDiv) return;

    const collectors = getCollectors();
    const selectedDateOnly = selectedDate ? selectedDate.toISOString().slice(0, 10) : '';

    let availableCollectors = [];
    if (selectedDateOnly) {
        availableCollectors = collectors.filter(collector => !getCollectorReason(collector, selectedDateOnly));
    }

    if (countBadge) {
        if (selectedDateOnly) {
            countBadge.style.display = 'inline-flex';
            countBadge.innerHTML = `<i class="fas fa-users"></i> ${availableCollectors.length} Available`;
            countBadge.style.background = availableCollectors.length > 0 ? 'var(--MainBlue)' : '#e74c3c';
        } else {
            countBadge.style.display = 'none';
        }
    }

    if (!selectedDateOnly) {
        contentDiv.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <p>Select a date to see collector availability.</p>
            </div>
        `;
        return;
    }

    if (availableCollectors.length === 0) {
        contentDiv.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Collectors Available</h3>
                <p>No collectors are available on ${selectedDate.toLocaleDateString('en-MY')}</p>
            </div>
        `;
        return;
    }

    const collectorJobsData = getCollectorScheduledJobs();

    contentDiv.innerHTML = `
        <div class="collectors-grid">
            ${availableCollectors.map(collector => {
                const jobs = Array.isArray(collectorJobsData[String(collector.id)])
                    ? collectorJobsData[String(collector.id)]
                    : [];

                const scheduledJobs = jobs
                    .filter(job => {
                        const status = String(job.status || '').toLowerCase();
                        return status === 'scheduled' || status === 'pending';
                    })
                    .sort((a, b) => new Date(a.scheduledDate) - new Date(b.scheduledDate));

                return `
                    <div class="collector-availability-card available">
                        <div class="collector-header">
                            <div class="collector-name">
                                <span class="status-dot green"></span>
                                ${escapeHtml(collector.name)}
                            </div>
                            <div class="collector-stats">
                                <span class="stat-badge">📋 ${scheduledJobs.length} Assigned Jobs</span>
                            </div>
                        </div>

                        <div class="scheduled-jobs-section">
                            <div class="section-title">
                                <i class="fas fa-list"></i>
                                Job Schedule
                            </div>

                            ${scheduledJobs.length > 0 ? `
                                <div class="scheduled-jobs-list">
                                    ${scheduledJobs.map(job => `
                                        <div class="scheduled-job-item">
                                            <div class="job-id-display">
                                                <span class="job-id-mini">Job #${String(job.jobID).padStart(3, '0')}</span>
                                                <span class="job-date-simple">${formatDate(job.scheduledDate)}</span>
                                                <span class="job-status-mini">${escapeHtml(job.status || '-')}</span>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : `
                                <div class="empty-jobs">
                                    <p>No assigned jobs</p>
                                </div>
                            `}
                        </div>

                        <button class="select-collector-btn" onclick="selectCollectorFromModal('${collector.id}', '${escapeHtml(collector.name)}')">
                            <i class="fas fa-check-circle"></i> Select Collector
                        </button>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

function openCollectorAvailabilityModal() {
    const scheduledDateInput = document.getElementById('scheduledDateTime')?.value;
    if (scheduledDateInput) {
        selectedAvailabilityDate = new Date(scheduledDateInput);
        selectedAvailabilityDate.setHours(0, 0, 0, 0);
    } else {
        selectedAvailabilityDate = null;
    }

    let modal = document.getElementById('collectorAvailabilityModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'collectorAvailabilityModal';
        modal.className = 'ops-modal-overlay';
        modal.style.display = 'none';
        document.body.appendChild(modal);

        modal.innerHTML = `
            <div class="ops-modal ops-modal-large nicer-modal">
                <div class="ops-modal-header nicer-modal-header">
                    <div>
                        <h3>Collector Availability</h3>
                        <p class="ops-modal-subtitle" id="selectedDateDisplay">No date selected</p>
                    </div>
                    <button type="button" class="ops-modal-close plain-close-btn" id="closeCollectorAvailabilityModal">&times;</button>
                </div>
                <div class="ops-modal-body">
                    <div class="availability-controls cleaner-toolbar">
                        <div class="availability-date-picker">
                            <input type="date" id="availabilityDatePicker" class="date-picker-small">
                        </div>
                        <div class="available-count-badge" id="availableCountBadge"></div>
                    </div>
                    <div id="collectorAvailabilityContent"></div>
                </div>
            </div>
        `;

        document.getElementById('closeCollectorAvailabilityModal')?.addEventListener('click', closeCollectorAvailabilityModal);

        modal.addEventListener('click', function (e) {
            if (e.target.id === 'collectorAvailabilityModal') {
                closeCollectorAvailabilityModal();
            }
        });

        document.getElementById('availabilityDatePicker')?.addEventListener('change', function (e) {
            if (e.target.value) {
                selectedAvailabilityDate = new Date(e.target.value);
                selectedAvailabilityDate.setHours(0, 0, 0, 0);
            } else {
                selectedAvailabilityDate = null;
            }
            updateCollectorDateDisplay();
            renderAllCollectorsAvailability(selectedAvailabilityDate);
        });
    }

    setMinPopupDates();

    const datePicker = document.getElementById('availabilityDatePicker');
    if (datePicker && selectedAvailabilityDate) {
        const year = selectedAvailabilityDate.getFullYear();
        const month = String(selectedAvailabilityDate.getMonth() + 1).padStart(2, '0');
        const day = String(selectedAvailabilityDate.getDate()).padStart(2, '0');
        datePicker.value = `${year}-${month}-${day}`;
    } else if (datePicker) {
        datePicker.value = '';
    }

    updateCollectorDateDisplay();
    renderAllCollectorsAvailability(selectedAvailabilityDate);
    modal.style.display = 'flex';
}

function selectCollectorFromModal(collectorId, collectorName) {
    const collector = getCollectors().find(c => String(c.id) === String(collectorId));
    const selectedDateOnly = getSelectedDateOnly();

    if (!collector) {
        // alert('Selected collector not found.');
        return;
    }

    const reason = getCollectorReason(collector, selectedDateOnly);
    if (reason) {
        showCollectorError(`This collector cannot be selected: ${reason}`);
        return;

    }

    const selectedCollectorText = document.getElementById('selectedCollectorText');
    const collectorDropdown = document.getElementById('collectorDropdown');

    if (selectedCollectorText && collectorDropdown) {
        collectorDropdown.dataset.selectedValue = collectorId;
        selectedCollectorText.innerHTML = `<span class="status-dot green"></span> ${collectorName}`;
        const hint = document.getElementById('collectorHint');
        if (hint) hint.textContent = '';
        closeCollectorAvailabilityModal();
        checkRequiredFields();
    }
}

window.selectCollectorFromModal = selectCollectorFromModal;

// --- Vehicle modal (unchanged) ---
function updateVehicleDateDisplay() {
    const subtitle = document.getElementById('selectedVehicleDateDisplay');
    if (!subtitle) return;

    if (selectedVehicleAvailabilityDate) {
        subtitle.innerHTML = `<i class="fas fa-calendar-day"></i> ${selectedVehicleAvailabilityDate.toLocaleDateString('en-MY', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        })}`;
    } else {
        subtitle.innerHTML = 'No date selected';
    }
}

function openVehicleMaintenanceModal() {
    const scheduledDateInput = document.getElementById('scheduledDateTime')?.value;
    if (scheduledDateInput) {
        selectedVehicleAvailabilityDate = new Date(scheduledDateInput);
        selectedVehicleAvailabilityDate.setHours(0, 0, 0, 0);
    } else {
        selectedVehicleAvailabilityDate = null;
    }

    const modal = document.getElementById('vehicleMaintenanceModal');
    if (!modal) return;

    setMinPopupDates();

    const datePicker = document.getElementById('vehicleAvailabilityDatePicker');
    if (datePicker && selectedVehicleAvailabilityDate) {
        const year = selectedVehicleAvailabilityDate.getFullYear();
        const month = String(selectedVehicleAvailabilityDate.getMonth() + 1).padStart(2, '0');
        const day = String(selectedVehicleAvailabilityDate.getDate()).padStart(2, '0');
        datePicker.value = `${year}-${month}-${day}`;
    } else if (datePicker) {
        datePicker.value = '';
    }

    updateVehicleDateDisplay();
    renderVehicleMaintenanceList();
    modal.style.display = 'flex';
}

function closeVehicleMaintenanceModal() {
    const modal = document.getElementById('vehicleMaintenanceModal');
    if (modal) modal.style.display = 'none';
}

function renderVehicleMaintenanceList() {
    const container = document.getElementById('vehicleMaintenanceCalendar');
    const countBadge = document.getElementById('availableVehicleCountBadge');
    if (!container) return;

    const selectedDateOnly = selectedVehicleAvailabilityDate
        ? selectedVehicleAvailabilityDate.toISOString().slice(0, 10)
        : '';

    const vehicles = [...getVehicles()].sort((a, b) =>
        String(a.model || '').localeCompare(String(b.model || ''))
    );

    const availableCount = vehicles.filter(vehicle => !getVehicleReason(vehicle, selectedDateOnly)).length;

    if (countBadge) {
        if (selectedDateOnly) {
            countBadge.style.display = 'inline-flex';
            countBadge.innerHTML = `<i class="fas fa-truck"></i> ${availableCount} Available`;
            countBadge.style.background = availableCount > 0 ? 'var(--MainBlue)' : '#e74c3c';
        } else {
            countBadge.style.display = 'none';
        }
    }

    if (vehicles.length === 0) {
        container.innerHTML = `
            <div class="maintenance-empty">
                No vehicles found.
            </div>
        `;
        return;
    }

    const maintenanceData = getVehicleMaintenance();

    container.innerHTML = vehicles.map(vehicle => {
        const records = Array.isArray(maintenanceData[String(vehicle.id)]) ? maintenanceData[String(vehicle.id)] : [];
        const sortedRecords = [...records].sort((a, b) => new Date(b.startDate || 0) - new Date(a.startDate || 0));
        const reason = getVehicleReason(vehicle, selectedDateOnly);
        const isAvailable = !reason;

        return `
            <div class="maintenance-vehicle-card ${isAvailable ? 'vehicle-available-card' : 'vehicle-blocked-card'}">
                <div class="maintenance-vehicle-top">
                    <div>
                        <div class="maintenance-vehicle-name">
                            <span class="status-dot ${isAvailable ? 'green' : 'red'}"></span>
                            ${escapeHtml(vehicle.model || `Vehicle ID ${vehicle.id}`)}
                        </div>
                        <div class="maintenance-vehicle-meta">
                            Status: ${escapeHtml(vehicle.status || '-')} • Capacity: ${escapeHtml(vehicle.capacity || '-')}
                        </div>
                    </div>
                    <span class="maintenance-badge ${isAvailable ? 'has-records' : 'blocked-records'}">
                        ${escapeHtml(isAvailable ? 'Available' : reason)}
                    </span>
                </div>

                ${
                    sortedRecords.length === 0
                        ? `<div class="maintenance-empty-small">No maintenance history for this vehicle.</div>`
                        : `
                            <div class="maintenance-list">
                                ${sortedRecords.map(record => `
                                    <div class="maintenance-item">
                                        <div class="maintenance-item-top">
                                            <div class="maintenance-dates">
                                                <strong>Start:</strong> ${formatMaintenanceDate(record.startDate)}<br>
                                                <strong>End:</strong> ${record.endDate ? formatMaintenanceDate(record.endDate) : 'Not set'}
                                            </div>
                                            <div class="maintenance-status ${getMaintenanceStatusClass(record.status)}">
                                                ${escapeHtml(record.status || '-')}
                                            </div>
                                        </div>
                                        <div class="maintenance-notes">
                                            ${escapeHtml((record.notes || '').trim() || 'No description provided.')}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        `
                }

                <button
                    class="select-vehicle-btn small-select-btn"
                    onclick="selectVehicleFromModal('${vehicle.id}', '${escapeHtml(vehicle.model || '')}')"
                    ${isAvailable ? '' : 'disabled'}
                >
                    <i class="fas fa-check-circle"></i>
                    ${isAvailable ? 'Select Vehicle' : 'Not Available'}
                </button>
            </div>
        `;
    }).join('');
}

function selectVehicleFromModal(vehicleId, vehicleName) {
    const selectedDateOnly = getSelectedDateOnly();
    const vehicle = getVehicles().find(v => String(v.id) === String(vehicleId));

    if (!vehicle) {
        // alert('Selected vehicle not found.');
        return;
    }

    const reason = getVehicleReason(vehicle, selectedDateOnly);
    if (reason) {
        // alert(`This vehicle cannot be selected: ${reason}`);
        return;
    }

    const vehicleDropdown = document.getElementById('vehicleDropdown');
    const selectedVehicleText = document.getElementById('selectedVehicleText');
    const hint = document.getElementById('vehicleHint');

    if (vehicleDropdown) vehicleDropdown.dataset.selectedValue = vehicleId;
    if (selectedVehicleText) {
        selectedVehicleText.innerHTML = `<span class="status-dot green"></span> ${vehicleName}`;
    }
    if (hint) {
        hint.textContent = vehicle.capacity || '';
    }

    closeVehicleMaintenanceModal();
    checkRequiredFields();
}

window.selectVehicleFromModal = selectVehicleFromModal;

async function confirmAssignment() {
    const selectedRequest = getSelectedRequest();
    const datetime = document.getElementById('scheduledDateTime')?.value || '';
    const collector = document.getElementById('collectorDropdown')?.dataset.selectedValue || '';
    const vehicle = document.getElementById('vehicleDropdown')?.dataset.selectedValue || '';
    const notes = document.getElementById('assignmentNotes')?.value || '';
    const confirmBtn = document.getElementById('confirmAssignmentBtn');

    if (!selectedRequest) {
    showCollectorError('Please select a request.');
    return;
}

if (!collector) {
    showCollectorError('Please select a collector.');
    return;
}

if (!vehicle) {
    showCollectorError('Please select a vehicle.');
    return;
}

if (!datetime) {
    showCollectorError('Please select a scheduled date and time.');
    return;
}
    const selectedDateOnly = datetime.split('T')[0];

    // Validate collector availability again
    const collectorObj = getCollectors().find(c => String(c.id) === String(collector));
    if (!collectorObj) {
        // alert('Selected collector not found.');
        return;
    }
    const collectorReason = getCollectorReason(collectorObj, selectedDateOnly);
    if (collectorReason) {
    showCollectorError(`Collector unavailable: ${collectorReason}`);
    return;
}

    // Validate vehicle availability again
    const vehicleObj = getVehicles().find(v => String(v.id) === String(vehicle));
    if (!vehicleObj) {
        // alert('Selected vehicle not found.');
        return;
    }
    const vehicleReason = getVehicleReason(vehicleObj, selectedDateOnly);
    if (vehicleReason) {
    showCollectorError(`Vehicle unavailable: ${vehicleReason}`);
    return;
}

    // Gather item‑centre selections
    const itemSelections = [];
    const itemRows = document.querySelectorAll('.item-centre-row');
    let allValid = true;

    for (const row of itemRows) {
        const select = row.querySelector('.centre-select');
        const centreId = select.value;
       if (!centreId) {
    showCollectorError('Please select a centre for all items.');
    allValid = false;
    break;
}
        const itemId = row.dataset.itemId;
        itemSelections.push({ itemID: parseInt(itemId), centreID: parseInt(centreId) });
    }

    if (!allValid) return;

    const originalBtnText = confirmBtn ? confirmBtn.innerHTML : '✓ Confirm';

    try {
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = 'Saving...';
        }

        const formData = new FormData();
        formData.append('action', 'assign_request');
        formData.append('requestID', selectedRequest.id);
        formData.append('collectorID', collector);
        formData.append('vehicleID', vehicle);
        formData.append('scheduledDateTime', datetime);
        formData.append('notes', notes);
        formData.append('item_centres', JSON.stringify(itemSelections));

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        let result = null;
        try {
            result = await response.json();
        } catch (e) {
            throw new Error('Server returned an invalid response.');
        }

        if (!response.ok || !result || !result.success) {
            throw new Error(result?.message || 'Failed to save assignment.');
        }

        const collectorText = document.getElementById('selectedCollectorText')?.textContent || 'Collector';
        const vehicleText = document.getElementById('selectedVehicleText')?.textContent || 'Vehicle';
        const centreText = 'multiple centres';

        addToTimeline(datetime, selectedRequest, collectorText, vehicleText, centreText);

        if (Array.isArray(window.requestsData)) {
            const index = window.requestsData.findIndex(r => String(r.id) === String(selectedRequest.id));
            if (index !== -1) {
                window.requestsData.splice(index, 1);
            }
        }

        if (!window.collectorScheduledJobsData || typeof window.collectorScheduledJobsData !== 'object') {
            window.collectorScheduledJobsData = {};
        }

        if (!Array.isArray(window.collectorScheduledJobsData[String(collector)])) {
            window.collectorScheduledJobsData[String(collector)] = [];
        }

        window.collectorScheduledJobsData[String(collector)].push({
            jobID: result.jobID || '',
            scheduledDate: datetime.split('T')[0],
            status: 'Pending'
        });

        resetAssignmentForm(true);
        filterRequests();
        checkRequiredFields();
    } catch (error) {
        showCollectorError(error.message || 'Error saving assignment.');
    } finally {
        if (confirmBtn) {
            confirmBtn.innerHTML = originalBtnText;
            checkRequiredFields();
        }
    }
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

    const cleanCollector = String(collectorName || '').replace(/[🟢🔴]/g, '').trim();
    const cleanVehicle = String(vehicleName || '').replace(/[🟢🔴]/g, '').trim();
    const cleanCentre = String(centreName || '').trim();

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
            <strong>${formatRequestCode(request?.id || '')}</strong> ${details}
        </span>
        <span class="timeline-date">${dateStr}</span>
    `;

    timeline.insertBefore(newItem, timeline.firstChild);

    while (timeline.children.length > 5) {
        timeline.removeChild(timeline.lastChild);
    }
}

function resetAssignmentForm(clearSelectedRequest = false) {
    if (clearSelectedRequest) {
        updateSelectedRequestId(null);
        const summaryDiv = document.getElementById('selectedRequestSummary');
        if (summaryDiv) summaryDiv.innerHTML = '';
        document.querySelectorAll('.request-card').forEach(c => c.classList.remove('selected'));
        const centreContainer = document.getElementById('itemCentreSelectors');
        if (centreContainer) centreContainer.innerHTML = '';
    }

    clearCollectorSelection();
    clearVehicleSelection();

    const scheduledDateTime = document.getElementById('scheduledDateTime');
    const assignmentNotes = document.getElementById('assignmentNotes');

    if (scheduledDateTime) scheduledDateTime.value = '';
    if (assignmentNotes) assignmentNotes.value = '';

    closeVehicleMaintenanceModal();
    closeCollectorAvailabilityModal();

    const confirmBtn = document.getElementById('confirmAssignmentBtn');
    if (confirmBtn) confirmBtn.disabled = true;
}

function setupEventListeners() {
    document.getElementById('requestFilter')?.addEventListener('change', filterRequests);
    document.getElementById('requestSearch')?.addEventListener('input', filterRequests);

    document.getElementById('scheduledDateTime')?.addEventListener('change', function () {
        refreshSelectionsAfterDateChange();
    });

    document.getElementById('confirmAssignmentBtn')?.addEventListener('click', confirmAssignment);
    document.getElementById('resetAssignmentBtn')?.addEventListener('click', () => resetAssignmentForm(true));

    document.getElementById('viewCollectorAvailability')?.addEventListener('click', openCollectorAvailabilityModal);
    document.getElementById('viewVehicleStatus')?.addEventListener('click', openVehicleMaintenanceModal);
    document.getElementById('closeVehicleMaintenanceModal')?.addEventListener('click', closeVehicleMaintenanceModal);

    document.getElementById('vehicleMaintenanceModal')?.addEventListener('click', function (e) {
        if (e.target.id === 'vehicleMaintenanceModal') {
            closeVehicleMaintenanceModal();
        }
    });

    document.getElementById('vehicleAvailabilityDatePicker')?.addEventListener('change', function (e) {
        if (e.target.value) {
            selectedVehicleAvailabilityDate = new Date(e.target.value);
            selectedVehicleAvailabilityDate.setHours(0, 0, 0, 0);
        } else {
            selectedVehicleAvailabilityDate = null;
        }
        updateVehicleDateDisplay();
        renderVehicleMaintenanceList();
    });
}

function addOperationsEnhancementStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .popup-only-field {
            pointer-events: none;
            width: 100%;
        }

        .no-arrow-field {
            justify-content: flex-start !important;
            cursor: default !important;
            padding-right: 1rem !important;
        }

        .popup-action-btn {
            width: 46px;
            min-width: 46px;
            height: 46px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Styles for per‑item centre selectors */
        .item-centre-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.8rem;
            flex-wrap: wrap;
        }
        .item-name {
            min-width: 130px;
            font-weight: 500;
        }
        .centre-select-wrapper {
            flex: 2;
            min-width: 200px;
        }
        .centre-select {
            width: 100%;
            padding: 0.6rem 1rem;
            border-radius: 30px;
            border: 1px solid var(--BlueGray);
            background: var(--bg-color);
            color: var(--text-color);
            font-size: 0.9rem;
        }
        .centre-warning {
            flex: 1;
            font-size: 0.75rem;
        }

        .centre-reason-text {
            display: block;
            font-size: 0.68rem;
            margin-top: 0.18rem;
            color: #d9534f;
            line-height: 1.15;
        }

        .nicer-modal {
            max-width: 980px;
            border-radius: 24px;
            overflow: hidden;
        }

        .nicer-modal-header {
            padding: 1.1rem 1.35rem;
            background: linear-gradient(180deg, var(--sec-bg-color), var(--bg-color));
            border-bottom: 1px solid var(--BlueGray);
        }

        .nicer-modal-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .plain-close-btn {
            width: auto !important;
            height: auto !important;
            padding: 0 !important;
            border: none !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            color: var(--Gray) !important;
            font-size: 1.8rem !important;
            line-height: 1 !important;
        }

        .plain-close-btn:hover {
            background: transparent !important;
            color: var(--text-color) !important;
            transform: none !important;
        }

        .cleaner-toolbar,
        .availability-controls,
        .maintenance-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            padding: 0;
            background: transparent;
        }

        .date-picker-small {
            padding: 0.55rem 0.8rem;
            border-radius: 12px;
            border: 1px solid var(--BlueGray);
            background: var(--bg-color);
            color: var(--text-color);
            font-size: 0.88rem;
            outline: none;
        }

        .available-count-badge {
            display: none;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            color: #fff;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .collectors-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .collector-availability-card {
            background: var(--sec-bg-color);
            border: 1px solid var(--BlueGray);
            border-left: 4px solid #2ecc71;
            border-radius: 20px;
            padding: 1rem;
            box-shadow: 0 8px 20px var(--shadow-color);
        }

        .collector-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.9rem;
            margin-bottom: 0.8rem;
            flex-wrap: wrap;
        }

        .collector-name {
            font-size: 1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .scheduled-jobs-section {
            margin-top: 0.4rem;
        }

        .section-title {
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.55rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .scheduled-jobs-list {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        .scheduled-job-item {
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 14px;
            padding: 0.7rem 0.8rem;
        }

        .scheduled-job-item .job-status-mini {
            display: none !important;
        }

        .job-id-display {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .job-id-mini {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--MainBlue);
            background: var(--LowMainBlue);
            padding: 0.22rem 0.65rem;
            border-radius: 999px;
        }

        .job-date-simple,
        .empty-jobs p,
        .maintenance-empty-small,
        .maintenance-vehicle-meta,
        .maintenance-notes {
            font-size: 0.82rem;
            color: var(--Gray);
        }

        .empty-jobs {
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 14px;
            padding: 0.7rem 0.8rem;
        }

        .select-collector-btn {
            width: auto;
            min-width: 142px;
            padding: 0.5rem 0.8rem;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-top: 0.8rem;
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            border: none;
            background: var(--MainBlue);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .maintenance-vehicle-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .maintenance-vehicle-card {
            border-radius: 20px;
            padding: 1rem;
            background: var(--sec-bg-color);
            border: 1px solid var(--BlueGray);
            box-shadow: 0 8px 20px var(--shadow-color);
        }

        .vehicle-available-card { border-left: 4px solid #2ecc71; }
        .vehicle-blocked-card { border-left: 4px solid #e74c3c; }

        .maintenance-vehicle-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.85rem;
            flex-wrap: wrap;
            margin-bottom: 0.8rem;
        }

        .maintenance-vehicle-name {
            font-size: 1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .maintenance-badge {
            border-radius: 999px;
            padding: 0.3rem 0.75rem;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .maintenance-badge.blocked-records {
            background: #fdeaea;
            color: #c0392b;
        }

        .maintenance-badge.has-records {
            background: #eaf6ff;
            color: var(--MainBlue);
        }

        .maintenance-list {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        .maintenance-item {
            background: var(--bg-color);
            border-radius: 12px;
            padding: 0.75rem;
        }

        .maintenance-item-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 0.4rem;
        }

        .maintenance-status {
            padding: 0.28rem 0.65rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .maintenance-status.scheduled {
            background: #fff4d6;
            color: #8a6d1d;
        }

        .maintenance-status.in-progress {
            background: #eaf2ff;
            color: #2853a6;
        }

        .maintenance-status.completed {
            background: #e7f8ee;
            color: #1d7d46;
        }

        .select-vehicle-btn {
            border: none;
            background: var(--MainBlue);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .small-select-btn {
            width: auto;
            min-width: 130px;
            padding: 0.48rem 0.78rem;
            font-size: 0.78rem;
            border-radius: 11px;
            margin-top: 0.8rem;
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .select-vehicle-btn:disabled {
            background: #c7ced8;
            cursor: not-allowed;
        }

        .empty-state,
        .maintenance-empty {
            text-align: center;
            padding: 2.2rem 1rem;
            color: var(--Gray);
        }

        .empty-state i,
        .maintenance-empty i {
            font-size: 2.3rem;
            margin-bottom: 0.75rem;
            opacity: 0.55;
        }

        .status-dot {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 50%;
        }

        .status-dot.green { background: #2ecc71; }
        .status-dot.red { background: #e74c3c; }

        @media (max-width: 900px) {
            .maintenance-vehicle-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .collector-header,
            .maintenance-vehicle-top,
            .maintenance-item-top,
            .job-id-display {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    `;
    document.head.appendChild(style);
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
            <span class="request-id">${formatRequestCode(request.id)}</span>
        </div>
        <div class="card-body">
            <div class="provider-name">${escapeHtml(request.provider)}</div>
            <div class="e-waste-items">
                ${(request.items || []).map(item => `<span class="item-chip">${escapeHtml(item)}</span>`).join('')}
            </div>
            <div class="card-footer">
                <span>${escapeHtml(request.weight || '0 kg')}</span>
                <span class="preferred-date">${formattedDate}</span>
            </div>
        </div>
    `;

    card.addEventListener('click', () => {
        document.querySelectorAll('.request-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');

        clearCollectorSelection();
        clearVehicleSelection();
        clearCentreSelections();

        updateAssignmentPanel(request);
        updateSelectedRequestId(request);
        checkRequiredFields();
    });

    return card;
}

document.addEventListener('DOMContentLoaded', function () {
    filterRequests();
    autoSelectRequestFromURL();
    loadRecentAssignments();
    setupEventListeners();
    setMinDateTime();
    setMinPopupDates();
    addOperationsEnhancementStyles();
    checkRequiredFields();
});