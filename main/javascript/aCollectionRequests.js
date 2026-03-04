document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Initializing Collection Requests Page');
    
    const collectionRequests = [
        {
            id: 'REQ002',
            title: 'Desktop PC, printer',
            items: ['Desktop PC', 'Printer', 'Scanner'],
            status: 'scheduled',
            provider: 'Sarah Tan',
            providerContact: '+60 12-345 6789',
            date: '23 Feb 2026',
            scheduledDate: '25 Feb 2026',
            scheduledTime: '10:00 AM - 12:00 PM',
            weight: '15.0',
            address: 'No 45, Jalan University, Petaling Jaya, 47300',
            description: 'Old desktop computer setup, printer needs repair. All items are in working condition but outdated.',
            brand: 'HP Pavilion, Canon Pixma',
            condition: 'Working but outdated',
            assignedCollector: 'Ahmad Bin Abdullah',
            assignedVehicle: 'Truck - VHL001',
            completionDate: null
        },
        {
            id: 'REQ004',
            title: 'CRT TV (broken)',
            items: ['CRT TV', 'Remote', 'Cables'],
            status: 'completed',
            provider: 'Natasha Wong',
            providerContact: '+60 11-234 5678',
            date: '20 Feb 2026',
            completedDate: '22 Feb 2026',
            completionTime: '14:30',
            weight: '24.5',
            address: 'No 12, Jalan SS2/72, Petaling Jaya, 47300',
            description: 'Old CRT television, not turning on. Screen has some discoloration.',
            brand: 'Sony Trinitron',
            condition: 'Not working',
            collector: 'Raj Kumar',
            vehicle: 'Van - VHL003',
            completionNotes: 'Successfully collected. Item will be processed for recycling.'
        },
        {
            id: 'REQ005',
            title: 'Server rack parts',
            items: ['Server Rack', 'Power Supply', 'Cables', 'Cooling Fans'],
            status: 'rejected',
            provider: 'James Lee',
            providerContact: '+60 16-789 0123',
            date: '19 Feb 2026',
            weight: '42.0',
            address: 'No 89, Jalan Industri, Shah Alam, 40200',
            description: 'Enterprise server equipment, contains hazardous materials including batteries and capacitors.',
            brand: 'Dell PowerEdge',
            condition: 'Parts only',
            rejectionReason: 'Contains hazardous materials requiring special handling. Please contact our hazardous waste department.',
            rejectionDate: '20 Feb 2026',
            rejectedBy: 'Admin - Michael Chen'
        },
        {
            id: 'REQ006',
            title: 'Battery collection',
            items: ['Car Battery (2x)', 'Phone Batteries (5x)', 'Power Bank (3x)'],
            status: 'cancelled',
            provider: 'Michelle Lim',
            providerContact: '+60 14-567 8901',
            date: '18 Feb 2026',
            weight: '3.5',
            address: 'No 3, Jalan Bukit, Bangsar, 59100',
            description: 'Mixed battery collection, some swollen and potentially dangerous.',
            brand: 'Various',
            condition: 'Used/Damaged',
            cancellationReason: 'Provider requested cancellation due to finding alternative disposal method.',
            cancellationDate: '19 Feb 2026',
            cancelledBy: 'Provider'
        },
        {
            id: 'REQ007',
            title: 'Refrigerator parts',
            items: ['Compressor', 'Shelving', 'Door Panels', 'Thermostat'],
            status: 'scheduled',
            provider: 'Ariff Faisal',
            providerContact: '+60 13-456 7890',
            date: '21 Feb 2026',
            scheduledDate: '26 Feb 2026',
            scheduledTime: '2:00 PM - 4:00 PM',
            weight: '35.0',
            address: 'No 67, Jalan Ampang, Kuala Lumpur, 50450',
            description: 'Disassembled refrigerator parts. Compressor still contains coolant.',
            brand: 'Panasonic',
            condition: 'Used - Partially working',
            assignedCollector: 'Zainal Abidin',
            assignedVehicle: 'Truck - VHL002',
            completionDate: null
        },
        {
            id: 'REQ008',
            title: 'Laptop bundle',
            items: ['Laptops (6 pcs)', 'Chargers', 'Docking Stations'],
            status: 'completed',
            provider: 'Siti Aishah',
            providerContact: '+60 12-987 6543',
            date: '15 Feb 2026',
            completedDate: '17 Feb 2026',
            completionTime: '11:15',
            weight: '12.8',
            address: 'No 23, Jalan Gasing, Petaling Jaya, 46000',
            description: 'Company laptops for recycling. Most are 5+ years old.',
            brand: 'Dell, Lenovo, HP',
            condition: 'Mixed conditions',
            collector: 'Raj Kumar',
            vehicle: 'Van - VHL003',
            completionNotes: 'All items collected. Will be sorted for parts recovery.'
        },
        {
            id: 'REQ009',
            title: 'Office Electronics Bulk',
            items: ['Monitors (5x)', 'Keyboards (8x)', 'Mice (8x)', 'Speakers (2x)'],
            status: 'ongoing',
            provider: 'Tech Solutions Sdn Bhd',
            providerContact: '+60 17-890 1234',
            date: '01 Mar 2026',
            scheduledDate: '04 Mar 2026',
            scheduledTime: '9:00 AM - 1:00 PM',
            weight: '28.5',
            address: 'Lot 12, Jalan Teknologi, Taman Sains, Selangor, 43300',
            description: 'Bulk office electronics from company upgrade. All items are used but functional.',
            brand: 'Dell, HP, Logitech',
            condition: 'Used - Working',
            assignedCollector: 'Rosli Bin Ahmad',
            assignedVehicle: 'Truck - VHL004',
            completionDate: null
        },
        {
            id: 'REQ010',
            title: 'Smartphones Collection',
            items: ['iPhone 6 (3x)', 'Samsung S7 (2x)', 'Chargers', 'Phone Cases'],
            status: 'ongoing',
            provider: 'Mobile Repairs KL',
            providerContact: '+60 12-345 6780',
            date: '02 Mar 2026',
            scheduledDate: '05 Mar 2026',
            scheduledTime: '2:00 PM - 4:30 PM',
            weight: '4.2',
            address: 'No 15, Jalan Alor, Bukit Bintang, Kuala Lumpur, 50200',
            description: 'Collection of old smartphones for parts recovery. Some have broken screens.',
            brand: 'Apple, Samsung',
            condition: 'Damaged/For Parts',
            assignedCollector: 'Mei Ling',
            assignedVehicle: 'Van - VHL005',
            completionDate: null
        },
        {
            id: 'REQ011',
            title: 'Printoners and Scanners',
            items: ['Laser Printers (3x)', 'Inkjet Printers (2x)', 'Scanner (1x)', 'Ink Cartridges (15x)'],
            status: 'ongoing',
            provider: 'Creative Design Studio',
            providerContact: '+60 16-543 2109',
            date: '03 Mar 2026',
            scheduledDate: '06 Mar 2026',
            scheduledTime: '10:30 AM - 12:30 PM',
            weight: '45.0',
            address: 'No 8, Jalan SS15/4, Subang Jaya, 47500',
            description: 'Office printers and scanners being replaced. Includes unused ink cartridges.',
            brand: 'Brother, Canon, Epson',
            condition: 'Mixed - Some working, some not',
            assignedCollector: 'Kevin Tan',
            assignedVehicle: 'Truck - VHL001',
            completionDate: null
        }
    ];

    // DOM Elements
    const listView = document.getElementById('collectionListView');
    const detailView = document.getElementById('collectionDetailView');
    const backToListBtn = document.getElementById('backToListBtn');
    const timelineContainer = document.getElementById('timelineContainer');
    const kanbanContainer = document.getElementById('kanbanContainer');
    const emptyState = document.getElementById('emptyState');
    const searchInput = document.getElementById('searchInput');
    const sortSelect = document.getElementById('sortSelect');
    const toggleBtns = document.querySelectorAll('.toggle-btn');
    const filterChips = document.querySelectorAll('.filter-chip');
    const resultCountSpan = document.getElementById('resultCount');
    const totalCountSpan = document.getElementById('totalCount');
    const exportBtn = document.getElementById('exportBtn');
    const printBtn = document.getElementById('printDetailBtn');
    const shareBtn = document.getElementById('shareDetailBtn');
    
    // Stats elements
    const scheduledCount = document.getElementById('scheduledCount');
    const completedCount = document.getElementById('completedCount');
    const cancelledCount = document.getElementById('cancelledCount');
    const rejectedCount = document.getElementById('rejectedCount');
    
    // Kanban count elements
    const kanbanScheduledCount = document.getElementById('kanbanScheduledCount');
    const kanbanCompletedCount = document.getElementById('kanbanCompletedCount');
    const kanbanCancelledCount = document.getElementById('kanbanCancelledCount');
    const kanbanRejectedCount = document.getElementById('kanbanRejectedCount');

    // Detail view elements
    const detailStatus = document.getElementById('detailStatus');
    const detailRequestId = document.getElementById('detailRequestId');
    const detailTitle = document.getElementById('detailTitle');
    const detailProvider = document.getElementById('detailProvider');
    const detailRequestDate = document.getElementById('detailRequestDate');
    const detailWeight = document.getElementById('detailWeight');
    const detailItemCount = document.getElementById('detailItemCount');
    const detailItemsList = document.getElementById('detailItemsList');
    const detailDescription = document.getElementById('detailDescription');
    const detailBrand = document.getElementById('detailBrand');
    const detailCondition = document.getElementById('detailCondition');
    const detailAddress = document.getElementById('detailAddress');
    const mapLink = document.getElementById('mapLink');
    const timelineSteps = document.getElementById('timelineSteps');
    const assignmentCard = document.getElementById('assignmentCard');
    const assignmentInfo = document.getElementById('assignmentInfo');
    const detailFooter = document.getElementById('detailFooter');
    const detailNotes = document.getElementById('detailNotes');
    const statusSpecificStat = document.getElementById('statusSpecificStat');

    // Activity feed
    const activityFeed = document.getElementById('activityFeed');

    // Sort
    const sortDropdownBtn = document.getElementById('sortDropdownBtn');
    const sortDropdownContent = document.getElementById('sortDropdownContent');
    const selectedSortSpan = document.getElementById('selectedSort');

    // State
    let currentView = 'timeline';
    let currentFilter = 'all';
    let currentSearch = '';
    let currentSort = 'date-desc';
    let currentQuickFilter = 'all';
    let filteredRequests = [...collectionRequests];
    let currentRequest = null;

    if (sortDropdownBtn) {
        sortDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            sortDropdownContent.classList.toggle('show');
        });
    }

    document.addEventListener('click', () => {
        if (sortDropdownContent) {
            sortDropdownContent.classList.remove('show');
        }
    });

    document.querySelectorAll('.sort-dropdown-content a').forEach(option => {
        option.addEventListener('click', (e) => {
            e.preventDefault();
      
            document.querySelectorAll('.sort-dropdown-content a').forEach(a => {
                a.classList.remove('active-sort');
            });
            
            option.classList.add('active-sort');
            
            selectedSortSpan.textContent = option.textContent.trim();
            
            const sortValue = option.dataset.sort;
            
            currentSort = sortValue;
            filterRequests();
            renderCurrentView();
            
            sortDropdownContent.classList.remove('show');
        });
    });

    function updateStats() {
        const scheduled = collectionRequests.filter(r => r.status === 'scheduled').length;
        const ongoing = collectionRequests.filter(r => r.status === 'ongoing').length;
        const completed = collectionRequests.filter(r => r.status === 'completed').length;
        const cancelled = collectionRequests.filter(r => r.status === 'cancelled').length;
        const rejected = collectionRequests.filter(r => r.status === 'rejected').length;
        
        scheduledCount.textContent = scheduled;
        ongoingCount.textContent = ongoing;
        completedCount.textContent = completed;
        cancelledCount.textContent = cancelled;
        rejectedCount.textContent = rejected;
        
        hidePillIfNoUpdates('scheduled', scheduled);
        hidePillIfNoUpdates('ongoing', ongoing);
        hidePillIfNoUpdates('completed', completed);
        hidePillIfNoUpdates('cancelled', cancelled);
        hidePillIfNoUpdates('rejected', rejected);
        
        if (document.getElementById('kanbanScheduledCount')) {
            document.getElementById('kanbanScheduledCount').textContent = scheduled;
        }
        if (document.getElementById('kanbanOngoingCount')) {
            document.getElementById('kanbanOngoingCount').textContent = ongoing;
        }
        if (document.getElementById('kanbanCompletedCount')) {
            document.getElementById('kanbanCompletedCount').textContent = completed;
        }
        if (document.getElementById('kanbanCancelledCount')) {
            document.getElementById('kanbanCancelledCount').textContent = cancelled;
        }
        if (document.getElementById('kanbanRejectedCount')) {
            document.getElementById('kanbanRejectedCount').textContent = rejected;
        }
        
        totalCountSpan.textContent = `Total: ${collectionRequests.length}`;
    }

    function hidePillIfNoUpdates(status, count) {
        const statusPanel = document.querySelector(`.status-panel.${status}`);
        
        if (statusPanel) {
            const trendPill = statusPanel.querySelector('.panel-trend');
            
            if (count === 0) {
                if (trendPill) {
                    trendPill.style.display = 'none';
                }
            } else {
                if (trendPill) {
                    trendPill.style.display = 'flex'; 
                }
            }
        }
    }

    // Update activity feed
    function updateActivityFeed() {
        const activities = [];
        
        collectionRequests.slice(0, 5).forEach(req => {
            if (req.status === 'completed') {
                activities.push({
                    icon: 'fas fa-check-circle',
                    text: `Request #${req.id} completed`,
                    time: req.completedDate
                });
            } else if (req.status === 'scheduled') {
                activities.push({
                    icon: 'fas fa-calendar',
                    text: `Request #${req.id} scheduled`,
                    time: req.scheduledDate
                });
            }
        });
        
        activityFeed.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="${activity.icon}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">${activity.text}</div>
                    <div class="activity-time">${activity.time}</div>
                </div>
            </div>
        `).join('');
    }

    // Filter and sort functions
    function filterRequests() {
        filteredRequests = collectionRequests.filter(request => {
            // Status filter
            const matchesStatus = currentFilter === 'all' || request.status === currentFilter;
            
            // Search filter
            const searchTerm = currentSearch.toLowerCase();
            const matchesSearch = searchTerm === '' || 
                request.id.toLowerCase().includes(searchTerm) ||
                request.provider.toLowerCase().includes(searchTerm) ||
                request.title.toLowerCase().includes(searchTerm) ||
                request.items.some(item => item.toLowerCase().includes(searchTerm));
            
            // Quick filter
            let matchesQuickFilter = true;
            const today = new Date();
            const requestDate = new Date(request.date);
            
            switch(currentQuickFilter) {
                case 'this-week':
                    const weekAgo = new Date(today.setDate(today.getDate() - 7));
                    matchesQuickFilter = requestDate >= weekAgo;
                    break;
                case 'this-month':
                    const monthAgo = new Date(today.setMonth(today.getMonth() - 1));
                    matchesQuickFilter = requestDate >= monthAgo;
                    break;
                case 'high-weight':
                    matchesQuickFilter = parseFloat(request.weight) > 20;
                    break;
                case 'needs-review':
                    matchesQuickFilter = request.status === 'rejected' || request.status === 'cancelled';
                    break;
            }
            
            return matchesStatus && matchesSearch && matchesQuickFilter;
        });

        sortRequests();
        updateResultsCount();
        
        const scheduled = filteredRequests.filter(r => r.status === 'scheduled').length;
        const ongoing = filteredRequests.filter(r => r.status === 'ongoing').length;
        const completed = filteredRequests.filter(r => r.status === 'completed').length;
        const cancelled = filteredRequests.filter(r => r.status === 'cancelled').length;
        const rejected = filteredRequests.filter(r => r.status === 'rejected').length;
        
        hidePillIfNoUpdates('scheduled', scheduled);
        hidePillIfNoUpdates('ongoing', ongoing);
        hidePillIfNoUpdates('completed', completed);
        hidePillIfNoUpdates('cancelled', cancelled);
        hidePillIfNoUpdates('rejected', rejected);
    }

    function sortRequests() {
        filteredRequests.sort((a, b) => {
            switch(currentSort) {
                case 'date-desc':
                    return new Date(b.date) - new Date(a.date);
                case 'date-asc':
                    return new Date(a.date) - new Date(b.date);
                case 'weight-desc':
                    return parseFloat(b.weight) - parseFloat(a.weight);
                case 'weight-asc':
                    return parseFloat(a.weight) - parseFloat(b.weight);
                default:
                    return 0;
            }
        });
    }

    function updateResultsCount() {
        resultCountSpan.textContent = `Showing ${filteredRequests.length} of ${collectionRequests.length} requests`;
    }

    function renderTimeline() {
        console.log('renderTimeline called, filteredRequests:', filteredRequests.length);
        
        if (filteredRequests.length === 0) {
            timelineContainer.innerHTML = '';
            emptyState.classList.remove('hidden');
            console.log('No requests, showing empty state');
            return;
        }

        emptyState.classList.add('hidden');
        
        let html = '';
        filteredRequests.forEach((request, index) => {
            const isLast = index === filteredRequests.length - 1;
            html += `
                <div class="timeline-item" data-req="${request.id}">
                    <div class="timeline-marker">
                        <div class="marker-dot ${request.status}"></div>
                        ${!isLast ? '<div class="marker-line"></div>' : ''}
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-main">
                            <h3>#${request.id} - ${request.title}</h3>
                            <p>${request.provider}</p>
                            <div class="timeline-tags">
                                <span class="timeline-tag"><i class="fas fa-weight-hanging"></i> ${request.weight} kg</span>
                                <span class="timeline-tag"><i class="fas fa-box"></i> ${request.items.length} items</span>
                            </div>
                        </div>
                        <div class="timeline-meta">
                            <span class="timeline-date">${request.date}</span>
                            <span class="timeline-status ${request.status}">${request.status}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        timelineContainer.innerHTML = html;
        console.log('Timeline HTML set, items:', filteredRequests.length);
        
        document.querySelectorAll('.timeline-item').forEach(item => {
            item.addEventListener('click', () => {
                const reqId = item.dataset.req;
                const request = collectionRequests.find(r => r.id === reqId);
                if (request) {
                    showDetail(request);
                }
            });
        });
    }

    // Render kanban view
    function renderKanban() {
        const kanbanScheduled = document.getElementById('kanbanScheduled');
        const kanbanCompleted = document.getElementById('kanbanCompleted');
        const kanbanCancelled = document.getElementById('kanbanCancelled');
        const kanbanRejected = document.getElementById('kanbanRejected');
        
        kanbanScheduled.innerHTML = '';
        kanbanCompleted.innerHTML = '';
        kanbanCancelled.innerHTML = '';
        kanbanRejected.innerHTML = '';
        
        filteredRequests.forEach(request => {
            const card = `
                <div class="kanban-card" data-req="${request.id}">
                    <div class="kanban-card-header">
                        <span class="kanban-id">#${request.id}</span>
                        <span class="kanban-weight">${request.weight} kg</span>
                    </div>
                    <div class="kanban-title">${request.title}</div>
                    <div class="kanban-provider">
                        <i class="fas fa-user"></i> ${request.provider}
                    </div>
                </div>
            `;
            
            switch(request.status) {
                case 'scheduled':
                    kanbanScheduled.innerHTML += card;
                    break;
                case 'ongoing':
                    break;
                case 'completed':
                    kanbanCompleted.innerHTML += card;
                    break;
                case 'cancelled':
                    kanbanCancelled.innerHTML += card;
                    break;
                case 'rejected':
                    kanbanRejected.innerHTML += card;
                    break;
            }
        });
        
        document.querySelectorAll('.kanban-card').forEach(card => {
            card.addEventListener('click', () => {
                const reqId = card.dataset.req;
                const request = collectionRequests.find(r => r.id === reqId);
                if (request) {
                    showDetail(request);
                }
            });
        });
    }

    // Show detail view
function showDetail(request) {
    currentRequest = request;
    listView.classList.add('hidden');
    detailView.classList.remove('hidden');
    
    // Update hero section
    detailStatus.textContent = request.status.charAt(0).toUpperCase() + request.status.slice(1);
    detailStatus.className = `hero-badge ${request.status}`;
    detailRequestId.textContent = `#${request.id}`;
    detailTitle.textContent = request.title;
    detailProvider.innerHTML = `
        <i class="fas fa-user-circle"></i>
        <div>
            <strong>${request.provider}</strong>
            <span>${request.providerContact}</span>
        </div>
    `;
    
    // Update stats
    detailRequestDate.textContent = request.date;
    detailWeight.textContent = `${request.weight} kg`;
    detailItemCount.textContent = `${request.items.length} items`;
    
    // Status specific stat
    let statusStatHtml = '';
    switch(request.status) {
        case 'scheduled':
            statusStatHtml = `
                <i class="fas fa-calendar-check"></i>
                <div>
                    <span class="stat-label">Scheduled</span>
                    <span class="stat-value">${request.scheduledDate || 'TBD'}</span>
                </div>
            `;
            break;
        case 'ongoing':
            statusStatHtml = `
                <i class="fas fa-sync-alt"></i>
                <div>
                    <span class="stat-label">Ongoing</span>
                    <span class="stat-value">${request.scheduledDate || 'In progress'}</span>
                </div>
            `;
            break;
        case 'completed':
            statusStatHtml = `
                <i class="fas fa-check-circle"></i>
                <div>
                    <span class="stat-label">Completed</span>
                    <span class="stat-value">${request.completedDate || request.date}</span>
                </div>
            `;
            break;
        case 'cancelled':
            statusStatHtml = `
                <i class="fas fa-ban"></i>
                <div>
                    <span class="stat-label">Cancelled</span>
                    <span class="stat-value">${request.cancellationDate || request.date}</span>
                </div>
            `;
            break;
        case 'rejected':
            statusStatHtml = `
                <i class="fas fa-times-circle"></i>
                <div>
                    <span class="stat-label">Rejected</span>
                    <span class="stat-value">${request.rejectionDate || request.date}</span>
                </div>
            `;
            break;
    }
    statusSpecificStat.innerHTML = statusStatHtml;
    
    // Update items list
    detailItemsList.innerHTML = request.items.map(item => `
        <div class="item-row">
            <i class="fas fa-box"></i>
            <span>${item}</span>
        </div>
    `).join('');
    
    // Update description and details
    detailDescription.textContent = request.description || 'No description provided';
    detailBrand.textContent = request.brand || 'Various';
    detailCondition.textContent = request.condition || 'Not specified';
    detailAddress.textContent = request.address;
    mapLink.href = `https://maps.google.com/?q=${encodeURIComponent(request.address)}`;
    
    // Update timeline
    updateDetailTimeline(request);
    
    // Update assignment
    updateAssignment(request);
    
    // Update action buttons
    updateActionButtons(request);
    
    // Update notes
    updateNotes(request);
}

function updateDetailTimeline(request) {
    let steps = '';
    
    steps += `
        <div class="timeline-step">
            <div class="step-icon">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="step-content">
                <p>Request submitted</p>
                <small>${request.date}</small>
            </div>
        </div>
    `;
    
    if (request.status === 'scheduled' || request.status === 'ongoing' || request.status === 'completed') {
        steps += `
            <div class="timeline-step">
                <div class="step-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="step-content">
                    <p>Request approved</p>
                    <small>${request.date}</small>
                </div>
            </div>
        `;
    }
    
    if (request.status === 'scheduled') {
        steps += `
            <div class="timeline-step">
                <div class="step-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="step-content">
                    <p>Scheduled for collection</p>
                    <small>${request.scheduledDate || 'Date TBD'} ${request.scheduledTime ? '· ' + request.scheduledTime : ''}</small>
                </div>
            </div>
        `;
    }
    
    if (request.status === 'ongoing') {
        steps += `
            <div class="timeline-step">
                <div class="step-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="step-content">
                    <p>Collection in progress</p>
                    <small>Started: ${request.scheduledDate || request.date}</small>
                </div>
            </div>
        `;
    }
    
    if (request.status === 'completed') {
        steps += `
            <div class="timeline-step">
                <div class="step-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="step-content">
                    <p>Items collected</p>
                    <small>${request.completedDate || 'Date TBD'} ${request.completionTime ? 'at ' + request.completionTime : ''}</small>
                </div>
            </div>
        `;
        steps += `
            <div class="timeline-step">
                <div class="step-icon">
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <div class="step-content">
                    <p>Request completed</p>
                    <small>${request.completedDate || 'Date TBD'}</small>
                    ${request.completionNotes ? `<div class="step-note">${request.completionNotes}</div>` : ''}
                </div>
            </div>
        `;
    }
    
    if (request.status === 'cancelled') {
        steps += `
            <div class="timeline-step">
                <div class="step-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="step-content">
                    <p>Request cancelled</p>
                    <small>${request.cancellationDate || request.date}</small>
                    ${request.cancellationReason ? `<div class="step-note">Reason: ${request.cancellationReason}</div>` : ''}
                </div>
            </div>
        `;
    }
    
    if (request.status === 'rejected') {
        steps += `
            <div class="timeline-step">
                <div class="step-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="step-content">
                    <p>Request rejected</p>
                    <small>${request.rejectionDate || request.date}</small>
                    ${request.rejectionReason ? `<div class="step-note">Reason: ${request.rejectionReason}</div>` : ''}
                </div>
            </div>
        `;
    }
    
    timelineSteps.innerHTML = steps;
}
    function updateAssignment(request) {
        if (request.status === 'scheduled' || request.status === 'ongoing' || request.status === 'completed') {
            assignmentCard.classList.remove('hidden');
            assignmentInfo.innerHTML = `
                <div class="assignment-row">
                    <i class="fas fa-user"></i>
                    <div>
                        <span class="details-label">Collector</span>
                        <span class="details-value">${request.assignedCollector || request.collector || 'Not assigned'}</span>
                    </div>
                </div>
                <div class="assignment-row">
                    <i class="fas fa-truck"></i>
                    <div>
                        <span class="details-label">Vehicle</span>
                        <span class="details-value">${request.assignedVehicle || request.vehicle || 'Not assigned'}</span>
                    </div>
                </div>
            `;
        } else {
            assignmentCard.classList.add('hidden');
        }
    }

    function updateActionButtons(request) {
        let buttons = '';
        
        switch(request.status) {
            case 'scheduled':
            case 'ongoing':
                buttons = `
                    <button class="detail-btn primary" onclick="window.location.href='/main/html/admin/aJobs.html?job=${request.id}'">
                        <i class="fas fa-eye"></i> View Job
                    </button>
                    <button class="detail-btn secondary" onclick="contactCollector('${request.id}')">
                        <i class="fas fa-phone"></i> Contact Collector
                    </button>
                `;
                break;
                
            case 'completed':
                buttons = `
                    <button class="detail-btn primary" onclick="window.location.href='/main/html/admin/aReport.html?request=${request.id}'">
                        <i class="fas fa-file-alt"></i> View Report
                    </button>
                    <button class="detail-btn secondary" onclick="viewCompletionDetails('${request.id}')">
                        <i class="fas fa-clipboard-list"></i> Details
                    </button>
                `;
                break;
                
            case 'cancelled':
            case 'rejected':
                buttons = `
                    <button class="detail-btn primary" onclick="contactProvider('${request.id}')">
                        <i class="fas fa-envelope"></i> Contact Provider
                    </button>
                    <button class="detail-btn secondary" onclick="viewHistory('${request.id}')">
                        <i class="fas fa-history"></i> View History
                    </button>
                `;
                break;
        }
        
        detailFooter.innerHTML = buttons;
    }

    function updateNotes(request) {
        let notes = '';
        
        switch(request.status) {
            case 'cancelled':
                notes = `
                    <i class="fas fa-info-circle"></i>
                    <strong>Cancellation Note:</strong> ${request.cancellationReason}
                `;
                break;
                
            case 'rejected':
                notes = `
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Rejection Reason:</strong> ${request.rejectionReason}
                `;
                break;
                
            case 'completed':
                notes = `
                    <i class="fas fa-check-circle"></i>
                    <strong>Collection Note:</strong> ${request.completionNotes}
                `;
                break;
                
            case 'scheduled':
                notes = `
                    <i class="fas fa-clock"></i>
                    <strong>Reminder:</strong> Collection scheduled for ${request.scheduledDate}
                `;
                break;
            case 'ongoing':
                notes = `
                    <i class="fas fa-truck"></i>
                    <strong>Status:</strong> Collection in progress
                `;
                break;
        }
        
        detailNotes.innerHTML = notes;
    }

    // Action functions
    window.contactCollector = function(reqId) {
        const request = collectionRequests.find(r => r.id === reqId);
        alert(`Contacting collector: ${request.assignedCollector || request.collector}`);
    };

    window.contactProvider = function(reqId) {
        const request = collectionRequests.find(r => r.id === reqId);
        alert(`Contacting provider: ${request.provider}\nPhone: ${request.providerContact}`);
    };

    window.viewCompletionDetails = function(reqId) {
        const request = collectionRequests.find(r => r.id === reqId);
        alert(`Completion Details:\nDate: ${request.completedDate}\nCollector: ${request.collector}\nNotes: ${request.completionNotes}`);
    };

    window.viewHistory = function(reqId) {
        alert(`Viewing history for request #${reqId}`);
    };

window.filterByStatus = function(status) {
    currentFilter = status;
    
    document.querySelectorAll('.status-panel').forEach(panel => {
        if (panel.classList.contains(status)) {
            panel.classList.add('active');
        } else {
            panel.classList.remove('active');
        }
    });
    
    if (!listView.classList.contains('hidden')) {
   
        filterRequests();
        renderCurrentView();
    } else {

        listView.classList.remove('hidden');
        detailView.classList.add('hidden');
        filterRequests();
        renderCurrentView();
    }
};

    searchInput.addEventListener('input', (e) => {
        currentSearch = e.target.value;
        filterRequests();
        renderCurrentView();
    });

    filterChips.forEach(chip => {
        chip.addEventListener('click', () => {
            filterChips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            currentQuickFilter = chip.dataset.filter;
            filterRequests();
            renderCurrentView();
        });
    });

    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            toggleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentView = btn.dataset.view;
            renderCurrentView();
        });
    });

    backToListBtn.addEventListener('click', () => {
        listView.classList.remove('hidden');
        detailView.classList.add('hidden');
    });

    // All Requests button
    const allRequestsBtn = document.getElementById('allRequestsBtn');
    if (allRequestsBtn) {
        allRequestsBtn.addEventListener('click', () => {
            // Reset to show all requests
            currentFilter = 'all';
            
            // Update active states on status panels
            document.querySelectorAll('.status-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            
            // Apply filter and render
            filterRequests();
            renderCurrentView();
        });
    }

    if (printBtn) {
        printBtn.addEventListener('click', () => {
            window.print();
        });
    }

    if (shareBtn) {
        shareBtn.addEventListener('click', () => {
            if (currentRequest) {
                navigator.clipboard?.writeText(`Request #${currentRequest.id} - ${window.location.href}`);
                alert('Link copied to clipboard!');
            }
        });
    }

    exportBtn.addEventListener('click', () => {
        const csv = generateCSV(filteredRequests);
        downloadCSV(csv, 'collection_requests.csv');
    });

    function generateCSV(requests) {
        const headers = ['ID', 'Title', 'Status', 'Provider', 'Date', 'Weight (kg)', 'Items', 'Description', 'Address'];
        const rows = requests.map(r => [
            r.id,
            r.title,
            r.status,
            r.provider,
            r.date,
            r.weight,
            r.items.join('; '),
            r.description,
            r.address
        ]);
        
        return [headers, ...rows].map(row => row.join(',')).join('\n');
    }

    function downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    }

    function renderCurrentView() {
        console.log('renderCurrentView called with view:', currentView);
        
        if (currentView === 'timeline') {
            timelineContainer.classList.remove('hidden');
            kanbanContainer.classList.add('hidden');
            renderTimeline();
        } else {
            timelineContainer.classList.add('hidden');
            kanbanContainer.classList.remove('hidden');
            renderKanban();
        }
    }

// Initialize the page
console.log('Initializing page with all requests');

// Reset all filters to default
currentFilter = 'all';
currentSearch = '';
currentQuickFilter = 'all';
currentSort = 'date-desc';
currentView = 'timeline';

// Clear search input
if (searchInput) {
    searchInput.value = '';
}

// Reset quick filter chips
filterChips.forEach(chip => {
    chip.classList.remove('active');
});

// Reset sort dropdown to default
document.querySelectorAll('.sort-dropdown-content a').forEach(a => {
    a.classList.remove('active-sort');
    if (a.dataset.sort === 'date-desc') {
        a.classList.add('active-sort');
        if (selectedSortSpan) {
            selectedSortSpan.textContent = a.textContent.trim();
        }
    }
});

// Reset status panel active states
document.querySelectorAll('.status-panel').forEach(panel => {
    panel.classList.remove('active');
});

// Update stats
updateStats();
updateActivityFeed();

filteredRequests = collectionRequests.map(request => ({...request})); // Create a copy

// Apply sorting
sortRequests();

// Update the result count
updateResultsCount();

// Force render the current view
renderCurrentView();

// Ensure timeline is visible
timelineContainer.classList.remove('hidden');
kanbanContainer.classList.add('hidden');

// Update toggle buttons
document.querySelectorAll('.toggle-btn').forEach(btn => {
    if (btn.dataset.view === 'timeline') {
        btn.classList.add('active');
    } else {
        btn.classList.remove('active');
    }
});

console.log('Page initialized with', filteredRequests.length, 'requests');

function setActiveNav() {
    const navLinks = document.querySelectorAll('.c-navbar-desktop a, .c-navbar-side-items a');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes('aCollectionRequests.html')) {
            link.classList.add('active');
        }
    });
}

setActiveNav();
});