/* Static asset registry extracted from the original prototype. No network calls. */
window.AK_ASSETS = {
  BASE: '',   /* '' = same folder · or 'assets/' · or a full CDN URL · (assets embedded as data URIs below for standalone-file portability) */

  ASSET_01_DOCTOR_HERO:{
    role:'Hero + Doctor section. Two-person brand asset. Dr. Arash Kouhfar is the man on the RIGHT.',
    file:'assets/a-001.png', alt:'دکتر آرش کوهفر همراه با تیم کلینیک',
    fallbacks:['1000135741.png','assets/asset-01-doctor-hero.png','images/asset-01-doctor-hero.png']
  },
  ASSET_02_LOGO:{
    role:'Header · Nav overlay · Footer · Booking confirmation',
    file:'assets/a-002.png', alt:'لوگو دکتر آرش کوهفر',
    fallbacks:['1000135905.png','assets/asset-02-logo.png','images/asset-02-logo.png']
  },
  ASSET_03_CASE01_BEFORE:{
    role:'Results — case 01 BEFORE (لمینت سرامیکی / اصلاح طرح لبخند)',
    file:'assets/a-003.jpg', alt:'وضعیت دندان‌ها پیش از درمان — کیس ۰۱',
    fallbacks:['1000135901.jpg','assets/asset-03-case01-before.jpg']
  },
  ASSET_04_CASE01_AFTER:{
    role:'Results — case 01 AFTER (لمینت سرامیکی / اصلاح طرح لبخند)',
    file:'assets/a-004.jpg', alt:'نتیجه درمان — کیس ۰۱',
    fallbacks:['1000135898.jpg','assets/asset-04-case01-after.jpg']
  }
  /* New pairs: add ASSET_05_CASE02_BEFORE / ASSET_06_CASE02_AFTER here,
     then reference the keys in AK_DATA.cases below. Nothing else changes. */
};
