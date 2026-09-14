'use strict';
document.addEventListener('DOMContentLoaded',()=>{
 const root=document.getElementById('vendor-contacts'),element=document.getElementById('vendor-contact-modal'),form=document.getElementById('vendor-contact-form');
 if(!root||!element||!form||!window.tabler?.Modal)return;
 const vendorId=Number(root.dataset.vendorId),modal=window.tabler.Modal.getOrCreateInstance(element),errorBox=document.getElementById('vendor-contact-error'),title=document.getElementById('vendor-contact-modal-title');
 const fields=['name','job_title','phone','mobile','email','notes'];
 function clearErrors(){errorBox.classList.add('d-none');errorBox.textContent='';form.querySelectorAll('.is-invalid').forEach(input=>input.classList.remove('is-invalid'));form.querySelectorAll('.invalid-feedback').forEach(item=>item.textContent='');}
 function open(contact=null){form.reset();clearErrors();form.action=contact?`/api/vendors/${vendorId}/contacts/${Number(contact.id)}`:`/api/vendors/${vendorId}/contacts`;title.textContent=contact?'تعديل جهة الاتصال':'إضافة جهة اتصال';if(contact){fields.forEach(name=>{form.elements[name].value=contact[name]??'';});form.elements.is_primary.checked=Boolean(contact.is_primary);}modal.show();}
 document.getElementById('add-vendor-contact')?.addEventListener('click',()=>open());
 root.addEventListener('click',async event=>{
  const edit=event.target.closest('.js-edit-vendor-contact');if(edit){try{open(JSON.parse(edit.dataset.contact));}catch{window.AppAlert?.show({type:'error',title:'تعذر فتح النموذج'});}return;}
  const primary=event.target.closest('.js-primary-vendor-contact');if(primary){await action(primary.dataset.url,false);return;}
  const remove=event.target.closest('.js-remove-vendor-contact');if(remove){await action(remove.dataset.url,true);}
 });
 form.addEventListener('submit',async event=>{event.preventDefault();clearErrors();setBusy(true);try{const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});const data=await response.json();if(!response.ok){showErrors(data);return;}root.innerHTML=data.html;modal.hide();window.AppAlert?.show({type:'success',title:'تم الحفظ',text:data.message});}catch{showErrors({message:'تعذر الاتصال بالخادم.'});}finally{setBusy(false);}});
 async function action(url,confirm){if(confirm){const result=await window.AppAlert.confirm({text:'سيتم حذف جهة الاتصال إذا لم تكن مرتبطة ببيانات أخرى.'});if(!result.isConfirmed)return;}const body=new FormData();body.append('_token',form.elements._token.value);try{const response=await fetch(url,{method:'POST',body,headers:{Accept:'application/json'}});const data=await response.json();if(!response.ok){throw new Error(data.message||'تعذر تنفيذ الطلب.');}root.innerHTML=data.html;window.AppAlert?.show({type:'success',title:'تم التحديث',text:data.message});}catch(error){window.AppAlert?.show({type:'error',title:'تعذر تنفيذ الطلب',text:error.message});}}
 function showErrors(data){errorBox.textContent=data.message||'يرجى مراجعة البيانات.';errorBox.classList.remove('d-none');Object.entries(data.errors||{}).forEach(([name,messages])=>{const input=form.elements[name];if(!input)return;input.classList.add('is-invalid');const feedback=input.parentElement.querySelector('.invalid-feedback');if(feedback)feedback.textContent=Array.isArray(messages)?messages[0]:messages;});}
 function setBusy(busy){const button=form.querySelector('[type="submit"]'),spinner=button.querySelector('.spinner-border');button.disabled=busy;spinner.classList.toggle('d-none',!busy);}
});
