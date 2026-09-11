// =========================================================
// FATHOM — Company Landing Page
// 1. Mobile nav toggle
// 2. Scroll reveal (IntersectionObserver)
// 3. Pricing: monthly / annual toggle
// 4. Contact / Inquiry Form (Connected to PHP API)
// 5. Dynamic footer year
// =========================================================

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
        // استخدام المسار النسبي المرن
        const response = await fetch('api/contact.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
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

});
// جلب المحتوى الديناميكي من قاعدة البيانات
async function loadDynamicContent() {
  try {
    const res = await fetch('api/manage_content.php');
    const data = await res.json();
    
    if (data.success && data.data.length > 0) {
      console.log('Dynamic Content Loaded:', data.data);
      // هنا تقدر تعرض المحتوى في المكان اللي تحبه
    }
  } catch (err) {
    console.error('Error loading dynamic content:', err);
  }
}

// تشغيل الدالة أول ما الصفحة تفتح
document.addEventListener('DOMContentLoaded', loadDynamicContent);