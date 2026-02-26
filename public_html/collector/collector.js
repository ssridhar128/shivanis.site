let sessionId = sessionStorage.getItem('session_id');
if (!sessionId) {
    sessionId = 'session-' + Math.random().toString(36).substr(2, 9);
    sessionStorage.setItem('session_id', sessionId);
}

const ENDPOINT = 'https://collector.shivanis.site/api/log';

// STATIC DATA
window.addEventListener('load', () => {
    const staticData = {
        type: 'static',
        userAgent: navigator.userAgent,
        language: navigator.language,
        cookiesEnabled: navigator.cookieEnabled,
        jsEnabled: true,
        imagesEnabled: checkImagesEnabled(),
        cssEnabled: checkCSSEnabled(),
        screenDim: `${window.screen.width}x${window.screen.height}`,
        windowDim: `${window.innerWidth}x${window.innerHeight}`,
        connection: navigator.connection ? navigator.connection.effectiveType : 'unknown',
        sessionId: sessionId,
        currentPage: window.location.pathname
    };

    sendData(staticData);
    collectPerformance();
});

function checkImagesEnabled() {
    const img = new Image();
    img.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";
    return (img.complete && img.naturalWidth !== 0);
}

function checkCSSEnabled() {
    const testDiv = document.createElement('div');
    testDiv.id = 'css-test';
    testDiv.style.display = 'none';
    document.body.appendChild(testDiv);
    const isEnabled = getComputedStyle(testDiv).display === 'none';
    document.body.removeChild(testDiv);
    return isEnabled;
}

// PERFORMANCE DATA
function collectPerformance() {
    setTimeout(() => {
        const [perf] = performance.getEntriesByType('navigation');
        if (perf) {
            const perfData = {
                type: 'performance',
                timingObject: perf,
                startLoad: perf.startTime,
                endLoad: perf.loadEventEnd,
                totalLoadTime: perf.loadEventEnd - perf.startTime,
                sessionId: sessionId
            };
            sendData(perfData);
        }
    }, 100);
}

// ACTIVITY DATA
window.onerror = (message, source, lineno, colno) => {
    sendData({ type: 'activity', event: 'error', data: { message, source, lineno, colno }, sessionId: sessionId });
};

document.addEventListener('click', (e) => { 
    sendData({ type: 'activity', event: 'click', button: e.button, coords: { x: e.clientX, y: e.clientY }, sessionId }); 
    resetIdle(); 
});
document.addEventListener('scroll', () => { 
    sendData({ type: 'activity', event: 'scroll', coords: { x: window.scrollX, y: window.scrollY }, sessionId }); 
    resetIdle(); 
});
document.addEventListener('keydown', (e) => { 
    sendData({ type: 'activity', event: 'keydown', key: e.key, sessionId }); 
    resetIdle(); 
});
document.addEventListener('keyup', (e) => { 
    sendData({ type: 'activity', event: 'keyup', key: e.key, sessionId }); 
    resetIdle(); 
});


let mouseThrottle = false;
document.addEventListener('mousemove', (e) => {
    if (!mouseThrottle) {
        mouseThrottle = true;
        setTimeout(() => {
            sendData({ type: 'activity', event: 'mousemove', coords: { x: e.clientX, y: e.clientY }, sessionId });
            mouseThrottle = false;
        }, 250);
    }
});

let lastActivity = Date.now();
function resetIdle() {
    const now = Date.now();
    const duration = now - lastActivity;
    if (duration >= 2000) {
        sendData({
            type: 'activity',
            event: 'idle_break',
            idleDuration: duration,
            breakEndedAt: now,
            sessionId: sessionId
        });
    }
    lastActivity = now;
}

window.addEventListener('pageshow', () => {
    sendData({ type: 'activity', event: 'page_enter', timestamp: Date.now(), sessionId });
});
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') {
        sendData({ type: 'activity', event: 'page_exit', timestamp: Date.now(), sessionId });
    }
});

function sendData(data) {
    const blob = new Blob([JSON.stringify(data)], { type: 'application/json' });
    if (navigator.sendBeacon) { navigator.sendBeacon(ENDPOINT, blob); }
    else { fetch(ENDPOINT, { method: 'POST', body: blob, keepalive: true }); }
}