var j=(u,e)=>()=>(e||u((e={exports:{}}).exports,e),e.exports);import{i as _,c as I,b as R,_ as c,d as T,r as $,n as h,S as D,z as x,g as v,o as f,A as M,w as Q,s as B,H as A,L as P,p as S,q,v as C,l as E,x as y,y as H}from"./chunk.SBCFYC2S-DMThxOYY.js";var X=j((J,b)=>{var F=u=>{var e;const{activeElement:t}=document;t&&u.contains(t)&&((e=document.activeElement)==null||e.blur())},N=_`
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
`,m=class extends D{constructor(){super(...arguments),this.hasFocus=!1,this.label="",this.disabled=!1}handleBlur(){this.hasFocus=!1,this.emit("sl-blur")}handleFocus(){this.hasFocus=!0,this.emit("sl-focus")}handleClick(u){this.disabled&&(u.preventDefault(),u.stopPropagation())}click(){this.button.click()}focus(u){this.button.focus(u)}blur(){this.button.blur()}render(){const u=!!this.href,e=u?x`a`:x`button`;return M`
      <${e}
        part="base"
        class=${v({"icon-button":!0,"icon-button--disabled":!u&&this.disabled,"icon-button--focused":this.hasFocus})}
        ?disabled=${f(u?void 0:this.disabled)}
        type=${f(u?void 0:"button")}
        href=${f(u?this.href:void 0)}
        target=${f(u?this.target:void 0)}
        download=${f(u?this.download:void 0)}
        rel=${f(u&&this.target?"noreferrer noopener":void 0)}
        role=${f(u?void 0:"button")}
        aria-disabled=${this.disabled?"true":"false"}
        aria-label="${this.label}"
        tabindex=${this.disabled?"-1":"0"}
        @blur=${this.handleBlur}
        @focus=${this.handleFocus}
        @click=${this.handleClick}
      >
        <sl-icon
          class="icon-button__icon"
          name=${f(this.name)}
          library=${f(this.library)}
          src=${f(this.src)}
          aria-hidden="true"
        ></sl-icon>
      </${e}>
    `}};m.styles=[I,N];m.dependencies={"sl-icon":R};c([T(".icon-button")],m.prototype,"button",2);c([$()],m.prototype,"hasFocus",2);c([h()],m.prototype,"name",2);c([h()],m.prototype,"library",2);c([h()],m.prototype,"src",2);c([h()],m.prototype,"href",2);c([h()],m.prototype,"target",2);c([h()],m.prototype,"download",2);c([h()],m.prototype,"label",2);c([h({type:Boolean,reflect:!0})],m.prototype,"disabled",2);var O=_`
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
`,g=class k extends D{constructor(){super(...arguments),this.hasSlotController=new A(this,"icon","suffix"),this.localize=new P(this),this.open=!1,this.closable=!1,this.variant="primary",this.duration=1/0,this.remainingTime=this.duration}static get toastStack(){return this.currentToastStack||(this.currentToastStack=Object.assign(document.createElement("div"),{className:"sl-toast-stack"})),this.currentToastStack}firstUpdated(){this.base.hidden=!this.open}restartAutoHide(){this.handleCountdownChange(),clearTimeout(this.autoHideTimeout),clearInterval(this.remainingTimeInterval),this.open&&this.duration<1/0&&(this.autoHideTimeout=window.setTimeout(()=>this.hide(),this.duration),this.remainingTime=this.duration,this.remainingTimeInterval=window.setInterval(()=>{this.remainingTime-=100},100))}pauseAutoHide(){var e;(e=this.countdownAnimation)==null||e.pause(),clearTimeout(this.autoHideTimeout),clearInterval(this.remainingTimeInterval)}resumeAutoHide(){var e;this.duration<1/0&&(this.autoHideTimeout=window.setTimeout(()=>this.hide(),this.remainingTime),this.remainingTimeInterval=window.setInterval(()=>{this.remainingTime-=100},100),(e=this.countdownAnimation)==null||e.play())}handleCountdownChange(){if(this.open&&this.duration<1/0&&this.countdown){const{countdownElement:e}=this,t="100%",r="0";this.countdownAnimation=e.animate([{width:t},{width:r}],{duration:this.duration,easing:"linear"})}}handleCloseClick(){this.hide()}async handleOpenChange(){if(this.open){this.emit("sl-show"),this.duration<1/0&&this.restartAutoHide(),await S(this.base),this.base.hidden=!1;const{keyframes:e,options:t}=q(this,"alert.show",{dir:this.localize.dir()});await C(this.base,e,t),this.emit("sl-after-show")}else{F(this),this.emit("sl-hide"),clearTimeout(this.autoHideTimeout),clearInterval(this.remainingTimeInterval),await S(this.base);const{keyframes:e,options:t}=q(this,"alert.hide",{dir:this.localize.dir()});await C(this.base,e,t),this.base.hidden=!0,this.emit("sl-after-hide")}}handleDurationChange(){this.restartAutoHide()}async show(){if(!this.open)return this.open=!0,E(this,"sl-after-show")}async hide(){if(this.open)return this.open=!1,E(this,"sl-after-hide")}async toast(){return new Promise(e=>{this.handleCountdownChange(),k.toastStack.parentElement===null&&document.body.append(k.toastStack),k.toastStack.appendChild(this),requestAnimationFrame(()=>{this.clientWidth,this.show()}),this.addEventListener("sl-after-hide",()=>{k.toastStack.removeChild(this),e(),k.toastStack.querySelector("sl-alert")===null&&k.toastStack.remove()},{once:!0})})}render(){return y`
      <div
        part="base"
        class=${v({alert:!0,"alert--open":this.open,"alert--closable":this.closable,"alert--has-countdown":!!this.countdown,"alert--has-icon":this.hasSlotController.test("icon"),"alert--primary":this.variant==="primary","alert--success":this.variant==="success","alert--neutral":this.variant==="neutral","alert--warning":this.variant==="warning","alert--danger":this.variant==="danger"})}
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

        ${this.closable?y`
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

        ${this.countdown?y`
              <div
                class=${v({alert__countdown:!0,"alert__countdown--ltr":this.countdown==="ltr"})}
              >
                <div class="alert__countdown-elapsed"></div>
              </div>
            `:""}
      </div>
    `}};g.styles=[I,O];g.dependencies={"sl-icon-button":m};c([T('[part~="base"]')],g.prototype,"base",2);c([T(".alert__countdown-elapsed")],g.prototype,"countdownElement",2);c([h({type:Boolean,reflect:!0})],g.prototype,"open",2);c([h({type:Boolean,reflect:!0})],g.prototype,"closable",2);c([h({reflect:!0})],g.prototype,"variant",2);c([h({type:Number})],g.prototype,"duration",2);c([h({type:String,reflect:!0})],g.prototype,"countdown",2);c([$()],g.prototype,"remainingTime",2);c([Q("open",{waitUntilFirstUpdate:!0})],g.prototype,"handleOpenChange",1);c([Q("duration")],g.prototype,"handleDurationChange",1);var z=g;B("alert.show",{keyframes:[{opacity:0,scale:.8},{opacity:1,scale:1}],options:{duration:250,easing:"ease"}});B("alert.hide",{keyframes:[{opacity:1,scale:1},{opacity:0,scale:.8}],options:{duration:250,easing:"ease"}});z.define("sl-alert");R.define("sl-icon");H.define("sl-button");class W{constructor(e={}){this.generalStatusSteps=["database","mail","google_drive","migrations","admin_user"],this.statusSteps=["database","mail","google_drive","migrations","admin_user","queue_worker"],this.refreshInProgress=!1,this.queueWorkerTestInProgress=!1,this.retryAttempts=0,this.maxRetryAttempts=3,this.retryDelay=2e3,this.autoRefreshInterval=null,this.autoRefreshEnabled=!1,this.autoInit=e.autoInit!==!1,this.lastRefreshTime=0,this.lastQueueTestTime=0,this.debounceDelay=1e3,this.clickTimeouts=new Map,this.refreshAllStatuses=this.refreshAllStatuses.bind(this),this.refreshGeneralStatuses=this.refreshGeneralStatuses.bind(this),this.refreshSingleStep=this.refreshSingleStep.bind(this),this.handleRefreshError=this.handleRefreshError.bind(this),this.retryRefresh=this.retryRefresh.bind(this),this.getCachedQueueWorkerStatus=this.getCachedQueueWorkerStatus.bind(this),this.triggerQueueWorkerTest=this.triggerQueueWorkerTest.bind(this),this.autoInit&&this.init()}init(){this.setupCSRFToken(),this.bindEventListeners(),this.setupKeyboardNavigation(),this.loadCachedQueueWorkerStatus()}setupCSRFToken(){var e;if(!document.querySelector('meta[name="csrf-token"]')){const t=document.createElement("meta");t.name="csrf-token",t.content=((e=document.querySelector('meta[name="csrf-token"]'))==null?void 0:e.getAttribute("content"))||"",document.head.appendChild(t)}}bindEventListeners(){const e=document.getElementById("refresh-status-btn");e&&e.addEventListener("click",o=>{this.debouncedRefreshAllStatuses(o)}),this.statusSteps.forEach(o=>{const i=document.getElementById(`refresh-${o}-btn`);i&&i.addEventListener("click",()=>{o==="queue_worker"?this.triggerQueueWorkerTest():this.refreshSingleStep(o)})});const t=document.getElementById("auto-refresh-toggle");t&&t.addEventListener("change",o=>{this.toggleAutoRefresh(o.target.checked)}),document.addEventListener("click",o=>{o.target.classList.contains("retry-refresh-btn")&&this.retryRefresh()});const r=document.getElementById("test-queue-worker-btn");r&&r.addEventListener("click",o=>{this.debouncedTestQueueWorker(o)});const s=document.getElementById("retry-queue-worker-btn");s&&s.addEventListener("click",o=>{this.debouncedRetryQueueWorkerTest(o)})}setupKeyboardNavigation(){document.addEventListener("keydown",e=>{(e.ctrlKey||e.metaKey)&&e.key==="r"&&!this.refreshInProgress&&(e.preventDefault(),this.refreshAllStatuses())})}getCSRFToken(){var t;const e=(t=document.querySelector('meta[name="csrf-token"]'))==null?void 0:t.getAttribute("content");return e||console.warn("CSRF token not found"),e}debouncedRefreshAllStatuses(e){e.preventDefault();const t=Date.now(),r=t-this.lastRefreshTime;if(this.refreshInProgress||this.queueWorkerTestInProgress){console.log("Operation already in progress, ignoring click");return}if(r<this.debounceDelay){console.log("Debouncing refresh request"),this.clickTimeouts.has("refresh")&&clearTimeout(this.clickTimeouts.get("refresh"));const s=setTimeout(()=>{this.refreshAllStatuses(),this.clickTimeouts.delete("refresh")},this.debounceDelay-r);this.clickTimeouts.set("refresh",s);return}this.lastRefreshTime=t,this.refreshAllStatuses()}debouncedTestQueueWorker(e){e.preventDefault();const t=Date.now(),r=t-this.lastQueueTestTime;if(this.queueWorkerTestInProgress||this.refreshInProgress){console.log("Operation already in progress, ignoring click");return}if(r<this.debounceDelay){console.log("Debouncing queue test request"),this.clickTimeouts.has("queueTest")&&clearTimeout(this.clickTimeouts.get("queueTest"));const s=setTimeout(()=>{this.testQueueWorker(),this.clickTimeouts.delete("queueTest")},this.debounceDelay-r);this.clickTimeouts.set("queueTest",s);return}this.lastQueueTestTime=t,this.testQueueWorker()}debouncedRetryQueueWorkerTest(e){e.preventDefault();const t=Date.now(),r=t-this.lastQueueTestTime;if(this.queueWorkerTestInProgress||this.refreshInProgress){console.log("Operation already in progress, ignoring click");return}if(r<this.debounceDelay){console.log("Debouncing retry request"),this.clickTimeouts.has("retry")&&clearTimeout(this.clickTimeouts.get("retry"));const s=setTimeout(()=>{this.retryQueueWorkerTest(),this.clickTimeouts.delete("retry")},this.debounceDelay-r);this.clickTimeouts.set("retry",s);return}this.lastQueueTestTime=t,this.retryQueueWorkerTest()}async refreshAllStatuses(){if(this.refreshInProgress){console.log("Refresh already in progress, skipping...");return}try{this.setLoadingState(!0),this.clearErrorMessages();const[e,t]=await Promise.allSettled([this.refreshGeneralStatuses(),this.triggerQueueWorkerTest()]);e.status==="fulfilled"?(this.updateLastChecked(),this.resetRetryAttempts()):(console.error("General status refresh failed:",e.reason),this.handleRefreshError(e.reason,"general")),t.status==="rejected"&&console.error("Queue worker test failed:",t.reason),e.status==="fulfilled"&&this.showSuccessMessage("Status refreshed successfully")}catch(e){console.error("Error refreshing all statuses:",e),this.handleRefreshError(e,"all")}finally{this.setLoadingState(!1),this.queueWorkerTestInProgress||this.setQueueWorkerTestButtonState(!1)}}async refreshGeneralStatuses(){var t;const e=await this.makeAjaxRequest("/setup/status/refresh",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"}});if(!e.success)throw new Error(((t=e.error)==null?void 0:t.message)||"Failed to refresh status");return this.updateGeneralStepStatuses(e.data.statuses),e}async refreshSingleStep(e){var t;if(this.refreshInProgress){console.log("Refresh already in progress, skipping...");return}if(!this.statusSteps.includes(e)){console.error("Invalid step name:",e);return}try{this.setSingleStepLoadingState(e,!0);const r=await this.makeAjaxRequest("/setup/status/refresh-step",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"},body:JSON.stringify({step:e})});if(!r.success)throw new Error(((t=r.error)==null?void 0:t.message)||`Failed to refresh ${e} status`);const s=r.data.status.details,o=typeof s=="object"?JSON.stringify(s):s||r.data.status.message;this.updateStatusIndicator(e,r.data.status.status,r.data.status.message,o),this.updateLastChecked(),this.showSuccessMessage(`${r.data.status.step_name} status refreshed`)}catch(r){console.error(`Error refreshing ${e} status:`,r),this.handleRefreshError(r,e)}finally{this.setSingleStepLoadingState(e,!1)}}async makeAjaxRequest(e,t={}){var o;const r=new AbortController,s=setTimeout(()=>r.abort(),3e4);try{const i=await fetch(e,{...t,signal:r.signal});if(clearTimeout(s),!i.ok){const n=await i.json().catch(()=>({}));throw new Error(((o=n.error)==null?void 0:o.message)||`HTTP ${i.status}: ${i.statusText}`)}return await i.json()}catch(i){throw clearTimeout(s),i.name==="AbortError"?new Error("Request timed out. Please check your connection and try again."):i}}updateAllStepStatuses(e){this.statusSteps.forEach(t=>{if(e&&e[t]){const r=e[t],s=r.details,o=typeof s=="object"?JSON.stringify(s):s||r.message;this.updateStatusIndicator(t,r.status,r.message,o)}else console.warn(`No status data found for step: ${t}`),this.updateStatusIndicator(t,"error","No Data","Status information not available")})}updateGeneralStepStatuses(e){this.generalStatusSteps.forEach(t=>{if(e&&e[t]){const r=e[t],s=r.details,o=typeof s=="object"?JSON.stringify(s):s||r.message;this.updateStatusIndicator(t,r.status,r.message,o)}else console.warn(`No status data found for step: ${t}`),this.updateStatusIndicator(t,"error","No Data","Status information not available")})}updateStatusIndicator(e,t,r,s=null){const o=document.getElementById(`status-${e}`),i=document.getElementById(`status-${e}-text`),n=document.getElementById(`details-${e}-text`);if(!o||!i){console.error(`Could not find status elements for step: ${e}`);return}const l=["status-completed","status-working","status-idle","status-incomplete","status-error","status-checking","status-cannot-verify","status-needs_attention"];if(o.classList.remove(...l),o.classList.add(`status-${t}`),this.animateTextChange(i,r),this.updateStatusIcon(o,t),n&&s){let a=s;typeof s=="object"&&s!==null&&(s.message?a=s.message:s.error_message?a=s.error_message:a=JSON.stringify(s)),this.updateStatusDetails(e,t,a)}o.setAttribute("aria-label",`${e} status: ${r}`)}updateStatusIcon(e,t){console.log(`SetupStatusManager: Updating icon for status: ${t}`);let r=e.querySelector("svg"),s=!1;if(r||(r=e.querySelector(".status-emoji"),s=!0,console.log("SetupStatusManager: Found emoji icon element")),!r){console.log("SetupStatusManager: Creating new emoji icon element"),r=document.createElement("span"),r.className="status-emoji w-4 h-4 mr-1.5 text-base";const n=e.querySelector("span");n?e.insertBefore(r,n):e.appendChild(r),s=!0}const o={completed:"✅",working:"✅",idle:"✅",incomplete:"❌",error:"🚫",failed:"🚫",timeout:"⏰","cannot-verify":"❓",needs_attention:"⚠️",checking:"🔄",not_tested:"❓"},i=o[t]||o.checking;if(console.log(`SetupStatusManager: Setting emoji to: ${i}`),s)r.textContent=i;else{const n=document.createElement("span");n.className="status-emoji w-4 h-4 mr-1.5 text-base",n.textContent=i,r.parentNode.replaceChild(n,r)}}animateTextChange(e,t){e.style.opacity="0.5",setTimeout(()=>{e.textContent=t,e.style.opacity="1"},150)}setLoadingState(e){this.refreshInProgress=e,this.setRefreshButtonState(e),this.setQueueWorkerTestButtonState(this.queueWorkerTestInProgress||e),e&&this.generalStatusSteps.forEach(t=>{this.updateStatusIndicator(t,"checking","Checking...","Verifying configuration...")})}setRefreshButtonState(e){const t=document.getElementById("refresh-status-btn"),r=document.getElementById("refresh-btn-text"),s=document.getElementById("refresh-spinner");t&&r&&s&&(t.disabled=e||this.queueWorkerTestInProgress,r.textContent=e?"Checking...":"Check Status",e?(s.classList.remove("hidden"),t.classList.add("cursor-not-allowed","opacity-75")):(s.classList.add("hidden"),t.classList.remove("cursor-not-allowed","opacity-75")),t.disabled?(t.setAttribute("aria-disabled","true"),t.setAttribute("title","Please wait for current operation to complete")):(t.removeAttribute("aria-disabled"),t.setAttribute("title","Check all setup statuses")))}setSingleStepLoadingState(e,t){t&&this.updateStatusIndicator(e,"checking","Checking...","Verifying configuration...")}handleRefreshError(e,t="all"){this.retryAttempts++,console.error(`Refresh error (attempt ${this.retryAttempts}):`,e),this.retryAttempts<this.maxRetryAttempts?(this.showRetryMessage(e.message,t),setTimeout(()=>{t==="all"?this.refreshAllStatuses():this.refreshSingleStep(t)},this.retryDelay*this.retryAttempts)):(this.showErrorState(e.message,t),this.resetRetryAttempts())}showRetryMessage(e,t){const r=`Failed to refresh status (${e}). Retrying in ${this.retryDelay*this.retryAttempts/1e3} seconds... (Attempt ${this.retryAttempts}/${this.maxRetryAttempts})`;this.showMessage(r,"warning")}showErrorState(e,t){t==="all"?this.statusSteps.forEach(r=>{this.updateStatusIndicator(r,"error","Check Failed","Unable to verify status. Please check your connection and try again.")}):this.updateStatusIndicator(t,"error","Check Failed","Unable to verify status. Please check your connection and try again."),this.showMessage(`Failed to refresh status: ${e}. Please check your connection and try again.`,"error",!0)}showMessage(e,t="info",r=!1){this.clearMessages();let s=document.getElementById("toast-container");s||(s=document.createElement("div"),s.id="toast-container",s.className="toast-container",document.body.appendChild(s));const o={success:"success",error:"danger",warning:"warning",info:"primary"},i=document.createElement("sl-alert");i.setAttribute("variant",o[t]||"primary"),i.setAttribute("closable","true"),i.className="status-message";let n=`
            <sl-icon slot="icon" name="${this.getIconName(t)}"></sl-icon>
            ${e}
        `;if(r&&(n+=`
                <sl-button slot="action" variant="text" size="small" class="retry-refresh-btn">
                    Retry Now
                </sl-button>
            `),i.innerHTML=n,s.appendChild(i),i.toast(),t==="success"&&setTimeout(()=>{i.parentNode&&i.hide()},5e3),r){const l=i.querySelector(".retry-refresh-btn");l&&l.addEventListener("click",()=>{i.hide(),this.retryRefresh()})}}showRetryButton(){const e=document.getElementById("retry-queue-worker-btn");e&&(e.classList.remove("hidden"),e.disabled=!1,e.classList.remove("cursor-not-allowed","opacity-75"))}hideRetryButton(){const e=document.querySelector(".retry-queue-test-btn");e&&e.remove()}showQueueWorkerErrorDetails(e){const t=document.getElementById("queue-test-error-details"),r=document.getElementById("queue-test-error-message"),s=document.getElementById("queue-test-troubleshooting-content");t&&r&&(r.textContent=e.error_message||"Test failed with unknown error",s&&e.troubleshooting&&Array.isArray(e.troubleshooting)&&(s.innerHTML="",e.troubleshooting.forEach(o=>{const i=document.createElement("div");i.className="text-xs text-red-600 mb-1",i.innerHTML=`• ${this.escapeHtml(o)}`,s.appendChild(i)})),t.classList.remove("hidden"))}hideQueueWorkerErrorDetails(){const e=document.getElementById("queue-test-error-details");e&&e.classList.add("hidden")}showQueueWorkerSuccessDetails(e){const t=document.getElementById("queue-test-success-details"),r=document.getElementById("queue-test-success-message"),s=document.getElementById("queue-test-processing-time");t&&r&&(r.textContent=e.message||"Queue worker is functioning properly",s&&e.processing_time&&(s.textContent=`Processing time: ${e.processing_time.toFixed(2)} seconds`),t.classList.remove("hidden"))}hideQueueWorkerSuccessDetails(){const e=document.getElementById("queue-test-success-details");e&&e.classList.add("hidden")}showQueueWorkerProgressDetails(e){const t=document.getElementById("queue-test-progress"),r=document.getElementById("queue-test-progress-text");t&&r&&(r.textContent=e.message||"Testing queue worker...",t.classList.remove("hidden"))}hideQueueWorkerProgressDetails(){const e=document.getElementById("queue-test-progress");e&&e.classList.add("hidden")}escapeHtml(e){const t=document.createElement("div");return t.textContent=e,t.innerHTML}getTroubleshootingSteps(e){const t={dispatch_failed:["Verify queue configuration in .env file (QUEUE_CONNECTION)","Check if database tables exist: php artisan migrate","Ensure queue driver is properly configured (database, redis, etc.)","Check application logs for configuration errors","Verify file permissions for storage and cache directories","Test database connection: php artisan tinker, then DB::connection()->getPdo()","For Redis queue: ensure Redis server is running and accessible"],timeout:["Ensure queue worker is running: php artisan queue:work","Check if worker process is stuck or crashed","Verify queue driver configuration (database, redis, etc.)","Check system resources (CPU, memory, disk space)","Review worker logs for errors or warnings","Restart the queue worker: php artisan queue:restart","Check for long-running jobs blocking the queue"],network_error:["Check your internet connection","Verify the application server is accessible","Check for firewall or proxy issues","Try refreshing the page and testing again","Contact your network administrator if issues persist"],general:["Check if queue worker is running: php artisan queue:work","Verify queue configuration in .env file","Check for failed jobs: php artisan queue:failed","Review application logs for errors","Restart the queue worker if needed","Check system resources and permissions"]};return t[e]||t.general}clearAllTimeouts(){this.clickTimeouts.forEach(e=>{clearTimeout(e)}),this.clickTimeouts.clear()}getIconName(e){const t={success:"check-circle",error:"exclamation-triangle",warning:"exclamation-triangle",info:"info-circle"};return t[e]||t.info}showSuccessMessage(e){this.showMessage(e,"success")}showErrorMessage(e,t=!1){this.showMessage(e,"error",t)}clearMessages(){const e=document.getElementById("toast-container");e&&e.querySelectorAll("sl-alert").forEach(t=>{t.hide()})}clearErrorMessages(){const e=document.getElementById("toast-container");e&&e.querySelectorAll('sl-alert[variant="danger"]').forEach(t=>{t.hide()})}async loadCachedQueueWorkerStatus(){try{const e=await this.getCachedQueueWorkerStatus();e&&!this.isStatusExpired(e)?this.updateQueueWorkerStatusFromCache(e):this.updateStatusIndicator("queue_worker","not_tested","Click the Test Queue Worker button below","No recent test results available")}catch(e){console.error("Error loading cached queue worker status:",e),this.updateStatusIndicator("queue_worker","not_tested","Click the Test Queue Worker button below","Unable to load cached status")}}async getCachedQueueWorkerStatus(){try{const e=await this.makeAjaxRequest("/setup/queue-worker/status",{method:"GET",headers:{"X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"}});return e.success&&e.data&&e.data.queue_worker?e.data.queue_worker:null}catch(e){return console.error("Error fetching cached queue worker status:",e),null}}isStatusExpired(e){if(!e.test_completed_at)return!0;const t=new Date(e.test_completed_at),r=new Date,s=3600*1e3;return r-t>s}updateQueueWorkerStatusFromCache(e){let t,r,s;switch(e.status){case"completed":t="completed",r="Queue worker is functioning properly",s=`Last tested: ${this.getTimeAgo(new Date(e.test_completed_at))}`,e.processing_time&&(s+=` (${e.processing_time.toFixed(2)}s)`),this.hideRetryButton(),this.hideQueueWorkerErrorDetails(),this.showQueueWorkerSuccessDetails(e);break;case"failed":t="error",r=e.message||"Queue worker test failed",s=e.error_message||"Test failed with unknown error",this.showRetryButton(),this.hideQueueWorkerSuccessDetails(),this.showQueueWorkerErrorDetails(e);break;case"timeout":t="timeout",r=e.message||"Queue worker test timed out",s=e.error_message||"The queue worker may not be running",this.showRetryButton(),this.hideQueueWorkerSuccessDetails(),this.showQueueWorkerErrorDetails(e);break;case"error":t="error",r=e.message||"Error checking queue worker status",s=e.error_message||"System error occurred",this.showRetryButton(),this.hideQueueWorkerSuccessDetails(),this.showQueueWorkerErrorDetails(e);break;case"testing":t="checking",r=e.message||"Testing queue worker...",s="Test in progress...",this.hideRetryButton(),this.hideQueueWorkerErrorDetails(),this.hideQueueWorkerSuccessDetails(),this.showQueueWorkerProgressDetails(e);break;default:t="not_tested",r="Click the Test Queue Worker button below",s="No recent test results available",this.hideRetryButton(),this.hideQueueWorkerErrorDetails(),this.hideQueueWorkerSuccessDetails()}this.updateStatusIndicator("queue_worker",t,r,s)}async triggerQueueWorkerTest(){if(this.queueWorkerTestInProgress){console.log("Queue worker test already in progress, skipping...");return}try{this.queueWorkerTestInProgress=!0,this.setQueueWorkerTestButtonState(!0),this.setRefreshButtonState(this.refreshInProgress),this.updateStatusIndicator("queue_worker","checking","Testing queue worker...","Dispatching test job..."),await this.performQueueWorkerTest()}catch(e){console.error("Queue worker test failed:",e);let t="Unknown error occurred";e&&typeof e=="object"?t=e.message||e.toString()||"Unknown error occurred":typeof e=="string"&&(t=e),this.updateStatusIndicator("queue_worker","error","Test failed",t),this.showRetryButton()}finally{this.queueWorkerTestInProgress=!1,this.setQueueWorkerTestButtonState(!1),this.setRefreshButtonState(this.refreshInProgress)}}setQueueWorkerTestButtonState(e){const t=document.getElementById("test-queue-worker-btn"),r=document.getElementById("test-queue-worker-btn-text"),s=document.getElementById("test-queue-worker-spinner"),o=document.getElementById("retry-queue-worker-btn");t&&r&&(t.disabled=e||this.refreshInProgress,r.textContent=e?"Testing...":"Test Queue Worker",s&&(e?s.classList.remove("hidden"):s.classList.add("hidden")),t.disabled?(t.classList.add("cursor-not-allowed","opacity-75"),t.setAttribute("aria-disabled","true"),t.setAttribute("title","Please wait for current operation to complete")):(t.classList.remove("cursor-not-allowed","opacity-75"),t.removeAttribute("aria-disabled"),t.setAttribute("title","Test queue worker functionality"))),o&&(o.disabled=e||this.refreshInProgress,o.disabled?(o.classList.add("cursor-not-allowed","opacity-75"),o.setAttribute("aria-disabled","true")):(o.classList.remove("cursor-not-allowed","opacity-75"),o.removeAttribute("aria-disabled")))}async performQueueWorkerTest(){var e;try{this.updateStatusIndicator("queue_worker","checking","Testing queue worker...","Dispatching test job...");const t=await this.makeAjaxRequest("/setup/queue/test",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"},body:JSON.stringify({delay:0,timeout:30})});if(!t.success)throw new Error(((e=t.error)==null?void 0:e.message)||t.message||"Failed to dispatch test job");if(!t.test_job_id)throw new Error("No test job ID returned from server");this.updateStatusIndicator("queue_worker","checking","Test job queued...","Waiting for queue worker to pick up job..."),await this.pollQueueTestResultWithEnhancedErrorHandling(t.test_job_id)}catch(t){console.error("Queue worker test failed:",t),this.handleQueueWorkerTestError(t)}}handleQueueWorkerTestError(e){let t="error",r="Test failed",s="Unknown error occurred";e&&typeof e=="object"?s=e.message||e.toString()||"Unknown error occurred":typeof e=="string"&&(s=e),this.isDispatchError(e)?(t="error",r="Failed to dispatch test job",s=this.getDispatchErrorDetails(e)):this.isNetworkError(e)?(t="error",r="Network error during test",s=this.getNetworkErrorDetails(e)):this.isTimeoutError(e)?(t="timeout",r="Queue worker test timed out",s=this.getTimeoutErrorDetails(e)):s=`${e.message}. Check the application logs for more details.`,this.updateStatusIndicator("queue_worker",t,r,s),this.addRetryButtonToQueueWorkerStatus()}isDispatchError(e){const t=e.message.toLowerCase();return t.includes("dispatch")||t.includes("queue connection")||t.includes("database connection")||t.includes("table")||t.includes("configuration")}isNetworkError(e){const t=e.message.toLowerCase();return t.includes("network")||t.includes("connection refused")||t.includes("timeout")||t.includes("unreachable")||t.includes("fetch")}isTimeoutError(e){const t=e.message.toLowerCase();return t.includes("timeout")||t.includes("timed out")}getDispatchErrorDetails(e){return`
            <div class="space-y-2">
                <p class="text-sm text-red-700">${e.message}</p>
                <div class="bg-red-50 p-3 rounded border border-red-200">
                    <h4 class="font-medium text-red-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                        <li>Verify queue configuration in .env file (QUEUE_CONNECTION)</li>
                        <li>Check if database tables exist: php artisan migrate</li>
                        <li>Ensure queue driver is properly configured</li>
                        <li>Check application logs for configuration errors</li>
                    </ul>
                </div>
            </div>
        `}getNetworkErrorDetails(e){return`
            <div class="space-y-2">
                <p class="text-sm text-red-700">${e.message}</p>
                <div class="bg-red-50 p-3 rounded border border-red-200">
                    <h4 class="font-medium text-red-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                        <li>Check your internet connection</li>
                        <li>Verify the application server is accessible</li>
                        <li>Check for firewall or proxy issues</li>
                        <li>Try refreshing the page and testing again</li>
                    </ul>
                </div>
            </div>
        `}getTimeoutErrorDetails(e){return`
            <div class="space-y-2">
                <p class="text-sm text-yellow-700">${e.message}</p>
                <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                    <h4 class="font-medium text-yellow-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-yellow-700 space-y-1 list-disc list-inside">
                        <li>Ensure queue worker is running: php artisan queue:work</li>
                        <li>Check if worker process is stuck or crashed</li>
                        <li>Verify queue driver configuration</li>
                        <li>Check system resources (CPU, memory, disk space)</li>
                        <li>Review worker logs for errors or warnings</li>
                    </ul>
                </div>
            </div>
        `}addRetryButtonToQueueWorkerStatus(){const e=document.getElementById("details-queue_worker-text");if(e&&!e.querySelector(".retry-queue-test-btn")){const t=document.createElement("button");t.className="retry-queue-test-btn mt-3 inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150",t.innerHTML=`
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Retry Test
                `,t.addEventListener("click",()=>{this.retryQueueWorkerTest()}),e.appendChild(t)}}async retryQueueWorkerTest(){console.log("Retrying queue worker test..."),this.hideQueueWorkerErrorDetails(),this.hideRetryButton();const e=document.querySelector(".retry-queue-test-btn");e&&e.remove(),this.clearErrorMessages(),this.queueWorkerTestInProgress=!1,await this.testQueueWorker()}showRetryButton(){const e=document.getElementById("retry-queue-worker-btn");e&&e.classList.remove("hidden")}showQueueWorkerErrorDetails(e,t="general"){const r=document.getElementById("queue-test-error-details"),s=document.getElementById("queue-test-error-message"),o=document.getElementById("queue-test-troubleshooting-content");r&&s&&(s.textContent=e,o&&(o.innerHTML=this.getTroubleshootingSteps(t)),r.classList.remove("hidden"),r.classList.add("error-message"),this.showRetryButton())}hideQueueWorkerErrorDetails(){const e=document.getElementById("queue-test-error-details");e&&(e.classList.add("hidden"),e.classList.remove("error-message"))}showQueueWorkerSuccessDetails(e,t=null){const r=document.getElementById("queue-test-success-details"),s=document.getElementById("queue-test-success-message"),o=document.getElementById("queue-test-processing-time");r&&s&&(s.textContent=e,t&&o&&(o.textContent=`Processing time: ${t}s`),r.classList.remove("hidden"),r.classList.add("success-message"),this.hideRetryButton())}hideQueueWorkerSuccessDetails(){const e=document.getElementById("queue-test-success-details");e&&(e.classList.add("hidden"),e.classList.remove("success-message"))}showQueueWorkerProgress(e,t=null){const r=document.getElementById("queue-test-progress"),s=document.getElementById("queue-test-progress-text"),o=document.getElementById("queue-test-progress-details");r&&s&&(s.textContent=e,t&&o?(o.textContent=t,o.classList.remove("hidden")):o&&o.classList.add("hidden"),r.classList.remove("hidden"),r.classList.add("queue-test-progress"))}hideQueueWorkerProgress(){const e=document.getElementById("queue-test-progress");e&&(e.classList.add("hidden"),e.classList.remove("queue-test-progress"))}getTroubleshootingSteps(e){const t={dispatch_failed:["Check that your database connection is working properly","Verify that the jobs table exists (run migrations if needed)","Ensure your queue configuration in .env is correct","Check Laravel logs for detailed error information"],timeout:["Verify that a queue worker is running (php artisan queue:work)","Check if the worker process is stuck or crashed","Restart the queue worker process","Check system resources (CPU, memory) on your server","Review worker logs for any error messages"],job_failed:["Check Laravel logs for the specific job failure reason","Verify file permissions in storage directories","Ensure all required dependencies are installed","Check if there are any configuration issues","Try running the queue worker with --tries=1 for debugging"],network_error:["Check your internet connection","Verify that the application server is running","Check for any firewall or proxy issues","Try refreshing the page and testing again"],general:["Check Laravel logs in storage/logs/laravel.log","Verify that the queue worker is running","Ensure database connection is working","Check system resources and server status","Try running: php artisan queue:work --tries=1"]};return(t[e]||t.general).map(s=>`<div class="troubleshooting-step">• ${s}</div>`).join("")}async pollQueueTestResultWithEnhancedErrorHandling(e){let s=0,o=0;const i=Date.now(),n=async()=>{var l;s++;try{const a=await this.makeAjaxRequest(`/setup/queue/test/status?test_job_id=${e}`,{method:"GET",headers:{"X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"}});if(o=0,!a.success||!a.status)throw new Error(((l=a.error)==null?void 0:l.message)||"Invalid response from server");const p=a.status,d=((Date.now()-i)/1e3).toFixed(1);switch(p.status){case"completed":this.updateStatusIndicator("queue_worker","completed","Queue worker is functioning properly",`Test completed successfully in ${(p.processing_time||0).toFixed(2)}s (total: ${d}s)`);return;case"failed":const L=p.error_message||"Test job failed without specific error";this.updateStatusIndicator("queue_worker","error","Test job execution failed",this.getJobFailureDetails(L,d)),this.addRetryButtonToQueueWorkerStatus();return;case"timeout":this.updateStatusIndicator("queue_worker","timeout","Queue worker test timed out",this.getTimeoutErrorDetails({message:`Test timed out after ${d}s. The queue worker may not be running.`})),this.addRetryButtonToQueueWorkerStatus();return;case"processing":this.updateStatusIndicator("queue_worker","checking","Test job processing...",`Job is being processed by queue worker (${d}s elapsed)`);break;case"pending":this.updateStatusIndicator("queue_worker","checking","Test job queued...",`Waiting for queue worker to pick up job (${d}s elapsed)`);break;default:this.updateStatusIndicator("queue_worker","checking","Testing queue worker...",`Checking test job status (${d}s elapsed)`);break}(p.status==="processing"||p.status==="pending")&&(s<30?setTimeout(n,1e3):(this.updateStatusIndicator("queue_worker","timeout","Queue worker test timed out",this.getTimeoutErrorDetails({message:`Test timed out after ${d}s. The queue worker may not be running.`})),this.addRetryButtonToQueueWorkerStatus()))}catch(a){if(console.error("Polling error:",a),this.isNetworkError(a)&&o<3){o++,console.log(`Network error during polling, retrying... (${o}/3)`);const d=((Date.now()-i)/1e3).toFixed(1);this.updateStatusIndicator("queue_worker","checking","Network error, retrying...",`Connection issue during status check (${d}s elapsed, retry ${o}/3)`),setTimeout(n,2e3);return}const p=((Date.now()-i)/1e3).toFixed(1);this.updateStatusIndicator("queue_worker","error","Error checking test status",this.getPollingErrorDetails(a,p)),this.addRetryButtonToQueueWorkerStatus()}};n()}getJobFailureDetails(e,t){return`
            <div class="space-y-2">
                <p class="text-sm text-red-700">Job failed after ${t}s: ${e}</p>
                <div class="bg-red-50 p-3 rounded border border-red-200">
                    <h4 class="font-medium text-red-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                        <li>Check failed jobs table: php artisan queue:failed</li>
                        <li>Review worker logs for specific error details</li>
                        <li>Ensure all required dependencies are installed</li>
                        <li>Check memory limits and execution time settings</li>
                        <li>Verify database connectivity from worker process</li>
                    </ul>
                </div>
            </div>
        `}getPollingErrorDetails(e,t){return`
            <div class="space-y-2">
                <p class="text-sm text-red-700">Status check failed after ${t}s: ${e.message}</p>
                <div class="bg-red-50 p-3 rounded border border-red-200">
                    <h4 class="font-medium text-red-800 mb-2">Troubleshooting Steps:</h4>
                    <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                        <li>Check your internet connection</li>
                        <li>Verify the application server is accessible</li>
                        <li>Try refreshing the page and testing again</li>
                        <li>Check application logs for server errors</li>
                    </ul>
                </div>
            </div>
        `}async pollQueueTestResultForStatus(e){let r=0;const s=async()=>{r++;try{const o=await this.makeAjaxRequest(`/setup/queue/test/status?test_job_id=${e}`,{method:"GET",headers:{"X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"}});if(!o.success||!o.status)throw new Error("Invalid response from server");const i=o.status;switch(i.status){case"completed":this.updateStatusIndicator("queue_worker","completed",`Queue worker is functioning properly (${(i.processing_time||0).toFixed(2)}s)`,`Test completed successfully at ${new Date().toLocaleTimeString()}`);return;case"failed":this.updateStatusIndicator("queue_worker","error","Queue worker test failed",i.error_message||"Test job failed with unknown error");return;case"timeout":this.updateStatusIndicator("queue_worker","error","Queue worker test timed out","The queue worker may not be running");return;case"processing":this.updateStatusIndicator("queue_worker","checking","Test job processing...",`Job is being processed by worker (${r}s elapsed)`);break;case"pending":this.updateStatusIndicator("queue_worker","checking","Test job queued...",`Waiting for queue worker to pick up job (${r}s elapsed)`);break;default:this.updateStatusIndicator("queue_worker","checking","Testing queue worker...",`Checking test job status (${r}s elapsed)`);break}(i.status==="processing"||i.status==="pending")&&(r<30?setTimeout(s,1e3):this.updateStatusIndicator("queue_worker","timeout","Queue worker test timed out (30s)","The queue worker may not be running. Check if 'php artisan queue:work' is active."))}catch(o){console.error("Polling error:",o),this.updateStatusIndicator("queue_worker","error","Error checking test status",o.message||"Unknown error")}};s()}async testQueueWorker(){const e=document.getElementById("queue-test-results"),t=document.getElementById("queue-test-status"),r=document.getElementById("test-queue-worker-btn"),s=document.getElementById("test-queue-worker-btn-text"),o=document.getElementById("test-queue-worker-spinner"),i=document.getElementById("refresh-status-btn");if(!e||!t){console.error("Queue test elements not found");return}this.queueWorkerTestInProgress=!0,r&&(r.disabled=!0,s&&(s.textContent="Testing..."),o&&o.classList.remove("hidden")),i&&(i.disabled=!0),this.hideQueueWorkerErrorDetails(),this.hideQueueWorkerSuccessDetails(),this.hideQueueWorkerProgress(),this.hideRetryButton();try{e.classList.remove("hidden"),t.innerHTML=`
                <div class="flex items-center">
                    <svg class="animate-spin h-4 w-4 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-blue-700">Testing queue worker...</span>
                </div>
            `,await this.triggerQueueWorkerTest();const n=await this.makeAjaxRequest("/setup/queue/test",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"},body:JSON.stringify({delay:0})});if(n.success&&n.test_job_id)t.innerHTML=`
                    <div class="flex items-center">
                        <svg class="animate-pulse h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-yellow-700">Test job queued...</span>
                    </div>
                `,await this.pollQueueTestResultWithProgressiveUpdates(n.test_job_id,t);else throw new Error(n.message||"Failed to dispatch test job")}catch(n){console.error("Queue test failed:",n),this.hideQueueWorkerProgressDetails(),this.hideQueueWorkerSuccessDetails();let l="general";n.message.includes("dispatch")?l="dispatch_failed":n.message.includes("timeout")?l="timeout":(n.message.includes("network")||n.message.includes("fetch"))&&(l="network_error");const a={status:"failed",message:"Queue worker test failed",error_message:n.message,troubleshooting:this.getTroubleshootingSteps(l)};this.showQueueWorkerErrorDetails(a),this.updateStatusIndicator("queue_worker","failed","Queue worker test failed",`Test failed: ${n.message||n.toString()||"Unknown error"}`),t.innerHTML=`
                <div class="flex items-center">
                    <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-red-700">Test failed: ${n.message||n.toString()||"Unknown error"}</span>
                </div>
            `}finally{this.queueWorkerTestInProgress=!1,r&&(r.disabled=!1,s&&(s.textContent="Test Queue Worker"),o&&o.classList.add("hidden")),i&&(i.disabled=!1)}}async pollQueueTestResultWithProgressiveUpdates(e,t){let s=0;const o=Date.now(),i=async()=>{s++;try{const n=await this.makeAjaxRequest(`/setup/queue/test/status?test_job_id=${e}`,{method:"GET",headers:{"X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"}});if(n.success&&n.status){const l=n.status,a=((Date.now()-o)/1e3).toFixed(1);switch(l.status){case"completed":this.hideQueueWorkerProgress();const p=(l.processing_time||0).toFixed(2);this.showQueueWorkerSuccessDetails(`Queue worker is functioning properly! Job completed in ${p}s (total test time: ${a}s)`,p),this.updateStatusIndicator("queue_worker","completed","Queue worker is functioning properly",`Last tested: just now (${p}s)`),t.innerHTML=`
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-green-700">Queue worker is functioning properly! Job completed in ${p}s (total test time: ${a}s)</span>
                                </div>
                            `;return;case"failed":this.hideQueueWorkerProgress();const d=l.error_message||"Unknown error";this.showQueueWorkerErrorDetails(d,"job_failed"),this.updateStatusIndicator("queue_worker","failed","Queue worker test failed",`Test failed: ${d}`),t.innerHTML=`
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-red-700">Queue test failed: ${d} (after ${a}s)</span>
                                </div>
                            `;return;case"timeout":this.hideQueueWorkerProgress(),this.showQueueWorkerErrorDetails(`Test timed out after ${a}s. The queue worker may not be running.`,"timeout"),this.updateStatusIndicator("queue_worker","timeout","Queue worker test timed out","The queue worker may not be running"),t.innerHTML=`
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-yellow-700">Test timed out after ${a}s. The queue worker may not be running.</span>
                                </div>
                            `;return;case"processing":this.showQueueWorkerProgress("Test job is being processed...",`${a}s elapsed`),t.innerHTML=`
                                <div class="flex items-center">
                                    <svg class="animate-spin h-4 w-4 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-blue-700">Test job is being processed... (${a}s elapsed)</span>
                                </div>
                            `;break;case"pending":this.showQueueWorkerProgress("Test job is queued, waiting for worker...",`${a}s elapsed`),t.innerHTML=`
                                <div class="flex items-center">
                                    <svg class="animate-pulse h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-yellow-700">Test job is queued, waiting for worker... (${a}s elapsed)</span>
                                </div>
                            `;break;default:t.innerHTML=`
                                <div class="flex items-center">
                                    <svg class="animate-spin h-4 w-4 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-blue-700">Testing queue worker... (${a}s elapsed)</span>
                                </div>
                            `;break}(l.status==="processing"||l.status==="pending")&&(s<30?setTimeout(i,1e3):t.innerHTML=`
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-yellow-700">Test timed out after ${a}s. The queue worker may not be running.</span>
                                </div>
                            `)}else throw new Error("Invalid response from server")}catch(n){console.error("Polling error:",n);const l=((Date.now()-o)/1e3).toFixed(1);t.innerHTML=`
                    <div class="flex items-center">
                        <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-red-700">Error checking test status: ${n.message} (after ${l}s)</span>
                    </div>
                `}};i()}async pollQueueTestResult(e,t){let s=0;const o=async()=>{s++;try{const i=await this.makeAjaxRequest(`/setup/queue/test/status?test_job_id=${e}`,{method:"GET",headers:{"X-CSRF-TOKEN":this.getCSRFToken(),"X-Requested-With":"XMLHttpRequest"}});if(i.success&&i.status){const n=i.status;switch(n.status){case"completed":t.innerHTML=`
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
                            `)}else throw new Error("Invalid response from server")}catch(i){console.error("Polling error:",i),t.innerHTML=`
                    <div class="flex items-center">
                        <svg class="h-4 w-4 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-red-700">Error checking test status: ${i.message}</span>
                    </div>
                `}};o()}updateLastChecked(){const e=document.getElementById("last-checked"),t=document.getElementById("last-checked-time");if(e&&t){const r=new Date;t.textContent=r.toLocaleTimeString(),e.classList.remove("hidden")}}resetRetryAttempts(){this.retryAttempts=0}retryRefresh(){this.resetRetryAttempts(),this.clearMessages(),this.refreshAllStatuses()}toggleAutoRefresh(e){this.autoRefreshEnabled=e,e?this.autoRefreshInterval=setInterval(()=>{this.refreshInProgress||this.refreshAllStatuses()},3e4):this.autoRefreshInterval&&(clearInterval(this.autoRefreshInterval),this.autoRefreshInterval=null)}updateStatusDetails(e,t,r){const s=document.getElementById(`details-${e}-text`);if(!s)return;let o="";if(typeof r=="object"&&r!==null){let i=r;if(typeof r=="string")try{i=JSON.parse(r)}catch{o=`<div>${r}</div>`,s.innerHTML=o;return}if(i.checked_at){const n=new Date(i.checked_at),l=this.getTimeAgo(n);o+=`<div class="mb-2"><strong>Last checked:</strong> ${l}</div>`}o+=this.getStatusSpecificDetails(e,t,i),(t==="incomplete"||t==="error"||t==="cannot_verify")&&(o+=this.getTroubleshootingGuidance(e,t,i))}else if(typeof r=="string")try{const i=JSON.parse(r);this.updateStatusDetails(e,t,i);return}catch{o=`<div>${r}</div>`}else o=`<div>${String(r||"No additional details available.")}</div>`;s.innerHTML=o||"No additional details available."}getStatusSpecificDetails(e,t,r){let s="";switch(e){case"queue_worker":r.recent_jobs!==void 0&&(s+=`<div class="mb-2">
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
                                        ${t.metadata.missing_fields.map(o=>`<li>${o}</li>`).join("")}
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
                                            ${t.metadata.missing_fields.map(o=>`<li>${o}</li>`).join("")}
                                        </ul>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-yellow-800">Configured fields:</p>
                                        <ul class="mt-1 text-sm text-yellow-700 list-disc list-inside">
                                            ${t.metadata.configured_fields.map(o=>`<li>${o}</li>`).join("")}
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
                `;break;default:s+=`<div class="mb-2"><strong>Status:</strong> ${t.description||"Database status information available"}</div>`}return s}getTroubleshootingGuidance(e,t,r){let s='<div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">';switch(t==="cannot_verify"?(s+="<strong>Cannot Verify - Manual Check Required:</strong>",s+='<p class="text-sm mt-1 mb-2">The system cannot automatically verify this step. Please check manually:</p>'):s+="<strong>Troubleshooting:</strong>",s+='<ul class="ml-4 mt-2 text-sm">',e){case"database":t==="cannot_verify"?(s+="<li>Check your database connection settings in .env file</li>",s+="<li>Ensure your database server is running</li>",s+="<li>Verify database credentials are correct</li>",s+="<li><strong>Manual verification:</strong> Try running <code>php artisan migrate:status</code></li>"):t==="incomplete"&&(s+="<li>Run database migrations: <code>php artisan migrate</code></li>",s+="<li>Check if all required tables exist</li>");break;case"mail":t==="cannot_verify"&&(s+="<li>Check mail configuration in .env file</li>",s+="<li>For local development, consider using log driver</li>",s+="<li>Verify SMTP credentials if using external mail service</li>",s+="<li><strong>Manual verification:</strong> Try sending a test email or check logs</li>");break;case"google_drive":t==="incomplete"&&(s+="<li>Set GOOGLE_DRIVE_CLIENT_ID in .env file</li>",s+="<li>Set GOOGLE_DRIVE_CLIENT_SECRET in .env file</li>",s+="<li>Complete OAuth setup in Google Cloud Console</li>");break;case"queue_worker":t==="cannot_verify"?(s+="<li>Start queue worker: <code>php artisan queue:work</code></li>",s+="<li>Check if queue tables exist in database</li>",s+="<li><strong>Manual verification:</strong> Use the test button above to verify functionality</li>",s+="<li>Check queue status with: <code>php artisan queue:monitor</code></li>"):t==="needs_attention"&&(s+="<li>Check failed jobs: <code>php artisan queue:failed</code></li>",s+="<li>Restart queue worker if needed</li>",s+="<li>Review application logs for errors</li>");break;case"admin_user":t==="incomplete"&&(s+="<li>Create admin user: <code>php artisan make:admin</code></li>",s+="<li>Or register through the web interface</li>");break;case"migrations":t==="incomplete"&&(s+="<li>Run migrations: <code>php artisan migrate</code></li>",s+="<li>Check database connection first</li>");break}return s+="</ul></div>",s}getTimeAgo(e){const r=new Date-e,s=Math.floor(r/1e3),o=Math.floor(s/60),i=Math.floor(o/60);return s<60?"Just now":o<60?`${o} minute${o!==1?"s":""} ago`:i<24?`${i} hour${i!==1?"s":""} ago`:e.toLocaleString()}toggleStatusDetails(e){const t=document.getElementById(`details-${e}`);t&&t.classList.toggle("show")}cleanup(){this.autoRefreshInterval&&clearInterval(this.autoRefreshInterval)}}let w;function U(u){w&&w.toggleStatusDetails(u)}window.toggleStatusDetails=U;document.addEventListener("DOMContentLoaded",function(){console.log("SetupStatusManager: DOM loaded, initializing..."),w=new W,console.log("SetupStatusManager: Instance created"),window.addEventListener("beforeunload",()=>{w&&w.cleanup()})});typeof b<"u"&&b.exports&&(b.exports=W)});export default X();
