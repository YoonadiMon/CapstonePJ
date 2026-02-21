
// SAMPLE JOB DATA

const jobsData = [
    { id: 'JOB001', status: 'pending', items: 3, weight: 5.4, date: '2026-02-12' },
    { id: 'JOB002', status: 'accepted', items: 3, weight: 5.4, date: '2026-04-15' },
    { id: 'JOB003', status: 'pending', items: 3, weight: 5.4, date: '2026-02-12' },
    { id: 'JOB004', status: 'pending', items: 3, weight: 5.4, date: '2026-02-27' },
    { id: 'JOB005', status: 'ongoing', items: 5, weight: 8.2, date: '2026-03-10' },
    { id: 'JOB006', status: 'accepted', items: 2, weight: 3.1, date: '2026-04-05' },
    { id: 'JOB007', status: 'pending', items: 4, weight: 6.7, date: '2026-05-18' },
    { id: 'JOB008', status: 'ongoing', items: 6, weight: 9.3, date: '2026-03-22' }
];


// GLOBAL VARIABLES

let currentMonth = new Date();
let selectedDate = null;



// PAGE LOAD

document.addEventListener('DOMContentLoaded', function () {

    renderJobs(jobsData);
    renderCalendar();

    // Search enter key
    const searchInput = document.getElementById('jobSearchInput');
    searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });
});



// MAIN FILTER FUNCTION

function applyFilters() {

    const searchText = document.getElementById('jobSearchInput').value.toLowerCase();

    const filterOngoing = document.getElementById('filterOngoing').checked;
    const filterAccepted = document.getElementById('filterAccepted').checked;
    const filterPending = document.getElementById('filterPending').checked;

    const selectedStatuses = [];

    if (filterOngoing) selectedStatuses.push('ongoing');
    if (filterAccepted) selectedStatuses.push('accepted');
    if (filterPending) selectedStatuses.push('pending');

    let result = jobsData;

    //  SEARCH FILTER
    if (searchText !== '') {
        result = result.filter(function (job) {
            return job.id.toLowerCase().includes(searchText);
        });
    }

    //  STATUS FILTER
    if (selectedStatuses.length > 0) {
        result = result.filter(function (job) {
            return selectedStatuses.includes(job.status);
        });
    }

    //  DATE FILTER
    if (selectedDate !== null) {
        result = result.filter(function (job) {
            return job.date === selectedDate;
        });
    }

    renderJobs(result);
}



// SEARCH BUTTON

function searchJobs() {
    applyFilters();
}



// STATUS FILTER

function filterJobs() {
    applyFilters();
}



// CLEAR FILTERS

function clearFilters() {

    document.getElementById('jobSearchInput').value = '';

    document.getElementById('filterOngoing').checked = false;
    document.getElementById('filterAccepted').checked = false;
    document.getElementById('filterPending').checked = false;

    selectedDate = null;

    renderJobs(jobsData);
    renderCalendar();
}



// RENDER JOB CARDS

function renderJobs(jobs) {

    const jobsGrid = document.getElementById('jobsGrid');
    jobsGrid.innerHTML = '';

    if (jobs.length === 0) {
        displayEmptyState('No jobs found.');
        return;
    }

    for (let i = 0; i < jobs.length; i++) {
        const card = createJobCard(jobs[i]);
        jobsGrid.appendChild(card);
    }
}



// CREATE JOB CARD

// function createJobCard(job) {

//     const card = document.createElement('div');
//     card.className = 'job-card';

//     // Simple date format (DD/MM/YYYY)
//     const formattedDate = job.date.split('-').reverse().join('/');

//     card.innerHTML = `
//         <div class="job-card-header">
//             <span class="job-card-status ${job.status}">${job.status}</span>
//             <h2 class="job-card-title">${job.id}</h2>
//         </div>
//         <div class="job-card-details">
//             <div class="job-card-item">
//                 <span class="job-card-label">Item Number:</span>
//                 <span class="job-card-value">${job.items}</span>
//             </div>
//             <div class="job-card-item">
//                 <span class="job-card-label">Total Weight:</span>
//                 <span class="job-card-value">${job.weight} kg</span>
//             </div>
//             <div class="job-card-item">
//                 <span class="job-card-label">Schedule Date:</span>
//                 <span class="job-card-value">${formattedDate}</span>
//             </div>
//         </div>
//         <a href="cJobDetails.html?id=${job.id}" class="job-card-more-btn">More</a>
//     `;

//     return card;
// }


function createJobCard(job) {
    const card = document.createElement('div');
    card.className = 'job-card';
    const formattedDate = job.date.split('-').reverse().join('/');

    // ✅ FIXED: Use 'isJob001' consistently (was 'isJOB001')
    const isJob001 = job.id === 'JOB001';
    const moreBtnHref = isJob001 ? `./cJobsDetail.html?id=${job.id}` : '#';

    card.innerHTML = `
        <div class="job-card-header">
            <span class="job-card-status ${job.status}">
                ${isJob001 ? '🔥 ' : ''}${job.status.toUpperCase()}
            </span>
            <h2 class="job-card-title">${job.id}</h2>
        </div>
        <div class="job-card-details">
            <div class="job-card-item">
                <span class="job-card-label">📦 Items:</span>
                <span class="job-card-value">${job.items}</span>
            </div>
            <div class="job-card-item">
                <span class="job-card-label">⚖️ Weight:</span>
                <span class="job-card-value">${job.weight} kg</span>
            </div>
            <div class="job-card-item">
                <span class="job-card-label">📅 Date:</span>
                <span class="job-card-value">${formattedDate}</span>
            </div>
        </div>
        <a href="${moreBtnHref}" class="job-card-more-btn ${!isJob001 ? 'disabled' : ''}"
           ${!isJob001 ? 'onclick="return false;"' : ''}>
            ${isJob001 ? 'View Details →' : 'Coming Soon'}
        </a>
    `;
    return card;
}




// EMPTY STATE

function displayEmptyState(message) {

    const jobsGrid = document.getElementById('jobsGrid');

    jobsGrid.innerHTML = `
        <div class="jobs-empty-state">
            <h2>No Jobs Found</h2>
            <p>${message}</p>
        </div>
    `;
}



// CALENDAR

function renderCalendar() {

    const year = currentMonth.getFullYear();
    const month = currentMonth.getMonth();

    const monthNames = [
        'January','February','March','April','May','June',
        'July','August','September','October','November','December'
    ];

    document.getElementById('monthDisplay').textContent = monthNames[month];

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    const calendarDates = document.getElementById('calendarDates');
    calendarDates.innerHTML = '';

    // Previous month
    for (let i = firstDay - 1; i >= 0; i--) {
        const date = document.createElement('div');
        date.className = 'jobs-calendar-date other-month';
        date.textContent = daysInPrevMonth - i;
        calendarDates.appendChild(date);
    }

    const today = new Date();

    // Current month
    // Current month
for (let day = 1; day <= daysInMonth; day++) {

    const date = document.createElement('div');
    date.className = 'jobs-calendar-date';
    date.textContent = day;

    const currentDate = new Date(year, month, day);

    // ✅ FIXED DATE FORMAT
    const yearStr = currentDate.getFullYear();
    const monthStr = String(currentDate.getMonth() + 1).padStart(2, '0');
    const dayStr = String(currentDate.getDate()).padStart(2, '0');

    const dateStr = yearStr + '-' + monthStr + '-' + dayStr;

    const today = new Date();

    if (currentDate.toDateString() === today.toDateString()) {
        date.classList.add('today');
    }

    if (selectedDate === dateStr) {
        date.classList.add('selected');
    }

    date.addEventListener('click', function () {
        selectDate(dateStr);
    });

    calendarDates.appendChild(date);
}

    // Fill remaining cells
    const totalCells = calendarDates.children.length;
    const remaining = 35 - totalCells;

    for (let i = 1; i <= remaining; i++) {
        const date = document.createElement('div');
        date.className = 'jobs-calendar-date other-month';
        date.textContent = i;
        calendarDates.appendChild(date);
    }
}


function previousMonth() {
    currentMonth.setMonth(currentMonth.getMonth() - 1);
    renderCalendar();
}


function nextMonth() {
    currentMonth.setMonth(currentMonth.getMonth() + 1);
    renderCalendar();
}


function selectDate(dateStr) {

    if (selectedDate === dateStr) {
        selectedDate = null;
    } else {
        selectedDate = dateStr;
    }

    renderCalendar();
    applyFilters();
}