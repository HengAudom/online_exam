import './bootstrap';
import 'katex/dist/katex.min.css';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { initPwa } from './composables/usePwaInstall';

// Initialize PWA event listeners & Service Worker registration
initPwa();

const app = createApp(App);
app.use(router);
app.mount('#app');

// Gracefully dismiss the standalone startup splash screen if present
if (typeof window !== 'undefined') {
  const splash = document.getElementById('splash-screen');
  if (splash) {
    // Smooth transition
    requestAnimationFrame(() => {
      splash.classList.add('fade-out');
      setTimeout(() => {
        if (splash.parentNode) {
          splash.parentNode.removeChild(splash);
        }
      }, 400);
    });
  }
}
