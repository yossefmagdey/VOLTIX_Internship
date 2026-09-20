document.addEventListener('DOMContentLoaded', () => {

  /* ---------- 1. Mobile nav toggle ---------- */
  const navToggle = document.getElementById('navToggle');
  const mainNav = document.getElementById('mainNav');

  if (navToggle && mainNav) {
    navToggle.addEventListener('click', () => {
      const isOpen = mainNav.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', String(isOpen));
      navToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
    });

    mainNav.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        mainNav.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  /* ---------- 2. Scroll reveal ---------- */
  const revealEls = document.querySelectorAll('[data-reveal]');

  if ('IntersectionObserver' in window && revealEls.length) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15 }
    );

    revealEls.forEach((el) => {
      el.classList.add('reveal-init');
      observer.observe(el);
    });
  }

  /* ---------- 3. Pricing toggle ---------- */
  const billingSwitch = document.getElementById('billingSwitch');
  const prices = document.querySelectorAll('.price[data-monthly]');

  if (billingSwitch) {
    billingSwitch.addEventListener('click', () => {
      const isAnnual = billingSwitch.getAttribute('aria-checked') === 'true';
      const next = !isAnnual;
      billingSwitch.setAttribute('aria-checked', String(next));

      prices.forEach((priceEl) => {
        const value = next ? priceEl.dataset.annual : priceEl.dataset.monthly;
        priceEl.textContent = `$${value}`;
      });
    });
  }

  /* ---------- 4. Contact / Inquiry Form ---------- */
  const contactForm = document.getElementById('contactForm');
  const formResponse = document.getElementById('formResponse');
  const submitBtn = document.getElementById('submitBtn');

  if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (formResponse) {
        formResponse.textContent = 'Sending message...';
        formResponse.style.color = '#10263A';
      }
      if (submitBtn) submitBtn.disabled = true;

      const formData = {
        name: document.getElementById('userName')?.value.trim() || '',
        email: document.getElementById('userEmail')?.value.trim() || '',
        subject: document.getElementById('userSubject')?.value.trim() || '',
        message: document.getElementById('userMessage')?.value.trim() || ''
      };

      try {
        const response = await fetch('api/contact.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          credentials: 'include',
          body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (response.ok && result.success) {
          if (formResponse) {
            formResponse.textContent = `✅ ${result.message}`;
            formResponse.style.color = 'green';
          }
          contactForm.reset();
        } else {
          if (formResponse) {
            formResponse.textContent = `❌ ${result.message}`;
            formResponse.style.color = 'red';
          }
        }
      } catch (error) {
        console.error('Error:', error);
        if (formResponse) {
          formResponse.textContent = '❌ Failed to connect to server. Please check Apache & MySQL.';
          formResponse.style.color = 'red';
        }
      } finally {
        if (submitBtn) submitBtn.disabled = false;
      }
    });
  }

  /* ---------- 5. Dynamic footer year ---------- */
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  /* ---------- 6. Dynamic content loader ---------- */
  loadServices();

  // لو المستخدم رجع للتاب بعد ما الأدمن عدّل، نحدّث الخدمات بدون Refresh
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) loadServices();
  });
});

/* ---------- Services (Public site) ---------- */
// بنجيب الخدمات من الـ Backend (api/services.php) ونبنيها كـ cards.
// مفيش أي خدمة مكتوبة يدويًا في الـ HTML، فأي تعديل من لوحة التحكم بيظهر هنا.
async function loadServices() {
  const grid = document.getElementById('servicesGrid');
  if (!grid) return;

  try {
    const res = await fetch('api/services.php', { cache: 'no-store' });
    const result = await res.json();

    if (!res.ok || !result.success) {
      throw new Error(result.message || 'Request failed');
    }
    renderServices(grid, result.data);
  } catch (err) {
    console.error('Error loading services:', err);
    showServicesMessage(grid, 'Services are temporarily unavailable. Please try again later.');
  }
}

function renderServices(grid, services) {
  grid.innerHTML = '';

  if (!services.length) {
    showServicesMessage(grid, 'No services are available right now.');
    return;
  }

  services.forEach((service) => {
    // textContent (مش innerHTML) عشان أي نص من الداتابيز ما يتنفذش كـ HTML
    const card = document.createElement('article');
    card.className = 'service-card';

    const tag = document.createElement('span');
    tag.className = 'service-tag';
    tag.textContent = service.category;

    const title = document.createElement('h3');
    title.textContent = service.title;

    const desc = document.createElement('p');
    desc.textContent = service.description;

    card.append(tag, title, desc);
    grid.appendChild(card);
  });
}

function showServicesMessage(grid, text) {
  grid.innerHTML = '';
  const p = document.createElement('p');
  p.className = 'services-message';
  p.textContent = text;
  grid.appendChild(p);
}
