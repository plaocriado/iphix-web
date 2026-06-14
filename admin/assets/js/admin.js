'use strict';

const sidebar   = document.getElementById('adminSidebar');
const adminMain = document.getElementById('adminMain');
const toggle    = document.getElementById('sidebarToggle');
const mobileBtn = document.getElementById('mobileSidebarBtn');
const overlay   = document.getElementById('mobileOverlay');

let collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
if (collapsed) { sidebar?.classList.add('collapsed'); adminMain?.classList.add('expanded'); }

toggle?.addEventListener('click', () => {
  collapsed = !collapsed;
  sidebar?.classList.toggle('collapsed', collapsed);
  adminMain?.classList.toggle('expanded', collapsed);
  localStorage.setItem('sidebarCollapsed', collapsed);
});

mobileBtn?.addEventListener('click', () => {
  sidebar?.classList.add('mobile-open');
  overlay?.classList.add('active');
});
overlay?.addEventListener('click', () => {
  sidebar?.classList.remove('mobile-open');
  overlay?.classList.remove('active');
});

if (window.Chart) {
  Chart.defaults.color = '#8892a4';
  Chart.defaults.borderColor = 'rgba(255,255,255,0.05)';
  Chart.defaults.font.family = "'DM Sans', sans-serif";
}
