'use strict';
document.addEventListener('DOMContentLoaded',()=>document.querySelector('.js-project-status-form')?.addEventListener('submit',async event=>{event.preventDefault();const result=await window.AppAlert.confirm({text:'هل تريد تغيير حالة المشروع؟'});if(result.isConfirmed)event.target.submit();}));
