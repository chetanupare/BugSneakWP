# BugSneak: Adaptive Crash Intelligence for WordPress

![BugSneak Banner](.assets/banner.png)

> **Catch Fatal Errors, Exceptions, and Warnings with interactive diagnostics and optional AI-powered insights.**

Instead of digging through massive `debug.log` files or guessing which plugin caused a white screen, **BugSneak** captures crashes and presents them in a curated, developer-focused dashboard. It intelligently groups repeated errors, pinpoints the suspected culprit, and shows the exact code snippet where things broke — all inside your WordPress admin.

![Dashboard Preview](https://raw.githubusercontent.com/chetanupare/BugSneak/main/.assets/dashboard.png)

## ✨ Key Features

- **🔍 Precise Culprit Detection:** Instantly identifies which plugin or theme is responsible for the crash.
- **🧠 Context-Aware Intelligence (v1.3.5):** Dynamic confidence scoring based on PHP/WP versions, request context, and detected environment signals.
- **📈 Spike Detection:** Detects rapid bursts of repeated errors to highlight abnormal patterns.
- **🔎 Code Snippet Preview:** View the exact file and line of the error with ±5 lines of context right in the dashboard.
- **🛡️ Production Safe:** Near-zero overhead. Safely logs in the background without affecting visitors.
- **🤖 Hybrid Intelligence:** Optional on-demand analysis via Google Gemini or OpenAI ChatGPT (requires API key).
- **🏥 Infrastructure Health:** Dedicated settings page with live health indicators.

## 🚀 Installation

1. Download the latest release `.zip`.
2. Upload to your WordPress plugins directory (`/wp-content/plugins/`).
3. Activate **BugSneak** via the clean plugin menu.
4. Navigate to **Tools > BugSneak** to view your error logs.

## 🤝 Contributing

BugSneak is an open-source project. We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## 🔒 Security

If you discover a security vulnerability, please check [SECURITY.md](SECURITY.md) for our responsible disclosure policy.

## 📜 License

BugSneak is licensed under the GPLv2 or later. See [LICENSE](LICENSE) for details.

---

**Built with ❤️ for the WordPress Community.**
