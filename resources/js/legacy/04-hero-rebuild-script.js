
(() => {
  const hero = document.querySelector('#home');
  if (!hero) return;
  setTrustedHTML(hero,`
    <div class="hero__pool" aria-hidden="true"></div>
    <div class="container hero__grid">
      <div class="hero__copy">
        <div class="hero-kicker hero-entrance" data-delay="0">دندانپزشکی دیجیتال؛ لبخندی دقیق‌تر</div>
        <h1 class="hero-entrance" data-delay="1"><span class="hero__line1">درمان دقیق،</span><span class="hero__line2">زیبایی طبیعی</span></h1>
        <p class="hero__lede hero-entrance" data-delay="2">با استفاده از تکنولوژی روز دنیا و رویکردی علمی، لبخندی سالم، زیبا و ماندگار برای شما طراحی می‌کنیم.</p>
        <div class="hero__cta hero-entrance" data-delay="3"><button type="button" class="btn btn--primary btn--lg" data-book><svg class="hero-book-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18m-13 5 2 2 4-4"/></svg><span>رزرو نوبت آنلاین</span></button><button type="button" class="btn btn--ghost btn--lg" id="heroVideoButton"><span class="hero-play">▶</span> تماشای ویدیو</button></div>
        <div class="hero-stats hero-entrance" data-delay="4">
          <div class="hero-stat"><span class="hero-stat__icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="5"/><path d="m8.8 12-1.3 9 4.5-2.5 4.5 2.5-1.3-9"/></svg></span><b data-count="14" data-prefix="+" data-suffix=" سال">+۱۴ سال</b><small>تخصص در ایمپلنت</small></div>
          <div class="hero-stat"><span class="hero-stat__icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M9 13l2 2 4-4"/></svg></span><b data-count="700" data-prefix="+">+۷۰۰</b><small>درمان موفق</small></div>
          <div class="hero-stat"><span class="hero-stat__icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M8 14a4.5 4.5 0 0 0 8 0M8.5 8.5h.01M15.5 8.5h.01"/></svg></span><b data-count="98" data-prefix="+" data-suffix="٪">+۹۸٪</b><small>رضایت بیماران</small></div>
        </div>
      </div>
    </div>`);

  const fa = value => String(value).replace(/[0-9]/g, digit => '۰۱۲۳۴۵۶۷۸۹'[digit]);
  const countUp = () => document.querySelectorAll('[data-count]').forEach(node => {
    const target = Number(node.dataset.count), start = performance.now(), duration = 980;
    const tick = now => { const progress = Math.min((now - start) / duration, 1); const eased = 1 - Math.pow(1 - progress, 3); node.textContent = `${node.dataset.prefix || ''}${fa(Math.round(target * eased))}${node.dataset.suffix || ''}`; if (progress < 1) requestAnimationFrame(tick); };
    requestAnimationFrame(tick);
  });

  const loader = document.querySelector('#heroLoader');
  const loaderPercent = document.querySelector('#heroLoaderPercent');
  const modal = document.querySelector('#heroVideoModal');
  let percent = 0;
  const percentTimer = setInterval(() => { percent = Math.min(percent + 7, 97); loaderPercent.textContent = `${fa(percent)}٪`; }, 64);
  let heroFinished = false; const finish = () => { if (heroFinished) return; heroFinished = true; clearInterval(percentTimer); loaderPercent.textContent = '۱۰۰٪'; setTimeout(() => { loader.classList.add('is-done'); document.body.classList.add('hero-ready'); countUp(); }, 160); };
  if (document.readyState === 'complete') finish(); else { window.addEventListener('load', finish, {once:true}); setTimeout(finish, 1300); }

  document.querySelector('#heroVideoButton')?.addEventListener('click', () => { modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false'); document.body.classList.add('is-locked'); });
  const closeVideo = () => { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); document.body.classList.remove('is-locked'); };
  document.querySelector('#heroVideoClose')?.addEventListener('click', closeVideo);
  modal?.addEventListener('click', event => { if (event.target === modal) closeVideo(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && modal?.classList.contains('is-open')) closeVideo(); });
})();
