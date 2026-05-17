// ECEC Help Center - shared scripts
(function () {
  // Top progress bar
  const bar = document.getElementById('bar');
  if (bar) {
    function tick() {
      const t = document.documentElement.scrollHeight - window.innerHeight;
      bar.style.width = (t > 0 ? (window.scrollY / t * 100) : 0) + '%';
    }
    document.addEventListener('scroll', tick, { passive: true });
    tick();
  }

  // Reveal sections on scroll
  const els = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, { threshold: .12 });
    els.forEach(e => io.observe(e));
  } else {
    els.forEach(e => e.classList.add('in'));
  }

  // TOC active section
  const links = document.querySelectorAll('.toc a');
  if (links.length) {
    const sections = Array.from(links).map(l => document.querySelector(l.getAttribute('href')));
    function update() {
      let active = 0;
      sections.forEach((s, i) => {
        if (s && s.getBoundingClientRect().top < 120) active = i;
      });
      links.forEach((l, i) => l.classList.toggle('active', i === active));
    }
    document.addEventListener('scroll', update, { passive: true });
    update();
  }
})();
