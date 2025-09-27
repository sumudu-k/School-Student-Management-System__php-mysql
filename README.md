# 🎓 Fully Responsive Student Management System For School

A web-based application for managing student, and teachers activities in a school environment. This system provides dashboards for both students and teachers, allowing for efficient management of academic and administrative tasks.

---

## <span style="color: #007ACC; font-weight: bold;">🌐 Live Website</span>

<a href="https://sms.sumudu.site">
  <img src="https://img.shields.io/badge/Visit-Live%20Website-%23ee0000?style=for-the-badge&logo=globe&logoColor=white" style="height:40px; background-color:blue;" />
</a>
<br>
🔗 Website: https://sms.sumudu.site

---

## 🚀 Features

### 👨‍🎓 Student Dashboard

- **Upcoming Exams**
  - View a list of upcoming exams with dates.
- **Assignments**
  - View upcoming assignments with due dates.
  - View past assignments with marks and teacher comments.
- **Exam Results**
  - Access past exam results, including term, year, subject, marks, grade, and comments.
- **Announcements**
  - See latest school announcements and updates.
- **Profile Management**
  - Edit personal and family details.
- **Subject Selection**
  - Choose subjects during registration.
- **Positions**
  - View assigned leadership positions (Class Monitor, Prefect, Head Prefect).

### 👨‍🏫 Admin Panel (For teachers)

- **Admin Authentication** Secure login for admins and super admins.
- **Assignment Management**
  - Add new assignments (title, description, due date).
  - Edit or delete existing assignments.
  - Add,edit or remove marks for students
- **Announcement Management**
  - Add new announcements (title, description).
  - Edit or delete announcements.
- **Assign marks for term exams**
  - Add, edit, delete marks for term exams with comments

### 👑 Super Admin Panel (For classroom teachers)

- **Manage Subjects**
  - Add, delete or edit a subject.
- **Exam Timetable Management**
  - Add, edit or delete exam schedules (subject, term, exam date, start time, end time).
- **Assign Student Positions**
  - Add or remove leadership positions for students (Class Monitor, Prefect, Head Prefect)
- **Review Admin(Teacher) Registration**:
  - Super admin can approve or reject admin(teacher) registration requests.
- **Review Student Registration**:
  - Super admin can approve or reject student registration requests.
- **Manage Students**
  - Super admin can manage students
- **Manage Admins/Teachers**
  - Super admin can manage teachers
- **Rreview Student Profile Updates**
  - Super admin can review student personal data updates(Approve or reject)
- **And All Features available in Admins**

### 🔒 Security & User Management

- **Session Management**: Ensures only authenticated users can access dashboards.
- **Role-Based Access**: Separate dashboards and permissions for students, admins, and super admins.
- **Password Hashing**: Secure password storage using bcrypt.

---

## 🏁 Getting Started

1. **Clone or Download** the repository to your local server (e.g., XAMPP `htdocs`).
2. **Import the Database**: Use the provided SQL file to set up the `sms` database.
3. **Configure Database Connection**: Update database credentials in PHP files if needed.
4. **Access the Application**:
   - Students: Register and log in via `register.php` and `login.php`.
   - Admins: Register via `admin_register.php` and log in via `admin_login.php`.

---

## 💻 Tech Stack

- **Frontend**

  - HTML5
  - CSS3
  - Bootstrap 5 (Responsive design and components)
  - JavaScript (Client-side validation and dynamic content)

- **Backend**

  - PHP (Server-side logic and processing)
  - MySQL (Database management)

- **Development Environment**
  - XAMPP (Apache server, MySQL, PHP)
 
---
## 🖥️ Deployed Server Specifications

The Student Management System is deployed on the following server configuration:

- ✅ **Server Provider**: DigitalOcean
- 🌐 **Data Center Location**: BLR1 (Bangalore, India)
- 🧠 **Memory (RAM)**: 2 GB
- 💾 **Disk Space**: 50 GB SSD
- 🖥️ **Operating System**: Ubuntu 24.04 LTS (x64)

---
 ## 🤝 <span style="color: #007ACC; font-weight: bold;">Contributing</span>

We welcome contributions to improve BlogMe! To contribute:

1. Fork the repository.
2. Create a new branch for your feature or bug fix.
3. Submit a pull request with a detailed description of your changes.

## 📄 <span style="color: #007ACC; font-weight: bold;">License</span>

 This project is licensed under the MIT License.

## 🟡<span style="color: #007ACC; font-weight: bold;">Screenshots</span>

<p float="left">
  <img src="https://raw.githubusercontent.com/sumudu-k/School-Student-Management-System__php-mysql/refs/heads/main/screenshots/sms-cover1.png" width="48%" />
  <img src="https://raw.githubusercontent.com/sumudu-k/School-Student-Management-System__php-mysql/refs/heads/main/screenshots/sms-cover2.png" width="48%" />
</p>
<p float="left">
  <img src="https://raw.githubusercontent.com/sumudu-k/School-Student-Management-System__php-mysql/refs/heads/main/screenshots/sms-cover3.png" width="48%" />
  <img src="https://raw.githubusercontent.com/sumudu-k/School-Student-Management-System__php-mysql/refs/heads/main/screenshots/sms-cover4.png" width="48%" />
</p>

