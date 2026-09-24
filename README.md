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
