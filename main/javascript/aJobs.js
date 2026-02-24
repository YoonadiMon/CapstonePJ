const jobDetailsData = {
    JOB001: {
        status: "pending",
        provider: {
            name: "Emma LXC",
            address: "43300, Selangor, Malaysia",
            date: "27/02/2026"
        },
        items: [
            {
                id: "ITEM001",
                name: "Laptop",
                brand: "Dell XPS 13 9310",
                weight: "2.1",
                dropoff: "Selangor Collection Center",
                description: "Screen cracked, fully functional otherwise"
            },
            {
                id: "ITEM002",
                name: "Smartphone",
                brand: "iPhone 12 Pro",
                weight: "0.4",
                dropoff: "Selangor Collection Center",
                description: "Battery swollen, needs replacement"
            },
            {
                id: "ITEM003",
                name: "Tablet",
                brand: "Samsung Galaxy Tab S7",
                weight: "1.2",
                dropoff: "Petaling Jaya Center",
                description: "Charging port damaged"
            }
        ],
        collector: "Not assigned",
        vehicle: "Not assigned",
        datetime: "2026-02-27T09:00",
        requestId: "REQ245"
    },
    JOB002: {
        status: "accepted",
        provider: {
            name: "James Wong",
            address: "47800, Petaling Jaya, Malaysia",
            date: "28/02/2026"
        },
        items: [
            {
                id: "ITEM004",
                name: "Desktop Computer",
                brand: "Custom PC - i7/16GB",
                weight: "12.5",
                dropoff: "Petaling Jaya Center",
                description: "Doesn't boot up, possibly PSU issue"
            },
            {
                id: "ITEM005",
                name: "Monitor",
                brand: "Samsung 24\"",
                weight: "3.8",
                dropoff: "Petaling Jaya Center",
                description: "Lines on screen"
            }
        ],
        collector: "Ahmad Bin Yusof",
        vehicle: "Toyota Hiace (VH23)",
        datetime: "2026-02-28T14:30",
        requestId: "REQ246"
    },
    JOB003: {
        status: "ongoing",
        provider: {
            name: "Sarah Tan",
            address: "56000, Kuala Lumpur, Malaysia",
            date: "25/02/2026"
        },
        items: [
            {
                id: "ITEM006",
                name: "Printer",
                brand: "Canon PIXMA",
                weight: "6.2",
                dropoff: "KL Central Hub",
                description: "Paper jam, multiple errors"
            },
            {
                id: "ITEM007",
                name: "Scanner",
                brand: "Epson Perfection",
                weight: "4.1",
                dropoff: "KL Central Hub",
                description: "Not powering on"
            },
            {
                id: "ITEM008",
                name: "External HDD",
                brand: "WD 2TB",
                weight: "0.3",
                dropoff: "KL Central Hub",
                description: "Clicking sound, not detected"
            }
        ],
        collector: "Siti Nurhaliza",
        vehicle: "Isuzu NLR (VH07)",
        datetime: "2026-02-25T10:15",
        requestId: "REQ250"
    },
    JOB004: {
        status: "delayed",
        provider: {
            name: "Raj Kumar",
            address: "40100, Shah Alam, Malaysia",
            date: "26/02/2026"
        },
        items: [
            {
                id: "ITEM009",
                name: "Microwave",
                brand: "Panasonic Inverter",
                weight: "14.5",
                dropoff: "Shah Alam Center",
                description: "Not heating, still powers on"
            }
        ],
        collector: "Mei Ling",
        vehicle: "Mitsubishi L300 (VH09)",
        datetime: "2026-02-26T08:00",
        requestId: "REQ251",
        delayReason: "Traffic accident"
    },
    JOB005: {
        status: "pickedup",
        provider: {
            name: "Lim Wei Jie",
            address: "43000, Kajang, Malaysia",
            date: "24/02/2026"
        },
        items: [
            {
                id: "ITEM010",
                name: "TV",
                brand: "Sony Bravia 55\"",
                weight: "18.2",
                dropoff: "Kajang Collection Point",
                description: "Cracked screen, otherwise works"
            },
            {
                id: "ITEM011",
                name: "Soundbar",
                brand: "Sony HT-S350",
                weight: "3.5",
                dropoff: "Kajang Collection Point",
                description: "Works but remote missing"
            }
        ],
        collector: "Tan Sri Aziz",
        vehicle: "Nissan NV350 (VH33)",
        datetime: "2026-02-24T13:45",
        requestId: "REQ252"
    },
    JOB006: {
        status: "completed",
        provider: {
            name: "Aisha Abdullah",
            address: "50400, Kuala Lumpur, Malaysia",
            date: "22/02/2026"
        },
        items: [
            {
                id: "ITEM012",
                name: "Tablet",
                brand: "iPad Air 4",
                weight: "0.5",
                dropoff: "KL Central Hub",
                description: "Broken screen, battery ok"
            },
            {
                id: "ITEM013",
                name: "Smart Watch",
                brand: "Apple Watch S6",
                weight: "0.1",
                dropoff: "KL Central Hub",
                description: "Not charging"
            }
        ],
        collector: "Vincent Wong",
        vehicle: "Hilux (VH05)",
        datetime: "2026-02-22T09:30",
        requestId: "REQ260",
        completedAt: "2026-02-22T14:20"
    },
    JOB007: {
        status: "cancelled",
        provider: {
            name: "Kevin Ng",
            address: "47300, Petaling Jaya, Malaysia",
            date: "20/02/2026"
        },
        items: [
            {
                id: "ITEM014",
                name: "Router",
                brand: "Asus RT-AC68U",
                weight: "0.8",
                dropoff: "Petaling Jaya Center",
                description: "Intermittent WiFi"
            }
        ],
        collector: "Not assigned",
        vehicle: "Not assigned",
        datetime: "2026-02-20T16:00",
        requestId: "REQ261",
        cancelReason: "Provider cancelled"
    },
    JOB008: {
        status: "failed",
        provider: {
            name: "Priya Krishnan",
            address: "50000, Kuala Lumpur, Malaysia",
            date: "21/02/2026"
        },
        items: [
            {
                id: "ITEM015",
                name: "Laptop",
                brand: "MacBook Pro 2019",
                weight: "2.0",
                dropoff: "KL Central Hub",
                description: "Water damage, not turning on"
            },
            {
                id: "ITEM016",
                name: "Power Bank",
                brand: "Anker 20000mAh",
                weight: "0.4",
                dropoff: "KL Central Hub",
                description: "Swollen battery"
            }
        ],
        collector: "Hassan Osman",
        vehicle: "Daihatsu (VH41)",
        datetime: "2026-02-21T08:30",
        requestId: "REQ262",
        failReason: "Vehicle breakdown after pickup"
    },
    JOB009: {
        status: "rejected",
        provider: {
            name: "Michael Chen",
            address: "Bukit Bintang, KL",
            date: "10/03/2026"
        },
        items: [
            {
                id: "ITEM017",
                name: "CRT TV",
                brand: "Sony Trinitron",
                weight: "25.0",
                dropoff: "KL Central Hub",
                description: "CRT TV - unacceptable item"
            }
        ],
        collector: "Not assigned",
        vehicle: "Not assigned",
        datetime: "2026-03-10T11:00",
        requestId: "REQ241",
        rejectReason: "Unacceptable items (CRT)"
    }
};

let jobsData = Object.entries(jobDetailsData).map(([jobId, jobData]) => {
    const totalWeight = jobData.items.reduce((sum, item) => sum + parseFloat(item.weight), 0);
    const itemsSummary = jobData.items.map(item => item.name).join(', ');
    
    return {
        id: jobId,
        requestId: jobData.requestId,
        status: jobData.status,
        collector: jobData.collector,
        vehicle: jobData.vehicle,
        datetime: jobData.datetime,
        address: jobData.provider.address,
        items: itemsSummary,
        totalWeight: totalWeight.toFixed(1),
        providerName: jobData.provider.name,
        providerDate: jobData.provider.date,
        itemCount: jobData.items.length,
        fullData: jobData,
        ...(jobData.rejectReason && { rejectReason: jobData.rejectReason }),
        ...(jobData.cancelReason && { cancelReason: jobData.cancelReason }),
        ...(jobData.delayReason && { delayReason: jobData.delayReason }),
        ...(jobData.failReason && { failReason: jobData.failReason }),
        ...(jobData.completedAt && { completedAt: jobData.completedAt })
    };
});

function getStageFromStatus(status) {
    const pre = ['pending', 'accepted', 'rejected'];
    const exec = ['ongoing', 'delayed', 'pickedup'];
    const res = ['completed', 'cancelled', 'failed'];
    if (pre.includes(status)) return 'pre';
    if (exec.includes(status)) return 'execution';
    if (res.includes(status)) return 'resolution';
    return 'other';
}

jobsData = jobsData.map(job => ({ 
    ...job, 
    stage: getStageFromStatus(job.status) 
}));

const listContainer = document.getElementById('jobsListContainer');
const detailContainer = document.getElementById('jobDetailContainer');
const pageTitle = document.getElementById('pageTitle');
const backBtn = document.getElementById('backToListBtn');

let currentFilter = 'all';
let currentSort = 'desc';
let currentSearch = '';

function filterJobs() {
    let filtered = jobsData.filter(job => {
        if (currentFilter !== 'all' && job.status !== currentFilter) return false;
        
        if (currentSearch) {
            const searchLower = currentSearch.toLowerCase();
            return job.id.toLowerCase().includes(searchLower) ||
                job.providerName.toLowerCase().includes(searchLower) ||
                job.collector.toLowerCase().includes(searchLower) ||
                job.requestId.toLowerCase().includes(searchLower) ||
                job.address.toLowerCase().includes(searchLower);
        }
        return true;
    });

    filtered.sort((a, b) => {
        const da = new Date(a.datetime);
        const db = new Date(b.datetime);
        return currentSort === 'desc' ? db - da : da - db;
    });
    
    return filtered;
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
    
    return `
        <div class="job-card-modern" data-job-id="${job.id}">
            <div class="job-card-header-modern">
                <span class="job-id-modern">${job.id}</span>
                <span class="job-badge-modern ${job.status}">${job.status}</span>
            </div>
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

function renderListView() {
    detailContainer.style.display = 'none';
    listContainer.style.display = 'block';
    backBtn.style.display = 'none';
    pageTitle.textContent = 'Collection Jobs';

    const filtered = filterJobs();

    const preCount = filtered.filter(j => ['pending', 'accepted', 'rejected'].includes(j.status)).length;
    const execCount = filtered.filter(j => ['ongoing', 'delayed', 'pickedup'].includes(j.status)).length;
    const resCount = filtered.filter(j => ['completed', 'cancelled', 'failed'].includes(j.status)).length;
    const issueCount = filtered.filter(j => ['delayed', 'failed'].includes(j.status)).length;

    const statsHtml = `
        <div class="jobs-stats-grid">
            <div class="stat-card-modern">
                <div class="stat-icon-modern"><i class="fas fa-hourglass-start"></i></div>
                <div class="stat-content-modern">
                    <div class="stat-value-modern">${preCount}</div>
                    <div class="stat-label-modern">Pre-execution</div>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon-modern"><i class="fas fa-play"></i></div>
                <div class="stat-content-modern">
                    <div class="stat-value-modern">${execCount}</div>
                    <div class="stat-label-modern">Execution</div>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon-modern"><i class="fas fa-check-double"></i></div>
                <div class="stat-content-modern">
                    <div class="stat-value-modern">${resCount}</div>
                    <div class="stat-label-modern">Resolution</div>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon-modern"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-content-modern">
                    <div class="stat-value-modern">${issueCount}</div>
                    <div class="stat-label-modern">Issues</div>
                </div>
            </div>
        </div>
    `;

    const filterChipsHtml = `
        <div class="filter-section-modern">
            <div class="filter-chips-modern">
                <span class="filter-chip-modern ${currentFilter === 'all' ? 'active' : ''}" data-filter="all">
                    <i class="fas fa-list"></i> All Jobs
                </span>
                <span class="filter-chip-modern ${currentFilter === 'pending' ? 'active' : ''}" data-filter="pending">
                    <i class="far fa-clock"></i> Pending
                </span>
                <span class="filter-chip-modern ${currentFilter === 'accepted' ? 'active' : ''}" data-filter="accepted">
                    <i class="fas fa-check-circle"></i> Accepted
                </span>
                <span class="filter-chip-modern ${currentFilter === 'rejected' ? 'active' : ''}" data-filter="rejected">
                    <i class="fas fa-times-circle"></i> Rejected
                </span>
                <span class="filter-chip-modern ${currentFilter === 'ongoing' ? 'active' : ''}" data-filter="ongoing">
                    <i class="fas fa-sync-alt"></i> Ongoing
                </span>
                <span class="filter-chip-modern ${currentFilter === 'delayed' ? 'active' : ''}" data-filter="delayed">
                    <i class="fas fa-exclamation-triangle"></i> Delayed
                </span>
                <span class="filter-chip-modern ${currentFilter === 'pickedup' ? 'active' : ''}" data-filter="pickedup">
                    <i class="fas fa-box"></i> Picked up
                </span>
                <span class="filter-chip-modern ${currentFilter === 'completed' ? 'active' : ''}" data-filter="completed">
                    <i class="fas fa-check-double"></i> Completed
                </span>
                <span class="filter-chip-modern ${currentFilter === 'cancelled' ? 'active' : ''}" data-filter="cancelled">
                    <i class="fas fa-ban"></i> Cancelled
                </span>
                <span class="filter-chip-modern ${currentFilter === 'failed' ? 'active' : ''}" data-filter="failed">
                    <i class="fas fa-times-circle"></i> Failed
                </span>
            </div>
            <div class="search-wrapper-modern">
                <input type="text" placeholder="Search jobs by ID, provider, collector..." id="searchInput" value="${currentSearch}">
                <button id="searchBtn"><i class="fas fa-search"></i> Search</button>
            </div>
        </div>
    `;

    const sortCountHtml = `
        <div class="jobs-count-modern">
            <div class="jobs-count-badge-modern">
                <i class="fas fa-briefcase"></i> ${filtered.length} job${filtered.length !== 1 ? 's' : ''}
            </div>
            <div class="jobs-sort-modern">
                <span class="sort-option ${currentSort === 'desc' ? 'active' : ''}" data-sort="desc">
                    <i class="fas fa-sort-amount-down"></i> Newest
                </span>
                <span class="sort-option ${currentSort === 'asc' ? 'active' : ''}" data-sort="asc">
                    <i class="fas fa-sort-amount-up"></i> Oldest
                </span>
            </div>
        </div>
    `;

    const stages = {
        pre: { 
            label: 'Pre‑execution', 
            icon: 'fas fa-hourglass-start',
            statuses: ['pending', 'accepted', 'rejected'] 
        },
        execution: { 
            label: 'Execution', 
            icon: 'fas fa-play',
            statuses: ['ongoing', 'delayed', 'pickedup'] 
        },
        resolution: { 
            label: 'Resolution', 
            icon: 'fas fa-check-double',
            statuses: ['completed', 'cancelled', 'failed'] 
        }
    };

    let timelineHtml = '<div class="jobs-timeline-container">';

    for (let [stageKey, stageObj] of Object.entries(stages)) {
        const jobsInStage = filtered.filter(j => j.stage === stageKey);
        if (jobsInStage.length === 0) continue;

        timelineHtml += `
            <div class="timeline-stage" data-stage="${stageKey}">
                <div class="stage-header-modern">
                    <div class="stage-icon-modern"><i class="${stageObj.icon}"></i></div>
                    <h2>${stageObj.label}</h2>
                    <div class="stage-progress">
                        <i class="fas fa-tasks"></i> ${jobsInStage.length} jobs
                    </div>
                </div>
                <div class="status-cards-grid">
        `;

        stageObj.statuses.forEach(status => {
            const statusJobs = jobsInStage.filter(j => j.status === status);
            if (statusJobs.length === 0) return;

            const statusLabel = status.charAt(0).toUpperCase() + status.slice(1);
            
            timelineHtml += `
                <div class="status-card-modern">
                    <div class="status-header-modern">
                        <h3>${statusLabel}</h3>
                        <span class="status-count-modern">${statusJobs.length}</span>
                    </div>
                    <div class="job-cards-modern">
            `;

            statusJobs.forEach(job => {
                timelineHtml += renderJobCard(job);
            });

            timelineHtml += `</div></div>`;
        });

        timelineHtml += `</div></div>`;
    }

    timelineHtml += '</div>';

    if (filtered.length === 0) {
        timelineHtml = `
            <div class="no-jobs-modern">
                <i class="fas fa-clipboard-list"></i>
                <p>No jobs match the current filters.</p>
                <div class="suggestion">Try adjusting your search or filter criteria</div>
            </div>
        `;
    }
  
    listContainer.innerHTML = statsHtml + filterChipsHtml + sortCountHtml + timelineHtml;

    // Filter chips
    document.querySelectorAll('.filter-chip-modern').forEach(chip => {
        chip.addEventListener('click', (e) => {
            document.querySelectorAll('.filter-chip-modern').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            currentFilter = chip.dataset.filter;
            renderListView();
        });
    });

    // Sort options
    document.querySelectorAll('.sort-option').forEach(option => {
        option.addEventListener('click', (e) => {
            document.querySelectorAll('.sort-option').forEach(o => o.classList.remove('active'));
            option.classList.add('active');
            currentSort = option.dataset.sort;
            renderListView();
        });
    });

    //Search
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    
    if (searchInput) {
        searchInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                currentSearch = searchInput.value;
                renderListView();
            }
        });
    }
    
    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                currentSearch = searchInput.value;
                renderListView();
            }
        });
    }

    // Job cards
    document.querySelectorAll('.job-card-modern').forEach(card => {
        card.addEventListener('click', (e) => {
            const jobId = card.dataset.jobId;
            if (jobId) showJobDetail(jobId);
        });
    });
}

// Job detail view
function showJobDetail(jobId) {
    const job = jobsData.find(j => j.id === jobId);
    if (!job) return;

    listContainer.style.display = 'none';
    detailContainer.style.display = 'block';
    backBtn.style.display = 'flex';
    pageTitle.textContent = `Job Details`;

    const jobData = job.fullData;
    const isDelayed = job.status === 'delayed';
    const collectorInfo = job.collector !== 'Not assigned' ? job.collector : 'Not assigned';
    const vehicleInfo = job.vehicle !== 'Not assigned' ? job.vehicle : 'Not assigned';

    let timelineHtml = '';
    
    timelineHtml += `
        <div class="timeline-item-modern">
            <div class="timeline-marker-modern"></div>
            <div class="timeline-content-modern">
                <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(job.datetime).toLocaleString()}</div>
                <div class="timeline-desc-modern"><i class="fas fa-paper-plane"></i> Request sent</div>
            </div>
        </div>
    `;

    if (job.status === 'rejected') {
        timelineHtml += `
            <div class="timeline-item-modern">
                <div class="timeline-marker-modern" style="background: #b83e3e; box-shadow: 0 0 0 2px #b83e3e;"></div>
                <div class="timeline-content-modern">
                    <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(new Date(job.datetime).getTime() + 24*60*60000).toLocaleString()}</div>
                    <div class="timeline-desc-modern"><i class="fas fa-times-circle"></i> Rejected: ${job.rejectReason || 'Unacceptable items'}</div>
                </div>
            </div>
        `;
    } else if (job.status === 'cancelled') {
        timelineHtml += `
            <div class="timeline-item-modern">
                <div class="timeline-marker-modern" style="background: #666666; box-shadow: 0 0 0 2px #666666;"></div>
                <div class="timeline-content-modern">
                    <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(new Date(job.datetime).getTime() + 12*60*60000).toLocaleString()}</div>
                    <div class="timeline-desc-modern"><i class="fas fa-ban"></i> Cancelled: ${job.cancelReason || 'Provider cancelled'}</div>
                </div>
            </div>
        `;
    } else {
        timelineHtml += `
            <div class="timeline-item-modern">
                <div class="timeline-marker-modern" style="background: var(--MainBlue); box-shadow: 0 0 0 2px var(--MainBlue);"></div>
                <div class="timeline-content-modern">
                    <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(new Date(job.datetime).getTime() + 2*60*60000).toLocaleString()}</div>
                    <div class="timeline-desc-modern"><i class="fas fa-check-circle"></i> Approved & scheduled</div>
                </div>
            </div>
        `;

        if (job.collector !== 'Not assigned') {
            timelineHtml += `
                <div class="timeline-item-modern">
                    <div class="timeline-marker-modern"></div>
                    <div class="timeline-content-modern">
                        <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(new Date(job.datetime).getTime() - 30*60000).toLocaleString()}</div>
                        <div class="timeline-desc-modern"><i class="fas fa-user-check"></i> Collection confirmed - ${job.collector}</div>
                    </div>
                </div>
            `;
        }

        if (job.status === 'ongoing' || job.status === 'delayed' || job.status === 'pickedup' || job.status === 'completed') {
            timelineHtml += `
                <div class="timeline-item-modern">
                    <div class="timeline-marker-modern"></div>
                    <div class="timeline-content-modern">
                        <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(job.datetime).toLocaleTimeString()}</div>
                        <div class="timeline-desc-modern"><i class="fas fa-truck"></i> Journey started at APU</div>
                    </div>
                </div>
            `;
        }

        if (job.status === 'delayed' && job.delayReason) {
            timelineHtml += `
                <div class="timeline-item-modern">
                    <div class="timeline-marker-modern" style="background: #b55f0e; box-shadow: 0 0 0 2px #b55f0e;"></div>
                    <div class="timeline-content-modern">
                        <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(new Date(job.datetime).getTime() + 75*60000).toLocaleTimeString()}</div>
                        <div class="timeline-desc-modern"><i class="fas fa-exclamation-triangle"></i> Delayed: ${job.delayReason}</div>
                    </div>
                </div>
            `;
        }

        if (job.status === 'pickedup' || job.status === 'completed') {
            timelineHtml += `
                <div class="timeline-item-modern">
                    <div class="timeline-marker-modern"></div>
                    <div class="timeline-content-modern">
                        <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(new Date(job.datetime).getTime() + 150*60000).toLocaleTimeString()}</div>
                        <div class="timeline-desc-modern"><i class="fas fa-check-circle"></i> Pickup completed</div>
                    </div>
                </div>
            `;
        }

        if (job.status === 'completed') {
            timelineHtml += `
                <div class="timeline-item-modern">
                    <div class="timeline-marker-modern" style="background: #1f6c2f; box-shadow: 0 0 0 2px #1f6c2f;"></div>
                    <div class="timeline-content-modern">
                        <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${job.completedAt ? new Date(job.completedAt).toLocaleString() : new Date(new Date(job.datetime).getTime() + 240*60000).toLocaleString()}</div>
                        <div class="timeline-desc-modern"><i class="fas fa-flag-checkered"></i> Drop-off completed</div>
                    </div>
                </div>
            `;
        }

        if (job.status === 'failed' && job.failReason) {
            timelineHtml += `
                <div class="timeline-item-modern">
                    <div class="timeline-marker-modern" style="background: #b83e3e; box-shadow: 0 0 0 2px #b83e3e;"></div>
                    <div class="timeline-content-modern">
                        <div class="timeline-time-modern"><i class="fas fa-clock"></i> ${new Date(new Date(job.datetime).getTime() + 180*60000).toLocaleTimeString()}</div>
                        <div class="timeline-desc-modern"><i class="fas fa-times-circle"></i> Failed: ${job.failReason}</div>
                    </div>
                </div>
            `;
        }
    }

    let itemsHtml = '';
    if (jobData.items && jobData.items.length > 0) {
        jobData.items.forEach(item => {
            itemsHtml += `
                <div class="info-row-modern">
                    <span class="info-label-modern">${item.id}</span>
                    <span class="info-value-modern"><strong>${item.name}</strong> - ${item.brand} (${item.weight} kg)</span>
                </div>
                <div class="info-row-modern">
                    <span class="info-label-modern">Drop-off</span>
                    <span class="info-value-modern">${item.dropoff}</span>
                </div>
                <div class="info-row-modern" style="border-bottom: 1px dashed var(--BlueGray); margin-bottom: 0.5rem;">
                    <span class="info-label-modern">Description</span>
                    <span class="info-value-modern">${item.description}</span>
                </div>
            `;
        });
    }

    const detailHtml = `
        <div class="job-detail-container-modern">
            <div class="detail-header-modern">
                <div class="detail-title-section-modern">
                    <h2>${job.id}</h2>
                    <span class="detail-status-modern ${job.status}">${job.status}</span>
                </div>
                <button class="detail-request-link-modern" id="viewRequestBtn">
                    <i class="fas fa-external-link-alt"></i> View Request ${job.requestId}
                </button>
            </div>

            <div class="info-grid-modern">
                <div class="info-card-modern">
                    <h3><i class="fas fa-store"></i> Provider Information</h3>
                    <div class="info-row-modern">
                        <span class="info-label-modern">Name</span>
                        <span class="info-value-modern">${jobData.provider.name}</span>
                    </div>
                    <div class="info-row-modern">
                        <span class="info-label-modern">Address</span>
                        <span class="info-value-modern">${jobData.provider.address}</span>
                    </div>
                    <div class="info-row-modern">
                        <span class="info-label-modern">Date</span>
                        <span class="info-value-modern">${jobData.provider.date}</span>
                    </div>
                </div>

                <div class="info-card-modern">
                    <h3><i class="fas fa-truck"></i> Assignment Details</h3>
                    <div class="info-row-modern">
                        <span class="info-label-modern">Collector</span>
                        <span class="info-value-modern">${collectorInfo}</span>
                    </div>
                    <div class="info-row-modern">
                        <span class="info-label-modern">Vehicle</span>
                        <span class="info-value-modern">${vehicleInfo}</span>
                    </div>
                    <div class="info-row-modern">
                        <span class="info-label-modern">Scheduled</span>
                        <span class="info-value-modern">${new Date(job.datetime).toLocaleString()}</span>
                    </div>
                    <div class="info-row-modern">
                        <span class="info-label-modern">Total Weight</span>
                        <span class="info-value-modern">${job.totalWeight} kg</span>
                    </div>
                </div>
            </div>

            <div class="items-section-modern">
                <div class="items-header-modern">
                    <h3><i class="fas fa-boxes"></i> Items</h3>
                    <span class="items-count-modern">${jobData.items.length}</span>
                </div>
                ${itemsHtml}
            </div>

            <div class="timeline-container-modern">
                <h3><i class="fas fa-history"></i> Job Timeline</h3>
                <div class="timeline-modern">
                    ${timelineHtml}
                </div>
            </div>

            <div class="action-buttons-modern">
                <button class="btn-modern-primary" id="viewRequestBtn2">
                    <i class="fas fa-external-link-alt"></i> View Request Details
                </button>
                ${isDelayed ? 
                    '<button class="btn-modern-report" id="reportIssueBtn"><i class="fas fa-exclamation-triangle"></i> Report Issue</button>' : 
                    job.status !== 'completed' && job.status !== 'cancelled' && job.status !== 'failed' && job.status !== 'rejected' ?
                    '<button class="btn-modern-outline" id="reportIssueBtn"><i class="fas fa-flag"></i> Report Issue</button>' : ''
                }
            </div>
        </div>
    `;

    detailContainer.innerHTML = detailHtml;

    // Event listeners 
    document.getElementById('viewRequestBtn')?.addEventListener('click', () => {
        alert(`View request: ${job.requestId}`);
    });

    document.getElementById('viewRequestBtn2')?.addEventListener('click', () => {
        alert(`View request: ${job.requestId}`);
    });

    const reportBtn = document.getElementById('reportIssueBtn');
    if (reportBtn) {
        reportBtn.addEventListener('click', () => {
            const reason = prompt('Describe the issue:');
            if (reason) alert(`Issue reported: ${reason}`);
        });
    }
}

// Back button
backBtn.addEventListener('click', () => {
    renderListView();
});

// Dark mode functions
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

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    
    const themeToggleDesktop = document.getElementById('themeToggleDesktop');
    const themeToggleMobile = document.getElementById('themeToggleMobile');
    
    if (themeToggleDesktop) {
        themeToggleDesktop.addEventListener('click', (e) => {
            e.preventDefault();
            toggleTheme();
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        });
    }
    
    if (themeToggleMobile) {
        themeToggleMobile.addEventListener('click', (e) => {
            e.preventDefault();
            toggleTheme();
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        });
    }

    renderListView();
});

// Global functions
window.hideMenu = window.hideMenu || function() { 
    document.getElementById('sidebarNav')?.classList.remove('open'); 
    document.getElementById('cover')?.classList.remove('active'); 
};

window.showMenu = window.showMenu || function() { 
    document.getElementById('sidebarNav')?.classList.add('open'); 
    document.getElementById('cover')?.classList.add('active'); 
};