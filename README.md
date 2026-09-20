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
