const body = document.body;

//  // Dark Mode/ Light Mode Toggle

const toggleBtnMobile = document.getElementById('themeToggleMobile');
const toggleBtnDesktop = document.getElementById('themeToggleDesktop'); 
const settingImg = document.getElementById('settingImg');
const settingImgM = document.getElementById('settingImgM');
const menuBtn = document.getElementById('menuBtn');

// Add event listeners to theme toggle button
if (toggleBtnMobile) {
    toggleBtnMobile.addEventListener('click', toggleTheme);
}
if (toggleBtnDesktop) {
    toggleBtnDesktop.addEventListener('click', toggleTheme);
}

// Load saved theme on page load
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        body.classList.add('dark-mode');
        updateThemeIcons(true);
    }
});

function toggleTheme() {
    body.classList.toggle('dark-mode');
    
    // Save the current theme to localStorage
    const isDark = body.classList.contains('dark-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');

    // Update button icons based on theme
    updateThemeIcons(isDark);
}

// Function to update theme-related icons for light and dark modes
function updateThemeIcons(isDark) {
    if (toggleBtnMobile) {
        const img1 = toggleBtnMobile.querySelector('img');
        if (img1) {
            img1.src = isDark ? '../assets/images/dark-mode-icon-mobile.svg' : '../assets/images/light-mode-icon.svg';
            img1.alt = isDark ? 'Dark Mode Icon' : 'Light Mode Icon';
        }
    }
    if (toggleBtnDesktop) {
        const img2 = toggleBtnDesktop.querySelector('img');
        if (img2) {
            img2.src = isDark ? '../assets/images/dark-mode-icon.svg' : '../assets/images/light-mode-icon.svg';
            img2.alt = isDark ? 'Dark Mode Icon' : 'Light Mode Icon';
        }
    }
    if (settingImg) {
        settingImg.src = isDark ? '../assets/images/setting-dark.svg' : '../assets/images/setting-light.svg';
    }
    if (settingImgM) {
        settingImgM.src = isDark ? '../assets/images/setting-dark.svg' : '../assets/images/setting-light.svg';
    }
    if (menuBtn) {
        menuBtn.src = isDark ? '../assets/images/icon-menu-dark.svg' : '../assets/images/icon-menu.svg';
    }
}

//  // Separate diff parts of main script like this

function showMenu() {
    sidebarNav.style.transform = "translateX(0)";
    cover.classList.add('cover');
    document.body.classList.add('stopScroll');
}

function hideMenu() {
    sidebarNav.style.transform = "translateX(100%)";
    cover.classList.remove('cover');
    document.body.classList.remove('stopScroll');
}