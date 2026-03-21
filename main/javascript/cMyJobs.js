// NOTE: jobsData is injected by PHP in cMyJobs.php as a <script> block above this file.
// No hardcoded sample data here.

// GLOBAL VARIABLES
let currentMonth = new Date();
let selectedDate = null;


// PAGE LOAD
document.addEventListener('DOMContentLoaded', function () {
    renderJobs(jobsData);
    renderCalendar();

    const searchInput = document.getElementById('jobSearchInput');
    searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') applyFilters();
    });
});


// MAIN FILTER FUNCTION
function applyFilters() {
    const searchText     = document.getElementById('jobSearchInput').value.toLowerCase();
    const filterOngoing  = document.getElementById('filterOngoing').checked;
    const filterAccepted = document.getElementById('filterAccepted').checked;
    const filterPending  = document.getElementById('filterPending').checked;

    const selectedStatuses = [];
    if (filterOngoing)  selectedStatuses.push('ongoing');
    if (filterAccepted) selectedStatuses.push('accepted');
    if (filterPending)  selectedStatuses.push('pending');

    let result = jobsData;

    if (searchText !== '') {
        result = result.filter(function (job) {
            return job.id.toLowerCase().includes(searchText);
        });
    }

    if (selectedStatuses.length > 0) {
        result = result.filter(function (job) {
            return selectedStatuses.includes(job.status);
        });
    }

    if (selectedDate !== null) {
        result = result.filter(function (job) {
            return job.date === selectedDate;
        });
    }

    renderJobs(result);
}

function searchJobs() { applyFilters(); }
function filterJobs()  { applyFilters(); }

function clearFilters() {
    document.getElementById('jobSearchInput').value   = '';
    document.getElementById('filterOngoing').checked  = false;
    document.getElementById('filterAccepted').checked = false;
    document.getElementById('filterPending').checked  = false;
    selectedDate = null;
    renderJobs(jobsData);
    renderCalendar();
}


// RENDER JOB CARDS
function renderJobs(jobs) {
    const jobsGrid = document.getElementById('jobsGrid');
    jobsGrid.innerHTML = '';
    if (jobs.length === 0) { displayEmptyState('No jobs found.'); return; }
    jobs.forEach(function (job) { jobsGrid.appendChild(createJobCard(job)); });
}

function createJobCard(job) {
    const card = document.createElement('div');
    card.className = 'job-card';

    const formattedDate = job.date.split('-').reverse().join('/');

    // Link uses the real numeric jobID from the database
    const detailHref = './cJobsDetail.php?id=' + job.jobID;

    card.innerHTML = `
        <div class="job-card-header">
            <span class="job-card-status ${job.status}">
                ${job.status.toUpperCase()}
            </span>
            <h2 class="job-card-title">${job.id}</h2>
        </div>
        <div class="job-card-details">
            <div class="job-card-item">
                <span class="job-card-label">Items:</span>
                <span class="job-card-value">${job.items}</span>
            </div>
            <div class="job-card-item">
                <span class="job-card-label">Weight:</span>
                <span class="job-card-value">${job.weight} kg</span>
            </div>
            <div class="job-card-item">
                <span class="job-card-label">Date:</span>
                <span class="job-card-value">${formattedDate}</span>
            </div>
        </div>
        <a href="${detailHref}" class="job-card-more-btn">View Details →</a>
    `;
    return card;
}

function displayEmptyState(message) {
    document.getElementById('jobsGrid').innerHTML = `
        <div class="jobs-empty-state">
            <h2>No Jobs Found</h2>
            <p>${message}</p>
        </div>
    `;
}


// CALENDAR
function renderCalendar() {
    const year  = currentMonth.getFullYear();
    const month = currentMonth.getMonth();

    const monthNames = [
        'January','February','March','April','May','June',
        'July','August','September','October','November','December'
    ];
    document.getElementById('monthDisplay').textContent = monthNames[month];

    const firstDay        = new Date(year, month, 1).getDay();
    const daysInMonth     = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();
    const calendarDates   = document.getElementById('calendarDates');
    const today           = new Date();

    calendarDates.innerHTML = '';

    // Previous month overflow
    for (let i = firstDay - 1; i >= 0; i--) {
        const d = document.createElement('div');
        d.className   = 'jobs-calendar-date other-month';
        d.textContent = daysInPrevMonth - i;
        calendarDates.appendChild(d);
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
        const d       = document.createElement('div');
        d.className   = 'jobs-calendar-date';
        d.textContent = day;

        const cur     = new Date(year, month, day);
        const dateStr = cur.getFullYear() + '-'
                      + String(cur.getMonth() + 1).padStart(2, '0') + '-'
                      + String(cur.getDate()).padStart(2, '0');

        if (cur.toDateString() === today.toDateString()) d.classList.add('today');
        if (selectedDate === dateStr)                     d.classList.add('selected');

        d.addEventListener('click', function () { selectDate(dateStr); });
        calendarDates.appendChild(d);
    }

    // Next month overflow — fill to 35 cells
    const remaining = 35 - calendarDates.children.length;
    for (let i = 1; i <= remaining; i++) {
        const d = document.createElement('div');
        d.className   = 'jobs-calendar-date other-month';
        d.textContent = i;
        calendarDates.appendChild(d);
    }
}

function previousMonth() { currentMonth.setMonth(currentMonth.getMonth() - 1); renderCalendar(); }
function nextMonth()      { currentMonth.setMonth(currentMonth.getMonth() + 1); renderCalendar(); }

function selectDate(dateStr) {
    selectedDate = (selectedDate === dateStr) ? null : dateStr;
    renderCalendar();
    applyFilters();
}