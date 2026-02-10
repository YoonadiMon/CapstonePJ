const body = document.body;

//  // Dark Mode/ Light Mode Toggle

const toggleBtn = document.getElementById('themeToggle'); 

// Add event listeners to theme toggle button
if (toggleBtn) {
    toggleBtn.addEventListener('click', toggleTheme);
    console.log('Theme toggle button found and event listener added.');
}

function toggleTheme() {
    body.classList.toggle('dark-mode');
    
    // Save the current theme to localStorage
    const isDark = body.classList.contains('dark-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

//  // Separate diff parts of main script like this