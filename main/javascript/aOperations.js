let mockRequests = [];
let currentPage = 1;
let itemsPerPage = 5;
let totalPages = 1;

let mockCollectors = [
    { id: 'collector1', name: 'John Smith', available: true, vehicle: 'Truck A' },
    { id: 'collector2', name: 'Sarah Johnson', available: true, vehicle: 'Van B' },
    { id: 'collector3', name: 'Mike Chen', available: false, vehicle: 'Truck C' },
    { id: 'collector4', name: 'Lisa Wong', available: true, vehicle: 'Van D' }
];

let mockVehicles = [
    { id: 'vehicle1', model: 'Truck A - AV-1234', status: 'Available', capacity: '1000 kg' },
    { id: 'vehicle2', model: 'Van B - AV-5678', status: 'Maintenance', capacity: '500 kg' },
    { id: 'vehicle3', model: 'Truck C - AV-9012', status: 'Available', capacity: '1200 kg' },
    { id: 'vehicle4', model: 'Van D - AV-3456', status: 'Available', capacity: '600 kg' }
];

let mockCentres = [
    { id: 'C01', name: 'APU E-waste Hub', capacity: 85, address: 'APU Campus' },
    { id: 'C02', name: 'Sunway Recycling Centre', capacity: 42, address: 'Sunway' },
    { id: 'C03', name: 'Shah Alam Collection Point', capacity: 68, address: 'Shah Alam' },
    { id: 'C04', name: 'KL Central Collection', capacity: 95, address: 'KL Central' }
];

// ==== Helper Functions ====
function determineEwasteType(items) {
    const itemString = items.join(' ').toLowerCase();
    
    if (itemString.includes('battery') || itemString.includes('power bank')) {
        return 'batteries';
    } else if (itemString.includes('tv') || itemString.includes('refrigerator') || itemString.includes('fridge') || itemString.includes('washing machine')) {
        return 'appliances';
    } else {
        return 'electronics';
    }
}


function initCustomDropdowns() {
    setupCustomDropdown('collectorDropdown', 'collectorMenu', 'selectedCollectorText', mockCollectors, 'collector');
    setupCustomDropdown('vehicleDropdown', 'vehicleMenu', 'selectedVehicleText', mockVehicles, 'vehicle');
    setupCustomDropdown('centreDropdown', 'centreMenu', 'selectedCentreText', mockCentres, 'centre');
}

function setupCustomDropdown(dropdownId, menuId, textId, items, type) {
    const dropdown = document.getElementById(dropdownId);
    const menu = document.getElementById(menuId);
    const selectedText = document.getElementById(textId);
    
    if (!dropdown || !menu || !selectedText) return;
    
    const selectBtn = dropdown.querySelector('.custom-dropdown-select');
    
    menu.innerHTML = '';
    items.forEach(item => {
        const menuItem = document.createElement('div');
        menuItem.className = 'custom-dropdown-item';
        menuItem.dataset.value = item.id;
        
        let isAvailable = true;
        let dotColor = 'green';
        
        if (type === 'collector') {
            isAvailable = item.available;
            dotColor = item.available ? 'green' : 'red';
            menuItem.innerHTML = `<span class="status-dot ${dotColor}"></span> ${item.name}`;
        } else if (type === 'vehicle') {
            isAvailable = item.status === 'Available';
            dotColor = isAvailable ? 'green' : 'red';
            menuItem.innerHTML = `<span class="status-dot ${dotColor}"></span> ${item.model}`;
        } else if (type === 'centre') {
            menuItem.textContent = `${item.name}`;
        }
        
        if (!isAvailable) {
            menuItem.style.opacity = '0.5';
            menuItem.style.pointerEvents = 'none';
            menuItem.style.cursor = 'not-allowed';
        }
        
        menuItem.addEventListener('click', function(e) {
            e.stopPropagation();
            
            if (!isAvailable) return;
            
            if (type === 'collector') {
                selectedText.innerHTML = `<span class="status-dot ${dotColor}"></span> ${item.name}`;
            } else if (type === 'vehicle') {
                selectedText.innerHTML = `<span class="status-dot ${dotColor}"></span> ${item.model}`;
                
                const hint = document.getElementById('vehicleHint');
                if (hint) hint.textContent = item.capacity;
            } else if (type === 'centre') {
                selectedText.textContent = `${item.name}`;
                
                updateCapacityCircle(item.capacity);
            }
            
            dropdown.dataset.selectedValue = item.id;
            
            menu.classList.remove('show');
            
            checkRequiredFields();
        });
        
        menu.appendChild(menuItem);
    });
    
    selectBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        
        document.querySelectorAll('.custom-dropdown-menu').forEach(m => {
            if (m !== menu) m.classList.remove('show');
        });
        
        menu.classList.toggle('show');
    });
    
    document.addEventListener('click', function() {
        menu.classList.remove('show');
    });
    
    menu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
}

function checkRequiredFields() {
    const collectorDropdown = document.getElementById('collectorDropdown');
    const vehicleDropdown = document.getElementById('vehicleDropdown');
    const centreDropdown = document.getElementById('centreDropdown');
    const datetime = document.getElementById('scheduledDateTime')?.value;
    
    const collector = collectorDropdown?.dataset.selectedValue;
    const vehicle = vehicleDropdown?.dataset.selectedValue;
    const centre = centreDropdown?.dataset.selectedValue;
    
    const allFieldsFilled = collector && vehicle && centre && datetime;
    document.getElementById('confirmAssignmentBtn').disabled = !allFieldsFilled;
}

// ===== LOAD APPROVED REQUESTS =====
function loadApprovedRequest() {
    const approvedRequestsJson = sessionStorage.getItem('approvedRequests');
    if (approvedRequestsJson) {
        const approvedRequests = JSON.parse(approvedRequestsJson);
        console.log('Found approved requests:', approvedRequests);
        
        approvedRequests.forEach(approvedRequest => {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const formattedDate = `${year}-${month}-${day}T${hours}:${minutes}`;
            
            const newRequest = {
                id: approvedRequest.id,
                provider: approvedRequest.provider,
                items: approvedRequest.items || ['Items'],
                address: approvedRequest.address || 'Address not provided',
                postcode: approvedRequest.address ? approvedRequest.address.split(',').pop().trim() : '50000',
                preferredDate: formattedDate,
                weight: approvedRequest.weight ? `${approvedRequest.weight} kg` : '0 kg',
                status: 'approved',
                type: determineEwasteType(approvedRequest.items || [])
            };
            
            const exists = mockRequests.some(req => req.id === newRequest.id);
            if (!exists) {
                mockRequests.push(newRequest);
            }
        });
        
        sessionStorage.removeItem('approvedRequests');
    }
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
    const formattedDate = preferredDate.toLocaleDateString('en-MY', { 
        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' 
    });
    
    card.innerHTML = `
    <div class="card-header">
        <span class="request-id">#${request.id}</span>
    </div>
    <div class="card-body">
        <div class="provider-name">${request.provider}</div>
        <div class="e-waste-items">
            ${request.items.map(item => `<span class="item-chip">${item}</span>`).join('')}
        </div>
        <div class="card-footer">
            <span>${request.weight}</span>
            <span class="preferred-date">${formattedDate}</span>
        </div>
    </div>
`;
    
    card.addEventListener('click', () => {
        document.querySelectorAll('.request-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        
        resetAssignmentForm();
        
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
    const dateStr = preferredDate.toLocaleString('en-MY', { 
        weekday: 'short', day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
    
    summaryDiv.innerHTML = `
        <div class="summary-row">
            <span class="summary-label">Provider:</span>
            <span class="summary-value">${request.provider}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Items:</span>
            <span class="summary-value">${request.items.join(', ')} (${request.weight})</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Preferred:</span>
            <span class="summary-value">${dateStr}</span>
        </div>
        <div class="summary-address">
            📍 ${request.address}
        </div>
    `;
    
    const dateNote = document.getElementById('dateConstraintNote');
    if (dateNote) {
        dateNote.innerHTML = `<span>ⓘ</span> Preferred: ${dateStr}`;
    }
    
    document.getElementById('confirmAssignmentBtn').disabled = true;
}

function loadCollectors() {
    console.log('Collectors loaded');
}

function loadVehicles() {
    console.log('Vehicles loaded');
}

function loadCentres() {
    console.log('Centres loaded');
    updateCapacityCircle(0);
}

function updateCapacityCircle(capacity) {
    const circle = document.getElementById('capacityCircle');
    const percentageSpan = document.getElementById('capacityPercentage');
    
    if (!circle || !percentageSpan) return;
    
    const degrees = (capacity / 100) * 360;
    
    circle.style.background = `conic-gradient(var(--MainBlue) ${degrees}deg, var(--LightBlue) 0deg)`;
    
    percentageSpan.textContent = `${capacity}%`;
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

function loadRecentAssignments() {
    const timeline = document.getElementById('recentTimeline');
    if (!timeline) return;
    
    const assignments = [
        { time: '10:30', event: '<strong>#REQ-0035</strong> assigned to Kevin Tan', ago: '2h ago' },
        { time: '09:15', event: '<strong>#REQ-0032</strong> assigned to Muthu Krishnan', ago: '3h ago' },
        { time: 'Yesterday', event: '<strong>#REQ-0028</strong> assigned to Jason Wong', ago: '1d ago' }
    ];
    
    timeline.innerHTML = assignments.map(a => `
        <div class="timeline-item">
            <span class="timeline-time">${a.time}</span>
            <span class="timeline-event">${a.event}</span>
            <span style="color: var(--Gray); margin-left: auto;">${a.ago}</span>
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

// ===== EVENT LISTENERS =====
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
    
    document.getElementById('resetAssignmentBtn')?.addEventListener('click', resetAssignmentForm);
    
    document.getElementById('viewCollectorAvailability')?.addEventListener('click', () => {
        alert('Collector availability calendar would open here');
    });
    
    document.getElementById('viewVehicleStatus')?.addEventListener('click', () => {
        alert('Vehicle maintenance schedule would open here');
    });
    
    document.getElementById('assignMultipleBtn')?.addEventListener('click', () => {
        alert('Bulk assignment wizard would open');
    });
    
    document.getElementById('rescheduleSelectedBtn')?.addEventListener('click', () => {
        alert('Bulk reschedule interface would open');
    });
    
    document.getElementById('exportScheduleBtn')?.addEventListener('click', () => {
        alert('Exporting schedule as CSV...');
    });
}

function filterRequests() {
    const filter = document.getElementById('requestFilter')?.value;
    const search = document.getElementById('requestSearch')?.value.toLowerCase();
    
    console.log('Filtering by:', filter, 'Search:', search);
    
    currentPage = 1;
    
    let filteredRequests = mockRequests;
    
    if (filter && filter !== 'all') {
        filteredRequests = mockRequests.filter(req => req.type === filter);
    }
    
    if (search) {
        filteredRequests = filteredRequests.filter(req => 
            req.provider.toLowerCase().includes(search) ||
            req.id.toLowerCase().includes(search) ||
            req.items.some(item => item.toLowerCase().includes(search))
        );
    }
    
    const container = document.getElementById('requestCardsContainer');
    if (!container) return;
    
    if (filteredRequests.length === 0) {
        container.innerHTML = `
            <div class="empty-requests">
                <i class="fas fa-filter"></i>
                <h3>No approved requests</h3>
            </div>
        `;
        updatePagination();
        updateSelectedRequestId(null);
        return;
    }
    
    const totalFilteredItems = filteredRequests.length;
    totalPages = Math.max(1, Math.ceil(totalFilteredItems / itemsPerPage));
    
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const paginatedRequests = filteredRequests.slice(start, end);
    
    container.innerHTML = '';
    paginatedRequests.forEach((req, index) => {
        const card = createRequestCard(req, start + index);
        container.appendChild(card);
    });
    
    if (paginatedRequests.length > 0) {
        document.querySelectorAll('.request-card')[0]?.classList.add('selected');
        updateAssignmentPanel(paginatedRequests[0]);
        updateSelectedRequestId(paginatedRequests[0]);
    } else {
        updateSelectedRequestId(null);
    }
    
    updatePagination();
}

function confirmAssignment() {
    const collectorDropdown = document.getElementById('collectorDropdown');
    const vehicleDropdown = document.getElementById('vehicleDropdown');
    const centreDropdown = document.getElementById('centreDropdown');
    const datetime = document.getElementById('scheduledDateTime')?.value;
    const notes = document.getElementById('assignmentNotes')?.value;
    
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
    
    const collectorText = document.getElementById('selectedCollectorText')?.textContent || 'Collector';
    const vehicleText = document.getElementById('selectedVehicleText')?.textContent || 'Vehicle';
    
    alert(`✓ Assignment confirmed!\n\nCollector: ${collectorText}\nVehicle: ${vehicleText}\nScheduled: ${new Date(datetime).toLocaleString()}`);
    
    const selectedCard = document.querySelector('.request-card.selected');
    if (selectedCard) {
        const requestId = selectedCard.dataset.id;
        const assignedRequest = mockRequests.find(r => r.id === requestId);
        
        addToTimeline(datetime, assignedRequest, collectorText, vehicleText);
        
        const index = mockRequests.findIndex(r => r.id === requestId);
        if (index !== -1) {
            mockRequests.splice(index, 1);
        }
    }
    
    const summaryDiv = document.getElementById('selectedRequestSummary');
    if (summaryDiv) {
        summaryDiv.innerHTML = ''; 
    }

    resetAssignmentForm();
    loadPendingRequests();
}

function resetAssignmentForm() {
    updateSelectedRequestId(null);
    
    const summaryDiv = document.getElementById('selectedRequestSummary');
    if (summaryDiv) {
        summaryDiv.innerHTML = '';
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
    
    document.getElementById('scheduledDateTime').value = '';
    document.getElementById('assignmentNotes').value = '';
    
    const vehicleHint = document.getElementById('vehicleHint');
    if (vehicleHint) vehicleHint.textContent = '';
    
    updateCapacityCircle(0);
    
    document.getElementById('confirmAssignmentBtn').disabled = true;
}

function addToTimeline(datetime, request, collectorName, vehicleName) {
    const timeline = document.getElementById('recentTimeline');
    const now = new Date();
    const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    const cleanCollector = collectorName.replace(/[🟢🔴]/g, '').trim();
    const cleanVehicle = vehicleName.replace(/[🟢🔴]/g, '').trim();
    
    const newItem = document.createElement('div');
    newItem.className = 'timeline-item';
    newItem.innerHTML = `
        <span class="timeline-time">${timeStr}</span>
        <span class="timeline-event"><strong>#${request?.id || 'REQ'}</strong> assigned to ${cleanCollector} (${cleanVehicle})</span>
        <span style="color: var(--Gray); margin-left: auto;">Just now</span>
    `;
    
    timeline.insertBefore(newItem, timeline.firstChild);
    
    if (timeline.children.length > 5) {
        timeline.removeChild(timeline.lastChild);
    }
}

function updatePagination() {
    const totalItems = mockRequests.length;
    const itemsPerPage = 5;
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
    loadPendingRequests();
    updatePagination();
}

// ===== DOM CONTENT LOADED =====
document.addEventListener('DOMContentLoaded', function() {
    currentPage = 1;
    itemsPerPage = 5;
    totalPages = 1;
    
    loadApprovedRequest();
    
    initCustomDropdowns();
    
    loadPendingRequests();
    loadCollectors();
    loadVehicles();
    loadCentres();
    loadRecentAssignments();
    setupEventListeners();
    setMinDateTime();
});