import { initBookingWizard } from './booking/wizard.js';

/* Task 05 Laravel integration: preserve the existing modular browser runtime order. */

/* ---- legacy/ak-assets.js ---- */
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


/* ---- legacy/ak-data.js ---- */
/* Static site content extracted from the original prototype. No network calls. */
window.AK_DATA = {

  contact:{
    address:'شهرری، خیابان ۲۴ متری، نبش خیابان نفری، ساختمان عقیق، طبقه ۲، واحد ۸',
    rows:[
      {k:'نشانی', v:'شهرری، خیابان ۲۴ متری، نبش خیابان نفری، ساختمان عقیق، طبقه ۲، واحد ۸'},
      {k:'تلفن', v:'<a href="tel:+982155953553"><bdi dir="ltr">۰۲۱-۵۵۹۵۳۵۵۳</bdi></a>'},
      {k:'واتس‌اپ', v:'<a href="https://wa.me/989194464951" target="_blank" rel="noopener noreferrer"><bdi dir="ltr">۰۹۱۹۴۴۶۴۹۵۱</bdi></a>'},
      {k:'اینستاگرام', v:'<a href="https://www.instagram.com/dr.arash.kouhfar?stkn=OHRqcXVmNzl0ODJq" target="_blank" rel="noopener noreferrer">کلیک کنید</a>'},
      {k:'ساعات کاری', v:'شنبه تا پنجشنبه (از ساعت ۱۲ ظهر تا ۸ شب)'}
    ]
  },

  /* ---- services: edit freely, everything derives from this ---- */
  services:[
    {
      id:'implant', title:'ایمپلنت دیجیتال', en:'ایمپلنت دیجیتال', featured:true,
      intro:'جایگزینی دندان از دست‌رفته با رویکرد دیجیتال، از تصویربرداری و طراحی تا جای‌گذاری و ساخت روکش نهایی.',
      what:'در ایمپلنت دیجیتال، موقعیت و زاویه ایمپلنت پیش از عمل بر اساس داده‌های تصویربرداری طراحی می‌شود و مسیر درمان از ابتدا مشخص است. این طراحی به هماهنگی بیشتر میان مرحله جراحی و مرحله ساخت روکش کمک می‌کند.',
      benefits:['طراحی مسیر درمان پیش از شروع کار','هماهنگی بیشتر بین مرحله جراحی و روکش نهایی','توجه به فرم و رنگ دندان مجاور در ساخت روکش','مراحل و زمان‌بندی مشخص برای بیمار'],
      steps:[
        {t:'بررسی و تصویربرداری', d:'ارزیابی وضعیت دهان و تهیه تصاویر لازم.'},
        {t:'طراحی دیجیتال', d:'تعیین موقعیت ایمپلنت و طرح درمان.'},
        {t:'جای‌گذاری ایمپلنت', d:'انجام مرحله جراحی بر اساس طرح تعیین‌شده.'},
        {t:'دوره ترمیم', d:'فاصله زمانی لازم تا آماده شدن برای مرحله بعد.'},
        {t:'ساخت و نصب روکش', d:'ساخت روکش متناسب با دندان‌های مجاور و نصب نهایی.'}
      ],
      who:['افرادی که یک یا چند دندان را از دست داده‌اند','افرادی که به دنبال جایگزین ثابت به‌جای پروتز متحرک هستند','مواردی که پس از بررسی، شرایط لازم برای ایمپلنت تأیید شود'],
      faq:[
        {q:'مراحل درمان چقدر طول می‌کشد؟', a:'مدت درمان به شرایط هر فرد بستگی دارد و پس از بررسی اولیه، زمان‌بندی تقریبی اعلام می‌شود.'},
        {q:'پیش از ایمپلنت چه بررسی‌هایی لازم است؟', a:'بررسی وضعیت دهان و تصویربرداری لازم انجام می‌شود تا مناسب بودن شرایط مشخص شود.'}
      ]
    },
    {
      id:'aesthetic', title:'زیبایی', en:'زیبایی لبخند',
      subs:['کامپوزیت','لمینت سرامیکی','اصلاح طرح لبخند','اصلاح لبخند لثه‌ای'],
      intro:'اصلاح فرم، رنگ و تناسب دندان‌ها با هدف رسیدن به لبخندی هماهنگ با چهره.',
      what:'در درمان‌های زیبایی، ابتدا وضعیت فعلی لبخند بررسی و طرح پیشنهادی مشخص می‌شود. بر اساس شرایط دندان‌ها و خواسته بیمار، یکی از روش‌ها مانند کامپوزیت، لمینت سرامیکی یا اصلاح طرح لبخند انتخاب می‌شود.',
      benefits:['هماهنگی فرم و رنگ دندان‌ها با چهره','امکان بررسی طرح پیشنهادی پیش از اجرا','انتخاب روش بر اساس شرایط دندان‌ها','توجه به تناسب لبخند و خط لثه'],
      steps:[
        {t:'بررسی و مشاوره', d:'ارزیابی وضعیت دندان‌ها و گفت‌وگو درباره نتیجه مورد نظر.'},
        {t:'طراحی طرح لبخند', d:'مشخص کردن فرم، تناسب و رنگ پیشنهادی.'},
        {t:'آماده‌سازی', d:'انجام مراحل لازم بر اساس روش انتخابی.'},
        {t:'اجرا و اصلاح نهایی', d:'اجرای درمان و اصلاح جزئیات تا رسیدن به نتیجه هماهنگ.'}
      ],
      who:['تغییر رنگ یا فرم دندان‌های جلو','فاصله یا نامرتبی محدود بین دندان‌ها','نارضایتی از تناسب لبخند یا نمایان بودن لثه'],
      faq:[
        {q:'کامپوزیت یا لمینت سرامیکی؟', a:'انتخاب روش به وضعیت دندان‌ها، میزان اصلاح مورد نیاز و نتیجه مورد انتظار بستگی دارد و در مشاوره مشخص می‌شود.'},
        {q:'نتیجه پیش از اجرا قابل بررسی است؟', a:'در بسیاری از موارد، طرح پیشنهادی پیش از اجرا با بیمار بررسی می‌شود.'}
      ]
    },
    {
      id:'prostho', title:'پروتز و روکش', en:'پروتز دندان',
      subs:['Crown','Inlay','Onlay','ترمیم‌های سرامیکی به روش بایومیمتیک'],
      intro:'بازسازی دندان‌های آسیب‌دیده با روکش و ترمیم‌های سرامیکی، با تمرکز بر حفظ بافت سالم.',
      what:'در این گروه از درمان‌ها، بخش آسیب‌دیده دندان با ترمیم یا روکش سرامیکی بازسازی می‌شود. در رویکرد بایومیمتیک تلاش می‌شود بافت سالم دندان تا حد امکان حفظ شود و ترمیم از نظر فرم و رنگ به ساختار طبیعی نزدیک بماند.',
      benefits:['حفظ بافت سالم دندان تا حد امکان','بازگشت فرم و عملکرد دندان','هماهنگی رنگ ترمیم با دندان‌های مجاور','انتخاب نوع ترمیم بر اساس میزان تخریب'],
      steps:[
        {t:'بررسی دندان', d:'تعیین میزان تخریب و نوع ترمیم مناسب.'},
        {t:'آماده‌سازی', d:'آماده‌سازی دندان برای ترمیم یا روکش.'},
        {t:'ساخت ترمیم', d:'ساخت قطعه سرامیکی متناسب با دندان.'},
        {t:'نصب و تنظیم نهایی', d:'قرار دادن ترمیم و تنظیم تماس‌های دندانی.'}
      ],
      who:['دندان‌های ترمیم‌شده با تخریب گسترده','دندان‌های شکسته یا ساییده','مواردی که ترمیم معمولی کافی نباشد'],
      faq:[
        {q:'تفاوت Inlay و Onlay چیست؟', a:'این دو در گستره سطحی که پوشش می‌دهند تفاوت دارند و انتخاب آن‌ها به میزان تخریب دندان بستگی دارد.'},
        {q:'چند جلسه لازم است؟', a:'تعداد جلسات به نوع ترمیم و شرایط دندان بستگی دارد و در جلسه بررسی اعلام می‌شود.'}
      ]
    },
    {
      id:'bleaching', title:'بلیچینگ', en:'سفید کردن دندان', subs:['سفید کردن دندان'],
      intro:'روشن‌تر کردن رنگ دندان‌ها با روش‌های کنترل‌شده و پس از بررسی وضعیت دهان.',
      what:'پیش از بلیچینگ، وضعیت دندان‌ها و لثه بررسی می‌شود. سپس روش مناسب و تعداد جلسات لازم مشخص می‌گردد. نتیجه بین افراد متفاوت است و به رنگ زمینه دندان بستگی دارد.',
      benefits:['روشن‌تر شدن رنگ دندان‌ها','انجام پس از بررسی وضعیت دهان','امکان انتخاب روش مطب یا خانگی','دستورالعمل مراقبت پس از درمان'],
      steps:[
        {t:'بررسی اولیه', d:'ارزیابی وضعیت دندان‌ها و لثه.'},
        {t:'آماده‌سازی', d:'انجام اقدامات لازم پیش از بلیچینگ.'},
        {t:'انجام بلیچینگ', d:'اجرای درمان در یک یا چند جلسه.'},
        {t:'مراقبت پس از درمان', d:'ارائه توصیه‌های لازم برای حفظ نتیجه.'}
      ],
      who:['تیرگی عمومی رنگ دندان‌ها','افرادی که پیش از درمان زیبایی به دنبال هماهنگی رنگ هستند'],
      faq:[
        {q:'نتیجه چقدر پایدار است؟', a:'پایداری نتیجه به عادات غذایی و بهداشت دهان بستگی دارد و توصیه‌های مراقبتی ارائه می‌شود.'},
        {q:'برای همه مناسب است؟', a:'مناسب بودن بلیچینگ پس از بررسی وضعیت دندان‌ها و لثه مشخص می‌شود.'}
      ]
    },
    {
      id:'surgery', title:'جراحی', en:'جراحی دندان', subs:['جراحی دندان ساده','جراحی دندان نهفته'],
      intro:'خارج کردن دندان در شرایطی که درمان نگهدارنده امکان‌پذیر نیست، با برنامه‌ریزی پیش از عمل.',
      what:'بر اساس بررسی و تصویربرداری، نوع جراحی (ساده یا نهفته) و مراحل آن مشخص می‌شود. توصیه‌های پیش و پس از جراحی به بیمار ارائه می‌گردد.',
      benefits:['برنامه‌ریزی پیش از انجام جراحی','توضیح مراحل و شرایط برای بیمار','دستورالعمل مراقبت پس از جراحی','هماهنگی با مراحل بعدی درمان در صورت نیاز'],
      steps:[
        {t:'بررسی و تصویربرداری', d:'ارزیابی موقعیت دندان و شرایط اطراف آن.'},
        {t:'برنامه‌ریزی جراحی', d:'تعیین روش و مراحل کار.'},
        {t:'انجام جراحی', d:'اجرای جراحی بر اساس برنامه تعیین‌شده.'},
        {t:'پیگیری', d:'بررسی روند بهبود و ارائه توصیه‌های لازم.'}
      ],
      who:['دندان‌هایی که قابل نگهداری نیستند','دندان‌های نهفته که نیاز به بررسی دارند'],
      faq:[
        {q:'پس از جراحی چه مراقبتی لازم است؟', a:'دستورالعمل مراقبت پس از جراحی به‌صورت اختصاصی برای هر بیمار ارائه می‌شود.'},
        {q:'پیش از جراحی چه بررسی‌هایی انجام می‌شود؟', a:'بررسی وضعیت دهان و تصویربرداری لازم برای برنامه‌ریزی انجام می‌گیرد.'}
      ]
    },
    {
      id:'endo', title:'درمان ریشه', en:'درمان ریشه', subs:['عصب‌کشی','درمان مجدد'],
      intro:'درمان ریشه با هدف حفظ دندان در مواردی که بافت داخلی دندان درگیر شده است.',
      what:'در درمان ریشه، فضای داخلی دندان پاک‌سازی و آماده‌سازی می‌شود و سپس مسدود می‌گردد. در مواردی که درمان قبلی نتیجه لازم را نداده باشد، درمان مجدد بررسی می‌شود.',
      benefits:['امکان حفظ دندان به‌جای خارج کردن آن','پاک‌سازی و آماده‌سازی فضای داخلی دندان','بررسی امکان درمان مجدد در موارد لازم','هماهنگی با مرحله ترمیم یا روکش بعدی'],
      steps:[
        {t:'بررسی و تشخیص', d:'ارزیابی وضعیت دندان و تعیین نیاز به درمان ریشه.'},
        {t:'آماده‌سازی', d:'پاک‌سازی و شکل‌دهی فضای داخلی دندان.'},
        {t:'پرکردن ریشه', d:'مسدود کردن فضای آماده‌شده.'},
        {t:'ترمیم نهایی', d:'ترمیم یا روکش دندان بر اساس شرایط.'}
      ],
      who:['درد یا حساسیت مداوم دندان','دندان‌هایی که بافت داخلی آن‌ها درگیر شده','دندان‌هایی با درمان ریشه ناموفق پیشین'],
      faq:[
        {q:'پس از درمان ریشه، دندان نیاز به روکش دارد؟', a:'در بسیاری از موارد ترمیم یا روکش پس از درمان ریشه توصیه می‌شود که بر اساس شرایط دندان تعیین می‌گردد.'},
        {q:'درمان مجدد در چه شرایطی انجام می‌شود؟', a:'در مواردی که درمان پیشین نتیجه مورد انتظار را نداشته باشد، پس از بررسی درباره درمان مجدد تصمیم‌گیری می‌شود.'}
      ]
    },
    {
      id:'restorative', title:'ترمیمی', en:'ترمیم دندان',
      intro:'ترمیم پوسیدگی و آسیب‌های محدود دندان با حفظ ساختار طبیعی.',
      what:'در درمان‌های ترمیمی، بخش آسیب‌دیده دندان پاک‌سازی و با مواد ترمیمی بازسازی می‌شود. هدف، بازگشت فرم و عملکرد دندان و هماهنگی ظاهری با دندان‌های مجاور است.',
      benefits:['بازسازی فرم و عملکرد دندان','هماهنگی رنگ ترمیم با دندان طبیعی','حفظ ساختار سالم دندان','جلوگیری از گسترش آسیب'],
      steps:[
        {t:'بررسی دندان', d:'تعیین محل و گستره آسیب.'},
        {t:'پاک‌سازی', d:'برداشتن بخش آسیب‌دیده.'},
        {t:'ترمیم', d:'بازسازی دندان با مواد ترمیمی.'},
        {t:'پرداخت نهایی', d:'تنظیم فرم و سطح ترمیم.'}
      ],
      who:['پوسیدگی‌های محدود','ترمیم‌های قدیمی که نیاز به تعویض دارند','آسیب‌های سطحی دندان'],
      faq:[
        {q:'ترمیم در یک جلسه انجام می‌شود؟', a:'بسیاری از ترمیم‌های محدود در یک جلسه انجام می‌شوند؛ موارد گسترده‌تر ممکن است به جلسات بیشتری نیاز داشته باشد.'},
        {q:'رنگ ترمیم با دندان هماهنگ می‌شود؟', a:'در ترمیم‌های هم‌رنگ دندان، رنگ متناسب با دندان‌های مجاور انتخاب می‌شود.'}
      ]
    }
  ],

  /* ---- results cases: add pairs here, images resolve from AK_ASSETS ---- */
  cases:[
    {id:'c1', cat:'لمینت سرامیکی / اصلاح طرح لبخند', label:'نمونه ۰۱', service:'aesthetic',
     before:'ASSET_03_CASE01_BEFORE', after:'ASSET_04_CASE01_AFTER',
     note:'تصاویر واقعی ارائه‌شده توسط کلینیک. هر جفت تصویر تنها به همان کیس تعلق دارد و با کیس‌های دیگر ترکیب نمی‌شود.'}
  ],
  caseSlots:[
    {t:'طراحی لبخند', s:'جایگاه کیس آینده'},
    {t:'ایمپلنت دیجیتال', s:'جایگاه کیس آینده'},
    {t:'ترمیم دندان', s:'جایگاه کیس آینده'}
  ],

  faq:[
    {q:'چگونه برای درمان مناسب تصمیم بگیرم؟', a:'انتخاب روش درمان به وضعیت دندان‌ها و هدف شما بستگی دارد. در جلسه بررسی، گزینه‌های موجود و تفاوت آن‌ها توضیح داده می‌شود تا تصمیم‌گیری آگاهانه باشد.'},
    {q:'آیا قبل از درمان نیاز به مشاوره دارم؟', a:'بله. بررسی اولیه برای مشخص شدن شرایط دهان و تعیین طرح درمان لازم است. بدون این بررسی، اعلام دقیق مراحل و زمان‌بندی امکان‌پذیر نیست.'},
    {q:'چگونه نوبت رزرو کنم؟', a:'از دکمه «رزرو نوبت» در همین صفحه استفاده کنید: خدمت، تاریخ و ساعت را انتخاب کنید و شماره تماس خود را وارد نمایید. سپس برای تأیید نهایی با شما تماس گرفته می‌شود.'},
    {q:'آیا امکان رزرو آنلاین وجود دارد؟', a:'بله. خدمت، تاریخ و ساعت قابل رزرو را انتخاب کنید و اطلاعات تماس خود را وارد نمایید. درخواست شما پس از ثبت در سامانه، برای تأیید نهایی توسط کلینیک بررسی می‌شود.'},
    {q:'برای شروع درمان چه اطلاعاتی لازم است؟', a:'نام، شماره تماس و خدمت مورد نظر برای رزرو کافی است. در صورت داشتن تصویربرداری یا سابقه درمان پیشین، همراه داشتن آن به بررسی کمک می‌کند.'}
  ],

  /* ---- AI assistant script: edit answers freely ---- */
  ai:{
    open:'سلام، من دستیار هوشمند کلینیک دکتر کوهفر هستم. چطور می‌توانم راهنمایی‌تان کنم؟',
    quick:['درباره ایمپلنت سؤال دارم','درباره لمینت سؤال دارم','هزینه خدمات چقدر است؟','برای طراحی لبخند مناسب هستم؟','می‌خواهم نوبت بگیرم','سؤال دیگری دارم'],
    intents:[
      {id:'implant', keys:['ایمپلنت','implant','دندان کشیده','دندان از دست'],
       a:'در <b>ایمپلنت دیجیتال</b>، موقعیت ایمپلنت پیش از عمل بر اساس تصویربرداری طراحی می‌شود، بنابراین مسیر درمان از ابتدا مشخص است. مراحل کلی: بررسی و تصویربرداری، طراحی دیجیتال، جای‌گذاری، دوره ترمیم و ساخت روکش نهایی.',
       act:[{t:'جزئیات ایمپلنت دیجیتال',s:'implant'},{t:'رزرو نوبت',b:'ایمپلنت دیجیتال'}]},
      {id:'veneer', keys:['لمینت','laminate','veneer','سرامیک'],
       a:'<b>لمینت سرامیکی</b> برای اصلاح فرم، رنگ و تناسب دندان‌های جلو استفاده می‌شود. پیش از اجرا، طرح پیشنهادی بررسی می‌شود تا نتیجه با چهره شما هماهنگ باشد. انتخاب بین لمینت و کامپوزیت به وضعیت دندان‌ها بستگی دارد.',
       act:[{t:'جزئیات خدمات زیبایی',s:'aesthetic'},{t:'رزرو نوبت',b:'لمینت سرامیکی'}]},
      {id:'price', keys:['هزینه','قیمت','چقدر','تومان','price'],
       a:'هزینه هر درمان به شرایط دهان، تعداد دندان‌های درگیر و روش انتخابی بستگی دارد، بنابراین بدون بررسی نمی‌توان عدد دقیقی اعلام کرد. پس از جلسه بررسی، برآورد هزینه و مراحل به‌صورت شفاف ارائه می‌شود.',
       act:[{t:'رزرو جلسه بررسی',b:'مشاوره و بررسی اولیه'},{t:'مشاهده خدمات',n:'#services'}]},
      {id:'smile', keys:['طراحی لبخند','طرح لبخند','smile','زیبایی','مناسب هستم'],
       a:'برای پاسخ دقیق باید وضعیت دندان‌ها، خط لثه و تناسب لبخند بررسی شود. اگر از رنگ، فرم یا فاصله دندان‌های جلو راضی نیستید، <b>اصلاح طرح لبخند</b> معمولاً یکی از گزینه‌های قابل بررسی است. تصمیم نهایی در جلسه مشاوره گرفته می‌شود.',
       act:[{t:'جزئیات خدمات زیبایی',s:'aesthetic'},{t:'رزرو مشاوره',b:'زیبایی و طرح لبخند'}]},
      {id:'book', keys:['نوبت','رزرو','وقت','appointment','وقت بگیرم'],
       a:'حتماً. فرم رزرو را باز می‌کنم: خدمت، تاریخ و ساعت را انتخاب کنید و شماره تماس بگذارید. تأیید نهایی از طرف کلینیک انجام می‌شود.',
       act:[{t:'باز کردن فرم رزرو',b:''}]},
      {id:'address', keys:['آدرس','کجا','نشانی','مسیر','location'],
       a:'نشانی کلینیک: شهرری، خیابان ۲۴ متری، نبش خیابان نفری، ساختمان عقیق، طبقه ۲، واحد ۸. شماره تماس و ساعات کاری به‌زودی در بخش تماس تکمیل می‌شود.',
       act:[{t:'بخش تماس با ما',n:'#contact'},{t:'رزرو نوبت',b:''}]},
      {id:'doctor', keys:['دکتر','کوهفر','سابقه','تخصص','آشنایی'],
       a:'<b>دکتر آرش کوهفر</b> با تمرکز بر دندانپزشکی دیجیتال کار می‌کند: طرح درمان پیش از شروع مشخص می‌شود و هدف، نتیجه‌ای هماهنگ با چهره و طبیعی در گذر زمان است. سوابق و مدارک پس از دریافت از کلینیک در بخش «درباره دکتر» تکمیل می‌شود.',
       act:[{t:'بخش درباره دکتر',n:'#doctor'},{t:'رزرو نوبت',b:''}]},
      {id:'pain', keys:['درد','حساسیت','عصب','ریشه'],
       a:'درد یا حساسیت مداوم می‌تواند دلایل مختلفی داشته باشد و بررسی حضوری لازم است. در این نمونه اولیه امکان ارائه توصیه درمانی وجود ندارد؛ پیشنهاد می‌کنم یک جلسه بررسی رزرو کنید.',
       act:[{t:'جزئیات درمان ریشه',s:'endo'},{t:'رزرو جلسه بررسی',b:'مشاوره و بررسی اولیه'}]},
      {id:'results', keys:['نتایج','نمونه','قبل و بعد','before'],
       a:'در بخش <b>نتایج درمان</b> می‌توانید تصاویر پیش و پس از درمان را با کشیدن دستگیره مقایسه کنید. کیس‌های بیشتری به‌تدریج اضافه می‌شود.',
       act:[{t:'مشاهده نتایج درمان',n:'#results'},{t:'رزرو نوبت',b:''}]}
    ],
    fallback:{
      a:'سؤال شما را ثبت کردم. برای پاسخ دقیق‌تر، یکی از موضوع‌های زیر را انتخاب کنید یا یک جلسه بررسی رزرو کنید تا همکاران کلینیک راهنمایی کاملی ارائه دهند.',
      act:[{t:'مشاهده خدمات',n:'#services'},{t:'رزرو نوبت',b:''}]
    }
  }
};


/* ---- legacy/sanitizer.js ---- */
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
        if (!ALLOWED.has(String(child.tagName).toUpperCase())) { child.remove(); return; }
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


/* ---- legacy/01-inline.js ---- */

(function () {
    // Design-ui pages are single compositions, so the whole body is editable.
    function markEditable() {
        document.body.classList.add('artifact-editable');
    }

    function init() {
        markEditable();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();



/* ---- legacy/03-inline.js ---- */

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

/* Booking is now API-backed; preserves the existing modal, selectors, and step markup. */
const Booking = initBookingWizard({ toast: Toast, goto });

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


/* ---- legacy/04-hero-rebuild-script.js ---- */

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


/* ---- legacy/05-modern-topnav-script.js ---- */

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


/* ---- legacy/06-implant-3d-script.js ---- */

(() => {
  const renderDentalImplant = async (canvas) => {
    if (!canvas || canvas.dataset.ready) return;
    canvas.dataset.ready = '1';
    canvas.style.background='center / contain no-repeat url(assets/a-026.png)';
    try {
      const THREE = await import(/* @vite-ignore */ '/assets/a-027.js');
      const renderer = new THREE.WebGLRenderer({canvas,alpha:true,antialias:true});
      renderer.setPixelRatio(Math.min(devicePixelRatio || 1, 2.5));
      renderer.setClearColor(0x000000,0);
      renderer.outputColorSpace = THREE.SRGBColorSpace;
      renderer.toneMapping = THREE.ACESFilmicToneMapping;
      renderer.toneMappingExposure = 1.12;
      const scene = new THREE.Scene();
      const camera = new THREE.PerspectiveCamera(30,1,.1,60);
      camera.position.set(0,.65,10.1);
      camera.lookAt(0,-.12,0);
      const root = new THREE.Group();
      root.rotation.set(0,-.18,-.025);
      scene.add(root);

      // Embedded photographic studio lighting: broad softboxes reflected by titanium.
      const ew=512,eh=256,envPixels=new Float32Array(ew*eh*4);
      const boxes=[[.20,.46,.065,.24,4.8],[.73,.43,.12,.29,3.1],[.46,.2,.23,.065,2.8]];
      for(let y=0;y<eh;y++) for(let x=0;x<ew;x++){
        const u=x/ew,v=y/eh;let value=.12+.2*(1-v);
        for(const [cx,cy,wx,wy,power] of boxes){
          const dx=Math.min(Math.abs(u-cx),1-Math.abs(u-cx))/wx,dy=Math.abs(v-cy)/wy;
          value+=power*Math.exp(-Math.pow(dx,6)-Math.pow(dy,6));
        }
        const i=(y*ew+x)*4;envPixels[i]=value;envPixels[i+1]=value;envPixels[i+2]=value*1.025;envPixels[i+3]=1;
      }
      const envTexture=new THREE.DataTexture(envPixels,ew,eh,THREE.RGBAFormat,THREE.FloatType);
      envTexture.mapping=THREE.EquirectangularReflectionMapping;envTexture.needsUpdate=true;
      const pmrem=new THREE.PMREMGenerator(renderer);
      const environment=pmrem.fromEquirectangular(envTexture);
      scene.environment=environment.texture;envTexture.dispose();pmrem.dispose();
      const ceramic=new THREE.MeshPhysicalMaterial({color:0xf3f2ed,roughness:.245,metalness:0,clearcoat:.45,clearcoatRoughness:.18,ior:1.46,envMapIntensity:.8});
      const titanium=new THREE.MeshStandardMaterial({color:0xaeb1b4,roughness:.24,metalness:1,envMapIntensity:1.15});
      const polished=new THREE.MeshStandardMaterial({color:0xbec0c2,roughness:.16,metalness:1,envMapIntensity:1.2});

      // A continuous sculpted crown. No extruded outline, boxes, or attached lobes.
      const profile=new THREE.CatmullRomCurve3([
        [0,.075],[.35,.075],[.60,.10],[.73,.22],[.82,.48],[.88,.88],
        [.90,1.28],[.87,1.60],[.76,1.82],[.54,1.88],[.28,1.84],[0,1.82]
      ].map(([r,y])=>new THREE.Vector3(r,y,0)),false,'centripetal');
      const radial=128,rings=112,positions=[],indices=[];
      for(let j=0;j<=rings;j++){
        const p=profile.getPoint(j/rings);
        for(let i=0;i<=radial;i++){
          const a=2*Math.PI*i/radial,co=Math.cos(a),si=Math.sin(a);
          let x=p.x*Math.sign(co)*Math.pow(Math.abs(co),.86);
          let z=p.x*.79*Math.sign(si)*Math.pow(Math.abs(si),.86);
          let y=p.y;
          const top=THREE.MathUtils.smoothstep(y,1.48,1.84);
          let cusps=0;
          for(const sx of [-1,1])for(const sz of [-1,1])
            cusps+=Math.exp(-((x-sx*.46)**2/.085+(z-sz*.32)**2/.065))*(sz>0?.12:.095);
          y+=top*(cusps-.042*Math.exp(-x*x/.022));
          const groove=.040*Math.exp(-x*x/.022)*Math.exp(-(((y-1.19)/.66)**4));
          z-=Math.sign(z)*groove*Math.pow(Math.abs(si),8);
          positions.push(x,y,z);
          if(j<rings && i<radial){const a0=j*(radial+1)+i,b=a0+radial+1;indices.push(a0,b,a0+1,b,b+1,a0+1);}
        }
      }
      const crownGeometry=new THREE.BufferGeometry();
      crownGeometry.setAttribute('position',new THREE.Float32BufferAttribute(positions,3));
      crownGeometry.setIndex(indices);crownGeometry.computeVertexNormals();
      root.add(new THREE.Mesh(crownGeometry,ceramic));
      const lathe=(points,material)=>{
        const mesh=new THREE.Mesh(new THREE.LatheGeometry(points.map(([r,y])=>new THREE.Vector2(r,y)),128),material);
        root.add(mesh);return mesh;
      };
      lathe([[0,-.27],[.40,-.27],[.46,-.24],[.49,-.20],[.49,-.045],[.48,.10],[0,.10]],polished);
      lathe([[0,-.40],[.39,-.40],[.45,-.37],[.46,-.33],[.46,-.29],[.42,-.27],[0,-.27]],titanium);
      lathe([[0,-2.16],[.09,-2.15],[.19,-2.10],[.27,-2.03],[.32,-1.94],[.365,-1.7],[.40,-1.2],[.42,-.45],[.41,-.37],[0,-.37]],titanium);
      // Machined helical flank: a real tapered screw surface, not a coiled tube.
      const tp=[],ti=[],steps=520,sectors=128;
      for(let j=0;j<=steps;j++){
        const t=j/steps,y=-.40-1.60*t,core=.42-.10*t;
        const fade=THREE.MathUtils.smoothstep(t,0,.035)*(1-THREE.MathUtils.smoothstep(t,.90,1));
        for(let i=0;i<=sectors;i++){
          const a=i/sectors*Math.PI*2;
          const phase=((t*9.4-i/sectors)%1+1)%1;
          const ridge=Math.pow(Math.max(0,1-Math.abs(phase-.5)/.42),.78);
          const r=core+.075*ridge*fade;
          tp.push(r*Math.cos(a),y,r*Math.sin(a));
          if(j<steps && i<sectors){const q=j*(sectors+1)+i,b=q+sectors+1;ti.push(q,q+1,b,b,q+1,b+1);}
        }
      }
      const screwGeometry=new THREE.BufferGeometry();
      screwGeometry.setAttribute('position',new THREE.Float32BufferAttribute(tp,3));
      screwGeometry.setIndex(ti);screwGeometry.computeVertexNormals();
      root.add(new THREE.Mesh(screwGeometry,titanium));
      scene.add(new THREE.HemisphereLight(0xffffff,0x6b7480,1.05));
      const key=new THREE.DirectionalLight(0xffffff,2.4);key.position.set(-3,4,5);scene.add(key);
      const fill=new THREE.DirectionalLight(0xf2f6ff,.75);fill.position.set(4,2,2);scene.add(fill);
      const rim=new THREE.DirectionalLight(0xffffff,1.4);rim.position.set(2,3,-4);scene.add(rim);
      const resize=()=>{
        const box=canvas.getBoundingClientRect();
        renderer.setSize(Math.max(1,Math.round(box.width)),Math.max(1,Math.round(box.height)),false);
        camera.aspect=Math.max(1,box.width)/Math.max(1,box.height);camera.updateProjectionMatrix();
      };
      resize();new ResizeObserver(resize).observe(canvas);
      const reduce=matchMedia('(prefers-reduced-motion: reduce)');
      let visible=true,last=0,angle=-.18;
      new IntersectionObserver(entries=>{visible=entries[0].isIntersecting;},{rootMargin:'80px'}).observe(canvas);
      const animate=time=>{
        const dt=last?Math.min((time-last)/1000,.1):0;last=time;
        if(visible&&!document.hidden){
          if(!reduce.matches)angle+=dt*.34;
          root.rotation.y=angle;
          renderer.render(scene,camera);
        }
        requestAnimationFrame(animate);
      };
      requestAnimationFrame(animate);
      renderer.render(scene,camera);
      canvas.style.background='transparent';
      canvas.dataset.ready='rendered';
    }catch(error){
      canvas.dataset.ready='error';
      console.warn('Implant renderer could not start',error);
      canvas.closest('.implant-visual')?.classList.add('implant-visual--fallback');
    }
  };

  const mountImplant = () => {
    const card = document.querySelector('.svc__lead[data-service="implant"]');
    if (!card || card.querySelector('.implant-visual')) return;
    card.classList.add('has-implant-3d');
    const visual = document.createElement('span');
    visual.className = 'implant-visual';
    visual.setAttribute('aria-hidden', 'true');
    setTrustedHTML(visual,`
      <span class="implant-visual__aura"></span>
      <span class="implant-visual__orbit"></span>
      <span class="implant-visual__frame"><canvas class="implant-visual__canvas"></canvas></span>
      <span class="implant-visual__shadow"></span>
      <span class="implant-visual__floor"></span>
      <span class="implant-visual__label">نمای سه‌بعدی ایمپلنت</span>`);
    const description = card.children[1];
    card.insertBefore(visual, description || null);
    renderDentalImplant(visual.querySelector('.implant-visual__canvas'));
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mountImplant, {once:true});
  else mountImplant();
})();


