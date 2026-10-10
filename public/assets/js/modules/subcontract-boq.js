'use strict';

document.addEventListener('DOMContentLoaded',()=>{
 const root=document.getElementById('boq-root');
 if(!root)return;

 async function send(url,body){
  const response=await fetch(url,{method:'POST',body,headers:{Accept:'application/json'}});
  const data=await response.json();
  if(!response.ok){const first=Object.values(data.errors||{}).flat()[0];throw new Error(first||data.message||'تعذر تنفيذ الطلب.');}
  location.reload();
 }

 document.addEventListener('click',async event=>{
  const action=event.target.closest('.js-action');
  if(!action)return;
  const confirmation=await AppAlert.confirm({text:action.dataset.approve?'سيؤدي الاعتماد إلى قفل جدول الأعمال وتثبيت قيمة العقد على الإجمالي المعروض.':'هل تريد حذف هذا البند؟'});
  if(!confirmation.isConfirmed)return;
  const data=new FormData();data.append('_token',root.dataset.token);
  try{await send(action.dataset.url,data);}catch(error){AppAlert.show({type:'error',title:'تعذر التنفيذ',text:error.message});}
 });

 document.querySelectorAll('.boq-form').forEach(form=>form.addEventListener('submit',async event=>{
  event.preventDefault();
  try{await send(form.action,new FormData(form));}catch(error){AppAlert.show({type:'error',title:'تعذر الحفظ',text:error.message});}
 }));

 document.querySelectorAll('.boq-inline-item').forEach(row=>{
  const unit=row.querySelector('.js-boq-unit');
  const quantity=row.querySelector('.js-boq-quantity');
  const amount=row.querySelector('.js-boq-amount');
  const form=document.getElementById(unit.getAttribute('form'));
  const pricing=form.querySelector('[name=pricing_type]');
  unit.addEventListener('change',()=>{
   const lumpSum=unit.options[unit.selectedIndex]?.dataset.code==='lump_sum';
   pricing.value=lumpSum?'lump_sum':'quantity';
   quantity.disabled=lumpSum;
   quantity.required=!lumpSum;
   quantity.value=lumpSum?'':quantity.value;
   amount.name=lumpSum?'lump_sum_amount':'unit_rate';
   amount.placeholder=lumpSum?'قيمة المقطوعية':'سعر الوحدة';
   amount.required=unit.value!=='';
  });
 });
});
