const { useState, useEffect, createElement: el, Fragment } = wp.element;
const apiFetch = wp.apiFetch;

/**
 * BugSneak — Settings Page
 * Standalone settings page with Health Indicator + 10 configuration sections.
 */

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

    // Responsive viewport calculation
    const [viewportHeight, setViewportHeight] = useState('100vh');

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

    if (!settings) return el('div', { className: 'min-h-screen bg-slate-900 flex items-center justify-center' },
        el('div', { className: 'text-sm text-slate-400 animate-pulse font-mono' }, 'Loading settings...')
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
                el('div', { className: 'mt-4 pt-4 border-t border-slate-700' },
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
                stats && el('div', { className: 'bg-indigo-500/10 border border-indigo-500/20 rounded-lg p-4 mb-4 flex items-center justify-between' }, [
                    el('div', null, [
                        el('div', { className: 'text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1' }, 'Database Usage'),
                        el('div', { className: 'text-[14px] font-bold text-white' }, `${stats.log_count.toLocaleString()} logs · ${stats.db_size_human}`)
                    ]),
                    el('button', { onClick: purgeAll, className: 'px-3 py-1.5 bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 text-[10px] font-bold rounded-lg uppercase tracking-wider transition-all' }, 'Purge All')
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
                el('div', { className: 'text-[13px] text-slate-500 italic mb-4' }, 'Email, Slack, Webhooks, and Daily Digests are coming in v2.0.'),
                el(SToggle, { label: 'Email on fatal error', checked: false, disabled: true }),
                el(SToggle, { label: 'Slack webhook', checked: false, disabled: true }),
                el(SToggle, { label: 'Daily error summary digest', checked: false, disabled: true }),
                el(SToggle, { label: 'Real-time webhook trigger', checked: false, disabled: true })
            ])
        },
        {
            id: 'ui', icon: 'palette', title: 'UI Preferences', content: () => el(Fragment, null, [
                el('p', { className: 'text-[13px] text-slate-400 italic' }, 'Modern Dark Mode is active.'),
                // el(SSelect, { label: 'Theme', value: settings.ui_theme, options: [{ v: 'dark', l: 'Dark mode (default)' }, { v: 'light', l: 'Light mode' }], onChange: v => update('ui_theme', v) }),
                el(SToggle, { label: 'Compact view', checked: settings.ui_compact, onChange: v => update('ui_compact', v) }),
                el(SToggle, { label: 'Expanded stack traces by default', checked: settings.ui_expand_traces, onChange: v => update('ui_expand_traces', v) }),
                el(SToggle, { label: 'Show context sidebar', checked: settings.ui_show_sidebar, onChange: v => update('ui_show_sidebar', v) })
            ])
        },
        {
            id: 'developer', icon: 'terminal', title: 'Developer Mode', content: () => el(Fragment, null, [
                el(SToggle, { label: 'Enable Developer Mode', checked: settings.developer_mode, onChange: v => update('developer_mode', v) }),
                settings.developer_mode && el('div', { className: 'mt-4 p-4 bg-indigo-500/10 border border-indigo-500/20 rounded-lg text-[12px] text-indigo-200' }, [
                    el('p', { className: 'font-bold text-indigo-400 mb-2 uppercase tracking-wider text-[11px]' }, 'When enabled, the dashboard will show:'),
                    el('ul', { className: 'space-y-1 ml-4 list-disc list-inside text-indigo-300' }, [
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
                            el('p', { className: 'text-[12px] text-slate-400 leading-relaxed' }, [
                                'Get a free API Key at ',
                                el('a', { href: 'https://aistudio.google.com/', target: '_blank', className: 'text-indigo-400 hover:text-indigo-300 hover:underline' }, 'Google AI Studio'),
                                '.'
                            ])
                        ]),
                        el(SInput, { label: 'Gemini API Key', value: settings.ai_gemini_key, onChange: v => update('ai_gemini_key', v), placeholder: 'AIzaSy...' }),
                        el(SSelect, { label: 'Model', value: settings.ai_gemini_model, options: [{ v: 'gemini-2.0-flash', l: 'Gemini 2.0 Flash (Fastest)' }, { v: 'gemini-2.5-flash', l: 'Gemini 2.5 Flash (Balanced)' }, { v: 'gemini-2.5-pro', l: 'Gemini 2.5 Pro (Deep)' }], onChange: v => update('ai_gemini_model', v) })
                    ]),
                    settings.ai_provider === 'openai' && el(Fragment, null, [
                        el('div', { className: 'mt-1 mb-2' }, [
                            el('p', { className: 'text-[12px] text-slate-400 leading-relaxed' }, [
                                'Requires an OpenAI API Key. Get one at ',
                                el('a', { href: 'https://platform.openai.com/', target: '_blank', className: 'text-indigo-400 hover:text-indigo-300 hover:underline' }, 'OpenAI Platform'),
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



    return el('div', {
        id: 'bugsneak-settings-root',
        className: 'bg-slate-900 text-white font-display flex flex-col overflow-hidden',
        style: { height: viewportHeight }
    },
        el('div', { className: 'w-full h-full flex flex-col' }, [

            // Header (Fixed)
            el('header', { className: 'flex-none bg-slate-900/95 backdrop-blur z-10 border-b border-slate-800 px-8 py-4 flex items-center justify-between' }, [
                el('div', { className: 'flex items-center gap-4' }, [
                    el('a', { href: (window.bugsneakSettingsData?.dashboardUrl || '#'), className: 'p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-all', title: 'Back to Dashboard' },
                        el('span', { className: 'material-icons text-[24px]' }, 'arrow_back')),
                    el('div', { className: 'flex items-center gap-3' }, [
                        el('div', { className: 'flex items-center justify-center' },
                            el('img', {
                                src: window.bugsneakSettingsData?.logo_text,
                                className: 'h-8 w-auto',
                                style: { filter: 'brightness(0) invert(1)' },
                                alt: 'BugSneak'
                            })
                        ),
                        el('span', { className: 'text-[11px] font-bold text-slate-500 bg-slate-800 px-2 py-0.5 rounded-md uppercase tracking-wider' }, 'v1.3.4')
                    ])
                ]),
                el('div', { className: 'flex items-center gap-3' }, [
                    saved && el('span', { className: 'text-[12px] font-semibold text-emerald-400 flex items-center gap-1.5 animate-pulse' }, [
                        el('span', { className: 'material-icons text-[16px]' }, 'check_circle'), 'Saved'
                    ]),
                    el('button', { onClick: saveSettings, disabled: saving, className: 'px-6 py-2.5 bg-indigo-600 text-white text-[12px] font-bold rounded-lg hover:bg-indigo-500 transition-all uppercase tracking-wider disabled:opacity-50 shadow-lg shadow-indigo-500/20' }, saving ? 'Saving...' : 'Save Changes')
                ])
            ]),

            // Scrollable Content
            el('div', { className: 'flex-1 overflow-y-auto custom-scrollbar' },
                el('div', { className: 'max-w-6xl mx-auto px-8 py-8 space-y-6' }, [

                    // Health Indicator
                    el('div', { className: 'bg-slate-800 border border-slate-700 rounded-2xl p-6 shadow-xl' }, [
                        el('div', { className: 'flex items-center gap-4 mb-6' }, [
                            el('div', { className: 'w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center shadow-lg shadow-emerald-500/20' },
                                el('span', { className: 'material-icons text-white text-[22px]' }, 'shield')),
                            el('div', null, [
                                el('span', { className: 'text-[16px] font-bold text-white block leading-tight' }, 'System Health'),
                                el('div', { className: 'flex items-center gap-2 mt-1' }, [
                                    el('span', { className: 'w-2 h-2 rounded-full bg-emerald-400 animate-pulse' }),
                                    el('span', { className: 'text-[11px] font-bold text-emerald-400 uppercase tracking-wider' }, 'All Systems Go')
                                ])
                            ])
                        ]),
                        el('div', { className: 'grid grid-cols-2 md:grid-cols-4 gap-4' }, [
                            el(HealthCard, { label: 'Status', value: 'Protected', icon: 'verified_user', color: '#10b981' }),
                            el(HealthCard, { label: 'Logging', value: settings.capture_mode === 'production' ? 'Production' : 'Debug Mode', icon: 'radio_button_checked', color: '#6366f1' }),
                            el(HealthCard, { label: 'Retention', value: `${settings.retention_days} days`, icon: 'schedule', color: '#f59e0b' }),
                            el(HealthCard, { label: 'Database', value: stats ? (stats.log_count > settings.max_rows * 0.9 ? 'Near Limit' : 'Optimal') : '...', icon: 'storage', color: stats && stats.log_count > settings.max_rows * 0.9 ? '#f59e0b' : '#10b981' })
                        ])
                    ]),

                    // Accordion Sections
                    el('div', { className: 'bg-slate-800 border border-slate-700 rounded-2xl overflow-hidden shadow-xl' },
                        sections.map(s => el('div', { key: s.id, className: 'border-b border-slate-700 last:border-0' }, [
                            el('button', { onClick: () => toggle(s.id), className: 'w-full flex items-center gap-4 px-6 py-5 text-left hover:bg-slate-700/50 transition-colors group' }, [
                                el('span', { className: `material-icons text-[22px] transition-colors ${openSection === s.id ? 'text-indigo-400' : 'text-slate-500 group-hover:text-slate-400'}` }, s.icon),
                                el('span', { className: `flex-1 text-[14px] font-semibold transition-colors ${openSection === s.id ? 'text-white' : 'text-slate-400 group-hover:text-slate-200'}` }, s.title),
                                s.badge && el('span', { className: 'text-[10px] font-bold bg-amber-500/10 text-amber-400 px-2 py-0.5 rounded uppercase tracking-wider' }, s.badge),
                                el('span', { className: `material-icons text-[20px] text-slate-500 transition-transform duration-200 ${openSection === s.id ? 'rotate-180' : ''}` }, 'expand_more')
                            ]),
                            openSection === s.id && el('div', { className: 'px-6 pb-6 space-y-4 animate-fadeIn' }, s.content())
                        ]))
                    ),

                    // Footer
                    el('div', { className: 'text-center py-8' },
                        el('p', { className: 'text-[11px] font-bold text-slate-600 uppercase tracking-[0.2em]' }, 'BugSneak · 100% Local · Zero External Calls')
                    )
                ])
            )
        ])
    );
};

// ─── Health Card ────────────────────────────────────────────────────────────

const HealthCard = ({ label, value, icon, color }) => (
    el('div', { className: 'flex items-center gap-3 p-4 bg-slate-900/50 rounded-xl border border-slate-700/50' }, [
        el('span', { className: 'material-icons text-[20px]', style: { color } }, icon),
        el('div', { className: 'flex-1 min-w-0' }, [
            el('div', { className: 'text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-0.5' }, label),
            el('div', { className: 'text-[14px] font-bold text-slate-200' }, value)
        ])
    ])
);

// ─── UI Primitives ──────────────────────────────────────────────────────────

const SToggle = ({ label, checked, onChange, disabled, hint }) => (
    el('label', { className: `flex items-center justify-between py-3 ${disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer group'}` }, [
        el('div', null, [
            el('span', { className: 'text-[13px] font-medium text-slate-300 group-hover:text-white transition-colors' }, label),
            hint && el('span', { className: 'text-[11px] text-slate-500 ml-2' }, `(${hint})`)
        ]),
        el('div', {
            className: `relative w-10 h-6 rounded-full transition-colors ${checked ? 'bg-indigo-600' : 'bg-slate-700'}`,
            onClick: disabled ? undefined : (e) => { e.preventDefault(); onChange && onChange(!checked); }
        },
            el('div', { className: `absolute top-1 w-4 h-4 bg-white rounded-full shadow-sm transition-transform ${checked ? 'translate-x-5' : 'translate-x-1'}` })
        )
    ])
);

const SNumber = ({ label, value, onChange, hint }) => (
    el('div', { className: 'flex items-center justify-between py-3' }, [
        el('div', null, [
            el('span', { className: 'text-[13px] font-medium text-slate-300' }, label),
            hint && el('span', { className: 'text-[11px] text-slate-500 ml-2' }, `(${hint})`)
        ]),
        el('input', {
            type: 'number', value: value || 0, min: 0,
            onChange: (e) => onChange(parseInt(e.target.value) || 0),
            className: 'w-24 px-3 py-1.5 text-[13px] text-right bg-slate-900 border border-slate-700 rounded-lg text-white outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all'
        })
    ])
);

const SSelect = ({ label, value, options, onChange }) => (
    el('div', { className: 'flex items-center justify-between py-3' }, [
        el('span', { className: 'text-[13px] font-medium text-slate-300' }, label),
        el('select', {
            value, onChange: (e) => onChange(e.target.value),
            className: 'px-3 py-1.5 text-[13px] bg-slate-900 border border-slate-700 rounded-lg text-white outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all cursor-pointer'
        }, options.map(o => el('option', { key: o.v, value: o.v }, o.l)))
    ])
);

const SInput = ({ label, value, onChange, placeholder }) => (
    el('div', { className: 'space-y-2 py-3' }, [
        el('span', { className: 'text-[13px] font-medium text-slate-300' }, label),
        el('input', {
            type: 'text', value: value || '', placeholder,
            onChange: (e) => onChange(e.target.value),
            className: 'w-full px-4 py-2.5 text-[13px] bg-slate-900 border border-slate-700 rounded-lg text-white placeholder-slate-600 outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all'
        })
    ])
);

// ─── Init ───────────────────────────────────────────────────────────────────

window.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('bugsneak-settings-app');
    if (root) wp.element.render(el(SettingsApp), root);
});
