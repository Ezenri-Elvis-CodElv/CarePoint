# CarePoint – Clinic Appointment Booking System

| | |
|---|---|
| **Student** | Tadas-Okonkwo Nnamdi |
| **Matric No.** | 23/SCI01/149 |
| **Project** | CarePoint – Clinic Appointment Booking System |

## Quick access for the supervisor

Both login pages have **one-click demo buttons**, so you can sign in without typing anything:

- **Patient** – <http://localhost/CarePoint/login.php> → click **Patient** (or open `login.php?quick=patient`)
- **Admin** – <http://localhost/CarePoint/admin/login.php> → click **Admin** (or open `admin/login.php?quick=admin`)

| Role | Login | Password |
|---|---|---|
| Administrator | `admin` (or `admin@carepointclinic.com`) | `Admin@123` |
| Patient | `patient@carepoint.com` | `Patient@123` |
| Patient | `maria.gonzalez@example.com` | `Patient@123` |
| Patient | `liam.oconnor@example.com` | `Patient@123` |

---

CarePoint is a full-stack web application where patients book appointments with doctors online. It works like an e-commerce site, except patients choose a department, a doctor and a time slot instead of products. Clinic staff manage appointments and doctors from a separate admin panel.

Built with **HTML5, CSS3, vanilla JavaScript, PHP 8 (PDO) and MySQL/MariaDB**. It has no frameworks and no build step, so it runs directly on XAMPP.

---

## Features

### Public website
| Page | What it does |
|---|---|
| **Home** (`index.php`) | Hero section ("Your Health, Our Priority"), quick department finder, services overview, the 3-step booking process, featured doctors, patient testimonials, call-to-action, contact details |
| **About Us** (`about.php`) | Clinic story, mission, vision and values, reasons to choose CarePoint, healthcare team, key statistics |
| **Medical Services** (`services.php`) | Seven service cards: General Consultation, Dental Care, Cardiology, Paediatrics, Dermatology, Eye Care, Laboratory Services. Each has a description, a features list and a **Book Appointment** button |
| **Our Doctors** (`doctors.php`) | Doctor profiles with photo, specialisation, qualifications, biography, consulting days and hours, and fee. Filter by department, and use **Book Appointment** to open the booking form with that doctor already selected |
| **Book Appointment** (`book.php`) | Booking form (details below) |
| **Track Booking** (`track.php`) | Look up a booking with its reference and email, follow its progress (Requested → Confirmed → Completed), read notes from the clinic, and cancel |
| **Contact** (`contact.php`) | Address, phone, email, opening hours, a contact form (saved to the database) and a map placeholder with an "Open in Google Maps" link |

### Appointment booking
- Choosing a **department** loads only the doctors in that department (via AJAX from `api/doctors.php`).
- Choosing a **doctor** shows their photo, consulting days, hours and fee.
- Choosing a **date** loads that doctor's real time slots from `api/slots.php`:
  - Booked slots are greyed out and cannot be selected.
  - Slots that have already passed today are disabled.
  - Days when the doctor doesn't consult are rejected with a clear message.
- **Double-booking is prevented at two levels:**
  1. The PHP code re-checks availability on submit.
  2. A database `UNIQUE (doctor_id, appointment_date, appointment_time, slot_lock)` key makes two simultaneous requests for the same slot impossible. When an appointment is cancelled or rejected, `slot_lock` becomes `NULL` and the slot can be booked again.
- A patient can't hold two appointments at the same date and time.
- Validation runs in the browser (JavaScript) and again on the server (PHP), with a clear message for each field.
- On success the patient sees a **confirmation page with a unique reference** (e.g. `CP-7K2M9Q`), which they can copy or print.
- Guests can book without an account. If they later register or log in with the same email, those bookings are **linked to their account automatically**.

### Patient accounts
- Register, log in and log out. Passwords are hashed with `password_hash()` and sessions are regenerated on login.
- The **patient dashboard** (`dashboard.php`) shows:
  - totals for all, pending, confirmed and cancelled appointments
  - upcoming and history tabs with each appointment's date, time, status and any clinic note
  - a **Cancel** button for future pending or confirmed appointments
  - the next appointment card
  - a **track by reference** box
  - the patient's profile details
- The **patient profile** (`profile.php`) has a header with the patient's avatar, patient ID (click to copy), verification and membership status, and tabs for Overview, Personal Information, Medical History, Appointments, Prescriptions, Medical Records and Account Settings. Patients can edit their name, phone, gender, date of birth, blood group and emergency contact. A patient counts as verified once they have a completed visit, because reception checks ID in person.
  - **Upgrading an existing database:** run `database/migrations/001_patient_profile_fields.sql` in phpMyAdmin to add the new profile columns. A fresh import of `carepoint_db.sql` already includes them.
- When a logged-in patient books, the form is pre-filled with their details.

### Admin panel (`/admin/`)
- A separate login page. Failed logins are throttled after 5 attempts.
- The **dashboard** shows:
  - total, pending, confirmed and cancelled appointments, and total doctors
  - today's count, completed, rejected and number of registered patients
  - pending requests with one-click **Approve / Reject**
  - upcoming confirmed appointments
  - bookings per department
  - quick actions
- **Appointments:** search by reference, name, email or phone; filter by status, department, doctor or date; change status from the list; paginated.
- **Appointment details:** all of the patient's booking information, their other bookings, Approve/Reject, any status change, and a note to the patient that they see when tracking the booking.
- **Doctors:**
  - add, edit or delete doctors, including photo upload (JPG, PNG or WEBP, max 2 MB, content checked to be a real image)
  - set consulting days, hours, slot length and fee
  - mark a doctor Active or Inactive, and choose whether they are featured on the home page
  - doctors with upcoming appointments can't be deleted (set them to Inactive instead); past records keep the doctor's name
- **Patients:** registered patients and guest bookers, with appointment counts and a link to their bookings.
- **Messages:** contact form submissions. Reply by email, mark as read or unread, or delete.

### Design
- White, light blue and dark navy colour scheme, using Plus Jakarta Sans and Inter fonts and Font Awesome icons.
- Fully responsive: tested at 360px, 390px, 768px and 1440px. Mobile has a slide-in menu, the admin sidebar becomes a drawer, and tables turn into cards.
- Hover effects, scroll-reveal animations and animated confirmation states. The animations are turned off when the user's system is set to "reduce motion".
- Every button and link does something real. No placeholder buttons.

### Security
- PDO prepared statements for every query (protects against SQL injection).
- All output is escaped with `htmlspecialchars` (protects against XSS).
- A CSRF token on every POST form.
- `password_hash` / `password_verify` for patient and admin passwords. The session ID is regenerated on login, and cookies are `HttpOnly` and `SameSite=Lax`.
- `.htaccess` blocks web access to `includes/` and `database/`, and stops PHP from running inside the photo upload folder.
- Admin redirects only go to admin pages, so they can't be used to send users to other sites.
- Patients can only view or cancel bookings they own, or ones they have verified with reference + email.

---

## Project structure

```
CarePoint/
├── index.php, about.php, services.php, doctors.php, contact.php
├── book.php                  # booking form + server-side validation
├── booking-success.php       # confirmation with booking reference
├── track.php                 # track by reference + email
├── cancel-appointment.php    # patient cancellation handler (POST)
├── login.php, register.php, logout.php, dashboard.php
├── api/
│   ├── doctors.php           # GET doctors for a department (JSON)
│   └── slots.php             # GET time slots + availability (JSON)
├── admin/
│   ├── login.php, logout.php, index.php (dashboard)
│   ├── appointments.php, appointment.php, appointment-action.php
│   ├── doctors.php, doctor-form.php, doctor-delete.php
│   ├── patients.php, messages.php
│   └── includes/ (admin layout + helpers)
├── includes/
│   ├── config.php            # DB credentials, clinic details, session
│   ├── db.php                # PDO connection
│   ├── functions.php         # helpers: auth, CSRF, slots, statuses…
│   ├── components.php        # reusable UI pieces
│   └── header.php, footer.php
├── assets/
│   ├── css/style.css, css/admin.css, css/auth.css (login/sign-up screens)
│   ├── js/main.js, js/booking.js, js/admin.js
│   └── images/ (hero, clinic, doctors/, placeholder & favicon SVGs)
└── database/
    └── carepoint_db.sql      # creates + populates carepoint_db
```

## Database (`carepoint_db`)

| Table | Purpose |
|---|---|
| `admin` | Administrator accounts (hashed passwords) |
| `users` | Registered patients (hashed passwords) |
| `departments` | Medical services / departments |
| `doctors` | Doctor profiles and schedules → `departments.id` |
| `appointments` | Bookings → `users.id` (nullable for guests), `doctors.id`, `departments.id` |
| `contact_messages` | Contact form submissions |

Key relationships:
- `appointments.user_id → users.id` (`ON DELETE SET NULL`)
- `appointments.doctor_id → doctors.id` (`ON DELETE SET NULL`; `doctor_name` keeps a copy of the name for history)
- `appointments.department_id → departments.id`
- `doctors.department_id → departments.id`

The SQL file adds demo data: 7 departments, 9 doctors, 3 patients, 11 appointments in every status, and 2 contact messages. Appointment dates are calculated from the day you import (`CURDATE() ± n`), so the demo always has upcoming and past bookings.

---

## Running with XAMPP

### Requirements
- XAMPP with **PHP 8.0 or newer** (any current XAMPP release) and MySQL/MariaDB.

### Steps
1. **Copy the project** into XAMPP's web folder, keeping the folder name `CarePoint`:
   - Windows: `C:\xampp\htdocs\CarePoint`
   - macOS: `/Applications/XAMPP/xamppfiles/htdocs/CarePoint`
   - Linux: `/opt/lampp/htdocs/CarePoint`
2. **Start Apache and MySQL** from the XAMPP Control Panel.
3. **Create the database.** Use either option:
   - **phpMyAdmin:** open <http://localhost/phpmyadmin>, click **Import**, choose `database/carepoint_db.sql`, then click **Import** (or **Go**). You don't need to create the database first; the script creates `carepoint_db` itself.
   - **Command line:** `mysql -u root -p < database/carepoint_db.sql` (press Enter when asked for a password on a default XAMPP install).
4. **Check the credentials** in `includes/config.php`. The defaults match XAMPP: host `localhost`, user `root`, empty password. Change them if your MySQL setup is different.
5. **Open the site:** <http://localhost/CarePoint/>
   - Admin panel: <http://localhost/CarePoint/admin/login.php> (the "Staff / Admin Login" link in the footer also goes there).

> **macOS / Linux only:** to let admins upload doctor photos, make the photo folder writable by Apache:
> `chmod 777 /Applications/XAMPP/xamppfiles/htdocs/CarePoint/assets/images/doctors`

### Demo accounts

| Role | Login | Password |
|---|---|---|
| Administrator | `admin` (or `admin@carepointclinic.com`) | `Admin@123` |
| Patient | `patient@carepoint.com` | `Patient@123` |

The other demo patients (`maria.gonzalez@example.com` and `liam.oconnor@example.com`) also use `Patient@123`.

You can also try these sample references on the **Track Booking** page:

| Reference | Email |
|---|---|
| `CP-4H8TXR` | `patient@carepoint.com` |
| `CP-5YC7HU` | `aisha.bello@example.com` (a guest booking) |

### A quick tour
1. On the home page, click **Book an Appointment**. Choose *Cardiology*, then *Dr. Arjun Mehta*, then a Monday, Wednesday or Friday. One slot may already show as booked. Pick a time and submit to get your reference.
2. Try to book the same doctor, date and time again. The slot is greyed out, and a forced submit is rejected.
3. Go to **Track Booking** and enter the reference and email to see the status, then cancel. The slot becomes available again.
4. Log in as the demo patient to see the dashboard.
5. Log in to the admin panel. Approve or reject pending requests, add a note, then add, edit or delete a doctor.

---

## Configuration notes
- **Timezone:** appointment times use the timezone in `includes/config.php` (`date_default_timezone_set('UTC')`). Change it to your clinic's timezone, e.g. `'Africa/Lagos'` or `'Europe/London'`.
- **Booking window:** patients can book up to 60 days ahead. Change `BOOKING_WINDOW_DAYS` in `config.php`.
- **Clinic details** (name, address, phone, email) are constants in `config.php` and are used across the whole site.
- **Base URL** is detected automatically, so the project also works from a different folder name.
- **Internet connection:** Google Fonts and Font Awesome icons load from a CDN. Without internet the site still works, but uses system fonts and shows no icons.
- **Tests used to verify this build:** PHP 8.2 + MariaDB 10.4 (the same database engine XAMPP ships).

## Troubleshooting
| Problem | Fix |
|---|---|
| "Unable to connect to the database" | Start MySQL in XAMPP, import `database/carepoint_db.sql`, and check the credentials in `includes/config.php` |
| Pages load without styles | Make sure the folder is directly inside `htdocs` and that you're opening `http://localhost/...`, not the file directly in the browser |
| "Could not save the photo" | Make `assets/images/doctors/` writable (see the macOS/Linux note above) |
| You want to reset all the demo data | Re-import `database/carepoint_db.sql`. It drops and recreates the database |

## Credits
The doctor and clinic photographs are from [Unsplash](https://unsplash.com) and are free to use under the Unsplash License. All names, addresses, phone numbers and testimonials are fictional demo data.
