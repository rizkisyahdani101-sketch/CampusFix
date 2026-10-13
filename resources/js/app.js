import './bootstrap';

const initializeTechnicianTeamField = () => {
    const form = document.querySelector('[data-admin-user-form]');
    const roleSelect = form?.querySelector('[data-admin-user-role]');
    const teamSection = form?.querySelector('[data-technician-team-section]');
    const teamSelect = teamSection?.querySelector('select');

    if (!roleSelect || !teamSection) return;

    const updateVisibility = () => {
        const isTechnician = roleSelect.value === 'technician';

        teamSection.hidden = !isTechnician;

        if (teamSelect) {
            teamSelect.disabled = !isTechnician;
            teamSelect.required = isTechnician;
        }
    };

    roleSelect.addEventListener('change', updateVisibility);
    updateVisibility();
};

const initializeCampusClock = () => {
    const clock = document.querySelector('[data-campus-clock]');
    const date = document.querySelector('[data-campus-date]');
    const greeting = document.querySelector('[data-campus-greeting]');

    if (!clock && !date && !greeting) return;

    const timeZone = clock?.dataset.timezone || date?.dataset.timezone || greeting?.dataset.timezone || 'Asia/Jakarta';
    const dateFormatter = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone,
    });
    const clockFormatter = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hourCycle: 'h23',
        timeZone,
    });
    const hourFormatter = new Intl.DateTimeFormat('en-US', {
        hour: '2-digit',
        hourCycle: 'h23',
        timeZone,
    });

    const updateCampusTime = () => {
        const now = new Date();
        const hour = Number(hourFormatter.format(now));

        if (clock) {
            clock.textContent = clockFormatter.format(now);
            clock.dateTime = now.toISOString();
        }

        if (date) {
            date.textContent = dateFormatter.format(now);
            date.dateTime = now.toISOString();
        }

        if (greeting) {
            greeting.textContent = hour < 11
                ? 'Selamat pagi'
                : hour < 15
                    ? 'Selamat siang'
                    : hour < 18
                        ? 'Selamat sore'
                        : 'Selamat malam';
        }
    };

    updateCampusTime();
    window.setInterval(updateCampusTime, 1000);
};

const initializeMobileNavigation = () => {
    const frame = document.querySelector('[data-portal-frame]');
    const toggle = frame?.querySelector('[data-nav-toggle]');
    const sidebar = frame?.querySelector('[data-portal-sidebar]');
    const backdrop = frame?.querySelector('[data-nav-backdrop]');

    if (!frame || !toggle || !sidebar) return;

    const closeButton = sidebar.querySelector('[data-nav-close]');
    const desktopQuery = window.matchMedia('(min-width: 901px)');

    // Keep the closed drawer out of the keyboard and screen-reader order on mobile.
    const syncSidebarAvailability = () => {
        sidebar.inert = !desktopQuery.matches && frame.dataset.navOpen !== 'true';
    };

    const closeOnEscape = (event) => {
        if (event.key === 'Escape') setDrawerOpen(false);
    };

    const setDrawerOpen = (open) => {
        const shouldOpen = open === true;
        const wasOpen = frame.dataset.navOpen === 'true';

        frame.dataset.navOpen = shouldOpen ? 'true' : 'false';
        toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        document.documentElement.classList.toggle('cf-nav-locked', shouldOpen);
        syncSidebarAvailability();

        if (shouldOpen) {
            closeButton?.focus();
            document.addEventListener('keydown', closeOnEscape);
        } else {
            document.removeEventListener('keydown', closeOnEscape);

            if (wasOpen) {
                toggle.focus();
            }
        }
    };

    // Reset any drawer state left behind by a cached back/forward navigation.
    document.documentElement.classList.remove('cf-nav-locked');
    frame.dataset.navOpen = 'false';
    toggle.setAttribute('aria-expanded', 'false');
    syncSidebarAvailability();

    toggle.addEventListener('click', () => {
        setDrawerOpen(frame.dataset.navOpen !== 'true');
    });

    closeButton?.addEventListener('click', () => setDrawerOpen(false));
    backdrop?.addEventListener('click', () => setDrawerOpen(false));

    const closeWhenLeavingMobile = (event) => {
        if (event.matches && frame.dataset.navOpen === 'true') {
            setDrawerOpen(false);
        }

        syncSidebarAvailability();
    };

    if (typeof desktopQuery.addEventListener === 'function') {
        desktopQuery.addEventListener('change', closeWhenLeavingMobile);
    } else {
        desktopQuery.addListener(closeWhenLeavingMobile);
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeTechnicianTeamField();
        initializeCampusClock();
        initializeMobileNavigation();
    }, { once: true });
} else {
    initializeTechnicianTeamField();
    initializeCampusClock();
    initializeMobileNavigation();
}
