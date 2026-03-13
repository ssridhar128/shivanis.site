const CHART_COLORS = {
    primary: '#0ea5e9',
    secondary: '#f97316',
    teal: '#14b8a6',
    amber: '#f59e0b',
    rose: '#f43f5e',
    indigo: '#6366f1',
    violet: '#8b5cf6',
    grid: '#374151',
    text: '#9ca3af'
};

function parseDim(str) {
    if (!str || typeof str !== 'string') return null;
    const parts = str.split('x').map(Number);
    return parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1]) ? { w: parts[0], h: parts[1] } : null;
}

function staticScreenVsWindow(staticData) {
    const screen = [], window = [];
    (staticData || []).forEach(r => {
        const p = r.payload || r;
        const sd = parseDim(p.screenDim);
        const wd = parseDim(p.windowDim);
        if (sd) screen.push({ x: sd.w, y: sd.h });
        if (wd) window.push({ x: wd.w, y: wd.h });
    });
    return { screen, window };
}

function staticFeatureSupport(staticData) {
    const features = ['cookiesEnabled', 'jsEnabled', 'imagesEnabled', 'cssEnabled'];
    const labels = ['Cookies', 'JavaScript', 'Images', 'CSS'];
    const total = (staticData || []).length || 1;
    const values = features.map(f => {
        const count = (staticData || []).filter(r => (r.payload || r)[f] === true).length;
        return Math.round((count / total) * 100);
    });
    return { labels, values };
}

function performanceLoadTimeOverTime(perfData) {
    const byDate = {};
    (perfData || []).forEach(r => {
        const p = r.payload || r;
        let t = p.totalLoadTime != null ? Number(p.totalLoadTime) : null;
        if (t == null && p.loadEventEnd != null && p.startTime != null) t = p.loadEventEnd - p.startTime;
        if (t == null && p.timingObject && typeof p.timingObject.loadEventEnd === 'number') t = p.timingObject.loadEventEnd - (p.timingObject.startTime || 0);
        if (t == null || typeof r.received_at !== 'string') return;
        const key = r.received_at.slice(0, 10);
        if (!byDate[key]) byDate[key] = { sum: 0, n: 0 };
        byDate[key].sum += t;
        byDate[key].n += 1;
    });
    const dates = Object.keys(byDate).sort();
    const values = dates.map(d => Math.round(byDate[d].sum / byDate[d].n));
    return { labels: dates, values };
}

function activityIdleVsActive(activityData) {
    const bySession = {};
    (activityData || []).forEach(r => {
        const sid = r.session_id || (r.payload && r.payload.sessionId) || 'unknown';
        if (!bySession[sid]) bySession[sid] = { idle: 0, events: [], received: [] };
        const p = r.payload || r;
        if (p.event === 'idle_break' && p.idleDuration != null) bySession[sid].idle += Number(p.idleDuration);
        bySession[sid].received.push(r.received_at || '');
    });
    const labels = [];
    const activeData = [];
    const idleData = [];
    Object.entries(bySession).slice(0, 12).forEach(([sid, v]) => {
        labels.push(sid.length > 8 ? sid.slice(0, 8) + '…' : sid);
        const idleSec = (v.idle / 1000) || 0;
        let activeSec = 0;
        if (v.received.length >= 2) {
            const times = v.received.map(d => new Date(d).getTime()).filter(Boolean);
            if (times.length >= 2) activeSec = (Math.max(...times) - Math.min(...times)) / 1000 - idleSec;
        }
        activeData.push(Math.max(0, Math.round(activeSec * 10) / 10));
        idleData.push(Math.round(idleSec * 10) / 10);
    });
    return { labels, activeData, idleData };
}

function activityEngagementHotspots(activityData) {
    const points = [];
    (activityData || []).forEach(r => {
        const p = r.payload || r;
        const c = p.coords || (p.data && p.data.coords);
        if (!c || (c.x == null && c.y == null)) return;
        const x = c.x != null ? Number(c.x) : 0;
        const y = c.y != null ? Number(c.y) : 0;
        if (['click', 'scroll', 'mousemove'].indexOf(p.event) !== -1) points.push({ x, y });
    });
    return points;
}