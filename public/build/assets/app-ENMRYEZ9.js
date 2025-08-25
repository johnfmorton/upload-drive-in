var Rl=(e,t)=>()=>(t||e((t={exports:{}}).exports,t),t.exports);var sm=Rl((Om,Ai)=>{function uo(e,t){return function(){return e.apply(t,arguments)}}const{toString:Fl}=Object.prototype,{getPrototypeOf:sr}=Object,{iterator:Bi,toStringTag:ho}=Symbol,Di=(e=>t=>{const i=Fl.call(t);return e[i]||(e[i]=i.slice(8,-1).toLowerCase())})(Object.create(null)),pt=e=>(e=e.toLowerCase(),t=>Di(t)===e),Ni=e=>t=>typeof t===e,{isArray:ye}=Array,We=Ni("undefined");function Ye(e){return e!==null&&!We(e)&&e.constructor!==null&&!We(e.constructor)&&Q(e.constructor.isBuffer)&&e.constructor.isBuffer(e)}const po=pt("ArrayBuffer");function Ol(e){let t;return typeof ArrayBuffer<"u"&&ArrayBuffer.isView?t=ArrayBuffer.isView(e):t=e&&e.buffer&&po(e.buffer),t}const Ll=Ni("string"),Q=Ni("function"),fo=Ni("number"),Ze=e=>e!==null&&typeof e=="object",Ml=e=>e===!0||e===!1,yi=e=>{if(Di(e)!=="object")return!1;const t=sr(e);return(t===null||t===Object.prototype||Object.getPrototypeOf(t)===null)&&!(ho in e)&&!(Bi in e)},Pl=e=>{if(!Ze(e)||Ye(e))return!1;try{return Object.keys(e).length===0&&Object.getPrototypeOf(e)===Object.prototype}catch{return!1}},zl=pt("Date"),Il=pt("File"),Bl=pt("Blob"),Dl=pt("FileList"),Nl=e=>Ze(e)&&Q(e.pipe),Ul=e=>{let t;return e&&(typeof FormData=="function"&&e instanceof FormData||Q(e.append)&&((t=Di(e))==="formdata"||t==="object"&&Q(e.toString)&&e.toString()==="[object FormData]"))},Hl=pt("URLSearchParams"),[jl,ql,Vl,Wl]=["ReadableStream","Request","Response","Headers"].map(pt),Jl=e=>e.trim?e.trim():e.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,"");function ti(e,t,{allOwnKeys:i=!1}={}){if(e===null||typeof e>"u")return;let n,r;if(typeof e!="object"&&(e=[e]),ye(e))for(n=0,r=e.length;n<r;n++)t.call(null,e[n],n,e);else{if(Ye(e))return;const s=i?Object.getOwnPropertyNames(e):Object.keys(e),o=s.length;let a;for(n=0;n<o;n++)a=s[n],t.call(null,e[a],a,e)}}function mo(e,t){if(Ye(e))return null;t=t.toLowerCase();const i=Object.keys(e);let n=i.length,r;for(;n-- >0;)if(r=i[n],t===r.toLowerCase())return r;return null}const Qt=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:global,go=e=>!We(e)&&e!==Qt;function $n(){const{caseless:e}=go(this)&&this||{},t={},i=(n,r)=>{const s=e&&mo(t,r)||r;yi(t[s])&&yi(n)?t[s]=$n(t[s],n):yi(n)?t[s]=$n({},n):ye(n)?t[s]=n.slice():t[s]=n};for(let n=0,r=arguments.length;n<r;n++)arguments[n]&&ti(arguments[n],i);return t}const Kl=(e,t,i,{allOwnKeys:n}={})=>(ti(t,(r,s)=>{i&&Q(r)?e[s]=uo(r,i):e[s]=r},{allOwnKeys:n}),e),Ql=e=>(e.charCodeAt(0)===65279&&(e=e.slice(1)),e),Xl=(e,t,i,n)=>{e.prototype=Object.create(t.prototype,n),e.prototype.constructor=e,Object.defineProperty(e,"super",{value:t.prototype}),i&&Object.assign(e.prototype,i)},Gl=(e,t,i,n)=>{let r,s,o;const a={};if(t=t||{},e==null)return t;do{for(r=Object.getOwnPropertyNames(e),s=r.length;s-- >0;)o=r[s],(!n||n(o,e,t))&&!a[o]&&(t[o]=e[o],a[o]=!0);e=i!==!1&&sr(e)}while(e&&(!i||i(e,t))&&e!==Object.prototype);return t},Yl=(e,t,i)=>{e=String(e),(i===void 0||i>e.length)&&(i=e.length),i-=t.length;const n=e.indexOf(t,i);return n!==-1&&n===i},Zl=e=>{if(!e)return null;if(ye(e))return e;let t=e.length;if(!fo(t))return null;const i=new Array(t);for(;t-- >0;)i[t]=e[t];return i},tc=(e=>t=>e&&t instanceof e)(typeof Uint8Array<"u"&&sr(Uint8Array)),ec=(e,t)=>{const n=(e&&e[Bi]).call(e);let r;for(;(r=n.next())&&!r.done;){const s=r.value;t.call(e,s[0],s[1])}},ic=(e,t)=>{let i;const n=[];for(;(i=e.exec(t))!==null;)n.push(i);return n},nc=pt("HTMLFormElement"),rc=e=>e.toLowerCase().replace(/[-_\s]([a-z\d])(\w*)/g,function(i,n,r){return n.toUpperCase()+r}),Wr=(({hasOwnProperty:e})=>(t,i)=>e.call(t,i))(Object.prototype),sc=pt("RegExp"),bo=(e,t)=>{const i=Object.getOwnPropertyDescriptors(e),n={};ti(i,(r,s)=>{let o;(o=t(r,s,e))!==!1&&(n[s]=o||r)}),Object.defineProperties(e,n)},oc=e=>{bo(e,(t,i)=>{if(Q(e)&&["arguments","caller","callee"].indexOf(i)!==-1)return!1;const n=e[i];if(Q(n)){if(t.enumerable=!1,"writable"in t){t.writable=!1;return}t.set||(t.set=()=>{throw Error("Can not rewrite read-only method '"+i+"'")})}})},ac=(e,t)=>{const i={},n=r=>{r.forEach(s=>{i[s]=!0})};return ye(e)?n(e):n(String(e).split(t)),i},lc=()=>{},cc=(e,t)=>e!=null&&Number.isFinite(e=+e)?e:t;function uc(e){return!!(e&&Q(e.append)&&e[ho]==="FormData"&&e[Bi])}const dc=e=>{const t=new Array(10),i=(n,r)=>{if(Ze(n)){if(t.indexOf(n)>=0)return;if(Ye(n))return n;if(!("toJSON"in n)){t[r]=n;const s=ye(n)?[]:{};return ti(n,(o,a)=>{const l=i(o,r+1);!We(l)&&(s[a]=l)}),t[r]=void 0,s}}return n};return i(e,0)},hc=pt("AsyncFunction"),pc=e=>e&&(Ze(e)||Q(e))&&Q(e.then)&&Q(e.catch),vo=((e,t)=>e?setImmediate:t?((i,n)=>(Qt.addEventListener("message",({source:r,data:s})=>{r===Qt&&s===i&&n.length&&n.shift()()},!1),r=>{n.push(r),Qt.postMessage(i,"*")}))(`axios@${Math.random()}`,[]):i=>setTimeout(i))(typeof setImmediate=="function",Q(Qt.postMessage)),fc=typeof queueMicrotask<"u"?queueMicrotask.bind(Qt):typeof process<"u"&&process.nextTick||vo,mc=e=>e!=null&&Q(e[Bi]),m={isArray:ye,isArrayBuffer:po,isBuffer:Ye,isFormData:Ul,isArrayBufferView:Ol,isString:Ll,isNumber:fo,isBoolean:Ml,isObject:Ze,isPlainObject:yi,isEmptyObject:Pl,isReadableStream:jl,isRequest:ql,isResponse:Vl,isHeaders:Wl,isUndefined:We,isDate:zl,isFile:Il,isBlob:Bl,isRegExp:sc,isFunction:Q,isStream:Nl,isURLSearchParams:Hl,isTypedArray:tc,isFileList:Dl,forEach:ti,merge:$n,extend:Kl,trim:Jl,stripBOM:Ql,inherits:Xl,toFlatObject:Gl,kindOf:Di,kindOfTest:pt,endsWith:Yl,toArray:Zl,forEachEntry:ec,matchAll:ic,isHTMLForm:nc,hasOwnProperty:Wr,hasOwnProp:Wr,reduceDescriptors:bo,freezeMethods:oc,toObjectSet:ac,toCamelCase:rc,noop:lc,toFiniteNumber:cc,findKey:mo,global:Qt,isContextDefined:go,isSpecCompliantForm:uc,toJSONObject:dc,isAsyncFn:hc,isThenable:pc,setImmediate:vo,asap:fc,isIterable:mc};function C(e,t,i,n,r){Error.call(this),Error.captureStackTrace?Error.captureStackTrace(this,this.constructor):this.stack=new Error().stack,this.message=e,this.name="AxiosError",t&&(this.code=t),i&&(this.config=i),n&&(this.request=n),r&&(this.response=r,this.status=r.status?r.status:null)}m.inherits(C,Error,{toJSON:function(){return{message:this.message,name:this.name,description:this.description,number:this.number,fileName:this.fileName,lineNumber:this.lineNumber,columnNumber:this.columnNumber,stack:this.stack,config:m.toJSONObject(this.config),code:this.code,status:this.status}}});const yo=C.prototype,wo={};["ERR_BAD_OPTION_VALUE","ERR_BAD_OPTION","ECONNABORTED","ETIMEDOUT","ERR_NETWORK","ERR_FR_TOO_MANY_REDIRECTS","ERR_DEPRECATED","ERR_BAD_RESPONSE","ERR_BAD_REQUEST","ERR_CANCELED","ERR_NOT_SUPPORT","ERR_INVALID_URL"].forEach(e=>{wo[e]={value:e}});Object.defineProperties(C,wo);Object.defineProperty(yo,"isAxiosError",{value:!0});C.from=(e,t,i,n,r,s)=>{const o=Object.create(yo);return m.toFlatObject(e,o,function(l){return l!==Error.prototype},a=>a!=="isAxiosError"),C.call(o,e.message,t,i,n,r),o.cause=e,o.name=e.name,s&&Object.assign(o,s),o};const gc=null;function Rn(e){return m.isPlainObject(e)||m.isArray(e)}function _o(e){return m.endsWith(e,"[]")?e.slice(0,-2):e}function Jr(e,t,i){return e?e.concat(t).map(function(r,s){return r=_o(r),!i&&s?"["+r+"]":r}).join(i?".":""):t}function bc(e){return m.isArray(e)&&!e.some(Rn)}const vc=m.toFlatObject(m,{},null,function(t){return/^is[A-Z]/.test(t)});function Ui(e,t,i){if(!m.isObject(e))throw new TypeError("target must be an object");t=t||new FormData,i=m.toFlatObject(i,{metaTokens:!0,dots:!1,indexes:!1},!1,function(w,g){return!m.isUndefined(g[w])});const n=i.metaTokens,r=i.visitor||u,s=i.dots,o=i.indexes,l=(i.Blob||typeof Blob<"u"&&Blob)&&m.isSpecCompliantForm(t);if(!m.isFunction(r))throw new TypeError("visitor must be a function");function c(b){if(b===null)return"";if(m.isDate(b))return b.toISOString();if(m.isBoolean(b))return b.toString();if(!l&&m.isBlob(b))throw new C("Blob is not supported. Use a Buffer instead.");return m.isArrayBuffer(b)||m.isTypedArray(b)?l&&typeof Blob=="function"?new Blob([b]):Buffer.from(b):b}function u(b,w,g){let _=b;if(b&&!g&&typeof b=="object"){if(m.endsWith(w,"{}"))w=n?w:w.slice(0,-2),b=JSON.stringify(b);else if(m.isArray(b)&&bc(b)||(m.isFileList(b)||m.endsWith(w,"[]"))&&(_=m.toArray(b)))return w=_o(w),_.forEach(function(E,k){!(m.isUndefined(E)||E===null)&&t.append(o===!0?Jr([w],k,s):o===null?w:w+"[]",c(E))}),!1}return Rn(b)?!0:(t.append(Jr(g,w,s),c(b)),!1)}const d=[],p=Object.assign(vc,{defaultVisitor:u,convertValue:c,isVisitable:Rn});function f(b,w){if(!m.isUndefined(b)){if(d.indexOf(b)!==-1)throw Error("Circular reference detected in "+w.join("."));d.push(b),m.forEach(b,function(_,x){(!(m.isUndefined(_)||_===null)&&r.call(t,_,m.isString(x)?x.trim():x,w,p))===!0&&f(_,w?w.concat(x):[x])}),d.pop()}}if(!m.isObject(e))throw new TypeError("data must be an object");return f(e),t}function Kr(e){const t={"!":"%21","'":"%27","(":"%28",")":"%29","~":"%7E","%20":"+","%00":"\0"};return encodeURIComponent(e).replace(/[!'()~]|%20|%00/g,function(n){return t[n]})}function or(e,t){this._pairs=[],e&&Ui(e,this,t)}const xo=or.prototype;xo.append=function(t,i){this._pairs.push([t,i])};xo.toString=function(t){const i=t?function(n){return t.call(this,n,Kr)}:Kr;return this._pairs.map(function(r){return i(r[0])+"="+i(r[1])},"").join("&")};function yc(e){return encodeURIComponent(e).replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+").replace(/%5B/gi,"[").replace(/%5D/gi,"]")}function Eo(e,t,i){if(!t)return e;const n=i&&i.encode||yc;m.isFunction(i)&&(i={serialize:i});const r=i&&i.serialize;let s;if(r?s=r(t,i):s=m.isURLSearchParams(t)?t.toString():new or(t,i).toString(n),s){const o=e.indexOf("#");o!==-1&&(e=e.slice(0,o)),e+=(e.indexOf("?")===-1?"?":"&")+s}return e}class Qr{constructor(){this.handlers=[]}use(t,i,n){return this.handlers.push({fulfilled:t,rejected:i,synchronous:n?n.synchronous:!1,runWhen:n?n.runWhen:null}),this.handlers.length-1}eject(t){this.handlers[t]&&(this.handlers[t]=null)}clear(){this.handlers&&(this.handlers=[])}forEach(t){m.forEach(this.handlers,function(n){n!==null&&t(n)})}}const So={silentJSONParsing:!0,forcedJSONParsing:!0,clarifyTimeoutError:!1},wc=typeof URLSearchParams<"u"?URLSearchParams:or,_c=typeof FormData<"u"?FormData:null,xc=typeof Blob<"u"?Blob:null,Ec={isBrowser:!0,classes:{URLSearchParams:wc,FormData:_c,Blob:xc},protocols:["http","https","file","blob","url","data"]},ar=typeof window<"u"&&typeof document<"u",Fn=typeof navigator=="object"&&navigator||void 0,Sc=ar&&(!Fn||["ReactNative","NativeScript","NS"].indexOf(Fn.product)<0),Cc=typeof WorkerGlobalScope<"u"&&self instanceof WorkerGlobalScope&&typeof self.importScripts=="function",kc=ar&&window.location.href||"http://localhost",Ac=Object.freeze(Object.defineProperty({__proto__:null,hasBrowserEnv:ar,hasStandardBrowserEnv:Sc,hasStandardBrowserWebWorkerEnv:Cc,navigator:Fn,origin:kc},Symbol.toStringTag,{value:"Module"})),j={...Ac,...Ec};function Tc(e,t){return Ui(e,new j.classes.URLSearchParams,{visitor:function(i,n,r,s){return j.isNode&&m.isBuffer(i)?(this.append(n,i.toString("base64")),!1):s.defaultVisitor.apply(this,arguments)},...t})}function $c(e){return m.matchAll(/\w+|\[(\w*)]/g,e).map(t=>t[0]==="[]"?"":t[1]||t[0])}function Rc(e){const t={},i=Object.keys(e);let n;const r=i.length;let s;for(n=0;n<r;n++)s=i[n],t[s]=e[s];return t}function Co(e){function t(i,n,r,s){let o=i[s++];if(o==="__proto__")return!0;const a=Number.isFinite(+o),l=s>=i.length;return o=!o&&m.isArray(r)?r.length:o,l?(m.hasOwnProp(r,o)?r[o]=[r[o],n]:r[o]=n,!a):((!r[o]||!m.isObject(r[o]))&&(r[o]=[]),t(i,n,r[o],s)&&m.isArray(r[o])&&(r[o]=Rc(r[o])),!a)}if(m.isFormData(e)&&m.isFunction(e.entries)){const i={};return m.forEachEntry(e,(n,r)=>{t($c(n),r,i,0)}),i}return null}function Fc(e,t,i){if(m.isString(e))try{return(t||JSON.parse)(e),m.trim(e)}catch(n){if(n.name!=="SyntaxError")throw n}return(i||JSON.stringify)(e)}const ei={transitional:So,adapter:["xhr","http","fetch"],transformRequest:[function(t,i){const n=i.getContentType()||"",r=n.indexOf("application/json")>-1,s=m.isObject(t);if(s&&m.isHTMLForm(t)&&(t=new FormData(t)),m.isFormData(t))return r?JSON.stringify(Co(t)):t;if(m.isArrayBuffer(t)||m.isBuffer(t)||m.isStream(t)||m.isFile(t)||m.isBlob(t)||m.isReadableStream(t))return t;if(m.isArrayBufferView(t))return t.buffer;if(m.isURLSearchParams(t))return i.setContentType("application/x-www-form-urlencoded;charset=utf-8",!1),t.toString();let a;if(s){if(n.indexOf("application/x-www-form-urlencoded")>-1)return Tc(t,this.formSerializer).toString();if((a=m.isFileList(t))||n.indexOf("multipart/form-data")>-1){const l=this.env&&this.env.FormData;return Ui(a?{"files[]":t}:t,l&&new l,this.formSerializer)}}return s||r?(i.setContentType("application/json",!1),Fc(t)):t}],transformResponse:[function(t){const i=this.transitional||ei.transitional,n=i&&i.forcedJSONParsing,r=this.responseType==="json";if(m.isResponse(t)||m.isReadableStream(t))return t;if(t&&m.isString(t)&&(n&&!this.responseType||r)){const o=!(i&&i.silentJSONParsing)&&r;try{return JSON.parse(t)}catch(a){if(o)throw a.name==="SyntaxError"?C.from(a,C.ERR_BAD_RESPONSE,this,null,this.response):a}}return t}],timeout:0,xsrfCookieName:"XSRF-TOKEN",xsrfHeaderName:"X-XSRF-TOKEN",maxContentLength:-1,maxBodyLength:-1,env:{FormData:j.classes.FormData,Blob:j.classes.Blob},validateStatus:function(t){return t>=200&&t<300},headers:{common:{Accept:"application/json, text/plain, */*","Content-Type":void 0}}};m.forEach(["delete","get","head","post","put","patch"],e=>{ei.headers[e]={}});const Oc=m.toObjectSet(["age","authorization","content-length","content-type","etag","expires","from","host","if-modified-since","if-unmodified-since","last-modified","location","max-forwards","proxy-authorization","referer","retry-after","user-agent"]),Lc=e=>{const t={};let i,n,r;return e&&e.split(`
`).forEach(function(o){r=o.indexOf(":"),i=o.substring(0,r).trim().toLowerCase(),n=o.substring(r+1).trim(),!(!i||t[i]&&Oc[i])&&(i==="set-cookie"?t[i]?t[i].push(n):t[i]=[n]:t[i]=t[i]?t[i]+", "+n:n)}),t},Xr=Symbol("internals");function Re(e){return e&&String(e).trim().toLowerCase()}function wi(e){return e===!1||e==null?e:m.isArray(e)?e.map(wi):String(e)}function Mc(e){const t=Object.create(null),i=/([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g;let n;for(;n=i.exec(e);)t[n[1]]=n[2];return t}const Pc=e=>/^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(e.trim());function sn(e,t,i,n,r){if(m.isFunction(n))return n.call(this,t,i);if(r&&(t=i),!!m.isString(t)){if(m.isString(n))return t.indexOf(n)!==-1;if(m.isRegExp(n))return n.test(t)}}function zc(e){return e.trim().toLowerCase().replace(/([a-z\d])(\w*)/g,(t,i,n)=>i.toUpperCase()+n)}function Ic(e,t){const i=m.toCamelCase(" "+t);["get","set","has"].forEach(n=>{Object.defineProperty(e,n+i,{value:function(r,s,o){return this[n].call(this,t,r,s,o)},configurable:!0})})}let X=class{constructor(t){t&&this.set(t)}set(t,i,n){const r=this;function s(a,l,c){const u=Re(l);if(!u)throw new Error("header name must be a non-empty string");const d=m.findKey(r,u);(!d||r[d]===void 0||c===!0||c===void 0&&r[d]!==!1)&&(r[d||l]=wi(a))}const o=(a,l)=>m.forEach(a,(c,u)=>s(c,u,l));if(m.isPlainObject(t)||t instanceof this.constructor)o(t,i);else if(m.isString(t)&&(t=t.trim())&&!Pc(t))o(Lc(t),i);else if(m.isObject(t)&&m.isIterable(t)){let a={},l,c;for(const u of t){if(!m.isArray(u))throw TypeError("Object iterator must return a key-value pair");a[c=u[0]]=(l=a[c])?m.isArray(l)?[...l,u[1]]:[l,u[1]]:u[1]}o(a,i)}else t!=null&&s(i,t,n);return this}get(t,i){if(t=Re(t),t){const n=m.findKey(this,t);if(n){const r=this[n];if(!i)return r;if(i===!0)return Mc(r);if(m.isFunction(i))return i.call(this,r,n);if(m.isRegExp(i))return i.exec(r);throw new TypeError("parser must be boolean|regexp|function")}}}has(t,i){if(t=Re(t),t){const n=m.findKey(this,t);return!!(n&&this[n]!==void 0&&(!i||sn(this,this[n],n,i)))}return!1}delete(t,i){const n=this;let r=!1;function s(o){if(o=Re(o),o){const a=m.findKey(n,o);a&&(!i||sn(n,n[a],a,i))&&(delete n[a],r=!0)}}return m.isArray(t)?t.forEach(s):s(t),r}clear(t){const i=Object.keys(this);let n=i.length,r=!1;for(;n--;){const s=i[n];(!t||sn(this,this[s],s,t,!0))&&(delete this[s],r=!0)}return r}normalize(t){const i=this,n={};return m.forEach(this,(r,s)=>{const o=m.findKey(n,s);if(o){i[o]=wi(r),delete i[s];return}const a=t?zc(s):String(s).trim();a!==s&&delete i[s],i[a]=wi(r),n[a]=!0}),this}concat(...t){return this.constructor.concat(this,...t)}toJSON(t){const i=Object.create(null);return m.forEach(this,(n,r)=>{n!=null&&n!==!1&&(i[r]=t&&m.isArray(n)?n.join(", "):n)}),i}[Symbol.iterator](){return Object.entries(this.toJSON())[Symbol.iterator]()}toString(){return Object.entries(this.toJSON()).map(([t,i])=>t+": "+i).join(`
`)}getSetCookie(){return this.get("set-cookie")||[]}get[Symbol.toStringTag](){return"AxiosHeaders"}static from(t){return t instanceof this?t:new this(t)}static concat(t,...i){const n=new this(t);return i.forEach(r=>n.set(r)),n}static accessor(t){const n=(this[Xr]=this[Xr]={accessors:{}}).accessors,r=this.prototype;function s(o){const a=Re(o);n[a]||(Ic(r,o),n[a]=!0)}return m.isArray(t)?t.forEach(s):s(t),this}};X.accessor(["Content-Type","Content-Length","Accept","Accept-Encoding","User-Agent","Authorization"]);m.reduceDescriptors(X.prototype,({value:e},t)=>{let i=t[0].toUpperCase()+t.slice(1);return{get:()=>e,set(n){this[i]=n}}});m.freezeMethods(X);function on(e,t){const i=this||ei,n=t||i,r=X.from(n.headers);let s=n.data;return m.forEach(e,function(a){s=a.call(i,s,r.normalize(),t?t.status:void 0)}),r.normalize(),s}function ko(e){return!!(e&&e.__CANCEL__)}function we(e,t,i){C.call(this,e??"canceled",C.ERR_CANCELED,t,i),this.name="CanceledError"}m.inherits(we,C,{__CANCEL__:!0});function Ao(e,t,i){const n=i.config.validateStatus;!i.status||!n||n(i.status)?e(i):t(new C("Request failed with status code "+i.status,[C.ERR_BAD_REQUEST,C.ERR_BAD_RESPONSE][Math.floor(i.status/100)-4],i.config,i.request,i))}function Bc(e){const t=/^([-+\w]{1,25})(:?\/\/|:)/.exec(e);return t&&t[1]||""}function Dc(e,t){e=e||10;const i=new Array(e),n=new Array(e);let r=0,s=0,o;return t=t!==void 0?t:1e3,function(l){const c=Date.now(),u=n[s];o||(o=c),i[r]=l,n[r]=c;let d=s,p=0;for(;d!==r;)p+=i[d++],d=d%e;if(r=(r+1)%e,r===s&&(s=(s+1)%e),c-o<t)return;const f=u&&c-u;return f?Math.round(p*1e3/f):void 0}}function Nc(e,t){let i=0,n=1e3/t,r,s;const o=(c,u=Date.now())=>{i=u,r=null,s&&(clearTimeout(s),s=null),e(...c)};return[(...c)=>{const u=Date.now(),d=u-i;d>=n?o(c,u):(r=c,s||(s=setTimeout(()=>{s=null,o(r)},n-d)))},()=>r&&o(r)]}const Ti=(e,t,i=3)=>{let n=0;const r=Dc(50,250);return Nc(s=>{const o=s.loaded,a=s.lengthComputable?s.total:void 0,l=o-n,c=r(l),u=o<=a;n=o;const d={loaded:o,total:a,progress:a?o/a:void 0,bytes:l,rate:c||void 0,estimated:c&&a&&u?(a-o)/c:void 0,event:s,lengthComputable:a!=null,[t?"download":"upload"]:!0};e(d)},i)},Gr=(e,t)=>{const i=e!=null;return[n=>t[0]({lengthComputable:i,total:e,loaded:n}),t[1]]},Yr=e=>(...t)=>m.asap(()=>e(...t)),Uc=j.hasStandardBrowserEnv?((e,t)=>i=>(i=new URL(i,j.origin),e.protocol===i.protocol&&e.host===i.host&&(t||e.port===i.port)))(new URL(j.origin),j.navigator&&/(msie|trident)/i.test(j.navigator.userAgent)):()=>!0,Hc=j.hasStandardBrowserEnv?{write(e,t,i,n,r,s){const o=[e+"="+encodeURIComponent(t)];m.isNumber(i)&&o.push("expires="+new Date(i).toGMTString()),m.isString(n)&&o.push("path="+n),m.isString(r)&&o.push("domain="+r),s===!0&&o.push("secure"),document.cookie=o.join("; ")},read(e){const t=document.cookie.match(new RegExp("(^|;\\s*)("+e+")=([^;]*)"));return t?decodeURIComponent(t[3]):null},remove(e){this.write(e,"",Date.now()-864e5)}}:{write(){},read(){return null},remove(){}};function jc(e){return/^([a-z][a-z\d+\-.]*:)?\/\//i.test(e)}function qc(e,t){return t?e.replace(/\/?\/$/,"")+"/"+t.replace(/^\/+/,""):e}function To(e,t,i){let n=!jc(t);return e&&(n||i==!1)?qc(e,t):t}const Zr=e=>e instanceof X?{...e}:e;function se(e,t){t=t||{};const i={};function n(c,u,d,p){return m.isPlainObject(c)&&m.isPlainObject(u)?m.merge.call({caseless:p},c,u):m.isPlainObject(u)?m.merge({},u):m.isArray(u)?u.slice():u}function r(c,u,d,p){if(m.isUndefined(u)){if(!m.isUndefined(c))return n(void 0,c,d,p)}else return n(c,u,d,p)}function s(c,u){if(!m.isUndefined(u))return n(void 0,u)}function o(c,u){if(m.isUndefined(u)){if(!m.isUndefined(c))return n(void 0,c)}else return n(void 0,u)}function a(c,u,d){if(d in t)return n(c,u);if(d in e)return n(void 0,c)}const l={url:s,method:s,data:s,baseURL:o,transformRequest:o,transformResponse:o,paramsSerializer:o,timeout:o,timeoutMessage:o,withCredentials:o,withXSRFToken:o,adapter:o,responseType:o,xsrfCookieName:o,xsrfHeaderName:o,onUploadProgress:o,onDownloadProgress:o,decompress:o,maxContentLength:o,maxBodyLength:o,beforeRedirect:o,transport:o,httpAgent:o,httpsAgent:o,cancelToken:o,socketPath:o,responseEncoding:o,validateStatus:a,headers:(c,u,d)=>r(Zr(c),Zr(u),d,!0)};return m.forEach(Object.keys({...e,...t}),function(u){const d=l[u]||r,p=d(e[u],t[u],u);m.isUndefined(p)&&d!==a||(i[u]=p)}),i}const $o=e=>{const t=se({},e);let{data:i,withXSRFToken:n,xsrfHeaderName:r,xsrfCookieName:s,headers:o,auth:a}=t;t.headers=o=X.from(o),t.url=Eo(To(t.baseURL,t.url,t.allowAbsoluteUrls),e.params,e.paramsSerializer),a&&o.set("Authorization","Basic "+btoa((a.username||"")+":"+(a.password?unescape(encodeURIComponent(a.password)):"")));let l;if(m.isFormData(i)){if(j.hasStandardBrowserEnv||j.hasStandardBrowserWebWorkerEnv)o.setContentType(void 0);else if((l=o.getContentType())!==!1){const[c,...u]=l?l.split(";").map(d=>d.trim()).filter(Boolean):[];o.setContentType([c||"multipart/form-data",...u].join("; "))}}if(j.hasStandardBrowserEnv&&(n&&m.isFunction(n)&&(n=n(t)),n||n!==!1&&Uc(t.url))){const c=r&&s&&Hc.read(s);c&&o.set(r,c)}return t},Vc=typeof XMLHttpRequest<"u",Wc=Vc&&function(e){return new Promise(function(i,n){const r=$o(e);let s=r.data;const o=X.from(r.headers).normalize();let{responseType:a,onUploadProgress:l,onDownloadProgress:c}=r,u,d,p,f,b;function w(){f&&f(),b&&b(),r.cancelToken&&r.cancelToken.unsubscribe(u),r.signal&&r.signal.removeEventListener("abort",u)}let g=new XMLHttpRequest;g.open(r.method.toUpperCase(),r.url,!0),g.timeout=r.timeout;function _(){if(!g)return;const E=X.from("getAllResponseHeaders"in g&&g.getAllResponseHeaders()),S={data:!a||a==="text"||a==="json"?g.responseText:g.response,status:g.status,statusText:g.statusText,headers:E,config:e,request:g};Ao(function(R){i(R),w()},function(R){n(R),w()},S),g=null}"onloadend"in g?g.onloadend=_:g.onreadystatechange=function(){!g||g.readyState!==4||g.status===0&&!(g.responseURL&&g.responseURL.indexOf("file:")===0)||setTimeout(_)},g.onabort=function(){g&&(n(new C("Request aborted",C.ECONNABORTED,e,g)),g=null)},g.onerror=function(){n(new C("Network Error",C.ERR_NETWORK,e,g)),g=null},g.ontimeout=function(){let k=r.timeout?"timeout of "+r.timeout+"ms exceeded":"timeout exceeded";const S=r.transitional||So;r.timeoutErrorMessage&&(k=r.timeoutErrorMessage),n(new C(k,S.clarifyTimeoutError?C.ETIMEDOUT:C.ECONNABORTED,e,g)),g=null},s===void 0&&o.setContentType(null),"setRequestHeader"in g&&m.forEach(o.toJSON(),function(k,S){g.setRequestHeader(S,k)}),m.isUndefined(r.withCredentials)||(g.withCredentials=!!r.withCredentials),a&&a!=="json"&&(g.responseType=r.responseType),c&&([p,b]=Ti(c,!0),g.addEventListener("progress",p)),l&&g.upload&&([d,f]=Ti(l),g.upload.addEventListener("progress",d),g.upload.addEventListener("loadend",f)),(r.cancelToken||r.signal)&&(u=E=>{g&&(n(!E||E.type?new we(null,e,g):E),g.abort(),g=null)},r.cancelToken&&r.cancelToken.subscribe(u),r.signal&&(r.signal.aborted?u():r.signal.addEventListener("abort",u)));const x=Bc(r.url);if(x&&j.protocols.indexOf(x)===-1){n(new C("Unsupported protocol "+x+":",C.ERR_BAD_REQUEST,e));return}g.send(s||null)})},Jc=(e,t)=>{const{length:i}=e=e?e.filter(Boolean):[];if(t||i){let n=new AbortController,r;const s=function(c){if(!r){r=!0,a();const u=c instanceof Error?c:this.reason;n.abort(u instanceof C?u:new we(u instanceof Error?u.message:u))}};let o=t&&setTimeout(()=>{o=null,s(new C(`timeout ${t} of ms exceeded`,C.ETIMEDOUT))},t);const a=()=>{e&&(o&&clearTimeout(o),o=null,e.forEach(c=>{c.unsubscribe?c.unsubscribe(s):c.removeEventListener("abort",s)}),e=null)};e.forEach(c=>c.addEventListener("abort",s));const{signal:l}=n;return l.unsubscribe=()=>m.asap(a),l}},Kc=function*(e,t){let i=e.byteLength;if(i<t){yield e;return}let n=0,r;for(;n<i;)r=n+t,yield e.slice(n,r),n=r},Qc=async function*(e,t){for await(const i of Xc(e))yield*Kc(i,t)},Xc=async function*(e){if(e[Symbol.asyncIterator]){yield*e;return}const t=e.getReader();try{for(;;){const{done:i,value:n}=await t.read();if(i)break;yield n}}finally{await t.cancel()}},ts=(e,t,i,n)=>{const r=Qc(e,t);let s=0,o,a=l=>{o||(o=!0,n&&n(l))};return new ReadableStream({async pull(l){try{const{done:c,value:u}=await r.next();if(c){a(),l.close();return}let d=u.byteLength;if(i){let p=s+=d;i(p)}l.enqueue(new Uint8Array(u))}catch(c){throw a(c),c}},cancel(l){return a(l),r.return()}},{highWaterMark:2})},Hi=typeof fetch=="function"&&typeof Request=="function"&&typeof Response=="function",Ro=Hi&&typeof ReadableStream=="function",Gc=Hi&&(typeof TextEncoder=="function"?(e=>t=>e.encode(t))(new TextEncoder):async e=>new Uint8Array(await new Response(e).arrayBuffer())),Fo=(e,...t)=>{try{return!!e(...t)}catch{return!1}},Yc=Ro&&Fo(()=>{let e=!1;const t=new Request(j.origin,{body:new ReadableStream,method:"POST",get duplex(){return e=!0,"half"}}).headers.has("Content-Type");return e&&!t}),es=64*1024,On=Ro&&Fo(()=>m.isReadableStream(new Response("").body)),$i={stream:On&&(e=>e.body)};Hi&&(e=>{["text","arrayBuffer","blob","formData","stream"].forEach(t=>{!$i[t]&&($i[t]=m.isFunction(e[t])?i=>i[t]():(i,n)=>{throw new C(`Response type '${t}' is not supported`,C.ERR_NOT_SUPPORT,n)})})})(new Response);const Zc=async e=>{if(e==null)return 0;if(m.isBlob(e))return e.size;if(m.isSpecCompliantForm(e))return(await new Request(j.origin,{method:"POST",body:e}).arrayBuffer()).byteLength;if(m.isArrayBufferView(e)||m.isArrayBuffer(e))return e.byteLength;if(m.isURLSearchParams(e)&&(e=e+""),m.isString(e))return(await Gc(e)).byteLength},tu=async(e,t)=>{const i=m.toFiniteNumber(e.getContentLength());return i??Zc(t)},eu=Hi&&(async e=>{let{url:t,method:i,data:n,signal:r,cancelToken:s,timeout:o,onDownloadProgress:a,onUploadProgress:l,responseType:c,headers:u,withCredentials:d="same-origin",fetchOptions:p}=$o(e);c=c?(c+"").toLowerCase():"text";let f=Jc([r,s&&s.toAbortSignal()],o),b;const w=f&&f.unsubscribe&&(()=>{f.unsubscribe()});let g;try{if(l&&Yc&&i!=="get"&&i!=="head"&&(g=await tu(u,n))!==0){let S=new Request(t,{method:"POST",body:n,duplex:"half"}),O;if(m.isFormData(n)&&(O=S.headers.get("content-type"))&&u.setContentType(O),S.body){const[R,H]=Gr(g,Ti(Yr(l)));n=ts(S.body,es,R,H)}}m.isString(d)||(d=d?"include":"omit");const _="credentials"in Request.prototype;b=new Request(t,{...p,signal:f,method:i.toUpperCase(),headers:u.normalize().toJSON(),body:n,duplex:"half",credentials:_?d:void 0});let x=await fetch(b,p);const E=On&&(c==="stream"||c==="response");if(On&&(a||E&&w)){const S={};["status","statusText","headers"].forEach(V=>{S[V]=x[V]});const O=m.toFiniteNumber(x.headers.get("content-length")),[R,H]=a&&Gr(O,Ti(Yr(a),!0))||[];x=new Response(ts(x.body,es,R,()=>{H&&H(),w&&w()}),S)}c=c||"text";let k=await $i[m.findKey($i,c)||"text"](x,e);return!E&&w&&w(),await new Promise((S,O)=>{Ao(S,O,{data:k,headers:X.from(x.headers),status:x.status,statusText:x.statusText,config:e,request:b})})}catch(_){throw w&&w(),_&&_.name==="TypeError"&&/Load failed|fetch/i.test(_.message)?Object.assign(new C("Network Error",C.ERR_NETWORK,e,b),{cause:_.cause||_}):C.from(_,_&&_.code,e,b)}}),Ln={http:gc,xhr:Wc,fetch:eu};m.forEach(Ln,(e,t)=>{if(e){try{Object.defineProperty(e,"name",{value:t})}catch{}Object.defineProperty(e,"adapterName",{value:t})}});const is=e=>`- ${e}`,iu=e=>m.isFunction(e)||e===null||e===!1,Oo={getAdapter:e=>{e=m.isArray(e)?e:[e];const{length:t}=e;let i,n;const r={};for(let s=0;s<t;s++){i=e[s];let o;if(n=i,!iu(i)&&(n=Ln[(o=String(i)).toLowerCase()],n===void 0))throw new C(`Unknown adapter '${o}'`);if(n)break;r[o||"#"+s]=n}if(!n){const s=Object.entries(r).map(([a,l])=>`adapter ${a} `+(l===!1?"is not supported by the environment":"is not available in the build"));let o=t?s.length>1?`since :
`+s.map(is).join(`
`):" "+is(s[0]):"as no adapter specified";throw new C("There is no suitable adapter to dispatch the request "+o,"ERR_NOT_SUPPORT")}return n},adapters:Ln};function an(e){if(e.cancelToken&&e.cancelToken.throwIfRequested(),e.signal&&e.signal.aborted)throw new we(null,e)}function ns(e){return an(e),e.headers=X.from(e.headers),e.data=on.call(e,e.transformRequest),["post","put","patch"].indexOf(e.method)!==-1&&e.headers.setContentType("application/x-www-form-urlencoded",!1),Oo.getAdapter(e.adapter||ei.adapter)(e).then(function(n){return an(e),n.data=on.call(e,e.transformResponse,n),n.headers=X.from(n.headers),n},function(n){return ko(n)||(an(e),n&&n.response&&(n.response.data=on.call(e,e.transformResponse,n.response),n.response.headers=X.from(n.response.headers))),Promise.reject(n)})}const Lo="1.11.0",ji={};["object","boolean","number","function","string","symbol"].forEach((e,t)=>{ji[e]=function(n){return typeof n===e||"a"+(t<1?"n ":" ")+e}});const rs={};ji.transitional=function(t,i,n){function r(s,o){return"[Axios v"+Lo+"] Transitional option '"+s+"'"+o+(n?". "+n:"")}return(s,o,a)=>{if(t===!1)throw new C(r(o," has been removed"+(i?" in "+i:"")),C.ERR_DEPRECATED);return i&&!rs[o]&&(rs[o]=!0,console.warn(r(o," has been deprecated since v"+i+" and will be removed in the near future"))),t?t(s,o,a):!0}};ji.spelling=function(t){return(i,n)=>(console.warn(`${n} is likely a misspelling of ${t}`),!0)};function nu(e,t,i){if(typeof e!="object")throw new C("options must be an object",C.ERR_BAD_OPTION_VALUE);const n=Object.keys(e);let r=n.length;for(;r-- >0;){const s=n[r],o=t[s];if(o){const a=e[s],l=a===void 0||o(a,s,e);if(l!==!0)throw new C("option "+s+" must be "+l,C.ERR_BAD_OPTION_VALUE);continue}if(i!==!0)throw new C("Unknown option "+s,C.ERR_BAD_OPTION)}}const _i={assertOptions:nu,validators:ji},vt=_i.validators;let Zt=class{constructor(t){this.defaults=t||{},this.interceptors={request:new Qr,response:new Qr}}async request(t,i){try{return await this._request(t,i)}catch(n){if(n instanceof Error){let r={};Error.captureStackTrace?Error.captureStackTrace(r):r=new Error;const s=r.stack?r.stack.replace(/^.+\n/,""):"";try{n.stack?s&&!String(n.stack).endsWith(s.replace(/^.+\n.+\n/,""))&&(n.stack+=`
`+s):n.stack=s}catch{}}throw n}}_request(t,i){typeof t=="string"?(i=i||{},i.url=t):i=t||{},i=se(this.defaults,i);const{transitional:n,paramsSerializer:r,headers:s}=i;n!==void 0&&_i.assertOptions(n,{silentJSONParsing:vt.transitional(vt.boolean),forcedJSONParsing:vt.transitional(vt.boolean),clarifyTimeoutError:vt.transitional(vt.boolean)},!1),r!=null&&(m.isFunction(r)?i.paramsSerializer={serialize:r}:_i.assertOptions(r,{encode:vt.function,serialize:vt.function},!0)),i.allowAbsoluteUrls!==void 0||(this.defaults.allowAbsoluteUrls!==void 0?i.allowAbsoluteUrls=this.defaults.allowAbsoluteUrls:i.allowAbsoluteUrls=!0),_i.assertOptions(i,{baseUrl:vt.spelling("baseURL"),withXsrfToken:vt.spelling("withXSRFToken")},!0),i.method=(i.method||this.defaults.method||"get").toLowerCase();let o=s&&m.merge(s.common,s[i.method]);s&&m.forEach(["delete","get","head","post","put","patch","common"],b=>{delete s[b]}),i.headers=X.concat(o,s);const a=[];let l=!0;this.interceptors.request.forEach(function(w){typeof w.runWhen=="function"&&w.runWhen(i)===!1||(l=l&&w.synchronous,a.unshift(w.fulfilled,w.rejected))});const c=[];this.interceptors.response.forEach(function(w){c.push(w.fulfilled,w.rejected)});let u,d=0,p;if(!l){const b=[ns.bind(this),void 0];for(b.unshift(...a),b.push(...c),p=b.length,u=Promise.resolve(i);d<p;)u=u.then(b[d++],b[d++]);return u}p=a.length;let f=i;for(d=0;d<p;){const b=a[d++],w=a[d++];try{f=b(f)}catch(g){w.call(this,g);break}}try{u=ns.call(this,f)}catch(b){return Promise.reject(b)}for(d=0,p=c.length;d<p;)u=u.then(c[d++],c[d++]);return u}getUri(t){t=se(this.defaults,t);const i=To(t.baseURL,t.url,t.allowAbsoluteUrls);return Eo(i,t.params,t.paramsSerializer)}};m.forEach(["delete","get","head","options"],function(t){Zt.prototype[t]=function(i,n){return this.request(se(n||{},{method:t,url:i,data:(n||{}).data}))}});m.forEach(["post","put","patch"],function(t){function i(n){return function(s,o,a){return this.request(se(a||{},{method:t,headers:n?{"Content-Type":"multipart/form-data"}:{},url:s,data:o}))}}Zt.prototype[t]=i(),Zt.prototype[t+"Form"]=i(!0)});let ru=class Mo{constructor(t){if(typeof t!="function")throw new TypeError("executor must be a function.");let i;this.promise=new Promise(function(s){i=s});const n=this;this.promise.then(r=>{if(!n._listeners)return;let s=n._listeners.length;for(;s-- >0;)n._listeners[s](r);n._listeners=null}),this.promise.then=r=>{let s;const o=new Promise(a=>{n.subscribe(a),s=a}).then(r);return o.cancel=function(){n.unsubscribe(s)},o},t(function(s,o,a){n.reason||(n.reason=new we(s,o,a),i(n.reason))})}throwIfRequested(){if(this.reason)throw this.reason}subscribe(t){if(this.reason){t(this.reason);return}this._listeners?this._listeners.push(t):this._listeners=[t]}unsubscribe(t){if(!this._listeners)return;const i=this._listeners.indexOf(t);i!==-1&&this._listeners.splice(i,1)}toAbortSignal(){const t=new AbortController,i=n=>{t.abort(n)};return this.subscribe(i),t.signal.unsubscribe=()=>this.unsubscribe(i),t.signal}static source(){let t;return{token:new Mo(function(r){t=r}),cancel:t}}};function su(e){return function(i){return e.apply(null,i)}}function ou(e){return m.isObject(e)&&e.isAxiosError===!0}const Mn={Continue:100,SwitchingProtocols:101,Processing:102,EarlyHints:103,Ok:200,Created:201,Accepted:202,NonAuthoritativeInformation:203,NoContent:204,ResetContent:205,PartialContent:206,MultiStatus:207,AlreadyReported:208,ImUsed:226,MultipleChoices:300,MovedPermanently:301,Found:302,SeeOther:303,NotModified:304,UseProxy:305,Unused:306,TemporaryRedirect:307,PermanentRedirect:308,BadRequest:400,Unauthorized:401,PaymentRequired:402,Forbidden:403,NotFound:404,MethodNotAllowed:405,NotAcceptable:406,ProxyAuthenticationRequired:407,RequestTimeout:408,Conflict:409,Gone:410,LengthRequired:411,PreconditionFailed:412,PayloadTooLarge:413,UriTooLong:414,UnsupportedMediaType:415,RangeNotSatisfiable:416,ExpectationFailed:417,ImATeapot:418,MisdirectedRequest:421,UnprocessableEntity:422,Locked:423,FailedDependency:424,TooEarly:425,UpgradeRequired:426,PreconditionRequired:428,TooManyRequests:429,RequestHeaderFieldsTooLarge:431,UnavailableForLegalReasons:451,InternalServerError:500,NotImplemented:501,BadGateway:502,ServiceUnavailable:503,GatewayTimeout:504,HttpVersionNotSupported:505,VariantAlsoNegotiates:506,InsufficientStorage:507,LoopDetected:508,NotExtended:510,NetworkAuthenticationRequired:511};Object.entries(Mn).forEach(([e,t])=>{Mn[t]=e});function Po(e){const t=new Zt(e),i=uo(Zt.prototype.request,t);return m.extend(i,Zt.prototype,t,{allOwnKeys:!0}),m.extend(i,t,null,{allOwnKeys:!0}),i.create=function(r){return Po(se(e,r))},i}const B=Po(ei);B.Axios=Zt;B.CanceledError=we;B.CancelToken=ru;B.isCancel=ko;B.VERSION=Lo;B.toFormData=Ui;B.AxiosError=C;B.Cancel=B.CanceledError;B.all=function(t){return Promise.all(t)};B.spread=su;B.isAxiosError=ou;B.mergeConfig=se;B.AxiosHeaders=X;B.formToJSON=e=>Co(m.isHTMLForm(e)?new FormData(e):e);B.getAdapter=Oo.getAdapter;B.HttpStatusCode=Mn;B.default=B;const{Axios:cm,AxiosError:um,CanceledError:dm,isCancel:hm,CancelToken:pm,VERSION:fm,all:mm,Cancel:gm,isAxiosError:bm,spread:vm,toFormData:ym,AxiosHeaders:wm,HttpStatusCode:_m,formToJSON:xm,getAdapter:Em,mergeConfig:Sm}=B;window.axios=B;window.axios.defaults.headers.common["X-Requested-With"]="XMLHttpRequest";window.fileManagerState=window.fileManagerState||{initialized:!1,initSource:null,instance:null};function au(e,t={}){return window.fileManagerAlreadyInitialized?(console.info(`File Manager already initialized. Skipping ${e} initialization.`),window.fileManagerState.instance):window.fileManagerState.initialized?(console.info(`File Manager already initialized by ${window.fileManagerState.initSource}. Skipping ${e} initialization.`),window.fileManagerState.instance):(console.info(`Initializing File Manager from ${e}`),window.fileManagerAlreadyInitialized=!0,window.fileManagerState.initialized=!0,window.fileManagerState.initSource=e,e==="lazy-loader"?window.fileManagerState.instance=new FileManagerLazyLoader(t):e==="alpine"&&console.info("Alpine.js initialization will set the instance when ready"),window.fileManagerState.instance)}window.initializeFileManager=au;window.debugFileManager=function(){console.group("File Manager Debug Information"),console.log("Alpine.js loaded:",typeof window.Alpine<"u");const e=document.querySelector("[data-lazy-container]");if(console.log("Container exists:",!!e),e&&(console.log("Container has x-data:",e.hasAttribute("x-data")),console.log("Container Alpine data stack:",e._x_dataStack),window.Alpine))try{const t=window.Alpine.$data(e);console.log("Alpine data:",t),console.log("Files count:",t.files?t.files.length:"N/A"),console.log("Filtered files count:",t.filteredFiles?t.filteredFiles.length:"N/A")}catch(t){console.error("Error accessing Alpine data:",t)}if(console.log("File Manager State:",window.fileManagerState),console.log("Already Initialized:",window.fileManagerAlreadyInitialized),window.FileManagerLazyLoader&&(console.log("Lazy Loader class exists"),window.fileManagerState&&window.fileManagerState.instance)){const t=window.fileManagerState.instance;console.log("Lazy Loader instance:",t),console.log("Cache stats:",t.getCacheStats?t.getCacheStats():"N/A")}return console.groupEnd(),`
    File Manager Debug Complete. Check console for details.
    
    Common fixes:
    1. If no files are showing, try running: 
       - window.location.reload() to refresh the page
       - Or manually trigger file loading with: window.fileManagerState.instance.loadMore()
       
    2. If Alpine.js component is not detected:
       - Check if the container has the correct x-data attribute
       - Ensure Alpine.js is properly initialized
       
    3. If files are loaded but not displayed:
       - Check the filters in the UI
       - Try clearing filters with: window.fileManagerState.instance.clearAllFilters()
    `};document.addEventListener("DOMContentLoaded",()=>{if(document.querySelector("[data-lazy-container]")){const t=document.createElement("button");t.textContent="Debug File Manager",t.className="debug-button hidden",t.style.position="fixed",t.style.bottom="10px",t.style.right="10px",t.style.zIndex="9999",t.style.padding="5px 10px",t.style.background="#f0f0f0",t.style.border="1px solid #ccc",t.style.borderRadius="4px",t.addEventListener("click",()=>{console.clear(),window.debugFileManager()}),document.body.appendChild(t),document.addEventListener("keydown",i=>{i.ctrlKey&&i.shiftKey&&i.key==="D"&&(t.classList.toggle("hidden"),i.preventDefault())})}});class lu{constructor(){this.currentStep=null,this.progressBar=null,this.init()}init(){this.currentStep=this.getCurrentStep(),this.progressBar=document.querySelector("[data-progress-bar]"),this.initializeStepFunctionality(),this.initializeFormSubmission(),this.initializeProgressIndicator(),console.log("Setup Wizard initialized for step:",this.currentStep)}getCurrentStep(){const t=document.querySelector("[data-setup-step]");return t?t.dataset.setupStep:"welcome"}initializeStepFunctionality(){switch(this.currentStep){case"database":this.initializeDatabaseStep();break;case"admin":this.initializeAdminStep();break;case"storage":this.initializeStorageStep();break}}initializeDatabaseStep(){const t=document.getElementById("sqlite"),i=document.getElementById("mysql"),n=document.getElementById("sqlite-config"),r=document.getElementById("mysql-config"),s=document.getElementById("test-connection");if(!t||!i)return;const o=()=>{t.checked?(n==null||n.classList.remove("hidden"),r==null||r.classList.add("hidden"),this.updateFormValidation("sqlite")):(n==null||n.classList.add("hidden"),r==null||r.classList.remove("hidden"),this.updateFormValidation("mysql"))};t.addEventListener("change",o),i.addEventListener("change",o),o(),s&&s.addEventListener("click",()=>{this.testDatabaseConnection()}),this.initializeDatabaseValidation()}initializeAdminStep(){const t=document.getElementById("password"),i=document.getElementById("password_confirmation"),n=document.getElementById("email"),r=document.getElementById("toggle-password");!t||!i||!n||(r&&r.addEventListener("click",()=>{this.togglePasswordVisibility(t,r)}),t.addEventListener("input",()=>{this.checkPasswordStrength(t.value),this.validatePasswordMatch()}),i.addEventListener("input",()=>{this.validatePasswordMatch()}),n.addEventListener("blur",()=>{this.validateEmailAvailability(n.value)}),this.initializeAdminFormValidation())}initializeStorageStep(){const t=document.getElementById("toggle-secret"),i=document.getElementById("google_client_secret"),n=document.getElementById("test-google-connection"),r=document.getElementById("skip_storage"),s=document.getElementById("google-drive-config");t&&i&&t.addEventListener("click",()=>{this.togglePasswordVisibility(i,t)}),r&&s&&r.addEventListener("change",()=>{this.toggleStorageRequirements(r.checked,s)}),n&&n.addEventListener("click",()=>{this.testGoogleDriveConnection()}),this.initializeStorageValidation()}initializeFormSubmission(){document.querySelectorAll('form[id$="-form"]').forEach(i=>{i.addEventListener("submit",n=>{this.handleFormSubmission(i,n)})})}initializeProgressIndicator(){if(this.progressBar){const t=this.progressBar.style.width;this.animateProgressBar(t)}this.updateStepIndicators()}async testDatabaseConnection(){var r,s,o,a,l;const t=document.getElementById("test-connection"),i=document.getElementById("connection-status");if(!t||!i)return;const n=t.innerHTML;try{this.setButtonLoading(t,"Testing...");const c=new FormData;c.append("_token",this.getCsrfToken()),c.append("host",((r=document.getElementById("mysql_host"))==null?void 0:r.value)||""),c.append("port",((s=document.getElementById("mysql_port"))==null?void 0:s.value)||""),c.append("database",((o=document.getElementById("mysql_database"))==null?void 0:o.value)||""),c.append("username",((a=document.getElementById("mysql_username"))==null?void 0:a.value)||""),c.append("password",((l=document.getElementById("mysql_password"))==null?void 0:l.value)||"");const d=await(await fetch("/setup/ajax/test-database",{method:"POST",body:c,headers:{"X-Requested-With":"XMLHttpRequest"}})).json();i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(d.success,d.message)}catch(c){console.error("Database connection test failed:",c),i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(!1,"Connection test failed. Please try again.")}finally{this.restoreButtonState(t,n)}}async testGoogleDriveConnection(){var r,s;const t=document.getElementById("test-google-connection"),i=document.getElementById("google-connection-status");if(!t||!i)return;const n=t.innerHTML;try{this.setButtonLoading(t,"Testing...");const o=new FormData;o.append("_token",this.getCsrfToken()),o.append("client_id",((r=document.getElementById("google_client_id"))==null?void 0:r.value)||""),o.append("client_secret",((s=document.getElementById("google_client_secret"))==null?void 0:s.value)||"");const l=await(await fetch("/setup/ajax/test-storage",{method:"POST",body:o,headers:{"X-Requested-With":"XMLHttpRequest"}})).json();i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(l.success,l.message)}catch(o){console.error("Google Drive connection test failed:",o),i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(!1,"Connection test failed. Please try again.")}finally{this.restoreButtonState(t,n)}}async validateEmailAvailability(t){if(!(!t||!this.isValidEmail(t)))try{const i=new FormData;i.append("_token",this.getCsrfToken()),i.append("email",t);const r=await(await fetch("/setup/ajax/validate-email",{method:"POST",body:i,headers:{"X-Requested-With":"XMLHttpRequest"}})).json();this.showEmailValidationResult(r.available,r.message)}catch(i){console.error("Email validation failed:",i)}}checkPasswordStrength(t){const i=document.getElementById("strength-bar"),n=document.getElementById("strength-text");if(!i||!n)return;const r=this.calculatePasswordScore(t);i.style.width=r+"%",r===0?(n.textContent="Enter password",n.className="font-medium text-gray-400",i.className="h-2 rounded-full transition-all duration-300 bg-gray-300"):r<50?(n.textContent="Weak",n.className="font-medium text-red-600",i.className="h-2 rounded-full transition-all duration-300 bg-red-500"):r<75?(n.textContent="Fair",n.className="font-medium text-yellow-600",i.className="h-2 rounded-full transition-all duration-300 bg-yellow-500"):r<100?(n.textContent="Good",n.className="font-medium text-blue-600",i.className="h-2 rounded-full transition-all duration-300 bg-blue-500"):(n.textContent="Strong",n.className="font-medium text-green-600",i.className="h-2 rounded-full transition-all duration-300 bg-green-500"),this.updatePasswordRequirements(t)}calculatePasswordScore(t){let i=0;return t.length>=8&&(i+=20),/[A-Z]/.test(t)&&(i+=20),/[a-z]/.test(t)&&(i+=20),/[0-9]/.test(t)&&(i+=20),/[^A-Za-z0-9]/.test(t)&&(i+=20),i}updatePasswordRequirements(t){[{id:"req-length",test:t.length>=8},{id:"req-uppercase",test:/[A-Z]/.test(t)},{id:"req-lowercase",test:/[a-z]/.test(t)},{id:"req-number",test:/[0-9]/.test(t)},{id:"req-special",test:/[^A-Za-z0-9]/.test(t)}].forEach(n=>{var s,o,a,l;const r=document.getElementById(n.id);r&&(n.test?(r.classList.remove("text-gray-600"),r.classList.add("text-green-600"),(s=r.querySelector("svg"))==null||s.classList.remove("text-gray-400"),(o=r.querySelector("svg"))==null||o.classList.add("text-green-500")):(r.classList.remove("text-green-600"),r.classList.add("text-gray-600"),(a=r.querySelector("svg"))==null||a.classList.remove("text-green-500"),(l=r.querySelector("svg"))==null||l.classList.add("text-gray-400")))})}validatePasswordMatch(){var a,l;const t=((a=document.getElementById("password"))==null?void 0:a.value)||"",i=((l=document.getElementById("password_confirmation"))==null?void 0:l.value)||"",n=document.getElementById("password-match-indicator"),r=document.getElementById("match-success"),s=document.getElementById("match-error"),o=document.getElementById("password-match-text");if(!(!n||!r||!s||!o)){if(i.length===0){n.classList.add("hidden"),o.textContent="Re-enter your password to confirm",o.className="mt-2 text-sm text-gray-500";return}n.classList.remove("hidden"),t===i?(r.classList.remove("hidden"),s.classList.add("hidden"),o.textContent="Passwords match",o.className="mt-2 text-sm text-green-600"):(r.classList.add("hidden"),s.classList.remove("hidden"),o.textContent="Passwords do not match",o.className="mt-2 text-sm text-red-600")}}togglePasswordVisibility(t,i){const n=t.getAttribute("type")==="password"?"text":"password";t.setAttribute("type",n);const r=i.querySelector('[id$="eye-closed"], [id$="-eye-closed"]'),s=i.querySelector('[id$="eye-open"], [id$="-eye-open"]');n==="text"?(r==null||r.classList.add("hidden"),s==null||s.classList.remove("hidden")):(r==null||r.classList.remove("hidden"),s==null||s.classList.add("hidden"))}toggleStorageRequirements(t,i){t?(i.style.opacity="0.5",i.style.pointerEvents="none",document.getElementById("google_client_id").required=!1,document.getElementById("google_client_secret").required=!1):(i.style.opacity="1",i.style.pointerEvents="auto",document.getElementById("google_client_id").required=!0,document.getElementById("google_client_secret").required=!0)}handleFormSubmission(t,i){const n=t.querySelector('button[type="submit"]');if(!n)return;const r=n.innerHTML;this.setButtonLoading(n,"Processing...");const s=t.querySelectorAll("input, select, textarea, button");s.forEach(o=>{o.disabled=!0}),setTimeout(()=>{s.forEach(o=>{o.disabled=!1}),this.restoreButtonState(n,r)},1e4)}initializeDatabaseValidation(){["mysql_host","mysql_port","mysql_database","mysql_username"].forEach(i=>{const n=document.getElementById(i);n&&n.addEventListener("blur",()=>{this.validateDatabaseField(i,n.value)})})}initializeAdminFormValidation(){const t=document.getElementById("email"),i=document.getElementById("password"),n=document.getElementById("password_confirmation"),r=document.getElementById("submit-btn");if(!t||!i||!n||!r)return;const s=()=>{const o=t.value,a=i.value,l=n.value,c=this.calculatePasswordScore(a),u=this.isValidEmail(o)&&c===100&&a===l&&l.length>0;r.disabled=!u};t.addEventListener("input",s),i.addEventListener("input",s),n.addEventListener("input",s),s()}initializeStorageValidation(){const t=document.getElementById("google_client_id"),i=document.getElementById("google_client_secret");t&&t.addEventListener("blur",()=>{this.validateGoogleClientId(t.value)}),i&&i.addEventListener("blur",()=>{this.validateGoogleClientSecret(i.value)})}validateDatabaseField(t,i){const n=document.getElementById(t);if(!n)return;let r=!0,s="";switch(t){case"mysql_host":r=i.length>0,s=r?"":"Host is required";break;case"mysql_port":r=/^\d+$/.test(i)&&parseInt(i)>0&&parseInt(i)<=65535,s=r?"":"Port must be a valid number between 1 and 65535";break;case"mysql_database":r=/^[a-zA-Z0-9_]+$/.test(i),s=r?"":"Database name can only contain letters, numbers, and underscores";break;case"mysql_username":r=i.length>0,s=r?"":"Username is required";break}this.showFieldValidation(n,r,s)}validateGoogleClientId(t){const i=document.getElementById("google_client_id");if(!i)return;const n=/^[0-9]+-[a-zA-Z0-9]+\.apps\.googleusercontent\.com$/.test(t),r=n?"":"Client ID should end with .apps.googleusercontent.com";this.showFieldValidation(i,n,r)}validateGoogleClientSecret(t){const i=document.getElementById("google_client_secret");if(!i)return;const n=/^GOCSPX-[a-zA-Z0-9_-]+$/.test(t),r=n?"":"Client Secret should start with GOCSPX-";this.showFieldValidation(i,n,r)}showFieldValidation(t,i,n){t.classList.remove("border-red-300","border-green-300");const r=t.parentNode.querySelector(".validation-message");if(r&&r.remove(),n){t.classList.add(i?"border-green-300":"border-red-300");const s=document.createElement("p");s.className=`mt-1 text-sm validation-message ${i?"text-green-600":"text-red-600"}`,s.textContent=n,t.parentNode.appendChild(s)}}showEmailValidationResult(t,i){const n=document.getElementById("email");n&&this.showFieldValidation(n,t,i)}updateFormValidation(t){["mysql_host","mysql_port","mysql_database","mysql_username"].forEach(n=>{const r=document.getElementById(n);r&&(r.required=t==="mysql")})}animateProgressBar(t){this.progressBar&&(this.progressBar.style.transition="width 0.5s ease-out",setTimeout(()=>{this.progressBar.style.width=t},100))}updateStepIndicators(){document.querySelectorAll("[data-step-indicator]").forEach(i=>{const n=i.dataset.stepIndicator,r=this.isStepCompleted(n),s=n===this.currentStep;r&&i.classList.add("completed"),s&&i.classList.add("current")})}isStepCompleted(t){if(!this.currentStep||!t)return!1;const i=["welcome","database","admin","storage","complete"],n=i.indexOf(this.currentStep),r=i.indexOf(t);return n===-1||r===-1?!1:r<n}setButtonLoading(t,i){t.disabled=!0,t.innerHTML=`
            <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            ${i}
        `}restoreButtonState(t,i){t.disabled=!1,t.innerHTML=i}formatConnectionResult(t,i){return`
            <div class="${t?"text-green-600":"text-red-600"}">
                <div class="flex items-center">
                    ${t?`<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>`:`<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>`}
                    ${t?"Connection successful!":"Connection failed"}
                </div>
                ${i?`<p class="text-sm mt-1">${i}</p>`:""}
            </div>
        `}getCsrfToken(){const t=document.querySelector('meta[name="csrf-token"]');return t?t.getAttribute("content"):""}isValidEmail(t){return/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t)}}document.addEventListener("DOMContentLoaded",function(){if(document.querySelector("[data-setup-step]")&&window.location.pathname.startsWith("/setup"))try{new lu}catch(t){console.warn("Setup wizard initialization failed:",t)}});class zo{constructor(){this.currentTestJobId=null,this.testStartTime=null,this.pollingInterval=null,this.elapsedTimeInterval=null,this.initializeElements(),this.bindEvents(),this.loadQueueHealth()}initializeElements(){this.testQueueBtn=document.getElementById("test-queue-btn"),this.testQueueBtnText=document.getElementById("test-queue-btn-text"),this.refreshQueueHealthBtn=document.getElementById("refresh-queue-health-btn"),this.queueStatus=document.getElementById("queue-status"),this.recentJobsCount=document.getElementById("recent-jobs-count"),this.recentJobsDescription=document.getElementById("recent-jobs-description"),this.failedJobsCount=document.getElementById("failed-jobs-count"),this.testResultsSection=document.getElementById("test-results-section"),this.currentTestProgress=document.getElementById("current-test-progress"),this.testProgressMessage=document.getElementById("test-progress-message"),this.testElapsedTime=document.getElementById("test-elapsed-time"),this.testResultsDisplay=document.getElementById("test-results-display"),this.failedJobsDetailsSection=document.getElementById("failed-jobs-details-section"),this.failedJobsList=document.getElementById("failed-jobs-list")}bindEvents(){this.testQueueBtn&&this.testQueueBtn.addEventListener("click",()=>this.startQueueTest()),this.refreshQueueHealthBtn&&this.refreshQueueHealthBtn.addEventListener("click",()=>this.loadQueueHealth())}async startQueueTest(){var t;if(this.currentTestJobId){console.warn("Test already in progress");return}try{this.setTestInProgress(!0),this.testStartTime=Date.now(),this.startElapsedTimeCounter();const i=await this.dispatchTestJob();if(i.success&&i.data)this.currentTestJobId=i.data.test_job_id,this.updateProgressMessage("Test job dispatched, waiting for processing..."),this.startPolling();else throw new Error(i.message||((t=i.error)==null?void 0:t.message)||"Failed to dispatch test job")}catch(i){console.error("Queue test failed:",i),this.handleTestError(i.message)}}async dispatchTestJob(){var i;const t=await fetch("/admin/queue/test",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":((i=document.querySelector('meta[name="csrf-token"]'))==null?void 0:i.getAttribute("content"))||""},body:JSON.stringify({delay:0})});if(!t.ok)throw new Error(`HTTP ${t.status}: ${t.statusText}`);return await t.json()}startPolling(){this.pollingInterval&&clearInterval(this.pollingInterval),this.pollingInterval=setInterval(async()=>{try{await this.checkTestJobStatus()}catch(t){console.error("Polling error:",t),this.handleTestError("Failed to check test status")}},1e3),setTimeout(()=>{this.currentTestJobId&&this.handleTestTimeout()},3e4)}async checkTestJobStatus(){var n;if(!this.currentTestJobId)return;const t=await fetch(`/admin/queue/test/status?test_job_id=${this.currentTestJobId}`,{method:"GET",headers:{"X-CSRF-TOKEN":((n=document.querySelector('meta[name="csrf-token"]'))==null?void 0:n.getAttribute("content"))||""}});if(!t.ok)throw new Error(`HTTP ${t.status}: ${t.statusText}`);const i=await t.json();if(i.success&&i.data&&i.data.status){const r=i.data.status;switch(r.status){case"completed":this.handleTestSuccess(r);break;case"failed":this.handleTestFailure(r);break;case"timeout":this.handleTestTimeout();break;case"processing":this.updateProgressMessage("Test job is being processed...");break;case"pending":this.updateProgressMessage("Test job is queued, waiting for worker...");break}}}handleTestSuccess(t){this.stopTest();const i=t.processing_time||0,n=Date.now()-this.testStartTime,r={status:"success",message:`Queue worker is functioning properly! Job completed in ${i.toFixed(2)}s`,details:{processing_time:i,total_time:(n/1e3).toFixed(2),completed_at:t.completed_at||new Date().toISOString(),job_id:this.currentTestJobId},timestamp:Date.now()};this.displayTestResult(r),this.showSuccessNotification(`Queue worker completed test in ${i.toFixed(2)}s`)}handleTestFailure(t){this.stopTest();const i={status:"failed",message:"Queue test failed: "+(t.error_message||"Unknown error"),details:{error:t.error_message,failed_at:t.failed_at||new Date().toISOString(),job_id:this.currentTestJobId},timestamp:Date.now()};this.displayTestResult(i)}handleTestTimeout(){this.stopTest();const t={status:"timeout",message:"Queue test timed out after 30 seconds. The queue worker may not be running.",details:{timeout_duration:30,job_id:this.currentTestJobId,timed_out_at:new Date().toISOString()},timestamp:Date.now()};this.displayTestResult(t)}handleTestError(t){this.stopTest();const i={status:"error",message:"Test error: "+t,details:{error:t,error_at:new Date().toISOString()},timestamp:Date.now()};this.displayTestResult(i),this.showDetailedError(new Error(t),"Queue test execution")}stopTest(){this.pollingInterval&&(clearInterval(this.pollingInterval),this.pollingInterval=null),this.elapsedTimeInterval&&(clearInterval(this.elapsedTimeInterval),this.elapsedTimeInterval=null),this.currentTestJobId=null,this.testStartTime=null,this.setTestInProgress(!1),this.hideCurrentTestProgress()}setTestInProgress(t){this.testQueueBtn&&(this.setLoadingStateWithAnimation(t),t&&this.showCurrentTestProgress())}showCurrentTestProgress(){this.currentTestProgress&&this.currentTestProgress.classList.remove("hidden"),this.testResultsSection&&this.testResultsSection.classList.remove("hidden")}hideCurrentTestProgress(){this.currentTestProgress&&this.currentTestProgress.classList.add("hidden")}updateProgressMessage(t){this.testProgressMessage&&this.updateProgressWithAnimation(t)}startElapsedTimeCounter(){this.elapsedTimeInterval&&clearInterval(this.elapsedTimeInterval),this.elapsedTimeInterval=setInterval(()=>{if(this.testStartTime&&this.testElapsedTime){const t=((Date.now()-this.testStartTime)/1e3).toFixed(1);this.testElapsedTime.textContent=`(${t}s)`}},100)}displayTestResult(t){if(!this.testResultsDisplay)return;const i=this.createTestResultElement(t);i.style.opacity="0",i.style.transform="translateY(-10px)",i.style.transition="all 0.3s ease-in-out",this.testResultsDisplay.insertBefore(i,this.testResultsDisplay.firstChild),setTimeout(()=>{i.style.opacity="1",i.style.transform="translateY(0)"},10),this.testResultsSection&&this.testResultsSection.classList.remove("hidden"),this.addResultAnimation(i,t.status);const n=this.testResultsDisplay.children;for(;n.length>5;){const r=n[n.length-1];this.animateResultRemoval(r)}}createTestResultElement(t){var l,c;const i=document.createElement("div");let n,r,s,o="";switch(t.status){case"success":n="bg-green-50 border-green-200",r="text-green-900",o="animate-pulse-success",s=`<svg class="h-5 w-5 text-green-600 animate-bounce-once" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`;break;case"failed":case"error":n="bg-red-50 border-red-200",r="text-red-900",o="animate-pulse-error",s=`<svg class="h-5 w-5 text-red-600 animate-shake" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`;break;case"timeout":n="bg-yellow-50 border-yellow-200",r="text-yellow-900",o="animate-pulse-warning",s=`<svg class="h-5 w-5 text-yellow-600 animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`;break}const a=new Date(t.timestamp).toLocaleString();return i.className=`border rounded-lg p-4 ${n} ${o} transition-all duration-300 hover:shadow-md`,i.innerHTML=`
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    ${s}
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-sm font-medium ${r}">
                        ${t.message}
                    </div>
                    <div class="text-sm text-gray-600 mt-1">
                        ${a}
                        ${(l=t.details)!=null&&l.processing_time?` • Processing: ${t.details.processing_time}s`:""}
                        ${(c=t.details)!=null&&c.total_time?` • Total: ${t.details.total_time}s`:""}
                    </div>
                    ${this.createResultDetailsSection(t)}
                </div>
            </div>
        `,i}async loadQueueHealth(){var t;try{const i=await fetch("/admin/queue/health",{method:"GET",headers:{"X-CSRF-TOKEN":((t=document.querySelector('meta[name="csrf-token"]'))==null?void 0:t.getAttribute("content"))||""}});if(!i.ok)throw new Error(`HTTP ${i.status}: ${i.statusText}`);const n=await i.json();n.success&&n.data&&n.data.metrics&&this.updateQueueHealthDisplay(n.data.metrics)}catch(i){console.error("Failed to load queue health:",i),this.updateQueueHealthDisplay({overall_status:"error",job_statistics:{pending_jobs:0,failed_jobs_total:0}})}}updateQueueHealthDisplay(t){var i,n,r;if(this.queueStatus){let s="Unknown",o="text-gray-900";switch(t.overall_status||t.status){case"healthy":s="Healthy",o="text-green-600";break;case"warning":s="Warning",o="text-yellow-600";break;case"critical":case"error":s="Error",o="text-red-600";break;case"idle":s="Idle",o="text-blue-600";break}this.queueStatus.textContent=s,this.queueStatus.className=`text-2xl font-bold ${o}`}if(this.recentJobsCount){const s=((i=t.test_job_statistics)==null?void 0:i.test_jobs_1h)||0,o=((n=t.job_statistics)==null?void 0:n.pending_jobs)||0;s>0?(this.recentJobsCount.textContent=s,this.recentJobsDescription&&(this.recentJobsDescription.textContent="Test jobs (1h)")):o>0?(this.recentJobsCount.textContent=o,this.recentJobsDescription&&(this.recentJobsDescription.textContent="Pending jobs")):(this.recentJobsCount.textContent="0",this.recentJobsDescription&&(this.recentJobsDescription.textContent="No recent activity"))}if(this.failedJobsCount){const s=((r=t.job_statistics)==null?void 0:r.failed_jobs_total)||0;this.failedJobsCount.textContent=s,s>0&&t.recent_failed_jobs&&t.recent_failed_jobs.length>0?this.displayFailedJobsDetails(t.recent_failed_jobs):this.hideFailedJobsDetails()}}displayFailedJobsDetails(t){!this.failedJobsDetailsSection||!this.failedJobsList||(this.failedJobsDetailsSection.classList.remove("hidden"),this.failedJobsList.innerHTML="",t.forEach(i=>{const n=this.createFailedJobElement(i);this.failedJobsList.appendChild(n)}))}hideFailedJobsDetails(){this.failedJobsDetailsSection&&this.failedJobsDetailsSection.classList.add("hidden")}createFailedJobElement(t){const i=document.createElement("div");i.className="bg-white border border-red-200 rounded-md p-3";const n=t.job_class.replace("App\\Jobs\\",""),r=new Date(t.failed_at).toLocaleString();return i.innerHTML=`
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900">
                        ${n}
                    </div>
                    <div class="text-sm text-red-600 mt-1">
                        ${t.error_message}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Failed: ${r} • Queue: ${t.queue} • ID: ${t.id}
                    </div>
                </div>
            </div>
        `,i}addResultAnimation(t,i){if(!(!t||!t.classList))switch(i){case"success":t.classList.add("animate-success-glow"),setTimeout(()=>t.classList.remove("animate-success-glow"),2e3);break;case"failed":case"error":t.classList.add("animate-error-shake"),setTimeout(()=>t.classList.remove("animate-error-shake"),1e3);break;case"timeout":t.classList.add("animate-warning-pulse"),setTimeout(()=>t.classList.remove("animate-warning-pulse"),3e3);break}}animateResultRemoval(t){t&&(t.style.transition="all 0.3s ease-out",t.style.opacity="0",t.style.transform="translateX(100%)",setTimeout(()=>{t.parentNode&&t.parentNode.removeChild(t)},300))}createResultDetailsSection(t){if(!t.details)return"";const i=[];return t.details.job_id&&i.push(`Job ID: ${t.details.job_id}`),t.details.error&&i.push(`Error: ${t.details.error}`),t.details.timeout_duration&&i.push(`Timeout: ${t.details.timeout_duration}s`),i.length===0?"":`
            <div class="mt-2 text-xs text-gray-500 border-t border-gray-200 pt-2">
                ${i.join(" • ")}
            </div>
        `}updateProgressWithAnimation(t){this.testProgressMessage&&(this.testProgressMessage.style.opacity="0.5",setTimeout(()=>{this.testProgressMessage.textContent=t,this.testProgressMessage.style.opacity="1"},150))}setLoadingStateWithAnimation(t){if(!(!this.testQueueBtn||!this.testQueueBtnText))if(t){this.testQueueBtn.disabled=!0,this.testQueueBtn.classList.add("opacity-75","cursor-not-allowed"),this.testQueueBtnText.textContent="Testing...";const i=this.testQueueBtn.querySelector("svg");i&&i.classList.add("animate-spin")}else{this.testQueueBtn.disabled=!1,this.testQueueBtn.classList.remove("opacity-75","cursor-not-allowed"),this.testQueueBtnText.textContent="Test Queue Worker";const i=this.testQueueBtn.querySelector("svg");i&&i.classList.remove("animate-spin")}}showDetailedError(t,i=""){const n=document.createElement("div");n.className="fixed top-4 right-4 max-w-md bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg z-50 animate-slide-in-right",n.innerHTML=`
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-sm font-medium">
                        Queue Test Error
                    </div>
                    <div class="text-sm mt-1">
                        ${t.message||"An unexpected error occurred"}
                        ${i?`<br><small>Context: ${i}</small>`:""}
                    </div>
                </div>
                <div class="ml-3">
                    <button class="text-red-600 hover:text-red-800" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `,document.body.appendChild(n),setTimeout(()=>{n.parentNode&&(n.style.opacity="0",n.style.transform="translateX(100%)",setTimeout(()=>n.remove(),300))},5e3)}showSuccessNotification(t){const i=document.createElement("div");i.className="fixed top-4 right-4 max-w-md bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg z-50 animate-slide-in-right",i.innerHTML=`
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-600 animate-bounce-once" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-sm font-medium">
                        Queue Test Successful
                    </div>
                    <div class="text-sm mt-1">
                        ${t}
                    </div>
                </div>
                <div class="ml-3">
                    <button class="text-green-600 hover:text-green-800" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `,document.body.appendChild(i),setTimeout(()=>{i.parentNode&&(i.style.opacity="0",i.style.transform="translateX(100%)",setTimeout(()=>i.remove(),300))},3e3)}}document.addEventListener("DOMContentLoaded",function(){document.getElementById("test-queue-btn")&&new zo});typeof Ai<"u"&&Ai.exports&&(Ai.exports=zo);/**
 * @license
 * Copyright 2019 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const xi=globalThis,lr=xi.ShadowRoot&&(xi.ShadyCSS===void 0||xi.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,cr=Symbol(),ss=new WeakMap;let Io=class{constructor(t,i,n){if(this._$cssResult$=!0,n!==cr)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=t,this.t=i}get styleSheet(){let t=this.o;const i=this.t;if(lr&&t===void 0){const n=i!==void 0&&i.length===1;n&&(t=ss.get(i)),t===void 0&&((this.o=t=new CSSStyleSheet).replaceSync(this.cssText),n&&ss.set(i,t))}return t}toString(){return this.cssText}};const cu=e=>new Io(typeof e=="string"?e:e+"",void 0,cr),ft=(e,...t)=>{const i=e.length===1?e[0]:t.reduce(((n,r,s)=>n+(o=>{if(o._$cssResult$===!0)return o.cssText;if(typeof o=="number")return o;throw Error("Value passed to 'css' function must be a 'css' function result: "+o+". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.")})(r)+e[s+1]),e[0]);return new Io(i,e,cr)},uu=(e,t)=>{if(lr)e.adoptedStyleSheets=t.map((i=>i instanceof CSSStyleSheet?i:i.styleSheet));else for(const i of t){const n=document.createElement("style"),r=xi.litNonce;r!==void 0&&n.setAttribute("nonce",r),n.textContent=i.cssText,e.appendChild(n)}},os=lr?e=>e:e=>e instanceof CSSStyleSheet?(t=>{let i="";for(const n of t.cssRules)i+=n.cssText;return cu(i)})(e):e;/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const{is:du,defineProperty:hu,getOwnPropertyDescriptor:pu,getOwnPropertyNames:fu,getOwnPropertySymbols:mu,getPrototypeOf:gu}=Object,It=globalThis,as=It.trustedTypes,bu=as?as.emptyScript:"",ln=It.reactiveElementPolyfillSupport,Ue=(e,t)=>e,fe={toAttribute(e,t){switch(t){case Boolean:e=e?bu:null;break;case Object:case Array:e=e==null?e:JSON.stringify(e)}return e},fromAttribute(e,t){let i=e;switch(t){case Boolean:i=e!==null;break;case Number:i=e===null?null:Number(e);break;case Object:case Array:try{i=JSON.parse(e)}catch{i=null}}return i}},ur=(e,t)=>!du(e,t),ls={attribute:!0,type:String,converter:fe,reflect:!1,useDefault:!1,hasChanged:ur};Symbol.metadata??(Symbol.metadata=Symbol("metadata")),It.litPropertyMetadata??(It.litPropertyMetadata=new WeakMap);let de=class extends HTMLElement{static addInitializer(t){this._$Ei(),(this.l??(this.l=[])).push(t)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(t,i=ls){if(i.state&&(i.attribute=!1),this._$Ei(),this.prototype.hasOwnProperty(t)&&((i=Object.create(i)).wrapped=!0),this.elementProperties.set(t,i),!i.noAccessor){const n=Symbol(),r=this.getPropertyDescriptor(t,n,i);r!==void 0&&hu(this.prototype,t,r)}}static getPropertyDescriptor(t,i,n){const{get:r,set:s}=pu(this.prototype,t)??{get(){return this[i]},set(o){this[i]=o}};return{get:r,set(o){const a=r==null?void 0:r.call(this);s==null||s.call(this,o),this.requestUpdate(t,a,n)},configurable:!0,enumerable:!0}}static getPropertyOptions(t){return this.elementProperties.get(t)??ls}static _$Ei(){if(this.hasOwnProperty(Ue("elementProperties")))return;const t=gu(this);t.finalize(),t.l!==void 0&&(this.l=[...t.l]),this.elementProperties=new Map(t.elementProperties)}static finalize(){if(this.hasOwnProperty(Ue("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(Ue("properties"))){const i=this.properties,n=[...fu(i),...mu(i)];for(const r of n)this.createProperty(r,i[r])}const t=this[Symbol.metadata];if(t!==null){const i=litPropertyMetadata.get(t);if(i!==void 0)for(const[n,r]of i)this.elementProperties.set(n,r)}this._$Eh=new Map;for(const[i,n]of this.elementProperties){const r=this._$Eu(i,n);r!==void 0&&this._$Eh.set(r,i)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(t){const i=[];if(Array.isArray(t)){const n=new Set(t.flat(1/0).reverse());for(const r of n)i.unshift(os(r))}else t!==void 0&&i.push(os(t));return i}static _$Eu(t,i){const n=i.attribute;return n===!1?void 0:typeof n=="string"?n:typeof t=="string"?t.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){var t;this._$ES=new Promise((i=>this.enableUpdating=i)),this._$AL=new Map,this._$E_(),this.requestUpdate(),(t=this.constructor.l)==null||t.forEach((i=>i(this)))}addController(t){var i;(this._$EO??(this._$EO=new Set)).add(t),this.renderRoot!==void 0&&this.isConnected&&((i=t.hostConnected)==null||i.call(t))}removeController(t){var i;(i=this._$EO)==null||i.delete(t)}_$E_(){const t=new Map,i=this.constructor.elementProperties;for(const n of i.keys())this.hasOwnProperty(n)&&(t.set(n,this[n]),delete this[n]);t.size>0&&(this._$Ep=t)}createRenderRoot(){const t=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return uu(t,this.constructor.elementStyles),t}connectedCallback(){var t;this.renderRoot??(this.renderRoot=this.createRenderRoot()),this.enableUpdating(!0),(t=this._$EO)==null||t.forEach((i=>{var n;return(n=i.hostConnected)==null?void 0:n.call(i)}))}enableUpdating(t){}disconnectedCallback(){var t;(t=this._$EO)==null||t.forEach((i=>{var n;return(n=i.hostDisconnected)==null?void 0:n.call(i)}))}attributeChangedCallback(t,i,n){this._$AK(t,n)}_$ET(t,i){var s;const n=this.constructor.elementProperties.get(t),r=this.constructor._$Eu(t,n);if(r!==void 0&&n.reflect===!0){const o=(((s=n.converter)==null?void 0:s.toAttribute)!==void 0?n.converter:fe).toAttribute(i,n.type);this._$Em=t,o==null?this.removeAttribute(r):this.setAttribute(r,o),this._$Em=null}}_$AK(t,i){var s,o;const n=this.constructor,r=n._$Eh.get(t);if(r!==void 0&&this._$Em!==r){const a=n.getPropertyOptions(r),l=typeof a.converter=="function"?{fromAttribute:a.converter}:((s=a.converter)==null?void 0:s.fromAttribute)!==void 0?a.converter:fe;this._$Em=r;const c=l.fromAttribute(i,a.type);this[r]=c??((o=this._$Ej)==null?void 0:o.get(r))??c,this._$Em=null}}requestUpdate(t,i,n){var r;if(t!==void 0){const s=this.constructor,o=this[t];if(n??(n=s.getPropertyOptions(t)),!((n.hasChanged??ur)(o,i)||n.useDefault&&n.reflect&&o===((r=this._$Ej)==null?void 0:r.get(t))&&!this.hasAttribute(s._$Eu(t,n))))return;this.C(t,i,n)}this.isUpdatePending===!1&&(this._$ES=this._$EP())}C(t,i,{useDefault:n,reflect:r,wrapped:s},o){n&&!(this._$Ej??(this._$Ej=new Map)).has(t)&&(this._$Ej.set(t,o??i??this[t]),s!==!0||o!==void 0)||(this._$AL.has(t)||(this.hasUpdated||n||(i=void 0),this._$AL.set(t,i)),r===!0&&this._$Em!==t&&(this._$Eq??(this._$Eq=new Set)).add(t))}async _$EP(){this.isUpdatePending=!0;try{await this._$ES}catch(i){Promise.reject(i)}const t=this.scheduleUpdate();return t!=null&&await t,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){var n;if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??(this.renderRoot=this.createRenderRoot()),this._$Ep){for(const[s,o]of this._$Ep)this[s]=o;this._$Ep=void 0}const r=this.constructor.elementProperties;if(r.size>0)for(const[s,o]of r){const{wrapped:a}=o,l=this[s];a!==!0||this._$AL.has(s)||l===void 0||this.C(s,void 0,o,l)}}let t=!1;const i=this._$AL;try{t=this.shouldUpdate(i),t?(this.willUpdate(i),(n=this._$EO)==null||n.forEach((r=>{var s;return(s=r.hostUpdate)==null?void 0:s.call(r)})),this.update(i)):this._$EM()}catch(r){throw t=!1,this._$EM(),r}t&&this._$AE(i)}willUpdate(t){}_$AE(t){var i;(i=this._$EO)==null||i.forEach((n=>{var r;return(r=n.hostUpdated)==null?void 0:r.call(n)})),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(t)),this.updated(t)}_$EM(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(t){return!0}update(t){this._$Eq&&(this._$Eq=this._$Eq.forEach((i=>this._$ET(i,this[i])))),this._$EM()}updated(t){}firstUpdated(t){}};de.elementStyles=[],de.shadowRootOptions={mode:"open"},de[Ue("elementProperties")]=new Map,de[Ue("finalized")]=new Map,ln==null||ln({ReactiveElement:de}),(It.reactiveElementVersions??(It.reactiveElementVersions=[])).push("2.1.1");/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const He=globalThis,Ri=He.trustedTypes,cs=Ri?Ri.createPolicy("lit-html",{createHTML:e=>e}):void 0,Bo="$lit$",Pt=`lit$${Math.random().toFixed(9).slice(2)}$`,Do="?"+Pt,vu=`<${Do}>`,oe=document,Je=()=>oe.createComment(""),Ke=e=>e===null||typeof e!="object"&&typeof e!="function",dr=Array.isArray,yu=e=>dr(e)||typeof(e==null?void 0:e[Symbol.iterator])=="function",cn=`[ 	
\f\r]`,Fe=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,us=/-->/g,ds=/>/g,Vt=RegExp(`>|${cn}(?:([^\\s"'>=/]+)(${cn}*=${cn}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`,"g"),hs=/'/g,ps=/"/g,No=/^(?:script|style|textarea|title)$/i,wu=e=>(t,...i)=>({_$litType$:e,strings:t,values:i}),I=wu(1),ot=Symbol.for("lit-noChange"),D=Symbol.for("lit-nothing"),fs=new WeakMap,Xt=oe.createTreeWalker(oe,129);function Uo(e,t){if(!dr(e)||!e.hasOwnProperty("raw"))throw Error("invalid template strings array");return cs!==void 0?cs.createHTML(t):t}const _u=(e,t)=>{const i=e.length-1,n=[];let r,s=t===2?"<svg>":t===3?"<math>":"",o=Fe;for(let a=0;a<i;a++){const l=e[a];let c,u,d=-1,p=0;for(;p<l.length&&(o.lastIndex=p,u=o.exec(l),u!==null);)p=o.lastIndex,o===Fe?u[1]==="!--"?o=us:u[1]!==void 0?o=ds:u[2]!==void 0?(No.test(u[2])&&(r=RegExp("</"+u[2],"g")),o=Vt):u[3]!==void 0&&(o=Vt):o===Vt?u[0]===">"?(o=r??Fe,d=-1):u[1]===void 0?d=-2:(d=o.lastIndex-u[2].length,c=u[1],o=u[3]===void 0?Vt:u[3]==='"'?ps:hs):o===ps||o===hs?o=Vt:o===us||o===ds?o=Fe:(o=Vt,r=void 0);const f=o===Vt&&e[a+1].startsWith("/>")?" ":"";s+=o===Fe?l+vu:d>=0?(n.push(c),l.slice(0,d)+Bo+l.slice(d)+Pt+f):l+Pt+(d===-2?a:f)}return[Uo(e,s+(e[i]||"<?>")+(t===2?"</svg>":t===3?"</math>":"")),n]};class Qe{constructor({strings:t,_$litType$:i},n){let r;this.parts=[];let s=0,o=0;const a=t.length-1,l=this.parts,[c,u]=_u(t,i);if(this.el=Qe.createElement(c,n),Xt.currentNode=this.el.content,i===2||i===3){const d=this.el.content.firstChild;d.replaceWith(...d.childNodes)}for(;(r=Xt.nextNode())!==null&&l.length<a;){if(r.nodeType===1){if(r.hasAttributes())for(const d of r.getAttributeNames())if(d.endsWith(Bo)){const p=u[o++],f=r.getAttribute(d).split(Pt),b=/([.?@])?(.*)/.exec(p);l.push({type:1,index:s,name:b[2],strings:f,ctor:b[1]==="."?Eu:b[1]==="?"?Su:b[1]==="@"?Cu:qi}),r.removeAttribute(d)}else d.startsWith(Pt)&&(l.push({type:6,index:s}),r.removeAttribute(d));if(No.test(r.tagName)){const d=r.textContent.split(Pt),p=d.length-1;if(p>0){r.textContent=Ri?Ri.emptyScript:"";for(let f=0;f<p;f++)r.append(d[f],Je()),Xt.nextNode(),l.push({type:2,index:++s});r.append(d[p],Je())}}}else if(r.nodeType===8)if(r.data===Do)l.push({type:2,index:s});else{let d=-1;for(;(d=r.data.indexOf(Pt,d+1))!==-1;)l.push({type:7,index:s}),d+=Pt.length-1}s++}}static createElement(t,i){const n=oe.createElement("template");return n.innerHTML=t,n}}function me(e,t,i=e,n){var o,a;if(t===ot)return t;let r=n!==void 0?(o=i._$Co)==null?void 0:o[n]:i._$Cl;const s=Ke(t)?void 0:t._$litDirective$;return(r==null?void 0:r.constructor)!==s&&((a=r==null?void 0:r._$AO)==null||a.call(r,!1),s===void 0?r=void 0:(r=new s(e),r._$AT(e,i,n)),n!==void 0?(i._$Co??(i._$Co=[]))[n]=r:i._$Cl=r),r!==void 0&&(t=me(e,r._$AS(e,t.values),r,n)),t}class xu{constructor(t,i){this._$AV=[],this._$AN=void 0,this._$AD=t,this._$AM=i}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(t){const{el:{content:i},parts:n}=this._$AD,r=((t==null?void 0:t.creationScope)??oe).importNode(i,!0);Xt.currentNode=r;let s=Xt.nextNode(),o=0,a=0,l=n[0];for(;l!==void 0;){if(o===l.index){let c;l.type===2?c=new ii(s,s.nextSibling,this,t):l.type===1?c=new l.ctor(s,l.name,l.strings,this,t):l.type===6&&(c=new ku(s,this,t)),this._$AV.push(c),l=n[++a]}o!==(l==null?void 0:l.index)&&(s=Xt.nextNode(),o++)}return Xt.currentNode=oe,r}p(t){let i=0;for(const n of this._$AV)n!==void 0&&(n.strings!==void 0?(n._$AI(t,n,i),i+=n.strings.length-2):n._$AI(t[i])),i++}}class ii{get _$AU(){var t;return((t=this._$AM)==null?void 0:t._$AU)??this._$Cv}constructor(t,i,n,r){this.type=2,this._$AH=D,this._$AN=void 0,this._$AA=t,this._$AB=i,this._$AM=n,this.options=r,this._$Cv=(r==null?void 0:r.isConnected)??!0}get parentNode(){let t=this._$AA.parentNode;const i=this._$AM;return i!==void 0&&(t==null?void 0:t.nodeType)===11&&(t=i.parentNode),t}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(t,i=this){t=me(this,t,i),Ke(t)?t===D||t==null||t===""?(this._$AH!==D&&this._$AR(),this._$AH=D):t!==this._$AH&&t!==ot&&this._(t):t._$litType$!==void 0?this.$(t):t.nodeType!==void 0?this.T(t):yu(t)?this.k(t):this._(t)}O(t){return this._$AA.parentNode.insertBefore(t,this._$AB)}T(t){this._$AH!==t&&(this._$AR(),this._$AH=this.O(t))}_(t){this._$AH!==D&&Ke(this._$AH)?this._$AA.nextSibling.data=t:this.T(oe.createTextNode(t)),this._$AH=t}$(t){var s;const{values:i,_$litType$:n}=t,r=typeof n=="number"?this._$AC(t):(n.el===void 0&&(n.el=Qe.createElement(Uo(n.h,n.h[0]),this.options)),n);if(((s=this._$AH)==null?void 0:s._$AD)===r)this._$AH.p(i);else{const o=new xu(r,this),a=o.u(this.options);o.p(i),this.T(a),this._$AH=o}}_$AC(t){let i=fs.get(t.strings);return i===void 0&&fs.set(t.strings,i=new Qe(t)),i}k(t){dr(this._$AH)||(this._$AH=[],this._$AR());const i=this._$AH;let n,r=0;for(const s of t)r===i.length?i.push(n=new ii(this.O(Je()),this.O(Je()),this,this.options)):n=i[r],n._$AI(s),r++;r<i.length&&(this._$AR(n&&n._$AB.nextSibling,r),i.length=r)}_$AR(t=this._$AA.nextSibling,i){var n;for((n=this._$AP)==null?void 0:n.call(this,!1,!0,i);t!==this._$AB;){const r=t.nextSibling;t.remove(),t=r}}setConnected(t){var i;this._$AM===void 0&&(this._$Cv=t,(i=this._$AP)==null||i.call(this,t))}}class qi{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(t,i,n,r,s){this.type=1,this._$AH=D,this._$AN=void 0,this.element=t,this.name=i,this._$AM=r,this.options=s,n.length>2||n[0]!==""||n[1]!==""?(this._$AH=Array(n.length-1).fill(new String),this.strings=n):this._$AH=D}_$AI(t,i=this,n,r){const s=this.strings;let o=!1;if(s===void 0)t=me(this,t,i,0),o=!Ke(t)||t!==this._$AH&&t!==ot,o&&(this._$AH=t);else{const a=t;let l,c;for(t=s[0],l=0;l<s.length-1;l++)c=me(this,a[n+l],i,l),c===ot&&(c=this._$AH[l]),o||(o=!Ke(c)||c!==this._$AH[l]),c===D?t=D:t!==D&&(t+=(c??"")+s[l+1]),this._$AH[l]=c}o&&!r&&this.j(t)}j(t){t===D?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,t??"")}}class Eu extends qi{constructor(){super(...arguments),this.type=3}j(t){this.element[this.name]=t===D?void 0:t}}class Su extends qi{constructor(){super(...arguments),this.type=4}j(t){this.element.toggleAttribute(this.name,!!t&&t!==D)}}class Cu extends qi{constructor(t,i,n,r,s){super(t,i,n,r,s),this.type=5}_$AI(t,i=this){if((t=me(this,t,i,0)??D)===ot)return;const n=this._$AH,r=t===D&&n!==D||t.capture!==n.capture||t.once!==n.once||t.passive!==n.passive,s=t!==D&&(n===D||r);r&&this.element.removeEventListener(this.name,this,n),s&&this.element.addEventListener(this.name,this,t),this._$AH=t}handleEvent(t){var i;typeof this._$AH=="function"?this._$AH.call(((i=this.options)==null?void 0:i.host)??this.element,t):this._$AH.handleEvent(t)}}class ku{constructor(t,i,n){this.element=t,this.type=6,this._$AN=void 0,this._$AM=i,this.options=n}get _$AU(){return this._$AM._$AU}_$AI(t){me(this,t)}}const un=He.litHtmlPolyfillSupport;un==null||un(Qe,ii),(He.litHtmlVersions??(He.litHtmlVersions=[])).push("3.3.1");const Au=(e,t,i)=>{const n=(i==null?void 0:i.renderBefore)??t;let r=n._$litPart$;if(r===void 0){const s=(i==null?void 0:i.renderBefore)??null;n._$litPart$=r=new ii(t.insertBefore(Je(),s),s,void 0,i??{})}return r._$AI(e),r};/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const te=globalThis;let je=class extends de{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){var i;const t=super.createRenderRoot();return(i=this.renderOptions).renderBefore??(i.renderBefore=t.firstChild),t}update(t){const i=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(t),this._$Do=Au(i,this.renderRoot,this.renderOptions)}connectedCallback(){var t;super.connectedCallback(),(t=this._$Do)==null||t.setConnected(!0)}disconnectedCallback(){var t;super.disconnectedCallback(),(t=this._$Do)==null||t.setConnected(!1)}render(){return ot}};var co;je._$litElement$=!0,je.finalized=!0,(co=te.litElementHydrateSupport)==null||co.call(te,{LitElement:je});const dn=te.litElementPolyfillSupport;dn==null||dn({LitElement:je});(te.litElementVersions??(te.litElementVersions=[])).push("4.2.1");var Tu=ft`
  :host(:not(:focus-within)) {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    clip: rect(0 0 0 0) !important;
    clip-path: inset(50%) !important;
    border: none !important;
    overflow: hidden !important;
    white-space: nowrap !important;
    padding: 0 !important;
  }
`,Tt=ft`
  :host {
    box-sizing: border-box;
  }

  :host *,
  :host *::before,
  :host *::after {
    box-sizing: inherit;
  }

  [hidden] {
    display: none !important;
  }
`,Ho=Object.defineProperty,$u=Object.defineProperties,Ru=Object.getOwnPropertyDescriptor,Fu=Object.getOwnPropertyDescriptors,ms=Object.getOwnPropertySymbols,Ou=Object.prototype.hasOwnProperty,Lu=Object.prototype.propertyIsEnumerable,hn=(e,t)=>(t=Symbol[e])?t:Symbol.for("Symbol."+e),hr=e=>{throw TypeError(e)},gs=(e,t,i)=>t in e?Ho(e,t,{enumerable:!0,configurable:!0,writable:!0,value:i}):e[t]=i,ce=(e,t)=>{for(var i in t||(t={}))Ou.call(t,i)&&gs(e,i,t[i]);if(ms)for(var i of ms(t))Lu.call(t,i)&&gs(e,i,t[i]);return e},Vi=(e,t)=>$u(e,Fu(t)),h=(e,t,i,n)=>{for(var r=n>1?void 0:n?Ru(t,i):t,s=e.length-1,o;s>=0;s--)(o=e[s])&&(r=(n?o(t,i,r):o(r))||r);return n&&r&&Ho(t,i,r),r},jo=(e,t,i)=>t.has(e)||hr("Cannot "+i),Mu=(e,t,i)=>(jo(e,t,"read from private field"),t.get(e)),Pu=(e,t,i)=>t.has(e)?hr("Cannot add the same private member more than once"):t instanceof WeakSet?t.add(e):t.set(e,i),zu=(e,t,i,n)=>(jo(e,t,"write to private field"),t.set(e,i),i),Iu=function(e,t){this[0]=e,this[1]=t},Bu=e=>{var t=e[hn("asyncIterator")],i=!1,n,r={};return t==null?(t=e[hn("iterator")](),n=s=>r[s]=o=>t[s](o)):(t=t.call(e),n=s=>r[s]=o=>{if(i){if(i=!1,s==="throw")throw o;return o}return i=!0,{done:!1,value:new Iu(new Promise(a=>{var l=t[s](o);l instanceof Object||hr("Object expected"),a(l)}),1)}}),r[hn("iterator")]=()=>r,n("next"),"throw"in t?n("throw"):r.throw=s=>{throw s},"return"in t&&n("return"),r};/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Du={attribute:!0,type:String,converter:fe,reflect:!1,hasChanged:ur},Nu=(e=Du,t,i)=>{const{kind:n,metadata:r}=i;let s=globalThis.litPropertyMetadata.get(r);if(s===void 0&&globalThis.litPropertyMetadata.set(r,s=new Map),n==="setter"&&((e=Object.create(e)).wrapped=!0),s.set(i.name,e),n==="accessor"){const{name:o}=i;return{set(a){const l=t.get.call(this);t.set.call(this,a),this.requestUpdate(o,l,e)},init(a){return a!==void 0&&this.C(o,void 0,e,a),a}}}if(n==="setter"){const{name:o}=i;return function(a){const l=this[o];t.call(this,a),this.requestUpdate(o,l,e)}}throw Error("Unsupported decorator location: "+n)};function v(e){return(t,i)=>typeof i=="object"?Nu(e,t,i):((n,r,s)=>{const o=r.hasOwnProperty(s);return r.constructor.createProperty(s,n),o?Object.getOwnPropertyDescriptor(r,s):void 0})(e,t,i)}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function et(e){return v({...e,state:!0,attribute:!1})}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function Uu(e){return(t,i)=>{const n=typeof t=="function"?t:t[i];Object.assign(n,e)}}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Hu=(e,t,i)=>(i.configurable=!0,i.enumerable=!0,Reflect.decorate&&typeof t!="object"&&Object.defineProperty(e,t,i),i);/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function it(e,t){return(i,n,r)=>{const s=o=>{var a;return((a=o.renderRoot)==null?void 0:a.querySelector(e))??null};return Hu(i,n,{get(){return s(this)}})}}var Ei,nt=class extends je{constructor(){super(),Pu(this,Ei,!1),this.initialReflectedProperties=new Map,Object.entries(this.constructor.dependencies).forEach(([e,t])=>{this.constructor.define(e,t)})}emit(e,t){const i=new CustomEvent(e,ce({bubbles:!0,cancelable:!1,composed:!0,detail:{}},t));return this.dispatchEvent(i),i}static define(e,t=this,i={}){const n=customElements.get(e);if(!n){try{customElements.define(e,t,i)}catch{customElements.define(e,class extends t{},i)}return}let r=" (unknown version)",s=r;"version"in t&&t.version&&(r=" v"+t.version),"version"in n&&n.version&&(s=" v"+n.version),!(r&&s&&r===s)&&console.warn(`Attempted to register <${e}>${r}, but <${e}>${s} has already been registered.`)}attributeChangedCallback(e,t,i){Mu(this,Ei)||(this.constructor.elementProperties.forEach((n,r)=>{n.reflect&&this[r]!=null&&this.initialReflectedProperties.set(r,this[r])}),zu(this,Ei,!0)),super.attributeChangedCallback(e,t,i)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,i)=>{e.has(i)&&this[i]==null&&(this[i]=t)})}};Ei=new WeakMap;nt.version="2.20.1";nt.dependencies={};h([v()],nt.prototype,"dir",2);h([v()],nt.prototype,"lang",2);var qo=class extends nt{render(){return I` <slot></slot> `}};qo.styles=[Tt,Tu];var ju=ft`
  :host {
    display: block;
  }

  .input {
    flex: 1 1 auto;
    display: inline-flex;
    align-items: stretch;
    justify-content: start;
    position: relative;
    width: 100%;
    font-family: var(--sl-input-font-family);
    font-weight: var(--sl-input-font-weight);
    letter-spacing: var(--sl-input-letter-spacing);
    vertical-align: middle;
    overflow: hidden;
    cursor: text;
    transition:
      var(--sl-transition-fast) color,
      var(--sl-transition-fast) border,
      var(--sl-transition-fast) box-shadow,
      var(--sl-transition-fast) background-color;
  }

  /* Standard inputs */
  .input--standard {
    background-color: var(--sl-input-background-color);
    border: solid var(--sl-input-border-width) var(--sl-input-border-color);
  }

  .input--standard:hover:not(.input--disabled) {
    background-color: var(--sl-input-background-color-hover);
    border-color: var(--sl-input-border-color-hover);
  }

  .input--standard.input--focused:not(.input--disabled) {
    background-color: var(--sl-input-background-color-focus);
    border-color: var(--sl-input-border-color-focus);
    box-shadow: 0 0 0 var(--sl-focus-ring-width) var(--sl-input-focus-ring-color);
  }

  .input--standard.input--focused:not(.input--disabled) .input__control {
    color: var(--sl-input-color-focus);
  }

  .input--standard.input--disabled {
    background-color: var(--sl-input-background-color-disabled);
    border-color: var(--sl-input-border-color-disabled);
    opacity: 0.5;
    cursor: not-allowed;
  }

  .input--standard.input--disabled .input__control {
    color: var(--sl-input-color-disabled);
  }

  .input--standard.input--disabled .input__control::placeholder {
    color: var(--sl-input-placeholder-color-disabled);
  }

  /* Filled inputs */
  .input--filled {
    border: none;
    background-color: var(--sl-input-filled-background-color);
    color: var(--sl-input-color);
  }

  .input--filled:hover:not(.input--disabled) {
    background-color: var(--sl-input-filled-background-color-hover);
  }

  .input--filled.input--focused:not(.input--disabled) {
    background-color: var(--sl-input-filled-background-color-focus);
    outline: var(--sl-focus-ring);
    outline-offset: var(--sl-focus-ring-offset);
  }

  .input--filled.input--disabled {
    background-color: var(--sl-input-filled-background-color-disabled);
    opacity: 0.5;
    cursor: not-allowed;
  }

  .input__control {
    flex: 1 1 auto;
    font-family: inherit;
    font-size: inherit;
    font-weight: inherit;
    min-width: 0;
    height: 100%;
    color: var(--sl-input-color);
    border: none;
    background: inherit;
    box-shadow: none;
    padding: 0;
    margin: 0;
    cursor: inherit;
    -webkit-appearance: none;
  }

  .input__control::-webkit-search-decoration,
  .input__control::-webkit-search-cancel-button,
  .input__control::-webkit-search-results-button,
  .input__control::-webkit-search-results-decoration {
    -webkit-appearance: none;
  }

  .input__control:-webkit-autofill,
  .input__control:-webkit-autofill:hover,
  .input__control:-webkit-autofill:focus,
  .input__control:-webkit-autofill:active {
    box-shadow: 0 0 0 var(--sl-input-height-large) var(--sl-input-background-color-hover) inset !important;
    -webkit-text-fill-color: var(--sl-color-primary-500);
    caret-color: var(--sl-input-color);
  }

  .input--filled .input__control:-webkit-autofill,
  .input--filled .input__control:-webkit-autofill:hover,
  .input--filled .input__control:-webkit-autofill:focus,
  .input--filled .input__control:-webkit-autofill:active {
    box-shadow: 0 0 0 var(--sl-input-height-large) var(--sl-input-filled-background-color) inset !important;
  }

  .input__control::placeholder {
    color: var(--sl-input-placeholder-color);
    user-select: none;
    -webkit-user-select: none;
  }

  .input:hover:not(.input--disabled) .input__control {
    color: var(--sl-input-color-hover);
  }

  .input__control:focus {
    outline: none;
  }

  .input__prefix,
  .input__suffix {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    cursor: default;
  }

  .input__prefix ::slotted(sl-icon),
  .input__suffix ::slotted(sl-icon) {
    color: var(--sl-input-icon-color);
  }

  /*
   * Size modifiers
   */

  .input--small {
    border-radius: var(--sl-input-border-radius-small);
    font-size: var(--sl-input-font-size-small);
    height: var(--sl-input-height-small);
  }

  .input--small .input__control {
    height: calc(var(--sl-input-height-small) - var(--sl-input-border-width) * 2);
    padding: 0 var(--sl-input-spacing-small);
  }

  .input--small .input__clear,
  .input--small .input__password-toggle {
    width: calc(1em + var(--sl-input-spacing-small) * 2);
  }

  .input--small .input__prefix ::slotted(*) {
    margin-inline-start: var(--sl-input-spacing-small);
  }

  .input--small .input__suffix ::slotted(*) {
    margin-inline-end: var(--sl-input-spacing-small);
  }

  .input--medium {
    border-radius: var(--sl-input-border-radius-medium);
    font-size: var(--sl-input-font-size-medium);
    height: var(--sl-input-height-medium);
  }

  .input--medium .input__control {
    height: calc(var(--sl-input-height-medium) - var(--sl-input-border-width) * 2);
    padding: 0 var(--sl-input-spacing-medium);
  }

  .input--medium .input__clear,
  .input--medium .input__password-toggle {
    width: calc(1em + var(--sl-input-spacing-medium) * 2);
  }

  .input--medium .input__prefix ::slotted(*) {
    margin-inline-start: var(--sl-input-spacing-medium);
  }

  .input--medium .input__suffix ::slotted(*) {
    margin-inline-end: var(--sl-input-spacing-medium);
  }

  .input--large {
    border-radius: var(--sl-input-border-radius-large);
    font-size: var(--sl-input-font-size-large);
    height: var(--sl-input-height-large);
  }

  .input--large .input__control {
    height: calc(var(--sl-input-height-large) - var(--sl-input-border-width) * 2);
    padding: 0 var(--sl-input-spacing-large);
  }

  .input--large .input__clear,
  .input--large .input__password-toggle {
    width: calc(1em + var(--sl-input-spacing-large) * 2);
  }

  .input--large .input__prefix ::slotted(*) {
    margin-inline-start: var(--sl-input-spacing-large);
  }

  .input--large .input__suffix ::slotted(*) {
    margin-inline-end: var(--sl-input-spacing-large);
  }

  /*
   * Pill modifier
   */

  .input--pill.input--small {
    border-radius: var(--sl-input-height-small);
  }

  .input--pill.input--medium {
    border-radius: var(--sl-input-height-medium);
  }

  .input--pill.input--large {
    border-radius: var(--sl-input-height-large);
  }

  /*
   * Clearable + Password Toggle
   */

  .input__clear,
  .input__password-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: inherit;
    color: var(--sl-input-icon-color);
    border: none;
    background: none;
    padding: 0;
    transition: var(--sl-transition-fast) color;
    cursor: pointer;
  }

  .input__clear:hover,
  .input__password-toggle:hover {
    color: var(--sl-input-icon-color-hover);
  }

  .input__clear:focus,
  .input__password-toggle:focus {
    outline: none;
  }

  /* Don't show the browser's password toggle in Edge */
  ::-ms-reveal {
    display: none;
  }

  /* Hide the built-in number spinner */
  .input--no-spin-buttons input[type='number']::-webkit-outer-spin-button,
  .input--no-spin-buttons input[type='number']::-webkit-inner-spin-button {
    -webkit-appearance: none;
    display: none;
  }

  .input--no-spin-buttons input[type='number'] {
    -moz-appearance: textfield;
  }
`,Vo=(e="value")=>(t,i)=>{const n=t.constructor,r=n.prototype.attributeChangedCallback;n.prototype.attributeChangedCallback=function(s,o,a){var l;const c=n.getPropertyOptions(e),u=typeof c.attribute=="string"?c.attribute:e;if(s===u){const d=c.converter||fe,f=(typeof d=="function"?d:(l=d==null?void 0:d.fromAttribute)!=null?l:fe.fromAttribute)(a,c.type);this[e]!==f&&(this[i]=f)}r.call(this,s,o,a)}},qu=ft`
  .form-control .form-control__label {
    display: none;
  }

  .form-control .form-control__help-text {
    display: none;
  }

  /* Label */
  .form-control--has-label .form-control__label {
    display: inline-block;
    color: var(--sl-input-label-color);
    margin-bottom: var(--sl-spacing-3x-small);
  }

  .form-control--has-label.form-control--small .form-control__label {
    font-size: var(--sl-input-label-font-size-small);
  }

  .form-control--has-label.form-control--medium .form-control__label {
    font-size: var(--sl-input-label-font-size-medium);
  }

  .form-control--has-label.form-control--large .form-control__label {
    font-size: var(--sl-input-label-font-size-large);
  }

  :host([required]) .form-control--has-label .form-control__label::after {
    content: var(--sl-input-required-content);
    margin-inline-start: var(--sl-input-required-content-offset);
    color: var(--sl-input-required-content-color);
  }

  /* Help text */
  .form-control--has-help-text .form-control__help-text {
    display: block;
    color: var(--sl-input-help-text-color);
    margin-top: var(--sl-spacing-3x-small);
  }

  .form-control--has-help-text.form-control--small .form-control__help-text {
    font-size: var(--sl-input-help-text-font-size-small);
  }

  .form-control--has-help-text.form-control--medium .form-control__help-text {
    font-size: var(--sl-input-help-text-font-size-medium);
  }

  .form-control--has-help-text.form-control--large .form-control__help-text {
    font-size: var(--sl-input-help-text-font-size-large);
  }

  .form-control--has-help-text.form-control--radio-group .form-control__help-text {
    margin-top: var(--sl-spacing-2x-small);
  }
`,Oe=new WeakMap,Le=new WeakMap,Me=new WeakMap,pn=new WeakSet,li=new WeakMap,pr=class{constructor(e,t){this.handleFormData=i=>{const n=this.options.disabled(this.host),r=this.options.name(this.host),s=this.options.value(this.host),o=this.host.tagName.toLowerCase()==="sl-button";this.host.isConnected&&!n&&!o&&typeof r=="string"&&r.length>0&&typeof s<"u"&&(Array.isArray(s)?s.forEach(a=>{i.formData.append(r,a.toString())}):i.formData.append(r,s.toString()))},this.handleFormSubmit=i=>{var n;const r=this.options.disabled(this.host),s=this.options.reportValidity;this.form&&!this.form.noValidate&&((n=Oe.get(this.form))==null||n.forEach(o=>{this.setUserInteracted(o,!0)})),this.form&&!this.form.noValidate&&!r&&!s(this.host)&&(i.preventDefault(),i.stopImmediatePropagation())},this.handleFormReset=()=>{this.options.setValue(this.host,this.options.defaultValue(this.host)),this.setUserInteracted(this.host,!1),li.set(this.host,[])},this.handleInteraction=i=>{const n=li.get(this.host);n.includes(i.type)||n.push(i.type),n.length===this.options.assumeInteractionOn.length&&this.setUserInteracted(this.host,!0)},this.checkFormValidity=()=>{if(this.form&&!this.form.noValidate){const i=this.form.querySelectorAll("*");for(const n of i)if(typeof n.checkValidity=="function"&&!n.checkValidity())return!1}return!0},this.reportFormValidity=()=>{if(this.form&&!this.form.noValidate){const i=this.form.querySelectorAll("*");for(const n of i)if(typeof n.reportValidity=="function"&&!n.reportValidity())return!1}return!0},(this.host=e).addController(this),this.options=ce({form:i=>{const n=i.form;if(n){const s=i.getRootNode().querySelector(`#${n}`);if(s)return s}return i.closest("form")},name:i=>i.name,value:i=>i.value,defaultValue:i=>i.defaultValue,disabled:i=>{var n;return(n=i.disabled)!=null?n:!1},reportValidity:i=>typeof i.reportValidity=="function"?i.reportValidity():!0,checkValidity:i=>typeof i.checkValidity=="function"?i.checkValidity():!0,setValue:(i,n)=>i.value=n,assumeInteractionOn:["sl-input"]},t)}hostConnected(){const e=this.options.form(this.host);e&&this.attachForm(e),li.set(this.host,[]),this.options.assumeInteractionOn.forEach(t=>{this.host.addEventListener(t,this.handleInteraction)})}hostDisconnected(){this.detachForm(),li.delete(this.host),this.options.assumeInteractionOn.forEach(e=>{this.host.removeEventListener(e,this.handleInteraction)})}hostUpdated(){const e=this.options.form(this.host);e||this.detachForm(),e&&this.form!==e&&(this.detachForm(),this.attachForm(e)),this.host.hasUpdated&&this.setValidity(this.host.validity.valid)}attachForm(e){e?(this.form=e,Oe.has(this.form)?Oe.get(this.form).add(this.host):Oe.set(this.form,new Set([this.host])),this.form.addEventListener("formdata",this.handleFormData),this.form.addEventListener("submit",this.handleFormSubmit),this.form.addEventListener("reset",this.handleFormReset),Le.has(this.form)||(Le.set(this.form,this.form.reportValidity),this.form.reportValidity=()=>this.reportFormValidity()),Me.has(this.form)||(Me.set(this.form,this.form.checkValidity),this.form.checkValidity=()=>this.checkFormValidity())):this.form=void 0}detachForm(){if(!this.form)return;const e=Oe.get(this.form);e&&(e.delete(this.host),e.size<=0&&(this.form.removeEventListener("formdata",this.handleFormData),this.form.removeEventListener("submit",this.handleFormSubmit),this.form.removeEventListener("reset",this.handleFormReset),Le.has(this.form)&&(this.form.reportValidity=Le.get(this.form),Le.delete(this.form)),Me.has(this.form)&&(this.form.checkValidity=Me.get(this.form),Me.delete(this.form)),this.form=void 0))}setUserInteracted(e,t){t?pn.add(e):pn.delete(e),e.requestUpdate()}doAction(e,t){if(this.form){const i=document.createElement("button");i.type=e,i.style.position="absolute",i.style.width="0",i.style.height="0",i.style.clipPath="inset(50%)",i.style.overflow="hidden",i.style.whiteSpace="nowrap",t&&(i.name=t.name,i.value=t.value,["formaction","formenctype","formmethod","formnovalidate","formtarget"].forEach(n=>{t.hasAttribute(n)&&i.setAttribute(n,t.getAttribute(n))})),this.form.append(i),i.click(),i.remove()}}getForm(){var e;return(e=this.form)!=null?e:null}reset(e){this.doAction("reset",e)}submit(e){this.doAction("submit",e)}setValidity(e){const t=this.host,i=!!pn.has(t),n=!!t.required;t.toggleAttribute("data-required",n),t.toggleAttribute("data-optional",!n),t.toggleAttribute("data-invalid",!e),t.toggleAttribute("data-valid",e),t.toggleAttribute("data-user-invalid",!e&&i),t.toggleAttribute("data-user-valid",e&&i)}updateValidity(){const e=this.host;this.setValidity(e.validity.valid)}emitInvalidEvent(e){const t=new CustomEvent("sl-invalid",{bubbles:!1,composed:!1,cancelable:!0,detail:{}});e||t.preventDefault(),this.host.dispatchEvent(t)||e==null||e.preventDefault()}},fr=Object.freeze({badInput:!1,customError:!1,patternMismatch:!1,rangeOverflow:!1,rangeUnderflow:!1,stepMismatch:!1,tooLong:!1,tooShort:!1,typeMismatch:!1,valid:!0,valueMissing:!1});Object.freeze(Vi(ce({},fr),{valid:!1,valueMissing:!0}));Object.freeze(Vi(ce({},fr),{valid:!1,customError:!0}));var Wo=class{constructor(e,...t){this.slotNames=[],this.handleSlotChange=i=>{const n=i.target;(this.slotNames.includes("[default]")&&!n.name||n.name&&this.slotNames.includes(n.name))&&this.host.requestUpdate()},(this.host=e).addController(this),this.slotNames=t}hasDefaultSlot(){return[...this.host.childNodes].some(e=>{if(e.nodeType===e.TEXT_NODE&&e.textContent.trim()!=="")return!0;if(e.nodeType===e.ELEMENT_NODE){const t=e;if(t.tagName.toLowerCase()==="sl-visually-hidden")return!1;if(!t.hasAttribute("slot"))return!0}return!1})}hasNamedSlot(e){return this.host.querySelector(`:scope > [slot="${e}"]`)!==null}test(e){return e==="[default]"?this.hasDefaultSlot():this.hasNamedSlot(e)}hostConnected(){this.host.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}};const Pn=new Set,he=new Map;let Kt,mr="ltr",gr="en";const Jo=typeof MutationObserver<"u"&&typeof document<"u"&&typeof document.documentElement<"u";if(Jo){const e=new MutationObserver(Qo);mr=document.documentElement.dir||"ltr",gr=document.documentElement.lang||navigator.language,e.observe(document.documentElement,{attributes:!0,attributeFilter:["dir","lang"]})}function Ko(...e){e.map(t=>{const i=t.$code.toLowerCase();he.has(i)?he.set(i,Object.assign(Object.assign({},he.get(i)),t)):he.set(i,t),Kt||(Kt=t)}),Qo()}function Qo(){Jo&&(mr=document.documentElement.dir||"ltr",gr=document.documentElement.lang||navigator.language),[...Pn.keys()].map(e=>{typeof e.requestUpdate=="function"&&e.requestUpdate()})}let Vu=class{constructor(t){this.host=t,this.host.addController(this)}hostConnected(){Pn.add(this.host)}hostDisconnected(){Pn.delete(this.host)}dir(){return`${this.host.dir||mr}`.toLowerCase()}lang(){return`${this.host.lang||gr}`.toLowerCase()}getTranslationData(t){var i,n;const r=new Intl.Locale(t.replace(/_/g,"-")),s=r==null?void 0:r.language.toLowerCase(),o=(n=(i=r==null?void 0:r.region)===null||i===void 0?void 0:i.toLowerCase())!==null&&n!==void 0?n:"",a=he.get(`${s}-${o}`),l=he.get(s);return{locale:r,language:s,region:o,primary:a,secondary:l}}exists(t,i){var n;const{primary:r,secondary:s}=this.getTranslationData((n=i.lang)!==null&&n!==void 0?n:this.lang());return i=Object.assign({includeFallback:!1},i),!!(r&&r[t]||s&&s[t]||i.includeFallback&&Kt&&Kt[t])}term(t,...i){const{primary:n,secondary:r}=this.getTranslationData(this.lang());let s;if(n&&n[t])s=n[t];else if(r&&r[t])s=r[t];else if(Kt&&Kt[t])s=Kt[t];else return console.error(`No translation found for: ${String(t)}`),String(t);return typeof s=="function"?s(...i):s}date(t,i){return t=new Date(t),new Intl.DateTimeFormat(this.lang(),i).format(t)}number(t,i){return t=Number(t),isNaN(t)?"":new Intl.NumberFormat(this.lang(),i).format(t)}relativeTime(t,i,n){return new Intl.RelativeTimeFormat(this.lang(),n).format(t,i)}};var Xo={$code:"en",$name:"English",$dir:"ltr",carousel:"Carousel",clearEntry:"Clear entry",close:"Close",copied:"Copied",copy:"Copy",currentValue:"Current value",error:"Error",goToSlide:(e,t)=>`Go to slide ${e} of ${t}`,hidePassword:"Hide password",loading:"Loading",nextSlide:"Next slide",numOptionsSelected:e=>e===0?"No options selected":e===1?"1 option selected":`${e} options selected`,previousSlide:"Previous slide",progress:"Progress",remove:"Remove",resize:"Resize",scrollToEnd:"Scroll to end",scrollToStart:"Scroll to start",selectAColorFromTheScreen:"Select a color from the screen",showPassword:"Show password",slideNum:e=>`Slide ${e}`,toggleColorFormat:"Toggle color format"};Ko(Xo);var Wu=Xo,_e=class extends Vu{};Ko(Wu);var zn="";function bs(e){zn=e}function Ju(e=""){if(!zn){const t=[...document.getElementsByTagName("script")],i=t.find(n=>n.hasAttribute("data-shoelace"));if(i)bs(i.getAttribute("data-shoelace"));else{const n=t.find(s=>/shoelace(\.min)?\.js($|\?)/.test(s.src)||/shoelace-autoloader(\.min)?\.js($|\?)/.test(s.src));let r="";n&&(r=n.getAttribute("src")),bs(r.split("/").slice(0,-1).join("/"))}}return zn.replace(/\/$/,"")+(e?`/${e.replace(/^\//,"")}`:"")}var Ku={name:"default",resolver:e=>Ju(`assets/icons/${e}.svg`)},Qu=Ku,vs={caret:`
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="6 9 12 15 18 9"></polyline>
    </svg>
  `,check:`
    <svg part="checked-icon" class="checkbox__icon" viewBox="0 0 16 16">
      <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round">
        <g stroke="currentColor">
          <g transform="translate(3.428571, 3.428571)">
            <path d="M0,5.71428571 L3.42857143,9.14285714"></path>
            <path d="M9.14285714,0 L3.42857143,9.14285714"></path>
          </g>
        </g>
      </g>
    </svg>
  `,"chevron-down":`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-down" viewBox="0 0 16 16">
      <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
    </svg>
  `,"chevron-left":`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-left" viewBox="0 0 16 16">
      <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
    </svg>
  `,"chevron-right":`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-right" viewBox="0 0 16 16">
      <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
    </svg>
  `,copy:`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16">
      <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V2Zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H6ZM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1H2Z"/>
    </svg>
  `,eye:`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
      <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
      <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
    </svg>
  `,"eye-slash":`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash" viewBox="0 0 16 16">
      <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/>
      <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
      <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/>
    </svg>
  `,eyedropper:`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eyedropper" viewBox="0 0 16 16">
      <path d="M13.354.646a1.207 1.207 0 0 0-1.708 0L8.5 3.793l-.646-.647a.5.5 0 1 0-.708.708L8.293 5l-7.147 7.146A.5.5 0 0 0 1 12.5v1.793l-.854.853a.5.5 0 1 0 .708.707L1.707 15H3.5a.5.5 0 0 0 .354-.146L11 7.707l1.146 1.147a.5.5 0 0 0 .708-.708l-.647-.646 3.147-3.146a1.207 1.207 0 0 0 0-1.708l-2-2zM2 12.707l7-7L10.293 7l-7 7H2v-1.293z"></path>
    </svg>
  `,"grip-vertical":`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-grip-vertical" viewBox="0 0 16 16">
      <path d="M7 2a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM7 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"></path>
    </svg>
  `,indeterminate:`
    <svg part="indeterminate-icon" class="checkbox__icon" viewBox="0 0 16 16">
      <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round">
        <g stroke="currentColor" stroke-width="2">
          <g transform="translate(2.285714, 6.857143)">
            <path d="M10.2857143,1.14285714 L1.14285714,1.14285714"></path>
          </g>
        </g>
      </g>
    </svg>
  `,"person-fill":`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
      <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
    </svg>
  `,"play-fill":`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-play-fill" viewBox="0 0 16 16">
      <path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"></path>
    </svg>
  `,"pause-fill":`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pause-fill" viewBox="0 0 16 16">
      <path d="M5.5 3.5A1.5 1.5 0 0 1 7 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5zm5 0A1.5 1.5 0 0 1 12 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5z"></path>
    </svg>
  `,radio:`
    <svg part="checked-icon" class="radio__icon" viewBox="0 0 16 16">
      <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
        <g fill="currentColor">
          <circle cx="8" cy="8" r="3.42857143"></circle>
        </g>
      </g>
    </svg>
  `,"star-fill":`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star-fill" viewBox="0 0 16 16">
      <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
    </svg>
  `,"x-lg":`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
      <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
    </svg>
  `,"x-circle-fill":`
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle-fill" viewBox="0 0 16 16">
      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"></path>
    </svg>
  `},Xu={name:"system",resolver:e=>e in vs?`data:image/svg+xml,${encodeURIComponent(vs[e])}`:""},Gu=Xu,Yu=[Qu,Gu],In=[];function Zu(e){In.push(e)}function td(e){In=In.filter(t=>t!==e)}function ys(e){return Yu.find(t=>t.name===e)}var ed=ft`
  :host {
    display: inline-block;
    width: 1em;
    height: 1em;
    box-sizing: content-box !important;
  }

  svg {
    display: block;
    height: 100%;
    width: 100%;
  }
`;function xt(e,t){const i=ce({waitUntilFirstUpdate:!1},t);return(n,r)=>{const{update:s}=n,o=Array.isArray(e)?e:[e];n.update=function(a){o.forEach(l=>{const c=l;if(a.has(c)){const u=a.get(c),d=this[c];u!==d&&(!i.waitUntilFirstUpdate||this.hasUpdated)&&this[r](u,d)}}),s.call(this,a)}}}/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const id=(e,t)=>(e==null?void 0:e._$litType$)!==void 0,nd=e=>e.strings===void 0,rd={},sd=(e,t=rd)=>e._$AH=t;var Pe=Symbol(),ci=Symbol(),fn,mn=new Map,mt=class extends nt{constructor(){super(...arguments),this.initialRender=!1,this.svg=null,this.label="",this.library="default"}async resolveIcon(e,t){var i;let n;if(t!=null&&t.spriteSheet)return this.svg=I`<svg part="svg">
        <use part="use" href="${e}"></use>
      </svg>`,this.svg;try{if(n=await fetch(e,{mode:"cors"}),!n.ok)return n.status===410?Pe:ci}catch{return ci}try{const r=document.createElement("div");r.innerHTML=await n.text();const s=r.firstElementChild;if(((i=s==null?void 0:s.tagName)==null?void 0:i.toLowerCase())!=="svg")return Pe;fn||(fn=new DOMParser);const a=fn.parseFromString(s.outerHTML,"text/html").body.querySelector("svg");return a?(a.part.add("svg"),document.adoptNode(a)):Pe}catch{return Pe}}connectedCallback(){super.connectedCallback(),Zu(this)}firstUpdated(){this.initialRender=!0,this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),td(this)}getIconSource(){const e=ys(this.library);return this.name&&e?{url:e.resolver(this.name),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label=="string"&&this.label.length>0?(this.setAttribute("role","img"),this.setAttribute("aria-label",this.label),this.removeAttribute("aria-hidden")):(this.removeAttribute("role"),this.removeAttribute("aria-label"),this.setAttribute("aria-hidden","true"))}async setIcon(){var e;const{url:t,fromLibrary:i}=this.getIconSource(),n=i?ys(this.library):void 0;if(!t){this.svg=null;return}let r=mn.get(t);if(r||(r=this.resolveIcon(t,n),mn.set(t,r)),!this.initialRender)return;const s=await r;if(s===ci&&mn.delete(t),t===this.getIconSource().url){if(id(s)){if(this.svg=s,n){await this.updateComplete;const o=this.shadowRoot.querySelector("[part='svg']");typeof n.mutator=="function"&&o&&n.mutator(o)}return}switch(s){case ci:case Pe:this.svg=null,this.emit("sl-error");break;default:this.svg=s.cloneNode(!0),(e=n==null?void 0:n.mutator)==null||e.call(n,this.svg),this.emit("sl-load")}}}render(){return this.svg}};mt.styles=[Tt,ed];h([et()],mt.prototype,"svg",2);h([v({reflect:!0})],mt.prototype,"name",2);h([v()],mt.prototype,"src",2);h([v()],mt.prototype,"label",2);h([v({reflect:!0})],mt.prototype,"library",2);h([xt("label")],mt.prototype,"handleLabelChange",1);h([xt(["name","src","library"])],mt.prototype,"setIcon",1);/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Mt={ATTRIBUTE:1,PROPERTY:3,BOOLEAN_ATTRIBUTE:4},br=e=>(...t)=>({_$litDirective$:e,values:t});let vr=class{constructor(t){}get _$AU(){return this._$AM._$AU}_$AT(t,i,n){this._$Ct=t,this._$AM=i,this._$Ci=n}_$AS(t,i){return this.update(t,i)}update(t,i){return this.render(...i)}};/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const kt=br(class extends vr{constructor(e){var t;if(super(e),e.type!==Mt.ATTRIBUTE||e.name!=="class"||((t=e.strings)==null?void 0:t.length)>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(e){return" "+Object.keys(e).filter((t=>e[t])).join(" ")+" "}update(e,[t]){var n,r;if(this.st===void 0){this.st=new Set,e.strings!==void 0&&(this.nt=new Set(e.strings.join(" ").split(/\s/).filter((s=>s!==""))));for(const s in t)t[s]&&!((n=this.nt)!=null&&n.has(s))&&this.st.add(s);return this.render(t)}const i=e.element.classList;for(const s of this.st)s in t||(i.remove(s),this.st.delete(s));for(const s in t){const o=!!t[s];o===this.st.has(s)||(r=this.nt)!=null&&r.has(s)||(o?(i.add(s),this.st.add(s)):(i.remove(s),this.st.delete(s)))}return ot}});/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const F=e=>e??D;/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const od=br(class extends vr{constructor(e){if(super(e),e.type!==Mt.PROPERTY&&e.type!==Mt.ATTRIBUTE&&e.type!==Mt.BOOLEAN_ATTRIBUTE)throw Error("The `live` directive is not allowed on child or event bindings");if(!nd(e))throw Error("`live` bindings can only contain a single expression")}render(e){return e}update(e,[t]){if(t===ot||t===D)return t;const i=e.element,n=e.name;if(e.type===Mt.PROPERTY){if(t===i[n])return ot}else if(e.type===Mt.BOOLEAN_ATTRIBUTE){if(!!t===i.hasAttribute(n))return ot}else if(e.type===Mt.ATTRIBUTE&&i.getAttribute(n)===t+"")return ot;return sd(e),t}});var A=class extends nt{constructor(){super(...arguments),this.formControlController=new pr(this,{assumeInteractionOn:["sl-blur","sl-input"]}),this.hasSlotController=new Wo(this,"help-text","label"),this.localize=new _e(this),this.hasFocus=!1,this.title="",this.__numberInput=Object.assign(document.createElement("input"),{type:"number"}),this.__dateInput=Object.assign(document.createElement("input"),{type:"date"}),this.type="text",this.name="",this.value="",this.defaultValue="",this.size="medium",this.filled=!1,this.pill=!1,this.label="",this.helpText="",this.clearable=!1,this.disabled=!1,this.placeholder="",this.readonly=!1,this.passwordToggle=!1,this.passwordVisible=!1,this.noSpinButtons=!1,this.form="",this.required=!1,this.spellcheck=!0}get valueAsDate(){var e;return this.__dateInput.type=this.type,this.__dateInput.value=this.value,((e=this.input)==null?void 0:e.valueAsDate)||this.__dateInput.valueAsDate}set valueAsDate(e){this.__dateInput.type=this.type,this.__dateInput.valueAsDate=e,this.value=this.__dateInput.value}get valueAsNumber(){var e;return this.__numberInput.value=this.value,((e=this.input)==null?void 0:e.valueAsNumber)||this.__numberInput.valueAsNumber}set valueAsNumber(e){this.__numberInput.valueAsNumber=e,this.value=this.__numberInput.value}get validity(){return this.input.validity}get validationMessage(){return this.input.validationMessage}firstUpdated(){this.formControlController.updateValidity()}handleBlur(){this.hasFocus=!1,this.emit("sl-blur")}handleChange(){this.value=this.input.value,this.emit("sl-change")}handleClearClick(e){e.preventDefault(),this.value!==""&&(this.value="",this.emit("sl-clear"),this.emit("sl-input"),this.emit("sl-change")),this.input.focus()}handleFocus(){this.hasFocus=!0,this.emit("sl-focus")}handleInput(){this.value=this.input.value,this.formControlController.updateValidity(),this.emit("sl-input")}handleInvalid(e){this.formControlController.setValidity(!1),this.formControlController.emitInvalidEvent(e)}handleKeyDown(e){const t=e.metaKey||e.ctrlKey||e.shiftKey||e.altKey;e.key==="Enter"&&!t&&setTimeout(()=>{!e.defaultPrevented&&!e.isComposing&&this.formControlController.submit()})}handlePasswordToggle(){this.passwordVisible=!this.passwordVisible}handleDisabledChange(){this.formControlController.setValidity(this.disabled)}handleStepChange(){this.input.step=String(this.step),this.formControlController.updateValidity()}async handleValueChange(){await this.updateComplete,this.formControlController.updateValidity()}focus(e){this.input.focus(e)}blur(){this.input.blur()}select(){this.input.select()}setSelectionRange(e,t,i="none"){this.input.setSelectionRange(e,t,i)}setRangeText(e,t,i,n="preserve"){const r=t??this.input.selectionStart,s=i??this.input.selectionEnd;this.input.setRangeText(e,r,s,n),this.value!==this.input.value&&(this.value=this.input.value)}showPicker(){"showPicker"in HTMLInputElement.prototype&&this.input.showPicker()}stepUp(){this.input.stepUp(),this.value!==this.input.value&&(this.value=this.input.value)}stepDown(){this.input.stepDown(),this.value!==this.input.value&&(this.value=this.input.value)}checkValidity(){return this.input.checkValidity()}getForm(){return this.formControlController.getForm()}reportValidity(){return this.input.reportValidity()}setCustomValidity(e){this.input.setCustomValidity(e),this.formControlController.updateValidity()}render(){const e=this.hasSlotController.test("label"),t=this.hasSlotController.test("help-text"),i=this.label?!0:!!e,n=this.helpText?!0:!!t,s=this.clearable&&!this.disabled&&!this.readonly&&(typeof this.value=="number"||this.value.length>0);return I`
      <div
        part="form-control"
        class=${kt({"form-control":!0,"form-control--small":this.size==="small","form-control--medium":this.size==="medium","form-control--large":this.size==="large","form-control--has-label":i,"form-control--has-help-text":n})}
      >
        <label
          part="form-control-label"
          class="form-control__label"
          for="input"
          aria-hidden=${i?"false":"true"}
        >
          <slot name="label">${this.label}</slot>
        </label>

        <div part="form-control-input" class="form-control-input">
          <div
            part="base"
            class=${kt({input:!0,"input--small":this.size==="small","input--medium":this.size==="medium","input--large":this.size==="large","input--pill":this.pill,"input--standard":!this.filled,"input--filled":this.filled,"input--disabled":this.disabled,"input--focused":this.hasFocus,"input--empty":!this.value,"input--no-spin-buttons":this.noSpinButtons})}
          >
            <span part="prefix" class="input__prefix">
              <slot name="prefix"></slot>
            </span>

            <input
              part="input"
              id="input"
              class="input__control"
              type=${this.type==="password"&&this.passwordVisible?"text":this.type}
              title=${this.title}
              name=${F(this.name)}
              ?disabled=${this.disabled}
              ?readonly=${this.readonly}
              ?required=${this.required}
              placeholder=${F(this.placeholder)}
              minlength=${F(this.minlength)}
              maxlength=${F(this.maxlength)}
              min=${F(this.min)}
              max=${F(this.max)}
              step=${F(this.step)}
              .value=${od(this.value)}
              autocapitalize=${F(this.autocapitalize)}
              autocomplete=${F(this.autocomplete)}
              autocorrect=${F(this.autocorrect)}
              ?autofocus=${this.autofocus}
              spellcheck=${this.spellcheck}
              pattern=${F(this.pattern)}
              enterkeyhint=${F(this.enterkeyhint)}
              inputmode=${F(this.inputmode)}
              aria-describedby="help-text"
              @change=${this.handleChange}
              @input=${this.handleInput}
              @invalid=${this.handleInvalid}
              @keydown=${this.handleKeyDown}
              @focus=${this.handleFocus}
              @blur=${this.handleBlur}
            />

            ${s?I`
                  <button
                    part="clear-button"
                    class="input__clear"
                    type="button"
                    aria-label=${this.localize.term("clearEntry")}
                    @click=${this.handleClearClick}
                    tabindex="-1"
                  >
                    <slot name="clear-icon">
                      <sl-icon name="x-circle-fill" library="system"></sl-icon>
                    </slot>
                  </button>
                `:""}
            ${this.passwordToggle&&!this.disabled?I`
                  <button
                    part="password-toggle-button"
                    class="input__password-toggle"
                    type="button"
                    aria-label=${this.localize.term(this.passwordVisible?"hidePassword":"showPassword")}
                    @click=${this.handlePasswordToggle}
                    tabindex="-1"
                  >
                    ${this.passwordVisible?I`
                          <slot name="show-password-icon">
                            <sl-icon name="eye-slash" library="system"></sl-icon>
                          </slot>
                        `:I`
                          <slot name="hide-password-icon">
                            <sl-icon name="eye" library="system"></sl-icon>
                          </slot>
                        `}
                  </button>
                `:""}

            <span part="suffix" class="input__suffix">
              <slot name="suffix"></slot>
            </span>
          </div>
        </div>

        <div
          part="form-control-help-text"
          id="help-text"
          class="form-control__help-text"
          aria-hidden=${n?"false":"true"}
        >
          <slot name="help-text">${this.helpText}</slot>
        </div>
      </div>
    `}};A.styles=[Tt,qu,ju];A.dependencies={"sl-icon":mt};h([it(".input__control")],A.prototype,"input",2);h([et()],A.prototype,"hasFocus",2);h([v()],A.prototype,"title",2);h([v({reflect:!0})],A.prototype,"type",2);h([v()],A.prototype,"name",2);h([v()],A.prototype,"value",2);h([Vo()],A.prototype,"defaultValue",2);h([v({reflect:!0})],A.prototype,"size",2);h([v({type:Boolean,reflect:!0})],A.prototype,"filled",2);h([v({type:Boolean,reflect:!0})],A.prototype,"pill",2);h([v()],A.prototype,"label",2);h([v({attribute:"help-text"})],A.prototype,"helpText",2);h([v({type:Boolean})],A.prototype,"clearable",2);h([v({type:Boolean,reflect:!0})],A.prototype,"disabled",2);h([v()],A.prototype,"placeholder",2);h([v({type:Boolean,reflect:!0})],A.prototype,"readonly",2);h([v({attribute:"password-toggle",type:Boolean})],A.prototype,"passwordToggle",2);h([v({attribute:"password-visible",type:Boolean})],A.prototype,"passwordVisible",2);h([v({attribute:"no-spin-buttons",type:Boolean})],A.prototype,"noSpinButtons",2);h([v({reflect:!0})],A.prototype,"form",2);h([v({type:Boolean,reflect:!0})],A.prototype,"required",2);h([v()],A.prototype,"pattern",2);h([v({type:Number})],A.prototype,"minlength",2);h([v({type:Number})],A.prototype,"maxlength",2);h([v()],A.prototype,"min",2);h([v()],A.prototype,"max",2);h([v()],A.prototype,"step",2);h([v()],A.prototype,"autocapitalize",2);h([v()],A.prototype,"autocorrect",2);h([v()],A.prototype,"autocomplete",2);h([v({type:Boolean})],A.prototype,"autofocus",2);h([v()],A.prototype,"enterkeyhint",2);h([v({type:Boolean,converter:{fromAttribute:e=>!(!e||e==="false"),toAttribute:e=>e?"true":"false"}})],A.prototype,"spellcheck",2);h([v()],A.prototype,"inputmode",2);h([xt("disabled",{waitUntilFirstUpdate:!0})],A.prototype,"handleDisabledChange",1);h([xt("step",{waitUntilFirstUpdate:!0})],A.prototype,"handleStepChange",1);h([xt("value",{waitUntilFirstUpdate:!0})],A.prototype,"handleValueChange",1);function gn(e,t){function i(r){const s=e.getBoundingClientRect(),o=e.ownerDocument.defaultView,a=s.left+o.scrollX,l=s.top+o.scrollY,c=r.pageX-a,u=r.pageY-l;t!=null&&t.onMove&&t.onMove(c,u)}function n(){document.removeEventListener("pointermove",i),document.removeEventListener("pointerup",n),t!=null&&t.onStop&&t.onStop()}document.addEventListener("pointermove",i,{passive:!0}),document.addEventListener("pointerup",n),(t==null?void 0:t.initialEvent)instanceof PointerEvent&&i(t.initialEvent)}var ad=ft`
  :host {
    display: inline-block;
  }

  .dropdown::part(popup) {
    z-index: var(--sl-z-index-dropdown);
  }

  .dropdown[data-current-placement^='top']::part(popup) {
    transform-origin: bottom;
  }

  .dropdown[data-current-placement^='bottom']::part(popup) {
    transform-origin: top;
  }

  .dropdown[data-current-placement^='left']::part(popup) {
    transform-origin: right;
  }

  .dropdown[data-current-placement^='right']::part(popup) {
    transform-origin: left;
  }

  .dropdown__trigger {
    display: block;
  }

  .dropdown__panel {
    font-family: var(--sl-font-sans);
    font-size: var(--sl-font-size-medium);
    font-weight: var(--sl-font-weight-normal);
    box-shadow: var(--sl-shadow-large);
    border-radius: var(--sl-border-radius-medium);
    pointer-events: none;
  }

  .dropdown--open .dropdown__panel {
    display: block;
    pointer-events: all;
  }

  /* When users slot a menu, make sure it conforms to the popup's auto-size */
  ::slotted(sl-menu) {
    max-width: var(--auto-size-available-width) !important;
    max-height: var(--auto-size-available-height) !important;
  }
`;function*Go(e=document.activeElement){e!=null&&(yield e,"shadowRoot"in e&&e.shadowRoot&&e.shadowRoot.mode!=="closed"&&(yield*Bu(Go(e.shadowRoot.activeElement))))}function ld(){return[...Go()].pop()}var ws=new WeakMap;function Yo(e){let t=ws.get(e);return t||(t=window.getComputedStyle(e,null),ws.set(e,t)),t}function cd(e){if(typeof e.checkVisibility=="function")return e.checkVisibility({checkOpacity:!1,checkVisibilityCSS:!0});const t=Yo(e);return t.visibility!=="hidden"&&t.display!=="none"}function ud(e){const t=Yo(e),{overflowY:i,overflowX:n}=t;return i==="scroll"||n==="scroll"?!0:i!=="auto"||n!=="auto"?!1:e.scrollHeight>e.clientHeight&&i==="auto"||e.scrollWidth>e.clientWidth&&n==="auto"}function dd(e){const t=e.tagName.toLowerCase(),i=Number(e.getAttribute("tabindex"));if(e.hasAttribute("tabindex")&&(isNaN(i)||i<=-1)||e.hasAttribute("disabled")||e.closest("[inert]"))return!1;if(t==="input"&&e.getAttribute("type")==="radio"){const s=e.getRootNode(),o=`input[type='radio'][name="${e.getAttribute("name")}"]`,a=s.querySelector(`${o}:checked`);return a?a===e:s.querySelector(o)===e}return cd(e)?(t==="audio"||t==="video")&&e.hasAttribute("controls")||e.hasAttribute("tabindex")||e.hasAttribute("contenteditable")&&e.getAttribute("contenteditable")!=="false"||["button","input","select","textarea","a","audio","video","summary","iframe"].includes(t)?!0:ud(e):!1}function hd(e){var t,i;const n=fd(e),r=(t=n[0])!=null?t:null,s=(i=n[n.length-1])!=null?i:null;return{start:r,end:s}}function pd(e,t){var i;return((i=e.getRootNode({composed:!0}))==null?void 0:i.host)!==t}function fd(e){const t=new WeakMap,i=[];function n(r){if(r instanceof Element){if(r.hasAttribute("inert")||r.closest("[inert]")||t.has(r))return;t.set(r,!0),!i.includes(r)&&dd(r)&&i.push(r),r instanceof HTMLSlotElement&&pd(r,e)&&r.assignedElements({flatten:!0}).forEach(s=>{n(s)}),r.shadowRoot!==null&&r.shadowRoot.mode==="open"&&n(r.shadowRoot)}for(const s of r.children)n(s)}return n(e),i.sort((r,s)=>{const o=Number(r.getAttribute("tabindex"))||0;return(Number(s.getAttribute("tabindex"))||0)-o})}var md=ft`
  :host {
    --arrow-color: var(--sl-color-neutral-1000);
    --arrow-size: 6px;

    /*
     * These properties are computed to account for the arrow's dimensions after being rotated 45º. The constant
     * 0.7071 is derived from sin(45), which is the diagonal size of the arrow's container after rotating.
     */
    --arrow-size-diagonal: calc(var(--arrow-size) * 0.7071);
    --arrow-padding-offset: calc(var(--arrow-size-diagonal) - var(--arrow-size));

    display: contents;
  }

  .popup {
    position: absolute;
    isolation: isolate;
    max-width: var(--auto-size-available-width, none);
    max-height: var(--auto-size-available-height, none);
  }

  .popup--fixed {
    position: fixed;
  }

  .popup:not(.popup--active) {
    display: none;
  }

  .popup__arrow {
    position: absolute;
    width: calc(var(--arrow-size-diagonal) * 2);
    height: calc(var(--arrow-size-diagonal) * 2);
    rotate: 45deg;
    background: var(--arrow-color);
    z-index: -1;
  }

  /* Hover bridge */
  .popup-hover-bridge:not(.popup-hover-bridge--visible) {
    display: none;
  }

  .popup-hover-bridge {
    position: fixed;
    z-index: calc(var(--sl-z-index-dropdown) - 1);
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    clip-path: polygon(
      var(--hover-bridge-top-left-x, 0) var(--hover-bridge-top-left-y, 0),
      var(--hover-bridge-top-right-x, 0) var(--hover-bridge-top-right-y, 0),
      var(--hover-bridge-bottom-right-x, 0) var(--hover-bridge-bottom-right-y, 0),
      var(--hover-bridge-bottom-left-x, 0) var(--hover-bridge-bottom-left-y, 0)
    );
  }
`;const Bt=Math.min,Z=Math.max,Fi=Math.round,ui=Math.floor,wt=e=>({x:e,y:e}),gd={left:"right",right:"left",bottom:"top",top:"bottom"},bd={start:"end",end:"start"};function Bn(e,t,i){return Z(e,Bt(t,i))}function xe(e,t){return typeof e=="function"?e(t):e}function Dt(e){return e.split("-")[0]}function Ee(e){return e.split("-")[1]}function Zo(e){return e==="x"?"y":"x"}function yr(e){return e==="y"?"height":"width"}const vd=new Set(["top","bottom"]);function Ct(e){return vd.has(Dt(e))?"y":"x"}function wr(e){return Zo(Ct(e))}function yd(e,t,i){i===void 0&&(i=!1);const n=Ee(e),r=wr(e),s=yr(r);let o=r==="x"?n===(i?"end":"start")?"right":"left":n==="start"?"bottom":"top";return t.reference[s]>t.floating[s]&&(o=Oi(o)),[o,Oi(o)]}function wd(e){const t=Oi(e);return[Dn(e),t,Dn(t)]}function Dn(e){return e.replace(/start|end/g,t=>bd[t])}const _s=["left","right"],xs=["right","left"],_d=["top","bottom"],xd=["bottom","top"];function Ed(e,t,i){switch(e){case"top":case"bottom":return i?t?xs:_s:t?_s:xs;case"left":case"right":return t?_d:xd;default:return[]}}function Sd(e,t,i,n){const r=Ee(e);let s=Ed(Dt(e),i==="start",n);return r&&(s=s.map(o=>o+"-"+r),t&&(s=s.concat(s.map(Dn)))),s}function Oi(e){return e.replace(/left|right|bottom|top/g,t=>gd[t])}function Cd(e){return{top:0,right:0,bottom:0,left:0,...e}}function ta(e){return typeof e!="number"?Cd(e):{top:e,right:e,bottom:e,left:e}}function Li(e){const{x:t,y:i,width:n,height:r}=e;return{width:n,height:r,top:i,left:t,right:t+n,bottom:i+r,x:t,y:i}}function Es(e,t,i){let{reference:n,floating:r}=e;const s=Ct(t),o=wr(t),a=yr(o),l=Dt(t),c=s==="y",u=n.x+n.width/2-r.width/2,d=n.y+n.height/2-r.height/2,p=n[a]/2-r[a]/2;let f;switch(l){case"top":f={x:u,y:n.y-r.height};break;case"bottom":f={x:u,y:n.y+n.height};break;case"right":f={x:n.x+n.width,y:d};break;case"left":f={x:n.x-r.width,y:d};break;default:f={x:n.x,y:n.y}}switch(Ee(t)){case"start":f[o]-=p*(i&&c?-1:1);break;case"end":f[o]+=p*(i&&c?-1:1);break}return f}const kd=async(e,t,i)=>{const{placement:n="bottom",strategy:r="absolute",middleware:s=[],platform:o}=i,a=s.filter(Boolean),l=await(o.isRTL==null?void 0:o.isRTL(t));let c=await o.getElementRects({reference:e,floating:t,strategy:r}),{x:u,y:d}=Es(c,n,l),p=n,f={},b=0;for(let w=0;w<a.length;w++){const{name:g,fn:_}=a[w],{x,y:E,data:k,reset:S}=await _({x:u,y:d,initialPlacement:n,placement:p,strategy:r,middlewareData:f,rects:c,platform:o,elements:{reference:e,floating:t}});u=x??u,d=E??d,f={...f,[g]:{...f[g],...k}},S&&b<=50&&(b++,typeof S=="object"&&(S.placement&&(p=S.placement),S.rects&&(c=S.rects===!0?await o.getElementRects({reference:e,floating:t,strategy:r}):S.rects),{x:u,y:d}=Es(c,p,l)),w=-1)}return{x:u,y:d,placement:p,strategy:r,middlewareData:f}};async function _r(e,t){var i;t===void 0&&(t={});const{x:n,y:r,platform:s,rects:o,elements:a,strategy:l}=e,{boundary:c="clippingAncestors",rootBoundary:u="viewport",elementContext:d="floating",altBoundary:p=!1,padding:f=0}=xe(t,e),b=ta(f),g=a[p?d==="floating"?"reference":"floating":d],_=Li(await s.getClippingRect({element:(i=await(s.isElement==null?void 0:s.isElement(g)))==null||i?g:g.contextElement||await(s.getDocumentElement==null?void 0:s.getDocumentElement(a.floating)),boundary:c,rootBoundary:u,strategy:l})),x=d==="floating"?{x:n,y:r,width:o.floating.width,height:o.floating.height}:o.reference,E=await(s.getOffsetParent==null?void 0:s.getOffsetParent(a.floating)),k=await(s.isElement==null?void 0:s.isElement(E))?await(s.getScale==null?void 0:s.getScale(E))||{x:1,y:1}:{x:1,y:1},S=Li(s.convertOffsetParentRelativeRectToViewportRelativeRect?await s.convertOffsetParentRelativeRectToViewportRelativeRect({elements:a,rect:x,offsetParent:E,strategy:l}):x);return{top:(_.top-S.top+b.top)/k.y,bottom:(S.bottom-_.bottom+b.bottom)/k.y,left:(_.left-S.left+b.left)/k.x,right:(S.right-_.right+b.right)/k.x}}const Ad=e=>({name:"arrow",options:e,async fn(t){const{x:i,y:n,placement:r,rects:s,platform:o,elements:a,middlewareData:l}=t,{element:c,padding:u=0}=xe(e,t)||{};if(c==null)return{};const d=ta(u),p={x:i,y:n},f=wr(r),b=yr(f),w=await o.getDimensions(c),g=f==="y",_=g?"top":"left",x=g?"bottom":"right",E=g?"clientHeight":"clientWidth",k=s.reference[b]+s.reference[f]-p[f]-s.floating[b],S=p[f]-s.reference[f],O=await(o.getOffsetParent==null?void 0:o.getOffsetParent(c));let R=O?O[E]:0;(!R||!await(o.isElement==null?void 0:o.isElement(O)))&&(R=a.floating[E]||s.floating[b]);const H=k/2-S/2,V=R/2-w[b]/2-1,K=Bt(d[_],V),$t=Bt(d[x],V),bt=K,Rt=R-w[b]-$t,W=R/2-w[b]/2+H,qt=Bn(bt,W,Rt),St=!l.arrow&&Ee(r)!=null&&W!==qt&&s.reference[b]/2-(W<bt?K:$t)-w[b]/2<0,lt=St?W<bt?W-bt:W-Rt:0;return{[f]:p[f]+lt,data:{[f]:qt,centerOffset:W-qt-lt,...St&&{alignmentOffset:lt}},reset:St}}}),Td=function(e){return e===void 0&&(e={}),{name:"flip",options:e,async fn(t){var i,n;const{placement:r,middlewareData:s,rects:o,initialPlacement:a,platform:l,elements:c}=t,{mainAxis:u=!0,crossAxis:d=!0,fallbackPlacements:p,fallbackStrategy:f="bestFit",fallbackAxisSideDirection:b="none",flipAlignment:w=!0,...g}=xe(e,t);if((i=s.arrow)!=null&&i.alignmentOffset)return{};const _=Dt(r),x=Ct(a),E=Dt(a)===a,k=await(l.isRTL==null?void 0:l.isRTL(c.floating)),S=p||(E||!w?[Oi(a)]:wd(a)),O=b!=="none";!p&&O&&S.push(...Sd(a,w,b,k));const R=[a,...S],H=await _r(t,g),V=[];let K=((n=s.flip)==null?void 0:n.overflows)||[];if(u&&V.push(H[_]),d){const W=yd(r,o,k);V.push(H[W[0]],H[W[1]])}if(K=[...K,{placement:r,overflows:V}],!V.every(W=>W<=0)){var $t,bt;const W=((($t=s.flip)==null?void 0:$t.index)||0)+1,qt=R[W];if(qt&&(!(d==="alignment"?x!==Ct(qt):!1)||K.every(ct=>Ct(ct.placement)===x?ct.overflows[0]>0:!0)))return{data:{index:W,overflows:K},reset:{placement:qt}};let St=(bt=K.filter(lt=>lt.overflows[0]<=0).sort((lt,ct)=>lt.overflows[1]-ct.overflows[1])[0])==null?void 0:bt.placement;if(!St)switch(f){case"bestFit":{var Rt;const lt=(Rt=K.filter(ct=>{if(O){const Ft=Ct(ct.placement);return Ft===x||Ft==="y"}return!0}).map(ct=>[ct.placement,ct.overflows.filter(Ft=>Ft>0).reduce((Ft,$l)=>Ft+$l,0)]).sort((ct,Ft)=>ct[1]-Ft[1])[0])==null?void 0:Rt[0];lt&&(St=lt);break}case"initialPlacement":St=a;break}if(r!==St)return{reset:{placement:St}}}return{}}}},$d=new Set(["left","top"]);async function Rd(e,t){const{placement:i,platform:n,elements:r}=e,s=await(n.isRTL==null?void 0:n.isRTL(r.floating)),o=Dt(i),a=Ee(i),l=Ct(i)==="y",c=$d.has(o)?-1:1,u=s&&l?-1:1,d=xe(t,e);let{mainAxis:p,crossAxis:f,alignmentAxis:b}=typeof d=="number"?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return a&&typeof b=="number"&&(f=a==="end"?b*-1:b),l?{x:f*u,y:p*c}:{x:p*c,y:f*u}}const Fd=function(e){return e===void 0&&(e=0),{name:"offset",options:e,async fn(t){var i,n;const{x:r,y:s,placement:o,middlewareData:a}=t,l=await Rd(t,e);return o===((i=a.offset)==null?void 0:i.placement)&&(n=a.arrow)!=null&&n.alignmentOffset?{}:{x:r+l.x,y:s+l.y,data:{...l,placement:o}}}}},Od=function(e){return e===void 0&&(e={}),{name:"shift",options:e,async fn(t){const{x:i,y:n,placement:r}=t,{mainAxis:s=!0,crossAxis:o=!1,limiter:a={fn:g=>{let{x:_,y:x}=g;return{x:_,y:x}}},...l}=xe(e,t),c={x:i,y:n},u=await _r(t,l),d=Ct(Dt(r)),p=Zo(d);let f=c[p],b=c[d];if(s){const g=p==="y"?"top":"left",_=p==="y"?"bottom":"right",x=f+u[g],E=f-u[_];f=Bn(x,f,E)}if(o){const g=d==="y"?"top":"left",_=d==="y"?"bottom":"right",x=b+u[g],E=b-u[_];b=Bn(x,b,E)}const w=a.fn({...t,[p]:f,[d]:b});return{...w,data:{x:w.x-i,y:w.y-n,enabled:{[p]:s,[d]:o}}}}}},Ld=function(e){return e===void 0&&(e={}),{name:"size",options:e,async fn(t){var i,n;const{placement:r,rects:s,platform:o,elements:a}=t,{apply:l=()=>{},...c}=xe(e,t),u=await _r(t,c),d=Dt(r),p=Ee(r),f=Ct(r)==="y",{width:b,height:w}=s.floating;let g,_;d==="top"||d==="bottom"?(g=d,_=p===(await(o.isRTL==null?void 0:o.isRTL(a.floating))?"start":"end")?"left":"right"):(_=d,g=p==="end"?"top":"bottom");const x=w-u.top-u.bottom,E=b-u.left-u.right,k=Bt(w-u[g],x),S=Bt(b-u[_],E),O=!t.middlewareData.shift;let R=k,H=S;if((i=t.middlewareData.shift)!=null&&i.enabled.x&&(H=E),(n=t.middlewareData.shift)!=null&&n.enabled.y&&(R=x),O&&!p){const K=Z(u.left,0),$t=Z(u.right,0),bt=Z(u.top,0),Rt=Z(u.bottom,0);f?H=b-2*(K!==0||$t!==0?K+$t:Z(u.left,u.right)):R=w-2*(bt!==0||Rt!==0?bt+Rt:Z(u.top,u.bottom))}await l({...t,availableWidth:H,availableHeight:R});const V=await o.getDimensions(a.floating);return b!==V.width||w!==V.height?{reset:{rects:!0}}:{}}}};function Wi(){return typeof window<"u"}function Se(e){return ea(e)?(e.nodeName||"").toLowerCase():"#document"}function tt(e){var t;return(e==null||(t=e.ownerDocument)==null?void 0:t.defaultView)||window}function Et(e){var t;return(t=(ea(e)?e.ownerDocument:e.document)||window.document)==null?void 0:t.documentElement}function ea(e){return Wi()?e instanceof Node||e instanceof tt(e).Node:!1}function ut(e){return Wi()?e instanceof Element||e instanceof tt(e).Element:!1}function _t(e){return Wi()?e instanceof HTMLElement||e instanceof tt(e).HTMLElement:!1}function Ss(e){return!Wi()||typeof ShadowRoot>"u"?!1:e instanceof ShadowRoot||e instanceof tt(e).ShadowRoot}const Md=new Set(["inline","contents"]);function ni(e){const{overflow:t,overflowX:i,overflowY:n,display:r}=dt(e);return/auto|scroll|overlay|hidden|clip/.test(t+n+i)&&!Md.has(r)}const Pd=new Set(["table","td","th"]);function zd(e){return Pd.has(Se(e))}const Id=[":popover-open",":modal"];function Ji(e){return Id.some(t=>{try{return e.matches(t)}catch{return!1}})}const Bd=["transform","translate","scale","rotate","perspective"],Dd=["transform","translate","scale","rotate","perspective","filter"],Nd=["paint","layout","strict","content"];function Ki(e){const t=xr(),i=ut(e)?dt(e):e;return Bd.some(n=>i[n]?i[n]!=="none":!1)||(i.containerType?i.containerType!=="normal":!1)||!t&&(i.backdropFilter?i.backdropFilter!=="none":!1)||!t&&(i.filter?i.filter!=="none":!1)||Dd.some(n=>(i.willChange||"").includes(n))||Nd.some(n=>(i.contain||"").includes(n))}function Ud(e){let t=Nt(e);for(;_t(t)&&!ge(t);){if(Ki(t))return t;if(Ji(t))return null;t=Nt(t)}return null}function xr(){return typeof CSS>"u"||!CSS.supports?!1:CSS.supports("-webkit-backdrop-filter","none")}const Hd=new Set(["html","body","#document"]);function ge(e){return Hd.has(Se(e))}function dt(e){return tt(e).getComputedStyle(e)}function Qi(e){return ut(e)?{scrollLeft:e.scrollLeft,scrollTop:e.scrollTop}:{scrollLeft:e.scrollX,scrollTop:e.scrollY}}function Nt(e){if(Se(e)==="html")return e;const t=e.assignedSlot||e.parentNode||Ss(e)&&e.host||Et(e);return Ss(t)?t.host:t}function ia(e){const t=Nt(e);return ge(t)?e.ownerDocument?e.ownerDocument.body:e.body:_t(t)&&ni(t)?t:ia(t)}function Xe(e,t,i){var n;t===void 0&&(t=[]),i===void 0&&(i=!0);const r=ia(e),s=r===((n=e.ownerDocument)==null?void 0:n.body),o=tt(r);if(s){const a=Nn(o);return t.concat(o,o.visualViewport||[],ni(r)?r:[],a&&i?Xe(a):[])}return t.concat(r,Xe(r,[],i))}function Nn(e){return e.parent&&Object.getPrototypeOf(e.parent)?e.frameElement:null}function na(e){const t=dt(e);let i=parseFloat(t.width)||0,n=parseFloat(t.height)||0;const r=_t(e),s=r?e.offsetWidth:i,o=r?e.offsetHeight:n,a=Fi(i)!==s||Fi(n)!==o;return a&&(i=s,n=o),{width:i,height:n,$:a}}function Er(e){return ut(e)?e:e.contextElement}function pe(e){const t=Er(e);if(!_t(t))return wt(1);const i=t.getBoundingClientRect(),{width:n,height:r,$:s}=na(t);let o=(s?Fi(i.width):i.width)/n,a=(s?Fi(i.height):i.height)/r;return(!o||!Number.isFinite(o))&&(o=1),(!a||!Number.isFinite(a))&&(a=1),{x:o,y:a}}const jd=wt(0);function ra(e){const t=tt(e);return!xr()||!t.visualViewport?jd:{x:t.visualViewport.offsetLeft,y:t.visualViewport.offsetTop}}function qd(e,t,i){return t===void 0&&(t=!1),!i||t&&i!==tt(e)?!1:t}function ae(e,t,i,n){t===void 0&&(t=!1),i===void 0&&(i=!1);const r=e.getBoundingClientRect(),s=Er(e);let o=wt(1);t&&(n?ut(n)&&(o=pe(n)):o=pe(e));const a=qd(s,i,n)?ra(s):wt(0);let l=(r.left+a.x)/o.x,c=(r.top+a.y)/o.y,u=r.width/o.x,d=r.height/o.y;if(s){const p=tt(s),f=n&&ut(n)?tt(n):n;let b=p,w=Nn(b);for(;w&&n&&f!==b;){const g=pe(w),_=w.getBoundingClientRect(),x=dt(w),E=_.left+(w.clientLeft+parseFloat(x.paddingLeft))*g.x,k=_.top+(w.clientTop+parseFloat(x.paddingTop))*g.y;l*=g.x,c*=g.y,u*=g.x,d*=g.y,l+=E,c+=k,b=tt(w),w=Nn(b)}}return Li({width:u,height:d,x:l,y:c})}function Xi(e,t){const i=Qi(e).scrollLeft;return t?t.left+i:ae(Et(e)).left+i}function sa(e,t){const i=e.getBoundingClientRect(),n=i.left+t.scrollLeft-Xi(e,i),r=i.top+t.scrollTop;return{x:n,y:r}}function Vd(e){let{elements:t,rect:i,offsetParent:n,strategy:r}=e;const s=r==="fixed",o=Et(n),a=t?Ji(t.floating):!1;if(n===o||a&&s)return i;let l={scrollLeft:0,scrollTop:0},c=wt(1);const u=wt(0),d=_t(n);if((d||!d&&!s)&&((Se(n)!=="body"||ni(o))&&(l=Qi(n)),_t(n))){const f=ae(n);c=pe(n),u.x=f.x+n.clientLeft,u.y=f.y+n.clientTop}const p=o&&!d&&!s?sa(o,l):wt(0);return{width:i.width*c.x,height:i.height*c.y,x:i.x*c.x-l.scrollLeft*c.x+u.x+p.x,y:i.y*c.y-l.scrollTop*c.y+u.y+p.y}}function Wd(e){return Array.from(e.getClientRects())}function Jd(e){const t=Et(e),i=Qi(e),n=e.ownerDocument.body,r=Z(t.scrollWidth,t.clientWidth,n.scrollWidth,n.clientWidth),s=Z(t.scrollHeight,t.clientHeight,n.scrollHeight,n.clientHeight);let o=-i.scrollLeft+Xi(e);const a=-i.scrollTop;return dt(n).direction==="rtl"&&(o+=Z(t.clientWidth,n.clientWidth)-r),{width:r,height:s,x:o,y:a}}const Cs=25;function Kd(e,t){const i=tt(e),n=Et(e),r=i.visualViewport;let s=n.clientWidth,o=n.clientHeight,a=0,l=0;if(r){s=r.width,o=r.height;const u=xr();(!u||u&&t==="fixed")&&(a=r.offsetLeft,l=r.offsetTop)}const c=Xi(n);if(c<=0){const u=n.ownerDocument,d=u.body,p=getComputedStyle(d),f=u.compatMode==="CSS1Compat"&&parseFloat(p.marginLeft)+parseFloat(p.marginRight)||0,b=Math.abs(n.clientWidth-d.clientWidth-f);b<=Cs&&(s-=b)}else c<=Cs&&(s+=c);return{width:s,height:o,x:a,y:l}}const Qd=new Set(["absolute","fixed"]);function Xd(e,t){const i=ae(e,!0,t==="fixed"),n=i.top+e.clientTop,r=i.left+e.clientLeft,s=_t(e)?pe(e):wt(1),o=e.clientWidth*s.x,a=e.clientHeight*s.y,l=r*s.x,c=n*s.y;return{width:o,height:a,x:l,y:c}}function ks(e,t,i){let n;if(t==="viewport")n=Kd(e,i);else if(t==="document")n=Jd(Et(e));else if(ut(t))n=Xd(t,i);else{const r=ra(e);n={x:t.x-r.x,y:t.y-r.y,width:t.width,height:t.height}}return Li(n)}function oa(e,t){const i=Nt(e);return i===t||!ut(i)||ge(i)?!1:dt(i).position==="fixed"||oa(i,t)}function Gd(e,t){const i=t.get(e);if(i)return i;let n=Xe(e,[],!1).filter(a=>ut(a)&&Se(a)!=="body"),r=null;const s=dt(e).position==="fixed";let o=s?Nt(e):e;for(;ut(o)&&!ge(o);){const a=dt(o),l=Ki(o);!l&&a.position==="fixed"&&(r=null),(s?!l&&!r:!l&&a.position==="static"&&!!r&&Qd.has(r.position)||ni(o)&&!l&&oa(e,o))?n=n.filter(u=>u!==o):r=a,o=Nt(o)}return t.set(e,n),n}function Yd(e){let{element:t,boundary:i,rootBoundary:n,strategy:r}=e;const o=[...i==="clippingAncestors"?Ji(t)?[]:Gd(t,this._c):[].concat(i),n],a=o[0],l=o.reduce((c,u)=>{const d=ks(t,u,r);return c.top=Z(d.top,c.top),c.right=Bt(d.right,c.right),c.bottom=Bt(d.bottom,c.bottom),c.left=Z(d.left,c.left),c},ks(t,a,r));return{width:l.right-l.left,height:l.bottom-l.top,x:l.left,y:l.top}}function Zd(e){const{width:t,height:i}=na(e);return{width:t,height:i}}function th(e,t,i){const n=_t(t),r=Et(t),s=i==="fixed",o=ae(e,!0,s,t);let a={scrollLeft:0,scrollTop:0};const l=wt(0);function c(){l.x=Xi(r)}if(n||!n&&!s)if((Se(t)!=="body"||ni(r))&&(a=Qi(t)),n){const f=ae(t,!0,s,t);l.x=f.x+t.clientLeft,l.y=f.y+t.clientTop}else r&&c();s&&!n&&r&&c();const u=r&&!n&&!s?sa(r,a):wt(0),d=o.left+a.scrollLeft-l.x-u.x,p=o.top+a.scrollTop-l.y-u.y;return{x:d,y:p,width:o.width,height:o.height}}function bn(e){return dt(e).position==="static"}function As(e,t){if(!_t(e)||dt(e).position==="fixed")return null;if(t)return t(e);let i=e.offsetParent;return Et(e)===i&&(i=i.ownerDocument.body),i}function aa(e,t){const i=tt(e);if(Ji(e))return i;if(!_t(e)){let r=Nt(e);for(;r&&!ge(r);){if(ut(r)&&!bn(r))return r;r=Nt(r)}return i}let n=As(e,t);for(;n&&zd(n)&&bn(n);)n=As(n,t);return n&&ge(n)&&bn(n)&&!Ki(n)?i:n||Ud(e)||i}const eh=async function(e){const t=this.getOffsetParent||aa,i=this.getDimensions,n=await i(e.floating);return{reference:th(e.reference,await t(e.floating),e.strategy),floating:{x:0,y:0,width:n.width,height:n.height}}};function ih(e){return dt(e).direction==="rtl"}const Si={convertOffsetParentRelativeRectToViewportRelativeRect:Vd,getDocumentElement:Et,getClippingRect:Yd,getOffsetParent:aa,getElementRects:eh,getClientRects:Wd,getDimensions:Zd,getScale:pe,isElement:ut,isRTL:ih};function la(e,t){return e.x===t.x&&e.y===t.y&&e.width===t.width&&e.height===t.height}function nh(e,t){let i=null,n;const r=Et(e);function s(){var a;clearTimeout(n),(a=i)==null||a.disconnect(),i=null}function o(a,l){a===void 0&&(a=!1),l===void 0&&(l=1),s();const c=e.getBoundingClientRect(),{left:u,top:d,width:p,height:f}=c;if(a||t(),!p||!f)return;const b=ui(d),w=ui(r.clientWidth-(u+p)),g=ui(r.clientHeight-(d+f)),_=ui(u),E={rootMargin:-b+"px "+-w+"px "+-g+"px "+-_+"px",threshold:Z(0,Bt(1,l))||1};let k=!0;function S(O){const R=O[0].intersectionRatio;if(R!==l){if(!k)return o();R?o(!1,R):n=setTimeout(()=>{o(!1,1e-7)},1e3)}R===1&&!la(c,e.getBoundingClientRect())&&o(),k=!1}try{i=new IntersectionObserver(S,{...E,root:r.ownerDocument})}catch{i=new IntersectionObserver(S,E)}i.observe(e)}return o(!0),s}function rh(e,t,i,n){n===void 0&&(n={});const{ancestorScroll:r=!0,ancestorResize:s=!0,elementResize:o=typeof ResizeObserver=="function",layoutShift:a=typeof IntersectionObserver=="function",animationFrame:l=!1}=n,c=Er(e),u=r||s?[...c?Xe(c):[],...Xe(t)]:[];u.forEach(_=>{r&&_.addEventListener("scroll",i,{passive:!0}),s&&_.addEventListener("resize",i)});const d=c&&a?nh(c,i):null;let p=-1,f=null;o&&(f=new ResizeObserver(_=>{let[x]=_;x&&x.target===c&&f&&(f.unobserve(t),cancelAnimationFrame(p),p=requestAnimationFrame(()=>{var E;(E=f)==null||E.observe(t)})),i()}),c&&!l&&f.observe(c),f.observe(t));let b,w=l?ae(e):null;l&&g();function g(){const _=ae(e);w&&!la(w,_)&&i(),w=_,b=requestAnimationFrame(g)}return i(),()=>{var _;u.forEach(x=>{r&&x.removeEventListener("scroll",i),s&&x.removeEventListener("resize",i)}),d==null||d(),(_=f)==null||_.disconnect(),f=null,l&&cancelAnimationFrame(b)}}const sh=Fd,oh=Od,ah=Td,Ts=Ld,lh=Ad,ch=(e,t,i)=>{const n=new Map,r={platform:Si,...i},s={...r.platform,_c:n};return kd(e,t,{...r,platform:s})};function uh(e){return dh(e)}function vn(e){return e.assignedSlot?e.assignedSlot:e.parentNode instanceof ShadowRoot?e.parentNode.host:e.parentNode}function dh(e){for(let t=e;t;t=vn(t))if(t instanceof Element&&getComputedStyle(t).display==="none")return null;for(let t=vn(e);t;t=vn(t)){if(!(t instanceof Element))continue;const i=getComputedStyle(t);if(i.display!=="contents"&&(i.position!=="static"||Ki(i)||t.tagName==="BODY"))return t}return null}function hh(e){return e!==null&&typeof e=="object"&&"getBoundingClientRect"in e&&("contextElement"in e?e.contextElement instanceof Element:!0)}var L=class extends nt{constructor(){super(...arguments),this.localize=new _e(this),this.active=!1,this.placement="top",this.strategy="absolute",this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement="anchor",this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements="",this.flipFallbackStrategy="best-fit",this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){const e=this.anchorEl.getBoundingClientRect(),t=this.popup.getBoundingClientRect(),i=this.placement.includes("top")||this.placement.includes("bottom");let n=0,r=0,s=0,o=0,a=0,l=0,c=0,u=0;i?e.top<t.top?(n=e.left,r=e.bottom,s=e.right,o=e.bottom,a=t.left,l=t.top,c=t.right,u=t.top):(n=t.left,r=t.bottom,s=t.right,o=t.bottom,a=e.left,l=e.top,c=e.right,u=e.top):e.left<t.left?(n=e.right,r=e.top,s=t.left,o=t.top,a=e.right,l=e.bottom,c=t.left,u=t.bottom):(n=t.right,r=t.top,s=e.left,o=e.top,a=t.right,l=t.bottom,c=e.left,u=e.bottom),this.style.setProperty("--hover-bridge-top-left-x",`${n}px`),this.style.setProperty("--hover-bridge-top-left-y",`${r}px`),this.style.setProperty("--hover-bridge-top-right-x",`${s}px`),this.style.setProperty("--hover-bridge-top-right-y",`${o}px`),this.style.setProperty("--hover-bridge-bottom-left-x",`${a}px`),this.style.setProperty("--hover-bridge-bottom-left-y",`${l}px`),this.style.setProperty("--hover-bridge-bottom-right-x",`${c}px`),this.style.setProperty("--hover-bridge-bottom-right-y",`${u}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(e){super.updated(e),e.has("active")&&(this.active?this.start():this.stop()),e.has("anchor")&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor=="string"){const e=this.getRootNode();this.anchorEl=e.getElementById(this.anchor)}else this.anchor instanceof Element||hh(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector('[slot="anchor"]');this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.active&&this.start()}start(){!this.anchorEl||!this.active||(this.cleanup=rh(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(e=>{this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute("data-current-placement"),this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height"),requestAnimationFrame(()=>e())):e()})}reposition(){if(!this.active||!this.anchorEl)return;const e=[sh({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?e.push(Ts({apply:({rects:i})=>{const n=this.sync==="width"||this.sync==="both",r=this.sync==="height"||this.sync==="both";this.popup.style.width=n?`${i.reference.width}px`:"",this.popup.style.height=r?`${i.reference.height}px`:""}})):(this.popup.style.width="",this.popup.style.height=""),this.flip&&e.push(ah({boundary:this.flipBoundary,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy==="best-fit"?"bestFit":"initialPlacement",padding:this.flipPadding})),this.shift&&e.push(oh({boundary:this.shiftBoundary,padding:this.shiftPadding})),this.autoSize?e.push(Ts({boundary:this.autoSizeBoundary,padding:this.autoSizePadding,apply:({availableWidth:i,availableHeight:n})=>{this.autoSize==="vertical"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-height",`${n}px`):this.style.removeProperty("--auto-size-available-height"),this.autoSize==="horizontal"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-width",`${i}px`):this.style.removeProperty("--auto-size-available-width")}})):(this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height")),this.arrow&&e.push(lh({element:this.arrowEl,padding:this.arrowPadding}));const t=this.strategy==="absolute"?i=>Si.getOffsetParent(i,uh):Si.getOffsetParent;ch(this.anchorEl,this.popup,{placement:this.placement,middleware:e,strategy:this.strategy,platform:Vi(ce({},Si),{getOffsetParent:t})}).then(({x:i,y:n,middlewareData:r,placement:s})=>{const o=this.localize.dir()==="rtl",a={top:"bottom",right:"left",bottom:"top",left:"right"}[s.split("-")[0]];if(this.setAttribute("data-current-placement",s),Object.assign(this.popup.style,{left:`${i}px`,top:`${n}px`}),this.arrow){const l=r.arrow.x,c=r.arrow.y;let u="",d="",p="",f="";if(this.arrowPlacement==="start"){const b=typeof l=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";u=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"",d=o?b:"",f=o?"":b}else if(this.arrowPlacement==="end"){const b=typeof l=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";d=o?"":b,f=o?b:"",p=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:""}else this.arrowPlacement==="center"?(f=typeof l=="number"?"calc(50% - var(--arrow-size-diagonal))":"",u=typeof c=="number"?"calc(50% - var(--arrow-size-diagonal))":""):(f=typeof l=="number"?`${l}px`:"",u=typeof c=="number"?`${c}px`:"");Object.assign(this.arrowEl.style,{top:u,right:d,bottom:p,left:f,[a]:"calc(var(--arrow-size-diagonal) * -1)"})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.emit("sl-reposition")}render(){return I`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${kt({"popup-hover-bridge":!0,"popup-hover-bridge--visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        part="popup"
        class=${kt({popup:!0,"popup--active":this.active,"popup--fixed":this.strategy==="fixed","popup--has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?I`<div part="arrow" class="popup__arrow" role="presentation"></div>`:""}
      </div>
    `}};L.styles=[Tt,md];h([it(".popup")],L.prototype,"popup",2);h([it(".popup__arrow")],L.prototype,"arrowEl",2);h([v()],L.prototype,"anchor",2);h([v({type:Boolean,reflect:!0})],L.prototype,"active",2);h([v({reflect:!0})],L.prototype,"placement",2);h([v({reflect:!0})],L.prototype,"strategy",2);h([v({type:Number})],L.prototype,"distance",2);h([v({type:Number})],L.prototype,"skidding",2);h([v({type:Boolean})],L.prototype,"arrow",2);h([v({attribute:"arrow-placement"})],L.prototype,"arrowPlacement",2);h([v({attribute:"arrow-padding",type:Number})],L.prototype,"arrowPadding",2);h([v({type:Boolean})],L.prototype,"flip",2);h([v({attribute:"flip-fallback-placements",converter:{fromAttribute:e=>e.split(" ").map(t=>t.trim()).filter(t=>t!==""),toAttribute:e=>e.join(" ")}})],L.prototype,"flipFallbackPlacements",2);h([v({attribute:"flip-fallback-strategy"})],L.prototype,"flipFallbackStrategy",2);h([v({type:Object})],L.prototype,"flipBoundary",2);h([v({attribute:"flip-padding",type:Number})],L.prototype,"flipPadding",2);h([v({type:Boolean})],L.prototype,"shift",2);h([v({type:Object})],L.prototype,"shiftBoundary",2);h([v({attribute:"shift-padding",type:Number})],L.prototype,"shiftPadding",2);h([v({attribute:"auto-size"})],L.prototype,"autoSize",2);h([v()],L.prototype,"sync",2);h([v({type:Object})],L.prototype,"autoSizeBoundary",2);h([v({attribute:"auto-size-padding",type:Number})],L.prototype,"autoSizePadding",2);h([v({attribute:"hover-bridge",type:Boolean})],L.prototype,"hoverBridge",2);var ca=new Map,ph=new WeakMap;function fh(e){return e??{keyframes:[],options:{duration:0}}}function $s(e,t){return t.toLowerCase()==="rtl"?{keyframes:e.rtlKeyframes||e.keyframes,options:e.options}:e}function ua(e,t){ca.set(e,fh(t))}function Rs(e,t,i){const n=ph.get(e);if(n!=null&&n[t])return $s(n[t],i.dir);const r=ca.get(t);return r?$s(r,i.dir):{keyframes:[],options:{duration:0}}}function Fs(e,t){return new Promise(i=>{function n(r){r.target===e&&(e.removeEventListener(t,n),i())}e.addEventListener(t,n)})}function Os(e,t,i){return new Promise(n=>{if((i==null?void 0:i.duration)===1/0)throw new Error("Promise-based animations must be finite.");const r=e.animate(t,Vi(ce({},i),{duration:mh()?0:i.duration}));r.addEventListener("cancel",n,{once:!0}),r.addEventListener("finish",n,{once:!0})})}function mh(){return window.matchMedia("(prefers-reduced-motion: reduce)").matches}function Ls(e){return Promise.all(e.getAnimations().map(t=>new Promise(i=>{t.cancel(),requestAnimationFrame(i)})))}var q=class extends nt{constructor(){super(...arguments),this.localize=new _e(this),this.open=!1,this.placement="bottom-start",this.disabled=!1,this.stayOpenOnSelect=!1,this.distance=0,this.skidding=0,this.hoist=!1,this.sync=void 0,this.handleKeyDown=e=>{this.open&&e.key==="Escape"&&(e.stopPropagation(),this.hide(),this.focusOnTrigger())},this.handleDocumentKeyDown=e=>{var t;if(e.key==="Escape"&&this.open&&!this.closeWatcher){e.stopPropagation(),this.focusOnTrigger(),this.hide();return}if(e.key==="Tab"){if(this.open&&((t=document.activeElement)==null?void 0:t.tagName.toLowerCase())==="sl-menu-item"){e.preventDefault(),this.hide(),this.focusOnTrigger();return}const i=(n,r)=>{if(!n)return null;const s=n.closest(r);if(s)return s;const o=n.getRootNode();return o instanceof ShadowRoot?i(o.host,r):null};setTimeout(()=>{var n;const r=((n=this.containingElement)==null?void 0:n.getRootNode())instanceof ShadowRoot?ld():document.activeElement;(!this.containingElement||i(r,this.containingElement.tagName.toLowerCase())!==this.containingElement)&&this.hide()})}},this.handleDocumentMouseDown=e=>{const t=e.composedPath();this.containingElement&&!t.includes(this.containingElement)&&this.hide()},this.handlePanelSelect=e=>{const t=e.target;!this.stayOpenOnSelect&&t.tagName.toLowerCase()==="sl-menu"&&(this.hide(),this.focusOnTrigger())}}connectedCallback(){super.connectedCallback(),this.containingElement||(this.containingElement=this)}firstUpdated(){this.panel.hidden=!this.open,this.open&&(this.addOpenListeners(),this.popup.active=!0)}disconnectedCallback(){super.disconnectedCallback(),this.removeOpenListeners(),this.hide()}focusOnTrigger(){const e=this.trigger.assignedElements({flatten:!0})[0];typeof(e==null?void 0:e.focus)=="function"&&e.focus()}getMenu(){return this.panel.assignedElements({flatten:!0}).find(e=>e.tagName.toLowerCase()==="sl-menu")}handleTriggerClick(){this.open?this.hide():(this.show(),this.focusOnTrigger())}async handleTriggerKeyDown(e){if([" ","Enter"].includes(e.key)){e.preventDefault(),this.handleTriggerClick();return}const t=this.getMenu();if(t){const i=t.getAllItems(),n=i[0],r=i[i.length-1];["ArrowDown","ArrowUp","Home","End"].includes(e.key)&&(e.preventDefault(),this.open||(this.show(),await this.updateComplete),i.length>0&&this.updateComplete.then(()=>{(e.key==="ArrowDown"||e.key==="Home")&&(t.setCurrentItem(n),n.focus()),(e.key==="ArrowUp"||e.key==="End")&&(t.setCurrentItem(r),r.focus())}))}}handleTriggerKeyUp(e){e.key===" "&&e.preventDefault()}handleTriggerSlotChange(){this.updateAccessibleTrigger()}updateAccessibleTrigger(){const t=this.trigger.assignedElements({flatten:!0}).find(n=>hd(n).start);let i;if(t){switch(t.tagName.toLowerCase()){case"sl-button":case"sl-icon-button":i=t.button;break;default:i=t}i.setAttribute("aria-haspopup","true"),i.setAttribute("aria-expanded",this.open?"true":"false")}}async show(){if(!this.open)return this.open=!0,Fs(this,"sl-after-show")}async hide(){if(this.open)return this.open=!1,Fs(this,"sl-after-hide")}reposition(){this.popup.reposition()}addOpenListeners(){var e;this.panel.addEventListener("sl-select",this.handlePanelSelect),"CloseWatcher"in window?((e=this.closeWatcher)==null||e.destroy(),this.closeWatcher=new CloseWatcher,this.closeWatcher.onclose=()=>{this.hide(),this.focusOnTrigger()}):this.panel.addEventListener("keydown",this.handleKeyDown),document.addEventListener("keydown",this.handleDocumentKeyDown),document.addEventListener("mousedown",this.handleDocumentMouseDown)}removeOpenListeners(){var e;this.panel&&(this.panel.removeEventListener("sl-select",this.handlePanelSelect),this.panel.removeEventListener("keydown",this.handleKeyDown)),document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("mousedown",this.handleDocumentMouseDown),(e=this.closeWatcher)==null||e.destroy()}async handleOpenChange(){if(this.disabled){this.open=!1;return}if(this.updateAccessibleTrigger(),this.open){this.emit("sl-show"),this.addOpenListeners(),await Ls(this),this.panel.hidden=!1,this.popup.active=!0;const{keyframes:e,options:t}=Rs(this,"dropdown.show",{dir:this.localize.dir()});await Os(this.popup.popup,e,t),this.emit("sl-after-show")}else{this.emit("sl-hide"),this.removeOpenListeners(),await Ls(this);const{keyframes:e,options:t}=Rs(this,"dropdown.hide",{dir:this.localize.dir()});await Os(this.popup.popup,e,t),this.panel.hidden=!0,this.popup.active=!1,this.emit("sl-after-hide")}}render(){return I`
      <sl-popup
        part="base"
        exportparts="popup:base__popup"
        id="dropdown"
        placement=${this.placement}
        distance=${this.distance}
        skidding=${this.skidding}
        strategy=${this.hoist?"fixed":"absolute"}
        flip
        shift
        auto-size="vertical"
        auto-size-padding="10"
        sync=${F(this.sync?this.sync:void 0)}
        class=${kt({dropdown:!0,"dropdown--open":this.open})}
      >
        <slot
          name="trigger"
          slot="anchor"
          part="trigger"
          class="dropdown__trigger"
          @click=${this.handleTriggerClick}
          @keydown=${this.handleTriggerKeyDown}
          @keyup=${this.handleTriggerKeyUp}
          @slotchange=${this.handleTriggerSlotChange}
        ></slot>

        <div aria-hidden=${this.open?"false":"true"} aria-labelledby="dropdown">
          <slot part="panel" class="dropdown__panel"></slot>
        </div>
      </sl-popup>
    `}};q.styles=[Tt,ad];q.dependencies={"sl-popup":L};h([it(".dropdown")],q.prototype,"popup",2);h([it(".dropdown__trigger")],q.prototype,"trigger",2);h([it(".dropdown__panel")],q.prototype,"panel",2);h([v({type:Boolean,reflect:!0})],q.prototype,"open",2);h([v({reflect:!0})],q.prototype,"placement",2);h([v({type:Boolean,reflect:!0})],q.prototype,"disabled",2);h([v({attribute:"stay-open-on-select",type:Boolean,reflect:!0})],q.prototype,"stayOpenOnSelect",2);h([v({attribute:!1})],q.prototype,"containingElement",2);h([v({type:Number})],q.prototype,"distance",2);h([v({type:Number})],q.prototype,"skidding",2);h([v({type:Boolean})],q.prototype,"hoist",2);h([v({reflect:!0})],q.prototype,"sync",2);h([xt("open",{waitUntilFirstUpdate:!0})],q.prototype,"handleOpenChange",1);ua("dropdown.show",{keyframes:[{opacity:0,scale:.9},{opacity:1,scale:1}],options:{duration:100,easing:"ease"}});ua("dropdown.hide",{keyframes:[{opacity:1,scale:1},{opacity:0,scale:.9}],options:{duration:100,easing:"ease"}});var gh=ft`
  :host {
    --grid-width: 280px;
    --grid-height: 200px;
    --grid-handle-size: 16px;
    --slider-height: 15px;
    --slider-handle-size: 17px;
    --swatch-size: 25px;

    display: inline-block;
  }

  .color-picker {
    width: var(--grid-width);
    font-family: var(--sl-font-sans);
    font-size: var(--sl-font-size-medium);
    font-weight: var(--sl-font-weight-normal);
    color: var(--color);
    background-color: var(--sl-panel-background-color);
    border-radius: var(--sl-border-radius-medium);
    user-select: none;
    -webkit-user-select: none;
  }

  .color-picker--inline {
    border: solid var(--sl-panel-border-width) var(--sl-panel-border-color);
  }

  .color-picker--inline:focus-visible {
    outline: var(--sl-focus-ring);
    outline-offset: var(--sl-focus-ring-offset);
  }

  .color-picker__grid {
    position: relative;
    height: var(--grid-height);
    background-image: linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 1) 100%),
      linear-gradient(to right, #fff 0%, rgba(255, 255, 255, 0) 100%);
    border-top-left-radius: var(--sl-border-radius-medium);
    border-top-right-radius: var(--sl-border-radius-medium);
    cursor: crosshair;
    forced-color-adjust: none;
  }

  .color-picker__grid-handle {
    position: absolute;
    width: var(--grid-handle-size);
    height: var(--grid-handle-size);
    border-radius: 50%;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.25);
    border: solid 2px white;
    margin-top: calc(var(--grid-handle-size) / -2);
    margin-left: calc(var(--grid-handle-size) / -2);
    transition: var(--sl-transition-fast) scale;
  }

  .color-picker__grid-handle--dragging {
    cursor: none;
    scale: 1.5;
  }

  .color-picker__grid-handle:focus-visible {
    outline: var(--sl-focus-ring);
  }

  .color-picker__controls {
    padding: var(--sl-spacing-small);
    display: flex;
    align-items: center;
  }

  .color-picker__sliders {
    flex: 1 1 auto;
  }

  .color-picker__slider {
    position: relative;
    height: var(--slider-height);
    border-radius: var(--sl-border-radius-pill);
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.2);
    forced-color-adjust: none;
  }

  .color-picker__slider:not(:last-of-type) {
    margin-bottom: var(--sl-spacing-small);
  }

  .color-picker__slider-handle {
    position: absolute;
    top: calc(50% - var(--slider-handle-size) / 2);
    width: var(--slider-handle-size);
    height: var(--slider-handle-size);
    background-color: white;
    border-radius: 50%;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.25);
    margin-left: calc(var(--slider-handle-size) / -2);
  }

  .color-picker__slider-handle:focus-visible {
    outline: var(--sl-focus-ring);
  }

  .color-picker__hue {
    background-image: linear-gradient(
      to right,
      rgb(255, 0, 0) 0%,
      rgb(255, 255, 0) 17%,
      rgb(0, 255, 0) 33%,
      rgb(0, 255, 255) 50%,
      rgb(0, 0, 255) 67%,
      rgb(255, 0, 255) 83%,
      rgb(255, 0, 0) 100%
    );
  }

  .color-picker__alpha .color-picker__alpha-gradient {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: inherit;
  }

  .color-picker__preview {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
    width: 2.25rem;
    height: 2.25rem;
    border: none;
    border-radius: var(--sl-border-radius-circle);
    background: none;
    margin-left: var(--sl-spacing-small);
    cursor: copy;
    forced-color-adjust: none;
  }

  .color-picker__preview:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: inherit;
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.2);

    /* We use a custom property in lieu of currentColor because of https://bugs.webkit.org/show_bug.cgi?id=216780 */
    background-color: var(--preview-color);
  }

  .color-picker__preview:focus-visible {
    outline: var(--sl-focus-ring);
    outline-offset: var(--sl-focus-ring-offset);
  }

  .color-picker__preview-color {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: solid 1px rgba(0, 0, 0, 0.125);
  }

  .color-picker__preview-color--copied {
    animation: pulse 0.75s;
  }

  @keyframes pulse {
    0% {
      box-shadow: 0 0 0 0 var(--sl-color-primary-500);
    }
    70% {
      box-shadow: 0 0 0 0.5rem transparent;
    }
    100% {
      box-shadow: 0 0 0 0 transparent;
    }
  }

  .color-picker__user-input {
    display: flex;
    padding: 0 var(--sl-spacing-small) var(--sl-spacing-small) var(--sl-spacing-small);
  }

  .color-picker__user-input sl-input {
    min-width: 0; /* fix input width in Safari */
    flex: 1 1 auto;
  }

  .color-picker__user-input sl-button-group {
    margin-left: var(--sl-spacing-small);
  }

  .color-picker__user-input sl-button {
    min-width: 3.25rem;
    max-width: 3.25rem;
    font-size: 1rem;
  }

  .color-picker__swatches {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    grid-gap: 0.5rem;
    justify-items: center;
    border-top: solid 1px var(--sl-color-neutral-200);
    padding: var(--sl-spacing-small);
    forced-color-adjust: none;
  }

  .color-picker__swatch {
    position: relative;
    width: var(--swatch-size);
    height: var(--swatch-size);
    border-radius: var(--sl-border-radius-small);
  }

  .color-picker__swatch .color-picker__swatch-color {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: solid 1px rgba(0, 0, 0, 0.125);
    border-radius: inherit;
    cursor: pointer;
  }

  .color-picker__swatch:focus-visible {
    outline: var(--sl-focus-ring);
    outline-offset: var(--sl-focus-ring-offset);
  }

  .color-picker__transparent-bg {
    background-image: linear-gradient(45deg, var(--sl-color-neutral-300) 25%, transparent 25%),
      linear-gradient(45deg, transparent 75%, var(--sl-color-neutral-300) 75%),
      linear-gradient(45deg, transparent 75%, var(--sl-color-neutral-300) 75%),
      linear-gradient(45deg, var(--sl-color-neutral-300) 25%, transparent 25%);
    background-size: 10px 10px;
    background-position:
      0 0,
      0 0,
      -5px -5px,
      5px 5px;
  }

  .color-picker--disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .color-picker--disabled .color-picker__grid,
  .color-picker--disabled .color-picker__grid-handle,
  .color-picker--disabled .color-picker__slider,
  .color-picker--disabled .color-picker__slider-handle,
  .color-picker--disabled .color-picker__preview,
  .color-picker--disabled .color-picker__swatch,
  .color-picker--disabled .color-picker__swatch-color {
    pointer-events: none;
  }

  /*
   * Color dropdown
   */

  .color-dropdown::part(panel) {
    max-height: none;
    background-color: var(--sl-panel-background-color);
    border: solid var(--sl-panel-border-width) var(--sl-panel-border-color);
    border-radius: var(--sl-border-radius-medium);
    overflow: visible;
  }

  .color-dropdown__trigger {
    display: inline-block;
    position: relative;
    background-color: transparent;
    border: none;
    cursor: pointer;
    forced-color-adjust: none;
  }

  .color-dropdown__trigger.color-dropdown__trigger--small {
    width: var(--sl-input-height-small);
    height: var(--sl-input-height-small);
    border-radius: var(--sl-border-radius-circle);
  }

  .color-dropdown__trigger.color-dropdown__trigger--medium {
    width: var(--sl-input-height-medium);
    height: var(--sl-input-height-medium);
    border-radius: var(--sl-border-radius-circle);
  }

  .color-dropdown__trigger.color-dropdown__trigger--large {
    width: var(--sl-input-height-large);
    height: var(--sl-input-height-large);
    border-radius: var(--sl-border-radius-circle);
  }

  .color-dropdown__trigger:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: inherit;
    background-color: currentColor;
    box-shadow:
      inset 0 0 0 2px var(--sl-input-border-color),
      inset 0 0 0 4px var(--sl-color-neutral-0);
  }

  .color-dropdown__trigger--empty:before {
    background-color: transparent;
  }

  .color-dropdown__trigger:focus-visible {
    outline: none;
  }

  .color-dropdown__trigger:focus-visible:not(.color-dropdown__trigger--disabled) {
    outline: var(--sl-focus-ring);
    outline-offset: var(--sl-focus-ring-offset);
  }

  .color-dropdown__trigger.color-dropdown__trigger--disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
`;function rt(e,t,i){const n=r=>Object.is(r,-0)?0:r;return e<t?n(t):e>i?n(i):n(e)}var bh=ft`
  :host {
    display: inline-block;
  }

  .button-group {
    display: flex;
    flex-wrap: nowrap;
  }
`,ri=class extends nt{constructor(){super(...arguments),this.disableRole=!1,this.label=""}handleFocus(e){const t=ze(e.target);t==null||t.toggleAttribute("data-sl-button-group__button--focus",!0)}handleBlur(e){const t=ze(e.target);t==null||t.toggleAttribute("data-sl-button-group__button--focus",!1)}handleMouseOver(e){const t=ze(e.target);t==null||t.toggleAttribute("data-sl-button-group__button--hover",!0)}handleMouseOut(e){const t=ze(e.target);t==null||t.toggleAttribute("data-sl-button-group__button--hover",!1)}handleSlotChange(){const e=[...this.defaultSlot.assignedElements({flatten:!0})];e.forEach(t=>{const i=e.indexOf(t),n=ze(t);n&&(n.toggleAttribute("data-sl-button-group__button",!0),n.toggleAttribute("data-sl-button-group__button--first",i===0),n.toggleAttribute("data-sl-button-group__button--inner",i>0&&i<e.length-1),n.toggleAttribute("data-sl-button-group__button--last",i===e.length-1),n.toggleAttribute("data-sl-button-group__button--radio",n.tagName.toLowerCase()==="sl-radio-button"))})}render(){return I`
      <div
        part="base"
        class="button-group"
        role="${this.disableRole?"presentation":"group"}"
        aria-label=${this.label}
        @focusout=${this.handleBlur}
        @focusin=${this.handleFocus}
        @mouseover=${this.handleMouseOver}
        @mouseout=${this.handleMouseOut}
      >
        <slot @slotchange=${this.handleSlotChange}></slot>
      </div>
    `}};ri.styles=[Tt,bh];h([it("slot")],ri.prototype,"defaultSlot",2);h([et()],ri.prototype,"disableRole",2);h([v()],ri.prototype,"label",2);function ze(e){var t;const i="sl-button, sl-radio-button";return(t=e.closest(i))!=null?t:e.querySelector(i)}var vh=ft`
  :host {
    --track-width: 2px;
    --track-color: rgb(128 128 128 / 25%);
    --indicator-color: var(--sl-color-primary-600);
    --speed: 2s;

    display: inline-flex;
    width: 1em;
    height: 1em;
    flex: none;
  }

  .spinner {
    flex: 1 1 auto;
    height: 100%;
    width: 100%;
  }

  .spinner__track,
  .spinner__indicator {
    fill: none;
    stroke-width: var(--track-width);
    r: calc(0.5em - var(--track-width) / 2);
    cx: 0.5em;
    cy: 0.5em;
    transform-origin: 50% 50%;
  }

  .spinner__track {
    stroke: var(--track-color);
    transform-origin: 0% 0%;
  }

  .spinner__indicator {
    stroke: var(--indicator-color);
    stroke-linecap: round;
    stroke-dasharray: 150% 75%;
    animation: spin var(--speed) linear infinite;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
      stroke-dasharray: 0.05em, 3em;
    }

    50% {
      transform: rotate(450deg);
      stroke-dasharray: 1.375em, 1.375em;
    }

    100% {
      transform: rotate(1080deg);
      stroke-dasharray: 0.05em, 3em;
    }
  }
`,da=class extends nt{constructor(){super(...arguments),this.localize=new _e(this)}render(){return I`
      <svg part="base" class="spinner" role="progressbar" aria-label=${this.localize.term("loading")}>
        <circle class="spinner__track"></circle>
        <circle class="spinner__indicator"></circle>
      </svg>
    `}};da.styles=[Tt,vh];var yh=ft`
  :host {
    display: inline-block;
    position: relative;
    width: auto;
    cursor: pointer;
  }

  .button {
    display: inline-flex;
    align-items: stretch;
    justify-content: center;
    width: 100%;
    border-style: solid;
    border-width: var(--sl-input-border-width);
    font-family: var(--sl-input-font-family);
    font-weight: var(--sl-font-weight-semibold);
    text-decoration: none;
    user-select: none;
    -webkit-user-select: none;
    white-space: nowrap;
    vertical-align: middle;
    padding: 0;
    transition:
      var(--sl-transition-x-fast) background-color,
      var(--sl-transition-x-fast) color,
      var(--sl-transition-x-fast) border,
      var(--sl-transition-x-fast) box-shadow;
    cursor: inherit;
  }

  .button::-moz-focus-inner {
    border: 0;
  }

  .button:focus {
    outline: none;
  }

  .button:focus-visible {
    outline: var(--sl-focus-ring);
    outline-offset: var(--sl-focus-ring-offset);
  }

  .button--disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  /* When disabled, prevent mouse events from bubbling up from children */
  .button--disabled * {
    pointer-events: none;
  }

  .button__prefix,
  .button__suffix {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    pointer-events: none;
  }

  .button__label {
    display: inline-block;
  }

  .button__label::slotted(sl-icon) {
    vertical-align: -2px;
  }

  /*
   * Standard buttons
   */

  /* Default */
  .button--standard.button--default {
    background-color: var(--sl-color-neutral-0);
    border-color: var(--sl-input-border-color);
    color: var(--sl-color-neutral-700);
  }

  .button--standard.button--default:hover:not(.button--disabled) {
    background-color: var(--sl-color-primary-50);
    border-color: var(--sl-color-primary-300);
    color: var(--sl-color-primary-700);
  }

  .button--standard.button--default:active:not(.button--disabled) {
    background-color: var(--sl-color-primary-100);
    border-color: var(--sl-color-primary-400);
    color: var(--sl-color-primary-700);
  }

  /* Primary */
  .button--standard.button--primary {
    background-color: var(--sl-color-primary-600);
    border-color: var(--sl-color-primary-600);
    color: var(--sl-color-neutral-0);
  }

  .button--standard.button--primary:hover:not(.button--disabled) {
    background-color: var(--sl-color-primary-500);
    border-color: var(--sl-color-primary-500);
    color: var(--sl-color-neutral-0);
  }

  .button--standard.button--primary:active:not(.button--disabled) {
    background-color: var(--sl-color-primary-600);
    border-color: var(--sl-color-primary-600);
    color: var(--sl-color-neutral-0);
  }

  /* Success */
  .button--standard.button--success {
    background-color: var(--sl-color-success-600);
    border-color: var(--sl-color-success-600);
    color: var(--sl-color-neutral-0);
  }

  .button--standard.button--success:hover:not(.button--disabled) {
    background-color: var(--sl-color-success-500);
    border-color: var(--sl-color-success-500);
    color: var(--sl-color-neutral-0);
  }

  .button--standard.button--success:active:not(.button--disabled) {
    background-color: var(--sl-color-success-600);
    border-color: var(--sl-color-success-600);
    color: var(--sl-color-neutral-0);
  }

  /* Neutral */
  .button--standard.button--neutral {
    background-color: var(--sl-color-neutral-600);
    border-color: var(--sl-color-neutral-600);
    color: var(--sl-color-neutral-0);
  }

  .button--standard.button--neutral:hover:not(.button--disabled) {
    background-color: var(--sl-color-neutral-500);
    border-color: var(--sl-color-neutral-500);
    color: var(--sl-color-neutral-0);
  }

  .button--standard.button--neutral:active:not(.button--disabled) {
    background-color: var(--sl-color-neutral-600);
    border-color: var(--sl-color-neutral-600);
    color: var(--sl-color-neutral-0);
  }

  /* Warning */
  .button--standard.button--warning {
    background-color: var(--sl-color-warning-600);
    border-color: var(--sl-color-warning-600);
    color: var(--sl-color-neutral-0);
  }
  .button--standard.button--warning:hover:not(.button--disabled) {
    background-color: var(--sl-color-warning-500);
    border-color: var(--sl-color-warning-500);
    color: var(--sl-color-neutral-0);
  }

  .button--standard.button--warning:active:not(.button--disabled) {
    background-color: var(--sl-color-warning-600);
    border-color: var(--sl-color-warning-600);
    color: var(--sl-color-neutral-0);
  }

  /* Danger */
  .button--standard.button--danger {
    background-color: var(--sl-color-danger-600);
    border-color: var(--sl-color-danger-600);
    color: var(--sl-color-neutral-0);
  }

  .button--standard.button--danger:hover:not(.button--disabled) {
    background-color: var(--sl-color-danger-500);
    border-color: var(--sl-color-danger-500);
    color: var(--sl-color-neutral-0);
  }

  .button--standard.button--danger:active:not(.button--disabled) {
    background-color: var(--sl-color-danger-600);
    border-color: var(--sl-color-danger-600);
    color: var(--sl-color-neutral-0);
  }

  /*
   * Outline buttons
   */

  .button--outline {
    background: none;
    border: solid 1px;
  }

  /* Default */
  .button--outline.button--default {
    border-color: var(--sl-input-border-color);
    color: var(--sl-color-neutral-700);
  }

  .button--outline.button--default:hover:not(.button--disabled),
  .button--outline.button--default.button--checked:not(.button--disabled) {
    border-color: var(--sl-color-primary-600);
    background-color: var(--sl-color-primary-600);
    color: var(--sl-color-neutral-0);
  }

  .button--outline.button--default:active:not(.button--disabled) {
    border-color: var(--sl-color-primary-700);
    background-color: var(--sl-color-primary-700);
    color: var(--sl-color-neutral-0);
  }

  /* Primary */
  .button--outline.button--primary {
    border-color: var(--sl-color-primary-600);
    color: var(--sl-color-primary-600);
  }

  .button--outline.button--primary:hover:not(.button--disabled),
  .button--outline.button--primary.button--checked:not(.button--disabled) {
    background-color: var(--sl-color-primary-600);
    color: var(--sl-color-neutral-0);
  }

  .button--outline.button--primary:active:not(.button--disabled) {
    border-color: var(--sl-color-primary-700);
    background-color: var(--sl-color-primary-700);
    color: var(--sl-color-neutral-0);
  }

  /* Success */
  .button--outline.button--success {
    border-color: var(--sl-color-success-600);
    color: var(--sl-color-success-600);
  }

  .button--outline.button--success:hover:not(.button--disabled),
  .button--outline.button--success.button--checked:not(.button--disabled) {
    background-color: var(--sl-color-success-600);
    color: var(--sl-color-neutral-0);
  }

  .button--outline.button--success:active:not(.button--disabled) {
    border-color: var(--sl-color-success-700);
    background-color: var(--sl-color-success-700);
    color: var(--sl-color-neutral-0);
  }

  /* Neutral */
  .button--outline.button--neutral {
    border-color: var(--sl-color-neutral-600);
    color: var(--sl-color-neutral-600);
  }

  .button--outline.button--neutral:hover:not(.button--disabled),
  .button--outline.button--neutral.button--checked:not(.button--disabled) {
    background-color: var(--sl-color-neutral-600);
    color: var(--sl-color-neutral-0);
  }

  .button--outline.button--neutral:active:not(.button--disabled) {
    border-color: var(--sl-color-neutral-700);
    background-color: var(--sl-color-neutral-700);
    color: var(--sl-color-neutral-0);
  }

  /* Warning */
  .button--outline.button--warning {
    border-color: var(--sl-color-warning-600);
    color: var(--sl-color-warning-600);
  }

  .button--outline.button--warning:hover:not(.button--disabled),
  .button--outline.button--warning.button--checked:not(.button--disabled) {
    background-color: var(--sl-color-warning-600);
    color: var(--sl-color-neutral-0);
  }

  .button--outline.button--warning:active:not(.button--disabled) {
    border-color: var(--sl-color-warning-700);
    background-color: var(--sl-color-warning-700);
    color: var(--sl-color-neutral-0);
  }

  /* Danger */
  .button--outline.button--danger {
    border-color: var(--sl-color-danger-600);
    color: var(--sl-color-danger-600);
  }

  .button--outline.button--danger:hover:not(.button--disabled),
  .button--outline.button--danger.button--checked:not(.button--disabled) {
    background-color: var(--sl-color-danger-600);
    color: var(--sl-color-neutral-0);
  }

  .button--outline.button--danger:active:not(.button--disabled) {
    border-color: var(--sl-color-danger-700);
    background-color: var(--sl-color-danger-700);
    color: var(--sl-color-neutral-0);
  }

  @media (forced-colors: active) {
    .button.button--outline.button--checked:not(.button--disabled) {
      outline: solid 2px transparent;
    }
  }

  /*
   * Text buttons
   */

  .button--text {
    background-color: transparent;
    border-color: transparent;
    color: var(--sl-color-primary-600);
  }

  .button--text:hover:not(.button--disabled) {
    background-color: transparent;
    border-color: transparent;
    color: var(--sl-color-primary-500);
  }

  .button--text:focus-visible:not(.button--disabled) {
    background-color: transparent;
    border-color: transparent;
    color: var(--sl-color-primary-500);
  }

  .button--text:active:not(.button--disabled) {
    background-color: transparent;
    border-color: transparent;
    color: var(--sl-color-primary-700);
  }

  /*
   * Size modifiers
   */

  .button--small {
    height: auto;
    min-height: var(--sl-input-height-small);
    font-size: var(--sl-button-font-size-small);
    line-height: calc(var(--sl-input-height-small) - var(--sl-input-border-width) * 2);
    border-radius: var(--sl-input-border-radius-small);
  }

  .button--medium {
    height: auto;
    min-height: var(--sl-input-height-medium);
    font-size: var(--sl-button-font-size-medium);
    line-height: calc(var(--sl-input-height-medium) - var(--sl-input-border-width) * 2);
    border-radius: var(--sl-input-border-radius-medium);
  }

  .button--large {
    height: auto;
    min-height: var(--sl-input-height-large);
    font-size: var(--sl-button-font-size-large);
    line-height: calc(var(--sl-input-height-large) - var(--sl-input-border-width) * 2);
    border-radius: var(--sl-input-border-radius-large);
  }

  /*
   * Pill modifier
   */

  .button--pill.button--small {
    border-radius: var(--sl-input-height-small);
  }

  .button--pill.button--medium {
    border-radius: var(--sl-input-height-medium);
  }

  .button--pill.button--large {
    border-radius: var(--sl-input-height-large);
  }

  /*
   * Circle modifier
   */

  .button--circle {
    padding-left: 0;
    padding-right: 0;
  }

  .button--circle.button--small {
    width: var(--sl-input-height-small);
    border-radius: 50%;
  }

  .button--circle.button--medium {
    width: var(--sl-input-height-medium);
    border-radius: 50%;
  }

  .button--circle.button--large {
    width: var(--sl-input-height-large);
    border-radius: 50%;
  }

  .button--circle .button__prefix,
  .button--circle .button__suffix,
  .button--circle .button__caret {
    display: none;
  }

  /*
   * Caret modifier
   */

  .button--caret .button__suffix {
    display: none;
  }

  .button--caret .button__caret {
    height: auto;
  }

  /*
   * Loading modifier
   */

  .button--loading {
    position: relative;
    cursor: wait;
  }

  .button--loading .button__prefix,
  .button--loading .button__label,
  .button--loading .button__suffix,
  .button--loading .button__caret {
    visibility: hidden;
  }

  .button--loading sl-spinner {
    --indicator-color: currentColor;
    position: absolute;
    font-size: 1em;
    height: 1em;
    width: 1em;
    top: calc(50% - 0.5em);
    left: calc(50% - 0.5em);
  }

  /*
   * Badges
   */

  .button ::slotted(sl-badge) {
    position: absolute;
    top: 0;
    right: 0;
    translate: 50% -50%;
    pointer-events: none;
  }

  .button--rtl ::slotted(sl-badge) {
    right: auto;
    left: 0;
    translate: -50% -50%;
  }

  /*
   * Button spacing
   */

  .button--has-label.button--small .button__label {
    padding: 0 var(--sl-spacing-small);
  }

  .button--has-label.button--medium .button__label {
    padding: 0 var(--sl-spacing-medium);
  }

  .button--has-label.button--large .button__label {
    padding: 0 var(--sl-spacing-large);
  }

  .button--has-prefix.button--small {
    padding-inline-start: var(--sl-spacing-x-small);
  }

  .button--has-prefix.button--small .button__label {
    padding-inline-start: var(--sl-spacing-x-small);
  }

  .button--has-prefix.button--medium {
    padding-inline-start: var(--sl-spacing-small);
  }

  .button--has-prefix.button--medium .button__label {
    padding-inline-start: var(--sl-spacing-small);
  }

  .button--has-prefix.button--large {
    padding-inline-start: var(--sl-spacing-small);
  }

  .button--has-prefix.button--large .button__label {
    padding-inline-start: var(--sl-spacing-small);
  }

  .button--has-suffix.button--small,
  .button--caret.button--small {
    padding-inline-end: var(--sl-spacing-x-small);
  }

  .button--has-suffix.button--small .button__label,
  .button--caret.button--small .button__label {
    padding-inline-end: var(--sl-spacing-x-small);
  }

  .button--has-suffix.button--medium,
  .button--caret.button--medium {
    padding-inline-end: var(--sl-spacing-small);
  }

  .button--has-suffix.button--medium .button__label,
  .button--caret.button--medium .button__label {
    padding-inline-end: var(--sl-spacing-small);
  }

  .button--has-suffix.button--large,
  .button--caret.button--large {
    padding-inline-end: var(--sl-spacing-small);
  }

  .button--has-suffix.button--large .button__label,
  .button--caret.button--large .button__label {
    padding-inline-end: var(--sl-spacing-small);
  }

  /*
   * Button groups support a variety of button types (e.g. buttons with tooltips, buttons as dropdown triggers, etc.).
   * This means buttons aren't always direct descendants of the button group, thus we can't target them with the
   * ::slotted selector. To work around this, the button group component does some magic to add these special classes to
   * buttons and we style them here instead.
   */

  :host([data-sl-button-group__button--first]:not([data-sl-button-group__button--last])) .button {
    border-start-end-radius: 0;
    border-end-end-radius: 0;
  }

  :host([data-sl-button-group__button--inner]) .button {
    border-radius: 0;
  }

  :host([data-sl-button-group__button--last]:not([data-sl-button-group__button--first])) .button {
    border-start-start-radius: 0;
    border-end-start-radius: 0;
  }

  /* All except the first */
  :host([data-sl-button-group__button]:not([data-sl-button-group__button--first])) {
    margin-inline-start: calc(-1 * var(--sl-input-border-width));
  }

  /* Add a visual separator between solid buttons */
  :host(
      [data-sl-button-group__button]:not(
          [data-sl-button-group__button--first],
          [data-sl-button-group__button--radio],
          [variant='default']
        ):not(:hover)
    )
    .button:after {
    content: '';
    position: absolute;
    top: 0;
    inset-inline-start: 0;
    bottom: 0;
    border-left: solid 1px rgb(128 128 128 / 33%);
    mix-blend-mode: multiply;
  }

  /* Bump hovered, focused, and checked buttons up so their focus ring isn't clipped */
  :host([data-sl-button-group__button--hover]) {
    z-index: 1;
  }

  /* Focus and checked are always on top */
  :host([data-sl-button-group__button--focus]),
  :host([data-sl-button-group__button][checked]) {
    z-index: 2;
  }
`;/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const ha=Symbol.for(""),wh=e=>{if((e==null?void 0:e.r)===ha)return e==null?void 0:e._$litStatic$},Ms=(e,...t)=>({_$litStatic$:t.reduce(((i,n,r)=>i+(s=>{if(s._$litStatic$!==void 0)return s._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${s}. Use 'unsafeStatic' to pass non-literal values, but
            take care to ensure page security.`)})(n)+e[r+1]),e[0]),r:ha}),Ps=new Map,_h=e=>(t,...i)=>{const n=i.length;let r,s;const o=[],a=[];let l,c=0,u=!1;for(;c<n;){for(l=t[c];c<n&&(s=i[c],(r=wh(s))!==void 0);)l+=r+t[++c],u=!0;c!==n&&a.push(s),o.push(l),c++}if(c===n&&o.push(t[n]),u){const d=o.join("$$lit$$");(t=Ps.get(d))===void 0&&(o.raw=o,Ps.set(d,t=o)),i=a}return e(t,...i)},yn=_h(I);var $=class extends nt{constructor(){super(...arguments),this.formControlController=new pr(this,{assumeInteractionOn:["click"]}),this.hasSlotController=new Wo(this,"[default]","prefix","suffix"),this.localize=new _e(this),this.hasFocus=!1,this.invalid=!1,this.title="",this.variant="default",this.size="medium",this.caret=!1,this.disabled=!1,this.loading=!1,this.outline=!1,this.pill=!1,this.circle=!1,this.type="button",this.name="",this.value="",this.href="",this.rel="noreferrer noopener"}get validity(){return this.isButton()?this.button.validity:fr}get validationMessage(){return this.isButton()?this.button.validationMessage:""}firstUpdated(){this.isButton()&&this.formControlController.updateValidity()}handleBlur(){this.hasFocus=!1,this.emit("sl-blur")}handleFocus(){this.hasFocus=!0,this.emit("sl-focus")}handleClick(){this.type==="submit"&&this.formControlController.submit(this),this.type==="reset"&&this.formControlController.reset(this)}handleInvalid(e){this.formControlController.setValidity(!1),this.formControlController.emitInvalidEvent(e)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.isButton()&&this.formControlController.setValidity(this.disabled)}click(){this.button.click()}focus(e){this.button.focus(e)}blur(){this.button.blur()}checkValidity(){return this.isButton()?this.button.checkValidity():!0}getForm(){return this.formControlController.getForm()}reportValidity(){return this.isButton()?this.button.reportValidity():!0}setCustomValidity(e){this.isButton()&&(this.button.setCustomValidity(e),this.formControlController.updateValidity())}render(){const e=this.isLink(),t=e?Ms`a`:Ms`button`;return yn`
      <${t}
        part="base"
        class=${kt({button:!0,"button--default":this.variant==="default","button--primary":this.variant==="primary","button--success":this.variant==="success","button--neutral":this.variant==="neutral","button--warning":this.variant==="warning","button--danger":this.variant==="danger","button--text":this.variant==="text","button--small":this.size==="small","button--medium":this.size==="medium","button--large":this.size==="large","button--caret":this.caret,"button--circle":this.circle,"button--disabled":this.disabled,"button--focused":this.hasFocus,"button--loading":this.loading,"button--standard":!this.outline,"button--outline":this.outline,"button--pill":this.pill,"button--rtl":this.localize.dir()==="rtl","button--has-label":this.hasSlotController.test("[default]"),"button--has-prefix":this.hasSlotController.test("prefix"),"button--has-suffix":this.hasSlotController.test("suffix")})}
        ?disabled=${F(e?void 0:this.disabled)}
        type=${F(e?void 0:this.type)}
        title=${this.title}
        name=${F(e?void 0:this.name)}
        value=${F(e?void 0:this.value)}
        href=${F(e&&!this.disabled?this.href:void 0)}
        target=${F(e?this.target:void 0)}
        download=${F(e?this.download:void 0)}
        rel=${F(e?this.rel:void 0)}
        role=${F(e?void 0:"button")}
        aria-disabled=${this.disabled?"true":"false"}
        tabindex=${this.disabled?"-1":"0"}
        @blur=${this.handleBlur}
        @focus=${this.handleFocus}
        @invalid=${this.isButton()?this.handleInvalid:null}
        @click=${this.handleClick}
      >
        <slot name="prefix" part="prefix" class="button__prefix"></slot>
        <slot part="label" class="button__label"></slot>
        <slot name="suffix" part="suffix" class="button__suffix"></slot>
        ${this.caret?yn` <sl-icon part="caret" class="button__caret" library="system" name="caret"></sl-icon> `:""}
        ${this.loading?yn`<sl-spinner part="spinner"></sl-spinner>`:""}
      </${t}>
    `}};$.styles=[Tt,yh];$.dependencies={"sl-icon":mt,"sl-spinner":da};h([it(".button")],$.prototype,"button",2);h([et()],$.prototype,"hasFocus",2);h([et()],$.prototype,"invalid",2);h([v()],$.prototype,"title",2);h([v({reflect:!0})],$.prototype,"variant",2);h([v({reflect:!0})],$.prototype,"size",2);h([v({type:Boolean,reflect:!0})],$.prototype,"caret",2);h([v({type:Boolean,reflect:!0})],$.prototype,"disabled",2);h([v({type:Boolean,reflect:!0})],$.prototype,"loading",2);h([v({type:Boolean,reflect:!0})],$.prototype,"outline",2);h([v({type:Boolean,reflect:!0})],$.prototype,"pill",2);h([v({type:Boolean,reflect:!0})],$.prototype,"circle",2);h([v()],$.prototype,"type",2);h([v()],$.prototype,"name",2);h([v()],$.prototype,"value",2);h([v()],$.prototype,"href",2);h([v()],$.prototype,"target",2);h([v()],$.prototype,"rel",2);h([v()],$.prototype,"download",2);h([v()],$.prototype,"form",2);h([v({attribute:"formaction"})],$.prototype,"formAction",2);h([v({attribute:"formenctype"})],$.prototype,"formEnctype",2);h([v({attribute:"formmethod"})],$.prototype,"formMethod",2);h([v({attribute:"formnovalidate",type:Boolean})],$.prototype,"formNoValidate",2);h([v({attribute:"formtarget"})],$.prototype,"formTarget",2);h([xt("disabled",{waitUntilFirstUpdate:!0})],$.prototype,"handleDisabledChange",1);/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const pa="important",xh=" !"+pa,Ot=br(class extends vr{constructor(e){var t;if(super(e),e.type!==Mt.ATTRIBUTE||e.name!=="style"||((t=e.strings)==null?void 0:t.length)>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(e){return Object.keys(e).reduce(((t,i)=>{const n=e[i];return n==null?t:t+`${i=i.includes("-")?i:i.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,"-$&").toLowerCase()}:${n};`}),"")}update(e,[t]){const{style:i}=e.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(t)),this.render(t);for(const n of this.ft)t[n]==null&&(this.ft.delete(n),n.includes("-")?i.removeProperty(n):i[n]=null);for(const n in t){const r=t[n];if(r!=null){this.ft.add(n);const s=typeof r=="string"&&r.endsWith(xh);n.includes("-")||s?i.setProperty(n,s?r.slice(0,-11):r,s?pa:""):i[n]=r}}return ot}});function U(e,t){Eh(e)&&(e="100%");const i=Sh(e);return e=t===360?e:Math.min(t,Math.max(0,parseFloat(e))),i&&(e=parseInt(String(e*t),10)/100),Math.abs(e-t)<1e-6?1:(t===360?e=(e<0?e%t+t:e%t)/parseFloat(String(t)):e=e%t/parseFloat(String(t)),e)}function di(e){return Math.min(1,Math.max(0,e))}function Eh(e){return typeof e=="string"&&e.indexOf(".")!==-1&&parseFloat(e)===1}function Sh(e){return typeof e=="string"&&e.indexOf("%")!==-1}function fa(e){return e=parseFloat(e),(isNaN(e)||e<0||e>1)&&(e=1),e}function hi(e){return Number(e)<=1?`${Number(e)*100}%`:e}function Gt(e){return e.length===1?"0"+e:String(e)}function Ch(e,t,i){return{r:U(e,255)*255,g:U(t,255)*255,b:U(i,255)*255}}function zs(e,t,i){e=U(e,255),t=U(t,255),i=U(i,255);const n=Math.max(e,t,i),r=Math.min(e,t,i);let s=0,o=0;const a=(n+r)/2;if(n===r)o=0,s=0;else{const l=n-r;switch(o=a>.5?l/(2-n-r):l/(n+r),n){case e:s=(t-i)/l+(t<i?6:0);break;case t:s=(i-e)/l+2;break;case i:s=(e-t)/l+4;break}s/=6}return{h:s,s:o,l:a}}function wn(e,t,i){return i<0&&(i+=1),i>1&&(i-=1),i<1/6?e+(t-e)*(6*i):i<1/2?t:i<2/3?e+(t-e)*(2/3-i)*6:e}function kh(e,t,i){let n,r,s;if(e=U(e,360),t=U(t,100),i=U(i,100),t===0)r=i,s=i,n=i;else{const o=i<.5?i*(1+t):i+t-i*t,a=2*i-o;n=wn(a,o,e+1/3),r=wn(a,o,e),s=wn(a,o,e-1/3)}return{r:n*255,g:r*255,b:s*255}}function Is(e,t,i){e=U(e,255),t=U(t,255),i=U(i,255);const n=Math.max(e,t,i),r=Math.min(e,t,i);let s=0;const o=n,a=n-r,l=n===0?0:a/n;if(n===r)s=0;else{switch(n){case e:s=(t-i)/a+(t<i?6:0);break;case t:s=(i-e)/a+2;break;case i:s=(e-t)/a+4;break}s/=6}return{h:s,s:l,v:o}}function Ah(e,t,i){e=U(e,360)*6,t=U(t,100),i=U(i,100);const n=Math.floor(e),r=e-n,s=i*(1-t),o=i*(1-r*t),a=i*(1-(1-r)*t),l=n%6,c=[i,o,s,s,a,i][l],u=[a,i,i,o,s,s][l],d=[s,s,a,i,i,o][l];return{r:c*255,g:u*255,b:d*255}}function Bs(e,t,i,n){const r=[Gt(Math.round(e).toString(16)),Gt(Math.round(t).toString(16)),Gt(Math.round(i).toString(16))];return n&&r[0].startsWith(r[0].charAt(1))&&r[1].startsWith(r[1].charAt(1))&&r[2].startsWith(r[2].charAt(1))?r[0].charAt(0)+r[1].charAt(0)+r[2].charAt(0):r.join("")}function Th(e,t,i,n,r){const s=[Gt(Math.round(e).toString(16)),Gt(Math.round(t).toString(16)),Gt(Math.round(i).toString(16)),Gt(Rh(n))];return r&&s[0].startsWith(s[0].charAt(1))&&s[1].startsWith(s[1].charAt(1))&&s[2].startsWith(s[2].charAt(1))&&s[3].startsWith(s[3].charAt(1))?s[0].charAt(0)+s[1].charAt(0)+s[2].charAt(0)+s[3].charAt(0):s.join("")}function $h(e,t,i,n){const r=e/100,s=t/100,o=i/100,a=n/100,l=255*(1-r)*(1-a),c=255*(1-s)*(1-a),u=255*(1-o)*(1-a);return{r:l,g:c,b:u}}function Ds(e,t,i){let n=1-e/255,r=1-t/255,s=1-i/255,o=Math.min(n,r,s);return o===1?(n=0,r=0,s=0):(n=(n-o)/(1-o)*100,r=(r-o)/(1-o)*100,s=(s-o)/(1-o)*100),o*=100,{c:Math.round(n),m:Math.round(r),y:Math.round(s),k:Math.round(o)}}function Rh(e){return Math.round(parseFloat(e)*255).toString(16)}function Ns(e){return Y(e)/255}function Y(e){return parseInt(e,16)}function Fh(e){return{r:e>>16,g:(e&65280)>>8,b:e&255}}const Un={aliceblue:"#f0f8ff",antiquewhite:"#faebd7",aqua:"#00ffff",aquamarine:"#7fffd4",azure:"#f0ffff",beige:"#f5f5dc",bisque:"#ffe4c4",black:"#000000",blanchedalmond:"#ffebcd",blue:"#0000ff",blueviolet:"#8a2be2",brown:"#a52a2a",burlywood:"#deb887",cadetblue:"#5f9ea0",chartreuse:"#7fff00",chocolate:"#d2691e",coral:"#ff7f50",cornflowerblue:"#6495ed",cornsilk:"#fff8dc",crimson:"#dc143c",cyan:"#00ffff",darkblue:"#00008b",darkcyan:"#008b8b",darkgoldenrod:"#b8860b",darkgray:"#a9a9a9",darkgreen:"#006400",darkgrey:"#a9a9a9",darkkhaki:"#bdb76b",darkmagenta:"#8b008b",darkolivegreen:"#556b2f",darkorange:"#ff8c00",darkorchid:"#9932cc",darkred:"#8b0000",darksalmon:"#e9967a",darkseagreen:"#8fbc8f",darkslateblue:"#483d8b",darkslategray:"#2f4f4f",darkslategrey:"#2f4f4f",darkturquoise:"#00ced1",darkviolet:"#9400d3",deeppink:"#ff1493",deepskyblue:"#00bfff",dimgray:"#696969",dimgrey:"#696969",dodgerblue:"#1e90ff",firebrick:"#b22222",floralwhite:"#fffaf0",forestgreen:"#228b22",fuchsia:"#ff00ff",gainsboro:"#dcdcdc",ghostwhite:"#f8f8ff",goldenrod:"#daa520",gold:"#ffd700",gray:"#808080",green:"#008000",greenyellow:"#adff2f",grey:"#808080",honeydew:"#f0fff0",hotpink:"#ff69b4",indianred:"#cd5c5c",indigo:"#4b0082",ivory:"#fffff0",khaki:"#f0e68c",lavenderblush:"#fff0f5",lavender:"#e6e6fa",lawngreen:"#7cfc00",lemonchiffon:"#fffacd",lightblue:"#add8e6",lightcoral:"#f08080",lightcyan:"#e0ffff",lightgoldenrodyellow:"#fafad2",lightgray:"#d3d3d3",lightgreen:"#90ee90",lightgrey:"#d3d3d3",lightpink:"#ffb6c1",lightsalmon:"#ffa07a",lightseagreen:"#20b2aa",lightskyblue:"#87cefa",lightslategray:"#778899",lightslategrey:"#778899",lightsteelblue:"#b0c4de",lightyellow:"#ffffe0",lime:"#00ff00",limegreen:"#32cd32",linen:"#faf0e6",magenta:"#ff00ff",maroon:"#800000",mediumaquamarine:"#66cdaa",mediumblue:"#0000cd",mediumorchid:"#ba55d3",mediumpurple:"#9370db",mediumseagreen:"#3cb371",mediumslateblue:"#7b68ee",mediumspringgreen:"#00fa9a",mediumturquoise:"#48d1cc",mediumvioletred:"#c71585",midnightblue:"#191970",mintcream:"#f5fffa",mistyrose:"#ffe4e1",moccasin:"#ffe4b5",navajowhite:"#ffdead",navy:"#000080",oldlace:"#fdf5e6",olive:"#808000",olivedrab:"#6b8e23",orange:"#ffa500",orangered:"#ff4500",orchid:"#da70d6",palegoldenrod:"#eee8aa",palegreen:"#98fb98",paleturquoise:"#afeeee",palevioletred:"#db7093",papayawhip:"#ffefd5",peachpuff:"#ffdab9",peru:"#cd853f",pink:"#ffc0cb",plum:"#dda0dd",powderblue:"#b0e0e6",purple:"#800080",rebeccapurple:"#663399",red:"#ff0000",rosybrown:"#bc8f8f",royalblue:"#4169e1",saddlebrown:"#8b4513",salmon:"#fa8072",sandybrown:"#f4a460",seagreen:"#2e8b57",seashell:"#fff5ee",sienna:"#a0522d",silver:"#c0c0c0",skyblue:"#87ceeb",slateblue:"#6a5acd",slategray:"#708090",slategrey:"#708090",snow:"#fffafa",springgreen:"#00ff7f",steelblue:"#4682b4",tan:"#d2b48c",teal:"#008080",thistle:"#d8bfd8",tomato:"#ff6347",turquoise:"#40e0d0",violet:"#ee82ee",wheat:"#f5deb3",white:"#ffffff",whitesmoke:"#f5f5f5",yellow:"#ffff00",yellowgreen:"#9acd32"};function Oh(e){let t={r:0,g:0,b:0},i=1,n=null,r=null,s=null,o=!1,a=!1;return typeof e=="string"&&(e=Ph(e)),typeof e=="object"&&(G(e.r)&&G(e.g)&&G(e.b)?(t=Ch(e.r,e.g,e.b),o=!0,a=String(e.r).substr(-1)==="%"?"prgb":"rgb"):G(e.h)&&G(e.s)&&G(e.v)?(n=hi(e.s),r=hi(e.v),t=Ah(e.h,n,r),o=!0,a="hsv"):G(e.h)&&G(e.s)&&G(e.l)?(n=hi(e.s),s=hi(e.l),t=kh(e.h,n,s),o=!0,a="hsl"):G(e.c)&&G(e.m)&&G(e.y)&&G(e.k)&&(t=$h(e.c,e.m,e.y,e.k),o=!0,a="cmyk"),Object.prototype.hasOwnProperty.call(e,"a")&&(i=e.a)),i=fa(i),{ok:o,format:e.format||a,r:Math.min(255,Math.max(t.r,0)),g:Math.min(255,Math.max(t.g,0)),b:Math.min(255,Math.max(t.b,0)),a:i}}const Lh="[-\\+]?\\d+%?",Mh="[-\\+]?\\d*\\.\\d+%?",zt="(?:"+Mh+")|(?:"+Lh+")",_n="[\\s|\\(]+("+zt+")[,|\\s]+("+zt+")[,|\\s]+("+zt+")\\s*\\)?",pi="[\\s|\\(]+("+zt+")[,|\\s]+("+zt+")[,|\\s]+("+zt+")[,|\\s]+("+zt+")\\s*\\)?",st={CSS_UNIT:new RegExp(zt),rgb:new RegExp("rgb"+_n),rgba:new RegExp("rgba"+pi),hsl:new RegExp("hsl"+_n),hsla:new RegExp("hsla"+pi),hsv:new RegExp("hsv"+_n),hsva:new RegExp("hsva"+pi),cmyk:new RegExp("cmyk"+pi),hex3:/^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/,hex6:/^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/,hex4:/^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/,hex8:/^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/};function Ph(e){if(e=e.trim().toLowerCase(),e.length===0)return!1;let t=!1;if(Un[e])e=Un[e],t=!0;else if(e==="transparent")return{r:0,g:0,b:0,a:0,format:"name"};let i=st.rgb.exec(e);return i?{r:i[1],g:i[2],b:i[3]}:(i=st.rgba.exec(e),i?{r:i[1],g:i[2],b:i[3],a:i[4]}:(i=st.hsl.exec(e),i?{h:i[1],s:i[2],l:i[3]}:(i=st.hsla.exec(e),i?{h:i[1],s:i[2],l:i[3],a:i[4]}:(i=st.hsv.exec(e),i?{h:i[1],s:i[2],v:i[3]}:(i=st.hsva.exec(e),i?{h:i[1],s:i[2],v:i[3],a:i[4]}:(i=st.cmyk.exec(e),i?{c:i[1],m:i[2],y:i[3],k:i[4]}:(i=st.hex8.exec(e),i?{r:Y(i[1]),g:Y(i[2]),b:Y(i[3]),a:Ns(i[4]),format:t?"name":"hex8"}:(i=st.hex6.exec(e),i?{r:Y(i[1]),g:Y(i[2]),b:Y(i[3]),format:t?"name":"hex"}:(i=st.hex4.exec(e),i?{r:Y(i[1]+i[1]),g:Y(i[2]+i[2]),b:Y(i[3]+i[3]),a:Ns(i[4]+i[4]),format:t?"name":"hex8"}:(i=st.hex3.exec(e),i?{r:Y(i[1]+i[1]),g:Y(i[2]+i[2]),b:Y(i[3]+i[3]),format:t?"name":"hex"}:!1))))))))))}function G(e){return typeof e=="number"?!Number.isNaN(e):st.CSS_UNIT.test(e)}class z{constructor(t="",i={}){if(t instanceof z)return t;typeof t=="number"&&(t=Fh(t)),this.originalInput=t;const n=Oh(t);this.originalInput=t,this.r=n.r,this.g=n.g,this.b=n.b,this.a=n.a,this.roundA=Math.round(100*this.a)/100,this.format=i.format??n.format,this.gradientType=i.gradientType,this.r<1&&(this.r=Math.round(this.r)),this.g<1&&(this.g=Math.round(this.g)),this.b<1&&(this.b=Math.round(this.b)),this.isValid=n.ok}isDark(){return this.getBrightness()<128}isLight(){return!this.isDark()}getBrightness(){const t=this.toRgb();return(t.r*299+t.g*587+t.b*114)/1e3}getLuminance(){const t=this.toRgb();let i,n,r;const s=t.r/255,o=t.g/255,a=t.b/255;return s<=.03928?i=s/12.92:i=Math.pow((s+.055)/1.055,2.4),o<=.03928?n=o/12.92:n=Math.pow((o+.055)/1.055,2.4),a<=.03928?r=a/12.92:r=Math.pow((a+.055)/1.055,2.4),.2126*i+.7152*n+.0722*r}getAlpha(){return this.a}setAlpha(t){return this.a=fa(t),this.roundA=Math.round(100*this.a)/100,this}isMonochrome(){const{s:t}=this.toHsl();return t===0}toHsv(){const t=Is(this.r,this.g,this.b);return{h:t.h*360,s:t.s,v:t.v,a:this.a}}toHsvString(){const t=Is(this.r,this.g,this.b),i=Math.round(t.h*360),n=Math.round(t.s*100),r=Math.round(t.v*100);return this.a===1?`hsv(${i}, ${n}%, ${r}%)`:`hsva(${i}, ${n}%, ${r}%, ${this.roundA})`}toHsl(){const t=zs(this.r,this.g,this.b);return{h:t.h*360,s:t.s,l:t.l,a:this.a}}toHslString(){const t=zs(this.r,this.g,this.b),i=Math.round(t.h*360),n=Math.round(t.s*100),r=Math.round(t.l*100);return this.a===1?`hsl(${i}, ${n}%, ${r}%)`:`hsla(${i}, ${n}%, ${r}%, ${this.roundA})`}toHex(t=!1){return Bs(this.r,this.g,this.b,t)}toHexString(t=!1){return"#"+this.toHex(t)}toHex8(t=!1){return Th(this.r,this.g,this.b,this.a,t)}toHex8String(t=!1){return"#"+this.toHex8(t)}toHexShortString(t=!1){return this.a===1?this.toHexString(t):this.toHex8String(t)}toRgb(){return{r:Math.round(this.r),g:Math.round(this.g),b:Math.round(this.b),a:this.a}}toRgbString(){const t=Math.round(this.r),i=Math.round(this.g),n=Math.round(this.b);return this.a===1?`rgb(${t}, ${i}, ${n})`:`rgba(${t}, ${i}, ${n}, ${this.roundA})`}toPercentageRgb(){const t=i=>`${Math.round(U(i,255)*100)}%`;return{r:t(this.r),g:t(this.g),b:t(this.b),a:this.a}}toPercentageRgbString(){const t=i=>Math.round(U(i,255)*100);return this.a===1?`rgb(${t(this.r)}%, ${t(this.g)}%, ${t(this.b)}%)`:`rgba(${t(this.r)}%, ${t(this.g)}%, ${t(this.b)}%, ${this.roundA})`}toCmyk(){return{...Ds(this.r,this.g,this.b)}}toCmykString(){const{c:t,m:i,y:n,k:r}=Ds(this.r,this.g,this.b);return`cmyk(${t}, ${i}, ${n}, ${r})`}toName(){if(this.a===0)return"transparent";if(this.a<1)return!1;const t="#"+Bs(this.r,this.g,this.b,!1);for(const[i,n]of Object.entries(Un))if(t===n)return i;return!1}toString(t){const i=!!t;t=t??this.format;let n=!1;const r=this.a<1&&this.a>=0;return!i&&r&&(t.startsWith("hex")||t==="name")?t==="name"&&this.a===0?this.toName():this.toRgbString():(t==="rgb"&&(n=this.toRgbString()),t==="prgb"&&(n=this.toPercentageRgbString()),(t==="hex"||t==="hex6")&&(n=this.toHexString()),t==="hex3"&&(n=this.toHexString(!0)),t==="hex4"&&(n=this.toHex8String(!0)),t==="hex8"&&(n=this.toHex8String()),t==="name"&&(n=this.toName()),t==="hsl"&&(n=this.toHslString()),t==="hsv"&&(n=this.toHsvString()),t==="cmyk"&&(n=this.toCmykString()),n||this.toHexString())}toNumber(){return(Math.round(this.r)<<16)+(Math.round(this.g)<<8)+Math.round(this.b)}clone(){return new z(this.toString())}lighten(t=10){const i=this.toHsl();return i.l+=t/100,i.l=di(i.l),new z(i)}brighten(t=10){const i=this.toRgb();return i.r=Math.max(0,Math.min(255,i.r-Math.round(255*-(t/100)))),i.g=Math.max(0,Math.min(255,i.g-Math.round(255*-(t/100)))),i.b=Math.max(0,Math.min(255,i.b-Math.round(255*-(t/100)))),new z(i)}darken(t=10){const i=this.toHsl();return i.l-=t/100,i.l=di(i.l),new z(i)}tint(t=10){return this.mix("white",t)}shade(t=10){return this.mix("black",t)}desaturate(t=10){const i=this.toHsl();return i.s-=t/100,i.s=di(i.s),new z(i)}saturate(t=10){const i=this.toHsl();return i.s+=t/100,i.s=di(i.s),new z(i)}greyscale(){return this.desaturate(100)}spin(t){const i=this.toHsl(),n=(i.h+t)%360;return i.h=n<0?360+n:n,new z(i)}mix(t,i=50){const n=this.toRgb(),r=new z(t).toRgb(),s=i/100,o={r:(r.r-n.r)*s+n.r,g:(r.g-n.g)*s+n.g,b:(r.b-n.b)*s+n.b,a:(r.a-n.a)*s+n.a};return new z(o)}analogous(t=6,i=30){const n=this.toHsl(),r=360/i,s=[this];for(n.h=(n.h-(r*t>>1)+720)%360;--t;)n.h=(n.h+r)%360,s.push(new z(n));return s}complement(){const t=this.toHsl();return t.h=(t.h+180)%360,new z(t)}monochromatic(t=6){const i=this.toHsv(),{h:n}=i,{s:r}=i;let{v:s}=i;const o=[],a=1/t;for(;t--;)o.push(new z({h:n,s:r,v:s})),s=(s+a)%1;return o}splitcomplement(){const t=this.toHsl(),{h:i}=t;return[this,new z({h:(i+72)%360,s:t.s,l:t.l}),new z({h:(i+216)%360,s:t.s,l:t.l})]}onBackground(t){const i=this.toRgb(),n=new z(t).toRgb(),r=i.a+n.a*(1-i.a);return new z({r:(i.r*i.a+n.r*n.a*(1-i.a))/r,g:(i.g*i.a+n.g*n.a*(1-i.a))/r,b:(i.b*i.a+n.b*n.a*(1-i.a))/r,a:r})}triad(){return this.polyad(3)}tetrad(){return this.polyad(4)}polyad(t){const i=this.toHsl(),{h:n}=i,r=[this],s=360/t;for(let o=1;o<t;o++)r.push(new z({h:(n+o*s)%360,s:i.s,l:i.l}));return r}equals(t){const i=new z(t);return this.format==="cmyk"||i.format==="cmyk"?this.toCmykString()===i.toCmykString():this.toRgbString()===i.toRgbString()}}var Us="EyeDropper"in window,T=class extends nt{constructor(){super(),this.formControlController=new pr(this),this.isSafeValue=!1,this.localize=new _e(this),this.hasFocus=!1,this.isDraggingGridHandle=!1,this.isEmpty=!1,this.inputValue="",this.hue=0,this.saturation=100,this.brightness=100,this.alpha=100,this.value="",this.defaultValue="",this.label="",this.format="hex",this.inline=!1,this.size="medium",this.noFormatToggle=!1,this.name="",this.disabled=!1,this.hoist=!1,this.opacity=!1,this.uppercase=!1,this.swatches="",this.form="",this.required=!1,this.handleFocusIn=()=>{this.hasFocus=!0,this.emit("sl-focus")},this.handleFocusOut=()=>{this.hasFocus=!1,this.emit("sl-blur")},this.addEventListener("focusin",this.handleFocusIn),this.addEventListener("focusout",this.handleFocusOut)}get validity(){return this.input.validity}get validationMessage(){return this.input.validationMessage}firstUpdated(){this.input.updateComplete.then(()=>{this.formControlController.updateValidity()})}handleCopy(){this.input.select(),document.execCommand("copy"),this.previewButton.focus(),this.previewButton.classList.add("color-picker__preview-color--copied"),this.previewButton.addEventListener("animationend",()=>{this.previewButton.classList.remove("color-picker__preview-color--copied")})}handleFormatToggle(){const e=["hex","rgb","hsl","hsv"],t=(e.indexOf(this.format)+1)%e.length;this.format=e[t],this.setColor(this.value),this.emit("sl-change"),this.emit("sl-input")}handleAlphaDrag(e){const t=this.shadowRoot.querySelector(".color-picker__slider.color-picker__alpha"),i=t.querySelector(".color-picker__slider-handle"),{width:n}=t.getBoundingClientRect();let r=this.value,s=this.value;i.focus(),e.preventDefault(),gn(t,{onMove:o=>{this.alpha=rt(o/n*100,0,100),this.syncValues(),this.value!==s&&(s=this.value,this.emit("sl-input"))},onStop:()=>{this.value!==r&&(r=this.value,this.emit("sl-change"))},initialEvent:e})}handleHueDrag(e){const t=this.shadowRoot.querySelector(".color-picker__slider.color-picker__hue"),i=t.querySelector(".color-picker__slider-handle"),{width:n}=t.getBoundingClientRect();let r=this.value,s=this.value;i.focus(),e.preventDefault(),gn(t,{onMove:o=>{this.hue=rt(o/n*360,0,360),this.syncValues(),this.value!==s&&(s=this.value,this.emit("sl-input"))},onStop:()=>{this.value!==r&&(r=this.value,this.emit("sl-change"))},initialEvent:e})}handleGridDrag(e){const t=this.shadowRoot.querySelector(".color-picker__grid"),i=t.querySelector(".color-picker__grid-handle"),{width:n,height:r}=t.getBoundingClientRect();let s=this.value,o=this.value;i.focus(),e.preventDefault(),this.isDraggingGridHandle=!0,gn(t,{onMove:(a,l)=>{this.saturation=rt(a/n*100,0,100),this.brightness=rt(100-l/r*100,0,100),this.syncValues(),this.value!==o&&(o=this.value,this.emit("sl-input"))},onStop:()=>{this.isDraggingGridHandle=!1,this.value!==s&&(s=this.value,this.emit("sl-change"))},initialEvent:e})}handleAlphaKeyDown(e){const t=e.shiftKey?10:1,i=this.value;e.key==="ArrowLeft"&&(e.preventDefault(),this.alpha=rt(this.alpha-t,0,100),this.syncValues()),e.key==="ArrowRight"&&(e.preventDefault(),this.alpha=rt(this.alpha+t,0,100),this.syncValues()),e.key==="Home"&&(e.preventDefault(),this.alpha=0,this.syncValues()),e.key==="End"&&(e.preventDefault(),this.alpha=100,this.syncValues()),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleHueKeyDown(e){const t=e.shiftKey?10:1,i=this.value;e.key==="ArrowLeft"&&(e.preventDefault(),this.hue=rt(this.hue-t,0,360),this.syncValues()),e.key==="ArrowRight"&&(e.preventDefault(),this.hue=rt(this.hue+t,0,360),this.syncValues()),e.key==="Home"&&(e.preventDefault(),this.hue=0,this.syncValues()),e.key==="End"&&(e.preventDefault(),this.hue=360,this.syncValues()),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleGridKeyDown(e){const t=e.shiftKey?10:1,i=this.value;e.key==="ArrowLeft"&&(e.preventDefault(),this.saturation=rt(this.saturation-t,0,100),this.syncValues()),e.key==="ArrowRight"&&(e.preventDefault(),this.saturation=rt(this.saturation+t,0,100),this.syncValues()),e.key==="ArrowUp"&&(e.preventDefault(),this.brightness=rt(this.brightness+t,0,100),this.syncValues()),e.key==="ArrowDown"&&(e.preventDefault(),this.brightness=rt(this.brightness-t,0,100),this.syncValues()),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleInputChange(e){const t=e.target,i=this.value;e.stopPropagation(),this.input.value?(this.setColor(t.value),t.value=this.value):this.value="",this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleInputInput(e){this.formControlController.updateValidity(),e.stopPropagation()}handleInputKeyDown(e){if(e.key==="Enter"){const t=this.value;this.input.value?(this.setColor(this.input.value),this.input.value=this.value,this.value!==t&&(this.emit("sl-change"),this.emit("sl-input")),setTimeout(()=>this.input.select())):this.hue=0}}handleInputInvalid(e){this.formControlController.setValidity(!1),this.formControlController.emitInvalidEvent(e)}handleTouchMove(e){e.preventDefault()}parseColor(e){const t=new z(e);if(!t.isValid)return null;const i=t.toHsl(),n={h:i.h,s:i.s*100,l:i.l*100,a:i.a},r=t.toRgb(),s=t.toHexString(),o=t.toHex8String(),a=t.toHsv(),l={h:a.h,s:a.s*100,v:a.v*100,a:a.a};return{hsl:{h:n.h,s:n.s,l:n.l,string:this.setLetterCase(`hsl(${Math.round(n.h)}, ${Math.round(n.s)}%, ${Math.round(n.l)}%)`)},hsla:{h:n.h,s:n.s,l:n.l,a:n.a,string:this.setLetterCase(`hsla(${Math.round(n.h)}, ${Math.round(n.s)}%, ${Math.round(n.l)}%, ${n.a.toFixed(2).toString()})`)},hsv:{h:l.h,s:l.s,v:l.v,string:this.setLetterCase(`hsv(${Math.round(l.h)}, ${Math.round(l.s)}%, ${Math.round(l.v)}%)`)},hsva:{h:l.h,s:l.s,v:l.v,a:l.a,string:this.setLetterCase(`hsva(${Math.round(l.h)}, ${Math.round(l.s)}%, ${Math.round(l.v)}%, ${l.a.toFixed(2).toString()})`)},rgb:{r:r.r,g:r.g,b:r.b,string:this.setLetterCase(`rgb(${Math.round(r.r)}, ${Math.round(r.g)}, ${Math.round(r.b)})`)},rgba:{r:r.r,g:r.g,b:r.b,a:r.a,string:this.setLetterCase(`rgba(${Math.round(r.r)}, ${Math.round(r.g)}, ${Math.round(r.b)}, ${r.a.toFixed(2).toString()})`)},hex:this.setLetterCase(s),hexa:this.setLetterCase(o)}}setColor(e){const t=this.parseColor(e);return t===null?!1:(this.hue=t.hsva.h,this.saturation=t.hsva.s,this.brightness=t.hsva.v,this.alpha=this.opacity?t.hsva.a*100:100,this.syncValues(),!0)}setLetterCase(e){return typeof e!="string"?"":this.uppercase?e.toUpperCase():e.toLowerCase()}async syncValues(){const e=this.parseColor(`hsva(${this.hue}, ${this.saturation}%, ${this.brightness}%, ${this.alpha/100})`);e!==null&&(this.format==="hsl"?this.inputValue=this.opacity?e.hsla.string:e.hsl.string:this.format==="rgb"?this.inputValue=this.opacity?e.rgba.string:e.rgb.string:this.format==="hsv"?this.inputValue=this.opacity?e.hsva.string:e.hsv.string:this.inputValue=this.opacity?e.hexa:e.hex,this.isSafeValue=!0,this.value=this.inputValue,await this.updateComplete,this.isSafeValue=!1)}handleAfterHide(){this.previewButton.classList.remove("color-picker__preview-color--copied")}handleEyeDropper(){if(!Us)return;new EyeDropper().open().then(t=>{const i=this.value;this.setColor(t.sRGBHex),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}).catch(()=>{})}selectSwatch(e){const t=this.value;this.disabled||(this.setColor(e),this.value!==t&&(this.emit("sl-change"),this.emit("sl-input")))}getHexString(e,t,i,n=100){const r=new z(`hsva(${e}, ${t}%, ${i}%, ${n/100})`);return r.isValid?r.toHex8String():""}stopNestedEventPropagation(e){e.stopImmediatePropagation()}handleFormatChange(){this.syncValues()}handleOpacityChange(){this.alpha=100}handleValueChange(e,t){if(this.isEmpty=!t,t||(this.hue=0,this.saturation=0,this.brightness=100,this.alpha=100),!this.isSafeValue){const i=this.parseColor(t);i!==null?(this.inputValue=this.value,this.hue=i.hsva.h,this.saturation=i.hsva.s,this.brightness=i.hsva.v,this.alpha=i.hsva.a*100,this.syncValues()):this.inputValue=e??""}}focus(e){this.inline?this.base.focus(e):this.trigger.focus(e)}blur(){var e;const t=this.inline?this.base:this.trigger;this.hasFocus&&(t.focus({preventScroll:!0}),t.blur()),(e=this.dropdown)!=null&&e.open&&this.dropdown.hide()}getFormattedValue(e="hex"){const t=this.parseColor(`hsva(${this.hue}, ${this.saturation}%, ${this.brightness}%, ${this.alpha/100})`);if(t===null)return"";switch(e){case"hex":return t.hex;case"hexa":return t.hexa;case"rgb":return t.rgb.string;case"rgba":return t.rgba.string;case"hsl":return t.hsl.string;case"hsla":return t.hsla.string;case"hsv":return t.hsv.string;case"hsva":return t.hsva.string;default:return""}}checkValidity(){return this.input.checkValidity()}getForm(){return this.formControlController.getForm()}reportValidity(){return!this.inline&&!this.validity.valid?(this.dropdown.show(),this.addEventListener("sl-after-show",()=>this.input.reportValidity(),{once:!0}),this.disabled||this.formControlController.emitInvalidEvent(),!1):this.input.reportValidity()}setCustomValidity(e){this.input.setCustomValidity(e),this.formControlController.updateValidity()}render(){const e=this.saturation,t=100-this.brightness,i=Array.isArray(this.swatches)?this.swatches:this.swatches.split(";").filter(r=>r.trim()!==""),n=I`
      <div
        part="base"
        class=${kt({"color-picker":!0,"color-picker--inline":this.inline,"color-picker--disabled":this.disabled,"color-picker--focused":this.hasFocus})}
        aria-disabled=${this.disabled?"true":"false"}
        aria-labelledby="label"
        tabindex=${this.inline?"0":"-1"}
      >
        ${this.inline?I`
              <sl-visually-hidden id="label">
                <slot name="label">${this.label}</slot>
              </sl-visually-hidden>
            `:null}

        <div
          part="grid"
          class="color-picker__grid"
          style=${Ot({backgroundColor:this.getHexString(this.hue,100,100)})}
          @pointerdown=${this.handleGridDrag}
          @touchmove=${this.handleTouchMove}
        >
          <span
            part="grid-handle"
            class=${kt({"color-picker__grid-handle":!0,"color-picker__grid-handle--dragging":this.isDraggingGridHandle})}
            style=${Ot({top:`${t}%`,left:`${e}%`,backgroundColor:this.getHexString(this.hue,this.saturation,this.brightness,this.alpha)})}
            role="application"
            aria-label="HSV"
            tabindex=${F(this.disabled?void 0:"0")}
            @keydown=${this.handleGridKeyDown}
          ></span>
        </div>

        <div class="color-picker__controls">
          <div class="color-picker__sliders">
            <div
              part="slider hue-slider"
              class="color-picker__hue color-picker__slider"
              @pointerdown=${this.handleHueDrag}
              @touchmove=${this.handleTouchMove}
            >
              <span
                part="slider-handle hue-slider-handle"
                class="color-picker__slider-handle"
                style=${Ot({left:`${this.hue===0?0:100/(360/this.hue)}%`})}
                role="slider"
                aria-label="hue"
                aria-orientation="horizontal"
                aria-valuemin="0"
                aria-valuemax="360"
                aria-valuenow=${`${Math.round(this.hue)}`}
                tabindex=${F(this.disabled?void 0:"0")}
                @keydown=${this.handleHueKeyDown}
              ></span>
            </div>

            ${this.opacity?I`
                  <div
                    part="slider opacity-slider"
                    class="color-picker__alpha color-picker__slider color-picker__transparent-bg"
                    @pointerdown="${this.handleAlphaDrag}"
                    @touchmove=${this.handleTouchMove}
                  >
                    <div
                      class="color-picker__alpha-gradient"
                      style=${Ot({backgroundImage:`linear-gradient(
                          to right,
                          ${this.getHexString(this.hue,this.saturation,this.brightness,0)} 0%,
                          ${this.getHexString(this.hue,this.saturation,this.brightness,100)} 100%
                        )`})}
                    ></div>
                    <span
                      part="slider-handle opacity-slider-handle"
                      class="color-picker__slider-handle"
                      style=${Ot({left:`${this.alpha}%`})}
                      role="slider"
                      aria-label="alpha"
                      aria-orientation="horizontal"
                      aria-valuemin="0"
                      aria-valuemax="100"
                      aria-valuenow=${Math.round(this.alpha)}
                      tabindex=${F(this.disabled?void 0:"0")}
                      @keydown=${this.handleAlphaKeyDown}
                    ></span>
                  </div>
                `:""}
          </div>

          <button
            type="button"
            part="preview"
            class="color-picker__preview color-picker__transparent-bg"
            aria-label=${this.localize.term("copy")}
            style=${Ot({"--preview-color":this.getHexString(this.hue,this.saturation,this.brightness,this.alpha)})}
            @click=${this.handleCopy}
          ></button>
        </div>

        <div class="color-picker__user-input" aria-live="polite">
          <sl-input
            part="input"
            type="text"
            name=${this.name}
            autocomplete="off"
            autocorrect="off"
            autocapitalize="off"
            spellcheck="false"
            value=${this.isEmpty?"":this.inputValue}
            ?required=${this.required}
            ?disabled=${this.disabled}
            aria-label=${this.localize.term("currentValue")}
            @keydown=${this.handleInputKeyDown}
            @sl-change=${this.handleInputChange}
            @sl-input=${this.handleInputInput}
            @sl-invalid=${this.handleInputInvalid}
            @sl-blur=${this.stopNestedEventPropagation}
            @sl-focus=${this.stopNestedEventPropagation}
          ></sl-input>

          <sl-button-group>
            ${this.noFormatToggle?"":I`
                  <sl-button
                    part="format-button"
                    aria-label=${this.localize.term("toggleColorFormat")}
                    exportparts="
                      base:format-button__base,
                      prefix:format-button__prefix,
                      label:format-button__label,
                      suffix:format-button__suffix,
                      caret:format-button__caret
                    "
                    @click=${this.handleFormatToggle}
                    @sl-blur=${this.stopNestedEventPropagation}
                    @sl-focus=${this.stopNestedEventPropagation}
                  >
                    ${this.setLetterCase(this.format)}
                  </sl-button>
                `}
            ${Us?I`
                  <sl-button
                    part="eye-dropper-button"
                    exportparts="
                      base:eye-dropper-button__base,
                      prefix:eye-dropper-button__prefix,
                      label:eye-dropper-button__label,
                      suffix:eye-dropper-button__suffix,
                      caret:eye-dropper-button__caret
                    "
                    @click=${this.handleEyeDropper}
                    @sl-blur=${this.stopNestedEventPropagation}
                    @sl-focus=${this.stopNestedEventPropagation}
                  >
                    <sl-icon
                      library="system"
                      name="eyedropper"
                      label=${this.localize.term("selectAColorFromTheScreen")}
                    ></sl-icon>
                  </sl-button>
                `:""}
          </sl-button-group>
        </div>

        ${i.length>0?I`
              <div part="swatches" class="color-picker__swatches">
                ${i.map(r=>{const s=this.parseColor(r);return s?I`
                    <div
                      part="swatch"
                      class="color-picker__swatch color-picker__transparent-bg"
                      tabindex=${F(this.disabled?void 0:"0")}
                      role="button"
                      aria-label=${r}
                      @click=${()=>this.selectSwatch(r)}
                      @keydown=${o=>!this.disabled&&o.key==="Enter"&&this.setColor(s.hexa)}
                    >
                      <div
                        class="color-picker__swatch-color"
                        style=${Ot({backgroundColor:s.hexa})}
                      ></div>
                    </div>
                  `:(console.error(`Unable to parse swatch color: "${r}"`,this),"")})}
              </div>
            `:""}
      </div>
    `;return this.inline?n:I`
      <sl-dropdown
        class="color-dropdown"
        aria-disabled=${this.disabled?"true":"false"}
        .containingElement=${this}
        ?disabled=${this.disabled}
        ?hoist=${this.hoist}
        @sl-after-hide=${this.handleAfterHide}
      >
        <button
          part="trigger"
          slot="trigger"
          class=${kt({"color-dropdown__trigger":!0,"color-dropdown__trigger--disabled":this.disabled,"color-dropdown__trigger--small":this.size==="small","color-dropdown__trigger--medium":this.size==="medium","color-dropdown__trigger--large":this.size==="large","color-dropdown__trigger--empty":this.isEmpty,"color-dropdown__trigger--focused":this.hasFocus,"color-picker__transparent-bg":!0})}
          style=${Ot({color:this.getHexString(this.hue,this.saturation,this.brightness,this.alpha)})}
          type="button"
        >
          <sl-visually-hidden>
            <slot name="label">${this.label}</slot>
          </sl-visually-hidden>
        </button>
        ${n}
      </sl-dropdown>
    `}};T.styles=[Tt,gh];T.dependencies={"sl-button-group":ri,"sl-button":$,"sl-dropdown":q,"sl-icon":mt,"sl-input":A,"sl-visually-hidden":qo};h([it('[part~="base"]')],T.prototype,"base",2);h([it('[part~="input"]')],T.prototype,"input",2);h([it(".color-dropdown")],T.prototype,"dropdown",2);h([it('[part~="preview"]')],T.prototype,"previewButton",2);h([it('[part~="trigger"]')],T.prototype,"trigger",2);h([et()],T.prototype,"hasFocus",2);h([et()],T.prototype,"isDraggingGridHandle",2);h([et()],T.prototype,"isEmpty",2);h([et()],T.prototype,"inputValue",2);h([et()],T.prototype,"hue",2);h([et()],T.prototype,"saturation",2);h([et()],T.prototype,"brightness",2);h([et()],T.prototype,"alpha",2);h([v()],T.prototype,"value",2);h([Vo()],T.prototype,"defaultValue",2);h([v()],T.prototype,"label",2);h([v()],T.prototype,"format",2);h([v({type:Boolean,reflect:!0})],T.prototype,"inline",2);h([v({reflect:!0})],T.prototype,"size",2);h([v({attribute:"no-format-toggle",type:Boolean})],T.prototype,"noFormatToggle",2);h([v()],T.prototype,"name",2);h([v({type:Boolean,reflect:!0})],T.prototype,"disabled",2);h([v({type:Boolean})],T.prototype,"hoist",2);h([v({type:Boolean})],T.prototype,"opacity",2);h([v({type:Boolean})],T.prototype,"uppercase",2);h([v()],T.prototype,"swatches",2);h([v({reflect:!0})],T.prototype,"form",2);h([v({type:Boolean,reflect:!0})],T.prototype,"required",2);h([Uu({passive:!1})],T.prototype,"handleTouchMove",1);h([xt("format",{waitUntilFirstUpdate:!0})],T.prototype,"handleFormatChange",1);h([xt("opacity",{waitUntilFirstUpdate:!0})],T.prototype,"handleOpacityChange",1);h([xt("value")],T.prototype,"handleValueChange",1);T.define("sl-color-picker");var Hn=!1,jn=!1,ee=[],qn=-1;function zh(e){Ih(e)}function Ih(e){ee.includes(e)||ee.push(e),Dh()}function Bh(e){let t=ee.indexOf(e);t!==-1&&t>qn&&ee.splice(t,1)}function Dh(){!jn&&!Hn&&(Hn=!0,queueMicrotask(Nh))}function Nh(){Hn=!1,jn=!0;for(let e=0;e<ee.length;e++)ee[e](),qn=e;ee.length=0,qn=-1,jn=!1}var Ce,ue,ke,ma,Vn=!0;function Uh(e){Vn=!1,e(),Vn=!0}function Hh(e){Ce=e.reactive,ke=e.release,ue=t=>e.effect(t,{scheduler:i=>{Vn?zh(i):i()}}),ma=e.raw}function Hs(e){ue=e}function jh(e){let t=()=>{};return[n=>{let r=ue(n);return e._x_effects||(e._x_effects=new Set,e._x_runEffects=()=>{e._x_effects.forEach(s=>s())}),e._x_effects.add(r),t=()=>{r!==void 0&&(e._x_effects.delete(r),ke(r))},r},()=>{t()}]}function ga(e,t){let i=!0,n,r=ue(()=>{let s=e();JSON.stringify(s),i?n=s:queueMicrotask(()=>{t(s,n),n=s}),i=!1});return()=>ke(r)}var ba=[],va=[],ya=[];function qh(e){ya.push(e)}function Sr(e,t){typeof t=="function"?(e._x_cleanups||(e._x_cleanups=[]),e._x_cleanups.push(t)):(t=e,va.push(t))}function wa(e){ba.push(e)}function _a(e,t,i){e._x_attributeCleanups||(e._x_attributeCleanups={}),e._x_attributeCleanups[t]||(e._x_attributeCleanups[t]=[]),e._x_attributeCleanups[t].push(i)}function xa(e,t){e._x_attributeCleanups&&Object.entries(e._x_attributeCleanups).forEach(([i,n])=>{(t===void 0||t.includes(i))&&(n.forEach(r=>r()),delete e._x_attributeCleanups[i])})}function Vh(e){var t,i;for((t=e._x_effects)==null||t.forEach(Bh);(i=e._x_cleanups)!=null&&i.length;)e._x_cleanups.pop()()}var Cr=new MutationObserver($r),kr=!1;function Ar(){Cr.observe(document,{subtree:!0,childList:!0,attributes:!0,attributeOldValue:!0}),kr=!0}function Ea(){Wh(),Cr.disconnect(),kr=!1}var Ie=[];function Wh(){let e=Cr.takeRecords();Ie.push(()=>e.length>0&&$r(e));let t=Ie.length;queueMicrotask(()=>{if(Ie.length===t)for(;Ie.length>0;)Ie.shift()()})}function P(e){if(!kr)return e();Ea();let t=e();return Ar(),t}var Tr=!1,Mi=[];function Jh(){Tr=!0}function Kh(){Tr=!1,$r(Mi),Mi=[]}function $r(e){if(Tr){Mi=Mi.concat(e);return}let t=[],i=new Set,n=new Map,r=new Map;for(let s=0;s<e.length;s++)if(!e[s].target._x_ignoreMutationObserver&&(e[s].type==="childList"&&(e[s].removedNodes.forEach(o=>{o.nodeType===1&&o._x_marker&&i.add(o)}),e[s].addedNodes.forEach(o=>{if(o.nodeType===1){if(i.has(o)){i.delete(o);return}o._x_marker||t.push(o)}})),e[s].type==="attributes")){let o=e[s].target,a=e[s].attributeName,l=e[s].oldValue,c=()=>{n.has(o)||n.set(o,[]),n.get(o).push({name:a,value:o.getAttribute(a)})},u=()=>{r.has(o)||r.set(o,[]),r.get(o).push(a)};o.hasAttribute(a)&&l===null?c():o.hasAttribute(a)?(u(),c()):u()}r.forEach((s,o)=>{xa(o,s)}),n.forEach((s,o)=>{ba.forEach(a=>a(o,s))});for(let s of i)t.some(o=>o.contains(s))||va.forEach(o=>o(s));for(let s of t)s.isConnected&&ya.forEach(o=>o(s));t=null,i=null,n=null,r=null}function Sa(e){return oi(be(e))}function si(e,t,i){return e._x_dataStack=[t,...be(i||e)],()=>{e._x_dataStack=e._x_dataStack.filter(n=>n!==t)}}function be(e){return e._x_dataStack?e._x_dataStack:typeof ShadowRoot=="function"&&e instanceof ShadowRoot?be(e.host):e.parentNode?be(e.parentNode):[]}function oi(e){return new Proxy({objects:e},Qh)}var Qh={ownKeys({objects:e}){return Array.from(new Set(e.flatMap(t=>Object.keys(t))))},has({objects:e},t){return t==Symbol.unscopables?!1:e.some(i=>Object.prototype.hasOwnProperty.call(i,t)||Reflect.has(i,t))},get({objects:e},t,i){return t=="toJSON"?Xh:Reflect.get(e.find(n=>Reflect.has(n,t))||{},t,i)},set({objects:e},t,i,n){const r=e.find(o=>Object.prototype.hasOwnProperty.call(o,t))||e[e.length-1],s=Object.getOwnPropertyDescriptor(r,t);return s!=null&&s.set&&(s!=null&&s.get)?s.set.call(n,i)||!0:Reflect.set(r,t,i)}};function Xh(){return Reflect.ownKeys(this).reduce((t,i)=>(t[i]=Reflect.get(this,i),t),{})}function Ca(e){let t=n=>typeof n=="object"&&!Array.isArray(n)&&n!==null,i=(n,r="")=>{Object.entries(Object.getOwnPropertyDescriptors(n)).forEach(([s,{value:o,enumerable:a}])=>{if(a===!1||o===void 0||typeof o=="object"&&o!==null&&o.__v_skip)return;let l=r===""?s:`${r}.${s}`;typeof o=="object"&&o!==null&&o._x_interceptor?n[s]=o.initialize(e,l,s):t(o)&&o!==n&&!(o instanceof Element)&&i(o,l)})};return i(e)}function ka(e,t=()=>{}){let i={initialValue:void 0,_x_interceptor:!0,initialize(n,r,s){return e(this.initialValue,()=>Gh(n,r),o=>Wn(n,r,o),r,s)}};return t(i),n=>{if(typeof n=="object"&&n!==null&&n._x_interceptor){let r=i.initialize.bind(i);i.initialize=(s,o,a)=>{let l=n.initialize(s,o,a);return i.initialValue=l,r(s,o,a)}}else i.initialValue=n;return i}}function Gh(e,t){return t.split(".").reduce((i,n)=>i[n],e)}function Wn(e,t,i){if(typeof t=="string"&&(t=t.split(".")),t.length===1)e[t[0]]=i;else{if(t.length===0)throw error;return e[t[0]]||(e[t[0]]={}),Wn(e[t[0]],t.slice(1),i)}}var Aa={};function gt(e,t){Aa[e]=t}function Jn(e,t){let i=Yh(t);return Object.entries(Aa).forEach(([n,r])=>{Object.defineProperty(e,`$${n}`,{get(){return r(t,i)},enumerable:!1})}),e}function Yh(e){let[t,i]=La(e),n={interceptor:ka,...t};return Sr(e,i),n}function Zh(e,t,i,...n){try{return i(...n)}catch(r){Ge(r,e,t)}}function Ge(e,t,i=void 0){e=Object.assign(e??{message:"No error message given."},{el:t,expression:i}),console.warn(`Alpine Expression Error: ${e.message}

${i?'Expression: "'+i+`"

`:""}`,t),setTimeout(()=>{throw e},0)}var Ci=!0;function Ta(e){let t=Ci;Ci=!1;let i=e();return Ci=t,i}function ie(e,t,i={}){let n;return J(e,t)(r=>n=r,i),n}function J(...e){return $a(...e)}var $a=Ra;function tp(e){$a=e}function Ra(e,t){let i={};Jn(i,e);let n=[i,...be(e)],r=typeof t=="function"?ep(n,t):np(n,t,e);return Zh.bind(null,e,t,r)}function ep(e,t){return(i=()=>{},{scope:n={},params:r=[]}={})=>{let s=t.apply(oi([n,...e]),r);Pi(i,s)}}var xn={};function ip(e,t){if(xn[e])return xn[e];let i=Object.getPrototypeOf(async function(){}).constructor,n=/^[\n\s]*if.*\(.*\)/.test(e.trim())||/^(let|const)\s/.test(e.trim())?`(async()=>{ ${e} })()`:e,s=(()=>{try{let o=new i(["__self","scope"],`with (scope) { __self.result = ${n} }; __self.finished = true; return __self.result;`);return Object.defineProperty(o,"name",{value:`[Alpine] ${e}`}),o}catch(o){return Ge(o,t,e),Promise.resolve()}})();return xn[e]=s,s}function np(e,t,i){let n=ip(t,i);return(r=()=>{},{scope:s={},params:o=[]}={})=>{n.result=void 0,n.finished=!1;let a=oi([s,...e]);if(typeof n=="function"){let l=n(n,a).catch(c=>Ge(c,i,t));n.finished?(Pi(r,n.result,a,o,i),n.result=void 0):l.then(c=>{Pi(r,c,a,o,i)}).catch(c=>Ge(c,i,t)).finally(()=>n.result=void 0)}}}function Pi(e,t,i,n,r){if(Ci&&typeof t=="function"){let s=t.apply(i,n);s instanceof Promise?s.then(o=>Pi(e,o,i,n)).catch(o=>Ge(o,r,t)):e(s)}else typeof t=="object"&&t instanceof Promise?t.then(s=>e(s)):e(t)}var Rr="x-";function Ae(e=""){return Rr+e}function rp(e){Rr=e}var zi={};function N(e,t){return zi[e]=t,{before(i){if(!zi[i]){console.warn(String.raw`Cannot find directive \`${i}\`. \`${e}\` will use the default order of execution`);return}const n=Yt.indexOf(i);Yt.splice(n>=0?n:Yt.indexOf("DEFAULT"),0,e)}}}function sp(e){return Object.keys(zi).includes(e)}function Fr(e,t,i){if(t=Array.from(t),e._x_virtualDirectives){let s=Object.entries(e._x_virtualDirectives).map(([a,l])=>({name:a,value:l})),o=Fa(s);s=s.map(a=>o.find(l=>l.name===a.name)?{name:`x-bind:${a.name}`,value:`"${a.value}"`}:a),t=t.concat(s)}let n={};return t.map(za((s,o)=>n[s]=o)).filter(Ba).map(lp(n,i)).sort(cp).map(s=>ap(e,s))}function Fa(e){return Array.from(e).map(za()).filter(t=>!Ba(t))}var Kn=!1,Ne=new Map,Oa=Symbol();function op(e){Kn=!0;let t=Symbol();Oa=t,Ne.set(t,[]);let i=()=>{for(;Ne.get(t).length;)Ne.get(t).shift()();Ne.delete(t)},n=()=>{Kn=!1,i()};e(i),n()}function La(e){let t=[],i=a=>t.push(a),[n,r]=jh(e);return t.push(r),[{Alpine:ai,effect:n,cleanup:i,evaluateLater:J.bind(J,e),evaluate:ie.bind(ie,e)},()=>t.forEach(a=>a())]}function ap(e,t){let i=()=>{},n=zi[t.type]||i,[r,s]=La(e);_a(e,t.original,s);let o=()=>{e._x_ignore||e._x_ignoreSelf||(n.inline&&n.inline(e,t,r),n=n.bind(n,e,t,r),Kn?Ne.get(Oa).push(n):n())};return o.runCleanups=s,o}var Ma=(e,t)=>({name:i,value:n})=>(i.startsWith(e)&&(i=i.replace(e,t)),{name:i,value:n}),Pa=e=>e;function za(e=()=>{}){return({name:t,value:i})=>{let{name:n,value:r}=Ia.reduce((s,o)=>o(s),{name:t,value:i});return n!==t&&e(n,t),{name:n,value:r}}}var Ia=[];function Or(e){Ia.push(e)}function Ba({name:e}){return Da().test(e)}var Da=()=>new RegExp(`^${Rr}([^:^.]+)\\b`);function lp(e,t){return({name:i,value:n})=>{let r=i.match(Da()),s=i.match(/:([a-zA-Z0-9\-_:]+)/),o=i.match(/\.[^.\]]+(?=[^\]]*$)/g)||[],a=t||e[i]||i;return{type:r?r[1]:null,value:s?s[1]:null,modifiers:o.map(l=>l.replace(".","")),expression:n,original:a}}}var Qn="DEFAULT",Yt=["ignore","ref","data","id","anchor","bind","init","for","model","modelable","transition","show","if",Qn,"teleport"];function cp(e,t){let i=Yt.indexOf(e.type)===-1?Qn:e.type,n=Yt.indexOf(t.type)===-1?Qn:t.type;return Yt.indexOf(i)-Yt.indexOf(n)}function qe(e,t,i={}){e.dispatchEvent(new CustomEvent(t,{detail:i,bubbles:!0,composed:!0,cancelable:!0}))}function le(e,t){if(typeof ShadowRoot=="function"&&e instanceof ShadowRoot){Array.from(e.children).forEach(r=>le(r,t));return}let i=!1;if(t(e,()=>i=!0),i)return;let n=e.firstElementChild;for(;n;)le(n,t),n=n.nextElementSibling}function at(e,...t){console.warn(`Alpine Warning: ${e}`,...t)}var js=!1;function up(){js&&at("Alpine has already been initialized on this page. Calling Alpine.start() more than once can cause problems."),js=!0,document.body||at("Unable to initialize. Trying to load Alpine before `<body>` is available. Did you forget to add `defer` in Alpine's `<script>` tag?"),qe(document,"alpine:init"),qe(document,"alpine:initializing"),Ar(),qh(t=>At(t,le)),Sr(t=>$e(t)),wa((t,i)=>{Fr(t,i).forEach(n=>n())});let e=t=>!Gi(t.parentElement,!0);Array.from(document.querySelectorAll(Ha().join(","))).filter(e).forEach(t=>{At(t)}),qe(document,"alpine:initialized"),setTimeout(()=>{fp()})}var Lr=[],Na=[];function Ua(){return Lr.map(e=>e())}function Ha(){return Lr.concat(Na).map(e=>e())}function ja(e){Lr.push(e)}function qa(e){Na.push(e)}function Gi(e,t=!1){return Te(e,i=>{if((t?Ha():Ua()).some(r=>i.matches(r)))return!0})}function Te(e,t){if(e){if(t(e))return e;if(e._x_teleportBack&&(e=e._x_teleportBack),!!e.parentElement)return Te(e.parentElement,t)}}function dp(e){return Ua().some(t=>e.matches(t))}var Va=[];function hp(e){Va.push(e)}var pp=1;function At(e,t=le,i=()=>{}){Te(e,n=>n._x_ignore)||op(()=>{t(e,(n,r)=>{n._x_marker||(i(n,r),Va.forEach(s=>s(n,r)),Fr(n,n.attributes).forEach(s=>s()),n._x_ignore||(n._x_marker=pp++),n._x_ignore&&r())})})}function $e(e,t=le){t(e,i=>{Vh(i),xa(i),delete i._x_marker})}function fp(){[["ui","dialog",["[x-dialog], [x-popover]"]],["anchor","anchor",["[x-anchor]"]],["sort","sort",["[x-sort]"]]].forEach(([t,i,n])=>{sp(i)||n.some(r=>{if(document.querySelector(r))return at(`found "${r}", but missing ${t} plugin`),!0})})}var Xn=[],Mr=!1;function Pr(e=()=>{}){return queueMicrotask(()=>{Mr||setTimeout(()=>{Gn()})}),new Promise(t=>{Xn.push(()=>{e(),t()})})}function Gn(){for(Mr=!1;Xn.length;)Xn.shift()()}function mp(){Mr=!0}function zr(e,t){return Array.isArray(t)?qs(e,t.join(" ")):typeof t=="object"&&t!==null?gp(e,t):typeof t=="function"?zr(e,t()):qs(e,t)}function qs(e,t){let i=r=>r.split(" ").filter(s=>!e.classList.contains(s)).filter(Boolean),n=r=>(e.classList.add(...r),()=>{e.classList.remove(...r)});return t=t===!0?t="":t||"",n(i(t))}function gp(e,t){let i=a=>a.split(" ").filter(Boolean),n=Object.entries(t).flatMap(([a,l])=>l?i(a):!1).filter(Boolean),r=Object.entries(t).flatMap(([a,l])=>l?!1:i(a)).filter(Boolean),s=[],o=[];return r.forEach(a=>{e.classList.contains(a)&&(e.classList.remove(a),o.push(a))}),n.forEach(a=>{e.classList.contains(a)||(e.classList.add(a),s.push(a))}),()=>{o.forEach(a=>e.classList.add(a)),s.forEach(a=>e.classList.remove(a))}}function Yi(e,t){return typeof t=="object"&&t!==null?bp(e,t):vp(e,t)}function bp(e,t){let i={};return Object.entries(t).forEach(([n,r])=>{i[n]=e.style[n],n.startsWith("--")||(n=yp(n)),e.style.setProperty(n,r)}),setTimeout(()=>{e.style.length===0&&e.removeAttribute("style")}),()=>{Yi(e,i)}}function vp(e,t){let i=e.getAttribute("style",t);return e.setAttribute("style",t),()=>{e.setAttribute("style",i||"")}}function yp(e){return e.replace(/([a-z])([A-Z])/g,"$1-$2").toLowerCase()}function Yn(e,t=()=>{}){let i=!1;return function(){i?t.apply(this,arguments):(i=!0,e.apply(this,arguments))}}N("transition",(e,{value:t,modifiers:i,expression:n},{evaluate:r})=>{typeof n=="function"&&(n=r(n)),n!==!1&&(!n||typeof n=="boolean"?_p(e,i,t):wp(e,n,t))});function wp(e,t,i){Wa(e,zr,""),{enter:r=>{e._x_transition.enter.during=r},"enter-start":r=>{e._x_transition.enter.start=r},"enter-end":r=>{e._x_transition.enter.end=r},leave:r=>{e._x_transition.leave.during=r},"leave-start":r=>{e._x_transition.leave.start=r},"leave-end":r=>{e._x_transition.leave.end=r}}[i](t)}function _p(e,t,i){Wa(e,Yi);let n=!t.includes("in")&&!t.includes("out")&&!i,r=n||t.includes("in")||["enter"].includes(i),s=n||t.includes("out")||["leave"].includes(i);t.includes("in")&&!n&&(t=t.filter((_,x)=>x<t.indexOf("out"))),t.includes("out")&&!n&&(t=t.filter((_,x)=>x>t.indexOf("out")));let o=!t.includes("opacity")&&!t.includes("scale"),a=o||t.includes("opacity"),l=o||t.includes("scale"),c=a?0:1,u=l?Be(t,"scale",95)/100:1,d=Be(t,"delay",0)/1e3,p=Be(t,"origin","center"),f="opacity, transform",b=Be(t,"duration",150)/1e3,w=Be(t,"duration",75)/1e3,g="cubic-bezier(0.4, 0.0, 0.2, 1)";r&&(e._x_transition.enter.during={transformOrigin:p,transitionDelay:`${d}s`,transitionProperty:f,transitionDuration:`${b}s`,transitionTimingFunction:g},e._x_transition.enter.start={opacity:c,transform:`scale(${u})`},e._x_transition.enter.end={opacity:1,transform:"scale(1)"}),s&&(e._x_transition.leave.during={transformOrigin:p,transitionDelay:`${d}s`,transitionProperty:f,transitionDuration:`${w}s`,transitionTimingFunction:g},e._x_transition.leave.start={opacity:1,transform:"scale(1)"},e._x_transition.leave.end={opacity:c,transform:`scale(${u})`})}function Wa(e,t,i={}){e._x_transition||(e._x_transition={enter:{during:i,start:i,end:i},leave:{during:i,start:i,end:i},in(n=()=>{},r=()=>{}){Zn(e,t,{during:this.enter.during,start:this.enter.start,end:this.enter.end},n,r)},out(n=()=>{},r=()=>{}){Zn(e,t,{during:this.leave.during,start:this.leave.start,end:this.leave.end},n,r)}})}window.Element.prototype._x_toggleAndCascadeWithTransitions=function(e,t,i,n){const r=document.visibilityState==="visible"?requestAnimationFrame:setTimeout;let s=()=>r(i);if(t){e._x_transition&&(e._x_transition.enter||e._x_transition.leave)?e._x_transition.enter&&(Object.entries(e._x_transition.enter.during).length||Object.entries(e._x_transition.enter.start).length||Object.entries(e._x_transition.enter.end).length)?e._x_transition.in(i):s():e._x_transition?e._x_transition.in(i):s();return}e._x_hidePromise=e._x_transition?new Promise((o,a)=>{e._x_transition.out(()=>{},()=>o(n)),e._x_transitioning&&e._x_transitioning.beforeCancel(()=>a({isFromCancelledTransition:!0}))}):Promise.resolve(n),queueMicrotask(()=>{let o=Ja(e);o?(o._x_hideChildren||(o._x_hideChildren=[]),o._x_hideChildren.push(e)):r(()=>{let a=l=>{let c=Promise.all([l._x_hidePromise,...(l._x_hideChildren||[]).map(a)]).then(([u])=>u==null?void 0:u());return delete l._x_hidePromise,delete l._x_hideChildren,c};a(e).catch(l=>{if(!l.isFromCancelledTransition)throw l})})})};function Ja(e){let t=e.parentNode;if(t)return t._x_hidePromise?t:Ja(t)}function Zn(e,t,{during:i,start:n,end:r}={},s=()=>{},o=()=>{}){if(e._x_transitioning&&e._x_transitioning.cancel(),Object.keys(i).length===0&&Object.keys(n).length===0&&Object.keys(r).length===0){s(),o();return}let a,l,c;xp(e,{start(){a=t(e,n)},during(){l=t(e,i)},before:s,end(){a(),c=t(e,r)},after:o,cleanup(){l(),c()}})}function xp(e,t){let i,n,r,s=Yn(()=>{P(()=>{i=!0,n||t.before(),r||(t.end(),Gn()),t.after(),e.isConnected&&t.cleanup(),delete e._x_transitioning})});e._x_transitioning={beforeCancels:[],beforeCancel(o){this.beforeCancels.push(o)},cancel:Yn(function(){for(;this.beforeCancels.length;)this.beforeCancels.shift()();s()}),finish:s},P(()=>{t.start(),t.during()}),mp(),requestAnimationFrame(()=>{if(i)return;let o=Number(getComputedStyle(e).transitionDuration.replace(/,.*/,"").replace("s",""))*1e3,a=Number(getComputedStyle(e).transitionDelay.replace(/,.*/,"").replace("s",""))*1e3;o===0&&(o=Number(getComputedStyle(e).animationDuration.replace("s",""))*1e3),P(()=>{t.before()}),n=!0,requestAnimationFrame(()=>{i||(P(()=>{t.end()}),Gn(),setTimeout(e._x_transitioning.finish,o+a),r=!0)})})}function Be(e,t,i){if(e.indexOf(t)===-1)return i;const n=e[e.indexOf(t)+1];if(!n||t==="scale"&&isNaN(n))return i;if(t==="duration"||t==="delay"){let r=n.match(/([0-9]+)ms/);if(r)return r[1]}return t==="origin"&&["top","right","left","center","bottom"].includes(e[e.indexOf(t)+2])?[n,e[e.indexOf(t)+2]].join(" "):n}var Ut=!1;function jt(e,t=()=>{}){return(...i)=>Ut?t(...i):e(...i)}function Ep(e){return(...t)=>Ut&&e(...t)}var Ka=[];function Zi(e){Ka.push(e)}function Sp(e,t){Ka.forEach(i=>i(e,t)),Ut=!0,Qa(()=>{At(t,(i,n)=>{n(i,()=>{})})}),Ut=!1}var tr=!1;function Cp(e,t){t._x_dataStack||(t._x_dataStack=e._x_dataStack),Ut=!0,tr=!0,Qa(()=>{kp(t)}),Ut=!1,tr=!1}function kp(e){let t=!1;At(e,(n,r)=>{le(n,(s,o)=>{if(t&&dp(s))return o();t=!0,r(s,o)})})}function Qa(e){let t=ue;Hs((i,n)=>{let r=t(i);return ke(r),()=>{}}),e(),Hs(t)}function Xa(e,t,i,n=[]){switch(e._x_bindings||(e._x_bindings=Ce({})),e._x_bindings[t]=i,t=n.includes("camel")?Mp(t):t,t){case"value":Ap(e,i);break;case"style":$p(e,i);break;case"class":Tp(e,i);break;case"selected":case"checked":Rp(e,t,i);break;default:Ga(e,t,i);break}}function Ap(e,t){if(tl(e))e.attributes.value===void 0&&(e.value=t),window.fromModel&&(typeof t=="boolean"?e.checked=ki(e.value)===t:e.checked=Vs(e.value,t));else if(Ir(e))Number.isInteger(t)?e.value=t:!Array.isArray(t)&&typeof t!="boolean"&&![null,void 0].includes(t)?e.value=String(t):Array.isArray(t)?e.checked=t.some(i=>Vs(i,e.value)):e.checked=!!t;else if(e.tagName==="SELECT")Lp(e,t);else{if(e.value===t)return;e.value=t===void 0?"":t}}function Tp(e,t){e._x_undoAddedClasses&&e._x_undoAddedClasses(),e._x_undoAddedClasses=zr(e,t)}function $p(e,t){e._x_undoAddedStyles&&e._x_undoAddedStyles(),e._x_undoAddedStyles=Yi(e,t)}function Rp(e,t,i){Ga(e,t,i),Op(e,t,i)}function Ga(e,t,i){[null,void 0,!1].includes(i)&&zp(t)?e.removeAttribute(t):(Ya(t)&&(i=t),Fp(e,t,i))}function Fp(e,t,i){e.getAttribute(t)!=i&&e.setAttribute(t,i)}function Op(e,t,i){e[t]!==i&&(e[t]=i)}function Lp(e,t){const i=[].concat(t).map(n=>n+"");Array.from(e.options).forEach(n=>{n.selected=i.includes(n.value)})}function Mp(e){return e.toLowerCase().replace(/-(\w)/g,(t,i)=>i.toUpperCase())}function Vs(e,t){return e==t}function ki(e){return[1,"1","true","on","yes",!0].includes(e)?!0:[0,"0","false","off","no",!1].includes(e)?!1:e?!!e:null}var Pp=new Set(["allowfullscreen","async","autofocus","autoplay","checked","controls","default","defer","disabled","formnovalidate","inert","ismap","itemscope","loop","multiple","muted","nomodule","novalidate","open","playsinline","readonly","required","reversed","selected","shadowrootclonable","shadowrootdelegatesfocus","shadowrootserializable"]);function Ya(e){return Pp.has(e)}function zp(e){return!["aria-pressed","aria-checked","aria-expanded","aria-selected"].includes(e)}function Ip(e,t,i){return e._x_bindings&&e._x_bindings[t]!==void 0?e._x_bindings[t]:Za(e,t,i)}function Bp(e,t,i,n=!0){if(e._x_bindings&&e._x_bindings[t]!==void 0)return e._x_bindings[t];if(e._x_inlineBindings&&e._x_inlineBindings[t]!==void 0){let r=e._x_inlineBindings[t];return r.extract=n,Ta(()=>ie(e,r.expression))}return Za(e,t,i)}function Za(e,t,i){let n=e.getAttribute(t);return n===null?typeof i=="function"?i():i:n===""?!0:Ya(t)?!![t,"true"].includes(n):n}function Ir(e){return e.type==="checkbox"||e.localName==="ui-checkbox"||e.localName==="ui-switch"}function tl(e){return e.type==="radio"||e.localName==="ui-radio"}function el(e,t){var i;return function(){var n=this,r=arguments,s=function(){i=null,e.apply(n,r)};clearTimeout(i),i=setTimeout(s,t)}}function il(e,t){let i;return function(){let n=this,r=arguments;i||(e.apply(n,r),i=!0,setTimeout(()=>i=!1,t))}}function nl({get:e,set:t},{get:i,set:n}){let r=!0,s,o=ue(()=>{let a=e(),l=i();if(r)n(En(a)),r=!1;else{let c=JSON.stringify(a),u=JSON.stringify(l);c!==s?n(En(a)):c!==u&&t(En(l))}s=JSON.stringify(e()),JSON.stringify(i())});return()=>{ke(o)}}function En(e){return typeof e=="object"?JSON.parse(JSON.stringify(e)):e}function Dp(e){(Array.isArray(e)?e:[e]).forEach(i=>i(ai))}var Jt={},Ws=!1;function Np(e,t){if(Ws||(Jt=Ce(Jt),Ws=!0),t===void 0)return Jt[e];Jt[e]=t,Ca(Jt[e]),typeof t=="object"&&t!==null&&t.hasOwnProperty("init")&&typeof t.init=="function"&&Jt[e].init()}function Up(){return Jt}var rl={};function Hp(e,t){let i=typeof t!="function"?()=>t:t;return e instanceof Element?sl(e,i()):(rl[e]=i,()=>{})}function jp(e){return Object.entries(rl).forEach(([t,i])=>{Object.defineProperty(e,t,{get(){return(...n)=>i(...n)}})}),e}function sl(e,t,i){let n=[];for(;n.length;)n.pop()();let r=Object.entries(t).map(([o,a])=>({name:o,value:a})),s=Fa(r);return r=r.map(o=>s.find(a=>a.name===o.name)?{name:`x-bind:${o.name}`,value:`"${o.value}"`}:o),Fr(e,r,i).map(o=>{n.push(o.runCleanups),o()}),()=>{for(;n.length;)n.pop()()}}var ol={};function qp(e,t){ol[e]=t}function Vp(e,t){return Object.entries(ol).forEach(([i,n])=>{Object.defineProperty(e,i,{get(){return(...r)=>n.bind(t)(...r)},enumerable:!1})}),e}var Wp={get reactive(){return Ce},get release(){return ke},get effect(){return ue},get raw(){return ma},version:"3.14.9",flushAndStopDeferringMutations:Kh,dontAutoEvaluateFunctions:Ta,disableEffectScheduling:Uh,startObservingMutations:Ar,stopObservingMutations:Ea,setReactivityEngine:Hh,onAttributeRemoved:_a,onAttributesAdded:wa,closestDataStack:be,skipDuringClone:jt,onlyDuringClone:Ep,addRootSelector:ja,addInitSelector:qa,interceptClone:Zi,addScopeToNode:si,deferMutations:Jh,mapAttributes:Or,evaluateLater:J,interceptInit:hp,setEvaluator:tp,mergeProxies:oi,extractProp:Bp,findClosest:Te,onElRemoved:Sr,closestRoot:Gi,destroyTree:$e,interceptor:ka,transition:Zn,setStyles:Yi,mutateDom:P,directive:N,entangle:nl,throttle:il,debounce:el,evaluate:ie,initTree:At,nextTick:Pr,prefixed:Ae,prefix:rp,plugin:Dp,magic:gt,store:Np,start:up,clone:Cp,cloneNode:Sp,bound:Ip,$data:Sa,watch:ga,walk:le,data:qp,bind:Hp},ai=Wp;function Jp(e,t){const i=Object.create(null),n=e.split(",");for(let r=0;r<n.length;r++)i[n[r]]=!0;return r=>!!i[r]}var Kp=Object.freeze({}),Qp=Object.prototype.hasOwnProperty,tn=(e,t)=>Qp.call(e,t),ne=Array.isArray,Ve=e=>al(e)==="[object Map]",Xp=e=>typeof e=="string",Br=e=>typeof e=="symbol",en=e=>e!==null&&typeof e=="object",Gp=Object.prototype.toString,al=e=>Gp.call(e),ll=e=>al(e).slice(8,-1),Dr=e=>Xp(e)&&e!=="NaN"&&e[0]!=="-"&&""+parseInt(e,10)===e,Yp=e=>{const t=Object.create(null);return i=>t[i]||(t[i]=e(i))},Zp=Yp(e=>e.charAt(0).toUpperCase()+e.slice(1)),cl=(e,t)=>e!==t&&(e===e||t===t),er=new WeakMap,De=[],yt,re=Symbol("iterate"),ir=Symbol("Map key iterate");function tf(e){return e&&e._isEffect===!0}function ef(e,t=Kp){tf(e)&&(e=e.raw);const i=sf(e,t);return t.lazy||i(),i}function nf(e){e.active&&(ul(e),e.options.onStop&&e.options.onStop(),e.active=!1)}var rf=0;function sf(e,t){const i=function(){if(!i.active)return e();if(!De.includes(i)){ul(i);try{return af(),De.push(i),yt=i,e()}finally{De.pop(),dl(),yt=De[De.length-1]}}};return i.id=rf++,i.allowRecurse=!!t.allowRecurse,i._isEffect=!0,i.active=!0,i.raw=e,i.deps=[],i.options=t,i}function ul(e){const{deps:t}=e;if(t.length){for(let i=0;i<t.length;i++)t[i].delete(e);t.length=0}}var ve=!0,Nr=[];function of(){Nr.push(ve),ve=!1}function af(){Nr.push(ve),ve=!0}function dl(){const e=Nr.pop();ve=e===void 0?!0:e}function ht(e,t,i){if(!ve||yt===void 0)return;let n=er.get(e);n||er.set(e,n=new Map);let r=n.get(i);r||n.set(i,r=new Set),r.has(yt)||(r.add(yt),yt.deps.push(r),yt.options.onTrack&&yt.options.onTrack({effect:yt,target:e,type:t,key:i}))}function Ht(e,t,i,n,r,s){const o=er.get(e);if(!o)return;const a=new Set,l=u=>{u&&u.forEach(d=>{(d!==yt||d.allowRecurse)&&a.add(d)})};if(t==="clear")o.forEach(l);else if(i==="length"&&ne(e))o.forEach((u,d)=>{(d==="length"||d>=n)&&l(u)});else switch(i!==void 0&&l(o.get(i)),t){case"add":ne(e)?Dr(i)&&l(o.get("length")):(l(o.get(re)),Ve(e)&&l(o.get(ir)));break;case"delete":ne(e)||(l(o.get(re)),Ve(e)&&l(o.get(ir)));break;case"set":Ve(e)&&l(o.get(re));break}const c=u=>{u.options.onTrigger&&u.options.onTrigger({effect:u,target:e,key:i,type:t,newValue:n,oldValue:r,oldTarget:s}),u.options.scheduler?u.options.scheduler(u):u()};a.forEach(c)}var lf=Jp("__proto__,__v_isRef,__isVue"),hl=new Set(Object.getOwnPropertyNames(Symbol).map(e=>Symbol[e]).filter(Br)),cf=pl(),uf=pl(!0),Js=df();function df(){const e={};return["includes","indexOf","lastIndexOf"].forEach(t=>{e[t]=function(...i){const n=M(this);for(let s=0,o=this.length;s<o;s++)ht(n,"get",s+"");const r=n[t](...i);return r===-1||r===!1?n[t](...i.map(M)):r}}),["push","pop","shift","unshift","splice"].forEach(t=>{e[t]=function(...i){of();const n=M(this)[t].apply(this,i);return dl(),n}}),e}function pl(e=!1,t=!1){return function(n,r,s){if(r==="__v_isReactive")return!e;if(r==="__v_isReadonly")return e;if(r==="__v_raw"&&s===(e?t?Cf:bl:t?Sf:gl).get(n))return n;const o=ne(n);if(!e&&o&&tn(Js,r))return Reflect.get(Js,r,s);const a=Reflect.get(n,r,s);return(Br(r)?hl.has(r):lf(r))||(e||ht(n,"get",r),t)?a:nr(a)?!o||!Dr(r)?a.value:a:en(a)?e?vl(a):qr(a):a}}var hf=pf();function pf(e=!1){return function(i,n,r,s){let o=i[n];if(!e&&(r=M(r),o=M(o),!ne(i)&&nr(o)&&!nr(r)))return o.value=r,!0;const a=ne(i)&&Dr(n)?Number(n)<i.length:tn(i,n),l=Reflect.set(i,n,r,s);return i===M(s)&&(a?cl(r,o)&&Ht(i,"set",n,r,o):Ht(i,"add",n,r)),l}}function ff(e,t){const i=tn(e,t),n=e[t],r=Reflect.deleteProperty(e,t);return r&&i&&Ht(e,"delete",t,void 0,n),r}function mf(e,t){const i=Reflect.has(e,t);return(!Br(t)||!hl.has(t))&&ht(e,"has",t),i}function gf(e){return ht(e,"iterate",ne(e)?"length":re),Reflect.ownKeys(e)}var bf={get:cf,set:hf,deleteProperty:ff,has:mf,ownKeys:gf},vf={get:uf,set(e,t){return console.warn(`Set operation on key "${String(t)}" failed: target is readonly.`,e),!0},deleteProperty(e,t){return console.warn(`Delete operation on key "${String(t)}" failed: target is readonly.`,e),!0}},Ur=e=>en(e)?qr(e):e,Hr=e=>en(e)?vl(e):e,jr=e=>e,nn=e=>Reflect.getPrototypeOf(e);function fi(e,t,i=!1,n=!1){e=e.__v_raw;const r=M(e),s=M(t);t!==s&&!i&&ht(r,"get",t),!i&&ht(r,"get",s);const{has:o}=nn(r),a=n?jr:i?Hr:Ur;if(o.call(r,t))return a(e.get(t));if(o.call(r,s))return a(e.get(s));e!==r&&e.get(t)}function mi(e,t=!1){const i=this.__v_raw,n=M(i),r=M(e);return e!==r&&!t&&ht(n,"has",e),!t&&ht(n,"has",r),e===r?i.has(e):i.has(e)||i.has(r)}function gi(e,t=!1){return e=e.__v_raw,!t&&ht(M(e),"iterate",re),Reflect.get(e,"size",e)}function Ks(e){e=M(e);const t=M(this);return nn(t).has.call(t,e)||(t.add(e),Ht(t,"add",e,e)),this}function Qs(e,t){t=M(t);const i=M(this),{has:n,get:r}=nn(i);let s=n.call(i,e);s?ml(i,n,e):(e=M(e),s=n.call(i,e));const o=r.call(i,e);return i.set(e,t),s?cl(t,o)&&Ht(i,"set",e,t,o):Ht(i,"add",e,t),this}function Xs(e){const t=M(this),{has:i,get:n}=nn(t);let r=i.call(t,e);r?ml(t,i,e):(e=M(e),r=i.call(t,e));const s=n?n.call(t,e):void 0,o=t.delete(e);return r&&Ht(t,"delete",e,void 0,s),o}function Gs(){const e=M(this),t=e.size!==0,i=Ve(e)?new Map(e):new Set(e),n=e.clear();return t&&Ht(e,"clear",void 0,void 0,i),n}function bi(e,t){return function(n,r){const s=this,o=s.__v_raw,a=M(o),l=t?jr:e?Hr:Ur;return!e&&ht(a,"iterate",re),o.forEach((c,u)=>n.call(r,l(c),l(u),s))}}function vi(e,t,i){return function(...n){const r=this.__v_raw,s=M(r),o=Ve(s),a=e==="entries"||e===Symbol.iterator&&o,l=e==="keys"&&o,c=r[e](...n),u=i?jr:t?Hr:Ur;return!t&&ht(s,"iterate",l?ir:re),{next(){const{value:d,done:p}=c.next();return p?{value:d,done:p}:{value:a?[u(d[0]),u(d[1])]:u(d),done:p}},[Symbol.iterator](){return this}}}}function Lt(e){return function(...t){{const i=t[0]?`on key "${t[0]}" `:"";console.warn(`${Zp(e)} operation ${i}failed: target is readonly.`,M(this))}return e==="delete"?!1:this}}function yf(){const e={get(s){return fi(this,s)},get size(){return gi(this)},has:mi,add:Ks,set:Qs,delete:Xs,clear:Gs,forEach:bi(!1,!1)},t={get(s){return fi(this,s,!1,!0)},get size(){return gi(this)},has:mi,add:Ks,set:Qs,delete:Xs,clear:Gs,forEach:bi(!1,!0)},i={get(s){return fi(this,s,!0)},get size(){return gi(this,!0)},has(s){return mi.call(this,s,!0)},add:Lt("add"),set:Lt("set"),delete:Lt("delete"),clear:Lt("clear"),forEach:bi(!0,!1)},n={get(s){return fi(this,s,!0,!0)},get size(){return gi(this,!0)},has(s){return mi.call(this,s,!0)},add:Lt("add"),set:Lt("set"),delete:Lt("delete"),clear:Lt("clear"),forEach:bi(!0,!0)};return["keys","values","entries",Symbol.iterator].forEach(s=>{e[s]=vi(s,!1,!1),i[s]=vi(s,!0,!1),t[s]=vi(s,!1,!0),n[s]=vi(s,!0,!0)}),[e,i,t,n]}var[wf,_f,Rm,Fm]=yf();function fl(e,t){const i=e?_f:wf;return(n,r,s)=>r==="__v_isReactive"?!e:r==="__v_isReadonly"?e:r==="__v_raw"?n:Reflect.get(tn(i,r)&&r in n?i:n,r,s)}var xf={get:fl(!1)},Ef={get:fl(!0)};function ml(e,t,i){const n=M(i);if(n!==i&&t.call(e,n)){const r=ll(e);console.warn(`Reactive ${r} contains both the raw and reactive versions of the same object${r==="Map"?" as keys":""}, which can lead to inconsistencies. Avoid differentiating between the raw and reactive versions of an object and only use the reactive version if possible.`)}}var gl=new WeakMap,Sf=new WeakMap,bl=new WeakMap,Cf=new WeakMap;function kf(e){switch(e){case"Object":case"Array":return 1;case"Map":case"Set":case"WeakMap":case"WeakSet":return 2;default:return 0}}function Af(e){return e.__v_skip||!Object.isExtensible(e)?0:kf(ll(e))}function qr(e){return e&&e.__v_isReadonly?e:yl(e,!1,bf,xf,gl)}function vl(e){return yl(e,!0,vf,Ef,bl)}function yl(e,t,i,n,r){if(!en(e))return console.warn(`value cannot be made reactive: ${String(e)}`),e;if(e.__v_raw&&!(t&&e.__v_isReactive))return e;const s=r.get(e);if(s)return s;const o=Af(e);if(o===0)return e;const a=new Proxy(e,o===2?n:i);return r.set(e,a),a}function M(e){return e&&M(e.__v_raw)||e}function nr(e){return!!(e&&e.__v_isRef===!0)}gt("nextTick",()=>Pr);gt("dispatch",e=>qe.bind(qe,e));gt("watch",(e,{evaluateLater:t,cleanup:i})=>(n,r)=>{let s=t(n),a=ga(()=>{let l;return s(c=>l=c),l},r);i(a)});gt("store",Up);gt("data",e=>Sa(e));gt("root",e=>Gi(e));gt("refs",e=>(e._x_refs_proxy||(e._x_refs_proxy=oi(Tf(e))),e._x_refs_proxy));function Tf(e){let t=[];return Te(e,i=>{i._x_refs&&t.push(i._x_refs)}),t}var Sn={};function wl(e){return Sn[e]||(Sn[e]=0),++Sn[e]}function $f(e,t){return Te(e,i=>{if(i._x_ids&&i._x_ids[t])return!0})}function Rf(e,t){e._x_ids||(e._x_ids={}),e._x_ids[t]||(e._x_ids[t]=wl(t))}gt("id",(e,{cleanup:t})=>(i,n=null)=>{let r=`${i}${n?`-${n}`:""}`;return Ff(e,r,t,()=>{let s=$f(e,i),o=s?s._x_ids[i]:wl(i);return n?`${i}-${o}-${n}`:`${i}-${o}`})});Zi((e,t)=>{e._x_id&&(t._x_id=e._x_id)});function Ff(e,t,i,n){if(e._x_id||(e._x_id={}),e._x_id[t])return e._x_id[t];let r=n();return e._x_id[t]=r,i(()=>{delete e._x_id[t]}),r}gt("el",e=>e);_l("Focus","focus","focus");_l("Persist","persist","persist");function _l(e,t,i){gt(t,n=>at(`You can't use [$${t}] without first installing the "${e}" plugin here: https://alpinejs.dev/plugins/${i}`,n))}N("modelable",(e,{expression:t},{effect:i,evaluateLater:n,cleanup:r})=>{let s=n(t),o=()=>{let u;return s(d=>u=d),u},a=n(`${t} = __placeholder`),l=u=>a(()=>{},{scope:{__placeholder:u}}),c=o();l(c),queueMicrotask(()=>{if(!e._x_model)return;e._x_removeModelListeners.default();let u=e._x_model.get,d=e._x_model.set,p=nl({get(){return u()},set(f){d(f)}},{get(){return o()},set(f){l(f)}});r(p)})});N("teleport",(e,{modifiers:t,expression:i},{cleanup:n})=>{e.tagName.toLowerCase()!=="template"&&at("x-teleport can only be used on a <template> tag",e);let r=Ys(i),s=e.content.cloneNode(!0).firstElementChild;e._x_teleport=s,s._x_teleportBack=e,e.setAttribute("data-teleport-template",!0),s.setAttribute("data-teleport-target",!0),e._x_forwardEvents&&e._x_forwardEvents.forEach(a=>{s.addEventListener(a,l=>{l.stopPropagation(),e.dispatchEvent(new l.constructor(l.type,l))})}),si(s,{},e);let o=(a,l,c)=>{c.includes("prepend")?l.parentNode.insertBefore(a,l):c.includes("append")?l.parentNode.insertBefore(a,l.nextSibling):l.appendChild(a)};P(()=>{o(s,r,t),jt(()=>{At(s)})()}),e._x_teleportPutBack=()=>{let a=Ys(i);P(()=>{o(e._x_teleport,a,t)})},n(()=>P(()=>{s.remove(),$e(s)}))});var Of=document.createElement("div");function Ys(e){let t=jt(()=>document.querySelector(e),()=>Of)();return t||at(`Cannot find x-teleport element for selector: "${e}"`),t}var xl=()=>{};xl.inline=(e,{modifiers:t},{cleanup:i})=>{t.includes("self")?e._x_ignoreSelf=!0:e._x_ignore=!0,i(()=>{t.includes("self")?delete e._x_ignoreSelf:delete e._x_ignore})};N("ignore",xl);N("effect",jt((e,{expression:t},{effect:i})=>{i(J(e,t))}));function rr(e,t,i,n){let r=e,s=l=>n(l),o={},a=(l,c)=>u=>c(l,u);if(i.includes("dot")&&(t=Lf(t)),i.includes("camel")&&(t=Mf(t)),i.includes("passive")&&(o.passive=!0),i.includes("capture")&&(o.capture=!0),i.includes("window")&&(r=window),i.includes("document")&&(r=document),i.includes("debounce")){let l=i[i.indexOf("debounce")+1]||"invalid-wait",c=Ii(l.split("ms")[0])?Number(l.split("ms")[0]):250;s=el(s,c)}if(i.includes("throttle")){let l=i[i.indexOf("throttle")+1]||"invalid-wait",c=Ii(l.split("ms")[0])?Number(l.split("ms")[0]):250;s=il(s,c)}return i.includes("prevent")&&(s=a(s,(l,c)=>{c.preventDefault(),l(c)})),i.includes("stop")&&(s=a(s,(l,c)=>{c.stopPropagation(),l(c)})),i.includes("once")&&(s=a(s,(l,c)=>{l(c),r.removeEventListener(t,s,o)})),(i.includes("away")||i.includes("outside"))&&(r=document,s=a(s,(l,c)=>{e.contains(c.target)||c.target.isConnected!==!1&&(e.offsetWidth<1&&e.offsetHeight<1||e._x_isShown!==!1&&l(c))})),i.includes("self")&&(s=a(s,(l,c)=>{c.target===e&&l(c)})),(zf(t)||El(t))&&(s=a(s,(l,c)=>{If(c,i)||l(c)})),r.addEventListener(t,s,o),()=>{r.removeEventListener(t,s,o)}}function Lf(e){return e.replace(/-/g,".")}function Mf(e){return e.toLowerCase().replace(/-(\w)/g,(t,i)=>i.toUpperCase())}function Ii(e){return!Array.isArray(e)&&!isNaN(e)}function Pf(e){return[" ","_"].includes(e)?e:e.replace(/([a-z])([A-Z])/g,"$1-$2").replace(/[_\s]/,"-").toLowerCase()}function zf(e){return["keydown","keyup"].includes(e)}function El(e){return["contextmenu","click","mouse"].some(t=>e.includes(t))}function If(e,t){let i=t.filter(s=>!["window","document","prevent","stop","once","capture","self","away","outside","passive"].includes(s));if(i.includes("debounce")){let s=i.indexOf("debounce");i.splice(s,Ii((i[s+1]||"invalid-wait").split("ms")[0])?2:1)}if(i.includes("throttle")){let s=i.indexOf("throttle");i.splice(s,Ii((i[s+1]||"invalid-wait").split("ms")[0])?2:1)}if(i.length===0||i.length===1&&Zs(e.key).includes(i[0]))return!1;const r=["ctrl","shift","alt","meta","cmd","super"].filter(s=>i.includes(s));return i=i.filter(s=>!r.includes(s)),!(r.length>0&&r.filter(o=>((o==="cmd"||o==="super")&&(o="meta"),e[`${o}Key`])).length===r.length&&(El(e.type)||Zs(e.key).includes(i[0])))}function Zs(e){if(!e)return[];e=Pf(e);let t={ctrl:"control",slash:"/",space:" ",spacebar:" ",cmd:"meta",esc:"escape",up:"arrow-up",down:"arrow-down",left:"arrow-left",right:"arrow-right",period:".",comma:",",equal:"=",minus:"-",underscore:"_"};return t[e]=e,Object.keys(t).map(i=>{if(t[i]===e)return i}).filter(i=>i)}N("model",(e,{modifiers:t,expression:i},{effect:n,cleanup:r})=>{let s=e;t.includes("parent")&&(s=e.parentNode);let o=J(s,i),a;typeof i=="string"?a=J(s,`${i} = __placeholder`):typeof i=="function"&&typeof i()=="string"?a=J(s,`${i()} = __placeholder`):a=()=>{};let l=()=>{let p;return o(f=>p=f),to(p)?p.get():p},c=p=>{let f;o(b=>f=b),to(f)?f.set(p):a(()=>{},{scope:{__placeholder:p}})};typeof i=="string"&&e.type==="radio"&&P(()=>{e.hasAttribute("name")||e.setAttribute("name",i)});var u=e.tagName.toLowerCase()==="select"||["checkbox","radio"].includes(e.type)||t.includes("lazy")?"change":"input";let d=Ut?()=>{}:rr(e,u,t,p=>{c(Cn(e,t,p,l()))});if(t.includes("fill")&&([void 0,null,""].includes(l())||Ir(e)&&Array.isArray(l())||e.tagName.toLowerCase()==="select"&&e.multiple)&&c(Cn(e,t,{target:e},l())),e._x_removeModelListeners||(e._x_removeModelListeners={}),e._x_removeModelListeners.default=d,r(()=>e._x_removeModelListeners.default()),e.form){let p=rr(e.form,"reset",[],f=>{Pr(()=>e._x_model&&e._x_model.set(Cn(e,t,{target:e},l())))});r(()=>p())}e._x_model={get(){return l()},set(p){c(p)}},e._x_forceModelUpdate=p=>{p===void 0&&typeof i=="string"&&i.match(/\./)&&(p=""),window.fromModel=!0,P(()=>Xa(e,"value",p)),delete window.fromModel},n(()=>{let p=l();t.includes("unintrusive")&&document.activeElement.isSameNode(e)||e._x_forceModelUpdate(p)})});function Cn(e,t,i,n){return P(()=>{if(i instanceof CustomEvent&&i.detail!==void 0)return i.detail!==null&&i.detail!==void 0?i.detail:i.target.value;if(Ir(e))if(Array.isArray(n)){let r=null;return t.includes("number")?r=kn(i.target.value):t.includes("boolean")?r=ki(i.target.value):r=i.target.value,i.target.checked?n.includes(r)?n:n.concat([r]):n.filter(s=>!Bf(s,r))}else return i.target.checked;else{if(e.tagName.toLowerCase()==="select"&&e.multiple)return t.includes("number")?Array.from(i.target.selectedOptions).map(r=>{let s=r.value||r.text;return kn(s)}):t.includes("boolean")?Array.from(i.target.selectedOptions).map(r=>{let s=r.value||r.text;return ki(s)}):Array.from(i.target.selectedOptions).map(r=>r.value||r.text);{let r;return tl(e)?i.target.checked?r=i.target.value:r=n:r=i.target.value,t.includes("number")?kn(r):t.includes("boolean")?ki(r):t.includes("trim")?r.trim():r}}})}function kn(e){let t=e?parseFloat(e):null;return Df(t)?t:e}function Bf(e,t){return e==t}function Df(e){return!Array.isArray(e)&&!isNaN(e)}function to(e){return e!==null&&typeof e=="object"&&typeof e.get=="function"&&typeof e.set=="function"}N("cloak",e=>queueMicrotask(()=>P(()=>e.removeAttribute(Ae("cloak")))));qa(()=>`[${Ae("init")}]`);N("init",jt((e,{expression:t},{evaluate:i})=>typeof t=="string"?!!t.trim()&&i(t,{},!1):i(t,{},!1)));N("text",(e,{expression:t},{effect:i,evaluateLater:n})=>{let r=n(t);i(()=>{r(s=>{P(()=>{e.textContent=s})})})});N("html",(e,{expression:t},{effect:i,evaluateLater:n})=>{let r=n(t);i(()=>{r(s=>{P(()=>{e.innerHTML=s,e._x_ignoreSelf=!0,At(e),delete e._x_ignoreSelf})})})});Or(Ma(":",Pa(Ae("bind:"))));var Sl=(e,{value:t,modifiers:i,expression:n,original:r},{effect:s,cleanup:o})=>{if(!t){let l={};jp(l),J(e,n)(u=>{sl(e,u,r)},{scope:l});return}if(t==="key")return Nf(e,n);if(e._x_inlineBindings&&e._x_inlineBindings[t]&&e._x_inlineBindings[t].extract)return;let a=J(e,n);s(()=>a(l=>{l===void 0&&typeof n=="string"&&n.match(/\./)&&(l=""),P(()=>Xa(e,t,l,i))})),o(()=>{e._x_undoAddedClasses&&e._x_undoAddedClasses(),e._x_undoAddedStyles&&e._x_undoAddedStyles()})};Sl.inline=(e,{value:t,modifiers:i,expression:n})=>{t&&(e._x_inlineBindings||(e._x_inlineBindings={}),e._x_inlineBindings[t]={expression:n,extract:!1})};N("bind",Sl);function Nf(e,t){e._x_keyExpression=t}ja(()=>`[${Ae("data")}]`);N("data",(e,{expression:t},{cleanup:i})=>{if(Uf(e))return;t=t===""?"{}":t;let n={};Jn(n,e);let r={};Vp(r,n);let s=ie(e,t,{scope:r});(s===void 0||s===!0)&&(s={}),Jn(s,e);let o=Ce(s);Ca(o);let a=si(e,o);o.init&&ie(e,o.init),i(()=>{o.destroy&&ie(e,o.destroy),a()})});Zi((e,t)=>{e._x_dataStack&&(t._x_dataStack=e._x_dataStack,t.setAttribute("data-has-alpine-state",!0))});function Uf(e){return Ut?tr?!0:e.hasAttribute("data-has-alpine-state"):!1}N("show",(e,{modifiers:t,expression:i},{effect:n})=>{let r=J(e,i);e._x_doHide||(e._x_doHide=()=>{P(()=>{e.style.setProperty("display","none",t.includes("important")?"important":void 0)})}),e._x_doShow||(e._x_doShow=()=>{P(()=>{e.style.length===1&&e.style.display==="none"?e.removeAttribute("style"):e.style.removeProperty("display")})});let s=()=>{e._x_doHide(),e._x_isShown=!1},o=()=>{e._x_doShow(),e._x_isShown=!0},a=()=>setTimeout(o),l=Yn(d=>d?o():s(),d=>{typeof e._x_toggleAndCascadeWithTransitions=="function"?e._x_toggleAndCascadeWithTransitions(e,d,o,s):d?a():s()}),c,u=!0;n(()=>r(d=>{!u&&d===c||(t.includes("immediate")&&(d?a():s()),l(d),c=d,u=!1)}))});N("for",(e,{expression:t},{effect:i,cleanup:n})=>{let r=jf(t),s=J(e,r.items),o=J(e,e._x_keyExpression||"index");e._x_prevKeys=[],e._x_lookup={},i(()=>Hf(e,r,s,o)),n(()=>{Object.values(e._x_lookup).forEach(a=>P(()=>{$e(a),a.remove()})),delete e._x_prevKeys,delete e._x_lookup})});function Hf(e,t,i,n){let r=o=>typeof o=="object"&&!Array.isArray(o),s=e;i(o=>{qf(o)&&o>=0&&(o=Array.from(Array(o).keys(),g=>g+1)),o===void 0&&(o=[]);let a=e._x_lookup,l=e._x_prevKeys,c=[],u=[];if(r(o))o=Object.entries(o).map(([g,_])=>{let x=eo(t,_,g,o);n(E=>{u.includes(E)&&at("Duplicate key on x-for",e),u.push(E)},{scope:{index:g,...x}}),c.push(x)});else for(let g=0;g<o.length;g++){let _=eo(t,o[g],g,o);n(x=>{u.includes(x)&&at("Duplicate key on x-for",e),u.push(x)},{scope:{index:g,..._}}),c.push(_)}let d=[],p=[],f=[],b=[];for(let g=0;g<l.length;g++){let _=l[g];u.indexOf(_)===-1&&f.push(_)}l=l.filter(g=>!f.includes(g));let w="template";for(let g=0;g<u.length;g++){let _=u[g],x=l.indexOf(_);if(x===-1)l.splice(g,0,_),d.push([w,g]);else if(x!==g){let E=l.splice(g,1)[0],k=l.splice(x-1,1)[0];l.splice(g,0,k),l.splice(x,0,E),p.push([E,k])}else b.push(_);w=_}for(let g=0;g<f.length;g++){let _=f[g];_ in a&&(P(()=>{$e(a[_]),a[_].remove()}),delete a[_])}for(let g=0;g<p.length;g++){let[_,x]=p[g],E=a[_],k=a[x],S=document.createElement("div");P(()=>{k||at('x-for ":key" is undefined or invalid',s,x,a),k.after(S),E.after(k),k._x_currentIfEl&&k.after(k._x_currentIfEl),S.before(E),E._x_currentIfEl&&E.after(E._x_currentIfEl),S.remove()}),k._x_refreshXForScope(c[u.indexOf(x)])}for(let g=0;g<d.length;g++){let[_,x]=d[g],E=_==="template"?s:a[_];E._x_currentIfEl&&(E=E._x_currentIfEl);let k=c[x],S=u[x],O=document.importNode(s.content,!0).firstElementChild,R=Ce(k);si(O,R,s),O._x_refreshXForScope=H=>{Object.entries(H).forEach(([V,K])=>{R[V]=K})},P(()=>{E.after(O),jt(()=>At(O))()}),typeof S=="object"&&at("x-for key cannot be an object, it must be a string or an integer",s),a[S]=O}for(let g=0;g<b.length;g++)a[b[g]]._x_refreshXForScope(c[u.indexOf(b[g])]);s._x_prevKeys=u})}function jf(e){let t=/,([^,\}\]]*)(?:,([^,\}\]]*))?$/,i=/^\s*\(|\)\s*$/g,n=/([\s\S]*?)\s+(?:in|of)\s+([\s\S]*)/,r=e.match(n);if(!r)return;let s={};s.items=r[2].trim();let o=r[1].replace(i,"").trim(),a=o.match(t);return a?(s.item=o.replace(t,"").trim(),s.index=a[1].trim(),a[2]&&(s.collection=a[2].trim())):s.item=o,s}function eo(e,t,i,n){let r={};return/^\[.*\]$/.test(e.item)&&Array.isArray(t)?e.item.replace("[","").replace("]","").split(",").map(o=>o.trim()).forEach((o,a)=>{r[o]=t[a]}):/^\{.*\}$/.test(e.item)&&!Array.isArray(t)&&typeof t=="object"?e.item.replace("{","").replace("}","").split(",").map(o=>o.trim()).forEach(o=>{r[o]=t[o]}):r[e.item]=t,e.index&&(r[e.index]=i),e.collection&&(r[e.collection]=n),r}function qf(e){return!Array.isArray(e)&&!isNaN(e)}function Cl(){}Cl.inline=(e,{expression:t},{cleanup:i})=>{let n=Gi(e);n._x_refs||(n._x_refs={}),n._x_refs[t]=e,i(()=>delete n._x_refs[t])};N("ref",Cl);N("if",(e,{expression:t},{effect:i,cleanup:n})=>{e.tagName.toLowerCase()!=="template"&&at("x-if can only be used on a <template> tag",e);let r=J(e,t),s=()=>{if(e._x_currentIfEl)return e._x_currentIfEl;let a=e.content.cloneNode(!0).firstElementChild;return si(a,{},e),P(()=>{e.after(a),jt(()=>At(a))()}),e._x_currentIfEl=a,e._x_undoIf=()=>{P(()=>{$e(a),a.remove()}),delete e._x_currentIfEl},a},o=()=>{e._x_undoIf&&(e._x_undoIf(),delete e._x_undoIf)};i(()=>r(a=>{a?s():o()})),n(()=>e._x_undoIf&&e._x_undoIf())});N("id",(e,{expression:t},{evaluate:i})=>{i(t).forEach(r=>Rf(e,r))});Zi((e,t)=>{e._x_ids&&(t._x_ids=e._x_ids)});Or(Ma("@",Pa(Ae("on:"))));N("on",jt((e,{value:t,modifiers:i,expression:n},{cleanup:r})=>{let s=n?J(e,n):()=>{};e.tagName.toLowerCase()==="template"&&(e._x_forwardEvents||(e._x_forwardEvents=[]),e._x_forwardEvents.includes(t)||e._x_forwardEvents.push(t));let o=rr(e,t,i,a=>{s(()=>{},{scope:{$event:a},params:[a]})});r(()=>o())}));rn("Collapse","collapse","collapse");rn("Intersect","intersect","intersect");rn("Focus","trap","focus");rn("Mask","mask","mask");function rn(e,t,i){N(t,n=>at(`You can't use [x-${t}] without first installing the "${e}" plugin here: https://alpinejs.dev/plugins/${i}`,n))}ai.setEvaluator(Ra);ai.setReactivityEngine({reactive:qr,effect:ef,release:nf,raw:M});var Vf=ai,Vr=Vf;function Wf(e){let t=()=>{let i,n;try{n=localStorage}catch(r){console.error(r),console.warn("Alpine: $persist is using temporary storage since localStorage is unavailable.");let s=new Map;n={getItem:s.get.bind(s),setItem:s.set.bind(s)}}return e.interceptor((r,s,o,a,l)=>{let c=i||`_x_${a}`,u=io(c,n)?no(c,n):r;return o(u),e.effect(()=>{let d=s();ro(c,d,n),o(d)}),u},r=>{r.as=s=>(i=s,r),r.using=s=>(n=s,r)})};Object.defineProperty(e,"$persist",{get:()=>t()}),e.magic("persist",t),e.persist=(i,{get:n,set:r},s=localStorage)=>{let o=io(i,s)?no(i,s):n();r(o),e.effect(()=>{let a=n();ro(i,a,s),r(a)})}}function io(e,t){return t.getItem(e)!==null}function no(e,t){let i=t.getItem(e,t);if(i!==void 0)return JSON.parse(i)}function ro(e,t,i){i.setItem(e,JSON.stringify(t))}var Jf=Wf,so=kl;function kl(){var e=[].slice.call(arguments),t=!1;typeof e[0]=="boolean"&&(t=e.shift());var i=e[0];if(oo(i))throw new Error("extendee must be an object");for(var n=e.slice(1),r=n.length,s=0;s<r;s++){var o=n[s];for(var a in o)if(Object.prototype.hasOwnProperty.call(o,a)){var l=o[a];if(t&&Kf(l)){var c=Array.isArray(l)?[]:{};i[a]=kl(!0,Object.prototype.hasOwnProperty.call(i,a)&&!oo(i[a])?i[a]:c,l)}else i[a]=l}}return i}function Kf(e){return Array.isArray(e)||{}.toString.call(e)=="[object Object]"}function oo(e){return!e||typeof e!="object"&&typeof e!="function"}function Qf(e){return e&&e.__esModule?e.default:e}class ao{on(t,i){return this._callbacks=this._callbacks||{},this._callbacks[t]||(this._callbacks[t]=[]),this._callbacks[t].push(i),this}emit(t,...i){this._callbacks=this._callbacks||{};let n=this._callbacks[t];if(n)for(let r of n)r.apply(this,i);return this.element&&this.element.dispatchEvent(this.makeEvent("dropzone:"+t,{args:i})),this}makeEvent(t,i){let n={bubbles:!0,cancelable:!0,detail:i};if(typeof window.CustomEvent=="function")return new CustomEvent(t,n);var r=document.createEvent("CustomEvent");return r.initCustomEvent(t,n.bubbles,n.cancelable,n.detail),r}off(t,i){if(!this._callbacks||arguments.length===0)return this._callbacks={},this;let n=this._callbacks[t];if(!n)return this;if(arguments.length===1)return delete this._callbacks[t],this;for(let r=0;r<n.length;r++)if(n[r]===i){n.splice(r,1);break}return this}}var Al={};Al=`<div class="dz-preview dz-file-preview">
  <div class="dz-image"><img data-dz-thumbnail=""></div>
  <div class="dz-details">
    <div class="dz-size"><span data-dz-size=""></span></div>
    <div class="dz-filename"><span data-dz-name=""></span></div>
  </div>
  <div class="dz-progress">
    <span class="dz-upload" data-dz-uploadprogress=""></span>
  </div>
  <div class="dz-error-message"><span data-dz-errormessage=""></span></div>
  <div class="dz-success-mark">
    <svg width="54" height="54" viewBox="0 0 54 54" fill="white" xmlns="http://www.w3.org/2000/svg">
      <path d="M10.2071 29.7929L14.2929 25.7071C14.6834 25.3166 15.3166 25.3166 15.7071 25.7071L21.2929 31.2929C21.6834 31.6834 22.3166 31.6834 22.7071 31.2929L38.2929 15.7071C38.6834 15.3166 39.3166 15.3166 39.7071 15.7071L43.7929 19.7929C44.1834 20.1834 44.1834 20.8166 43.7929 21.2071L22.7071 42.2929C22.3166 42.6834 21.6834 42.6834 21.2929 42.2929L10.2071 31.2071C9.81658 30.8166 9.81658 30.1834 10.2071 29.7929Z"></path>
    </svg>
  </div>
  <div class="dz-error-mark">
    <svg width="54" height="54" viewBox="0 0 54 54" fill="white" xmlns="http://www.w3.org/2000/svg">
      <path d="M26.2929 20.2929L19.2071 13.2071C18.8166 12.8166 18.1834 12.8166 17.7929 13.2071L13.2071 17.7929C12.8166 18.1834 12.8166 18.8166 13.2071 19.2071L20.2929 26.2929C20.6834 26.6834 20.6834 27.3166 20.2929 27.7071L13.2071 34.7929C12.8166 35.1834 12.8166 35.8166 13.2071 36.2071L17.7929 40.7929C18.1834 41.1834 18.8166 41.1834 19.2071 40.7929L26.2929 33.7071C26.6834 33.3166 27.3166 33.3166 27.7071 33.7071L34.7929 40.7929C35.1834 41.1834 35.8166 41.1834 36.2071 40.7929L40.7929 36.2071C41.1834 35.8166 41.1834 35.1834 40.7929 34.7929L33.7071 27.7071C33.3166 27.3166 33.3166 26.6834 33.7071 26.2929L40.7929 19.2071C41.1834 18.8166 41.1834 18.1834 40.7929 17.7929L36.2071 13.2071C35.8166 12.8166 35.1834 12.8166 34.7929 13.2071L27.7071 20.2929C27.3166 20.6834 26.6834 20.6834 26.2929 20.2929Z"></path>
    </svg>
  </div>
</div>
`;let Xf={url:null,method:"post",withCredentials:!1,timeout:null,parallelUploads:2,uploadMultiple:!1,chunking:!1,forceChunking:!1,chunkSize:2097152,parallelChunkUploads:!1,retryChunks:!1,retryChunksLimit:3,maxFilesize:256,paramName:"file",createImageThumbnails:!0,maxThumbnailFilesize:10,thumbnailWidth:120,thumbnailHeight:120,thumbnailMethod:"crop",resizeWidth:null,resizeHeight:null,resizeMimeType:null,resizeQuality:.8,resizeMethod:"contain",filesizeBase:1e3,maxFiles:null,headers:null,defaultHeaders:!0,clickable:!0,ignoreHiddenFiles:!0,acceptedFiles:null,acceptedMimeTypes:null,autoProcessQueue:!0,autoQueue:!0,addRemoveLinks:!1,previewsContainer:null,disablePreviews:!1,hiddenInputContainer:"body",capture:null,renameFilename:null,renameFile:null,forceFallback:!1,dictDefaultMessage:"Drop files here to upload",dictFallbackMessage:"Your browser does not support drag'n'drop file uploads.",dictFallbackText:"Please use the fallback form below to upload your files like in the olden days.",dictFileTooBig:"File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.",dictInvalidFileType:"You can't upload files of this type.",dictResponseError:"Server responded with {{statusCode}} code.",dictCancelUpload:"Cancel upload",dictUploadCanceled:"Upload canceled.",dictCancelUploadConfirmation:"Are you sure you want to cancel this upload?",dictRemoveFile:"Remove file",dictRemoveFileConfirmation:null,dictMaxFilesExceeded:"You can not upload any more files.",dictFileSizeUnits:{tb:"TB",gb:"GB",mb:"MB",kb:"KB",b:"b"},init(){},params(e,t,i){if(i)return{dzuuid:i.file.upload.uuid,dzchunkindex:i.index,dztotalfilesize:i.file.size,dzchunksize:this.options.chunkSize,dztotalchunkcount:i.file.upload.totalChunkCount,dzchunkbyteoffset:i.index*this.options.chunkSize}},accept(e,t){return t()},chunksUploaded:function(e,t){t()},binaryBody:!1,fallback(){let e;this.element.className=`${this.element.className} dz-browser-not-supported`;for(let i of this.element.getElementsByTagName("div"))if(/(^| )dz-message($| )/.test(i.className)){e=i,i.className="dz-message";break}e||(e=y.createElement('<div class="dz-message"><span></span></div>'),this.element.appendChild(e));let t=e.getElementsByTagName("span")[0];return t&&(t.textContent!=null?t.textContent=this.options.dictFallbackMessage:t.innerText!=null&&(t.innerText=this.options.dictFallbackMessage)),this.element.appendChild(this.getFallbackForm())},resize(e,t,i,n){let r={srcX:0,srcY:0,srcWidth:e.width,srcHeight:e.height},s=e.width/e.height;t==null&&i==null?(t=r.srcWidth,i=r.srcHeight):t==null?t=i*s:i==null&&(i=t/s),t=Math.min(t,r.srcWidth),i=Math.min(i,r.srcHeight);let o=t/i;if(r.srcWidth>t||r.srcHeight>i)if(n==="crop")s>o?(r.srcHeight=e.height,r.srcWidth=r.srcHeight*o):(r.srcWidth=e.width,r.srcHeight=r.srcWidth/o);else if(n==="contain")s>o?i=t/s:t=i*s;else throw new Error(`Unknown resizeMethod '${n}'`);return r.srcX=(e.width-r.srcWidth)/2,r.srcY=(e.height-r.srcHeight)/2,r.trgWidth=t,r.trgHeight=i,r},transformFile(e,t){return(this.options.resizeWidth||this.options.resizeHeight)&&e.type.match(/image.*/)?this.resizeImage(e,this.options.resizeWidth,this.options.resizeHeight,this.options.resizeMethod,t):t(e)},previewTemplate:Qf(Al),drop(e){return this.element.classList.remove("dz-drag-hover")},dragstart(e){},dragend(e){return this.element.classList.remove("dz-drag-hover")},dragenter(e){return this.element.classList.add("dz-drag-hover")},dragover(e){return this.element.classList.add("dz-drag-hover")},dragleave(e){return this.element.classList.remove("dz-drag-hover")},paste(e){},reset(){return this.element.classList.remove("dz-started")},addedfile(e){if(this.element===this.previewsContainer&&this.element.classList.add("dz-started"),this.previewsContainer&&!this.options.disablePreviews){e.previewElement=y.createElement(this.options.previewTemplate.trim()),e.previewTemplate=e.previewElement,this.previewsContainer.appendChild(e.previewElement);for(var t of e.previewElement.querySelectorAll("[data-dz-name]"))t.textContent=e.name;for(t of e.previewElement.querySelectorAll("[data-dz-size]"))t.innerHTML=this.filesize(e.size);this.options.addRemoveLinks&&(e._removeLink=y.createElement(`<a class="dz-remove" href="javascript:undefined;" data-dz-remove>${this.options.dictRemoveFile}</a>`),e.previewElement.appendChild(e._removeLink));let i=n=>(n.preventDefault(),n.stopPropagation(),e.status===y.UPLOADING?y.confirm(this.options.dictCancelUploadConfirmation,()=>this.removeFile(e)):this.options.dictRemoveFileConfirmation?y.confirm(this.options.dictRemoveFileConfirmation,()=>this.removeFile(e)):this.removeFile(e));for(let n of e.previewElement.querySelectorAll("[data-dz-remove]"))n.addEventListener("click",i)}},removedfile(e){return e.previewElement!=null&&e.previewElement.parentNode!=null&&e.previewElement.parentNode.removeChild(e.previewElement),this._updateMaxFilesReachedClass()},thumbnail(e,t){if(e.previewElement){e.previewElement.classList.remove("dz-file-preview");for(let i of e.previewElement.querySelectorAll("[data-dz-thumbnail]"))i.alt=e.name,i.src=t;return setTimeout(()=>e.previewElement.classList.add("dz-image-preview"),1)}},error(e,t){if(e.previewElement){e.previewElement.classList.add("dz-error"),typeof t!="string"&&t.error&&(t=t.error);for(let i of e.previewElement.querySelectorAll("[data-dz-errormessage]"))i.textContent=t}},errormultiple(){},processing(e){if(e.previewElement&&(e.previewElement.classList.add("dz-processing"),e._removeLink))return e._removeLink.innerHTML=this.options.dictCancelUpload},processingmultiple(){},uploadprogress(e,t,i){if(e.previewElement)for(let n of e.previewElement.querySelectorAll("[data-dz-uploadprogress]"))n.nodeName==="PROGRESS"?n.value=t:n.style.width=`${t}%`},totaluploadprogress(){},sending(){},sendingmultiple(){},success(e){if(e.previewElement)return e.previewElement.classList.add("dz-success")},successmultiple(){},canceled(e){return this.emit("error",e,this.options.dictUploadCanceled)},canceledmultiple(){},complete(e){if(e._removeLink&&(e._removeLink.innerHTML=this.options.dictRemoveFile),e.previewElement)return e.previewElement.classList.add("dz-complete")},completemultiple(){},maxfilesexceeded(){},maxfilesreached(){},queuecomplete(){},addedfiles(){}};var Gf=Xf;class y extends ao{static initClass(){this.prototype.Emitter=ao,this.prototype.events=["drop","dragstart","dragend","dragenter","dragover","dragleave","addedfile","addedfiles","removedfile","thumbnail","error","errormultiple","processing","processingmultiple","uploadprogress","totaluploadprogress","sending","sendingmultiple","success","successmultiple","canceled","canceledmultiple","complete","completemultiple","reset","maxfilesexceeded","maxfilesreached","queuecomplete"],this.prototype._thumbnailQueue=[],this.prototype._processingThumbnail=!1}getAcceptedFiles(){return this.files.filter(t=>t.accepted).map(t=>t)}getRejectedFiles(){return this.files.filter(t=>!t.accepted).map(t=>t)}getFilesWithStatus(t){return this.files.filter(i=>i.status===t).map(i=>i)}getQueuedFiles(){return this.getFilesWithStatus(y.QUEUED)}getUploadingFiles(){return this.getFilesWithStatus(y.UPLOADING)}getAddedFiles(){return this.getFilesWithStatus(y.ADDED)}getActiveFiles(){return this.files.filter(t=>t.status===y.UPLOADING||t.status===y.QUEUED).map(t=>t)}init(){if(this.element.tagName==="form"&&this.element.setAttribute("enctype","multipart/form-data"),this.element.classList.contains("dropzone")&&!this.element.querySelector(".dz-message")&&this.element.appendChild(y.createElement(`<div class="dz-default dz-message"><button class="dz-button" type="button">${this.options.dictDefaultMessage}</button></div>`)),this.clickableElements.length){let n=()=>{this.hiddenFileInput&&this.hiddenFileInput.parentNode.removeChild(this.hiddenFileInput),this.hiddenFileInput=document.createElement("input"),this.hiddenFileInput.setAttribute("type","file"),(this.options.maxFiles===null||this.options.maxFiles>1)&&this.hiddenFileInput.setAttribute("multiple","multiple"),this.hiddenFileInput.className="dz-hidden-input",this.options.acceptedFiles!==null&&this.hiddenFileInput.setAttribute("accept",this.options.acceptedFiles),this.options.capture!==null&&this.hiddenFileInput.setAttribute("capture",this.options.capture),this.hiddenFileInput.setAttribute("tabindex","-1"),this.hiddenFileInput.style.visibility="hidden",this.hiddenFileInput.style.position="absolute",this.hiddenFileInput.style.top="0",this.hiddenFileInput.style.left="0",this.hiddenFileInput.style.height="0",this.hiddenFileInput.style.width="0",y.getElement(this.options.hiddenInputContainer,"hiddenInputContainer").appendChild(this.hiddenFileInput),this.hiddenFileInput.addEventListener("change",()=>{let{files:r}=this.hiddenFileInput;if(r.length)for(let s of r)this.addFile(s);this.emit("addedfiles",r),n()})};n()}this.URL=window.URL!==null?window.URL:window.webkitURL;for(let n of this.events)this.on(n,this.options[n]);this.on("uploadprogress",()=>this.updateTotalUploadProgress()),this.on("removedfile",()=>this.updateTotalUploadProgress()),this.on("canceled",n=>this.emit("complete",n)),this.on("complete",n=>{if(this.getAddedFiles().length===0&&this.getUploadingFiles().length===0&&this.getQueuedFiles().length===0)return setTimeout(()=>this.emit("queuecomplete"),0)});const t=function(n){if(n.dataTransfer.types){for(var r=0;r<n.dataTransfer.types.length;r++)if(n.dataTransfer.types[r]==="Files")return!0}return!1};let i=function(n){if(t(n))return n.stopPropagation(),n.preventDefault?n.preventDefault():n.returnValue=!1};return this.listeners=[{element:this.element,events:{dragstart:n=>this.emit("dragstart",n),dragenter:n=>(i(n),this.emit("dragenter",n)),dragover:n=>{let r;try{r=n.dataTransfer.effectAllowed}catch{}return n.dataTransfer.dropEffect=r==="move"||r==="linkMove"?"move":"copy",i(n),this.emit("dragover",n)},dragleave:n=>this.emit("dragleave",n),drop:n=>(i(n),this.drop(n)),dragend:n=>this.emit("dragend",n)}}],this.clickableElements.forEach(n=>this.listeners.push({element:n,events:{click:r=>((n!==this.element||r.target===this.element||y.elementInside(r.target,this.element.querySelector(".dz-message")))&&this.hiddenFileInput.click(),!0)}})),this.enable(),this.options.init.call(this)}destroy(){return this.disable(),this.removeAllFiles(!0),this.hiddenFileInput!=null&&this.hiddenFileInput.parentNode&&(this.hiddenFileInput.parentNode.removeChild(this.hiddenFileInput),this.hiddenFileInput=null),delete this.element.dropzone,y.instances.splice(y.instances.indexOf(this),1)}updateTotalUploadProgress(){let t,i=0,n=0;if(this.getActiveFiles().length){for(let s of this.getActiveFiles())i+=s.upload.bytesSent,n+=s.upload.total;t=100*i/n}else t=100;return this.emit("totaluploadprogress",t,n,i)}_getParamName(t){return typeof this.options.paramName=="function"?this.options.paramName(t):`${this.options.paramName}${this.options.uploadMultiple?`[${t}]`:""}`}_renameFile(t){return typeof this.options.renameFile!="function"?t.name:this.options.renameFile(t)}getFallbackForm(){let t,i;if(t=this.getExistingFallback())return t;let n='<div class="dz-fallback">';this.options.dictFallbackText&&(n+=`<p>${this.options.dictFallbackText}</p>`),n+=`<input type="file" name="${this._getParamName(0)}" ${this.options.uploadMultiple?'multiple="multiple"':void 0} /><input type="submit" value="Upload!"></div>`;let r=y.createElement(n);return this.element.tagName!=="FORM"?(i=y.createElement(`<form action="${this.options.url}" enctype="multipart/form-data" method="${this.options.method}"></form>`),i.appendChild(r)):(this.element.setAttribute("enctype","multipart/form-data"),this.element.setAttribute("method",this.options.method)),i??r}getExistingFallback(){let t=function(n){for(let r of n)if(/(^| )fallback($| )/.test(r.className))return r};for(let n of["div","form"]){var i;if(i=t(this.element.getElementsByTagName(n)))return i}}setupEventListeners(){return this.listeners.map(t=>(()=>{let i=[];for(let n in t.events){let r=t.events[n];i.push(t.element.addEventListener(n,r,!1))}return i})())}removeEventListeners(){return this.listeners.map(t=>(()=>{let i=[];for(let n in t.events){let r=t.events[n];i.push(t.element.removeEventListener(n,r,!1))}return i})())}disable(){return this.clickableElements.forEach(t=>t.classList.remove("dz-clickable")),this.removeEventListeners(),this.disabled=!0,this.files.map(t=>this.cancelUpload(t))}enable(){return delete this.disabled,this.clickableElements.forEach(t=>t.classList.add("dz-clickable")),this.setupEventListeners()}filesize(t){let i=0,n="b";if(t>0){let r=["tb","gb","mb","kb","b"];for(let s=0;s<r.length;s++){let o=r[s],a=Math.pow(this.options.filesizeBase,4-s)/10;if(t>=a){i=t/Math.pow(this.options.filesizeBase,4-s),n=o;break}}i=Math.round(10*i)/10}return`<strong>${i}</strong> ${this.options.dictFileSizeUnits[n]}`}_updateMaxFilesReachedClass(){return this.options.maxFiles!=null&&this.getAcceptedFiles().length>=this.options.maxFiles?(this.getAcceptedFiles().length===this.options.maxFiles&&this.emit("maxfilesreached",this.files),this.element.classList.add("dz-max-files-reached")):this.element.classList.remove("dz-max-files-reached")}drop(t){if(!t.dataTransfer)return;this.emit("drop",t);let i=[];for(let n=0;n<t.dataTransfer.files.length;n++)i[n]=t.dataTransfer.files[n];if(i.length){let{items:n}=t.dataTransfer;n&&n.length&&n[0].webkitGetAsEntry!=null?this._addFilesFromItems(n):this.handleFiles(i)}this.emit("addedfiles",i)}paste(t){if(im(t!=null?t.clipboardData:void 0,n=>n.items)==null)return;this.emit("paste",t);let{items:i}=t.clipboardData;if(i.length)return this._addFilesFromItems(i)}handleFiles(t){for(let i of t)this.addFile(i)}_addFilesFromItems(t){return(()=>{let i=[];for(let r of t){var n;r.webkitGetAsEntry!=null&&(n=r.webkitGetAsEntry())?n.isFile?i.push(this.addFile(r.getAsFile())):n.isDirectory?i.push(this._addFilesFromDirectory(n,n.name)):i.push(void 0):r.getAsFile!=null&&(r.kind==null||r.kind==="file")?i.push(this.addFile(r.getAsFile())):i.push(void 0)}return i})()}_addFilesFromDirectory(t,i){let n=t.createReader(),r=o=>nm(console,"log",a=>a.log(o));var s=()=>n.readEntries(o=>{if(o.length>0){for(let a of o)a.isFile?a.file(l=>{if(!(this.options.ignoreHiddenFiles&&l.name.substring(0,1)==="."))return l.fullPath=`${i}/${l.name}`,this.addFile(l)}):a.isDirectory&&this._addFilesFromDirectory(a,`${i}/${a.name}`);s()}return null},r);return s()}accept(t,i){this.options.maxFilesize&&t.size>this.options.maxFilesize*1048576?i(this.options.dictFileTooBig.replace("{{filesize}}",Math.round(t.size/1024/10.24)/100).replace("{{maxFilesize}}",this.options.maxFilesize)):y.isValidFile(t,this.options.acceptedFiles)?this.options.maxFiles!=null&&this.getAcceptedFiles().length>=this.options.maxFiles?(i(this.options.dictMaxFilesExceeded.replace("{{maxFiles}}",this.options.maxFiles)),this.emit("maxfilesexceeded",t)):this.options.accept.call(this,t,i):i(this.options.dictInvalidFileType)}addFile(t){t.upload={uuid:y.uuidv4(),progress:0,total:t.size,bytesSent:0,filename:this._renameFile(t)},this.files.push(t),t.status=y.ADDED,this.emit("addedfile",t),this._enqueueThumbnail(t),this.accept(t,i=>{i?(t.accepted=!1,this._errorProcessing([t],i)):(t.accepted=!0,this.options.autoQueue&&this.enqueueFile(t)),this._updateMaxFilesReachedClass()})}enqueueFiles(t){for(let i of t)this.enqueueFile(i);return null}enqueueFile(t){if(t.status===y.ADDED&&t.accepted===!0){if(t.status=y.QUEUED,this.options.autoProcessQueue)return setTimeout(()=>this.processQueue(),0)}else throw new Error("This file can't be queued because it has already been processed or was rejected.")}_enqueueThumbnail(t){if(this.options.createImageThumbnails&&t.type.match(/image.*/)&&t.size<=this.options.maxThumbnailFilesize*1048576)return this._thumbnailQueue.push(t),setTimeout(()=>this._processThumbnailQueue(),0)}_processThumbnailQueue(){if(this._processingThumbnail||this._thumbnailQueue.length===0)return;this._processingThumbnail=!0;let t=this._thumbnailQueue.shift();return this.createThumbnail(t,this.options.thumbnailWidth,this.options.thumbnailHeight,this.options.thumbnailMethod,!0,i=>(this.emit("thumbnail",t,i),this._processingThumbnail=!1,this._processThumbnailQueue()))}removeFile(t){if(t.status===y.UPLOADING&&this.cancelUpload(t),this.files=Yf(this.files,t),this.emit("removedfile",t),this.files.length===0)return this.emit("reset")}removeAllFiles(t){t==null&&(t=!1);for(let i of this.files.slice())(i.status!==y.UPLOADING||t)&&this.removeFile(i);return null}resizeImage(t,i,n,r,s){return this.createThumbnail(t,i,n,r,!0,(o,a)=>{if(a==null)return s(t);{let{resizeMimeType:l}=this.options;l==null&&(l=t.type);let c=a.toDataURL(l,this.options.resizeQuality);return(l==="image/jpeg"||l==="image/jpg")&&(c=Tl.restore(t.dataURL,c)),s(y.dataURItoBlob(c))}})}createThumbnail(t,i,n,r,s,o){let a=new FileReader;a.onload=()=>{if(t.dataURL=a.result,t.type==="image/svg+xml"){o!=null&&o(a.result);return}this.createThumbnailFromUrl(t,i,n,r,s,o)},a.readAsDataURL(t)}displayExistingFile(t,i,n,r,s=!0){if(this.emit("addedfile",t),this.emit("complete",t),!s)this.emit("thumbnail",t,i),n&&n();else{let o=a=>{this.emit("thumbnail",t,a),n&&n()};t.dataURL=i,this.createThumbnailFromUrl(t,this.options.thumbnailWidth,this.options.thumbnailHeight,this.options.thumbnailMethod,this.options.fixOrientation,o,r)}}createThumbnailFromUrl(t,i,n,r,s,o,a){let l=document.createElement("img");return a&&(l.crossOrigin=a),s=getComputedStyle(document.body).imageOrientation=="from-image"?!1:s,l.onload=()=>{let c=u=>u(1);return typeof EXIF<"u"&&EXIF!==null&&s&&(c=u=>EXIF.getData(l,function(){return u(EXIF.getTag(this,"Orientation"))})),c(u=>{t.width=l.width,t.height=l.height;let d=this.options.resize.call(this,t,i,n,r),p=document.createElement("canvas"),f=p.getContext("2d");switch(p.width=d.trgWidth,p.height=d.trgHeight,u>4&&(p.width=d.trgHeight,p.height=d.trgWidth),u){case 2:f.translate(p.width,0),f.scale(-1,1);break;case 3:f.translate(p.width,p.height),f.rotate(Math.PI);break;case 4:f.translate(0,p.height),f.scale(1,-1);break;case 5:f.rotate(.5*Math.PI),f.scale(1,-1);break;case 6:f.rotate(.5*Math.PI),f.translate(0,-p.width);break;case 7:f.rotate(.5*Math.PI),f.translate(p.height,-p.width),f.scale(-1,1);break;case 8:f.rotate(-.5*Math.PI),f.translate(-p.height,0);break}em(f,l,d.srcX!=null?d.srcX:0,d.srcY!=null?d.srcY:0,d.srcWidth,d.srcHeight,d.trgX!=null?d.trgX:0,d.trgY!=null?d.trgY:0,d.trgWidth,d.trgHeight);let b=p.toDataURL("image/png");if(o!=null)return o(b,p)})},o!=null&&(l.onerror=o),l.src=t.dataURL}processQueue(){let{parallelUploads:t}=this.options,i=this.getUploadingFiles().length,n=i;if(i>=t)return;let r=this.getQueuedFiles();if(r.length>0){if(this.options.uploadMultiple)return this.processFiles(r.slice(0,t-i));for(;n<t;){if(!r.length)return;this.processFile(r.shift()),n++}}}processFile(t){return this.processFiles([t])}processFiles(t){for(let i of t)i.processing=!0,i.status=y.UPLOADING,this.emit("processing",i);return this.options.uploadMultiple&&this.emit("processingmultiple",t),this.uploadFiles(t)}_getFilesWithXhr(t){return this.files.filter(i=>i.xhr===t).map(i=>i)}cancelUpload(t){if(t.status===y.UPLOADING){let i=this._getFilesWithXhr(t.xhr);for(let n of i)n.status=y.CANCELED;typeof t.xhr<"u"&&t.xhr.abort();for(let n of i)this.emit("canceled",n);this.options.uploadMultiple&&this.emit("canceledmultiple",i)}else(t.status===y.ADDED||t.status===y.QUEUED)&&(t.status=y.CANCELED,this.emit("canceled",t),this.options.uploadMultiple&&this.emit("canceledmultiple",[t]));if(this.options.autoProcessQueue)return this.processQueue()}resolveOption(t,...i){return typeof t=="function"?t.apply(this,i):t}uploadFile(t){return this.uploadFiles([t])}uploadFiles(t){this._transformFiles(t,i=>{if(this.options.chunking){let n=i[0];t[0].upload.chunked=this.options.chunking&&(this.options.forceChunking||n.size>this.options.chunkSize),t[0].upload.totalChunkCount=Math.ceil(n.size/this.options.chunkSize)}if(t[0].upload.chunked){let n=t[0],r=i[0];n.upload.chunks=[];let s=()=>{let o=0;for(;n.upload.chunks[o]!==void 0;)o++;if(o>=n.upload.totalChunkCount)return;let a=o*this.options.chunkSize,l=Math.min(a+this.options.chunkSize,r.size),c={name:this._getParamName(0),data:r.webkitSlice?r.webkitSlice(a,l):r.slice(a,l),filename:n.upload.filename,chunkIndex:o};n.upload.chunks[o]={file:n,index:o,dataBlock:c,status:y.UPLOADING,progress:0,retries:0},this._uploadData(t,[c])};if(n.upload.finishedChunkUpload=(o,a)=>{let l=!0;o.status=y.SUCCESS,o.dataBlock=null,o.response=o.xhr.responseText,o.responseHeaders=o.xhr.getAllResponseHeaders(),o.xhr=null;for(let c=0;c<n.upload.totalChunkCount;c++){if(n.upload.chunks[c]===void 0)return s();n.upload.chunks[c].status!==y.SUCCESS&&(l=!1)}l&&this.options.chunksUploaded(n,()=>{this._finished(t,a,null)})},this.options.parallelChunkUploads)for(let o=0;o<n.upload.totalChunkCount;o++)s();else s()}else{let n=[];for(let r=0;r<t.length;r++)n[r]={name:this._getParamName(r),data:i[r],filename:t[r].upload.filename};this._uploadData(t,n)}})}_getChunk(t,i){for(let n=0;n<t.upload.totalChunkCount;n++)if(t.upload.chunks[n]!==void 0&&t.upload.chunks[n].xhr===i)return t.upload.chunks[n]}_uploadData(t,i){let n=new XMLHttpRequest;for(let c of t)c.xhr=n;t[0].upload.chunked&&(t[0].upload.chunks[i[0].chunkIndex].xhr=n);let r=this.resolveOption(this.options.method,t,i),s=this.resolveOption(this.options.url,t,i);n.open(r,s,!0),this.resolveOption(this.options.timeout,t)&&(n.timeout=this.resolveOption(this.options.timeout,t)),n.withCredentials=!!this.options.withCredentials,n.onload=c=>{this._finishedUploading(t,n,c)},n.ontimeout=()=>{this._handleUploadError(t,n,`Request timedout after ${this.options.timeout/1e3} seconds`)},n.onerror=()=>{this._handleUploadError(t,n)};let a=n.upload!=null?n.upload:n;a.onprogress=c=>this._updateFilesUploadProgress(t,n,c);let l=this.options.defaultHeaders?{Accept:"application/json","Cache-Control":"no-cache","X-Requested-With":"XMLHttpRequest"}:{};this.options.binaryBody&&(l["Content-Type"]=t[0].type),this.options.headers&&so(l,this.options.headers);for(let c in l){let u=l[c];u&&n.setRequestHeader(c,u)}if(this.options.binaryBody){for(let c of t)this.emit("sending",c,n);this.options.uploadMultiple&&this.emit("sendingmultiple",t,n),this.submitRequest(n,null,t)}else{let c=new FormData;if(this.options.params){let u=this.options.params;typeof u=="function"&&(u=u.call(this,t,n,t[0].upload.chunked?this._getChunk(t[0],n):null));for(let d in u){let p=u[d];if(Array.isArray(p))for(let f=0;f<p.length;f++)c.append(d,p[f]);else c.append(d,p)}}for(let u of t)this.emit("sending",u,n,c);this.options.uploadMultiple&&this.emit("sendingmultiple",t,n,c),this._addFormElementData(c);for(let u=0;u<i.length;u++){let d=i[u];c.append(d.name,d.data,d.filename)}this.submitRequest(n,c,t)}}_transformFiles(t,i){let n=[],r=0;for(let s=0;s<t.length;s++)this.options.transformFile.call(this,t[s],o=>{n[s]=o,++r===t.length&&i(n)})}_addFormElementData(t){if(this.element.tagName==="FORM")for(let i of this.element.querySelectorAll("input, textarea, select, button")){let n=i.getAttribute("name"),r=i.getAttribute("type");if(r&&(r=r.toLowerCase()),!(typeof n>"u"||n===null))if(i.tagName==="SELECT"&&i.hasAttribute("multiple"))for(let s of i.options)s.selected&&t.append(n,s.value);else(!r||r!=="checkbox"&&r!=="radio"||i.checked)&&t.append(n,i.value)}}_updateFilesUploadProgress(t,i,n){if(t[0].upload.chunked){let r=t[0],s=this._getChunk(r,i);n?(s.progress=100*n.loaded/n.total,s.total=n.total,s.bytesSent=n.loaded):(s.progress=100,s.bytesSent=s.total),r.upload.progress=0,r.upload.total=0,r.upload.bytesSent=0;for(let o=0;o<r.upload.totalChunkCount;o++)r.upload.chunks[o]&&typeof r.upload.chunks[o].progress<"u"&&(r.upload.progress+=r.upload.chunks[o].progress,r.upload.total+=r.upload.chunks[o].total,r.upload.bytesSent+=r.upload.chunks[o].bytesSent);r.upload.progress=r.upload.progress/r.upload.totalChunkCount,this.emit("uploadprogress",r,r.upload.progress,r.upload.bytesSent)}else for(let r of t)r.upload.total&&r.upload.bytesSent&&r.upload.bytesSent==r.upload.total||(n?(r.upload.progress=100*n.loaded/n.total,r.upload.total=n.total,r.upload.bytesSent=n.loaded):(r.upload.progress=100,r.upload.bytesSent=r.upload.total),this.emit("uploadprogress",r,r.upload.progress,r.upload.bytesSent))}_finishedUploading(t,i,n){let r;if(t[0].status!==y.CANCELED&&i.readyState===4){if(i.responseType!=="arraybuffer"&&i.responseType!=="blob"&&(r=i.responseText,i.getResponseHeader("content-type")&&~i.getResponseHeader("content-type").indexOf("application/json")))try{r=JSON.parse(r)}catch(s){n=s,r="Invalid JSON response from server."}this._updateFilesUploadProgress(t,i),200<=i.status&&i.status<300?t[0].upload.chunked?t[0].upload.finishedChunkUpload(this._getChunk(t[0],i),r):this._finished(t,r,n):this._handleUploadError(t,i,r)}}_handleUploadError(t,i,n){if(t[0].status!==y.CANCELED){if(t[0].upload.chunked&&this.options.retryChunks){let r=this._getChunk(t[0],i);if(r.retries++<this.options.retryChunksLimit){this._uploadData(t,[r.dataBlock]);return}else console.warn("Retried this chunk too often. Giving up.")}this._errorProcessing(t,n||this.options.dictResponseError.replace("{{statusCode}}",i.status),i)}}submitRequest(t,i,n){if(t.readyState!=1){console.warn("Cannot send this request because the XMLHttpRequest.readyState is not OPENED.");return}if(this.options.binaryBody)if(n[0].upload.chunked){const r=this._getChunk(n[0],t);t.send(r.dataBlock.data)}else t.send(n[0]);else t.send(i)}_finished(t,i,n){for(let r of t)r.status=y.SUCCESS,this.emit("success",r,i,n),this.emit("complete",r);if(this.options.uploadMultiple&&(this.emit("successmultiple",t,i,n),this.emit("completemultiple",t)),this.options.autoProcessQueue)return this.processQueue()}_errorProcessing(t,i,n){for(let r of t)r.status=y.ERROR,this.emit("error",r,i,n),this.emit("complete",r);if(this.options.uploadMultiple&&(this.emit("errormultiple",t,i,n),this.emit("completemultiple",t)),this.options.autoProcessQueue)return this.processQueue()}static uuidv4(){return"xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g,function(t){let i=Math.random()*16|0;return(t==="x"?i:i&3|8).toString(16)})}constructor(t,i){super();let n,r;if(this.element=t,this.clickableElements=[],this.listeners=[],this.files=[],typeof this.element=="string"&&(this.element=document.querySelector(this.element)),!this.element||this.element.nodeType==null)throw new Error("Invalid dropzone element.");if(this.element.dropzone)throw new Error("Dropzone already attached.");y.instances.push(this),this.element.dropzone=this;let s=(r=y.optionsForElement(this.element))!=null?r:{};if(this.options=so(!0,{},Gf,s,i??{}),this.options.previewTemplate=this.options.previewTemplate.replace(/\n*/g,""),this.options.forceFallback||!y.isBrowserSupported())return this.options.fallback.call(this);if(this.options.url==null&&(this.options.url=this.element.getAttribute("action")),!this.options.url)throw new Error("No URL provided.");if(this.options.acceptedFiles&&this.options.acceptedMimeTypes)throw new Error("You can't provide both 'acceptedFiles' and 'acceptedMimeTypes'. 'acceptedMimeTypes' is deprecated.");if(this.options.uploadMultiple&&this.options.chunking)throw new Error("You cannot set both: uploadMultiple and chunking.");if(this.options.binaryBody&&this.options.uploadMultiple)throw new Error("You cannot set both: binaryBody and uploadMultiple.");this.options.acceptedMimeTypes&&(this.options.acceptedFiles=this.options.acceptedMimeTypes,delete this.options.acceptedMimeTypes),this.options.renameFilename!=null&&(this.options.renameFile=o=>this.options.renameFilename.call(this,o.name,o)),typeof this.options.method=="string"&&(this.options.method=this.options.method.toUpperCase()),(n=this.getExistingFallback())&&n.parentNode&&n.parentNode.removeChild(n),this.options.previewsContainer!==!1&&(this.options.previewsContainer?this.previewsContainer=y.getElement(this.options.previewsContainer,"previewsContainer"):this.previewsContainer=this.element),this.options.clickable&&(this.options.clickable===!0?this.clickableElements=[this.element]:this.clickableElements=y.getElements(this.options.clickable,"clickable")),this.init()}}y.initClass();y.options={};y.optionsForElement=function(e){if(e.getAttribute("id"))return y.options[Zf(e.getAttribute("id"))]};y.instances=[];y.forElement=function(e){if(typeof e=="string"&&(e=document.querySelector(e)),(e!=null?e.dropzone:void 0)==null)throw new Error("No Dropzone found for given element. This is probably because you're trying to access it before Dropzone had the time to initialize. Use the `init` option to setup any additional observers on your Dropzone.");return e.dropzone};y.discover=function(){let e;if(document.querySelectorAll)e=document.querySelectorAll(".dropzone");else{e=[];let t=i=>(()=>{let n=[];for(let r of i)/(^| )dropzone($| )/.test(r.className)?n.push(e.push(r)):n.push(void 0);return n})();t(document.getElementsByTagName("div")),t(document.getElementsByTagName("form"))}return(()=>{let t=[];for(let i of e)y.optionsForElement(i)!==!1?t.push(new y(i)):t.push(void 0);return t})()};y.blockedBrowsers=[/opera.*(Macintosh|Windows Phone).*version\/12/i];y.isBrowserSupported=function(){let e=!0;if(window.File&&window.FileReader&&window.FileList&&window.Blob&&window.FormData&&document.querySelector)if(!("classList"in document.createElement("a")))e=!1;else{y.blacklistedBrowsers!==void 0&&(y.blockedBrowsers=y.blacklistedBrowsers);for(let t of y.blockedBrowsers)if(t.test(navigator.userAgent)){e=!1;continue}}else e=!1;return e};y.dataURItoBlob=function(e){let t=atob(e.split(",")[1]),i=e.split(",")[0].split(":")[1].split(";")[0],n=new ArrayBuffer(t.length),r=new Uint8Array(n);for(let s=0,o=t.length,a=0<=o;a?s<=o:s>=o;a?s++:s--)r[s]=t.charCodeAt(s);return new Blob([n],{type:i})};const Yf=(e,t)=>e.filter(i=>i!==t).map(i=>i),Zf=e=>e.replace(/[\-_](\w)/g,t=>t.charAt(1).toUpperCase());y.createElement=function(e){let t=document.createElement("div");return t.innerHTML=e,t.childNodes[0]};y.elementInside=function(e,t){if(e===t)return!0;for(;e=e.parentNode;)if(e===t)return!0;return!1};y.getElement=function(e,t){let i;if(typeof e=="string"?i=document.querySelector(e):e.nodeType!=null&&(i=e),i==null)throw new Error(`Invalid \`${t}\` option provided. Please provide a CSS selector or a plain HTML element.`);return i};y.getElements=function(e,t){let i,n;if(e instanceof Array){n=[];try{for(i of e)n.push(this.getElement(i,t))}catch{n=null}}else if(typeof e=="string"){n=[];for(i of document.querySelectorAll(e))n.push(i)}else e.nodeType!=null&&(n=[e]);if(n==null||!n.length)throw new Error(`Invalid \`${t}\` option provided. Please provide a CSS selector, a plain HTML element or a list of those.`);return n};y.confirm=function(e,t,i){if(window.confirm(e))return t();if(i!=null)return i()};y.isValidFile=function(e,t){if(!t)return!0;t=t.split(",");let i=e.type,n=i.replace(/\/.*$/,"");for(let r of t)if(r=r.trim(),r.charAt(0)==="."){if(e.name.toLowerCase().indexOf(r.toLowerCase(),e.name.length-r.length)!==-1)return!0}else if(/\/\*$/.test(r)){if(n===r.replace(/\/.*$/,""))return!0}else if(i===r)return!0;return!1};typeof jQuery<"u"&&jQuery!==null&&(jQuery.fn.dropzone=function(e){return this.each(function(){return new y(this,e)})});y.ADDED="added";y.QUEUED="queued";y.ACCEPTED=y.QUEUED;y.UPLOADING="uploading";y.PROCESSING=y.UPLOADING;y.CANCELED="canceled";y.ERROR="error";y.SUCCESS="success";let tm=function(e){e.naturalWidth;let t=e.naturalHeight,i=document.createElement("canvas");i.width=1,i.height=t;let n=i.getContext("2d");n.drawImage(e,0,0);let{data:r}=n.getImageData(1,0,1,t),s=0,o=t,a=t;for(;a>s;)r[(a-1)*4+3]===0?o=a:s=a,a=o+s>>1;let l=a/t;return l===0?1:l};var em=function(e,t,i,n,r,s,o,a,l,c){let u=tm(t);return e.drawImage(t,i,n,r,s,o,a,l,c/u)};class Tl{static initClass(){this.KEY_STR="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/="}static encode64(t){let i="",n,r,s="",o,a,l,c="",u=0;for(;n=t[u++],r=t[u++],s=t[u++],o=n>>2,a=(n&3)<<4|r>>4,l=(r&15)<<2|s>>6,c=s&63,isNaN(r)?l=c=64:isNaN(s)&&(c=64),i=i+this.KEY_STR.charAt(o)+this.KEY_STR.charAt(a)+this.KEY_STR.charAt(l)+this.KEY_STR.charAt(c),n=r=s="",o=a=l=c="",u<t.length;);return i}static restore(t,i){if(!t.match("data:image/jpeg;base64,"))return i;let n=this.decode64(t.replace("data:image/jpeg;base64,","")),r=this.slice2Segments(n),s=this.exifManipulation(i,r);return`data:image/jpeg;base64,${this.encode64(s)}`}static exifManipulation(t,i){let n=this.getExifArray(i),r=this.insertExif(t,n);return new Uint8Array(r)}static getExifArray(t){let i,n=0;for(;n<t.length;){if(i=t[n],i[0]===255&i[1]===225)return i;n++}return[]}static insertExif(t,i){let n=t.replace("data:image/jpeg;base64,",""),r=this.decode64(n),s=r.indexOf(255,3),o=r.slice(0,s),a=r.slice(s),l=o;return l=l.concat(i),l=l.concat(a),l}static slice2Segments(t){let i=0,n=[];for(;;){var r;if(t[i]===255&t[i+1]===218)break;if(t[i]===255&t[i+1]===216)i+=2;else{r=t[i+2]*256+t[i+3];let s=i+r+2,o=t.slice(i,s);n.push(o),i=s}if(i>t.length)break}return n}static decode64(t){let i,n,r="",s,o,a,l="",c=0,u=[];for(/[^A-Za-z0-9\+\/\=]/g.exec(t)&&console.warn(`There were invalid base64 characters in the input text.
Valid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='
Expect errors in decoding.`),t=t.replace(/[^A-Za-z0-9\+\/\=]/g,"");s=this.KEY_STR.indexOf(t.charAt(c++)),o=this.KEY_STR.indexOf(t.charAt(c++)),a=this.KEY_STR.indexOf(t.charAt(c++)),l=this.KEY_STR.indexOf(t.charAt(c++)),i=s<<2|o>>4,n=(o&15)<<4|a>>2,r=(a&3)<<6|l,u.push(i),a!==64&&u.push(n),l!==64&&u.push(r),i=n=r="",s=o=a=l="",c<t.length;);return u}}Tl.initClass();function im(e,t){return typeof e<"u"&&e!==null?t(e):void 0}function nm(e,t,i){if(typeof e<"u"&&e!==null&&typeof e[t]=="function")return i(e,t)}window.Alpine=Vr;Vr.plugin(Jf);Vr.start();y.autoDiscover=!1;const lo=document.getElementById("file-upload-dropzone"),An=document.getElementById("messageForm"),Tn=document.getElementById("message"),Wt=document.getElementById("file_upload_ids");if(lo&&An&&Tn&&Wt){const e=document.querySelector('meta[name="csrf-token"]').getAttribute("content"),t=lo.dataset.uploadUrl;if(!t)console.error("Dropzone element is missing the data-upload-url attribute!");else{const i=new y("#file-upload-dropzone",{url:t,paramName:"file",maxFilesize:5e3,chunking:!0,forceChunking:!0,chunkSize:5242880,retryChunks:!0,retryChunksLimit:3,parallelChunkUploads:!1,addRemoveLinks:!0,autoProcessQueue:!1,headers:{"X-CSRF-TOKEN":e},params:function(n,r,s){const o={};s&&(o.dzuuid=s.file.upload.uuid,o.dzchunkindex=s.index,o.dztotalfilesize=s.file.size,o.dzchunksize=this.options.chunkSize,o.dztotalchunkcount=s.file.upload.totalChunkCount,o.dzchunkbyteoffset=s.index*this.options.chunkSize);const a=document.getElementById("company_user_id");return a&&a.value&&(o.company_user_id=a.value),o},uploadprogress:function(n,r,s){},success:function(n,r){if(console.log(`Success callback for ${n.name}:`,r),r&&r.file_upload_id){if(console.log(`Final FileUpload ID for ${n.name}: ${r.file_upload_id}`),!n.finalIdReceived){n.finalIdReceived=!0,n.file_upload_id=r.file_upload_id;let s=Wt.value?JSON.parse(Wt.value):[];s.includes(r.file_upload_id)||(s.push(r.file_upload_id),Wt.value=JSON.stringify(s),console.log("Updated file_upload_ids:",Wt.value))}}else console.log(`Received intermediate chunk success for ${n.name}`)},error:function(n,r,s){console.error("Error uploading file chunk:",n.name,r,s);const o=document.getElementById("upload-errors");if(o){const a=typeof r=="object"?r.error||JSON.stringify(r):r;o.innerHTML+=`<p class="text-red-500">Error uploading ${n.name}: ${a}</p>`,o.classList.remove("hidden")}},complete:function(n){console.log("File processing complete (success or error): ",n.name),i.processQueue()}});An.addEventListener("submit",function(n){n.preventDefault();const r=this.querySelector('button[type="submit"]'),s=i.getQueuedFiles(),o=i.getFilesWithStatus(y.UPLOADING),a=i.getFilesWithStatus(y.SUCCESS).length+i.getFilesWithStatus(y.ERROR).length;console.log(`Submit triggered. Queued: ${s.length}, InProgress: ${o.length}, Done: ${a}`),s.length>0?(console.log("Starting file uploads for queue..."),r.disabled=!0,r.textContent="Uploading Files...",i.processQueue()):i.getFilesWithStatus(y.SUCCESS).length>0?(console.log("Files already uploaded, attempting to associate message via queuecomplete."),console.log("Submit triggered, but files seem already uploaded."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-error"}))):(console.log("Submit triggered, but no files added."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"no-files-error"})))}),i.on("queuecomplete",function(){const n=i.getFilesWithStatus(y.SUCCESS).length+i.getFilesWithStatus(y.ERROR).length,r=i.files.length;console.log(`--- Queue Complete Fired --- Processed: ${n}, Total Added: ${r}`);const s=An.querySelector('button[type="submit"]'),o=Tn.value,l=i.getFilesWithStatus(y.SUCCESS).map(c=>c.file_upload_id).filter(c=>c);if(console.log("Queue complete. Message:",o),console.log("Queue complete. Successful file IDs:",l),o&&l.length>0){console.log("Attempting to associate message..."),s.textContent="Associating Message...";const c=window.employeeUploadConfig?window.employeeUploadConfig.associateMessageUrl:"/client/uploads/associate-message";fetch(c,{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":e},body:JSON.stringify({message:o,file_upload_ids:l})}).then(u=>{if(!u.ok)throw u.text().then(d=>{console.error("Error response from associate-message:",u.status,d)}),new Error(`HTTP error! status: ${u.status}`);return u.json()}).then(u=>{console.log("Message associated successfully:",u),Tn.value="",Wt.value="[]",i.removeAllFiles(!0),window.dispatchEvent(new CustomEvent("open-modal",{detail:"association-success"}))}).catch(u=>{console.error("Error associating message:",u),window.dispatchEvent(new CustomEvent("open-modal",{detail:"association-error"}))}).finally(()=>{s.disabled=!1,s.textContent="Upload and Send Message"})}else if(l.length>0&&!o){console.log("Batch upload complete without message. Successful IDs:",l),console.log("Calling /api/uploads/batch-complete..."),s.textContent="Finalizing Upload...",s.disabled=!0;const c=window.employeeUploadConfig?window.employeeUploadConfig.batchCompleteUrl:"/client/uploads/batch-complete";fetch(c,{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":e},body:JSON.stringify({file_upload_ids:l})}).then(u=>{if(!u.ok)throw console.error("Error response from batch-complete endpoint:",u.status),u.text().then(d=>console.error("Batch Complete Error Body:",d)),new Error(`HTTP error! status: ${u.status}`);return u.json()}).then(u=>{console.log("Backend acknowledged batch completion:",u),console.log("Dispatching upload-success modal..."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-success"})),console.log("Attempting to clear Dropzone UI..."),i.removeAllFiles(!0),console.log("Dropzone UI should be cleared now."),console.log("Attempting to clear file IDs input..."),Wt.value="[]",console.log("File IDs input cleared.")}).catch(u=>{console.error("Error calling batch-complete endpoint:",u),window.dispatchEvent(new CustomEvent("open-modal",{detail:"association-error"}))}).finally(()=>{s.disabled=!1,s.textContent="Upload and Send Message",i.getRejectedFiles().length>0&&(console.log("Found rejected files, dispatching upload-error modal as well."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-error"})))})}else console.log("Queue finished, but no successful uploads or handling other cases."),l.length===0&&(s.disabled=!1,s.textContent="Upload and Send Message",i.getRejectedFiles().length>0&&window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-error"})))})}}const rm=window.location.hostname;document.querySelectorAll('a[href^="http"]:not([href*="'+rm+'"]):not([href^="#"]):not(.button-link)').forEach(e=>{e.querySelector(".external-link-icon")||(e.innerHTML+='<svg class="external-link-icon" xmlns="http://www.w3.org/2000/svg" baseProfile="tiny" version="1.2" viewBox="0 0 79 79"><path d="M64,39.8v34.7c0,2.5-2,4.5-4.5,4.5H4.5c-2.5,0-4.5-2-4.5-4.5V19.5c0-2.5,2-4.5,4.5-4.5h35.6c2.5,0,4.5,2,4.5,4.5s-.5,2.4-1.4,3.3c-.8.8-1.9,1.3-3.1,1.3H9v45.9h45.9v-30.2c0-1.3.5-2.4,1.4-3.3.8-.8,1.9-1.3,3.1-1.3,2.5,0,4.5,2,4.5,4.5h0Z"/><path d="M74.5,0h-28.7c-2.2,0-4.2,1.5-4.6,3.6s1.6,5.5,4.4,5.5h17.9l-31.5,31.6c-1.8,1.8-1.8,4.7,0,6.5h0c1.7,1.8,4.6,1.8,6.3,0l31.6-31.6v17.7c0,2.2,1.5,4.2,3.6,4.6s5.5-1.6,5.5-4.4V4.7c0-2.5-2-4.5-4.5-4.5h0v-.2Z"/></svg>')})});export default sm();
