document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Initializing Requests Page');
    
    const sampleRequests = [
        {
            id: 'REQ001',
            title: 'Laptop & monitor',
            status: 'pending',
            user: 'Maya',
            date: '24 Feb 2026',
            weight: '8.2',
            statusText: 'pending'
        },
        {
            id: 'REQ002',
            title: 'Desktop PC, printer',
            status: 'scheduled',
            user: 'Sarah',
            date: '23 Feb 2026',
            weight: '15.0',
            statusText: 'scheduled'
        },
        {
            id: 'REQ003',
            title: 'Mobile phones (5 pcs)',
            status: 'ongoing',
            user: 'Aiman Hakim',
            date: '22 Feb 2026',
            weight: '2.1',
            statusText: 'ongoing'
        },
        {
            id: 'REQ004',
            title: 'CRT TV (broken)',
            status: 'completed',
            user: 'Natasha',
            date: '20 Feb 2026',
            weight: '24.5',
            statusText: 'completed'
        },
        {
            id: 'REQ005',
            title: 'Server rack parts',
            status: 'rejected',
            user: 'James',
            date: '19 Feb 2026',
            weight: '42.0',
            statusText: 'rejected'
        },
        {
            id: 'REQ006',
            title: 'Battery collection',
            status: 'cancelled',
            user: 'Michelle',
            date: '18 Feb 2026',
            weight: '3.5',
            statusText: 'cancelled'
        }
    ];

    function renderRequestCards() {
        console.log('Rendering request cards');
        const container = document.getElementById('requestsContainer');
        if (!container) {
            console.error('requestsContainer not found!');
            return;
        }
        
        let html = '';
        sampleRequests.forEach(req => {
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
        container.innerHTML = html;
        console.log('Cards rendered, count:', sampleRequests.length);
    }

    renderRequestCards();

    const listView = document.getElementById('requestListView');
    const detailView = document.getElementById('requestDetailView');
    const backToListBtn = document.getElementById('backToListBtn');
    
    console.log('List view element:', listView);
    console.log('Detail view element:', detailView);
    console.log('Back button element:', backToListBtn);
 
    const filterDropdownBtn = document.getElementById('filterDropdownBtn');
    const filterDropdownContent = document.getElementById('filterDropdownContent');
    const filterLinks = document.querySelectorAll('.filter-dropdown-content a');

    const sortDropdownBtn = document.getElementById('sortDropdownBtn');
    const sortDropdownContent = document.getElementById('sortDropdownContent');
    const sortLinks = document.querySelectorAll('.sort-dropdown-content a');
    
    const requestCount = document.getElementById('requestCount');
    const searchInput = document.getElementById('searchInput');

    let cards = document.querySelectorAll('.req-card');
    console.log('Cards found after render:', cards.length);

    let currentFilter = 'all';
    let currentSearch = '';
    let currentSort = 'date-desc'; 

    function showList(e) {
        if (e) e.preventDefault();
        console.log('Showing list view');
        
        if (listView) {
            listView.classList.remove('hidden');
            console.log('List view visible');
        }
        if (detailView) {
            detailView.classList.add('hidden');
            console.log('Detail view hidden');
        }
    }

    function showDetail(e) {
        if (e) e.preventDefault();
        console.log('Showing detail view');

        if (filterDropdownContent) {
            filterDropdownContent.classList.remove('show');
        }
        if (sortDropdownContent) {
            sortDropdownContent.classList.remove('show');
        }
        
        if (listView) {
            listView.classList.add('hidden');
            console.log('List view hidden');
        }
        if (detailView) {
            detailView.classList.remove('hidden');
            console.log('Detail view visible');
        }
    }

function updateDetailView(reqId) {
    console.log('Updating detail view for request:', reqId);
   
    const request = sampleRequests.find(req => req.id === reqId);
    console.log('Found request:', request);
    
    if (request) {
     
        const detailTitle = document.querySelector('.detail-badge h2');
        const detailStatus = document.querySelector('.big-status');
        const providerMini = document.querySelector('.provider-mini');
        const actionButtonsContainer = document.querySelector('.action-btns');
        
        console.log('Detail elements found:', {
            title: !!detailTitle,
            status: !!detailStatus,
            provider: !!providerMini,
            actions: !!actionButtonsContainer
        });
        
        if (detailTitle) {
            detailTitle.textContent = '#' + request.id;
            console.log('Updated title to:', '#' + request.id);
        }
        
        if (detailStatus) {
            detailStatus.className = 'big-status';
            detailStatus.classList.add(request.status);
            detailStatus.textContent = request.statusText;
            console.log('Updated status to:', request.statusText);
        }
        
        if (providerMini) {
            providerMini.innerHTML = `<i class="fas fa-store"></i> ${request.user} · +60 12-345 6789`;
            console.log('Updated provider to:', request.user);
        }
   
        if (actionButtonsContainer) {
            if (request.status === 'scheduled') {
                actionButtonsContainer.innerHTML = `
                    <button class="btn btn-primary" onclick="window.location.href='/main/html/admin/aJobs.html?job=${request.id}'">
                        <i class="fas fa-eye"></i> View Job
                    </button>
                `;
            } else if (request.status === 'ongoing') {
                actionButtonsContainer.innerHTML = `
                    <button class="btn btn-primary" onclick="window.location.href='/main/html/admin/aJobs.html?track=${request.id}'">
                        <i class="fas fa-map-marker-alt"></i> View Job
                    </button>
                    <button class="btn btn-outline" onclick="contactCollector('${request.id}')">
                        <i class="fas fa-phone"></i> Contact Collector
                    </button>
                `;
            } else if (request.status === 'completed') {
                actionButtonsContainer.innerHTML = `
                    <button class="btn btn-primary" onclick="window.location.href='/main/html/admin/aReport.html?request=${request.id}'">
                        <i class="fas fa-file-alt"></i> View Report
                    </button>
                `;
            } else {
                actionButtonsContainer.innerHTML = '';
            }
        }
        
        console.log('Detail view updated successfully');
    } else {
        console.error('Request not found for ID:', reqId);
    }
}

function contactCollector(reqId) {
    console.log('Contact collector for request:', reqId);
    //contact logic
    alert('Collector contact info would be displayed here');
}

    function attachCardEventListeners() {
        console.log('Attaching event listeners to', cards.length, 'cards');
        
        cards.forEach((card, index) => {
            const newCard = card.cloneNode(true);
            card.parentNode.replaceChild(newCard, card);
   
            newCard.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Card clicked - Index:', index);
                
                const reqId = this.getAttribute('data-req');
                console.log('Request ID from card:', reqId);
                
                if (filterDropdownContent) {
                    filterDropdownContent.classList.remove('show');
                }
                if (sortDropdownContent) {
                    sortDropdownContent.classList.remove('show');
                }
     
                updateDetailView(reqId);
       
                showDetail();
            });
        });

        cards = document.querySelectorAll('.req-card');
    }

    if (filterDropdownBtn) {
        filterDropdownBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            filterDropdownContent.classList.toggle('show');
            if (sortDropdownContent) {
                sortDropdownContent.classList.remove('show');
            }
            console.log('Filter dropdown toggled');
        });
    } else {
        console.error('filterDropdownBtn not found');
    }

    if (sortDropdownBtn) {
        sortDropdownBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            sortDropdownContent.classList.toggle('show');
            if (filterDropdownContent) {
                filterDropdownContent.classList.remove('show');
            }
            console.log('Sort dropdown toggled');
        });
    } else {
        console.error('sortDropdownBtn not found');
    }

    document.addEventListener('click', function(e) {
        if (filterDropdownContent && !filterDropdownBtn?.contains(e.target) && !filterDropdownContent.contains(e.target)) {
            filterDropdownContent.classList.remove('show');
        }
        if (sortDropdownContent && !sortDropdownBtn?.contains(e.target) && !sortDropdownContent.contains(e.target)) {
            sortDropdownContent.classList.remove('show');
        }
    });

    function sortRequests() {
        console.log('Sorting requests by:', currentSort);
        const cardsArray = Array.from(cards);
        const container = document.getElementById('requestListView');

        cardsArray.forEach(card => card.remove());

        cardsArray.sort((a, b) => {
            const aId = a.querySelector('.req-id')?.textContent || '';
            const bId = b.querySelector('.req-id')?.textContent || '';
            const aName = aId.split('·')[1]?.trim() || aId;
            const bName = bId.split('·')[1]?.trim() || bId;
            
            const aDate = a.querySelector('.req-meta span:nth-child(2)')?.textContent || '';
            const bDate = b.querySelector('.req-meta span:nth-child(2)')?.textContent || '';
            
            const aWeight = parseFloat(a.querySelector('.req-meta span:last-child')?.textContent) || 0;
            const bWeight = parseFloat(b.querySelector('.req-meta span:last-child')?.textContent) || 0;

            const parseDate = (dateStr) => {
                try {
                    const months = { 'Jan': 0, 'Feb': 1, 'Mar': 2, 'Apr': 3, 'May': 4, 'Jun': 5,
                                    'Jul': 6, 'Aug': 7, 'Sep': 8, 'Oct': 9, 'Nov': 10, 'Dec': 11 };
                    const [day, month, year] = dateStr.split(' ');
                    return new Date(parseInt(year), months[month], parseInt(day));
                } catch (e) {
                    return new Date(0);
                }
            };
            
            switch(currentSort) {
                case 'date-desc':
                    return parseDate(bDate) - parseDate(aDate);
                case 'date-asc':
                    return parseDate(aDate) - parseDate(bDate);
                case 'name-asc':
                    return aName.localeCompare(bName);
                case 'name-desc':
                    return bName.localeCompare(aName);
                case 'weight-desc':
                    return bWeight - aWeight;
                case 'weight-asc':
                    return aWeight - bWeight;
                default:
                    return 0;
            }
        });

        cardsArray.forEach(card => container.appendChild(card));

        cards = document.querySelectorAll('.req-card');
        attachCardEventListeners();
        
        console.log('Sort complete');
    }

    // Filter function
    function filterRequests() {
        let visibleCount = 0;
        
        cards.forEach(card => {
            const statusElement = card.querySelector('.status');
            const statusClasses = statusElement ? statusElement.classList : [];
            let status = '';
            for (let i = 0; i < statusClasses.length; i++) {
                if (statusClasses[i] !== 'status') {
                    status = statusClasses[i];
                    break;
                }
            }
            
            const requestText = card.textContent.toLowerCase();
            const searchTerm = currentSearch.toLowerCase();
            
            const matchesFilter = currentFilter === 'all' || status === currentFilter;
            const matchesSearch = currentSearch === '' || requestText.includes(searchTerm);
            
            if (matchesFilter && matchesSearch) {
                card.classList.remove('filtered-out');
                card.classList.add('filtered-in');
                visibleCount++;
            } else {
                card.classList.add('filtered-out');
                card.classList.remove('filtered-in');
            }
        });
        
        if (requestCount) {
            const totalCards = cards.length;
            const filterName = currentFilter === 'all' ? 'all' : currentFilter;
            
            if (currentSearch) {
                requestCount.textContent = `${visibleCount} of ${totalCards} requests (filtered by "${currentSearch}")`;
            } else if (currentFilter !== 'all') {
                requestCount.textContent = `${visibleCount} ${filterName} requests`;
            } else {
                requestCount.textContent = `${visibleCount} requests`;
            }
        }
        
        console.log(`Filter: ${currentFilter}, Search: "${currentSearch}", Visible: ${visibleCount}/${cards.length}`);
    }

    filterLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            filterLinks.forEach(l => l.classList.remove('active-filter'));
            this.classList.add('active-filter');
            
            currentFilter = this.getAttribute('data-filter');
            
            if (filterDropdownBtn) {
                const filterText = this.textContent.trim();
                filterDropdownBtn.innerHTML = `<i class="fas fa-filter"></i> ${filterText} <i class="fas fa-chevron-down"></i>`;
            }

            filterRequests();
            filterDropdownContent.classList.remove('show');
        });
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
            
            sortRequests();
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
    } else {
        console.error('backToListBtn not found');
    }

    attachCardEventListeners();

    showList();
  
    setTimeout(() => {
        filterRequests();
        sortRequests();
        
        filterLinks.forEach(link => {
            if (link.getAttribute('data-filter') === 'all') {
                link.classList.add('active-filter');
            }
        });
        
        sortLinks.forEach(link => {
            if (link.getAttribute('data-sort') === 'date-desc') {
                link.classList.add('active-sort');
            }
        });
        
        console.log('Initialization complete');
    }, 100);
});