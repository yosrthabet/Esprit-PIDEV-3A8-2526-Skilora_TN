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

  /* ─── LOADER → HERO REVEAL ─── */
  const loaderTL = gsap.timeline({
      onComplete: () => {
          gsap.set(".hero-title, .hero-subtitle, .hero-scroll-indicator", { clearProps: "all" });
      }
  });
  loaderTL.to("#loader-dot", { scale: 0, opacity: 0, duration: 0.5, ease: "power2.inOut" })
          .to("#loader", { opacity: 0, duration: 0.5, display: "none" }, "-=0.2");
  loaderTL.from(".hero-title", { y: 80, opacity: 0, duration: 1.2, ease: "power4.out", stagger: 0.2 }, "-=0.2")
          .from(".hero-subtitle", { y: 40, opacity: 0, duration: 0.8, ease: "power3.out", stagger: 0.1 }, "-=0.6")
          .from(".hero-scroll-indicator", { opacity: 0, y: 20, duration: 0.6, ease: "power2.out" }, "-=0.3");

  /* ─── HERO PARALLAX DEPTH (3 orbs at different speeds) ─── */
  gsap.to("#hero-blob", {
      y: "40vh", scale: 1.5,
      scrollTrigger: { trigger: "#hero-section", start: "top top", end: "bottom top", scrub: true }
  });
  gsap.to("#hero-orb-2", {
      y: "25vh", scale: 1.3, x: "-5vw",
      scrollTrigger: { trigger: "#hero-section", start: "top top", end: "bottom top", scrub: 0.5 }
  });
  gsap.to("#hero-orb-3", {
      y: "55vh", scale: 1.8, x: "8vw",
      scrollTrigger: { trigger: "#hero-section", start: "top top", end: "bottom top", scrub: 1.5 }
  });

  /* ─── HERO MARQUEE ─── */
  if (document.getElementById("hero-marquee")) {
    gsap.to("#hero-marquee", { xPercent: -50, ease: "none", duration: 20, repeat: -1 });
  }

  /* ─── VIDEO SCROLL SCRUB (frame-by-frame on scroll) ─── */
  const vfSection = document.getElementById("video-frame-section");
  const vfVideo = document.getElementById("video-scrub");
  if (vfSection && vfVideo) {
      let vfInitialized = false;
      const initVideoScrub = () => {
          if (vfInitialized) return;
          if (!vfVideo.duration || isNaN(vfVideo.duration) || vfVideo.duration === Infinity) return;
          vfInitialized = true;

          vfVideo.currentTime = 0;
          vfVideo.pause();

          const scrubObj = { frame: 0 };

          const vfTl = gsap.timeline({
              scrollTrigger: {
                  trigger: vfSection,
                  start: "top top",
                  end: "+=300%",
                  scrub: 0.5,
                  pin: true,
                  pinSpacing: true,
                  anticipatePin: 1,
              }
          });

          vfTl.to(scrubObj, {
              frame: vfVideo.duration,
              ease: "none",
              duration: 2,
              onUpdate: () => { vfVideo.currentTime = scrubObj.frame; }
          });

          const texts = document.querySelectorAll('#vf-overlay .vf-text');
          const count = texts.length;
          if (count > 0) {
              const sliceDur = 2 / count;
              texts.forEach((txt, i) => {
                  const start = i * sliceDur;
                  vfTl.fromTo(txt,
                      { opacity: 0, y: 60, scale: 0.95 },
                      { opacity: 1, y: 0, scale: 1, duration: sliceDur * 0.3, ease: "power2.out" },
                      start
                  );
                  if (i < count - 1) {
                      vfTl.to(txt, { opacity: 0, y: -30, duration: sliceDur * 0.2, ease: "power1.in" }, start + sliceDur * 0.7);
                  }
              });
          }

          ScrollTrigger.refresh();
      };

      vfVideo.addEventListener('loadedmetadata', initVideoScrub);
      vfVideo.addEventListener('canplay', initVideoScrub);
      if (vfVideo.readyState >= 1) initVideoScrub();
      vfVideo.load();
      // polling fallback
      let attempts = 0;
      const poll = setInterval(() => {
          if (vfInitialized || ++attempts > 50) { clearInterval(poll); return; }
          initVideoScrub();
      }, 200);
  }

  /* ─── CLIP-PATH TEXT REVEALS ─── */
  document.querySelectorAll('.clip-reveal').forEach(el => {
      ScrollTrigger.create({
          trigger: el,
          start: "top 85%",
          onEnter: () => el.classList.add('revealed'),
          once: true,
      });
  });

  /* ─── ABOUT SECTION ─── */
  gsap.from("#about-section .space-y-8 > *", {
      y: 40, opacity: 0, stagger: 0.1, duration: 0.8, ease: "power3.out",
      scrollTrigger: { trigger: "#about-section", start: "top 70%" }
  });
  gsap.from("#about-section .grid.grid-cols-2 > *", {
      y: 60, opacity: 0, stagger: 0.15, duration: 1, ease: "power3.out",
      scrollTrigger: { trigger: "#about-section .relative", start: "top 75%" }
  });

  /* ─── STAT COUNTER ANIMATION ─── */
  document.querySelectorAll('.stat-number[data-target]').forEach(el => {
      const target = parseInt(el.dataset.target, 10);
      const suffix = el.dataset.suffix || '';
      if (isNaN(target)) return;
      ScrollTrigger.create({
          trigger: el, start: "top 90%", once: true,
          onEnter: () => {
              gsap.to({ val: 0 }, {
                  val: target, duration: 1.5, ease: "power2.out",
                  onUpdate: function() { el.textContent = Math.round(this.targets()[0].val) + suffix; }
              });
          }
      });
  });

  /* ─── HORIZONTAL SCROLL (desktop) ─── */
  const horizontalSection = document.getElementById("horizontal-section");
  const horizontalTrack = document.getElementById("horizontal-track");
  if (horizontalSection && horizontalTrack && window.innerWidth >= 768) {
      gsap.to(horizontalTrack, {
          x: () => -(horizontalTrack.scrollWidth - window.innerWidth),
          ease: "none",
          scrollTrigger: {
              trigger: horizontalSection, start: "top top",
              end: () => `+=${horizontalTrack.scrollWidth}`,
              scrub: 1, pin: true, invalidateOnRefresh: true, anticipatePin: 1,
          }
      });
  }

  /* ─── PROCESS STEPS ─── */
  const featuresSection = document.getElementById("features-section");
  if (featuresSection) {
      gsap.from(".process-step", {
          y: 40, opacity: 0, stagger: 0.15, duration: 0.8, ease: "power3.out",
          scrollTrigger: { trigger: featuresSection, start: "top 85%" }
      });
  }

  /* ─── PLATFORM FEATURES ─── */
  const platformSection = document.getElementById("platform-section");
  if (platformSection) {
      gsap.from("#platform-section .group", {
          y: 50, opacity: 0, stagger: 0.08, duration: 0.7, ease: "power3.out",
          scrollTrigger: { trigger: platformSection, start: "top 85%" }
      });
  }

  /* ─── TESTIMONIALS (pinned horizontal scroll + stagger reveal) ─── */
  const tSection = document.getElementById("testimonials-section");
  const tTrack = document.getElementById("testimonial-track");
  if (tSection && tTrack && window.innerWidth >= 768) {
      const cards = tTrack.querySelectorAll('.testimonial-card');
      gsap.set(cards, { opacity: 0.15, y: 40, scale: 0.9 });

      const scrollTween = gsap.to(tTrack, {
          x: () => -(tTrack.scrollWidth - tSection.querySelector('.max-w-7xl').offsetWidth),
          ease: "none",
          scrollTrigger: {
              trigger: tSection,
              start: "top 15%",
              end: () => `+=${tTrack.scrollWidth}`,
              scrub: 0.5,
              pin: true,
              pinSpacing: true,
              anticipatePin: 1,
              invalidateOnRefresh: true,
          }
      });

      cards.forEach(card => {
          gsap.to(card, {
              opacity: 1, y: 0, scale: 1, duration: 0.5, ease: "power2.out",
              scrollTrigger: {
                  trigger: card,
                  containerAnimation: scrollTween,
                  start: "left 80%",
                  end: "left 30%",
                  scrub: true,
              }
          });
      });
  } else if (tTrack) {
      gsap.from(".testimonial-card", {
          y: 50, opacity: 0, stagger: 0.15, duration: 0.8, ease: "power3.out",
          scrollTrigger: { trigger: tSection, start: "top 80%" }
      });
  }

  /* ─── CTA SECTION ─── */
  const ctaSection = document.querySelector('.cta-section');
  if (ctaSection) {
      gsap.to(".cta-element", {
          opacity: 1, y: 0, stagger: 0.12, duration: 1.2, ease: "power4.out",
          scrollTrigger: { trigger: ctaSection, start: "top 85%" }
      });
  }

  /* ─── MAGNETIC CURSOR ON CTA BUTTONS ─── */
  document.querySelectorAll('.magnetic-btn').forEach(btn => {
      btn.addEventListener('mousemove', (e) => {
          const rect = btn.getBoundingClientRect();
          const x = e.clientX - rect.left - rect.width / 2;
          const y = e.clientY - rect.top - rect.height / 2;
          gsap.to(btn, { x: x * 0.3, y: y * 0.3, duration: 0.3, ease: "power2.out" });
      });
      btn.addEventListener('mouseleave', () => {
          gsap.to(btn, { x: 0, y: 0, duration: 0.5, ease: "elastic.out(1, 0.3)" });
      });
  });

  setTimeout(() => ScrollTrigger.refresh(), 500);
};

window.bootstrapTheme();

document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) {
    window.lucide.createIcons();
  }
});

window.addEventListener('load', () => {
  if (document.body.dataset.page === 'home-connected') {
    window.initHomepage();
  }
});
