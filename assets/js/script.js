// assets/js/script.js — Wiki CMS Interactive Enhancements

document.addEventListener('DOMContentLoaded', function () {

  /* ── 1. Mobile Navigation Toggle ── */
  const toggler = document.getElementById('wk-mobile-toggle');
  const mobileNav = document.getElementById('wk-nav-mobile');
  if (toggler && mobileNav) {
    toggler.addEventListener('click', function () {
      const isOpen = mobileNav.classList.toggle('open');
      toggler.setAttribute('aria-expanded', isOpen);
      toggler.innerHTML = isOpen
        ? '<i class="bi bi-x-lg"></i>'
        : '<i class="bi bi-list"></i>';
    });
    // Close on outside click
    document.addEventListener('click', function (e) {
      if (!toggler.contains(e.target) && !mobileNav.contains(e.target)) {
        mobileNav.classList.remove('open');
        toggler.setAttribute('aria-expanded', 'false');
        toggler.innerHTML = '<i class="bi bi-list"></i>';
      }
    });
    // Close on nav link click
    mobileNav.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        mobileNav.classList.remove('open');
        toggler.innerHTML = '<i class="bi bi-list"></i>';
      });
    });
  }

  /* ── 2. Scroll-Reveal Animations (IntersectionObserver) ── */
  const revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length > 0 && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    revealEls.forEach(function (el) { observer.observe(el); });
  } else {
    // Fallback: show all
    revealEls.forEach(function (el) { el.classList.add('visible'); });
  }

  /* ── 3. Smooth Scroll for TOC anchor links ── */
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      const target = document.querySelector(a.getAttribute('href'));
      if (target) {
        e.preventDefault();
        const navH = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-h')) || 66;
        const top = target.getBoundingClientRect().top + window.scrollY - navH - 16;
        window.scrollTo({ top: top, behavior: 'smooth' });
      }
    });
  });

  /* ── 4. Navbar scroll shadow enhancement ── */
  const navbar = document.querySelector('.wk-navbar');
  if (navbar) {
    window.addEventListener('scroll', function () {
      navbar.style.boxShadow = window.scrollY > 10
        ? '0 4px 20px rgba(0,0,0,.1)'
        : '0 1px 3px rgba(0,0,0,.06)';
    }, { passive: true });
  }

  /* ── 5. Delete confirmation (dashboard table) ── */
  document.querySelectorAll('.wk-delete-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      if (!confirm('Are you sure you want to delete this article? This action cannot be undone.')) {
        e.preventDefault();
      }
    });
  });

  /* ── 6. Active nav link highlight ── */
  let currentPath = window.location.pathname.split('/').pop() || 'index.php';
  if (!currentPath.endsWith('.php')) {
    currentPath = 'index.php';
  }
  document.querySelectorAll('.wk-nav-links a.nav-link-item, .wk-nav-mobile a').forEach(function (a) {
    const href = a.getAttribute('href') || '';
    if (href === currentPath || (currentPath === 'index.php' && (href === 'index.php' || href === ''))) {
      a.style.color = 'var(--accent)';
      a.style.background = 'var(--accent-light)';
    }
  });

  /* ── 7. Animate stat cards on dashboard ── */
  const statValues = document.querySelectorAll('.wk-stat-value[data-target]');
  if (statValues.length > 0 && 'IntersectionObserver' in window) {
    const countObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          const el = entry.target;
          const target = parseInt(el.getAttribute('data-target')) || 0;
          let current = 0;
          const step = Math.max(1, Math.ceil(target / 30));
          const timer = setInterval(function () {
            current = Math.min(current + step, target);
            el.textContent = current;
            if (current >= target) clearInterval(timer);
          }, 40);
          countObserver.unobserve(el);
        }
      });
    }, { threshold: 0.5 });
    statValues.forEach(function (el) { countObserver.observe(el); });
  }

  /* ── 8. Form submit loading state ── */
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      const submitBtn = form.querySelector('button[type="submit"]:not([name])');
      if (submitBtn && !submitBtn.disabled) {
        // Only add spinner to generic submit buttons (not Quill publish/draft buttons)
        const originalHTML = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>' + originalHTML;
        submitBtn.disabled = true;
        // Re-enable after 8s to avoid permanent lock on validation errors
        setTimeout(function () {
          submitBtn.innerHTML = originalHTML;
          submitBtn.disabled = false;
        }, 8000);
      }
    });
  });

  /* ── 9. Auto-dismiss alerts after 6s ── */
  document.querySelectorAll('.alert:not(.alert-warning)').forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = 'opacity .4s ease-out';
      alert.style.opacity = '0';
      setTimeout(function () { alert.remove(); }, 400);
    }, 6000);
  });

  /* ── 10. File input preview label ── */
  document.querySelectorAll('input[type="file"]').forEach(function (input) {
    input.addEventListener('change', function () {
      const file = input.files[0];
      if (file) {
        const label = input.closest('.mb-3')?.querySelector('.form-text');
        if (label) {
          label.textContent = '✓ Selected: ' + file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
          label.style.color = 'var(--success)';
        }
      }
    });
  });

});
