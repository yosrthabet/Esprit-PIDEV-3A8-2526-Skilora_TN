import './app.css';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

// --- Alpine.js Toast Store ---
Alpine.store('toast', {
  items: [],
  add(message, type = 'info', duration = 4000) {
    const id = Date.now();
    this.items.push({ id, message, type });
    setTimeout(() => this.remove(id), duration);
  },
  remove(id) {
    this.items = this.items.filter(item => item.id !== id);
  },
  success(message) { this.add(message, 'success'); },
  error(message) { this.add(message, 'destructive', 6000); },
  info(message) { this.add(message, 'info'); },
  warning(message) { this.add(message, 'warning', 5000); },
});

window.Alpine = Alpine;
Alpine.start();
