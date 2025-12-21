// assets/js/student_dashboard.js

document.addEventListener('DOMContentLoaded', function () {

    // ================================
    // PAGE NAVIGATION
    // ================================
    const pages = {
        home: 'mainPage',
        history: 'historyPage',
        notifications: 'notificationsPage',
        trash: 'trashPage',
        chat: 'chatPage'
    };

    // Read current view from URL
    let view = 'home';
    try {
        if (window.URLSearchParams) {
            const urlParams = new URLSearchParams(window.location.search);
            view = urlParams.get('view') || 'home';
        }
    } catch (e) {
        console.warn('Could not read view from URL, defaulting to home:', e);
    }

    // Show initial page
    if (pages[view]) {
        showPage(pages[view]);
        if (view === 'notifications') {
            markNotificationsAsSeen();
        }
    } else {
        showPage(pages.home);
    }

    // Navigation button click handlers
    Object.keys(pages).forEach(key => {
        const btn = document.getElementById(key + 'Btn');
        if (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                showPage(pages[key]);
                window.history.pushState({}, '', `student_index.php?view=${key}`);

                if (key === 'notifications') {
                    markNotificationsAsSeen();
                }
            });
        }
    });

    // Function to show page section
    function showPage(pageId) {
        try {
            document.querySelectorAll('.page').forEach(p => {
                p.classList.remove('active');
                p.style.display = 'none';
            });

            const target = document.getElementById(pageId);
            if (target) {
                target.classList.add('active');
                target.style.display = 'block';
            } else {
                console.error('Target page not found:', pageId);
            }
        } catch (e) {
            console.error('Navigation error:', e);
        }
    }

    // ================================
    // NOTIFICATIONS
    // ================================
    function markNotificationsAsSeen() {
        const badge = document.getElementById('notificationBadge');
        if (badge) badge.remove();

        fetch('mark_notifications_seen.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `_csrf=${encodeURIComponent(window.CSRF_TOKEN)}&user_id=${encodeURIComponent(window.USER_ID)}`
        }).catch(err => console.error('Failed to mark notifications as seen:', err));
    }

});
