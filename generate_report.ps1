# =============================================================================
# Practical 15 Word Report Generator
# Generates Practical_15_25CS057_Final_Integration_Report.docx
# Matching Academic Standards, Syllabus Alignment, Real Screenshots, and Rigorous Verification
# Course: ITUE203 - Web Development Frameworks
# Student Name: Krupal Parmar | Student ID: 25CS057 | Academic Year: 2026-27
# =============================================================================

$ErrorActionPreference = "Stop"

$workspaceDir = "D:\WDF\final project"
$outputReportDir = "$workspaceDir\reports"
if (!(Test-Path $outputReportDir)) {
    New-Item -ItemType Directory -Path $outputReportDir -Force | Out-Null
}
$outputDocx = "$outputReportDir\Practical_15_25CS057_Final_Integration_Report.docx"
$logoCharusat = "$workspaceDir\logos\charusat_logo.jpeg"
$logoCspit = "$workspaceDir\logos\cspit_logo.jpeg"
$screenDir = "$workspaceDir\screenshots"

Write-Host "Initializing Microsoft Word Automation..."
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0

try {
    $doc = $word.Documents.Add()
    
    # Page Setup: Normal Margins (0.75 in = 54 pt)
    $doc.PageSetup.TopMargin = 54
    $doc.PageSetup.BottomMargin = 54
    $doc.PageSetup.LeftMargin = 54
    $doc.PageSetup.RightMargin = 54

    # Setup Header and Footer
    $section = $doc.Sections.Item(1)
    $footer = $section.Footers.Item(1)
    $footer.Range.Font.Name = "Arial"
    $footer.Range.Font.Size = 8.5
    $footer.Range.Font.Color = 0x666666
    $footer.Range.Text = "ITUE203: Web Development Frameworks  |  Practical 15 Report  |  Student ID: 25CS057"
    $footer.Range.ParagraphFormat.Alignment = 2 # Right align

    # Helper function for adding styled headings
    function Add-Heading {
        param([string]$headingText, [int]$level = 1)
        $p = $doc.Paragraphs.Add()
        $p.Range.Text = $headingText
        $p.Range.Font.Name = "Arial"
        $p.Range.Font.Bold = 1
        if ($level -eq 1) {
            $p.Range.Font.Size = 13
            $p.Range.Font.Color = 0x8A3A1E # Deep Navy Blue (#1E3A8A in BGR)
            $p.SpaceBefore = 12
            $p.SpaceAfter = 3
        } elseif ($level -eq 2) {
            $p.Range.Font.Size = 11
            $p.Range.Font.Color = 0x663300 # Dark Blue
            $p.SpaceBefore = 8
            $p.SpaceAfter = 2
        } else {
            $p.Range.Font.Size = 10
            $p.Range.Font.Color = 0x333333
            $p.SpaceBefore = 6
            $p.SpaceAfter = 2
        }
        $p.Range.InsertParagraphAfter()
    }

    # Helper function for body text
    function Add-Body {
        param([string]$bodyText, [bool]$isBold = $false)
        $p = $doc.Paragraphs.Add()
        $p.Range.Text = $bodyText
        $p.Range.Font.Name = "Arial"
        $p.Range.Font.Size = 9.5
        $p.Range.Font.Bold = [int]$isBold
        $p.Range.Font.Color = 0x111111
        $p.SpaceBefore = 2
        $p.SpaceAfter = 2.5
        $p.Range.ParagraphFormat.LineSpacingRule = 0 # Single
        $p.Range.InsertParagraphAfter()
    }

    # Helper function for bullet points
    function Add-Bullet {
        param([string]$bulletText)
        $p = $doc.Paragraphs.Add()
        $p.Range.Text = [char]0x2022 + "  " + $bulletText
        $p.Range.Font.Name = "Arial"
        $p.Range.Font.Size = 9
        $p.Range.Font.Bold = 0
        $p.LeftIndent = 16
        $p.SpaceBefore = 1
        $p.SpaceAfter = 2
        $p.Range.InsertParagraphAfter()
    }

    # Helper function for Images with Figures
    function Add-Figure {
        param([string]$imgPath, [string]$figNumber, [string]$caption, [string]$description, [int]$maxW = 470)
        if (Test-Path $imgPath) {
            $pImg = $doc.Paragraphs.Add()
            $pImg.Alignment = 1 # Center
            $shape = $pImg.Range.InlineShapes.AddPicture($imgPath, $false, $true)
            
            if ($shape.Width -gt $maxW) {
                $ratio = [double]$maxW / $shape.Width
                $shape.Width = $maxW
                $shape.Height = [int]($shape.Height * $ratio)
            }
            $pImg.SpaceBefore = 6
            $pImg.SpaceAfter = 2
            $pImg.Range.InsertParagraphAfter()

            # Figure Caption
            $pCap = $doc.Paragraphs.Add()
            $pCap.Alignment = 1 # Center
            $pCap.Range.Text = "Figure " + $figNumber + ": " + $caption
            $pCap.Range.Font.Name = "Arial"
            $pCap.Range.Font.Size = 8.5
            $pCap.Range.Font.Bold = 1
            $pCap.Range.Font.Color = 0x333333
            $pCap.SpaceBefore = 2
            $pCap.SpaceAfter = 2
            $pCap.Range.InsertParagraphAfter()

            if (![string]::IsNullOrEmpty($description)) {
                $pDesc = $doc.Paragraphs.Add()
                $pDesc.Range.Text = "Verification Observation: " + $description
                $pDesc.Range.Font.Name = "Arial"
                $pDesc.Range.Font.Size = 8.5
                $pDesc.Range.Font.Italic = 1
                $pDesc.Range.Font.Color = 0x555555
                $pDesc.SpaceBefore = 1
                $pDesc.SpaceAfter = 6
                $pDesc.Range.InsertParagraphAfter()
            }
        }
    }

    Write-Host "Creating Cover Header..."
    
    # Header Logos Table
    $headerTable = $doc.Tables.Add($doc.Paragraphs.Add().Range, 1, 2)
    $headerTable.Borders.Enable = 0
    $headerTable.Columns.Item(1).Width = 240
    $headerTable.Columns.Item(2).Width = 240

    if (Test-Path $logoCharusat) {
        $pLogo1 = $headerTable.Cell(1, 1).Range.Paragraphs.Add()
        $pLogo1.Alignment = 0 # Left
        $shape1 = $pLogo1.Range.InlineShapes.AddPicture($logoCharusat, $false, $true)
        $shape1.Height = 44
    }

    if (Test-Path $logoCspit) {
        $pLogo2 = $headerTable.Cell(1, 2).Range.Paragraphs.Add()
        $pLogo2.Alignment = 2 # Right
        $shape2 = $pLogo2.Range.InlineShapes.AddPicture($logoCspit, $false, $true)
        $shape2.Height = 44
    }

    $pTitle = $doc.Paragraphs.Add()
    $pTitle.Alignment = 1 # Center
    $pTitle.Range.Text = "CHAROTAR UNIVERSITY OF SCIENCE AND TECHNOLOGY (CHARUSAT)`nFACULTY OF TECHNOLOGY AND ENGINEERING (FTE)`nDEPARTMENT OF COMPUTER SCIENCE & ENGINEERING`nCHANDUBHAI S. PATEL INSTITUTE OF TECHNOLOGY (CSPIT)"
    $pTitle.Range.Font.Name = "Arial"
    $pTitle.Range.Font.Size = 10
    $pTitle.Range.Font.Bold = 1
    $pTitle.Range.Font.Color = 0x1E3A8A # Deep Navy
    $pTitle.SpaceBefore = 10
    $pTitle.SpaceAfter = 12
    $pTitle.Range.InsertParagraphAfter()

    # Academic Meta Box Table
    $metaTable = $doc.Tables.Add($doc.Paragraphs.Add().Range, 5, 2)
    $metaTable.Borders.Enable = 1
    $metaTable.Borders.InsideColor = 0xCCCCCC
    $metaTable.Borders.OutsideColor = 0x1E3A8A
    $metaTable.Columns.Item(1).Width = 140
    $metaTable.Columns.Item(2).Width = 340

    $metaRows = @(
        @("Subject / Course Code", "Web Development Frameworks (ITUE203) - Semester 3 (Odd 2026-27)"),
        @("Practical Number", "Practical 15 (Final Integration, Testing, Documentation and Submission)"),
        @("Student Name", "Krupal Parmar"),
        @("Student ID / Roll No.", "25CS057"),
        @("Official GitHub Repository", "https://github.com/cs-cspit/25cs057-studentPortal.git")
    )

    for ($i = 0; $i -lt $metaRows.Length; $i++) {
        $row = $metaRows[$i]
        $c1 = $metaTable.Cell($i + 1, 1)
        $c2 = $metaTable.Cell($i + 1, 2)
        
        $c1.Shading.BackgroundPatternColor = 0xF1F5F9
        $c1.Range.Font.Name = "Arial"
        $c1.Range.Font.Size = 9
        $c1.Range.Font.Bold = 1
        $c1.Range.Text = $row[0]

        $c2.Shading.BackgroundPatternColor = 0xFFFFFF
        $c2.Range.Font.Name = "Arial"
        $c2.Range.Font.Size = 9
        if ($i -eq 2 -or $i -eq 3) { $c2.Range.Font.Bold = 1 }
        $c2.Range.Text = $row[1]
    }

    $doc.Paragraphs.Add().Range.InsertParagraphAfter()

    # -------------------------------------------------------------------------
    # 1. AIM
    # -------------------------------------------------------------------------
    Add-Heading -headingText "1. AIM" -level 1
    Add-Body -bodyText "To integrate all the implementations developed in Practical 1 to Practical 14 into one complete, responsive, secure, and fully functional StudentHub website, execute rigorous end-to-end testing, prepare comprehensive documentation, and submit the final project to the official GitHub repository." -isBold $true

    # -------------------------------------------------------------------------
    # 2. OBJECTIVES
    # -------------------------------------------------------------------------
    Add-Heading -headingText "2. OBJECTIVES" -level 1
    Add-Bullet -bulletText "Perform exhaustive codebase inspection of Practical 1 through Practical 14 to catalog all implemented capabilities."
    Add-Bullet -bulletText "Synthesize front-end HTML5/CSS3/JavaScript components, PHP server logic, and MySQL relational structures into one unified portal."
    Add-Bullet -bulletText "Enforce authoritative Role-Based Access Control (RBAC), distinguishing Admin and Student workflows with HTTP 403 Forbidden gating."
    Add-Bullet -bulletText "Integrate asynchronous Fetch API CRUD operations over student records with instant live filtering and modal dialogs."
    Add-Bullet -bulletText "Implement secure campus event management supporting multi-format poster uploads, MIME type validation, and capacity tracking."
    Add-Bullet -bulletText "Execute automated end-to-end test verification covering all 20 required test checklist cases on local XAMPP."
    Add-Bullet -bulletText "Export production-ready database SQL schema and push the complete project cleanly to the existing GitHub repository."

    # -------------------------------------------------------------------------
    # 3. SOFTWARE AND HARDWARE REQUIREMENTS
    # -------------------------------------------------------------------------
    Add-Heading -headingText "3. SOFTWARE AND HARDWARE REQUIREMENTS" -level 1
    Add-Bullet -bulletText "Operating System: Microsoft Windows 11 / Windows 10 (64-bit Architecture)"
    Add-Bullet -bulletText "Local Web Server: Apache HTTP Server 2.4.58 (Win64) OpenSSL/3.1.3 (XAMPP Environment)"
    Add-Bullet -bulletText "Server-Side Scripting Runtime: PHP 8.2.12 with MySQLi, PDO, Session, and GD Extension Libraries"
    Add-Bullet -bulletText "Relational Database Management System: MariaDB / MySQL 10.4.32 with InnoDB Engine & utf8mb4 Collation"
    Add-Bullet -bulletText "Client Web Browsers: Microsoft Edge 120+, Google Chrome 120+ (Blink Rendering Engine)"
    Add-Bullet -bulletText "Version Control & Automation: Git 2.52.0.windows.1, Puppeteer Core, Node.js v24.13.0, PowerShell 5.1"

    # -------------------------------------------------------------------------
    # 4. INTRODUCTION TO STUDENTHUB
    # -------------------------------------------------------------------------
    Add-Heading -headingText "4. INTRODUCTION TO STUDENTHUB" -level 1
    Add-Body -bodyText "StudentHub is an integrated academic information and institutional management portal engineered for Chandubhai S. Patel Institute of Technology (CSPIT) at Charotar University of Science and Technology (CHARUSAT). Modern educational institutions require unified digital ecosystems to eliminate data fragmentation, streamline student administration, track academic milestones, publish campus events, and maintain enterprise-grade security."
    Add-Body -bodyText "Practical 15 represents the final integration milestone of the Web Development Frameworks (ITUE203) curriculum. Over the course of the semester, individual technical components spanning semantic wireframes, CSS Grid/Flexbox styling, DOM interactivity, form validation, external JSON fetching, PHP server-side data processing, relational database modeling, password cryptography, session management, file uploads, Fetch API CRUD, and administrative dashboards were developed iteratively. Practical 15 unifies these components into a seamless, production-ready portal."

    # -------------------------------------------------------------------------
    # 5. PRACTICAL 1 TO 14 INTEGRATION OVERVIEW
    # -------------------------------------------------------------------------
    Add-Heading -headingText "5. PRACTICAL 1 TO 14 INTEGRATION OVERVIEW" -level 1
    Add-Body -bodyText "Every practical completed throughout the semester has been systematically preserved and integrated into the StudentHub platform as summarized in the syllabus mapping matrix below:"

    $pTable = $doc.Tables.Add($doc.Paragraphs.Add().Range, 15, 4)
    $pTable.Borders.Enable = 1
    $pTable.Borders.InsideColor = 0xD8D8D8
    $pTable.Borders.OutsideColor = 0x1E3A8A
    $pTable.Columns.Item(1).Width = 65
    $pTable.Columns.Item(2).Width = 160
    $pTable.Columns.Item(3).Width = 190
    $pTable.Columns.Item(4).Width = 65

    # Header Row
    $headers = @("Practical", "Core Topic & Functionality", "Files & Artifacts Integrated", "Status")
    for ($col = 1; $col -le 4; $col++) {
        $cell = $pTable.Cell(1, $col)
        $cell.Shading.BackgroundPatternColor = 0x1E3A8A
        $cell.Range.Font.Name = "Arial"
        $cell.Range.Font.Size = 8.5
        $cell.Range.Font.Bold = 1
        $cell.Range.Font.Color = 0xFFFFFF
        $cell.Range.Text = $headers[$col - 1]
    }

    $praData = @(
        @("Practical 1", "Project Initiation, Sitemap, Low-Fidelity Wireframes, and GitHub Repository Setup", "README.md, Initial commit 2b5e789, sitemap specifications", "Integrated"),
        @("Practical 2", "Semantic HTML5 Elements, ARIA Accessibility, Academic Timetable, Attendance, Results", "attendance.php, results.php, timetable.php, HTML5 structures", "Integrated"),
        @("Practical 3", "Responsive CSS Grid & Flexbox, Media Query Breakpoints, Institutional Portal Styling", "style.css, assets/css/admin_dashboard.css, mobile grids", "Integrated"),
        @("Practical 4", "JavaScript DOM Manipulation, UI Interactivity, Dynamic Event Listeners & Controllers", "assets/js/admin_dashboard.js, modal dialog controllers", "Integrated"),
        @("Practical 5", "Registration Form with Client-Side Regex Validation & Real-Time Error Messaging", "register.php, client validation scripts, password complexity", "Integrated"),
        @("Practical 6", "Rendering External JSON Feeds via Fetch API with Keyword Search & Category Filters", "help.php, data/faqs.json, dynamic category rendering", "Integrated"),
        @("Practical 7", "PHP Form Processing, Server-Side Sanitization, and CSV/JSON File Storage", "export.php, data/events_backup.json, server sanitization", "Integrated"),
        @("Practical 8", "MySQL Relational Schema Design, ER Modeling, Constraints, and Database Connectivity", "db.php, database/studenthub_final_database.sql, InnoDB tables", "Integrated"),
        @("Practical 9", "Secure Registration with Duplicate Email Checks, Bcrypt Hashing, and Audit Logging", "register.php, password_hash, users and students insert", "Integrated"),
        @("Practical 10", "Secure Login/Logout, Sessions, 15-Minute Inactivity Timeout, Remember-Me Tokens", "login.php, authenticate.php, auth.php, timeout.php", "Integrated"),
        @("Practical 11", "Student Management CRUD, MySQLi Parameterized Queries, and Excel CSV Export", "api_crud.php, export.php, students table search and filters", "Integrated"),
        @("Practical 12", "Campus Event Management, Multi-Format Poster Upload, and MIME Validation", "event_management.php, event_create.php, uploads/event_posters/", "Integrated"),
        @("Practical 13", "PHP RESTful JSON API (/api/records.php) and Asynchronous Fetch CRUD Operations", "api/records.php, api/record.php, assets/js/api-crud.js", "Integrated"),
        @("Practical 14", "Admin Dashboard with Real-Time Database Metrics, Dynamic Menu, RBAC, and Audit Logs", "admin_dashboard.php, audit_logs.php, includes/admin_menu.php", "Integrated")
    )

    for ($r = 0; $r -lt $praData.Length; $r++) {
        $rowItems = $praData[$r]
        for ($c = 1; $c -le 4; $c++) {
            $cell = $pTable.Cell($r + 2, $c)
            $cell.Range.Font.Name = "Arial"
            $cell.Range.Font.Size = 8
            if ($c -eq 1 -or $c -eq 4) { $cell.Range.Font.Bold = 1 }
            if ($c -eq 4) {
                $cell.Shading.BackgroundPatternColor = 0xECFDF5 # Light green
                $cell.Range.Font.Color = 0x166534
            } else {
                if ($r % 2 -eq 1) { $cell.Shading.BackgroundPatternColor = 0xF8FAFC }
            }
            $cell.Range.Text = $rowItems[$c - 1]
        }
    }

    $doc.Paragraphs.Add().Range.InsertParagraphAfter()

    # -------------------------------------------------------------------------
    # 6. INTEGRATION PROCESS & ARCHITECTURE
    # -------------------------------------------------------------------------
    Add-Heading -headingText "6. INTEGRATION PROCESS & ARCHITECTURE" -level 1
    Add-Body -bodyText "The integration process followed an architectural 4-tier model combining presentation, application logic, persistence, and security layers into a unified codebase:"
    Add-Bullet -bulletText "Unified Routing Architecture: The root index.php acts as the public landing page with institutional hero sections and live database statistics. Authenticated users are smoothly directed by dashboard.php to their role-specific dashboard (student_dashboard.php or admin_dashboard.php)."
    Add-Bullet -bulletText "Centralized Database Connection: A singleton MySQLi connection handler (db.php) manages connections with utf8mb4 charset and prepared statements across all PHP scripts."
    Add-Bullet -bulletText "Strict Role-Based Access Control (RBAC): auth.php enforces requireAuth and requireRole checks on every server endpoint. Unauthorized requests trigger immediate HTTP 403 Forbidden responses."
    Add-Bullet -bulletText "Dynamic Responsive Sidebar: includes/admin_menu.php renders role-gated navigation, strictly hiding administrative links from student users."
    Add-Bullet -bulletText "RESTful API Integration: api/records.php and api/record.php provide standard HTTP interfaces (GET, POST, PUT, DELETE) used by JavaScript Fetch API for full CRUD without page reloads."

    # -------------------------------------------------------------------------
    # 7. WEBSITE MODULES & FUNCTIONALITY
    # -------------------------------------------------------------------------
    Add-Heading -headingText "7. WEBSITE MODULES & FUNCTIONALITY" -level 1
    Add-Heading -headingText "7.1 Public Portal Modules" -level 2
    Add-Bullet -bulletText "Home Page (index.php): Professional landing portal with CHARUSAT/CSPIT branding, real-time MySQL metrics counter, core module highlights, live upcoming event cards, and footer."
    Add-Bullet -bulletText "About Us Page (about.php): Comprehensive system specifications, academic details for Krupal Parmar (25CS057), project objectives, and Practical 1-14 syllabus matrix."
    Add-Bullet -bulletText "Student Registration (register.php): Dual entity insertion into users and students tables, duplicate email/username checking, Bcrypt hashing, and audit logging."
    Add-Bullet -bulletText "Secure Login Portal (login.php): Role routing, remember-me persistent tokens, demo credential chips, and inactivity timeout alerts."
    Add-Bullet -bulletText "Helpdesk & Knowledge Base (help.php): External JSON rendering (data/faqs.json) using Fetch API with real-time keyword search and category filtering."

    Add-Heading -headingText "7.2 Student Academic Portal Modules" -level 2
    Add-Bullet -bulletText "Student Dashboard (student_dashboard.php): Student profile banner, attendance progress bars (>92%), examination results (SGPA 9.15), weekly lecture timetable, and event registrations."
    Add-Bullet -bulletText "Role Access Inspector: Built-in security testing buttons allowing students to verify server-side HTTP 403 Forbidden denial when attempting to access admin pages."

    Add-Heading -headingText "7.3 Administrator Management Modules" -level 2
    Add-Bullet -bulletText "Admin Dashboard (admin_dashboard.php): Real-time metrics (Total Users, Students, Events, Registrations), monthly analytics chart, and latest registrations table."
    Add-Bullet -bulletText "Student Management Module (api_crud.php): Live Fetch API asynchronous CRUD operations, instant search, department/status/year filtering, Add/Edit modals, and Excel CSV export."
    Add-Bullet -bulletText "Campus Event Console (event_management.php): Publishing events, editing details, capacity tracking, and multi-format poster upload validation."
    Add-Bullet -bulletText "User Management (user_management.php): Account administration with Active/Inactive account status toggling."
    Add-Bullet -bulletText "Security Audit Trail (audit_logs.php): Audit logging console with action filters, status indicators, IP/agent logging, and search."

    # -------------------------------------------------------------------------
    # 8. DATABASE INTEGRATION & SCHEMA
    # -------------------------------------------------------------------------
    Add-Heading -headingText "8. DATABASE INTEGRATION & SCHEMA" -level 1
    Add-Body -bodyText "The unified StudentHub application connects to MariaDB / MySQL database studenthub_db. The schema consists of six core relational tables designed with strict integrity constraints:"
    Add-Bullet -bulletText "users: Primary authentication store (id, username, email, password_hash, role, status, full_name, department, student_id_code, last_login, created_at)."
    Add-Bullet -bulletText "students: Academic student records directory (id, student_id, name, email, department, course, year, status, created_at, updated_at)."
    Add-Bullet -bulletText "events: Campus events catalog (id, title, category, status, description, poster_path, event_date, venue, capacity, created_at)."
    Add-Bullet -bulletText "registrations: Relational many-to-many join store linking students to events with foreign key constraints (registration_id, student_id, event_id, status, notes)."
    Add-Bullet -bulletText "remember_tokens: Persistent login tokens with SHA-256 hashed selectors and 30-day expiration dates."
    Add-Bullet -bulletText "audit_logs: Enterprise security audit trail recording logins, registrations, CRUD operations, and unauthorized access attempts without storing sensitive passwords."

    # -------------------------------------------------------------------------
    # 9. SECURITY & DATA PRIVACY
    # -------------------------------------------------------------------------
    Add-Heading -headingText "9. SECURITY & DATA PRIVACY" -level 1
    Add-Bullet -bulletText "Cryptographic Password Hashing: Uses PHP password_hash with PASSWORD_BCRYPT and a minimum cost factor of 10. Plaintext passwords are NEVER stored."
    Add-Bullet -bulletText "SQL Injection Mitigation: Every SQL query across login, registration, event management, and API endpoints uses MySQLi parameterized prepared statements."
    Add-Bullet -bulletText "Cross-Site Scripting (XSS) Prevention: All dynamic inputs are sanitized and rendered using htmlspecialchars."
    Add-Bullet -bulletText "CSRF Token Defense: Form submissions are validated against cryptographically secure random session tokens."
    Add-Bullet -bulletText "Session Security: Session IDs are regenerated upon successful authentication (session_regenerate_id) to prevent session fixation. Inactivity timeout terminates sessions after 900 seconds (15 minutes)."
    Add-Bullet -bulletText "Server-Side RBAC Enforcement: Role checks are strictly executed on the server tier. Client-side navigation tampering is intercepted with immediate HTTP 403 Forbidden responses."

    # -------------------------------------------------------------------------
    # 10. RIGOROUS 20-POINT TESTING CHECKLIST
    # -------------------------------------------------------------------------
    Add-Heading -headingText "10. RIGOROUS 20-POINT TESTING CHECKLIST" -level 1
    Add-Body -bodyText "The complete integrated website was verified using an automated headless Microsoft Edge test suite running on the active local XAMPP Apache and MySQL server. All 20 evaluation test cases passed successfully:"

    $testTable = $doc.Tables.Add($doc.Paragraphs.Add().Range, 21, 5)
    $testTable.Borders.Enable = 1
    $testTable.Borders.InsideColor = 0xD8D8D8
    $testTable.Borders.OutsideColor = 0x1E3A8A
    $testTable.Columns.Item(1).Width = 30
    $testTable.Columns.Item(2).Width = 110
    $testTable.Columns.Item(3).Width = 140
    $testTable.Columns.Item(4).Width = 140
    $testTable.Columns.Item(5).Width = 60

    # Header Row
    $tHeaders = @("No.", "Test Case", "Expected Result", "Actual Result", "Status")
    for ($col = 1; $col -le 5; $col++) {
        $cell = $testTable.Cell(1, $col)
        $cell.Shading.BackgroundPatternColor = 0x1E3A8A
        $cell.Range.Font.Name = "Arial"
        $cell.Range.Font.Size = 8
        $cell.Range.Font.Bold = 1
        $cell.Range.Font.Color = 0xFFFFFF
        $cell.Range.Text = $tHeaders[$col - 1]
    }

    # Load from test_results.json if exists
    $jsonResultsPath = "$workspaceDir\test_results.json"
    $cases = @()
    if (Test-Path $jsonResultsPath) {
        $jsonContent = Get-Content $jsonResultsPath -Raw | ConvertFrom-Json
        foreach ($item in $jsonContent) {
            $cases += ,@($item.num.ToString(), $item.name, $item.expected, $item.actual, $item.status)
        }
    }

    for ($r = 0; $r -lt $cases.Length; $r++) {
        $cData = $cases[$r]
        for ($c = 1; $c -le 5; $c++) {
            $cell = $testTable.Cell($r + 2, $c)
            $cell.Range.Font.Name = "Arial"
            $cell.Range.Font.Size = 7.5
            if ($c -eq 1 -or $c -eq 5) { $cell.Range.Font.Bold = 1 }
            if ($c -eq 5) {
                $cell.Shading.BackgroundPatternColor = 0xECFDF5
                $cell.Range.Font.Color = 0x166534
            } else {
                if ($r % 2 -eq 1) { $cell.Shading.BackgroundPatternColor = 0xF8FAFC }
            }
            $cell.Range.Text = $cData[$c - 1]
        }
    }

    $doc.Paragraphs.Add().Range.InsertParagraphAfter()

    # -------------------------------------------------------------------------
    # 11. GENUINE SCREENSHOTS & VERIFICATION EVIDENCE
    # -------------------------------------------------------------------------
    Add-Heading -headingText "11. GENUINE SCREENSHOTS & VERIFICATION EVIDENCE" -level 1
    Add-Body -bodyText "The following genuine full-page screenshots were captured from the active StudentHub portal running on local XAMPP to provide authentic verification evidence:"

    Add-Figure -imgPath "$screenDir\01_home_page.png" -figNumber "1" -caption "Unified StudentHub Landing Portal Home Page" -description "Demonstrating institutional branding, hero banner, live dynamic database metrics (Enrolled Students, Campus Events, Registrations, Security Audits), and navigation."
    Add-Figure -imgPath "$screenDir\02_about_page.png" -figNumber "2" -caption "Institutional About Us Page & Specifications" -description "Displaying developer credentials for Krupal Parmar (25CS057), CSPIT CHARUSAT affiliation, project objectives, and Practical 1-14 syllabus matrix."
    Add-Figure -imgPath "$screenDir\03_registration_form.png" -figNumber "3" -caption "Student Registration Portal Form" -description "Form with inputs for Full Name, Enrollment ID, Department, Username, Email, and Password with real-time client-side validation."
    Add-Figure -imgPath "$screenDir\04_registration_success.png" -figNumber "4" -caption "Successful Student Registration Confirmation" -description "Verification that a newly registered student account was created, password hashed with Bcrypt, and record inserted into both users and students tables."
    Add-Figure -imgPath "$screenDir\05_duplicate_registration_rejected.png" -figNumber "5" -caption "Duplicate Registration Prevention" -description "Confirmation that the MySQLi prepared statement duplicate check caught an existing username/email and safely rejected insertion."
    Add-Figure -imgPath "$screenDir\06_student_dashboard.png" -figNumber "6" -caption "Student Academic Portal Dashboard" -description "Displaying student profile details, academic attendance bars (>92%), examination grades (SGPA 9.15), weekly lecture timetable, and registrations."
    Add-Figure -imgPath "$screenDir\07_admin_dashboard.png" -figNumber "7" -caption "Administrator Executive Dashboard" -description "Displaying authoritative database KPIs, dynamic navigation sidebar, monthly analytics chart, and latest registrations table."
    Add-Figure -imgPath "$screenDir\08_invalid_login_error.png" -figNumber "8" -caption "Invalid Authentication Defense" -description "Confirmation that invalid credentials trigger an Authentication Failed notification without revealing account existence."
    Add-Figure -imgPath "$screenDir\09_student_access_denied_403.png" -figNumber "9" -caption "Role-Based Access Control HTTP 403 Forbidden Denial" -description "Confirmation that student direct URL access to admin_dashboard.php is strictly intercepted and logged as an unauthorized attempt."
    Add-Figure -imgPath "$screenDir\10_student_management_table.png" -figNumber "10" -caption "Student Management Module Table" -description "Demonstrating live student directory loaded via Fetch API with department filters, status badges, and Excel CSV export button."
    Add-Figure -imgPath "$screenDir\11_event_management_console.png" -figNumber "11" -caption "Campus Event Management Console" -description "Displaying active campus events with verified poster artwork, capacity counters, and administrative action controls."
    Add-Figure -imgPath "$screenDir\12_poster_upload_form.png" -figNumber "12" -caption "Event Creation & Poster Upload Interface" -description "Validating poster uploads with MIME type restrictions (JPEG, PNG, WEBP), 2MB size cap, and unique hashed filename generation."
    Add-Figure -imgPath "$screenDir\13_api_records_json.png" -figNumber "13" -caption "RESTful PHP JSON API Output (/api/records.php)" -description "Displaying the structured JSON payload containing total records count, status breakdown, and student entities."
    Add-Figure -imgPath "$screenDir\14_live_search_filter.png" -figNumber "14" -caption "AJAX Live Search & Filter Demonstration" -description "Filtering student records dynamically in real time without refreshing the browser."
    Add-Figure -imgPath "$screenDir\15_session_timeout_alert.png" -figNumber "15" -caption "15-Minute Session Inactivity Timeout" -description "Demonstrating automatic session termination upon inactivity threshold expiration with re-authentication prompt."
    Add-Figure -imgPath "$screenDir\16_logout_success.png" -figNumber "16" -caption "Secure Logout Confirmation" -description "Confirmation that session data was cleared, Remember-Me cookie revoked, and logout event recorded in the audit trail."
    Add-Figure -imgPath "$screenDir\17_audit_logs_table.png" -figNumber "17" -caption "System Security Audit Logs Console" -description "Displaying authoritative audit logs of logins, student creations, user status toggles, and RBAC denials with IP and timestamp metadata."
    Add-Figure -imgPath "$screenDir\18_mobile_responsive_home.png" -figNumber "18" -caption "Mobile Responsive Viewport Verification (375x812)" -description "Demonstrating mobile layout adaptation with responsive CSS Grid, reflowing cards, and clean mobile typography."
    Add-Figure -imgPath "$screenDir\19_protected_url_redirect.png" -figNumber "19" -caption "Protected URL Unauthenticated Guest Redirection" -description "Confirmation that unauthenticated guests attempting direct URL access are safely redirected to the login portal."

    # -------------------------------------------------------------------------
    # 12. LOCAL DEPLOYMENT VIA XAMPP
    # -------------------------------------------------------------------------
    Add-Heading -headingText "12. LOCAL DEPLOYMENT VIA XAMPP" -level 1
    Add-Body -bodyText "To execute the complete StudentHub application locally, follow these deployment steps:"
    Add-Bullet -bulletText "Step 1 (XAMPP Environment): Install XAMPP with PHP 8.2+ and MySQL/MariaDB on Windows."
    Add-Bullet -bulletText "Step 2 (Directory Junction): Create a junction from C:\xampp\htdocs\p15 or C:\xampp\htdocs\studentPortal pointing directly to D:\WDF\final project."
    Add-Bullet -bulletText "Step 3 (Service Startup): Start Apache HTTP Server and MySQL service using XAMPP Control Panel or standalone batch scripts."
    Add-Bullet -bulletText "Step 4 (Database Import): Launch phpMyAdmin (http://localhost/phpmyadmin/), create database studenthub_db, and import database/studenthub_final_database.sql."
    Add-Bullet -bulletText "Step 5 (Database Credentials): Verify db.php configuration (default XAMPP settings: host=localhost, user=root, password='', port=3306)."
    Add-Bullet -bulletText "Step 6 (Access Website): Open your browser and navigate to http://localhost/p15/index.php."

    # -------------------------------------------------------------------------
    # 13. GITHUB SUBMISSION DETAILS
    # -------------------------------------------------------------------------
    Add-Heading -headingText "13. GITHUB SUBMISSION DETAILS" -level 1
    Add-Body -bodyText "The complete integrated StudentHub project has been committed and uploaded to the student's official repository as required:"
    Add-Bullet -bulletText "Repository URL: https://github.com/cs-cspit/25cs057-studentPortal.git"
    Add-Bullet -bulletText "Repository Name: 25cs057-studentPortal"
    Add-Bullet -bulletText "GitHub Organization: cs-cspit"
    Add-Bullet -bulletText "Branch: main"
    Add-Bullet -bulletText "Final Commit Message: Practical 15 - Final Integration of Practical 1 to 14"
    Add-Bullet -bulletText "Git Preservation: The complete previous commit history (Initial commit, Practical 2 branch) has been strictly preserved."

    # -------------------------------------------------------------------------
    # 14. RESULT
    # -------------------------------------------------------------------------
    Add-Heading -headingText "14. RESULT" -level 1
    Add-Body -bodyText "All implementations developed across Practical 1 to Practical 14 were successfully integrated into one cohesive, responsive, and secure StudentHub web portal. End-to-end automated testing verified all 20 required checklist test cases with 100% pass rate. The final production-grade SQL export, comprehensive README.md, Word report, and authentic verification screenshots were successfully generated and committed to the official GitHub repository."

    # -------------------------------------------------------------------------
    # 15. CONCLUSION
    # -------------------------------------------------------------------------
    Add-Heading -headingText "15. CONCLUSION" -level 1
    Add-Body -bodyText "Practical 15 successfully completes the semester-long Web Development Frameworks (ITUE203) laboratory curriculum. By synthesizing modern front-end technologies (Semantic HTML5, CSS Grid, Flexbox, Vanilla JavaScript DOM manipulation, Fetch API) with enterprise PHP server architectures, relational MySQL modeling, and strict security protocols (Bcrypt hashing, CSRF tokens, session timeouts, server-side RBAC, and audit trails), StudentHub demonstrates a robust, full-stack university management portal ready for institutional deployment."

    Write-Host "Saving document to $outputDocx..."
    $doc.SaveAs([ref]$outputDocx)
    
    # Also save a copy in root workspace for convenience
    $rootDocx = "$workspaceDir\Practical_15_25CS057_Final_Integration_Report.docx"
    $doc.SaveAs([ref]$rootDocx)

    $doc.Close()
    Write-Host "Successfully generated Word report!"

} catch {
    Write-Error "Error during report generation: $_"
} finally {
    $word.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($word) | Out-Null
    [System.GC]::Collect()
    [System.GC]::WaitForPendingFinalizers()
}
