const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

window.createToastStore = () => ({
  items: [],
  add(message, type = 'info', duration = 4000) {
    const id = Date.now() + Math.random();
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

document.addEventListener('alpine:init', () => {
  Alpine.store('toast', window.createToastStore());
});

window.bootstrapTheme = () => {
  const page = document.body.getAttribute('data-page') || '';
  // Theme preferences only apply to logged-in dashboard pages
  if (page !== 'dashboard') {
    document.documentElement.classList.remove('dark');
    return;
  }
  const storedTheme = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const useDark = storedTheme ? storedTheme === 'dark' : prefersDark;
  document.documentElement.classList.toggle('dark', useDark);
};

window.toggleTheme = () => {
  const isDark = document.documentElement.classList.toggle('dark');
  localStorage.setItem('theme', isDark ? 'dark' : 'light');
};

window.initOsmoNavbar = () => {
    const headerWrapper = document.querySelector('.header-wrapper');
    const navContainer = document.querySelector('.nav-container');
    const navbar = document.querySelector('.navbar');
    const menuBtn = document.querySelector('.menu-btn');
    const menuText = document.querySelector('.menu-text');
    const topLine = document.querySelector('.top-line');
    const bottomLine = document.querySelector('.bottom-line');
    let isMenuOpen = false;

    if (!headerWrapper || !navContainer || !navbar || !menuBtn) return;

    document.querySelectorAll('.rolling-text').forEach(text => {
        if (!text.getAttribute('data-text')) {
             text.setAttribute('data-text', text.innerText.trim());
        }
    });

    const menuTl = gsap.timeline({ paused: true });

    menuTl
        .to(headerWrapper, { top: '5vh', duration: 0.4, ease: "power3.inOut" })
        .to(navContainer, { width: '90vw', maxWidth: 'none', duration: 0.4, ease: "power3.inOut" }, "<")
        .to(navbar, { borderRadius: '5px', duration: 0.4 }, "<")
        .to('.marquee-banner', { height: 0, opacity: 0, duration: 0.3 }, "<") 
        .to(navContainer, { height: 'auto', duration: 0.6, ease: "expo.inOut" })
        .to(navbar, { height: 'auto', duration: 0.6, ease: "expo.inOut" }, "<")
        .to(topLine, { top: 4, rotation: 45, transformOrigin: "50% 50%", duration: 0.3 }, "<")
        .to(bottomLine, { bottom: 4, rotation: -45, transformOrigin: "50% 50%", duration: 0.3 }, "<")
        .to('.menu-overlay', { display: 'flex', opacity: 1, duration: 0.4 }, "-=0.3") 
        .from('.menu-col', { y: 30, opacity: 0, stagger: 0.1, duration: 0.6, ease: "power3.out" }, "-=0.2")
        .from('.book-img', { scale: 0.8, opacity: 0, rotationY: 45, duration: 0.8, ease: "back.out(1.2)" }, "-=0.4");

    // Toggle Logic
    const toggleMenu = () => {
        isMenuOpen = !isMenuOpen;
        menuBtn.style.pointerEvents = 'none'; 
        
        if (isMenuOpen) {
            if (menuText) menuText.innerText = "Close", menuText.setAttribute('data-text', "Close");
            // Set timeScale < 1 to slow down opening slightly
            menuTl.timeScale(0.85).play().then(() => menuBtn.style.pointerEvents = 'auto');
        } else {
            if (menuText) menuText.innerText = "Menu", menuText.setAttribute('data-text', "Menu");
            // Set timeScale to speed up reverse animation contextually
            menuTl.timeScale(1.6).reverse().then(() => {
                menuBtn.style.pointerEvents = 'auto';
                gsap.set([headerWrapper, navContainer, navbar], { clearProps: "all" });
            });
        }
    };

    menuBtn.addEventListener('click', toggleMenu);

    // Close on click outside
    document.addEventListener('click', (e) => {
        if (isMenuOpen && !navContainer.contains(e.target) && !menuBtn.contains(e.target)) {
            toggleMenu();
        }
    });

    // Scroll listener to collapse marquee and morph logo
    const handleScroll = () => {
        if (window.scrollY > 50) {
            navContainer.classList.add('is-scrolled');
        } else {
            navContainer.classList.remove('is-scrolled');
        }
    };
    window.addEventListener('scroll', handleScroll);
    handleScroll(); // Check on init
};

window.initHomepage = () => {
  window.initOsmoNavbar();
  if (!window.gsap || !window.ScrollTrigger) return;
  const { gsap, ScrollTrigger } = window;
  gsap.registerPlugin(ScrollTrigger);

  const loaderTL = gsap.timeline();
  loaderTL.to("#loader-dot", { scale: 0, opacity: 0, duration: 0.5, ease: "power2.inOut" })
          .to("#loader", { opacity: 0, duration: 0.5, display: "none" }, "-=0.2")
          .to("#hero-title", { y: "0%", duration: 1.2, ease: "power4.out" }, "-=0.3")
          .to("#hero-title-2", { y: "0%", duration: 1.2, ease: "power4.out" }, "-=1");

  gsap.to("#hero-blob", {
      y: "40vh", scale: 1.5,
      scrollTrigger: { trigger: "#hero-section", start: "top top", end: "bottom top", scrub: true }
  });

  if (document.getElementById("hero-marquee")) {
    gsap.to("#hero-marquee", { xPercent: -50, ease: "none", duration: 20, repeat: -1 });
  }


  const horizontalSection = document.getElementById("horizontal-section");
  const horizontalTrack = document.getElementById("horizontal-track");
  if (horizontalSection && horizontalTrack) {
      let getScrollAmount = () => -(horizontalTrack.scrollWidth - window.innerWidth);
      gsap.to(horizontalTrack, { 
          x: getScrollAmount, 
          ease: "none", 
          scrollTrigger: { 
              trigger: horizontalSection, 
              start: "top top",
              end: () => `+=${horizontalTrack.scrollWidth}`, 
              scrub: 1,
              pin: true,
              invalidateOnRefresh: true 
          }
      });
  }

  if (document.getElementById("footer-cta")) {
    const footerTL = gsap.timeline({ scrollTrigger: { trigger: "#footer-cta", start: "top 50%", end: "bottom bottom", scrub: 1 } });
    footerTL.to(["#cta-title", "#cta-btn"], { scale: 1, opacity: 1, stagger: 0.1, ease: "back.out(1.5)" });
  }

  setTimeout(() => ScrollTrigger.refresh(), 300);
};

window.bootstrapTheme();

window.createFormationCatalogChatbot = (config) => ({
  endpoint: config.endpoint,
  activeCategory: config.activeCategory || '',
  searchQuery: config.searchQuery || '',
  messages: [],
  input: '',
  isOpen: false,
  isLoading: false,
  showNudge: false,
  greetPulse: false,
  nudgeText: 'Looking for a specific skill? I can filter our development or design catalog for you.',
  visibleFormations: [],
  closeKey: 'skilora.chat.formations.closed',
  historyKey: 'skilora.chat.formations.history',
  engagedKey: 'skilora.chat.formations.engaged',
  intentTimer: null,
  init() {
    this.isOpen = false;
    this.messages = this.readHistory();
    const closeState = sessionStorage.getItem(this.closeKey);
    if (closeState === '1') {
      this.showNudge = false;
      this.bindObservers();
      this.bindEngagementTracking();
      return;
    }
    this.bindObservers();
    this.bindEngagementTracking();
    this.scheduleIntentTrigger();
    if (this.messages.length === 0) {
      this.messages.push({
        role: 'assistant',
        content: 'Bonjour. Je peux vous aider à trouver la bonne formation selon votre niveau et votre objectif.',
      });
      this.persistHistory();
    }
  },
  bindObservers() {
    const grid = document.querySelector('[data-formation-grid]');
    if (!grid) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        this.collectVisibleFormations();
        if (!this.isOpen && sessionStorage.getItem(this.closeKey) !== '1') {
          this.showNudge = true;
          this.greetPulse = true;
          setTimeout(() => (this.greetPulse = false), 2200);
        }
      });
    }, { threshold: 0.2 });
    observer.observe(grid);
  },
  bindEngagementTracking() {
    document.querySelectorAll('[data-formation-action]').forEach((el) => {
      el.addEventListener('click', () => {
        sessionStorage.setItem(this.engagedKey, '1');
      }, { once: true });
    });
  },
  scheduleIntentTrigger() {
    clearTimeout(this.intentTimer);
    this.intentTimer = setTimeout(() => {
      const hasEngaged = sessionStorage.getItem(this.engagedKey) === '1';
      if (hasEngaged || this.isOpen || sessionStorage.getItem(this.closeKey) === '1') return;
      this.openChat();
      this.messages.push({
        role: 'assistant',
        content: this.nudgeText,
      });
      this.persistHistory();
      this.scrollMessagesToBottom();
    }, 10000);
  },
  collectVisibleFormations() {
    const cards = Array.from(document.querySelectorAll('[data-formation-card]'));
    this.visibleFormations = cards.slice(0, 8).map((card) => ({
      id: card.dataset.formationId || '',
      title: card.dataset.formationTitle || '',
      category: card.dataset.formationCategory || '',
      categoryLabel: card.dataset.formationCategoryLabel || '',
      level: card.dataset.formationLevel || '',
    }));
  },
  openChat() {
    this.isOpen = true;
    this.showNudge = false;
    sessionStorage.removeItem(this.closeKey);
    this.$nextTick(() => this.scrollMessagesToBottom());
  },
  closeChat() {
    this.isOpen = false;
    this.showNudge = false;
    sessionStorage.setItem(this.closeKey, '1');
    this.persistHistory();
  },
  async sendMessage() {
    const content = this.input.trim();
    if (!content || this.isLoading) return;
    this.collectVisibleFormations();
    this.messages.push({ role: 'user', content });
    this.input = '';
    this.isLoading = true;
    this.persistHistory();
    this.scrollMessagesToBottom();
    try {
      const payload = {
        message: content,
        context: {
          active_category: this.activeCategory,
          search_query: this.searchQuery,
          visible_formations: this.visibleFormations,
          current_url: window.location.pathname + window.location.search,
        },
      };
      const res = await fetch(this.endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-Chat-Origin-Path': window.location.pathname,
        },
        body: JSON.stringify(payload),
      });
      if (!res.ok) throw new Error('Chatbot endpoint rejected request');
      const data = await res.json();
      this.messages.push({
        role: 'assistant',
        content: data.reply || 'Je n’ai pas pu générer de réponse pour le moment.',
      });
      this.persistHistory();
      this.scrollMessagesToBottom();
    } catch (e) {
      this.messages.push({
        role: 'assistant',
        content: 'Service assistant indisponible temporairement. Essayez à nouveau dans quelques secondes.',
      });
      this.persistHistory();
    } finally {
      this.isLoading = false;
      this.scrollMessagesToBottom();
    }
  },
  readHistory() {
    try {
      const raw = sessionStorage.getItem(this.historyKey);
      if (!raw) return [];
      const parsed = JSON.parse(raw);
      if (!Array.isArray(parsed)) return [];
      return parsed.slice(-20);
    } catch (_) {
      return [];
    }
  },
  persistHistory() {
    try {
      sessionStorage.setItem(this.historyKey, JSON.stringify(this.messages.slice(-20)));
    } catch (_) {
      // ignore storage issues
    }
  },
  scrollMessagesToBottom() {
    this.$nextTick(() => {
      const pane = this.$refs.messagesPane;
      if (pane) pane.scrollTop = pane.scrollHeight;
    });
  },
});

document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) {
    window.lucide.createIcons();
  }

  window.formationCatalogChatbot = (config) => window.createFormationCatalogChatbot(config);

  if (document.body.dataset.page === 'home-connected') {
    window.initHomepage();
  }
});
