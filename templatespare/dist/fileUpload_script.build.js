!function(e){var t={};function n(r){if(t[r])return t[r].exports;var l=t[r]={i:r,l:!1,exports:{}};return e[r].call(l.exports,l,l.exports,n),l.l=!0,l.exports}n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var l in e)n.d(r,l,function(t){return e[t]}.bind(null,l));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=30)}({30:function(e,t){document.addEventListener("DOMContentLoaded",()=>{const e=document.querySelector(".templatespare-drop-zone"),t=e.querySelector("input"),n=e.querySelector(".file-name"),r=e.querySelector(".templatespare-drop-zone-text");let l=null;e.addEventListener("click",()=>t.click()),e.addEventListener("dragover",t=>{t.preventDefault(),e.classList.add("drag-over"),e.classList.add("templatespare-file-selected")}),e.addEventListener("dragleave",()=>e.classList.remove("drag-over")),e.addEventListener("drop",a=>{a.preventDefault(),e.classList.remove("drag-over");const o=a.dataTransfer.files;if(e.classList.add("templatespare-file-selected"),o.length){const e=o[0];e.name.endsWith(".zip")?(t.files=o,n.textContent="Selected file: "+e.name,n.style.display="block",r.style.display="none",l=e):(alert("Please upload a .zip file"),l=null)}}),t.addEventListener("change",()=>{if(t.files.length){e.classList.add("templatespare-file-selected");const a=t.files[0];a.name.endsWith(".zip")?(n.textContent="Selected file: "+a.name,n.style.display="block",r.style.display="none",l=a):(alert("Please upload a .zip file"),t.value="",l=null)}})})}});