# HamariJobs — Hostinger Production Deployment Guide (0% Code Breakage Guarantee)

This guide provides step-by-step instructions for deploying **HamariJobs** to **Hostinger Web / Cloud Hosting (hPanel)** with zero downtime, zero broken links, and 100% database & asset availability.

---

## Architecture Overview
- **Runtime**: PHP 8.0 / 8.1 / 8.2 (Native Apache with `mod_rewrite`).
- **Database**: MySQL 8.0 / MariaDB.
- **Routing**: Universal Hybrid `.htaccess` (works seamlessly whether document root is `public_html` or `public_html/backend/public`).
- **Automation Pipeline**: Python 3.9+ with automated hPanel Cron jobs.

---

## Step 1: Create MySQL Database in Hostinger hPanel

1. Log in to your [Hostinger hPanel](https://hpanel.hostinger.com/).
2. Navigate to **Databases** → **Management**.
3. Create a new MySQL database:
   - **Database Name**: e.g., `u123456789_hamarijobs`
   - **Username**: e.g., `u123456789_admin`
   - **Password**: (Generate a strong password and save it)
4. Click **Create**.
5. Once created, click **Enter phpMyAdmin** next to your new database.
6. In phpMyAdmin, click on the **Import** tab at the top.
7. Click **Choose File** and select:
   ```
   database/production_master_schema.sql
   ```
8. Click **Import / Go** at the bottom.
   > **Result**: All 28 tables, triggers, 13 Commissions, 14 Exam Hubs, 21 Recruitments, 94 Cutoffs, 63 Articles, and Default Admin account will be imported instantly.

---

## Step 2: Deploy Code to Hostinger

### Option A: Using Hostinger Git Integration (Recommended — Auto Deployment)
1. In hPanel, go to **Advanced** → **GIT**.
2. Fill in the repository details:
   - **Repository URL**: `https://github.com/jobrecruitmentdev-lang/hamarijobs.git`
   - **Branch**: `main`
   - **Install Directory**: leave blank (or `public_html` depending on your domain setup).
3. Click **Create**.
4. Click **Deploy** to pull the latest code.
   *(Every time you push to GitHub `main`, click "Deploy" or set up the Hostinger Webhook for auto-deploy).*

### Option B: Using SSH (Terminal)
1. In hPanel, enable **SSH Access** under **Advanced** → **SSH Access**.
2. Connect to your server using Terminal or PuTTY:
   ```bash
   ssh -p 65002 u123456789@your-server-ip
   ```
3. Navigate to your website directory:
   ```bash
   cd domains/yourdomain.com/public_html
   git clone https://github.com/jobrecruitmentdev-lang/hamarijobs.git .
   ```

---

## Step 3: Configure Environment Variables (`.env`)

In Hostinger File Manager or via SSH, create or edit the `.env` file in the project root:

```ini
# ============================================================================
# HAMARIJOBS PRODUCTION CONFIGURATION
# ============================================================================
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
API_V1_STR=/api/v1
PROJECT_NAME="HamariJobs"

# SECURITY & SECRETS
SECRET_KEY=generate_a_random_64_character_hex_key_here
ACCESS_TOKEN_EXPIRE_MINUTES=1440
INTERNAL_API_SECRET=gov_sec_sync_k9a2b8e4f1c7d3a5e8b0c2d4e6f8a0b2

# DATABASE (Your Hostinger MySQL Credentials)
MYSQL_HOST=localhost
MYSQL_PORT=3306
MYSQL_USER=u123456789_admin
MYSQL_PASSWORD=your_hostinger_db_password
MYSQL_DB=u123456789_hamarijobs

# AI API KEYS (Optional: Fallback engine activates automatically if keys expire)
OPENAI_API_KEY=
GEMINI_API_KEY=
DEFAULT_LLM_MODEL=gemini-2.5-flash
FALLBACK_LLM_MODEL=gpt-4o-mini
```

---

## Step 4: Verify Web Server Directory Routing

HamariJobs includes **Universal Hybrid `.htaccess` files**:
1. **If your domain points to `public_html`**:
   The root `.htaccess` will automatically protect all backend code, routes `/css/`, `/js/`, and `/assets/` directly, and forwards dynamic requests to `backend/public/index.php`.
2. **If your domain points to `public_html/backend/public`**:
   The `backend/public/.htaccess` serves all assets and rewrites dynamic URLs cleanly.

**Zero Configuration Needed**: It works out of the box in either scenario without changing a single line of PHP code.

---

## Step 5: Set up Automated Crawling (Cron Job) in Hostinger

To automatically check for new government recruitments and keep sitemaps updated every 4 hours:

1. In hPanel, navigate to **Advanced** → **Cron Jobs**.
2. Select **Custom** type.
3. Schedule: **Once every 4 hours** (`0 */4 * * *`).
4. Command:
   ```bash
   python3 /home/u123456789/domains/yourdomain.com/public_html/automation/live_ingestion_pipeline.py SCHEDULED_DAEMON
   ```
   *(Replace `/home/u123456789/...` with your exact Hostinger home directory path shown in hPanel).*

---

## Step 6: Verify Live Website & Admin Access

1. **Public Portal**: Open `https://yourdomain.com/` in your browser.
   - [Government Jobs](https://yourdomain.com/government-jobs) — Verify active recruitments.
   - [Commissions](https://yourdomain.com/commissions) — Verify all 13 commissions.
   - [Exam Hubs](https://yourdomain.com/exams) — Verify all 14 master exams.
   - [Admit Cards](https://yourdomain.com/admit-cards) — Verify hall tickets.
   - [Results](https://yourdomain.com/results) — Verify merit lists & cutoffs.
   - [Preparation Guides](https://yourdomain.com/articles) — Verify 60+ guides.

2. **Admin Console Access**:
   - URL: `https://yourdomain.com/admin/login`
   - **Default Email**: `admin@hamarijobs.com`
   - **Default Password**: `Admin@123456`
   *(Immediately update your password in the admin settings after first login).*

---

## Troubleshooting & FAQ

- **Q: What if Hostinger throws 500 Internal Server Error?**
  - Check `.env` database credentials (`MYSQL_HOST=localhost`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DB`).
  - Ensure `mod_rewrite` is active (default on Hostinger Apache).
- **Q: Are sensitive files protected from public access?**
  - Yes. `.env`, `.git`, `storage/`, `automation/`, and `database/` are blocked with `HTTP 403 Forbidden` via `.htaccess`.
- **Q: What if Python dependencies are missing on shared hosting?**
  - The PHP frontend is completely self-sufficient. Python is only needed for the crawler pipeline. You can run `run_automation_now.bat` locally on your PC, which directly updates the remote database if remote MySQL access is allowed in hPanel.
