/* ─── STATE ─── */
    // sessions: 0 = not started, 1 = active, 2 = completed
    const state = {
        journeyStarted: false,
        sessions: [0, 0, 0, 0], // session1, session2, session3, session4
        reported: [false, false, false, false],
        pendingSession: null,
    };

    /* ─── THEME ─── */
    function toggleTheme() {
        document.body.classList.toggle('dark-mode');
    }

    /* ─── POPUP HELPERS ─── */
    function openPopup(id) { document.getElementById(id).classList.add('visible'); }
    function closePopup(id) { document.getElementById(id).classList.remove('visible'); }

    /* ─── TOAST ─── */
    function showToast(msg, duration = 2500) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), duration);
    }

    /* ─── START JOURNEY ─── */
    function openStartPopup() { openPopup('startPopup'); }

    function confirmStartJourney() {
        closePopup('startPopup');
        state.journeyStarted = true;

        // Hide start button
        document.getElementById('startJourneyBtn').style.display = 'none';

        // Update overview badge
        setBadge('badge-overview', 'ongoing', 'Ongoing');
        setBadge('globalStatus', 'ongoing', 'Ongoing');

        // Unlock session 1
        unlockSession(0);
        updateRouteStep(0, 'active');
        updateRouteStep(1, 'active');

        showToast('🚗 Start journey, drive safe!');
    }

    /* ─── COMPLETE SESSION ─── */
    function openCompletePopup(sessionKey) {
        const idx = sessionIndex(sessionKey);
        state.pendingSession = sessionKey;
        const msgs = [
            'Confirm you have collected all items from the provider location.',
            'Confirm you have delivered Laptop & Phone to Recycling Center 1.',
            'Confirm you have delivered Printer to Recycling Center 2.',
            'Confirm you have returned safely to APU Facility.',
        ];
        document.getElementById('completePopupMsg').textContent = msgs[idx];
        openPopup('completePopup');
    }

    function confirmComplete() {
        closePopup('completePopup');
        const key = state.pendingSession;
        const idx = sessionIndex(key);
        state.sessions[idx] = 2;

        // Mark card completed
        const card = document.getElementById('card-' + key);
        card.classList.remove('active-card');
        card.classList.add('completed-card');

        // Update badge
        if (key === 'session1') setBadge('badge-session1', 'completed', 'Completed');
        else if (key === 'session2') setBadge('badge-session2', 'completed', 'Completed');
        else if (key === 'session3') setBadge('badge-session3', 'completed', 'Completed');
        else if (key === 'session4') setBadge('badge-session4', 'completed', 'Completed');

        // Disable complete button
        document.getElementById('completeBtn-' + key).disabled = true;
        document.getElementById('completeBtn-' + key).textContent = '✓ Done';

        // Route step
        if (idx === 0) {
            updateRouteStep(1, 'done');
            updateRouteStep(2, 'active');
            unlockSession(1);
        } else if (idx === 1) {
            updateRouteStep(2, 'done');
            updateRouteStep(3, 'active');
            unlockSession(2);
        } else if (idx === 2) {
            updateRouteStep(3, 'done');
            updateRouteStep(4, 'active');
            unlockSession(3);
        } else if (idx === 3) {
            updateRouteStep(4, 'done');
            // All done
            setBadge('globalStatus', 'completed', 'Completed');
            setBadge('badge-overview', 'completed', 'Completed');
            showToast('🎉 Job completed! Well done!', 4000);
            return;
        }

        showToast('✅ Session completed!');
    }

    /* ─── REPORT ISSUE ─── */
    let pendingReportSession = null;

    function openReport(sessionKey) {
        pendingReportSession = sessionKey;
        openPopup('reportPopup');
    }

    function toggleBreakdownAddress() {
        const val = document.getElementById('issueReason').value;
        document.getElementById('breakdownAddressGroup').style.display =
            val === 'vehicle_breakdown' ? 'block' : 'none';
    }

    function submitReport() {
        const reason = document.getElementById('issueReason').value;
        if (!reason) { showToast('⚠️ Please select a reason.'); return; }

        closePopup('reportPopup');

        // Lock the session card
        const idx = sessionIndex(pendingReportSession);
        state.reported[idx] = true;
        const card = document.getElementById('card-' + pendingReportSession);
        card.classList.add('locked');
        card.classList.remove('active-card');

        // Update badge to interrupted
        setBadge('badge-' + pendingReportSession, 'interrupted', 'Interrupted');
        setBadge('globalStatus', 'interrupted', 'Interrupted');

        showToast('⚠️ Issue reported. Admin has been notified.', 4000);

        // Reset form
        document.getElementById('issueReason').value = '';
        document.getElementById('issueNote').value = '';
        document.getElementById('breakdownAddressGroup').style.display = 'none';
    }

    /* ─── HELPERS ─── */
    function sessionIndex(key) {
        return { session1: 0, session2: 1, session3: 2, session4: 3 }[key];
    }

    function unlockSession(idx) {
        const keys = ['session1', 'session2', 'session3', 'session4'];
        const key = keys[idx];
        const card = document.getElementById('card-' + key);
        card.classList.remove('locked');
        card.classList.add('active-card');
        document.getElementById('completeBtn-' + key).disabled = false;

        // Enable report button if exists
        const rb = document.getElementById('reportBtn-' + key);
        if (rb) rb.disabled = false;
    }

    function setBadge(elemId, type, label) {
        const el = document.getElementById(elemId);
        el.className = 'badge badge-' + type;
        el.textContent = label;
    }

    function updateRouteStep(dotIdx, state) {
        const dot = document.getElementById('dot-' + dotIdx);
        const label = document.getElementById('label-' + dotIdx);
        const line = document.getElementById('line-' + dotIdx);

        dot.className = 'step-dot ' + (state === 'done' ? 'done' : state === 'active' ? 'active' : '');
        label.className = 'step-label ' + (state === 'done' ? 'done' : state === 'active' ? 'active' : '');
        if (line) line.className = 'step-line ' + (state === 'done' ? 'done' : state === 'active' ? 'active' : '');
    }