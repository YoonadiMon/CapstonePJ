document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Initializing Collection Requests Page');
    
    // Sample data - only non-pending requests
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

    // State
    let currentView = 'timeline';
    let currentFilter = 'all';
    let currentSearch = '';
    let currentSort = 'date-desc';
    let currentQuickFilter = 'all';
    let filteredRequests = [...collectionRequests];
    let currentRequest = null;

    // Initialize stats
    function updateStats() {
        const scheduled = collectionRequests.filter(r => r.status === 'scheduled').length;
        const completed = collectionRequests.filter(r => r.status === 'completed').length;
        const cancelled = collectionRequests.filter(r => r.status === 'cancelled').length;
        const rejected = collectionRequests.filter(r => r.status === 'rejected').length;
        
        scheduledCount.textContent = scheduled;
        completedCount.textContent = completed;
        cancelledCount.textContent = cancelled;
        rejectedCount.textContent = rejected;
        
        kanbanScheduledCount.textContent = scheduled;
        kanbanCompletedCount.textContent = completed;
        kanbanCancelledCount.textContent = cancelled;
        kanbanRejectedCount.textContent = rejected;
        
        totalCountSpan.textContent = `Total: ${collectionRequests.length}`;
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

    // Render timeline view
    function renderTimeline() {
        if (filteredRequests.length === 0) {
            timelineContainer.innerHTML = '';
            emptyState.classList.remove('hidden');
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
        
        // Add click handlers
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
        
        // Add click handlers
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
                        <span class="stat-value">${request.scheduledDate}</span>
                    </div>
                `;
                break;
            case 'completed':
                statusStatHtml = `
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <span class="stat-label">Completed</span>
                        <span class="stat-value">${request.completedDate}</span>
                    </div>
                `;
                break;
            case 'cancelled':
                statusStatHtml = `
                    <i class="fas fa-ban"></i>
                    <div>
                        <span class="stat-label">Cancelled</span>
                        <span class="stat-value">${request.cancellationDate}</span>
                    </div>
                `;
                break;
            case 'rejected':
                statusStatHtml = `
                    <i class="fas fa-times-circle"></i>
                    <div>
                        <span class="stat-label">Rejected</span>
                        <span class="stat-value">${request.rejectionDate}</span>
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
        detailDescription.textContent = request.description;
        detailBrand.textContent = request.brand;
        detailCondition.textContent = request.condition;
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
        
        // Request submitted
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
        
        switch(request.status) {
            case 'scheduled':
                steps += `
                    <div class="timeline-step">
                        <div class="step-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="step-content">
                            <p>Approved</p>
                            <small>${request.date}</small>
                        </div>
                    </div>
                    <div class="timeline-step">
                        <div class="step-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="step-content">
                            <p>Scheduled for collection</p>
                            <small>${request.scheduledDate} · ${request.scheduledTime}</small>
                        </div>
                    </div>
                `;
                break;
                
            case 'completed':
                steps += `
                    <div class="timeline-step">
                        <div class="step-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="step-content">
                            <p>Approved</p>
                            <small>${request.date}</small>
                        </div>
                    </div>
                    <div class="timeline-step">
                        <div class="step-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="step-content">
                            <p>Collected</p>
                            <small>${request.completedDate} at ${request.completionTime}</small>
                            <div class="step-note">${request.completionNotes}</div>
                        </div>
                    </div>
                `;
                break;
                
            case 'cancelled':
                steps += `
                    <div class="timeline-step">
                        <div class="step-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="step-content">
                            <p>Approved</p>
                            <small>${request.date}</small>
                        </div>
                    </div>
                    <div class="timeline-step">
                        <div class="step-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="step-content">
                            <p>Cancelled</p>
                            <small>${request.cancellationDate}</small>
                            <div class="step-note">${request.cancellationReason}</div>
                        </div>
                    </div>
                `;
                break;
                
            case 'rejected':
                steps += `
                    <div class="timeline-step">
                        <div class="step-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="step-content">
                            <p>Rejected</p>
                            <small>${request.rejectionDate}</small>
                            <div class="step-note">${request.rejectionReason}</div>
                        </div>
                    </div>
                `;
                break;
        }
        
        timelineSteps.innerHTML = steps;
    }

    function updateAssignment(request) {
        if (request.status === 'scheduled' || request.status === 'completed') {
            assignmentCard.classList.remove('hidden');
            assignmentInfo.innerHTML = `
                <div class="assignment-row">
                    <i class="fas fa-user"></i>
                    <div>
                        <span class="details-label">Collector</span>
                        <span class="details-value">${request.assignedCollector || request.collector}</span>
                    </div>
                </div>
                <div class="assignment-row">
                    <i class="fas fa-truck"></i>
                    <div>
                        <span class="details-label">Vehicle</span>
                        <span class="details-value">${request.assignedVehicle || request.vehicle}</span>
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
        
        // Update filter chips
        filterChips.forEach(chip => {
            if (chip.dataset.filter === 'all') {
                chip.classList.add('active');
            } else {
                chip.classList.remove('active');
            }
        });
        
        filterRequests();
        renderCurrentView();
    };

    // Event Listeners
    searchInput.addEventListener('input', (e) => {
        currentSearch = e.target.value;
        filterRequests();
        renderCurrentView();
    });

    sortSelect.addEventListener('change', (e) => {
        currentSort = e.target.value;
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

    // Initialize
    updateStats();
    updateActivityFeed();
    filterRequests();
    renderCurrentView();
    
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