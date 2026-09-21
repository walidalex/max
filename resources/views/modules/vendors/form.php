<?php $vendorId=(int)$vendor['id']; ?>
<div class="page-header mb-4"><div class="row align-items-center"><div class="col"><div class="page-pretitle">الموردون ومقاولو الباطن</div><h2 class="page-title"><?=e($title)?></h2></div></div></div>
<form class="card js-vendor-form" method="post" action="/vendors/<?=$vendorId?>/edit"><div class="card-body"><input type="hidden" name="_token" value="<?=e($csrfToken)?>"><?=$this->component('vendor-fields',['vendor'=>$vendor,'workSections'=>$workSections])?></div><div class="card-footer text-end"><a class="btn btn-link" href="/vendors/<?=$vendorId?>">إلغاء</a><button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy ms-2"></i>حفظ</button></div></form>
<script src="/assets/js/modules/vendors.js" defer></script>
