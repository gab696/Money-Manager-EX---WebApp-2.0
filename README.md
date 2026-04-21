# MMEX Web (v2)

A modern UI rework of the [Money Manager EX WebApp](https://github.com/moneymanagerex/web-money-manager-ex).
Mobile-first **step-by-step** entry flow, full-screen numeric keypad, bottom-sheets with instant search, multi-user with invitations, installable as a PWA — and **fully drop-in compatible with the current MMEX desktop** (no C++ patch required).

> **Scope.** Mobile entry of transactions only. They are queued and pulled by
> the desktop at the next sync. No balance, no full history, no reporting —
> all of that lives on the desktop side.

![screenshot placeholder](docs/screenshot.png)

## Features

### Entry
- **Step-by-step flow** tailored for phones:
  - **Withdrawal / Deposit** (4 steps): Amount → Date + Account → Payee + Category → Notes + Summary
  - **Transfer** (3 steps): Amount → Date + Source + Destination → Notes + Summary
- **Full-screen numeric keypad** for the amount (large keys, `00`, decimal, ⌫, CE, ↩ to reuse last tx)
- **Bottom-sheets** with instant search for Account / Category / Payee — a "Frequent" section on top and an inline "＋ Create" button
- **Summary screen** before saving (amount, date, account, payee, category, notes)
- **Auto-fill**: the payee's default category is reused automatically
- **Clear buttons** on every chip (category, payee)
- Progress bar colored by type (red/green/blue)

### Queue
- Transactions grouped by day, amounts colored, type badge
- Expenses / income totals at the top
- Tap → edit, duplicate, delete before sync
- Last desktop pull timestamp

### Multi-user (new in v2)
- **Roles**: first account created = admin, subsequent accounts = standard users
- **Invitations**: the admin generates unique links (valid 7 days) to share; the invitee picks their own username and password
- **Auto-tagging**: each entry can be tagged `[By: <username>]` in the Notes field (admin toggle)
- Every user can change their own password; the admin can reset others' passwords

### Preferences and i18n
- Languages: **French** / **English** (per-user preference)
- Configurable default transaction status: None / Reconciled / Follow-up / Duplicate / Void
- Optional hiding of Payee and Category fields (matching the legacy webapp behavior)

### Desktop sync
- The `services.php` protocol is **identical to the original** — no MMEX desktop patch required
- The SQLite schema is **identical to the original** (5 tables, column names, types, collations)
- GUID visible in settings, admin-regeneratable

### PWA — installable as a native-like app
- **Manifest** + **service worker** included: Android/iOS prompt to install to the home screen
- **Official MMEX icon** (piggy bank) in all required sizes (192, 512, apple-touch 180, favicon 16/32, vector SVG)
- **Standalone** display (URL bar hidden, native splash, indigo theme color)
- **App shortcuts** (long-press on icon): "New expense" / "Queue"
- Static shell is cached → the app opens instantly even on slow/offline networks (only *submission* requires connectivity)

## Requirements

- PHP **8.0+** with the `pdo_sqlite` extension (enabled by default on most hosts)
- Apache with `mod_rewrite` (or Nginx — config below)
- Write access to the installation folder (so SQLite can create the database file)
- No MySQL. No Node.js. No mandatory Docker.

Tested on shared hosting at Infomaniak, Hostpoint, O2Switch.

## Installation (3 minutes)

1. **Upload** all files to your web space root (or a sub-folder)
2. Make sure the folder is **writable** by PHP (`chmod 755` or `775`)
3. Open `https://your-domain.com/` in the browser
   - On first launch, `MMEX_New_Transaction.db` is created automatically
   - Setup screen: pick a username + password → that account will be **admin**
4. Once logged in, go to **Settings → Sync** to grab the `services.php` URL and GUID to paste into the desktop

## Configuring MMEX desktop (unchanged)

Same as the original webapp:

1. Open MMEX desktop
2. *Options → Network → WebApp Settings*
3. Paste the URL (e.g. `https://your-domain.com/services.php`) and the GUID
4. *Tools → Refresh WebApp* → the desktop pushes accounts / categories / payees
5. Transactions you enter on mobile will be pulled on every *Refresh WebApp*

The `services.php` protocol (URLs, parameters, JSON format, status codes) is reproduced byte-for-byte — the desktop sees no difference.

### Installing on a smartphone (PWA)

- **Android / Chrome**: open the URL → automatic "Install app" banner → one tap. Or ⋮ menu → "Install app"
- **iOS / Safari**: open the URL → Share button → "Add to Home Screen". The app then opens as a native-like app (no Safari bar)
- **Desktop Chrome / Edge**: ⊕ icon in the address bar → "Install"

Once installed, the MMEX icon sits on the home screen and the app launches straight to the entry form (no re-login while the session is valid).

## Migrating from the legacy webapp

If you already have an `MMEX_New_Transaction.db` from the original webapp:
1. Drop it in the project root **before** the first launch
2. The app reuses it as-is (the schema is identical)
3. Your existing user (`Parameters.Username` / `Password` sha512) is migrated to the new `Users` table with admin role
4. On your next login, the password is transparently re-hashed to bcrypt

## Inviting additional users

1. Log in as admin → **Settings → Invitations**
2. Click "Generate link" → a unique 7-day link appears
3. Copy and send it (SMS, email, Signal, anything)
4. The invitee opens the link → picks their own username and password → is auto-logged in
5. You can revoke a link as long as it has not been used

Invitations used or expired for more than 30 days are purged automatically.

## Adding a language

The i18n system is **file-based** and designed for easy extension. Each locale is a PHP file returning a nested array of translation keys; missing keys fall back to English. Adding a new language is three steps:

### 1. Create the translation file

Copy `app/Lang/en.php` to `app/Lang/<code>.php` where `<code>` is the [ISO 639-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes) language code (e.g. `it`, `de`, `es`, `pt`). Translate the string values, keeping the keys and structure intact.

```php
// app/Lang/it.php
<?php
return [
    'app' => [ 'name' => 'MMEX Web' ],
    'common' => [
        'back'   => 'Indietro',
        'edit'   => 'Modifica',
        'delete' => 'Elimina',
        // ...
    ],
    // ...
];
```

### 2. Register the locale

Open `app/I18n.php` and add an entry to the `LOCALES` constant:

```php
public const LOCALES = [
    'fr' => 'Français',
    'en' => 'English',
    'it' => 'Italiano',   // ← added
];
```

### 3. Upload the files

Upload `app/Lang/<code>.php` and the updated `app/I18n.php`. The new language appears immediately in **Settings → Preferences → Language** for every user to pick.

**Partial translations are fine** — any missing key automatically falls back to the English string, so you can contribute a language incrementally. Pull requests with new translation files are welcome.

### JavaScript strings

A subset of strings (toasts, dynamic labels used by the wizard) lives in JavaScript and is injected by the server into `window.MMEX_BOOT.strings` at page load. Those keys are under the `tx.*` namespace in the translation files and are listed in `TransactionController::jsStrings()`. Whenever you add a new JS-side string, add the key to that list and to each locale file.

### Locale code for date / number formatting

The client-side uses `Intl.DateTimeFormat` and `toLocaleDateString` with a BCP-47 code derived from the active locale (`fr-CH` for French, `en-GB` for English by default). To change the regional variant (e.g. `en-US` for US date order), edit `TransactionController::jsLocale()`.

## Deploying in a sub-folder

Example for `https://your-domain.com/mmex/`:
- Upload into `public_html/mmex/`
- Copy `config.php.example` to `config.php` and set `'base_url' => '/mmex'`
- In `.htaccess`, un-comment `RewriteBase /mmex/`

## Without Apache (Nginx)

```nginx
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}
location ~ \.(db|sqlite|sqlite3)$ { deny all; }
location ~ ^/config\.php$         { deny all; }
location ~ ^/(app|prototype)/     { deny all; }
```

## File tree

```
/                              ← web root
├── index.php                  ← UI front controller
├── services.php               ← Sync API consumed by the desktop (original protocol)
├── manifest.webmanifest       ← PWA manifest
├── sw.js                      ← Service worker (caches static shell)
├── MMEX_New_Transaction.db    ← SQLite, auto-created on first launch
├── .htaccess                  ← Rewrite + .db / config.php protection + webmanifest MIME
├── config.php                 ← Optional (copy from config.php.example)
├── config.php.example
├── README.md
├── assets/
│   ├── style.css              ← Small additions to Tailwind
│   ├── app.js                 ← Alpine wizard, bottom-sheets, keypad, AJAX
│   └── icons/                 ← PWA icons (official MMEX assets)
│       ├── icon.svg           ← Vector source
│       ├── icon-192.png · icon-512.png
│       ├── apple-touch-icon.png (180×180)
│       └── favicon-16.png · favicon-32.png
├── attachments/               ← File uploads (auto-created, reserved for future use)
├── app/                       ← Application code (blocked by .htaccess)
│   ├── bootstrap.php
│   ├── Auth.php Config.php Csrf.php Db.php Router.php View.php I18n.php
│   ├── Controllers/
│   │   ├── AuthController.php          (login, setup, public invitations)
│   │   ├── TransactionController.php   (entry + update + delete)
│   │   ├── QueueController.php
│   │   ├── ListsController.php         (JSON + inline creation of payee/account/category)
│   │   └── SettingsController.php      (prefs, users, invitations, GUID)
│   ├── Models/
│   │   ├── Account.php Category.php Payee.php Transaction.php Attachment.php
│   │   ├── Parameter.php               (Parameters table)
│   │   ├── User.php                    (Users table)
│   │   └── Invitation.php              (Invitations table)
│   ├── Lang/
│   │   ├── fr.php
│   │   └── en.php
│   └── Views/
│       ├── login.php setup.php invite.php new.php queue.php settings.php
│       ├── layout/app.php nav.php pwa_head.php
│       └── errors/404.php
└── prototype/                 ← Static mock-ups (optional, blocked in prod)
```

## SQLite schema

### Tables expected by MMEX desktop (identical to the original)

| Table | Key columns | Purpose |
|---|---|---|
| `New_Transaction` | ID, Date, Account, ToAccount, Status, Type, Payee, Category, SubCategory, Amount, Notes | Queue of pending transactions |
| `Account_List` | AccountName (PK) | Accounts pushed by the desktop |
| `Payee_List` | PayeeName (PK), DefCateg, DefSubCateg | Payees pushed by the desktop |
| `Category_List` | CategoryName, SubCategoryName (composite PK NOCASE) | Categories pushed by the desktop |
| `Parameters` | Parameter (PK), Value | Version, DesktopGuid, LastSyncAt, DefaultStatus, DisablePayee, DisableCategory, Language, … |

### MMEX Web extension tables (invisible to the desktop)

| Table | Columns | Purpose |
|---|---|---|
| `Users` | id, username, password_hash (bcrypt), active, is_admin | User accounts |
| `Invitations` | id, token, created_by, expires_at, used_at, used_by | Invitation links |

## `services.php` endpoints (consumed by the desktop)

Auth: `guid=…` query parameter must match `Parameters.DesktopGuid`.

| URL | Action |
|---|---|
| `services.php?check_guid=1&guid=X` | ping |
| `services.php?check_api_version=1&guid=X` | API version (1.0.1) |
| `services.php?delete_bankaccount=1&guid=X` | clear Account_List |
| `services.php?import_bankaccount=1&guid=X` + POST MMEX_Post (JSON) | populate Account_List |
| `services.php?delete_payee=1&guid=X` / `import_payee=1` | same for payees |
| `services.php?delete_category=1&guid=X` / `import_category=1` | same for categories |
| `services.php?download_transaction=1&guid=X` | JSON of all queued transactions |
| `services.php?delete_group=1,2,3&guid=X` | remove after pulling (ACK) |
| `services.php?download_attachment=FILE&guid=X` | stream an attachment |
| `services.php?delete_attachment=FILE&guid=X` | delete an attachment |

Response: plain text `Operation has succeeded` / `Wrong GUID`, or JSON for `download_transaction`.

## UI routes

### Public
| Method | URL | Role |
|---|---|---|
| GET | `/` | redirects to `/login` |
| GET | `/setup` | first run (no users) |
| POST | `/setup` | creates the initial admin account |
| GET | `/login` | login page |
| POST | `/login` | authentication |
| GET | `/invite/{token}` | invitation landing page |
| POST | `/invite/{token}` | account creation via invitation |

### Authenticated (any user)
| Method | URL | Role |
|---|---|---|
| POST | `/logout` | log out |
| GET | `/new` | step-by-step entry form |
| GET | `/transaction/{id}/edit` | edit a queued transaction |
| POST | `/transaction` | create / update (JSON) |
| POST | `/transaction/{id}/delete` | delete |
| GET | `/queue` | pending queue |
| GET | `/api/lists` | accounts / categories / payees as JSON |
| POST | `/api/payees` / `/api/accounts` / `/api/categories` | inline creation |
| GET | `/settings` | settings |
| POST | `/settings/password` | change own password |
| POST | `/settings/preferences` | preferences (language for all, rest admin-only) |

### Admin only
| Method | URL | Role |
|---|---|---|
| POST | `/settings/guid` | regenerate desktop GUID |
| POST | `/settings/users/{id}/delete` | delete a user |
| POST | `/settings/users/{id}/password` | reset a user's password |
| POST | `/settings/invitations` | generate an invitation link |
| POST | `/settings/invitations/{id}/revoke` | revoke an invitation |

## Security

- Passwords: **bcrypt** (`password_hash` / `password_verify`) for all new accounts
- Transparent migration of legacy sha512 hashes on the next successful login
- Sessions: HttpOnly + SameSite=Lax + Secure auto under HTTPS
- CSRF on every POST (`Csrf::assertPost()`)
- SQL via prepared PDO statements (no concatenation)
- `app/`, `config.php`, `.db` blocked by `.htaccess`
- Roles: `Auth::requireAdmin()` returns a 403 on every admin-only route
- Invitations: 48-char hex tokens (24 random bytes), 7-day expiry, single-use

## Local development

```bash
# From the project root:
php -S 127.0.0.1:8080 index.php
# → http://127.0.0.1:8080/
```

Note: the built-in server does not apply `.htaccess`, but `index.php` acts as both the front controller and the fallback router (`services.php` is still reachable as a real file).

Enable debug: copy `config.php.example` to `config.php` and set `'debug' => true`.

## MMEX custom fields — known limitation

Custom fields defined in the MMEX desktop (stored in `CUSTOMFIELDDATA_V1`) **are not supported by the webapp sync protocol** — this is a limitation on the desktop side, not this webapp.

Workaround available: enable **Settings → Custom fields** to tag every entry with the author. Each saved transaction is automatically prefixed with `[By: <name>] ` in the Notes field, which is part of the standard protocol and reaches the desktop intact. For a real custom field mapping you would need to patch the MMEX desktop C++ code.

## Roadmap

- [x] Step-by-step entry for withdrawal / deposit / transfer
- [x] Queue with edit / delete / duplicate
- [x] `services.php` protocol identical to the original
- [x] SQLite schema identical to the original (+ Users / Invitations extension tables)
- [x] Multi-user with admin role + link-based invitations
- [x] File-based i18n (FR / EN, easy to add more)
- [x] PWA installable (manifest + service worker + official MMEX icons)
- [x] Automatic author tagging in Notes (custom-fields workaround)
- [ ] Mobile attachment upload (schema already ready server-side)
- [ ] IndexedDB offline queue (offline entry with flush on reconnect)
- [ ] Biometrics via WebAuthn (Face ID / Touch ID / hardware keys)
- [ ] Translations IT / DE / ES (contributions welcome — see "Adding a language")

## Donation

A PayPal donation button is included, pointing to the fork author (Gabriele Fusco). Donations are entirely voluntary — **no feature is ever gated or locked**, and every user can access every feature freely.

**For forks and self-hosters**: the donation URL is hardcoded as `Db::PAYPAL_URL` in `app/Db.php`. By open-source convention, please leave it untouched in a redistribution or a multi-tenant hosting. If you run a purely internal instance and prefer not to show the button, users can individually ignore it (it is non-blocking) — or you can comment out the tip-jar block in `app/Views/new.php` and the donation section in `app/Views/settings.php` within your local deployment. Thank you for respecting upstream attribution.

## License

GPL-2.0, same as Money Manager EX. This means you are free to use, study, modify and redistribute the code, provided derivative works are also GPL-2.0 and attribution to the original authors is preserved.
