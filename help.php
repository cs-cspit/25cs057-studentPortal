<?php
/**
 * StudentHub — Helpdesk, Academic Guidance & Interactive FAQs
 * Practical 6 & Practical 15: Rendering External JSON Data with Fetch API,
 * Live Search & Filter, and Institutional Support Guidelines
 * 
 * Student: Krupal Parmar | Student ID: 25CS057
 * Institute: Chandubhai S. Patel Institute of Technology (CSPIT)
 * University: Charotar University of Science and Technology (CHARUSAT)
 * Subject: Web Development Frameworks (ITUE203)
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';
require_once __DIR__ . '/auth.php';

$currentUser = isLoggedIn() ? getCurrentUser() : null;
$userRole    = $currentUser['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk &amp; FAQs — StudentHub | CHARUSAT CSPIT</title>
    
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        .help-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 70%, #2563eb 100%);
            color: #ffffff;
            padding: 50px 24px 60px;
            text-align: center;
        }
        .help-container {
            max-width: 1050px;
            margin: 0 auto;
            padding: 40px 20px 80px;
        }
        .search-filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px 24px;
            margin-top: -30px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            justify-content: space-between;
        }
        .search-input-wrap {
            flex: 1;
            min-width: 260px;
            position: relative;
        }
        .search-input-wrap input {
            width: 100%;
            padding: 11px 16px 11px 40px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            font-family: inherit;
        }
        .search-input-wrap input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
        .search-icon-svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .category-filter-select {
            padding: 11px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            background: #ffffff;
            color: #334155;
            cursor: pointer;
            font-family: inherit;
        }
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 32px;
        }
        .faq-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .faq-card:hover {
            border-color: #93c5fa;
            box-shadow: 0 8px 20px -4px rgba(15, 23, 42, 0.08);
            transform: translateY(-2px);
        }
        .faq-category {
            font-size: 11px;
            font-weight: 700;
            color: #2563eb;
            background: #eff6ff;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 10px;
            align-self: flex-start;
        }
        .faq-q {
            font-size: 15.5px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 10px;
            line-height: 1.35;
        }
        .faq-a {
            font-size: 13.5px;
            color: #475569;
            line-height: 1.6;
            margin: 0;
        }

        /* Nav */
        .site-nav {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .site-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .site-brand-block {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }
        .logo-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logo-group img {
            height: 38px;
            border-radius: 4px;
            object-fit: contain;
        }
        .brand-name {
            font-size: 19px;
            font-weight: 800;
            color: #1e3a8a;
            line-height: 1.1;
        }
        .brand-sub {
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .nav-links a {
            color: #334155;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .btn-nav-login {
            background: #f1f5f9;
            color: #1e293b;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
        }
        .btn-nav-register {
            background: #2563eb;
            color: #ffffff;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>
<body style="margin:0;font-family:'Inter',sans-serif;background:#f8fafc;color:#0f172a;">

    <!-- Top Site Navigation -->
    <header class="site-nav">
        <div class="site-nav-inner">
            <a href="index.php" class="site-brand-block">
                <div class="logo-group">
                    <?php if (file_exists(__DIR__ . '/logos/charusat_logo.jpeg')): ?>
                        <img src="logos/charusat_logo.jpeg" alt="CHARUSAT Logo">
                    <?php endif; ?>
                    <?php if (file_exists(__DIR__ . '/logos/cspit_logo.jpeg')): ?>
                        <img src="logos/cspit_logo.jpeg" alt="CSPIT Logo">
                    <?php endif; ?>
                </div>
                <div class="brand-text-block">
                    <span class="brand-name">StudentHub</span>
                    <span class="brand-sub">CHARUSAT &bull; CSPIT</span>
                </div>
            </a>

            <nav>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="index.php#features">Features</a></li>
                    <li><a href="index.php#events">Campus Events</a></li>
                    <li><a href="help.php" style="color:#2563eb;font-weight:600;">Helpdesk &amp; FAQs</a></li>
                </ul>
            </nav>

            <div style="display:flex;gap:10px;">
                <?php if ($currentUser): ?>
                    <a href="<?php echo ($userRole === 'Admin') ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>" class="btn-nav-register" style="background:#1e3a8a;">
                        Dashboard (<?php echo htmlspecialchars($userRole); ?>)
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-nav-login">Sign In</a>
                    <a href="register.php" class="btn-nav-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Header Section -->
    <section class="help-header">
        <div style="max-width:800px;margin:0 auto;">
            <span style="display:inline-block;background:rgba(255,255,255,0.12);padding:4px 14px;border-radius:9999px;font-size:12px;font-weight:600;margin-bottom:12px;text-transform:uppercase;">
                Practical 6 &bull; Practical 15 Support
            </span>
            <h1 style="font-size:32px;font-weight:800;margin:0 0 10px;">Helpdesk &amp; Knowledge Base</h1>
            <p style="margin:0;color:#cbd5e1;font-size:15.5px;line-height:1.6;">
                Rendered dynamically using the JavaScript Fetch API from external JSON resources with live search and category filtering.
            </p>
        </div>
    </section>

    <!-- Main Content Area -->
    <main class="help-container">

        <!-- Search and Filter Controller -->
        <div class="search-filter-card">
            <div class="search-input-wrap">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon-svg">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="faqSearchInput" placeholder="Search questions or keywords (e.g., fetch, login, sessions)..." autocomplete="off">
            </div>

            <select id="faqCategoryFilter" class="category-filter-select">
                <option value="ALL">All Categories</option>
            </select>
        </div>

        <!-- Dynamic FAQ Results Container -->
        <div id="faqLoading" style="text-align:center;padding:40px;color:#64748b;font-size:14px;">
            Loading FAQ knowledge base from data/faqs.json via Fetch API...
        </div>

        <div class="faq-grid" id="faqContainer" style="display:none;"></div>

        <!-- Academic Guidelines Section (Practical 2 Helpdesk) -->
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:32px;margin-top:40px;">
            <h3 style="margin:0 0 16px;color:#1e3a8a;font-size:19px;font-weight:700;">Institutional Support Guidelines</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:20px;font-size:13.5px;color:#475569;line-height:1.6;">
                <div>
                    <h4 style="color:#0f172a;margin:0 0 6px;">Academic Attendance</h4>
                    <p style="margin:0;">Students must maintain a minimum of 80% aggregate attendance across all registered laboratory and lecture courses.</p>
                </div>
                <div>
                    <h4 style="color:#0f172a;margin:0 0 6px;">Portal Credentials</h4>
                    <p style="margin:0;">Use your CHARUSAT institutional email address and enrollment number (e.g., 25CS057) to access the student portal.</p>
                </div>
                <div>
                    <h4 style="color:#0f172a;margin:0 0 6px;">Session Security</h4>
                    <p style="margin:0;">Sessions automatically terminate after 15 minutes of inactivity. Remember-Me tokens are valid for 30 days on personal devices.</p>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer style="background:#0f172a;color:#94a3b8;padding:32px 24px;text-align:center;font-size:13px;border-top:1px solid #1e293b;">
        <p style="margin:0 0 8px;">
            &copy; <?php echo date('Y'); ?> StudentHub Portal &bull; CHARUSAT / CSPIT Computer Science &amp; Engineering.
        </p>
        <p style="margin:0;color:#64748b;">
            Academic Project by <strong>Krupal Parmar (25CS057)</strong> &bull; Subject: Web Development Frameworks (ITUE203)
        </p>
    </footer>

    <!-- Fetch API Script for Rendering data/faqs.json (Practical 6) -->
    <script>
        let allFaqs = [];

        async function loadFaqs() {
            try {
                const response = await fetch('data/faqs.json');
                if (!response.ok) throw new Error('Network response was not ok');
                allFaqs = await response.json();

                // Populate category options
                const categories = ['ALL', ...new Set(allFaqs.map(f => f.category || 'General'))];
                const select = document.getElementById('faqCategoryFilter');
                select.innerHTML = categories.map(cat => `<option value="${cat}">${cat === 'ALL' ? 'All Categories' : cat}</option>`).join('');

                renderFaqs(allFaqs);
                document.getElementById('faqLoading').style.display = 'none';
                document.getElementById('faqContainer').style.display = 'grid';
            } catch (err) {
                document.getElementById('faqLoading').innerHTML = '<span style="color:#ef4444;">Failed to load FAQ data: ' + err.message + '</span>';
            }
        }

        function renderFaqs(items) {
            const container = document.getElementById('faqContainer');
            if (!items.length) {
                container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#64748b;">No matching FAQs found for your search query.</div>';
                return;
            }

            container.innerHTML = items.map(faq => `
                <div class="faq-card">
                    <span class="faq-category">${escapeHtml(faq.category || 'General')}</span>
                    <h3 class="faq-q">${escapeHtml(faq.question)}</h3>
                    <p class="faq-a">${escapeHtml(faq.answer)}</p>
                </div>
            `).join('');
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function filterFaqs() {
            const query = document.getElementById('faqSearchInput').value.toLowerCase().trim();
            const category = document.getElementById('faqCategoryFilter').value;

            const filtered = allFaqs.filter(faq => {
                const matchCat = (category === 'ALL' || (faq.category || 'General') === category);
                const matchQuery = !query || 
                    faq.question.toLowerCase().includes(query) || 
                    faq.answer.toLowerCase().includes(query) ||
                    (faq.category && faq.category.toLowerCase().includes(query));
                return matchCat && matchQuery;
            });

            renderFaqs(filtered);
        }

        document.getElementById('faqSearchInput').addEventListener('input', filterFaqs);
        document.getElementById('faqCategoryFilter').addEventListener('change', filterFaqs);

        document.addEventListener('DOMContentLoaded', loadFaqs);
    </script>
</body>
</html>
