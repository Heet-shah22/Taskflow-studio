# ⚡ TaskFlow Studio — Modern PHP & MySQL Project Management Platform

TaskFlow Studio is a high-performance, modern web application designed for engineering, design, and product teams to plan sprints, track deliverables, organize Kanban workflows, and collaborate in real-time.

Built using **Pure PHP (PHP 7.4 / 8.x+)**, **MySQL (InnoDB with Foreign Keys)**, and **Modern CSS/Vanilla JS**, it delivers an ultra-fast, zero-dependency experience with an aesthetic dark-mode design system.

---

## 🌟 Key Features

- **📊 Comprehensive Workspace Dashboard**:
  - Live metric cards (Active Projects, Completion Rate %, In-Progress tasks, Urgent flags).
  - Recent Projects with dynamic percentage progress calculation.
  - Priority Queue with deadline status badges (Overdue, Today, Upcoming).
  - Real-time activity timeline.

- **📋 Interactive Kanban Board & List Views**:
  - Drag-and-drop task movements across 4 lifecycle stages: `To Do`, `In Progress`, `In Review`, and `Completed`.
  - Instant status synchronization via AJAX with toast notifications.
  - Switch smoothly between Kanban Board view and Table List view.

- **📁 Project Directory & Hub**:
  - Filter projects by status (`Planning`, `In Progress`, `Completed`, `On Hold`).
  - Project detail pages with roadmap timelines, assigned members, budget tracking, and progress metrics.
  - Ability to add collaborators to specific projects.

- **📝 Rich Task Details & Comment Threads**:
  - Task modals showing priority, estimated hours, due date, description, and assignee.
  - Interactive discussion thread for each task where team members can post updates and notes.

- **🛡️ Role-Based Access Control (RBAC)**:
  - **Admin**: Full workspace access, user management, project creation & deletion.
  - **Project Manager**: Project planning, sprint organization, task assignment.
  - **Member**: Manage assigned tasks, update progress, post comments, update personal profile.

- **⚡ 1-Click Database Setup Wizard (`install.php`)**:
  - Initialize tables, constraints, and rich seed data with a single button.

---

## 🚀 How to Run in XAMPP / WampServer / Laragon

### Step 1: Place the Project in Your Web Root
Copy or move this project folder into your web server root directory:
- **XAMPP**: `C:\xampp\htdocs\taskflow\`
- **WampServer**: `C:\wamp64\www\taskflow\`
- **Laragon**: `C:\laragon\www\taskflow\`

*(Alternatively, you can keep the current folder name or configure an Apache VirtualHost)*.

### Step 2: Start Apache and MySQL
1. Open the **XAMPP Control Panel** (or Wamp/Laragon).
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 3: Run Database Initialization

Choose either **Method A** (easiest) or **Method B**:

#### Method A: 1-Click Web Installer (Recommended)
1. Open your browser and navigate to:
   ```
   http://localhost/taskflow/install.php
   ```
   *(Or `http://localhost/New%20folder%20(2)/install.php` if accessing from the current folder name)*
2. Click **Run 1-Click Database Setup**.
3. The wizard will automatically create the database `taskflow_db`, execute the schema, and seed demo accounts and projects.

#### Method B: Manual phpMyAdmin Import
1. Navigate to `http://localhost/phpmyadmin/`.
2. Click on the **Import** tab.
3. Choose the [`database.sql`](file:///database.sql) file included in this directory.
4. Click **Go** / **Import**.

---

## 🔑 Demo Login Credentials

All demo accounts are pre-configured with the default password: **`password123`**

| Role | Name | Email Address | Password |
| :--- | :--- | :--- | :--- |
| **Admin** | Sophia Vance | `admin@taskflow.com` | `password123` |
| **Manager** | Marcus Drake | `manager@taskflow.com` | `password123` |
| **Member** | Elena Rostova | `elena@taskflow.com` | `password123` |
| **Member** | David Chen | `david@taskflow.com` | `password123` |

> 💡 **Quick Login**: The sign-in page features 1-click credential buttons to automatically populate email and password for quick testing.

---

## 🛠️ Database Configuration

Database connection settings are located in [`config/db.php`](file:///config/db.php):

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Default for XAMPP is empty
define('DB_NAME', 'taskflow_db');
define('DB_CHARSET', 'utf8mb4');
```

If your MySQL password is not empty (e.g. MAMP or a custom root password), update the `DB_PASS` constant in this file.

---

## 📂 Project Architecture

```
taskflow/
├── config/
│   ├── db.php                 # PDO database connection & error handling
│   └── init.php               # Sessions, auth helpers, sanitization, flash messages
├── includes/
│   ├── header.php             # HTML head, auth verification, top navigation wrapper
│   ├── sidebar.php            # Left navigation sidebar with user profile link
│   ├── navbar.php             # Sticky topbar with quick search and modal triggers
│   ├── footer.php             # Modals (New Task, New Project, Task Details) and scripts
│   └── kanban_card.php        # Reusable draggable Kanban card component
├── actions/
│   ├── auth.php               # Secure login, registration, and logout processor
│   ├── task_actions.php       # Task CRUD, AJAX status updater, comments engine
│   ├── project_actions.php    # Project creation, updates, and member assignments
│   └── user_actions.php       # User creation and role management
├── assets/
│   ├── css/
│   │   └── style.css          # Design system, glassmorphic UI, responsive layouts
│   └── js/
│       └── app.js             # Drag & drop Kanban engine, modals, AJAX requests, toasts
├── database.sql               # Complete SQL schema with relational tables & seed dataset
├── install.php                # 1-Click web installer and database reset tool
├── index.php                  # Main dashboard with statistics, active projects & feed
├── projects.php               # Projects directory with status filters & progress bars
├── project-detail.php         # Dedicated project hub with Kanban & Table views
├── tasks.php                  # Global backlog & filtered Kanban board
├── activity.php               # Real-time activity timeline & audit log
├── users.php                  # Team directory & member permissions
├── profile.php                # User account settings & password changer
├── login.php                  # High-end login page with 1-click test credentials
├── register.php               # Account registration page
└── logout.php                 # Clean session logout handler
```

---

## 🔒 Security Best Practices Implemented

1. **Prepared Statements**: All database queries utilize PDO prepared statements (`execute([$params])`) to prevent SQL Injection.
2. **Password Hashing**: Uses PHP's `password_hash()` with `PASSWORD_BCRYPT` and `password_verify()`.
3. **Cross-Site Scripting (XSS) Prevention**: Data displayed to users is sanitized via `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
4. **CSRF Tokens**: Form submissions include session-based CSRF protection.
5. **Session Security**: Session protection and role checks on all administrative endpoints.
