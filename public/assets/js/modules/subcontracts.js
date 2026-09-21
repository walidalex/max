'use strict';
document.addEventListener('DOMContentLoaded',()=>{
 const section=document.getElementById('subcontract-work-section-id'),vendor=document.getElementById('subcontract-vendor-id');
 if(!section||!vendor)return;
 const refresh=async()=>{const id=section.value;if(!id){setVendors([]);return;}try{const r=await fetch(`/api/subcontracts/vendors-by-work-section?work_section_id=${encodeURIComponent(id)}`,{headers:{Accept:'application/json'}});const d=await r.json();setVendors(d.data||[]);}catch(_){setVendors([]);}};
 const setVendors=(rows)=>{const current=vendor.value;if(vendor.tomselect){vendor.tomselect.clear(true);vendor.tomselect.clearOptions();vendor.tomselect.addOptions(rows.map(v=>({value:String(v.id),text:`${v.vendor_code} - ${v.name}`})));if(rows.some(v=>String(v.id)===current))vendor.tomselect.setValue(current);}else{vendor.replaceChildren(new Option('اختر مقاول الباطن',''));rows.forEach(v=>vendor.add(new Option(`${v.vendor_code} - ${v.name}`,v.id)));if(rows.some(v=>String(v.id)===current))vendor.value=current;}};
 section.addEventListener('change',refresh);if(section.value)refresh();
});
