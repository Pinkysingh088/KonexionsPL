
**Project Title: Admin Dashboard for Konexions - Profit & Loss (P\&L) Module**

**Description:**
This project is a web-based admin dashboard developed using **PHP**, **MySQL**, **HTML**, **CSS**, and **JavaScript**. It is designed to manage and visualize the financial performance of different business processes under Konexions Back Office Services.

The Profit and Loss (P\&L) module enables the admin to calculate and track:
**Process Management**: Each process represents a client engagement (e.g., banks or financial institutions). Agents work under these processes for services like EMI collections, recoveries, and related financial operations.

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
**How to Run Locally:**

1. Install XAMPP or WAMP.
2. Clone/download this project and place it in the `htdocs` directory.
3. Import `konexions_expenses` into phpMyAdmin.
4. Update your DB credentials in `db_connections.php`.
5. Run via `localhost/konexionsPL/index.php\`.

**Screenshot Preview:**
**Admin Dashboard:**

![image](https://github.com/user-attachments/assets/a774e7a6-c2cd-4df8-8fd5-0cc0f3cfd7b8)
![image](https://github.com/user-attachments/assets/8a9d9c3b-c313-430c-9bae-25caf1ebf0ce)


**Displays Total of every expenses:**

![image](https://github.com/user-attachments/assets/a6c96ae4-2843-4349-9dc7-7177d75d5f8b)

**Displays Each Every Process Expenses:**

![image](https://github.com/user-attachments/assets/e9d3523a-4377-4689-9495-98af7100847e)
![image](https://github.com/user-attachments/assets/d14b07bb-8a59-4bdc-8105-7b14ae70ee9c)
![image](https://github.com/user-attachments/assets/733f9bc8-1505-4eab-8038-2fa2da326723)
![image](https://github.com/user-attachments/assets/6312a319-0eff-423f-a7cf-b2ef6bbce42f)
![image](https://github.com/user-attachments/assets/a3b0fc15-e484-421e-8138-92f49731ca01)
![image](https://github.com/user-attachments/assets/2349de2d-1276-4acd-8a75-b92f84d19c6a)
![image](https://github.com/user-attachments/assets/c84c83ba-de95-404e-8817-bc3e8a1e1d65)
![image](https://github.com/user-attachments/assets/662df488-f3f5-416f-b5e4-1c54e3d7ccd5)
![image](https://github.com/user-attachments/assets/caf508df-fe39-4df7-b09c-2516e6a6ecfb)


**Total Expenses:**

![image](https://github.com/user-attachments/assets/8abaa59c-d13b-4790-b829-5c319d90cc22)
![image](https://github.com/user-attachments/assets/a3678541-ab84-4d6e-bc16-0026abe95fe0)
![image](https://github.com/user-attachments/assets/63fab8a0-cbbc-44b0-8fc6-91afdd1e620a)
![image](https://github.com/user-attachments/assets/3028a726-8892-4af2-97a4-ffb101f9ecfc)



**Accountant Dashboard:**

![image](https://github.com/user-attachments/assets/306322f0-b679-4265-891b-215035c1be86)
![image](https://github.com/user-attachments/assets/48caa06a-6563-44fa-b798-b8271f6b6b9b)
![image](https://github.com/user-attachments/assets/375a033f-6ebe-4087-9f5c-7670e736adf0)

**Author:** Pinky
**GitHub Repo:** [https://pinkysingh088.github.io/KonexionsPL/)
**GitHub Repo:** (https://github.com/Pinkysingh088/KonexionsPL)




