<?php
/**
 * StudentHub Demonstration Page: Practical 13 - PHP JSON API & Fetch CRUD
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057 | Academic Year: 2026-27
 *
 * Implements asynchronous, full-page-reload-free CRUD operations powered by
 * PHP JSON API endpoints and JavaScript Fetch API with prepared MySQLi statements.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';
require_once __DIR__ . '/auth.php';

// Enforce server-side authentication
requireAuth();

$user = getCurrentUser();
$currentUserRole = (string)($user['role'] ?? 'Student');
$isAdmin = ($currentUserRole === 'Admin');
$username = htmlspecialchars($user['username'] ?? 'User', ENT_QUOTES, 'UTF-8');
$fullName = htmlspecialchars($user['full_name'] ?? 'Authorized User', ENT_QUOTES, 'UTF-8');
$csrfToken = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <title>Practical 13: Fetch API CRUD — StudentHub</title>
    
    <!-- Institutional & Practical Stylesheets -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/api-crud.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body" data-is-admin="<?php echo $isAdmin ? 'true' : 'false'; ?>">

    <!-- Top Institutional Navbar -->
    <nav class="navbar" id="apiCrudNavbar">
        <div class="nav-container">
            <div class="nav-brand" style="display:flex;align-items:center;gap:12px;">
                <div class="institutional-logos" style="display:flex;gap:8px;align-items:center;">
                    <?php if (file_exists(__DIR__ . '/logos/charusat_logo.jpeg')): ?>
                        <img src="logos/charusat_logo.jpeg" alt="CHARUSAT Logo" style="height:36px;border-radius:4px;background:#fff;padding:2px;">
                    <?php endif; ?>
                    <?php if (file_exists(__DIR__ . '/logos/cspit_logo.jpeg')): ?>
                        <img src="logos/cspit_logo.jpeg" alt="CSPIT Logo" style="height:36px;border-radius:4px;background:#fff;padding:2px;">
                    <?php endif; ?>
                </div>
                <div class="brand-text">
                    <span class="portal-tag">StudentHub</span>
                    <span class="sub-tag">Academic Information System</span>
                </div>
            </div>

            <div class="nav-user-panel" style="display:flex;align-items:center;gap:12px;">
                <div class="user-meta" style="text-align:right;">
                    <span class="greeting" style="display:block;font-size:13px;">Logged in as: <strong><?php echo $username; ?></strong></span>
                    <span class="badge <?php echo $isAdmin ? 'badge-admin' : 'badge-student'; ?>" id="navUserRoleBadge">
                        <?php echo $isAdmin ? 'ADMINISTRATOR' : 'STUDENT (READ-ONLY)'; ?>
                    </span>
                </div>

                <a href="<?php echo $isAdmin ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>" class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:6px;text-decoration:none;font-size:13px;font-weight:600;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    <span>Dashboard</span>
                </a>

                <?php if ($isAdmin): ?>
                <a href="event_management.php" class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:6px;text-decoration:none;font-size:13px;font-weight:600;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span>Events (P12)</span>
                </a>
                <?php endif; ?>

                <a href="logout.php" class="btn btn-logout" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:6px;text-decoration:none;font-size:13px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Page Header Banner -->
    <header class="api-crud-header">
        <div class="api-crud-header-content">
            <div class="api-crud-title-group">
                <span class="api-badge-tag">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    Practical 13 — ITUE203
                </span>
                <h1>
                    <span>Student Directory & Academic API</span>
                </h1>
                <p>
                    Asynchronous, zero-page-reload CRUD engine powered by PHP JSON API endpoints and JavaScript Fetch API with prepared MySQLi statements.
                </p>
            </div>

            <div class="api-header-actions" style="display:flex;gap:10px;align-items:center;">
                <a href="export.php" class="api-btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;font-size:13.5px;font-weight:600;color:#334155;" title="Download full student directory in RFC 4180 CSV format for Microsoft Excel">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    <span>Export CSV</span>
                </a>
                <?php if ($isAdmin): ?>
                    <button type="button" class="api-btn-primary" id="apiAddNewBtn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Add New Student</span>
                    </button>
                <?php else: ?>
                    <span class="status-pill status-inactive" style="background:rgba(255,255,255,0.2);color:#fff;border-color:rgba(255,255,255,0.4);" title="Student account has read-only access">
                        <span class="status-dot" style="background:#fff;"></span> Read-Only Mode
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Live Statistics Ribbon -->
    <section class="api-stats-ribbon" aria-label="Student Directory Summary Statistics">
        <div class="api-stat-card">
            <div class="api-stat-info">
                <div class="stat-label">Total Registered</div>
                <div class="stat-value" id="statTotalStudents">--</div>
            </div>
            <div class="api-stat-icon stat-icon-blue">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
        </div>

        <div class="api-stat-card">
            <div class="api-stat-info">
                <div class="stat-label">Active Enrollment</div>
                <div class="stat-value" id="statActiveStudents" style="color:var(--api-success);">--</div>
            </div>
            <div class="api-stat-icon stat-icon-green">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
        </div>

        <div class="api-stat-card">
            <div class="api-stat-info">
                <div class="stat-label">Inactive Students</div>
                <div class="stat-value" id="statInactiveStudents" style="color:var(--api-warning);">--</div>
            </div>
            <div class="api-stat-icon stat-icon-amber">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
        </div>

        <div class="api-stat-card">
            <div class="api-stat-info">
                <div class="stat-label">Filtered Results</div>
                <div class="stat-value" id="statFilteredCount" style="color:var(--api-accent);">--</div>
            </div>
            <div class="api-stat-icon stat-icon-purple">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            </div>
        </div>
    </section>

    <!-- Main Content Container -->
    <main class="api-crud-container">
        <!-- Interactive Toolbar & Filter Controls -->
        <div class="api-toolbar-card">
            <div class="api-filters-row">
                <!-- Live Search Box with Icon and Clear Button -->
                <div class="api-search-box">
                    <svg class="api-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="apiSearchInput" class="api-search-input" placeholder="Search by name, ID, email, department..." autocomplete="off">
                    <button type="button" id="apiSearchClearBtn" class="api-search-clear" title="Clear search">&times;</button>
                </div>

                <!-- Department Filter Dropdown -->
                <select id="apiDeptFilter" class="api-select-filter" aria-label="Filter by department">
                    <option value="All">All Departments</option>
                    <option value="Computer Science & Engineering">Computer Science & Engineering</option>
                    <option value="Information Technology">Information Technology</option>
                    <option value="Electronics & Communication">Electronics & Communication</option>
                    <option value="Mechanical Engineering">Mechanical Engineering</option>
                    <option value="Civil Engineering">Civil Engineering</option>
                </select>

                <!-- Status Filter Dropdown -->
                <select id="apiStatusFilter" class="api-select-filter" aria-label="Filter by enrollment status">
                    <option value="All">All Statuses</option>
                    <option value="Active">Active Only</option>
                    <option value="Inactive">Inactive Only</option>
                </select>

                <!-- Year Filter Dropdown -->
                <select id="apiYearFilter" class="api-select-filter" aria-label="Filter by academic year">
                    <option value="All">All Years</option>
                    <option value="1">Year 1 (Freshman)</option>
                    <option value="2">Year 2 (Sophomore)</option>
                    <option value="3">Year 3 (Junior)</option>
                    <option value="4">Year 4 (Senior)</option>
                </select>

                <!-- Reset Filters Button -->
                <button type="button" id="apiResetFiltersBtn" class="api-btn-reset" title="Reset all search filters">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                    <span>Reset</span>
                </button>
            </div>

            <!-- Toolbar Action Controls -->
            <div class="api-actions-row">
                <button type="button" id="apiRefreshBtn" class="api-btn-refresh" title="Refresh Table via Fetch API">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
                </button>
            </div>
        </div>

        <!-- Student Records Table Card -->
        <div class="api-table-card">
            <!-- Table Header Bar -->
            <div class="api-table-header-bar">
                <div class="api-table-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Active Student Records</span>
                </div>
                <span id="recordCountBadge" class="api-record-count-badge">Loading records...</span>
            </div>

            <!-- Table Wrapper with Loading Overlay -->
            <div class="api-table-wrapper">
                <!-- Dynamic Loading Indicator Overlay -->
                <div id="tableLoadingOverlay" class="api-loading-overlay">
                    <div class="api-spinner"></div>
                    <span style="font-size:13px;font-weight:600;color:var(--api-text-muted);">Fetching records via Fetch API...</span>
                </div>

                <!-- Responsive HTML5 Table -->
                <table class="api-table" id="studentsTable">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#ID</th>
                            <th style="width: 120px;">Student ID</th>
                            <th>Student Information</th>
                            <th>Department & Course</th>
                            <th style="width: 90px;">Year</th>
                            <th style="width: 110px;">Status</th>
                            <th style="width: 150px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        <!-- Populated dynamically via JavaScript Fetch API -->
                    </tbody>
                </table>

                <!-- Empty State Message Card -->
                <div id="tableEmptyState" class="api-empty-state" style="display:none;">
                    <svg class="api-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>
                    <div class="api-empty-title">No student records found</div>
                    <div class="api-empty-desc">
                        No database records match your active search keyword or filter criteria. Try clearing filters or create a new student record.
                    </div>
                    <?php if ($isAdmin): ?>
                    <button type="button" class="api-btn-primary" onclick="document.getElementById('apiAddNewBtn').click()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Add First Student</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Add / Edit Student Modal -->
    <div id="studentFormModal" class="api-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="studentModalTitle">
        <div class="api-modal-dialog">
            <div class="api-modal-header">
                <div class="api-modal-title-group">
                    <div class="api-modal-icon-badge">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
                    </div>
                    <h3 id="studentModalTitle" class="api-modal-title">Add New Student Record</h3>
                </div>
                <button type="button" class="api-modal-close-btn" data-dismiss="modal" aria-label="Close modal">&times;</button>
            </div>

            <form id="studentRecordForm" novalidate>
                <div class="api-modal-body">
                    <div class="api-form-grid">
                        <!-- Student ID -->
                        <div class="api-form-group">
                            <label for="inputStudentId" class="api-label">Student ID / Enrollment <span class="req">*</span></label>
                            <input type="text" id="inputStudentId" name="student_id" class="api-input" placeholder="e.g. 25CS099" required autocomplete="off" style="text-transform:uppercase; font-family:monospace; font-weight:600;">
                            <div id="error-student_id" class="api-invalid-feedback"></div>
                        </div>

                        <!-- Full Name -->
                        <div class="api-form-group">
                            <label for="inputName" class="api-label">Full Name <span class="req">*</span></label>
                            <input type="text" id="inputName" name="name" class="api-input" placeholder="e.g. Aarav Patel" required autocomplete="off">
                            <div id="error-name" class="api-invalid-feedback"></div>
                        </div>

                        <!-- Email Address -->
                        <div class="api-form-group full-span">
                            <label for="inputEmail" class="api-label">Institutional Email Address <span class="req">*</span></label>
                            <input type="email" id="inputEmail" name="email" class="api-input" placeholder="e.g. aarav.patel25@charusat.edu.in" required autocomplete="off">
                            <div id="error-email" class="api-invalid-feedback"></div>
                        </div>

                        <!-- Department -->
                        <div class="api-form-group">
                            <label for="inputDepartment" class="api-label">Department <span class="req">*</span></label>
                            <select id="inputDepartment" name="department" class="api-select" required>
                                <option value="" disabled selected>Select Department</option>
                                <option value="Computer Science & Engineering">Computer Science & Engineering</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Electronics & Communication">Electronics & Communication</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                            </select>
                            <div id="error-department" class="api-invalid-feedback"></div>
                        </div>

                        <!-- Degree Course -->
                        <div class="api-form-group">
                            <label for="inputCourse" class="api-label">Degree Course / Program <span class="req">*</span></label>
                            <select id="inputCourse" name="course" class="api-select" required>
                                <option value="" disabled selected>Select Course Program</option>
                                <option value="B.Tech Computer Science & Engineering">B.Tech Computer Science & Engineering</option>
                                <option value="B.Tech Information Technology">B.Tech Information Technology</option>
                                <option value="B.Tech Electronics & Communication">B.Tech Electronics & Communication</option>
                                <option value="B.Tech Mechanical Engineering">B.Tech Mechanical Engineering</option>
                                <option value="B.Tech Civil Engineering">B.Tech Civil Engineering</option>
                            </select>
                            <div id="error-course" class="api-invalid-feedback"></div>
                        </div>

                        <!-- Academic Year -->
                        <div class="api-form-group">
                            <label for="inputYear" class="api-label">Academic Year <span class="req">*</span></label>
                            <select id="inputYear" name="year" class="api-select" required>
                                <option value="" disabled selected>Select Year</option>
                                <option value="1">1st Year (Freshman)</option>
                                <option value="2">2nd Year (Sophomore)</option>
                                <option value="3">3rd Year (Junior)</option>
                                <option value="4">4th Year (Senior)</option>
                            </select>
                            <div id="error-year" class="api-invalid-feedback"></div>
                        </div>

                        <!-- Status -->
                        <div class="api-form-group">
                            <label for="inputStatus" class="api-label">Enrollment Status <span class="req">*</span></label>
                            <select id="inputStatus" name="status" class="api-select" required>
                                <option value="Active" selected>Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                            <div id="error-status" class="api-invalid-feedback"></div>
                        </div>
                    </div>
                </div>

                <div class="api-modal-footer">
                    <button type="button" class="api-btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" id="studentFormSaveBtn" class="api-btn-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Save Student Record</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="api-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
        <div class="api-modal-dialog" style="max-width: 480px;">
            <div class="api-modal-header">
                <div class="api-modal-title-group">
                    <div class="api-modal-icon-badge danger">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </div>
                    <h3 id="deleteModalTitle" class="api-modal-title">Confirm Deletion</h3>
                </div>
                <button type="button" class="api-modal-close-btn" data-dismiss="modal" aria-label="Close modal">&times;</button>
            </div>

            <div class="api-modal-body">
                <div class="api-delete-warning-box">
                    <div class="api-delete-warning-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    </div>
                    <div class="api-delete-warning-text">
                        <h4>Destructive Operation</h4>
                        <p>Are you sure you want to permanently delete this student record? This action will remove the record from MySQL and write a security audit log entry.</p>
                    </div>
                </div>

                <div class="api-record-preview-box">
                    <div class="api-preview-row">
                        <span class="api-preview-label">Student ID:</span>
                        <span class="api-preview-value" id="deletePreviewId">--</span>
                    </div>
                    <div class="api-preview-row">
                        <span class="api-preview-label">Full Name:</span>
                        <span class="api-preview-value" id="deletePreviewName">--</span>
                    </div>
                    <div class="api-preview-row">
                        <span class="api-preview-label">Department:</span>
                        <span class="api-preview-value" id="deletePreviewDept">--</span>
                    </div>
                </div>
            </div>

            <div class="api-modal-footer">
                <button type="button" class="api-btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="api-btn-danger">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    <span>Confirm Delete</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Floating Toast Notifications Container -->
    <div id="apiToastContainer" class="api-toast-container" aria-live="polite"></div>

    <!-- Institutional Footer -->
    <footer style="text-align:center;padding:24px 0;color:#64748b;font-size:12px;border-top:1px solid #e2e8f0;background:#ffffff;margin-top:auto;">
        <div style="max-width:1240px;margin:0 auto;padding:0 24px;">
            <p style="margin:0 0 4px 0;"><strong>StudentHub</strong> — Web Development Frameworks (ITUE203) Practical 13</p>
            <p style="margin:0;">Charotar University of Science and Technology (CHARUSAT) | CSPIT - Computer Science & Engineering | Student ID: <strong>25CS057</strong></p>
        </div>
    </footer>

    <!-- JavaScript Fetch API Client -->
    <script src="assets/js/api-crud.js"></script>
</body>
</html>
