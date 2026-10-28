<?php $canEdit=$canApprove=$canCancel=false;?>
<?php if(($statement['pricing_method_snapshot']??'')==='cost_plus'):?><style>@page{size:A4 landscape;margin:10mm}@media print{.card{break-inside:auto!important}.table-responsive{overflow:visible!important}table{font-size:10px}thead{display:table-header-group}tr{break-inside:avoid}th,td{padding:5px!important}.container{width:100%;max-width:none!important}}</style><?php endif;?>
<?php require __DIR__.'/show.php';?>
