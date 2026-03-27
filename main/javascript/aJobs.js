let currentFilter = 'all';
let currentSort = 'desc';
let searchTerm = '';
let currentSelectedJob = null;

let listContainer, detailContainer, backBtn, pageTitle, timelineContainer, statsContainer;
let filterDropdownBtn, filterDropdownContent, searchInput, sortDescBtn, sortAscBtn;
let detailJobId, detailJobStatus, detailRequestId, detailProviderName, detailProviderAddress;
let detailProviderDate, detailCollector, detailVehicle, detailScheduled, detailTotalWeight;
let detailItemsCount, detailItemsList, detailTimeline, detailActionButtons;

function getJobsData() {
    return Array.isArray(window.jobsData) ? window.jobsData : [];
}

function fmtStatus(status) {
    if (!status) return 'Unknown';

    const statusMap = {
        Pending: 'Pending',
        Scheduled: 'Scheduled',
        Rejected: 'Rejected',
        Ongoing: 'Ongoing',
        Completed: 'Completed',
        Cancelled: 'Cancelled'
    };

    return statusMap[status] || status;
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openImageModal(imageSrc) {
    let modal = document.getElementById('imageModal');

    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'imageModal';
        modal.innerHTML = `
            <div class="image-modal-overlay">
                <button type="button" class="image-modal-close">&times;</button>
                <img class="image-modal-content" alt="Item image">
            </div>
        `;
        document.body.appendChild(modal);

        modal.querySelector('.image-modal-close').addEventListener('click', closeImageModal);

        modal.querySelector('.image-modal-overlay').addEventListener('click', function (e) {
            if (e.target === this) {
                closeImageModal();
            }
        });
    }

    const img = modal.querySelector('.image-modal-content');
    img.src = imageSrc;
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.style.display = 'none';
    }
    document.body.style.overflow = '';
}

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
}

function setupEventListeners() {
    if (filterDropdownBtn) {
        filterDropdownBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            filterDropdownContent.classList.toggle('show');
        });
    }

    document.addEventListener('click', function () {
        if (filterDropdownContent) {
            filterDropdownContent.classList.remove('show');
        }
    });

    document.querySelectorAll('.filter-dropdown-content a').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            currentFilter = this.dataset.filter;

            document.querySelectorAll('.filter-dropdown-content a').forEach(a => {
                a.classList.remove('active-filter');
            });

            this.classList.add('active-filter');

            const selectedSpan = document.getElementById('selectedFilter');
            if (selectedSpan) {
                selectedSpan.textContent = this.textContent.trim();
            }

            renderListView();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            searchTerm = this.value.toLowerCase().trim();
            renderListView();
        });
    }

    if (sortDescBtn) {
        sortDescBtn.addEventListener('click', function () {
            currentSort = 'desc';
            sortDescBtn.classList.add('active');
            if (sortAscBtn) sortAscBtn.classList.remove('active');
            renderListView();
        });
    }

    if (sortAscBtn) {
        sortAscBtn.addEventListener('click', function () {
            currentSort = 'asc';
            sortAscBtn.classList.add('active');
            if (sortDescBtn) sortDescBtn.classList.remove('active');
            renderListView();
        });
    }

    if (backBtn) {
        backBtn.addEventListener('click', function () {
            renderListView();
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
        });
    }

    const goToCollectionsBtn = document.getElementById('goToCollectionsBtn');
    if (goToCollectionsBtn) {
        goToCollectionsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Navigate to the collection jobs (operations) page
            window.location.href = '../../html/admin/aCollectionJobs.php';
        });
    }

    if (sortDescBtn) {
        sortDescBtn.classList.add('active');
    }
}

function renderStats() {
    if (statsContainer) {
        statsContainer.innerHTML = '';
    }
}

function getFilteredAndSortedJobs() {
    let filtered = [...getJobsData()];

    if (currentFilter !== 'all') {
        filtered = filtered.filter(j => j && j.status === currentFilter);
    }

    if (searchTerm) {
        filtered = filtered.filter(j =>
            j && (
                String(j.id || '').toLowerCase().includes(searchTerm) ||
                String(j.requestID || '').toLowerCase().includes(searchTerm) ||
                String(j.providerName || '').toLowerCase().includes(searchTerm) ||
                String(j.collector || '').toLowerCase().includes(searchTerm) ||
                String(j.vehicle || '').toLowerCase().includes(searchTerm)
            )
        );
    }

    filtered.sort((a, b) => {
        if (!a || !b) return 0;
        const da = new Date(a.datetime || 0).getTime();
        const db = new Date(b.datetime || 0).getTime();
        return currentSort === 'desc' ? db - da : da - db;
    });

    return filtered;
}

function renderStatusCard(statusName, jobs) {
    if (!jobs || !jobs.length) return '';

    return `
        <div class="status-card-modern">
            <div class="status-header-modern">
                <h3>${escapeHtml(statusName)}</h3>
                <span class="status-count-modern">${jobs.length}</span>
            </div>
            <div class="job-cards-modern">
                ${jobs.map(job => renderJobCard(job)).join('')}
            </div>
        </div>
    `;
}

function renderJobCard(job) {
    if (!job) return '';

    const scheduledDate = job.datetime
        ? new Date(job.datetime).toLocaleString('en-MY', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        })
        : 'Not scheduled';

    const shortAddress = job.address ? String(job.address).split(',')[0] : 'No address';

    return `
        <div class="job-card-modern" data-job-id="${escapeHtml(job.id)}">
            <div class="job-card-header-modern">
                <span class="job-id-modern">${escapeHtml(job.id)}</span>
                <span class="job-badge-modern ${escapeHtml(String(job.status || 'Pending').toLowerCase())}">${fmtStatus(job.status)}</span>
            </div>
            ${job.reasonText ? `<div class="job-reason" title="${escapeHtml(job.reasonText)}">${escapeHtml(job.reasonText)}</div>` : ''}
            <div class="job-provider-modern">${escapeHtml(job.providerName || 'N/A')}</div>
            <div class="job-request-modern">Request ID: ${escapeHtml(job.requestID || 'N/A')}</div>
            <div class="job-meta-grid">
                <div class="job-meta-item">
                    <i class="fas fa-user"></i>
                    <div class="job-meta-text">
                        <span class="job-meta-label">Collector</span>
                        <span class="job-meta-value">${escapeHtml(job.collector || 'Not assigned')}</span>
                    </div>
                </div>
                <div class="job-meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div class="job-meta-text">
                        <span class="job-meta-label">Scheduled</span>
                        <span class="job-meta-value">${escapeHtml(scheduledDate)}</span>
                    </div>
                </div>
                <div class="job-meta-item">
                    <i class="fas fa-box"></i>
                    <div class="job-meta-text">
                        <span class="job-meta-label">Items</span>
                        <span class="job-meta-value">${job.itemCount || 0} items</span>
                    </div>
                </div>
                <div class="job-meta-item">
                    <i class="fas fa-weight-hanging"></i>
                    <div class="job-meta-text">
                        <span class="job-meta-label">Weight</span>
                        <span class="job-meta-value">${job.totalWeight || '0'} kg</span>
                    </div>
                </div>
            </div>
            <div class="job-footer-modern">
                <div class="job-location-modern">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>${escapeHtml(shortAddress)}</span>
                </div>
            </div>
        </div>
    `;
}

function renderStage(title, icon, jobs) {
    if (!jobs || !jobs.length) return '';

    const statusOrder = title === 'Pre-Execution'
        ? ['Pending', 'Scheduled', 'Rejected']
        : title === 'Execution'
            ? ['Ongoing']
            : ['Completed', 'Cancelled'];

    return `
        <div class="timeline-stage">
            <div class="stage-header-modern">
                <div class="stage-icon-modern"><i class="${icon}"></i></div>
                <h2>${escapeHtml(title)}</h2>
                <div class="stage-progress"><i class="fas fa-clipboard-list"></i> ${jobs.length} jobs</div>
            </div>
            <div class="status-cards-grid">
                ${statusOrder.map(s => renderStatusCard(fmtStatus(s), jobs.filter(j => j && j.status === s))).join('')}
            </div>
        </div>
    `;
}

function renderListView() {
    if (!listContainer || !detailContainer || !backBtn || !pageTitle || !timelineContainer) return;

    currentSelectedJob = null;
    listContainer.style.display = 'block';
    detailContainer.style.display = 'none';
    backBtn.style.display = 'none';
    pageTitle.textContent = 'Jobs';

    const filteredJobs = getFilteredAndSortedJobs();

    if (!filteredJobs.length) {
        timelineContainer.innerHTML = `
            <div class="no-jobs-modern">
                <i class="fas fa-search"></i>
                <p>No jobs found</p>
            </div>
        `;
        return;
    }

    const pre = filteredJobs.filter(j => j && j.stage === 'pre-execution');
    const exe = filteredJobs.filter(j => j && j.stage === 'execution');
    const res = filteredJobs.filter(j => j && j.stage === 'resolution');

    let html = '';
    if (pre.length) html += renderStage('Pre-Execution', 'fas fa-hourglass-half', pre);
    if (exe.length) html += renderStage('Execution', 'fas fa-truck', exe);
    if (res.length) html += renderStage('Resolution', 'fas fa-flag-checkered', res);

    timelineContainer.innerHTML = html;

    document.querySelectorAll('.job-card-modern').forEach(card => {
        card.addEventListener('click', () => {
            const jobId = card.dataset.jobId;
            if (jobId) showJobDetail(jobId);
        });
    });
}

function showJobDetail(jobId) {
    const jobsData = getJobsData();
    const job = jobsData.find(j => j && j.id === jobId);

    if (!job || !job.fullData) return;

    currentSelectedJob = job;

    listContainer.style.display = 'none';
    detailContainer.style.display = 'block';
    backBtn.style.display = 'flex';
    pageTitle.textContent = 'Job Details';

    if (detailJobId) detailJobId.textContent = job.id || 'N/A';

    if (detailJobStatus) {
        detailJobStatus.textContent = fmtStatus(job.status);
        detailJobStatus.className = `detail-status-modern ${String(job.status || '').toLowerCase()}`;
    }

    if (detailRequestId) detailRequestId.textContent = job.requestID || 'N/A';

    if (detailProviderName) detailProviderName.textContent = job.fullData.provider?.name || 'N/A';
    if (detailProviderAddress) detailProviderAddress.textContent = job.fullData.provider?.address || 'N/A';
    if (detailProviderDate) detailProviderDate.textContent = job.fullData.provider?.date || 'N/A';

    if (detailCollector) detailCollector.textContent = job.fullData.assignment?.collector || 'N/A';
    if (detailVehicle) detailVehicle.textContent = job.fullData.assignment?.vehicle || 'N/A';
    if (detailScheduled) detailScheduled.textContent = job.fullData.assignment?.scheduled || 'N/A';
    if (detailTotalWeight) detailTotalWeight.textContent = `${job.totalWeight || '0'} kg`;

    const items = job.fullData.items || [];
    if (detailItemsCount) detailItemsCount.textContent = items.length;

    if (detailItemsList) {
        if (items.length) {
            detailItemsList.innerHTML = items.map((item, index) => `
<div class="job-detail-item-card collapsible-item ${index === 0 ? 'open' : ''}">
    <button type="button" class="job-detail-item-summary">
        <div class="job-detail-item-summary-left">
            <span class="job-detail-item-id">${escapeHtml(item.id || 'N/A')}</span>
        </div>
        <div class="job-detail-item-summary-right">
            <span class="job-detail-item-weight">
                <i class="fas fa-weight-hanging"></i> ${escapeHtml(item.weight || '0')} kg
            </span>
            <i class="fas fa-chevron-down item-dropdown-icon"></i>
        </div>
    </button>

    <div class="job-detail-item-body">
        <div class="job-detail-item-name">
            <i class="fas fa-box"></i>
            ${escapeHtml(item.name || 'Unknown Item')}
        </div>

        <div class="job-detail-item-brand">
            <span class="brand-label">Model/Brand:</span>
            <span class="brand-value">${escapeHtml(item.brand || 'N/A')}</span>
        </div>

        <div class="job-detail-item-action-row">
            <div class="job-detail-item-description">
                <div class="description-label">
                    <i class="fas fa-pen"></i> Description
                </div>
                <div class="description-text">
                    ${escapeHtml(item.description || 'No description')}
                </div>
            </div>

            <div class="job-detail-item-image-wrapper">
                ${
                    item.imagePath
                        ? `<button type="button" class="view-image-btn" data-image-path="${escapeHtml(item.imagePath)}">
                                <i class="fas fa-image"></i> View Image
                           </button>`
                        : `<span class="no-images">
                                <i class="fas fa-image"></i> No image
                           </span>`
                }
            </div>
        </div>

        <div class="job-detail-item-details">
            <div class="job-detail-item-detail">
                <span class="detail-label">Drop-off Location</span>
                <span class="detail-value">
                    <i class="fas fa-location-dot"></i>
                    ${escapeHtml(item.dropoff || 'Not assigned')}
                </span>
            </div>
        </div>
    </div>
</div>
`).join('');

            document.querySelectorAll('.job-detail-item-summary').forEach(btn => {
                btn.addEventListener('click', function () {
                    const card = this.closest('.collapsible-item');
                    if (card) {
                        card.classList.toggle('open');
                    }
                });
            });

            document.querySelectorAll('.view-image-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const imagePath = this.dataset.imagePath;
                    if (imagePath) {
                        openImageModal(imagePath);
                    }
                });
            });
        } else {
            detailItemsList.innerHTML = '<div class="no-items">No items found for this job.</div>';
        }
    }

    const timeline = job.fullData.timeline || [];
    if (detailTimeline) {
        if (timeline.length) {
            detailTimeline.innerHTML = timeline.map(entry => `
                <div class="timeline-item-modern">
                    <div class="timeline-marker-modern"></div>
                    <div class="timeline-content-modern">
                        <div class="timeline-time-modern">
                            <i class="fas fa-clock"></i>
                            ${new Date(entry.time).toLocaleString('en-MY')}
                        </div>
                        <div class="timeline-desc-modern">
                            <i class="${entry.icon || 'fas fa-clock'}"></i>
                            ${escapeHtml(entry.text || 'No details available')}
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            detailTimeline.innerHTML = `
                <div class="timeline-item-modern">
                    <div class="timeline-marker-modern"></div>
                    <div class="timeline-content-modern">
                        <div class="timeline-desc-modern">No timeline entries available.</div>
                    </div>
                </div>
            `;
        }
    }

    if (detailActionButtons) {
        detailActionButtons.innerHTML = '';
    }

    if (job.jobID) {
        const newUrl = `${window.location.pathname}?jobID=${encodeURIComponent(job.jobID)}`;
        window.history.replaceState({ jobID: job.jobID }, document.title, newUrl);
    } else if (job.requestIDRaw) {
        const newUrl = `${window.location.pathname}?requestID=${encodeURIComponent(job.requestIDRaw)}`;
        window.history.replaceState({ requestID: job.requestIDRaw }, document.title, newUrl);
    }
}

function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

function openJobFromUrl() {
    const jobIDParam = getQueryParam('jobID');
    const requestIDParam = getQueryParam('requestID');

    const jobsData = getJobsData();
    if (!Array.isArray(jobsData) || !jobsData.length) return;

    let matchedJob = null;

    if (jobIDParam) {
        const cleanJobID = String(jobIDParam).trim();

        matchedJob = jobsData.find(j =>
            j &&
            (
                String(j.jobID) === cleanJobID ||
                String(j.id) === cleanJobID ||
                String(j.id).replace(/^JOB/i, '') === cleanJobID.replace(/^JOB/i, '')
            )
        );
    }

    if (!matchedJob && requestIDParam) {
        const cleanRequestID = String(requestIDParam).trim();

        matchedJob = jobsData.find(j =>
            j &&
            (
                String(j.requestIDRaw) === cleanRequestID ||
                String(j.requestID) === cleanRequestID ||
                String(j.requestID).replace(/^REQ/i, '') === cleanRequestID.replace(/^REQ/i, '')
            )
        );
    }

    if (matchedJob) {
        showJobDetail(matchedJob.id);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    initializeElements();
    setupEventListeners();
    renderStats();
    renderListView();
    openJobFromUrl();
});