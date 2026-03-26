let map = null;
let markersLayer = null;
let routeLayer = null;
let selectedCollectorId = null;
let tilesLayer = null;

const collectionJobsData = window.DB_COLLECTION_JOBS || {
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
                <button class="btn-icon" onclick="assignHandover('${escapeJs(job.id || '')}')" title="Assign handover">
                    <i class="fas fa-user-plus"></i>
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
                <button class="btn-icon" onclick="notifyCollector('${escapeJs(job.id || '')}')" title="Notify collector">
                    <i class="fas fa-bell"></i>
                </button>
                <button class="btn-icon" onclick="reassignJob('${escapeJs(job.id || '')}')" title="Reassign">
                    <i class="fas fa-route"></i>
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
        if (centresAvailable) centresAvailable.textContent = '0';
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
                    <span class="dropoff-detail-value">${escapeHtml(job.items || '0 items')}</span>
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
                <button class="btn-reassign-centre" onclick="reassignCentre('${escapeJs(job.id || '')}')">
                    <i class="fas fa-map-marker-alt"></i>
                    Reassign Centre
                </button>
                <button class="btn-icon" onclick="contactCollector('${escapeJs(job.id || '')}')" title="Contact collector">
                    <i class="fas fa-phone"></i>
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
    const collectors = getCollectorsData().filter(c => c.status !== 'offline');

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
    const handoverCount = (collectionJobsData.handoverJobs || []).length;
    const delayedCount = (collectionJobsData.delayedJobs || []).length;
    const pendingCount = (collectionJobsData.pendingDropoffJobs || []).length;
    const activeCount = getCollectorsData().filter(c => c.status !== 'offline').length;

    const panelHandoverCount = document.getElementById('panelHandoverCount');
    const panelDelayedCount = document.getElementById('panelDelayedCount');
    const pendingDropoffCount = document.getElementById('pendingDropoffCount');
    const activeCollectorCount = document.getElementById('activeCollectorCount');

    if (panelHandoverCount) panelHandoverCount.textContent = handoverCount;
    if (panelDelayedCount) panelDelayedCount.textContent = delayedCount;
    if (pendingDropoffCount) pendingDropoffCount.textContent = pendingCount;
    if (activeCollectorCount) activeCollectorCount.textContent = activeCount;
}

function initMap() {
    const mapElement = document.getElementById('actualMap');
    const mapPlaceholder = document.getElementById('mapPlaceholder');

    if (!mapElement || typeof L === 'undefined') return;

    map = L.map('actualMap').setView([3.1390, 101.6869], 10);

    tilesLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);
    routeLayer = L.layerGroup().addTo(map);

    if (mapPlaceholder) mapPlaceholder.style.display = 'none';
    mapElement.style.display = 'block';

    setTimeout(() => {
        map.invalidateSize();
    }, 300);
}

async function updateMapMarkers() {
    if (!map || !markersLayer || !routeLayer) return;

    markersLayer.clearLayers();
    routeLayer.clearLayers();

    const collectors = getCollectorsData().filter(c => c.jobId && c.pickupAddress && c.centreAddress);

    if (!collectors.length) {
        hideRouteInfo();
        return;
    }

    const allBounds = [];

    for (const collector of collectors) {
        const pickupCoords = await geocodeAddress(collector.pickupAddress);
        const centreCoords = await geocodeAddress(collector.centreAddress);

        if (!pickupCoords || !centreCoords) {
            continue;
        }

        collector.pickupCoords = pickupCoords;
        collector.centreCoords = centreCoords;

        const pickupMarker = L.marker([pickupCoords.lat, pickupCoords.lng]).bindPopup(`
            <strong>${escapeHtml(collector.jobId || '')}</strong><br>
            Pickup<br>
            ${escapeHtml(collector.pickupLabel || collector.pickupAddress || '-')}
        `);

        const centreMarker = L.marker([centreCoords.lat, centreCoords.lng]).bindPopup(`
            <strong>${escapeHtml(collector.jobId || '')}</strong><br>
            Centre: ${escapeHtml(collector.centreName || '-') }<br>
            ${escapeHtml(collector.centreLabel || collector.centreAddress || '-')}
        `);

        pickupMarker.addTo(markersLayer);
        centreMarker.addTo(markersLayer);

        allBounds.push([pickupCoords.lat, pickupCoords.lng]);
        allBounds.push([centreCoords.lat, centreCoords.lng]);

        const isSelected = selectedCollectorId === collector.id;

        L.polyline(
            [
                [pickupCoords.lat, pickupCoords.lng],
                [centreCoords.lat, centreCoords.lng]
            ],
            {
                weight: isSelected ? 6 : 4,
                opacity: isSelected ? 0.95 : 0.6
            }
        ).addTo(routeLayer);

        if (isSelected) {
            updateRouteInfo(collector, pickupCoords, centreCoords);
        }
    }

    if (!selectedCollectorId && collectors.length) {
        selectedCollectorId = collectors[0].id;
        const selected = collectors[0];
        if (selected.pickupCoords && selected.centreCoords) {
            updateRouteInfo(selected, selected.pickupCoords, selected.centreCoords);
        }
        loadActiveCollectors();
    }

    if (allBounds.length) {
        map.fitBounds(allBounds, { padding: [40, 40] });
    }
}

function selectCollector(collectorId) {
    selectedCollectorId = collectorId;
    loadActiveCollectors();
    updateMapMarkers();
}

function updateRouteInfo(collector, pickupCoords, centreCoords) {
    const routeInfoBox = document.getElementById('routeInfoBox');
    const routeCollectorName = document.getElementById('routeCollectorName');
    const routeCurrentLocation = document.getElementById('routeCurrentLocation');
    const routeEta = document.getElementById('routeEta');
    const etaBubble = document.getElementById('etaBubble');
    const etaBubbleText = document.getElementById('etaBubbleText');

    const km = calculateDistanceKm(
        pickupCoords.lat,
        pickupCoords.lng,
        centreCoords.lat,
        centreCoords.lng
    );

    const estimatedMinutes = Math.max(5, Math.round(km * 4));

    if (routeCollectorName) {
        routeCollectorName.textContent = collector.name || 'Collector';
    }

    if (routeCurrentLocation) {
        routeCurrentLocation.textContent = `Pickup: ${collector.pickupLabel || '-'}`;
    }

    if (routeEta) {
        routeEta.textContent = `ETA to collection centre: ${estimatedMinutes} min`;
    }

    if (etaBubbleText) {
        etaBubbleText.textContent = `ETA: ${estimatedMinutes} min`;
    }

    if (routeInfoBox) routeInfoBox.style.display = 'block';
    if (etaBubble) etaBubble.style.display = 'block';
}

function hideRouteInfo() {
    const routeInfoBox = document.getElementById('routeInfoBox');
    const etaBubble = document.getElementById('etaBubble');

    if (routeInfoBox) routeInfoBox.style.display = 'none';
    if (etaBubble) etaBubble.style.display = 'none';
}

function centerMapOnAll() {
    updateMapMarkers();
}

function toggleMapLayers() {
    if (!map || !tilesLayer) return;

    if (mapLayersVisible) {
        map.removeLayer(tilesLayer);
    } else {
        map.addLayer(tilesLayer);
    }

    mapLayersVisible = !mapLayersVisible;
}

function zoomToFit() {
    updateMapMarkers();
}

async function geocodeAddress(address) {
    if (!address) return null;

    const key = address.trim().toLowerCase();
    if (geocodeCache[key]) {
        return geocodeCache[key];
    }

    try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(address)}`;
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            return null;
        }

        const data = await response.json();

        if (!Array.isArray(data) || !data.length) {
            return null;
        }

        const coords = {
            lat: parseFloat(data[0].lat),
            lng: parseFloat(data[0].lon)
        };

        geocodeCache[key] = coords;
        return coords;
    } catch (error) {
        console.error('Geocoding failed for address:', address, error);
        return null;
    }
}

function calculateDistanceKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = toRadians(lat2 - lat1);
    const dLon = toRadians(lon2 - lon1);

    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function toRadians(value) {
    return value * (Math.PI / 180);
}

/* -----------------------------
   Modal actions
------------------------------ */
function assignHandover(jobId) {
    const job = (collectionJobsData.handoverLookup || {})[jobId];
    if (!job) return;

    setInputValue('handoverJobId', job.id || '');
    setInputValue('handoverCurrentCollector', job.collector || '');
    setInputValue('handoverReason', job.reason || '');
    setInputValue('handoverNewCollector', '');
    setInputValue('handoverVehicle', '');
    setInputValue('handoverPriority', 'High');
    setTextareaValue('handoverAdminNotes', '');

    showModal('assignHandoverModal');
}

function closeAssignHandoverModal() {
    hideModal('assignHandoverModal');
}

function viewJobDetails(jobId) {
    const job = (collectionJobsData.handoverLookup || {})[jobId] || (collectionJobsData.delayedLookup || {})[jobId];
    if (!job) return;

    setText('detailsJobId', job.id || '-');
    setText('detailsStatus', job.status || '-');
    setText('detailsTime', job.time || '-');
    setText('detailsLocation', job.location || '-');
    setText('detailsCollector', job.collector || '-');
    setText('detailsVehicle', job.vehicle || '-');
    setText('detailsReason', job.reason || '-');
    setTextareaValue('detailsAdminNotes', '');

    showModal('jobDetailsModal');
}

function closeJobDetailsModal() {
    hideModal('jobDetailsModal');
}

function reassignJob(jobId) {
    const job = (collectionJobsData.delayedLookup || {})[jobId];
    if (!job) return;

    setInputValue('reassignJobId', job.id || '');
    setInputValue('reassignCurrentCollector', job.collector || '');
    setInputValue('reassignDelayReason', `${job.reason || ''}${job.delay ? ' (' + job.delay + ')' : ''}`);
    setInputValue('reassignNewCollector', '');
    setInputValue('reassignNewVehicle', '');
    setInputValue('reassignEta', '');
    setTextareaValue('reassignRemarks', '');

    showModal('reassignJobModal');
}

function closeReassignJobModal() {
    hideModal('reassignJobModal');
}

function reassignCentre(jobId) {
    const job = (collectionJobsData.pendingDropoffLookup || {})[jobId];
    if (!job) return;

    setInputValue('reassignCentreJobId', job.id || '');
    setInputValue('reassignCentreCollector', job.collector || '');
    setInputValue('reassignCentreOriginal', job.originalCentre || '');
    setInputValue('reassignCentreReason', job.failReason || '');
    setInputValue('reassignCentreNew', '');
    setInputValue('reassignCentrePriority', 'High');
    setTextareaValue('reassignCentreInstructions', '');
    setTextareaValue('reassignCentreRemarks', '');

    showModal('reassignCentreModal');
}

function closeReassignCentreModal() {
    hideModal('reassignCentreModal');
}

/* -----------------------------
   Temp button actions
------------------------------ */
function notifyCollector(jobId) {
    alert(`Notify collector for ${jobId}`);
}

function contactCollector(jobId) {
    alert(`Contact collector for ${jobId}`);
}

/* -----------------------------
   Form submits
------------------------------ */
document.addEventListener('submit', function (e) {
    if (e.target && e.target.id === 'assignHandoverForm') {
        e.preventDefault();
        alert('Handover assigned successfully');
        closeAssignHandoverModal();
    }

    if (e.target && e.target.id === 'reassignJobForm') {
        e.preventDefault();
        alert('Job reassigned successfully');
        closeReassignJobModal();
    }

    if (e.target && e.target.id === 'reassignCentreForm') {
        e.preventDefault();
        alert('Collection centre reassigned successfully');
        closeReassignCentreModal();
    }
});

/* -----------------------------
   Helpers
------------------------------ */
function showModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('show');
}

function hideModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('show');
}

function setInputValue(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value;
}

function setTextareaValue(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value;
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

function getInitials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .map(part => part[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function escapeJs(value) {
    return String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}