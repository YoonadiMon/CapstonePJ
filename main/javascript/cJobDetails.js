

// SAMPLE DATA (Replace with PHP API later)
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
        ]
    }
};

// Get job ID from URL
const urlParams = new URLSearchParams(window.location.search);
const jobId = urlParams.get("id");

document.addEventListener('DOMContentLoaded', function() {
    if (jobId && jobDetailsData[jobId]) {
        loadJobDetails(jobDetailsData[jobId], jobId);
        animateEntrance();
    } else {
        document.querySelector('.job-details-container').innerHTML = 
            '<div style="text-align:center;padding:4rem;color:var(--Gray);"><h2>Job not found</h2><p>Please go back and select a valid job</p></div>';
    }
});

function loadJobDetails(data, jobId) {
    // Header
    document.getElementById("jobTitle").textContent = jobId;
    const statusBadge = document.getElementById("jobStatus");
    statusBadge.textContent = capitalize(data.status);
    statusBadge.className = `job-status-badge ${data.status}`;

    // Provider info
    document.getElementById("providerName").textContent = data.provider.name;
    document.getElementById("providerAddress").textContent = data.provider.address;
    document.getElementById("providerDate").textContent = data.provider.date;

    // Calculate total weight
    let totalWeight = 0;
    const itemList = document.getElementById("itemList");
    const brandList = document.getElementById("brandList");

    data.items.forEach((item, index) => {
        totalWeight += parseFloat(item.weight);
        itemList.innerHTML += `<li>${index + 1}. ${item.name}</li>`;
        brandList.innerHTML += `<li>${index + 1}. ${item.brand}</li>`;
        createItemCard(item);
    });

    document.getElementById("totalWeight").textContent = totalWeight.toFixed(1);
}


// function createItemCard(item) {
//     const container = document.getElementById("itemsContainer");

//     const card = document.createElement("div");
//     card.className = "item-dropdown";

//     card.innerHTML = `
//         <div class="item-dropdown-header">
//             <h3>${item.id}</h3>
//             <span class="dropdown-arrow">▼</span>
//         </div>

//         <div class="item-dropdown-content">
//             <div class="item-grid">
//                 <div>
//                     <p><strong>Item:</strong> ${item.name}</p>
//                     <p><strong>Brand:</strong> ${item.brand}</p>
//                     <p><strong>Weight:</strong> ${item.weight} kg</p>
//                 </div>
//                 <div>
//                     <p><strong>Drop-off:</strong> ${item.dropoff}</p>
//                     <p><strong>Description:</strong> ${item.description}</p>
//                 </div>
//             </div>
//         </div>
//     `;

//     // Toggle logic
//     const header = card.querySelector(".item-dropdown-header");
//     const content = card.querySelector(".item-dropdown-content");
//     const arrow = card.querySelector(".dropdown-arrow");

//     header.addEventListener("click", () => {
//         content.classList.toggle("active");
//         arrow.classList.toggle("rotate");
//     });

//     container.appendChild(card);
// }


function createItemCard(item) {
    const container = document.getElementById("itemsContainer");

    const sampleImages = {
        "Laptop": "https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&q=80",
        "Smartphone": "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800&q=80",
        "Tablet": "https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=800&q=80"
    };
    const imgSrc = sampleImages[item.name] || "https://placehold.co/600x400?text=No+Image";

    const card = document.createElement("div");
    card.className = "item-dropdown";

    card.innerHTML = `
        <div class="item-dropdown-header">
            <h3>${item.id}</h3>
            <span class="dropdown-arrow">▼</span>
        </div>

        <div class="item-dropdown-content">
            <div class="item-grid-with-image">
                <div class="item-image-col">
                    <img src="${imgSrc}" alt="${item.name}" class="item-sample-img" />
                    <a class="view-full-pic-link" onclick="openImageModal('${imgSrc}', '${item.name}')">View full Pic</a>
                </div>
                <div class="item-details-col">
                    <div class="item-grid">
                        <div>
                            <p><strong>Item:</strong> ${item.name}</p>
                            <p><strong>Brand:</strong> ${item.brand}</p>
                            <p><strong>Weight:</strong> ${item.weight} kg</p>
                        </div>
                        <div>
                            <p><strong>Drop-off:</strong> ${item.dropoff}</p>
                            <p><strong>Description:</strong> ${item.description}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    const header = card.querySelector(".item-dropdown-header");
    const content = card.querySelector(".item-dropdown-content");
    const arrow = card.querySelector(".dropdown-arrow");

    header.addEventListener("click", () => {
        content.classList.toggle("active");
        arrow.classList.toggle("rotate");
    });

    container.appendChild(card);
}

// Modal functions
function openImageModal(src, name) {
    document.getElementById("modalImg").src = src;
    document.getElementById("modalCaption").textContent = name;
    document.getElementById("imageModal").classList.add("active");
    document.body.style.overflow = "hidden";
}

function closeImageModal() {
    document.getElementById("imageModal").classList.remove("active");
    document.body.style.overflow = "";
}


function capitalize(text) {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

// Action handlers
function acceptJob() {
    alert('Job accepted! 🚀');
    // Add PHP API call here
}

function rejectJob() {
    if (confirm('Reject this job?')) {
        alert('Job rejected.');
        // Add PHP API call here
    }
}

function animateEntrance() {
    const container = document.querySelector('.job-details-container');
    container.style.opacity = '0';
    container.style.transform = 'translateY(20px)';
    setTimeout(() => {
        container.style.transition = 'all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        container.style.opacity = '1';
        container.style.transform = 'translateY(0)';
    }, 100);
}
