# BugSneak — Development Roadmap
> Adaptive Crash Intelligence for WordPress

## Vision
Inspired by modern crash intelligence tools, BugSneak uses **Pattern-Aware Error Diagnostics** to provide the cleanest, fastest way to understand crashes inside WP Admin.

---

## The 3 Intelligence Layers

### Layer 1 — Crash Pattern Detection (Local) 🔲
*Aims to detect frequency and loops without external calls.*
- [ ] First seen / Last seen tracking ✅
- [ ] Occurrence trend detection (spikes)
- [ ] Repeated fatal loop detection (auto-silencing)

### Layer 2 — Error Classification Engine (Rule-Based) 🔲
*Instant suggestions for common WordPress pitfalls.*
- [ ] Logic to match error strings against a local rules array
- [ ] Classifications (e.g., "Memory Exhaustion", "Missing Function")
- [ ] Local suggestions (e.g., "Increase WP_MEMORY_LIMIT")

### Layer 3 — AI On-Demand (Hybrid Intelligence) ✅
*Deep contextual analysis when you need it.*
- [x] On-demand analysis via Google Gemini/OpenAI
- [x] Context-aware fix suggestions
- [x] Markdown-formatted diagnostic reports

---

## Roadmap Phases

### Phase 1 — MVP Core ✅
- [x] Intelligent grouping (fingerprint hash)
- [x] Basic culprit detection (plugin/theme pinpointing)
- [x] Rich Context Capture (WP State, User Role)
- [x] MU-Loader for early-boot crash capture

### Phase 2 — Modern Dashboard ✅
- [x] React + Tailwind dark mode UI
- [x] Code snippet engine (±5 lines, syntax coloring)
- [x] System Health indicators

### Phase 3 — WordPress.org Launch Prep 🔲
- [x] Clean README with positioning ("Adaptive Crash Intelligence")
- [ ] Professional screenshots
- [ ] Performance benchmark section

### Phase 4 — Future Ecosystem 🔲
- [ ] Filtering & Search
- [ ] Email/Webhook Notifications
- [ ] Export & sharing (Diagnostic Bundle)

---

## Non-Negotiable Rules
- **No Overengineering:** Stay as a tool, not an infrastructure company.
- **No Global Fingerprinting:** Keep log data local and private.
- **Maintainable Code:** Rule-based logic first, AI second.
