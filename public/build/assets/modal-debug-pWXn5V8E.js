class n{constructor(){this.isEnabled=!1,this.init()}init(){const t=new URLSearchParams(window.location.search).get("modal-debug"),a=localStorage.getItem("modal-debug");this.isEnabled=t==="true"||a==="true",this.isEnabled&&(this.enableDebugging(),this.addDebugControls(),this.logZIndexHierarchy())}enableDebugging(){if(document.body.classList.add("modal-debug-enabled"),console.log("🔍 Modal debugging enabled"),!document.getElementById("modal-debug-styles")){const o=document.createElement("style");o.id="modal-debug-styles",o.textContent=`
                .modal-debug-info {
                    position: fixed;
                    top: 10px;
                    left: 10px;
                    background: rgba(0, 0, 0, 0.8);
                    color: white;
                    padding: 10px;
                    border-radius: 5px;
                    font-family: monospace;
                    font-size: 12px;
                    z-index: 99999; /* Debug panel must be above all modals (99999 > 10000) */
                    max-width: 300px;
                }
                .modal-debug-info h4 {
                    margin: 0 0 5px 0;
                    color: #ffff00;
                }
                .modal-debug-info ul {
                    margin: 0;
                    padding-left: 15px;
                }
            `,document.head.appendChild(o)}}addDebugControls(){const o=document.createElement("div");o.id="modal-debug-panel",o.innerHTML=`
            <div class="modal-debug-info">
                <h4>Modal Debug Controls</h4>
                <button onclick="modalDebugger.toggleDebugging()" style="margin: 2px; padding: 4px 8px; font-size: 11px;">
                    Toggle Debug Mode
                </button>
                <button onclick="modalDebugger.logZIndexHierarchy()" style="margin: 2px; padding: 4px 8px; font-size: 11px;">
                    Log Z-Index Hierarchy
                </button>
                <button onclick="modalDebugger.highlightModals()" style="margin: 2px; padding: 4px 8px; font-size: 11px;">
                    Highlight Modals
                </button>
                <button onclick="modalDebugger.clearHighlights()" style="margin: 2px; padding: 4px 8px; font-size: 11px;">
                    Clear Highlights
                </button>
                <div id="modal-debug-info" style="margin-top: 10px; font-size: 10px;"></div>
            </div>
        `,document.body.appendChild(o)}toggleDebugging(){this.isEnabled=!this.isEnabled,localStorage.setItem("modal-debug",this.isEnabled.toString()),this.isEnabled?(document.body.classList.add("modal-debug-enabled"),console.log("🔍 Modal debugging enabled")):(document.body.classList.remove("modal-debug-enabled"),console.log("🔍 Modal debugging disabled")),this.updateDebugInfo()}logZIndexHierarchy(){console.group("🔍 Z-Index Hierarchy Analysis");const o=document.querySelectorAll("*"),t=[];o.forEach(e=>{const d=getComputedStyle(e),l=d.zIndex;l!=="auto"&&l!=="0"&&t.push({element:e,zIndex:parseInt(l),tagName:e.tagName,className:e.className,id:e.id,position:d.position})}),t.sort((e,d)=>e.zIndex-d.zIndex),console.table(t.map(e=>({"Z-Index":e.zIndex,Tag:e.tagName,ID:e.id||"N/A",Classes:e.className||"N/A",Position:e.position})));const a=document.querySelectorAll("[data-modal-type]");a.length>0&&(console.group("Modal Elements"),a.forEach(e=>{const d=getComputedStyle(e);console.log(`${e.dataset.modalType} (${e.dataset.modalName}):`,{zIndex:d.zIndex,position:d.position,display:d.display,visibility:d.visibility,opacity:d.opacity})}),console.groupEnd()),console.groupEnd(),this.updateDebugInfo()}highlightModals(){document.querySelectorAll("[data-modal-type]").forEach(t=>{switch(t.dataset.modalType){case"container":t.classList.add("z-debug-highest");break;case"backdrop":t.classList.add("z-debug-medium");break;case"content":t.classList.add("z-debug-high");break}}),console.log("🔍 Modal elements highlighted")}clearHighlights(){["z-debug-low","z-debug-medium","z-debug-high","z-debug-highest"].forEach(t=>{document.querySelectorAll(`.${t}`).forEach(a=>{a.classList.remove(t)})}),console.log("🔍 Highlights cleared")}updateDebugInfo(){const o=document.getElementById("modal-debug-info");if(!o)return;const t=document.querySelectorAll("[data-modal-type]"),a=Array.from(t).filter(e=>{const d=getComputedStyle(e);return d.display!=="none"&&d.visibility!=="hidden"});o.innerHTML=`
            <strong>Status:</strong> ${this.isEnabled?"Enabled":"Disabled"}<br>
            <strong>Total Modals:</strong> ${t.length}<br>
            <strong>Visible Modals:</strong> ${a.length}<br>
            <strong>Timestamp:</strong> ${new Date().toLocaleTimeString()}
        `}observeModalChanges(){new MutationObserver(t=>{t.forEach(a=>{if(a.type==="attributes"&&(a.attributeName==="style"||a.attributeName==="class")){const e=a.target;e.hasAttribute("data-modal-type")&&(console.log("🔍 Modal state changed:",{modalName:e.dataset.modalName,modalType:e.dataset.modalType,display:getComputedStyle(e).display,zIndex:getComputedStyle(e).zIndex}),this.updateDebugInfo())}})}).observe(document.body,{attributes:!0,subtree:!0,attributeFilter:["style","class"]})}}const s=new n;window.modalDebugger=s;s.isEnabled&&s.observeModalChanges();export{n as default};
