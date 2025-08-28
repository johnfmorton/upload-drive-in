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
        showConfirmDialog: false,
        confirmDialogTitle: '',
        confirmDialogMessage: '',
        confirmDialogAction: null,
        confirmDialogType: 'info', // 'danger', 'warning', 'info'
        confirmDialogDestructive: false,
        
        // Enhanced modal state management with standardized debugging
        debugMode: window.location.search.includes('modal-debug=true') || localStorage.getItem('modal-debug') === 'true',
        modalDebugInfo: null,
        modalPreventClose: false,
        modalInitialized: false,
        modalCloseTimeout: null,
        modalDebugger: null, // Reference to global modal debugger instance
        
        // Operation states
        isDeleting: false,
        isDownloading: false,
        operationInProgress: '',
        lastOperation: null,
        currentFile: null,
        
        // Progress tracking
        bulkOperationProgress: {
            show: false,
            current: 0,
            total: 0,
            operation: '',
            message: ''
        },
        downloadProgress: {
            show: false,
            percentage: 0,
            filename: ''
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

            // Apply search filter with enhanced multi-term search
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
            // Initialize debug mode and integrate with standardized modal debugger
            if (this.debugMode) {
                document.body.classList.add('modal-debug-enabled');
                console.log('🔍 Modal Debug: File Manager initialized with debug mode enabled');
                
                // Initialize modal debugger integration
                this.initializeModalDebugger();
            }

            // Ensure files is always an array
            if (!Array.isArray(this.files)) {
                this.files = [];
            }

            try {
                this.visibleColumns = this.getStoredColumnVisibility();
                this.columnWidths = this.getStoredColumnWidths();
                this.setupColumnResizing();

                // Register with coordination module if available
                if (window.fileManagerState) {
                    window.fileManagerState.initialized = true;
                    window.fileManagerState.initSource = 'alpine';
                    window.fileManagerState.instance = this;
                }

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
                        'X-Requested-With': 'XMLHttpRequest',
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

        // Force refresh method
        forceRefresh() {
            // Trigger reactivity by creating a new array
            this.files = [...this.files];
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
                
                // For small files, use fetch + blob approach (most reliable)
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
                            // Create a download link with the blob
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
                            // Fallback to direct navigation
                            window.location.href = url;
                        });
                } else {
                    // For larger files, use direct navigation
                    window.location.href = url;
                }
            } catch (error) {
                console.error('Download error:', error);
                alert('Download failed. Please try again.');
            }
        },

        deleteFile(file) {
            this.currentFile = file;
            this.showConfirmation(
                'Delete File',
                `Are you sure you want to delete "${file.original_filename}"? This action cannot be undone.`,
                () => this.performDeleteFile(file),
                'danger'
            );
        },

        async performDeleteFile(file) {
            if (!file || this.isDeleting) return;

            this.isDeleting = true;
            this.lastOperation = {
                type: 'file deletion',
                params: { file: file }
            };

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
                        // Remove file from local array
                        this.files = this.files.filter(f => f.id !== file.id);
                        // Remove from selected files if it was selected
                        this.selectedFiles = this.selectedFiles.filter(id => id !== file.id);
                        this.showSuccess(`File "${file.original_filename}" deleted successfully.`);
                    } else {
                        this.showError(data.message || 'Failed to delete file.');
                    }
                } else {
                    const errorData = await response.json();
                    this.showError(errorData.message || 'Failed to delete file.');
                }
            } catch (error) {
                console.error('Delete error:', error);
                this.showError('An error occurred while deleting the file.');
            } finally {
                this.isDeleting = false;
                this.currentFile = null;
            }
        },

        confirmBulkDelete() {
            if (this.selectedFiles.length === 0) {
                this.showError('Please select files to delete.');
                return;
            }

            this.showConfirmation(
                'Delete Selected Files',
                `Are you sure you want to delete ${this.selectedFiles.length} selected files? This action cannot be undone.`,
                () => this.performBulkDelete(),
                'danger'
            );
        },

        async performBulkDelete() {
            if (this.selectedFiles.length === 0) return;

            this.lastOperation = {
                type: 'bulk delete',
                params: { fileIds: [...this.selectedFiles] }
            };

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
                        
                        setTimeout(() => {
                            this.hideBulkProgress();
                            this.showSuccess(`${deletedCount} files deleted successfully.`);
                        }, 1000);
                    } else {
                        this.hideBulkProgress();
                        this.showError(data.message || 'Failed to delete files.');
                    }
                } else {
                    this.hideBulkProgress();
                    const errorData = await response.json();
                    this.showError(errorData.message || 'Failed to delete files.');
                }
            } catch (error) {
                console.error('Bulk delete error:', error);
                this.hideBulkProgress();
                this.handleApiError(error, 'bulk delete');
            }
        },

        async performBulkDownload() {
            if (this.selectedFiles.length === 0) {
                this.showError('Please select files to download.');
                return;
            }

            this.lastOperation = {
                type: 'bulk download',
                params: { fileIds: [...this.selectedFiles] }
            };

            this.isLoading = true;
            this.isDownloading = true;

            try {
                @if($userType === 'employee')
                const url = `/employee/{{ $username }}/file-manager/bulk-download`;
                @else
                const url = '/admin/file-manager/bulk-download';
                @endif

                // Use XMLHttpRequest for better progress tracking
                const xhr = new XMLHttpRequest();
                xhr.open('POST', url, true);
                xhr.responseType = 'blob';
                
                // Set headers
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.setRequestHeader('Accept', 'application/octet-stream');

                // Handle successful response
                xhr.onload = () => {
                    if (xhr.status === 200) {
                        // Create download link
                        const blob = xhr.response;
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = `files_${new Date().getTime()}.zip`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        
                        this.showSuccess(`${this.selectedFiles.length} files downloaded successfully.`);
                    } else {
                        // Handle error response
                        try {
                            const reader = new FileReader();
                            reader.onload = () => {
                                try {
                                    const errorData = JSON.parse(reader.result);
                                    this.showError(errorData.message || 'Failed to download files.');
                                } catch (e) {
                                    this.showError('Failed to download files. Please try again.');
                                }
                            };
                            reader.readAsText(xhr.response);
                        } catch (e) {
                            this.showError('Failed to download files. Please try again.');
                        }
                    }
                    
                    this.isLoading = false;
                    this.isDownloading = false;
                };

                // Handle download errors
                xhr.onerror = () => {
                    this.showError('Failed to download files. Please try again.');
                    this.isLoading = false;
                    this.isDownloading = false;
                };

                // Send request
                xhr.send(JSON.stringify({
                    file_ids: this.selectedFiles
                }));

            } catch (error) {
                console.error('Bulk download error:', error);
                this.handleApiError(error, 'bulk download');
                this.isLoading = false;
                this.isDownloading = false;
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
                // If it's just a number, treat as bytes
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

        showError(message, isRetryable = false) {
            this.errorMessage = message;
            this.isErrorRetryable = isRetryable;
            this.showErrorModal = true;

            // Auto-hide non-critical errors after 5 seconds
            if (!isRetryable) {
                setTimeout(() => {
                    this.showErrorModal = false;
                }, 5000);
            }
        },

        handleApiError(error, operation = 'operation') {
            console.error(`Error during ${operation}:`, error);

            let message = 'An unexpected error occurred. Please try again.';
            let isRetryable = false;

            if (error.response) {
                // Server responded with an error
                if (error.response.data && error.response.data.message) {
                    message = error.response.data.message;
                }
                isRetryable = error.response.data && error.response.data.is_retryable;
            } else if (error.request) {
                // Request was made but no response
                message = 'No response from server. Please check your connection and try again.';
                isRetryable = true;
            }

            // Store the last operation for retry functionality
            this.lastOperation = {
                type: operation,
                params: this.getOperationParams(operation)
            };

            this.showError(message, isRetryable);
        },

        // Retry functionality
        retryLastOperation() {
            if (!this.lastOperation) {
                this.showError('No operation to retry.');
                return;
            }

            this.showErrorModal = false;

            // Delay slightly to allow modal to close
            setTimeout(() => {
                switch (this.lastOperation.type) {
                    case 'file deletion':
                        if (this.lastOperation.params && this.lastOperation.params.file) {
                            this.performDeleteFile(this.lastOperation.params.file);
                        }
                        break;
                    case 'bulk delete':
                        this.performBulkDelete();
                        break;
                    case 'download':
                        if (this.lastOperation.params && this.lastOperation.params.file) {
                            this.downloadFile(this.lastOperation.params.file);
                        }
                        break;
                    case 'bulk download':
                        this.performBulkDownload();
                        break;
                    default:
                        this.loadFiles();
                        break;
                }
            }, 300);
        },

        getOperationParams(operation) {
            // Store relevant parameters for retry functionality
            switch (operation) {
                case 'file deletion':
                    return { file: this.currentFile };
                case 'download':
                    return { file: this.currentFile };
                case 'bulk delete':
                    return { fileIds: [...this.selectedFiles] };
                case 'bulk download':
                    return { fileIds: [...this.selectedFiles] };
                default:
                    return {};
            }
        },

        // Enhanced modal management methods
        showConfirmation(title, message, action, type = 'info') {
            try {
                // Enhanced debug logging following standardized patterns
                this.logModalDebugInfo('Showing confirmation modal', {
                    title: title,
                    type: type,
                    actionType: typeof action,
                    hasAction: !!action
                });
                
                // Clear any existing timeouts to prevent conflicts
                if (this.modalCloseTimeout) {
                    clearTimeout(this.modalCloseTimeout);
                    this.modalCloseTimeout = null;
                }
                
                // Comprehensive state initialization with enhanced stability
                this.confirmDialogTitle = title;
                this.confirmDialogMessage = message;
                this.confirmDialogAction = action;
                this.confirmDialogType = type;
                this.confirmDialogDestructive = type === 'danger';
                this.modalPreventClose = false;
                this.modalInitialized = true;
                
                // Initialize debug info early for better tracking
                this.modalDebugInfo = {
                    timestamp: Date.now(),
                    title: title,
                    type: type,
                    actionType: typeof action,
                    modalName: 'file-manager-confirm',
                    initializationStep: 'pre-dom-ready'
                };
                
                // Use nextTick to ensure DOM is ready before showing modal
                this.$nextTick(() => {
                    // Verify state is still valid before proceeding
                    if (!this.modalInitialized) {
                        this.logModalDebugInfo('Modal state was reset during initialization, aborting');
                        return;
                    }
                    
                    this.showConfirmDialog = true;
                    
                    // Update debug info to reflect DOM ready state
                    this.modalDebugInfo = {
                        ...this.modalDebugInfo,
                        initializationStep: 'dom-ready',
                        displayedAt: Date.now()
                    };
                    

                    
                    // Set auto-recovery timeout (30 seconds) using standardized pattern
                    this.modalCloseTimeout = setTimeout(() => {
                        if (this.showConfirmDialog && this.modalInitialized) {
                            this.logModalDebugInfo('Modal auto-recovery triggered', {
                                title: title,
                                reason: 'timeout_recovery',
                                timeoutDuration: 30000
                            });
                            this.recoverFromStuckModal();
                        }
                    }, 30000);
                    
                    this.logModalDebugInfo('Confirmation modal displayed successfully', {
                        timeToDisplay: Date.now() - this.modalDebugInfo.timestamp
                    });
                });
            } catch (error) {
                this.logModalError(error, 'showConfirmation');
                
                // Enhanced fallback with proper state cleanup
                try {
                    // Clear any partial state
                    if (this.modalCloseTimeout) {
                        clearTimeout(this.modalCloseTimeout);
                        this.modalCloseTimeout = null;
                    }
                    
                    // Simple fallback modal display
                    this.showConfirmDialog = true;
                    this.modalInitialized = true;
                    this.logModalDebugInfo('Fallback modal display used due to error');
                } catch (fallbackError) {
                    this.logModalError(fallbackError, 'showConfirmation fallback');
                    // Last resort: force modal recovery
                    this.recoverFromStuckModal();
                }
            }
        },

        confirmAction() {
            try {
                this.logModalDebugInfo('Confirming modal action', {
                    hasAction: !!this.confirmDialogAction,
                    actionType: typeof this.confirmDialogAction,
                    modalState: this.showConfirmDialog,
                    preventClose: this.modalPreventClose
                });
                
                // Prevent multiple executions
                if (this.modalPreventClose) {
                    this.logModalDebugInfo('Modal action already in progress, ignoring duplicate call');
                    return;
                }
                
                // Set loading state to prevent duplicate actions
                this.modalPreventClose = true;
                this.isLoading = true;
                
                // Clear any existing timeouts
                if (this.modalCloseTimeout) {
                    clearTimeout(this.modalCloseTimeout);
                    this.modalCloseTimeout = null;
                }
                
                // Enhanced function validation before execution
                if (this.confirmDialogAction) {
                    if (typeof this.confirmDialogAction === 'function') {
                        this.logModalDebugInfo('Executing confirmation action');
                        
                        // Execute the action with error handling
                        try {
                            this.confirmDialogAction();
                            this.logModalDebugInfo('Confirmation action executed successfully');
                        } catch (actionError) {
                            this.logModalError(actionError, 'confirmAction - action execution');
                            // Don't close modal on action error, let user retry
                            this.modalPreventClose = false;
                            this.isLoading = false;
                            return;
                        }
                    } else {
                        this.logModalDebugInfo('Invalid action type - not a function', {
                            actionType: typeof this.confirmDialogAction,
                            actionValue: this.confirmDialogAction
                        });
                        // Still close modal for invalid actions
                    }
                } else {
                    this.logModalDebugInfo('No action to execute - proceeding with modal close');
                }
                
                // Enhanced state cleanup with comprehensive reset
                this.performCompleteStateCleanup('confirmed');
                
                this.logModalDebugInfo('Modal action confirmed and closed successfully');
            } catch (error) {
                this.logModalError(error, 'confirmAction');
                // Force close modal on error using enhanced recovery
                this.recoverFromStuckModal();
            }
        },

        cancelConfirmation() {
            try {
                this.logModalDebugInfo('Cancelling modal confirmation', {
                    modalState: this.showConfirmDialog,
                    hasAction: !!this.confirmDialogAction,
                    preventClose: this.modalPreventClose,
                    isLoading: this.isLoading
                });
                
                // Prevent multiple cancellations
                if (!this.showConfirmDialog && !this.modalPreventClose) {
                    this.logModalDebugInfo('Modal already closed, ignoring duplicate cancel call');
                    return;
                }
                
                // Clear any existing timeouts immediately
                if (this.modalCloseTimeout) {
                    clearTimeout(this.modalCloseTimeout);
                    this.modalCloseTimeout = null;
                }
                
                // Enhanced complete state reset with validation
                this.performCompleteStateCleanup('cancelled');
                
                // Additional cleanup for bulk operations if in progress
                if (this.bulkOperationProgress && this.bulkOperationProgress.show) {
                    this.logModalDebugInfo('Cancelling bulk operation in progress');
                    this.bulkOperationProgress = {
                        show: false,
                        current: 0,
                        total: 0,
                        message: '',
                        operation: null
                    };
                }
                
                // Reset any loading states
                this.isLoading = false;
                
                // Clear selected files if this was a bulk operation
                if (this.selectedFiles && this.selectedFiles.length > 0) {
                    this.logModalDebugInfo('Clearing selected files after cancel');
                    this.selectedFiles = [];
                }
                
                this.logModalDebugInfo('Modal cancelled and state completely reset');
            } catch (error) {
                this.logModalError(error, 'cancelConfirmation');
                // Force recovery on error using enhanced recovery
                this.recoverFromStuckModal();
            }
        },

        performCompleteStateCleanup(closeReason = 'unknown') {
            try {
                this.logModalDebugInfo('Performing complete state cleanup', {
                    closeReason: closeReason,
                    currentState: {
                        showConfirmDialog: this.showConfirmDialog,
                        modalPreventClose: this.modalPreventClose,
                        isLoading: this.isLoading,
                        hasAction: !!this.confirmDialogAction
                    }
                });
                
                // Reset all modal state properties
                this.showConfirmDialog = false;
                this.modalPreventClose = false;
                this.modalInitialized = false;
                this.isLoading = false;
                
                // Clear modal content and action
                this.confirmDialogAction = null;
                this.confirmDialogTitle = '';
                this.confirmDialogMessage = '';
                this.confirmDialogType = 'info';
                this.confirmDialogDestructive = false;
                
                // Clear any timeouts
                if (this.modalCloseTimeout) {
                    clearTimeout(this.modalCloseTimeout);
                    this.modalCloseTimeout = null;
                }
                

                
                // Update debug info with cleanup details
                this.modalDebugInfo = {
                    ...this.modalDebugInfo,
                    closeReason: closeReason,
                    closedAt: Date.now(),
                    cleanupPerformed: true
                };
                
                this.logModalDebugInfo('Complete state cleanup finished', {
                    closeReason: closeReason,
                    finalState: {
                        showConfirmDialog: this.showConfirmDialog,
                        modalPreventClose: this.modalPreventClose,
                        isLoading: this.isLoading
                    }
                });
            } catch (error) {
                this.logModalError(error, 'performCompleteStateCleanup');
                // Fallback to basic cleanup
                this.showConfirmDialog = false;
                this.modalPreventClose = false;
                this.confirmDialogAction = null;
                this.isLoading = false;
            }
        },

        handleBackgroundClick(event) {
            // Enhanced debug logging following standardized patterns
            this.logModalDebugInfo('Backdrop click detected', {
                modalName: 'file-manager-confirm',
                eventTarget: event.target.tagName,
                currentTarget: event.currentTarget.tagName,
                targetMatches: event.target === event.currentTarget,
                modalPreventClose: this.modalPreventClose,
                eventType: event.type,
                coordinates: {
                    clientX: event.clientX,
                    clientY: event.clientY
                }
            });

            // Only close if clicking directly on the background, not on child elements
            // This prevents accidental closes when clicking on modal content
            if (event.target === event.currentTarget && !this.modalPreventClose) {
                // Prevent event propagation to avoid conflicts with other click handlers
                event.preventDefault();
                event.stopPropagation();
                
                this.logModalDebugInfo('Closing modal via backdrop click', {
                    reason: 'backdrop_click',
                    preventClose: this.modalPreventClose
                });
                
                this.cancelConfirmation();
            } else {
                this.logModalDebugInfo('Backdrop click ignored', {
                    reason: event.target !== event.currentTarget ? 'clicked_on_child_element' : 'modal_prevent_close_enabled',
                    preventClose: this.modalPreventClose,
                    targetMatches: event.target === event.currentTarget
                });
            }
        },

        // Progress modal methods
        showBulkProgress(operation, total, message = '') {
            this.bulkOperationProgress = {
                show: true,
                current: 0,
                total: total,
                operation: operation,
                message: message
            };
        },

        updateBulkProgress(current, message = '') {
            this.bulkOperationProgress.current = current;
            if (message) {
                this.bulkOperationProgress.message = message;
            }
        },

        hideBulkProgress() {
            this.bulkOperationProgress.show = false;
        },

        showDownloadProgress(filename) {
            this.downloadProgress = {
                show: true,
                percentage: 0,
                filename: filename
            };
        },

        updateDownloadProgress(percentage) {
            this.downloadProgress.percentage = Math.min(100, Math.max(0, percentage));
        },

        hideDownloadProgress() {
            this.downloadProgress.show = false;
        },

        // Column management
        toggleColumn(columnKey) {
            this.visibleColumns[columnKey] = !this.visibleColumns[columnKey];
            this.saveColumnPreferences();
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
            this.saveColumnPreferences();
        },

        saveColumnPreferences() {
            localStorage.setItem('fileManagerColumnVisibility_{{ $userType }}', JSON.stringify(this.visibleColumns));
            localStorage.setItem('fileManagerColumnWidths_{{ $userType }}', JSON.stringify(this.columnWidths));
        },

        getStoredColumnVisibility() {
            const stored = localStorage.getItem('fileManagerColumnVisibility_{{ $userType }}');
            return stored ? JSON.parse(stored) : {
                original_filename: true,
                email: true,
                file_size: true,
                status: true,
                created_at: true,
                message: false
            };
        },

        getStoredColumnWidths() {
            const stored = localStorage.getItem('fileManagerColumnWidths_{{ $userType }}');
            return stored ? JSON.parse(stored) : {
                original_filename: 300,
                email: 200,
                file_size: 120,
                status: 120,
                created_at: 180,
                message: 300
            };
        },

        setupColumnResizing() {
            // Column resizing functionality
            document.addEventListener('mousedown', (e) => {
                if (e.target.classList.contains('column-resizer')) {
                    this.isResizing = true;
                    this.resizingColumn = e.target.dataset.column;
                    this.startX = e.clientX;
                    this.startWidth = parseInt(this.columnWidths[this.resizingColumn] || 150);
                    e.preventDefault();
                }
            });

            document.addEventListener('mousemove', (e) => {
                if (this.isResizing && this.resizingColumn) {
                    const diff = e.clientX - this.startX;
                    const newWidth = Math.max(100, this.startWidth + diff);
                    this.columnWidths[this.resizingColumn] = newWidth;
                    this.saveColumnPreferences();
                }
            });

            document.addEventListener('mouseup', () => {
                this.isResizing = false;
                this.resizingColumn = null;
            });
        },

        getTotalTableWidth() {
            return this.visibleColumnsList.reduce((total, column) => {
                return total + (this.columnWidths[column.key] || column.defaultWidth || 150);
            }, 0);
        },

        // Modal debugging methods
        initializeModalDebugger() {
            try {
                // Check if global modal debugger is available
                if (window.modalDebugger) {
                    this.modalDebugger = window.modalDebugger;
                    console.log('🔍 Modal Debug: Connected to global modal debugger');
                    
                    // Enable debugging features
                    this.modalDebugger.enableDebugging();
                    
                    // Start observing modal changes for file manager modals
                    this.observeFileManagerModalChanges();
                    
                    // Log initial state
                    this.logModalDebugInfo('File Manager modal debugger initialized');
                } else {
                    console.warn('🔍 Modal Debug: Global modal debugger not available, using fallback debugging');
                    this.initializeFallbackDebugger();
                }
            } catch (error) {
                console.error('🔍 Modal Debug: Error initializing modal debugger:', error);
                this.initializeFallbackDebugger();
            }
        },

        initializeFallbackDebugger() {
            // Fallback debugging when global debugger is not available
            console.log('🔍 Modal Debug: Using fallback debugging for file manager modals');
            
            // Add basic debug styles
            if (!document.getElementById('file-manager-modal-debug-styles')) {
                const style = document.createElement('style');
                style.id = 'file-manager-modal-debug-styles';
                style.textContent = `
                    .file-manager-modal-debug {
                        border: 2px dashed rgba(255, 0, 0, 0.5) !important;
                    }
                    .file-manager-modal-debug::before {
                        content: "FILE MANAGER MODAL DEBUG";
                        position: absolute;
                        top: -25px;
                        left: 0;
                        background: rgba(255, 0, 0, 0.8);
                        color: white;
                        padding: 2px 6px;
                        font-size: 10px;
                        font-weight: bold;
                        border-radius: 3px;
                        z-index: 99999;
                    }
                `;
                document.head.appendChild(style);
            }
        },

        observeFileManagerModalChanges() {
            // Observe changes to file manager modals specifically
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && 
                        (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                        const target = mutation.target;
                        
                        // Check if this is a file manager modal
                        if (target.hasAttribute('data-modal-name') && 
                            target.dataset.modalName.includes('file-manager')) {
                            
                            const computedStyle = getComputedStyle(target);
                            this.logModalDebugInfo('File Manager modal state changed', {
                                modalName: target.dataset.modalName,
                                modalType: target.dataset.modalType,
                                zIndex: computedStyle.zIndex,
                                display: computedStyle.display,
                                visibility: computedStyle.visibility
                            });
                        }
                    }
                });
            });

            // Start observing
            observer.observe(document.body, {
                attributes: true,
                subtree: true,
                attributeFilter: ['style', 'class']
            });
        },

        logModalDebugInfo(message, data = {}) {
            if (this.debugMode) {
                console.log(`🔍 File Manager Modal Debug: ${message}`, {
                    timestamp: new Date().toISOString(),
                    modalState: {
                        showConfirmDialog: this.showConfirmDialog,
                        modalInitialized: this.modalInitialized,
                        modalPreventClose: this.modalPreventClose
                    },
                    debugInfo: this.modalDebugInfo,
                    ...data
                });
            }
        },

        logModalError(error, context) {
            console.error('Modal Error:', {
                error: error,
                context: context,
                modalState: {
                    showConfirmDialog: this.showConfirmDialog,
                    modalInitialized: this.modalInitialized,
                    modalPreventClose: this.modalPreventClose,
                    debugInfo: this.modalDebugInfo
                },
                timestamp: Date.now()
            });
        },





        recoverFromStuckModal() {
            // Enhanced error recovery method using standardized patterns
            this.logModalDebugInfo('Attempting modal recovery', {
                reason: 'stuck_modal_recovery',
                modalState: this.debugModal()
            });
            
            try {
                // Force close all modals
                this.showConfirmDialog = false;
                this.bulkOperationProgress.show = false;
                this.downloadProgress.show = false;
                
                // Reset all modal state
                this.modalPreventClose = false;
                this.modalInitialized = false;
                this.isLoading = false;
                this.confirmDialogAction = null;
                
                // Clear timeouts
                if (this.modalCloseTimeout) {
                    clearTimeout(this.modalCloseTimeout);
                    this.modalCloseTimeout = null;
                }
                
                // Remove debug classes
                if (this.debugMode) {
                    this.removeDebugClasses();
                }
                
                this.logModalDebugInfo('Modal recovery completed');
            } catch (error) {
                this.logModalError(error, 'recoverFromStuckModal');
            }
        },

        debugModal() {
            return {
                state: this.showConfirmDialog,
                initialized: this.modalInitialized,
                preventClose: this.modalPreventClose,
                debugInfo: this.modalDebugInfo,
                hasAction: !!this.confirmDialogAction,
                timestamp: Date.now()
            };
        },

        getThumbnailUrl(file) {
            // Add cache-busting parameter based on file's updated timestamp to prevent thumbnail mix-ups
            const timestamp = file.updated_at ? new Date(file.updated_at).getTime() : Date.now();
            return `/files/${file.id}/thumbnail?v=${timestamp}`;
        },

        getShowUrl(file) {
            @if($userType === 'employee')
            return `/employee/{{ $username }}/file-manager/${file.id}`;
            @else
            return `/admin/file-manager/${file.id}`;
            @endif
        },

        // Utility methods
        formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        formatDate(dateString) {
            const options = {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            };
            return new Date(dateString).toLocaleString(undefined, options);
        },

        truncateText(text, length) {
            if (!text) return '';
            return text.length > length ? text.substring(0, length) + '...' : text;
        },

        getFileExtension(filename) {
            return filename.split('.').pop().toUpperCase();
        },

        // Table-specific methods
        getCellContent(file, column) {
            const value = file[column.key];
            
            switch (column.key) {
                case 'original_filename':
                    return `<div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8">
                            <div class="h-8 w-8 rounded bg-gray-100 flex items-center justify-center text-xs font-medium text-gray-600">
                                ${this.getFileExtension(file.original_filename)}
                            </div>
                        </div>
                        <div class="ml-3 w-full">
                            <div class="text-sm font-medium text-gray-900 truncate" title="${file.original_filename}">
                                ${this.truncateText(file.original_filename, 40)}
                            </div>
                        </div>
                    </div>`;
                    
                case 'email':
                    return `<div class="text-sm text-gray-900">${value || 'N/A'}</div>`;
                    
                case 'file_size':
                    return `<div class="text-sm text-gray-900">${this.formatBytes(value || 0)}</div>`;
                    
                case 'status':
                    const isUploaded = file.google_drive_file_id;
                    return `<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        isUploaded 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-yellow-100 text-yellow-800'
                    }">
                        ${isUploaded ? 'Uploaded' : 'Pending'}
                    </span>`;
                    
                case 'created_at':
                    return `<div class="text-sm text-gray-900">${this.formatDate(value)}</div>`;
                    
                case 'message':
                    return `<div class="text-sm text-gray-500" title="${value || ''}">
                        ${this.truncateText(value || 'No message', 50)}
                    </div>`;
                    
                default:
                    return `<div class="text-sm text-gray-900">${value || ''}</div>`;
            }
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

        getTotalTableWidth() {
            const visibleColumns = this.visibleColumnsList;
            const totalWidth = visibleColumns.reduce((sum, column) => {
                return sum + (this.columnWidths[column.key] || 200);
            }, 0);
            
            // Add width for selection column (80px) and actions column (200px)
            return totalWidth + 80 + 200;
        },

        // Column resizing methods
        startColumnResize(event, columnKey) {
            event.preventDefault();
            this.isResizing = true;
            this.resizingColumn = columnKey;
            this.startX = event.clientX;
            this.startWidth = this.columnWidths[columnKey] || 200;
            
            document.addEventListener('mousemove', this.handleColumnResize.bind(this));
            document.addEventListener('mouseup', this.endColumnResize.bind(this));
            document.body.style.cursor = 'col-resize';
        },

        handleColumnResize(event) {
            if (!this.isResizing || !this.resizingColumn) return;
            
            const diff = event.clientX - this.startX;
            const newWidth = Math.max(100, this.startWidth + diff); // Minimum width of 100px
            
            this.columnWidths[this.resizingColumn] = newWidth;
        },

        endColumnResize() {
            if (this.isResizing) {
                this.isResizing = false;
                this.resizingColumn = null;
                
                document.removeEventListener('mousemove', this.handleColumnResize.bind(this));
                document.removeEventListener('mouseup', this.endColumnResize.bind(this));
                document.body.style.cursor = '';
                
                // Save column widths to localStorage
                localStorage.setItem('fileManagerColumnWidths_{{ $userType }}', JSON.stringify(this.columnWidths));
            }
        },

        setupColumnResizing() {
            // Initialize column resizing event listeners
            // This is called during init()
        }
    }));
});
</script>