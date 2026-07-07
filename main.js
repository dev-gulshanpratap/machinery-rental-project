// ============================================================
// MachineryRent — Main JavaScript
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ── Mobile Nav ──
  const toggle = document.getElementById('mobileToggle');
  const navLinks = document.querySelector('.nav-links');
  if (toggle && navLinks) {
    toggle.addEventListener('click', () => {
      navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
      navLinks.style.flexDirection = 'column';
      navLinks.style.position = 'absolute';
      navLinks.style.top = '64px';
      navLinks.style.left = '0';
      navLinks.style.right = '0';
      navLinks.style.background = 'var(--bg2)';
      navLinks.style.padding = '12px';
      navLinks.style.borderBottom = '1px solid var(--border)';
    });
  }

  // ── Flash auto-dismiss ──
  const flash = document.getElementById('flashAlert');
  if (flash) setTimeout(() => flash.remove(), 5000);

  // ── Modal handling ──
  document.querySelectorAll('[data-modal]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = document.getElementById(btn.dataset.modal);
      if (modal) modal.classList.add('open');
    });
  });

  document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
    el.addEventListener('click', (e) => {
      if (e.target === el) {
        document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
      }
    });
  });

  // ── Rental date calculator ──
  const startDate = document.getElementById('start_date');
  const endDate   = document.getElementById('end_date');
  const summary   = document.getElementById('rental-summary');
  const dailyRate = parseFloat(document.getElementById('daily_rate')?.value || 0);

  function calcRental() {
    if (!startDate || !endDate || !startDate.value || !endDate.value) return;
    const start = new Date(startDate.value);
    const end   = new Date(endDate.value);
    const diffMs = end - start;
    if (diffMs <= 0) return;
    const days = Math.ceil(diffMs / (1000 * 60 * 60 * 24));
    const subtotal = days * dailyRate;
    const tax = subtotal * 0.18;
    const total = subtotal + tax;

    if (summary) {
      document.getElementById('calc-days').textContent = days + ' day(s)';
      document.getElementById('calc-sub').textContent  = '₹ ' + subtotal.toFixed(2);
      document.getElementById('calc-tax').textContent  = '₹ ' + tax.toFixed(2);
      document.getElementById('calc-total').textContent = '₹ ' + total.toFixed(2);
      summary.style.display = 'block';
    }
  }

  if (startDate) startDate.addEventListener('change', calcRental);
  if (endDate)   endDate.addEventListener('change', calcRental);

  // ── Star rating ──
  document.querySelectorAll('.star-select').forEach(container => {
    const stars = container.querySelectorAll('i');
    const input = container.nextElementSibling;

    stars.forEach((star, idx) => {
      star.addEventListener('mouseenter', () => {
        stars.forEach((s, i) => s.className = i <= idx ? 'fas fa-star' : 'far fa-star');
      });
      star.addEventListener('click', () => {
        if (input) input.value = idx + 1;
        container.dataset.rating = idx + 1;
      });
    });

    container.addEventListener('mouseleave', () => {
      const rating = parseInt(container.dataset.rating || 0);
      stars.forEach((s, i) => s.className = i < rating ? 'fas fa-star' : 'far fa-star');
    });
  });

  // ── Confirm delete ──
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
      if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
  });

  // ── Admin sidebar mobile ──
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.admin-sidebar');
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  }

  // ── Set min date for rental start ──
  if (startDate) startDate.min = new Date().toISOString().split('T')[0];

  if (startDate) startDate.addEventListener('change', () => {
    if (endDate) endDate.min = startDate.value;
  });

  // ── Print invoice ──
  const printBtn = document.getElementById('printInvoice');
  if (printBtn) printBtn.addEventListener('click', () => window.print());

  // ── Search filter live ──
  const searchBox = document.getElementById('liveSearch');
  if (searchBox) {
    searchBox.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('[data-searchable]').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // ── Animate stats on scroll ──
  const statNums = document.querySelectorAll('[data-count]');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const target = parseInt(el.dataset.count);
        let current = 0;
        const step = Math.ceil(target / 60);
        const interval = setInterval(() => {
          current = Math.min(current + step, target);
          el.textContent = current.toLocaleString();
          if (current >= target) clearInterval(interval);
        }, 20);
        observer.unobserve(el);
      }
    });
  }, { threshold: 0.5 });

  statNums.forEach(el => observer.observe(el));
});
