/*
 * Trusted HTML sanitizer v1.0.0
 * Policy: HTML/SVG allow-list; strips scripts, event-handler attributes,
 * dangerous URLs, and unknown elements/attributes. Used only for trusted
 * templates that interpolate static clinic content. User-entered text is
 * inserted with textContent instead.
 */
(() => {
  const ALLOWED = new Set([
    'A','B','BR','BUTTON','CANVAS','CIRCLE','DIV','FIGURE','H1','H2','H3','HEADER','I','IMG','LI','P','PATH','SPAN','SMALL','STRONG','SVG','UL','OL'
  ]);
  const ATTR = new Set([
    'class','id','href','src','alt','title','type','name','value','width','height','loading','decoding','referrerpolicy',
    'aria-hidden','aria-label','aria-expanded','aria-selected','aria-pressed','aria-disabled','role','tabindex','disabled','hidden','x','y','xmlns','data-book','data-service','data-ai','data-nav','data-nav-target','data-svc','data-t','data-ssa-choice','data-ssa-view','style','viewBox','fill','stroke','stroke-width','stroke-linecap','stroke-linejoin','rx','ry','r','cx','cy','d','focusable'
  ]);
  const URL_ATTRS = new Set(['href','src']);
  const SAFE_URL = /^(?:https?:|mailto:|tel:|\/|\.?\.?\/|#|data:image\/(?:png|jpe?g|gif|webp);base64,)/i;

  function clean(root) {
    const walk = node => {
      [...node.children].forEach(child => {
        if (!ALLOWED.has(child.tagName)) { child.remove(); return; }
        [...child.attributes].forEach(attr => {
          const name=attr.name;
          if (name.startsWith('on') || (!(ATTR.has(name)) && !name.startsWith('data-') && !name.startsWith('aria-'))) { child.removeAttribute(name); return; }
          if (URL_ATTRS.has(name) && !SAFE_URL.test(attr.value.trim())) child.removeAttribute(name);
        });
        walk(child);
      });
    };
    walk(root);
    return root;
  }

  window.setTrustedHTML = (target, html) => {
    const template=document.createElement('template');
    template.innerHTML=String(html);
    const frag=clean(template.content);
    target.replaceChildren(...frag.childNodes);
  };
})();
