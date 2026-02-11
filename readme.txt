=== BugSneak ===
Contributors: bugsneak
Tags: debug, error log, stack trace, fatal error, php error
Requires at least: 6.2
Tested up to: 6.9
Stable tag: 1.3.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Catch Fatal Errors and Exceptions with interactive diagnostics and optional AI-powered insights.

BugSneak is a modern Crash Intelligence System for WordPress.

Instead of digging through massive `debug.log` files or guessing which plugin caused a white screen, BugSneak captures Fatal Errors, Exceptions, and Warnings and presents them in a curated, developer-focused dashboard.

It intelligently groups repeated crashes, pinpoints the suspected plugin or theme, and shows the exact code snippet where things broke — all inside your WordPress admin.

BugSneak focuses entirely on crash clarity, not performance profiling.

## Vision
Inspired by modern crash intelligence tools, BugSneak brings curated diagnostics directly into WordPress — the cleanest, fastest way to understand crashes inside WP Admin.

== Features ==

*   **Intelligent Error Grouping:** Automatically merges identical errors to prevent log explosion.
*   **Precise Culprit Detection:** Instantly identifies which plugin or theme is responsible for the crash.
*   **Code Snippet Preview:** View the exact file and line of the error with ±5 lines of context right in the dashboard.
*   **Rich Context Capture:** See User Identity ($ID, Role), WP State, PHP Version, and Memory Usage at the moment of failure.
*   **Early Crash Capture:** Uses an MU (Must-Use) loader to catch errors that happen before normal plugins even load.
*   **Production Safe:** Near-zero overhead during normal requests. Safely logs in the background without exposing errors to visitors.
*   **AI-Powered Insights (v1.3):** Integrated **Hybrid Intelligence**. Features on-demand error analysis via Google Gemini or OpenAI ChatGPT to explain crashes and suggest fixes instantly. AI analysis is optional and requires a valid API key. Error data is sent only when you explicitly request AI insights.
*   **Infrastructure Health:** Dedicated settings page with live health indicators and performance safety guards.

== Privacy Notice ==

BugSneak stores all error logs locally in your WordPress database.

If you enable AI Insights and provide an API key, the selected error message, stack trace, and related diagnostic context will be sent to the chosen AI provider (Google Gemini or OpenAI) only when you explicitly request analysis.

No data is transmitted automatically.

== Installation ==

1. Upload the `bugsneak` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access your logs by navigating to **Tools > BugSneak**.
4. (Optional) Configure retention and safety settings in **Tools > BugSneak Settings**.

== Frequently Asked Questions ==

= Is this a replacement for Query Monitor? =
No. Query Monitor is for performance and debugging tools (SQL, Hooks, etc.). BugSneak is for **Crash Intelligence**. It focuses 100% on understanding why your site died.

= Will this slow down my site? =
No. BugSneak only executes its heavy logic when an error occurs. During normal operation, it adds negligible overhead and only performs logging when an error occurs.

= Does it catch out-of-memory errors? =
Yes. By using the registered shutdown handler and an MU loader, it catches most fatal exhaustion errors.

= Does BugSneak send data to external services automatically? =
No. All logging is stored locally. External AI providers are only contacted when you manually request AI analysis and provide your own API key.

== Screenshots ==

1. The modern BugSneak dashboard with card-based error grouping.
2. Detailed error view with stack traces and code snippet previews.
3. Dedicated settings page with System Health indicators.

== Changelog ==

= 1.3.4 =
* Bugfix: Resolved `MarkdownContent` reference error in Dashboard.
* Polish: Corrected Tailwind inline script initialization.
* Refactor: Optimized AI provider-specific model settings.

= 1.3.0 =
* Feature: AI Insight Engine. Integrated Google Gemini & OpenAI ChatGPT support.
* Feature: Hybrid Intelligence UI. On-demand AI analysis with localized fallback.
* Refinement: Provider-based AI settings for easier API key management.

= 1.2.0 =
* Major Refactor: Standalone Settings page with Health Indicators.
* Performance: Optimized database indexes (last_seen, created_at).
* Added: Culprit strategy selection (First vs Deepest).
* Reliability: Added MU-Loader for early-boot crash capture.
* Safety: Added capacity guards and memory safety during log insertion.

= 1.1.0 =
* Added intelligent error grouping.
* Implemented culprit detection logic.
* Added code snippet context view.

= 1.0.0 =
* Initial release: The modern WordPress error catcher is born.
