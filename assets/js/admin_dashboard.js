/**
 * StudentHub Admin Dashboard & Analytics Controller
 * Practical 14: Admin Dashboard with RBAC, Dynamic Menu, and Audit Log
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Toggle Functionality
    const sidebar = document.getElementById('appSidebar');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const closeBtn = document.getElementById('sidebarCloseBtn');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }

    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', () => {
            sidebar.classList.remove('show');
        });
    }

    // Close sidebar on clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && (!toggleBtn || !toggleBtn.contains(e.target))) {
                sidebar.classList.remove('show');
            }
        }
    });

    // 2. Monthly Registrations Analytics Chart (Practical 14 Advanced Extension)
    initMonthlyAnalyticsChart();

    // 3. User Status Toggle (Practical 14 Intermediate Extension)
    initUserStatusToggle();
});

/**
 * Initializes the Monthly Registration Analytics Chart using Chart.js or dynamic SVG fallback.
 */
function initMonthlyAnalyticsChart() {
    const canvas = document.getElementById('monthlyRegistrationsChart');
    if (!canvas) return;

    const dataScript = document.getElementById('monthlyAnalyticsData');
    if (!dataScript) return;

    let analyticsData = [];
    try {
        analyticsData = JSON.parse(dataScript.textContent);
    } catch (e) {
        console.error('[Analytics Error] Failed to parse chart data JSON:', e);
        return;
    }

    const labels = analyticsData.map(item => item.month_label);
    const counts = analyticsData.map(item => parseInt(item.total_count, 10));

    if (typeof Chart !== 'undefined') {
        const ctx = canvas.getContext('2d');
        
        // Gradient fill for sleek presentation
        const gradient = ctx.createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.45)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.05)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Registrations per Month',
                    data: counts,
                    backgroundColor: gradient,
                    borderColor: '#2563eb',
                    borderWidth: 3,
                    pointBackgroundColor: '#1d4ed8',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    tension: 0.35,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Inter', size: 12, weight: 'bold' },
                        bodyFont: { family: 'Inter', size: 13 },
                        padding: 10,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                return ` ${context.parsed.y} Student Registrations`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { family: 'Inter', size: 11 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 2,
                            font: { family: 'Inter', size: 11 }
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    }
                }
            }
        });
    } else {
        // Fallback: render SVG bar chart if Chart.js CDN is unavailable
        renderSvgFallback(canvas, labels, counts);
    }
}

/**
 * Pure SVG Fallback generator in case external CDN is blocked by environment.
 */
function renderSvgFallback(canvas, labels, counts) {
    const parent = canvas.parentElement;
    const maxVal = Math.max(...counts, 10);
    const svgWidth = 500;
    const svgHeight = 220;
    const barWidth = Math.floor(svgWidth / (counts.length * 2));
    
    let svgHtml = `<svg width="100%" height="220" viewBox="0 0 ${svgWidth} ${svgHeight}" style="font-family:Inter,sans-serif;">`;
    svgHtml += `<line x1="40" y1="180" x2="${svgWidth - 20}" y2="180" stroke="#cbd5e1" stroke-width="1.5" />`;

    counts.forEach((val, idx) => {
        const x = 50 + (idx * (barWidth * 2));
        const barH = Math.round((val / maxVal) * 140);
        const y = 180 - barH;
        svgHtml += `
            <rect x="${x}" y="${y}" width="${barWidth}" height="${barH}" rx="4" fill="#3b82f6" />
            <text x="${x + (barWidth / 2)}" y="${y - 6}" font-size="11" font-weight="bold" fill="#1e3a8a" text-anchor="middle">${val}</text>
            <text x="${x + (barWidth / 2)}" y="198" font-size="10.5" fill="#64748b" text-anchor="middle">${labels[idx]}</text>
        `;
    });

    svgHtml += `</svg>`;
    parent.innerHTML = svgHtml;
}

/**
 * Handles Active/Inactive user status toggling via AJAX.
 */
function initUserStatusToggle() {
    document.querySelectorAll('.btn-toggle-status').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const userId = btn.getAttribute('data-user-id');
            const currentStatus = btn.getAttribute('data-status');
            const csrfToken = btn.getAttribute('data-csrf');
            const targetUsername = btn.getAttribute('data-username') || `User #${userId}`;
            
            const nextStatus = (currentStatus === 'Active') ? 'Inactive' : 'Active';
            const confirmMsg = `Are you sure you want to change status of '${targetUsername}' to ${nextStatus}?`;
            
            if (!confirm(confirmMsg)) return;

            btn.disabled = true;
            btn.innerHTML = `<span style="display:inline-block;animation:spin 1s linear infinite;">⏳</span> Updating...`;

            try {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('new_status', nextStatus);
                formData.append('csrf_token', csrfToken);

                const response = await fetch('user_status_toggle.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const result = await response.json();

                if (result.success) {
                    // Update badge and button state
                    const statusCell = document.getElementById(`statusBadge_${userId}`);
                    if (statusCell) {
                        statusCell.className = `status-badge status-${nextStatus.toLowerCase()}`;
                        statusCell.textContent = nextStatus;
                    }

                    btn.setAttribute('data-status', nextStatus);
                    btn.className = `btn-toggle-status ${nextStatus === 'Active' ? 'btn-toggle-active' : 'btn-toggle-inactive'}`;
                    btn.innerHTML = (nextStatus === 'Active')
                        ? `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg> Deactivate`
                        : `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Activate`;

                    // If on dashboard, reload recent activity feed or display notification
                    showNotification(result.message || `User status changed to ${nextStatus}.`, 'success');
                } else {
                    alert(result.message || 'Failed to update user status.');
                    // Reset button
                    btn.innerHTML = (currentStatus === 'Active') ? 'Deactivate' : 'Activate';
                }
            } catch (err) {
                console.error('[Status Toggle Error]', err);
                alert('A network error occurred while updating user status.');
            } finally {
                btn.disabled = false;
            }
        });
    });
}

function showNotification(text, type = 'success') {
    let toast = document.getElementById('dashToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'dashToast';
        toast.style.cssText = `
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 12px 20px;
            background: #0f172a;
            color: #ffffff;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 500;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: opacity 0.3s ease;
        `;
        document.body.appendChild(toast);
    }
    toast.innerHTML = `<span style="color:#34d399;">✔</span> ${text}`;
    toast.style.opacity = '1';
    setTimeout(() => {
        toast.style.opacity = '0';
    }, 3500);
}

