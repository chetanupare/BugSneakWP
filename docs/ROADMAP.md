# BugSneak — Development Roadmap
> Reputation-Focused, Crash Intelligence Only

## Vision
Inspired by modern crash intelligence tools, BugSneak brings curated diagnostics directly into WordPress — the cleanest, fastest way to understand crashes inside WP Admin.

---

## Phase 0 — Foundation ✅
- [x] Namespaced PSR-style structure + autoloader
- [x] Activation/deactivation hooks
- [x] Secure capability checks (`manage_options`)
- [x] Database schema (`wp_bugsneak_logs`) with fingerprint, message, file, line, severity, stack_trace, culprit, first_seen, last_seen, occurrence_count, context_json
- [x] Error interception: `set_error_handler`, `set_exception_handler`, `register_shutdown_function`

## Phase 1 — MVP Core ✅
- [x] Intelligent grouping (fingerprint hash, occurrence_count, last_seen)
- [x] Basic culprit detection (first plugin/theme in stack trace)
- [x] Minimal context capture (user, role, WP/PHP version, theme, memory, current_filter)
- [x] Retention system (7/30/60/90 days dropdown, cron cleanup, Clear All)

## Phase 2 — Modern Dashboard ✅
- [x] Custom page (Tools > BugSneak), React + Tailwind, dark mode
- [x] Error cards with severity badge, message, culprit, occurrence count, last seen
- [x] Detail panel with tabs: Stack Trace, Code Snippet, Context, Environment
- [x] Code snippet engine (±5 lines, error line highlight, syntax coloring)

## Phase 3 — Polish & Stability ✅
- [x] Max errors per request, admin-only mode, log-once protection
- [x] File size guard, memory safety, graceful DB failure
- [x] Settings page (10 sections, Health Indicator)
- [ ] Database index optimization (fingerprint, last_seen)
- [ ] Stress testing (5K notices, fatal loops, grouping verification)

## Phase 4 — WordPress.org Launch Prep 🔲
- [ ] Clean README with positioning + comparison
- [ ] Professional screenshots (dark mode, cards, code snippet, culprit)
- [ ] Performance benchmark section
- [ ] Community launch posts

## Post-Launch (After User Feedback)
- Phase 5: Filtering & search (severity, text, date)
- Phase 6: Notifications (email on fatal, webhooks)
- Phase 7: Export & sharing (JSON, diagnostic bundle)
- Phase 8: Multi-site support

---

## Non-Negotiable Rules
- Never add: SQL monitoring, hook explorer, template hierarchy, performance engine
- If it doesn't help understand crashes faster → don't build it
