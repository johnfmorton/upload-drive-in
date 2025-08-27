var L=(o,e)=>()=>(e||o((e={exports:{}}).exports,e),e.exports);import{i as C,c as $,b as T,_ as l,d as y,r as E,n as c,S as I,z as w,g as b,o as h,A as j,w as R,s as A,H as q,L as B,p as k,q as S,v as x,l as _,x as v,y as D}from"./chunk.SBCFYC2S-DMThxOYY.js";var X=L((U,f)=>{var H=o=>{var e;const{activeElement:t}=document;t&&o.contains(t)&&((e=document.activeElement)==null||e.blur())},F=C`
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
`,d=class extends I{constructor(){super(...arguments),this.hasFocus=!1,this.label="",this.disabled=!1}handleBlur(){this.hasFocus=!1,this.emit("sl-blur")}handleFocus(){this.hasFocus=!0,this.emit("sl-focus")}handleClick(o){this.disabled&&(o.preventDefault(),o.stopPropagation())}click(){this.button.click()}focus(o){this.button.focus(o)}blur(){this.button.blur()}render(){const o=!!this.href,e=o?w`a`:w`button`;return j`
      <${e}
        part="base"
        class=${b({"icon-button":!0,"icon-button--disabled":!o&&this.disabled,"icon-button--focused":this.hasFocus})}
        ?disabled=${h(o?void 0:this.disabled)}
        type=${h(o?void 0:"button")}
        href=${h(o?this.href:void 0)}
        target=${h(o?this.target:void 0)}
        download=${h(o?this.download:void 0)}
        rel=${h(o&&this.target?"noreferrer noopener":void 0)}
        role=${h(o?void 0:"button")}
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
    `}};d.styles=[$,F];d.dependencies={"sl-icon":T};l([y(".icon-button")],d.prototype,"button",2);l([E()],d.prototype,"hasFocus",2);l([c()],d.prototype,"name",2);l([c()],d.prototype,"library",2);l([c()],d.prototype,"src",2);l([c()],d.prototype,"href",2);l([c()],d.prototype,"target",2);l([c()],d.prototype,"download",2);l([c()],d.prototype,"label",2);l([c({type:Boolean,reflect:!0})],d.prototype,"disabled",2);var z=C`
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
`,u=class m extends I{constructor(){super(...arguments),this.hasSlotController=new q(this,"icon","suffix"),this.localize=new B(this),this.open=!1,this.closable=!1,this.variant="primary",this.duration=1/0,this.remainingTime=this.duration}static get toastStack(){return this.currentToastStack||(this.currentToastStack=Object.assign(document.createElement("div"),{className:"sl-toast-stack"})),this.currentToastStack}firstUpdated(){this.base.hidden=!this.open}restartAutoHide(){this.handleCountdownChange(),clearTimeout(this.autoHideTimeout),clearInterval(this.remainingTimeInterval),this.open&&this.duration<1/0&&(this.autoHideTimeout=window.setTimeout(()=>this.hide(),this.duration),this.remainingTime=this.duration,this.remainingTimeInterval=window.setInterval(()=>{this.remainingTime-=100},100))}pauseAutoHide(){var e;(e=this.countdownAnimation)==null||e.pause(),clearTimeout(this.autoHideTimeout),clearInterval(this.remainingTimeInterval)}resumeAutoHide(){var e;this.duration<1/0&&(this.autoHideTimeout=window.setTimeout(()=>this.hide(),this.remainingTime),this.remainingTimeInterval=window.setInterval(()=>{this.remainingTime-=100},100),(e=this.countdownAnimation)==null||e.play())}handleCountdownChange(){if(this.open&&this.duration<1/0&&this.countdown){const{countdownElement:e}=this,t="100%",r="0";this.countdownAnimation=e.animate([{width:t},{width:r}],{duration:this.duration,easing:"linear"})}}handleCloseClick(){this.hide()}async handleOpenChange(){if(this.open){this.emit("sl-show"),this.duration<1/0&&this.restartAutoHide(),await k(this.base),this.base.hidden=!1;const{keyframes:e,options:t}=S(this,"alert.show",{dir:this.localize.dir()});await x(this.base,e,t),this.emit("sl-after-show")}else{H(this),this.emit("sl-hide"),clearTimeout(this.autoHideTimeout),clearInterval(this.remainingTimeInterval),await k(this.base);const{keyframes:e,options:t}=S(this,"alert.hide",{dir:this.localize.dir()});await x(this.base,e,t),this.base.hidden=!0,this.emit("sl-after-hide")}}handleDurationChange(){this.restartAutoHide()}async show(){if(!this.open)return this.open=!0,_(this,"sl-after-show")}async hide(){if(this.open)return this.open=!1,_(this,"sl-after-hide")}async toast(){return new Promise(e=>{this.handleCountdownChange(),m.toastStack.parentElement===null&&document.body.append(m.toastStack),m.toastStack.appendChild(this),requestAnimationFrame(()=>{this.clientWidth,this.show()}),this.addEventListener("sl-after-hide",()=>{m.toastStack.removeChild(this),e(),m.toastStack.querySelector("sl-alert")===null&&m.toastStack.remove()},{once:!0})})}render(){return v`
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
    `}};u.styles=[$,z];u.dependencies={"sl-icon-button":d};l([y('[part~="base"]')],u.prototype,"base",2);l([y(".alert__countdown-elapsed")],u.prototype,"countdownElement",2);l([c({type:Boolean,reflect:!0})],u.prototype,"open",2);l([c({type:Boolean,reflect:!0})],u.prototype,"closable",2);l([c({reflect:!0})],u.prototype,"variant",2);l([c({type:Number})],u.prototype,"duration",2);l([c({type:String,reflect:!0})],u.prototype,"countdown",2);l([E()],u.prototype,"remainingTime",2);l([R("open",{waitUntilFirstUpdate:!0})],u.prototype,"handleOpenChange",1);l([R("duration")],u.prototype,"handleDurationChange",1);var O=u;A("alert.show",{keyframes:[{opacity:0,scale:.8},{opacity:1,scale:1}],options:{duration:250,easing:"ease"}});A("alert.hide",{keyframes:[{opacity:1,scale:1},{opacity:0,scale:.8}],options:{duration:250,easing:"ease"}});O.define("sl-alert");T.define("sl-icon");D.define("sl-button");class M{constructor(e={}){this.statusSteps=["database","mail","google_drive","migrations","admin_user","queue_worker"],this.refreshInProgress=!1,this.retryAttempts=0,this.maxRetryAttempts=3,this.retryDelay=2e3,this.autoRefreshInterval=null,this.autoRefreshEnabled=!1,this.autoInit=e.autoInit!==!1,this.refreshAllStatuses=this.refreshAllStatuses.bind(this),this.refreshSingleStep=this.refreshSingleStep.bind(this),this.handleRefreshError=this.handleRefreshError.bind(this),this.retryRefresh=this.retryRefresh.bind(this),this.autoInit&&this.init()}init(){this.setupCSRFToken(),this.bindEventListeners(),this.setupKeyboardNavigation()}setupCSRFToken(){var e;if(!document.querySelector('meta[name="csrf-token"]')){const t=document.createElement("meta");t.name="csrf-token",t.content=((e=document.querySelector('meta[name="csrf-token"]'))==null?void 0:e.getAttribute("content"))||"",document.head.appendChild(t)}}bindEventListeners(){const e=document.getElementById("refresh-status-btn");e&&e.addEventListener("click",this.refreshAllStatuses),this.statusSteps.forEach(s=>{const a=document.getElementById(`refresh-${s}-btn`);a&&a.addEventListener("click",()=>{this.refreshSingleStep(s)})});const t=document.getElementById("auto-refresh-toggle");t&&t.addEventListener("change",s=>{this.toggleAutoRefresh(s.target.checked)}),document.addEventListener("click",s=>{s.target.classList.contains("retry-refresh-btn")&&this.retryRefresh()});const r=document.getElementById("test-queue-worker-btn");r&&r.addEventListener("click",()=>this.testQueueWorker())}setupKeyboardNavigation(){document.addEventListener("keydown",e=>{(e.ctrlKey||e.metaKey)&&e.key==="r"&&!this.refreshInProgress&&(e.preventDefault(),this.refreshAllStatuses())})}getCSRFToken(){var t;const e=(t=document.querySelector('meta[name="csrf-token"]'))==null?void 0:t.getAttribute("content");return e||console.warn("CSRF token not found"),e}async refreshAllStatuses(){var e;if(this.refreshInProgress){console.log("Refresh already in progress, skipping...");return}try{this.setLoadingState(!0),this.clearErrorMessages();const t=await this.makeAjaxRequest("/setup/status/refresh",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"}});if(!t.success)throw new Error(((e=t.error)==null?void 0:e.message)||"Failed to refresh status");this.updateAllStepStatuses(t.data.statuses),this.updateLastChecked(),this.resetRetryAttempts(),this.showSuccessMessage("Status refreshed successfully")}catch(t){console.error("Error refreshing all statuses:",t),this.handleRefreshError(t,"all")}finally{this.setLoadingState(!1)}}async refreshSingleStep(e){var t;if(this.refreshInProgress){console.log("Refresh already in progress, skipping...");return}if(!this.statusSteps.includes(e)){console.error("Invalid step name:",e);return}try{this.setSingleStepLoadingState(e,!0);const r=await this.makeAjaxRequest("/setup/status/refresh-step",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"},body:JSON.stringify({step:e})});if(!r.success)throw new Error(((t=r.error)==null?void 0:t.message)||`Failed to refresh ${e} status`);this.updateStatusIndicator(e,r.data.status.status,r.data.status.message,r.data.status.details||r.data.status.message),this.updateLastChecked(),this.showSuccessMessage(`${r.data.status.step_name} status refreshed`)}catch(r){console.error(`Error refreshing ${e} status:`,r),this.handleRefreshError(r,e)}finally{this.setSingleStepLoadingState(e,!1)}}async makeAjaxRequest(e,t={}){var a;const r=new AbortController,s=setTimeout(()=>r.abort(),3e4);try{const i=await fetch(e,{...t,signal:r.signal});if(clearTimeout(s),!i.ok){const n=await i.json().catch(()=>({}));throw new Error(((a=n.error)==null?void 0:a.message)||`HTTP ${i.status}: ${i.statusText}`)}return await i.json()}catch(i){throw clearTimeout(s),i.name==="AbortError"?new Error("Request timed out. Please check your connection and try again."):i}}updateAllStepStatuses(e){this.statusSteps.forEach(t=>{if(e&&e[t]){const r=e[t];this.updateStatusIndicator(t,r.status,r.message,r.details||r.message)}else console.warn(`No status data found for step: ${t}`),this.updateStatusIndicator(t,"error","No Data","Status information not available")})}updateStatusIndicator(e,t,r,s=null){const a=document.getElementById(`status-${e}`),i=document.getElementById(`status-${e}-text`),n=document.getElementById(`details-${e}-text`);if(!a||!i){console.error(`Could not find status elements for step: ${e}`);return}const g=["status-completed","status-working","status-idle","status-incomplete","status-error","status-checking","status-cannot-verify","status-needs_attention"];a.classList.remove(...g),a.classList.add(`status-${t}`),this.animateTextChange(i,r),this.updateStatusIcon(a,t),n&&s&&this.updateStatusDetails(e,t,s),a.setAttribute("aria-label",`${e} status: ${r}`)}updateStatusIcon(e,t){console.log(`SetupStatusManager: Updating icon for status: ${t}`);let r=e.querySelector("svg"),s=!1;if(r||(r=e.querySelector(".status-emoji"),s=!0,console.log("SetupStatusManager: Found emoji icon element")),!r){console.log("SetupStatusManager: Creating new emoji icon element"),r=document.createElement("span"),r.className="status-emoji w-4 h-4 mr-1.5 text-base";const n=e.querySelector("span");n?e.insertBefore(r,n):e.appendChild(r),s=!0}const a={completed:"✅",working:"✅",idle:"✅",incomplete:"❌",error:"🚫","cannot-verify":"❓",needs_attention:"⚠️",checking:"🔄"},i=a[t]||a.checking;if(console.log(`SetupStatusManager: Setting emoji to: ${i}`),s)r.textContent=i;else{const n=document.createElement("span");n.className="status-emoji w-4 h-4 mr-1.5 text-base",n.textContent=i,r.parentNode.replaceChild(n,r)}}animateTextChange(e,t){e.style.opacity="0.5",setTimeout(()=>{e.textContent=t,e.style.opacity="1"},150)}setLoadingState(e){this.refreshInProgress=e;const t=document.getElementById("refresh-status-btn"),r=document.getElementById("refresh-btn-text"),s=document.getElementById("refresh-spinner");t&&r&&s&&(t.disabled=e,r.textContent=e?"Checking...":"Check Status",e?s.classList.remove("hidden"):s.classList.add("hidden")),e&&this.statusSteps.forEach(a=>{this.updateStatusIndicator(a,"checking","Checking...","Verifying configuration...")})}setSingleStepLoadingState(e,t){t&&this.updateStatusIndicator(e,"checking","Checking...","Verifying configuration...")}handleRefreshError(e,t="all"){this.retryAttempts++,console.error(`Refresh error (attempt ${this.retryAttempts}):`,e),this.retryAttempts<this.maxRetryAttempts?(this.showRetryMessage(e.message,t),setTimeout(()=>{t==="all"?this.refreshAllStatuses():this.refreshSingleStep(t)},this.retryDelay*this.retryAttempts)):(this.showErrorState(e.message,t),this.resetRetryAttempts())}showRetryMessage(e,t){const r=`Failed to refresh status (${e}). Retrying in ${this.retryDelay*this.retryAttempts/1e3} seconds... (Attempt ${this.retryAttempts}/${this.maxRetryAttempts})`;this.showMessage(r,"warning")}showErrorState(e,t){t==="all"?this.statusSteps.forEach(r=>{this.updateStatusIndicator(r,"error","Check Failed","Unable to verify status. Please check your connection and try again.")}):this.updateStatusIndicator(t,"error","Check Failed","Unable to verify status. Please check your connection and try again."),this.showMessage(`Failed to refresh status: ${e}. Please check your connection and try again.`,"error",!0)}showMessage(e,t="info",r=!1){this.clearMessages();let s=document.getElementById("toast-container");s||(s=document.createElement("div"),s.id="toast-container",s.className="toast-container",document.body.appendChild(s));const a={success:"success",error:"danger",warning:"warning",info:"primary"},i=document.createElement("sl-alert");i.setAttribute("variant",a[t]||"primary"),i.setAttribute("closable","true"),i.className="status-message";let n=`
            <sl-icon slot="icon" name="${this.getIconName(t)}"></sl-icon>
            ${e}
        `;if(r&&(n+=`
                <sl-button slot="action" variant="text" size="small" class="retry-refresh-btn">
                    Retry Now
                </sl-button>
            `),i.innerHTML=n,s.appendChild(i),i.toast(),t==="success"&&setTimeout(()=>{i.parentNode&&i.hide()},5e3),r){const g=i.querySelector(".retry-refresh-btn");g&&g.addEventListener("click",()=>{i.hide(),this.retryRefresh()})}}getIconName(e){const t={success:"check-circle",error:"exclamation-triangle",warning:"exclamation-triangle",info:"info-circle"};return t[e]||t.info}showSuccessMessage(e){this.showMessage(e,"success")}showErrorMessage(e,t=!1){this.showMessage(e,"error",t)}clearMessages(){const e=document.getElementById("toast-container");e&&e.querySelectorAll("sl-alert").forEach(t=>{t.hide()})}clearErrorMessages(){const e=document.getElementById("toast-container");e&&e.querySelectorAll('sl-alert[variant="danger"]').forEach(t=>{t.hide()})}async testQueueWorker(){const e=document.getElementById("test-queue-worker-btn"),t=document.getElementById("test-queue-worker-btn-text"),r=document.getElementById("queue-test-results"),s=document.getElementById("queue-test-status");if(!e||!t||!r||!s){console.error("Queue test elements not found");return}try{e.disabled=!0,t.textContent="Testing...",r.classList.remove("hidden"),s.innerHTML=`
                <div class="flex items-center">
                    <svg class="animate-spin h-4 w-4 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-blue-700">Dispatching test job...</span>
                </div>
            `;const a=await this.makeAjaxRequest("/setup/queue/test",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"},body:JSON.stringify({delay:0})});if(a.success&&a.test_job_id)await this.pollQueueTestResult(a.test_job_id,s);else throw new Error(a.message||"Failed to dispatch test job")}catch(a){console.error("Queue test failed:",a),s.innerHTML=`
                <div class="flex items-center">
                    <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-red-700">Test failed: ${a.message}</span>
                </div>
            `}finally{e.disabled=!1,t.textContent="Test Queue Worker"}}async pollQueueTestResult(e,t){let s=0;const a=async()=>{s++;try{const i=await this.makeAjaxRequest(`/setup/queue/test/status?test_job_id=${e}`,{method:"GET",headers:{"X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"}});if(i.success&&i.status){const n=i.status;switch(n.status){case"completed":t.innerHTML=`
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
                            `;break}(n.status==="processing"||n.status==="pending")&&(s<30?setTimeout(a,1e3):t.innerHTML=`
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-yellow-700">Test timed out after 30 seconds. The queue worker may not be running.</span>
                                </div>
                            `)}else throw new Error("Invalid response from server")}catch(i){console.error("Polling error:",i),t.innerHTML=`
                    <div class="flex items-center">
                        <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-red-700">Error checking test status: ${i.message}</span>
                    </div>
                `}};a()}updateLastChecked(){const e=document.getElementById("last-checked"),t=document.getElementById("last-checked-time");if(e&&t){const r=new Date;t.textContent=r.toLocaleTimeString(),e.classList.remove("hidden")}}resetRetryAttempts(){this.retryAttempts=0}retryRefresh(){this.resetRetryAttempts(),this.clearMessages(),this.refreshAllStatuses()}toggleAutoRefresh(e){this.autoRefreshEnabled=e,e?this.autoRefreshInterval=setInterval(()=>{this.refreshInProgress||this.refreshAllStatuses()},3e4):this.autoRefreshInterval&&(clearInterval(this.autoRefreshInterval),this.autoRefreshInterval=null)}updateStatusDetails(e,t,r){const s=document.getElementById(`details-${e}-text`);if(!s)return;let a="";if(typeof r=="object"){if(r.checked_at){const i=new Date(r.checked_at),n=this.getTimeAgo(i);a+=`<div class="mb-2"><strong>Last checked:</strong> ${n}</div>`}a+=this.getStatusSpecificDetails(e,t,r),(t==="incomplete"||t==="error"||t==="cannot_verify")&&(a+=this.getTroubleshootingGuidance(e,t,r))}else typeof r=="string"&&(a=`<div>${r}</div>`);s.innerHTML=a||"No additional details available."}getStatusSpecificDetails(e,t,r){let s="";switch(e){case"queue_worker":r.recent_jobs!==void 0&&(s+=`<div class="mb-2">
                        <strong>Queue Statistics:</strong>
                        <ul class="ml-4 mt-1 text-sm">
                            <li>Recent jobs (24h): ${r.recent_jobs}</li>
                            <li>Recent failed jobs: ${r.recent_failed_jobs||0}</li>
                            <li>Total failed jobs: ${r.total_failed_jobs||0}</li>
                            <li>Stalled jobs: ${r.stalled_jobs||0}</li>
                        </ul>
                    </div>`);break;case"database":r.scenario?s+=this.getDatabaseStatusDetails(t,r):r.connection_name&&(s+=`<div class="mb-2"><strong>Connection:</strong> ${r.connection_name}</div>`);break;case"mail":r.driver&&(s+=`<div class="mb-2"><strong>Mail driver:</strong> ${r.driver}</div>`);break;case"google_drive":r.client_id&&(s+='<div class="mb-2"><strong>Client ID configured:</strong> Yes</div>');break}return r.error&&(s+=`<div class="mb-2 p-2 bg-red-50 border border-red-200 rounded">
                <strong>Error:</strong> ${r.error}
            </div>`),s}getDatabaseStatusDetails(e,t){const r=t.scenario;let s="";switch(r){case"no_credentials":s+=`
                    <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded">
                        <div class="flex items-start">
                            <span class="text-red-600 text-lg mr-2">❌</span>
                            <div>
                                <h4 class="text-sm font-medium text-red-800">No Database Credentials</h4>
                                <p class="mt-1 text-sm text-red-700">${t.description}</p>
                                <div class="mt-2">
                                    <p class="text-sm font-medium text-red-800">Missing fields:</p>
                                    <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                                        ${t.metadata.missing_fields.map(a=>`<li>${a}</li>`).join("")}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                `;break;case"partial_credentials":s+=`
                    <div class="mb-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
                        <div class="flex items-start">
                            <span class="text-yellow-600 text-lg mr-2">⚠️</span>
                            <div>
                                <h4 class="text-sm font-medium text-yellow-800">Partial Database Configuration</h4>
                                <p class="mt-1 text-sm text-yellow-700">${t.description}</p>
                                <div class="mt-2 grid grid-cols-1 gap-2">
                                    <div>
                                        <p class="text-sm font-medium text-yellow-800">Missing fields:</p>
                                        <ul class="mt-1 text-sm text-yellow-700 list-disc list-inside">
                                            ${t.metadata.missing_fields.map(a=>`<li>${a}</li>`).join("")}
                                        </ul>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-yellow-800">Configured fields:</p>
                                        <ul class="mt-1 text-sm text-yellow-700 list-disc list-inside">
                                            ${t.metadata.configured_fields.map(a=>`<li>${a}</li>`).join("")}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;break;case"connection_failed":s+=`
                    <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded">
                        <div class="flex items-start">
                            <span class="text-red-600 text-lg mr-2">🚫</span>
                            <div>
                                <h4 class="text-sm font-medium text-red-800">Database Connection Failed</h4>
                                <p class="mt-1 text-sm text-red-700">${t.description}</p>
                                <div class="mt-2">
                                    <p class="text-sm font-medium text-red-800">Connection details:</p>
                                    <ul class="mt-1 text-sm text-red-700 space-y-1">
                                        <li><strong>Type:</strong> ${t.metadata.connection_type}</li>
                                        <li><strong>Host:</strong> ${t.metadata.host}</li>
                                        <li><strong>Database:</strong> ${t.metadata.database}</li>
                                        <li><strong>Username:</strong> ${t.metadata.username}</li>
                                    </ul>
                                    ${t.metadata.error_message?`
                                        <div class="mt-2 p-2 bg-red-100 rounded text-xs text-red-800">
                                            <strong>Error:</strong> ${t.metadata.error_message}
                                        </div>
                                    `:""}
                                </div>
                            </div>
                        </div>
                    </div>
                `;break;case"connection_successful":s+=`
                    <div class="mb-3 p-3 bg-green-50 border border-green-200 rounded">
                        <div class="flex items-start">
                            <span class="text-green-600 text-lg mr-2">✅</span>
                            <div>
                                <h4 class="text-sm font-medium text-green-800">Database Connection Successful</h4>
                                <p class="mt-1 text-sm text-green-700">${t.description}</p>
                                <div class="mt-2">
                                    <p class="text-sm font-medium text-green-800">Connection details:</p>
                                    <ul class="mt-1 text-sm text-green-700 space-y-1">
                                        <li><strong>Type:</strong> ${t.metadata.connection_type}</li>
                                        <li><strong>Host:</strong> ${t.metadata.host}</li>
                                        <li><strong>Database:</strong> ${t.metadata.database}</li>
                                        <li><strong>Username:</strong> ${t.metadata.username}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                `;break;default:s+=`<div class="mb-2"><strong>Status:</strong> ${t.description||"Database status information available"}</div>`}return s}getTroubleshootingGuidance(e,t,r){let s='<div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">';switch(t==="cannot_verify"?(s+="<strong>Cannot Verify - Manual Check Required:</strong>",s+='<p class="text-sm mt-1 mb-2">The system cannot automatically verify this step. Please check manually:</p>'):s+="<strong>Troubleshooting:</strong>",s+='<ul class="ml-4 mt-2 text-sm">',e){case"database":t==="cannot_verify"?(s+="<li>Check your database connection settings in .env file</li>",s+="<li>Ensure your database server is running</li>",s+="<li>Verify database credentials are correct</li>",s+="<li><strong>Manual verification:</strong> Try running <code>php artisan migrate:status</code></li>"):t==="incomplete"&&(s+="<li>Run database migrations: <code>php artisan migrate</code></li>",s+="<li>Check if all required tables exist</li>");break;case"mail":t==="cannot_verify"&&(s+="<li>Check mail configuration in .env file</li>",s+="<li>For local development, consider using log driver</li>",s+="<li>Verify SMTP credentials if using external mail service</li>",s+="<li><strong>Manual verification:</strong> Try sending a test email or check logs</li>");break;case"google_drive":t==="incomplete"&&(s+="<li>Set GOOGLE_DRIVE_CLIENT_ID in .env file</li>",s+="<li>Set GOOGLE_DRIVE_CLIENT_SECRET in .env file</li>",s+="<li>Complete OAuth setup in Google Cloud Console</li>");break;case"queue_worker":t==="cannot_verify"?(s+="<li>Start queue worker: <code>php artisan queue:work</code></li>",s+="<li>Check if queue tables exist in database</li>",s+="<li><strong>Manual verification:</strong> Use the test button above to verify functionality</li>",s+="<li>Check queue status with: <code>php artisan queue:monitor</code></li>"):t==="needs_attention"&&(s+="<li>Check failed jobs: <code>php artisan queue:failed</code></li>",s+="<li>Restart queue worker if needed</li>",s+="<li>Review application logs for errors</li>");break;case"admin_user":t==="incomplete"&&(s+="<li>Create admin user: <code>php artisan make:admin</code></li>",s+="<li>Or register through the web interface</li>");break;case"migrations":t==="incomplete"&&(s+="<li>Run migrations: <code>php artisan migrate</code></li>",s+="<li>Check database connection first</li>");break}return s+="</ul></div>",s}getTimeAgo(e){const r=new Date-e,s=Math.floor(r/1e3),a=Math.floor(s/60),i=Math.floor(a/60);return s<60?"Just now":a<60?`${a} minute${a!==1?"s":""} ago`:i<24?`${i} hour${i!==1?"s":""} ago`:e.toLocaleString()}toggleStatusDetails(e){const t=document.getElementById(`details-${e}`);t&&t.classList.toggle("show")}cleanup(){this.autoRefreshInterval&&clearInterval(this.autoRefreshInterval)}}let p;function P(o){p&&p.toggleStatusDetails(o)}window.toggleStatusDetails=P;document.addEventListener("DOMContentLoaded",function(){console.log("SetupStatusManager: DOM loaded, initializing..."),p=new M,console.log("SetupStatusManager: Instance created"),window.addEventListener("beforeunload",()=>{p&&p.cleanup()})});typeof f<"u"&&f.exports&&(f.exports=M)});export default X();
