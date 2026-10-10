<?php

declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return ($value === false || $value === null) ? $default : $value;
    }
}

if (!function_exists('env_bool')) {
    function env_bool(string $key, bool $default = false): bool
    {
        $value = env($key);
        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOL);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('decimal_format')) {
    function decimal_format(string $value, int $scale = 2): string
    {
        if (!preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', trim($value), $matches)) return $value;
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', ltrim($matches[2], '0') ?: '0');
        return $matches[1] . $whole . ($scale > 0 ? '.' . str_pad(substr($matches[3] ?? '', 0, $scale), $scale, '0') : '');
    }
}

if (!function_exists('egypt_governorates')) {
    /** @return array<string, string> */
    function egypt_governorates(): array
    {
        return [
            'all'=>'جميع المحافظات',
            'cairo'=>'القاهرة','giza'=>'الجيزة','alexandria'=>'الإسكندرية','dakahlia'=>'الدقهلية',
            'red_sea'=>'البحر الأحمر','beheira'=>'البحيرة','fayoum'=>'الفيوم','gharbia'=>'الغربية',
            'ismailia'=>'الإسماعيلية','monufia'=>'المنوفية','minya'=>'المنيا','qalubia'=>'القليوبية',
            'new_valley'=>'الوادي الجديد','suez'=>'السويس','aswan'=>'أسوان','assiut'=>'أسيوط',
            'beni_suef'=>'بني سويف','port_said'=>'بورسعيد','damietta'=>'دمياط','sharqia'=>'الشرقية',
            'south_sinai'=>'جنوب سيناء','kafr_el_sheikh'=>'كفر الشيخ','matrouh'=>'مطروح','luxor'=>'الأقصر',
            'qena'=>'قنا','north_sinai'=>'شمال سيناء','sohag'=>'سوهاج',
        ];
    }
}

if (!function_exists('egypt_banks')) {
    /** @return array<string, string> */
    function egypt_banks(): array
    {
        return [
            'nbe' => 'البنك الأهلي المصري',
            'banque_misr' => 'بنك مصر',
            'banque_du_caire' => 'بنك القاهرة',
            'agricultural_bank' => 'البنك الزراعي المصري',
            'cib' => 'البنك التجاري الدولي CIB',
            'qnb_egypt' => 'بنك QNB مصر',
            'aaib' => 'البنك العربي الأفريقي الدولي',
            'alexbank' => 'بنك الإسكندرية',
            'credit_agricole' => 'كريدي أجريكول مصر',
            'hsbc' => 'بنك HSBC مصر',
            'emirates_nbd' => 'بنك الإمارات دبي الوطني مصر',
            'adcb' => 'بنك أبوظبي التجاري مصر',
            'fabmisr' => 'بنك أبوظبي الأول مصر',
            'mashreq' => 'بنك المشرق مصر',
            'adib' => 'مصرف أبوظبي الإسلامي مصر',
            'al_baraka' => 'بنك البركة مصر',
            'faisal' => 'بنك فيصل الإسلامي المصري',
            'hdb' => 'بنك التعمير والإسكان',
            'ebank' => 'البنك المصري لتنمية الصادرات EBank',
            'scb' => 'بنك قناة السويس',
            'eg_bank' => 'البنك المصري الخليجي EG Bank',
            'kfh_egypt' => 'بيت التمويل الكويتي مصر',
            'aib' => 'المصرف العربي الدولي',
            'arab_bank' => 'البنك العربي',
            'bank_abc' => 'بنك ABC مصر',
            'saib' => 'بنك SAIB',
            'midbank' => 'ميدبنك MIDBank',
            'bank_nxt' => 'بنك نكست Bank NXT',
            'attijariwafa' => 'التجاري وفا بنك إيجيبت',
            'nbk_egypt' => 'بنك الكويت الوطني مصر',
            'idb' => 'بنك التنمية الصناعية',
            'united_bank' => 'المصرف المتحد',
            'ealb' => 'البنك العقاري المصري العربي',
            'abk_egypt' => 'البنك الأهلي الكويتي مصر',
            'citibank' => 'سيتي بنك',
            'standard_chartered' => 'ستاندرد تشارترد مصر',
            'other' => 'بنك آخر',
        ];
    }
}
