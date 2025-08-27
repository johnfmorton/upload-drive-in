var L=(i,e)=>()=>(e||i((e={exports:{}}).exports,e),e.exports);import{i as x,c as T,b as E,_ as l,d as y,r as I,n as c,S as $,z as w,g as b,o as h,A as q,w as R,s as A,H as j,L as B,p as k,q as S,v as _,l as C,x as v,y as F}from"./chunk.SBCFYC2S-DMThxOYY.js";var X=L((V,g)=>{var H=i=>{var e;const{activeElement:t}=document;t&&i.contains(t)&&((e=document.activeElement)==null||e.blur())},D=x`
  :host {
    display: inline-block;
    color: var(--sl-color-neutral-600);
  }

  .icon-button {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    background: none;
    border: none;
    border-radius: var(--sl-border-radius-medium);
    font-size: inherit;
    color: inherit;
    padding: var(--sl-spacing-x-small);
    cursor: pointer;
    transition: var(--sl-transition-x-fast) color;
    -webkit-appearance: none;
  }

  .icon-button:hover:not(.icon-button--disabled),
  .icon-button:focus-visible:not(.icon-button--disabled) {
    color: var(--sl-color-primary-600);
  }

  .icon-button:active:not(.icon-button--disabled) {
    color: var(--sl-color-primary-700);
  }

  .icon-button:focus {
    outline: none;
  }

  .icon-button--disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .icon-button:focus-visible {
    outline: var(--sl-focus-ring);
    outline-offset: var(--sl-focus-ring-offset);
  }

  .icon-button__icon {
    pointer-events: none;
  }
`,d=class extends ${constructor(){super(...arguments),this.hasFocus=!1,this.label="",this.disabled=!1}handleBlur(){this.hasFocus=!1,this.emit("sl-blur")}handleFocus(){this.hasFocus=!0,this.emit("sl-focus")}handleClick(i){this.disabled&&(i.preventDefault(),i.stopPropagation())}click(){this.button.click()}focus(i){this.button.focus(i)}blur(){this.button.blur()}render(){const i=!!this.href,e=i?w`a`:w`button`;return q`
      <${e}
        part="base"
        class=${b({"icon-button":!0,"icon-button--disabled":!i&&this.disabled,"icon-button--focused":this.hasFocus})}
        ?disabled=${h(i?void 0:this.disabled)}
        type=${h(i?void 0:"button")}
        href=${h(i?this.href:void 0)}
        target=${h(i?this.target:void 0)}
        download=${h(i?this.download:void 0)}
        rel=${h(i&&this.target?"noreferrer noopener":void 0)}
        role=${h(i?void 0:"button")}
        aria-disabled=${this.disabled?"true":"false"}
        aria-label="${this.label}"
        tabindex=${this.disabled?"-1":"0"}
        @blur=${this.handleBlur}
        @focus=${this.handleFocus}
        @click=${this.handleClick}
      >
        <sl-icon
          class="icon-button__icon"
          name=${h(this.name)}
          library=${h(this.library)}
          src=${h(this.src)}
          aria-hidden="true"
        ></sl-icon>
      </${e}>
    `}};d.styles=[T,D];d.dependencies={"sl-icon":E};l([y(".icon-button")],d.prototype,"button",2);l([I()],d.prototype,"hasFocus",2);l([c()],d.prototype,"name",2);l([c()],d.prototype,"library",2);l([c()],d.prototype,"src",2);l([c()],d.prototype,"href",2);l([c()],d.prototype,"target",2);l([c()],d.prototype,"download",2);l([c()],d.prototype,"label",2);l([c({type:Boolean,reflect:!0})],d.prototype,"disabled",2);var z=x`
  :host {
    display: contents;

    /* For better DX, we'll reset the margin here so the base part can inherit it */
    margin: 0;
  }

  .alert {
    position: relative;
    display: flex;
    align-items: stretch;
    background-color: var(--sl-panel-background-color);
    border: solid var(--sl-panel-border-width) var(--sl-panel-border-color);
    border-top-width: calc(var(--sl-panel-border-width) * 3);
    border-radius: var(--sl-border-radius-medium);
    font-family: var(--sl-font-sans);
    font-size: var(--sl-font-size-small);
    font-weight: var(--sl-font-weight-normal);
    line-height: 1.6;
    color: var(--sl-color-neutral-700);
    margin: inherit;
    overflow: hidden;
  }

  .alert:not(.alert--has-icon) .alert__icon,
  .alert:not(.alert--closable) .alert__close-button {
    display: none;
  }

  .alert__icon {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    font-size: var(--sl-font-size-large);
    padding-inline-start: var(--sl-spacing-large);
  }

  .alert--has-countdown {
    border-bottom: none;
  }

  .alert--primary {
    border-top-color: var(--sl-color-primary-600);
  }

  .alert--primary .alert__icon {
    color: var(--sl-color-primary-600);
  }

  .alert--success {
    border-top-color: var(--sl-color-success-600);
  }

  .alert--success .alert__icon {
    color: var(--sl-color-success-600);
  }

  .alert--neutral {
    border-top-color: var(--sl-color-neutral-600);
  }

  .alert--neutral .alert__icon {
    color: var(--sl-color-neutral-600);
  }

  .alert--warning {
    border-top-color: var(--sl-color-warning-600);
  }

  .alert--warning .alert__icon {
    color: var(--sl-color-warning-600);
  }

  .alert--danger {
    border-top-color: var(--sl-color-danger-600);
  }

  .alert--danger .alert__icon {
    color: var(--sl-color-danger-600);
  }

  .alert__message {
    flex: 1 1 auto;
    display: block;
    padding: var(--sl-spacing-large);
    overflow: hidden;
  }

  .alert__close-button {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    font-size: var(--sl-font-size-medium);
    margin-inline-end: var(--sl-spacing-medium);
    align-self: center;
  }

  .alert__countdown {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: calc(var(--sl-panel-border-width) * 3);
    background-color: var(--sl-panel-border-color);
    display: flex;
  }

  .alert__countdown--ltr {
    justify-content: flex-end;
  }

  .alert__countdown .alert__countdown-elapsed {
    height: 100%;
    width: 0;
  }

  .alert--primary .alert__countdown-elapsed {
    background-color: var(--sl-color-primary-600);
  }

  .alert--success .alert__countdown-elapsed {
    background-color: var(--sl-color-success-600);
  }

  .alert--neutral .alert__countdown-elapsed {
    background-color: var(--sl-color-neutral-600);
  }

  .alert--warning .alert__countdown-elapsed {
    background-color: var(--sl-color-warning-600);
  }

  .alert--danger .alert__countdown-elapsed {
    background-color: var(--sl-color-danger-600);
  }

  .alert__timer {
    display: none;
  }
`,u=class f extends ${constructor(){super(...arguments),this.hasSlotController=new j(this,"icon","suffix"),this.localize=new B(this),this.open=!1,this.closable=!1,this.variant="primary",this.duration=1/0,this.remainingTime=this.duration}static get toastStack(){return this.currentToastStack||(this.currentToastStack=Object.assign(document.createElement("div"),{className:"sl-toast-stack"})),this.currentToastStack}firstUpdated(){this.base.hidden=!this.open}restartAutoHide(){this.handleCountdownChange(),clearTimeout(this.autoHideTimeout),clearInterval(this.remainingTimeInterval),this.open&&this.duration<1/0&&(this.autoHideTimeout=window.setTimeout(()=>this.hide(),this.duration),this.remainingTime=this.duration,this.remainingTimeInterval=window.setInterval(()=>{this.remainingTime-=100},100))}pauseAutoHide(){var e;(e=this.countdownAnimation)==null||e.pause(),clearTimeout(this.autoHideTimeout),clearInterval(this.remainingTimeInterval)}resumeAutoHide(){var e;this.duration<1/0&&(this.autoHideTimeout=window.setTimeout(()=>this.hide(),this.remainingTime),this.remainingTimeInterval=window.setInterval(()=>{this.remainingTime-=100},100),(e=this.countdownAnimation)==null||e.play())}handleCountdownChange(){if(this.open&&this.duration<1/0&&this.countdown){const{countdownElement:e}=this,t="100%",r="0";this.countdownAnimation=e.animate([{width:t},{width:r}],{duration:this.duration,easing:"linear"})}}handleCloseClick(){this.hide()}async handleOpenChange(){if(this.open){this.emit("sl-show"),this.duration<1/0&&this.restartAutoHide(),await k(this.base),this.base.hidden=!1;const{keyframes:e,options:t}=S(this,"alert.show",{dir:this.localize.dir()});await _(this.base,e,t),this.emit("sl-after-show")}else{H(this),this.emit("sl-hide"),clearTimeout(this.autoHideTimeout),clearInterval(this.remainingTimeInterval),await k(this.base);const{keyframes:e,options:t}=S(this,"alert.hide",{dir:this.localize.dir()});await _(this.base,e,t),this.base.hidden=!0,this.emit("sl-after-hide")}}handleDurationChange(){this.restartAutoHide()}async show(){if(!this.open)return this.open=!0,C(this,"sl-after-show")}async hide(){if(this.open)return this.open=!1,C(this,"sl-after-hide")}async toast(){return new Promise(e=>{this.handleCountdownChange(),f.toastStack.parentElement===null&&document.body.append(f.toastStack),f.toastStack.appendChild(this),requestAnimationFrame(()=>{this.clientWidth,this.show()}),this.addEventListener("sl-after-hide",()=>{f.toastStack.removeChild(this),e(),f.toastStack.querySelector("sl-alert")===null&&f.toastStack.remove()},{once:!0})})}render(){return v`
      <div
        part="base"
        class=${b({alert:!0,"alert--open":this.open,"alert--closable":this.closable,"alert--has-countdown":!!this.countdown,"alert--has-icon":this.hasSlotController.test("icon"),"alert--primary":this.variant==="primary","alert--success":this.variant==="success","alert--neutral":this.variant==="neutral","alert--warning":this.variant==="warning","alert--danger":this.variant==="danger"})}
        role="alert"
        aria-hidden=${this.open?"false":"true"}
        @mouseenter=${this.pauseAutoHide}
        @mouseleave=${this.resumeAutoHide}
      >
        <div part="icon" class="alert__icon">
          <slot name="icon"></slot>
        </div>

        <div part="message" class="alert__message" aria-live="polite">
          <slot></slot>
        </div>

        ${this.closable?v`
              <sl-icon-button
                part="close-button"
                exportparts="base:close-button__base"
                class="alert__close-button"
                name="x-lg"
                library="system"
                label=${this.localize.term("close")}
                @click=${this.handleCloseClick}
              ></sl-icon-button>
            `:""}

        <div role="timer" class="alert__timer">${this.remainingTime}</div>

        ${this.countdown?v`
              <div
                class=${b({alert__countdown:!0,"alert__countdown--ltr":this.countdown==="ltr"})}
              >
                <div class="alert__countdown-elapsed"></div>
              </div>
            `:""}
      </div>
    `}};u.styles=[T,z];u.dependencies={"sl-icon-button":d};l([y('[part~="base"]')],u.prototype,"base",2);l([y(".alert__countdown-elapsed")],u.prototype,"countdownElement",2);l([c({type:Boolean,reflect:!0})],u.prototype,"open",2);l([c({type:Boolean,reflect:!0})],u.prototype,"closable",2);l([c({reflect:!0})],u.prototype,"variant",2);l([c({type:Number})],u.prototype,"duration",2);l([c({type:String,reflect:!0})],u.prototype,"countdown",2);l([I()],u.prototype,"remainingTime",2);l([R("open",{waitUntilFirstUpdate:!0})],u.prototype,"handleOpenChange",1);l([R("duration")],u.prototype,"handleDurationChange",1);var O=u;A("alert.show",{keyframes:[{opacity:0,scale:.8},{opacity:1,scale:1}],options:{duration:250,easing:"ease"}});A("alert.hide",{keyframes:[{opacity:1,scale:1},{opacity:0,scale:.8}],options:{duration:250,easing:"ease"}});O.define("sl-alert");E.define("sl-icon");F.define("sl-button");class M{constructor(e={}){this.statusSteps=["database","mail","google_drive","migrations","admin_user","queue_worker"],this.refreshInProgress=!1,this.retryAttempts=0,this.maxRetryAttempts=3,this.retryDelay=2e3,this.autoRefreshInterval=null,this.autoRefreshEnabled=!1,this.autoInit=e.autoInit!==!1,this.refreshAllStatuses=this.refreshAllStatuses.bind(this),this.refreshSingleStep=this.refreshSingleStep.bind(this),this.handleRefreshError=this.handleRefreshError.bind(this),this.retryRefresh=this.retryRefresh.bind(this),this.autoInit&&this.init()}init(){this.setupCSRFToken(),this.bindEventListeners(),this.setupKeyboardNavigation()}setupCSRFToken(){var e;if(!document.querySelector('meta[name="csrf-token"]')){const t=document.createElement("meta");t.name="csrf-token",t.content=((e=document.querySelector('meta[name="csrf-token"]'))==null?void 0:e.getAttribute("content"))||"",document.head.appendChild(t)}}bindEventListeners(){const e=document.getElementById("refresh-status-btn");e&&e.addEventListener("click",this.refreshAllStatuses),this.statusSteps.forEach(s=>{const o=document.getElementById(`refresh-${s}-btn`);o&&o.addEventListener("click",()=>{this.refreshSingleStep(s)})});const t=document.getElementById("auto-refresh-toggle");t&&t.addEventListener("change",s=>{this.toggleAutoRefresh(s.target.checked)}),document.addEventListener("click",s=>{s.target.classList.contains("retry-refresh-btn")&&this.retryRefresh()});const r=document.getElementById("test-queue-worker-btn");r&&r.addEventListener("click",()=>this.testQueueWorker())}setupKeyboardNavigation(){document.addEventListener("keydown",e=>{(e.ctrlKey||e.metaKey)&&e.key==="r"&&!this.refreshInProgress&&(e.preventDefault(),this.refreshAllStatuses())})}getCSRFToken(){var t;const e=(t=document.querySelector('meta[name="csrf-token"]'))==null?void 0:t.getAttribute("content");return e||console.warn("CSRF token not found"),e}async refreshAllStatuses(){var e;if(this.refreshInProgress){console.log("Refresh already in progress, skipping...");return}try{this.setLoadingState(!0),this.clearErrorMessages();const t=await this.makeAjaxRequest("/setup/status/refresh",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"}});if(!t.success)throw new Error(((e=t.error)==null?void 0:e.message)||"Failed to refresh status");this.updateAllStepStatuses(t.data.statuses),this.updateLastChecked(),this.resetRetryAttempts(),this.showSuccessMessage("Status refreshed successfully")}catch(t){console.error("Error refreshing all statuses:",t),this.handleRefreshError(t,"all")}finally{this.setLoadingState(!1)}}async refreshSingleStep(e){var t;if(this.refreshInProgress){console.log("Refresh already in progress, skipping...");return}if(!this.statusSteps.includes(e)){console.error("Invalid step name:",e);return}try{this.setSingleStepLoadingState(e,!0);const r=await this.makeAjaxRequest("/setup/status/refresh-step",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"},body:JSON.stringify({step:e})});if(!r.success)throw new Error(((t=r.error)==null?void 0:t.message)||`Failed to refresh ${e} status`);this.updateStatusIndicator(e,r.data.status.status,r.data.status.message,r.data.status.details||r.data.status.message),this.updateLastChecked(),this.showSuccessMessage(`${r.data.status.step_name} status refreshed`)}catch(r){console.error(`Error refreshing ${e} status:`,r),this.handleRefreshError(r,e)}finally{this.setSingleStepLoadingState(e,!1)}}async makeAjaxRequest(e,t={}){var o;const r=new AbortController,s=setTimeout(()=>r.abort(),3e4);try{const a=await fetch(e,{...t,signal:r.signal});if(clearTimeout(s),!a.ok){const n=await a.json().catch(()=>({}));throw new Error(((o=n.error)==null?void 0:o.message)||`HTTP ${a.status}: ${a.statusText}`)}return await a.json()}catch(a){throw clearTimeout(s),a.name==="AbortError"?new Error("Request timed out. Please check your connection and try again."):a}}updateAllStepStatuses(e){this.statusSteps.forEach(t=>{if(e&&e[t]){const r=e[t];this.updateStatusIndicator(t,r.status,r.message,r.details||r.message)}else console.warn(`No status data found for step: ${t}`),this.updateStatusIndicator(t,"error","No Data","Status information not available")})}updateStatusIndicator(e,t,r,s=null){const o=document.getElementById(`status-${e}`),a=document.getElementById(`status-${e}-text`),n=document.getElementById(`details-${e}-text`);if(!o||!a){console.error(`Could not find status elements for step: ${e}`);return}const p=["status-completed","status-incomplete","status-error","status-checking","status-cannot-verify","status-needs_attention"];o.classList.remove(...p),o.classList.add(`status-${t}`),this.animateTextChange(a,r),this.updateStatusIcon(o,t),n&&s&&this.updateStatusDetails(e,t,s),o.setAttribute("aria-label",`${e} status: ${r}`)}updateStatusIcon(e,t){console.log(`SetupStatusManager: Updating icon for status: ${t}`);let r=e.querySelector("svg"),s=!1;if(r||(r=e.querySelector(".status-emoji"),s=!0,console.log("SetupStatusManager: Found emoji icon element")),!r){console.log("SetupStatusManager: Creating new emoji icon element"),r=document.createElement("span"),r.className="status-emoji w-4 h-4 mr-1.5 text-base";const n=e.querySelector("span");n?e.insertBefore(r,n):e.appendChild(r),s=!0}const o={completed:"✅",incomplete:"❌",error:"🚫","cannot-verify":"❓",needs_attention:"⚠️",checking:"🔄"},a=o[t]||o.checking;if(console.log(`SetupStatusManager: Setting emoji to: ${a}`),s)r.textContent=a;else{const n=document.createElement("span");n.className="status-emoji w-4 h-4 mr-1.5 text-base",n.textContent=a,r.parentNode.replaceChild(n,r)}}animateTextChange(e,t){e.style.opacity="0.5",setTimeout(()=>{e.textContent=t,e.style.opacity="1"},150)}setLoadingState(e){this.refreshInProgress=e;const t=document.getElementById("refresh-status-btn"),r=document.getElementById("refresh-btn-text"),s=document.getElementById("refresh-spinner");t&&r&&s&&(t.disabled=e,r.textContent=e?"Checking...":"Check Status",e?s.classList.remove("hidden"):s.classList.add("hidden")),e&&this.statusSteps.forEach(o=>{this.updateStatusIndicator(o,"checking","Checking...","Verifying configuration...")})}setSingleStepLoadingState(e,t){t&&this.updateStatusIndicator(e,"checking","Checking...","Verifying configuration...")}handleRefreshError(e,t="all"){this.retryAttempts++,console.error(`Refresh error (attempt ${this.retryAttempts}):`,e),this.retryAttempts<this.maxRetryAttempts?(this.showRetryMessage(e.message,t),setTimeout(()=>{t==="all"?this.refreshAllStatuses():this.refreshSingleStep(t)},this.retryDelay*this.retryAttempts)):(this.showErrorState(e.message,t),this.resetRetryAttempts())}showRetryMessage(e,t){const r=`Failed to refresh status (${e}). Retrying in ${this.retryDelay*this.retryAttempts/1e3} seconds... (Attempt ${this.retryAttempts}/${this.maxRetryAttempts})`;this.showMessage(r,"warning")}showErrorState(e,t){t==="all"?this.statusSteps.forEach(r=>{this.updateStatusIndicator(r,"error","Check Failed","Unable to verify status. Please check your connection and try again.")}):this.updateStatusIndicator(t,"error","Check Failed","Unable to verify status. Please check your connection and try again."),this.showMessage(`Failed to refresh status: ${e}. Please check your connection and try again.`,"error",!0)}showMessage(e,t="info",r=!1){this.clearMessages();let s=document.getElementById("toast-container");s||(s=document.createElement("div"),s.id="toast-container",s.className="toast-container",document.body.appendChild(s));const o={success:"success",error:"danger",warning:"warning",info:"primary"},a=document.createElement("sl-alert");a.setAttribute("variant",o[t]||"primary"),a.setAttribute("closable","true"),a.className="status-message";let n=`
            <sl-icon slot="icon" name="${this.getIconName(t)}"></sl-icon>
            ${e}
        `;if(r&&(n+=`
                <sl-button slot="action" variant="text" size="small" class="retry-refresh-btn">
                    Retry Now
                </sl-button>
            `),a.innerHTML=n,s.appendChild(a),a.toast(),t==="success"&&setTimeout(()=>{a.parentNode&&a.hide()},5e3),r){const p=a.querySelector(".retry-refresh-btn");p&&p.addEventListener("click",()=>{a.hide(),this.retryRefresh()})}}getIconName(e){const t={success:"check-circle",error:"exclamation-triangle",warning:"exclamation-triangle",info:"info-circle"};return t[e]||t.info}showSuccessMessage(e){this.showMessage(e,"success")}showErrorMessage(e,t=!1){this.showMessage(e,"error",t)}clearMessages(){const e=document.getElementById("toast-container");e&&e.querySelectorAll("sl-alert").forEach(t=>{t.hide()})}clearErrorMessages(){const e=document.getElementById("toast-container");e&&e.querySelectorAll('sl-alert[variant="danger"]').forEach(t=>{t.hide()})}async testQueueWorker(){const e=document.getElementById("test-queue-worker-btn"),t=document.getElementById("test-queue-worker-btn-text"),r=document.getElementById("queue-test-results"),s=document.getElementById("queue-test-status");if(!e||!t||!r||!s){console.error("Queue test elements not found");return}try{e.disabled=!0,t.textContent="Testing...",r.classList.remove("hidden"),s.innerHTML=`
                <div class="flex items-center">
                    <svg class="animate-spin h-4 w-4 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-blue-700">Dispatching test job...</span>
                </div>
            `;const o=await this.makeAjaxRequest("/setup/queue/test",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"},body:JSON.stringify({delay:0})});if(o.success&&o.test_job_id)await this.pollQueueTestResult(o.test_job_id,s);else throw new Error(o.message||"Failed to dispatch test job")}catch(o){console.error("Queue test failed:",o),s.innerHTML=`
                <div class="flex items-center">
                    <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-red-700">Test failed: ${o.message}</span>
                </div>
            `}finally{e.disabled=!1,t.textContent="Test Queue Worker"}}async pollQueueTestResult(e,t){let s=0;const o=async()=>{s++;try{const a=await this.makeAjaxRequest(`/setup/queue/test/status?test_job_id=${e}`,{method:"GET",headers:{"X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"}});if(a.success&&a.status){const n=a.status;switch(n.status){case"completed":t.innerHTML=`
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-green-700">Queue worker is functioning properly! Job completed in ${(n.processing_time||0).toFixed(2)}s</span>
                                </div>
                            `;return;case"failed":t.innerHTML=`
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-red-700">Queue test failed: ${n.error_message||"Unknown error"}</span>
                                </div>
                            `;return;case"processing":t.innerHTML=`
                                <div class="flex items-center">
                                    <svg class="animate-spin h-4 w-4 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-blue-700">Test job is being processed... (${s}s)</span>
                                </div>
                            `;break;case"pending":t.innerHTML=`
                                <div class="flex items-center">
                                    <svg class="animate-pulse h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-yellow-700">Test job is queued, waiting for worker... (${s}s)</span>
                                </div>
                            `;break}(n.status==="processing"||n.status==="pending")&&(s<30?setTimeout(o,1e3):t.innerHTML=`
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-yellow-700">Test timed out after 30 seconds. The queue worker may not be running.</span>
                                </div>
                            `)}else throw new Error("Invalid response from server")}catch(a){console.error("Polling error:",a),t.innerHTML=`
                    <div class="flex items-center">
                        <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-red-700">Error checking test status: ${a.message}</span>
                    </div>
                `}};o()}updateLastChecked(){const e=document.getElementById("last-checked"),t=document.getElementById("last-checked-time");if(e&&t){const r=new Date;t.textContent=r.toLocaleTimeString(),e.classList.remove("hidden")}}resetRetryAttempts(){this.retryAttempts=0}retryRefresh(){this.resetRetryAttempts(),this.clearMessages(),this.refreshAllStatuses()}toggleAutoRefresh(e){this.autoRefreshEnabled=e,e?this.autoRefreshInterval=setInterval(()=>{this.refreshInProgress||this.refreshAllStatuses()},3e4):this.autoRefreshInterval&&(clearInterval(this.autoRefreshInterval),this.autoRefreshInterval=null)}updateStatusDetails(e,t,r){const s=document.getElementById(`details-${e}-text`);if(!s)return;let o="";if(typeof r=="object"){if(r.checked_at){const a=new Date(r.checked_at),n=this.getTimeAgo(a);o+=`<div class="mb-2"><strong>Last checked:</strong> ${n}</div>`}o+=this.getStatusSpecificDetails(e,t,r),(t==="incomplete"||t==="error"||t==="cannot_verify")&&(o+=this.getTroubleshootingGuidance(e,t,r))}else typeof r=="string"&&(o=`<div>${r}</div>`);s.innerHTML=o||"No additional details available."}getStatusSpecificDetails(e,t,r){let s="";switch(e){case"queue_worker":r.recent_jobs!==void 0&&(s+=`<div class="mb-2">
                        <strong>Queue Statistics:</strong>
                        <ul class="ml-4 mt-1 text-sm">
                            <li>Recent jobs (24h): ${r.recent_jobs}</li>
                            <li>Recent failed jobs: ${r.recent_failed_jobs||0}</li>
                            <li>Total failed jobs: ${r.total_failed_jobs||0}</li>
                            <li>Stalled jobs: ${r.stalled_jobs||0}</li>
                        </ul>
                    </div>`);break;case"database":r.connection_name&&(s+=`<div class="mb-2"><strong>Connection:</strong> ${r.connection_name}</div>`);break;case"mail":r.driver&&(s+=`<div class="mb-2"><strong>Mail driver:</strong> ${r.driver}</div>`);break;case"google_drive":r.client_id&&(s+='<div class="mb-2"><strong>Client ID configured:</strong> Yes</div>');break}return r.error&&(s+=`<div class="mb-2 p-2 bg-red-50 border border-red-200 rounded">
                <strong>Error:</strong> ${r.error}
            </div>`),s}getTroubleshootingGuidance(e,t,r){let s='<div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">';switch(t==="cannot_verify"?(s+="<strong>Cannot Verify - Manual Check Required:</strong>",s+='<p class="text-sm mt-1 mb-2">The system cannot automatically verify this step. Please check manually:</p>'):s+="<strong>Troubleshooting:</strong>",s+='<ul class="ml-4 mt-2 text-sm">',e){case"database":t==="cannot_verify"?(s+="<li>Check your database connection settings in .env file</li>",s+="<li>Ensure your database server is running</li>",s+="<li>Verify database credentials are correct</li>",s+="<li><strong>Manual verification:</strong> Try running <code>php artisan migrate:status</code></li>"):t==="incomplete"&&(s+="<li>Run database migrations: <code>php artisan migrate</code></li>",s+="<li>Check if all required tables exist</li>");break;case"mail":t==="cannot_verify"&&(s+="<li>Check mail configuration in .env file</li>",s+="<li>For local development, consider using log driver</li>",s+="<li>Verify SMTP credentials if using external mail service</li>",s+="<li><strong>Manual verification:</strong> Try sending a test email or check logs</li>");break;case"google_drive":t==="incomplete"&&(s+="<li>Set GOOGLE_DRIVE_CLIENT_ID in .env file</li>",s+="<li>Set GOOGLE_DRIVE_CLIENT_SECRET in .env file</li>",s+="<li>Complete OAuth setup in Google Cloud Console</li>");break;case"queue_worker":t==="cannot_verify"?(s+="<li>Start queue worker: <code>php artisan queue:work</code></li>",s+="<li>Check if queue tables exist in database</li>",s+="<li><strong>Manual verification:</strong> Use the test button above to verify functionality</li>",s+="<li>Check queue status with: <code>php artisan queue:monitor</code></li>"):t==="needs_attention"&&(s+="<li>Check failed jobs: <code>php artisan queue:failed</code></li>",s+="<li>Restart queue worker if needed</li>",s+="<li>Review application logs for errors</li>");break;case"admin_user":t==="incomplete"&&(s+="<li>Create admin user: <code>php artisan make:admin</code></li>",s+="<li>Or register through the web interface</li>");break;case"migrations":t==="incomplete"&&(s+="<li>Run migrations: <code>php artisan migrate</code></li>",s+="<li>Check database connection first</li>");break}return s+="</ul></div>",s}getTimeAgo(e){const r=new Date-e,s=Math.floor(r/1e3),o=Math.floor(s/60),a=Math.floor(o/60);return s<60?"Just now":o<60?`${o} minute${o!==1?"s":""} ago`:a<24?`${a} hour${a!==1?"s":""} ago`:e.toLocaleString()}toggleStatusDetails(e){const t=document.getElementById(`details-${e}`);t&&t.classList.toggle("show")}cleanup(){this.autoRefreshInterval&&clearInterval(this.autoRefreshInterval)}}let m;function P(i){m&&m.toggleStatusDetails(i)}window.toggleStatusDetails=P;document.addEventListener("DOMContentLoaded",function(){console.log("SetupStatusManager: DOM loaded, initializing..."),m=new M,console.log("SetupStatusManager: Instance created"),window.addEventListener("beforeunload",()=>{m&&m.cleanup()})});typeof g<"u"&&g.exports&&(g.exports=M)});export default X();
