# Coffee Blends

A small self-hosted app for recording your home-roasted coffee blends: bean
varieties, weights, roast temperatures/times, roaster and ambient
conditions, sky conditions, roast level, and tasting notes for espresso and
milk-based drinks.

Plain PHP (PDO, no framework) + MySQL. No Composer dependencies required —
just upload the files.

## Features

- Login with email + password (`password_hash`/`password_verify`)
- "Forgot password" flow: emails a time-limited reset link
- Home page lists all your blends, with an "Add blend" button
- A blend has a name, roast date, and espresso/milky tasting notes
- Each blend can have any number of bean varieties, each with:
  weight, roast temperature, roast time, roaster temperature at start,
  ambient temperature, sky conditions (sunny/partial/cloudy), and roast
  level (light/medium/dark/very dark/oily/burnt)
- Each user only sees their own blends

There's no public self-registration page (this is meant for personal/family
use, not an open sign-up site). Create accounts with the `bin/create_user.php`
CLI script — see below.

## Project layout

```
config/   config.php (gitignored, your real settings) + example
src/      PHP application code, not web-accessible
public/   the web root — point your server's DocumentRoot here
sql/      schema.sql to set up the database
bin/      create_user.php CLI helper
```

## 1. Database setup

Create a database and user, then import the schema:

```bash
mysql -u root -p -e "CREATE DATABASE coffee_blends CHARACTER SET utf8mb4;"
mysql -u root -p -e "CREATE USER 'coffee_user'@'localhost' IDENTIFIED BY 'change-this-password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON coffee_blends.* TO 'coffee_user'@'localhost';"
mysql -u coffee_user -p coffee_blends < sql/schema.sql
```

## 2. App configuration

```bash
cp config/config.example.php config/config.php
```

Edit `config/config.php` with your database credentials and mail "from"
address. `app.base_url` can usually be left blank — it's auto-detected from
the request — but set it explicitly if you're behind a proxy/load balancer
that changes the host/scheme.

## 3. Create your login

```bash
php bin/create_user.php "Your Name" "you@example.com" "a-strong-password"
```

Run this again (with a different email) for anyone else who should have
their own account.

## 4. Deploy to your Linux server

**Recommended: point your web server's document root at `public/`.**

Example Apache vhost:

```apache
<VirtualHost *:443>
    ServerName coffee.example.com
    DocumentRoot /var/www/coffee_blends/public

    <Directory /var/www/coffee_blends/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

With `DocumentRoot` set this way, `config/`, `src/`, `sql/`, and `bin/`
are never reachable over the web at all — the safest setup.

**If your host only lets you serve from `public_html`** (common on cheap
shared hosting, where you can't change the document root): upload the
whole project somewhere *outside* `public_html`, then either symlink
`public_html` to the `public/` folder, or ask your host to point the
domain at a subfolder. As a fallback, `.htaccess` files with `Require all
denied` are included in `config/`, `src/`, `sql/`, and `bin/` so that even
if those folders end up under the web root, Apache refuses to serve their
contents directly. This only works on Apache with `AllowOverride` enabled;
don't rely on it as your only protection — the document-root approach above
is the real fix.

Make sure PHP has the `pdo_mysql` extension enabled (standard on nearly
all LAMP hosts).

### Local testing without deploying

```bash
php -S localhost:8000 -t public
```

Then visit http://localhost:8000.

## Password reset email

By default, password resets are sent with PHP's built-in `mail()`. This
works on many shared hosts out of the box, but some hosts block it or mark
it as spam. If your reset emails aren't arriving, swap `send_password_reset_email()`
in `src/mailer.php` for a proper SMTP library (e.g. install PHPMailer with
`composer require phpmailer/phpmailer` and send via your mail provider's
SMTP credentials).

## Security notes

- All database queries use prepared statements (PDO)
- Passwords are hashed with PHP's `password_hash` (bcrypt)
- Reset tokens are random 32-byte values; only their SHA-256 hash is stored,
  and they expire after 1 hour
- All forms are protected with CSRF tokens
- Every blend query is scoped to the logged-in user's `user_id`
