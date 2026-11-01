<?php
/**
 * StudentHub Campus Event Management Console
 * Practical 12: Campus Event Management with Validated Poster Upload and Role-Based Access
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057 | Academic Year: 2026-27
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/event_helpers.php';

// 1. Authoritative Server-Side Authentication & Role-Based Authorization
requireAuth();
requireRole('Admin');

$user = getCurrentUser();
$adminUsername = htmlspecialchars($user['username'] ?? 'admin_user', ENT_QUOTES, 'UTF-8');
$csrfToken = get_csrf_token();
$flashMessages = get_flashes();

// 2. Fetch Events and Metrics from Database
$conn = getDBConnection();

$events = [];
$totalEvents = 0;
$withPosters = 0;
$totalCapacity = 0;

$query = "SELECT id, title, category, status, description, poster_path, event_date, venue, capacity, created_at, updated_at 
          FROM events 
          ORDER BY id DESC";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
        $totalEvents++;
        if (!empty($row['poster_path'])) {
            $withPosters++;
        }
        $totalCapacity += (int)$row['capacity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Event Management Console — StudentHub</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/event_management.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="event-management-body admin-theme">
    <!-- Top Institutional Navbar -->
    <nav class="navbar navbar-admin" id="adminNavbar">
        <div class="nav-container">
            <div class="nav-brand">
                <div class="brand-text">
                    <span class="portal-tag">StudentHub</span>
                    <span class="sub-tag">Campus Event Management Portal</span>
                </div>
            </div>
            <div class="nav-user-panel">
                <a href="admin_dashboard.php" class="nav-link-btn nav-link-back" id="btnBackToDashboard">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    <span>Admin Portal</span>
                </a>
                <div class="user-meta">
                    <span class="greeting">Welcome, <strong><?php echo $adminUsername; ?></strong></span>
                    <span class="badge badge-admin" id="roleBadge">ADMIN</span>
                </div>
                <a href="logout.php" class="btn btn-logout" id="logoutBtn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="event-container">
        <!-- Flash Alerts Wrapper -->
        <div class="alerts-wrapper" id="alertsWrapper">
            <?php foreach ($flashMessages as $flash): ?>
                <div class="alert alert-<?php echo ($flash['type'] === 'success') ? 'success' : 'error'; ?>">
                    <div class="alert-content">
                        <?php if ($flash['type'] === 'success'): ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        <?php else: ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <button type="button" class="alert-close-btn" onclick="this.parentElement.remove()">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Header Action Card -->
        <header class="event-header">
            <div class="header-title-group">
                <h1>
                    <span>Campus Event Management Console</span>
                    <span class="header-badge">Practical 12</span>
                </h1>
                <p>Create, update, and manage university campus events with verified poster upload and authoritative role authorization.</p>
            </div>
            <button type="button" class="btn-create-event" id="btnOpenCreateModal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Create New Event</span>
            </button>
        </header>

        <!-- KPI Summary Cards -->
        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon kpi-blue">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <div class="kpi-meta">
                    <span class="kpi-label">Total Campus Events</span>
                    <span class="kpi-value" id="kpiTotalEvents"><?php echo $totalEvents; ?></span>
                    <span class="kpi-sub">Registered university activities</span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon kpi-green">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                </div>
                <div class="kpi-meta">
                    <span class="kpi-label">With Uploaded Posters</span>
                    <span class="kpi-value" id="kpiEventsWithPosters"><?php echo $withPosters; ?></span>
                    <span class="kpi-sub">Verified image storage</span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon kpi-purple">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div class="kpi-meta">
                    <span class="kpi-label">Combined Capacity</span>
                    <span class="kpi-value" id="kpiCombinedCapacity"><?php echo number_format($totalCapacity); ?></span>
                    <span class="kpi-sub">Total student seats available</span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon kpi-amber">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <div class="kpi-meta">
                    <span class="kpi-label">Upload Security Guard</span>
                    <span class="kpi-value" style="font-size:17px;margin:5px 0;">2 MB Limit</span>
                    <span class="kpi-sub">JPG, PNG, WEBP (finfo Checked)</span>
                </div>
            </div>
        </section>

        <!-- Events List Header -->
        <div class="events-section-header">
            <h2 class="events-section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                <span>Campus Events Catalog (<?php echo $totalEvents; ?> Events)</span>
            </h2>
        </div>

        <!-- Event Cards Grid -->
        <section class="events-grid" id="eventsGrid">
            <?php if (empty($events)): ?>
                <div style="grid-column:1/-1;text-align:center;padding:60px;background:#fff;border-radius:12px;border:1px solid #e2e8f0;">
                    <p style="color:#64748b;font-size:16px;">No campus events registered yet. Click <strong>Create New Event</strong> to add your first event.</p>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): 
                    $safeId          = (int)$event['id'];
                    $safeTitle       = htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8');
                    $safeCategory    = htmlspecialchars($event['category'], ENT_QUOTES, 'UTF-8');
                    $safeStatus      = htmlspecialchars($event['status'], ENT_QUOTES, 'UTF-8');
                    $safeDescription = htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8');
                    $safeDate        = htmlspecialchars($event['event_date'], ENT_QUOTES, 'UTF-8');
                    $safeVenue       = htmlspecialchars($event['venue'], ENT_QUOTES, 'UTF-8');
                    $safeCapacity    = (int)$event['capacity'];
                    $posterPath      = $event['poster_path'];
                    $hasPoster       = !empty($posterPath) && file_exists(__DIR__ . '/' . $posterPath);
                    $displayPoster   = $hasPoster ? htmlspecialchars($posterPath, ENT_QUOTES, 'UTF-8') : '';
                    $statusClass     = strtolower($safeStatus);
                ?>
                    <article class="event-card" id="eventCard-<?php echo $safeId; ?>">
                        <!-- Poster Media -->
                        <div class="event-card-media">
                            <?php if ($hasPoster): ?>
                                <img src="<?php echo $displayPoster; ?>" alt="<?php echo $safeTitle; ?> Poster" class="event-card-poster" loading="lazy">
                            <?php else: ?>
                                <div class="event-poster-placeholder">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                    <span>No Poster Uploaded</span>
                                </div>
                            <?php endif; ?>

                            <!-- Badges Overlay -->
                            <div class="card-badges-top">
                                <span class="badge-category"><?php echo $safeCategory; ?></span>
                                <span class="badge-status badge-<?php echo $statusClass; ?>"><?php echo $safeStatus; ?></span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="event-card-body">
                            <span class="event-card-id-tag">EVENT #<?php echo $safeId; ?></span>
                            <h3 class="event-card-title"><?php echo $safeTitle; ?></h3>
                            <p class="event-card-description"><?php echo $safeDescription; ?></p>

                            <!-- Meta Details -->
                            <div class="event-meta-list">
                                <div class="event-meta-item">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <span><strong>Date:</strong> <?php echo date('M d, Y', strtotime($safeDate)); ?></span>
                                </div>
                                <div class="event-meta-item">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    <span><strong>Venue:</strong> <?php echo $safeVenue; ?></span>
                                </div>
                                <div class="event-meta-item">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    <span><strong>Capacity:</strong> <?php echo number_format($safeCapacity); ?> Students</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Actions Footer -->
                        <div class="event-card-actions">
                            <?php if ($hasPoster): ?>
                                <button type="button" class="btn-card btn-view-poster" 
                                        data-poster="<?php echo $displayPoster; ?>"
                                        data-title="<?php echo $safeTitle; ?>"
                                        data-category="<?php echo $safeCategory; ?>"
                                        data-date="<?php echo date('M d, Y', strtotime($safeDate)); ?>"
                                        data-venue="<?php echo $safeVenue; ?>"
                                        id="btnViewPoster-<?php echo $safeId; ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    <span>View Poster</span>
                                </button>
                            <?php endif; ?>

                            <button type="button" class="btn-card btn-edit-event" 
                                    data-id="<?php echo $safeId; ?>"
                                    data-title="<?php echo $safeTitle; ?>"
                                    data-category="<?php echo $safeCategory; ?>"
                                    data-status="<?php echo $safeStatus; ?>"
                                    data-date="<?php echo $safeDate; ?>"
                                    data-capacity="<?php echo $safeCapacity; ?>"
                                    data-venue="<?php echo $safeVenue; ?>"
                                    data-description="<?php echo $safeDescription; ?>"
                                    data-poster="<?php echo $displayPoster; ?>"
                                    id="btnEditEvent-<?php echo $safeId; ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                <span>Edit</span>
                            </button>

                            <button type="button" class="btn-card btn-delete-event" 
                                    data-id="<?php echo $safeId; ?>"
                                    data-title="<?php echo $safeTitle; ?>"
                                    id="btnDeleteEvent-<?php echo $safeId; ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                <span>Delete</span>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>

    <!-- =======================================================================
         1. Create New Event Modal
         ======================================================================= -->
    <div class="modal-overlay" id="createEventModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span>Create New Campus Event</span>
                </h3>
                <button type="button" class="modal-close-btn" data-close-modal>&times;</button>
            </div>

            <form action="event_create.php" method="POST" enctype="multipart/form-data" id="createEventForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group-full">
                            <label class="form-label" for="createTitle">Event Title <span class="required">*</span></label>
                            <input type="text" class="form-control" id="createTitle" name="title" required placeholder="e.g. AI Summit 2026: National Student Innovation Expo" maxlength="150">
                        </div>

                        <div>
                            <label class="form-label" for="createCategory">Category <span class="required">*</span></label>
                            <select class="form-control" id="createCategory" name="category" required>
                                <option value="">Select Category...</option>
                                <option value="Coding Competition">Coding Competition</option>
                                <option value="Technical Workshop">Technical Workshop</option>
                                <option value="Industry Seminar">Industry Seminar</option>
                                <option value="Cultural Festival">Cultural Festival</option>
                                <option value="Security Workshop">Security Workshop</option>
                                <option value="Technical Summit">Technical Summit</option>
                                <option value="Hands-on Lab">Hands-on Lab</option>
                                <option value="Webinar">Webinar</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="createStatus">Status <span class="required">*</span></label>
                            <select class="form-control" id="createStatus" name="status" required>
                                <option value="Upcoming" selected>Upcoming</option>
                                <option value="Active">Active</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="createDate">Event Date <span class="required">*</span></label>
                            <input type="date" class="form-control" id="createDate" name="event_date" required value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
                        </div>

                        <div>
                            <label class="form-label" for="createCapacity">Capacity (Students) <span class="required">*</span></label>
                            <input type="number" class="form-control" id="createCapacity" name="capacity" required min="1" max="50000" placeholder="e.g. 200">
                        </div>

                        <div class="form-group-full">
                            <label class="form-label" for="createVenue">Venue <span class="required">*</span></label>
                            <input type="text" class="form-control" id="createVenue" name="venue" required placeholder="e.g. Dr. APJ Abdul Kalam Auditorium" maxlength="150">
                        </div>

                        <div class="form-group-full">
                            <label class="form-label" for="createDescription">Event Description <span class="required">*</span></label>
                            <textarea class="form-control" id="createDescription" name="description" required rows="3" placeholder="Describe the objectives, highlights, and participant criteria..."></textarea>
                        </div>

                        <!-- Poster Upload Section with Drag & Drop and Instant Validation -->
                        <div class="form-group-full">
                            <label class="form-label">Event Poster Image (Max 2 MB, JPG/PNG/WEBP)</label>
                            <div class="upload-dropzone" id="createDropzone">
                                <div class="dropzone-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                </div>
                                <p class="dropzone-prompt">Drag and drop your poster here, or <span style="color:#2563eb;text-decoration:underline;">Browse</span></p>
                                <p class="dropzone-hint">Supports JPG, JPEG, PNG, WEBP (Strict 2 MB Limit, Server MIME Verified)</p>
                                <input type="file" id="createPosterInput" name="poster" class="file-input-hidden" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            </div>

                            <!-- Live Feedback in Dialog -->
                            <div class="dialog-validation-feedback" id="createFeedback"></div>

                            <!-- Live Thumbnail Preview Box -->
                            <div class="poster-preview-box" id="createPreviewBox"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitCreateEvent" style="background:#2563eb;color:#fff;">Create Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =======================================================================
         2. Edit Event Modal
         ======================================================================= -->
    <div class="modal-overlay" id="editEventModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    <span>Edit Campus Event <span id="editModalEventIdDisplay"></span></span>
                </h3>
                <button type="button" class="modal-close-btn" data-close-modal>&times;</button>
            </div>

            <form action="event_edit.php" method="POST" enctype="multipart/form-data" id="editEventForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="id" id="editEventId" value="">
                
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group-full">
                            <label class="form-label" for="editTitle">Event Title <span class="required">*</span></label>
                            <input type="text" class="form-control" id="editTitle" name="title" required maxlength="150">
                        </div>

                        <div>
                            <label class="form-label" for="editCategory">Category <span class="required">*</span></label>
                            <select class="form-control" id="editCategory" name="category" required>
                                <option value="Coding Competition">Coding Competition</option>
                                <option value="Technical Workshop">Technical Workshop</option>
                                <option value="Industry Seminar">Industry Seminar</option>
                                <option value="Cultural Festival">Cultural Festival</option>
                                <option value="Security Workshop">Security Workshop</option>
                                <option value="Technical Summit">Technical Summit</option>
                                <option value="Hands-on Lab">Hands-on Lab</option>
                                <option value="Webinar">Webinar</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="editStatus">Status <span class="required">*</span></label>
                            <select class="form-control" id="editStatus" name="status" required>
                                <option value="Upcoming">Upcoming</option>
                                <option value="Active">Active</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label" for="editDate">Event Date <span class="required">*</span></label>
                            <input type="date" class="form-control" id="editDate" name="event_date" required>
                        </div>

                        <div>
                            <label class="form-label" for="editCapacity">Capacity (Students) <span class="required">*</span></label>
                            <input type="number" class="form-control" id="editCapacity" name="capacity" required min="1" max="50000">
                        </div>

                        <div class="form-group-full">
                            <label class="form-label" for="editVenue">Venue <span class="required">*</span></label>
                            <input type="text" class="form-control" id="editVenue" name="venue" required maxlength="150">
                        </div>

                        <div class="form-group-full">
                            <label class="form-label" for="editDescription">Event Description <span class="required">*</span></label>
                            <textarea class="form-control" id="editDescription" name="description" required rows="3"></textarea>
                        </div>

                        <div class="form-group-full">
                            <label class="form-label">Replace Poster (Optional - Leave blank to keep current poster)</label>
                            <div class="upload-dropzone" id="editDropzone">
                                <div class="dropzone-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                </div>
                                <p class="dropzone-prompt">Choose a new file to replace current poster</p>
                                <p class="dropzone-hint">Supports JPG, JPEG, PNG, WEBP (Max 2 MB)</p>
                                <input type="file" id="editPosterInput" name="poster" class="file-input-hidden" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            </div>

                            <div class="dialog-validation-feedback" id="editFeedback"></div>
                            <div class="poster-preview-box" id="editPreviewBox"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitEditEvent" style="background:#2563eb;color:#fff;">Update Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =======================================================================
         3. View Poster Lightbox Modal
         ======================================================================= -->
    <div class="modal-overlay" id="viewPosterModal">
        <div class="modal-card modal-card-large">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="viewPosterTitle">Event Poster</h3>
                    <p style="font-size:12.5px;color:#64748b;margin:3px 0 0;" id="viewPosterMeta"></p>
                </div>
                <button type="button" class="modal-close-btn" data-close-modal>&times;</button>
            </div>
            <div class="modal-body" style="text-align:center;background:#0f172a;">
                <img src="" alt="Event Poster" id="viewPosterImage" class="poster-full-preview">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Close</button>
            </div>
        </div>
    </div>

    <!-- =======================================================================
         4. Delete Event Confirmation Modal
         ======================================================================= -->
    <div class="modal-overlay" id="deleteConfirmModal">
        <div class="modal-card modal-card-small">
            <div class="modal-header">
                <h3 class="modal-title" style="color:#ef4444;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <span>Confirm Event Deletion</span>
                </h3>
                <button type="button" class="modal-close-btn" data-close-modal>&times;</button>
            </div>

            <form action="event_delete.php" method="POST" id="deleteEventForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="id" id="deleteEventId" value="">

                <div class="modal-body">
                    <p style="font-size:14px;color:#334155;line-height:1.5;margin:0 0 12px;">
                        Are you sure you want to permanently delete <strong id="deleteEventTitleSpan">this event</strong>?
                    </p>
                    <p style="font-size:12.5px;color:#991b1b;background:#fee2e2;padding:10px 14px;border-radius:6px;margin:0;">
                        <strong>Warning:</strong> This operation will remove the event record from the database and safely delete its stored poster from storage. This action cannot be undone.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmDelete" style="background:#ef4444;color:#fff;">Yes, Delete Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Client-Side Scripts -->
    <script src="assets/js/event_management.js"></script>
</body>
</html>
