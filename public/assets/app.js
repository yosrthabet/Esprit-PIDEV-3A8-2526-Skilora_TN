import './bootstrap.js';
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
  if (horizontalSection && horizontalTrack && window.innerWidth >= 768) {
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

document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) {
    window.lucide.createIcons();
  }

  if (document.body.dataset.page === 'home-connected') {
    window.initHomepage();
  }
});
