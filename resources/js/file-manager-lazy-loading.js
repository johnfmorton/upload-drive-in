/**
 * Performance-optimized lazy loading implementation for file manager
 */
class FileManagerLazyLoader {
    constructor(options = {}) {

        this.container =
            options.container ||
            document.querySelector("[data-lazy-container]");
        this.loadMoreButton =
            options.loadMoreButton ||
            document.querySelector("[data-load-more]");
        this.loadingIndicator =
            options.loadingIndicator ||
            document.querySelector("[data-loading]");
        this.itemsPerPage = options.itemsPerPage || 50; // Increased for better performance
        this.currentPage = 1;
        this.isLoading = false;
        this.hasMoreItems = true;
        this.filters = {};
        this.cache = new Map(); // Cache for loaded pages
        this.preloadThreshold = 2; // Preload when 2 pages from end
        this.virtualScrolling = options.virtualScrolling || false;
        this.visibleRange = { start: 0, end: 50 }; // For virtual scrolling
        this.itemHeight = options.itemHeight || 80; // Estimated item height
        this.bufferSize = 10; // Extra items to render outside viewport

        // Register with coordination module if available
        if (window.fileManagerState) {
            window.fileManagerState.instance = this;
        }

        this.init();
    }

    init() {
        if (this.loadMoreButton) {
            this.loadMoreButton.addEventListener("click", () =>
                this.loadMore()
            );
        }

        // Intersection Observer for infinite scroll
        this.setupIntersectionObserver();

        // Debounced search handler
        this.setupSearchHandler();

        // Performance monitoring
        this.setupPerformanceMonitoring();

        // Virtual scrolling setup if enabled
        if (this.virtualScrolling) {
            this.setupVirtualScrolling();
        }

        // Preload next page when approaching end
        this.setupPreloading();

        // Load initial files if Alpine.js component has empty files array
        this.loadInitialFiles();
    }

    loadInitialFiles() {
        const alpineComponent = this.getAlpineComponent();
        if (alpineComponent) {
            console.log('Alpine.js component found, checking files...');
            if (alpineComponent.files.length === 0) {
                console.log('Alpine.js component has no files, loading initial page...');
                this.loadMore();
            } else {
                console.log('Alpine.js component already has files:', alpineComponent.files.length);
            }
        } else {
            console.warn('Alpine.js component not found, cannot load initial files');
            // Try again after a short delay
            setTimeout(() => {
                const retryComponent = this.getAlpineComponent();
                if (retryComponent && retryComponent.files.length === 0) {
                    console.log('Retry: Alpine.js component found, loading initial page...');
                    this.loadMore();
                }
            }, 500);
        }
    }

    setupIntersectionObserver() {
        if (!window.IntersectionObserver) {
            return; // Fallback to manual load more button
        }

        const sentinel = document.createElement("div");
        sentinel.className = "lazy-load-sentinel";
        sentinel.style.height = "1px";

        if (this.container) {
            this.container.appendChild(sentinel);
        }

        this.observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (
                        entry.isIntersecting &&
                        this.hasMoreItems &&
                        !this.isLoading
                    ) {
                        this.loadMore();
                    }
                });
            },
            {
                rootMargin: "100px", // Start loading 100px before the sentinel comes into view
            }
        );

        this.observer.observe(sentinel);
    }

    setupSearchHandler() {
        const searchInput = document.querySelector("[data-search-input]");
        if (!searchInput) return;

        let searchTimeout;
        searchInput.addEventListener("input", (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.filters.search = e.target.value;
                this.resetAndReload();
            }, 300); // 300ms debounce
        });
    }

    async loadMore() {
        if (this.isLoading || !this.hasMoreItems) {
            return;
        }

        const nextPage = this.currentPage + 1;

        // Check cache first
        if (this.cache.has(nextPage)) {
            const cachedData = this.cache.get(nextPage);
            this.appendFiles(cachedData.files);
            this.currentPage = nextPage;
            this.hasMoreItems = cachedData.hasMore;
            return;
        }

        this.setLoading(true);
        const startTime = performance.now();

        try {
            const response = await this.fetchFiles(nextPage);

            if (response.success) {
                // Cache the response
                this.cache.set(nextPage, {
                    files: response.files.data,
                    hasMore:
                        response.files.has_more_pages ||
                        response.files.next_page_url !== null,
                });

                this.appendFiles(response.files.data);
                this.currentPage = nextPage;
                this.hasMoreItems =
                    response.files.has_more_pages ||
                    response.files.next_page_url !== null;

                if (!this.hasMoreItems && this.loadMoreButton) {
                    this.loadMoreButton.style.display = "none";
                }

                // Preload next page if approaching end
                if (this.hasMoreItems && this.shouldPreload()) {
                    this.preloadNextPage();
                }
            } else {
                this.showError("Failed to load more files");
            }
        } catch (error) {
            console.error("Error loading more files:", error);
            this.showError("Error loading files. Please try again.");
        } finally {
            this.setLoading(false);

            // Performance logging
            const loadTime = performance.now() - startTime;
            if (loadTime > 1000) {
                console.warn(
                    `Slow page load: ${loadTime}ms for page ${nextPage}`
                );
            }
        }
    }

    async fetchFiles(page = 1) {
        const params = new URLSearchParams({
            page: page,
            per_page: this.itemsPerPage,
            ...this.filters,
        });

        const response = await fetch(`${window.location.pathname}?${params}`, {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });

        return await response.json();
    }

    appendFiles(files) {
        if (!files.length) {
            return;
        }

        // Check if Alpine.js component exists and update its files array
        const alpineComponent = this.getAlpineComponent();
        if (alpineComponent) {
            console.log('Updating Alpine.js component with new files:', files);
            // Add new files to the Alpine.js component's files array
            alpineComponent.files.push(...files);
            return;
        }

        // Fallback to DOM manipulation if Alpine.js component is not available
        if (!this.container) {
            return;
        }

        const fragment = document.createDocumentFragment();

        files.forEach((file) => {
            const fileElement = this.createFileElement(file);
            fragment.appendChild(fileElement);
        });

        // Find the insertion point (before the sentinel or load more button)
        const sentinel = this.container.querySelector(".lazy-load-sentinel");
        const insertBefore = sentinel || this.loadMoreButton || null;

        if (insertBefore && insertBefore.parentNode === this.container) {
            this.container.insertBefore(fragment, insertBefore);
        } else {
            this.container.appendChild(fragment);
        }
    }

    createFileElement(file) {
        const template = document.querySelector("[data-file-template]");
        if (!template) {
            console.error("File template not found");
            return document.createElement("div");
        }

        const clone = template.content.cloneNode(true);
        const element = clone.querySelector("[data-file-item]");

        if (element) {
            // Populate file data
            this.populateFileData(element, file);
        }

        return clone;
    }

    populateFileData(element, file) {
        // Update file ID
        element.dataset.fileId = file.id;

        // Update filename
        const filenameEl = element.querySelector("[data-filename]");
        if (filenameEl) {
            filenameEl.textContent = file.original_filename;
        }

        // Update file size
        const sizeEl = element.querySelector("[data-file-size]");
        if (sizeEl) {
            sizeEl.textContent = file.file_size_human;
        }

        // Update upload date
        const dateEl = element.querySelector("[data-upload-date]");
        if (dateEl) {
            const date = new Date(file.created_at);
            dateEl.textContent = date.toLocaleDateString();
        }

        // Update thumbnail
        const thumbnailEl = element.querySelector("[data-thumbnail]");
        if (thumbnailEl && file.thumbnail_url) {
            thumbnailEl.src = file.thumbnail_url;
            thumbnailEl.style.display = "block";
        }

        // Update preview button
        const previewBtn = element.querySelector("[data-preview-btn]");
        if (previewBtn) {
            if (file.can_preview) {
                previewBtn.style.display = "inline-block";
                previewBtn.dataset.previewUrl = file.preview_url;
            } else {
                previewBtn.style.display = "none";
            }
        }

        // Update download link
        const downloadBtn = element.querySelector("[data-download-btn]");
        if (downloadBtn) {
            downloadBtn.href = file.download_url;
        }

        // Update status indicator
        const statusEl = element.querySelector("[data-status]");
        if (statusEl) {
            statusEl.textContent = file.is_pending ? "Pending" : "Completed";
            statusEl.className = file.is_pending
                ? "status-pending"
                : "status-completed";
        }
    }

    setLoading(loading) {
        this.isLoading = loading;

        if (this.loadingIndicator) {
            this.loadingIndicator.style.display = loading ? "block" : "none";
        }

        if (this.loadMoreButton) {
            this.loadMoreButton.disabled = loading;
            this.loadMoreButton.textContent = loading
                ? "Loading..."
                : "Load More";
        }
    }

    showError(message) {
        // Create or update error message element
        let errorEl = document.querySelector("[data-lazy-error]");
        if (!errorEl) {
            errorEl = document.createElement("div");
            errorEl.className = "alert alert-error";
            errorEl.dataset.lazyError = "";

            if (this.container) {
                this.container.appendChild(errorEl);
            }
        }

        errorEl.textContent = message;
        errorEl.style.display = "block";

        // Hide error after 5 seconds
        setTimeout(() => {
            errorEl.style.display = "none";
        }, 5000);
    }

    updateFilters(newFilters) {
        this.filters = { ...this.filters, ...newFilters };
        this.resetAndReload();
    }

    resetAndReload() {
        this.currentPage = 1;
        this.hasMoreItems = true;

        // Clear Alpine.js component files if available
        const alpineComponent = this.getAlpineComponent();
        if (alpineComponent) {
            alpineComponent.files = [];
        }

        // Clear existing items except template
        if (this.container) {
            const items = this.container.querySelectorAll("[data-file-item]");
            items.forEach((item) => item.remove());
        }

        // Load first page
        this.loadMore();
    }

    getAlpineComponent() {
        // Try to get the Alpine.js component instance
        if (this.container) {
            // Check if Alpine.js is available and container has Alpine data
            if (window.Alpine && this.container._x_dataStack) {
                // Find the fileManager component in the data stack
                for (const data of this.container._x_dataStack) {
                    if (data.files !== undefined && data.filteredFiles !== undefined) {
                        return data;
                    }
                }
            } else if (window.Alpine) {
                // Try to get the Alpine data directly
                const alpineData = window.Alpine.$data(this.container);
                if (alpineData && alpineData.files !== undefined) {
                    return alpineData;
                }
            }
            
            // Log debugging info
            console.debug('Alpine component detection:', {
                alpineExists: !!window.Alpine,
                containerHasXData: !!this.container.hasAttribute('x-data'),
                containerHasDataStack: !!(this.container._x_dataStack),
                containerDataStackLength: this.container._x_dataStack ? this.container._x_dataStack.length : 0
            });
        }
        return null;
    }

    setupPerformanceMonitoring() {
        // Monitor memory usage
        if (window.performance && window.performance.memory) {
            setInterval(() => {
                const memory = window.performance.memory;
                if (memory.usedJSHeapSize > 100 * 1024 * 1024) {
                    // 100MB threshold
                    console.warn("High memory usage detected:", {
                        used:
                            Math.round(memory.usedJSHeapSize / 1024 / 1024) +
                            "MB",
                        total:
                            Math.round(memory.totalJSHeapSize / 1024 / 1024) +
                            "MB",
                    });
                    this.optimizeMemory();
                }
            }, 30000); // Check every 30 seconds
        }
    }

    setupVirtualScrolling() {
        if (!this.container) return;

        this.container.addEventListener(
            "scroll",
            this.throttle(() => {
                this.updateVisibleRange();
                this.renderVisibleItems();
            }, 16)
        ); // ~60fps
    }

    setupPreloading() {
        // Preload next page when user is close to the end
        if (this.observer) {
            const preloadSentinel = document.createElement("div");
            preloadSentinel.className = "preload-sentinel";
            preloadSentinel.style.height = "1px";
            preloadSentinel.style.position = "absolute";
            preloadSentinel.style.bottom = "200px"; // Trigger 200px before end

            if (this.container) {
                this.container.appendChild(preloadSentinel);
            }

            const preloadObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting && this.shouldPreload()) {
                        this.preloadNextPage();
                    }
                });
            });

            preloadObserver.observe(preloadSentinel);
        }
    }

    shouldPreload() {
        return (
            this.hasMoreItems &&
            !this.isLoading &&
            !this.cache.has(this.currentPage + 1)
        );
    }

    async preloadNextPage() {
        const nextPage = this.currentPage + 1;

        if (this.cache.has(nextPage)) {
            return;
        }

        try {
            const response = await this.fetchFiles(nextPage);
            if (response.success) {
                this.cache.set(nextPage, {
                    files: response.files.data,
                    hasMore:
                        response.files.has_more_pages ||
                        response.files.next_page_url !== null,
                });
            }
        } catch (error) {
            console.warn("Preload failed for page", nextPage, error);
        }
    }

    updateVisibleRange() {
        if (!this.virtualScrolling || !this.container) return;

        const scrollTop = this.container.scrollTop;
        const containerHeight = this.container.clientHeight;

        const startIndex =
            Math.floor(scrollTop / this.itemHeight) - this.bufferSize;
        const endIndex =
            Math.ceil((scrollTop + containerHeight) / this.itemHeight) +
            this.bufferSize;

        this.visibleRange = {
            start: Math.max(0, startIndex),
            end: Math.min(this.getTotalItemCount(), endIndex),
        };
    }

    renderVisibleItems() {
        if (!this.virtualScrolling) return;

        // Implementation would depend on the specific UI framework
        // This is a placeholder for virtual scrolling logic
        console.debug("Rendering items", this.visibleRange);
    }

    getTotalItemCount() {
        // Return total number of items loaded
        return this.container
            ? this.container.querySelectorAll("[data-file-item]").length
            : 0;
    }

    optimizeMemory() {
        // Clear old cache entries
        const maxCacheSize = 5;
        if (this.cache.size > maxCacheSize) {
            const oldestKeys = Array.from(this.cache.keys()).slice(
                0,
                this.cache.size - maxCacheSize
            );
            oldestKeys.forEach((key) => this.cache.delete(key));
        }

        // Force garbage collection if available
        if (window.gc) {
            window.gc();
        }
    }

    throttle(func, limit) {
        let inThrottle;
        return function () {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => (inThrottle = false), limit);
            }
        };
    }

    // Enhanced cache management
    clearCache() {
        this.cache.clear();
    }

    getCacheStats() {
        return {
            size: this.cache.size,
            pages: Array.from(this.cache.keys()),
            memoryEstimate: this.cache.size * this.itemsPerPage * 1024, // Rough estimate
        };
    }

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }

        if (this.loadMoreButton) {
            this.loadMoreButton.removeEventListener("click", this.loadMore);
        }

        // Clear cache and cleanup
        this.clearCache();

        // Remove event listeners
        if (this.container) {
            this.container.removeEventListener(
                "scroll",
                this.updateVisibleRange
            );
        }
    }
}

// Auto-initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
    const lazyContainer = document.querySelector("[data-lazy-container]");
    
    if (!lazyContainer) {
        console.info("No lazy container found. File manager initialization skipped.");
        return;
    }
    
    // Check if we should use the coordination module
    if (window.initializeFileManager) {
        console.info("Using coordination module to initialize file manager");
        window.initializeFileManager('lazy-loader', {
            container: lazyContainer
        });
        return;
    }
    
    // Fallback to direct initialization
    if (lazyContainer.hasAttribute("x-data")) {
        // Container has Alpine.js, wait for Alpine to initialize then start lazy loader
        console.info("Container has Alpine.js, waiting for initialization...");
        
        const initializeLoader = () => {
            console.info("Initializing FileManagerLazyLoader for Alpine.js integration");
            
            // Check if Alpine is properly initialized on the container
            const checkAlpineInit = () => {
                if (lazyContainer._x_dataStack) {
                    console.info("Alpine.js data stack found, initializing lazy loader");
                    new FileManagerLazyLoader();
                } else {
                    console.info("Alpine.js data stack not found yet, retrying...");
                    setTimeout(checkAlpineInit, 100);
                }
            };
            
            checkAlpineInit();
        };
        
        // Wait for Alpine.js to initialize
        if (window.Alpine) {
            // Alpine is already loaded, wait for next tick
            setTimeout(initializeLoader, 100);
        } else {
            // Wait for Alpine to load
            document.addEventListener('alpine:init', () => {
                setTimeout(initializeLoader, 100);
            });
        }
    } else if (lazyContainer) {
        console.info("Initializing FileManagerLazyLoader for non-Alpine.js container");
        new FileManagerLazyLoader();
    }
});

// Export for manual initialization
window.FileManagerLazyLoader = FileManagerLazyLoader;
