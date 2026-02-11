const { useState, useEffect, createElement: el, Fragment } = wp.element;
const apiFetch = wp.apiFetch;

/**
 * BugSneak — Settings Page
 * Standalone settings page with Health Indicator + 10 configuration sections.
 */

const THEMES = {
    dark: {
        '--tl-bg': '#0f172a', '--tl-surface': '#08061D', '--tl-surface-hover': '#334155',
        '--tl-surface-alt': 'rgba(15,23,42,0.5)', '--tl-border': '#334155', '--tl-border-hover': '#475569',
        '--tl-text': '#f8fafc', '--tl-text-secondary': '#e2e8f0', '--tl-text-body': '#cbd5e1',
        '--tl-text-muted': '#94a3b8', '--tl-text-faint': '#64748b',
        '--tl-primary': '#6366f1', '--tl-primary-hover': '#4f46e5', '--tl-primary-light': '#818cf8',
        '--tl-primary-bg': 'rgba(99,102,241,0.1)', '--tl-primary-border': 'rgba(99,102,241,0.2)',
        '--tl-danger': '#ef4444', '--tl-danger-bg': 'rgba(239,68,68,0.15)',
        '--tl-warning': '#f59e0b', '--tl-warning-bg': 'rgba(245,158,11,0.15)', '--tl-warning-text': '#fcd34d',
        '--tl-success': '#10b981', '--tl-input-bg': '#0f172a',
    },
    light: {
        '--tl-bg': '#f8fafc', '--tl-surface': '#ffffff', '--tl-surface-hover': '#f1f5f9',
        '--tl-surface-alt': '#f1f5f9', '--tl-border': '#e2e8f0', '--tl-border-hover': '#cbd5e1',
        '--tl-text': '#0f172a', '--tl-text-secondary': '#334155', '--tl-text-body': '#475569',
        '--tl-text-muted': '#64748b', '--tl-text-faint': '#94a3b8',
        '--tl-primary': '#6366f1', '--tl-primary-hover': '#4f46e5', '--tl-primary-light': '#818cf8',
        '--tl-primary-bg': 'rgba(99,102,241,0.06)', '--tl-primary-border': 'rgba(99,102,241,0.12)',
        '--tl-danger': '#ef4444', '--tl-danger-bg': '#fef2f2',
        '--tl-warning': '#f59e0b', '--tl-warning-bg': '#fffbeb', '--tl-warning-text': '#d97706',
        '--tl-success': '#10b981', '--tl-input-bg': '#f1f5f9',
    }
};

// ─── Settings App ───────────────────────────────────────────────────────────

const SettingsApp = () => {
    const [settings, setSettings] = useState(null);
    const [stats, setStats] = useState(null);
    const [saving, setSaving] = useState(false);
    const [saved, setSaved] = useState(false);
    const [openSection, setOpenSection] = useState('error_levels');
    const [isDark, setIsDark] = useState(() => {
        const s = localStorage.getItem('bugsneak_theme');
        return s ? s === 'dark' : true;
    });

    useEffect(() => {
        apiFetch({ path: '/bugsneak/v1/settings' }).then(res => {
            setSettings(res.settings);
            setStats(res.stats);
        }).catch(err => console.error('Settings load failed', err));
    }, []);

    useEffect(() => { localStorage.setItem('bugsneak_theme', isDark ? 'dark' : 'light'); }, [isDark]);

    const update = (key, value) => { setSettings(prev => ({ ...prev, [key]: value })); setSaved(false); };
    const updateNested = (parentKey, childKey, value) => { setSettings(prev => ({ ...prev, [parentKey]: { ...prev[parentKey], [childKey]: value } })); setSaved(false); };

    const saveSettings = async () => {
        setSaving(true);
        try {
            const res = await apiFetch({ path: '/bugsneak/v1/settings', method: 'POST', data: settings });
            setSettings(res.settings);
            setSaved(true);
            setTimeout(() => setSaved(false), 3000);
        } catch (err) { console.error('Save failed', err); }
        finally { setSaving(false); }
    };

    const purgeAll = async () => {
        if (!confirm('This will permanently delete ALL error logs. Continue?')) return;
        try {
            const res = await apiFetch({ path: '/bugsneak/v1/purge', method: 'POST' });
            setStats(res.stats);
        } catch (err) { console.error('Purge failed', err); }
    };

    const themeVars = isDark ? THEMES.dark : THEMES.light;

    if (!settings) return el('div', { className: 'h-full flex items-center justify-center', style: { ...themeVars, background: themeVars['--tl-bg'], color: themeVars['--tl-text'] } },
        el('div', { className: 'text-sm animate-pulse' }, 'Loading settings...')
    );

    const toggle = (id) => setOpenSection(openSection === id ? null : id);

    const sections = [
        {
            id: 'error_levels', icon: 'error', title: 'Error Levels to Capture', content: () => el(Fragment, null, [
                el(SToggle, { label: 'Fatal Errors', checked: true, disabled: true, hint: 'Always enabled' }),
                el(SToggle, { label: 'Parse Errors', checked: settings.error_levels?.parse, onChange: v => updateNested('error_levels', 'parse', v) }),
                el(SToggle, { label: 'Uncaught Exceptions', checked: settings.error_levels?.exceptions, onChange: v => updateNested('error_levels', 'exceptions', v) }),
                el(SToggle, { label: 'Warnings', checked: settings.error_levels?.warnings, onChange: v => updateNested('error_levels', 'warnings', v) }),
                el(SToggle, { label: 'Notices', checked: settings.error_levels?.notices, onChange: v => updateNested('error_levels', 'notices', v) }),
                el(SToggle, { label: 'Deprecated', checked: settings.error_levels?.deprecated, onChange: v => updateNested('error_levels', 'deprecated', v) }),
                el(SToggle, { label: 'Strict Standards', checked: settings.error_levels?.strict, onChange: v => updateNested('error_levels', 'strict', v) }),
                el('div', { className: 'mt-4 pt-4 border-t border-[var(--tl-border)]' },
                    el(SSelect, { label: 'Capture Mode', value: settings.capture_mode, options: [{ v: 'debug', l: 'WP_DEBUG mode only' }, { v: 'production', l: 'Production (always)' }], onChange: v => update('capture_mode', v) })
                )
            ])
        },
        {
            id: 'grouping', icon: 'layers', title: 'Error Grouping', content: () => el(Fragment, null, [
                el(SToggle, { label: 'Enable intelligent grouping', checked: settings.grouping_enabled, onChange: v => update('grouping_enabled', v) }),
                el(SNumber, { label: 'Max occurrence counter', value: settings.max_occurrences, onChange: v => update('max_occurrences', v), hint: '0 = unlimited' }),
                el(SNumber, { label: 'Reset grouping after (minutes)', value: settings.grouping_reset_mins, onChange: v => update('grouping_reset_mins', v), hint: '0 = never' }),
                el(SToggle, { label: 'Merge identical stack traces only', checked: settings.merge_stack_only, onChange: v => update('merge_stack_only', v) })
            ])
        },
        {
            id: 'database', icon: 'storage', title: 'Database & Retention', content: () => el(Fragment, null, [
                stats && el('div', { className: 'bg-[var(--tl-primary-bg)] border border-[var(--tl-primary-border)] rounded-lg p-3 mb-4 flex items-center justify-between' }, [
                    el('div', null, [
                        el('div', { className: 'text-[10px] font-bold text-[var(--tl-text-faint)] uppercase tracking-wider mb-1' }, 'Database Usage'),
                        el('div', { className: 'text-[14px] font-bold text-[var(--tl-text)]' }, `${stats.log_count.toLocaleString()} logs · ${stats.db_size_human}`)
                    ]),
                    el('button', { onClick: purgeAll, className: 'px-3 py-1.5 bg-[var(--tl-danger)] text-white text-[10px] font-bold rounded-lg uppercase tracking-wider hover:opacity-90 transition-opacity' }, 'Purge All')
                ]),
                el(SSelect, { label: 'Auto-delete logs older than', value: String(settings.retention_days), options: [{ v: '7', l: '7 days' }, { v: '30', l: '30 days' }, { v: '60', l: '60 days' }, { v: '90', l: '90 days' }], onChange: v => update('retention_days', parseInt(v)) }),
                el(SNumber, { label: 'Maximum stored errors', value: settings.max_rows, onChange: v => update('max_rows', v) }),
                el(SSelect, { label: 'Cleanup cron frequency', value: settings.cleanup_frequency, options: [{ v: 'daily', l: 'Daily' }, { v: 'weekly', l: 'Weekly' }], onChange: v => update('cleanup_frequency', v) })
            ])
        },
        {
            id: 'culprit', icon: 'find_in_page', title: 'Culprit Detection', content: () => el(Fragment, null, [
                el(SSelect, { label: 'Detection strategy', value: settings.culprit_strategy, options: [{ v: 'first', l: 'First plugin in stack trace' }, { v: 'deepest', l: 'Deepest plugin in stack trace' }], onChange: v => update('culprit_strategy', v) }),
                el(SToggle, { label: 'Ignore WordPress core frames', checked: settings.ignore_core, onChange: v => update('ignore_core', v) }),
                el(SToggle, { label: 'Ignore mu-plugins', checked: settings.ignore_mu_plugins, onChange: v => update('ignore_mu_plugins', v) }),
                el(SInput, { label: 'Blacklisted plugins (comma-separated)', value: settings.blacklisted_plugins, onChange: v => update('blacklisted_plugins', v), placeholder: 'plugin-a, plugin-b' })
            ])
        },
        {
            id: 'code', icon: 'code', title: 'Code Snippet View', content: () => el(Fragment, null, [
                el(SNumber, { label: 'Lines before error', value: settings.lines_before, onChange: v => update('lines_before', v) }),
                el(SNumber, { label: 'Lines after error', value: settings.lines_after, onChange: v => update('lines_after', v) }),
                el(SToggle, { label: 'Enable syntax highlighting', checked: settings.syntax_highlight, onChange: v => update('syntax_highlight', v) }),
                el(SToggle, { label: 'Dark theme for code', checked: settings.code_dark_theme, onChange: v => update('code_dark_theme', v) }),
                el(SNumber, { label: 'Max file size to read (KB)', value: settings.max_file_size_kb, onChange: v => update('max_file_size_kb', v), hint: 'Prevents memory issues' })
            ])
        },
        {
            id: 'context', icon: 'privacy_tip', title: 'Context Capture', content: () => el(Fragment, null, [
                el(SToggle, { label: 'Capture $_GET', checked: settings.capture_get, onChange: v => update('capture_get', v) }),
                el(SToggle, { label: 'Capture $_POST', checked: settings.capture_post, onChange: v => update('capture_post', v) }),
                el(SToggle, { label: 'Capture $_SERVER', checked: settings.capture_server, onChange: v => update('capture_server', v) }),
                el(SToggle, { label: 'Capture current user data', checked: settings.capture_user, onChange: v => update('capture_user', v) }),
                el(SToggle, { label: 'Capture cookies', checked: settings.capture_cookies, onChange: v => update('capture_cookies', v) }),
                el(SToggle, { label: 'Capture environment variables', checked: settings.capture_env, onChange: v => update('capture_env', v) }),
                el(SToggle, { label: 'Capture memory usage', checked: settings.capture_memory, onChange: v => update('capture_memory', v) }),
                el(SToggle, { label: 'Capture current_filter()', checked: settings.capture_filter, onChange: v => update('capture_filter', v) })
            ])
        },
        {
            id: 'performance', icon: 'speed', title: 'Performance & Safety', content: () => el(Fragment, null, [
                el(SToggle, { label: 'Disable frontend logging', checked: settings.disable_frontend, onChange: v => update('disable_frontend', v) }),
                el(SToggle, { label: 'Disable admin logging', checked: settings.disable_admin, onChange: v => update('disable_admin', v) }),
                el(SToggle, { label: 'Enable only for administrators', checked: settings.admin_only, onChange: v => update('admin_only', v) }),
                el(SToggle, { label: 'Safe mode (minimal context)', checked: settings.safe_mode, onChange: v => update('safe_mode', v) }),
                el(SToggle, { label: 'Log only once per request', checked: settings.log_once_per_request, onChange: v => update('log_once_per_request', v) }),
                el(SNumber, { label: 'Max errors per request', value: settings.max_errors_per_request, onChange: v => update('max_errors_per_request', v), hint: 'Prevents loops' })
            ])
        },
        {
            id: 'notifications', icon: 'notifications', title: 'Notifications', badge: 'v2.0', content: () => el(Fragment, null, [
                el('div', { className: 'text-[12px] text-[var(--tl-text-faint)] italic mb-3' }, 'Email, Slack, Webhooks, and Daily Digests are coming in v2.0.'),
                el(SToggle, { label: 'Email on fatal error', checked: false, disabled: true }),
                el(SToggle, { label: 'Slack webhook', checked: false, disabled: true }),
                el(SToggle, { label: 'Daily error summary digest', checked: false, disabled: true }),
                el(SToggle, { label: 'Real-time webhook trigger', checked: false, disabled: true })
            ])
        },
        {
            id: 'ui', icon: 'palette', title: 'UI Preferences', content: () => el(Fragment, null, [
                el(SSelect, { label: 'Theme', value: settings.ui_theme, options: [{ v: 'dark', l: 'Dark mode (default)' }, { v: 'light', l: 'Light mode' }], onChange: v => update('ui_theme', v) }),
                el(SToggle, { label: 'Compact view', checked: settings.ui_compact, onChange: v => update('ui_compact', v) }),
                el(SToggle, { label: 'Expanded stack traces by default', checked: settings.ui_expand_traces, onChange: v => update('ui_expand_traces', v) }),
                el(SToggle, { label: 'Show context sidebar', checked: settings.ui_show_sidebar, onChange: v => update('ui_show_sidebar', v) })
            ])
        },
        {
            id: 'developer', icon: 'terminal', title: 'Developer Mode', content: () => el(Fragment, null, [
                el(SToggle, { label: 'Enable Developer Mode', checked: settings.developer_mode, onChange: v => update('developer_mode', v) }),
                settings.developer_mode && el('div', { className: 'mt-3 p-3 bg-[var(--tl-primary-bg)] border border-[var(--tl-primary-border)] rounded-lg text-[11px] text-[var(--tl-text-body)]' }, [
                    el('p', { className: 'font-bold text-[var(--tl-primary-light)] mb-2 uppercase tracking-wider text-[10px]' }, 'When enabled, the dashboard will show:'),
                    el('ul', { className: 'space-y-1 ml-3 list-disc list-inside' }, [
                        el('li', null, 'Raw stack trace JSON'),
                        el('li', null, 'Request ID'),
                        el('li', null, 'Error hash'),
                        el('li', null, 'Internal grouping key'),
                        el('li', null, 'DB query time')
                    ])
                ])
            ])
        },
        {
            id: 'ai', icon: 'auto_fix_high', title: 'AI Integration', content: () => el(Fragment, null, [
                el(SToggle, { label: 'Enable AI-Powered Insights', checked: settings.ai_enabled, onChange: v => update('ai_enabled', v) }),
                settings.ai_enabled && el(Fragment, null, [
                    el(SSelect, { label: 'AI Provider', value: settings.ai_provider, options: [{ v: 'gemini', l: 'Google Gemini (Free Tier available)' }, { v: 'openai', l: 'OpenAI ChatGPT' }], onChange: v => update('ai_provider', v) }),
                    settings.ai_provider === 'gemini' && el(Fragment, null, [
                        el('div', { className: 'mt-1 mb-2' }, [
                            el('p', { className: 'text-[11px] text-[var(--tl-text-faint)] leading-relaxed' }, [
                                'Get a free API Key at ',
                                el('a', { href: 'https://aistudio.google.com/', target: '_blank', className: 'text-[var(--tl-primary)] hover:underline' }, 'Google AI Studio'),
                                '.'
                            ])
                        ]),
                        el(SInput, { label: 'Gemini API Key', value: settings.ai_gemini_key, onChange: v => update('ai_gemini_key', v), placeholder: 'AIzaSy...' }),
                        el(SSelect, { label: 'Model', value: settings.ai_gemini_model, options: [{ v: 'gemini-2.0-flash', l: 'Gemini 2.0 Flash (Fastest)' }, { v: 'gemini-2.5-flash', l: 'Gemini 2.5 Flash (Balanced)' }, { v: 'gemini-2.5-pro', l: 'Gemini 2.5 Pro (Deep)' }], onChange: v => update('ai_gemini_model', v) })
                    ]),
                    settings.ai_provider === 'openai' && el(Fragment, null, [
                        el('div', { className: 'mt-1 mb-2' }, [
                            el('p', { className: 'text-[11px] text-[var(--tl-text-faint)] leading-relaxed' }, [
                                'Requires an OpenAI API Key. Get one at ',
                                el('a', { href: 'https://platform.openai.com/', target: '_blank', className: 'text-[var(--tl-primary)] hover:underline' }, 'OpenAI Platform'),
                                '.'
                            ])
                        ]),
                        el(SInput, { label: 'OpenAI API Key', value: settings.ai_openai_key, onChange: v => update('ai_openai_key', v), placeholder: 'sk-...' }),
                        el(SSelect, { label: 'Model', value: settings.ai_openai_model, options: [{ v: 'gpt-4o-mini', l: 'GPT-4o mini (Fast & Cheap)' }, { v: 'gpt-4o', l: 'GPT-4o (Premium Diagnostic)' }], onChange: v => update('ai_openai_model', v) })
                    ])
                ])
            ])
        }
    ];

    return el('div', { className: 'font-display h-full flex flex-col overflow-hidden', style: themeVars }, [
        // Header
        el('header', { className: 'bg-[var(--tl-surface)] border-b border-[var(--tl-border)] h-14 flex items-center justify-between px-6 shrink-0' }, [
            el('div', { className: 'flex items-center gap-3' }, [
                el('a', { href: (window.bugsneakSettingsData?.dashboardUrl || '#'), className: 'p-1.5 text-[var(--tl-text-muted)] hover:text-[var(--tl-text)] hover:bg-[var(--tl-surface-hover)] rounded-lg transition-all', title: 'Back to Dashboard' },
                    el('span', { className: 'material-icons text-[20px]' }, 'arrow_back')),
                el('div', { className: 'flex items-center gap-2.5' }, [
                    el('div', { className: 'flex items-center justify-center' },
                        el('img', {
                            src: isDark ? window.bugsneakSettingsData?.logo_light : window.bugsneakSettingsData?.logo_dark,
                            className: 'h-8 w-auto',
                            alt: 'BugSneak'
                        })
                    ),
                    el('div', { className: 'flex items-center justify-center' },
                        el('img', {
                            src: window.bugsneakSettingsData?.logo_text,
                            className: 'h-8 w-auto ml-1',
                            style: { filter: isDark ? 'brightness(0) invert(1)' : 'none' },
                            alt: 'BugSneak'
                        })
                    ),
                    el('span', { className: 'text-[10px] font-bold text-[var(--tl-text-muted)] bg-[var(--tl-surface-hover)] px-2 py-0.5 rounded-md ml-2 uppercase tracking-wider' }, 'v1.2')
                ])
            ]),
            el('div', { className: 'flex items-center gap-2' }, [
                saved && el('span', { className: 'text-[11px] font-semibold text-[var(--tl-success)] flex items-center gap-1' }, [
                    el('span', { className: 'material-icons text-[14px]' }, 'check_circle'), 'Saved'
                ]),
                el('button', { onClick: () => setIsDark(!isDark), className: 'p-2 text-[var(--tl-text-muted)] hover:text-[var(--tl-primary)] transition-all rounded-lg hover:bg-[var(--tl-surface-hover)]', title: isDark ? 'Light Mode' : 'Dark Mode' },
                    el('span', { className: 'material-icons text-[20px]' }, isDark ? 'light_mode' : 'dark_mode')),
                el('button', { onClick: saveSettings, disabled: saving, className: 'px-5 py-2 bg-[var(--tl-primary)] text-white text-[11px] font-bold rounded-lg hover:bg-[var(--tl-primary-hover)] transition-all uppercase tracking-wider disabled:opacity-50 shadow-lg shadow-[var(--tl-primary)]/20' }, saving ? 'Saving...' : 'Save Changes')
            ])
        ]),

        // Content
        el('div', { className: 'flex-1 overflow-y-auto custom-scrollbar bg-[var(--tl-bg)]' },
            el('div', { className: 'max-w-3xl mx-auto px-6 py-6 space-y-5' }, [

                // Health Indicator
                el('div', { className: 'bg-[var(--tl-surface)] border border-[var(--tl-border)] rounded-xl p-5' }, [
                    el('div', { className: 'flex items-center gap-2.5 mb-4' }, [
                        el('div', { className: 'w-8 h-8 rounded-lg bg-gradient-to-br from-[var(--tl-success)] to-emerald-600 flex items-center justify-center shadow-lg' },
                            el('span', { className: 'material-icons text-white text-[18px]' }, 'shield')),
                        el('div', null, [
                            el('span', { className: 'text-[13px] font-bold text-[var(--tl-text)]' }, 'System Health'),
                            el('div', { className: 'flex items-center gap-1.5 mt-0.5' }, [
                                el('span', { className: 'w-2 h-2 rounded-full bg-[var(--tl-success)] animate-pulse' }),
                                el('span', { className: 'text-[10px] font-bold text-[var(--tl-success)] uppercase tracking-wider' }, 'All Systems Go')
                            ])
                        ])
                    ]),
                    el('div', { className: 'grid grid-cols-2 md:grid-cols-4 gap-3' }, [
                        el(HealthCard, { label: 'Status', value: 'Protected', icon: 'verified_user', color: 'var(--tl-success)' }),
                        el(HealthCard, { label: 'Logging', value: settings.capture_mode === 'production' ? 'Production' : 'Debug Mode', icon: 'radio_button_checked', color: 'var(--tl-primary)' }),
                        el(HealthCard, { label: 'Retention', value: `${settings.retention_days} days`, icon: 'schedule', color: 'var(--tl-warning)' }),
                        el(HealthCard, { label: 'Database', value: stats ? (stats.log_count > settings.max_rows * 0.9 ? 'Near Limit' : 'Optimal') : '...', icon: 'storage', color: stats && stats.log_count > settings.max_rows * 0.9 ? 'var(--tl-warning)' : 'var(--tl-success)' })
                    ])
                ]),

                // Accordion Sections
                el('div', { className: 'bg-[var(--tl-surface)] border border-[var(--tl-border)] rounded-xl overflow-hidden' },
                    sections.map(s => el('div', { key: s.id, className: 'border-b border-[var(--tl-border)] last:border-0' }, [
                        el('button', { onClick: () => toggle(s.id), className: 'w-full flex items-center gap-3 px-5 py-4 text-left hover:bg-[var(--tl-surface-hover)] transition-colors' }, [
                            el('span', { className: `material-icons text-[20px] ${openSection === s.id ? 'text-[var(--tl-primary)]' : 'text-[var(--tl-text-faint)]'}` }, s.icon),
                            el('span', { className: `flex-1 text-[13px] font-semibold ${openSection === s.id ? 'text-[var(--tl-text)]' : 'text-[var(--tl-text-secondary)]'}` }, s.title),
                            s.badge && el('span', { className: 'text-[9px] font-bold bg-[var(--tl-warning-bg)] text-[var(--tl-warning-text)] px-1.5 py-0.5 rounded uppercase tracking-wider' }, s.badge),
                            el('span', { className: `material-icons text-[16px] text-[var(--tl-text-faint)] transition-transform duration-200 ${openSection === s.id ? 'rotate-180' : ''}` }, 'expand_more')
                        ]),
                        openSection === s.id && el('div', { className: 'px-5 pb-5 space-y-3' }, s.content())
                    ]))
                ),

                // Footer
                el('div', { className: 'text-center py-4' },
                    el('p', { className: 'text-[10px] font-bold text-[var(--tl-text-faint)] uppercase tracking-[0.15em]' }, 'BugSneak · 100% Local · Zero External Calls')
                )
            ])
        )
    ]);
};

// ─── Health Card ────────────────────────────────────────────────────────────

const HealthCard = ({ label, value, icon, color }) => (
    el('div', { className: 'flex items-center gap-3 p-3 bg-[var(--tl-bg)] rounded-lg' }, [
        el('span', { className: 'material-icons text-[18px]', style: { color } }, icon),
        el('div', { className: 'flex-1 min-w-0' }, [
            el('div', { className: 'text-[9px] font-bold text-[var(--tl-text-faint)] uppercase tracking-wider' }, label),
            el('div', { className: 'text-[13px] font-bold text-[var(--tl-text)]' }, value)
        ])
    ])
);

// ─── UI Primitives ──────────────────────────────────────────────────────────

const SToggle = ({ label, checked, onChange, disabled, hint }) => (
    el('label', { className: `flex items-center justify-between py-2 ${disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'}` }, [
        el('div', null, [
            el('span', { className: 'text-[12px] font-medium text-[var(--tl-text-secondary)]' }, label),
            hint && el('span', { className: 'text-[10px] text-[var(--tl-text-faint)] ml-2' }, `(${hint})`)
        ]),
        el('div', {
            className: `relative w-9 h-5 rounded-full transition-colors ${checked ? 'bg-[var(--tl-primary)]' : 'bg-[var(--tl-surface-hover)]'}`,
            onClick: disabled ? undefined : (e) => { e.preventDefault(); onChange && onChange(!checked); }
        },
            el('div', { className: `absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform ${checked ? 'translate-x-4' : 'translate-x-0.5'}` })
        )
    ])
);

const SNumber = ({ label, value, onChange, hint }) => (
    el('div', { className: 'flex items-center justify-between py-2' }, [
        el('div', null, [
            el('span', { className: 'text-[12px] font-medium text-[var(--tl-text-secondary)]' }, label),
            hint && el('span', { className: 'text-[10px] text-[var(--tl-text-faint)] ml-2' }, `(${hint})`)
        ]),
        el('input', {
            type: 'number', value: value || 0, min: 0,
            onChange: (e) => onChange(parseInt(e.target.value) || 0),
            className: 'w-24 px-2.5 py-1.5 text-[12px] text-right bg-[var(--tl-input-bg)] border border-[var(--tl-border)] rounded-lg text-[var(--tl-text)] outline-none focus:ring-2 focus:ring-[var(--tl-primary)]/40'
        })
    ])
);

const SSelect = ({ label, value, options, onChange }) => (
    el('div', { className: 'flex items-center justify-between py-2' }, [
        el('span', { className: 'text-[12px] font-medium text-[var(--tl-text-secondary)]' }, label),
        el('select', {
            value, onChange: (e) => onChange(e.target.value),
            className: 'px-2.5 py-1.5 text-[12px] bg-[var(--tl-input-bg)] border border-[var(--tl-border)] rounded-lg text-[var(--tl-text)] outline-none focus:ring-2 focus:ring-[var(--tl-primary)]/40'
        }, options.map(o => el('option', { key: o.v, value: o.v }, o.l)))
    ])
);

const SInput = ({ label, value, onChange, placeholder }) => (
    el('div', { className: 'space-y-1.5 py-2' }, [
        el('span', { className: 'text-[12px] font-medium text-[var(--tl-text-secondary)]' }, label),
        el('input', {
            type: 'text', value: value || '', placeholder,
            onChange: (e) => onChange(e.target.value),
            className: 'w-full px-3 py-2 text-[12px] bg-[var(--tl-input-bg)] border border-[var(--tl-border)] rounded-lg text-[var(--tl-text)] placeholder-[var(--tl-text-faint)] outline-none focus:ring-2 focus:ring-[var(--tl-primary)]/40'
        })
    ])
);

// ─── Init ───────────────────────────────────────────────────────────────────

window.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('bugsneak-settings-app');
    if (root) wp.element.render(el(SettingsApp), root);
});
