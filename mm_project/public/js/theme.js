(() => {
    const key = 'membership-theme';
    const system = window.matchMedia('(prefers-color-scheme: dark)');
    let preference = null;
    try {
        const saved = localStorage.getItem(key);
        if (saved === 'light' || saved === 'dark') preference = saved;
    } catch { /* Device preference remains available when storage is blocked. */ }

    const applyTheme = () => {
        const mode = preference ?? (system.matches ? 'dark' : 'light');
        document.documentElement.dataset.theme = mode;
        document.documentElement.style.colorScheme = mode;
        document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
            toggle.checked = mode === 'dark';
        });
    };

    applyTheme();
    system.addEventListener('change', applyTheme);
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-theme-control]').forEach((control) => { control.hidden = false; });
        document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
            toggle.addEventListener('change', () => {
                preference = toggle.checked ? 'dark' : 'light';
                try { localStorage.setItem(key, preference); } catch { /* Keep this page usable without storage. */ }
                applyTheme();
            });
        });
        applyTheme();
    });
    window.addEventListener('storage', (event) => {
        if (event.key !== key && event.key !== null) return;
        preference = event.newValue === 'dark' || event.newValue === 'light' ? event.newValue : null;
        applyTheme();
    });
})();
