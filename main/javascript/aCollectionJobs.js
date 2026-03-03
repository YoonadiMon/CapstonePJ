const collectionJobsData = {
    JOB002: {
        status: "accepted",
        provider: {
            name: "James Wong",
            address: "47800, Petaling Jaya, Malaysia",
            date: "28/02/2026",
            lat: 3.1073,
            lng: 101.6066
        },
        items: [
            {
                id: "ITEM004",
                name: "Desktop Computer",
                brand: "Custom PC - i7/16GB",
                weight: "12.5",
                dropoff: "Petaling Jaya Center"
            },
            {
                id: "ITEM005",
                name: "Monitor",
                brand: "Samsung 24\"",
                weight: "3.8",
                dropoff: "Petaling Jaya Center"
            }
        ],
        collector: "Ahmad Bin Yusof",
        vehicle: "Toyota Hiace (VH23)",
        datetime: "2026-02-28T14:30",
        requestId: "REQ246",
        distance: "3.2",
        estimatedDuration: "25",
        collectorLat: 3.1200,
        collectorLng: 101.6200,
        collectorStatus: "online"
    },
    JOB003: {
        status: "ongoing",
        provider: {
            name: "Sarah Tan",
            address: "56000, Kuala Lumpur, Malaysia",
            date: "25/02/2026",
            lat: 3.1390,
            lng: 101.6869
        },
        items: [
            {
                id: "ITEM006",
                name: "Printer",
                brand: "Canon PIXMA",
                weight: "6.2",
                dropoff: "KL Central Hub"
            },
            {
                id: "ITEM007",
                name: "Scanner",
                brand: "Epson Perfection",
                weight: "4.1",
                dropoff: "KL Central Hub"
            },
            {
                id: "ITEM008",
                name: "External HDD",
                brand: "WD 2TB",
                weight: "0.3",
                dropoff: "KL Central Hub"
            }
        ],
        collector: "Siti Nurhaliza",
        vehicle: "Isuzu NLR (VH07)",
        datetime: "2026-02-25T10:15",
        requestId: "REQ250",
        currentLocation: "In transit - Jalan Ampang",
        collectorLat: 3.1450,
        collectorLng: 101.6950,
        collectorStatus: "busy"
    },
    JOB004: {
        status: "delayed",
        provider: {
            name: "Raj Kumar",
            address: "40100, Shah Alam, Malaysia",
            date: "26/02/2026",
            lat: 3.0735,
            lng: 101.5185
        },
        items: [
            {
                id: "ITEM009",
                name: "Microwave",
                brand: "Panasonic Inverter",
                weight: "14.5",
                dropoff: "Shah Alam Center"
            }
        ],
        collector: "Mei Ling",
        vehicle: "Mitsubishi L300 (VH09)",
        datetime: "2026-02-26T08:00",
        requestId: "REQ251",
        delayReason: "Traffic accident",
        estimatedDelay: "45",
        collectorLat: 3.0800,
        collectorLng: 101.5250,
        collectorStatus: "busy"
    },
    JOB005: {
        status: "pickedup",
        provider: {
            name: "Lim Wei Jie",
            address: "43000, Kajang, Malaysia",
            date: "24/02/2026",
            lat: 2.9904,
            lng: 101.7886
        },
        items: [
            {
                id: "ITEM010",
                name: "TV",
                brand: "Sony Bravia 55\"",
                weight: "18.2",
                dropoff: "Kajang Collection Point"
            },
            {
                id: "ITEM011",
                name: "Soundbar",
                brand: "Sony HT-S350",
                weight: "3.5",
                dropoff: "Kajang Collection Point"
            }
        ],
        collector: "Tan Sri Aziz",
        vehicle: "Nissan NV350 (VH33)",
        datetime: "2026-02-24T13:45",
        requestId: "REQ252",
        collectorLat: 3.0000,
        collectorLng: 101.7950,
        collectorStatus: "online"
    },
    JOB008: {
        status: "failed",
        provider: {
            name: "Priya Krishnan",
            address: "50000, Kuala Lumpur, Malaysia",
            date: "21/02/2026",
            lat: 3.1515,
            lng: 101.7062
        },
        items: [
            {
                id: "ITEM015",
                name: "Laptop",
                brand: "MacBook Pro 2019",
                weight: "2.0",
                dropoff: "KL Central Hub"
            },
            {
                id: "ITEM016",
                name: "Power Bank",
                brand: "Anker 20000mAh",
                weight: "0.4",
                dropoff: "KL Central Hub"
            }
        ],
        collector: "Hassan Osman",
        vehicle: "Daihatsu (VH41)",
        datetime: "2026-02-21T08:30",
        requestId: "REQ262",
        failReason: "handover required - vehicle breakdown",
        collectorLat: 3.1550,
        collectorLng: 101.7100,
        collectorStatus: "offline"
    },
    JOB010: {
        status: "accepted",
        provider: {
            name: "Emergency Request",
            address: "57000, Kuala Lumpur, Malaysia",
            date: "03/03/2026",
            lat: 3.1625,
            lng: 101.6712
        },
        items: [
            {
                id: "ITEM018",
                name: "Server Equipment",
                brand: "Dell PowerEdge",
                weight: "45.0",
                dropoff: "KL Central Hub"
            }
        ],
        collector: "Emergency Team",
        vehicle: "Spare Van (VH99)",
        datetime: "2026-03-03T10:00",
        requestId: "REQ270",
        acceptedAsEmergency: true,
        collectorLat: 3.1650,
        collectorLng: 101.6750,
        collectorStatus: "online"
    }
};

// Process data
let collectionJobs = Object.entries(collectionJobsData).map(([jobId, jobData]) => {
    const totalWeight = jobData.items.reduce((sum, item) => sum + parseFloat(item.weight), 0);
    const today = new Date().toDateString();
    const jobDate = new Date(jobData.datetime).toDateString();
    
    return {
        id: jobId,
        requestId: jobData.requestId,
        status: jobData.status,
        collector: jobData.collector,
        vehicle: jobData.vehicle,
        datetime: jobData.datetime,
        address: jobData.provider.address,
        totalWeight: totalWeight.toFixed(1),
        providerName: jobData.provider.name,
        itemCount: jobData.items.length,
        items: jobData.items,
        isToday: jobDate === today,
        fullData: jobData,
        lat: jobData.provider.lat,
        lng: jobData.provider.lng,
        collectorLat: jobData.collectorLat,
        collectorLng: jobData.collectorLng,
        collectorStatus: jobData.collectorStatus || 'online',
        ...(jobData.delayReason && { delayReason: jobData.delayReason }),
        ...(jobData.estimatedDelay && { estimatedDelay: jobData.estimatedDelay }),
        ...(jobData.failReason && { failReason: jobData.failReason }),
        ...(jobData.acceptedAsEmergency && { acceptedAsEmergency: true }),
        ...(jobData.distance && { distance: jobData.distance }),
        ...(jobData.estimatedDuration && { estimatedDuration: jobData.estimatedDuration }),
        ...(jobData.currentLocation && { currentLocation: jobData.currentLocation })
    };
});

// State
let currentCollectorFilter = 'all';
let map = null;
let mapMarkers = [];
let mapInitialized = false;
let currentView = 'list'; 

// DOM Elements
const priorityQueue = document.getElementById('priorityQueue');
const collectorsGrid = document.getElementById('collectorsGrid');
const delayedList = document.getElementById('delayedList');
const handoverList = document.getElementById('handoverList');
const handoverBanner = document.getElementById('handoverBanner');
const bannerHandoverCount = document.getElementById('bannerHandoverCount');
const panelDelayedCount = document.getElementById('panelDelayedCount');
const panelHandoverCount = document.getElementById('panelHandoverCount');
const headerDelayedCount = document.getElementById('headerDelayedCount');
const headerHandoverCount = document.getElementById('headerHandoverCount');
const priorityCount = document.getElementById('priorityCount');
const activeCollectionsCount = document.getElementById('activeCollectionsCount');
const completedToday = document.getElementById('completedToday');
const avgResponse = document.getElementById('avgResponse');
const totalDistance = document.getElementById('totalDistance');
const mapPlaceholder = document.getElementById('mapPlaceholder');
const actualMap = document.getElementById('actualMap');
const viewToggleText = document.getElementById('viewToggleText');
const viewIcon = document.getElementById('viewIcon');
const toggleViewBtn = document.getElementById('toggleViewBtn');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    setupEventListeners();
    renderDashboard();
    initMap();
    
    setInterval(() => {
        simulateLiveUpdate();
    }, 30000);
});

function setupEventListeners() {
    document.getElementById('refreshBtn')?.addEventListener('click', () => {
        renderDashboard();
        if (mapInitialized) {
            updateMapMarkers();
        }
    });

    document.querySelectorAll('.collector-filter-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.collector-filter-btn').forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            currentCollectorFilter = e.target.dataset.collector;
            renderDashboard();
            if (mapInitialized) {
                updateMapMarkers();
            }
        });
    });

    toggleViewBtn.addEventListener('click', toggleView);

    const themeToggleDesktop = document.getElementById('themeToggleDesktop');
    const themeToggleMobile = document.getElementById('themeToggleMobile');
    
    if (themeToggleDesktop) {
        themeToggleDesktop.addEventListener('click', (e) => {
            e.preventDefault();
            toggleTheme();
        });
    }
    
    if (themeToggleMobile) {
        themeToggleMobile.addEventListener('click', (e) => {
            e.preventDefault();
            toggleTheme();
        });
    }
}

function renderDashboard() {
    renderPriorityQueue();
    renderCollectorsGrid();
    renderDelayedList();
    renderHandoverList();
    updateStats();
}

function renderPriorityQueue() {
    if (!priorityQueue) return;

    const priorityJobs = collectionJobs
        .filter(job => {
            if (!job.isToday) return false;
            const activeStatuses = ['accepted', 'ongoing', 'delayed', 'pickedup'];
            if (!activeStatuses.includes(job.status)) return false;
            if (currentCollectorFilter !== 'all' && job.collector !== currentCollectorFilter) return false;
            return true;
        })
        .sort((a, b) => {
            if (a.status === 'delayed' && b.status !== 'delayed') return -1;
            if (a.status !== 'delayed' && b.status === 'delayed') return 1;
            return new Date(a.datetime) - new Date(b.datetime);
        })
        .slice(0, 5);

    priorityCount.textContent = priorityJobs.length;

    if (priorityJobs.length === 0) {
        priorityQueue.innerHTML = `
            <div class="no-jobs-message">
                <i class="fas fa-check-circle"></i>
                <p>No priority jobs</p>
            </div>
        `;
        return;
    }

    let html = '';
    priorityJobs.forEach(job => {
        const time = new Date(job.datetime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const priorityClass = job.status === 'delayed' ? 'delayed' : (job.acceptedAsEmergency ? 'urgent' : '');
        
        html += `
            <div class="priority-item ${priorityClass}" onclick="showJobQuickView('${job.id}')">
                <div class="priority-info">
                    <span class="priority-time">${time}</span>
                    <div class="priority-details">
                        <strong>${job.providerName}</strong>
                        <small>${job.collector}</small>
                    </div>
                </div>
                ${job.status === 'delayed' ? 
                    `<span class="priority-eta">+${job.estimatedDelay || '?'}min</span>` : 
                    (job.acceptedAsEmergency ? '<span class="priority-eta" style="background:#ff4757;color:white;">EMERGENCY</span>' : '')
                }
            </div>
        `;
    });

    priorityQueue.innerHTML = html;
}

function renderCollectorsGrid() {
    if (!collectorsGrid) return;

    const collectors = {};
    collectionJobs.forEach(job => {
        if (job.collector === 'Not assigned' || job.collector === 'Emergency Team') return;
        
        if (!collectors[job.collector]) {
            collectors[job.collector] = {
                name: job.collector,
                vehicle: job.vehicle,
                status: job.collectorStatus || 'online',
                activeJobs: [],
                totalJobs: 0
            };
        }
        
        if (['accepted', 'ongoing', 'delayed', 'pickedup'].includes(job.status)) {
            collectors[job.collector].activeJobs.push(job);
        }
        collectors[job.collector].totalJobs++;
    });

    const collectorsArray = Object.values(collectors);
    activeCollectionsCount.textContent = collectorsArray.length;

    if (collectorsArray.length === 0) {
        collectorsGrid.innerHTML = `
            <div class="no-jobs-message">
                <i class="fas fa-truck"></i>
                <p>No active collectors</p>
            </div>
        `;
        return;
    }

    let html = '';
    collectorsArray.forEach(collector => {
        const activeCount = collector.activeJobs.length;
        const progress = collector.activeJobs[0] ? 
            Math.min(100, (new Date() - new Date(collector.activeJobs[0].datetime)) / (30 * 60 * 1000) * 100) : 0;
        
        html += `
            <div class="collector-card ${collector.status}" onclick="filterByCollector('${collector.name}')">
                <div class="collector-header">
                    <span class="collector-name">
                        <i class="fas fa-user"></i> ${collector.name.split(' ')[0]}
                    </span>
                    <span class="collector-status-badge ${collector.status}">${collector.status}</span>
                </div>
                <div class="collector-job">
                    <i class="fas fa-truck"></i> ${collector.vehicle}
                </div>
                ${activeCount > 0 ? `
                    <div class="collector-progress">
                        <div class="progress-bar" style="width: ${progress}%"></div>
                    </div>
                    <div class="collector-meta">
                        <span><i class="fas fa-box"></i> ${activeCount} active</span>
                        <span><i class="fas fa-clock"></i> ${collector.activeJobs[0]?.estimatedDuration || '?'}min</span>
                    </div>
                ` : `
                    <div class="collector-meta">
                        <span><i class="fas fa-check"></i> Available</span>
                    </div>
                `}
            </div>
        `;
    });

    collectorsGrid.innerHTML = html;
}

function renderDelayedList() {
    if (!delayedList) return;

    const delayedJobs = collectionJobs.filter(job => job.status === 'delayed');
    panelDelayedCount.textContent = delayedJobs.length;
    headerDelayedCount.textContent = delayedJobs.length;

    if (delayedJobs.length === 0) {
        delayedList.innerHTML = `
            <div class="no-jobs-message">
                <i class="fas fa-check-circle"></i>
                <p>No delayed jobs</p>
            </div>
        `;
        return;
    }

    let html = '';
    delayedJobs.forEach(job => {
        html += `
            <div class="panel-item">
                <div class="item-info">
                    <i class="fas fa-hashtag"></i>
                    <span>${job.id}</span>
                    <span class="item-reason">${job.delayReason || 'Delayed'}</span>
                </div>
                <div class="item-actions">
                    <button class="btn-icon" onclick="showJobQuickView('${job.id}')" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon" onclick="contactCollector('${job.id}')" title="Contact">
                        <i class="fas fa-phone"></i>
                    </button>
                </div>
            </div>
        `;
    });

    delayedList.innerHTML = html;
}

function renderHandoverList() {
    if (!handoverList) return;

    const handoverJobs = collectionJobs.filter(job => 
        job.status === 'failed' && job.failReason && job.failReason.includes('handover')
    );
    
    panelHandoverCount.textContent = handoverJobs.length;
    headerHandoverCount.textContent = handoverJobs.length;
    bannerHandoverCount.textContent = handoverJobs.length;
    handoverBanner.style.display = handoverJobs.length > 0 ? 'flex' : 'none';

    if (handoverJobs.length === 0) {
        handoverList.innerHTML = `
            <div class="no-jobs-message">
                <i class="fas fa-check-circle"></i>
                <p>No handover required</p>
            </div>
        `;
        return;
    }

    let html = '';
    handoverJobs.forEach(job => {
        html += `
            <div class="panel-item">
                <div class="item-info">
                    <i class="fas fa-hashtag"></i>
                    <span>${job.id}</span>
                    <span class="item-reason">Handover</span>
                </div>
                <div class="item-actions">
                    <button class="btn-icon" onclick="showReassignModal('${job.id}')" title="Reassign" style="background:#ff4757;color:white;">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                    <button class="btn-icon" onclick="showJobQuickView('${job.id}')" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        `;
    });

    handoverList.innerHTML = html;
}

function updateStats() {
    // Calculate stats
    const today = new Date().toDateString();
    const completedTodayCount = collectionJobs.filter(job => 
        job.status === 'completed' && new Date(job.datetime).toDateString() === today
    ).length;
    
    const avgResponseTime = '32min';
    const totalDistanceToday = '187km';
    
    completedToday.textContent = completedTodayCount;
    avgResponse.textContent = avgResponseTime;
    totalDistance.textContent = totalDistanceToday;
}

// Map Functions
function initMap() {
    const defaultCenter = [3.1390, 101.6869];
    
    map = L.map('actualMap').setView(defaultCenter, 11);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    mapInitialized = true;
    updateMapMarkers();
  
    mapPlaceholder.style.display = 'none';
    actualMap.style.display = 'block';
}

function updateMapMarkers() {
    if (!mapInitialized) return;
    
    mapMarkers.forEach(marker => map.removeLayer(marker));
    mapMarkers = [];
    
    const collectors = {};
    collectionJobs.forEach(job => {
        if (job.collector !== 'Not assigned' && job.collector !== 'Emergency Team' && job.collectorLat && job.collectorLng) {
            if (!collectors[job.collector]) {
                collectors[job.collector] = {
                    name: job.collector,
                    lat: job.collectorLat,
                    lng: job.collectorLng,
                    status: job.collectorStatus || 'online',
                    jobId: job.id,
                    providerName: job.providerName
                };
            }
        }
    });
    
    // Add collector markers
    Object.values(collectors).forEach(collector => {
        if (currentCollectorFilter === 'all' || collector.name === currentCollectorFilter) {
            const markerColor = getCollectorColor(collector.name);
            const marker = L.circleMarker([collector.lat, collector.lng], {
                radius: 8,
                fillColor: markerColor,
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(map);
            
            marker.bindPopup(`
                <b>${collector.name}</b><br>
                Status: ${collector.status}<br>
                Current job: ${collector.providerName}<br>
                <button onclick="showJobQuickView('${collector.jobId}')" style="margin-top:5px;padding:2px 8px;border-radius:12px;border:none;background:#3498db;color:white;cursor:pointer;">
                    View Job
                </button>
            `);
            
            mapMarkers.push(marker);
        }
    });
    
    collectionJobs.forEach(job => {
        if (job.lat && job.lng && ['accepted', 'ongoing', 'delayed'].includes(job.status)) {
            if (currentCollectorFilter === 'all' || job.collector === currentCollectorFilter) {
                const markerColor = job.status === 'delayed' ? '#ff4757' : '#f39c12';
                const marker = L.circleMarker([job.lat, job.lng], {
                    radius: 6,
                    fillColor: markerColor,
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(map);
                
                marker.bindPopup(`
                    <b>${job.providerName}</b><br>
                    Status: ${job.status}<br>
                    Collector: ${job.collector}<br>
                    Items: ${job.itemCount}<br>
                    <button onclick="showJobQuickView('${job.id}')" style="margin-top:5px;padding:2px 8px;border-radius:12px;border:none;background:#3498db;color:white;cursor:pointer;">
                        View Details
                    </button>
                `);
                
                mapMarkers.push(marker);
            }
        }
    });
}

function getCollectorColor(collectorName) {
    const colors = {
        'Ahmad Bin Yusof': '#3498db',
        'Siti Nurhaliza': '#9b59b6',
        'Mei Ling': '#1abc9c',
        'Tan Sri Aziz': '#f39c12',
        'Hassan Osman': '#95a5a6'
    };
    return colors[collectorName] || '#3498db';
}

function toggleView() {
    if (currentView === 'list') {
        currentView = 'map';
        viewToggleText.textContent = 'List View';
        viewIcon.className = 'fas fa-list';
 
    } else {
        currentView = 'list';
        viewToggleText.textContent = 'Map View';
        viewIcon.className = 'fas fa-map';
    }
}

window.centerMapOnAll = function() {
    if (!mapInitialized) return;
    const bounds = L.latLngBounds([]);
    mapMarkers.forEach(marker => {
        bounds.extend(marker.getLatLng());
    });
    map.fitBounds(bounds, { padding: [50, 50] });
};

window.toggleMapLayers = function() {
    alert('Layer toggle - would switch map style');
};

window.zoomToFit = function() {
    window.centerMapOnAll();
};

window.showJobQuickView = function(jobId) {
    const job = collectionJobs.find(j => j.id === jobId);
    if (!job) return;
    
    const content = document.getElementById('quickViewContent');
    const time = new Date(job.datetime).toLocaleString();
    
    content.innerHTML = `
        <div style="margin-bottom:1rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                <strong style="font-size:1.1rem;">${job.id}</strong>
                <span style="padding:0.2rem 0.8rem;border-radius:12px;background:${getStatusColor(job.status)};color:white;font-size:0.8rem;">
                    ${job.status}
                </span>
            </div>
            <p><i class="fas fa-user"></i> ${job.providerName}</p>
            <p><i class="fas fa-map-marker-alt"></i> ${job.address}</p>
            <p><i class="fas fa-truck"></i> ${job.collector}</p>
            <p><i class="fas fa-box"></i> ${job.itemCount} items (${job.totalWeight} kg)</p>
            <p><i class="fas fa-clock"></i> ${time}</p>
            ${job.delayReason ? `<p style="color:#ff4757;"><i class="fas fa-exclamation-triangle"></i> ${job.delayReason}</p>` : ''}
            ${job.failReason ? `<p style="color:#ff4757;"><i class="fas fa-times-circle"></i> ${job.failReason}</p>` : ''}
        </div>
    `;
    
    document.getElementById('jobQuickViewModal').style.display = 'flex';
};

window.closeJobQuickView = function() {
    document.getElementById('jobQuickViewModal').style.display = 'none';
};

window.viewFullJobDetails = function() {
    alert('Would redirect to full job details page');
    closeJobQuickView();
};

function getStatusColor(status) {
    const colors = {
        'accepted': '#3498db',
        'ongoing': '#f39c12',
        'delayed': '#ff4757',
        'pickedup': '#2ecc71',
        'failed': '#ff4757',
        'completed': '#2ecc71'
    };
    return colors[status] || '#95a5a6';
}

window.filterByCollector = function(collectorName) {
    const buttons = document.querySelectorAll('.collector-filter-btn');
    buttons.forEach(btn => {
        if (btn.dataset.collector === collectorName) {
            btn.click();
        }
    });
};

window.scrollToHandover = function() {
    document.getElementById('handoverPanel')?.scrollIntoView({ behavior: 'smooth' });
};

function simulateLiveUpdate() {
    const randomJob = collectionJobs[Math.floor(Math.random() * collectionJobs.length)];
    if (randomJob && randomJob.status === 'ongoing') {
        renderDashboard();
        if (mapInitialized) {
            updateMapMarkers();
        }
    }
}

let currentReassignJobId = null;

window.showReassignModal = function(jobId) {
    const job = collectionJobs.find(j => j.id === jobId);
    if (!job) return;

    currentReassignJobId = jobId;
    
    const collectorSelect = document.getElementById('newCollectorSelect');
    const collectors = ['Ahmad Bin Yusof', 'Siti Nurhaliza', 'Mei Ling', 'Tan Sri Aziz', 'Vincent Wong', 'Hassan Osman'];
    collectorSelect.innerHTML = '<option value="">-- Select Collector --</option>' + 
        collectors.map(c => `<option value="${c}">${c}</option>`).join('');
    
    const vehicleSelect = document.getElementById('newVehicleSelect');
    const vehicles = ['Toyota Hiace (VH23)', 'Isuzu NLR (VH07)', 'Mitsubishi L300 (VH09)', 'Nissan NV350 (VH33)', 'Hilux (VH05)', 'Daihatsu (VH41)'];
    vehicleSelect.innerHTML = '<option value="">-- Select Vehicle --</option>' + 
        vehicles.map(v => `<option value="${v}">${v}</option>`).join('');
    
    document.getElementById('modalJobInfo').innerHTML = `
        <span><strong>${job.id}</strong> - ${job.providerName}</span>
        <span class="item-reason">${job.failReason || 'Handover required'}</span>
    `;
    
    const eta = new Date();
    eta.setHours(eta.getHours() + 2);
    document.getElementById('etaTime').textContent = eta.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    document.getElementById('reassignModal').style.display = 'flex';
};

window.closeReassignModal = function() {
    document.getElementById('reassignModal').style.display = 'none';
    currentReassignJobId = null;
};

window.confirmReassign = function() {
    const collector = document.getElementById('newCollectorSelect')?.value;
    const vehicle = document.getElementById('newVehicleSelect')?.value;
    const notes = document.getElementById('reassignNotes')?.value;
    
    if (!collector || !vehicle) {
        alert('Please select both collector and vehicle');
        return;
    }
    
    alert(`Job ${currentReassignJobId} reassigned to ${collector} (${vehicle})\nNotes: ${notes || 'None'}`);
    closeReassignModal();
    
    renderDashboard();
    if (mapInitialized) {
        updateMapMarkers();
    }
};

window.startJob = function(jobId) {
    alert(`Starting job ${jobId}`);
};

window.markPickedUp = function(jobId) {
    if (confirm(`Mark job ${jobId} as picked up?`)) {
        alert(`Job ${jobId} marked as picked up`);
    }
};

window.completeJob = function(jobId) {
    if (confirm(`Complete job ${jobId}?`)) {
        alert(`Job ${jobId} completed`);
    }
};

window.reportDelay = function(jobId) {
    const reason = prompt('Reason for delay:');
    if (reason) {
        alert(`Delay reported for ${jobId}: ${reason}`);
    }
};

window.updateDelay = function(jobId) {
    const eta = prompt('New estimated arrival time (minutes):');
    if (eta) {
        alert(`ETA updated for ${jobId}: +${eta} minutes`);
    }
};

window.contactCollector = function(jobId) {
    const job = collectionJobs.find(j => j.id === jobId);
    alert(`Calling collector: ${job?.collector || 'Unknown'}`);
};

function toggleTheme() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    
    const settingImg = document.getElementById('settingImg');
    const settingImgM = document.getElementById('settingImgM');
    
    if (settingImg) settingImg.src = isDark ? '/main/assets/images/setting-dark.svg' : '/main/assets/images/setting-light.svg';
    if (settingImgM) settingImgM.src = isDark ? '/main/assets/images/setting-dark.svg' : '/main/assets/images/setting-light.svg';
    
    const themeToggleDesktop = document.getElementById('themeToggleDesktop');
    const themeToggleMobile = document.getElementById('themeToggleMobile');
    
    if (themeToggleDesktop) {
        const img = themeToggleDesktop.querySelector('img');
        if (img) img.src = isDark ? '/main/assets/images/dark-mode-icon.svg' : '/main/assets/images/light-mode-icon.svg';
    }
    
    if (themeToggleMobile) {
        const img = themeToggleMobile.querySelector('img');
        if (img) img.src = isDark ? '/main/assets/images/dark-mode-icon.svg' : '/main/assets/images/light-mode-icon.svg';
    }
    
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

function initDarkMode() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    } else if (savedTheme === 'light') {
        document.body.classList.remove('dark-mode');
    } else {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (prefersDark) document.body.classList.add('dark-mode');
    }
    updateThemeIcons(document.body.classList.contains('dark-mode'));
}

function updateThemeIcons(isDark) {
    const settingImg = document.getElementById('settingImg');
    const settingImgM = document.getElementById('settingImgM');
    
    if (settingImg) settingImg.src = isDark ? '/main/assets/images/setting-dark.svg' : '/main/assets/images/setting-light.svg';
    if (settingImgM) settingImgM.src = isDark ? '/main/assets/images/setting-dark.svg' : '/main/assets/images/setting-light.svg';
    
    const themeToggleDesktop = document.getElementById('themeToggleDesktop');
    const themeToggleMobile = document.getElementById('themeToggleMobile');
    
    if (themeToggleDesktop) {
        const img = themeToggleDesktop.querySelector('img');
        if (img) img.src = isDark ? '/main/assets/images/dark-mode-icon.svg' : '/main/assets/images/light-mode-icon.svg';
    }
    
    if (themeToggleMobile) {
        const img = themeToggleMobile.querySelector('img');
        if (img) img.src = isDark ? '/main/assets/images/dark-mode-icon.svg' : '/main/assets/images/light-mode-icon.svg';
    }
}

window.hideMenu = window.hideMenu || function() { 
    document.getElementById('sidebarNav')?.classList.remove('open'); 
    document.getElementById('cover')?.classList.remove('active'); 
};

window.showMenu = window.showMenu || function() { 
    document.getElementById('sidebarNav')?.classList.add('open'); 
    document.getElementById('cover')?.classList.add('active'); 
};