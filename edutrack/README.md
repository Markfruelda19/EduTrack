# ⬡ EduTrack — Student Record System

A clean, modern **PHP + MySQL** student records system built as a beginner portfolio project.
Demonstrates CRUD operations, relational databases, AJAX search, and basic business logic.

---

## 🚀 Features

| Feature | Details |
|---|---|
| Student CRUD | Add, edit, delete, view all students |
| Grade Management | Add / edit / remove grades per subject |
| Auto Average | Computed live from the `grades` table |
| AJAX Live Search | Search by name, ID, or course — no page reload |
| Status Badges | Excellent / Passing / Failing based on average |
| Dashboard | Total students, passing/failing counts, top student |
| Prepared Statements | All queries use `$conn->prepare()` — SQL injection safe |

---

## 🗂️ Folder Structure

```
edutrack/
├── config/
│   └── database.php        # DB credentials & connection helper
├── assets/
│   ├── css/style.css       # Full stylesheet (dark academic theme)
│   └── js/main.js          # AJAX search + UI helpers
├── students/
│   ├── view.php            # Student list with live search
│   ├── profile.php         # Individual student + grades
│   ├── add.php             # Add student form
│   ├── edit.php            # Edit student form
│   ├── delete.php          # Delete handler
│   └── search.php          # JSON endpoint for AJAX
├── grades/
│   ├── add_grade.php       # Add grade (POST handler)
│   ├── edit_grade.php      # Edit grade form
│   └── delete_grade.php    # Delete handler
├── includes/
│   ├── header.php          # Sidebar + topbar HTML
│   └── footer.php          # Closing tags + script
├── index.php               # Dashboard
├── database.sql            # Full schema + seed data
└── README.md
```

---

## ⚙️ Setup (XAMPP / WAMP)

1. **Copy project** into your `htdocs` (XAMPP) or `www` (WAMP) folder as `edutrack/`.

2. **Import the database:**
   - Open phpMyAdmin → New → Create database `edutrack_db`
   - Import `database.sql`

3. **Configure credentials** in `config/database.php`:
   ```php
   define('DB_USER', 'root');
   define('DB_PASS', '');       // your MySQL password
   ```

4. **Open in browser:**  `http://localhost/edutrack/`

---

## 🗃️ Database Schema

```
students    ←──< grades >──→  subjects
id (PK)           id (PK)       id (PK)
student_id        student_id    subject_code
firstname         subject_id    subject_name
lastname          grade
course
year_level
email
created_at
```

Grades cascade-delete when a student is removed.

---

## 🔒 Security Notes

- All DB queries use **prepared statements** with bound parameters
- Output is escaped with `htmlspecialchars()` throughout
- No raw `$_GET`/`$_POST` is ever passed directly to SQL

---

## 🎨 Tech Stack

- **PHP 8+** — backend logic
- **MySQL** — relational data
- **Vanilla JS** — AJAX fetch, DOM manipulation
- **CSS custom properties** — theming without a framework
- **Google Fonts** — Syne + DM Sans

---

## 📈 Possible Upgrades

- [ ] Login system (PHP sessions)
- [ ] Role-based access (Admin / Teacher / Student)
- [ ] Export grades to PDF (use FPDF or DomPDF)
- [ ] Pagination on student list
- [ ] Chart.js analytics on dashboard
- [ ] Student profile photo upload

---

*Built with PHP, MySQL, and vanilla JS. No frameworks required.*
