
**Project Title: Admin Dashboard for Konexions - Profit & Loss (P\&L) Module**

**Description:**
This project is a web-based admin dashboard developed using **PHP**, **MySQL**, **HTML**, **CSS**, and **JavaScript**. It is designed to manage and visualize the financial performance of different business processes under Konexions Back Office Services.

The Profit and Loss (P\&L) module enables the admin to calculate and track:

* **Billing Revenue**
* **Infra Expenses** (e.g., office rent, maintenance)
* **IT Asset & Vendor Expenses** (hardware/software purchases, service vendor costs)
* **Telecom Expenses** (e.g., SIM cards, internet, calling charges)
* **Net Profit & Profit %** (Calculated automatically based on the total revenue and total expense per vertical/process)

**Key Features:**

* Admin Login/Logout system.
* Selection filters by Vertical and Date range.
* Dynamic table for displaying per-process expenses and calculated profit.
* Option to download the report in Excel format.
* Responsive UI with a clean layout.

**Technologies Used:**

* PHP for backend logic and data handling.
* MySQL for storing user, process, and expense data.
* HTML/CSS/JavaScript for frontend display.
* Chart.js (or similar) for future enhancements to display graphs.

**Folder Structure:**

```
konexionsPL/
├── index.php
├── db_connection.php
├── admin_dashboard.php/(after admin login pages when admin will login can see all expenses)
│   ├── admin_view.php
│   └── admin_total_view.php
├── dashboard.php/((after accountant login pages, when acountant will login and do activities, tasks or calculation))
│   ├── billing.php
│   └── infra_expenses.php
│   └── it_asset_vendor.php
│   └── it_asset_vendor.php
│   └──telecom_expense.php
├── Manage Task/
│   ├── add_process.php
│   ├── manage_it_vendors.php
│   ├── manage_telecom_vendors.php
│   ├── view_expenses.php
└── Database/
     └── konexions_expenses
├── logout.php/
```
![Uploading image.png…]()

**How to Run Locally:**

1. Install XAMPP or WAMP.
2. Clone/download this project and place it in the `htdocs` directory.
3. Import `konexions_expenses` into phpMyAdmin.
4. Update your DB credentials in `db_connections.php`.
5. Run via `localhost/konexionsPL/index.php\`.

**Screenshot Preview:**
![Dashboard Interface](screenshot.png) *(Replace with actual screenshot image in repo)*

**Author:** Pinky Singh
**GitHub Repo:** [github.com/pinkysingh088/admin-dashboard-konexions](https://github.com/pinkysingh088/admin-dashboard-konexions)




