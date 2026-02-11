const { useState, useEffect, useMemo, createElement: el, Fragment } = wp.element;
const apiFetch = wp.apiFetch;

/**
 * BugSneak — Error Dashboard
 * CSS Variable-based Day/Night Theming
 */

const THEMES = {
    dark: {
        '--tl-bg': '#0f172a', '--tl-surface': '#08061D', '--tl-surface-hover': '#334155',
        '--tl-surface-alt': 'rgba(15,23,42,0.5)', '--tl-border': '#334155', '--tl-border-hover': '#475569',
        '--tl-text': '#f8fafc', '--tl-text-secondary': '#e2e8f0', '--tl-text-body': '#cbd5e1',
        '--tl-text-muted': '#94a3b8', '--tl-text-faint': '#64748b',
        '--tl-primary': '#6366f1', '--tl-primary-hover': '#4f46e5', '--tl-primary-light': '#818cf8',
        '--tl-primary-bg': 'rgba(99,102,241,0.1)', '--tl-primary-border': 'rgba(99,102,241,0.2)',
        '--tl-primary-glow': 'rgba(99,102,241,0.25)',
        '--tl-danger': '#ef4444', '--tl-danger-bg': 'rgba(239,68,68,0.15)', '--tl-danger-text': '#fca5a5',
        '--tl-warning': '#f59e0b', '--tl-warning-bg': 'rgba(245,158,11,0.15)', '--tl-warning-text': '#fcd34d',
        '--tl-success': '#10b981', '--tl-code-bg': '#0f172a', '--tl-code-header': '#1e293b',
        '--tl-code-text': '#cbd5e1', '--tl-code-line-num': '#475569', '--tl-input-bg': '#0f172a',
        '--tl-badge-fatal-bg': 'rgba(239,68,68,0.15)', '--tl-badge-fatal-text': '#fca5a5',
        '--tl-badge-fatal-border': 'rgba(239,68,68,0.2)',
        '--tl-badge-warn-bg': 'rgba(245,158,11,0.15)', '--tl-badge-warn-text': '#fcd34d',
        '--tl-badge-warn-border': 'rgba(245,158,11,0.2)',

        // Critical / High (Red/Orange)
        '--tl-card-critical-bg': 'rgba(239,68,68,0.1)', '--tl-card-critical-border': 'rgba(239,68,68,0.2)', '--tl-card-critical-text': '#fca5a5',
        '--tl-card-high-bg': 'rgba(249,115,22,0.1)', '--tl-card-high-border': 'rgba(249,115,22,0.2)', '--tl-card-high-text': '#fdba74',

        // Medium (Amber)
        '--tl-card-medium-bg': 'rgba(245,158,11,0.1)', '--tl-card-medium-border': 'rgba(245,158,11,0.2)', '--tl-card-medium-text': '#fcd34d',

        // Low / Info (Blue)
        '--tl-card-low-bg': 'rgba(59,130,246,0.1)', '--tl-card-low-border': 'rgba(59,130,246,0.2)', '--tl-card-low-text': '#93c5fd',

        // Unknown (Slate)
        '--tl-card-unknown-bg': 'rgba(148,163,184,0.1)', '--tl-card-unknown-border': 'rgba(148,163,184,0.2)', '--tl-card-unknown-text': '#cbd5e1',
    },
    light: {
        '--tl-bg': '#f8fafc', '--tl-surface': '#ffffff', '--tl-surface-hover': '#f1f5f9',
        '--tl-surface-alt': '#f1f5f9', '--tl-border': '#e2e8f0', '--tl-border-hover': '#cbd5e1',
        '--tl-text': '#0f172a', '--tl-text-secondary': '#334155', '--tl-text-body': '#475569',
        '--tl-text-muted': '#64748b', '--tl-text-faint': '#94a3b8',
        '--tl-primary': '#6366f1', '--tl-primary-hover': '#4f46e5', '--tl-primary-light': '#818cf8',
        '--tl-primary-bg': 'rgba(99,102,241,0.06)', '--tl-primary-border': 'rgba(99,102,241,0.12)',
        '--tl-primary-glow': 'rgba(99,102,241,0.15)',
        '--tl-danger': '#ef4444', '--tl-danger-bg': '#fef2f2', '--tl-danger-text': '#dc2626',
        '--tl-warning': '#f59e0b', '--tl-warning-bg': '#fffbeb', '--tl-warning-text': '#d97706',
        '--tl-success': '#10b981', '--tl-code-bg': '#1e293b', '--tl-code-header': '#334155',
        '--tl-code-text': '#cbd5e1', '--tl-code-line-num': '#64748b', '--tl-input-bg': '#f1f5f9',
        '--tl-badge-fatal-bg': '#fef2f2', '--tl-badge-fatal-text': '#dc2626', '--tl-badge-fatal-border': '#fecaca',
        '--tl-badge-warn-bg': '#fffbeb', '--tl-badge-warn-text': '#d97706', '--tl-badge-warn-border': '#fde68a',

        // Critical / High (Red/Orange)
        '--tl-card-critical-bg': '#fef2f2', '--tl-card-critical-border': '#fecaca', '--tl-card-critical-text': '#b91c1c',
        '--tl-card-high-bg': '#fff7ed', '--tl-card-high-border': '#fed7aa', '--tl-card-high-text': '#c2410c',

        // Medium (Amber)
        '--tl-card-medium-bg': '#fffbeb', '--tl-card-medium-border': '#fde68a', '--tl-card-medium-text': '#b45309',

        // Low / Info (Blue)
        '--tl-card-low-bg': '#eff6ff', '--tl-card-low-border': '#bfdbfe', '--tl-card-low-text': '#1d4ed8',

        // Unknown (Slate)
        '--tl-card-unknown-bg': '#f8fafc', '--tl-card-unknown-border': '#e2e8f0', '--tl-card-unknown-text': '#475569',
    }
};

// ─── App ────────────────────────────────────────────────────────────────────

const App = () => {
    const [logs, setLogs] = useState(window.bugsneakData?.logs || []);
    const [activeLog, setActiveLog] = useState(() => {
        const list = window.bugsneakData?.logs || [];
        return list.find(l => !l.status || l.status === 'open') || (list.length > 0 ? null : null);
    });
    const [search, setSearch] = useState('');
    const [filter, setFilter] = useState('all');
    const [loading, setLoading] = useState(false);
    const [isDark, setIsDark] = useState(() => {
        const saved = localStorage.getItem('bugsneak_theme');
        return saved ? saved === 'dark' : true;
    });
    const env = window.bugsneakData?.env || {};
    const settingsUrl = window.bugsneakData?.settingsUrl || '#';

    // Responsive viewport calculation
    const [viewportHeight, setViewportHeight] = useState('100vh');

    useEffect(() => { localStorage.setItem('bugsneak_theme', isDark ? 'dark' : 'light'); }, [isDark]);

    useEffect(() => {
        const updateHeight = () => {
            const adminBar = document.getElementById('wpadminbar');
            const barHeight = adminBar ? adminBar.offsetHeight : 0;
            // Subtract barHeight to fit exactly below it
            setViewportHeight(`${window.innerHeight - barHeight}px`);
        };

        updateHeight();
        window.addEventListener('resize', updateHeight);
        return () => window.removeEventListener('resize', updateHeight);
    }, []);

    const filteredLogs = useMemo(() => {
        return logs.filter(log => {
            if (log.status && log.status !== 'open') return false;
            const term = search.toLowerCase();
            const msg = (log.error_message || '').toLowerCase();
            const file = (log.file_path || '').toLowerCase();
            const culprit = (log.culprit || '').toLowerCase();
            const type = (log.error_type || '').toLowerCase();

            const m = msg.includes(term) || file.includes(term) || culprit.includes(term);

            if (filter === 'all') return m;
            if (filter === 'fatal') return m && type.includes('fatal');
            if (filter === 'warning') return m && type.includes('warning');
            return m;
        });
    }, [logs, search, filter]);

    const setLogStatus = async (id, status) => {
        try {
            await apiFetch({ path: `/bugsneak/v1/logs/${id}/status`, method: 'POST', data: { status } });
            setLogs(prev => prev.map(l => l.id === id ? { ...l, status } : l));
            if (activeLog?.id === id) {
                const index = filteredLogs.findIndex(l => l.id === id);
                const nextLog = filteredLogs[index + 1] || filteredLogs[index - 1] || null;
                setActiveLog(nextLog);
            }
        } catch (err) { console.error('Status update failed', err); }
    };

    const refreshLogs = async () => {
        setLoading(true);
        try {
            const data = await apiFetch({ path: '/bugsneak/v1/logs' });
            setLogs(data);
            if (!activeLog && data.length > 0) setActiveLog(data.find(l => !l.status || l.status === 'open') || null);
        } catch (err) { console.error('Failed to fetch logs', err); }
        finally { setLoading(false); }
    };

    const themeVars = isDark ? THEMES.dark : THEMES.light;

    return el('div', {
        className: 'font-display flex flex-col overflow-hidden',
        style: { ...themeVars, height: viewportHeight }
    }, [
        el(Header, { search, setSearch, loading, onRefresh: refreshLogs, isDark, toggleTheme: () => setIsDark(!isDark), settingsUrl }),
        el('div', { className: 'flex flex-1 overflow-hidden' }, [
            el(Aside, { logs: filteredLogs, activeLog, setActiveLog, filter, setFilter }),
            el(Main, { activeLog, env, setLogStatus, isDark })
        ])
    ]);
};

// ─── Header ─────────────────────────────────────────────────────────────────

const Header = ({ search, setSearch, loading, onRefresh, isDark, toggleTheme, settingsUrl }) => (
    el('header', { className: 'bg-[var(--tl-surface)] border-b border-[var(--tl-border)] h-14 flex items-center justify-between px-6 shrink-0 z-20' }, [
        el('div', { className: 'flex items-center gap-6' }, [
            el('div', { className: 'flex items-center gap-2.5' }, [
                el('div', { className: 'flex items-center justify-center' },
                    el('img', {
                        src: isDark ? window.bugsneakData?.logo_light : window.bugsneakData?.logo_dark,
                        className: 'h-8 w-auto',
                        alt: 'BugSneak'
                    })
                ),
                el('div', { className: 'flex items-center justify-center' },
                    el('img', {
                        src: window.bugsneakData?.logo_text,
                        className: 'h-6 w-auto ml-1',
                        style: { filter: isDark ? 'brightness(0) invert(1)' : 'none' },
                        alt: 'BugSneak'
                    })
                ),
                el('span', { className: 'text-[10px] font-bold text-[var(--tl-text-muted)] bg-[var(--tl-surface-hover)] px-2 py-0.5 rounded-md ml-2 uppercase tracking-wider' }, 'v1.3.4')
            ]),
            el('div', { className: 'hidden md:flex relative w-72' }, [
                el('span', { className: 'absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none' },
                    el('span', { className: 'material-icons text-[18px] text-[var(--tl-text-faint)]' }, 'search')
                ),
                el('input', {
                    className: 'block w-full pl-3 pr-10 py-2 border border-[var(--tl-border)] rounded-lg bg-[var(--tl-input-bg)] text-sm text-[var(--tl-text)] placeholder-[var(--tl-text-faint)] focus:ring-2 focus:ring-[var(--tl-primary)]/40 focus:border-[var(--tl-primary)] transition-all outline-none',
                    placeholder: 'Search errors...', type: 'text', value: search,
                    onChange: (e) => setSearch(e.target.value)
                })
            ])
        ]),
        el('div', { className: 'flex items-center gap-0.5' }, [
            el('button', { onClick: onRefresh, className: 'p-2 text-[var(--tl-text-muted)] hover:text-[var(--tl-primary)] transition-colors rounded-lg hover:bg-[var(--tl-surface-hover)]' },
                el('span', { className: `material-icons text-[20px] ${loading ? 'animate-spin' : ''}` }, 'refresh')),
            el('button', { onClick: toggleTheme, className: 'p-2 text-[var(--tl-text-muted)] hover:text-[var(--tl-primary)] transition-all rounded-lg hover:bg-[var(--tl-surface-hover)]', title: isDark ? 'Light Mode' : 'Dark Mode' },
                el('span', { className: 'material-icons text-[20px]' }, isDark ? 'light_mode' : 'dark_mode')),
            el('div', { className: 'h-5 w-px bg-[var(--tl-border)] mx-1' }),
            el('a', { href: settingsUrl, className: 'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[var(--tl-text-muted)] hover:bg-[var(--tl-surface-hover)] hover:text-[var(--tl-text)] transition-all group no-underline' }, [
                el('span', { className: 'material-icons text-[18px]' }, 'settings'),
                el('span', { className: 'text-[11px] font-semibold uppercase tracking-wider hidden lg:block' }, 'Settings')
            ]),
            el(NavButton, { label: 'Docs', icon: 'description' }),
            el(NavButton, { label: 'Help', icon: 'help_outline' })
        ])
    ])
);

const NavButton = ({ label, icon }) => (
    el('button', { className: 'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[var(--tl-text-muted)] hover:bg-[var(--tl-surface-hover)] hover:text-[var(--tl-text)] transition-all group' }, [
        el('span', { className: 'material-icons text-[18px]' }, icon),
        el('span', { className: 'text-[11px] font-semibold uppercase tracking-wider hidden lg:block' }, label)
    ])
);

// ─── Sidebar ────────────────────────────────────────────────────────────────

const Aside = ({ logs, activeLog, setActiveLog, filter, setFilter }) => (
    el('aside', { className: 'w-80 lg:w-96 border-r border-[var(--tl-border)] bg-[var(--tl-surface)] flex flex-col shrink-0' }, [
        el('div', { className: 'p-3 border-b border-[var(--tl-border)] shrink-0' },
            el('div', { className: 'flex gap-1.5' }, ['all', 'fatal', 'warning'].map(f =>
                el('button', { key: f, onClick: () => setFilter(f), className: `px-3 py-1.5 text-[11px] font-semibold rounded-md transition-all capitalize ${filter === f ? 'bg-[var(--tl-primary)] text-white shadow-md' : 'text-[var(--tl-text-muted)] hover:bg-[var(--tl-surface-hover)] hover:text-[var(--tl-text)]'}` }, f)
            ))
        ),
        el('div', { className: 'flex-1 overflow-y-auto custom-scrollbar p-2.5 space-y-2 bg-[var(--tl-surface-alt)]' },
            logs.length === 0
                ? el('div', { className: 'p-8 text-center text-[var(--tl-text-faint)]' }, 'No errors found')
                : logs.map(log => el(LogCard, { key: log.id, log, isActive: activeLog?.id === log.id, onClick: () => setActiveLog(log) }))
        )
    ])
);

const LogCard = ({ log, isActive, onClick }) => {
    const isFatal = log.error_type.toLowerCase().includes('fatal');
    const b = isFatal ? 'bg-[var(--tl-badge-fatal-bg)] text-[var(--tl-badge-fatal-text)] border-[var(--tl-badge-fatal-border)]' : 'bg-[var(--tl-badge-warn-bg)] text-[var(--tl-badge-warn-text)] border-[var(--tl-badge-warn-border)]';
    return el('div', { onClick, className: `group rounded-lg border p-3 cursor-pointer transition-all duration-200 ${isActive ? 'bg-[var(--tl-surface)] border-[var(--tl-primary)] shadow-lg ring-1 ring-[var(--tl-primary-glow)]' : 'bg-[var(--tl-surface)] border-[var(--tl-border)] hover:border-[var(--tl-border-hover)] hover:shadow-md opacity-80 hover:opacity-100'}` }, [
        el('div', { className: 'flex items-center justify-between mb-2' }, [
            el('span', { className: `inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider border ${b}` }, log.error_type),
            el('span', { className: 'text-[10px] font-mono text-[var(--tl-text-faint)]' }, log.occurrence_count > 1 ? `${log.occurrence_count}x` : 'new')
        ]),
        el('h3', { className: `text-[13px] font-semibold leading-snug mb-2 line-clamp-2 transition-colors ${isActive ? 'text-[var(--tl-text)]' : 'text-[var(--tl-text-secondary)] group-hover:text-[var(--tl-text)]'}` }, log.error_message),
        el('div', { className: 'flex items-center gap-2 text-[10px] text-[var(--tl-text-faint)] font-mono truncate' }, [
            el('span', { className: `w-1.5 h-1.5 rounded-full shrink-0 ${log.culprit.includes('Plugin') ? 'bg-[var(--tl-primary-light)]' : 'bg-cyan-400'}` }),
            el('span', { className: 'truncate' }, log.culprit.split(':').pop().trim()),
            el('span', { className: 'text-[var(--tl-border)]' }, '·'),
            el('span', { className: 'truncate' }, log.file_path.split('/').pop())
        ])
    ]);
};

// ─── Main Detail View ───────────────────────────────────────────────────────

const Main = ({ activeLog, env, setLogStatus }) => {
    const [activeTab, setActiveTab] = useState('stack');
    const [aiResult, setAiResult] = useState({});
    const [aiLoading, setAiLoading] = useState(false);

    if (!activeLog) return el('main', { className: 'flex-1 flex flex-col items-center justify-center text-[var(--tl-text-faint)]' }, [
        el('span', { className: 'material-icons text-5xl mb-3 opacity-40' }, 'bug_report'),
        el('p', { className: 'text-sm font-medium' }, 'Select an error to begin diagnostic')
    ]);

    const requestContext = activeLog.request_context ? JSON.parse(activeLog.request_context) : {};
    const envContext = activeLog.env_context ? JSON.parse(activeLog.env_context) : {};
    const aiEnabled = window.bugsneakData?.ai_enabled;
    const aiProvider = window.bugsneakData?.ai_provider;

    const runAIAnalysis = async () => {
        setAiLoading(true);
        try {
            const data = await apiFetch({ path: `/bugsneak/v1/analyze/${activeLog.id}`, method: 'POST' });
            setAiResult(prev => ({ ...prev, [activeLog.id]: { text: data.insight, error: false } }));
        } catch (err) {
            console.error('AI Analysis failed', err);
            const msg = err.message || 'AI Analysis failed. Please check your API key and connection.';
            setAiResult(prev => ({ ...prev, [activeLog.id]: { text: msg, error: true } }));
        } finally {
            setAiLoading(false);
        }
    };

    const currentAiInsight = aiResult[activeLog.id];

    return el('main', { className: 'flex-1 flex flex-col bg-[var(--tl-bg)] overflow-hidden' }, [
        el('div', { className: 'px-6 pt-5 pb-0 shrink-0' }, [
            el('div', { className: 'flex justify-between items-start mb-4' }, [
                el('div', { className: 'min-w-0 flex-1 mr-4' }, [
                    el('div', { className: 'flex items-center gap-2.5 mb-2' }, [
                        el('span', { className: `inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold text-white uppercase tracking-wider ${activeLog.error_type.toLowerCase().includes('fatal') ? 'bg-[var(--tl-danger)]' : 'bg-[var(--tl-warning)]'}` }, activeLog.error_type),
                        el('span', { className: 'text-[11px] text-[var(--tl-text-faint)] font-mono' }, `#Err-${activeLog.id}`)
                    ]),
                    el('h1', { className: 'text-lg font-bold text-[var(--tl-text)] mb-1.5 leading-tight truncate' }, activeLog.error_message),
                    el('div', { className: 'flex items-center gap-1.5 text-[12px] text-[var(--tl-text-faint)] font-mono truncate' }, [
                        el('span', { className: 'material-icons text-[14px]' }, 'folder_open'),
                        el('span', { className: 'truncate' }, activeLog.file_path)
                    ])
                ]),
                el('div', { className: 'flex gap-2 shrink-0' }, [
                    el('button', { onClick: () => setLogStatus(activeLog.id, 'ignored'), className: 'px-3 py-1.5 text-[11px] font-semibold text-[var(--tl-text-muted)] hover:text-[var(--tl-text)] hover:bg-[var(--tl-surface-hover)] rounded-lg transition-all uppercase tracking-wider' }, 'Ignore'),
                    el('button', { onClick: () => setLogStatus(activeLog.id, 'resolved'), className: 'px-3 py-1.5 bg-[var(--tl-primary)] text-white text-[11px] font-semibold rounded-lg hover:bg-[var(--tl-primary-hover)] transition-all shadow-lg flex items-center gap-1.5 uppercase tracking-wider' }, [
                        el('span', { className: 'material-icons text-[14px]' }, 'check_circle'), 'Resolve'
                    ])
                ])
            ]),
            el('div', { className: 'flex gap-1 border-b border-[var(--tl-border)]' }, [
                el(TabButton, { id: 'stack', label: 'Stack Trace', icon: 'code', isActive: activeTab === 'stack', onClick: setActiveTab }),
                el(TabButton, { id: 'context', label: 'Context', icon: 'person', isActive: activeTab === 'context', onClick: setActiveTab }),
                el(TabButton, { id: 'request', label: 'Request', icon: 'http', isActive: activeTab === 'request', onClick: setActiveTab }),
                el(TabButton, { id: 'env', label: 'Environment', icon: 'dns', isActive: activeTab === 'env', onClick: setActiveTab }),
            ])
        ]),
        el('div', { className: 'flex-1 overflow-auto p-6 custom-scrollbar' }, [
            activeTab === 'stack' && el('div', { className: 'space-y-4' }, [
                el(IntelligencePanel, { activeLog, aiResult, aiLoading, runAIAnalysis, aiEnabled, aiProvider }),
                el('div', { className: 'bg-[var(--tl-code-bg)] rounded-lg border border-[var(--tl-border)] flex flex-col overflow-hidden' }, [
                    el('div', { className: 'h-8 bg-[var(--tl-code-header)] border-b border-[var(--tl-border)] flex items-center px-4 justify-between shrink-0' }, [
                        el('div', { className: 'flex items-center gap-1.5' }, [
                            el('span', { className: 'w-2.5 h-2.5 rounded-full bg-[#ef4444]' }),
                            el('span', { className: 'w-2.5 h-2.5 rounded-full bg-[#f59e0b]' }),
                            el('span', { className: 'w-2.5 h-2.5 rounded-full bg-[#10b981]' })
                        ]),
                        el('span', { className: 'text-[11px] text-[var(--tl-code-line-num)] font-mono' }, activeLog.file_path.split('/').pop())
                    ]),
                    el('div', { className: 'flex-1 overflow-auto' }, el(CodeViewer, { snippet: JSON.parse(activeLog.code_snippet) }))
                ])
            ]),
            activeTab === 'context' && el('div', { className: 'space-y-4' }, [
                el(ContextCard, { title: 'User Identity', items: [{ label: 'User ID', value: envContext.user_id || 'Guest' }, { label: 'Roles', value: envContext.user_roles?.join(', ') || 'N/A' }] }),
                el(ContextCard, { title: 'WordPress State', items: [{ label: 'Current Filter', value: envContext.current_filter || 'N/A' }, { label: 'Active Theme', value: activeLog.active_theme }, { label: 'WP Version', value: activeLog.wp_version }] }),
                el(ContextCard, { title: 'Memory', items: [{ label: 'Usage', value: ((envContext.memory_usage || 0) / 1024 / 1024).toFixed(2) + ' MB' }, { label: 'Peak', value: ((envContext.peak_memory || 0) / 1024 / 1024).toFixed(2) + ' MB' }] })
            ]),
            activeTab === 'request' && el('div', { className: 'space-y-4' }, [
                el(RequestTable, { title: '$_GET', data: requestContext.get }),
                el(RequestTable, { title: '$_POST', data: requestContext.post }),
                el(RequestTable, { title: '$_SERVER', data: requestContext.server })
            ]),
            activeTab === 'env' && el(EnvironmentPanel, { env, activeLog })
        ]),
        el('div', { className: 'px-6 py-2.5 bg-[var(--tl-surface)] border-t border-[var(--tl-border)] flex justify-between items-center shrink-0' }, [
            el('div', { className: 'text-[11px] font-medium text-[var(--tl-text-faint)] tracking-wide' }, 'Telemetry: 100% Local · No External Calls'),
            el('button', { onClick: () => { navigator.clipboard.writeText(JSON.stringify({ activeLog, env }, null, 2)); alert('Copied!'); }, className: 'px-3 py-1.5 bg-[var(--tl-surface-hover)] hover:bg-[var(--tl-border-hover)] text-[var(--tl-text-secondary)] text-[11px] font-semibold rounded-lg transition-all flex items-center gap-1.5' }, [
                el('span', { className: 'material-icons text-[14px]' }, 'content_paste'), 'Copy Bundle'
            ])
        ])
    ]);
};

const TabButton = ({ id, label, icon, isActive, onClick }) => (
    el('button', { onClick: () => onClick(id), className: `flex items-center gap-1.5 px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wider border-b-2 -mb-px transition-all ${isActive ? 'border-[var(--tl-primary)] text-[var(--tl-primary-light)]' : 'border-transparent text-[var(--tl-text-faint)] hover:text-[var(--tl-text-muted)]'}` }, [
        el('span', { className: 'material-icons text-[16px]' }, icon), label
    ])
);

const ContextCard = ({ title, items }) => (
    el('div', { className: 'bg-[var(--tl-surface)] border border-[var(--tl-border)] rounded-lg overflow-hidden' }, [
        el('div', { className: 'px-4 py-2 border-b border-[var(--tl-border)] text-[10px] font-bold uppercase tracking-wider text-[var(--tl-text-faint)]' }, title),
        el('div', { className: 'p-4 grid grid-cols-2 gap-3' }, items.map(i => el('div', { key: i.label }, [
            el('span', { className: 'block text-[10px] font-semibold text-[var(--tl-text-faint)] uppercase tracking-wider mb-1' }, i.label),
            el('span', { className: 'text-[13px] font-medium text-[var(--tl-text-secondary)]' }, i.value)
        ])))
    ])
);

const RequestTable = ({ title, data }) => {
    const e = data ? Object.entries(data) : [];
    return el('div', { className: 'bg-[var(--tl-surface)] border border-[var(--tl-border)] rounded-lg overflow-hidden' }, [
        el('div', { className: 'px-4 py-2 border-b border-[var(--tl-border)] text-[10px] font-bold uppercase tracking-wider text-[var(--tl-text-faint)]' }, title),
        e.length === 0
            ? el('div', { className: 'p-4 text-[12px] italic text-[var(--tl-text-faint)]' }, 'No data.')
            : el('table', { className: 'w-full text-left border-collapse' }, el('tbody', null, e.map(([k, v]) =>
                el('tr', { key: k, className: 'border-b border-[var(--tl-border)] last:border-0' }, [
                    el('td', { className: 'px-4 py-2.5 text-[11px] font-semibold text-[var(--tl-text-muted)] w-1/3' }, k),
                    el('td', { className: 'px-4 py-2.5 text-[11px] font-mono text-[var(--tl-text-secondary)] break-all' }, typeof v === 'object' ? JSON.stringify(v) : String(v))
                ])
            )))
    ]);
};

const EnvironmentPanel = ({ env, activeLog }) => (
    el('div', { className: 'grid grid-cols-2 gap-4' }, [
        el(ContextCard, { title: 'Server', items: [{ label: 'PHP', value: env.php_version }, { label: 'Memory Limit', value: env.memory_limit }, { label: 'OS', value: env.server_os }] }),
        el(ContextCard, { title: 'WordPress', items: [{ label: 'WP', value: env.wp_version }, { label: 'Theme', value: env.theme }, { label: 'Hash', value: activeLog.error_hash }] })
    ])
);

const MarkdownContent = ({ content, error, fallback, isDark }) => {
    if (!content) return el('div', { className: 'text-[13px] text-[var(--tl-text-body)] leading-relaxed' }, fallback);
    const html = window.marked ? window.marked.parse(content) : content;
    return el('div', {
        className: `prose prose-sm max-w-none text-[13px] leading-relaxed ${isDark ? 'prose-invert' : ''} ${error ? 'text-[var(--tl-danger)] font-medium p-3 bg-red-500/5 rounded-lg border border-red-500/20' : 'text-[var(--tl-text-body)]'}`,
        dangerouslySetInnerHTML: { __html: html }
    });
};

const CodeViewer = ({ snippet }) => {
    if (!snippet || !snippet.lines) return el('div', { className: 'p-4 text-[var(--tl-text-faint)] italic text-center text-sm' }, 'No code context');
    return el('div', { className: 'flex min-w-max' }, [
        el('div', { className: 'w-12 py-2 text-[var(--tl-code-line-num)] text-right pr-3 select-none flex flex-col border-r border-[var(--tl-border)] text-[11px] font-mono' },
            Object.keys(snippet.lines).map(n => el('div', { key: n, className: parseInt(n) === snippet.target ? 'text-white font-bold' : '' }, n))),
        el('div', { className: 'flex-1 py-2 text-[var(--tl-code-text)] w-full text-[12px] leading-5 font-mono' },
            Object.entries(snippet.lines).map(([n, c]) => {
                const t = parseInt(n) === snippet.target;
                return el('div', { key: n, className: `px-4 w-full relative ${t ? 'bg-[rgba(239,68,68,0.15)] border-l-2 border-[#ef4444]' : ''}` }, [
                    highlightLine(c), t && el('span', { className: 'text-[#fca5a5] italic ml-4 text-[10px]' }, '← error')
                ]);
            }))
    ]);
};

const highlightLine = (c) => {
    if (!c) return ' ';
    const kw = ['public', 'function', 'class', 'return', 'if', 'else', 'new', 'private', 'protected', 'static'];
    return c.split(/(\s+)/).map((p, i) => {
        if (kw.includes(p.trim())) return el('span', { key: i, className: 'text-[#c084fc]' }, p);
        if (p.startsWith('$')) return el('span', { key: i, className: 'text-[#67e8f9]' }, p);
        if (p.match(/^[a-zA-Z_]\w*\(/)) return el('span', { key: i, className: 'text-[#fbbf24]' }, p);
        return p;
    });
};

const IntelligencePanel = ({ activeLog, aiResult, aiLoading, runAIAnalysis, aiEnabled, aiProvider, isDark }) => {
    let classification = activeLog.classification;
    // Safety: Parse if string (common WP REST API quirk)
    if (typeof classification === 'string') {
        try { classification = JSON.parse(classification); } catch (e) { classification = null; }
    }

    const currentAiInsight = aiResult[activeLog.id];
    const isSpike = activeLog.is_spike;

    // Determine styles based on classification, defaulting to 'unknown' if missing
    const severityMap = {
        'critical': { bg: 'var(--tl-card-critical-bg)', border: 'var(--tl-card-critical-border)', text: 'var(--tl-card-critical-text)' },
        'high': { bg: 'var(--tl-card-high-bg)', border: 'var(--tl-card-high-border)', text: 'var(--tl-card-high-text)' },
        'medium': { bg: 'var(--tl-card-medium-bg)', border: 'var(--tl-card-medium-border)', text: 'var(--tl-card-medium-text)' },
        'low': { bg: 'var(--tl-card-low-bg)', border: 'var(--tl-card-low-border)', text: 'var(--tl-card-low-text)' },
        'unknown': { bg: 'var(--tl-card-unknown-bg)', border: 'var(--tl-card-unknown-border)', text: 'var(--tl-card-unknown-text)' }
    };

    const hasClassification = classification && classification.category && classification.category !== 'Unclassified';
    const s = hasClassification ? (severityMap[classification.severity] || severityMap['unknown']) : severityMap['unknown'];
    const confidence = classification?.confidence || 0;

    let confidenceBadge = { text: 'Low Confidence', className: 'text-[var(--tl-text-muted)] bg-[var(--tl-surface-alt)]' };
    if (confidence >= 90) confidenceBadge = { text: 'High Confidence', className: 'text-emerald-500 bg-emerald-500/10' };
    else if (confidence >= 70) confidenceBadge = { text: 'Medium Confidence', className: 'text-amber-500 bg-amber-500/10' };

    return el('div', { className: 'space-y-3 mb-4' }, [
        // Spike Warning
        isSpike && el('div', { className: 'flex items-center gap-2 p-2.5 bg-[var(--tl-danger-bg)] text-[var(--tl-danger-text)] rounded-lg border border-[var(--tl-badge-fatal-border)] animate-pulse' }, [
            el('span', { className: 'material-icons text-[18px]' }, 'local_fire_department'),
            el('div', null, [
                el('strong', { className: 'block text-[11px] uppercase tracking-wider' }, 'Abnormal Spike Detected'),
                el('span', { className: 'text-[11px]' }, 'Error rate allows this to be classified as a spike.')
            ])
        ]),

        // Unified Intelligence Card
        el('div', {
            className: 'rounded-lg border relative overflow-hidden transition-all',
            style: { backgroundColor: s.bg, borderColor: s.border }
        }, [
            // Header (Local Intelligence or Culprit Fallback)
            (hasClassification || activeLog.culprit) && el('div', { className: 'px-3 py-2.5 border-b border-[rgba(0,0,0,0.05)]' }, [
                el('div', { className: 'flex items-center justify-between mb-2 relative z-10' }, [
                    el('div', { className: 'flex items-center gap-2' }, [
                        el('div', {
                            className: 'w-6 h-6 rounded-full flex items-center justify-center',
                            style: { backgroundColor: s.border, color: s.text }
                        }, el('span', { className: 'material-icons text-[14px]' }, hasClassification ? 'lightbulb' : 'extension')),
                        el('h3', {
                            className: 'text-[11px] font-bold uppercase tracking-wider',
                            style: { color: s.text }
                        }, hasClassification ? classification.category : (activeLog.culprit ? activeLog.culprit.split(':').pop().trim() : 'Unknown Source'))
                    ]),
                    hasClassification
                        ? el('span', { className: `px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider ${confidenceBadge.className}` }, confidenceBadge.text)
                        : el('span', { className: 'px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider text-[var(--tl-text-muted)] bg-[var(--tl-surface-alt)]' }, activeLog.culprit ? activeLog.culprit.split(':')[0] : 'System')
                ]),
                el('p', { className: 'text-[12px] font-medium leading-relaxed mb-2', style: { color: 'var(--tl-text)' } },
                    hasClassification ? classification.suggestion : `Error originated from ${activeLog.culprit || 'an unknown source'}.`
                ),
                hasClassification && el('div', { className: 'flex flex-wrap gap-1 relative z-10' },
                    (classification.tags || []).map(tag => el('span', {
                        key: tag,
                        className: 'px-1.5 py-0.5 rounded text-[9px] font-mono border transition-colors',
                        style: {
                            backgroundColor: 'var(--tl-surface-hover)',
                            borderColor: 'var(--tl-border)',
                            color: 'var(--tl-text-secondary)'
                        }
                    }, `#${tag}`))
                )
            ]),

            // AI Deep Dive Section
            el('div', { className: 'bg-[rgba(0,0,0,0.1)] px-3 py-2.5' }, [
                el('div', { className: 'flex items-center justify-between mb-2' }, [
                    el('div', { className: 'flex items-center gap-2' }, [
                        el('span', { className: 'material-icons text-[var(--tl-primary-light)] text-[16px]' }, 'auto_fix_high'),
                        el('h3', { className: 'text-[11px] font-bold text-[var(--tl-primary-light)] uppercase tracking-wider' },
                            currentAiInsight ? `BugSneak AI Insight (${aiProvider})` : 'BugSneak AI Insight'
                        )
                    ]),
                    aiEnabled && !currentAiInsight && el('button', {
                        onClick: runAIAnalysis,
                        disabled: aiLoading,
                        className: 'px-2.5 py-1 bg-[var(--tl-primary)] text-white text-[10px] font-bold rounded uppercase tracking-wider hover:opacity-90 disabled:opacity-50 transition-all shadow-sm'
                    }, aiLoading ? 'Analyzing...' : 'Deep Dive Analysis')
                ]),

                // Content Area
                (currentAiInsight || aiLoading) ?
                    el('div', { className: `transition-opacity ${aiLoading ? 'opacity-50' : 'opacity-100'}` },
                        el(MarkdownContent, {
                            content: currentAiInsight ? currentAiInsight.text : '',
                            error: currentAiInsight?.error,
                            fallback: aiLoading ? 'Generating insight...' : '',
                            isDark
                        })
                    )
                    :
                    el('p', { className: 'text-[11px] text-[var(--tl-text-faint)] italic' },
                        'Run a deep dive analysis to get code snippets, solutions, and architectural advice from the AI engine.'
                    )
            ])
        ])
    ]);
};

// ─── Init ───────────────────────────────────────────────────────────────────

window.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('bugsneak-app');
    if (root) wp.element.render(el(App), root);
});
