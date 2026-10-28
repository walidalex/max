'use strict';
document.addEventListener('DOMContentLoaded',()=>{
 const client=document.getElementById('client_id'),contact=document.getElementById('primary_contact_id');if(!client||!contact)return;
 if(document.getElementById('project-contract-wizard')){initializeQuickClient(client);return;}
 client.addEventListener('change',async()=>{
  const select=contact.tomselect;select?.clear();select?.clearOptions();select?.addOption({value:'',text:'بدون'});select?.disable();
  if(!client.value){select?.enable();return;}
  try{const response=await fetch('/api/projects/client-contacts?client_id='+encodeURIComponent(client.value),{headers:{Accept:'application/json'}});const body=await response.json();if(!response.ok)throw new Error(body.message||'تعذر تحميل جهات الاتصال.');
   for(const item of body.data)select?.addOption({value:String(item.id),text:item.name+(item.job_title?' - '+item.job_title:'')});select?.refreshOptions(false);
  }catch(error){window.AppAlert?.show({type:'error',title:'تعذر التحميل',text:error.message});}finally{select?.enable();}
 });
 initializeQuickClient(client);
});

function initializeQuickClient(clientSelect){
 const modalElement=document.getElementById('project-client-modal'),form=document.getElementById('project-client-form');if(!modalElement||!form||!window.tabler?.Modal)return;
 const modal=window.tabler.Modal.getOrCreateInstance(modalElement),type=form.querySelector('.js-quick-client-type'),error=form.querySelector('.js-quick-client-error'),save=form.querySelector('.js-save-quick-client');
 const updateType=()=>{const company=type.value==='company';form.querySelectorAll('.js-quick-company').forEach(field=>{field.classList.toggle('d-none',!company);field.querySelectorAll('input').forEach(input=>{input.disabled=!company;});});form.querySelectorAll('.js-quick-individual').forEach(field=>{field.classList.toggle('d-none',company);field.querySelectorAll('input').forEach(input=>{input.disabled=company;});});form.querySelector('[name="company_name"]')?.toggleAttribute('required',company);form.querySelector('[name="name"]')?.toggleAttribute('required',!company);};
 type.addEventListener('change',updateType);updateType();document.getElementById('add-project-client')?.addEventListener('click',()=>modal.show());
 form.addEventListener('submit',async event=>{event.preventDefault();error.classList.add('d-none');save.disabled=true;try{const response=await fetch(form.action,{method:'POST',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},body:new FormData(form)}),body=await response.json();if(!response.ok||!body.ok)throw new Error(body.message||'تعذر حفظ العميل.');const option={value:String(body.client.id),text:`${body.client.client_code} - ${body.client.name}`};if(clientSelect.tomselect){clientSelect.tomselect.addOption(option);clientSelect.tomselect.setValue(option.value);}else{clientSelect.add(new Option(option.text,option.value,true,true));clientSelect.dispatchEvent(new Event('change',{bubbles:true}));}modal.hide();form.reset();updateType();window.AppAlert?.show({type:'success',title:'تمت الإضافة',text:'تم حفظ العميل واختياره للمشروع.'});}catch(exception){error.textContent=exception.message;error.classList.remove('d-none');}finally{save.disabled=false;}});
}
