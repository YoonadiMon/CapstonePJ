document.addEventListener('DOMContentLoaded', function() {

    loadPendingRequests();
    loadCollectors();
    loadVehicles();
    loadCentres();
    loadRecentAssignments();

    setupEventListeners();
  
    setMinDateTime();
});

const mockRequests = [
    {
        id: 'REQ0042',
        provider: 'Ahmad Bin Abdullah',
        items: ['Laptop', 'Smartphone', 'Battery'],
        address: 'No. 42, Jalan SS2/72, 47300 Petaling Jaya, Selangor',
        postcode: '47300',
        preferredDate: '2026-02-23T14:00',
        weight: '5.2 kg',
        status: 'approved'
    },
    {
        id: 'REQ0038',
        provider: 'Siti Nurhaliza',
        items: ['TV', 'Monitor', 'Cables'],
        address: 'B-3-5, Pangsapuri Damai, Jalan Gasing, 46000 PJ',
        postcode: '46000',
        preferredDate: '2026-02-24T10:00',
        weight: '18 kg',
        status: 'approved'
    },
    {
        id: 'REQ0045',
        provider: 'Rajesh Kumar',
        items: ['Printer', 'Scanner', 'Keyboard'],
        address: '12, Jalan 19/1B, Seksyen 19, 46300 Shah Alam',
        postcode: '46300',
        preferredDate: '2026-02-25T15:30',
        weight: '7.8 kg',
        status: 'approved'
    }
];

const mockCollectors = [
    { id: 1, name: 'Kevin Tan', available: true, jobsToday: 2, vehicle: 'Toyota Hiace' },
    { id: 2, name: 'Muthu Krishnan', available: true, jobsToday: 1, vehicle: 'Isuzu D-Max' },
    { id: 3, name: 'Jason Wong', available: false, jobsToday: 3, vehicle: 'Ford Transit' }
];

const mockVehicles = [
    { id: 'V101', model: 'Toyota Hiawa', status: 'Available', capacity: '800 kg' },
    { id: 'V102', model: 'Isuzu D-Max', status: 'In Use', capacity: '1200 kg' },
    { id: 'V103', model: 'Ford Transit', status: 'Maintenance', capacity: '1000 kg' }
];

const mockCentres = [
    { id: 'C01', name: 'APU E-waste Hub', capacity: 85, address: 'APU Campus, Technology Park' },
    { id: 'C02', name: 'Sunway Recycling Centre', capacity: 42, address: 'Sunway Mentari' },
    { id: 'C03', name: 'Shah Alam Collection Point', capacity: 68, address: 'Seksyen 15' }
];

// ===== CORE FUNCTIONS =====

function loadPendingRequests() {
    const container = document.getElementById('requestCardsContainer');
    if (!container) return;
    
    container.innerHTML = '<div class="loading-spinner">Loading requests...</div>';
    
    setTimeout(() => {
        container.innerHTML = '';
        mockRequests.forEach((req, index) => {
            const card = createRequestCard(req, index);
            container.appendChild(card);
        });
        
        if (mockRequests.length > 0) {
            document.querySelectorAll('.request-card')[0]?.classList.add('selected');
            updateAssignmentPanel(mockRequests[0]);
        }
    }, 500);
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
            <span class="preferred-date">📅 ${formattedDate}</span>
        </div>
    </div>
`;
    
    card.addEventListener('click', () => {
        document.querySelectorAll('.request-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        updateAssignmentPanel(request);
    });
    
    return card;
}

function updateAssignmentPanel(request) {
    document.getElementById('selectedRequestId').textContent = `#${request.id}`;

    const summaryDiv = document.getElementById('selectedRequestSummary');
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
    dateNote.innerHTML = `<span>ⓘ</span> Preferred: ${dateStr}`;
    
    document.getElementById('confirmAssignmentBtn').disabled = false;
}

function loadVehicles() {
    const select = document.getElementById('vehicleSelect');
    if (!select) return;
    
    select.innerHTML = '<option value="" disabled selected>Select a vehicle</option>';
    
    mockVehicles.forEach(vehicle => {
        const option = document.createElement('option');
        option.value = vehicle.id;
        option.textContent = `${vehicle.model} - ${vehicle.status} (${vehicle.capacity})`;
        if (vehicle.status !== 'Available') option.disabled = true;
        select.appendChild(option);
    });
}

function loadCentres() {
    const select = document.getElementById('centreSelect');
    if (!select) return;
    
    select.innerHTML = '<option value="" disabled selected>Select a collection centre</option>';
    
    mockCentres.forEach(centre => {
        const option = document.createElement('option');
        option.value = centre.id;
        option.textContent = centre.name;
        option.dataset.capacity = centre.capacity;
        option.dataset.address = centre.address;
        select.appendChild(option);
    });
   
    if (mockCentres.length > 0) {
        updateCapacityCircle(mockCentres[0].capacity);
    }
}

function updateCapacityCircle(capacity) {
    const circle = document.getElementById('capacityCircle');
    const percentageSpan = document.getElementById('capacityPercentage');
    
    if (!circle || !percentageSpan) return;
    
    const degrees = (capacity / 100) * 360;
    
    circle.style.background = `conic-gradient(var(--MainBlue) ${degrees}deg, var(--LightBlue) 0deg)`;
    
    percentageSpan.textContent = `${capacity}%`;
}

function loadRecentAssignments() {
    const timeline = document.getElementById('recentTimeline');
    if (!timeline) return;
    
    const now = new Date();
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
    document.getElementById('scheduledDateTime').min = now.toISOString().slice(0, 16);
}

// ===== EVENT LISTENERS =====

function setupEventListeners() {
    document.getElementById('requestFilter')?.addEventListener('change', filterRequests);
    document.getElementById('requestSearch')?.addEventListener('input', filterRequests);
   
    document.getElementById('collectorSelect')?.addEventListener('change', function(e) {
        const hint = document.getElementById('collectorHint');
        if (this.value) {
            const collector = mockCollectors.find(c => c.id == this.value);
            hint.textContent = `✅ Available - ${collector.name} uses ${collector.vehicle}`;
        } else {
            hint.textContent = '';
        }
    });
    
    document.getElementById('vehicleSelect')?.addEventListener('change', function(e) {
        const hint = document.getElementById('vehicleHint');
        if (this.value) {
            const vehicle = mockVehicles.find(v => v.id == this.value);
            hint.textContent = `🚛 ${vehicle.model} - ${vehicle.status}, ${vehicle.capacity}`;
        } else {
            hint.textContent = '';
        }
    });
    
   
document.getElementById('centreSelect')?.addEventListener('change', function(e) {
    const selectedOption = this.options[this.selectedIndex];
    if (this.value) {
   
        const centre = mockCentres.find(c => c.id == this.value);
        if (centre) {
            updateCapacityCircle(centre.capacity);
        }
    } else {
        updateCapacityCircle(0); 
    }
});
    
    // Confirm assignment
    document.getElementById('confirmAssignmentBtn')?.addEventListener('click', confirmAssignment);
    
    // Reset button
    document.getElementById('resetAssignmentBtn')?.addEventListener('click', resetAssignmentForm);
    
    // View collector availability
    document.getElementById('viewCollectorAvailability')?.addEventListener('click', () => {
        alert('Collector availability calendar would open here');
    });
    
    // View vehicle status
    document.getElementById('viewVehicleStatus')?.addEventListener('click', () => {
        alert('Vehicle maintenance schedule would open here');
    });
    
    // Quick actions
    document.getElementById('contactProvider')?.addEventListener('click', () => {
        alert('Contact dialog would open (phone/SMS)');
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
    loadPendingRequests(); 
}

function confirmAssignment() {
    const collector = document.getElementById('collectorSelect')?.value;
    const vehicle = document.getElementById('vehicleSelect')?.value;
    const centre = document.getElementById('centreSelect')?.value;
    const datetime = document.getElementById('scheduledDateTime')?.value;
    const notes = document.getElementById('assignmentNotes')?.value;
    
    if (!collector || !vehicle || !centre || !datetime) {
        alert('Please fill all required fields');
        return;
    }
    
    alert(`✓ Assignment confirmed!\n\nCollector assigned\nVehicle assigned\nCentre assigned\nScheduled: ${new Date(datetime).toLocaleString()}`);
    
    addToTimeline(datetime);
    
    resetAssignmentForm();
}

function resetAssignmentForm() {
    document.getElementById('collectorSelect').value = '';
    document.getElementById('vehicleSelect').value = '';
    document.getElementById('centreSelect').value = '';
    document.getElementById('scheduledDateTime').value = '';
    document.getElementById('assignmentNotes').value = '';
    document.getElementById('collectorHint').textContent = '';
    document.getElementById('vehicleHint').textContent = '';
    
    if (mockCentres.length > 0) {
        updateCapacityCircle(mockCentres[0].capacity);
    } else {
        updateCapacityCircle(85);
    }
    
    document.getElementById('confirmAssignmentBtn').disabled = true;
}

function addToTimeline(datetime) {
    const timeline = document.getElementById('recentTimeline');
    const now = new Date();
    const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    const newItem = document.createElement('div');
    newItem.className = 'timeline-item';
    newItem.innerHTML = `
        <span class="timeline-time">${timeStr}</span>
        <span class="timeline-event"><strong>#REQ-0042</strong> assigned</span>
        <span style="color: var(--Gray); margin-left: auto;">Just now</span>
    `;
    
    timeline.insertBefore(newItem, timeline.firstChild);
    
    if (timeline.children.length > 4) {
        timeline.removeChild(timeline.lastChild);
    }
}