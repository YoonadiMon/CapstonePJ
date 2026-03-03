// E-waste Guide Dropdown Functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // Get the E-waste Guide link elements
    const desktopGuideLink = document.querySelector('.c-navbar-desktop a[href*="EwasteGuide"]');
    const mobileGuideLink = document.querySelector('.c-navbar-side-items a[href*="EwasteGuide"]');
    
    // Create dropdown container for desktop
    if (desktopGuideLink) {
        createDesktopDropdown(desktopGuideLink);
    }
    
    // Create dropdown for mobile sidebar
    if (mobileGuideLink) {
        createMobileDropdown(mobileGuideLink);
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.c-navbar-desktop') && !event.target.closest('.c-dropdown-content')) {
            const dropdowns = document.querySelectorAll('.c-dropdown-content');
            dropdowns.forEach(dropdown => {
                dropdown.style.display = 'none';
            });
        }
    });
});

function createDesktopDropdown(guideLink) {
    // Prevent default link behavior
    guideLink.addEventListener('click', function(e) {
        e.preventDefault();
    });
    
    // Create dropdown structure
    const dropdownContainer = document.createElement('div');
    dropdownContainer.className = 'c-dropdown-container';
    
    // Wrap the guide link and dropdown content
    const parent = guideLink.parentNode;
    parent.replaceChild(dropdownContainer, guideLink);
    dropdownContainer.appendChild(guideLink);
    
    // Create dropdown content
    const dropdownContent = document.createElement('div');
    dropdownContent.className = 'c-dropdown-content';
    dropdownContent.innerHTML = `
        <a href="/main/html/provider/pFindCentre.html" class="c-dropdown-item">📍 Find a Centre</a>
        <a href="/main/html/provider/pAcceptedItems.html" class="c-dropdown-item">📋 Accepted Items</a>
        <a href="/main/html/provider/pPreparationGuide.html" class="c-dropdown-item">📝 Preparation Guide</a>
        <a href="/main/html/provider/pTheJourney.html" class="c-dropdown-item">🔄 The Journey</a>
    `;
    
    dropdownContainer.appendChild(dropdownContent);
    
    // Toggle dropdown on click
    guideLink.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Close all other dropdowns first
        document.querySelectorAll('.c-dropdown-content').forEach(dd => {
            if (dd !== dropdownContent) {
                dd.style.display = 'none';
            }
        });
        
        // Toggle current dropdown
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
    });
}

function createMobileDropdown(guideLink) {
    // Prevent default link behavior
    guideLink.addEventListener('click', function(e) {
        e.preventDefault();
    });
    
    // Create dropdown structure for mobile
    const dropdownContainer = document.createElement('div');
    dropdownContainer.className = 'c-mobile-dropdown-container';
    
    // Get the parent of the guide link (the sidebar items container)
    const parent = guideLink.parentNode;
    
    // Insert dropdown after the guide link
    const dropdownContent = document.createElement('div');
    dropdownContent.className = 'c-mobile-dropdown-content';
    dropdownContent.innerHTML = `
        <a href="/main/html/provider/pFindCentre.html" class="c-mobile-dropdown-item">📍 Find a Centre</a>
        <a href="/main/html/provider/pAcceptedItems.html" class="c-mobile-dropdown-item">📋 Accepted Items</a>
        <a href="/main/html/provider/pPreparationGuide.html" class="c-mobile-dropdown-item">📝 Preparation Guide</a>
        <a href="/main/html/provider/pTheJourney.html" class="c-mobile-dropdown-item">🔄 The Journey</a>
    `;
    
    // Insert dropdown content after the guide link
    guideLink.insertAdjacentElement('afterend', dropdownContent);
    
    // Toggle dropdown on click
    guideLink.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Toggle current dropdown
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
    });
}