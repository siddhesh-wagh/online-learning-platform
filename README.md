
---

### ✅ `README.md` for `online-learning-platform`

````markdown
# 🎓 Online Learning Platform

A complete web-based platform built with PHP and MySQL for managing online learning. The system includes user registration with email verification, role-based access, and a responsive email template using HTML/CSS.

🔗 **Live Demo (Optional):** Coming soon  
📂 **Repository:** [siddhesh-wagh/online-learning-platform](https://github.com/siddhesh-wagh/online-learning-platform)

---

## 🚀 Features

- 🔐 Secure User Registration with Email Verification
- 👥 Role-Based Access (Learners, Instructors, Admins)
- 📧 HTML Email Template with Professional Styling
- 🔑 Password Hashing and Token-Based Verification
- 📊 MySQL Database Integration
- 🧩 Modular Codebase (`includes/`, `auth/`, `db-config.php`)
- 📦 Clean and Maintainable Project Structure

---

## 🛠️ Tech Stack

- **Backend:** PHP (Procedural + MySQLi)
- **Database:** MySQL (`online_learning`)
- **Frontend:** HTML5, CSS3 (email templates)
- **Mailing:** PHPMailer

---

## 🗂️ Project Structure

```bash
online-learning-platform/
│
├── auth/
│   └── verify.php               # Email verification script
│
├── includes/
│   ├── mailer.php              # Mail sending logic using PHPMailer
│   ├── functions.php           # Utility functions like logging
│
├── db-config.php               # Database connection file
├── register.php                # Handles user signup and sends email
├── .gitignore
└── README.md
````

---

## 🧑‍💻 Installation Guide

### 1. Clone the Repo

```bash
git clone https://github.com/siddhesh-wagh/online-learning-platform.git
cd online-learning-platform
```

### 2. Setup the Database

* Create a MySQL database named `online_learning`
* Import your schema and tables (you can export/import using phpMyAdmin or MySQL CLI)

```sql
CREATE DATABASE online_learning;
-- then import your SQL schema and seed data
```

### 3. Configure Database Connection

Edit `db-config.php`:

```php
$host = 'localhost';
$user = 'your_mysql_username';
$pass = 'your_mysql_password';
$dbname = 'online_learning';
```

### 4. Setup Mailer (PHPMailer)

Make sure PHPMailer is available via Composer or manual download.

You can install via Composer:

```bash
composer require phpmailer/phpmailer
```

Configure `includes/mailer.php` with your SMTP credentials.

---

## ✅ Sample Email Template

A modern HTML-based email with:

* Responsive design
* Call-to-action button
* Fallback URL
* Professional tone

![Email Screenshot](https://user-images.githubusercontent.com/your-placeholder/email-preview.png)

---

## 🤝 Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you'd like to change.

---

## 🛡️ License

[MIT License](LICENSE)

---

## 📬 Contact

Created by **[@siddhesh-wagh](https://github.com/siddhesh-wagh)**
For queries, email: `youremail@example.com`

```

---

### ✅ What You Should Do:
- Replace `youremail@example.com` with your real contact email.
- Replace the screenshot link in the email section if you want to show your email design visually.
- Ensure your `LICENSE` file exists (MIT recommended).

Let me know if you want a `SQL` schema file or auto email tests in the README too!
```
