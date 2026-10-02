# Fathom — Company Landing Page

A responsive, single-page company landing page built with plain HTML, CSS, and JavaScript (no frameworks, no build step).

## Live structure

```
fathom-landing/
├── index.html        # All page markup and content, organized by section
├── css/
│   └── style.css      # Design tokens (colors, type), layout, and responsive rules
├── js/
│   └── script.js       # Mobile nav, scroll-reveal animation, pricing toggle, demo form
└── README.md
```

## How to run it

No build tools or server required.

1. Download / unzip the folder.
2. Open `index.html` directly in any browser — or serve locally (e.g. VS Code "Live Server").

## Features included

- Sticky glassmorphic header with responsive navigation
- Hero section with interactive SVG artwork
- Trusted-by client logos
- Product features, process flow, and client testimonial
- Dynamic pricing switcher with annual discounts
- Fully responsive across mobile, tablet, and widescreen layouts
## Task 6 — Company Service Management

- `database/services.sql` : creates the `services` table (run once in phpMyAdmin).
- `api/manage_content.php` : admin-only CRUD (GET / POST / PUT / DELETE) on services.
- `api/services.php` : public read-only endpoint used by the landing page.
- `admin.html` : the internal dashboard to add / edit / delete services.
- `index.html` + `js/script.js` : the "Our Services" section is built from the API, nothing is hard-coded.

Run with XAMPP: put the folder in `htdocs`, open `http://localhost/fathom-landing/`.
Make an admin account by changing `role` to `admin` for your user in the `users` table.

## Task 7 — Customer Request Management

- `database/requests.sql` : adds `status` (`new` / `in_progress` / `resolved`) and `updated_at` to the `inquiries` table (run once).
- `api/contact.php` : customer-side submission from the public "Contact" form — already existed, fixed to store clean text and validate field length.
- `api/inquiries.php` : admin-only. `GET` lists all requests, `PUT` updates a request's status (validated against a whitelist), `DELETE` removes a request.
- `requests.html` : internal dashboard — lists requests with filters (All / New / In Progress / Resolved), a "View" button for full details, and a status dropdown + Update button per row.
- Linked from `admin.html`'s top bar ("Customer Requests"), and links back to Services from its own top bar.

Flow: customer submits the contact form on `index.html` → `api/contact.php` saves it with status `new` → staff open `requests.html`, review it, and move it to `in_progress` / `resolved`.

## Task 8 — Search & Filtering for Company Data

Implemented inside the **Customer Requests** section (`requests.html`).

- `api/inquiries.php` (`GET`) now accepts optional query params: `q` (free-text search across name/email/subject/message), `status`, `from`, `to` (date range on `created_at`). All are combined into one parameterized `WHERE` clause built at request time — nothing is hardcoded, and no filtering happens on data already loaded in the browser.
- `%`/`_` in the search term are escaped before being used in `LIKE`, so a user's own wildcard characters don't affect matching.
- `requests.html`: replaced the old client-side status buttons with a search box (debounced 350ms), a status dropdown, and a From/To date range — all four can be combined, and every change re-queries the backend. A "results found" count and a "Clear" button were added.

Example: typing "shipping" while Status = "New" and From = last 7 days returns only new requests from that week whose name/email/subject/message contains "shipping" — computed entirely in SQL.

## Task 10 — File & Document Management

Implemented on the **Customer Dashboard** (`dashboard.html`) — available to every authenticated role (user/employee/admin), since document management is a personal feature, not an elevated one.

- `database/documents.sql` : creates the `documents` table (`user_id`, `original_name`, `stored_name`, `mime_type`, `size_bytes`, `uploaded_at`).
- `api/documents.php` :
  - `POST ?action=upload` (multipart/form-data) — validates the upload (size ≤ 5 MB, extension whitelist: pdf/doc/docx/xls/xlsx/ppt/pptx/png/jpg/jpeg/txt/csv), then double-checks the file's *real* content type with `finfo` and rejects anything that looks like a script/executable even if the extension was disguised. Stores the file on disk under a random name (never the original filename) and records its metadata in the database.
  - `GET ?action=list` — returns only the current user's own documents.
  - `GET ?action=download&id=X` — streams the file back, after confirming the document belongs to the logged-in user.
  - `DELETE` (`{id}`) — removes the DB row and the file from disk, same ownership check.
  - All four actions are gated by `requirePermission()` from the Task 9 RBAC system (`documents.upload` / `documents.view_own` / `documents.delete_own`, granted to all three roles) **plus** a row-level ownership check — a valid permission is not enough on its own if the document belongs to someone else.
- `api/uploads/documents/` : where files are actually stored, with a `.htaccess` that refuses to execute `.php`/`.phtml`/etc. even if one ever ended up there (defense in depth; the real gate is the extension + content-type check).
- `dashboard.html` : new "My Documents" panel — upload form, list with Download/Delete, friendly error toasts for invalid files.

Invalid submissions are rejected with a clear message at the point they occur: no file chosen, file over 5 MB, disallowed extension, or content that doesn't match an allowed type.
