# ⚡ AI Portfolio Optimizer & Mock Interviewer

A full-stack mini-SaaS application for web developers and freelancers to **review code** with AI and **practice technical interviews** — built with PHP (OOP), MySQL, Tailwind CSS, and TypeScript, powered by the **Google Gemini API**.

---

## 🚀 Features

| Feature | Description |
|---|---|
| 🔍 **AI Code Reviewer** | Split-pane editor — paste any HTML/CSS/JS/PHP/TS and get expert AI feedback with performance analysis, security audit, refactored code, and a quality score. |
| 🎤 **Mock Interviewer** | Real-time AI chat that acts as a technical interviewer — asks scored questions based on your role (Frontend, Backend, Full-Stack), evaluates answers, and gives a final scorecard. |
| 📊 **Dashboard** | Personal overview of stats, recent reviews, and interview sessions with score history. |
| 📋 **History** | Paginated log of all code reviews and interview sessions. |
| 🔐 **Auth System** | Secure registration, login with bcrypt, CSRF protection, session management. |
| 🛡️ **Security** | PDO prepared statements, XSS sanitization, rate limiting, security headers. |

---

## 🗂️ Project Structure

```
Freelancing-web/
├── .htaccess                   # Apache security rules
├── .gitignore
├── tsconfig.json               # TypeScript config
│
├── config/
│   ├── config.php              # ⚠️ API keys & DB credentials (DO NOT COMMIT)
│   └── database.php            # Singleton PDO wrapper
│
├── database/
│   └── schema.sql              # MySQL schema + seed
│
├── src/
│   └── classes/
│       ├── Auth.php            # Registration, login, session guards, CSRF
│       ├── AIService.php       # Gemini/OpenAI cURL bridge + system prompts
│       └── RateLimiter.php     # DB-backed per-user rate limiting
│
├── api/
│   ├── auth.php                # Login/register/logout/status endpoint
│   ├── review.php              # AI code review endpoint
│   └── chat.php                # Mock interview stateful chat endpoint
│
└── public/
    ├── index.php               # Landing page
    ├── login.php               # Login
    ├── register.php            # Registration
    ├── dashboard.php           # User dashboard
    ├── reviewer.php            # Code reviewer split-pane UI
    ├── interviewer.php         # Mock interview chat UI
    ├── history.php             # Activity history
    └── assets/
        ├── css/
        │   └── app.css         # Custom design system extending Tailwind
        └── js/
            ├── app.ts/.js      # Auth bootstrapper, API helper, toasts
            ├── reviewer.ts/.js # Code reviewer page logic
            └── interviewer.ts/.js # Interview chat page logic
```

---

## ⚙️ Setup Guide

### Prerequisites
- **PHP** 8.1+
- **MySQL** 8.0+
- **Apache** with `mod_rewrite` enabled (XAMPP / WAMP / Laragon)
- A **Google Gemini API key** (free at [aistudio.google.com](https://aistudio.google.com))

### 1. Clone & Configure

```bash
git clone <your-repo-url>
cd Freelancing-web
```

### 2. Configure API Keys & Database

Edit **`config/config.php`**:

```php
define('GEMINI_API_KEY', 'YOUR_ACTUAL_GEMINI_KEY');
define('DB_USER', 'root');
define('DB_PASS', 'your_db_password');
```

### 3. Create the Database

Import the schema into MySQL:

```bash
mysql -u root -p < database/schema.sql
```

Or open **phpMyAdmin** → Import → select `database/schema.sql`.

### 4. Configure your Web Server

**For XAMPP/WAMP:**  
Move or symlink the project into `htdocs/Freelancing-web/`.

Update `APP_URL` in `config/config.php`:

```php
define('APP_URL', 'http://localhost/Freelancing-web/public');
```

### 5. Visit the App

```
http://localhost/Freelancing-web/public/
```

Default admin account: `admin@aiportfolio.dev` / `password` (change immediately!)

---

## 🔑 Getting a Gemini API Key

1. Go to [Google AI Studio](https://aistudio.google.com/app/apikey)
2. Click **"Create API Key"**
3. Copy the key into `config/config.php` as `GEMINI_API_KEY`

**Free tier:** 15 RPM, 1M tokens/day — plenty for development.

---

## 🔐 Security Notes

- **Never commit `config/config.php`** — it contains secrets. It's in `.gitignore`.
- CSRF tokens are regenerated on every login.
- All DB queries use **PDO prepared statements** — no SQL injection possible.
- Rate limiting is enforced per-user, per-endpoint in the database.
- All user input is sanitized with `htmlspecialchars()` before storage.

---

## 📦 TypeScript (Optional Build)

The `.js` files are pre-compiled and ready to use. To recompile from `.ts`:

```bash
npm install -g typescript
tsc --watch
```

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, Tailwind CSS (CDN), TypeScript → JS |
| Backend | PHP 8.1 OOP (PDO, cURL, Sessions) |
| Database | MySQL 8.0 (normalized, indexed) |
| AI | Google Gemini 1.5 Flash API |
| Markdown | marked.js |
| Syntax Highlighting | highlight.js |

---

## 📄 License

MIT — free to use, modify, and distribute.
