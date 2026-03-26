document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM Content Loaded - Initializing Collection Requests Page');

    const collectionRequests = Array.isArray(window.collectionRequestsData)
    ? window.collectionRequestsData
    : [];

    // DOM Elements
    const listView = document.getElementById('collectionListView');
    const detailView = document.getElementById('collectionDetailView');
    const backToListBtn = document.getElementById('backToListBtn');
    const timelineContainer = document.getElementById('timelineContainer');
    const kanbanContainer = document.getElementById('kanbanContainer');
    const emptyState = document.getElementById('emptyState');
    const searchInput = document.getElementById('searchInput');
    const toggleBtns = document.querySelectorAll('.toggle-btn');
    const filterChips = document.querySelectorAll('.filter-chip');
    const resultCountSpan = document.getElementById('resultCount');
    const totalCountSpan = document.getElementById('totalCount');
    const exportBtn = document.getElementById('exportBtn');
    const printBtn = document.getElementById('printDetailBtn');

    // Stats elements
    const scheduledCount = document.getElementById('scheduledCount');
    const ongoingCount = document.getElementById('ongoingCount');
    const completedCount = document.getElementById('completedCount');
    const cancelledCount = document.getElementById('cancelledCount');
    const rejectedCount = document.getElementById('rejectedCount');

    // Kanban count elements
    const kanbanScheduledCount = document.getElementById('kanbanScheduledCount');
    const kanbanOngoingCount = document.getElementById('kanbanOngoingCount');
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

    function parseDisplayDate(dateStr) {
        if (!dateStr) return new Date(0);
        const parsed = new Date(dateStr);
        return isNaN(parsed.getTime()) ? new Date(0) : parsed;
    }

    if (sortDropdownBtn && sortDropdownContent) {
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

            if (selectedSortSpan) {
                selectedSortSpan.textContent = option.textContent.trim();
            }

            currentSort = option.dataset.sort || 'date-desc';
            filterRequests();
            renderCurrentView();

            if (sortDropdownContent) {
                sortDropdownContent.classList.remove('show');
            }
        });
    });

    function updateStats() {
        const scheduled = collectionRequests.filter(r => r.status === 'scheduled').length;
        const ongoing = collectionRequests.filter(r => r.status === 'ongoing').length;
        const completed = collectionRequests.filter(r => r.status === 'completed').length;
        const cancelled = collectionRequests.filter(r => r.status === 'cancelled').length;
        const rejected = collectionRequests.filter(r => r.status === 'rejected').length;

        if (scheduledCount) scheduledCount.textContent = scheduled;
        if (ongoingCount) ongoingCount.textContent = ongoing;
        if (completedCount) completedCount.textContent = completed;
        if (cancelledCount) cancelledCount.textContent = cancelled;
        if (rejectedCount) rejectedCount.textContent = rejected;

        hidePillIfNoUpdates('scheduled', scheduled);
        hidePillIfNoUpdates('ongoing', ongoing);
        hidePillIfNoUpdates('completed', completed);
        hidePillIfNoUpdates('cancelled', cancelled);
        hidePillIfNoUpdates('rejected', rejected);

        if (kanbanScheduledCount) kanbanScheduledCount.textContent = scheduled;
        if (kanbanOngoingCount) kanbanOngoingCount.textContent = ongoing;
        if (kanbanCompletedCount) kanbanCompletedCount.textContent = completed;
        if (kanbanCancelledCount) kanbanCancelledCount.textContent = cancelled;
        if (kanbanRejectedCount) kanbanRejectedCount.textContent = rejected;

        if (totalCountSpan) {
            totalCountSpan.textContent = `Total: ${collectionRequests.length}`;
        }
    }

    function hidePillIfNoUpdates(status, count) {
        const statusPanel = document.querySelector(`.status-panel.${status}`);
        if (!statusPanel) return;

        const trendPill = statusPanel.querySelector('.panel-trend');
        if (!trendPill) return;

        trendPill.style.display = count === 0 ? 'none' : 'flex';
    }

    function filterRequests() {
        filteredRequests = collectionRequests.filter(request => {
            const matchesStatus = currentFilter === 'all' || request.status === currentFilter;

            const searchTerm = currentSearch.toLowerCase().trim();
            const matchesSearch =
                searchTerm === '' ||
                request.id.toLowerCase().includes(searchTerm) ||
                request.provider.toLowerCase().includes(searchTerm) ||
                request.title.toLowerCase().includes(searchTerm) ||
                request.items.some(item => item.toLowerCase().includes(searchTerm));

            let matchesQuickFilter = true;
            const now = new Date();
            const requestDate = parseDisplayDate(request.date);

            switch (currentQuickFilter) {
                case 'this-week': {
                    const weekAgo = new Date();
                    weekAgo.setDate(now.getDate() - 7);
                    matchesQuickFilter = requestDate >= weekAgo;
                    break;
                }
                case 'this-month': {
                    const monthAgo = new Date();
                    monthAgo.setMonth(now.getMonth() - 1);
                    matchesQuickFilter = requestDate >= monthAgo;
                    break;
                }
                case 'high-weight':
                    matchesQuickFilter = parseFloat(request.weight) > 20;
                    break;
                case 'needs-review':
                    matchesQuickFilter = request.status === 'rejected' || request.status === 'cancelled';
                    break;
                case 'all':
                default:
                    matchesQuickFilter = true;
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
            switch (currentSort) {
                case 'date-desc':
                    return parseDisplayDate(b.date) - parseDisplayDate(a.date);
                case 'date-asc':
                    return parseDisplayDate(a.date) - parseDisplayDate(b.date);
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
        if (resultCountSpan) {
            resultCountSpan.textContent = `Showing ${filteredRequests.length} of ${collectionRequests.length} requests`;
        }
    }

    function renderTimeline() {
        if (!timelineContainer) return;

        if (filteredRequests.length === 0) {
            timelineContainer.innerHTML = '';
            if (emptyState) emptyState.classList.remove('hidden');
            return;
        }

        if (emptyState) emptyState.classList.add('hidden');

        let html = '';
        filteredRequests.forEach((request, index) => {
            const isLast = index === filteredRequests.length - 1;
            html += `
                <div class="timeline-item" data-req="${request.id}">
                    <div class="timeline-marker">
                        <div class="marker-dot ${request.status}"></div>
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

    function renderKanban() {
        const kanbanScheduled = document.getElementById('kanbanScheduled');
        const kanbanOngoing = document.getElementById('kanbanOngoing');
        const kanbanCompleted = document.getElementById('kanbanCompleted');
        const kanbanCancelled = document.getElementById('kanbanCancelled');
        const kanbanRejected = document.getElementById('kanbanRejected');

        if (kanbanScheduled) kanbanScheduled.innerHTML = '';
        if (kanbanOngoing) kanbanOngoing.innerHTML = '';
        if (kanbanCompleted) kanbanCompleted.innerHTML = '';
        if (kanbanCancelled) kanbanCancelled.innerHTML = '';
        if (kanbanRejected) kanbanRejected.innerHTML = '';

        if (filteredRequests.length === 0) {
            if (emptyState) emptyState.classList.remove('hidden');
            return;
        }

        if (emptyState) emptyState.classList.add('hidden');

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

            switch (request.status) {
                case 'scheduled':
                    if (kanbanScheduled) kanbanScheduled.innerHTML += card;
                    break;
                case 'ongoing':
                    if (kanbanOngoing) kanbanOngoing.innerHTML += card;
                    break;
                case 'completed':
                    if (kanbanCompleted) kanbanCompleted.innerHTML += card;
                    break;
                case 'cancelled':
                    if (kanbanCancelled) kanbanCancelled.innerHTML += card;
                    break;
                case 'rejected':
                    if (kanbanRejected) kanbanRejected.innerHTML += card;
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

    function showDetail(request) {
        currentRequest = request;

        if (listView) listView.classList.add('hidden');
        if (detailView) detailView.classList.remove('hidden');

        if (detailStatus) {
            detailStatus.textContent = request.status.charAt(0).toUpperCase() + request.status.slice(1);
            detailStatus.className = `hero-badge ${request.status}`;
        }

        if (detailRequestId) detailRequestId.textContent = `#${request.id}`;
        if (detailTitle) detailTitle.textContent = request.title;

        if (detailProvider) {
            detailProvider.innerHTML = `
                <i class="fas fa-user-circle"></i>
                <div>
                    <strong>${request.provider}</strong>
                    <span>${request.providerContact}</span>
                </div>
            `;
        }

        if (detailRequestDate) detailRequestDate.textContent = request.date;
        if (detailWeight) detailWeight.textContent = `${request.weight} kg`;
        if (detailItemCount) detailItemCount.textContent = request.items.length;

        let statusStatHtml = '';
        switch (request.status) {
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

        if (statusSpecificStat) statusSpecificStat.innerHTML = statusStatHtml;

        if (detailItemsList) {
            detailItemsList.innerHTML = request.items.map(item => `
                <div class="item-row">
                    <i class="fas fa-box"></i>
                    <span>${item}</span>
                </div>
            `).join('');
        }

        if (detailDescription) detailDescription.textContent = request.description || 'No description provided';
        if (detailBrand) detailBrand.textContent = request.brand || 'Various';
        if (detailCondition) detailCondition.textContent = request.condition || 'Not specified';
        if (detailAddress) detailAddress.textContent = request.address;
        if (mapLink) mapLink.href = `https://maps.google.com/?q=${encodeURIComponent(request.address)}`;

        updateDetailTimeline(request);
        updateAssignment(request);
        updateActionButtons(request);
        updateNotes(request);
    }

    function updateDetailTimeline(request) {
        if (!timelineSteps) return;

        let steps = `
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
        if (!assignmentCard || !assignmentInfo) return;

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
        if (!detailFooter) return;

        let buttons = '';

        switch (request.status) {
            case 'scheduled':
            case 'ongoing':
                buttons = `
                    <button class="detail-btn primary" onclick="window.location.href='/main/html/admin/aJobs.php?job=${request.id}'">
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
        if (!detailNotes) return;

        let notes = '';

        switch (request.status) {
            case 'cancelled':
                notes = `
                    <i class="fas fa-info-circle"></i>
                    <strong>Cancellation Note:</strong> ${request.cancellationReason || 'No reason provided'}
                `;
                break;

            case 'rejected':
                notes = `
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Rejection Reason:</strong> ${request.rejectionReason || 'No reason provided'}
                `;
                break;

            case 'completed':
                notes = `
                    <i class="fas fa-check-circle"></i>
                    <strong>Collection Note:</strong> ${request.completionNotes || 'No note available'}
                `;
                break;

            case 'scheduled':
                notes = `
                    <i class="fas fa-clock"></i>
                    <strong>Reminder:</strong> Collection scheduled for ${request.scheduledDate || 'TBD'}
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
    window.contactCollector = function (reqId) {
        const request = collectionRequests.find(r => r.id === reqId);
        if (!request) return;
        alert(`Contacting collector: ${request.assignedCollector || request.collector || 'Not assigned'}`);
    };

    window.contactProvider = function (reqId) {
        const request = collectionRequests.find(r => r.id === reqId);
        if (!request) return;
        alert(`Contacting provider: ${request.provider}\nPhone: ${request.providerContact}`);
    };

    window.viewCompletionDetails = function (reqId) {
        const request = collectionRequests.find(r => r.id === reqId);
        if (!request) return;
        alert(`Completion Details:\nDate: ${request.completedDate}\nCollector: ${request.collector}\nNotes: ${request.completionNotes}`);
    };

    window.viewHistory = function (reqId) {
        alert(`Viewing history for request #${reqId}`);
    };

    window.filterByStatus = function (status) {
        currentFilter = status;

        document.querySelectorAll('.status-panel').forEach(panel => {
            if (panel.classList.contains(status)) {
                panel.classList.add('active');
            } else {
                panel.classList.remove('active');
            }
        });

        if (currentFilter === 'all') {
            document.querySelectorAll('.status-panel').forEach(panel => {
                panel.classList.remove('active');
            });
        }

        if (listView && !listView.classList.contains('hidden')) {
            filterRequests();
            renderCurrentView();
        } else {
            if (listView) listView.classList.remove('hidden');
            if (detailView) detailView.classList.add('hidden');
            filterRequests();
            renderCurrentView();
        }
    };

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            currentSearch = e.target.value;
            filterRequests();
            renderCurrentView();
        });
    }

    filterChips.forEach(chip => {
        chip.addEventListener('click', () => {
            filterChips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            currentQuickFilter = chip.dataset.filter || 'all';
            filterRequests();
            renderCurrentView();
        });
    });

    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            toggleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentView = btn.dataset.view || 'timeline';
            renderCurrentView();
        });
    });

    if (backToListBtn) {
        backToListBtn.addEventListener('click', () => {
            if (listView) listView.classList.remove('hidden');
            if (detailView) detailView.classList.add('hidden');
        });
    }

    const allRequestsBtn = document.getElementById('allRequestsBtn');
    if (allRequestsBtn) {
        allRequestsBtn.addEventListener('click', () => {
            currentFilter = 'all';
            currentSearch = '';
            currentQuickFilter = 'all';

            if (searchInput) searchInput.value = '';

            document.querySelectorAll('.status-panel').forEach(panel => {
                panel.classList.remove('active');
            });

            filterChips.forEach(chip => {
                chip.classList.remove('active');
                if (chip.dataset.filter === 'all') {
                    chip.classList.add('active');
                }
            });

            filterRequests();
            renderCurrentView();
        });
    }

    if (printBtn) {
        printBtn.addEventListener('click', () => {
            window.print();
        });
    }

    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            const csv = generateCSV(filteredRequests);
            downloadCSV(csv, 'collection_requests.csv');
        });
    }

    function escapeCSV(value) {
        const stringValue = String(value ?? '');
        return `"${stringValue.replace(/"/g, '""')}"`;
    }

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

        return [headers, ...rows]
            .map(row => row.map(cell => escapeCSV(cell)).join(','))
            .join('\n');
    }

    function downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    function renderCurrentView() {
        if (currentView === 'timeline') {
            if (timelineContainer) timelineContainer.classList.remove('hidden');
            if (kanbanContainer) kanbanContainer.classList.add('hidden');
            renderTimeline();
        } else {
            if (timelineContainer) timelineContainer.classList.add('hidden');
            if (kanbanContainer) kanbanContainer.classList.remove('hidden');
            renderKanban();
        }
    }

    // Initialize page so all requests show automatically on open
    console.log('Initializing page with all requests');

    currentFilter = 'all';
    currentSearch = '';
    currentQuickFilter = 'all';
    currentSort = 'date-desc';
    currentView = 'timeline';

    if (searchInput) {
        searchInput.value = '';
    }

    filterChips.forEach(chip => {
        chip.classList.remove('active');
        if (chip.dataset.filter === 'all') {
            chip.classList.add('active');
        }
    });

    document.querySelectorAll('.sort-dropdown-content a').forEach(a => {
        a.classList.remove('active-sort');
        if (a.dataset.sort === 'date-desc') {
            a.classList.add('active-sort');
            if (selectedSortSpan) {
                selectedSortSpan.textContent = a.textContent.trim();
            }
        }
    });

    document.querySelectorAll('.status-panel').forEach(panel => {
        panel.classList.remove('active');
    });

    updateStats();
    filterRequests();
    renderCurrentView();

    if (listView) listView.classList.remove('hidden');
    if (detailView) detailView.classList.add('hidden');

    if (timelineContainer) timelineContainer.classList.remove('hidden');
    if (kanbanContainer) kanbanContainer.classList.add('hidden');

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