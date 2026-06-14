'use strict';

(function () {
  const navbar = document.querySelector('.navbar-iphix');
  if (!navbar) return;
  window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 50);
  });
})();

(function () {
  const els = document.querySelectorAll('.reveal');
  if (!els.length) return;
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('revealed');
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });
  els.forEach(el => observer.observe(el));
})();

const Toast = {
  container: null,
  init() {
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.className = 'toast-container';
      document.body.appendChild(this.container);
    }
  },
  show(message, type = 'info', duration = 4000) {
    this.init();
    const icons = { success: 'bi-check-circle-fill', error: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill', warning: 'bi-exclamation-circle-fill' };
    const colors = { success: '#00ff9d', error: '#ff4d6d', info: '#00d4ff', warning: '#ffbe0b' };
    const toast = document.createElement('div');
    toast.className = `toast-iphix toast-${type}`;
    toast.innerHTML = `
      <i class="bi ${icons[type]}" style="color:${colors[type]};font-size:1.1rem;flex-shrink:0;margin-top:1px"></i>
      <div style="flex:1">
        <div style="font-size:0.9rem;color:#e2e8f4;font-weight:500">${message}</div>
      </div>
      <button onclick="this.closest('.toast-iphix').remove()" style="background:none;border:none;color:#6b7a96;cursor:pointer;padding:0;font-size:1rem;line-height:1">
        <i class="bi bi-x"></i>
      </button>`;
    this.container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, duration);
  },
  success(msg) { this.show(msg, 'success'); },
  error(msg)   { this.show(msg, 'error');   },
  info(msg)    { this.show(msg, 'info');     },
  warning(msg) { this.show(msg, 'warning'); }
};

const Cart = {
  async add(productId, qty = 1) {
    try {
      const res = await fetch('/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'add',
          producto_id: productId,
          cantidad: qty,
          csrf_token: document.querySelector('meta[name="csrf-token"]')?.content
        })
      });
      const data = await res.json();
      if (data.exito) {
        Toast.success('Producto añadido al carrito');
        this.updateBadge(data.cantidad_total);
      } else {
        Toast.error(data.error || 'Error al añadir al carrito');
      }
    } catch {
      Toast.error('Error de conexión');
    }
  },

  async remove(productId) {
    try {
      const res = await fetch('/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'remove', producto_id: productId })
      });
      const data = await res.json();
      if (data.exito) {
        document.querySelector(`.cart-item[data-id="${productId}"]`)?.remove();
        this.updateBadge(data.cantidad_total);
        this.updateTotal(data.total);
        if (data.cantidad_total === 0) location.reload();
      }
    } catch {
      Toast.error('Error al eliminar producto');
    }
  },

  updateBadge(count) {
    const badge = document.querySelector('.cart-badge');
    if (badge) {
      badge.textContent = count;
      badge.style.display = count > 0 ? 'flex' : 'none';
    }
  },

  updateTotal(total) {
    const el = document.querySelector('.cart-total-value');
    if (el) el.textContent = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(total);
  }
};

(function () {
  const searchInput = document.querySelector('.global-search-input');
  if (!searchInput) return;
  let debounceTimer;
  const resultsBox = document.createElement('div');
  resultsBox.className = 'search-results-dropdown';
  resultsBox.style.cssText = `
    position: absolute; top: 100%; left: 0; right: 0; z-index: 500;
    background: var(--color-bg-card); border: 1px solid var(--color-border);
    border-radius: var(--radius-md); box-shadow: var(--shadow-card);
    margin-top: 4px; max-height: 350px; overflow-y: auto; display: none;`;
  searchInput.parentElement.style.position = 'relative';
  searchInput.parentElement.appendChild(resultsBox);

  searchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const q = searchInput.value.trim();
    if (q.length < 2) { resultsBox.style.display = 'none'; return; }
    debounceTimer = setTimeout(async () => {
      try {
        const res = await fetch(`/api/search.php?q=${encodeURIComponent(q)}&limit=6`);
        const data = await res.json();
        if (data.length === 0) { resultsBox.style.display = 'none'; return; }
        resultsBox.innerHTML = data.map(p => `
          <a href="/pages/detalle.php?slug=${p.slug}" style="display:flex;gap:0.75rem;align-items:center;padding:0.75rem 1rem;color:var(--color-text);border-bottom:1px solid var(--color-border);transition:background 0.15s ease;" onmouseover="this.style.background='rgba(0,212,255,0.05)'" onmouseout="this.style.background=''">
            <img src="/assets/img/productos/${p.imagen_principal}" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:6px;background:#111620;flex-shrink:0">
            <div>
              <div style="font-size:0.88rem;font-weight:600;font-family:var(--font-display)">${p.nombre}</div>
              <div style="font-size:0.8rem;color:var(--color-primary);font-weight:700">${formatPrice(p.precio_venta)}</div>
            </div>
          </a>`).join('') + `<a href="/pages/buscar.php?q=${encodeURIComponent(q)}" style="display:block;padding:0.75rem 1rem;text-align:center;font-size:0.82rem;color:var(--color-primary);font-weight:600">Ver todos los resultados <i class="bi bi-arrow-right"></i></a>`;
        resultsBox.style.display = 'block';
      } catch { }
    }, 300);
  });

  document.addEventListener('click', (e) => {
    if (!searchInput.parentElement.contains(e.target)) resultsBox.style.display = 'none';
  });
})();

(function () {
  const mainImg = document.querySelector('.product-gallery-main img');
  if (!mainImg) return;
  document.querySelectorAll('.product-thumb').forEach(thumb => {
    thumb.addEventListener('click', () => {
      document.querySelectorAll('.product-thumb').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
      mainImg.src = thumb.src;
      mainImg.style.animation = 'fadeIn 0.3s ease';
      setTimeout(() => mainImg.style.animation = '', 300);
    });
  });
})();

document.querySelectorAll('.cart-qty-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const item = btn.closest('.cart-item');
    const id = parseInt(item?.dataset.id);
    const action = btn.dataset.action;
    if (!id) return;
    const res = await fetch('/api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: action === '+' ? 'increase' : 'decrease', producto_id: id })
    });
    const data = await res.json();
    if (data.exito) {
      const qtyEl = item.querySelector('.cart-qty-value');
      if (qtyEl) qtyEl.textContent = data.nueva_cantidad;
      Cart.updateBadge(data.cantidad_total);
      Cart.updateTotal(data.total);
      if (data.nueva_cantidad === 0) item.remove();
    }
  });
});

(function () {
  const minRange = document.querySelector('#precio_min');
  const maxRange = document.querySelector('#precio_max');
  const minLabel = document.querySelector('#precio_min_label');
  const maxLabel = document.querySelector('#precio_max_label');
  if (!minRange || !maxRange) return;
  const update = () => {
    if (minLabel) minLabel.textContent = parseInt(minRange.value) + ' €';
    if (maxLabel) maxLabel.textContent = parseInt(maxRange.value) + ' €';
  };
  minRange.addEventListener('input', update);
  maxRange.addEventListener('input', update);
  update();
})();

document.querySelectorAll('[data-confirm]').forEach(btn => {
  btn.addEventListener('click', e => {
    if (!confirm(btn.dataset.confirm || '¿Estás seguro?')) e.preventDefault();
  });
});

function animateCounter(el, target, duration = 1500) {
  const start = performance.now();
  const update = (time) => {
    const progress = Math.min((time - start) / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    el.textContent = Math.floor(eased * target);
    if (progress < 1) requestAnimationFrame(update);
    else el.textContent = target;
  };
  requestAnimationFrame(update);
}

const counterObserver = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      const target = parseInt(e.target.dataset.count);
      if (!isNaN(target)) animateCounter(e.target, target);
      counterObserver.unobserve(e.target);
    }
  });
}, { threshold: 0.5 });
document.querySelectorAll('[data-count]').forEach(el => counterObserver.observe(el));

function formatPrice(price) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(price);
}

document.querySelectorAll('img').forEach(img => {
  img.addEventListener('error', function() {
    this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMGUxMjIwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZpbGw9IiM0YTU1NjgiIGZvbnQtc2l6ZT0iMTQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5TaW4gaW1hZ2VuPC90ZXh0Pjwvc3ZnPg==';
  });
});

window.Cart = Cart;
window.Toast = Toast;
