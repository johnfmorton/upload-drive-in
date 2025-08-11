@props([
    'userType' => 'admin', // 'admin' or 'employee'
    'username' => null, // Required for employee routes
])

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('{{ $userType }}FileManager', (initialFiles, initialStatistics = {}) => ({
        // Data
        files: initialFiles || [],
        selectedFiles: [],
        selectAll: false,
        searchQuery: '',
        statusFilter: '',
        fileTypeFilter: '',
        viewMode: localStorage.getItem('{{ $userType }}FileManagerViewMode') || 'grid',
        
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
            minWidth: 200
        }, {
            key: 'email',
            label: 'From',
            sortable: true,
            minWidth: 150
        }, {
            key: 'file_size',
            label: 'Size',
            sortable: true,
            minWidth: 100
        }, {
            key: 'status',
            label: 'Status',
            sortable: false,
            minWidth: 120
        }, {
            key: 'created_at',
            label: 'Date',
            sortable: true,
            minWidth: 150
        }, {
            key: 'message',
            label: 'Message',
            sortable: false,
            minWidth: 200
        }],
        visibleColumns: {},
        columnWidths: {},
        
        // Statistics
        statistics: initialStatistics,

        // Computed
        get filteredFiles() {
            let filtered = this.files;

            // Search filter
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter(file => 
                    file.original_filename.toLowerCase().includes(query) ||
                    file.email.toLowerCase().includes(query) ||
                    (file.message && file.message.toLowerCase().includes(query))
                );
            }

            // Status filter
            if (this.statusFilter) {
                if (this.statusFilter === 'uploaded') {
                    filtered = filtered.filter(file => file.google_drive_file_id);
                } else if (this.statusFilter === 'pending') {
                    filtered = filtered.filter(file => !file.google_drive_file_id);
                }
            }

            // File type filter
            if (this.fileTypeFilter) {
                filtered = filtered.filter(file => {
                    const mimeType = file.mime_type || '';
                    switch (this.fileTypeFilter) {
                        case 'image': return mimeType.startsWith('image/');
                        case 'document': return mimeType.includes('pdf') || mimeType.includes('document') || mimeType.includes('word') || mimeType.includes('text');
                        case 'video': return mimeType.startsWith('video/');
                        case 'audio': return mimeType.startsWith('audio/');
                        case 'archive': return mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('tar');
                        default: return true;
                    }
                });
            }

            // Advanced filters
            if (this.dateFromFilter) {
                filtered = filtered.filter(file => new Date(file.created_at) >= new Date(this.dateFromFilter));
            }
            if (this.dateToFilter) {
                filtered = filtered.filter(file => new Date(file.created_at) <= new Date(this.dateToFilter));
            }
            if (this.userEmailFilter) {
                filtered = filtered.filter(file => file.email.toLowerCase().includes(this.userEmailFilter.toLowerCase()));
            }

            return filtered;
        },

        get activeFiltersCount() {
            let count = 0;
            if (this.searchQuery) count++;
            if (this.statusFilter) count++;
            if (this.fileTypeFilter) count++;
            if (this.dateFromFilter) count++;
            if (this.dateToFilter) count++;
            if (this.userEmailFilter) count++;
            if (this.fileSizeMinFilter) count++;
            if (this.fileSizeMaxFilter) count++;
            return count;
        },

        get visibleColumnsList() {
            return this.availableColumns.filter(column => this.visibleColumns[column.key]);
        },

        // Methods
        init() {
            this.$watch('selectedFiles', () => {
                this.selectAll = this.selectedFiles.length === this.filteredFiles.length && this.filteredFiles.length > 0;
            });

            // Initialize column visibility
            this.visibleColumns = this.getStoredColumnVisibility();
            this.columnWidths = this.getStoredColumnWidths();
        },

        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedFiles = this.filteredFiles.map(file => file.id);
            } else {
                this.selectedFiles = [];
            }
        },

        toggleViewMode() {
            this.viewMode = this.viewMode === 'grid' ? 'table' : 'grid';
            localStorage.setItem('{{ $userType }}FileManagerViewMode', this.viewMode);
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
                    fetch(url)
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

        async deleteFile(file) {
            if (!confirm(`Are you sure you want to delete "${file.original_filename}"?`)) {
                return;
            }

            try {
                @if($userType === 'employee')
                const response = await fetch(`/employee/{{ $username }}/file-manager/${file.id}`, {
                @else
                const response = await fetch(`/admin/file-manager/${file.id}`, {
                @endif
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                });

                if (response.ok) {
                    // Remove file from local array
                    this.files = this.files.filter(f => f.id !== file.id);
                    // Remove from selected files if it was selected
                    this.selectedFiles = this.selectedFiles.filter(id => id !== file.id);
                    alert('File deleted successfully.');
                } else {
                    throw new Error('Delete failed');
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('Failed to delete file. Please try again.');
            }
        },

        async bulkDelete() {
            if (this.selectedFiles.length === 0) return;
            
            if (!confirm(`Are you sure you want to delete ${this.selectedFiles.length} selected files?`)) {
                return;
            }

            try {
                @if($userType === 'employee')
                const response = await fetch(`/employee/{{ $username }}/file-manager/bulk-delete`, {
                @else
                const response = await fetch('/admin/file-manager/bulk-delete', {
                @endif
                    method: 'POST',
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
                    // Remove deleted files from local array
                    this.files = this.files.filter(f => !this.selectedFiles.includes(f.id));
                    this.selectedFiles = [];
                    alert('Files deleted successfully.');
                } else {
                    throw new Error('Bulk delete failed');
                }
            } catch (error) {
                console.error('Bulk delete error:', error);
                alert('Failed to delete files. Please try again.');
            }
        },

        async bulkDownload() {
            if (this.selectedFiles.length === 0) return;

            try {
                @if($userType === 'employee')
                const url = `/employee/{{ $username }}/file-manager/bulk-download`;
                @else
                const url = '/admin/file-manager/bulk-download';
                @endif

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.style.display = 'none';

                // Add CSRF token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                form.appendChild(csrfInput);

                // Add file IDs
                const fileIdsInput = document.createElement('input');
                fileIdsInput.type = 'hidden';
                fileIdsInput.name = 'file_ids';
                fileIdsInput.value = JSON.stringify(this.selectedFiles);
                form.appendChild(fileIdsInput);

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            } catch (error) {
                console.error('Bulk download error:', error);
                alert('Failed to download files. Please try again.');
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
            this.showAdvancedFilters = false;
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
            localStorage.setItem('{{ $userType }}FileManagerColumnVisibility', JSON.stringify(this.visibleColumns));
            localStorage.setItem('{{ $userType }}FileManagerColumnWidths', JSON.stringify(this.columnWidths));
        },

        getStoredColumnVisibility() {
            const stored = localStorage.getItem('{{ $userType }}FileManagerColumnVisibility');
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
            const stored = localStorage.getItem('{{ $userType }}FileManagerColumnWidths');
            return stored ? JSON.parse(stored) : {};
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
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString();
        },

        truncateText(text, length) {
            if (!text) return '';
            return text.length > length ? text.substring(0, length) + '...' : text;
        },

        getFileExtension(filename) {
            return filename.split('.').pop().toUpperCase();
        }
    }));
});
</script>