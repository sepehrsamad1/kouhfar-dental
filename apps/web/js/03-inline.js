
/* ============================================================
   AK_DATA — all editable content in one place
   ============================================================ */
const AK_DATA = window.AK_DATA;
const AK_ASSETS = window.AK_ASSETS;

/* ============================================================
   utilities
   ============================================================ */
const $=(s,r=document)=>r.querySelector(s), $$=(s,r=document)=>[...r.querySelectorAll(s)];
const FA='۰۱۲۳۴۵۶۷۸۹';
const fa=v=>String(v).replace(/[0-9]/g,d=>FA[+d]);
const el=(t,c,h)=>{const n=document.createElement(t);if(c)n.className=c;if(h!=null)setTrustedHTML(n,h);return n};
const svcById=id=>AK_DATA.services.find(s=>s.id===id);
const jalali=(d,o)=>new Intl.DateTimeFormat('fa-IR-u-ca-persian',o).format(d);
const arrow=(d=1)=>`<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M${d>0?'15 6l-6 6 6 6':'9 6l6 6-6 6'}"/></svg>`;

function mountAsset(img,container,key){
  const cfg=window.AK_ASSETS[key],base=window.AK_ASSETS.BASE||'';
  const q=[base+cfg.file,...(cfg.fallbacks||[]).map(f=>base+f),...(cfg.fallbacks||[])];
  img.alt=cfg.alt||'';
  (function n(){ if(!q.length){container&&container.classList.add('asset-missing');return;}
    img.onerror=n; img.src=q.shift(); })();
}
const assetSrc=key=>{const c=window.AK_ASSETS[key];return (window.AK_ASSETS.BASE||'')+c.file};
const assetFallbacks=key=>{const c=window.AK_ASSETS[key],b=window.AK_ASSETS.BASE||'';
  return [...(c.fallbacks||[]).map(f=>b+f),...(c.fallbacks||[])]};
function loadInto(img,key){
  const q=[assetSrc(key),...assetFallbacks(key)];
  img.alt=window.AK_ASSETS[key].alt||'';
  (function n(){ if(!q.length)return; img.onerror=n; img.src=q.shift(); })();
}

/* ---------- Toast ---------- */
const Toast=(()=>{const n=$('#toast'),m=$('#toastMsg');let t;
  return{show(x){m.textContent=x;n.classList.add('is-on');clearTimeout(t);t=setTimeout(()=>n.classList.remove('is-on'),3200)}}})();

/* ---------- Header ---------- */
const Header=(()=>{const n=$('#header');let last=0;
  return{onScroll(y){n.classList.toggle('is-stuck',y>18);
    n.classList.toggle('is-hidden',y>420&&y>last&&!document.body.classList.contains('is-locked'));last=y}}})();

/* ---------- Nav overlay ---------- */
const Nav=(()=>{
  const n=$('#nav'),b=$('#burger'),h=$('#header');let open=false;
  const f=()=>$$('a[href],button',n);
  function set(v){open=v;n.classList.toggle('is-open',v);b.setAttribute('aria-expanded',String(v));
    document.body.classList.toggle('is-locked',v);h.classList.toggle('header--onDark',v);
    if(v){h.classList.remove('is-hidden');setTimeout(()=>f()[0]?.focus({preventScroll:true}),380);}else b.focus({preventScroll:true});}
  b.addEventListener('click',()=>set(!open));
  document.addEventListener('keydown',e=>{
    if(!open)return;
    if(e.key==='Escape')set(false);
    if(e.key==='Tab'){const k=f(),a=k[0],z=k[k.length-1];
      if(e.shiftKey&&document.activeElement===a){e.preventDefault();z.focus()}
      else if(!e.shiftKey&&document.activeElement===z){e.preventDefault();a.focus()}}
  });
  return{close:()=>open&&set(false),get isOpen(){return open}}})();

function goto(hash){
  const t=document.querySelector(hash);
  if(!t)return;
  const y=t.getBoundingClientRect().top+scrollY-(hash==='#home'?0:parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-h'))||70);
  scrollTo({top:Math.max(y,0),behavior:'smooth'});
}

/* ============================================================
   SERVICES + SERVICE SHEET
   ============================================================ */
(function buildServices(){
  const mount=$('#svcMount'),list=AK_DATA.services;
  const lead=list.find(s=>s.featured)||list[0];

  const leadEl=el('button','svc__lead reveal');
  leadEl.type='button';leadEl.dataset.service=lead.id;
  setTrustedHTML(leadEl,`
    <div>
      <p class="svc__leadEn">${lead.en}</p>
      <h3 class="svc__leadTitle">${lead.title}</h3>
    </div>
    <div style="display:grid;gap:var(--s-4)">
      <p class="svc__leadTxt">${lead.intro}</p>
      <span class="svc__leadGo">مشاهده جزئیات ${arrow(1)}</span>
    </div>`);
  mount.appendChild(leadEl);

  const rows=el('div','svc__rows');
  list.filter(s=>s!==lead).forEach((s,i)=>{
    const b=el('button','svc__row reveal');b.type='button';b.dataset.service=s.id;b.style.setProperty('--i',i%4);
    setTrustedHTML(b,`
      <span class="svc__num">${String(i+2).padStart(2,'0')}</span>
      <span class="svc__main">
        <span class="svc__title">${s.title}</span>
        ${s.subs?`<ul class="svc__subs">${s.subs.map(x=>`<li>${x}</li>`).join('')}</ul>`:`<span class="svc__subs"><li>${s.en}</li></span>`}
      </span>
      <span class="svc__arrow">${arrow(1)}</span>`);
    rows.appendChild(b);
  });
  mount.appendChild(rows);

  /* footer service links */
  const ft=$('#ftServices');
  list.forEach(s=>{const li=el('li');const b=el('button',null,s.title);b.dataset.service=s.id;li.appendChild(b);ft.appendChild(li)});
})();

const SERVICE_DETAIL_IMAGES={"implant":"assets/a-015.png","aesthetic":"assets/a-016.png","prostho":"assets/a-017.png","bleaching":"assets/a-018.png","surgery":"assets/a-019.png","endo":"assets/a-020.png","restorative":"assets/a-021.png"};
const Sheet=(()=>{
  const dlg=$('#sheet'),body=$('#sheetBody'),prev=$('#sheetPrev'),next=$('#sheetNext');
  let idx=-1;
  function render(i){
    const list=AK_DATA.services,s=list[i];idx=i;
    prev.disabled=i<=0;next.disabled=i>=list.length-1;
    setTrustedHTML(body,`
      <header>
        <p class="sd__en">${s.en}</p>
        <h2 class="sd__title">${s.title}</h2>
        <figure class="sd__photo"><img src="${SERVICE_DETAIL_IMAGES[s.id]}" alt="تصویر توضیحی ${s.title}" decoding="async" width="533" height="355"></figure>
        <p class="sd__intro">${s.intro}</p>
        ${s.subs?`<ul class="svc__subs" style="margin-block-start:var(--s-4)">${s.subs.map(x=>`<li>${x}</li>`).join('')}</ul>`:''}
      </header>
      <div class="sd__block"><h3 class="sd__h">این درمان چیست</h3><p class="sd__p">${s.what}</p></div>
      <div class="sd__block"><h3 class="sd__h">مزیت‌های اصلی</h3><ul class="sd__list">${s.benefits.map(b=>`<li><span></span>${b}</li>`).join('')}</ul></div>
      <div class="sd__block"><h3 class="sd__h">مراحل درمان</h3><div class="sd__steps">${s.steps.map((p,n)=>`
        <div class="sd__step"><span class="sd__stepN">${fa(String(n+1).padStart(2,'0'))}</span>
        <div><p class="sd__stepT">${p.t}</p><p class="sd__stepD">${p.d}</p></div></div>`).join('')}</div></div>
      <div class="sd__block"><h3 class="sd__h">برای چه کسانی ممکن است مناسب باشد</h3><ul class="sd__list">${s.who.map(w=>`<li><span></span>${w}</li>`).join('')}</ul>
        <p class="sd__stepD">تعیین نهایی مناسب بودن درمان، پس از بررسی حضوری انجام می‌شود.</p></div>
      <div class="sd__block"><h3 class="sd__h">رسانه درمان</h3>
        <div class="sd__reel"><span class="sd__reelIcon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
          <p class="sd__reelTxt">جایگاه ریلز / ویدئو این درمان<br>پس از دریافت از کلینیک جایگزین می‌شود</p></div></div>
      <div class="sd__block"><h3 class="sd__h">پرسش‌های متداول</h3>
        <div class="acc">${s.faq.map(f=>`
          <div class="acc__item"><button class="acc__btn" aria-expanded="false"><span>${f.q}</span><span class="acc__sign" aria-hidden="true"></span></button>
          <div class="acc__panel"><div class="acc__inner"><p>${f.a}</p></div></div></div>`).join('')}</div></div>
      <div class="sd__cta">
        <button class="btn btn--primary btn--lg" data-book="${s.title}">رزرو نوبت</button>
        <button class="btn btn--ghost btn--lg" data-ai>پرسش از دستیار هوشمند</button>
      </div>
      <div class="sd__nav">
        <button class="sd__navBtn" data-step="-1" ${i<=0?'disabled':''}><span>خدمت قبلی</span><b>${i>0?list[i-1].title:'—'}</b></button>
        <button class="sd__navBtn" data-step="1" style="text-align:end" ${i>=list.length-1?'disabled':''}><span>خدمت بعدی</span><b>${i<list.length-1?list[i+1].title:'—'}</b></button>
      </div>`);
    body.scrollTop=0;
  }
  function open(id){
    const i=AK_DATA.services.findIndex(s=>s.id===id);if(i<0)return;
    render(i);
    if(!dlg.open)dlg.showModal();
    requestAnimationFrame(()=>dlg.classList.add('is-on'));
    document.body.classList.add('is-locked');
  }
  function close(){dlg.classList.remove('is-on');document.body.classList.remove('is-locked');
    setTimeout(()=>dlg.open&&dlg.close(),420)}
  $('#sheetClose').addEventListener('click',close);
  prev.addEventListener('click',()=>render(idx-1));
  next.addEventListener('click',()=>render(idx+1));
  body.addEventListener('click',e=>{
    const nb=e.target.closest('.sd__navBtn');if(nb){render(idx+ +nb.dataset.step);return;}
  });
  dlg.addEventListener('cancel',e=>{e.preventDefault();close()});
  dlg.addEventListener('click',e=>{if(e.target===dlg)close()});
  return{open,close}})();

/* accordions (delegated, works for page FAQ + sheet FAQ) */
document.addEventListener('click',e=>{
  const b=e.target.closest('.acc__btn');if(!b)return;
  const item=b.closest('.acc__item'),on=item.classList.toggle('is-open');
  b.setAttribute('aria-expanded',String(on));
});

/* page FAQ */
(function buildFaq(){
  const m=$('#faqMount');
  AK_DATA.faq.forEach(f=>{
    m.appendChild(el('div','acc__item',`
      <button class="acc__btn" aria-expanded="false"><span>${f.q}</span><span class="acc__sign" aria-hidden="true"></span></button>
      <div class="acc__panel"><div class="acc__inner"><p>${f.a}</p></div></div>`));
  });
})();

/* contact rows */
(function buildContact(){
  const m=$('#ctMount');
  AK_DATA.contact.rows.forEach(r=>{
    m.appendChild(el('div','ct__row',
      `<span class="ct__k">${r.k}</span><span class="ct__v">${r.v?r.v:`<span class="placeholder">${r.ph}</span>`}</span>`));
  });
})();

/* ============================================================
   RESULTS — comparison slider
   ============================================================ */
const Results=(()=>{
  const box=$('#compare'),before=$('#cmpBefore'),after=$('#cmpAfter'),
        tabs=$('#resTabs'),slots=$('#resSlots');
  let cur=0;

  AK_DATA.cases.forEach((c,i)=>{
    const b=el('button','res__tab',c.cat);b.role='tab';b.type='button';
    b.setAttribute('aria-selected',String(i===0));b.dataset.i=i;
    tabs.appendChild(b);
  });
  AK_DATA.caseSlots.forEach(s=>slots.appendChild(el('div','res__slot',
    `<b>${s.t}</b><span>${s.s}</span><span class="placeholder" style="justify-self:center">${'در انتظار تصویر'}</span>`)));

  function show(i){
    cur=i;const c=AK_DATA.cases[i];
    loadInto(before,c.before);loadInto(after,c.after);
    $('#resCase').textContent=c.label;$('#resTitle').textContent=c.cat;$('#resNote').textContent=c.note;
    $$('.res__tab',tabs).forEach(t=>t.setAttribute('aria-selected',String(+t.dataset.i===i)));
    $('.res__cta [data-service]').dataset.service=c.service;
    set(50);
  }
  function set(pct){
    const v=Math.min(Math.max(pct,0),100);
    box.style.setProperty('--x',v+'%');
    box.setAttribute('aria-valuenow',Math.round(v));
    box.setAttribute('aria-valuetext',Math.round(v)+' درصد تصویر بعد از درمان');
    box.querySelector('.compare__tag--a').style.opacity=v<14?'0':'1';
    box.querySelector('.compare__tag--b').style.opacity=v>86?'0':'1';
  }
  function fromEvent(e){
    const r=box.getBoundingClientRect();
    if(r.width>0)set(((e.clientX-r.left)/r.width)*100);
  }
  let activePointer=null;
  function finishDrag(e){
    if(e.pointerId!==activePointer)return;
    activePointer=null;
    box.classList.remove('is-dragging');
    if(box.hasPointerCapture(e.pointerId))box.releasePointerCapture(e.pointerId);
  }
  box.addEventListener('pointerdown',e=>{
    if(activePointer!==null||e.isPrimary===false||(e.pointerType==='mouse'&&e.button!==0))return;
    activePointer=e.pointerId;
    box.classList.add('is-dragging');
    box.focus({preventScroll:true});
    box.setPointerCapture(e.pointerId);
    fromEvent(e);
  });
  box.addEventListener('pointermove',e=>{if(e.pointerId===activePointer)fromEvent(e)});
  box.addEventListener('pointerup',e=>{if(e.pointerId===activePointer)fromEvent(e);finishDrag(e)});
  box.addEventListener('pointercancel',finishDrag);
  box.addEventListener('lostpointercapture',finishDrag);
  box.addEventListener('dragstart',e=>e.preventDefault());
  box.addEventListener('keydown',e=>{
    const now=+box.getAttribute('aria-valuenow'),step=e.shiftKey?10:4;
    if(e.key==='ArrowLeft'){set(now-step);e.preventDefault()}
    if(e.key==='ArrowRight'){set(now+step);e.preventDefault()}
    if(e.key==='Home'){set(0);e.preventDefault()}
    if(e.key==='End'){set(100);e.preventDefault()}
  });
  tabs.addEventListener('click',e=>{const t=e.target.closest('.res__tab');if(t)show(+t.dataset.i)});
  show(0);
  return{}})();

/* ============================================================
   AI ASSISTANT — simulated, script-driven
   ============================================================ */
const AI=(()=>{
  const panel=$('#ai'),fab=$('#fab'),log=$('#aiLog'),quick=$('#aiQuick'),
        form=$('#aiForm'),input=$('#aiInput');
  let open=false,booted=false,busy=false;

  AK_DATA.ai.quick.forEach(q=>{const b=el('button',null,q);b.type='button';b.dataset.q=q;quick.appendChild(b)});

  const scroll=()=>log.scrollTo({top:log.scrollHeight,behavior:'smooth'});
  function me(t){log.appendChild(el('div','msg msg--me',t));scroll()}
  function bot(html,acts){
    const n=el('div','msg msg--bot',html);
    if(acts&&acts.length){
      const w=el('div','msg__act');
      acts.forEach(a=>{
        const b=el('button',null,a.t);b.type='button';
        if(a.s)b.dataset.service=a.s;
        if(a.n)b.dataset.nav=a.n;
        if(a.b!==undefined)b.dataset.book=a.b;
        w.appendChild(b);
      });
      n.appendChild(w);
    }
    log.appendChild(n);scroll();
  }
  function typing(){const n=el('div','typing','<i></i><i></i><i></i>');log.appendChild(n);scroll();return n}
  function answer(text){
    if(busy)return;busy=true;
    const t=typing();
    const q=text.toLowerCase();
    const hit=AK_DATA.ai.intents.find(i=>i.keys.some(k=>q.includes(k.toLowerCase())))||AK_DATA.ai.fallback;
    const delay=700+Math.min(text.length*18,900);
    setTimeout(()=>{t.remove();bot(hit.a,hit.act);busy=false;
      if(hit.id==='book')setTimeout(()=>Booking.open(''),600);},delay);
  }
  function boot(){
    if(booted)return;booted=true;
    const t=typing();
    setTimeout(()=>{t.remove();bot(AK_DATA.ai.open);},650);
  }
  function set(v){
    open=v;panel.classList.toggle('is-open',v);fab.setAttribute('aria-expanded',String(v));
    fab.classList.toggle('is-hidden',v);
    if(v){boot();setTimeout(()=>{if(matchMedia('(min-width:768px)').matches)input.focus({preventScroll:true})},420)}
  }
  fab.addEventListener('click',()=>set(!open));
  $('#aiClose').addEventListener('click',()=>set(false));
  quick.addEventListener('click',e=>{const b=e.target.closest('button[data-q]');if(!b)return;
    me(b.dataset.q);answer(b.dataset.q)});
  form.addEventListener('submit',e=>{e.preventDefault();
    const v=input.value.trim();if(!v)return;input.value='';me(v);answer(v)});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&open&&!$('#bk').open&&!$('#sheet').open)set(false)});
  return{open:()=>set(true),ask(q){set(true);setTimeout(()=>{me(q);answer(q)},700)}}})();

/* ============================================================
   BOOKING — 5 steps + confirmation
   ============================================================ */
const Booking=(()=>{
  const dlg=$('#bk'),rail=$('#bkRail'),next=$('#bkNext'),back=$('#bkBack'),foot=$('#bkFoot');
  const STEPS=['انتخاب خدمت','انتخاب تاریخ','انتخاب ساعت','اطلاعات تماس','تأیید رزرو'];
  const st={step:1,service:'',date:null,time:'',name:'',phone:'',ref:''};

  /* rail */
  STEPS.forEach((s,i)=>rail.appendChild(el('span','bk__step',`<i>${fa(i+1)}</i>${s}`)));

  /* step 1 */
  const svcWrap=$('#bkServices');
  AK_DATA.booking.services.forEach(s=>{
    const b=el('button','bk__opt',`<span class="bk__optT">${s}</span>`);
    b.type='button';b.setAttribute('aria-pressed','false');b.dataset.svc=s;
    svcWrap.appendChild(b);
  });
  svcWrap.addEventListener('click',e=>{const b=e.target.closest('.bk__opt');if(!b)return;
    st.service=b.dataset.svc;
    $$('.bk__opt',svcWrap).forEach(x=>x.setAttribute('aria-pressed',String(x===b)));
    sync()});

  /* step 2 — Jalali strip */
  const dateWrap=$('#bkDates');
  function buildDates(){
    dateWrap.replaceChildren();
    const now=new Date();
    for(let i=1;i<=AK_DATA.booking.days;i++){
      const d=new Date(now);d.setDate(now.getDate()+i);
      const closed=d.getDay()===5; /* Friday */
      const b=el('button','bk__date',`
        <span class="bk__dw">${jalali(d,{weekday:'short'})}</span>
        <span class="bk__dd">${jalali(d,{day:'numeric'})}</span>
        <span class="bk__dm">${jalali(d,{month:'long'})}</span>`);
      b.type='button';b.setAttribute('aria-pressed','false');b.dataset.t=d.getTime();
      if(closed){b.disabled=true;b.title='تعطیل'}
      dateWrap.appendChild(b);
    }
  }
  buildDates();
  dateWrap.addEventListener('click',e=>{const b=e.target.closest('.bk__date');if(!b)return;
    st.date=new Date(+b.dataset.t);st.time='';
    $$('.bk__date',dateWrap).forEach(x=>x.setAttribute('aria-pressed',String(x===b)));
    buildTimes();sync()});

  /* step 3 — deterministic simulated availability */
  const timeWrap=$('#bkTimes');
  function buildTimes(){
    timeWrap.replaceChildren();
    if(!st.date)return;
    const seed=st.date.getDate()+st.date.getMonth()*7;
    $('#bkTimeSub').textContent='ساعت‌های خالی برای '+jalali(st.date,{weekday:'long',day:'numeric',month:'long'});
    AK_DATA.booking.times.forEach((t,i)=>{
      const b=el('button','bk__time',fa(t));b.type='button';b.setAttribute('aria-pressed','false');b.dataset.t=t;
      if((seed+i*3)%4===0)b.disabled=true;
      timeWrap.appendChild(b);
    });
  }
  timeWrap.addEventListener('click',e=>{const b=e.target.closest('.bk__time');if(!b)return;
    st.time=b.dataset.t;
    $$('.bk__time',timeWrap).forEach(x=>x.setAttribute('aria-pressed',String(x===b)));
    sync()});

  /* step 4 — validation */
  const nameI=$('#bkName'),phoneI=$('#bkPhone');
  const normalize=v=>v.replace(/[۰-۹]/g,c=>FA.indexOf(c)).replace(/\D/g,'');
  function validName(){const ok=nameI.value.trim().length>=3;
    $('#fName').classList.toggle('is-bad',!ok&&nameI.value.length>0);
    $('#eName').textContent=(!ok&&nameI.value.length>0)?'نام را کامل وارد کنید':'';return ok}
  function validPhone(){const v=normalize(phoneI.value);const ok=/^09\d{9}$/.test(v);
    $('#fPhone').classList.toggle('is-bad',!ok&&v.length>0);
    $('#ePhone').textContent=(!ok&&v.length>0)?'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد':'';
    return ok}
  [nameI,phoneI].forEach(i=>i.addEventListener('input',()=>{
    if(i===phoneI)phoneI.value=normalize(phoneI.value).slice(0,11);
    sync()}));

  /* step 5 */
  function review(target){
    const rows=[
      ['خدمت',st.service],
      ['تاریخ',st.date?jalali(st.date,{weekday:'long',day:'numeric',month:'long',year:'numeric'}):'—'],
      ['ساعت',fa(st.time)],
      ['نام',nameI.value.trim()],
      ['موبایل',fa(normalize(phoneI.value))]
    ];
    setTrustedHTML(target,rows.map(r=>`<div class="bk__rrow"><span class="bk__rk"></span><span class="bk__rv"></span></div>`).join('')); rows.forEach((r,i)=>{const row=target.children[i];row.children[0].textContent=r[0];row.children[1].textContent=r[1]||'—';});
  }

  function canAdvance(){
    if(st.step===1)return !!st.service;
    if(st.step===2)return !!st.date;
    if(st.step===3)return !!st.time;
    if(st.step===4)return validName()&&validPhone();
    return true;
  }
  function sync(){
    $$('.bk__pane').forEach(p=>p.classList.toggle('is-now',+p.dataset.step===st.step));
    $$('.bk__step',rail).forEach((s,i)=>{s.classList.toggle('is-now',i+1===st.step);
      s.classList.toggle('is-done',i+1<st.step)});
    rail.style.display=st.step===6?'none':'';
    foot.style.display=st.step===6?'none':'';
    back.hidden=st.step<=1;
    next.textContent=st.step===5?'تأیید و ثبت رزرو':'مرحله بعد';
    next.setAttribute('aria-disabled',String(!canAdvance()));
    if(st.step===5)review($('#bkReview'));
  }

  next.addEventListener('click',()=>{
    if(!canAdvance()){
      if(st.step===4){validName();validPhone();Toast.show('نام و شماره موبایل را کامل وارد کنید')}
      else Toast.show('برای ادامه، یک گزینه انتخاب کنید');
      return;
    }
    if(st.step===5){
      setTrustedHTML(next,'<span class="spin"></span>در حال ثبت');next.setAttribute('aria-disabled','true');
      setTimeout(()=>{
        st.ref='AK-'+fa(String(Math.floor(1000+Math.random()*8999)))+'-'+fa(String(new Date().getFullYear()).slice(2));
        $('#bkRef').textContent=st.ref;review($('#bkDetails'));
        st.step=6;next.textContent='مرحله بعد';sync();
        $('.bk__body').scrollTop=0;
      },1100);
      return;
    }
    st.step++;sync();$('.bk__body').scrollTop=0;
  });
  back.addEventListener('click',()=>{if(st.step>1){st.step--;sync();$('.bk__body').scrollTop=0}});
  $('#bkShow').addEventListener('click',e=>{
    const d=$('#bkDetails');d.hidden=!d.hidden;
    e.currentTarget.textContent=d.hidden?'مشاهده جزئیات رزرو':'بستن جزئیات';
  });
  $('#bkHome').addEventListener('click',()=>{close();setTimeout(()=>goto('#home'),260)});

  function open(preselect){
    if(preselect){
      const match=AK_DATA.booking.services.find(s=>s===preselect)||
                  AK_DATA.booking.services.find(s=>preselect.includes(s)||s.includes(preselect));
      if(match){st.service=match;
        $$('.bk__opt',svcWrap).forEach(x=>x.setAttribute('aria-pressed',String(x.dataset.svc===match)));
        st.step=2;}
    }
    if(!dlg.open)dlg.showModal();
    requestAnimationFrame(()=>dlg.classList.add('is-on'));
    document.body.classList.add('is-locked');
    sync();
  }
  function close(){dlg.classList.remove('is-on');document.body.classList.remove('is-locked');
    setTimeout(()=>dlg.open&&dlg.close(),420)}
  $('#bkClose').addEventListener('click',close);
  dlg.addEventListener('cancel',e=>{e.preventDefault();close()});
  sync();
  return{open,close}})();

/* ============================================================
   SMART SMILE ASSESSMENT — frontend-only demo
   No API · no backend · no upload · no image analysis.
   The selected image is previewed locally only; the result is
   always the same predefined demo content.
   ============================================================ */

/* Smart Smile Assessment v2 — self-contained visual demonstration, no API request */
const SmileAssessment=(()=>{
  const models={
    gallery:{label:'نمونه انتخاب‌شده از گالری',before:'assets/a-022.png',after:'assets/a-023.png',findings:[
      {title:'رسوبات سطحی قابل مشاهده',desc:'در این مدل نمایشی، ناحیه‌ی مشخص‌شده برای نمایش تفاوت‌های سطحی و رسوبات احتمالی علامت‌گذاری شده است.',x:43,y:61,w:20,h:10,cx:53,cy:63},
      {title:'ناهمگونی رنگ دندان‌ها',desc:'تفاوت تون رنگ در محدوده‌ی دندان‌های پیشین به‌صورت بصری نمایش داده شده است.',x:45,y:39,w:25,h:15,cx:62,cy:43},
      {title:'بی‌نظمی خفیف تراز لبه‌ها',desc:'این محدوده برای نمایش احتمال تفاوت در تراز ظاهری لبه‌ی دندان‌ها انتخاب شده است.',x:27,y:41,w:19,h:15,cx:34,cy:39}
    ]},
    camera:{label:'نمونه انتخاب‌شده از دوربین',before:'assets/a-024.jpg',after:'assets/a-025.jpg',findings:[
      {title:'بی‌نظمی چیدمان دندان‌های پیشین',desc:'در این نمونه، منطقه‌ی مشخص‌شده برای نشان‌دادن تفاوت در راستای ظاهری دندان‌های بالایی طراحی شده است.',x:29,y:37,w:36,h:20,cx:46,cy:35},
      {title:'ازدحام ظاهری دندان‌های پایین',desc:'ناحیه‌ی پایین تصویر به‌صورت نمادین برای نمایش تراکم و هم‌پوشانی احتمالی دندان‌ها مشخص شده است.',x:33,y:58,w:31,h:13,cx:54,cy:67},
      {title:'عدم تقارن نسبی خط لبخند',desc:'این نشانه برای نمایش بررسی بصری تراز و تقارن کلی خط لبخند در مدل آماده استفاده می‌شود.',x:43,y:42,w:18,h:13,cx:63,cy:48}
    ]}
  };
  const $=(s,r=document)=>r.querySelector(s),$$=(s,r=document)=>[...r.querySelectorAll(s)],fa=v=>String(v).replace(/[0-9]/g,d=>'۰۱۲۳۴۵۶۷۸۹'[d]);
  const dlg=$('#smileModal'),body=$('.smile-modal__body',dlg),stages=$$('[data-ssa-stage]',dlg),chosen=$('#ssaChosenImage'),source=$('#ssaChosenSource'),scanImage=$('#ssaScanImage'),beforeImage=$('#ssaBeforeImage'),compareBefore=$('#ssaCompareBefore'),compareAfter=$('#ssaCompareAfter'),zones=$('#ssaZones'),hotspots=$('#ssaHotspots'),findings=$('#ssaFindings'),detail=$('#ssaDetail'),resultStage=$('[data-ssa-stage="result"]',dlg);
  let active=null,activeFinding=0,scanTimer=null,scanFrame=null;
  const stage=name=>{stages.forEach(node=>node.classList.toggle('is-now',node.dataset.ssaStage===name));body.scrollTop=0};
  const close=()=>{clearTimeout(scanTimer);cancelAnimationFrame(scanFrame);dlg.classList.remove('is-on');document.body.classList.remove('is-locked');setTimeout(()=>{if(dlg.open)dlg.close()},420)};
  const open=()=>{clearTimeout(scanTimer);cancelAnimationFrame(scanFrame);stage('choose');if(!dlg.open)dlg.showModal();requestAnimationFrame(()=>dlg.classList.add('is-on'));document.body.classList.add('is-locked')};
  const choose=key=>{active=models[key];chosen.src=active.before;scanImage.src=active.before;source.textContent=active.label;stage('confirm')};
  const selectFinding=index=>{if(!active)return;activeFinding=index;const item=active.findings[index];$$('.ssa-zone',zones).forEach((node,i)=>node.classList.toggle('is-active',i===index));$$('.ssa-hotspot',hotspots).forEach((node,i)=>node.classList.toggle('is-active',i===index));$$('.ssa-finding',findings).forEach((node,i)=>node.classList.toggle('is-active',i===index));setTrustedHTML(detail,`<b></b><p></p>`);detail.firstElementChild.textContent=`${fa(index+1)}. ${item.title}`;detail.lastElementChild.textContent=item.desc};
  const buildResult=()=>{beforeImage.src=active.before;compareBefore.src=active.before;compareAfter.src=active.after;zones.replaceChildren();hotspots.replaceChildren();findings.replaceChildren();active.findings.forEach((item,index)=>{const zone=document.createElement('span');zone.className='ssa-zone';zone.style.cssText=`left:${item.x}%;top:${item.y}%;width:${item.w}%;height:${item.h}%`;zones.appendChild(zone);const spot=document.createElement('button');spot.type='button';spot.className='ssa-hotspot';spot.style.cssText=`left:${item.cx}%;top:${item.cy}%`;spot.textContent=fa(index+1);spot.setAttribute('aria-label',item.title);spot.addEventListener('click',()=>selectFinding(index));hotspots.appendChild(spot);const card=document.createElement('button');card.type='button';card.className='ssa-finding';setTrustedHTML(card,`<span class="ssa-finding__number"></span><span><span class="ssa-finding__title"></span><span class="ssa-finding__desc"></span></span>`);card.querySelector('.ssa-finding__number').textContent=fa(index+1);card.querySelector('.ssa-finding__title').textContent=item.title;card.querySelector('.ssa-finding__desc').textContent=item.desc;card.addEventListener('click',()=>selectFinding(index));findings.appendChild(card)});selectFinding(0)};
  const startScan=()=>{stage('scan');const bar=$('#ssaProgress'),percent=$('#ssaPercent'),title=$('#ssaProcessTitle'),text=$('#ssaProcessText'),steps=[['در حال شناسایی محدوده‌ی لبخند…','اسکن نمایشی خطوط و محدوده‌های اصلی تصویر'],['در حال علامت‌گذاری نواحی قابل توجه…','چیدمان نشانه‌های بصری روی مدل آماده'],['در حال آماده‌سازی گزارش تعاملی…','ساخت مقایسه قبل و بعد و کارت‌های توضیحی']];const started=performance.now(),duration=3400;const animate=now=>{const progress=Math.min((now-started)/duration,1),value=Math.round(progress*100),step=steps[Math.min(steps.length-1,Math.floor(progress*steps.length))];bar.style.width=value+'%';percent.textContent=fa(value)+'٪';title.textContent=step[0];text.textContent=step[1];if(progress<1){scanFrame=requestAnimationFrame(animate)}else{scanTimer=setTimeout(()=>{buildResult();stage('result');resultStage.classList.add('is-revealed')},260)}};bar.style.width='0';resultStage.classList.remove('is-revealed');scanFrame=requestAnimationFrame(animate)};
  $$('[data-ssa-choice]',dlg).forEach(button=>button.addEventListener('click',()=>choose(button.dataset.ssaChoice)));$('#ssaChangeModel').addEventListener('click',()=>stage('choose'));$('#ssaStartScan').addEventListener('click',startScan);$('#ssaRestart').addEventListener('click',()=>stage('choose'));$('#smileClose').addEventListener('click',close);dlg.addEventListener('cancel',event=>{event.preventDefault();close()});dlg.addEventListener('click',event=>{if(event.target===dlg)close()});$('#ssaConsult').addEventListener('click',()=>close());$$('[data-ssa-view]',dlg).forEach(button=>button.addEventListener('click',()=>{const compare=button.dataset.ssaView==='compare';$$('[data-ssa-view]',dlg).forEach(node=>{node.classList.toggle('is-active',node===button);node.setAttribute('aria-selected',String(node===button))});$('#ssaAnalysisPhoto').style.display=compare?'none':'';$('#ssaCompare').classList.toggle('is-active',compare)}));
  document.addEventListener('click',event=>{const trigger=event.target.closest('#smileStart,#smilePreview');if(!trigger)return;event.preventDefault();event.stopImmediatePropagation();open()},true);
  const previewBefore=$('#smilePrevBefore'),previewAfter=$('#smilePrevAfter');if(previewBefore)previewBefore.src=models.gallery.before;if(previewAfter)previewAfter.src=models.gallery.after;
  return{open,close};
})();


/* ============================================================
   GLOBAL DELEGATION — no dead buttons
   ============================================================ */
document.addEventListener('click',e=>{
  const t=e.target.closest('[data-book],[data-service],[data-ai],[data-nav],[data-nav-target]');
  if(!t)return;

  const run=()=>{
    if(t.hasAttribute('data-book')){Sheet.close();Booking.open(t.dataset.book||'');return}
    if(t.hasAttribute('data-service')){Booking.close();Sheet.open(t.dataset.service);return}
    if(t.hasAttribute('data-ai')){Sheet.close();Booking.close();AI.open();return}
    if(t.dataset.nav&&t.dataset.nav.startsWith('#')){Sheet.close();Booking.close();goto(t.dataset.nav);return}
    if(t.hasAttribute('data-nav')&&t.getAttribute('href')){e.preventDefault();goto(t.getAttribute('href'))}
  };
  if(t.tagName==='A')e.preventDefault();
  if(Nav.isOpen){Nav.close();setTimeout(run,420)}else run();
});

/* ============================================================
   MOTION
   ============================================================ */
(function heroScroll(){
  const h=$('#home');
  const wide=()=>matchMedia('(min-width:768px)').matches;
  const reduce=matchMedia('(prefers-reduced-motion:reduce)').matches;
  let raf=null,p=-1;
  function frame(){
    raf=null;const y=scrollY||0;Header.onScroll(y);
    if(reduce||!wide()){h.style.setProperty('--p',0);return}
    const span=Math.max(h.offsetHeight*.92,1);
    const v=Math.min(Math.max(y/span,0),1),eased=v*v*(3-2*v);
    if(Math.abs(eased-p)>.002){p=eased;h.style.setProperty('--p',eased.toFixed(4))}
  }
  const on=()=>{if(!raf)raf=requestAnimationFrame(frame)};
  addEventListener('scroll',on,{passive:true});addEventListener('resize',on);frame();
})();

const io=new IntersectionObserver(es=>{es.forEach(en=>{
  if(en.isIntersecting){en.target.classList.add('is-in');io.unobserve(en.target)}})},
  {rootMargin:'-6% 0px -10%',threshold:.01});
$$('.reveal').forEach(n=>io.observe(n));

/* ---------- boot ---------- */
mountAsset($('#heroImg'),$('#heroFigure'),'ASSET_01_DOCTOR_HERO');
mountAsset($('#docImg'),$('#docFigure'),'ASSET_01_DOCTOR_HERO');
mountAsset($('#brandImg'),$('#brand'),'ASSET_02_LOGO');
mountAsset($('#ftBrandImg'),$('#ftBrand'),'ASSET_02_LOGO');
mountAsset($('#bkBarImg'),$('#bkBar'),'ASSET_02_LOGO');
requestAnimationFrame(()=>$$('.hero .reveal').forEach(n=>n.classList.add('is-in')));
