
(() => {
  const header = document.querySelector('#header .header__in');
  if (!header || document.querySelector('.topnav-links')) return;
  const nav = document.createElement('nav');
  nav.className = 'topnav-links';
  nav.setAttribute('aria-label', 'دسترسی سریع');
  setTrustedHTML(nav,`
    <a href="#home" class="is-active" data-nav>خانه</a>
    <a href="#services" data-nav>خدمات</a>
    <a href="#doctor" data-nav>درباره دکتر</a>
    <a href="#results" data-nav>نتایج درمان</a>
    <a href="#smile" data-nav>ارزیابی لبخند</a>
    <a href="#faq" data-nav>سؤالات متداول</a>
    <a href="#contact" data-nav>تماس با ما</a>`);
  const booking = header.querySelector('[data-book]');
  if (booking) header.insertBefore(nav, booking); else header.append(nav);

  const links = [...nav.querySelectorAll('a')];
  const sections = links.map(link => document.querySelector(link.getAttribute('href'))).filter(Boolean);
  const updateActive = () => {
    const offset = window.scrollY + 120;
    let current = sections[0]?.id || 'home';
    sections.forEach(section => { if (section.offsetTop <= offset) current = section.id; });
    links.forEach(link => link.classList.toggle('is-active', link.getAttribute('href') === `#${current}`));
  };
  addEventListener('scroll', updateActive, {passive:true});
  updateActive();
})();
