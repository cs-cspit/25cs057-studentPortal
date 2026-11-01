/**
 * StudentHub Practical 13: Fetch API & AJAX CRUD Client
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057
 *
 * Implements asynchronous, non-reloading CRUD operations using JavaScript Fetch API
 * with prepared statement PHP backend, live search, filtering, and modal dialogs.
 */

(function () {
    'use strict';

    // Application State
    let cachedRecords = [];
    let currentEditingId = null;
    let currentDeletingRecord = null;
    let searchDebounceTimer = null;

    // DOM Elements Cache
    const elements = {
        tableBody: document.getElementById('studentsTableBody'),
        loadingOverlay: document.getElementById('tableLoadingOverlay'),
        emptyState: document.getElementById('tableEmptyState'),
        recordCountBadge: document.getElementById('recordCountBadge'),
        
        // Stats Ribbon
        statTotal: document.getElementById('statTotalStudents'),
        statActive: document.getElementById('statActiveStudents'),
        statInactive: document.getElementById('statInactiveStudents'),
        statFiltered: document.getElementById('statFilteredCount'),
        
        // Search & Filters
        searchInput: document.getElementById('apiSearchInput'),
        searchClearBtn: document.getElementById('apiSearchClearBtn'),
        deptFilter: document.getElementById('apiDeptFilter'),
        statusFilter: document.getElementById('apiStatusFilter'),
        yearFilter: document.getElementById('apiYearFilter'),
        resetFiltersBtn: document.getElementById('apiResetFiltersBtn'),
        refreshBtn: document.getElementById('apiRefreshBtn'),
        addNewBtn: document.getElementById('apiAddNewBtn'),
        
        // Student Modal (Add/Edit)
        studentModal: document.getElementById('studentFormModal'),
        studentModalTitle: document.getElementById('studentModalTitle'),
        studentForm: document.getElementById('studentRecordForm'),
        studentFormSaveBtn: document.getElementById('studentFormSaveBtn'),
        studentIdInput: document.getElementById('inputStudentId'),
        nameInput: document.getElementById('inputName'),
        emailInput: document.getElementById('inputEmail'),
        deptInput: document.getElementById('inputDepartment'),
        courseInput: document.getElementById('inputCourse'),
        yearInput: document.getElementById('inputYear'),
        statusInput: document.getElementById('inputStatus'),
        
        // Delete Confirmation Modal
        deleteModal: document.getElementById('deleteConfirmModal'),
        deleteConfirmBtn: document.getElementById('confirmDeleteBtn'),
        deletePreviewId: document.getElementById('deletePreviewId'),
        deletePreviewName: document.getElementById('deletePreviewName'),
        deletePreviewDept: document.getElementById('deletePreviewDept'),
        
        // Toast Container
        toastContainer: document.getElementById('apiToastContainer')
    };

    // Environment Context
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const isAdmin = document.body.dataset.isAdmin === 'true';

    /**
     * Escape special HTML characters to prevent XSS.
     */
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Displays a modern toast notification.
     *
     * @param {string} title
     * @param {string} message
     * @param {'success'|'error'|'warning'|'info'} type
     * @param {number} duration Duration in ms
     */
    function showToast(title, message, type = 'info', duration = 4000) {
        if (!elements.toastContainer) return;

        const toast = document.createElement('div');
        toast.className = `api-toast ${type}`;

        const icons = {
            success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
        };

        toast.innerHTML = `
            <div class="api-toast-icon">${icons[type] || icons.info}</div>
            <div class="api-toast-content">
                <div class="api-toast-title">${escapeHtml(title)}</div>
                <div class="api-toast-msg">${escapeHtml(message)}</div>
            </div>
            <button class="api-toast-close" aria-label="Close">&times;</button>
        `;

        const closeBtn = toast.querySelector('.api-toast-close');
        const dismiss = () => {
            toast.classList.add('toast-hiding');
            setTimeout(() => toast.remove(), 250);
        };

        closeBtn.addEventListener('click', dismiss);
        elements.toastContainer.appendChild(toast);

        if (duration > 0) {
            setTimeout(dismiss, duration);
        }
    }

    /**
     * Toggles table loading overlay.
     */
    function setLoading(isLoading) {
        if (elements.loadingOverlay) {
            if (isLoading) {
                elements.loadingOverlay.classList.add('active');
            } else {
                elements.loadingOverlay.classList.remove('active');
            }
        }
        if (elements.refreshBtn) {
            if (isLoading) {
                elements.refreshBtn.classList.add('rotating');
            } else {
                elements.refreshBtn.classList.remove('rotating');
            }
        }
    }

    /**
     * Clears all validation errors in the student form modal.
     */
    function clearFormErrors() {
        if (!elements.studentForm) return;
        const invalidInputs = elements.studentForm.querySelectorAll('.is-invalid');
        invalidInputs.forEach(input => input.classList.remove('is-invalid'));

        const feedbackEls = elements.studentForm.querySelectorAll('.api-invalid-feedback');
        feedbackEls.forEach(fb => {
            fb.textContent = '';
            fb.classList.remove('visible');
        });
    }

    /**
     * Highlights fields with server-side validation errors.
     */
    function displayFormErrors(errors = {}) {
        clearFormErrors();
        for (const [field, errorMsg] of Object.entries(errors)) {
            const input = document.getElementById(`input${field.charAt(0).toUpperCase() + field.slice(1)}`) ||
                          document.querySelector(`[name="${field}"]`);
            const feedback = document.getElementById(`error-${field}`);
            if (input) {
                input.classList.add('is-invalid');
            }
            if (feedback) {
                feedback.textContent = errorMsg;
                feedback.classList.add('visible');
            }
        }
    }

    /**
     * Renders student records in the HTML table.
     */
    function renderRecordsTable(records) {
        if (!elements.tableBody) return;
        elements.tableBody.innerHTML = '';

        if (!records || records.length === 0) {
            if (elements.emptyState) elements.emptyState.style.display = 'block';
            if (elements.recordCountBadge) elements.recordCountBadge.textContent = '0 Records';
            return;
        }

        if (elements.emptyState) elements.emptyState.style.display = 'none';
        if (elements.recordCountBadge) elements.recordCountBadge.textContent = `${records.length} ${records.length === 1 ? 'Record' : 'Records'}`;

        records.forEach((record, index) => {
            const tr = document.createElement('tr');
            tr.id = `student-row-${record.id}`;
            tr.dataset.id = record.id;

            const statusClass = record.status === 'Active' ? 'status-active' : 'status-inactive';
            const actionButtons = isAdmin
                ? `<div class="api-row-actions">
                     <button type="button" class="api-btn-action btn-edit-action" data-action="edit" data-id="${record.id}" title="Edit Student Record">
                       <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                       <span>Edit</span>
                     </button>
                     <button type="button" class="api-btn-action btn-delete-action" data-action="delete" data-id="${record.id}" title="Delete Student Record">
                       <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                       <span>Delete</span>
                     </button>
                   </div>`
                : `<div class="api-row-actions">
                     <span class="status-pill status-inactive" style="font-size:11px;" title="Administrator permission required to edit">Read Only</span>
                   </div>`;

            tr.innerHTML = `
                <td><strong>#${record.id}</strong></td>
                <td><span class="badge-id">${escapeHtml(record.student_id)}</span></td>
                <td>
                    <div class="student-meta-group">
                        <div class="student-name">${escapeHtml(record.name)}</div>
                        <div class="student-email">${escapeHtml(record.email)}</div>
                    </div>
                </td>
                <td>
                    <span class="dept-badge">${escapeHtml(record.department)}</span>
                    <div class="course-subtext">${escapeHtml(record.course)}</div>
                </td>
                <td><span class="year-chip">Year ${record.year}</span></td>
                <td>
                    <span class="status-pill ${statusClass}">
                        <span class="status-dot"></span>
                        <span>${escapeHtml(record.status)}</span>
                    </span>
                </td>
                <td style="text-align: right;">${actionButtons}</td>
            `;

            elements.tableBody.appendChild(tr);
        });
    }

    /**
     * 1. GET: Asynchronously loads records from PHP API using fetch().
     */
    async function loadRecords(silent = false) {
        if (!silent) setLoading(true);

        const params = new URLSearchParams();
        const search = elements.searchInput?.value.trim();
        const department = elements.deptFilter?.value;
        const status = elements.statusFilter?.value;
        const year = elements.yearFilter?.value;

        if (search) params.append('q', search);
        if (department && department !== 'All') params.append('department', department);
        if (status && status !== 'All') params.append('status', status);
        if (year && year !== 'All') params.append('year', year);

        try {
            const url = `api/records.php?${params.toString()}`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.status === 401) {
                showToast('Session Expired', 'Your session has timed out. Redirecting to login...', 'error');
                setTimeout(() => window.location.href = 'login.php?error=session_expired', 1500);
                return;
            }

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to retrieve records.');
            }

            cachedRecords = result.data?.records || [];
            renderRecordsTable(cachedRecords);

            // Update Statistics
            if (elements.statTotal) elements.statTotal.textContent = result.data?.total_records ?? cachedRecords.length;
            if (elements.statActive) elements.statActive.textContent = result.data?.active_count ?? 0;
            if (elements.statInactive) elements.statInactive.textContent = result.data?.inactive_count ?? 0;
            if (elements.statFiltered) elements.statFiltered.textContent = cachedRecords.length;

        } catch (error) {
            console.error('[API Fetch Error]', error);
            showToast('API Connection Error', error.message || 'Unable to connect to PHP API.', 'error');
            if (elements.emptyState) elements.emptyState.style.display = 'block';
        } finally {
            if (!silent) setLoading(false);
        }
    }

    /**
     * 2. POST: Asynchronously creates a new student record via fetch().
     */
    async function createRecord(recordData) {
        try {
            elements.studentFormSaveBtn.disabled = true;
            elements.studentFormSaveBtn.textContent = 'Saving Record...';

            const response = await fetch('api/records.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'Accept': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(recordData)
            });

            const result = await response.json();

            if (response.status === 422) {
                displayFormErrors(result.errors || {});
                showToast('Validation Error', result.message || 'Please fix highlighted errors.', 'warning');
                return false;
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Error occurred while creating student record.');
            }

            // Success: Close modal, show notification, reload table dynamically
            closeStudentModal();
            showToast('Success!', result.message, 'success');
            await loadRecords(true);
            return true;

        } catch (error) {
            console.error('[Create Record Error]', error);
            showToast('Creation Failed', error.message || 'Network request failed.', 'error');
            return false;
        } finally {
            elements.studentFormSaveBtn.disabled = false;
            elements.studentFormSaveBtn.textContent = 'Save Student Record';
        }
    }

    /**
     * 3. PUT: Asynchronously updates an existing student record via fetch().
     */
    async function updateRecord(id, recordData) {
        try {
            elements.studentFormSaveBtn.disabled = true;
            elements.studentFormSaveBtn.textContent = 'Updating Record...';

            const response = await fetch(`api/record.php?id=${encodeURIComponent(id)}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'Accept': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ ...recordData, id: id, _method: 'PUT' })
            });

            const result = await response.json();

            if (response.status === 422) {
                displayFormErrors(result.errors || {});
                showToast('Validation Error', result.message || 'Please fix highlighted errors.', 'warning');
                return false;
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Error occurred while updating student record.');
            }

            // Success: Close modal, show notification, reload table dynamically
            closeStudentModal();
            showToast('Record Updated', result.message, 'success');
            await loadRecords(true);
            return true;

        } catch (error) {
            console.error('[Update Record Error]', error);
            showToast('Update Failed', error.message || 'Network request failed.', 'error');
            return false;
        } finally {
            elements.studentFormSaveBtn.disabled = false;
            elements.studentFormSaveBtn.textContent = 'Save Student Record';
        }
    }

    /**
     * 4. DELETE: Asynchronously deletes a student record via fetch().
     */
    async function deleteRecord(id) {
        try {
            elements.deleteConfirmBtn.disabled = true;
            elements.deleteConfirmBtn.textContent = 'Deleting...';

            const response = await fetch(`api/record.php?id=${encodeURIComponent(id)}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'Accept': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ id: id, _method: 'DELETE' })
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete record.');
            }

            closeDeleteModal();
            showToast('Record Deleted', result.message, 'success');
            await loadRecords(true);
            return true;

        } catch (error) {
            console.error('[Delete Record Error]', error);
            showToast('Delete Failed', error.message || 'Could not delete record.', 'error');
            return false;
        } finally {
            elements.deleteConfirmBtn.disabled = false;
            elements.deleteConfirmBtn.textContent = 'Delete Record';
        }
    }

    /**
     * Open Modal for creating a new student record.
     */
    function openCreateModal() {
        currentEditingId = null;
        clearFormErrors();
        elements.studentForm.reset();
        elements.studentIdInput.removeAttribute('readonly');
        elements.studentModalTitle.textContent = 'Add New Student Record';
        elements.studentModal.classList.add('active');
        elements.studentIdInput.focus();
    }

    /**
     * Open Modal for editing an existing student record (prefilled via async GET).
     */
    async function openEditModal(id) {
        try {
            setLoading(true);
            const response = await fetch(`api/record.php?id=${encodeURIComponent(id)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Could not load student record.');
            }

            const record = result.data;
            currentEditingId = record.id;
            clearFormErrors();

            elements.studentIdInput.value = record.student_id;
            elements.nameInput.value = record.name;
            elements.emailInput.value = record.email;
            elements.deptInput.value = record.department;
            elements.courseInput.value = record.course;
            elements.yearInput.value = record.year;
            elements.statusInput.value = record.status;

            elements.studentModalTitle.textContent = `Edit Student: ${record.name} (${record.student_id})`;
            elements.studentModal.classList.add('active');
            elements.nameInput.focus();

        } catch (error) {
            console.error('[Edit Modal Prefill Error]', error);
            showToast('Error Loading Record', error.message, 'error');
        } finally {
            setLoading(false);
        }
    }

    /**
     * Close the student Add/Edit modal.
     */
    function closeStudentModal() {
        if (elements.studentModal) {
            elements.studentModal.classList.remove('active');
            clearFormErrors();
            currentEditingId = null;
        }
    }

    /**
     * Open Delete Confirmation Modal.
     */
    function openDeleteModal(id) {
        const record = cachedRecords.find(r => r.id == id);
        if (!record) return;

        currentDeletingRecord = record;
        if (elements.deletePreviewId) elements.deletePreviewId.textContent = record.student_id;
        if (elements.deletePreviewName) elements.deletePreviewName.textContent = record.name;
        if (elements.deletePreviewDept) elements.deletePreviewDept.textContent = record.department;

        elements.deleteModal.classList.add('active');
    }

    /**
     * Close Delete Confirmation Modal.
     */
    function closeDeleteModal() {
        if (elements.deleteModal) {
            elements.deleteModal.classList.remove('active');
            currentDeletingRecord = null;
        }
    }

    /**
     * Event Listeners Registration
     */
    function initEventListeners() {
        // Debounced Search Input
        if (elements.searchInput) {
            elements.searchInput.addEventListener('input', (e) => {
                const val = e.target.value.trim();
                if (elements.searchClearBtn) {
                    elements.searchClearBtn.style.display = val ? 'block' : 'none';
                }
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(() => {
                    loadRecords(false);
                }, 300);
            });
        }

        // Search Clear Button
        if (elements.searchClearBtn) {
            elements.searchClearBtn.addEventListener('click', () => {
                elements.searchInput.value = '';
                elements.searchClearBtn.style.display = 'none';
                loadRecords(false);
            });
        }

        // Filter Dropdowns
        [elements.deptFilter, elements.statusFilter, elements.yearFilter].forEach(el => {
            if (el) {
                el.addEventListener('change', () => loadRecords(false));
            }
        });

        // Reset Filters Button
        if (elements.resetFiltersBtn) {
            elements.resetFiltersBtn.addEventListener('click', () => {
                if (elements.searchInput) elements.searchInput.value = '';
                if (elements.searchClearBtn) elements.searchClearBtn.style.display = 'none';
                if (elements.deptFilter) elements.deptFilter.value = 'All';
                if (elements.statusFilter) elements.statusFilter.value = 'All';
                if (elements.yearFilter) elements.yearFilter.value = 'All';
                loadRecords(false);
            });
        }

        // Refresh Button
        if (elements.refreshBtn) {
            elements.refreshBtn.addEventListener('click', () => loadRecords(false));
        }

        // Add New Student Button (Admin only)
        if (elements.addNewBtn && isAdmin) {
            elements.addNewBtn.addEventListener('click', openCreateModal);
        }

        // Modal Form Submit (Create or Update)
        if (elements.studentForm) {
            elements.studentForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = {
                    student_id: elements.studentIdInput.value.trim(),
                    name: elements.nameInput.value.trim(),
                    email: elements.emailInput.value.trim(),
                    department: elements.deptInput.value,
                    course: elements.courseInput.value,
                    year: parseInt(elements.yearInput.value, 10),
                    status: elements.statusInput.value,
                    csrf_token: csrfToken
                };

                if (currentEditingId) {
                    await updateRecord(currentEditingId, formData);
                } else {
                    await createRecord(formData);
                }
            });
        }

        // Delegate Table Row Action Clicks (Edit / Delete)
        if (elements.tableBody) {
            elements.tableBody.addEventListener('click', (e) => {
                const actionBtn = e.target.closest('[data-action]');
                if (!actionBtn) return;

                const action = actionBtn.dataset.action;
                const id = parseInt(actionBtn.dataset.id, 10);

                if (action === 'edit') {
                    openEditModal(id);
                } else if (action === 'delete') {
                    openDeleteModal(id);
                }
            });
        }

        // Confirm Delete Button Click
        if (elements.deleteConfirmBtn) {
            elements.deleteConfirmBtn.addEventListener('click', async () => {
                if (currentDeletingRecord && currentDeletingRecord.id) {
                    await deleteRecord(currentDeletingRecord.id);
                }
            });
        }

        // Close Modal Triggers
        document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                closeStudentModal();
                closeDeleteModal();
            });
        });

        // Close on ESC Key
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeStudentModal();
                closeDeleteModal();
            }
        });
    }

    // Auto-initialize on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        initEventListeners();
        loadRecords(false);
    });

})();
