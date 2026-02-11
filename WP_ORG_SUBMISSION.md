# 🚀 Publishing BugSneak to WordPress.org

Congratulations on building a production-ready plugin! Here is your checklist to get BugSneak published on the official WordPress.org Plugin Repository.

## 1. Prepare Your Assets 🎨

WordPress.org requires specific image sizes for your plugin page. You need to create a folder named `assets` (separate from your plugin code) or commit these to the SVN `assets` directory later.

**Required Files:**
-   **`icon-256x256.png`**: High-res plugin icon. (Use your `logo-dark-new.png` as a base).
-   **`icon-128x128.png`**: Standard-res icon.
-   **`banner-772x250.png`**: Plugin page header banner.
-   **`banner-1544x500.png`**: High-res banner (Retina).
-   **`screenshot-1.png`, `screenshot-2.png`**: (Optional but recommended) Screenshots of the dashboard.

> **Tip:** Do NOT put these inside your plugin's `assets/` folder in the ZIP. These go into the **SVN assets directory** after your plugin is approved.

## 2. Final Code Check 🛡️

We've already done most of this, but double-check:
-   [x] **`readme.txt` Validation:** Copy your `readme.txt` content and paste it into the [Official Readme Validator](https://wordpress.org/plugins/developers/readme-validator/).
-   [x] **Stable Tag:** Ensure `Stable tag: 1.3.5` in `readme.txt` matches `Version: 1.3.5` in `bugsneak.php`.
-   [x] **License:** Ensure `LICENSE` file is present (GPLv2 or later).
-   [x] **No Prohibited Code:** No `eval()`, no automatic updates, no external calls without user consent (AI is opt-in, so we are good).

## 3. Create the Submission ZIP 📦

1.  Create a clean folder named `bugsneak`.
2.  Copy ONLY the necessary files into it:
    *   `src/`
    *   `assets/` (The plugin's JS/CSS assets, NOT the banners/icons)
    *   `languages/`
    *   `bugsneak.php`
    *   `readme.txt`
    *   `LICENSE`
    *   `index.php` (silence is golden)
3.  **Exclude:** `.git`, `.github`, `.gitignore`, `tests`, `node_modules`, `README.md` (WP uses `readme.txt`), `CONTRIBUTING.md`.
4.  Compress the `bugsneak` folder into `bugsneak.zip`.

## 4. Submit Your Plugin 📤

1.  Log in to [WordPress.org](https://login.wordpress.org/).
2.  Go to the [Add Your Plugin](https://wordpress.org/plugins/developers/add/) page.
3.  Upload your `bugsneak.zip`.
4.  **Wait for Review:** This can take 1-10 days. They will email you.

## 5. After Approval (SVN) 🐢

Once approved, you will get access to an SVN repository.

1.  **Check out the SVN repo:** `svn co https://plugins.svn.wordpress.org/bugsneak my-local-dir`
2.  **Add your code:** Copy your plugin files to `trunk/`.
3.  **Add exact version:** Copy your files to `tags/1.3.5/`.
4.  **Add assets:** Copy your icons and banners to `assets/`.
5.  **Commit:** `svn ci -m "Initial release 1.3.5"`

## 6. Celebrate! 🎉

Your plugin will be live on `https://wordpress.org/plugins/bugsneak/`.
