const jobsData = [
    {
        id: "JOB001",
        requestId: "REQ001",
        status: "pending",
        providerName: "John's Electronics",
        address: "123 Main Street, 50450 Kuala Lumpur, Malaysia",
        collector: "Not assigned",
        vehicle: "Not assigned",
        datetime: "2026-03-03T09:00",
        itemCount: 3,
        totalWeight: "15.5",
        stage: "pre-execution",
        fullData: {
            provider: {
                name: "John's Electronics",
                address: "123 Main Street, 50450 Kuala Lumpur, Malaysia",
                date: "03/03/2026"
            },
            items: [
                {
                    id: "ITEM001",
                    name: "Laptop",
                    brand: "Dell XPS",
                    weight: "2.5",
                    dropoff: "KL Central Hub",
                    description: "Non-working laptop"
                },
                {
                    id: "ITEM002",
                    name: "Monitor",
                    brand: "Samsung 24\"",
                    weight: "5.0",
                    dropoff: "KL Central Hub",
                    description: "Broken screen"
                },
                {
                    id: "ITEM003",
                    name: "Keyboard",
                    brand: "Logitech",
                    weight: "8.0",
                    dropoff: "KL Central Hub",
                    description: "Multiple keyboards"
                }
            ]
        }
    },
    {
        id: "JOB002",
        requestId: "REQ002",
        status: "accepted",
        providerName: "Tech Solutions",
        address: "456 Business Park, 50400 Kuala Lumpur, Malaysia",
        collector: "Ahmad Bin Yusof",
        vehicle: "Toyota Hiace (VH23)",
        datetime: "2026-03-03T10:30",
        itemCount: 2,
        totalWeight: "25.0",
        stage: "pre-execution",
        fullData: {
            provider: {
                name: "Tech Solutions",
                address: "456 Business Park, 50400 Kuala Lumpur, Malaysia",
                date: "03/03/2026"
            },
            items: [
                {
                    id: "ITEM004",
                    name: "Server Rack",
                    brand: "Dell PowerEdge",
                    weight: "20.0",
                    dropoff: "Tech Hub",
                    description: "Old server equipment"
                },
                {
                    id: "ITEM005",
                    name: "Network Switch",
                    brand: "Cisco",
                    weight: "5.0",
                    dropoff: "Tech Hub",
                    description: "24-port switch"
                }
            ]
        }
    },
    {
        id: "JOB006",
        requestId: "REQ006",
        status: "rejected",
        providerName: "Quick Electronics",
        address: "555 Tech Park, 50200 Kuala Lumpur, Malaysia",
        collector: "Not assigned",
        vehicle: "Not assigned",
        datetime: "2026-03-02T15:30",
        itemCount: 2,
        totalWeight: "8.5",
        stage: "pre-execution",
        rejectReason: "Unacceptable items (hazardous materials)",
        fullData: {
            provider: {
                name: "Quick Electronics",
                address: "555 Tech Park, 50200 Kuala Lumpur, Malaysia",
                date: "02/03/2026"
            },
            items: [
                {
                    id: "ITEM012",
                    name: "Batteries",
                    brand: "Industrial",
                    weight: "5.0",
                    dropoff: "N/A",
                    description: "Hazardous materials"
                },
                {
                    id: "ITEM013",
                    name: "CRT Monitors",
                    brand: "Various",
                    weight: "3.5",
                    dropoff: "N/A",
                    description: "Contains lead"
                }
            ]
        }
    },

    {
        id: "JOB003",
        requestId: "REQ003",
        status: "ongoing",
        providerName: "Green Recycling",
        address: "789 Industrial Area, 57000 Kuala Lumpur, Malaysia",
        collector: "Siti Nurhaliza",
        vehicle: "Isuzu NLR (VH07)",
        datetime: "2026-03-03T08:00",
        itemCount: 5,
        totalWeight: "45.2",
        stage: "execution",
        fullData: {
            provider: {
                name: "Green Recycling",
                address: "789 Industrial Area, 57000 Kuala Lumpur, Malaysia",
                date: "03/03/2026"
            },
            items: [
                {
                    id: "ITEM006",
                    name: "Batteries",
                    brand: "Various",
                    weight: "15.2",
                    dropoff: "Recycling Centre",
                    description: "Mixed batteries"
                },
                {
                    id: "ITEM007",
                    name: "Circuit Boards",
                    brand: "Various",
                    weight: "30.0",
                    dropoff: "Recycling Centre",
                    description: "Computer components"
                }
            ]
        }
    },
    {
        id: "JOB005",
        requestId: "REQ005",
        status: "delayed",
        providerName: "City Electronics",
        address: "321 Urban Square, 50470 Kuala Lumpur, Malaysia",
        collector: "Vincent Wong",
        vehicle: "Nissan NV350 (VH33)",
        datetime: "2026-03-03T11:15",
        itemCount: 4,
        totalWeight: "35.7",
        stage: "execution",
        delayReason: "Traffic congestion",
        fullData: {
            provider: {
                name: "City Electronics",
                address: "321 Urban Square, 50470 Kuala Lumpur, Malaysia",
                date: "03/03/2026"
            },
            items: [
                {
                    id: "ITEM010",
                    name: "Televisions",
                    brand: "Sony/LG",
                    weight: "25.0",
                    dropoff: "City Hub",
                    description: "3 LED TVs"
                },
                {
                    id: "ITEM011",
                    name: "Audio Equipment",
                    brand: "Various",
                    weight: "10.7",
                    dropoff: "City Hub",
                    description: "Speakers and receivers"
                }
            ]
        }
    },
    {
        id: "JOB007",
        requestId: "REQ007",
        status: "pickedup",
        providerName: "Northern Recycling",
        address: "123 North Road, 52000 Kuala Lumpur, Malaysia",
        collector: "Hassan Osman",
        vehicle: "Hilux (VH05)",
        datetime: "2026-03-02T13:00",
        itemCount: 6,
        totalWeight: "52.3",
        stage: "execution",
        fullData: {
            provider: {
                name: "Northern Recycling",
                address: "123 North Road, 52000 Kuala Lumpur, Malaysia",
                date: "02/03/2026"
            },
            items: [
                {
                    id: "ITEM014",
                    name: "Industrial Equipment",
                    brand: "Various",
                    weight: "52.3",
                    dropoff: "North Hub",
                    description: "Mixed industrial e-waste"
                }
            ]
        }
    },

    {
        id: "JOB004",
        requestId: "REQ004",
        status: "completed",
        providerName: "APU University",
        address: "Technology Park, 57000 Kuala Lumpur, Malaysia",
        collector: "Mei Ling",
        vehicle: "Mitsubishi L300 (VH09)",
        datetime: "2026-03-02T14:00",
        itemCount: 8,
        totalWeight: "62.8",
        stage: "resolution",
        completedAt: "2026-03-02T18:30",
        fullData: {
            provider: {
                name: "APU University",
                address: "Technology Park, 57000 Kuala Lumpur, Malaysia",
                date: "02/03/2026"
            },
            items: [
                {
                    id: "ITEM008",
                    name: "Desktop Computers",
                    brand: "HP",
                    weight: "40.0",
                    dropoff: "APU Recycling",
                    description: "10 units"
                },
                {
                    id: "ITEM009",
                    name: "Printers",
                    brand: "Canon",
                    weight: "22.8",
                    dropoff: "APU Recycling",
                    description: "3 printers"
                }
            ]
        }
    },
    {
        id: "JOB008",
        requestId: "REQ008",
        status: "cancelled",
        providerName: "Sunset Electronics",
        address: "777 Sunset Blvd, 54000 Kuala Lumpur, Malaysia",
        collector: "Not assigned",
        vehicle: "Not assigned",
        datetime: "2026-03-01T10:00",
        itemCount: 3,
        totalWeight: "18.0",
        stage: "resolution",
        cancelReason: "Provider cancelled",
        fullData: {
            provider: {
                name: "Sunset Electronics",
                address: "777 Sunset Blvd, 54000 Kuala Lumpur, Malaysia",
                date: "01/03/2026"
            },
            items: [
                {
                    id: "ITEM015",
                    name: "Misc Electronics",
                    brand: "Various",
                    weight: "18.0",
                    dropoff: "N/A",
                    description: "Cancelled request"
                }
            ]
        }
    },
    {
        id: "JOB009",
        requestId: "REQ009",
        status: "failed",
        providerName: "Coastal Recycling",
        address: "999 Beach Road, 51000 Kuala Lumpur, Malaysia",
        collector: "Tan Sri Aziz",
        vehicle: "Daihatsu (VH41)",
        datetime: "2026-03-01T09:30",
        itemCount: 4,
        totalWeight: "28.5",
        stage: "resolution",
        failReason: "Vehicle breakdown, handover required",
        fullData: {
            provider: {
                name: "Coastal Recycling",
                address: "999 Beach Road, 51000 Kuala Lumpur, Malaysia",
                date: "01/03/2026"
            },
            items: [
                {
                    id: "ITEM016",
                    name: "Recyclables",
                    brand: "Mixed",
                    weight: "28.5",
                    dropoff: "Coastal Hub",
                    description: "Failed collection"
                }
            ]
        }
    }
];

// ============ GLOBAL VARIABLES ============
let currentFilter = 'all';
let currentSort = 'desc';
let searchTerm = '';

// DOM Elements
let listContainer, detailContainer, backBtn, pageTitle, timelineContainer, statsContainer;
let filterDropdownBtn, filterDropdownContent, searchInput, sortDescBtn, sortAscBtn;

// Detail view elements
let detailJobId, detailJobStatus, detailRequestId, detailProviderName, detailProviderAddress;
let detailProviderDate, detailCollector, detailVehicle, detailScheduled, detailTotalWeight;
let detailItemsCount, detailItemsList, detailTimeline, detailActionButtons;

// ============ INITIALIZATION ============
document.addEventListener('DOMContentLoaded', function() {
    console.log('aJobs.js loaded');
    initializeElements();
    initializeJobsPage();
});

function initializeElements() {
    listContainer = document.getElementById('jobsListContainer');
    detailContainer = document.getElementById('jobDetailContainer');
    backBtn = document.getElementById('backToListBtn');
    pageTitle = document.getElementById('pageTitle');
    timelineContainer = document.getElementById('timelineContainer');
    statsContainer = document.getElementById('statsContainer');
    filterDropdownBtn = document.getElementById('filterDropdownBtn');
    filterDropdownContent = document.getElementById('filterDropdownContent');
    searchInput = document.getElementById('searchInput');
    sortDescBtn = document.getElementById('sortDescBtn');
    sortAscBtn = document.getElementById('sortAscBtn');

    // Detail view elements
    detailJobId = document.getElementById('detailJobId');
    detailJobStatus = document.getElementById('detailJobStatus');
    detailRequestId = document.getElementById('detailRequestId');
    detailProviderName = document.getElementById('detailProviderName');
    detailProviderAddress = document.getElementById('detailProviderAddress');
    detailProviderDate = document.getElementById('detailProviderDate');
    detailCollector = document.getElementById('detailCollector');
    detailVehicle = document.getElementById('detailVehicle');
    detailScheduled = document.getElementById('detailScheduled');
    detailTotalWeight = document.getElementById('detailTotalWeight');
    detailItemsCount = document.getElementById('detailItemsCount');
    detailItemsList = document.getElementById('detailItemsList');
    detailTimeline = document.getElementById('detailTimeline');
    detailActionButtons = document.getElementById('detailActionButtons');

    console.log('Elements initialized');
}

function initializeJobsPage() {
    console.log('Initializing Jobs Page...');
    if (!statsContainer) {
        console.error('Stats container not found!');
        return;
    }
    renderStats();
    setupEventListeners();
    renderListView();
}

// ============ EVENT LISTENERS ============
function setupEventListeners() {
    if (!filterDropdownBtn || !filterDropdownContent || !searchInput || !sortDescBtn || !sortAscBtn || !backBtn) {
        console.error('Some elements not found for event listeners');
        return;
    }

    // Filter dropdown toggle
    filterDropdownBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        filterDropdownContent.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        filterDropdownContent.classList.remove('show');
    });

    // Filter dropdown items
    document.querySelectorAll('.filter-dropdown-content a').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const filter = this.dataset.filter;
            setActiveFilter(filter);
            filterDropdownContent.classList.remove('show');
        });
    });

    // Search input
    searchInput.addEventListener('input', function() {
        searchTerm = this.value.toLowerCase();
        renderListView();
    });

    // Sort buttons - now with slider styling
    sortDescBtn.addEventListener('click', function() {
        currentSort = 'desc';
        sortDescBtn.classList.add('active');
        sortAscBtn.classList.remove('active');
        renderListView();
    });

    sortAscBtn.addEventListener('click', function() {
        currentSort = 'asc';
        sortAscBtn.classList.add('active');
        sortDescBtn.classList.remove('active');
        renderListView();
    });

    // Back button
    backBtn.addEventListener('click', function() {
        renderListView();
    });

    // Set initial active sort
    sortDescBtn.classList.add('active');
}

function setActiveFilter(filter) {
    currentFilter = filter;
    
    // Update dropdown button text
    const filterText = document.querySelector(`.filter-dropdown-content a[data-filter="${filter}"]`).textContent;
    document.getElementById('selectedFilter').textContent = filterText.trim();
    
    // Update active class
    document.querySelectorAll('.filter-dropdown-content a').forEach(a => {
        a.classList.remove('active-filter');
    });
    document.querySelector(`.filter-dropdown-content a[data-filter="${filter}"]`).classList.add('active-filter');
    
    renderListView();
}

// ============ RENDERING FUNCTIONS ============
function renderStats() {
    if (!statsContainer) return;

    const preExecJobs = jobsData.filter(j => j.stage === 'pre-execution').length;
    const execJobs = jobsData.filter(j => j.stage === 'execution').length;
    const resolutionJobs = jobsData.filter(j => j.stage === 'resolution').length;

    statsContainer.innerHTML = `
        <div class="stat-card-modern">
            <div class="stat-icon-modern"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-content-modern">
                <div class="stat-value-modern">${preExecJobs}</div>
                <div class="stat-label-modern">Pre-Execution</div>
            </div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-icon-modern"><i class="fas fa-truck"></i></div>
            <div class="stat-content-modern">
                <div class="stat-value-modern">${execJobs}</div>
                <div class="stat-label-modern">Execution</div>
            </div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-icon-modern"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content-modern">
                <div class="stat-value-modern">${resolutionJobs}</div>
                <div class="stat-label-modern">Resolution</div>
            </div>
        </div>
    `;
}

function getFilteredAndSortedJobs() {
    let filtered = jobsData;

    // Apply status filter
    if (currentFilter !== 'all') {
        filtered = filtered.filter(job => job.status === currentFilter);
    }

    // Apply search filter
    if (searchTerm) {
        filtered = filtered.filter(job => 
            job.id.toLowerCase().includes(searchTerm) ||
            job.providerName.toLowerCase().includes(searchTerm) ||
            job.collector.toLowerCase().includes(searchTerm) ||
            job.requestId.toLowerCase().includes(searchTerm)
        );
    }

    // Apply sorting
    filtered.sort((a, b) => {
        const dateA = new Date(a.datetime).getTime();
        const dateB = new Date(b.datetime).getTime();
        return currentSort === 'desc' ? dateB - dateA : dateA - dateB;
    });

    return filtered;
}

function groupJobsByStage(jobs) {
    const groups = {
        pre: {
            title: 'Pre-Execution',
            icon: 'fas fa-hourglass-half',
            jobs: jobs.filter(j => j.stage === 'pre-execution')
        },
        execution: {
            title: 'Execution',
            icon: 'fas fa-truck',
            jobs: jobs.filter(j => j.stage === 'execution')
        },
        resolution: {
            title: 'Resolution',
            icon: 'fas fa-flag-checkered',
            jobs: jobs.filter(j => j.stage === 'resolution')
        }
    };
    return groups;
}

function renderListView() {
    if (!listContainer || !detailContainer || !backBtn || !pageTitle || !timelineContainer) return;

    listContainer.style.display = 'block';
    detailContainer.style.display = 'none';
    backBtn.style.display = 'none';
    pageTitle.textContent = 'Jobs';

    const filteredJobs = getFilteredAndSortedJobs();
    
    if (filteredJobs.length === 0) {
        timelineContainer.innerHTML = `
            <div class="no-jobs-modern">
                <i class="fas fa-search"></i>
                <p>No jobs found</p>
            </div>
        `;
        return;
    }

    const groupedJobs = groupJobsByStage(filteredJobs);
    let timelineHtml = '';

    if (groupedJobs.pre.jobs.length > 0) {
        timelineHtml += renderStage(groupedJobs.pre.title, groupedJobs.pre.icon, groupedJobs.pre.jobs);
    }

    if (groupedJobs.execution.jobs.length > 0) {
        timelineHtml += renderStage(groupedJobs.execution.title, groupedJobs.execution.icon, groupedJobs.execution.jobs);
    }

    if (groupedJobs.resolution.jobs.length > 0) {
        timelineHtml += renderStage(groupedJobs.resolution.title, groupedJobs.resolution.icon, groupedJobs.resolution.jobs);
    }

    timelineContainer.innerHTML = timelineHtml;

    document.querySelectorAll('.job-card-modern').forEach(card => {
        card.addEventListener('click', function() {
            const jobId = this.dataset.jobId;
            showJobDetail(jobId);
        });
    });
}

function renderStage(title, icon, jobs) {
    const statusGroups = {
        pending: jobs.filter(j => j.status === 'pending'),
        accepted: jobs.filter(j => j.status === 'accepted'),
        rejected: jobs.filter(j => j.status === 'rejected'),
        ongoing: jobs.filter(j => j.status === 'ongoing'),
        delayed: jobs.filter(j => j.status === 'delayed'),
        pickedup: jobs.filter(j => j.status === 'pickedup'),
        completed: jobs.filter(j => j.status === 'completed'),
        cancelled: jobs.filter(j => j.status === 'cancelled'),
        failed: jobs.filter(j => j.status === 'failed')
    };

    let statusCardsHtml = '';

    if (title === 'Pre-Execution') {
        statusCardsHtml += renderStatusCard('Pending', statusGroups.pending);
        statusCardsHtml += renderStatusCard('Accepted', statusGroups.accepted);
        statusCardsHtml += renderStatusCard('Rejected', statusGroups.rejected);
    } else if (title === 'Execution') {
        statusCardsHtml += renderStatusCard('Ongoing', statusGroups.ongoing);
        statusCardsHtml += renderStatusCard('Delayed', statusGroups.delayed);
        statusCardsHtml += renderStatusCard('Picked Up', statusGroups.pickedup);
    } else if (title === 'Resolution') {
        statusCardsHtml += renderStatusCard('Completed', statusGroups.completed);
        statusCardsHtml += renderStatusCard('Cancelled', statusGroups.cancelled);
        statusCardsHtml += renderStatusCard('Failed', statusGroups.failed);
    }

    return `
        <div class="timeline-stage">
            <div class="stage-header-modern">
                <div class="stage-icon-modern"><i class="${icon}"></i></div>
                <h2>${title}</h2>
                <div class="stage-progress">
                    <i class="fas fa-clipboard-list"></i> ${jobs.length} jobs
                </div>
            </div>
            <div class="status-cards-grid">
                ${statusCardsHtml}
            </div>
        </div>
    `;
}

function renderStatusCard(statusName, jobs) {
    if (jobs.length === 0) return '';

    let jobsHtml = '';
    jobs.forEach(job => {
        jobsHtml += renderJobCard(job);
    });

    return `
        <div class="status-card-modern">
            <div class="status-header-modern">
                <h3>${statusName}</h3>
                <span class="status-count-modern">${jobs.length}</span>
            </div>
            <div class="job-cards-modern">
                ${jobsHtml}
            </div>
        </div>
    `;
}

function renderJobCard(job) {
    const date = new Date(job.datetime).toLocaleString('en-MY', { 
        day: '2-digit', 
        month: 'short', 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    
    const collectorDisplay = job.collector === 'Not assigned' ? 'Not assigned' : job.collector;
    const timeString = new Date(job.datetime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    let reasonBadge = '';
    if (job.rejectReason) {
        reasonBadge = `<span class="reason-badge">${job.rejectReason.substring(0, 20)}...</span>`;
    } else if (job.cancelReason) {
        reasonBadge = `<span class="reason-badge">${job.cancelReason.substring(0, 20)}...</span>`;
    } else if (job.failReason) {
        reasonBadge = `<span class="reason-badge">${job.failReason.substring(0, 20)}...</span>`;
    } else if (job.delayReason) {
        reasonBadge = `<span class="reason-badge">${job.delayReason.substring(0, 20)}...</span>`;
    }

    return `
        <div class="job-card-modern" data-job-id="${job.id}">
            <div class="job-card-header-modern">
                <span class="job-id-modern">${job.id}</span>
                <span class="job-badge-modern ${job.status}">${job.status}</span>
            </div>
            ${reasonBadge ? `<div class="job-reason">${reasonBadge}</div>` : ''}
            <div class="job-info-modern">
                <span class="job-info-tag"><i class="fas fa-hashtag"></i> Req: ${job.requestId}</span>
                <span class="job-info-tag"><i class="fas fa-user"></i> ${job.providerName}</span>
                <span class="job-info-tag"><i class="fas fa-truck"></i> ${collectorDisplay}</span>
                <span class="job-info-tag"><i class="fas fa-calendar"></i> ${date}</span>
                <span class="job-info-tag"><i class="fas fa-box"></i> ${job.itemCount} items</span>
                <span class="job-info-tag"><i class="fas fa-weight-hanging"></i> ${job.totalWeight} kg</span>
            </div>
            <div class="job-details-row">
                <span><i class="fas fa-map-marker-alt"></i> ${job.address.split(',')[0]}</span>
                <span><i class="fas fa-clock"></i> ${timeString}</span>
            </div>
        </div>
    `;
}

// ============ JOB DETAIL VIEW ============
function showJobDetail(jobId) {
    const job = jobsData.find(j => j.id === jobId);
    if (!job) return;

    listContainer.style.display = 'none';
    detailContainer.style.display = 'block';
    backBtn.style.display = 'flex';
    pageTitle.textContent = `Job Details`;

    const jobData = job.fullData;

    detailJobId.textContent = job.id;
    detailJobStatus.textContent = job.status;
    detailJobStatus.className = `detail-status-modern ${job.status}`;
    detailRequestId.textContent = job.requestId;
    detailProviderName.textContent = jobData.provider.name;
    detailProviderAddress.textContent = jobData.provider.address;
    detailProviderDate.textContent = jobData.provider.date;
    detailCollector.textContent = job.collector;
    detailVehicle.textContent = job.vehicle;
    detailScheduled.textContent = new Date(job.datetime).toLocaleString();
    detailTotalWeight.textContent = `${job.totalWeight} kg`;
    detailItemsCount.textContent = jobData.items.length;

    let itemsHtml = '';
    jobData.items.forEach((item, index) => {
        // Sample image URLs 
        const itemImages = [
            { url: '/main/assets/images/sample-item1.jpg', name: 'Front view' },
            { url: '/main/assets/images/sample-item2.jpg', name: 'Back view' },
            { url: '/main/assets/images/sample-item3.jpg', name: 'Label' }
        ].filter(img => img.url);
        
        itemsHtml += `
            <div class="job-detail-item-card" data-item-id="${item.id}">
                <div class="job-detail-item-header">
                    <span class="job-detail-item-id">${item.id}</span>
                    <span class="job-detail-item-weight"><i class="fas fa-weight-hanging"></i> ${item.weight} kg</span>
                </div>
                
                <div class="job-detail-item-name">
                    <i class="fas fa-box"></i> ${item.name}
                </div>
                
                <div class="job-detail-item-brand">
                    <span class="brand-label">Model/Brand:</span>
                    <span class="brand-value">${item.brand}</span>
                </div>
                
                <div class="job-detail-item-action-row">
                    <!-- Description first -->
                    <div class="job-detail-item-description">
                        <div class="description-label">
                            <i class="fas fa-pen"></i> Description
                        </div>
                        <div class="description-text">
                            ${item.description}
                        </div>
                    </div>
                    
                    <div class="job-detail-item-image-wrapper">
                        ${itemImages.length > 0 ? `
                            <button class="view-image-btn" onclick="openLightbox(${index}, 0)">
                                <i class="fas fa-image"></i> View Image
                            </button>
                        ` : `
                            <div class="no-images">
                                <i class="fas fa-image"></i> No image
                            </div>
                        `}
                    </div>
                </div>
                
                <!-- Drop-off Information -->
                <div class="job-detail-item-details">
                    <div class="job-detail-item-detail">
                        <span class="detail-label">Drop-off</span>
                        <span class="detail-value"><i class="fas fa-location-dot"></i> ${item.dropoff}</span>
                    </div>
                </div>
            </div>
        `;
    });
    detailItemsList.innerHTML = itemsHtml;

    // Initialize timelineHtml as an empty string
    let timelineHtml = '';

    timelineHtml += `
        <div class="timeline-item-modern">
            <div class="timeline-marker-modern"></div>
            <div class="timeline-content-modern" style="cursor: default;">
                <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(job.datetime).toLocaleString()}</div>
                <div class="timeline-desc-modern" style="margin-top: 0.5rem;"><i class="fas fa-paper-plane"></i> Request created</div>
            </div>
        </div>
    `;

    if (job.status === 'accepted') {
        timelineHtml += `
            <div class="timeline-item-modern">
                <div class="timeline-marker-modern" style="background: var(--MainBlue);"></div>
                <div class="timeline-content-modern" style="cursor: default;">
                    <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(new Date(job.datetime).getTime() - 24*60*60000).toLocaleString()}</div>
                    <div class="timeline-desc-modern" style="margin-top: 0.5rem;"><i class="fas fa-check-circle"></i> Accepted & assigned to ${job.collector}</div>
                </div>
            </div>
        `;
    } else if (job.status === 'rejected') {
        timelineHtml += `
            <div class="timeline-item-modern">
                <div class="timeline-marker-modern" style="background: #b83e3e;"></div>
                <div class="timeline-content-modern" style="cursor: default;">
                    <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(new Date(job.datetime).getTime() + 24*60*60000).toLocaleString()}</div>
                    <div class="timeline-desc-modern" style="margin-top: 0.5rem;"><i class="fas fa-times-circle"></i> Rejected: ${job.rejectReason || 'Unacceptable items'}</div>
                </div>
            </div>
        `;
    } else if (job.status === 'cancelled') {
        timelineHtml += `
            <div class="timeline-item-modern">
                <div class="timeline-marker-modern" style="background: #666666;"></div>
                <div class="timeline-content-modern" style="cursor: default;">
                    <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(new Date(job.datetime).getTime() + 12*60*60000).toLocaleString()}</div>
                    <div class="timeline-desc-modern" style="margin-top: 0.5rem;"><i class="fas fa-ban"></i> Cancelled: ${job.cancelReason || 'Provider cancelled'}</div>
                </div>
            </div>
        `;
    }

    if (job.status === 'ongoing' || job.status === 'delayed' || job.status === 'pickedup' || job.status === 'completed') {
        timelineHtml += `
            <div class="timeline-item-modern">
                <div class="timeline-marker-modern"></div>
                <div class="timeline-content-modern" style="cursor: default;">
                    <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(job.datetime).toLocaleTimeString()}</div>
                    <div class="timeline-desc-modern" style="margin-top: 0.5rem;"><i class="fas fa-truck"></i> Collection started</div>
                </div>
            </div>
        `;
    }

    if (job.status === 'delayed' && job.delayReason) {
        timelineHtml += `
            <div class="timeline-item-modern">
                <div class="timeline-marker-modern" style="background: #b55f0e;"></div>
                <div class="timeline-content-modern" style="cursor: default;">
                    <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(new Date(job.datetime).getTime() + 75*60000).toLocaleTimeString()}</div>
                    <div class="timeline-desc-modern" style="margin-top: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Delayed: ${job.delayReason}</div>
                </div>
            </div>
        `;
    }

    if (job.status === 'pickedup' || job.status === 'completed') {
        timelineHtml += `
            <div class="timeline-item-modern">
                <div class="timeline-marker-modern"></div>
                <div class="timeline-content-modern" style="cursor: default;">
                    <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(new Date(job.datetime).getTime() + 150*60000).toLocaleTimeString()}</div>
                    <div class="timeline-desc-modern" style="margin-top: 0.5rem;"><i class="fas fa-check-circle"></i> Items picked up</div>
                </div>
            </div>
        `;
    }

    if (job.status === 'completed') {
        timelineHtml += `
            <div class="timeline-item-modern">
                <div class="timeline-marker-modern" style="background: #1f6c2f;"></div>
                <div class="timeline-content-modern" style="cursor: default;">
                    <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${job.completedAt ? new Date(job.completedAt).toLocaleString() : new Date(new Date(job.datetime).getTime() + 240*60000).toLocaleString()}</div>
                    <div class="timeline-desc-modern" style="margin-top: 0.5rem;"><i class="fas fa-flag-checkered"></i> Job completed</div>
                </div>
            </div>
        `;
    }

    if (job.status === 'failed') {
        timelineHtml += `
            <div class="timeline-item-modern">
                <div class="timeline-marker-modern" style="background: #b83e3e;"></div>
                <div class="timeline-content-modern" style="cursor: default;">
                    <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(new Date(job.datetime).getTime() + 180*60000).toLocaleTimeString()}</div>
                    <div class="timeline-desc-modern" style="margin-top: 0.5rem;"><i class="fas fa-times-circle"></i> Failed: ${job.failReason || 'Collection failed'}</div>
                </div>
            </div>
        `;
    }

    detailTimeline.innerHTML = timelineHtml;

    console.log('detailActionButtons element:', detailActionButtons);

    if (!detailActionButtons) {
        console.error('detailActionButtons not found!');
        detailActionButtons = document.getElementById('detailActionButtons');
    }

    let buttonsHtml = `
        <button class="btn-modern-outline" id="reportIssueBtn">
            <i class="fas fa-flag"></i> Report Issue
        </button>
    `;

    if (job.status === 'failed' && job.failReason && job.failReason.includes('handover')) {
        buttonsHtml += `
            <button class="btn-modern-primary" id="reassignJobBtn" style="background: #ff6b6b;">
                <i class="fas fa-exchange-alt"></i> Reassign Job
            </button>
        `;
    }

    // Clear and set the HTML
    detailActionButtons.innerHTML = '';
    detailActionButtons.insertAdjacentHTML('beforeend', buttonsHtml);

    // Verify the button was added and attach event listener
    setTimeout(() => {
        const reportBtn = document.getElementById('reportIssueBtn');
        console.log('Report button exists:', !!reportBtn);
        if (reportBtn) {
            reportBtn.addEventListener('click', () => {
                showReportIssueModal(job);
            });
        }
    }, 50);

    document.getElementById('viewRequestBtn2')?.addEventListener('click', () => {
        alert(`Viewing request ${job.requestId}`);
    });

    document.getElementById('viewRequestBtn')?.addEventListener('click', () => {
        alert(`Viewing request ${job.requestId}`);
    });

    const reassignBtn = document.getElementById('reassignJobBtn');
    if (reassignBtn) {
        reassignBtn.addEventListener('click', () => {
            showReassignModal(job);
        });
    }
}

function showReassignModal(job) {
    const collectors = ['Ahmad Bin Yusof', 'Siti Nurhaliza', 'Mei Ling', 'Tan Sri Aziz', 'Vincent Wong', 'Hassan Osman'];
    const vehicles = ['Toyota Hiace (VH23)', 'Isuzu NLR (VH07)', 'Mitsubishi L300 (VH09)', 'Nissan NV350 (VH33)', 'Hilux (VH05)', 'Daihatsu (VH41)'];
    
    const modalHtml = `
        <div class="reassign-modal" id="reassignModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Reassign Job: ${job.id}</h3>
                    <button class="modal-close" onclick="closeReassignModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p><strong>Reason for handover:</strong> ${job.failReason || 'Handover required'}</p>
                    
                    <div class="form-group">
                        <label>Select New Collector:</label>
                        <select id="newCollectorSelect">
                            <option value="">-- Select Collector --</option>
                            ${collectors.map(c => `<option value="${c}">${c}</option>`).join('')}
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Select New Vehicle:</label>
                        <select id="newVehicleSelect">
                            <option value="">-- Select Vehicle --</option>
                            ${vehicles.map(v => `<option value="${v}">${v}</option>`).join('')}
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Notes:</label>
                        <textarea id="reassignNotes" placeholder="Add any notes for the new collector..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-modern-outline" onclick="closeReassignModal()">Cancel</button>
                    <button class="btn-modern-primary" onclick="confirmReassign('${job.id}')">Confirm Reassignment</button>
                </div>
            </div>
        </div>
    `;
    
    const modalContainer = document.createElement('div');
    modalContainer.innerHTML = modalHtml;
    document.body.appendChild(modalContainer.firstChild);
    
    document.getElementById('reassignModal').style.display = 'flex';
}

window.closeReassignModal = function() {
    const modal = document.getElementById('reassignModal');
    if (modal) {
        modal.remove();
    }
};

window.confirmReassign = function(jobId) {
    const collector = document.getElementById('newCollectorSelect')?.value;
    const vehicle = document.getElementById('newVehicleSelect')?.value;
    const notes = document.getElementById('reassignNotes')?.value;
    
    if (!collector || !vehicle) {
        alert('Please select both collector and vehicle');
        return;
    }
    
    alert(`Job ${jobId} reassigned to ${collector} (${vehicle})\nNotes: ${notes || 'None'}`);
    closeReassignModal();
    renderListView();
};

// Image Lightbox functionality
let currentLightboxIndex = 0;
let currentItemIndex = 0;
let lightboxImages = [];

function openLightbox(itemIndex, imageIndex) {

    const sampleImages = [
        { url: '/main/assets/images/sample-item1.jpg', name: 'Front view' },
        { url: '/main/assets/images/sample-item2.jpg', name: 'Back view' },
        { url: '/main/assets/images/sample-item3.jpg', name: 'Label' }
    ].filter(img => img.url);
    
    lightboxImages = sampleImages;
    currentItemIndex = itemIndex;
    currentLightboxIndex = imageIndex;
    
    // Create lightbox if it doesn't exist
    let lightbox = document.getElementById('imageLightbox');
    if (!lightbox) {
        lightbox = document.createElement('div');
        lightbox.id = 'imageLightbox';
        lightbox.className = 'image-lightbox';
        lightbox.innerHTML = `
            <div class="lightbox-content">
                <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
                <button class="lightbox-nav prev" onclick="navigateLightbox(-1)">❮</button>
                <button class="lightbox-nav next" onclick="navigateLightbox(1)">❯</button>
                <img id="lightboxImage" src="" alt="Item image">
                <div class="lightbox-counter" id="lightboxCounter"></div>
            </div>
        `;
        document.body.appendChild(lightbox);
    }
    
    updateLightboxImage();
    lightbox.classList.add('active');
}

function closeLightbox() {
    const lightbox = document.getElementById('imageLightbox');
    if (lightbox) {
        lightbox.classList.remove('active');
    }
}

function navigateLightbox(direction) {
    currentLightboxIndex += direction;
    if (currentLightboxIndex < 0) {
        currentLightboxIndex = lightboxImages.length - 1;
    } else if (currentLightboxIndex >= lightboxImages.length) {
        currentLightboxIndex = 0;
    }
    updateLightboxImage();
}

function updateLightboxImage() {
    const lightboxImg = document.getElementById('lightboxImage');
    const counter = document.getElementById('lightboxCounter');
    
    if (lightboxImg && lightboxImages.length > 0) {
        lightboxImg.src = lightboxImages[currentLightboxIndex].url;
        lightboxImg.alt = lightboxImages[currentLightboxIndex].name;
        
        if (counter) {
            counter.textContent = `${currentLightboxIndex + 1} / ${lightboxImages.length}`;
        }
    }
}

// Close lightbox with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    } else if (e.key === 'ArrowLeft') {
        navigateLightbox(-1);
    } else if (e.key === 'ArrowRight') {
        navigateLightbox(1);
    }
});

// Report Issue Modal Function
function showReportIssueModal(job) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('reportIssueModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'reportIssueModal';
        modal.className = 'report-issue-modal';
        document.body.appendChild(modal);
    }
    
    // Get current date and time for default values
    const now = new Date();
    const dateStr = now.toISOString().split('T')[0];
    const timeStr = now.toTimeString().split(' ')[0].substring(0, 5);
    
    modal.innerHTML = `
        <div class="report-issue-content">
            <div class="report-issue-header">
                <h3><i class="fas fa-flag"></i> Report Issue - ${job.id}</h3>
                <button class="report-issue-close" onclick="closeReportIssueModal()">&times;</button>
            </div>
            <div class="report-issue-body">
                <form id="reportIssueForm">
                    <div class="issue-form-group">
                        <label><i class="fas fa-exclamation-triangle"></i> Issue Type</label>
                        <select id="issueType" required>
                            <option value="">-- Select Issue Type --</option>
                            <option value="collection">Collection Issue</option>
                            <option value="item">Item Damage/Missing</option>
                            <option value="provider">Provider Issue</option>
                            <option value="collector">Collector Issue</option>
                            <option value="vehicle">Vehicle Problem</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="issue-form-group">
                        <label><i class="fas fa-tag"></i> Priority</label>
                        <div class="issue-priority">
                            <label class="priority-option low">
                                <input type="radio" name="priority" value="low" checked> Low
                            </label>
                            <label class="priority-option medium">
                                <input type="radio" name="priority" value="medium"> Medium
                            </label>
                            <label class="priority-option high">
                                <input type="radio" name="priority" value="high"> High
                            </label>
                        </div>
                    </div>
                    
                    <div class="issue-form-group">
                        <label><i class="fas fa-heading"></i> Title</label>
                        <input type="text" id="issueTitle" placeholder="Brief summary of the issue" required>
                    </div>
                    
                    <div class="issue-form-group">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <textarea id="issueDescription" placeholder="Detailed description of the issue..." required></textarea>
                    </div>
                    
                    <div class="issue-form-group">
                        <label><i class="fas fa-calendar"></i> Date & Time</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="date" id="issueDate" value="${dateStr}" style="flex: 1;" required>
                            <input type="time" id="issueTime" value="${timeStr}" style="flex: 1;" required>
                        </div>
                    </div>
                    
                    <div class="issue-form-group">
                        <label><i class="fas fa-user"></i> Reported By</label>
                        <input type="text" id="reportedBy" value="Admin" required>
                    </div>
                </form>
            </div>
            <div class="report-issue-footer">
                <button class="btn-modern-outline" onclick="closeReportIssueModal()">Cancel</button>
                <button class="btn-modern-primary" onclick="submitIssueReport('${job.id}')">Submit Report</button>
            </div>
        </div>
    `;
    
    modal.classList.add('active');
    
    // Add click event to priority options for visual feedback
    document.querySelectorAll('.priority-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.priority-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            this.classList.add('selected');
        });
    });
    
    // Close when clicking outside the modal
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeReportIssueModal();
        }
    });
}

// Close report issue modal - FIXED
window.closeReportIssueModal = function() {
    const modal = document.getElementById('reportIssueModal');
    if (modal) {
        modal.classList.remove('active');
    }
};

// Submit issue report
window.submitIssueReport = function(jobId) {
    // Get form values
    const issueType = document.getElementById('issueType')?.value;
    const priority = document.querySelector('input[name="priority"]:checked')?.value;
    const title = document.getElementById('issueTitle')?.value;
    const description = document.getElementById('issueDescription')?.value;
    const date = document.getElementById('issueDate')?.value;
    const time = document.getElementById('issueTime')?.value;
    const reportedBy = document.getElementById('reportedBy')?.value;
    
    // Validate form
    if (!issueType || !title || !description) {
        alert('Please fill in all required fields');
        return;
    }
    
    // Create report object
    const report = {
        jobId: jobId,
        issueType: issueType,
        priority: priority,
        title: title,
        description: description,
        dateTime: `${date} ${time}`,
        reportedBy: reportedBy,
        reportedAt: new Date().toISOString(),
        status: 'pending'
    };
    
    console.log('Issue Report Submitted:', report);
    
    alert(`Issue report submitted successfully!\n\nReport ID: ${generateReportId()}\nIssue: ${title}\nPriority: ${priority}`);
    
    closeReportIssueModal();
};

function generateReportId() {
    return 'ISS' + Math.floor(Math.random() * 1000).toString().padStart(3, '0');
}

function makeTimelineExpandable() {
    console.log('Timeline displayed in simple mode');
}

window.initializeJobsPage = initializeJobsPage;