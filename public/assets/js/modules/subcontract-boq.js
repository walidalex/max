'use strict';

document.addEventListener('DOMContentLoaded',()=>{
 const root=document.getElementById('boq-root');
 if(!root)return;

 async function send(url,body){
  const response=await fetch(url,{method:'POST',body,headers:{Accept:'application/json'}});
  const data=await response.json();
  if(!response.ok){const first=Object.values(data.errors||{}).flat()[0];throw new Error(first||data.message||'تعذر تنفيذ الطلب.');}
  await window.AppAlert?.show({type:'success',title:'تم بنجاح',text:data.message||'تم حفظ التغييرات.'});
  location.reload();
 }

 document.addEventListener('click',async event=>{
  const action=event.target.closest('.js-action');
  if(!action)return;
  const confirmation=await AppAlert.confirm({title:action.dataset.approve?'اعتماد جدول الأعمال وتفعيل العقد؟':undefined,text:action.dataset.approve?'سيتم تثبيت قيمة العقد حسب إجمالي البنود، وتفعيل العقد، وقفل جدول الأعمال نهائيًا أمام التعديل.':'هل تريد حذف هذا البند؟',confirmButtonText:action.dataset.approve?'اعتماد وتفعيل':'متابعة'});
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
  unit.dispatchEvent(new Event('change'));
 });

 document.querySelectorAll('.js-edit-item').forEach(button=>button.addEventListener('click',()=>{
  const display=button.closest('.js-item-display');
  const editor=display?.nextElementSibling;
  if(!editor?.classList.contains('js-item-editor'))return;
  document.querySelectorAll('.js-item-editor:not(.d-none)').forEach(row=>{row.classList.add('d-none');row.previousElementSibling?.classList.remove('d-none');});
  display.classList.add('d-none');
  editor.classList.remove('d-none');
 }));
 document.querySelectorAll('.js-cancel-edit').forEach(button=>button.addEventListener('click',()=>{
  const editor=button.closest('.js-item-editor');
  editor?.classList.add('d-none');
  editor?.previousElementSibling?.classList.remove('d-none');
 }));
});
