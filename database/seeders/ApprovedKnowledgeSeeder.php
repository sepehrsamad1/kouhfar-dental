<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\ContentItem;
use App\Enums\ContentItemStatus;
use App\Enums\ContentKind;
use Illuminate\Database\Seeder;
use LogicException;

final class ApprovedKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('ApprovedKnowledgeSeeder is disabled in production until clinic production approval is migrated through the content workflow.');
        }

        $adminId = '11111111-1111-4111-8111-111111111111';

        $items = [
            ['key'=>'patient-rights','kind'=>ContentKind::ClinicInfo,'scope'=>'general',
             'fa'=>['title'=>'حقوق بیمار','text'=>'هر بیمار حق دارد خدمات سلامت محترمانه، عادلانه و مبتنی بر دانش روز دریافت کند. بیمار باید اطلاعات کافی و قابل فهم درباره مراقبت دریافت کند، در چارچوب ضوابط درباره انتخاب و تصمیم‌گیری درمانی اختیار داشته باشد و حریم خصوصی و محرمانگی اطلاعات او رعایت شود. همچنین باید مسیر مؤثر رسیدگی به شکایت در دسترس باشد.'],
             'en'=>['title'=>'Patient rights','text'=>'Patients have the right to respectful, equitable, up-to-date healthcare; clear and sufficient information; free and informed participation in healthcare decisions within applicable rules; privacy and confidentiality; and access to an effective complaints process.']],
            ['key'=>'dental-infection-prevention','kind'=>ContentKind::ClinicInfo,'scope'=>'clinical',
             'fa'=>['title'=>'اصول پیشگیری از عفونت در محیط دندانپزشکی','text'=>'ایمنی عفونت باید بر پایه احتیاطات استاندارد باشد: بهداشت دست، استفاده مناسب از وسایل حفاظت فردی، بهداشت تنفسی، ایمنی وسایل تیز، تزریق ایمن، استریل‌سازی و بازفرآوری صحیح ابزار و تمیز و ضدعفونی کردن سطوح محیطی. ابزار قابل استفاده مجدد باید طبق دستورالعمل معتبر سازنده و فرایندهای مصوب بازفرآوری شود و ابزارهای بحرانی که وارد بافت نرم یا استخوان می‌شوند باید به روش مناسب استریل شوند.'],
             'en'=>['title'=>'Dental infection prevention','text'=>'Safe dental care should use Standard Precautions: hand hygiene, appropriate personal protective equipment, respiratory hygiene, sharps safety, safe injection practices, proper instrument reprocessing and sterilization, and cleaning and disinfection of clinical surfaces. Reusable devices should be reprocessed according to validated manufacturer instructions and applicable requirements. Critical instruments that penetrate soft tissue or bone require appropriate heat sterilization when heat-tolerant.']],
            ['key'=>'general-oral-health','kind'=>ContentKind::Faq,'scope'=>'clinical',
             'fa'=>['title'=>'اطلاعات عمومی سلامت دهان','text'=>'اطلاعات عمومی سلامت دهان جایگزین معاینه و تشخیص دندانپزشک نیست. درد مداوم، تورم، تب، خونریزی غیرعادی یا تغییراتی که برطرف نمی‌شوند باید توسط دندانپزشک ارزیابی شوند. انتخاب درمان به شرایط فرد، معاینه و در صورت نیاز بررسی‌های تکمیلی بستگی دارد.'],
             'en'=>['title'=>'General oral-health information','text'=>'General oral-health information does not replace a dental examination or diagnosis. Persistent pain, swelling, fever, unusual bleeding, or changes that do not resolve should be assessed by a dentist. Treatment choices depend on the individual situation, examination findings, and additional assessment when needed.']],
            ['key'=>'dental-emergency-response','kind'=>ContentKind::ClinicInfo,'scope'=>'clinical',
             'fa'=>['title'=>'پاسخ ایمن به علائم اورژانسی','text'=>'در صورت مشکل شدید در تنفس یا بلع، تورم سریع یا شدید دهان، صورت، گلو یا گردن، خونریزی شدید و کنترل‌نشده، بیهوشی یا آسیب شدید صورت، فرد باید فوراً از خدمات اورژانسی پزشکی استفاده کند. در ایران شماره اورژانس پزشکی ۱۱۵ است. برای این علائم نباید منتظر پاسخ دستیار هوش مصنوعی یا نوبت معمول دندانپزشکی ماند.'],
             'en'=>['title'=>'Emergency safety response','text'=>'Severe difficulty breathing or swallowing, rapidly increasing or severe swelling of the mouth, face, throat or neck, uncontrolled heavy bleeding, loss of consciousness, or serious facial trauma require immediate emergency medical assessment. In Iran, the emergency medical services number is 115. Do not wait for an AI response or a routine dental appointment when these red flags are present.']],
            ['key'=>'assistant-safety-scope','kind'=>ContentKind::ClinicInfo,'scope'=>'general',
             'fa'=>['title'=>'محدوده دستیار اطلاعاتی','text'=>'دستیار برای ارائه اطلاعات عمومی و تأییدشده طراحی شده است. دستیار تشخیص پزشکی، تجویز دارو یا دوز، تصمیم‌گیری درمانی فردی، دسترسی به پرونده بیمار، تغییر یا لغو نوبت، تأیید نوبت، یا ارائه اطلاعات داخلی کارکنان را انجام نمی‌دهد. اگر اطلاعات کافی در محتوای تأییدشده وجود نداشته باشد، باید این محدودیت را صریح اعلام کند.'],
             'en'=>['title'=>'Assistant safety scope','text'=>'The assistant provides public, approved information only. It does not diagnose, prescribe medication or dosage, make individualized treatment decisions, access patient records, create or modify appointments, approve appointments, or disclose internal staff information. When approved knowledge is insufficient, it must say so rather than invent an answer.']],
        ];

        foreach ($items as $item) {
            foreach (['fa'=>$item['fa'], 'en'=>$item['en']] as $locale=>$body) {
                ContentItem::query()->updateOrCreate(
                    ['content_key'=>$item['key'], 'locale'=>$locale, 'version'=>1],
                    [
                        'kind'=>$item['kind'], 'body'=>$body, 'status'=>ContentItemStatus::Published,
                        'approval_scope'=>$item['scope'], 'created_by'=>$adminId, 'reviewed_by'=>$adminId,
                        'reviewed_at'=>now('UTC'),
                        'owner_approved_by'=>$item['scope']==='general' ? null : $adminId,
                        'owner_approved_at'=>$item['scope']==='general' ? null : now('UTC'),
                        'published_by'=>$adminId, 'published_at'=>now('UTC'), 'archived_at'=>null,
                    ],
                );
            }
        }
    }
}
