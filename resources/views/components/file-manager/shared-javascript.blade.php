@props([
    'userType' => 'admin', // 'admin' or 'employee'
    'username' => null, // Required for employee routes
])

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('fileManager', (initialFiles, initialStatistics = {}) => ({
        // Data
        files: initialFiles || [],
        selectedFiles: [],
        selectAll: false,
        searchQuery: '',
        statusFilter: '',
        fileTypeFilter: '',
        viewMode: localStorage.getItem('fileManagerViewMode_{{ $userType }}') || 'grid',
        
        // Sorting
        sortColumn: 'created_at',
        sortDirection: 'desc',
        
        // Advanced filters
        showAdvancedFilters: false,
        dateFromFilter: '',
        dateToFilter: '',
        userEmailFilter: '',
        fileSizeMinFilter: '',
        fileSizeMaxFilter: '',
        
        // Column management (for table view)
        availableColumns: [{
            key: 'original_filename',
            label: 'Filename',
            sortable: true,
            resizable: true,
            defaultWidth: 300,
            minWidth: 200
        }, {
            key: 'email',
            label: 'From',
            sortable: true,
            resizable: true,
            defaultWidth: 200,
            minWidth: 150
        }, {
            key: 'file_size',
            label: 'Size',
            sortable: true,
            resizable: true,
            defaultWidth: 120,
            minWidth: 100
        }, {
            key: 'status',
            label: 'Status',
            sortable: false,
            resizable: true,
            defaultWidth: 120,
            minWidth: 120
        }, {
            key: 'created_at',
            label: 'Date',
            sortable: true,
            resizable: true,
            defaultWidth: 180,
            minWidth: 150
        }, {
            key: 'message',
            label: 'Message',
            sortable: false,
            resizable: true,
            defaultWidth: 300,
            minWidth: 200
        }],
        visibleColumns: {},
        columnWidths: {},
        
        // Column resizing state
        isResizing: false,
        resizingColumn: null,
        startX: 0,
        startWidth: 0,
        
        // User feedback states
        isLoading: false,
        showErrorModal: false,
        errorMessage: '',
        isErrorRetryable: false,
        showSuccessNotification: false,
        successMessage: '',
        
        // Simple delete modal state
        showDeleteModal: false,
        deleteModalFile: null,
        deleteModalTitle: '',
        deleteModalMessage: '',
        
        // Process pending modal state
        showProcessPendingModal: false,
        pendingCount: 0,
        isProcessingPending: false,
        processingProgress: 0,
        processedCount: 0,
        totalCount: 0,
        processingMessage: '',
        processingResults: null,
        
        // Operation states
        isDeleting: false,
        isDownloading: false,
        currentFile: null,
        
        // Progress tracking
        bulkOperationProgress: {
            show: false,
            current: 0,
            total: 0,
            operation: '',
            message: ''
        },
        
        // Statistics
        statistics: initialStatistics,

        // Computed
        get selectAll() {
            return this.filteredFiles.length > 0 && this.selectedFiles.length === this.filteredFiles.length;
        },

        set selectAll(value) {
            if (value) {
                this.selectedFiles = this.filteredFiles.map(file => file.id);
            } else {
                this.selectedFiles = [];
            }
        },

        get filteredFiles() {
            if (!Array.isArray(this.files)) {
                return [];
            }

            let filtered = [...this.files];

            // Apply search filter
            if (this.searchQuery.trim()) {
                const searchTerms = this.searchQuery.toLowerCase().split(' ').filter(term => term.length >= 2);
                filtered = filtered.filter(file => {
                    return searchTerms.every(term =>
                        file.original_filename.toLowerCase().includes(term) ||
                        file.email.toLowerCase().includes(term) ||
                        (file.message && file.message.toLowerCase().includes(term)) ||
                        (file.mime_type && file.mime_type.toLowerCase().includes(term))
                    );
                });
            }

            // Apply status filter
            if (this.statusFilter) {
                filtered = filtered.filter(file => {
                    if (this.statusFilter === 'uploaded') {
                        return file.google_drive_file_id;
                    } else if (this.statusFilter === 'pending') {
                        return !file.google_drive_file_id;
                    }
                    return true;
                });
            }

            // Apply file type filter
            if (this.fileTypeFilter) {
                filtered = filtered.filter(file => {
                    const mimeType = file.mime_type || '';
                    switch (this.fileTypeFilter) {
                        case 'image':
                            return mimeType.startsWith('image/');
                        case 'document':
                            return mimeType.includes('pdf') ||
                                mimeType.includes('msword') ||
                                mimeType.includes('officedocument') ||
                                mimeType.startsWith('text/');
                        case 'video':
                            return mimeType.startsWith('video/');
                        case 'audio':
                            return mimeType.startsWith('audio/');
                        case 'archive':
                            return mimeType.includes('zip') ||
                                mimeType.includes('rar') ||
                                mimeType.includes('7z');
                        case 'other':
                            return !mimeType.startsWith('image/') &&
                                !mimeType.startsWith('video/') &&
                                !mimeType.startsWith('audio/') &&
                                !mimeType.includes('pdf') &&
                                !mimeType.includes('msword') &&
                                !mimeType.includes('officedocument') &&
                                !mimeType.startsWith('text/') &&
                                !mimeType.includes('zip') &&
                                !mimeType.includes('rar') &&
                                !mimeType.includes('7z');
                        default:
                            return true;
                    }
                });
            }

            // Apply date range filters
            if (this.dateFromFilter) {
                const fromDate = new Date(this.dateFromFilter);
                filtered = filtered.filter(file => new Date(file.created_at) >= fromDate);
            }
            if (this.dateToFilter) {
                const toDate = new Date(this.dateToFilter + 'T23:59:59');
                filtered = filtered.filter(file => new Date(file.created_at) <= toDate);
            }

            // Apply user email filter
            if (this.userEmailFilter.trim()) {
                const emailQuery = this.userEmailFilter.toLowerCase();
                filtered = filtered.filter(file =>
                    file.email && file.email.toLowerCase().includes(emailQuery)
                );
            }

            // Apply file size filters
            if (this.fileSizeMinFilter) {
                const minSize = this.parseFileSize(this.fileSizeMinFilter);
                if (minSize > 0) {
                    filtered = filtered.filter(file => (file.file_size || 0) >= minSize);
                }
            }
            if (this.fileSizeMaxFilter) {
                const maxSize = this.parseFileSize(this.fileSizeMaxFilter);
                if (maxSize > 0) {
                    filtered = filtered.filter(file => (file.file_size || 0) <= maxSize);
                }
            }

            // Apply sorting
            filtered.sort((a, b) => {
                let valA = a[this.sortColumn];
                let valB = b[this.sortColumn];

                if (this.sortColumn === 'file_size') {
                    valA = parseInt(valA) || 0;
                    valB = parseInt(valB) || 0;
                } else if (this.sortColumn === 'created_at') {
                    valA = new Date(valA);
                    valB = new Date(valB);
                } else if (typeof valA === 'string') {
                    valA = valA.toLowerCase();
                    valB = valB.toLowerCase();
                }

                let comparison = 0;
                if (valA > valB) comparison = 1;
                else if (valA < valB) comparison = -1;

                return this.sortDirection === 'asc' ? comparison : -comparison;
            });

            return filtered;
        },

        get activeFiltersCount() {
            let count = 0;
            if (this.searchQuery.trim()) count++;
            if (this.statusFilter) count++;
            if (this.fileTypeFilter) count++;
            if (this.dateFromFilter) count++;
            if (this.dateToFilter) count++;
            if (this.userEmailFilter.trim()) count++;
            if (this.fileSizeMinFilter.trim()) count++;
            if (this.fileSizeMaxFilter.trim()) count++;
            return count;
        },

        get visibleColumnsList() {
            return this.availableColumns.filter(column => this.visibleColumns[column.key]);
        },

        get tableStyles() {
            return `table-layout: fixed; width: ${this.getTotalTableWidth()}px;`;
        },

        // Methods
        init() {
            // Ensure files is always an array
            if (!Array.isArray(this.files)) {
                this.files = [];
            }

            try {
                this.visibleColumns = this.getStoredColumnVisibility();
                this.columnWidths = this.getStoredColumnWidths();
                this.setupColumnResizing();

                // Load files if the initial array is empty
                if (this.files.length === 0) {
                    this.loadFiles();
                }
            } catch (error) {
                console.error('Error initializing file manager:', error);
                // Set default values if initialization fails
                this.visibleColumns = {
                    original_filename: true,
                    email: true,
                    file_size: true,
                    status: true,
                    created_at: true,
                    message: false
                };
                this.columnWidths = {
                    original_filename: 300,
                    email: 200,
                    file_size: 120,
                    status: 120,
                    created_at: 180,
                    message: 300
                };
            }
        },

        // File loading method
        async loadFiles() {
            try {
                const response = await fetch(window.location.pathname, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.files && data.files.data) {
                        this.files = data.files.data;
                    }
                }
            } catch (error) {
                // Handle error silently
            }
        },

        // Sorting methods
        sortBy(column) {
            if (this.sortColumn === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = column;
                this.sortDirection = 'asc';
            }
        },

        toggleViewMode() {
            this.viewMode = this.viewMode === 'grid' ? 'table' : 'grid';
            localStorage.setItem('fileManagerViewMode_{{ $userType }}', this.viewMode);
        },

        previewFile(file) {
            // Open preview modal
            this.$dispatch('open-preview-modal', file);
        },

        async downloadFile(file) {
            try {
                @if($userType === 'employee')
                const url = `/employee/{{ $username }}/file-manager/${file.id}/download`;
                @else
                const url = `/admin/file-manager/${file.id}/download`;
                @endif
                
                // For small files, use fetch + blob approach
                if (file.file_size < 5 * 1024 * 1024) { // Less than 5MB
                    fetch(url, {
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            }
                            return response.blob();
                        })
                        .then(blob => {
                            const downloadUrl = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.style.display = 'none';
                            a.href = downloadUrl;
                            a.download = file.original_filename;
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(downloadUrl);
                            document.body.removeChild(a);
                        })
                        .catch(error => {
                            console.error('Download failed:', error);
                            window.location.href = url;
                        });
                } else {
                    window.location.href = url;
                }
            } catch (error) {
                console.error('Download error:', error);
                alert('Download failed. Please try again.');
            }
        },

        deleteFile(file) {
            console.log('🔍 deleteFile called with:', file);
            this.currentFile = file;
            
            // Simple modal state management
            console.log('🔍 Setting showDeleteModal to true');
            this.showDeleteModal = true;
            this.deleteModalFile = file;
            this.deleteModalTitle = 'Delete File';
            this.deleteModalMessage = `Are you sure you want to delete "${file.original_filename}"? This action cannot be undone.`;
            console.log('🔍 Modal state set:', {
                showDeleteModal: this.showDeleteModal,
                deleteModalTitle: this.deleteModalTitle,
                deleteModalMessage: this.deleteModalMessage
            });
        },
        
        confirmDelete() {
            console.log('🔍 confirmDelete called, isDeleting:', this.isDeleting);
            if (!this.deleteModalFile || this.isDeleting) {
                console.log('🔍 Returning early - no file or already deleting');
                return;
            }
            
            console.log('🔍 Starting delete operation');
            
            // Check if this is a bulk delete or single file delete
            if (this.deleteModalFile.bulk) {
                console.log('🔍 Performing bulk delete');
                this.performBulkDelete()
                    .then(() => {
                        console.log('🔍 Bulk delete successful, closing modal');
                        this.closeDeleteModal();
                    })
                    .catch(error => {
                        console.error('🔍 Bulk delete failed:', error);
                        this.showError(error.message || 'Failed to delete files');
                        // Don't close modal on error, let user retry
                    });
            } else {
                console.log('🔍 Performing single file delete');
                this.performDeleteFile(this.deleteModalFile)
                    .then(() => {
                        console.log('🔍 Single delete successful, closing modal');
                        this.closeDeleteModal();
                    })
                    .catch(error => {
                        console.error('🔍 Single delete failed:', error);
                        this.showError(error.message || 'Failed to delete file');
                        // Don't close modal on error, let user retry
                    });
            }
        },
        
        closeDeleteModal() {
            this.showDeleteModal = false;
            this.deleteModalFile = null;
            this.deleteModalTitle = '';
            this.deleteModalMessage = '';
            this.isDeleting = false;
        },

        // Process Pending Modal Methods
        openProcessPendingModal() {
            console.log('🔍 Opening process pending modal');
            this.showProcessPendingModal = true;
            this.pendingCount = this.getPendingCount();
            this.processingProgress = 0;
            this.processedCount = 0;
            this.totalCount = 0;
            this.processingMessage = '';
            this.processingResults = null;
        },

        closeProcessPendingModal() {
            console.log('🔍 Closing process pending modal');
            this.showProcessPendingModal = false;
            this.isProcessingPending = false;
            this.processingProgress = 0;
            this.processedCount = 0;
            this.totalCount = 0;
            this.processingMessage = '';
            this.processingResults = null;
        },

        confirmProcessPending() {
            console.log('🔍 Confirm process pending called');
            if (this.isProcessingPending || this.pendingCount === 0) {
                console.log('🔍 Returning early - already processing or no pending files');
                return;
            }

            console.log('🔍 Starting process pending operation');
            this.performProcessPending()
                .then((result) => {
                    console.log('🔍 Process pending successful:', result);
                    this.processingResults = result;
                    // Auto-close modal after 3 seconds on success
                    if (result.success) {
                        setTimeout(() => {
                            this.closeProcessPendingModal();
                            // Refresh the file list to show updated statuses
                            this.loadFiles();
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('🔍 Process pending failed:', error);
                    this.processingResults = {
                        success: false,
                        message: error.message || 'Failed to process pending uploads'
                    };
                });
        },

        async performProcessPending() {
            this.isProcessingPending = true;
            this.processingMessage = 'Initializing upload processing...';
            this.totalCount = this.pendingCount;

            try {
                @if($userType === 'employee')
                const response = await fetch(`/employee/{{ $username }}/file-manager/process-pending`, {
                @else
                const response = await fetch('/admin/file-manager/process-pending', {
                @endif
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        // Simulate progress for better UX
                        this.processingMessage = 'Processing uploads...';
                        this.processingProgress = 50;
                        this.processedCount = Math.floor(this.totalCount / 2);

                        // Wait a moment to show progress
                        await new Promise(resolve => setTimeout(resolve, 1000));

                        this.processingProgress = 100;
                        this.processedCount = this.totalCount;
                        this.processingMessage = 'Upload processing completed';

                        return {
                            success: true,
                            message: data.message || `Successfully processed ${data.processed_count || this.totalCount} uploads`,
                            processed_count: data.processed_count || this.totalCount
                        };
                    } else {
                        throw new Error(data.message || 'Failed to process pending uploads');
                    }
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `Failed to process uploads (${response.status})`);
                }
            } catch (error) {
                console.error('Process pending error:', error);
                throw error;
            } finally {
                this.isProcessingPending = false;
            }
        },

        getPendingCount() {
            // Count files that don't have google_drive_file_id (pending uploads)
            return this.files.filter(file => !file.google_drive_file_id).length;
        },

        async performDeleteFile(file) {
            if (!file || !file.id) {
                throw new Error('Invalid file data');
            }

            // Check if file still exists in our local array (might have been deleted already)
            const fileExists = this.files.some(f => f.id === file.id);
            if (!fileExists) {
                console.warn('File no longer exists in local array, skipping delete');
                return; // File already deleted
            }

            this.isDeleting = true;
            console.log('🔍 Starting delete for file:', file.id);

            try {
                @if($userType === 'employee')
                const response = await fetch(`/employee/{{ $username }}/file-manager/${file.id}`, {
                @else
                const response = await fetch(`/admin/file-manager/${file.id}`, {
                @endif
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        console.log('Delete successful for file:', file.id);
                        // Remove file from local array
                        this.files = this.files.filter(f => f.id !== file.id);
                        // Remove from selected files if it was selected
                        this.selectedFiles = this.selectedFiles.filter(id => id !== file.id);
                        this.showSuccess(`File "${file.original_filename}" deleted successfully.`);
                        return true;
                    } else {
                        throw new Error(data.message || 'Failed to delete file.');
                    }
                } else if (response.status === 404) {
                    // File already deleted, treat as success
                    console.warn('File not found (404), treating as already deleted');
                    this.files = this.files.filter(f => f.id !== file.id);
                    this.selectedFiles = this.selectedFiles.filter(id => id !== file.id);
                    this.showSuccess(`File "${file.original_filename}" was already deleted.`);
                    return true;
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `Failed to delete file (${response.status}).`);
                }
            } catch (error) {
                console.error('Delete error:', error);
                this.showError(error.message || 'An error occurred while deleting the file.');
                throw error;
            } finally {
                this.isDeleting = false;
                this.currentFile = null;
                console.log('Delete operation completed for file:', file.id);
            }
        },

        bulkDelete() {
            this.confirmBulkDelete();
        },

        bulkDownload() {
            if (this.selectedFiles.length === 0) {
                this.showError('Please select files to download.');
                return;
            }

            // Create a form and submit it for bulk download
            const form = document.createElement('form');
            form.method = 'POST';
            @if($userType === 'employee')
            form.action = `/employee/{{ $username }}/file-manager/bulk-download`;
            @else
            form.action = '/admin/file-manager/bulk-download';
            @endif

            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);

            // Add selected file IDs
            this.selectedFiles.forEach(fileId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'file_ids[]';
                input.value = fileId;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        },

        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedFiles = this.filteredFiles.map(file => file.id);
            } else {
                this.selectedFiles = [];
            }
        },

        toggleColumn(columnKey) {
            this.visibleColumns[columnKey] = !this.visibleColumns[columnKey];
            this.saveColumnVisibility();
        },

        resetColumns() {
            this.visibleColumns = {
                original_filename: true,
                email: true,
                file_size: true,
                status: true,
                created_at: true,
                message: false
            };
            this.saveColumnVisibility();
        },

        saveColumnVisibility() {
            try {
                localStorage.setItem('fileManagerColumns_{{ $userType }}', JSON.stringify(this.visibleColumns));
            } catch (error) {
                console.error('Error saving column visibility:', error);
            }
        },

        confirmBulkDelete() {
            if (this.selectedFiles.length === 0) {
                this.showError('Please select files to delete.');
                return;
            }

            // Simple modal for bulk delete
            this.showDeleteModal = true;
            this.deleteModalFile = { bulk: true, count: this.selectedFiles.length };
            this.deleteModalTitle = 'Delete Selected Files';
            this.deleteModalMessage = `Are you sure you want to delete ${this.selectedFiles.length} selected files? This action cannot be undone.`;
        },

        async performBulkDelete() {
            if (this.selectedFiles.length === 0) {
                throw new Error('No files selected for deletion');
            }

            this.showBulkProgress('Deleting files', this.selectedFiles.length, 'Preparing to delete files...');

            try {
                @if($userType === 'employee')
                const response = await fetch(`/employee/{{ $username }}/file-manager/bulk-delete`, {
                @else
                const response = await fetch('/admin/file-manager/bulk-delete', {
                @endif
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        file_ids: this.selectedFiles
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.updateBulkProgress(this.selectedFiles.length, 'Files deleted successfully');
                        
                        // Remove deleted files from local array
                        this.files = this.files.filter(f => !this.selectedFiles.includes(f.id));
                        const deletedCount = this.selectedFiles.length;
                        this.selectedFiles = [];
                        
                        // Use a Promise to handle the delayed success message
                        return new Promise((resolve) => {
                            setTimeout(() => {
                                this.hideBulkProgress();
                                this.showSuccess(`${deletedCount} files deleted successfully.`);
                                resolve(true);
                            }, 1000);
                        });
                    } else {
                        this.hideBulkProgress();
                        throw new Error(data.message || 'Failed to delete files.');
                    }
                } else {
                    this.hideBulkProgress();
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || 'Failed to delete files.');
                }
            } catch (error) {
                console.error('Bulk delete error:', error);
                this.hideBulkProgress();
                this.showError(error.message || 'An error occurred while deleting files.');
                throw error; // Re-throw to let the modal handle the error
            }
        },

        clearAllFilters() {
            this.searchQuery = '';
            this.statusFilter = '';
            this.fileTypeFilter = '';
            this.dateFromFilter = '';
            this.dateToFilter = '';
            this.userEmailFilter = '';
            this.fileSizeMinFilter = '';
            this.fileSizeMaxFilter = '';
            this.selectedFiles = [];
        },

        parseFileSize(sizeString) {
            if (!sizeString || typeof sizeString !== 'string') return 0;

            const sizeStr = sizeString.trim().toUpperCase();
            const match = sizeStr.match(/^(\d+(?:\.\d+)?)\s*([KMGT]?B?)$/);

            if (!match) {
                const num = parseFloat(sizeString);
                return isNaN(num) ? 0 : num;
            }

            const number = parseFloat(match[1]);
            const unit = match[2] || 'B';

            const multipliers = {
                'B': 1,
                'KB': 1024,
                'MB': 1024 * 1024,
                'GB': 1024 * 1024 * 1024,
                'TB': 1024 * 1024 * 1024 * 1024,
                'K': 1024,
                'M': 1024 * 1024,
                'G': 1024 * 1024 * 1024,
                'T': 1024 * 1024 * 1024 * 1024
            };

            return Math.floor(number * (multipliers[unit] || 1));
        },

        // User feedback methods
        showSuccess(message, duration = 3000) {
            this.successMessage = message;
            this.showSuccessNotification = true;

            setTimeout(() => {
                this.showSuccessNotification = false;
            }, duration);
        },

        showError(message) {
            this.errorMessage = message;
            this.showErrorModal = true;

            setTimeout(() => {
                this.showErrorModal = false;
            }, 5000);
        },

        // Progress methods
        showBulkProgress(operation, total, message) {
            this.bulkOperationProgress = {
                show: true,
                current: 0,
                total: total,
                operation: operation,
                message: message
            };
        },

        updateBulkProgress(current, message) {
            this.bulkOperationProgress.current = current;
            this.bulkOperationProgress.message = message;
        },

        hideBulkProgress() {
            this.bulkOperationProgress.show = false;
        },

        // Column management methods
        getStoredColumnVisibility() {
            try {
                const stored = localStorage.getItem('fileManagerColumns_{{ $userType }}');
                if (stored) {
                    return JSON.parse(stored);
                }
            } catch (error) {
                console.error('Error loading column visibility:', error);
            }
            
            return {
                original_filename: true,
                email: true,
                file_size: true,
                status: true,
                created_at: true,
                message: false
            };
        },

        getStoredColumnWidths() {
            try {
                const stored = localStorage.getItem('fileManagerColumnWidths_{{ $userType }}');
                if (stored) {
                    return JSON.parse(stored);
                }
            } catch (error) {
                console.error('Error loading column widths:', error);
            }
            
            return {
                original_filename: 300,
                email: 200,
                file_size: 120,
                status: 120,
                created_at: 180,
                message: 300
            };
        },

        setupColumnResizing() {
            // Basic column resizing setup
            document.addEventListener('mousemove', (e) => {
                if (this.isResizing && this.resizingColumn) {
                    const diff = e.clientX - this.startX;
                    const newWidth = Math.max(this.startWidth + diff, 100);
                    this.columnWidths[this.resizingColumn] = newWidth;
                }
            });

            document.addEventListener('mouseup', () => {
                if (this.isResizing) {
                    this.isResizing = false;
                    this.resizingColumn = null;
                    this.saveColumnWidths();
                }
            });
        },

        startColumnResize(event, columnKey) {
            this.isResizing = true;
            this.resizingColumn = columnKey;
            this.startX = event.clientX;
            this.startWidth = this.columnWidths[columnKey] || 200;
            event.preventDefault();
        },

        saveColumnWidths() {
            try {
                localStorage.setItem('fileManagerColumnWidths_{{ $userType }}', JSON.stringify(this.columnWidths));
            } catch (error) {
                console.error('Error saving column widths:', error);
            }
        },

        getTotalTableWidth() {
            return Object.values(this.columnWidths).reduce((sum, width) => sum + width, 200); // 200 for actions column
        },

        getColumnHeaderClass(column) {
            let classes = 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
            if (column.sortable) {
                classes += ' cursor-pointer hover:bg-gray-100';
            }
            return classes;
        },

        getColumnCellClass(column) {
            return 'px-6 py-4 whitespace-nowrap text-sm text-gray-900';
        },

        getColumnStyle(columnKey) {
            const width = this.columnWidths[columnKey] || 200;
            return `width: ${width}px; min-width: ${width}px; max-width: ${width}px;`;
        },

        getCellContent(file, column) {
            switch (column.key) {
                case 'original_filename':
                    return `<span class="font-medium" title="${file.original_filename}">${file.original_filename}</span>`;
                case 'email':
                    return `<span title="${file.email}">${file.email}</span>`;
                case 'file_size':
                    return this.formatBytes(file.file_size);
                case 'status':
                    if (file.google_drive_file_id) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Uploaded</span>';
                    } else {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>';
                    }
                case 'created_at':
                    return this.formatDate(file.created_at);
                case 'message':
                    return `<span title="${file.message || 'No message'}">${file.message || 'No message'}</span>`;
                default:
                    return file[column.key] || '';
            }
        },

        // Utility methods
        formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        },

        getFileExtension(filename) {
            if (!filename) return '';
            const parts = filename.split('.');
            return parts.length > 1 ? parts.pop().toUpperCase() : '';
        }
    }));
});
</script>