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
  gsap.registerPlugin(ScrollTrigger); console.log("GSAP INIT");

  // Loader → hero reveal
  const loaderTL = gsap.timeline();
  loaderTL.to("#loader-dot", { scale: 0, opacity: 0, duration: 0.5, ease: "power2.inOut" })
          .to("#loader", { opacity: 0, duration: 0.5, display: "none" }, "-=0.2");

  // Hero title + subtitle stagger
  loaderTL.from(".hero-title", { y: 60, opacity: 0, duration: 1, ease: "power4.out", stagger: 0.15 }, "-=0.2")
          .from(".hero-subtitle", { y: 40, opacity: 0, duration: 0.8, ease: "power3.out", stagger: 0.1 }, "-=0.6")
          .from(".hero-scroll-indicator", { opacity: 0, y: 20, duration: 0.6, ease: "power2.out" }, "-=0.3");

  // Hero blob parallax
  gsap.to("#hero-blob", {
      y: "40vh", scale: 1.5,
      scrollTrigger: { trigger: "#hero-section", start: "top top", end: "bottom top", scrub: true }
  });

  // Hero marquee
  if (document.getElementById("hero-marquee")) {
    gsap.to("#hero-marquee", { xPercent: -50, ease: "none", duration: 20, repeat: -1 });
  }

  // Companies marquee
  const companiesTrack = document.querySelector('.companies-track');
  if (companiesTrack) {
      gsap.to(companiesTrack, { xPercent: -33.33, ease: "none", duration: 30, repeat: -1 });
  }

  // About section
  gsap.from("#about-section .space-y-8 > *", {
      y: 40, opacity: 0, stagger: 0.1, duration: 0.8, ease: "power3.out",
      scrollTrigger: { trigger: "#about-section", start: "top 70%" }
  });
  gsap.from("#about-section .grid.grid-cols-2 > *", {
      y: 60, opacity: 0, stagger: 0.15, duration: 1, ease: "power3.out",
      scrollTrigger: { trigger: "#about-section .relative", start: "top 75%" }
  });

  // Horizontal scroll (desktop)
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

  // Process steps (How it works)
  const featuresSection = document.getElementById("features-section");
  if (featuresSection) {
      gsap.from(".process-step", {
          y: 40, opacity: 0, stagger: 0.15, duration: 0.8, ease: "power3.out",
          scrollTrigger: { trigger: featuresSection, start: "top 85%" }
      });
  }

  // Categories cards
  const categoriesSection = document.querySelector(".categories-section");
  if (categoriesSection) {
      gsap.from(".category-card", {
          y: 50, opacity: 0, stagger: 0.08, duration: 0.7, ease: "power3.out",
          scrollTrigger: { trigger: categoriesSection, start: "top 85%" }
      });
  }

  // Platform features cards
  const platformSection = document.getElementById("platform-section");
  if (platformSection) {
      gsap.from("#platform-section .group", {
          y: 50, opacity: 0, stagger: 0.08, duration: 0.7, ease: "power3.out",
          scrollTrigger: { trigger: platformSection, start: "top 85%" }
      });
  }

  // Testimonials
  const testimonialsSection = document.getElementById("testimonials-section");
  if (testimonialsSection) {
      gsap.from("#testimonials-section .group", {
          y: 50, opacity: 0, stagger: 0.12, duration: 0.8, ease: "power3.out",
          scrollTrigger: { trigger: testimonialsSection, start: "top 85%" }
      });
  }

  // CTA section
  const ctaSection = document.querySelector('.cta-section');
  if (ctaSection) {
      gsap.to(".cta-element", {
          opacity: 1, y: 0, stagger: 0.12, duration: 1.2, ease: "power4.out",
          scrollTrigger: { trigger: ctaSection, start: "top 85%" }
      });
  }

  setTimeout(() => ScrollTrigger.refresh(), 300);
  window.addEventListener('load', () => ScrollTrigger.refresh());
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
