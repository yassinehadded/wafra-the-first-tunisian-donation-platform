// Back Office Dashboard JavaScript
// API endpoints for dashboard statistics and charts
const userStatsApi = '../../controller/api/user_stats.php';
const registrationsChartApi = '../../controller/api/user_registrations_chart.php';
const loginActivityChartApi = '../../controller/api/login_activity_chart.php';

let registrationsChartInstance = null;
let loginActivityChartInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the dashboard
    initializeDashboard();

    // Sidebar navigation
    setupSidebarNavigation();

    // Dark mode toggle
    setupDarkModeToggle();

    // Search functionality
    setupSearch();

    // User stats + charts
    initializeUserStats();

    // Initialize charts
    initializeCharts();

    // Sidebar toggle for mobile
    setupSidebarToggle();

    // User management functionality
    setupUserFilters();
    setupUserModal();
    setupTableActions();

    // Donations management functionality
    setupDonationFilters();
    setupDonationActions();
    setupDonationTypeCards();

    // Reports charts
    initializeReportsCharts();

    // Reports navigation
    setupReportsNavigation();

    // Notification dropdown navigation
    setupNotificationNavigation();

    // Profile dropdown navigation
    setupProfileNavigation();
});

function setupProfileNavigation() {
    const profileDropdownMenu = document.querySelector('#profileDropdown + .dropdown-menu');

    if (profileDropdownMenu) {
        profileDropdownMenu.addEventListener('click', function(e) {
            const item = e.target.closest('.dropdown-item');
            if (item) {
                e.preventDefault();

                const sectionId = item.getAttribute('data-section');
                if (sectionId) {
                    // Update sidebar active state
                    document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));
                    document.querySelector(`[data-section="${sectionId}"]`).parentElement.classList.add('active');

                    // Show the corresponding section
                    showSection(sectionId);
                } else if (item.textContent.trim().toLowerCase().includes('logout')) {
                    // Handle logout by redirecting to logout page
                    window.location.href = '../frontoffice/logout.php';
                }
            }
        });
    }
}

function initializeDashboard() {
    const initialSection = window.defaultSection || 'dashboard';
    showSection(initialSection);

    // Ensure sidebar reflects initial state on load
    const initialLink = document.querySelector(`.sidebar-link[data-section="${initialSection}"]`);
    if (initialLink) {
        document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));
        initialLink.parentElement.classList.add('active');
    }
}

function setupSidebarNavigation() {
    const sidebarLinks = document.querySelectorAll('.sidebar-link');

    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all sidebar items
            document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));

            // Add active class to the parent li of clicked link
            this.parentElement.classList.add('active');

            // Show corresponding section
            const sectionId = this.getAttribute('data-section');
            showSection(sectionId);
            updateUrlSection(sectionId);
        });
    });
}

function showSection(sectionId) {
    // Hide all sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.classList.remove('active');
        section.style.display = 'none';
    });

    // Show selected section
    const targetSection = document.getElementById(sectionId + '-section');
    if (targetSection) {
        targetSection.classList.add('active');
        targetSection.style.display = 'block';
    }
}

function setupDarkModeToggle() {
    const darkModeToggle = document.getElementById('toggle-dark');
    const body = document.body;
    const themeToggle = document.querySelector('.theme-toggle');
    const sunIcon = themeToggle.querySelector('.iconify--system-uicons');
    const moonIcon = themeToggle.querySelector('.iconify--mdi');

    // Prevent clicks on icons
    if (sunIcon) sunIcon.style.pointerEvents = 'none';
    if (moonIcon) moonIcon.style.pointerEvents = 'none';

    // Check for saved theme preference or default to light mode
    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') {
        body.classList.add('dark-mode');
        darkModeToggle.checked = true;
        updateDarkModeIcon(true, sunIcon, moonIcon);
    } else {
        updateDarkModeIcon(false, sunIcon, moonIcon);
    }

    // Only the checkbox toggles the theme
    darkModeToggle.addEventListener('change', function() {
        const isDarkMode = this.checked;
        if (isDarkMode) {
            body.classList.add('dark-mode');
        } else {
            body.classList.remove('dark-mode');
        }

        // Save theme preference
        localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');

        // Update icon
        updateDarkModeIcon(isDarkMode, sunIcon, moonIcon);
    });
}

function updateDarkModeIcon(isDarkMode, sunIcon, moonIcon) {
    if (isDarkMode) {
        sunIcon.style.display = 'inline';
        moonIcon.style.display = 'none';
    } else {
        sunIcon.style.display = 'none';
        moonIcon.style.display = 'inline';
    }
}

function setupSearch() {
    // Global search bar in navbar
    const globalSearchInput = document.getElementById('globalSearchInput');
    const globalSearchForm = document.getElementById('globalSearchForm');
    const globalSearchBtn = document.getElementById('globalSearchBtn');

    if (globalSearchForm) {
        // Prevent form submission
        globalSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performGlobalSearch();
        });

        // Handle search button click
        if (globalSearchBtn) {
            globalSearchBtn.addEventListener('click', function(e) {
                e.preventDefault();
                performGlobalSearch();
            });
        }

        // Handle Enter key in search input
        if (globalSearchInput) {
            globalSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performGlobalSearch();
                }
            });

            // Real-time search as user types (with debounce)
            let searchTimeout;
            globalSearchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    performGlobalSearch();
                }, 300); // Wait 300ms after user stops typing
            });
        }
    }

    // Users section search
    const userSearch = document.getElementById('userSearch');
    const usersTable = document.getElementById('usersTable');

    if (userSearch && usersTable) {
        userSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = usersTable.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Donations search
    const donationSearch = document.getElementById('donationSearch');
    const donationsTable = document.getElementById('donationsTable');

    if (donationSearch && donationsTable) {
        donationSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = donationsTable.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Login Sessions search
    const loginSessionsTable = document.querySelector('#loginSessions-section table');
    if (loginSessionsTable && globalSearchInput) {
        // This will be handled by performGlobalSearch function
    }
}

function performGlobalSearch() {
    const searchInput = document.getElementById('globalSearchInput');
    if (!searchInput) return;

    const searchTerm = searchInput.value.trim().toLowerCase();
    const currentSection = document.querySelector('.content-section.active');
    
    if (!searchTerm) {
        // If search is empty, show all rows in current section
        if (currentSection) {
            const rows = currentSection.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
        }
        return;
    }

    // If already in a specific section, search only in that section
    if (currentSection) {
        const sectionId = currentSection.id;
        
        if (sectionId === 'users-section') {
            // Search in users table
            const usersTable = document.getElementById('usersTable');
            if (usersTable) {
                const rows = usersTable.querySelectorAll('tbody tr');
                let hasMatches = false;
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                        hasMatches = true;
                    } else {
                        row.style.display = 'none';
                    }
                });
                if (!hasMatches) {
                    showAlert('No users found matching: ' + searchTerm, 'info');
                }
            }
            return;
        } else if (sectionId === 'loginSessions-section') {
            // Search in login sessions table
            const sessionsTable = currentSection.querySelector('table');
            if (sessionsTable) {
                const rows = sessionsTable.querySelectorAll('tbody tr');
                let hasMatches = false;
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                        hasMatches = true;
                    } else {
                        row.style.display = 'none';
                    }
                });
                if (!hasMatches) {
                    showAlert('No sessions found matching: ' + searchTerm, 'info');
                }
            }
            return;
        }
    }

    // If not in a specific section or in dashboard, search all sections
    let foundInUsers = false;
    let foundInSessions = false;

    // Check users section
    const usersTable = document.getElementById('usersTable');
    if (usersTable) {
        const userRows = usersTable.querySelectorAll('tbody tr');
        userRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                foundInUsers = true;
            }
        });
    }

    // Check login sessions section
    const loginSessionsSection = document.getElementById('loginSessions-section');
    let sessionsTable = null;
    if (loginSessionsSection) {
        sessionsTable = loginSessionsSection.querySelector('table');
        if (sessionsTable) {
            const sessionRows = sessionsTable.querySelectorAll('tbody tr');
            sessionRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    foundInSessions = true;
                }
            });
        }
    }

    // Determine which section to show based on matches
    // Priority: users first, then sessions
    if (foundInUsers) {
        // Switch to users section and perform search
        showSection('users');
        updateUrlSection('users');
        // Update sidebar active state
        document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));
        const usersLink = document.querySelector('[data-section="users"]');
        if (usersLink) {
            usersLink.parentElement.classList.add('active');
        }
        // Now filter the users table
        if (usersTable) {
            const rows = usersTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    } else if (foundInSessions) {
        // Switch to login sessions section and perform search
        showSection('loginSessions');
        updateUrlSection('loginSessions');
        // Update sidebar active state
        document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));
        const loginSessionsLink = document.querySelector('[data-section="loginSessions"]');
        if (loginSessionsLink) {
            loginSessionsLink.parentElement.classList.add('active');
        }
        // Now filter the sessions table
        if (sessionsTable) {
            const rows = sessionsTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    } else {
        // No matches found in any section
        showAlert('No results found for: ' + searchTerm, 'info');
    }
}

function searchUsersTable(searchTerm) {
    const usersTable = document.getElementById('usersTable');
    if (!usersTable) return false;

    const rows = usersTable.querySelectorAll('tbody tr');
    let hasMatches = false;

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
            hasMatches = true;
        } else {
            row.style.display = 'none';
        }
    });

    return hasMatches;
}

function searchLoginSessionsTable(searchTerm) {
    const loginSessionsSection = document.getElementById('loginSessions-section');
    if (!loginSessionsSection) return false;

    const table = loginSessionsSection.querySelector('table');
    if (!table) return false;

    const rows = table.querySelectorAll('tbody tr');
    let hasMatches = false;

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
            hasMatches = true;
        } else {
            row.style.display = 'none';
        }
    });

    return hasMatches;
}

function initializeUserStats() {
    loadUserStats();

    const refreshBtn = document.getElementById('refreshUserStats');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            refreshBtn.disabled = true;
            Promise.all([loadUserStats(), loadDashboardCharts()]).finally(() => {
                refreshBtn.disabled = false;
            });
        });
    }
}

async function loadUserStats() {
    const data = await fetchJsonSafe(userStatsApi, 'Unable to load user statistics.');
    if (!data) return;

    setStatValue('total_users', formatNumber(data.total_users ?? 0));
    setStatValue('active_users', formatNumber(data.active_users ?? 0));
    setStatValue('inactive_users', formatNumber(data.inactive_users ?? 0));
    setStatValue('new_users_today', formatNumber(data.new_users_today ?? 0));
    setStatValue('new_users_month', formatNumber(data.new_users_month ?? 0));

    const loginAttempts = data.login_attempts || { total: 0, success: 0, failed: 0 };
    const attemptsText = `Total: ${formatNumber(loginAttempts.total)} | ✓ ${formatNumber(loginAttempts.success)} / ✗ ${formatNumber(loginAttempts.failed)}`;
    setStatValue('login_attempts', attemptsText);

    setStatValue('users_online', formatNumber(data.users_online ?? 0));
}

function setStatValue(key, value) {
    const el = document.querySelector(`[data-stat="${key}"]`);
    if (el) {
        el.textContent = value;
    }
}

function formatNumber(value) {
    if (value === null || value === undefined) return '0';
    if (typeof value === 'number') {
        return value.toLocaleString('en-US');
    }
    const numeric = Number(value);
    return Number.isFinite(numeric) ? numeric.toLocaleString('en-US') : value;
}

async function fetchJsonSafe(url, errorMessage = null) {
    try {
        const response = await fetch(url, { credentials: 'same-origin' });
        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.error(`Error fetching ${url}:`, error);
        if (errorMessage) {
            showAlert(errorMessage, 'danger');
        }
        return null;
    }
}

async function initializeCharts() {
    await loadDashboardCharts();
}

async function loadDashboardCharts() {
    await Promise.all([loadRegistrationsChart(), loadLoginActivityChart()]);
}

async function loadRegistrationsChart() {
    const chartContainer = document.getElementById('userRegistrationsChart');
    if (!chartContainer || typeof Chart === 'undefined') return;

    const rows = await fetchJsonSafe(registrationsChartApi);
    if (!rows) return;

    const labels = rows.map(row => row.month);
    const values = rows.map(row => Number(row.registrations || 0));

    if (registrationsChartInstance) {
        registrationsChartInstance.destroy();
    }

    registrationsChartInstance = new Chart(chartContainer, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Registrations',
                data: values,
                backgroundColor: '#0d6efd'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });
}

async function loadLoginActivityChart() {
    const chartContainer = document.getElementById('loginActivityChart');
    if (!chartContainer || typeof Chart === 'undefined') return;

    const rows = await fetchJsonSafe(loginActivityChartApi);
    if (!rows) return;

    const labels = rows.map(row => row.day);
    const success = rows.map(row => Number(row.success_count || 0));
    const failed = rows.map(row => Number(row.failed_count || 0));

    if (loginActivityChartInstance) {
        loginActivityChartInstance.destroy();
    }

    loginActivityChartInstance = new Chart(chartContainer, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Success',
                    data: success,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.15)',
                    tension: 0.25,
                    fill: true
                },
                {
                    label: 'Failed',
                    data: failed,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.15)',
                    tension: 0.25,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            },
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

function setupSidebarToggle() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
}

// Utility functions
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Table sorting functionality
function makeTableSortable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => {
            sortTable(table, index);
        });
    });
}

function sortTable(table, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        const aText = a.cells[column].textContent.trim();
        const bText = b.cells[column].textContent.trim();

        // Check if values are numbers
        const aNum = parseFloat(aText);
        const bNum = parseFloat(bText);

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return aNum - bNum;
        }

        return aText.localeCompare(bText);
    });

    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

// Initialize sortable tables
makeTableSortable('usersTable');
makeTableSortable('donationsTable');

function setupUserFilters() {
    const filterAll = document.getElementById('filterAll');
    const filterActive = document.getElementById('filterActive');
    const filterInactive = document.getElementById('filterInactive');
    const usersTable = document.getElementById('usersTable');

    if (filterAll && filterActive && filterInactive && usersTable) {
        const filterButtons = [filterAll, filterActive, filterInactive];

        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');

                const filterType = this.id.replace('filter', '').toLowerCase();
                const rows = usersTable.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const statusBadge = row.querySelector('.badge');
                    const statusText = statusBadge ? statusBadge.textContent.toLowerCase() : '';

                    if (filterType === 'all' || statusText.includes(filterType)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    }
}

function setupUserModal() {
    const saveUserBtn = document.getElementById('saveUserBtn');
    const addUserForm = document.getElementById('addUserForm');

    if (saveUserBtn && addUserForm) {
        saveUserBtn.addEventListener('click', function() {
            const userData = {
                cin: document.getElementById('userCin').value,
                firstname: document.getElementById('userFirstname').value,
                lastname: document.getElementById('userLastname').value,
                email: document.getElementById('userEmail').value,
                password: document.getElementById('userPassword').value,
                confirmPassword: document.getElementById('userConfirmPassword').value,
                role: document.getElementById('userRole').value,
                status: document.getElementById('userStatus').value
            };

            // Validate form
            if (!userData.cin || !userData.firstname || !userData.lastname || !userData.email || !userData.password || !userData.confirmPassword || !userData.role || !userData.status) {
                showAlert('Please fill in all required fields.', 'warning');
                return;
            }

            // Check if passwords match
            if (userData.password !== userData.confirmPassword) {
                showAlert('Passwords do not match.', 'warning');
                return;
            }

            // Send AJAX request to add user
            fetch('../controller/add_user_backoffice.php', {
                method: 'POST',
                body: new URLSearchParams({
                    cin: userData.cin,
                    firstname: userData.firstname,
                    lastname: userData.lastname,
                    email: userData.email,
                    password: userData.password,
                    role: userData.role
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add user to table
                    addUserToTable(userData);

                    // Switch to users section to show the new user
                    document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));
                    document.querySelector('[data-section="users"]').parentElement.classList.add('active');
                    showSection('users');

                    // Reapply current filter to include the new user
                    const activeFilter = document.querySelector('#filterAll.active, #filterActive.active, #filterInactive.active');
                    if (activeFilter) {
                        activeFilter.click();
                    } else {
                        document.getElementById('filterAll').click();
                    }

                    // Close modal and reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                    modal.hide();
                    addUserForm.reset();

                    showAlert('User added successfully! A verification email has been sent.', 'success');
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while adding the user.', 'danger');
            });
        });
    }
}

function addUserToTable(userData) {
    const usersTable = document.getElementById('usersTable');
    if (!usersTable) return;

    const tbody = usersTable.querySelector('tbody');
    const newRow = document.createElement('tr');

    // Generate new ID (in a real app, this would come from the server)
    const newId = tbody.children.length + 1;

    // Determine badge classes based on role and status
    const roleBadgeClass = {
        'admin': 'bg-primary',
        'moderator': 'bg-secondary',
        'user': 'bg-info'
    }[userData.role] || 'bg-info';

    const statusBadgeClass = userData.status === 'active' ? 'bg-success' : 'bg-warning';

    newRow.innerHTML = `
        <td>${userData.cin}</td>
        <td>
            <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3">
                    <img src="../../dist/assets/compiled/jpg/${newId % 5 + 1}.jpg" alt="Avatar">
                </div>
                <span>${userData.firstname} ${userData.lastname}</span>
            </div>
        </td>
        <td>${userData.email}</td>
        <td><span class="badge ${roleBadgeClass}">${userData.role.charAt(0).toUpperCase() + userData.role.slice(1)}</span></td>
        <td><span class="badge ${statusBadgeClass}">${userData.status.charAt(0).toUpperCase() + userData.status.slice(1)}</span></td>
        <td>${new Date().toISOString().split('T')[0]}</td>
        <td>
            <div class="btn-group" role="group">
                <button class="btn btn-sm btn-outline-primary" title="View">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-warning" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>
    `;

    tbody.appendChild(newRow);
}

function setupTableActions() {
    const usersTable = document.getElementById('usersTable');

    if (usersTable) {
        usersTable.addEventListener('click', function(e) {
            const target = e.target.closest('button');
            if (!target) return;

            const action = target.title.toLowerCase();
            const row = target.closest('tr');

            switch (action) {
                case 'view':
                    viewUser(row);
                    break;
                case 'edit':
                    editUser(row);
                    break;
                case 'delete':
                    const userId = row.querySelector('td:first-child').textContent;
                    deleteUser(userId);
                    break;
            }
        });
    }
}

function viewUser(row) {
    const cells = row.querySelectorAll('td');
    const userData = {
        id: cells[0].textContent,
        name: cells[1].querySelector('span').textContent,
        email: cells[2].textContent,
        role: cells[3].querySelector('.badge').textContent.toLowerCase(),
        status: cells[4].querySelector('.badge').textContent.toLowerCase(),
        joinedDate: cells[5].textContent
    };

    showAlert(`Viewing user: ${userData.name}`, 'info');
    // In a real app, this would open a detailed view modal
}

function editUser(row) {
    const cells = row.querySelectorAll('td');
    const userData = {
        id: cells[0].textContent,
        name: cells[1].querySelector('span').textContent,
        email: cells[2].textContent,
        role: cells[3].querySelector('.badge').textContent.toLowerCase(),
        status: cells[4].querySelector('.badge').textContent.toLowerCase(),
        joinedDate: cells[5].textContent
    };

    showAlert(`Editing user: ${userData.name}`, 'info');
    // In a real app, this would open an edit modal with pre-filled data
}

function deleteUser(userId) {
    const userName = 'this user'; // You can get the name from the row if needed

    if (confirm(`Are you sure you want to delete this user?`)) {
        // Send AJAX request to delete user
        fetch(`../../controller/delete_user.php?id=${userId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Remove the row from the table
                const rows = document.querySelectorAll('#usersTable tbody tr');
                let rowRemoved = false;
                rows.forEach(row => {
                    const firstCell = row.querySelector('td:first-child');
                    if (firstCell && firstCell.textContent.trim() == userId) {
                        row.remove();
                        rowRemoved = true;
                    }
                });
                showAlert('User deleted successfully!', 'success');
                // Refresh the page to update stats
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('Error deleting user: ' + (data.message || 'Unknown error'), 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error deleting user: ' + error.message, 'danger');
        });
    }
}

// Donations management functions
function setupDonationFilters() {
    const donationFilterAll = document.getElementById('filterAllDonations');
    const donationFilterCompleted = document.getElementById('filterCompleted');
    const donationFilterPending = document.getElementById('filterPending');
    const donationsTable = document.getElementById('donationsTable');

    if (donationFilterAll && donationFilterCompleted && donationFilterPending && donationsTable) {
        const filterButtons = [donationFilterAll, donationFilterCompleted, donationFilterPending];

        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');

                let filterType = this.id.replace('filter', '').toLowerCase();
                if (filterType === 'alldonations') filterType = 'all';
                const rows = donationsTable.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const statusBadge = row.querySelector('.badge');
                    const statusText = statusBadge ? statusBadge.textContent.toLowerCase() : '';

                    if (filterType === 'all' || statusText.includes(filterType)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    }
}

function setupDonationActions() {
    const donationsTable = document.getElementById('donationsTable');

    if (donationsTable) {
        donationsTable.addEventListener('click', function(e) {
            const target = e.target.closest('button');
            if (!target) return;

            const action = target.title.toLowerCase();
            const row = target.closest('tr');

            switch (action) {
                case 'view':
                    viewDonation(row);
                    break;
                case 'edit':
                    editDonation(row);
                    break;
                case 'delete':
                    deleteDonation(row);
                    break;
            }
        });
    }
}

function viewDonation(row) {
    const cells = row.querySelectorAll('td');
    const donationData = {
        id: cells[0].textContent,
        donorName: cells[1].querySelector('span').textContent,
        amount: cells[2].textContent,
        project: cells[3].textContent,
        status: cells[4].querySelector('.badge').textContent.toLowerCase(),
        date: cells[5].textContent
    };

    showAlert(`Viewing donation: ${donationData.donorName} - $${donationData.amount}`, 'info');
    // In a real app, this would open a detailed view modal
}

function editDonation(row) {
    const cells = row.querySelectorAll('td');
    const donationData = {
        id: cells[0].textContent,
        donorName: cells[1].querySelector('span').textContent,
        amount: cells[2].textContent,
        project: cells[3].textContent,
        status: cells[4].querySelector('.badge').textContent.toLowerCase(),
        date: cells[5].textContent
    };

    showAlert(`Editing donation: ${donationData.donorName}`, 'info');
    // In a real app, this would open an edit modal with pre-filled data
}

function deleteDonation(row) {
    const donorName = row.querySelector('td:nth-child(2) span').textContent;

    if (confirm(`Are you sure you want to delete donation from: ${donorName}?`)) {
        row.remove();
        showAlert(`Donation from ${donorName} deleted successfully!`, 'success');
    }
}

// Reports navigation
function setupReportsNavigation() {
    // Handle report card clicks
    const reportCards = document.querySelectorAll('.report-card');
    reportCards.forEach(card => {
        card.addEventListener('click', function() {
            const reportType = this.getAttribute('data-report');
            showReportDetail(reportType);
        });
    });

    // Handle back to overview buttons
    const backButtons = document.querySelectorAll('.back-to-overview');
    backButtons.forEach(button => {
        button.addEventListener('click', function() {
            showReportsOverview();
        });
    });
}

function showReportDetail(reportType) {
    // Hide all report views
    const reportViews = document.querySelectorAll('.reports-view');
    reportViews.forEach(view => {
        view.classList.remove('active');
    });

    // Show the selected report detail
    const targetReport = document.getElementById(reportType + '-report');
    if (targetReport) {
        targetReport.classList.add('active');
    }
}

function showReportsOverview() {
    // Hide all report views
    const reportViews = document.querySelectorAll('.reports-view');
    reportViews.forEach(view => {
        view.classList.remove('active');
    });

    // Show the overview
    const overview = document.getElementById('reports-overview');
    if (overview) {
        overview.classList.add('active');
    }
}

function updateUrlSection(sectionId) {
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('section', sectionId);
        history.replaceState(null, '', url.toString());
    } catch (err) {
        // Fallback for older browsers
        const base = window.location.href.split('?')[0];
        window.history.replaceState(null, '', `${base}?section=${sectionId}`);
    }
}

// Reports charts initialization
function initializeReportsCharts() {
    // Monthly Donations Chart (Bar Chart)
    const monthlyDonationsChartCtx = document.getElementById('monthly-donations-chart');
    if (monthlyDonationsChartCtx) {
        new Chart(monthlyDonationsChartCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Donations ($)',
                    data: [5200, 6800, 7500, 8200, 9100, 8878],
                    backgroundColor: '#28a745'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // User Roles Distribution Chart (Pie Chart)
    const userRolesChartCtx = document.getElementById('user-roles-chart');
    if (userRolesChartCtx) {
        new Chart(userRolesChartCtx, {
            type: 'pie',
            data: {
                labels: ['Admin', 'Moderator', 'User'],
                datasets: [{
                    data: [15, 25, 60],
                    backgroundColor: [
                        '#007bff',
                        '#ffc107',
                        '#17a2b8'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }

    // Legacy charts for backward compatibility
    const donationsChartCtx = document.getElementById('donationsChart');
    if (donationsChartCtx) {
        new Chart(donationsChartCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Donations ($)',
                    data: [5000, 7000, 8000, 6000, 9000, 11000],
                    backgroundColor: '#28a745'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // User Roles Distribution Chart (Pie Chart)
    const userRolesChartCtxLegacy = document.getElementById('userRolesChart');
    if (userRolesChartCtxLegacy) {
        new Chart(userRolesChartCtxLegacy, {
            type: 'pie',
            data: {
                labels: ['Admin', 'Moderator', 'User'],
                datasets: [{
                    data: [10, 25, 65],
                    backgroundColor: [
                        '#007bff',
                        '#ffc107',
                        '#17a2b8'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }
}

// Donation type cards functionality
function setupDonationTypeCards() {
    const donationTypeCards = document.querySelectorAll('.donation-type-card');

    donationTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove active class from all cards
            donationTypeCards.forEach(c => c.classList.remove('active'));

            // Add active class to clicked card
            this.classList.add('active');

            // Get the donation type from data attribute
            const donationType = this.getAttribute('data-type');

            // Hide all donation lists
            const donationLists = document.querySelectorAll('.donation-list');
            donationLists.forEach(list => {
                list.classList.remove('active');
                list.style.display = 'none';
            });

            // Show the selected donation list
            const targetList = document.getElementById(donationType + '-donations-list');
            if (targetList) {
                targetList.classList.add('active');
                targetList.style.display = 'block';
            }
        });
    });
}

// Notification dropdown navigation
function setupNotificationNavigation() {
    const notificationItems = document.querySelectorAll('#notificationsDropdown + .dropdown-menu .dropdown-item');

    notificationItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();

            const notificationText = this.textContent.trim().toLowerCase();

            // Navigate based on notification content
            if (notificationText.includes('new user registered')) {
                showSection('users');
                // Update sidebar active state
                document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));
                document.querySelector('[data-section="users"]').parentElement.classList.add('active');
            } else if (notificationText.includes('donation received')) {
                showSection('donations');
                // Update sidebar active state
                document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));
                document.querySelector('[data-section="donations"]').parentElement.classList.add('active');
            } else if (notificationText.includes('system update available')) {
                showSection('settings');
                // Update sidebar active state
                document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));
                document.querySelector('[data-section="settings"]').parentElement.classList.add('active');
            } else if (notificationText.includes('view all notifications')) {
                // Could navigate to a notifications page, but since we don't have one, show an alert
                showAlert('Notifications page not implemented yet.', 'info');
            }
        });
    });
}

// Export functions for potential use
window.dashboardUtils = {
    showAlert,
    makeTableSortable,
    sortTable,
    setupUserFilters,
    setupUserModal,
    setupTableActions,
    addUserToTable,
    viewUser,
    editUser,
    deleteUser,
    setupDonationFilters,
    setupDonationActions,
    setupDonationTypeCards,
    viewDonation,
    editDonation,
    deleteDonation,
    initializeReportsCharts,
    setupNotificationNavigation
};
