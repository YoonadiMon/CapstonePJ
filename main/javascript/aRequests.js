document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM Content Loaded - Initializing Requests Page');

    // Data from PHP
    const requests = window.requestsData || [];
    const successMsg = window.successMsg || '';
    const errorMsg = window.errorMsg || '';

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
    let currentSearch = '';
    let currentSort = 'date-desc';
    let filteredRequests = [...requests];
    let currentRequest = null;

    // Toast
    function showToast(msg, type) {
        const t = document.getElementById('toast');
        if (!t) return;

        t.className = 'toast ' + type;
        t.textContent = msg;
        t.classList.add('show');

        setTimeout(() => {
            t.classList.remove('show');
        }, 3000);
    }

    if (successMsg) showToast(successMsg, 'success');
    if (errorMsg) showToast(errorMsg, 'error');

    function updateRequestCount() {
        if (requestCount) {
            requestCount.textContent = `${filteredRequests.length} pending ${filteredRequests.length === 1 ? 'request' : 'requests'}`;
        }
    }

    function parseDisplayDate(dateStr) {
        if (!dateStr) return new Date(0);

        const parts = dateStr.trim().split(' ');
        if (parts.length !== 3) return new Date(dateStr);

        const [day, monthStr, year] = parts;
        const months = {
            Jan: 0, Feb: 1, Mar: 2, Apr: 3, May: 4, Jun: 5,
            Jul: 6, Aug: 7, Sep: 8, Oct: 9, Nov: 10, Dec: 11
        };

        return new Date(parseInt(year, 10), months[monthStr] ?? 0, parseInt(day, 10));
    }

    function filterRequests() {
        filteredRequests = requests.filter(request => {
            const searchTerm = currentSearch.toLowerCase();

            return (
                currentSearch === '' ||
                (request.id && request.id.toLowerCase().includes(searchTerm)) ||
                (request.user && request.user.toLowerCase().includes(searchTerm)) ||
                (request.title && request.title.toLowerCase().includes(searchTerm)) ||
                (request.items && request.items.toLowerCase().includes(searchTerm))
            );
        });

        sortRequests();
        renderRequestCards();
        updateRequestCount();
    }

    function sortRequests() {
        filteredRequests.sort((a, b) => {
            switch (currentSort) {
                case 'date-desc':
                    return parseDisplayDate(b.date) - parseDisplayDate(a.date);
                case 'date-asc':
                    return parseDisplayDate(a.date) - parseDisplayDate(b.date);
                case 'name-asc':
                    return (a.user || '').localeCompare(b.user || '');
                case 'name-desc':
                    return (b.user || '').localeCompare(a.user || '');
                case 'weight-desc':
                    return parseFloat(b.weight || 0) - parseFloat(a.weight || 0);
                case 'weight-asc':
                    return parseFloat(a.weight || 0) - parseFloat(b.weight || 0);
                default:
                    return 0;
            }
        });
    }

    function renderRequestCards() {
        if (!requestsContainer) return;

    if (filteredRequests.length === 0) {
    requestsContainer.innerHTML = `
        <div class="no-results">No pending requests found</div>

        <div class="view-all-requests-wrap">
            <a href="../../html/admin/aCollectionRequests.php" class="view-all-requests-btn">
                <span>View All Requests</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    `;
    return;
}

        let html = '';

        filteredRequests.forEach(req => {
            html += `
                <div class="req-card" data-req="${req.id}">
                    <div class="req-info">
                        <div class="req-id-status">
                            <span class="req-id">#${req.id} · ${req.title}</span>
                            <span class="status ${req.status}">${req.statusText}</span>
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

        html += `
    <div class="view-all-requests-wrap">
        <a href="../../html/admin/aCollectionRequests.php" class="view-all-requests-btn">
            <span>View All Requests</span>
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
`;

        requestsContainer.innerHTML = html;
        attachCardEventListeners();
    }

    function attachCardEventListeners() {
        const cards = document.querySelectorAll('.req-card');

        cards.forEach(card => {
            card.addEventListener('click', function (e) {
                e.preventDefault();

                const reqId = this.getAttribute('data-req');
                const request = requests.find(r => r.id === reqId);

                if (request) {
                    updateDetailView(request);
                    showDetail();
                }
            });
        });
    }

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
        if (listView) listView.classList.remove('hidden');
        if (detailView) detailView.classList.add('hidden');
    }

    function showDetail() {
        if (listView) listView.classList.add('hidden');
        if (detailView) detailView.classList.remove('hidden');
    }

    if (sortDropdownBtn) {
        sortDropdownBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (sortDropdownContent) {
                sortDropdownContent.classList.toggle('show');
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (
            sortDropdownContent &&
            !sortDropdownBtn?.contains(e.target) &&
            !sortDropdownContent.contains(e.target)
        ) {
            sortDropdownContent.classList.remove('show');
        }
    });

    sortLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            sortLinks.forEach(l => l.classList.remove('active-sort'));
            this.classList.add('active-sort');

            currentSort = this.getAttribute('data-sort');

            if (sortDropdownBtn) {
                const sortText = this.textContent.trim();
                sortDropdownBtn.innerHTML = `<i class="fas fa-sort-amount-down"></i> ${sortText} <i class="fas fa-chevron-down"></i>`;
            }

            filterRequests();

            if (sortDropdownContent) {
                sortDropdownContent.classList.remove('show');
            }
        });
    });

    if (searchInput) {
        let searchTimeout;

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);

            searchTimeout = setTimeout(() => {
                currentSearch = this.value.trim();
                filterRequests();
            }, 300);
        });
    }

    if (backToListBtn) {
        backToListBtn.addEventListener('click', showList);
    }

if (approveBtn) {
    approveBtn.addEventListener('click', async function () {
        if (!currentRequest) return;

        try {
            const formData = new FormData();
            formData.append('action', 'approve');
            formData.append('requestID', currentRequest.dbID);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const text = await response.text();

            // If PHP redirects, fetch may return redirected HTML.
            // We only need to know approval succeeded, so treat a normal response as success.
            if (!response.ok) {
                throw new Error('Failed to approve request.');
            }

            // remove from current requests list immediately
            if (Array.isArray(window.requestsData)) {
                const index = window.requestsData.findIndex(r => String(r.id) === String(currentRequest.dbID));
                if (index !== -1) {
                    window.requestsData.splice(index, 1);
                }
            }

            // refresh requests UI
            filterRequests(true);

            const approvedRequestId = currentRequest.dbID;

            const modalOverlay = document.createElement('div');
            modalOverlay.className = 'approve-modal-overlay';

            modalOverlay.innerHTML = `
                <div class="approve-modal-content">
                    <div class="approve-modal-header">
                        <i class="fas fa-check-circle"></i>
                        <h3>Request Approved</h3>
                        <button type="button" class="approve-modal-close" id="closeApproveModal">&times;</button>
                    </div>

                    <div class="approve-modal-body">
                        <p><strong>#REQ${String(approvedRequestId).padStart(3, '0')}</strong> has been approved successfully.</p>
                        <p>Do you want to go to Operations to schedule it now?</p>
                    </div>

                    <div class="approve-modal-footer">
                        <button class="btn btn-primary" id="goToOperationsBtn">
                            <i class="fas fa-arrow-right"></i> Go to Scheduling
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modalOverlay);

            const closeApproveModal = () => {
                if (document.body.contains(modalOverlay)) {
                    document.body.removeChild(modalOverlay);
                }
            };

            document.getElementById('closeApproveModal')?.addEventListener('click', closeApproveModal);

            document.getElementById('goToOperationsBtn')?.addEventListener('click', function () {
                window.location.href = `../../html/admin/aOperations.php?requestID=${approvedRequestId}`;
            });

            modalOverlay.addEventListener('click', function (e) {
                if (e.target === modalOverlay) {
                    closeApproveModal();
                }
            });

        } catch (error) {
            alert(error.message || 'Failed to approve request.');
        }
    });
}

    if (rejectBtn) {
        rejectBtn.addEventListener('click', function () {
            if (!currentRequest) return;

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

            const cancelBtn = document.getElementById('cancelReject');
            const confirmBtn = document.getElementById('confirmReject');

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    document.body.removeChild(modalOverlay);
                });
            }

            if (confirmBtn) {
                confirmBtn.addEventListener('click', function () {
                    const reason = document.getElementById('rejectionReason').value.trim();

                    if (!reason) {
                        alert('Please enter a rejection reason');
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';

                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'reject';

                    const requestIdInput = document.createElement('input');
                    requestIdInput.type = 'hidden';
                    requestIdInput.name = 'requestID';
                    requestIdInput.value = currentRequest.dbID;

                    const reasonInput = document.createElement('input');
                    reasonInput.type = 'hidden';
                    reasonInput.name = 'reason';
                    reasonInput.value = reason;

                    form.appendChild(actionInput);
                    form.appendChild(requestIdInput);
                    form.appendChild(reasonInput);

                    document.body.appendChild(form);
                    form.submit();
                });
            }

            modalOverlay.addEventListener('click', function (e) {
                if (e.target === modalOverlay) {
                    document.body.removeChild(modalOverlay);
                }
            });
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