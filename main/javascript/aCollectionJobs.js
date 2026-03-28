function formatJobId(id) {
    if (typeof id === 'number') {
        return 'JOB' + String(id).padStart(3, '0');
    }
    if (typeof id === 'string') {
        if (id.startsWith('JOB')) return id;
        var num = parseInt(id, 10);
        if (!isNaN(num)) {
            return 'JOB' + String(num).padStart(3, '0');
        }
    }
    return id;
}

function extractNumericJobId(jobId) {
    if (typeof jobId === 'number') return jobId;
    if (typeof jobId === 'string') {
        if (jobId.startsWith('JOB')) {
            return parseInt(jobId.substring(3), 10);
        }
        var num = parseInt(jobId, 10);
        if (!isNaN(num)) return num;
    }
    return 0;
}

var map = null;
var markersLayer = null;
var routeLayer = null;
var selectedCollectorId = null;
var tilesLayer = null;

// GPS Simulation Variables
var simulationInterval = null;
var routeLine = null;
var movingMarker = null;
var currentPositionIndex = 0;
var routeCoordinates = [];
var totalRouteTime = 0;
var totalRouteDistance = 0;

// Random simulation states for different collectors
var collectorSimulationStates = {};

var collectionJobsData = window.collectionJobsData || {
    handoverJobs: [],
    delayedJobs: [],
    pendingDropoffJobs: [],
    activeCollectors: [],
    quickStats: {
        completedToday: 0,
        avgResponse: '0min',
        totalDistance: 0
    },
    handoverLookup: {},
    delayedLookup: {},
    pendingDropoffLookup: {},
    centresAvailable: 0
};

var isSubmitting = false;

var mapLayersVisible = true;

// OSRM Routing Service URL
var OSRM_URL = 'https://router.project-osrm.org/route/v1/driving/';
var routeCache = {};

// Fetch real road route from OSRM
async function fetchRealRoute(startLat, startLng, endLat, endLng) {
    var cacheKey = startLat + ',' + startLng + '|' + endLat + ',' + endLng;
    
    if (routeCache[cacheKey]) {
        return routeCache[cacheKey];
    }
    
    var url = OSRM_URL + startLng + ',' + startLat + ';' + endLng + ',' + endLat + '?overview=full&geometries=geojson';
    
    try {
        var controller = new AbortController();
        var timeoutId = setTimeout(function() { controller.abort(); }, 8000);
        
        var response = await fetch(url, { signal: controller.signal });
        clearTimeout(timeoutId);
        
        var data = await response.json();
        
        if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
            var route = data.routes[0];
            var coordinates = route.geometry.coordinates.map(function(coord) { 
                return [coord[1], coord[0]]; 
            });
            var duration = route.duration;
            var distance = route.distance;
            
            var result = {
                coordinates: coordinates,
                duration: duration,
                distance: distance,
                summary: route.legs[0].summary || 'Route'
            };
            
            routeCache[cacheKey] = result;
            return result;
        }
    } catch (error) {
        console.error('OSRM error:', error);
    }
    
    return null;
}

// Generate smooth curve route as fallback (simulates road curves)
function generateCurvedRoute(startLat, startLng, endLat, endLng, pointsCount) {
    pointsCount = pointsCount || 50;
    var coordinates = [];
    
    // Add control points to create a realistic curved path
    var midLat = (startLat + endLat) / 2;
    var midLng = (startLng + endLng) / 2;
    
    // Create a perpendicular offset to simulate road curves
    var dx = endLng - startLng;
    var dy = endLat - startLat;
    var offset = Math.sqrt(dx * dx + dy * dy) * 0.15;
    
    // Random curve direction
    var curveDirection = Math.random() > 0.5 ? 1 : -1;
    
    for (var i = 0; i <= pointsCount; i++) {
        var t = i / pointsCount;
        
        // Bezier curve for smooth road-like path
        var bezierT = t;
        var lat = Math.pow(1 - bezierT, 2) * startLat + 
                  2 * (1 - bezierT) * bezierT * (midLat + curveDirection * offset * Math.sin(bezierT * Math.PI)) + 
                  Math.pow(bezierT, 2) * endLat;
        var lng = Math.pow(1 - bezierT, 2) * startLng + 
                  2 * (1 - bezierT) * bezierT * (midLng + curveDirection * offset * Math.cos(bezierT * Math.PI)) + 
                  Math.pow(bezierT, 2) * endLng;
        
        coordinates.push([lat, lng]);
    }
    
    // Calculate distance
    var R = 6371e3;
    var totalDist = 0;
    for (var j = 1; j < coordinates.length; j++) {
        var lat1 = coordinates[j-1][0] * Math.PI / 180;
        var lat2 = coordinates[j][0] * Math.PI / 180;
        var lng1 = coordinates[j-1][1] * Math.PI / 180;
        var lng2 = coordinates[j][1] * Math.PI / 180;
        var dLat = lat2 - lat1;
        var dLng = lng2 - lng1;
        var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1) * Math.cos(lat2) *
                Math.sin(dLng/2) * Math.sin(dLng/2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        totalDist += R * c;
    }
    
    var estimatedSpeed = 13.89;
    var duration = totalDist / estimatedSpeed;
    
    return {
        coordinates: coordinates,
        duration: duration,
        distance: totalDist,
        summary: 'Road Route'
    };
}

// Get random simulation state for a collector
function getRandomSimulationState(collectorId) {
    if (!collectorSimulationStates[collectorId]) {
        var randomValue = Math.random();
        var isArrived = randomValue < 0.3;
        collectorSimulationStates[collectorId] = {
            isArrived: isArrived,
            progress: isArrived ? 100 : Math.random() * 80,
            startTime: Date.now()
        };
    }
    return collectorSimulationStates[collectorId];
}

// Start GPS simulation with real road routing
async function startGPSSimulation(collector) {
    if (simulationInterval) {
        clearInterval(simulationInterval);
        simulationInterval = null;
    }
    
    if (!collector.pickupLat || !collector.pickupLng || !collector.centreLat || !collector.centreLng) {
        console.warn('Missing coordinates for simulation');
        return;
    }
    
    var simulationState = getRandomSimulationState(collector.id);
    var isArrived = simulationState.isArrived;
    var initialProgress = isArrived ? 100 : simulationState.progress;
    
    var routeEta = document.getElementById('routeEta');
    if (routeEta) {
        routeEta.textContent = isArrived ? '' : 'Loading route...';
    }
    
    showLoadingSpinner();
    
    // Try to fetch real road route from OSRM
    var routeData = await fetchRealRoute(
        collector.pickupLat, collector.pickupLng,
        collector.centreLat, collector.centreLng
    );
    
    // Fallback to curved route if OSRM fails
    if (!routeData) {
        console.log('Using curved route fallback');
        routeData = generateCurvedRoute(
            collector.pickupLat, collector.pickupLng,
            collector.centreLat, collector.centreLng
        );
    }
    
    hideLoadingSpinner();
    
    routeCoordinates = routeData.coordinates;
    totalRouteTime = routeData.duration;
    totalRouteDistance = routeData.distance;
    var totalDistanceKm = (totalRouteDistance / 1000).toFixed(1);
    
    var startIndex = Math.floor((initialProgress / 100) * (routeCoordinates.length - 1));
    currentPositionIndex = Math.max(0, Math.min(startIndex, routeCoordinates.length - 1));
    
    var remainingTime = totalRouteTime * (1 - (initialProgress / 100));
    var remainingMinutes = Math.round(remainingTime / 60);
    var remainingEta = remainingMinutes < 60 ? remainingMinutes + ' min' : Math.floor(remainingMinutes / 60) + 'h ' + (remainingMinutes % 60) + 'm';
    
    if (routeEta) {
        if (isArrived) {
            routeEta.textContent = '';
        } else {
            routeEta.textContent = 'ETA: ' + remainingEta + ' | ' + totalDistanceKm + ' km | ' + Math.round(initialProgress) + '%';
        }
    }
    
    if (routeLayer) {
        routeLayer.clearLayers();
    }
    
    var latLngs = [];
    for (var i = 0; i < routeCoordinates.length; i++) {
        latLngs.push([routeCoordinates[i][0], routeCoordinates[i][1]]);
    }
    
    routeLine = L.polyline(latLngs, {
        color: isArrived ? '#4caf50' : '#2196f3',
        weight: 5,
        opacity: 0.9,
        lineJoin: 'round',
        lineCap: 'round'
    }).addTo(routeLayer);
    
    var startMarker = L.marker([collector.pickupLat, collector.pickupLng], {
        icon: L.divIcon({
            className: 'route-marker',
            html: '<i class="fas fa-circle" style="color: #4caf50; font-size: 12px;"></i>',
            iconSize: [12, 12]
        })
    }).addTo(routeLayer);
    startMarker.bindPopup(escapeHtml(collector.pickupLabel || ''));
    
    var endMarker = L.marker([collector.centreLat, collector.centreLng], {
        icon: L.divIcon({
            className: 'route-marker',
            html: '<i class="fas fa-circle" style="color: #f44336; font-size: 12px;"></i>',
            iconSize: [12, 12]
        })
    }).addTo(routeLayer);
    endMarker.bindPopup(escapeHtml(collector.centreLabel || 'Centre'));
    
    var currentPosition = routeCoordinates[currentPositionIndex];
    var markerHtml = '';
    
    if (isArrived) {
        markerHtml = '<i class="fas fa-check-circle" style="color: #4caf50; font-size: 28px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"></i>';
    } else {
        markerHtml = '<i class="fas fa-truck-moving" style="color: #ff9800; font-size: 28px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)); animation: bounce 0.5s ease infinite;"></i>';
    }
    
    movingMarker = L.marker([currentPosition[0], currentPosition[1]], {
        icon: L.divIcon({
            className: 'moving-vehicle-marker',
            html: markerHtml,
            iconSize: [28, 28]
        }),
        zIndexOffset: 1000
    }).addTo(routeLayer);
    
    var vehicleDisplay = collector.vehicle || 'Vehicle';
    movingMarker.bindPopup(vehicleDisplay).openPopup();
    
    if (isArrived) {
        var routeCurrentLocation = document.getElementById('routeCurrentLocation');
        if (routeCurrentLocation) routeCurrentLocation.textContent = '';
        
        var progressBar = document.getElementById('routeProgressBar');
        if (progressBar) progressBar.style.width = '100%';
        
        showEtaBubble(collector.centreLat, collector.centreLng, '✓ ARRIVED', false);
        
        return;
    }
    
    map.setView([currentPosition[0], currentPosition[1]], 13);
    
    var elapsedTime = (initialProgress / 100) * totalRouteTime * 1000;
    var lastTimestamp = Date.now();
    var routeEtaElement = routeEta;
    var vehicleText = collector.vehicle || 'Vehicle';
    var centreLatVal = collector.centreLat;
    var centreLngVal = collector.centreLng;
    
    simulationInterval = setInterval(function() {
        var now = Date.now();
        var deltaTime = Math.min(100, now - lastTimestamp);
        lastTimestamp = now;
        elapsedTime = elapsedTime + deltaTime;
        
        var progress = Math.min(1, elapsedTime / (totalRouteTime * 1000));
        var targetIndex = Math.floor(progress * (routeCoordinates.length - 1));
        
        if (targetIndex > currentPositionIndex && targetIndex < routeCoordinates.length) {
            currentPositionIndex = targetIndex;
            var position = routeCoordinates[currentPositionIndex];
            
            movingMarker.setLatLng([position[0], position[1]]);
            
            var remainingTimeVal = totalRouteTime - (elapsedTime / 1000);
            var remainingMinutesVal = Math.max(0, Math.round(remainingTimeVal / 60));
            var remainingEtaVal = remainingMinutesVal < 60 ? remainingMinutesVal + ' min' : Math.floor(remainingMinutesVal / 60) + 'h ' + (remainingMinutesVal % 60) + 'm';
            var progressPercent = Math.round(progress * 100);
            var remainingDistanceVal = ((totalRouteTime - (elapsedTime / 1000)) / totalRouteTime) * totalRouteDistance;
            var remainingDistanceKmVal = (remainingDistanceVal / 1000).toFixed(1);
            
            if (routeEtaElement) {
                routeEtaElement.textContent = 'ETA: ' + remainingEtaVal + ' | ' + remainingDistanceKmVal + ' km | ' + progressPercent + '%';
            }
            
            var routeCurrentLocationElem = document.getElementById('routeCurrentLocation');
            if (routeCurrentLocationElem) {
                routeCurrentLocationElem.textContent = progressPercent + '% | ' + remainingDistanceKmVal + ' km';
            }
            
            movingMarker.bindPopup(vehicleText).openPopup();
            
            showEtaBubble(position[0], position[1], 'ETA: ' + remainingEtaVal, true);
            
            var progressBarElem = document.getElementById('routeProgressBar');
            if (progressBarElem) {
                progressBarElem.style.width = progressPercent + '%';
            }
        }
        
        if (progress >= 1) {
            stopSimulation();
            
            if (routeEtaElement) routeEtaElement.textContent = '';
            
            var finalRouteLocation = document.getElementById('routeCurrentLocation');
            if (finalRouteLocation) finalRouteLocation.textContent = '';
            
            movingMarker.bindPopup(vehicleText).openPopup();
            
            movingMarker.setIcon(L.divIcon({
                className: 'arrived-marker',
                html: '<i class="fas fa-check-circle" style="color: #4caf50; font-size: 28px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"></i>',
                iconSize: [28, 28]
            }));
            
            showEtaBubble(centreLatVal, centreLngVal, '✓ ARRIVED', false);
            
            var finalProgressBar = document.getElementById('routeProgressBar');
            if (finalProgressBar) finalProgressBar.style.width = '100%';
        }
    }, 100);
}

function showLoadingSpinner() {
    var mapContainer = document.getElementById('mapContainer');
    if (mapContainer && !document.getElementById('mapLoadingSpinner')) {
        var spinner = document.createElement('div');
        spinner.id = 'mapLoadingSpinner';
        spinner.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.7); color: white; padding: 12px 20px; border-radius: 8px; z-index: 2000; display: flex; align-items: center; gap: 10px; font-size: 14px;';
        spinner.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading route...';
        mapContainer.appendChild(spinner);
    }
}

function hideLoadingSpinner() {
    var spinner = document.getElementById('mapLoadingSpinner');
    if (spinner) spinner.remove();
}

function stopSimulation() {
    if (simulationInterval) {
        clearInterval(simulationInterval);
        simulationInterval = null;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initMap();
    loadAllData();

    var reportIssueModal = document.getElementById('reportIssueModal');
    var reportIssueForm = document.getElementById('reportIssueForm');
    var closeReportIssueModalBtn = document.getElementById('closeReportIssueModal');
    var cancelReportIssueBtn = document.getElementById('cancelReportIssueBtn');

    if (closeReportIssueModalBtn) {
        closeReportIssueModalBtn.addEventListener('click', closeReportIssueModal);
    }

    if (cancelReportIssueBtn) {
        cancelReportIssueBtn.addEventListener('click', closeReportIssueModal);
    }

    if (reportIssueModal) {
        reportIssueModal.addEventListener('click', function(e) {
            if (e.target === reportIssueModal) {
                closeReportIssueModal();
            }
        });
    }

    var priorityOptions = document.querySelectorAll('.priority-option input[type="radio"]');
    for (var i = 0; i < priorityOptions.length; i++) {
        priorityOptions[i].addEventListener('change', function() {
            var allOptions = document.querySelectorAll('.priority-option');
            for (var j = 0; j < allOptions.length; j++) {
                allOptions[j].classList.remove('selected');
            }
            var parent = this.closest('.priority-option');
            if (parent) {
                parent.classList.add('selected');
            }
        });
    }

    var assignHandoverForm = document.getElementById('assignHandoverForm');
    if (assignHandoverForm) {
        assignHandoverForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var handoverJobId = document.getElementById('handoverJobId');
            // alert('Handover assigned for ' + (handoverJobId ? handoverJobId.value : ''));
            closeAssignHandoverModal();
        });
    }

    // var reassignJobForm = document.getElementById('reassignJobForm');
    // if (reassignJobForm) {
    //     reassignJobForm.addEventListener('submit', function(e) {
    //         e.preventDefault();
    //         var reassignJobId = document.getElementById('reassignJobId');
    //         // alert('Job reassigned for ' + (reassignJobId ? reassignJobId.value : ''));
    //         closeReassignJobModal();
    //     });
    // }

    // var reassignCentreForm = document.getElementById('reassignCentreForm');
    // if (reassignCentreForm) {
    //     reassignCentreForm.addEventListener('submit', function(e) {
    //         e.preventDefault();
    //         var reassignCentreJobId = document.getElementById('reassignCentreJobId');
    //         // alert('Collection centre reassigned for ' + (reassignCentreJobId ? reassignCentreJobId.value : ''));
    //         closeReassignCentreModal();
    //     });
    // }
    
    var routeInfoBox = document.getElementById('routeInfoBox');
    if (routeInfoBox && !document.getElementById('routeProgressBar')) {
        var progressContainer = document.createElement('div');
        progressContainer.style.cssText = 'margin-top: 8px; height: 4px; background: #e0e0e0; border-radius: 2px; overflow: hidden;';
        progressContainer.innerHTML = '<div id="routeProgressBar" style="width: 0%; height: 100%; background: #4caf50; transition: width 0.3s ease;"></div>';
        routeInfoBox.appendChild(progressContainer);
    }

    var successPopup = document.getElementById('successPopupModal');
    var closeSuccessPopup = document.getElementById('closeSuccessPopup');
    var closeSuccessPopupBtn = document.getElementById('closeSuccessPopupBtn');
    var goToIssuesBtn = document.getElementById('goToIssuesBtn');

    function closeSuccessPopupModal() {
        if (successPopup) {
            successPopup.classList.remove('active');
            successPopup.removeAttribute('data-job-id');
        }
    }

    if (closeSuccessPopup) {
        closeSuccessPopup.addEventListener('click', closeSuccessPopupModal);
    }

    if (closeSuccessPopupBtn) {
        closeSuccessPopupBtn.addEventListener('click', closeSuccessPopupModal);
    }

    if (goToIssuesBtn) {
        goToIssuesBtn.addEventListener('click', function() {
            var jobId = successPopup ? successPopup.getAttribute('data-job-id') : '';
            if (jobId) {
                window.location.href = 'aIssue.php?jobID=' + encodeURIComponent(jobId);
            } else {
                window.location.href = 'aIssue.php';
            }
        });
    }

    if (successPopup) {
        successPopup.addEventListener('click', function(e) {
            if (e.target === successPopup) {
                closeSuccessPopupModal();
            }
        });
    }

    if (reportIssueForm) {
        reportIssueForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            var issueJobId = document.getElementById('issueJobId');
            var issueSubject = document.getElementById('issueSubject');
            var issueDescription = document.getElementById('issueDescription');
            var issueTypeSelect = document.getElementById('issueType');
            var severityRadio = document.querySelector('input[name="severity"]:checked');
            var otherIssueTextElem = document.getElementById('otherIssueText');
            
            var selectedIssue = issueTypeSelect ? issueTypeSelect.value : '';
            
            if (!severityRadio) {
                // alert('Please select a severity level');
                return;
            }
            
            var formData = new FormData();
            formData.append('submit_issue', '1');
            
            var numericJobId = issueJobId ? (issueJobId.getAttribute('data-numeric-id') || extractNumericJobId(issueJobId.value)) : 0;
            formData.append('jobId', numericJobId);
            
            formData.append('subject', issueSubject ? issueSubject.value.trim() : '');
            formData.append('issueType', selectedIssue);
            formData.append('severity', severityRadio.value);
            formData.append('description', issueDescription ? issueDescription.value.trim() : '');
            
            if (!selectedIssue) {
                // alert('Please select an issue type');
                return;
            }
            
            var submitBtn = reportIssueForm.querySelector('button[type="submit"]');
            var originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            
            try {
                var response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                var result = await response.json();
                
                if (result.success) {
                    closeReportIssueModal();
                    
                    try {
                        // Force refresh the page data
                        const refreshResponse = await fetch(window.location.href + '?fetch_data=1&t=' + Date.now());
                        const freshData = await refreshResponse.json();
                        
                        // Update the global data object
                        window.collectionJobsData = freshData;
                        
                        // Reload all UI components with fresh data
                        loadAllData();
                        
                        // Show success popup
                        if (successPopup) {
                            successPopup.classList.add('active');
                            successPopup.setAttribute('data-job-id', numericJobId);
                        }
                        
                    } catch (refreshError) {
                        console.error('Failed to refresh data:', refreshError);
                        // Fallback: reload the page
                        window.location.reload();
                    }
                    
                    reportIssueForm.reset();
                    
                    var priorityOptionsList = document.querySelectorAll('.priority-option');
                    for (var i = 0; i < priorityOptionsList.length; i++) {
                        priorityOptionsList[i].classList.remove('selected');
                    }
                    
                    var otherIssueGroupElem = document.getElementById('otherIssueGroup');
                    if (otherIssueGroupElem) otherIssueGroupElem.style.display = 'none';
                    
                } else {
                    // alert('Error: ' + result.message);
                }
                
            } catch (error) {
                console.error('Error submitting issue:', error);
                // alert('Failed to submit issue. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }
});

function loadAllData() {
    loadHandoverJobs();
    loadDelayedJobs();
    loadPendingDropoffJobs();
    loadActiveCollectors();
    loadQuickStats();
    updateCounts();
    updateMapMarkers();
}

function loadHandoverJobs() {
    displayHandoverJobs(collectionJobsData.handoverJobs || []);
}

function displayHandoverJobs(jobs) {
    var handoverList = document.getElementById('handoverList');
    var panelHandoverCount = document.getElementById('panelHandoverCount');

    if (!handoverList) return;

    if (!jobs.length) {
        handoverList.innerHTML = '<div class="no-jobs-message"><i class="fas fa-check-circle"></i><p>No handover required</p></div>';
        if (panelHandoverCount) panelHandoverCount.textContent = '0';
        return;
    }

    if (panelHandoverCount) panelHandoverCount.textContent = jobs.length;

    var html = '';
    for (var i = 0; i < jobs.length; i++) {
        var job = jobs[i];
        html += '<div class="panel-item"><div class="item-info"><i class="fas fa-route"></i><div><strong>' + escapeHtml(job.id || '') + '</strong><small>' + escapeHtml(job.location || '-') + '</small></div><span class="item-reason">' + escapeHtml(job.reason || '-') + '</span></div><div class="item-actions"><button class="btn-icon" onclick="openReportIssueModal(\'' + escapeJs(job.jobID || '') + '\')" title="Report Issue"><i class="fas fa-exclamation-circle"></i></button><button class="btn-icon" onclick="viewJobDetails(\'' + escapeJs(job.id || '') + '\')" title="View details"><i class="fas fa-eye"></i></button></div></div>';
    }
    handoverList.innerHTML = html;
}

function loadDelayedJobs() {
    displayDelayedJobs(collectionJobsData.delayedJobs || []);
}

function displayDelayedJobs(jobs) {
    var delayedList = document.getElementById('delayedList');
    var panelDelayedCount = document.getElementById('panelDelayedCount');

    if (!delayedList) return;

    if (!jobs.length) {
        delayedList.innerHTML = '<div class="no-jobs-message"><i class="fas fa-check-circle"></i><p>No delayed jobs</p></div>';
        if (panelDelayedCount) panelDelayedCount.textContent = '0';
        return;
    }

    if (panelDelayedCount) panelDelayedCount.textContent = jobs.length;

    var html = '';
    for (var i = 0; i < jobs.length; i++) {
        var job = jobs[i];
        
        var statusText = '';
        if (job.reason) {
            statusText = job.reason;
            if (job.delay) {
                statusText += ' (' + job.delay + ')';
            }
        } else if (job.delay) {
            statusText = job.delay;
        }
        
        html += '<div class="panel-item">' +
                    '<div class="item-info" style="flex: 1;">' +
                        '<i class="fas fa-clock"></i>' +
                        '<div style="flex: 1;">' +
                            '<div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.25rem;">' +
                                '<strong style="font-size: 0.85rem; line-height: 1.3;">' + escapeHtml(job.id || '') + '</strong>' +
                                '<span class="item-reason" style="margin: 0; line-height: 1.3;">' + escapeHtml(statusText) + '</span>' +
                            '</div>' +
                            '<small style="color: var(--Gray); font-size: 0.7rem; display: block; margin-top: 0.25rem;">' + escapeHtml(job.location || '-') + '</small>' +
                        '</div>' +
                    '</div>' +
                    '<div class="item-actions">' +
                        '<button class="btn-icon" onclick="openReportIssueModal(\'' + escapeJs(job.jobID || '') + '\')" title="Report issue">' +
                        '<i class="fas fa-exclamation-circle"></i>' +
                        '</button>' +
                        '<button class="btn-icon" onclick="viewJobDetails(\'' + escapeJs(job.id || '') + '\')" title="View details">' +
                            '<i class="fas fa-eye"></i>' +
                        '</button>' +
                    '</div>' +
                '</div>';
    }
    delayedList.innerHTML = html;
}

function loadPendingDropoffJobs() {
    displayPendingDropoffJobs(collectionJobsData.pendingDropoffJobs || []);
}

function displayPendingDropoffJobs(jobs) {
    var pendingDropoffList = document.getElementById('pendingDropoffList');
    var pendingDropoffCount = document.getElementById('pendingDropoffCount');
    var itemsInTransit = document.getElementById('itemsInTransit');
    var affectedCollectors = document.getElementById('affectedCollectors');
    var centresAvailable = document.getElementById('centresAvailable');

    if (!pendingDropoffList) return;

    if (!jobs.length) {
        pendingDropoffList.innerHTML = '<div class="no-jobs-message"><i class="fas fa-check-circle"></i><p>No pending drop-offs</p></div>';
        if (pendingDropoffCount) pendingDropoffCount.textContent = '0';
        if (itemsInTransit) itemsInTransit.textContent = '0';
        if (affectedCollectors) affectedCollectors.textContent = '0';
        if (centresAvailable) centresAvailable.textContent = collectionJobsData.centresAvailable || 0;
        return;
    }

    if (pendingDropoffCount) pendingDropoffCount.textContent = jobs.length;

    var totalItems = 0;
    for (var i = 0; i < jobs.length; i++) {
        var job = jobs[i];
        var match = String(job.items || '').match(/\d+/);
        totalItems += match ? parseInt(match[0], 10) : 0;
    }

    if (itemsInTransit) itemsInTransit.textContent = totalItems;
    if (affectedCollectors) affectedCollectors.textContent = jobs.length;
    if (centresAvailable) centresAvailable.textContent = collectionJobsData.centresAvailable || 0;

    var html = '';
    for (var j = 0; j < jobs.length; j++) {
        var dropoffJob = jobs[j];
        html += '<div class="dropoff-item">' +
                    '<div class="dropoff-header">' +
                        '<span class="dropoff-id">' + escapeHtml(dropoffJob.id || '') + '</span>' +
                        '<span class="dropoff-status">Failed Drop-off</span>' +
                    '</div>' +
                    '<div class="dropoff-details">' +
                        '<div class="dropoff-detail">' +
                            '<span class="dropoff-detail-label">Collector</span>' +
                            '<span class="dropoff-detail-value">' + escapeHtml(dropoffJob.collector || '-') + '</span>' +
                        '</div>' +
                        '<div class="dropoff-detail">' +
                            '<span class="dropoff-detail-label">Items</span>' +
                            '<span class="dropoff-detail-value">' + escapeHtml(dropoffJob.items || '-') + '</span>' +
                        '</div>' +
                        '<div class="dropoff-detail">' +
                            '<span class="dropoff-detail-label">Original Centre</span>' +
                            '<span class="dropoff-detail-value">' + escapeHtml(dropoffJob.originalCentre || '-') + '</span>' +
                        '</div>' +
                        '<div class="dropoff-detail">' +
                            '<span class="dropoff-detail-label">Time</span>' +
                            '<span class="dropoff-detail-value">' + escapeHtml(dropoffJob.time || '-') + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="dropoff-fail-reason">' +
                        '<i class="fas fa-exclamation-circle"></i>' +
                        '<span>' + escapeHtml(dropoffJob.failReason || '-') + '</span>' +
                    '</div>' +
                    '<div class="dropoff-actions">' +
                        '<button class="btn-reassign-centre" onclick="openReportIssueModal(\'' + escapeJs(dropoffJob.jobID || '') + '\')">' +
                            '<i class="fas fa-exclamation-circle"></i> Report Issue' +
                        '</button>' +
                        '<button class="btn-icon" onclick="viewFailedDropoffDetails(\'' + escapeJs(dropoffJob.id || '') + '\')" title="View details">' +
                            '<i class="fas fa-eye"></i>' +
                        '</button>' +
                    '</div>' +
                '</div>';
    }
    pendingDropoffList.innerHTML = html;
}

function getCollectorsData() {
    return Array.isArray(collectionJobsData.activeCollectors) ? collectionJobsData.activeCollectors : [];
}

function loadActiveCollectors() {
    var collectorList = document.getElementById('activeCollectorList');
    var collectorCount = document.getElementById('activeCollectorCount');
    var collectors = getCollectorsData().filter(function(c) { return c.jobStatus === 'Ongoing'; });

    if (!collectorList) return;
    if (collectorCount) collectorCount.textContent = collectors.length;

    if (!collectors.length) {
        collectorList.innerHTML = '<div class="no-jobs-message"><i class="fas fa-user-check"></i><p>No active collectors</p></div>';
        return;
    }

    var html = '';
    for (var i = 0; i < collectors.length; i++) {
        var collector = collectors[i];
        var statusClass = collector.status === 'online' ? 'online' : 'busy';
        var initials = getInitials(collector.name || 'NA');
        var activeClass = selectedCollectorId === collector.id ? 'active' : '';
        html += '<div class="collector-list-item ' + activeClass + '" onclick="selectCollector(\'' + escapeJs(collector.id || '') + '\')"><div class="collector-list-info"><div class="collector-avatar">' + escapeHtml(initials) + '</div><div class="collector-details"><span class="collector-list-name">' + escapeHtml(collector.name || '-') + '</span><span class="collector-list-vehicle">' + escapeHtml(collector.vehicle || '-') + '</span></div></div><div class="collector-list-status"><span class="status-badge-collector ' + statusClass + '"></span>' + (collector.jobId ? '<span class="collector-job-id">' + escapeHtml(collector.jobId) + '</span>' : '<span>Available</span>') + '</div></div>';
    }
    collectorList.innerHTML = html;
}

function loadQuickStats() {
    var completedToday = document.getElementById('completedToday');
    var avgResponse = document.getElementById('avgResponse');
    var totalDistance = document.getElementById('totalDistance');

    if (completedToday) completedToday.textContent = collectionJobsData.quickStats.completedToday || 0;
    if (avgResponse) avgResponse.textContent = collectionJobsData.quickStats.avgResponse || '0min';
    if (totalDistance) totalDistance.textContent = collectionJobsData.quickStats.totalDistance || 0;
}

function updateCounts() {
    var handoverCount = document.getElementById('panelHandoverCount');
    var delayedCount = document.getElementById('panelDelayedCount');
    var pendingDropoffCount = document.getElementById('pendingDropoffCount');
    var activeCollectorCount = document.getElementById('activeCollectorCount');

    if (handoverCount) handoverCount.textContent = (collectionJobsData.handoverJobs || []).length;
    if (delayedCount) delayedCount.textContent = (collectionJobsData.delayedJobs || []).length;
    if (pendingDropoffCount) pendingDropoffCount.textContent = (collectionJobsData.pendingDropoffJobs || []).length;
    if (activeCollectorCount) {
        activeCollectorCount.textContent = getCollectorsData().filter(function(c) { return c.jobStatus === 'Ongoing'; }).length;
    }
}

function initMap() {
    var mapEl = document.getElementById('actualMap');
    var placeholderEl = document.getElementById('mapPlaceholder');

    if (!mapEl || typeof L === 'undefined') return;

    if (map) {
        updateMapMarkers();
        return;
    }

    if (placeholderEl) placeholderEl.style.display = 'none';
    mapEl.style.display = 'block';

    map = L.map('actualMap').setView([3.1390, 101.6869], 11);
    map.attributionControl.setPrefix('');

    tilesLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);
    routeLayer = L.layerGroup().addTo(map);

    map.on('move zoom', function() {
        positionEtaBubble();
    });
}

function updateMapMarkers() {
    if (!map || !markersLayer) return;

    markersLayer.clearLayers();

    var collectors = getCollectorsData().filter(function(c) { return c.jobStatus === 'Ongoing'; });

    if (!collectors.length) return;

    for (var i = 0; i < collectors.length; i++) {
        var collector = collectors[i];
        var lat = collector.pickupLat || (3.10 + (i * 0.02));
        var lng = collector.pickupLng || (101.60 + (i * 0.02));

        var marker = L.marker([lat, lng]).addTo(markersLayer);
        marker.bindPopup('<strong>' + escapeHtml(collector.name || '-') + '</strong><br>' + escapeHtml(collector.vehicle || '-') + '<br>' + (collector.jobId ? 'Job: ' + escapeHtml(collector.jobId) : 'No active job'));
    }

    if (!selectedCollectorId) {
        var group = L.featureGroup(markersLayer.getLayers());
        if (group.getLayers().length) {
            map.fitBounds(group.getBounds().pad(0.2));
        }
    }
}

function notifyCollector(jobId) {
    // alert('Notification sent for job ' + jobId);
}

function contactCollector(jobId) {
    // alert('Contacting collector for job ' + jobId);
}

function selectCollector(collectorId) {
    selectedCollectorId = collectorId;
    loadActiveCollectors();
    startGPSSimulationForCollector(collectorId);
}

async function startGPSSimulationForCollector(collectorId) {
    var collectors = getCollectorsData();
    var collector = null;
    for (var i = 0; i < collectors.length; i++) {
        if (collectors[i].id === collectorId) {
            collector = collectors[i];
            break;
        }
    }
    if (!collector) return;
    
    stopSimulation();
    
    if (routeLayer) {
        routeLayer.clearLayers();
    }
    
    var routeInfoBox = document.getElementById('routeInfoBox');
    var routeCollectorName = document.getElementById('routeCollectorName');
    var routeCurrentLocation = document.getElementById('routeCurrentLocation');
    
    if (routeCollectorName) routeCollectorName.textContent = collector.vehicle || '-';
    if (routeCurrentLocation) routeCurrentLocation.textContent = 'Loading...';
    if (routeInfoBox) routeInfoBox.style.display = 'block';
    
    await startGPSSimulation(collector);
}



function centerMapOnAll() {
    selectedCollectorId = null;
    stopSimulation();

    if (routeLayer) {
        routeLayer.clearLayers();
    }

    var routeInfoBox = document.getElementById('routeInfoBox');
    if (routeInfoBox) routeInfoBox.style.display = 'none';

    hideEtaBubble();
    loadActiveCollectors();
    updateMapMarkers();
}

function zoomToFit() {
    if (selectedCollectorId) {
        startGPSSimulationForCollector(selectedCollectorId);
    } else {
        centerMapOnAll();
    }
}

function toggleMapLayers() {
    if (!map || !tilesLayer) return;

    if (map.hasLayer(tilesLayer)) {
        map.removeLayer(tilesLayer);
    } else {
        map.addLayer(tilesLayer);
    }

    mapLayersVisible = !mapLayersVisible;
}

function showEtaBubble(lat, lng, text, isEnRoute) {
    var etaBubble = document.getElementById('etaBubble');
    var etaBubbleText = document.getElementById('etaBubbleText');
    var etaDot = document.querySelector('.eta-dot');

    if (!etaBubble || !map) return;

    if (etaBubbleText) etaBubbleText.textContent = text;
    
    if (etaDot) {
        if (isEnRoute) {
            etaDot.style.animation = 'etaBlink 1s infinite';
        } else {
            etaDot.style.animation = 'none';
            etaDot.style.opacity = '1';
        }
    }
    
    etaBubble.dataset.lat = lat;
    etaBubble.dataset.lng = lng;
    etaBubble.style.display = 'flex';

    positionEtaBubble();
}

function positionEtaBubble() {
    var etaBubble = document.getElementById('etaBubble');
    var mapContainer = document.getElementById('mapContainer');

    if (!etaBubble || !mapContainer || etaBubble.style.display === 'none') return;
    if (!etaBubble.dataset.lat || !etaBubble.dataset.lng || !map) return;

    var lat = parseFloat(etaBubble.dataset.lat);
    var lng = parseFloat(etaBubble.dataset.lng);
    
    var point = map.latLngToContainerPoint([lat, lng]);

    etaBubble.style.left = point.x + 'px';
    etaBubble.style.top = (point.y - 12) + 'px';
}

function hideEtaBubble() {
    var etaBubble = document.getElementById('etaBubble');
    if (etaBubble) etaBubble.style.display = 'none';
}

function getDelayedJobById(jobId) {
    return (collectionJobsData.delayedLookup || {})[jobId] || null;
}

function openReportIssueModal(jobId) {
    var formattedJobId = formatJobId(jobId);
    var numericJobId = extractNumericJobId(jobId);
    
    var job = (collectionJobsData.handoverLookup || {})[formattedJobId] || 
              (collectionJobsData.delayedLookup || {})[formattedJobId] || 
              (collectionJobsData.pendingDropoffLookup || {})[formattedJobId];
    
    if (!job) {
        var allJobs = [
            ...(collectionJobsData.handoverJobs || []),
            ...(collectionJobsData.delayedJobs || []),
            ...(collectionJobsData.pendingDropoffJobs || [])
        ];
        job = allJobs.find(j => j.jobID == numericJobId);
    }
    
    var modal = document.getElementById('reportIssueModal');
    var form = document.getElementById('reportIssueForm');
    var issueJobId = document.getElementById('issueJobId');
    var issueRequestId = document.getElementById('issueRequestId');
    var issueSubject = document.getElementById('issueSubject');
    var otherIssueGroupElem = document.getElementById('otherIssueGroup');
    var otherIssueTextElem = document.getElementById('otherIssueText');

    if (!job) {
        console.error('Job not found for ID:', jobId);
        // alert('Job details not found. Please try again.');
        return;
    }

    if (!modal || !form) {
        console.error('Modal or form not found');
        return;
    }

    form.reset();

    var priorityOptions = document.querySelectorAll('.priority-option');
    for (var i = 0; i < priorityOptions.length; i++) {
        priorityOptions[i].classList.remove('selected');
    }

    if (otherIssueGroupElem) otherIssueGroupElem.style.display = 'none';
    if (otherIssueTextElem) {
        otherIssueTextElem.removeAttribute('required');
        otherIssueTextElem.value = '';
    }

    if (issueJobId) {
        issueJobId.value = formattedJobId;
        issueJobId.setAttribute('data-numeric-id', numericJobId);
    }
    
    if (issueRequestId && job.requestID) {
        issueRequestId.value = 'REQ' + String(job.requestID).padStart(3, '0');
    }
    
    if (issueSubject) {
        issueSubject.placeholder = 'Issue with ' + formattedJobId;
        issueSubject.value = '';
    }

    modal.classList.add('active');
}

function closeReportIssueModal() {
    var modal = document.getElementById('reportIssueModal');
    var form = document.getElementById('reportIssueForm');
    var otherIssueGroupElem = document.getElementById('otherIssueGroup');
    var otherIssueTextElem = document.getElementById('otherIssueText');

    if (modal) modal.classList.remove('active');
    
    if (form) {
        form.reset(); 
       
        var issueJobId = document.getElementById('issueJobId');
        if (issueJobId) {
            issueJobId.value = '';
            issueJobId.removeAttribute('data-numeric-id');
        }
        
        var issueRequestId = document.getElementById('issueRequestId');
        if (issueRequestId) {
            issueRequestId.value = '';
        }
        
        var issueSubject = document.getElementById('issueSubject');
        if (issueSubject) {
            issueSubject.value = '';
        }
        
        var issueDescription = document.getElementById('issueDescription');
        if (issueDescription) {
            issueDescription.value = '';
        }
        
        var issueTypeSelect = document.getElementById('issueType');
        if (issueTypeSelect) {
            issueTypeSelect.value = '';
        }
    }

    var priorityOptions = document.querySelectorAll('.priority-option');
    for (var i = 0; i < priorityOptions.length; i++) {
        priorityOptions[i].classList.remove('selected');
        var radio = priorityOptions[i].querySelector('input[type="radio"]');
        if (radio) {
            radio.checked = false;
        }
    }

    if (otherIssueGroupElem) {
        otherIssueGroupElem.style.display = 'none';
    }
    if (otherIssueTextElem) {
        otherIssueTextElem.removeAttribute('required');
        otherIssueTextElem.value = '';
    }
}

function assignHandover(jobId) {
    var job = (collectionJobsData.handoverLookup || {})[jobId];
    if (!job) return;

    var handoverJobId = document.getElementById('handoverJobId');
    var handoverCurrentCollector = document.getElementById('handoverCurrentCollector');
    var handoverReason = document.getElementById('handoverReason');
    
    if (handoverJobId) handoverJobId.value = job.id || '';
    if (handoverCurrentCollector) handoverCurrentCollector.value = job.collector || '';
    if (handoverReason) handoverReason.value = job.reason || '';

    var assignHandoverModal = document.getElementById('assignHandoverModal');
    if (assignHandoverModal) assignHandoverModal.classList.add('show');
}

function closeAssignHandoverModal() {
    var assignHandoverModal = document.getElementById('assignHandoverModal');
    if (assignHandoverModal) assignHandoverModal.classList.remove('show');
}

function viewJobDetails(jobId) {
    var job = (collectionJobsData.handoverLookup || {})[jobId] || (collectionJobsData.delayedLookup || {})[jobId];
    if (!job) return;

    var detailsJobId = document.getElementById('detailsJobId');
    var detailsStatus = document.getElementById('detailsStatus');
    var detailsTime = document.getElementById('detailsTime');
    var detailsLocation = document.getElementById('detailsLocation');
    var detailsCollector = document.getElementById('detailsCollector');
    var detailsVehicle = document.getElementById('detailsVehicle');
    var detailsReason = document.getElementById('detailsReason');
    var detailsAdminNotes = document.getElementById('detailsAdminNotes');

    if (detailsJobId) detailsJobId.textContent = job.id || '-';
    if (detailsStatus) detailsStatus.textContent = job.status || '-';
    if (detailsTime) detailsTime.textContent = job.time || '-';
    if (detailsLocation) detailsLocation.textContent = job.location || '-';
    if (detailsCollector) detailsCollector.textContent = job.collector || '-';
    if (detailsVehicle) detailsVehicle.textContent = job.vehicle || '-';
    if (detailsReason) detailsReason.textContent = job.reason || '-';
    if (detailsAdminNotes) detailsAdminNotes.value = '';

    var jobDetailsModal = document.getElementById('jobDetailsModal');
    if (jobDetailsModal) jobDetailsModal.classList.add('show');
}

function closeJobDetailsModal() {
    var jobDetailsModal = document.getElementById('jobDetailsModal');
    if (jobDetailsModal) jobDetailsModal.classList.remove('show');
}

// function reassignJob(jobId) {
//     var job = (collectionJobsData.delayedLookup || {})[jobId];
//     if (!job) return;

//     var reassignJobId = document.getElementById('reassignJobId');
//     var reassignCurrentCollector = document.getElementById('reassignCurrentCollector');
//     var reassignDelayReason = document.getElementById('reassignDelayReason');
    
//     if (reassignJobId) reassignJobId.value = job.id || '';
//     if (reassignCurrentCollector) reassignCurrentCollector.value = job.collector || '';
//     if (reassignDelayReason) reassignDelayReason.value = (job.reason || '-') + (job.delay ? ' (' + job.delay + ')' : '');

//     var reassignJobModal = document.getElementById('reassignJobModal');
//     if (reassignJobModal) reassignJobModal.classList.add('show');
// }

// function closeReassignJobModal() {
//     var reassignJobModal = document.getElementById('reassignJobModal');
//     if (reassignJobModal) reassignJobModal.classList.remove('show');
// }

// function reassignCentre(jobId) {
//     var job = (collectionJobsData.pendingDropoffLookup || {})[jobId];
//     if (!job) return;

//     var reassignCentreJobId = document.getElementById('reassignCentreJobId');
//     var reassignCentreCollector = document.getElementById('reassignCentreCollector');
//     var reassignCentreOriginal = document.getElementById('reassignCentreOriginal');
//     var reassignCentreReason = document.getElementById('reassignCentreReason');
    
//     if (reassignCentreJobId) reassignCentreJobId.value = job.id || '';
//     if (reassignCentreCollector) reassignCentreCollector.value = job.collector || '';
//     if (reassignCentreOriginal) reassignCentreOriginal.value = job.originalCentre || '';
//     if (reassignCentreReason) reassignCentreReason.value = job.failReason || '';

//     var reassignCentreModal = document.getElementById('reassignCentreModal');
//     if (reassignCentreModal) reassignCentreModal.classList.add('show');
// }

// function closeReassignCentreModal() {
//     var reassignCentreModal = document.getElementById('reassignCentreModal');
//     if (reassignCentreModal) reassignCentreModal.classList.remove('show');
// }

function viewFailedDropoffDetails(jobId) {
    var job = (collectionJobsData.pendingDropoffLookup || {})[jobId];
    if (!job) return;

    var detailsJobId = document.getElementById('detailsJobId');
    var detailsStatus = document.getElementById('detailsStatus');
    var detailsTime = document.getElementById('detailsTime');
    var detailsLocation = document.getElementById('detailsLocation');
    var detailsCollector = document.getElementById('detailsCollector');
    var detailsReason = document.getElementById('detailsReason');
    var detailsAdminNotes = document.getElementById('detailsAdminNotes');

    if (detailsJobId) detailsJobId.textContent = job.id || '-';
    if (detailsStatus) detailsStatus.textContent = 'Failed Drop-off';
    if (detailsTime) detailsTime.textContent = job.time || '-';
    if (detailsLocation) detailsLocation.textContent = job.originalCentre || '-';
    if (detailsCollector) detailsCollector.textContent = job.collector || '-';
    if (detailsReason) detailsReason.textContent = job.failReason || '-';
    if (detailsAdminNotes) detailsAdminNotes.value = '';

    var jobDetailsModal = document.getElementById('jobDetailsModal');
    if (jobDetailsModal) jobDetailsModal.classList.add('show');
}

function getInitials(name) {
    var parts = String(name || '').trim().split(/\s+/).filter(function(p) { return p.length > 0; });
    if (!parts.length) return 'NA';
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeJs(value) {
    return String(value || '')
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '\\r');
}

window.assignHandover = assignHandover;
window.closeAssignHandoverModal = closeAssignHandoverModal;
window.viewJobDetails = viewJobDetails;
window.closeJobDetailsModal = closeJobDetailsModal;
window.notifyCollector = notifyCollector;
// window.reassignJob = reassignJob;
// window.closeReassignJobModal = closeReassignJobModal;
// window.reassignCentre = reassignCentre;
// window.closeReassignCentreModal = closeReassignCentreModal;
// window.contactCollector = contactCollector;
window.selectCollector = selectCollector;
window.centerMapOnAll = centerMapOnAll;
window.toggleMapLayers = toggleMapLayers;
window.zoomToFit = zoomToFit;

function goBackToJobs() {
    window.location.href = '../../html/admin/aJobs.php';
}

window.openReportIssueModal = openReportIssueModal;
window.closeReportIssueModal = closeReportIssueModal;
window.viewFailedDropoffDetails = viewFailedDropoffDetails;