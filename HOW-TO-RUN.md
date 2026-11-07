# How to Run — Online Examination System (Auto-Grading)

Assumes prerequisites in `PREREQUISITES.md` are already done. No Composer step — this project has no dependencies.

## One-time setup

```
mysql -u root -h 127.0.0.1 -P 3306 < database/schema.sql
```
(or import `database/schema.sql` via phpMyAdmin)

## Start MySQL

Open **XAMPP Control Panel** and make sure **MySQL** is running.

## Run the app

From this project's folder:
```
php -S localhost:8000 -t public public/index.php
```
(`-t public` is required — without it, CSS/JS assets 404 while the HTML still renders.)

## Open it

Visit http://localhost:8000/login and sign in with any of the demo credentials in `README.md` under "Default Login Credentials".

## Stop the app

`Ctrl+C` in the terminal running the `php -S` command.
