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
config/   config.example.php — copy to config.php (gitignored) for local, two-tier setups
public/   the web root — point your server's DocumentRoot here
  includes/   PHP application code (bootstrap.php, db.php, auth.php, Blend.php, views/)
sql/      schema.sql to set up the database
bin/      create_user.php CLI helper
```

`config/config.php` and `sql/` sit **one level above** `public/`. Only
`public/` should ever be reachable over HTTP — even if the webserver were
ever misconfigured to serve `.php` files as plain text instead of executing
them, your database password still couldn't be fetched over the network,
because it simply isn't inside the folder the web is pointed at.

**If your host won't let you set the document root above `public_html`**
(common on basic shared hosting — some subdomain tools only accept a single
folder directly inside `public_html`, no nested custom path at all): deploy
just `public/`'s *contents* as that folder, and create `config.php` directly
alongside them there instead of one level up. Nothing in the app needs
changing for this — `public/includes/bootstrap.php` already looks for
`config.php` in both the two-tier spot and this flat one, trying the
two-tier path first, and `public/.htaccess` already denies direct requests
for `config.php` and any stray `.sql`/`.bak` file, which is what actually
keeps `config.php` safe once it's sitting inside the web-exposed folder.
`sql/` and `bin/` don't need to go anywhere near the server in this layout —
see "Deploying to thebriffas.co.uk" below for a worked example of exactly
this setup.

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

**Recommended: point your web server's document root at `public/`** (the
two-tier layout — see "Project layout" above). Example Apache vhost:

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

With `DocumentRoot` set this way, `config/`, `sql/`, and `bin/` are never
reachable over the web at all — the safest setup. `config.php` still needs
creating manually on the server (it's gitignored, so it never ships in a
deploy) — copy `config.example.php`, fill in real values.

**If your host only offers a single folder inside `public_html`**, see the
flat layout described under "Project layout" above, and "Deploying to
thebriffas.co.uk" below for a full worked example including automated
deploys over FTP.

Either way, make sure PHP has the `pdo_mysql` extension enabled (standard
on nearly all LAMP hosts).

### Local testing without deploying

```bash
php -S localhost:8000 -t public
```

Then visit http://localhost:8000.

## Password reset email

By default, password resets are sent with PHP's built-in `mail()`. This
works on many shared hosts out of the box, but some hosts block it or mark
it as spam. If your reset emails aren't arriving, swap `send_password_reset_email()`
in `public/includes/mailer.php` for a proper SMTP library (e.g. install
PHPMailer with `composer require phpmailer/phpmailer` and send via your
mail provider's SMTP credentials).

## Deploying to thebriffas.co.uk (this install)

Same cPanel host as the other thebriffas.co.uk sites (this host doesn't
support FTPS — "500 AUTH not understood" — so plain FTP it is). Deploys are
**manual**: `.github/workflows/deploy.yml`, triggered from this repo's
**Actions** tab → **Run workflow** → uploads `public/`'s contents over FTP.

This host's subdomain tool only accepts a single folder directly inside
`public_html` — no nested custom path — so this uses the "flat" layout
described under "Project layout" above: only `public/`'s contents get
deployed (not `sql/` or `bin/` — neither is needed at runtime), and
`config.php` is created directly inside that same folder on the server.

### One-time server setup, in cPanel

1. **Subdomain** — *Domains* → create `coffee.thebriffas.co.uk`, document
   root the single folder cPanel offers inside `public_html` (e.g.
   `public_html/coffee`).
2. **Database** — *MySQL® Databases* (or the *Database Wizard*): create a
   database and a user with all privileges on it. cPanel usually prefixes
   both with your account username (e.g. `cpaneluser_coffee`) — note the
   final database name, username and password, you'll need them for
   `config.php`.
3. **A dedicated FTP account** — *FTP Accounts*: create one scoped to that
   same `public_html/coffee` folder (not `public_html` itself, and not
   another site's FTP account — keep them separate, so a leaked secret in
   one repo can't touch the other sites).

### GitHub secrets

This repo's **Settings → Secrets and variables → Actions**, add
`FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` — the account from step 3
above.

### Deploy the code, then create `config.php` on the server

1. **Actions** tab → **Deploy to coffee.thebriffas.co.uk** → **Run
   workflow**. This lands `public/`'s contents straight into
   `public_html/coffee/`.
2. `config.php` is gitignored, so it's never in that upload — create it
   directly on the server once, via cPanel's *File Manager*: new file at
   `public_html/coffee/config.php`. Open `config/config.example.php` from
   this repo on your own machine, copy its contents in, and fill in the
   **step 2** database credentials plus a mail "from" address. Set
   `app.base_url` explicitly to `https://coffee.thebriffas.co.uk` rather
   than leaving it blank, since this host terminates TLS in front of PHP.

### Load the schema

No SSH on this host, so use cPanel's **phpMyAdmin** instead: select the new
database, **Import** tab, and upload `sql/schema.sql` **from your own
machine's copy of the repo** (nothing needs to exist on the server for
this — phpMyAdmin's import reads the file you pick locally).

### Create your login

There's no self-registration page, and this host doesn't give you an easy
way to run `bin/create_user.php` against the live database. Instead, hash a
password locally and insert the row yourself:

```bash
php -r "echo password_hash('a-strong-password', PASSWORD_DEFAULT), \"\n\";"
```

Then in phpMyAdmin's **SQL** tab, against the same database:

```sql
INSERT INTO users (name, email, password_hash)
VALUES ('Your Name', 'you@example.com', 'paste-the-hash-here');
```

(If your host has enabled *Remote MySQL* access for your database and
allowed your IP, you can instead point your local `config/config.php` at
the production database temporarily and run `php bin/create_user.php`
normally — just remember to point it back afterwards.)

### HTTPS

The `*.thebriffas.co.uk` wildcard certificate should cover this subdomain
automatically. If it doesn't pick it up on its own, cPanel's **SSL/TLS
Status** page has an "Autofill by Domain" option to apply the existing
wildcard to the new subdomain — no need to issue a second certificate.

### Go live

Visit `https://coffee.thebriffas.co.uk/login.php` and sign in with the
account you just created.

## Security notes

- All database queries use prepared statements (PDO)
- Passwords are hashed with PHP's `password_hash` (bcrypt)
- Reset tokens are random 32-byte values; only their SHA-256 hash is stored,
  and they expire after 1 hour
- All forms are protected with CSRF tokens
- Every blend query is scoped to the logged-in user's `user_id`
