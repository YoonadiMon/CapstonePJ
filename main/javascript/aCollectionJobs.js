let map = null;
let markersLayer = null;
let routeLayer = null;
let selectedCollectorId = null;
let tilesLayer = null;

const collectionJobsData = window.collectionJobsData || {
    handoverJobs: [],
    delayedJobs: [],
    pendingDropoffJobs: [],
    activeCollectors: [],
    quickStats: {
        completedToday: 0,
        avgResponse: '0min',
        totalDistance: 0
    },
    handoverLookup: {},
    delayedLookup: {},
    pendingDropoffLookup: {},
    centresAvailable: 0
};

const geocodeCache = {};
let mapLayersVisible = true;

document.addEventListener('DOMContentLoaded', function () {
    initMap();
    loadAllData();

    const reportIssueModal = document.getElementById('reportIssueModal');
    const reportIssueForm = document.getElementById('reportIssueForm');
    const closeReportIssueModalBtn = document.getElementById('closeReportIssueModal');
    const cancelReportIssueBtn = document.getElementById('cancelReportIssueBtn');
    const issueType = document.getElementById('issueType');
    const otherIssueGroup = document.getElementById('otherIssueGroup');
    const otherIssueText = document.getElementById('otherIssueText');

    if (closeReportIssueModalBtn) {
        closeReportIssueModalBtn.addEventListener('click', closeReportIssueModal);
    }

    if (cancelReportIssueBtn) {
        cancelReportIssueBtn.addEventListener('click', closeReportIssueModal);
    }

    if (reportIssueModal) {
        reportIssueModal.addEventListener('click', function (e) {
            if (e.target === reportIssueModal) {
                closeReportIssueModal();
            }
        });
    }

    document.querySelectorAll('.priority-option input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.priority-option').forEach(opt => {
                opt.classList.remove('selected');
            });

            const parent = this.closest('.priority-option');
            if (parent) {
                parent.classList.add('selected');
            }
        });
    });

    if (issueType) {
        issueType.addEventListener('change', function () {
            if (this.value === 'Other') {
                if (otherIssueGroup) otherIssueGroup.style.display = 'block';
                if (otherIssueText) otherIssueText.setAttribute('required', true);
            } else {
                if (otherIssueGroup) otherIssueGroup.style.display = 'none';
                if (otherIssueText) {
                    otherIssueText.removeAttribute('required');
                    otherIssueText.value = '';
                }
            }
        });
    }

    if (reportIssueForm) {
        reportIssueForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const issueJobId = document.getElementById('issueJobId');
            const issueRequestId = document.getElementById('issueRequestId');
            const issueDescription = document.getElementById('issueDescription');

            let selectedIssue = issueType ? issueType.value : '';
            if (selectedIssue === 'Other') {
                selectedIssue = otherIssueText ? otherIssueText.value.trim() : '';
            }

            const selectedPriority = document.querySelector('input[name="priority"]:checked');

            const formData = {
                jobId: issueJobId ? issueJobId.value : '',
                requestId: issueRequestId ? issueRequestId.value : '',
                issueType: selectedIssue,
                priority: selectedPriority ? selectedPriority.value : '',
                description: issueDescription ? issueDescription.value.trim() : ''
            };

            if (!formData.issueType || !formData.priority || !formData.description) {
                alert('Please complete all fields.');
                return;
            }

            console.log('Issue form submitted:', formData);
            alert('Issue reported successfully.');
            closeReportIssueModal();
        });
    }

    document.getElementById('assignHandoverForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        alert(`Handover assigned for ${document.getElementById('handoverJobId').value}`);
        closeAssignHandoverModal();
    });

    document.getElementById('reassignJobForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        alert(`Job reassigned for ${document.getElementById('reassignJobId').value}`);
        closeReassignJobModal();
    });

    document.getElementById('reassignCentreForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        alert(`Collection centre reassigned for ${document.getElementById('reassignCentreJobId').value}`);
        closeReassignCentreModal();
    });
});

function loadAllData() {
    loadHandoverJobs();
    loadDelayedJobs();
    loadPendingDropoffJobs();
    loadActiveCollectors();
    loadQuickStats();
    updateCounts();
    updateMapMarkers();
}

function loadHandoverJobs() {
    displayHandoverJobs(collectionJobsData.handoverJobs || []);
}

function displayHandoverJobs(jobs) {
    const handoverList = document.getElementById('handoverList');
    const panelHandoverCount = document.getElementById('panelHandoverCount');

    if (!handoverList) return;

    if (!jobs.length) {
        handoverList.innerHTML = `
            <div class="no-jobs-message">
                <i class="fas fa-check-circle"></i>
                <p>No handover required</p>
            </div>
        `;
        if (panelHandoverCount) panelHandoverCount.textContent = '0';
        return;
    }

    if (panelHandoverCount) panelHandoverCount.textContent = jobs.length;

    handoverList.innerHTML = jobs.map(job => `
        <div class="panel-item">
            <div class="item-info">
                <i class="fas fa-route"></i>
                <div>
                    <strong>${escapeHtml(job.id || '')}</strong>
                    <small>${escapeHtml(job.location || '-')}</small>
                </div>
                <span class="item-reason">${escapeHtml(job.reason || '-')}</span>
            </div>
            <div class="item-actions">
                <button class="btn-icon" onclick="goToIssuePage('${escapeJs(job.jobID || '')}')" title="Go to issue page">
                    <i class="fas fa-exclamation-circle"></i>
                </button>
                <button class="btn-icon" onclick="viewJobDetails('${escapeJs(job.id || '')}')" title="View details">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function loadDelayedJobs() {
    displayDelayedJobs(collectionJobsData.delayedJobs || []);
}

function displayDelayedJobs(jobs) {
    const delayedList = document.getElementById('delayedList');
    const panelDelayedCount = document.getElementById('panelDelayedCount');

    if (!delayedList) return;

    if (!jobs.length) {
        delayedList.innerHTML = `
            <div class="no-jobs-message">
                <i class="fas fa-check-circle"></i>
                <p>No delayed jobs</p>
            </div>
        `;
        if (panelDelayedCount) panelDelayedCount.textContent = '0';
        return;
    }

    if (panelDelayedCount) panelDelayedCount.textContent = jobs.length;

    delayedList.innerHTML = jobs.map(job => `
        <div class="panel-item">
            <div class="item-info">
                <i class="fas fa-clock"></i>
                <div>
                    <strong>${escapeHtml(job.id || '')}</strong>
                    <small>${escapeHtml(job.location || '-')}</small>
                </div>
                <span class="item-reason">${escapeHtml(job.reason || '-')}${job.delay ? ` (${escapeHtml(job.delay)})` : ''}</span>
            </div>
            <div class="item-actions">
                <button class="btn-icon" onclick="openReportIssueModal('${escapeJs(job.id || '')}')" title="Report issue">
                    <i class="fas fa-flag"></i>
                </button>
                <button class="btn-icon" onclick="viewJobDetails('${escapeJs(job.id || '')}')" title="View details">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function loadPendingDropoffJobs() {
    displayPendingDropoffJobs(collectionJobsData.pendingDropoffJobs || []);
}

function displayPendingDropoffJobs(jobs) {
    const pendingDropoffList = document.getElementById('pendingDropoffList');
    const pendingDropoffCount = document.getElementById('pendingDropoffCount');
    const itemsInTransit = document.getElementById('itemsInTransit');
    const affectedCollectors = document.getElementById('affectedCollectors');
    const centresAvailable = document.getElementById('centresAvailable');

    if (!pendingDropoffList) return;

    if (!jobs.length) {
        pendingDropoffList.innerHTML = `
            <div class="no-jobs-message">
                <i class="fas fa-check-circle"></i>
                <p>No pending drop-offs</p>
            </div>
        `;
        if (pendingDropoffCount) pendingDropoffCount.textContent = '0';
        if (itemsInTransit) itemsInTransit.textContent = '0';
        if (affectedCollectors) affectedCollectors.textContent = '0';
        if (centresAvailable) centresAvailable.textContent = collectionJobsData.centresAvailable || 0;
        return;
    }

    if (pendingDropoffCount) pendingDropoffCount.textContent = jobs.length;

    let totalItems = 0;
    jobs.forEach(job => {
        const match = String(job.items || '').match(/\d+/);
        totalItems += match ? parseInt(match[0], 10) : 0;
    });

    if (itemsInTransit) itemsInTransit.textContent = totalItems;
    if (affectedCollectors) affectedCollectors.textContent = jobs.length;
    if (centresAvailable) centresAvailable.textContent = collectionJobsData.centresAvailable || 0;

    pendingDropoffList.innerHTML = jobs.map(job => `
        <div class="dropoff-item">
            <div class="dropoff-header">
                <span class="dropoff-id">${escapeHtml(job.id || '')}</span>
                <span class="dropoff-status">Failed Drop-off</span>
            </div>
            <div class="dropoff-details">
                <div class="dropoff-detail">
                    <span class="dropoff-detail-label">Collector</span>
                    <span class="dropoff-detail-value">${escapeHtml(job.collector || '-')}</span>
                </div>
                <div class="dropoff-detail">
                    <span class="dropoff-detail-label">Items</span>
                    <span class="dropoff-detail-value">${escapeHtml(job.items || '-')}</span>
                </div>
                <div class="dropoff-detail">
                    <span class="dropoff-detail-label">Original Centre</span>
                    <span class="dropoff-detail-value">${escapeHtml(job.originalCentre || '-')}</span>
                </div>
                <div class="dropoff-detail">
                    <span class="dropoff-detail-label">Time</span>
                    <span class="dropoff-detail-value">${escapeHtml(job.time || '-')}</span>
                </div>
            </div>
            <div class="dropoff-fail-reason">
                <i class="fas fa-exclamation-circle"></i>
                <span>${escapeHtml(job.failReason || '-')}</span>
            </div>
            <div class="dropoff-actions">
                <button class="btn-reassign-centre" onclick="goToIssuePage('${escapeJs(job.jobID || '')}')">
                    <i class="fas fa-exclamation-circle"></i>
                    Go to Issue
                </button>
                <button class="btn-icon" onclick="viewFailedDropoffDetails('${escapeJs(job.id || '')}')" title="View details">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function getCollectorsData() {
    return Array.isArray(collectionJobsData.activeCollectors)
        ? collectionJobsData.activeCollectors
        : [];
}

function loadActiveCollectors() {
    const collectorList = document.getElementById('activeCollectorList');
    const collectorCount = document.getElementById('activeCollectorCount');
    const collectors = getCollectorsData().filter(c => c.jobStatus === 'Ongoing');

    if (!collectorList) return;
    if (collectorCount) collectorCount.textContent = collectors.length;

    collectorList.innerHTML = collectors.length ? collectors.map(collector => {
        const statusClass = collector.status === 'online' ? 'online' : 'busy';
        const initials = getInitials(collector.name || 'NA');
        const activeClass = selectedCollectorId === collector.id ? 'active' : '';

        return `
            <div class="collector-list-item ${activeClass}" onclick="selectCollector('${escapeJs(collector.id || '')}')">
                <div class="collector-list-info">
                    <div class="collector-avatar">${escapeHtml(initials)}</div>
                    <div class="collector-details">
                        <span class="collector-list-name">${escapeHtml(collector.name || '-')}</span>
                        <span class="collector-list-vehicle">${escapeHtml(collector.vehicle || '-')}</span>
                    </div>
                </div>
                <div class="collector-list-status">
                    <span class="status-badge-collector ${statusClass}"></span>
                    ${collector.jobId ? `<span class="collector-job-id">${escapeHtml(collector.jobId)}</span>` : '<span>Available</span>'}
                </div>
            </div>
        `;
    }).join('') : `
        <div class="no-jobs-message">
            <i class="fas fa-user-check"></i>
            <p>No active collectors</p>
        </div>
    `;
}

function loadQuickStats() {
    const completedToday = document.getElementById('completedToday');
    const avgResponse = document.getElementById('avgResponse');
    const totalDistance = document.getElementById('totalDistance');

    if (completedToday) completedToday.textContent = collectionJobsData.quickStats.completedToday ?? 0;
    if (avgResponse) avgResponse.textContent = collectionJobsData.quickStats.avgResponse ?? '0min';
    if (totalDistance) totalDistance.textContent = collectionJobsData.quickStats.totalDistance ?? 0;
}

function updateCounts() {
    const handoverCount = document.getElementById('panelHandoverCount');
    const delayedCount = document.getElementById('panelDelayedCount');
    const pendingDropoffCount = document.getElementById('pendingDropoffCount');
    const activeCollectorCount = document.getElementById('activeCollectorCount');

    if (handoverCount) handoverCount.textContent = (collectionJobsData.handoverJobs || []).length;
    if (delayedCount) delayedCount.textContent = (collectionJobsData.delayedJobs || []).length;
    if (pendingDropoffCount) pendingDropoffCount.textContent = (collectionJobsData.pendingDropoffJobs || []).length;
    if (activeCollectorCount) {
        activeCollectorCount.textContent = getCollectorsData().filter(c => c.jobStatus === 'Ongoing').length;
    }
}

function initMap() {
    const mapEl = document.getElementById('actualMap');
    const placeholderEl = document.getElementById('mapPlaceholder');

    if (!mapEl || typeof L === 'undefined') return;

    if (map) {
        updateMapMarkers();
        return;
    }

    if (placeholderEl) placeholderEl.style.display = 'none';
    mapEl.style.display = 'block';

    map = L.map('actualMap', { zoomControl: true }).setView([3.1390, 101.6869], 11);
    map.attributionControl.setPrefix('');

    tilesLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);
    routeLayer = L.layerGroup().addTo(map);

    map.on('move zoom', function () {
        positionEtaBubble();
    });
}

function updateMapMarkers() {
    if (!map || !markersLayer) return;

    markersLayer.clearLayers();

    const collectors = getCollectorsData().filter(c => c.jobStatus === 'Ongoing');

    if (!collectors.length) return;

    collectors.forEach((collector, index) => {
        const fallbackLat = 3.10 + (index * 0.02);
        const fallbackLng = 101.60 + (index * 0.02);

        const lat = fallbackLat;
        const lng = fallbackLng;

        const marker = L.marker([lat, lng]).addTo(markersLayer);
        marker.bindPopup(`
            <strong>${escapeHtml(collector.name || '-')}</strong><br>
            ${escapeHtml(collector.vehicle || '-')}<br>
            ${escapeHtml(collector.jobId || 'No active job')}
        `);
    });

    if (!selectedCollectorId) {
        const group = new L.featureGroup(markersLayer.getLayers());
        if (group.getLayers().length) {
            map.fitBounds(group.getBounds().pad(0.2));
        }
    }
}

function notifyCollector(jobId) {
    alert(`Notification sent for job ${jobId}`);
}

function contactCollector(jobId) {
    alert(`Contacting collector for job ${jobId}`);
}

function selectCollector(collectorId) {
    selectedCollectorId = collectorId;
    loadActiveCollectors();
    showCollectorRouteOnMap(collectorId);
}

function showCollectorRouteOnMap(collectorId) {
    if (!map) return;

    selectedCollectorId = collectorId;

    if (routeLayer) {
        routeLayer.clearLayers();
    }

    const collector = getCollectorsData().find(c => c.id === collectorId);
    if (!collector) return;

    const routeInfoBox = document.getElementById('routeInfoBox');
    const routeCollectorName = document.getElementById('routeCollectorName');
    const routeCurrentLocation = document.getElementById('routeCurrentLocation');
    const routeEta = document.getElementById('routeEta');

    if (routeCollectorName) routeCollectorName.textContent = collector.name || '-';
    if (routeCurrentLocation) routeCurrentLocation.textContent = `Current location: ${collector.currentRoad || '-'}`;
    if (routeEta) routeEta.textContent = `ETA to collection centre: En route`;
    if (routeInfoBox) routeInfoBox.style.display = 'block';

    const fallbackLat = 3.12;
    const fallbackLng = 101.65;

    const marker = L.marker([fallbackLat, fallbackLng]).addTo(routeLayer);
    marker.bindPopup(`<strong>${escapeHtml(collector.name || '-')}</strong>`).openPopup();

    map.setView([fallbackLat, fallbackLng], 13);
    showEtaBubble(fallbackLat, fallbackLng, 'ETA: En route');
}

function centerMapOnAll() {
    selectedCollectorId = null;

    if (routeLayer) {
        routeLayer.clearLayers();
    }

    const routeInfoBox = document.getElementById('routeInfoBox');
    if (routeInfoBox) routeInfoBox.style.display = 'none';

    hideEtaBubble();
    loadActiveCollectors();
    updateMapMarkers();
}

function zoomToFit() {
    if (selectedCollectorId) {
        showCollectorRouteOnMap(selectedCollectorId);
    } else {
        centerMapOnAll();
    }
}

function toggleMapLayers() {
    if (!map || !tilesLayer) return;

    if (map.hasLayer(tilesLayer)) {
        map.removeLayer(tilesLayer);
    } else {
        map.addLayer(tilesLayer);
    }

    mapLayersVisible = !mapLayersVisible;
}

function showEtaBubble(lat, lng, text) {
    const etaBubble = document.getElementById('etaBubble');
    const etaBubbleText = document.getElementById('etaBubbleText');

    if (!etaBubble || !map) return;

    if (etaBubbleText) etaBubbleText.textContent = text;
    etaBubble.dataset.lat = lat;
    etaBubble.dataset.lng = lng;
    etaBubble.style.display = 'flex';

    positionEtaBubble();
}

function positionEtaBubble() {
    const etaBubble = document.getElementById('etaBubble');
    const mapContainer = document.getElementById('mapContainer');

    if (!etaBubble || !mapContainer || etaBubble.style.display === 'none') return;
    if (!etaBubble.dataset.lat || !etaBubble.dataset.lng || !map) return;

    const lat = parseFloat(etaBubble.dataset.lat);
    const lng = parseFloat(etaBubble.dataset.lng);
    const point = map.latLngToContainerPoint([lat, lng]);

    etaBubble.style.left = `${point.x}px`;
    etaBubble.style.top = `${point.y - 12}px`;
}

function hideEtaBubble() {
    const etaBubble = document.getElementById('etaBubble');
    if (etaBubble) etaBubble.style.display = 'none';
}

function getDelayedJobById(jobId) {
    return (collectionJobsData.delayedLookup || {})[jobId] || null;
}

function openReportIssueModal(jobId) {
    const job = getDelayedJobById(jobId);
    const modal = document.getElementById('reportIssueModal');
    const form = document.getElementById('reportIssueForm');
    const issueJobId = document.getElementById('issueJobId');
    const issueRequestId = document.getElementById('issueRequestId');
    const otherIssueGroup = document.getElementById('otherIssueGroup');
    const otherIssueText = document.getElementById('otherIssueText');

    if (!job || !modal || !form) return;

    form.reset();

    document.querySelectorAll('.priority-option').forEach(opt => {
        opt.classList.remove('selected');
    });

    if (otherIssueGroup) otherIssueGroup.style.display = 'none';
    if (otherIssueText) {
        otherIssueText.removeAttribute('required');
        otherIssueText.value = '';
    }

    if (issueJobId) issueJobId.value = job.id || '';
    if (issueRequestId) {
        issueRequestId.value = job.requestID ? `REQ${String(job.requestID).padStart(3, '0')}` : '';
    }

    modal.classList.add('active');
}

function closeReportIssueModal() {
    const modal = document.getElementById('reportIssueModal');
    const form = document.getElementById('reportIssueForm');
    const otherIssueGroup = document.getElementById('otherIssueGroup');
    const otherIssueText = document.getElementById('otherIssueText');

    if (modal) modal.classList.remove('active');
    if (form) form.reset();

    document.querySelectorAll('.priority-option').forEach(opt => {
        opt.classList.remove('selected');
    });

    if (otherIssueGroup) otherIssueGroup.style.display = 'none';
    if (otherIssueText) {
        otherIssueText.removeAttribute('required');
        otherIssueText.value = '';
    }
}

function assignHandover(jobId) {
    const job = (collectionJobsData.handoverLookup || {})[jobId];
    if (!job) return;

    document.getElementById('handoverJobId').value = job.id || '';
    document.getElementById('handoverCurrentCollector').value = job.collector || '';
    document.getElementById('handoverReason').value = job.reason || '';
    document.getElementById('handoverNewCollector').value = '';
    document.getElementById('handoverVehicle').value = '';
    document.getElementById('handoverPriority').value = 'High';
    document.getElementById('handoverAdminNotes').value = '';

    document.getElementById('assignHandoverModal')?.classList.add('show');
}

function closeAssignHandoverModal() {
    document.getElementById('assignHandoverModal')?.classList.remove('show');
}

function viewJobDetails(jobId) {
    const job = (collectionJobsData.handoverLookup || {})[jobId] || (collectionJobsData.delayedLookup || {})[jobId];
    if (!job) return;

    document.getElementById('detailsJobId').textContent = job.id || '-';
    document.getElementById('detailsStatus').textContent = job.status || '-';
    document.getElementById('detailsTime').textContent = job.time || '-';
    document.getElementById('detailsLocation').textContent = job.location || '-';
    document.getElementById('detailsCollector').textContent = job.collector || '-';
    document.getElementById('detailsVehicle').textContent = job.vehicle || '-';
    document.getElementById('detailsReason').textContent = job.reason || '-';
    document.getElementById('detailsAdminNotes').value = '';

    document.getElementById('jobDetailsModal')?.classList.add('show');
}

function closeJobDetailsModal() {
    document.getElementById('jobDetailsModal')?.classList.remove('show');
}

function reassignJob(jobId) {
    const job = (collectionJobsData.delayedLookup || {})[jobId];
    if (!job) return;

    document.getElementById('reassignJobId').value = job.id || '';
    document.getElementById('reassignCurrentCollector').value = job.collector || '';
    document.getElementById('reassignDelayReason').value = `${job.reason || '-'}${job.delay ? ` (${job.delay})` : ''}`;
    document.getElementById('reassignNewCollector').value = '';
    document.getElementById('reassignNewVehicle').value = '';
    document.getElementById('reassignEta').value = '';
    document.getElementById('reassignRemarks').value = '';

    document.getElementById('reassignJobModal')?.classList.add('show');
}

function closeReassignJobModal() {
    document.getElementById('reassignJobModal')?.classList.remove('show');
}

function reassignCentre(jobId) {
    const job = (collectionJobsData.pendingDropoffLookup || {})[jobId];
    if (!job) return;

    document.getElementById('reassignCentreJobId').value = job.id || '';
    document.getElementById('reassignCentreCollector').value = job.collector || '';
    document.getElementById('reassignCentreOriginal').value = job.originalCentre || '';
    document.getElementById('reassignCentreReason').value = job.failReason || '';
    document.getElementById('reassignCentreNew').value = '';
    document.getElementById('reassignCentrePriority').value = 'High';
    document.getElementById('reassignCentreInstructions').value = '';
    document.getElementById('reassignCentreRemarks').value = '';

    document.getElementById('reassignCentreModal')?.classList.add('show');
}

function closeReassignCentreModal() {
    document.getElementById('reassignCentreModal')?.classList.remove('show');
}

function viewFailedDropoffDetails(jobId) {
    const job = (collectionJobsData.pendingDropoffLookup || {})[jobId];
    if (!job) return;

    document.getElementById('detailsJobId').textContent = job.id || '-';
    document.getElementById('detailsStatus').textContent = 'Failed Drop-off';
    document.getElementById('detailsTime').textContent = job.time || '-';
    document.getElementById('detailsLocation').textContent = job.originalCentre || '-';
    document.getElementById('detailsCollector').textContent = job.collector || '-';
    document.getElementById('detailsVehicle').textContent = '-';
    document.getElementById('detailsReason').textContent = job.failReason || '-';
    document.getElementById('detailsAdminNotes').value = '';

    document.getElementById('jobDetailsModal')?.classList.add('show');
}

function goToIssuePage(jobId) {
    if (!jobId) return;
    window.location.href = `aIssue.php?jobID=${encodeURIComponent(jobId)}`;
}

function getInitials(name) {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return 'NA';
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeJs(value) {
    return String(value ?? '')
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '\\r');
}

window.assignHandover = assignHandover;
window.closeAssignHandoverModal = closeAssignHandoverModal;
window.viewJobDetails = viewJobDetails;
window.closeJobDetailsModal = closeJobDetailsModal;
window.notifyCollector = notifyCollector;
window.reassignJob = reassignJob;
window.closeReassignJobModal = closeReassignJobModal;
window.reassignCentre = reassignCentre;
window.closeReassignCentreModal = closeReassignCentreModal;
window.contactCollector = contactCollector;
window.selectCollector = selectCollector;
window.centerMapOnAll = centerMapOnAll;
window.toggleMapLayers = toggleMapLayers;
window.zoomToFit = zoomToFit;
window.goToIssuePage = goToIssuePage;
window.openReportIssueModal = openReportIssueModal;
window.closeReportIssueModal = closeReportIssueModal;
window.viewFailedDropoffDetails = viewFailedDropoffDetails;