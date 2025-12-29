# Central auth for tools.veerl.es/apps

Setup quickstart (local XAMPP)

1. Run `composer install` inside `apps/auth` to install PHPMailer.
2. Copy `config.sample.php` to `config.php` and set DB credentials and `base_url`.
3. Import `sql/migration.sql` into your database (phpMyAdmin).
4. Import `users.csv` into the `users` table using phpMyAdmin: map `firstname,lastname,email,role` to `first_name,last_name,email,role` and leave `password_hash` empty.
5. Visit `password_reset_request.php` and send reset links to users (or run script to create reset tokens). Users will set their passwords via the emailed links.
6. Test login at `login.php` and apps listing at `index.php`.

Notes:
- SMTP now uses PHPMailer via Composer. Set SMTP values in `config.php`; if you leave SMTP blank it will fall back to `mail()`.
- On production, set `cookie_secure` to `true` and ensure HTTPS is enabled.
