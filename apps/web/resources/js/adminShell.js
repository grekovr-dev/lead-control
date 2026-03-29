export default function adminShell() {
    return {
        sidebarOpen: typeof window !== 'undefined' ? window.innerWidth >= 1024 : false,
        sidebarCollapsed: false,

        init() {
            this.sidebarCollapsed = this.readCollapsedPreference();
            this.syncViewport();
        },

        syncViewport() {
            if (this.isDesktop()) {
                this.sidebarOpen = true;
                return;
            }

            this.sidebarOpen = false;
        },

        isDesktop() {
            return window.matchMedia('(min-width: 1024px)').matches;
        },

        showSidebarText() {
            return !this.isDesktop() || !this.sidebarCollapsed;
        },

        sidebarDesktopWidth() {
            return this.sidebarCollapsed ? '5rem' : '16rem';
        },

        openSidebar() {
            this.sidebarOpen = true;
        },

        closeSidebar() {
            if (this.isDesktop()) {
                return;
            }

            this.sidebarOpen = false;
        },

        toggleDesktopSidebar() {
            if (!this.isDesktop()) {
                return;
            }

            this.sidebarCollapsed = !this.sidebarCollapsed;
            this.persistCollapsedPreference();
        },

        readCollapsedPreference() {
            try {
                return window.localStorage.getItem('admin-sidebar-collapsed') === '1';
            } catch {
                return false;
            }
        },

        persistCollapsedPreference() {
            try {
                window.localStorage.setItem('admin-sidebar-collapsed', this.sidebarCollapsed ? '1' : '0');
            } catch {
                // Ignore storage failures and keep the current UI state in memory.
            }
        },
    };
}
