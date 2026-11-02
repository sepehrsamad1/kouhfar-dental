<?php

declare(strict_types=1);

namespace App\Services;

final class AssistantGuard
{
    public function classify(string $message): string
    {
        $text = mb_strtolower(trim($message));

        if ($this->hasAny($text, ['ignore all previous', 'ignore previous instructions', 'system prompt', 'developer message', 'reveal your prompt', 'hidden instructions', 'پرامپت سیستم', 'دستورهای مخفی', 'اطلاعات محرمانه', 'کلید api', 'api key', 'database credentials', 'environment variables', 'پرونده بیماران'])) {
            return 'prompt_injection';
        }

        if ($this->hasAny($text, ['نمی‌توانم نفس', 'نمی توانم نفس', 'مشکل تنفس', 'مشکل بلع', 'خونریزی شدید', 'خونریزی کنترل‌نشده', 'تورم شدید', 'تورم سریع', 'بیهوش', 'uncontrolled heavy bleeding', 'difficulty breathing', 'difficulty swallowing', 'severe swelling', 'airway', 'unconscious', 'serious facial trauma', 'lost consciousness', 'loss of consciousness', 'swelling rapidly', 'rapidly swelling', 'seriously injured', 'uncontrolled bleeding', 'double vision', 'cannot swallow saliva', 'cannot swallow my saliva'])) {
            return 'emergency';
        }

        if ($this->hasAny($text, ['hurt myself', 'self-harm', 'harm someone', 'آسیب زدن به خود', 'مواد خطرناک', 'break into the booking', 'جعل گواهی', 'دستکاری پرونده', 'alter a medical record', 'medical record', 'controlled medicine without a prescription', 'fake treatment certificate', 'dangerous substances', 'invasive dental treatment at home', 'anesthetize someone without training', 'hide an infection', 'medical equipment to injure'])) {
            return 'prompt_injection';
        }

        if ($this->hasAny($text, ['اسم تو چیست', 'اسم تو چیه', 'تو کی هستی', 'چه کسی هستی', 'چه کسی ساخته', 'خودت را chatgpt', 'what is your name', 'who are you', 'who created you', 'introduce yourself as', 'which ai model', 'claim to be dr.', 'claim to be doctor'])) {
            return 'identity';
        }

        if ($this->hasAny($text, ['تشخیص بیماری', 'بیماری من', 'diagnose', 'diagnosis', 'prescribe', 'prescription', 'نسخه', 'دوز دارو', 'مقدار دارو', 'آنتی‌بیوتیک تجویز', 'antibiotic dose', 'medication dose'])) {
            return 'medical_refusal';
        }

        if ($this->hasAny($text, ['دندان', 'دندانپزشک', 'دندان‌پزشک', 'دندان‌پزشکی', 'دهان', 'لثه', 'ایمپلنت', 'لمینیت', 'کامپوزیت', 'روکش', 'بلیچینگ', 'عصب‌کشی', 'درمان ریشه', 'جرم‌گیری', 'ارتودنسی', 'دندان عقل', 'پوسیدگی', 'پرکردگی', 'نوبت', 'کلینیک', 'سلامت دهان', 'tooth', 'teeth', 'dental', 'dentist', 'dentistry', 'oral health', 'gum', 'implant', 'veneer', 'composite', 'crown', 'whitening', 'root canal', 'filling', 'braces', 'wisdom tooth', 'clinic', 'patient rights', 'toothache', 'tooth pain', 'dental treatment', 'mouth pain', 'breath', 'how much does dental treatment cost', 'is this dental problem dangerous', 'dry mouth', 'tooth pain', 'toothache', 'dental treatment'])) {
            return 'in_scope';
        }

        return 'out_of_domain';
    }

    private function hasAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (mb_stripos($text, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
