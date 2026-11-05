# Wiki CMS – Collaborative Encyclopedia

A modern, Wikipedia-style Content Management System (CMS) built using PHP and MySQL. The platform allows users to explore articles, share knowledge, publish content and manage their contributions through a user-friendly interface.

The system also includes a dedicated Admin Dashboard for managing users, articles, categories, comments and website settings.

## Project Overview

Wiki CMS is designed as a collaborative knowledge-sharing platform where registered users can create, edit and publish articles on different topics.

Inspired by Wikipedia, the platform provides an organized article browsing experience with categories, search functionality, author profiles and article revision history.

## Features

### 1. User Features
- User registration and login.
- Secure password-based authentication.
- User dashboard.
- Create and publish articles.
- Save articles as drafts.
- Edit and delete articles.
- Browse published articles.
- Search articles.
- Explore articles by category.
- View author profiles.
- Update user profiles.
- Upload profile pictures.
- Add comments to articles.
- View article revision history.
- Password recovery and reset functionality.

### 2. Admin Dashboard
- Dedicated Admin Dashboard.
- Dashboard statistics and overview.
- Manage registered users.
- View individual user details.
- Manage articles.
- Manage categories.
- Manage comments.
- Manage website pages.
- Website settings management.
- Admin authentication and access control.

### 3. Article Management
- Create and publish articles.
- Rich-text article editor using Quill.
- Organize articles using categories.
- Add tags to articles.
- Upload featured images.
- Support draft and published article statuses.
- View article details.
- Maintain article revision history.
- Wikipedia-style internal article linking.

### 4. Frontend Features
- Modern and responsive user interface.
- Homepage with latest articles.
- Article search functionality.
- Category-based browsing.
- Author information and profiles.
- Bootstrap-based layout.
- Custom CSS styling.
- JavaScript-based interactions.

## Technologies Used

| Technology | Purpose |
|---|---|
| HTML5 | Website structure |
| CSS3 | Styling and layout |
| JavaScript | Interactive functionality |
| Bootstrap | Responsive UI framework |
| PHP | Backend development |
| MySQL | Database management |
| MySQLi | PHP database connectivity |
| Apache | Local web server |
| XAMPP | Local development environment |
| Quill.js | Rich-text article editor |
| Git & GitHub | Version control |

## Project Structure

```text
Content_Management_system-/
│
├── admin/
│   ├── includes/
│   │   ├── admin_auth.php
│   │   ├── admin_header.php
│   │   └── admin_footer.php
│   ├── index.php
│   ├── articles.php
│   ├── categories.php
│   ├── comments.php
│   ├── pages.php
│   ├── revisions.php
│   ├── settings.php
│   ├── users.php
│   └── user_detail.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
│
├── config/
│   └── db.php
│
├── database/
│   └── migrations/
│       └── 001_admin_pages_and_settings.sql
│
├── includes/
│   ├── auth_check.php
│   ├── header.php
│   ├── navbar.php
│   └── footer.php
│
├── uploads/
│   ├── images/
│   └── profiles/
│
├── index.php
├── login.php
├── register.php
├── logout.php
├── dashboard.php
├── create_article.php
├── edit_article.php
├── delete_article.php
├── article.php
├── category.php
├── search.php
├── author.php
├── profile.php
├── history.php
├── revision.php
├── forgot_password.php
├── reset_password.php
├── page.php
└── test_db.php
```

## Installation and Setup

Follow these steps to run the project locally.

### Prerequisites

Install the following:

- XAMPP
- Visual Studio Code
- Web browser

### Step 1: Clone the Repository

Open your terminal and execute:

```bash
git clone https://github.com/izsnippet/Content_Management_system-.git
```

### Step 2: Move the Project

Move the downloaded project folder into:

```text
C:\xampp\htdocs\
```

### Step 3: Start XAMPP

Open XAMPP Control Panel and start:

- Apache
- MySQL

### Step 4: Create the Database

1. Open `http://localhost/phpmyadmin`.
2. Create a MySQL database named `wiki_cms_v2`.
3. Import your project's database SQL backup if available.
4. Apply the SQL migration files in `database/migrations/` where required.

**Note:** The repository contains an admin-related migration, but a complete database backup is not included. You need the original database export or the complete table creation SQL to initialize all project tables.

### Step 5: Configure Database Connection

Open:

```text
config/db.php
```

Configure the local database connection:

```php
$host = "localhost";
$username = "root";
$password = "";
$database = "wiki_cms_v2";
```

Use the credentials configured in your XAMPP MySQL installation.

### Step 6: Run the Project

Open your browser and visit:

```text
http://localhost/Content_Management_system-/
```

The CMS homepage should now load.

## Screens and Modules

The project includes the following major sections:

- Public Homepage
- Article Details Page
- Article Search
- Category Browsing
- Author Profiles
- User Registration and Login
- User Dashboard
- Article Editor
- Article History and Revisions
- Admin Dashboard
- Admin User Management
- Admin Article Management
- Admin Category Management
- Admin Comment Management
- Admin Website Settings

## Future Improvements

Possible future enhancements include:

- Cloud-based image storage.
- Email verification.
- Advanced article moderation.
- Improved security and activity logging.
- Article recommendation system.
- Advanced search and filtering.
- SEO improvements.
- Cloud deployment.
- Better revision comparison tools.

## Project Team

**Team Name:** Code 404

**Project:** Content Management System (CMS)

**Team Members:**
- Ishake Mondal
- Shivam Mishra

**Course:** Bachelor of Computer Applications (BCA)

**Semester:** 5th Semester

## Repository

GitHub: https://github.com/izsnippet/Content_Management_system-

---

**Wiki CMS – Explore, Create and Share Knowledge.**
