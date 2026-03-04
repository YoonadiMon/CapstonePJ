document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Initializing Requests Page');
    
    // Sample data
    const sampleRequests = [
        {
            id: 'REQ001',
            title: 'Laptop & monitor',
            status: 'pending',
            user: 'Tan Wei Ming',
            date: '26 Feb 2026',
            weight: '8.2',
            statusText: 'pending',
            description: 'Laptop doesn\'t power on, screen flickers. Monitor works but has scratch.',
            brand: 'Dell XPS 13 9360 · LG 24MP58',
            address: 'No 12, Jalan SS2/72, Petaling Jaya, Selangor, 47300',
            items: 'Laptop (Dell XPS 13) · 19" monitor (LG) · keyboard',
            contact: '+60 12-345 6789',
            sentDate: '24 Feb 2026, 09:23'
        },
        {
            id: 'REQ009',
            title: 'Office Electronics',
            status: 'pending',
            user: 'Tech Solutions',
            date: '03 Mar 2026',
            weight: '18.5',
            statusText: 'pending',
            description: 'Bulk office electronics from company renovation. All items are 3-5 years old.',
            brand: 'Dell, Logitech, HP',
            address: 'Level 15, Menara Standard Chartered, Kuala Lumpur',
            items: 'Monitors (5 pcs) · Keyboards (10 pcs) · Mice (8 pcs)',
            contact: '+60 12-987 6543',
            sentDate: '03 Mar 2026, 10:15'
        },
        {
            id: 'REQ010',
            title: 'Home Entertainment',
            status: 'pending',
            user: 'Kevin Tan',
            date: '02 Mar 2026',
            weight: '22.3',
            statusText: 'pending',
            description: 'Complete home entertainment setup. TV has minor scratch on screen.',
            brand: 'Sony Bravia, Bose, PlayStation 4',
            address: 'No 15, Jalan Setia, Bangsar, 59100',
            items: 'LCD TV · Home Theater System · Gaming Console',
            contact: '+60 16-234 5678',
            sentDate: '02 Mar 2026, 14:30'
        },
        {
            id: 'REQ011',
            title: 'Network Equipment',
            status: 'pending',
            user: 'Network Solutions',
            date: '01 Mar 2026',
            weight: '9.8',
            statusText: 'pending',
            description: 'Used network equipment from office upgrade. Some units may need repair.',
            brand: 'Cisco, MikroTik, Ubiquiti',
            address: 'No 89, Jalan Technology, Cyberjaya, 63000',
            items: 'Routers (3 pcs) · Switches (2 pcs) · Firewall',
            contact: '+60 13-456 7890',
            sentDate: '01 Mar 2026, 09:45'
        },
        {
            id: 'REQ012',
            title: 'Printer Bundle',
            status: 'pending',
            user: 'PrintHub',
            date: '29 Feb 2026',
            weight: '32.0',
            statusText: 'pending',
            description: 'Multiple printers from closing office. Some need toner replacement.',
            brand: 'HP, Canon, Epson',
            address: 'No 34, Jalan Industri, Shah Alam, 40200',
            items: 'Laser Printers (2 pcs) · Inkjet Printer · Scanner',
            contact: '+60 14-567 8901',
            sentDate: '29 Feb 2026, 11:20'
        }
    ];

    // DOM Elements
    const listView = document.getElementById('requestListView');
    const detailView = document.getElementById('requestDetailView');
    const backToListBtn = document.getElementById('backToListBtn');
    const sortDropdownBtn = document.getElementById('sortDropdownBtn');
    const sortDropdownContent = document.getElementById('sortDropdownContent');
    const sortLinks = document.querySelectorAll('.sort-dropdown-content a');
    const requestCount = document.getElementById('requestCount');
    const searchInput = document.getElementById('searchInput');
    const requestsContainer = document.getElementById('requestsContainer');
    const approveBtn = document.getElementById('approveBtn');
    const rejectBtn = document.getElementById('rejectBtn');

    // Detail view elements
    const detailReqId = document.getElementById('detailReqId');
    const detailStatus = document.getElementById('detailStatus');
    const detailProvider = document.getElementById('detailProvider');
    const detailItems = document.getElementById('detailItems');
    const detailDescription = document.getElementById('detailDescription');
    const detailBrand = document.getElementById('detailBrand');
    const detailWeight = document.getElementById('detailWeight');
    const detailAddress = document.getElementById('detailAddress');
    const detailDate = document.getElementById('detailDate');
    const detailSentDate = document.getElementById('detailSentDate');

    // State
    let currentFilter = 'pending'; 
    let currentSearch = '';
    let currentSort = 'date-desc';
    let filteredRequests = [...sampleRequests];
    let currentRequest = null;

    // Request count
    function updateRequestCount() {
        if (requestCount) {
            requestCount.textContent = `${filteredRequests.length} pending ${filteredRequests.length === 1 ? 'request' : 'requests'}`;
        }
    }

    function filterRequests() {
        filteredRequests = sampleRequests.filter(request => {
            const searchTerm = currentSearch.toLowerCase();
            const matchesSearch = currentSearch === '' || 
                request.id.toLowerCase().includes(searchTerm) ||
                request.user.toLowerCase().includes(searchTerm) ||
                request.title.toLowerCase().includes(searchTerm);
            
            return matchesSearch;
        });

        sortRequests();
        renderRequestCards();
        updateRequestCount();
    }

    // Sort function
    function sortRequests() {
        filteredRequests.sort((a, b) => {
            switch(currentSort) {
                case 'date-desc':
                    return new Date(b.date) - new Date(a.date);
                case 'date-asc':
                    return new Date(a.date) - new Date(b.date);
                case 'name-asc':
                    return a.user.localeCompare(b.user);
                case 'name-desc':
                    return b.user.localeCompare(a.user);
                case 'weight-desc':
                    return parseFloat(b.weight) - parseFloat(a.weight);
                case 'weight-asc':
                    return parseFloat(a.weight) - parseFloat(b.weight);
                default:
                    return 0;
            }
        });
    }

    // Render cards
    function renderRequestCards() {
        console.log('Rendering pending request cards');
        if (!requestsContainer) return;
        
        if (filteredRequests.length === 0) {
            requestsContainer.innerHTML = '<div class="no-results">No pending requests found</div>';
            return;
        }
        
        let html = '';
        filteredRequests.forEach(req => {
            html += `
                <div class="req-card" data-req="${req.id}">
                    <div class="req-info">
                        <div class="req-id-status">
                            <span class="req-id">#${req.id} · ${req.title}</span>
                            <span class="status ${req.status}">${req.status}</span>
                        </div>
                        <div class="req-meta">
                            <span><i class="fas fa-user"></i> ${req.user}</span>
                            <span><i class="fas fa-calendar"></i> ${req.date}</span>
                            <span><i class="fas fa-weight-hanging"></i> ${req.weight} kg</span>
                        </div>
                    </div>
                    <div class="arrow-btn"><i class="fas fa-chevron-right"></i></div>
                </div>
            `;
        });
        requestsContainer.innerHTML = html;

        attachCardEventListeners();
    }

    function attachCardEventListeners() {
        const cards = document.querySelectorAll('.req-card');
        cards.forEach(card => {
            card.addEventListener('click', function(e) {
                e.preventDefault();
                const reqId = this.getAttribute('data-req');
                const request = sampleRequests.find(r => r.id === reqId);
                if (request) {
                    updateDetailView(request);
                    showDetail();
                }
            });
        });
    }

    // Detail view
    function updateDetailView(request) {
        currentRequest = request;
        
        detailReqId.textContent = `#${request.id}`;
        detailStatus.className = `big-status ${request.status}`;
        detailStatus.textContent = request.statusText;
        detailProvider.innerHTML = `<i class="fas fa-store"></i> ${request.user} · ${request.contact}`;
        detailItems.textContent = request.items;
        detailDescription.textContent = request.description;
        detailBrand.textContent = request.brand;
        detailWeight.textContent = `${request.weight} kg (total)`;
        detailAddress.textContent = request.address;
        detailDate.textContent = request.date;
        detailSentDate.textContent = request.sentDate;
    }

    function showList() {
        listView.classList.remove('hidden');
        detailView.classList.add('hidden');
    }

    function showDetail() {
        listView.classList.add('hidden');
        detailView.classList.remove('hidden');
    }

    if (sortDropdownBtn) {
        sortDropdownBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            sortDropdownContent.classList.toggle('show');
            if (filterDropdownContent) filterDropdownContent.classList.remove('show');
        });
    }

    document.addEventListener('click', function(e) {
    if (sortDropdownContent && !sortDropdownBtn?.contains(e.target) && !sortDropdownContent.contains(e.target)) {
        sortDropdownContent.classList.remove('show');
    }
    });

    sortLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            sortLinks.forEach(l => l.classList.remove('active-sort'));
            this.classList.add('active-sort');
            
            currentSort = this.getAttribute('data-sort');
            
            if (sortDropdownBtn) {
                const sortText = this.textContent.trim();
                sortDropdownBtn.innerHTML = `<i class="fas fa-sort-amount-down"></i> ${sortText} <i class="fas fa-chevron-down"></i>`;
            }
            
            filterRequests(); 
            sortDropdownContent.classList.remove('show');
        });
    });

    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearch = this.value;
                filterRequests();
            }, 300);
        });
    }

    if (backToListBtn) {
        backToListBtn.addEventListener('click', showList);
    }

    // Approve button
if (approveBtn) {
    approveBtn.addEventListener('click', function() {
        if (currentRequest) {
            const modalOverlay = document.createElement('div');
            modalOverlay.className = 'approve-modal-overlay';

            const modalContent = document.createElement('div');
            modalContent.className = 'approve-modal-content';
            modalContent.innerHTML = `
                <div class="approve-modal-header">
                    <i class="fas fa-check-circle"></i>
                    <h3>Request Approved!</h3>
                    <button class="approve-modal-close" id="closeApproveModal">&times;</button>
                </div>
                <div class="approve-modal-body">
                    <p>Request <strong>#${currentRequest.id}</strong> has been approved.</p>
                    <div class="request-summary">
                        <div><i class="fas fa-user"></i> ${currentRequest.user}</div>
                        <div><i class="fas fa-box"></i> ${currentRequest.items}</div>
                        <div><i class="fas fa-weight-hanging"></i> ${currentRequest.weight} kg</div>
                    </div>
                </div>
                <div class="approve-modal-footer">
                    <button class="btn btn-primary" id="goToOperationsBtn">
                        <i class="fas fa-truck"></i> Assign Collector
                    </button>
                </div>
            `;
            
            modalOverlay.appendChild(modalContent);
            document.body.appendChild(modalOverlay);

const approvedRequest = {
    id: currentRequest.id,
    provider: currentRequest.user,
    items: currentRequest.items.split(' · '), 
    address: currentRequest.address,
    preferredDate: currentRequest.date,
    weight: currentRequest.weight,
    description: currentRequest.description,
    contact: currentRequest.contact,
    status: 'approved'
};

let approvedRequests = JSON.parse(sessionStorage.getItem('approvedRequests')) || [];
approvedRequests.push(approvedRequest);
sessionStorage.setItem('approvedRequests', JSON.stringify(approvedRequests));

console.log('Stored approved requests:', approvedRequests);
       
            const index = sampleRequests.findIndex(r => r.id === currentRequest.id);
            if (index !== -1) {
                sampleRequests.splice(index, 1);
                filterRequests();
            }
            
            // Assign Collector 
            document.getElementById('goToOperationsBtn').addEventListener('click', function() {
                window.location.href = '/main/html/admin/aOperations.html';
            });
         
            document.getElementById('closeApproveModal').addEventListener('click', function() {
                document.body.removeChild(modalOverlay);
                showList(); // Go back to list view
            });
      
            modalOverlay.addEventListener('click', function(e) {
                if (e.target === modalOverlay) {
                    document.body.removeChild(modalOverlay);
                    showList();
                }
            });
        }
    });
}

    // Reject button
if (rejectBtn) {
    rejectBtn.addEventListener('click', function() {
        if (currentRequest) {

            const modalOverlay = document.createElement('div');
            modalOverlay.className = 'modal-overlay';

            const modalContent = document.createElement('div');
            modalContent.className = 'modal-content';
            modalContent.innerHTML = `
                <h3><i class="fas fa-times-circle"></i> Reject Request #${currentRequest.id}</h3>
                <p>Please provide a reason for rejection:</p>
                <textarea id="rejectionReason" placeholder="Enter rejection reason..." rows="4"></textarea>
                <div class="modal-buttons">
                    <button class="modal-btn cancel" id="cancelReject">Cancel</button>
                    <button class="modal-btn confirm" id="confirmReject">Confirm Rejection</button>
                </div>
            `;
            
            modalOverlay.appendChild(modalContent);
            document.body.appendChild(modalOverlay);

            document.getElementById('confirmReject').addEventListener('click', function() {
                const reason = document.getElementById('rejectionReason').value.trim();
                if (!reason) {
                    alert('Please enter a rejection reason');
                    return;
                }
                
                alert(`Request #${currentRequest.id} has been rejected. Reason: ${reason}`);
         
                const index = sampleRequests.findIndex(r => r.id === currentRequest.id);
                if (index !== -1) {
                    sampleRequests.splice(index, 1);
                    filterRequests();
                    showList();
                }
                
                document.body.removeChild(modalOverlay);
            });
        
            document.getElementById('cancelReject').addEventListener('click', function() {
                document.body.removeChild(modalOverlay);
            });
     
            modalOverlay.addEventListener('click', function(e) {
                if (e.target === modalOverlay) {
                    document.body.removeChild(modalOverlay);
                }
            });
        }
    });
}

    filterRequests(); 

    sortLinks.forEach(link => {
        if (link.getAttribute('data-sort') === 'date-desc') {
            link.classList.add('active-sort');
        }
    });
    
    console.log('Initialization complete');
});