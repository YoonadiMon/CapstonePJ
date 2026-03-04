document.addEventListener('DOMContentLoaded', function() {
    
console.log("aCollectionJobs.js loaded successfully");

const collectionJobsData = {
    JOB001: {
        status: "ongoing",
        provider: {
            name: "Ahmad Bin Abdullah",
            address: "No 15, Jalan SS2/72, Petaling Jaya, Selangor, 47300",
            date: "04/03/2026",
            lat: 3.1234,
            lng: 101.6123
        },
        items: [
            {
                id: "ITEM001",
                name: "Refrigerator",
                brand: "Samsung 2-door",
                weight: "35.5",
                dropoff: "Petaling Jaya Center"
            },
            {
                id: "ITEM002",
                name: "Washing Machine",
                brand: "LG Front Load",
                weight: "42.0",
                dropoff: "Petaling Jaya Center"
            }
        ],
        collector: "Ahmad Bin Yusof",
        vehicle: "Toyota Hiace (VH23)",
        datetime: "2026-03-04T09:30",
        requestId: "REQ001",
        distance: "3.2",
        estimatedDuration: "45",
        collectorLat: 3.1189,
        collectorLng: 101.6089,
        collectorStatus: "busy",
        currentLocation: "In transit - Jalan Universiti"
    },
    JOB002: {
        status: "accepted",
        provider: {
            name: "James Wong",
            address: "47800, Petaling Jaya, Malaysia",
            date: "04/03/2026",
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
        datetime: "2026-03-04T10:15",
        requestId: "REQ246",
        distance: "1.8",
        estimatedDuration: "20",
        collectorLat: 3.1200,
        collectorLng: 101.6200,
        collectorStatus: "busy"
    },
    JOB003: {
        status: "ongoing",
        provider: {
            name: "Sarah Tan",
            address: "56000, Kuala Lumpur, Malaysia",
            date: "04/03/2026",
            lat: 3.1520,
            lng: 101.7030
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
        datetime: "2026-03-04T08:45",
        requestId: "REQ250",
        currentLocation: "In transit - Jalan Ampang",
        collectorLat: 3.1480,
        collectorLng: 101.6980,
        collectorStatus: "busy"
    },
    JOB004: {
        status: "ongoing",
        provider: {
            name: "Raj Kumar",
            address: "40100, Shah Alam, Malaysia",
            date: "04/03/2026",
            lat: 3.0835,
            lng: 101.5285
        },
        items: [
            {
                id: "ITEM009",
                name: "Air Conditioner",
                brand: "Daikin 1.5HP",
                weight: "28.5",
                dropoff: "Shah Alam Center"
            },
            {
                id: "ITEM012",
                name: "Ceiling Fan",
                brand: "Panasonic",
                weight: "5.2",
                dropoff: "Shah Alam Center"
            }
        ],
        collector: "Siti Nurhaliza",
        vehicle: "Isuzu NLR (VH07)",
        datetime: "2026-03-04T11:30",
        requestId: "REQ253",
        currentLocation: "Near Shah Alam Stadium",
        collectorLat: 3.0880,
        collectorLng: 101.5330,
        collectorStatus: "busy"
    },
    JOB005: {
        status: "ongoing",
        provider: {
            name: "Lim Wei Jie",
            address: "43000, Kajang, Malaysia",
            date: "04/03/2026",
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
        datetime: "2026-03-04T09:15",
        requestId: "REQ252",
        collectorLat: 2.9950,
        collectorLng: 101.7920,
        collectorStatus: "busy"
    },
    JOB006: {
        status: "ongoing",
        provider: {
            name: "Michael Chen",
            address: "No 8, Jalan Kuchai Lama, Kuala Lumpur, 58200",
            date: "04/03/2026",
            lat: 3.0890,
            lng: 101.6820
        },
        items: [
            {
                id: "ITEM013",
                name: "Microwave Oven",
                brand: "Panasonic",
                weight: "14.5",
                dropoff: "KL Central Hub"
            },
            {
                id: "ITEM014",
                name: "Rice Cooker",
                brand: "Philips",
                weight: "2.8",
                dropoff: "KL Central Hub"
            }
        ],
        collector: "Tan Sri Aziz",
        vehicle: "Nissan NV350 (VH33)",
        datetime: "2026-03-04T13:00",
        requestId: "REQ254",
        collectorLat: 3.0920,
        collectorLng: 101.6870,
        collectorStatus: "busy"
    },
    JOB007: {
        status: "pickedup",
        provider: {
            name: "Mei Ling Wong",
            address: "No 22, Jalan Ipoh, Kuala Lumpur, 51200",
            date: "04/03/2026",
            lat: 3.1680,
            lng: 101.6910
        },
        items: [
            {
                id: "ITEM017",
                name: "Electric Kettle",
                brand: "Khind",
                weight: "1.2",
                dropoff: "KL Central Hub"
            },
            {
                id: "ITEM019",
                name: "Iron",
                brand: "Philips",
                weight: "1.5",
                dropoff: "KL Central Hub"
            }
        ],
        collector: "Mei Ling",
        vehicle: "Mitsubishi L300 (VH09)",
        datetime: "2026-03-04T10:30",
        requestId: "REQ255",
        collectorLat: 3.1650,
        collectorLng: 101.6880,
        collectorStatus: "online"
    },
    JOB008: {
        status: "failed",
        provider: {
            name: "Priya Krishnan",
            address: "50000, Kuala Lumpur, Malaysia",
            date: "04/03/2026",
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
        datetime: "2026-03-04T08:30",
        requestId: "REQ262",
        failReason: "handover required - vehicle breakdown",
        collectorLat: 3.1550,
        collectorLng: 101.7100,
        collectorStatus: "offline"
    },
    JOB009: {
        status: "delayed",
        provider: {
            name: "Kevin Tan",
            address: "No 45, Jalan Bukit Bintang, Kuala Lumpur, 55100",
            date: "04/03/2026",
            lat: 3.1460,
            lng: 101.7130
        },
        items: [
            {
                id: "ITEM020",
                name: "Gaming Console",
                brand: "PlayStation 5",
                weight: "4.5",
                dropoff: "KL Central Hub"
            },
            {
                id: "ITEM021",
                name: "Gaming Chair",
                brand: "Secretlab",
                weight: "22.0",
                dropoff: "KL Central Hub"
            }
        ],
        collector: "Mei Ling",
        vehicle: "Mitsubishi L300 (VH09)",
        datetime: "2026-03-04T07:45",
        requestId: "REQ263",
        delayReason: "Heavy traffic - Jalan Tun Razak",
        estimatedDelay: "30",
        collectorLat: 3.1490,
        collectorLng: 101.7150,
        collectorStatus: "busy"
    },
    JOB010: {
        status: "delayed",
        provider: {
            name: "Emergency Request",
            address: "57000, Kuala Lumpur, Malaysia",
            date: "04/03/2026",
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
        datetime: "2026-03-04T10:00",
        requestId: "REQ270",
        acceptedAsEmergency: true,
        collectorLat: 3.1650,
        collectorLng: 101.6750,
        collectorStatus: "busy",
        delayReason: "Road closure - alternate route",
        estimatedDelay: "25"
    },
    JOB011: {
        status: "ongoing",
        provider: {
            name: "Zainal Abidin",
            address: "No 3, Jalan Gasing, Petaling Jaya, 46000",
            date: "04/03/2026",
            lat: 3.1030,
            lng: 101.6500
        },
        items: [
            {
                id: "ITEM022",
                name: "Battery Pack",
                brand: "Tesla Powerwall",
                weight: "120.0",
                dropoff: "Petaling Jaya Center"
            }
        ],
        collector: "Ahmad Bin Yusof",
        vehicle: "Toyota Hiace (VH23)",
        datetime: "2026-03-04T14:00",
        requestId: "REQ271",
        collectorLat: 3.1080,
        collectorLng: 101.6450,
        collectorStatus: "busy",
        currentLocation: "Near Jalan Gasing"
    },

    JOB012: {
        status: "pickedup",
        provider: {
            name: "Chong Wei",
            address: "No 56, Jalan Imbi, Kuala Lumpur, 55100",
            date: "04/03/2026",
            lat: 3.1420,
            lng: 101.7150
        },
        items: [
            {
                id: "ITEM023",
                name: "Server Rack",
                brand: "Dell",
                weight: "65.0",
                dropoff: "KL Central Hub"
            },
            {
                id: "ITEM024",
                name: "Network Switch",
                brand: "Cisco",
                weight: "8.5",
                dropoff: "KL Central Hub"
            }
        ],
        collector: "Tan Sri Aziz",
        vehicle: "Nissan NV350 (VH33)",
        datetime: "2026-03-04T11:45",
        requestId: "REQ272",
        collectorLat: 3.1380,
        collectorLng: 101.7120,
        collectorStatus: "busy",
        dropoffFailed: true,
        failReason: "Centre at full capacity - KL Central Hub"
    },
    JOB013: {
        status: "pickedup",
        provider: {
            name: "Nur Aisyah",
            address: "No 12, Jalan Telawi, Bangsar, 59100",
            date: "04/03/2026",
            lat: 3.1290,
            lng: 101.6700
        },
        items: [
            {
                id: "ITEM025",
                name: "Battery Bank",
                brand: "EcoFlow",
                weight: "25.0",
                dropoff: "Petaling Jaya Center"
            }
        ],
        collector: "Mei Ling",
        vehicle: "Mitsubishi L300 (VH09)",
        datetime: "2026-03-04T12:30",
        requestId: "REQ273",
        collectorLat: 3.1320,
        collectorLng: 101.6750,
        collectorStatus: "busy",
        dropoffFailed: true,
        failReason: "Centre closed - Petaling Jaya Center"
    }
};

// ===== SECOND: Process the data =====
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
        dropoffFailed: jobData.dropoffFailed || false,
        ...(jobData.delayReason && { delayReason: jobData.delayReason }),
        ...(jobData.estimatedDelay && { estimatedDelay: jobData.estimatedDelay }),
        ...(jobData.failReason && { failReason: jobData.failReason }),
        ...(jobData.acceptedAsEmergency && { acceptedAsEmergency: true }),
        ...(jobData.distance && { distance: jobData.distance }),
        ...(jobData.estimatedDuration && { estimatedDuration: jobData.estimatedDuration }),
        ...(jobData.currentLocation && { currentLocation: jobData.currentLocation })
    };
});

console.log("Total jobs before test data:", collectionJobs.length);
console.log("Failed jobs before test:", collectionJobs.filter(j => j.status === 'failed').length);
console.log("Delayed jobs before test:", collectionJobs.filter(j => j.status === 'delayed').length);
console.log("Picked up with dropoffFailed before test:", collectionJobs.filter(j => j.status === 'pickedup' && j.dropoffFailed).length);

if (collectionJobs.filter(j => j.status === 'failed').length === 0) {
    console.log("Adding test failed job");
    collectionJobs.push({
        id: "TEST001",
        status: "failed",
        collector: "Test Collector",
        vehicle: "Test Vehicle",
        datetime: new Date().toISOString(),
        address: "Test Address",
        totalWeight: "10.0",
        providerName: "Test Provider",
        itemCount: 2,
        isToday: true,
        failReason: "handover required - test"
    });
}

if (collectionJobs.filter(j => j.status === 'delayed').length === 0) {
    console.log("Adding test delayed job");
    collectionJobs.push({
        id: "TEST002",
        status: "delayed",
        collector: "Test Collector",
        vehicle: "Test Vehicle",
        datetime: new Date().toISOString(),
        address: "Test Address",
        totalWeight: "15.0",
        providerName: "Test Provider",
        itemCount: 3,
        isToday: true,
        delayReason: "Test delay"
    });
}

if (collectionJobs.filter(j => j.status === 'pickedup' && j.dropoffFailed).length === 0) {
    console.log("Adding test dropoff failed job");
    collectionJobs.push({
        id: "TEST003",
        status: "pickedup",
        collector: "Test Collector",
        vehicle: "Test Vehicle",
        datetime: new Date().toISOString(),
        address: "Test Address",
        totalWeight: "20.0",
        providerName: "Test Provider",
        itemCount: 4,
        isToday: true,
        dropoffFailed: true,
        failReason: "Centre at full capacity - Test Centre",
        items: [{ dropoff: "Test Centre" }]
    });
}

console.log("Total jobs after test data:", collectionJobs.length);
console.log("Failed jobs after test:", collectionJobs.filter(j => j.status === 'failed').length);
console.log("Delayed jobs after test:", collectionJobs.filter(j => j.status === 'delayed').length);
console.log("Picked up with dropoffFailed after test:", collectionJobs.filter(j => j.status === 'pickedup' && j.dropoffFailed).length);

console.log("Checking DOM elements:");
console.log("- activeCollectorList:", document.getElementById('activeCollectorList'));
console.log("- activeCollectorCount:", document.getElementById('activeCollectorCount'));
console.log("- actualMap:", document.getElementById('actualMap'));

// State
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

const activeCollectorList = document.getElementById('activeCollectorList');
const activeCollectorCount = document.getElementById('activeCollectorCount');

const pendingDropoffList = document.getElementById('pendingDropoffList');
const pendingDropoffCount = document.getElementById('pendingDropoffCount');
const itemsInTransit = document.getElementById('itemsInTransit');
const affectedCollectors = document.getElementById('affectedCollectors');
const centresAvailable = document.getElementById('centresAvailable');

console.log("DOM Elements found:", {
    priorityQueue: !!priorityQueue,
    collectorsGrid: !!collectorsGrid,
    delayedList: !!delayedList,
    handoverList: !!handoverList,
    toggleViewBtn: !!toggleViewBtn,
    pendingDropoffList: !!pendingDropoffList
});

// ===== FIFTH: Initialize everything =====
console.log("DOM Content Loaded - Initializing...");
initDarkMode();
setupEventListeners();
renderDashboard();
renderActiveCollectorsList();
renderPendingDropoffList();
initMap();

setInterval(() => {
    console.log("Auto-refreshing data...");
    refreshData();
}, 30000);


function setupEventListeners() {
    console.log("Setting up event listeners");
    
    document.getElementById('refreshBtn')?.addEventListener('click', () => {
        console.log("Refresh button clicked - forcing all data to reload");
        
        renderPriorityQueue();
        renderCollectorsGrid();
        renderDelayedList();
        renderHandoverList();
        renderPendingDropoffList();  // NOW INCLUDED
        renderActiveCollectorsList();
        updateStats();
        
        if (mapInitialized) {
            updateMapMarkers();
        }
        
        console.log("Refresh complete");
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
    console.log("Rendering dashboard...");
    renderPriorityQueue();
    renderCollectorsGrid();
    renderDelayedList();
    renderHandoverList();
    renderPendingDropoffList();  // ADDED THIS LINE
    updateStats();
}

function renderPriorityQueue() {
    if (!priorityQueue) {
        console.error("priorityQueue element not found");
        return;
    }

    const priorityJobs = collectionJobs
        .filter(job => {
            if (!job.isToday) return false;
            const activeStatuses = ['accepted', 'ongoing', 'delayed', 'pickedup'];
            return activeStatuses.includes(job.status);
        })
        .sort((a, b) => {
            if (a.status === 'delayed' && b.status !== 'delayed') return -1;
            if (a.status !== 'delayed' && b.status === 'delayed') return 1;
            return new Date(a.datetime) - new Date(b.datetime);
        })
        .slice(0, 5);

    console.log("Priority jobs found:", priorityJobs.length);
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
    if (!collectorsGrid) {
        console.error("collectorsGrid element not found");
        return;
    }

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
    console.log("Collectors found:", collectorsArray.length);
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
            <div class="collector-card ${collector.status}">
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
    if (!delayedList) {
        console.error("delayedList element not found");
        return;
    }

    const delayedJobs = collectionJobs.filter(job => job.status === 'delayed');
    console.log("Delayed jobs found:", delayedJobs.length);
    console.log("Delayed jobs data:", delayedJobs);
    
    panelDelayedCount.textContent = delayedJobs.length;
    headerDelayedCount.textContent = delayedJobs.length;

    if (delayedJobs.length === 0) {
        delayedList.innerHTML = `
            <div class="no-jobs-message">
                <i class="fas fa-check-circle"></i>
                <p>No delayed jobs</p>
                <small style="color: var(--Gray);">Add test data to see examples</small>
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
    if (!handoverList) {
        console.error("handoverList element not found");
        return;
    }

    // More flexible filtering
    const handoverJobs = collectionJobs.filter(job => 
        job.status === 'failed' || 
        (job.failReason && job.failReason.includes('handover'))
    );
    
    console.log("Handover jobs found:", handoverJobs.length);
    console.log("Handover jobs data:", handoverJobs);
    
    panelHandoverCount.textContent = handoverJobs.length;
    headerHandoverCount.textContent = handoverJobs.length;
    bannerHandoverCount.textContent = handoverJobs.length;
    handoverBanner.style.display = handoverJobs.length > 0 ? 'flex' : 'none';

    if (handoverJobs.length === 0) {
        handoverList.innerHTML = `
            <div class="no-jobs-message">
                <i class="fas fa-check-circle"></i>
                <p>No handover required</p>
                <small style="color: var(--Gray);">Add test data to see examples</small>
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
                    <span class="item-reason">${job.failReason || 'Handover required'}</span>
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

// Render Pending Drop-off Jobs (Picked Up but drop-off failed)
function renderPendingDropoffList() {
    console.log("Rendering pending dropoff list...");
    const pendingDropoffList = document.getElementById('pendingDropoffList');
    const pendingDropoffCount = document.getElementById('pendingDropoffCount');
    const itemsInTransit = document.getElementById('itemsInTransit');
    const affectedCollectors = document.getElementById('affectedCollectors');
    const centresAvailable = document.getElementById('centresAvailable');
    
    if (!pendingDropoffList) {
        console.error("pendingDropoffList element not found");
        return;
    }
    
    // Find jobs with status 'pickedup' that have dropoffFailed flag
    const pendingDropoffJobs = collectionJobs.filter(job => 
        job.status === 'pickedup' && job.dropoffFailed === true
    );
    
    console.log("Pending dropoff jobs found:", pendingDropoffJobs.length);
    
    if (pendingDropoffCount) pendingDropoffCount.textContent = pendingDropoffJobs.length;
    
    // Calculate items in transit
    let totalItems = 0;
    pendingDropoffJobs.forEach(job => totalItems += job.itemCount);
    if (itemsInTransit) itemsInTransit.textContent = totalItems;
    
    // Calculate affected collectors
    const uniqueCollectors = new Set(pendingDropoffJobs.map(job => job.collector));
    if (affectedCollectors) affectedCollectors.textContent = uniqueCollectors.size;
    
    // Simulate centres available
    if (centresAvailable) centresAvailable.textContent = '3';
    
    if (pendingDropoffJobs.length === 0) {
        pendingDropoffList.innerHTML = `
            <div class="no-jobs-message" style="padding: 2rem;">
                <i class="fas fa-check-circle" style="color: #2ecc71;"></i>
                <p>No pending drop-off failures</p>
                <small style="color: var(--Gray);">All picked up jobs completed successfully</small>
            </div>
        `;
        return;
    }
    
    let html = '';
    pendingDropoffJobs.forEach(job => {
        const failReason = job.failReason || 'Drop-off failed - Centre at capacity';
        const dropoffCentre = job.items[0]?.dropoff || 'Unknown Centre';
        
        html += `
            <div class="dropoff-item">
                <div class="dropoff-header">
                    <span class="dropoff-id">${job.id}</span>
                    <span class="dropoff-status">Partial Completed</span>
                </div>
                <div class="dropoff-details">
                    <div class="dropoff-detail">
                        <span class="dropoff-detail-label">Collector</span>
                        <span class="dropoff-detail-value">${job.collector}</span>
                    </div>
                    <div class="dropoff-detail">
                        <span class="dropoff-detail-label">Vehicle</span>
                        <span class="dropoff-detail-value">${job.vehicle}</span>
                    </div>
                    <div class="dropoff-detail">
                        <span class="dropoff-detail-label">Items</span>
                        <span class="dropoff-detail-value">${job.itemCount} (${job.totalWeight}kg)</span>
                    </div>
                    <div class="dropoff-detail">
                        <span class="dropoff-detail-label">Original Centre</span>
                        <span class="dropoff-detail-value">${dropoffCentre}</span>
                    </div>
                </div>
                <div class="dropoff-fail-reason">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>${failReason}</span>
                </div>
                <div class="dropoff-actions">
                    <button class="btn-reassign-centre" onclick="alert('Reassign ${job.id} to alternative centre')">
                        <i class="fas fa-exchange-alt"></i> Reassign Centre
                    </button>
                    <button class="btn-icon" onclick="showJobQuickView('${job.id}')" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon" onclick="contactCollector('${job.id}')" title="Contact Collector">
                        <i class="fas fa-phone"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    pendingDropoffList.innerHTML = html;
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

// Map Functions - SIMULATED for now
function initMap() {
    console.log("Initializing simulated map...");
    console.log("- actualMap element:", actualMap);
    
    if (!actualMap) {
        console.error("Map element not found!");
        return;
    }
    
    // Simulate map with a placeholder message
    if (mapPlaceholder) {
        mapPlaceholder.innerHTML = `
            <i class="fas fa-map-marked-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <p style="font-size: 1.2rem; font-weight: 600;">Map Simulation Mode</p>
            <p style="font-size: 0.9rem; color: var(--Gray);">Real map will be integrated later</p>
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <span style="background: #3498db; color: white; padding: 0.3rem 1rem; border-radius: 20px;">Ahmad</span>
                <span style="background: #9b59b6; color: white; padding: 0.3rem 1rem; border-radius: 20px;">Siti</span>
                <span style="background: #1abc9c; color: white; padding: 0.3rem 1rem; border-radius: 20px;">Mei Ling</span>
                <span style="background: #f39c12; color: white; padding: 0.3rem 1rem; border-radius: 20px;">Tan Sri</span>
            </div>
        `;
        mapPlaceholder.style.display = 'flex';
        mapPlaceholder.style.flexDirection = 'column';
    }
    
    if (actualMap) actualMap.style.display = 'none';
    
    // Mark as initialized for other functions
    mapInitialized = true;
    console.log("Map simulation complete");
}

function updateMapMarkers() {
    // Simulated - does nothing in simulation mode
    console.log("Map markers update simulated");
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
    alert('Map simulation - center on all collectors');
};

window.toggleMapLayers = function() {
    alert('Map simulation - toggle layers');
};

window.zoomToFit = function() {
    alert('Map simulation - zoom to fit');
};

window.showJobQuickView = function(jobId) {
    const job = collectionJobs.find(j => j.id === jobId);
    if (!job) return;
    
    alert(`Job Details: ${job.id}\nProvider: ${job.providerName}\nStatus: ${job.status}\nCollector: ${job.collector}\nItems: ${job.itemCount} (${job.totalWeight}kg)`);
};

window.closeJobQuickView = function() {
    // Simulated
};

window.viewFullJobDetails = function() {
    alert('Would redirect to full job details page');
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

    alert(`Reassign ${jobId} - Select collector from: Ahmad, Siti, Mei Ling, Tan Sri`);
};

window.closeReassignModal = function() {
    // Simulated
};

window.confirmReassign = function() {
    alert('Job reassigned (simulated)');
};

window.startJob = function(jobId) {
    alert(`Starting job ${jobId} (simulated)`);
};

window.markPickedUp = function(jobId) {
    alert(`Job ${jobId} marked as picked up (simulated)`);
};

window.completeJob = function(jobId) {
    alert(`Job ${jobId} completed (simulated)`);
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
    alert(`Calling collector: ${job?.collector || 'Unknown'} (simulated)`);
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

function renderActiveCollectorsList() {
    if (!activeCollectorList) {
        console.error("activeCollectorList element not found");
        return;
    }

    const activeCollectors = {};
    collectionJobs.forEach(job => {
        if (job.collector === 'Not assigned' || job.collector === 'Emergency Team') return;
        
        if (!activeCollectors[job.collector]) {
            activeCollectors[job.collector] = {
                name: job.collector,
                vehicle: job.vehicle,
                status: job.collectorStatus || 'online',
                activeJobs: [],
                lat: job.collectorLat,
                lng: job.collectorLng,
                jobIds: []
            };
        }
        
        if (['accepted', 'ongoing', 'delayed', 'pickedup'].includes(job.status)) {
            activeCollectors[job.collector].activeJobs.push(job);
            activeCollectors[job.collector].jobIds.push(job.id);
        }
    });

    const collectorsArray = Object.values(activeCollectors);
    console.log("Active collectors found:", collectorsArray.length);
    
    if (activeCollectorCount) activeCollectorCount.textContent = collectorsArray.length;

    if (collectorsArray.length === 0) {
        activeCollectorList.innerHTML = `
            <div class="no-jobs-message" style="padding:1rem;">
                <p>No active collectors</p>
                <small style="color: var(--Gray);">Add test data to see examples</small>
            </div>
        `;
        return;
    }

    let html = '';
    collectorsArray.forEach(collector => {
        const initials = collector.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        const activeCount = collector.activeJobs.length;
        
        html += `
            <div class="collector-list-item" onclick="showCollectorRoute('${collector.name}')">
                <div class="collector-list-info">
                    <div class="collector-avatar">${initials}</div>
                    <div class="collector-details">
                        <span class="collector-list-name">${collector.name}</span>
                        <span class="collector-list-vehicle">${collector.vehicle}</span>
                    </div>
                </div>
                <div class="collector-list-status">
                    <span class="status-badge-collector ${collector.status}"></span>
                    <span>${activeCount} job${activeCount !== 1 ? 's' : ''}</span>
                </div>
            </div>
        `;
    });

    activeCollectorList.innerHTML = html;
}

// Show collector route on map (simulated)
window.showCollectorRoute = function(collectorName) {
    console.log("Showing route for:", collectorName);
    
    // Remove active class from all items
    document.querySelectorAll('.collector-list-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Add active class to clicked item
    const clickedItem = event?.currentTarget;
    if (clickedItem) {
        clickedItem.classList.add('active');
    }
    
    alert(`Route simulation for ${collectorName}\n\nPath would show:\n- Collector location\n- All pickup points\n- Estimated route on map`);
};

window.hideMenu = window.hideMenu || function() { 
    document.getElementById('sidebarNav')?.classList.remove('open'); 
    document.getElementById('cover')?.classList.remove('active'); 
};

window.showMenu = window.showMenu || function() { 
    document.getElementById('sidebarNav')?.classList.add('open'); 
    document.getElementById('cover')?.classList.add('active'); 
};

// FORCE REFRESH FUNCTION
window.forceRefresh = function() {
    console.log("FORCE REFRESHING ALL DATA...");
    
    // Force update collectors grid
    if (collectorsGrid) {
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

        let html = '';
        collectorsArray.forEach(collector => {
            const activeCount = collector.activeJobs.length;
            const progress = collector.activeJobs[0] ? 
                Math.min(100, (new Date() - new Date(collector.activeJobs[0].datetime)) / (30 * 60 * 1000) * 100) : 0;
            
            html += `
                <div class="collector-card ${collector.status}">
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
    
    // Force update active collectors list
    if (activeCollectorList) {
        const activeCollectors = {};
        collectionJobs.forEach(job => {
            if (job.collector === 'Not assigned' || job.collector === 'Emergency Team') return;
            
            if (!activeCollectors[job.collector]) {
                activeCollectors[job.collector] = {
                    name: job.collector,
                    vehicle: job.vehicle,
                    status: job.collectorStatus || 'online',
                    activeJobs: [],
                    lat: job.collectorLat,
                    lng: job.collectorLng
                };
            }
            
            if (['accepted', 'ongoing', 'delayed', 'pickedup'].includes(job.status)) {
                activeCollectors[job.collector].activeJobs.push(job);
            }
        });

        const collectorsArray = Object.values(activeCollectors);
        activeCollectorCount.textContent = collectorsArray.length;

        let listHtml = '';
        collectorsArray.forEach(collector => {
            const initials = collector.name.split(' ').map(n => n[0]).join('').substring(0, 2);
            const activeCount = collector.activeJobs.length;
            
            listHtml += `
                <div class="collector-list-item" onclick="showCollectorRoute('${collector.name}')">
                    <div class="collector-list-info">
                        <div class="collector-avatar">${initials}</div>
                        <div class="collector-details">
                            <span class="collector-list-name">${collector.name}</span>
                            <span class="collector-list-vehicle">${collector.vehicle}</span>
                        </div>
                    </div>
                    <div class="collector-list-status">
                        <span class="status-badge-collector ${collector.status}"></span>
                        <span>${activeCount} job${activeCount !== 1 ? 's' : ''}</span>
                    </div>
                </div>
            `;
        });
        activeCollectorList.innerHTML = listHtml;
    }
    
    // Force update delayed list
    if (delayedList) {
        const delayedJobs = collectionJobs.filter(job => job.status === 'delayed');
        panelDelayedCount.textContent = delayedJobs.length;
        headerDelayedCount.textContent = delayedJobs.length;

        if (delayedJobs.length === 0) {
            delayedList.innerHTML = '<div class="no-jobs-message"><i class="fas fa-check-circle"></i><p>No delayed jobs</p></div>';
        } else {
            let delayedHtml = '';
            delayedJobs.forEach(job => {
                delayedHtml += `
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
            delayedList.innerHTML = delayedHtml;
        }
    }
    
    // Force update handover list
    if (handoverList) {
        const handoverJobs = collectionJobs.filter(job => 
            job.status === 'failed' && job.failReason && job.failReason.includes('handover')
        );
        
        panelHandoverCount.textContent = handoverJobs.length;
        headerHandoverCount.textContent = handoverJobs.length;
        bannerHandoverCount.textContent = handoverJobs.length;
        handoverBanner.style.display = handoverJobs.length > 0 ? 'flex' : 'none';

        if (handoverJobs.length === 0) {
            handoverList.innerHTML = '<div class="no-jobs-message"><i class="fas fa-check-circle"></i><p>No handover required</p></div>';
        } else {
            let handoverHtml = '';
            handoverJobs.forEach(job => {
                handoverHtml += `
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
            handoverList.innerHTML = handoverHtml;
        }
    }
    
    // Force update pending dropoff list
    if (pendingDropoffList) {
        renderPendingDropoffList();
    }
    
    // Update priority queue
    if (priorityQueue) {
        const priorityJobs = collectionJobs
            .filter(job => {
                if (!job.isToday) return false;
                const activeStatuses = ['accepted', 'ongoing', 'delayed', 'pickedup'];
                return activeStatuses.includes(job.status);
            })
            .sort((a, b) => {
                if (a.status === 'delayed' && b.status !== 'delayed') return -1;
                if (a.status !== 'delayed' && b.status === 'delayed') return 1;
                return new Date(a.datetime) - new Date(b.datetime);
            })
            .slice(0, 5);

        priorityCount.textContent = priorityJobs.length;

        if (priorityJobs.length === 0) {
            priorityQueue.innerHTML = '<div class="no-jobs-message"><i class="fas fa-check-circle"></i><p>No priority jobs</p></div>';
        } else {
            let priorityHtml = '';
            priorityJobs.forEach(job => {
                const time = new Date(job.datetime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const priorityClass = job.status === 'delayed' ? 'delayed' : (job.acceptedAsEmergency ? 'urgent' : '');
                
                priorityHtml += `
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
            priorityQueue.innerHTML = priorityHtml;
        }
    }
    
    // Update stats
    const today = new Date().toDateString();
    const completedTodayCount = collectionJobs.filter(job => 
        job.status === 'completed' && new Date(job.datetime).toDateString() === today
    ).length;
    
    completedToday.textContent = completedTodayCount;
    avgResponse.textContent = '32min';
    totalDistance.textContent = '187km';
    
    console.log("Force refresh complete!");
};

// Call force refresh after 1 second to ensure DOM is ready
setTimeout(function() {
    console.log("Initial force refresh...");
    window.forceRefresh();
}, 1000);

// Refresh data function
function refreshData() {
    console.log("Refreshing data...");
    window.forceRefresh();
    if (mapInitialized) {
        updateMapMarkers();
    }
}

// Force show handover and delayed data
setTimeout(function() {
    console.log("Forcing handover and delayed data to show...");
    
    // Force update handover list
    if (handoverList) {
        const handoverJobs = collectionJobs.filter(job => 
            job.status === 'failed' && job.failReason && job.failReason.includes('handover')
        );
        
        panelHandoverCount.textContent = handoverJobs.length;
        headerHandoverCount.textContent = handoverJobs.length;
        
        if (handoverJobs.length > 0) {
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
    }
    
    // Force update delayed list
    if (delayedList) {
        const delayedJobs = collectionJobs.filter(job => job.status === 'delayed');
        panelDelayedCount.textContent = delayedJobs.length;
        headerDelayedCount.textContent = delayedJobs.length;
        
        if (delayedJobs.length > 0) {
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
    }

    if (pendingDropoffList) {
        renderPendingDropoffList();
    }
    
}, 500); 

setTimeout(function() {
    console.log("Final force refresh...");
    window.forceRefresh();
    renderPendingDropoffList();
    renderActiveCollectorsList();
}, 1500);

}); 