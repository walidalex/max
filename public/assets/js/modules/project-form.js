'use strict';
document.addEventListener('DOMContentLoaded',()=>{
 const client=document.getElementById('client_id'),contact=document.getElementById('primary_contact_id');if(!client||!contact)return;
 client.addEventListener('change',async()=>{
  const select=contact.tomselect;select?.clear();select?.clearOptions();select?.addOption({value:'',text:'بدون'});select?.disable();
  if(!client.value){select?.enable();return;}
  try{const response=await fetch('/api/projects/client-contacts?client_id='+encodeURIComponent(client.value),{headers:{Accept:'application/json'}});const body=await response.json();if(!response.ok)throw new Error(body.message||'تعذر تحميل جهات الاتصال.');
   for(const item of body.data)select?.addOption({value:String(item.id),text:item.name+(item.job_title?' - '+item.job_title:'')});select?.refreshOptions(false);
  }catch(error){window.AppAlert?.show({type:'error',title:'تعذر التحميل',text:error.message});}finally{select?.enable();}
 });
});
