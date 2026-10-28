<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?=e($title)?> — <?=e($certificate['certificate_code'])?></title>
    <link rel="stylesheet" href="/assets/css/subcontract-certificate-print.css">
</head>
<body>
<nav class="print-toolbar"><button type="button" onclick="window.print()">طباعة / حفظ PDF</button><a href="/subcontract-certificates/<?=(int)$certificate['id']?>">العودة للمستخلص</a><span>مقاس A4 أفقي — اختر «حفظ بتنسيق PDF» من نافذة الطباعة.</span></nav>
<main><?=$content?></main>
</body>
</html>
