/**
 * Shared Header Configuration
 * Include this BEFORE loading the shared header JavaScript
 */

window.sharedHeaderConfig = {
    // Logo configuration
    logoUrl: '/apps/auth/assets/images/veerless-logo-sunrise-rgb-1920px-w-144ppi.png',
    logoText: '',
    logoLink: '/apps/',
    
    // Authentication URLs
    loginUrl: '/apps/auth/login.php',
    logoutUrl: '/apps/auth/logout.php',
    ssoCheckUrl: '/apps/auth/api/auth/check.php', // The endpoint we just created
    
    // Default avatar if user has none
    defaultAvatar: 'https://ui-avatars.com/api/?name=User&background=667eea&color=fff&size=128',
    
    // Custom event handlers
    onLogin: () => {
        // Redirect to login page with return URL
        const returnTo = encodeURIComponent(window.location.pathname + window.location.search);
        window.location.href = `/apps/auth/login.php?return_to=${returnTo}`;
    },
    
    onLogout: async () => {
        // Call your logout endpoint
        try {
            await fetch('/apps/auth/logout.php', { 
                method: 'GET',
                credentials: 'include' // Important for cookies
            });
        } catch (e) {
            console.error('Logout error:', e);
        }
        // Redirect to home
        window.location.href = '/apps/';
    },
    
    onAvatarClick: (userData) => {
        // Handle avatar click - could go to profile page or show menu
        console.log('User clicked avatar:', userData);
        // Example: window.location.href = '/apps/auth/profile.php';
    }
};
