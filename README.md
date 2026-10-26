## How to Run the System

1. Start Apache and MySQL in XAMPP. PHP 8.1+ with PDO MySQL is required. The previous revision ran on PHP 8.3 / MariaDB 10.11; this UI revision needs local XAMPP verification. MySQL 8.0.16+ is also targeted.
2. Import `database.sql` using phpMyAdmin. It creates the complete `cuyotech_ssis_db` schema and related sample records. The separately delivered SQL is identical to the copy inside the ZIP.
3. Check `config/database.php`: defaults are `127.0.0.1`, port `3306`, user `root`, empty password, and base URL `/CuyoTech-SSIS`. Change these only to match your local setup.
4. Open `http://localhost/CuyoTech-SSIS/`. If forms repeatedly expire, ensure PHP's `session.save_path` exists and is writable.

## Accounts

| Portal | Email | Initial password |
|---|---|---|
| Administrator | admin@cuyotech.edu | Admin123! |
| Student — Andrea Villanueva | student@cuyotech.edu | Student123! |
| Registrar | registrar@cuyotech.edu | Registrar123! |
| Cashier | cashier@cuyotech.edu | Cashier123! |
| Department | department@cuyotech.edu | Department123! |
| Student — Miguel Navarro | student2@cuyotech.edu | Student123! |
