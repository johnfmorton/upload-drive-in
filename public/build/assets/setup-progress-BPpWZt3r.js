var c=(n,t)=>()=>(t||n((t={exports:{}}).exports,t),t.exports);var l=c((u,o)=>{class a{constructor(){this.currentStep=null,this.progress=0,this.stepStartTime=null,this.setupStartTime=null,this.completedSteps=[],this.init()}init(){this.loadSetupState(),this.setupEventListeners(),this.startProgressTracking()}loadSetupState(){const t=document.querySelector("[data-setup-step]");t&&(this.currentStep=t.dataset.setupStep);const e=document.querySelector("[data-setup-progress]");e&&(this.progress=parseInt(e.dataset.setupProgress)||0);const s=localStorage.getItem("setup_progress_state");if(s)try{const i=JSON.parse(s);this.setupStartTime=i.setupStartTime,this.completedSteps=i.completedSteps||[]}catch(i){console.warn("Failed to load setup state from localStorage:",i)}this.setupStartTime||(this.setupStartTime=Date.now(),this.saveState()),this.stepStartTime=Date.now()}setupEventListeners(){document.addEventListener("setup:step-completed",t=>{this.handleStepCompletion(t.detail)}),document.addEventListener("setup:step-transition",t=>{this.handleStepTransition(t.detail)}),document.addEventListener("submit",t=>{t.target.closest("[data-setup-step]")&&this.handleFormSubmission(t)}),document.addEventListener("visibilitychange",()=>{document.hidden?this.pauseTracking():this.resumeTracking()})}startProgressTracking(){this.updateProgressIndicators(),this.startStepTimer(),this.animateProgressBars()}handleStepCompletion(t){const{step:e,nextStep:s,progress:i,details:r}=t;this.completedSteps.includes(e)||this.completedSteps.push(e),this.progress=i||this.progress,this.showStepCompletionFeedback(e,r),this.saveState(),s&&setTimeout(()=>{this.triggerStepTransition(e,s)},2e3)}handleStepTransition(t){const{fromStep:e,toStep:s,message:i}=t;this.showStepTransition(e,s,i),this.currentStep=s,this.stepStartTime=Date.now(),this.saveState()}handleFormSubmission(t){const e=t.target,s=e.closest("[data-setup-step]");s&&(this.showFormLoadingState(e),this.trackFormSubmission(s.dataset.setupStep))}showStepCompletionFeedback(t,e={}){const s=this.createCompletionNotification(t,e);document.body.appendChild(s),requestAnimationFrame(()=>{s.classList.add("show")}),setTimeout(()=>{s.classList.remove("show"),setTimeout(()=>{s.parentNode&&s.parentNode.removeChild(s)},300)},4e3)}showStepTransition(t,e,s){const i=this.createTransitionOverlay(t,e,s);document.body.appendChild(i),requestAnimationFrame(()=>{i.classList.add("show")}),setTimeout(()=>{i.classList.remove("show"),setTimeout(()=>{i.parentNode&&i.parentNode.removeChild(i)},300)},2e3)}createCompletionNotification(t,e){const s=document.createElement("div");return s.className="setup-completion-notification",s.innerHTML=`
            <div class="notification-content">
                <div class="notification-icon">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="notification-text">
                    <div class="notification-title">${this.getStepTitle(t)} Complete!</div>
                    <div class="notification-message">${e.message||"Step completed successfully."}</div>
                </div>
            </div>
            <div class="notification-progress">
                <div class="progress-bar" style="width: ${this.progress}%"></div>
            </div>
        `,s}createTransitionOverlay(t,e,s){const i=document.createElement("div");return i.className="setup-transition-overlay",i.innerHTML=`
            <div class="transition-content">
                <div class="transition-icon">
                    <svg class="w-8 h-8 text-blue-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                <div class="transition-text">
                    <div class="transition-title">Moving to Next Step</div>
                    <div class="transition-message">${s||"Proceeding to the next setup step..."}</div>
                </div>
                <div class="transition-steps">
                    <span class="step-badge completed">${this.getStepTitle(t)}</span>
                    <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <span class="step-badge current">${this.getStepTitle(e)}</span>
                </div>
            </div>
        `,i}updateProgressIndicators(){document.querySelectorAll(".setup-progress-bar").forEach(s=>{s.style.width=`${this.progress}%`}),document.querySelectorAll(".setup-progress-text").forEach(s=>{s.textContent=`${this.progress}%`}),this.updateStepIndicators()}updateStepIndicators(){document.querySelectorAll(".setup-step-indicator").forEach(e=>{const s=e.dataset.step;this.completedSteps.includes(s)?(e.classList.add("completed"),e.classList.remove("current","upcoming")):s===this.currentStep?(e.classList.add("current"),e.classList.remove("completed","upcoming")):(e.classList.add("upcoming"),e.classList.remove("completed","current"))})}animateProgressBars(){document.querySelectorAll(".setup-progress-bar").forEach(e=>{e.style.transition="width 1s ease-out",e.style.width=`${this.progress}%`})}startStepTimer(){const t=document.querySelector(".setup-step-timer");t&&(this.updateStepTimer(t),this.stepTimerInterval=setInterval(()=>{this.updateStepTimer(t)},1e3))}updateStepTimer(t){if(!this.stepStartTime)return;const e=Date.now()-this.stepStartTime,s=Math.floor(e/1e3),i=Math.floor(s/60),r=s%60,p=i>0?`${i}:${r.toString().padStart(2,"0")}`:`${s}s`;t.textContent=p}showFormLoadingState(t){const e=t.querySelector('button[type="submit"], input[type="submit"]');if(e&&(e.disabled=!0,e.classList.add("loading"),!e.querySelector(".loading-spinner"))){const s=document.createElement("span");s.className="loading-spinner",s.innerHTML=`
                    <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                `,e.insertBefore(s,e.firstChild)}}trackFormSubmission(t){console.log(`Setup step form submitted: ${t}`)}getStepTitle(t){return{assets:"Assets",welcome:"Welcome",database:"Database",admin:"Admin User",storage:"Cloud Storage",complete:"Complete"}[t]||t.charAt(0).toUpperCase()+t.slice(1)}saveState(){const t={currentStep:this.currentStep,progress:this.progress,setupStartTime:this.setupStartTime,completedSteps:this.completedSteps,lastUpdated:Date.now()};try{localStorage.setItem("setup_progress_state",JSON.stringify(t))}catch(e){console.warn("Failed to save setup state to localStorage:",e)}}pauseTracking(){this.stepTimerInterval&&clearInterval(this.stepTimerInterval)}resumeTracking(){this.startStepTimer()}triggerStepTransition(t,e){const s=new CustomEvent("setup:step-transition",{detail:{fromStep:t,toStep:e,message:`Moving from ${this.getStepTitle(t)} to ${this.getStepTitle(e)}`}});document.dispatchEvent(s)}completeStep(t,e={}){const s=new CustomEvent("setup:step-completed",{detail:{step:t,details:e,progress:this.progress}});document.dispatchEvent(s)}updateProgress(t){this.progress=t,this.updateProgressIndicators(),this.saveState()}getElapsedTime(){return this.setupStartTime?Date.now()-this.setupStartTime:0}getStepElapsedTime(){return this.stepStartTime?Date.now()-this.stepStartTime:0}}const d=`
<style>
.setup-completion-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border: 1px solid #d1fae5;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    padding: 16px;
    max-width: 400px;
    z-index: 1000;
    transform: translateX(100%);
    transition: transform 0.3s ease-out;
}

.setup-completion-notification.show {
    transform: translateX(0);
}

.notification-content {
    display: flex;
    align-items: flex-start;
    margin-bottom: 8px;
}

.notification-icon {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    background: #dcfce7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
}

.notification-text {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #065f46;
    margin-bottom: 4px;
}

.notification-message {
    font-size: 14px;
    color: #047857;
}

.notification-progress {
    height: 4px;
    background: #d1fae5;
    border-radius: 2px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: #10b981;
    transition: width 0.5s ease-out;
}

.setup-transition-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1001;
    opacity: 0;
    transition: opacity 0.3s ease-out;
}

.setup-transition-overlay.show {
    opacity: 1;
}

.transition-content {
    background: white;
    border-radius: 12px;
    padding: 32px;
    text-align: center;
    max-width: 400px;
    margin: 16px;
}

.transition-icon {
    margin-bottom: 16px;
}

.transition-title {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.transition-message {
    color: #6b7280;
    margin-bottom: 24px;
}

.transition-steps {
    display: flex;
    align-items: center;
    justify-content: center;
}

.step-badge {
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 500;
}

.step-badge.completed {
    background: #dcfce7;
    color: #065f46;
}

.step-badge.current {
    background: #dbeafe;
    color: #1e40af;
}

.loading-spinner {
    display: inline-flex;
    align-items: center;
}
</style>
`;document.addEventListener("DOMContentLoaded",()=>{document.head.insertAdjacentHTML("beforeend",d),window.setupProgressTracker=new a});typeof o<"u"&&o.exports&&(o.exports=a)});export default l();
