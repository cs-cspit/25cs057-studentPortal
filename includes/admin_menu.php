<?php
/**
 * StudentHub Dynamic Role-Based Navigation & Menu System
 * Practical 14: Admin Dashboard with RBAC, Dynamic Menu, and Audit Log
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057
 */

if (!function_exists('getNavigationMenu')) {
    /**
     * Returns the authorized navigation menu items for a specific role.
     * Only displays menu options that the role is permitted to access.
     *
     * @param string $role The authenticated user's role ('Admin' or 'Student')
     * @return array List of authorized menu items with key, label, url, icon, and badge
     */
    function getNavigationMenu(string $role): array {
        $normalizedRole = ucfirst(strtolower(trim($role)));

        if ($normalizedRole === 'Admin') {
            return [
                [
                    'key'   => 'dashboard',
                    'label' => 'Dashboard',
                    'url'   => 'admin_dashboard.php',
                    'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
                    'badge' => null
                ],
                [
                    'key'   => 'students',
                    'label' => 'Student Management',
                    'url'   => 'api_crud.php',
                    'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
                    'badge' => 'CRUD'
                ],
                [
                    'key'   => 'events',
                    'label' => 'Event Management',
                    'url'   => 'event_management.php',
                    'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                    'badge' => null
                ],
                [
                    'key'   => 'registrations',
                    'label' => 'Registrations',
                    'url'   => 'admin_dashboard.php#registrationsSection',
                    'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
                    'badge' => 'Live'
                ],
                [
                    'key'   => 'users',
                    'label' => 'User Management',
                    'url'   => 'user_management.php',
                    'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>',
                    'badge' => 'RBAC'
                ],
                [
                    'key'   => 'audit_logs',
                    'label' => 'Audit Logs',
                    'url'   => 'audit_logs.php',
                    'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
                    'badge' => 'Audit'
                ]
            ];
        }

        // Student Role Navigation Menu (Admin links strictly prohibited)
        return [
            [
                'key'   => 'student_dashboard',
                'label' => 'Student Dashboard',
                'url'   => 'student_dashboard.php',
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
                'badge' => null
            ],
            [
                'key'   => 'profile',
                'label' => 'My Academic Profile',
                'url'   => 'student_dashboard.php#profileSection',
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
                'badge' => null
            ],
            [
                'key'   => 'attendance',
                'label' => 'Attendance Tracker',
                'url'   => 'student_dashboard.php#attendanceSection',
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>',
                'badge' => '92%'
            ],
            [
                'key'   => 'results',
                'label' => 'Grades & Results',
                'url'   => 'student_dashboard.php#resultsSection',
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>',
                'badge' => '9.15'
            ],
            [
                'key'   => 'timetable',
                'label' => 'Class Timetable',
                'url'   => 'student_dashboard.php#timetableSection',
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                'badge' => null
            ],
            [
                'key'   => 'registrations',
                'label' => 'My Registrations',
                'url'   => 'student_dashboard.php#registrationsSection',
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
                'badge' => null
            ],
            [
                'key'   => 'help',
                'label' => 'Helpdesk & FAQs',
                'url'   => 'help.php',
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
                'badge' => 'JSON'
            ]
        ];
    }
}

if (!function_exists('renderDynamicSidebar')) {
    /**
     * Renders the dynamic responsive sidebar navigation according to the user's role.
     *
     * @param string $activeKey The active menu key to highlight
     * @param array|null $user The user session data
     */
    function renderDynamicSidebar(string $activeKey, ?array $user = null): void {
        $role = $user['role'] ?? ($_SESSION['role'] ?? 'Student');
        $username = $user['username'] ?? ($_SESSION['username'] ?? 'User');
        $fullName = $user['full_name'] ?? ($_SESSION['full_name'] ?? $username);
        $roleClass = strtolower($role);
        $menuItems = getNavigationMenu($role);
        ?>
        <aside class="app-sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <a href="<?php echo ($role === 'Admin') ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>" class="brand-link">
                    <div class="brand-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <div class="brand-text">
                        <span class="brand-title">StudentHub</span>
                        <span class="brand-subtitle"><?php echo ($role === 'Admin') ? 'Admin Portal' : 'Student Portal'; ?></span>
                    </div>
                </a>
                <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close Sidebar">&times;</button>
            </div>

            <!-- Role Indicator Banner -->
            <div class="sidebar-user-card">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                </div>
                <div class="user-info">
                    <span class="user-name" title="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="role-pill role-pill-<?php echo $roleClass; ?>">
                        <span class="role-dot"></span>
                        <?php echo strtoupper($role); ?>
                    </span>
                </div>
            </div>

            <!-- Dynamic Role-Based Navigation Links -->
            <nav class="sidebar-nav">
                <div class="nav-section-title">Navigation (<?php echo strtoupper($role); ?>)</div>
                <ul class="nav-list">
                    <?php foreach ($menuItems as $item): 
                        $isActive = ($activeKey === $item['key']);
                    ?>
                        <li class="nav-item">
                            <a href="<?php echo $item['url']; ?>" class="nav-link <?php echo $isActive ? 'active' : ''; ?>">
                                <span class="nav-icon"><?php echo $item['icon']; ?></span>
                                <span class="nav-label"><?php echo htmlspecialchars($item['label']); ?></span>
                                <?php if (!empty($item['badge'])): ?>
                                    <span class="nav-badge"><?php echo htmlspecialchars($item['badge']); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="nav-section-title">Account</div>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link nav-link-logout">
                            <span class="nav-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                            </span>
                            <span class="nav-label">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <div class="footer-meta">
                    <span>CSPIT &bull; CSE Department</span>
                    <span class="version-tag">Practical 14 RBAC</span>
                </div>
            </div>
        </aside>
        <?php
    }
}

if (!function_exists('renderDynamicTopHeader')) {
    /**
     * Renders the top institutional header with page title, current date, user profile, and mobile drawer button.
     *
     * @param string $pageTitle The current page title
     * @param array|null $user The user session data
     */
    function renderDynamicTopHeader(string $pageTitle, ?array $user = null): void {
        $role = $user['role'] ?? ($_SESSION['role'] ?? 'Student');
        $username = $user['username'] ?? ($_SESSION['username'] ?? 'User');
        $fullName = $user['full_name'] ?? ($_SESSION['full_name'] ?? $username);
        $roleClass = strtolower($role);
        $currentDate = date('l, d F Y');
        ?>
        <header class="dashboard-header">
            <div class="header-left">
                <button type="button" class="btn-sidebar-toggle" id="sidebarToggleBtn" aria-label="Toggle navigation">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <div class="header-titles">
                    <h1 class="header-page-title"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <span class="header-date">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <?php echo $currentDate; ?>
                    </span>
                </div>
            </div>

            <div class="header-right">
                <div class="user-profile-badge">
                    <div class="profile-meta">
                        <span class="profile-greeting">Welcome, <strong><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                        <span class="badge badge-<?php echo $roleClass; ?>"><?php echo strtoupper($role); ?></span>
                    </div>
                    <a href="logout.php" class="btn btn-logout-top" title="Sign out securely">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </header>
        <?php
    }
}

