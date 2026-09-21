'use strict';
document.addEventListener('DOMContentLoaded',()=>{
 const root=document.getElementById('boq-root');if(!root)return;
 async function send(url,body){const response=await fetch(url,{method:'POST',body,headers:{Accept:'application/json'}});const data=await response.json();if(!response.ok){const first=Object.values(data.errors||{}).flat()[0];throw new Error(first||data.message||'تعذر تنفيذ الطلب.');}location.reload();}
 const itemModal=document.getElementById('item-modal');
 if(itemModal){
  const cost=itemModal.querySelector('[name=cost_code_id]');
  cost.insertAdjacentHTML('beforebegin','<select class="form-select mb-2" name="pricing_type"><option value="quantity">بند كمي — كمية × سعر الوحدة</option><option value="lump_sum">بند مقطوعية — قيمة إجمالية</option></select>');
  const sort=itemModal.querySelector('[name=sort_order]');
  sort.insertAdjacentHTML('beforebegin','<input class="form-control mb-2" type="number" step="0.01" min="0" name="lump_sum_amount" placeholder="قيمة البند المقطوعي">');
  const pricing=itemModal.querySelector('[name=pricing_type]'),unit=itemModal.querySelector('[name=unit_id]'),quantity=itemModal.querySelector('[name=quantity]'),rate=itemModal.querySelector('[name=unit_rate]'),lump=itemModal.querySelector('[name=lump_sum_amount]');
  const togglePricing=()=>{const isLump=pricing.value==='lump_sum';[unit,quantity,rate].forEach(field=>{field.disabled=isLump;field.classList.toggle('d-none',isLump);});lump.disabled=!isLump;lump.classList.toggle('d-none',!isLump);};
  pricing.addEventListener('change',togglePricing);togglePricing();
 }
 document.addEventListener('click',async event=>{
  const action=event.target.closest('.js-action');
  if(action){const confirmation=await AppAlert.confirm({text:action.dataset.approve?'سيؤدي الاعتماد إلى قفل جدول الأعمال وتثبيت قيمة العقد على الإجمالي المعروض.':'هل تريد تنفيذ هذا الإجراء؟'});if(confirmation.isConfirmed){const data=new FormData();data.append('_token',root.dataset.token);try{await send(action.dataset.url,data);}catch(error){AppAlert.show({type:'error',title:'تعذر التنفيذ',text:error.message});}}}
  const add=event.target.closest('.js-add-item');if(add){itemModal.querySelector('[name=section_id]').value=add.dataset.section;tabler.Modal.getOrCreateInstance(itemModal).show();}
 });
 document.querySelectorAll('.boq-form').forEach(form=>form.addEventListener('submit',async event=>{event.preventDefault();try{await send(form.action,new FormData(form));}catch(error){AppAlert.show({type:'error',title:'تعذر الحفظ',text:error.message});}}));
 const workSection=document.querySelector('[name=work_section_id]');workSection?.addEventListener('change',()=>{const option=workSection.options[workSection.selectedIndex];if(option.value){document.querySelector('[name=section_code]').value=option.dataset.code;document.querySelector('[name=title]').value=option.dataset.title;}});
 const costCode=document.querySelector('[name=cost_code_id]');costCode?.addEventListener('change',()=>{const option=costCode.options[costCode.selectedIndex];if(option.value){document.querySelector('[name=item_code]').value=option.dataset.code;document.querySelector('[name=description]').value=option.dataset.description;document.querySelector('[name=unit_id]').value=option.dataset.unit||'';}});
});
