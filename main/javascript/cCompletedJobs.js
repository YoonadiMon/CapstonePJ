/* cJobHistory.js — Sample data + UI logic for cJobHistory.html */

// ─── SAMPLE DATA — mirrors cJobDetails.js, status set to completed ─
const historyData = [
    {
        id: "JOB001",
        status: "completed",
        date: "27-2-2026",
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
                description: "Screen cracked, fully functional otherwise",
                img: "https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&q=80"
            },
            {
                id: "ITEM002",
                name: "Smartphone",
                brand: "iPhone 12 Pro",
                weight: "0.4",
                dropoff: "Selangor Collection Center",
                description: "Battery swollen, needs replacement",
                img: "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800&q=80"
            },
            {
                id: "ITEM003",
                name: "Tablet",
                brand: "Samsung Galaxy Tab S7",
                weight: "1.2",
                dropoff: "Petaling Jaya Center",
                description: "Charging port damaged",
                img: "https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=800&q=80"
            }
        ]
    },
    {
        id: "JOB002",
        status: "completed",
        date: "18-2-2026",
        provider: {
            name: "Ali Hassan",
            address: "Jalan Ampang, 50450, Kuala Lumpur",
            date: "18/02/2026"
        },
        items: [
            {
                id: "ITEM004",
                name: "Tablet",
                brand: "Samsung Galaxy Tab S7",
                weight: "1.2",
                dropoff: "Petaling Jaya Center",
                description: "Charging port damaged",
                img: "https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=800&q=80"
            },
            {
                id: "ITEM005",
                name: "Printer",
                brand: "HP LaserJet Pro M428",
                weight: "6.5",
                dropoff: "Petaling Jaya Center",
                description: "Paper jam issues, otherwise working",
                img: "https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?w=800&q=80"
            },
            {
                id: "ITEM005",
                name: "Monitor",
                brand: "LG 27UK850",
                weight: "5.8",
                dropoff: "Petaling Jaya Center",
                description: "Dead pixels on left side",
                img: "https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=800&q=80"
            }
        ]
    },
    {
        id: "JOB003",
        status: "completed",
        date: "19-2-2026",
        provider: {
            name: "Priya Nair",
            address: "Subang Jaya, 47500, Selangor",
            date: "19/02/2026"
        },
        items: [
            {
                id: "ITEM006",
                name: "Desktop PC",
                brand: "Lenovo ThinkCentre",
                weight: "8.3",
                dropoff: "Shah Alam Drop Point",
                description: "PSU faulty, rest functional",
                img: "https://images.unsplash.com/photo-1591489378430-ef2f4c626b35?w=800&q=80"
            }
        ]
    },
    {
        id: "JOB004",
        status: "interrupted",
        date: "15-2-2026",
        provider: {
            name: "Tan Wei Ming",
            address: "Puchong, 47100, Selangor",
            date: "15/02/2026"
        },
        items: [
            {
                id: "ITEM007",
                name: "Laptop",
                brand: "Asus VivoBook 15",
                weight: "1.8",
                dropoff: "Selangor Collection Center",
                description: "Keyboard not working",
                img: "https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&q=80"
            },
            {
                id: "ITEM008",
                name: "Keyboard",
                brand: "Logitech MX Keys",
                weight: "0.8",
                dropoff: "Selangor Collection Center",
                description: "Some keys unresponsive",
                img: "https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=800&q=80"
            }
        ]
    }
];

// ─── STATE ────────────────────────────────────────────────────
let activeJobId = null;
let filteredData = [...historyData];

// ─── INIT ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    renderStats();
    renderJobList(historyData);
});

// ─── STATS ───────────────────────────────────────────────────
function renderStats() {
    let totalJobs = historyData.length;
    let totalItems = 0;
    let totalWeight = 0;

    historyData.forEach(job => {
        job.items.forEach(item => {
            totalItems++;
            totalWeight += parseFloat(item.weight);
        });
    });

    document.getElementById('statJobs').textContent = totalJobs;
    document.getElementById('statItems').textContent = totalItems;
    document.getElementById('statWeight').textContent = totalWeight.toFixed(1) + ' kg';
}

// ─── JOB LIST ─────────────────────────────────────────────────
function renderJobList(data) {
    const list = document.getElementById('historyJobList');
    list.innerHTML = '';

    if (data.length === 0) {
        list.innerHTML = '<div class="history-empty-list">No jobs found</div>';
        return;
    }

    data.forEach(job => {
        const item = document.createElement('div');
        item.className = 'history-job-item' + (job.id === activeJobId ? ' active' : '');
        item.dataset.jobId = job.id;
        item.innerHTML = `
            <span class="history-job-id">${job.id}</span>
            <span class="history-job-date">${job.date}</span>
        `;
        item.addEventListener('click', () => selectJob(job.id));
        list.appendChild(item);
    });
}

// ─── SEARCH / FILTER ──────────────────────────────────────────
function filterHistory() {
    const query = document.getElementById('historySearch').value.toLowerCase().trim();
    filteredData = historyData.filter(job =>
        job.id.toLowerCase().includes(query) ||
        job.provider.name.toLowerCase().includes(query) ||
        job.date.includes(query)
    );
    renderJobList(filteredData);
}

// ─── SELECT JOB & SHOW DETAIL ─────────────────────────────────
function selectJob(jobId) {
    activeJobId = jobId;

    // Update active state in list
    document.querySelectorAll('.history-job-item').forEach(el => {
        el.classList.toggle('active', el.dataset.jobId === jobId);
    });

    const job = historyData.find(j => j.id === jobId);
    if (!job) return;

    // Show detail panel
    document.getElementById('detailEmpty').style.display = 'none';
    const content = document.getElementById('detailContent');
    content.style.display = 'block';

    // Header
    document.getElementById('detailJobId').textContent = job.id;
    const badge = document.getElementById('detailStatus');
    badge.textContent = capitalize(job.status);
    badge.className = 'detail-badge badge-' + job.status;

    document.getElementById('detailDate').textContent = job.date;
    document.getElementById('detailProvider').textContent = job.provider.name;

    // Overview
    document.getElementById('dProviderName').textContent = job.provider.name;
    document.getElementById('dProviderAddress').textContent = job.provider.address;
    document.getElementById('dProviderDate').textContent = job.provider.date;

    const itemList = document.getElementById('dItemList');
    const brandList = document.getElementById('dBrandList');
    itemList.innerHTML = '';
    brandList.innerHTML = '';

    let totalWeight = 0;
    job.items.forEach((item, i) => {
        totalWeight += parseFloat(item.weight);
        itemList.innerHTML += `<li>${i + 1}. ${item.name}</li>`;
        brandList.innerHTML += `<li>${i + 1}. ${item.brand}</li>`;
    });

    document.getElementById('dTotalWeight').textContent = totalWeight.toFixed(1) + ' kg total';

    // Item dropdowns
    const dropdowns = document.getElementById('dItemDropdowns');
    dropdowns.innerHTML = '';
    job.items.forEach(item => {
        const el = document.createElement('div');
        el.className = 'item-dropdown';
        el.innerHTML = `
            <div class="item-dropdown-header">
                <span>${item.id} — ${item.name}</span>
                <span class="dropdown-arrow">▼</span>
            </div>
            <div class="item-dropdown-content">
                <div class="item-grid-with-image">
                    <div class="item-image-col">
                        <img src="${item.img}" alt="${item.name}" class="item-sample-img">
                        <a class="view-full-pic-link" onclick="openHistoryModal('${item.img}','${item.name}')">View full pic</a>
                    </div>
                    <div class="item-details-col">
                        <p><strong>Item</strong> ${item.name}</p>
                        <p><strong>Brand</strong> ${item.brand}</p>
                        <p><strong>Weight</strong> ${item.weight} kg</p>
                        <p><strong>Drop-off</strong> ${item.dropoff}</p>
                        <p><strong>Note</strong> ${item.description}</p>
                    </div>
                </div>
            </div>
        `;

        const header = el.querySelector('.item-dropdown-header');
        const body   = el.querySelector('.item-dropdown-content');
        const arrow  = el.querySelector('.dropdown-arrow');

        header.addEventListener('click', () => {
            body.classList.toggle('active');
            arrow.classList.toggle('rotate');
        });

        dropdowns.appendChild(el);
    });
}

// ─── IMAGE MODAL ──────────────────────────────────────────────
function openHistoryModal(src, name) {
    document.getElementById('historyModalImg').src = src;
    document.getElementById('historyModalCaption').textContent = name;
    document.getElementById('historyImageModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeHistoryModal() {
    document.getElementById('historyImageModal').classList.remove('active');
    document.body.style.overflow = '';
}

// ─── UTIL ─────────────────────────────────────────────────────
function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}