var Rl=(e,t)=>()=>(t||e((t={exports:{}}).exports,t),t.exports);var sm=Rl((Nm,Ai)=>{function co(e,t){return function(){return e.apply(t,arguments)}}const{toString:Ol}=Object.prototype,{getPrototypeOf:sn}=Object,{iterator:Bi,toStringTag:ho}=Symbol,Di=(e=>t=>{const i=Ol.call(t);return e[i]||(e[i]=i.slice(8,-1).toLowerCase())})(Object.create(null)),pt=e=>(e=e.toLowerCase(),t=>Di(t)===e),Ni=e=>t=>typeof t===e,{isArray:ye}=Array,We=Ni("undefined");function Ye(e){return e!==null&&!We(e)&&e.constructor!==null&&!We(e.constructor)&&J(e.constructor.isBuffer)&&e.constructor.isBuffer(e)}const po=pt("ArrayBuffer");function Fl(e){let t;return typeof ArrayBuffer<"u"&&ArrayBuffer.isView?t=ArrayBuffer.isView(e):t=e&&e.buffer&&po(e.buffer),t}const Ll=Ni("string"),J=Ni("function"),fo=Ni("number"),Ze=e=>e!==null&&typeof e=="object",Ml=e=>e===!0||e===!1,yi=e=>{if(Di(e)!=="object")return!1;const t=sn(e);return(t===null||t===Object.prototype||Object.getPrototypeOf(t)===null)&&!(ho in e)&&!(Bi in e)},Pl=e=>{if(!Ze(e)||Ye(e))return!1;try{return Object.keys(e).length===0&&Object.getPrototypeOf(e)===Object.prototype}catch{return!1}},zl=pt("Date"),Il=pt("File"),Bl=pt("Blob"),Dl=pt("FileList"),Nl=e=>Ze(e)&&J(e.pipe),Ul=e=>{let t;return e&&(typeof FormData=="function"&&e instanceof FormData||J(e.append)&&((t=Di(e))==="formdata"||t==="object"&&J(e.toString)&&e.toString()==="[object FormData]"))},Hl=pt("URLSearchParams"),[ql,Vl,jl,Wl]=["ReadableStream","Request","Response","Headers"].map(pt),Kl=e=>e.trim?e.trim():e.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,"");function ti(e,t,{allOwnKeys:i=!1}={}){if(e===null||typeof e>"u")return;let r,n;if(typeof e!="object"&&(e=[e]),ye(e))for(r=0,n=e.length;r<n;r++)t.call(null,e[r],r,e);else{if(Ye(e))return;const s=i?Object.getOwnPropertyNames(e):Object.keys(e),o=s.length;let a;for(r=0;r<o;r++)a=s[r],t.call(null,e[a],a,e)}}function mo(e,t){if(Ye(e))return null;t=t.toLowerCase();const i=Object.keys(e);let r=i.length,n;for(;r-- >0;)if(n=i[r],t===n.toLowerCase())return n;return null}const Jt=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:global,go=e=>!We(e)&&e!==Jt;function $r(){const{caseless:e}=go(this)&&this||{},t={},i=(r,n)=>{const s=e&&mo(t,n)||n;yi(t[s])&&yi(r)?t[s]=$r(t[s],r):yi(r)?t[s]=$r({},r):ye(r)?t[s]=r.slice():t[s]=r};for(let r=0,n=arguments.length;r<n;r++)arguments[r]&&ti(arguments[r],i);return t}const Ql=(e,t,i,{allOwnKeys:r}={})=>(ti(t,(n,s)=>{i&&J(n)?e[s]=co(n,i):e[s]=n},{allOwnKeys:r}),e),Jl=e=>(e.charCodeAt(0)===65279&&(e=e.slice(1)),e),Xl=(e,t,i,r)=>{e.prototype=Object.create(t.prototype,r),e.prototype.constructor=e,Object.defineProperty(e,"super",{value:t.prototype}),i&&Object.assign(e.prototype,i)},Gl=(e,t,i,r)=>{let n,s,o;const a={};if(t=t||{},e==null)return t;do{for(n=Object.getOwnPropertyNames(e),s=n.length;s-- >0;)o=n[s],(!r||r(o,e,t))&&!a[o]&&(t[o]=e[o],a[o]=!0);e=i!==!1&&sn(e)}while(e&&(!i||i(e,t))&&e!==Object.prototype);return t},Yl=(e,t,i)=>{e=String(e),(i===void 0||i>e.length)&&(i=e.length),i-=t.length;const r=e.indexOf(t,i);return r!==-1&&r===i},Zl=e=>{if(!e)return null;if(ye(e))return e;let t=e.length;if(!fo(t))return null;const i=new Array(t);for(;t-- >0;)i[t]=e[t];return i},tu=(e=>t=>e&&t instanceof e)(typeof Uint8Array<"u"&&sn(Uint8Array)),eu=(e,t)=>{const r=(e&&e[Bi]).call(e);let n;for(;(n=r.next())&&!n.done;){const s=n.value;t.call(e,s[0],s[1])}},iu=(e,t)=>{let i;const r=[];for(;(i=e.exec(t))!==null;)r.push(i);return r},ru=pt("HTMLFormElement"),nu=e=>e.toLowerCase().replace(/[-_\s]([a-z\d])(\w*)/g,function(i,r,n){return r.toUpperCase()+n}),Wn=(({hasOwnProperty:e})=>(t,i)=>e.call(t,i))(Object.prototype),su=pt("RegExp"),bo=(e,t)=>{const i=Object.getOwnPropertyDescriptors(e),r={};ti(i,(n,s)=>{let o;(o=t(n,s,e))!==!1&&(r[s]=o||n)}),Object.defineProperties(e,r)},ou=e=>{bo(e,(t,i)=>{if(J(e)&&["arguments","caller","callee"].indexOf(i)!==-1)return!1;const r=e[i];if(J(r)){if(t.enumerable=!1,"writable"in t){t.writable=!1;return}t.set||(t.set=()=>{throw Error("Can not rewrite read-only method '"+i+"'")})}})},au=(e,t)=>{const i={},r=n=>{n.forEach(s=>{i[s]=!0})};return ye(e)?r(e):r(String(e).split(t)),i},lu=()=>{},uu=(e,t)=>e!=null&&Number.isFinite(e=+e)?e:t;function cu(e){return!!(e&&J(e.append)&&e[ho]==="FormData"&&e[Bi])}const du=e=>{const t=new Array(10),i=(r,n)=>{if(Ze(r)){if(t.indexOf(r)>=0)return;if(Ye(r))return r;if(!("toJSON"in r)){t[n]=r;const s=ye(r)?[]:{};return ti(r,(o,a)=>{const l=i(o,n+1);!We(l)&&(s[a]=l)}),t[n]=void 0,s}}return r};return i(e,0)},hu=pt("AsyncFunction"),pu=e=>e&&(Ze(e)||J(e))&&J(e.then)&&J(e.catch),vo=((e,t)=>e?setImmediate:t?((i,r)=>(Jt.addEventListener("message",({source:n,data:s})=>{n===Jt&&s===i&&r.length&&r.shift()()},!1),n=>{r.push(n),Jt.postMessage(i,"*")}))(`axios@${Math.random()}`,[]):i=>setTimeout(i))(typeof setImmediate=="function",J(Jt.postMessage)),fu=typeof queueMicrotask<"u"?queueMicrotask.bind(Jt):typeof process<"u"&&process.nextTick||vo,mu=e=>e!=null&&J(e[Bi]),b={isArray:ye,isArrayBuffer:po,isBuffer:Ye,isFormData:Ul,isArrayBufferView:Fl,isString:Ll,isNumber:fo,isBoolean:Ml,isObject:Ze,isPlainObject:yi,isEmptyObject:Pl,isReadableStream:ql,isRequest:Vl,isResponse:jl,isHeaders:Wl,isUndefined:We,isDate:zl,isFile:Il,isBlob:Bl,isRegExp:su,isFunction:J,isStream:Nl,isURLSearchParams:Hl,isTypedArray:tu,isFileList:Dl,forEach:ti,merge:$r,extend:Ql,trim:Kl,stripBOM:Jl,inherits:Xl,toFlatObject:Gl,kindOf:Di,kindOfTest:pt,endsWith:Yl,toArray:Zl,forEachEntry:eu,matchAll:iu,isHTMLForm:ru,hasOwnProperty:Wn,hasOwnProp:Wn,reduceDescriptors:bo,freezeMethods:ou,toObjectSet:au,toCamelCase:nu,noop:lu,toFiniteNumber:uu,findKey:mo,global:Jt,isContextDefined:go,isSpecCompliantForm:cu,toJSONObject:du,isAsyncFn:hu,isThenable:pu,setImmediate:vo,asap:fu,isIterable:mu};function k(e,t,i,r,n){Error.call(this),Error.captureStackTrace?Error.captureStackTrace(this,this.constructor):this.stack=new Error().stack,this.message=e,this.name="AxiosError",t&&(this.code=t),i&&(this.config=i),r&&(this.request=r),n&&(this.response=n,this.status=n.status?n.status:null)}b.inherits(k,Error,{toJSON:function(){return{message:this.message,name:this.name,description:this.description,number:this.number,fileName:this.fileName,lineNumber:this.lineNumber,columnNumber:this.columnNumber,stack:this.stack,config:b.toJSONObject(this.config),code:this.code,status:this.status}}});const yo=k.prototype,wo={};["ERR_BAD_OPTION_VALUE","ERR_BAD_OPTION","ECONNABORTED","ETIMEDOUT","ERR_NETWORK","ERR_FR_TOO_MANY_REDIRECTS","ERR_DEPRECATED","ERR_BAD_RESPONSE","ERR_BAD_REQUEST","ERR_CANCELED","ERR_NOT_SUPPORT","ERR_INVALID_URL"].forEach(e=>{wo[e]={value:e}});Object.defineProperties(k,wo);Object.defineProperty(yo,"isAxiosError",{value:!0});k.from=(e,t,i,r,n,s)=>{const o=Object.create(yo);return b.toFlatObject(e,o,function(l){return l!==Error.prototype},a=>a!=="isAxiosError"),k.call(o,e.message,t,i,r,n),o.cause=e,o.name=e.name,s&&Object.assign(o,s),o};const gu=null;function Tr(e){return b.isPlainObject(e)||b.isArray(e)}function _o(e){return b.endsWith(e,"[]")?e.slice(0,-2):e}function Kn(e,t,i){return e?e.concat(t).map(function(n,s){return n=_o(n),!i&&s?"["+n+"]":n}).join(i?".":""):t}function bu(e){return b.isArray(e)&&!e.some(Tr)}const vu=b.toFlatObject(b,{},null,function(t){return/^is[A-Z]/.test(t)});function Ui(e,t,i){if(!b.isObject(e))throw new TypeError("target must be an object");t=t||new FormData,i=b.toFlatObject(i,{metaTokens:!0,dots:!1,indexes:!1},!1,function(v,m){return!b.isUndefined(m[v])});const r=i.metaTokens,n=i.visitor||c,s=i.dots,o=i.indexes,l=(i.Blob||typeof Blob<"u"&&Blob)&&b.isSpecCompliantForm(t);if(!b.isFunction(n))throw new TypeError("visitor must be a function");function u(f){if(f===null)return"";if(b.isDate(f))return f.toISOString();if(b.isBoolean(f))return f.toString();if(!l&&b.isBlob(f))throw new k("Blob is not supported. Use a Buffer instead.");return b.isArrayBuffer(f)||b.isTypedArray(f)?l&&typeof Blob=="function"?new Blob([f]):Buffer.from(f):f}function c(f,v,m){let w=f;if(f&&!m&&typeof f=="object"){if(b.endsWith(v,"{}"))v=r?v:v.slice(0,-2),f=JSON.stringify(f);else if(b.isArray(f)&&bu(f)||(b.isFileList(f)||b.endsWith(v,"[]"))&&(w=b.toArray(f)))return v=_o(v),w.forEach(function(E,S){!(b.isUndefined(E)||E===null)&&t.append(o===!0?Kn([v],S,s):o===null?v:v+"[]",u(E))}),!1}return Tr(f)?!0:(t.append(Kn(m,v,s),u(f)),!1)}const d=[],h=Object.assign(vu,{defaultVisitor:c,convertValue:u,isVisitable:Tr});function p(f,v){if(!b.isUndefined(f)){if(d.indexOf(f)!==-1)throw Error("Circular reference detected in "+v.join("."));d.push(f),b.forEach(f,function(w,x){(!(b.isUndefined(w)||w===null)&&n.call(t,w,b.isString(x)?x.trim():x,v,h))===!0&&p(w,v?v.concat(x):[x])}),d.pop()}}if(!b.isObject(e))throw new TypeError("data must be an object");return p(e),t}function Qn(e){const t={"!":"%21","'":"%27","(":"%28",")":"%29","~":"%7E","%20":"+","%00":"\0"};return encodeURIComponent(e).replace(/[!'()~]|%20|%00/g,function(r){return t[r]})}function on(e,t){this._pairs=[],e&&Ui(e,this,t)}const xo=on.prototype;xo.append=function(t,i){this._pairs.push([t,i])};xo.toString=function(t){const i=t?function(r){return t.call(this,r,Qn)}:Qn;return this._pairs.map(function(n){return i(n[0])+"="+i(n[1])},"").join("&")};function yu(e){return encodeURIComponent(e).replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+").replace(/%5B/gi,"[").replace(/%5D/gi,"]")}function Eo(e,t,i){if(!t)return e;const r=i&&i.encode||yu;b.isFunction(i)&&(i={serialize:i});const n=i&&i.serialize;let s;if(n?s=n(t,i):s=b.isURLSearchParams(t)?t.toString():new on(t,i).toString(r),s){const o=e.indexOf("#");o!==-1&&(e=e.slice(0,o)),e+=(e.indexOf("?")===-1?"?":"&")+s}return e}class Jn{constructor(){this.handlers=[]}use(t,i,r){return this.handlers.push({fulfilled:t,rejected:i,synchronous:r?r.synchronous:!1,runWhen:r?r.runWhen:null}),this.handlers.length-1}eject(t){this.handlers[t]&&(this.handlers[t]=null)}clear(){this.handlers&&(this.handlers=[])}forEach(t){b.forEach(this.handlers,function(r){r!==null&&t(r)})}}const So={silentJSONParsing:!0,forcedJSONParsing:!0,clarifyTimeoutError:!1},wu=typeof URLSearchParams<"u"?URLSearchParams:on,_u=typeof FormData<"u"?FormData:null,xu=typeof Blob<"u"?Blob:null,Eu={isBrowser:!0,classes:{URLSearchParams:wu,FormData:_u,Blob:xu},protocols:["http","https","file","blob","url","data"]},an=typeof window<"u"&&typeof document<"u",Rr=typeof navigator=="object"&&navigator||void 0,Su=an&&(!Rr||["ReactNative","NativeScript","NS"].indexOf(Rr.product)<0),Cu=typeof WorkerGlobalScope<"u"&&self instanceof WorkerGlobalScope&&typeof self.importScripts=="function",ku=an&&window.location.href||"http://localhost",Au=Object.freeze(Object.defineProperty({__proto__:null,hasBrowserEnv:an,hasStandardBrowserEnv:Su,hasStandardBrowserWebWorkerEnv:Cu,navigator:Rr,origin:ku},Symbol.toStringTag,{value:"Module"})),q={...Au,...Eu};function $u(e,t){return Ui(e,new q.classes.URLSearchParams,{visitor:function(i,r,n,s){return q.isNode&&b.isBuffer(i)?(this.append(r,i.toString("base64")),!1):s.defaultVisitor.apply(this,arguments)},...t})}function Tu(e){return b.matchAll(/\w+|\[(\w*)]/g,e).map(t=>t[0]==="[]"?"":t[1]||t[0])}function Ru(e){const t={},i=Object.keys(e);let r;const n=i.length;let s;for(r=0;r<n;r++)s=i[r],t[s]=e[s];return t}function Co(e){function t(i,r,n,s){let o=i[s++];if(o==="__proto__")return!0;const a=Number.isFinite(+o),l=s>=i.length;return o=!o&&b.isArray(n)?n.length:o,l?(b.hasOwnProp(n,o)?n[o]=[n[o],r]:n[o]=r,!a):((!n[o]||!b.isObject(n[o]))&&(n[o]=[]),t(i,r,n[o],s)&&b.isArray(n[o])&&(n[o]=Ru(n[o])),!a)}if(b.isFormData(e)&&b.isFunction(e.entries)){const i={};return b.forEachEntry(e,(r,n)=>{t(Tu(r),n,i,0)}),i}return null}function Ou(e,t,i){if(b.isString(e))try{return(t||JSON.parse)(e),b.trim(e)}catch(r){if(r.name!=="SyntaxError")throw r}return(i||JSON.stringify)(e)}const ei={transitional:So,adapter:["xhr","http","fetch"],transformRequest:[function(t,i){const r=i.getContentType()||"",n=r.indexOf("application/json")>-1,s=b.isObject(t);if(s&&b.isHTMLForm(t)&&(t=new FormData(t)),b.isFormData(t))return n?JSON.stringify(Co(t)):t;if(b.isArrayBuffer(t)||b.isBuffer(t)||b.isStream(t)||b.isFile(t)||b.isBlob(t)||b.isReadableStream(t))return t;if(b.isArrayBufferView(t))return t.buffer;if(b.isURLSearchParams(t))return i.setContentType("application/x-www-form-urlencoded;charset=utf-8",!1),t.toString();let a;if(s){if(r.indexOf("application/x-www-form-urlencoded")>-1)return $u(t,this.formSerializer).toString();if((a=b.isFileList(t))||r.indexOf("multipart/form-data")>-1){const l=this.env&&this.env.FormData;return Ui(a?{"files[]":t}:t,l&&new l,this.formSerializer)}}return s||n?(i.setContentType("application/json",!1),Ou(t)):t}],transformResponse:[function(t){const i=this.transitional||ei.transitional,r=i&&i.forcedJSONParsing,n=this.responseType==="json";if(b.isResponse(t)||b.isReadableStream(t))return t;if(t&&b.isString(t)&&(r&&!this.responseType||n)){const o=!(i&&i.silentJSONParsing)&&n;try{return JSON.parse(t)}catch(a){if(o)throw a.name==="SyntaxError"?k.from(a,k.ERR_BAD_RESPONSE,this,null,this.response):a}}return t}],timeout:0,xsrfCookieName:"XSRF-TOKEN",xsrfHeaderName:"X-XSRF-TOKEN",maxContentLength:-1,maxBodyLength:-1,env:{FormData:q.classes.FormData,Blob:q.classes.Blob},validateStatus:function(t){return t>=200&&t<300},headers:{common:{Accept:"application/json, text/plain, */*","Content-Type":void 0}}};b.forEach(["delete","get","head","post","put","patch"],e=>{ei.headers[e]={}});const Fu=b.toObjectSet(["age","authorization","content-length","content-type","etag","expires","from","host","if-modified-since","if-unmodified-since","last-modified","location","max-forwards","proxy-authorization","referer","retry-after","user-agent"]),Lu=e=>{const t={};let i,r,n;return e&&e.split(`
`).forEach(function(o){n=o.indexOf(":"),i=o.substring(0,n).trim().toLowerCase(),r=o.substring(n+1).trim(),!(!i||t[i]&&Fu[i])&&(i==="set-cookie"?t[i]?t[i].push(r):t[i]=[r]:t[i]=t[i]?t[i]+", "+r:r)}),t},Xn=Symbol("internals");function Re(e){return e&&String(e).trim().toLowerCase()}function wi(e){return e===!1||e==null?e:b.isArray(e)?e.map(wi):String(e)}function Mu(e){const t=Object.create(null),i=/([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g;let r;for(;r=i.exec(e);)t[r[1]]=r[2];return t}const Pu=e=>/^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(e.trim());function nr(e,t,i,r,n){if(b.isFunction(r))return r.call(this,t,i);if(n&&(t=i),!!b.isString(t)){if(b.isString(r))return t.indexOf(r)!==-1;if(b.isRegExp(r))return r.test(t)}}function zu(e){return e.trim().toLowerCase().replace(/([a-z\d])(\w*)/g,(t,i,r)=>i.toUpperCase()+r)}function Iu(e,t){const i=b.toCamelCase(" "+t);["get","set","has"].forEach(r=>{Object.defineProperty(e,r+i,{value:function(n,s,o){return this[r].call(this,t,n,s,o)},configurable:!0})})}let X=class{constructor(t){t&&this.set(t)}set(t,i,r){const n=this;function s(a,l,u){const c=Re(l);if(!c)throw new Error("header name must be a non-empty string");const d=b.findKey(n,c);(!d||n[d]===void 0||u===!0||u===void 0&&n[d]!==!1)&&(n[d||l]=wi(a))}const o=(a,l)=>b.forEach(a,(u,c)=>s(u,c,l));if(b.isPlainObject(t)||t instanceof this.constructor)o(t,i);else if(b.isString(t)&&(t=t.trim())&&!Pu(t))o(Lu(t),i);else if(b.isObject(t)&&b.isIterable(t)){let a={},l,u;for(const c of t){if(!b.isArray(c))throw TypeError("Object iterator must return a key-value pair");a[u=c[0]]=(l=a[u])?b.isArray(l)?[...l,c[1]]:[l,c[1]]:c[1]}o(a,i)}else t!=null&&s(i,t,r);return this}get(t,i){if(t=Re(t),t){const r=b.findKey(this,t);if(r){const n=this[r];if(!i)return n;if(i===!0)return Mu(n);if(b.isFunction(i))return i.call(this,n,r);if(b.isRegExp(i))return i.exec(n);throw new TypeError("parser must be boolean|regexp|function")}}}has(t,i){if(t=Re(t),t){const r=b.findKey(this,t);return!!(r&&this[r]!==void 0&&(!i||nr(this,this[r],r,i)))}return!1}delete(t,i){const r=this;let n=!1;function s(o){if(o=Re(o),o){const a=b.findKey(r,o);a&&(!i||nr(r,r[a],a,i))&&(delete r[a],n=!0)}}return b.isArray(t)?t.forEach(s):s(t),n}clear(t){const i=Object.keys(this);let r=i.length,n=!1;for(;r--;){const s=i[r];(!t||nr(this,this[s],s,t,!0))&&(delete this[s],n=!0)}return n}normalize(t){const i=this,r={};return b.forEach(this,(n,s)=>{const o=b.findKey(r,s);if(o){i[o]=wi(n),delete i[s];return}const a=t?zu(s):String(s).trim();a!==s&&delete i[s],i[a]=wi(n),r[a]=!0}),this}concat(...t){return this.constructor.concat(this,...t)}toJSON(t){const i=Object.create(null);return b.forEach(this,(r,n)=>{r!=null&&r!==!1&&(i[n]=t&&b.isArray(r)?r.join(", "):r)}),i}[Symbol.iterator](){return Object.entries(this.toJSON())[Symbol.iterator]()}toString(){return Object.entries(this.toJSON()).map(([t,i])=>t+": "+i).join(`
`)}getSetCookie(){return this.get("set-cookie")||[]}get[Symbol.toStringTag](){return"AxiosHeaders"}static from(t){return t instanceof this?t:new this(t)}static concat(t,...i){const r=new this(t);return i.forEach(n=>r.set(n)),r}static accessor(t){const r=(this[Xn]=this[Xn]={accessors:{}}).accessors,n=this.prototype;function s(o){const a=Re(o);r[a]||(Iu(n,o),r[a]=!0)}return b.isArray(t)?t.forEach(s):s(t),this}};X.accessor(["Content-Type","Content-Length","Accept","Accept-Encoding","User-Agent","Authorization"]);b.reduceDescriptors(X.prototype,({value:e},t)=>{let i=t[0].toUpperCase()+t.slice(1);return{get:()=>e,set(r){this[i]=r}}});b.freezeMethods(X);function sr(e,t){const i=this||ei,r=t||i,n=X.from(r.headers);let s=r.data;return b.forEach(e,function(a){s=a.call(i,s,n.normalize(),t?t.status:void 0)}),n.normalize(),s}function ko(e){return!!(e&&e.__CANCEL__)}function we(e,t,i){k.call(this,e??"canceled",k.ERR_CANCELED,t,i),this.name="CanceledError"}b.inherits(we,k,{__CANCEL__:!0});function Ao(e,t,i){const r=i.config.validateStatus;!i.status||!r||r(i.status)?e(i):t(new k("Request failed with status code "+i.status,[k.ERR_BAD_REQUEST,k.ERR_BAD_RESPONSE][Math.floor(i.status/100)-4],i.config,i.request,i))}function Bu(e){const t=/^([-+\w]{1,25})(:?\/\/|:)/.exec(e);return t&&t[1]||""}function Du(e,t){e=e||10;const i=new Array(e),r=new Array(e);let n=0,s=0,o;return t=t!==void 0?t:1e3,function(l){const u=Date.now(),c=r[s];o||(o=u),i[n]=l,r[n]=u;let d=s,h=0;for(;d!==n;)h+=i[d++],d=d%e;if(n=(n+1)%e,n===s&&(s=(s+1)%e),u-o<t)return;const p=c&&u-c;return p?Math.round(h*1e3/p):void 0}}function Nu(e,t){let i=0,r=1e3/t,n,s;const o=(u,c=Date.now())=>{i=c,n=null,s&&(clearTimeout(s),s=null),e(...u)};return[(...u)=>{const c=Date.now(),d=c-i;d>=r?o(u,c):(n=u,s||(s=setTimeout(()=>{s=null,o(n)},r-d)))},()=>n&&o(n)]}const $i=(e,t,i=3)=>{let r=0;const n=Du(50,250);return Nu(s=>{const o=s.loaded,a=s.lengthComputable?s.total:void 0,l=o-r,u=n(l),c=o<=a;r=o;const d={loaded:o,total:a,progress:a?o/a:void 0,bytes:l,rate:u||void 0,estimated:u&&a&&c?(a-o)/u:void 0,event:s,lengthComputable:a!=null,[t?"download":"upload"]:!0};e(d)},i)},Gn=(e,t)=>{const i=e!=null;return[r=>t[0]({lengthComputable:i,total:e,loaded:r}),t[1]]},Yn=e=>(...t)=>b.asap(()=>e(...t)),Uu=q.hasStandardBrowserEnv?((e,t)=>i=>(i=new URL(i,q.origin),e.protocol===i.protocol&&e.host===i.host&&(t||e.port===i.port)))(new URL(q.origin),q.navigator&&/(msie|trident)/i.test(q.navigator.userAgent)):()=>!0,Hu=q.hasStandardBrowserEnv?{write(e,t,i,r,n,s){const o=[e+"="+encodeURIComponent(t)];b.isNumber(i)&&o.push("expires="+new Date(i).toGMTString()),b.isString(r)&&o.push("path="+r),b.isString(n)&&o.push("domain="+n),s===!0&&o.push("secure"),document.cookie=o.join("; ")},read(e){const t=document.cookie.match(new RegExp("(^|;\\s*)("+e+")=([^;]*)"));return t?decodeURIComponent(t[3]):null},remove(e){this.write(e,"",Date.now()-864e5)}}:{write(){},read(){return null},remove(){}};function qu(e){return/^([a-z][a-z\d+\-.]*:)?\/\//i.test(e)}function Vu(e,t){return t?e.replace(/\/?\/$/,"")+"/"+t.replace(/^\/+/,""):e}function $o(e,t,i){let r=!qu(t);return e&&(r||i==!1)?Vu(e,t):t}const Zn=e=>e instanceof X?{...e}:e;function se(e,t){t=t||{};const i={};function r(u,c,d,h){return b.isPlainObject(u)&&b.isPlainObject(c)?b.merge.call({caseless:h},u,c):b.isPlainObject(c)?b.merge({},c):b.isArray(c)?c.slice():c}function n(u,c,d,h){if(b.isUndefined(c)){if(!b.isUndefined(u))return r(void 0,u,d,h)}else return r(u,c,d,h)}function s(u,c){if(!b.isUndefined(c))return r(void 0,c)}function o(u,c){if(b.isUndefined(c)){if(!b.isUndefined(u))return r(void 0,u)}else return r(void 0,c)}function a(u,c,d){if(d in t)return r(u,c);if(d in e)return r(void 0,u)}const l={url:s,method:s,data:s,baseURL:o,transformRequest:o,transformResponse:o,paramsSerializer:o,timeout:o,timeoutMessage:o,withCredentials:o,withXSRFToken:o,adapter:o,responseType:o,xsrfCookieName:o,xsrfHeaderName:o,onUploadProgress:o,onDownloadProgress:o,decompress:o,maxContentLength:o,maxBodyLength:o,beforeRedirect:o,transport:o,httpAgent:o,httpsAgent:o,cancelToken:o,socketPath:o,responseEncoding:o,validateStatus:a,headers:(u,c,d)=>n(Zn(u),Zn(c),d,!0)};return b.forEach(Object.keys({...e,...t}),function(c){const d=l[c]||n,h=d(e[c],t[c],c);b.isUndefined(h)&&d!==a||(i[c]=h)}),i}const To=e=>{const t=se({},e);let{data:i,withXSRFToken:r,xsrfHeaderName:n,xsrfCookieName:s,headers:o,auth:a}=t;t.headers=o=X.from(o),t.url=Eo($o(t.baseURL,t.url,t.allowAbsoluteUrls),e.params,e.paramsSerializer),a&&o.set("Authorization","Basic "+btoa((a.username||"")+":"+(a.password?unescape(encodeURIComponent(a.password)):"")));let l;if(b.isFormData(i)){if(q.hasStandardBrowserEnv||q.hasStandardBrowserWebWorkerEnv)o.setContentType(void 0);else if((l=o.getContentType())!==!1){const[u,...c]=l?l.split(";").map(d=>d.trim()).filter(Boolean):[];o.setContentType([u||"multipart/form-data",...c].join("; "))}}if(q.hasStandardBrowserEnv&&(r&&b.isFunction(r)&&(r=r(t)),r||r!==!1&&Uu(t.url))){const u=n&&s&&Hu.read(s);u&&o.set(n,u)}return t},ju=typeof XMLHttpRequest<"u",Wu=ju&&function(e){return new Promise(function(i,r){const n=To(e);let s=n.data;const o=X.from(n.headers).normalize();let{responseType:a,onUploadProgress:l,onDownloadProgress:u}=n,c,d,h,p,f;function v(){p&&p(),f&&f(),n.cancelToken&&n.cancelToken.unsubscribe(c),n.signal&&n.signal.removeEventListener("abort",c)}let m=new XMLHttpRequest;m.open(n.method.toUpperCase(),n.url,!0),m.timeout=n.timeout;function w(){if(!m)return;const E=X.from("getAllResponseHeaders"in m&&m.getAllResponseHeaders()),C={data:!a||a==="text"||a==="json"?m.responseText:m.response,status:m.status,statusText:m.statusText,headers:E,config:e,request:m};Ao(function(R){i(R),v()},function(R){r(R),v()},C),m=null}"onloadend"in m?m.onloadend=w:m.onreadystatechange=function(){!m||m.readyState!==4||m.status===0&&!(m.responseURL&&m.responseURL.indexOf("file:")===0)||setTimeout(w)},m.onabort=function(){m&&(r(new k("Request aborted",k.ECONNABORTED,e,m)),m=null)},m.onerror=function(){r(new k("Network Error",k.ERR_NETWORK,e,m)),m=null},m.ontimeout=function(){let S=n.timeout?"timeout of "+n.timeout+"ms exceeded":"timeout exceeded";const C=n.transitional||So;n.timeoutErrorMessage&&(S=n.timeoutErrorMessage),r(new k(S,C.clarifyTimeoutError?k.ETIMEDOUT:k.ECONNABORTED,e,m)),m=null},s===void 0&&o.setContentType(null),"setRequestHeader"in m&&b.forEach(o.toJSON(),function(S,C){m.setRequestHeader(C,S)}),b.isUndefined(n.withCredentials)||(m.withCredentials=!!n.withCredentials),a&&a!=="json"&&(m.responseType=n.responseType),u&&([h,f]=$i(u,!0),m.addEventListener("progress",h)),l&&m.upload&&([d,p]=$i(l),m.upload.addEventListener("progress",d),m.upload.addEventListener("loadend",p)),(n.cancelToken||n.signal)&&(c=E=>{m&&(r(!E||E.type?new we(null,e,m):E),m.abort(),m=null)},n.cancelToken&&n.cancelToken.subscribe(c),n.signal&&(n.signal.aborted?c():n.signal.addEventListener("abort",c)));const x=Bu(n.url);if(x&&q.protocols.indexOf(x)===-1){r(new k("Unsupported protocol "+x+":",k.ERR_BAD_REQUEST,e));return}m.send(s||null)})},Ku=(e,t)=>{const{length:i}=e=e?e.filter(Boolean):[];if(t||i){let r=new AbortController,n;const s=function(u){if(!n){n=!0,a();const c=u instanceof Error?u:this.reason;r.abort(c instanceof k?c:new we(c instanceof Error?c.message:c))}};let o=t&&setTimeout(()=>{o=null,s(new k(`timeout ${t} of ms exceeded`,k.ETIMEDOUT))},t);const a=()=>{e&&(o&&clearTimeout(o),o=null,e.forEach(u=>{u.unsubscribe?u.unsubscribe(s):u.removeEventListener("abort",s)}),e=null)};e.forEach(u=>u.addEventListener("abort",s));const{signal:l}=r;return l.unsubscribe=()=>b.asap(a),l}},Qu=function*(e,t){let i=e.byteLength;if(i<t){yield e;return}let r=0,n;for(;r<i;)n=r+t,yield e.slice(r,n),r=n},Ju=async function*(e,t){for await(const i of Xu(e))yield*Qu(i,t)},Xu=async function*(e){if(e[Symbol.asyncIterator]){yield*e;return}const t=e.getReader();try{for(;;){const{done:i,value:r}=await t.read();if(i)break;yield r}}finally{await t.cancel()}},ts=(e,t,i,r)=>{const n=Ju(e,t);let s=0,o,a=l=>{o||(o=!0,r&&r(l))};return new ReadableStream({async pull(l){try{const{done:u,value:c}=await n.next();if(u){a(),l.close();return}let d=c.byteLength;if(i){let h=s+=d;i(h)}l.enqueue(new Uint8Array(c))}catch(u){throw a(u),u}},cancel(l){return a(l),n.return()}},{highWaterMark:2})},Hi=typeof fetch=="function"&&typeof Request=="function"&&typeof Response=="function",Ro=Hi&&typeof ReadableStream=="function",Gu=Hi&&(typeof TextEncoder=="function"?(e=>t=>e.encode(t))(new TextEncoder):async e=>new Uint8Array(await new Response(e).arrayBuffer())),Oo=(e,...t)=>{try{return!!e(...t)}catch{return!1}},Yu=Ro&&Oo(()=>{let e=!1;const t=new Request(q.origin,{body:new ReadableStream,method:"POST",get duplex(){return e=!0,"half"}}).headers.has("Content-Type");return e&&!t}),es=64*1024,Or=Ro&&Oo(()=>b.isReadableStream(new Response("").body)),Ti={stream:Or&&(e=>e.body)};Hi&&(e=>{["text","arrayBuffer","blob","formData","stream"].forEach(t=>{!Ti[t]&&(Ti[t]=b.isFunction(e[t])?i=>i[t]():(i,r)=>{throw new k(`Response type '${t}' is not supported`,k.ERR_NOT_SUPPORT,r)})})})(new Response);const Zu=async e=>{if(e==null)return 0;if(b.isBlob(e))return e.size;if(b.isSpecCompliantForm(e))return(await new Request(q.origin,{method:"POST",body:e}).arrayBuffer()).byteLength;if(b.isArrayBufferView(e)||b.isArrayBuffer(e))return e.byteLength;if(b.isURLSearchParams(e)&&(e=e+""),b.isString(e))return(await Gu(e)).byteLength},tc=async(e,t)=>{const i=b.toFiniteNumber(e.getContentLength());return i??Zu(t)},ec=Hi&&(async e=>{let{url:t,method:i,data:r,signal:n,cancelToken:s,timeout:o,onDownloadProgress:a,onUploadProgress:l,responseType:u,headers:c,withCredentials:d="same-origin",fetchOptions:h}=To(e);u=u?(u+"").toLowerCase():"text";let p=Ku([n,s&&s.toAbortSignal()],o),f;const v=p&&p.unsubscribe&&(()=>{p.unsubscribe()});let m;try{if(l&&Yu&&i!=="get"&&i!=="head"&&(m=await tc(c,r))!==0){let C=new Request(t,{method:"POST",body:r,duplex:"half"}),F;if(b.isFormData(r)&&(F=C.headers.get("content-type"))&&c.setContentType(F),C.body){const[R,H]=Gn(m,$i(Yn(l)));r=ts(C.body,es,R,H)}}b.isString(d)||(d=d?"include":"omit");const w="credentials"in Request.prototype;f=new Request(t,{...h,signal:p,method:i.toUpperCase(),headers:c.normalize().toJSON(),body:r,duplex:"half",credentials:w?d:void 0});let x=await fetch(f,h);const E=Or&&(u==="stream"||u==="response");if(Or&&(a||E&&v)){const C={};["status","statusText","headers"].forEach(j=>{C[j]=x[j]});const F=b.toFiniteNumber(x.headers.get("content-length")),[R,H]=a&&Gn(F,$i(Yn(a),!0))||[];x=new Response(ts(x.body,es,R,()=>{H&&H(),v&&v()}),C)}u=u||"text";let S=await Ti[b.findKey(Ti,u)||"text"](x,e);return!E&&v&&v(),await new Promise((C,F)=>{Ao(C,F,{data:S,headers:X.from(x.headers),status:x.status,statusText:x.statusText,config:e,request:f})})}catch(w){throw v&&v(),w&&w.name==="TypeError"&&/Load failed|fetch/i.test(w.message)?Object.assign(new k("Network Error",k.ERR_NETWORK,e,f),{cause:w.cause||w}):k.from(w,w&&w.code,e,f)}}),Fr={http:gu,xhr:Wu,fetch:ec};b.forEach(Fr,(e,t)=>{if(e){try{Object.defineProperty(e,"name",{value:t})}catch{}Object.defineProperty(e,"adapterName",{value:t})}});const is=e=>`- ${e}`,ic=e=>b.isFunction(e)||e===null||e===!1,Fo={getAdapter:e=>{e=b.isArray(e)?e:[e];const{length:t}=e;let i,r;const n={};for(let s=0;s<t;s++){i=e[s];let o;if(r=i,!ic(i)&&(r=Fr[(o=String(i)).toLowerCase()],r===void 0))throw new k(`Unknown adapter '${o}'`);if(r)break;n[o||"#"+s]=r}if(!r){const s=Object.entries(n).map(([a,l])=>`adapter ${a} `+(l===!1?"is not supported by the environment":"is not available in the build"));let o=t?s.length>1?`since :
`+s.map(is).join(`
`):" "+is(s[0]):"as no adapter specified";throw new k("There is no suitable adapter to dispatch the request "+o,"ERR_NOT_SUPPORT")}return r},adapters:Fr};function or(e){if(e.cancelToken&&e.cancelToken.throwIfRequested(),e.signal&&e.signal.aborted)throw new we(null,e)}function rs(e){return or(e),e.headers=X.from(e.headers),e.data=sr.call(e,e.transformRequest),["post","put","patch"].indexOf(e.method)!==-1&&e.headers.setContentType("application/x-www-form-urlencoded",!1),Fo.getAdapter(e.adapter||ei.adapter)(e).then(function(r){return or(e),r.data=sr.call(e,e.transformResponse,r),r.headers=X.from(r.headers),r},function(r){return ko(r)||(or(e),r&&r.response&&(r.response.data=sr.call(e,e.transformResponse,r.response),r.response.headers=X.from(r.response.headers))),Promise.reject(r)})}const Lo="1.11.0",qi={};["object","boolean","number","function","string","symbol"].forEach((e,t)=>{qi[e]=function(r){return typeof r===e||"a"+(t<1?"n ":" ")+e}});const ns={};qi.transitional=function(t,i,r){function n(s,o){return"[Axios v"+Lo+"] Transitional option '"+s+"'"+o+(r?". "+r:"")}return(s,o,a)=>{if(t===!1)throw new k(n(o," has been removed"+(i?" in "+i:"")),k.ERR_DEPRECATED);return i&&!ns[o]&&(ns[o]=!0,console.warn(n(o," has been deprecated since v"+i+" and will be removed in the near future"))),t?t(s,o,a):!0}};qi.spelling=function(t){return(i,r)=>(console.warn(`${r} is likely a misspelling of ${t}`),!0)};function rc(e,t,i){if(typeof e!="object")throw new k("options must be an object",k.ERR_BAD_OPTION_VALUE);const r=Object.keys(e);let n=r.length;for(;n-- >0;){const s=r[n],o=t[s];if(o){const a=e[s],l=a===void 0||o(a,s,e);if(l!==!0)throw new k("option "+s+" must be "+l,k.ERR_BAD_OPTION_VALUE);continue}if(i!==!0)throw new k("Unknown option "+s,k.ERR_BAD_OPTION)}}const _i={assertOptions:rc,validators:qi},vt=_i.validators;let Zt=class{constructor(t){this.defaults=t||{},this.interceptors={request:new Jn,response:new Jn}}async request(t,i){try{return await this._request(t,i)}catch(r){if(r instanceof Error){let n={};Error.captureStackTrace?Error.captureStackTrace(n):n=new Error;const s=n.stack?n.stack.replace(/^.+\n/,""):"";try{r.stack?s&&!String(r.stack).endsWith(s.replace(/^.+\n.+\n/,""))&&(r.stack+=`
`+s):r.stack=s}catch{}}throw r}}_request(t,i){typeof t=="string"?(i=i||{},i.url=t):i=t||{},i=se(this.defaults,i);const{transitional:r,paramsSerializer:n,headers:s}=i;r!==void 0&&_i.assertOptions(r,{silentJSONParsing:vt.transitional(vt.boolean),forcedJSONParsing:vt.transitional(vt.boolean),clarifyTimeoutError:vt.transitional(vt.boolean)},!1),n!=null&&(b.isFunction(n)?i.paramsSerializer={serialize:n}:_i.assertOptions(n,{encode:vt.function,serialize:vt.function},!0)),i.allowAbsoluteUrls!==void 0||(this.defaults.allowAbsoluteUrls!==void 0?i.allowAbsoluteUrls=this.defaults.allowAbsoluteUrls:i.allowAbsoluteUrls=!0),_i.assertOptions(i,{baseUrl:vt.spelling("baseURL"),withXsrfToken:vt.spelling("withXSRFToken")},!0),i.method=(i.method||this.defaults.method||"get").toLowerCase();let o=s&&b.merge(s.common,s[i.method]);s&&b.forEach(["delete","get","head","post","put","patch","common"],f=>{delete s[f]}),i.headers=X.concat(o,s);const a=[];let l=!0;this.interceptors.request.forEach(function(v){typeof v.runWhen=="function"&&v.runWhen(i)===!1||(l=l&&v.synchronous,a.unshift(v.fulfilled,v.rejected))});const u=[];this.interceptors.response.forEach(function(v){u.push(v.fulfilled,v.rejected)});let c,d=0,h;if(!l){const f=[rs.bind(this),void 0];for(f.unshift(...a),f.push(...u),h=f.length,c=Promise.resolve(i);d<h;)c=c.then(f[d++],f[d++]);return c}h=a.length;let p=i;for(d=0;d<h;){const f=a[d++],v=a[d++];try{p=f(p)}catch(m){v.call(this,m);break}}try{c=rs.call(this,p)}catch(f){return Promise.reject(f)}for(d=0,h=u.length;d<h;)c=c.then(u[d++],u[d++]);return c}getUri(t){t=se(this.defaults,t);const i=$o(t.baseURL,t.url,t.allowAbsoluteUrls);return Eo(i,t.params,t.paramsSerializer)}};b.forEach(["delete","get","head","options"],function(t){Zt.prototype[t]=function(i,r){return this.request(se(r||{},{method:t,url:i,data:(r||{}).data}))}});b.forEach(["post","put","patch"],function(t){function i(r){return function(s,o,a){return this.request(se(a||{},{method:t,headers:r?{"Content-Type":"multipart/form-data"}:{},url:s,data:o}))}}Zt.prototype[t]=i(),Zt.prototype[t+"Form"]=i(!0)});let nc=class Mo{constructor(t){if(typeof t!="function")throw new TypeError("executor must be a function.");let i;this.promise=new Promise(function(s){i=s});const r=this;this.promise.then(n=>{if(!r._listeners)return;let s=r._listeners.length;for(;s-- >0;)r._listeners[s](n);r._listeners=null}),this.promise.then=n=>{let s;const o=new Promise(a=>{r.subscribe(a),s=a}).then(n);return o.cancel=function(){r.unsubscribe(s)},o},t(function(s,o,a){r.reason||(r.reason=new we(s,o,a),i(r.reason))})}throwIfRequested(){if(this.reason)throw this.reason}subscribe(t){if(this.reason){t(this.reason);return}this._listeners?this._listeners.push(t):this._listeners=[t]}unsubscribe(t){if(!this._listeners)return;const i=this._listeners.indexOf(t);i!==-1&&this._listeners.splice(i,1)}toAbortSignal(){const t=new AbortController,i=r=>{t.abort(r)};return this.subscribe(i),t.signal.unsubscribe=()=>this.unsubscribe(i),t.signal}static source(){let t;return{token:new Mo(function(n){t=n}),cancel:t}}};function sc(e){return function(i){return e.apply(null,i)}}function oc(e){return b.isObject(e)&&e.isAxiosError===!0}const Lr={Continue:100,SwitchingProtocols:101,Processing:102,EarlyHints:103,Ok:200,Created:201,Accepted:202,NonAuthoritativeInformation:203,NoContent:204,ResetContent:205,PartialContent:206,MultiStatus:207,AlreadyReported:208,ImUsed:226,MultipleChoices:300,MovedPermanently:301,Found:302,SeeOther:303,NotModified:304,UseProxy:305,Unused:306,TemporaryRedirect:307,PermanentRedirect:308,BadRequest:400,Unauthorized:401,PaymentRequired:402,Forbidden:403,NotFound:404,MethodNotAllowed:405,NotAcceptable:406,ProxyAuthenticationRequired:407,RequestTimeout:408,Conflict:409,Gone:410,LengthRequired:411,PreconditionFailed:412,PayloadTooLarge:413,UriTooLong:414,UnsupportedMediaType:415,RangeNotSatisfiable:416,ExpectationFailed:417,ImATeapot:418,MisdirectedRequest:421,UnprocessableEntity:422,Locked:423,FailedDependency:424,TooEarly:425,UpgradeRequired:426,PreconditionRequired:428,TooManyRequests:429,RequestHeaderFieldsTooLarge:431,UnavailableForLegalReasons:451,InternalServerError:500,NotImplemented:501,BadGateway:502,ServiceUnavailable:503,GatewayTimeout:504,HttpVersionNotSupported:505,VariantAlsoNegotiates:506,InsufficientStorage:507,LoopDetected:508,NotExtended:510,NetworkAuthenticationRequired:511};Object.entries(Lr).forEach(([e,t])=>{Lr[t]=e});function Po(e){const t=new Zt(e),i=co(Zt.prototype.request,t);return b.extend(i,Zt.prototype,t,{allOwnKeys:!0}),b.extend(i,t,null,{allOwnKeys:!0}),i.create=function(n){return Po(se(e,n))},i}const B=Po(ei);B.Axios=Zt;B.CanceledError=we;B.CancelToken=nc;B.isCancel=ko;B.VERSION=Lo;B.toFormData=Ui;B.AxiosError=k;B.Cancel=B.CanceledError;B.all=function(t){return Promise.all(t)};B.spread=sc;B.isAxiosError=oc;B.mergeConfig=se;B.AxiosHeaders=X;B.formToJSON=e=>Co(b.isHTMLForm(e)?new FormData(e):e);B.getAdapter=Fo.getAdapter;B.HttpStatusCode=Lr;B.default=B;const{Axios:um,AxiosError:cm,CanceledError:dm,isCancel:hm,CancelToken:pm,VERSION:fm,all:mm,Cancel:gm,isAxiosError:bm,spread:vm,toFormData:ym,AxiosHeaders:wm,HttpStatusCode:_m,formToJSON:xm,getAdapter:Em,mergeConfig:Sm}=B;window.axios=B;window.axios.defaults.headers.common["X-Requested-With"]="XMLHttpRequest";window.fileManagerState=window.fileManagerState||{initialized:!1,initSource:null,instance:null};function ac(e,t={}){return window.fileManagerAlreadyInitialized?(console.info(`File Manager already initialized. Skipping ${e} initialization.`),window.fileManagerState.instance):window.fileManagerState.initialized?(console.info(`File Manager already initialized by ${window.fileManagerState.initSource}. Skipping ${e} initialization.`),window.fileManagerState.instance):(console.info(`Initializing File Manager from ${e}`),window.fileManagerAlreadyInitialized=!0,window.fileManagerState.initialized=!0,window.fileManagerState.initSource=e,e==="lazy-loader"?window.fileManagerState.instance=new FileManagerLazyLoader(t):e==="alpine"&&console.info("Alpine.js initialization will set the instance when ready"),window.fileManagerState.instance)}window.initializeFileManager=ac;class lc{constructor(){this.currentStep=null,this.progressBar=null,this.init()}init(){this.currentStep=this.getCurrentStep(),this.progressBar=document.querySelector("[data-progress-bar]"),this.initializeStepFunctionality(),this.initializeFormSubmission(),this.initializeProgressIndicator(),console.log("Setup Wizard initialized for step:",this.currentStep)}getCurrentStep(){const t=document.querySelector("[data-setup-step]");return t?t.dataset.setupStep:"welcome"}initializeStepFunctionality(){switch(this.currentStep){case"database":this.initializeDatabaseStep();break;case"admin":this.initializeAdminStep();break;case"storage":this.initializeStorageStep();break}}initializeDatabaseStep(){const t=document.getElementById("sqlite"),i=document.getElementById("mysql"),r=document.getElementById("sqlite-config"),n=document.getElementById("mysql-config"),s=document.getElementById("test-connection");if(!t||!i)return;const o=()=>{t.checked?(r==null||r.classList.remove("hidden"),n==null||n.classList.add("hidden"),this.updateFormValidation("sqlite")):(r==null||r.classList.add("hidden"),n==null||n.classList.remove("hidden"),this.updateFormValidation("mysql"))};t.addEventListener("change",o),i.addEventListener("change",o),o(),s&&s.addEventListener("click",()=>{this.testDatabaseConnection()}),this.initializeDatabaseValidation()}initializeAdminStep(){const t=document.getElementById("password"),i=document.getElementById("password_confirmation"),r=document.getElementById("email"),n=document.getElementById("toggle-password");!t||!i||!r||(n&&n.addEventListener("click",()=>{this.togglePasswordVisibility(t,n)}),t.addEventListener("input",()=>{this.checkPasswordStrength(t.value),this.validatePasswordMatch()}),i.addEventListener("input",()=>{this.validatePasswordMatch()}),r.addEventListener("blur",()=>{this.validateEmailAvailability(r.value)}),this.initializeAdminFormValidation())}initializeStorageStep(){const t=document.getElementById("toggle-secret"),i=document.getElementById("google_client_secret"),r=document.getElementById("test-google-connection"),n=document.getElementById("skip_storage"),s=document.getElementById("google-drive-config");t&&i&&t.addEventListener("click",()=>{this.togglePasswordVisibility(i,t)}),n&&s&&n.addEventListener("change",()=>{this.toggleStorageRequirements(n.checked,s)}),r&&r.addEventListener("click",()=>{this.testGoogleDriveConnection()}),this.initializeStorageValidation()}initializeFormSubmission(){document.querySelectorAll('form[id$="-form"]').forEach(i=>{i.addEventListener("submit",r=>{this.handleFormSubmission(i,r)})})}initializeProgressIndicator(){if(this.progressBar){const t=this.progressBar.style.width;this.animateProgressBar(t)}this.updateStepIndicators()}async testDatabaseConnection(){var n,s,o,a,l;const t=document.getElementById("test-connection"),i=document.getElementById("connection-status");if(!t||!i)return;const r=t.innerHTML;try{this.setButtonLoading(t,"Testing...");const u=new FormData;u.append("_token",this.getCsrfToken()),u.append("host",((n=document.getElementById("mysql_host"))==null?void 0:n.value)||""),u.append("port",((s=document.getElementById("mysql_port"))==null?void 0:s.value)||""),u.append("database",((o=document.getElementById("mysql_database"))==null?void 0:o.value)||""),u.append("username",((a=document.getElementById("mysql_username"))==null?void 0:a.value)||""),u.append("password",((l=document.getElementById("mysql_password"))==null?void 0:l.value)||"");const d=await(await fetch("/setup/ajax/test-database",{method:"POST",body:u,headers:{"X-Requested-With":"XMLHttpRequest"}})).json();i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(d.success,d.message)}catch(u){console.error("Database connection test failed:",u),i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(!1,"Connection test failed. Please try again.")}finally{this.restoreButtonState(t,r)}}async testGoogleDriveConnection(){var n,s;const t=document.getElementById("test-google-connection"),i=document.getElementById("google-connection-status");if(!t||!i)return;const r=t.innerHTML;try{this.setButtonLoading(t,"Testing...");const o=new FormData;o.append("_token",this.getCsrfToken()),o.append("client_id",((n=document.getElementById("google_client_id"))==null?void 0:n.value)||""),o.append("client_secret",((s=document.getElementById("google_client_secret"))==null?void 0:s.value)||"");const l=await(await fetch("/setup/ajax/test-storage",{method:"POST",body:o,headers:{"X-Requested-With":"XMLHttpRequest"}})).json();i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(l.success,l.message)}catch(o){console.error("Google Drive connection test failed:",o),i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(!1,"Connection test failed. Please try again.")}finally{this.restoreButtonState(t,r)}}async validateEmailAvailability(t){if(!(!t||!this.isValidEmail(t)))try{const i=new FormData;i.append("_token",this.getCsrfToken()),i.append("email",t);const n=await(await fetch("/setup/ajax/validate-email",{method:"POST",body:i,headers:{"X-Requested-With":"XMLHttpRequest"}})).json();this.showEmailValidationResult(n.available,n.message)}catch(i){console.error("Email validation failed:",i)}}checkPasswordStrength(t){const i=document.getElementById("strength-bar"),r=document.getElementById("strength-text");if(!i||!r)return;const n=this.calculatePasswordScore(t);i.style.width=n+"%",n===0?(r.textContent="Enter password",r.className="font-medium text-gray-400",i.className="h-2 rounded-full transition-all duration-300 bg-gray-300"):n<50?(r.textContent="Weak",r.className="font-medium text-red-600",i.className="h-2 rounded-full transition-all duration-300 bg-red-500"):n<75?(r.textContent="Fair",r.className="font-medium text-yellow-600",i.className="h-2 rounded-full transition-all duration-300 bg-yellow-500"):n<100?(r.textContent="Good",r.className="font-medium text-blue-600",i.className="h-2 rounded-full transition-all duration-300 bg-blue-500"):(r.textContent="Strong",r.className="font-medium text-green-600",i.className="h-2 rounded-full transition-all duration-300 bg-green-500"),this.updatePasswordRequirements(t)}calculatePasswordScore(t){let i=0;return t.length>=8&&(i+=20),/[A-Z]/.test(t)&&(i+=20),/[a-z]/.test(t)&&(i+=20),/[0-9]/.test(t)&&(i+=20),/[^A-Za-z0-9]/.test(t)&&(i+=20),i}updatePasswordRequirements(t){[{id:"req-length",test:t.length>=8},{id:"req-uppercase",test:/[A-Z]/.test(t)},{id:"req-lowercase",test:/[a-z]/.test(t)},{id:"req-number",test:/[0-9]/.test(t)},{id:"req-special",test:/[^A-Za-z0-9]/.test(t)}].forEach(r=>{var s,o,a,l;const n=document.getElementById(r.id);n&&(r.test?(n.classList.remove("text-gray-600"),n.classList.add("text-green-600"),(s=n.querySelector("svg"))==null||s.classList.remove("text-gray-400"),(o=n.querySelector("svg"))==null||o.classList.add("text-green-500")):(n.classList.remove("text-green-600"),n.classList.add("text-gray-600"),(a=n.querySelector("svg"))==null||a.classList.remove("text-green-500"),(l=n.querySelector("svg"))==null||l.classList.add("text-gray-400")))})}validatePasswordMatch(){var a,l;const t=((a=document.getElementById("password"))==null?void 0:a.value)||"",i=((l=document.getElementById("password_confirmation"))==null?void 0:l.value)||"",r=document.getElementById("password-match-indicator"),n=document.getElementById("match-success"),s=document.getElementById("match-error"),o=document.getElementById("password-match-text");if(!(!r||!n||!s||!o)){if(i.length===0){r.classList.add("hidden"),o.textContent="Re-enter your password to confirm",o.className="mt-2 text-sm text-gray-500";return}r.classList.remove("hidden"),t===i?(n.classList.remove("hidden"),s.classList.add("hidden"),o.textContent="Passwords match",o.className="mt-2 text-sm text-green-600"):(n.classList.add("hidden"),s.classList.remove("hidden"),o.textContent="Passwords do not match",o.className="mt-2 text-sm text-red-600")}}togglePasswordVisibility(t,i){const r=t.getAttribute("type")==="password"?"text":"password";t.setAttribute("type",r);const n=i.querySelector('[id$="eye-closed"], [id$="-eye-closed"]'),s=i.querySelector('[id$="eye-open"], [id$="-eye-open"]');r==="text"?(n==null||n.classList.add("hidden"),s==null||s.classList.remove("hidden")):(n==null||n.classList.remove("hidden"),s==null||s.classList.add("hidden"))}toggleStorageRequirements(t,i){t?(i.style.opacity="0.5",i.style.pointerEvents="none",document.getElementById("google_client_id").required=!1,document.getElementById("google_client_secret").required=!1):(i.style.opacity="1",i.style.pointerEvents="auto",document.getElementById("google_client_id").required=!0,document.getElementById("google_client_secret").required=!0)}handleFormSubmission(t,i){const r=t.querySelector('button[type="submit"]');if(!r)return;const n=r.innerHTML;this.setButtonLoading(r,"Processing...");const s=t.querySelectorAll("input, select, textarea, button");s.forEach(o=>{o.disabled=!0}),setTimeout(()=>{s.forEach(o=>{o.disabled=!1}),this.restoreButtonState(r,n)},1e4)}initializeDatabaseValidation(){["mysql_host","mysql_port","mysql_database","mysql_username"].forEach(i=>{const r=document.getElementById(i);r&&r.addEventListener("blur",()=>{this.validateDatabaseField(i,r.value)})})}initializeAdminFormValidation(){const t=document.getElementById("email"),i=document.getElementById("password"),r=document.getElementById("password_confirmation"),n=document.getElementById("submit-btn");if(!t||!i||!r||!n)return;const s=()=>{const o=t.value,a=i.value,l=r.value,u=this.calculatePasswordScore(a),c=this.isValidEmail(o)&&u===100&&a===l&&l.length>0;n.disabled=!c};t.addEventListener("input",s),i.addEventListener("input",s),r.addEventListener("input",s),s()}initializeStorageValidation(){const t=document.getElementById("google_client_id"),i=document.getElementById("google_client_secret");t&&t.addEventListener("blur",()=>{this.validateGoogleClientId(t.value)}),i&&i.addEventListener("blur",()=>{this.validateGoogleClientSecret(i.value)})}validateDatabaseField(t,i){const r=document.getElementById(t);if(!r)return;let n=!0,s="";switch(t){case"mysql_host":n=i.length>0,s=n?"":"Host is required";break;case"mysql_port":n=/^\d+$/.test(i)&&parseInt(i)>0&&parseInt(i)<=65535,s=n?"":"Port must be a valid number between 1 and 65535";break;case"mysql_database":n=/^[a-zA-Z0-9_]+$/.test(i),s=n?"":"Database name can only contain letters, numbers, and underscores";break;case"mysql_username":n=i.length>0,s=n?"":"Username is required";break}this.showFieldValidation(r,n,s)}validateGoogleClientId(t){const i=document.getElementById("google_client_id");if(!i)return;const r=/^[0-9]+-[a-zA-Z0-9]+\.apps\.googleusercontent\.com$/.test(t),n=r?"":"Client ID should end with .apps.googleusercontent.com";this.showFieldValidation(i,r,n)}validateGoogleClientSecret(t){const i=document.getElementById("google_client_secret");if(!i)return;const r=/^GOCSPX-[a-zA-Z0-9_-]+$/.test(t),n=r?"":"Client Secret should start with GOCSPX-";this.showFieldValidation(i,r,n)}showFieldValidation(t,i,r){t.classList.remove("border-red-300","border-green-300");const n=t.parentNode.querySelector(".validation-message");if(n&&n.remove(),r){t.classList.add(i?"border-green-300":"border-red-300");const s=document.createElement("p");s.className=`mt-1 text-sm validation-message ${i?"text-green-600":"text-red-600"}`,s.textContent=r,t.parentNode.appendChild(s)}}showEmailValidationResult(t,i){const r=document.getElementById("email");r&&this.showFieldValidation(r,t,i)}updateFormValidation(t){["mysql_host","mysql_port","mysql_database","mysql_username"].forEach(r=>{const n=document.getElementById(r);n&&(n.required=t==="mysql")})}animateProgressBar(t){this.progressBar&&(this.progressBar.style.transition="width 0.5s ease-out",setTimeout(()=>{this.progressBar.style.width=t},100))}updateStepIndicators(){document.querySelectorAll("[data-step-indicator]").forEach(i=>{const r=i.dataset.stepIndicator,n=this.isStepCompleted(r),s=r===this.currentStep;n&&i.classList.add("completed"),s&&i.classList.add("current")})}isStepCompleted(t){if(!this.currentStep||!t)return!1;const i=["welcome","database","admin","storage","complete"],r=i.indexOf(this.currentStep),n=i.indexOf(t);return r===-1||n===-1?!1:n<r}setButtonLoading(t,i){t.disabled=!0,t.innerHTML=`
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
        `}getCsrfToken(){const t=document.querySelector('meta[name="csrf-token"]');return t?t.getAttribute("content"):""}isValidEmail(t){return/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t)}}document.addEventListener("DOMContentLoaded",function(){if(document.querySelector("[data-setup-step]")&&window.location.pathname.startsWith("/setup"))try{new lc}catch(t){console.warn("Setup wizard initialization failed:",t)}});class zo{constructor(){this.currentTestJobId=null,this.testStartTime=null,this.pollingInterval=null,this.elapsedTimeInterval=null,this.initializeElements(),this.bindEvents()}initializeElements(){this.testQueueBtn=document.getElementById("test-queue-btn"),this.testQueueBtnText=document.getElementById("test-queue-btn-text"),this.testResultsSection=document.getElementById("test-results-section"),this.currentTestProgress=document.getElementById("current-test-progress"),this.testProgressMessage=document.getElementById("test-progress-message"),this.testElapsedTime=document.getElementById("test-elapsed-time"),this.testResultsDisplay=document.getElementById("test-results-display")}bindEvents(){this.testQueueBtn&&this.testQueueBtn.addEventListener("click",()=>this.startQueueTest())}async startQueueTest(){var t;if(this.currentTestJobId){console.warn("Test already in progress");return}try{this.setTestInProgress(!0),this.testStartTime=Date.now(),this.startElapsedTimeCounter();const i=await this.dispatchTestJob();if(i.success&&i.data)this.currentTestJobId=i.data.test_job_id,this.updateProgressMessage("Test job dispatched, waiting for processing..."),this.startPolling();else throw new Error(i.message||((t=i.error)==null?void 0:t.message)||"Failed to dispatch test job")}catch(i){console.error("Queue test failed:",i),this.handleTestError(i.message)}}async dispatchTestJob(){var i;const t=await fetch("/admin/queue/test",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":((i=document.querySelector('meta[name="csrf-token"]'))==null?void 0:i.getAttribute("content"))||""},body:JSON.stringify({delay:0})});if(!t.ok)throw new Error(`HTTP ${t.status}: ${t.statusText}`);return await t.json()}startPolling(){this.pollingInterval&&clearInterval(this.pollingInterval),this.pollingInterval=setInterval(async()=>{try{await this.checkTestJobStatus()}catch(t){console.error("Polling error:",t),this.handleTestError("Failed to check test status")}},1e3),setTimeout(()=>{this.currentTestJobId&&this.handleTestTimeout()},3e4)}async checkTestJobStatus(){var r;if(!this.currentTestJobId)return;const t=await fetch(`/admin/queue/test/status?test_job_id=${this.currentTestJobId}`,{method:"GET",headers:{"X-CSRF-TOKEN":((r=document.querySelector('meta[name="csrf-token"]'))==null?void 0:r.getAttribute("content"))||""}});if(!t.ok)throw new Error(`HTTP ${t.status}: ${t.statusText}`);const i=await t.json();if(i.success&&i.data&&i.data.status){const n=i.data.status;switch(n.status){case"completed":this.handleTestSuccess(n);break;case"failed":this.handleTestFailure(n);break;case"timeout":this.handleTestTimeout();break;case"processing":this.updateProgressMessage("Test job is being processed...");break;case"pending":this.updateProgressMessage("Test job is queued, waiting for worker...");break}}}handleTestSuccess(t){this.stopTest();const i=t.processing_time||0,r=Date.now()-this.testStartTime,n={status:"success",message:`Queue worker is functioning properly! Job completed in ${i.toFixed(2)}s`,details:{processing_time:i,total_time:(r/1e3).toFixed(2),completed_at:t.completed_at||new Date().toISOString(),job_id:this.currentTestJobId},timestamp:Date.now()};this.displayTestResult(n),this.showSuccessNotification(`Queue worker completed test in ${i.toFixed(2)}s`)}handleTestFailure(t){this.stopTest();const i={status:"failed",message:"Queue test failed: "+(t.error_message||"Unknown error"),details:{error:t.error_message,failed_at:t.failed_at||new Date().toISOString(),job_id:this.currentTestJobId},timestamp:Date.now()};this.displayTestResult(i)}handleTestTimeout(){this.stopTest();const t={status:"timeout",message:"Queue test timed out after 30 seconds. The queue worker may not be running.",details:{timeout_duration:30,job_id:this.currentTestJobId,timed_out_at:new Date().toISOString()},timestamp:Date.now()};this.displayTestResult(t)}handleTestError(t){this.stopTest();const i={status:"error",message:"Test error: "+t,details:{error:t,error_at:new Date().toISOString()},timestamp:Date.now()};this.displayTestResult(i),this.showDetailedError(new Error(t),"Queue test execution")}stopTest(){this.pollingInterval&&(clearInterval(this.pollingInterval),this.pollingInterval=null),this.elapsedTimeInterval&&(clearInterval(this.elapsedTimeInterval),this.elapsedTimeInterval=null),this.currentTestJobId=null,this.testStartTime=null,this.setTestInProgress(!1),this.hideCurrentTestProgress()}setTestInProgress(t){this.testQueueBtn&&(this.setLoadingStateWithAnimation(t),t&&this.showCurrentTestProgress())}showCurrentTestProgress(){this.currentTestProgress&&this.currentTestProgress.classList.remove("hidden"),this.testResultsSection&&this.testResultsSection.classList.remove("hidden")}hideCurrentTestProgress(){this.currentTestProgress&&this.currentTestProgress.classList.add("hidden")}updateProgressMessage(t){this.testProgressMessage&&this.updateProgressWithAnimation(t)}startElapsedTimeCounter(){this.elapsedTimeInterval&&clearInterval(this.elapsedTimeInterval),this.elapsedTimeInterval=setInterval(()=>{if(this.testStartTime&&this.testElapsedTime){const t=((Date.now()-this.testStartTime)/1e3).toFixed(1);this.testElapsedTime.textContent=`(${t}s)`}},100)}displayTestResult(t){if(!this.testResultsDisplay)return;const i=this.createTestResultElement(t);i.style.opacity="0",i.style.transform="translateY(-10px)",i.style.transition="all 0.3s ease-in-out",this.testResultsDisplay.insertBefore(i,this.testResultsDisplay.firstChild),setTimeout(()=>{i.style.opacity="1",i.style.transform="translateY(0)"},10),this.testResultsSection&&this.testResultsSection.classList.remove("hidden"),this.addResultAnimation(i,t.status);const r=this.testResultsDisplay.children;for(;r.length>5;){const n=r[r.length-1];this.animateResultRemoval(n)}}createTestResultElement(t){var l,u;const i=document.createElement("div");let r,n,s,o="";switch(t.status){case"success":r="bg-green-50 border-green-200",n="text-green-900",o="animate-pulse-success",s=`<svg class="h-5 w-5 text-green-600 animate-bounce-once" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`;break;case"failed":case"error":r="bg-red-50 border-red-200",n="text-red-900",o="animate-pulse-error",s=`<svg class="h-5 w-5 text-red-600 animate-shake" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`;break;case"timeout":r="bg-yellow-50 border-yellow-200",n="text-yellow-900",o="animate-pulse-warning",s=`<svg class="h-5 w-5 text-yellow-600 animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`;break}const a=new Date(t.timestamp).toLocaleString();return i.className=`border rounded-lg p-4 ${r} ${o} transition-all duration-300 hover:shadow-md`,i.innerHTML=`
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    ${s}
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-sm font-medium ${n}">
                        ${t.message}
                    </div>
                    <div class="text-sm text-gray-600 mt-1">
                        ${a}
                        ${(l=t.details)!=null&&l.processing_time?` • Processing: ${t.details.processing_time}s`:""}
                        ${(u=t.details)!=null&&u.total_time?` • Total: ${t.details.total_time}s`:""}
                    </div>
                    ${this.createResultDetailsSection(t)}
                </div>
            </div>
        `,i}addResultAnimation(t,i){if(!(!t||!t.classList))switch(i){case"success":t.classList.add("animate-success-glow"),setTimeout(()=>t.classList.remove("animate-success-glow"),2e3);break;case"failed":case"error":t.classList.add("animate-error-shake"),setTimeout(()=>t.classList.remove("animate-error-shake"),1e3);break;case"timeout":t.classList.add("animate-warning-pulse"),setTimeout(()=>t.classList.remove("animate-warning-pulse"),3e3);break}}animateResultRemoval(t){t&&(t.style.transition="all 0.3s ease-out",t.style.opacity="0",t.style.transform="translateX(100%)",setTimeout(()=>{t.parentNode&&t.parentNode.removeChild(t)},300))}createResultDetailsSection(t){if(!t.details)return"";const i=[];return t.details.job_id&&i.push(`Job ID: ${t.details.job_id}`),t.details.error&&i.push(`Error: ${t.details.error}`),t.details.timeout_duration&&i.push(`Timeout: ${t.details.timeout_duration}s`),i.length===0?"":`
            <div class="mt-2 text-xs text-gray-500 border-t border-gray-200 pt-2">
                ${i.join(" • ")}
            </div>
        `}updateProgressWithAnimation(t){this.testProgressMessage&&(this.testProgressMessage.style.opacity="0.5",setTimeout(()=>{this.testProgressMessage.textContent=t,this.testProgressMessage.style.opacity="1"},150))}setLoadingStateWithAnimation(t){if(!(!this.testQueueBtn||!this.testQueueBtnText))if(t){this.testQueueBtn.disabled=!0,this.testQueueBtn.classList.add("opacity-75","cursor-not-allowed"),this.testQueueBtnText.textContent="Testing...";const i=this.testQueueBtn.querySelector("svg");i&&i.classList.add("animate-spin")}else{this.testQueueBtn.disabled=!1,this.testQueueBtn.classList.remove("opacity-75","cursor-not-allowed"),this.testQueueBtnText.textContent="Test Queue Worker";const i=this.testQueueBtn.querySelector("svg");i&&i.classList.remove("animate-spin")}}showDetailedError(t,i=""){const r=document.createElement("div");r.className="fixed top-4 right-4 max-w-md bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg z-50 animate-slide-in-right",r.innerHTML=`
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
        `,document.body.appendChild(r),setTimeout(()=>{r.parentNode&&(r.style.opacity="0",r.style.transform="translateX(100%)",setTimeout(()=>r.remove(),300))},5e3)}showSuccessNotification(t){const i=document.createElement("div");i.className="fixed top-4 right-4 max-w-md bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg z-50 animate-slide-in-right",i.innerHTML=`
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
 */const xi=globalThis,ln=xi.ShadowRoot&&(xi.ShadyCSS===void 0||xi.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,un=Symbol(),ss=new WeakMap;let Io=class{constructor(t,i,r){if(this._$cssResult$=!0,r!==un)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=t,this.t=i}get styleSheet(){let t=this.o;const i=this.t;if(ln&&t===void 0){const r=i!==void 0&&i.length===1;r&&(t=ss.get(i)),t===void 0&&((this.o=t=new CSSStyleSheet).replaceSync(this.cssText),r&&ss.set(i,t))}return t}toString(){return this.cssText}};const uc=e=>new Io(typeof e=="string"?e:e+"",void 0,un),ft=(e,...t)=>{const i=e.length===1?e[0]:t.reduce(((r,n,s)=>r+(o=>{if(o._$cssResult$===!0)return o.cssText;if(typeof o=="number")return o;throw Error("Value passed to 'css' function must be a 'css' function result: "+o+". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.")})(n)+e[s+1]),e[0]);return new Io(i,e,un)},cc=(e,t)=>{if(ln)e.adoptedStyleSheets=t.map((i=>i instanceof CSSStyleSheet?i:i.styleSheet));else for(const i of t){const r=document.createElement("style"),n=xi.litNonce;n!==void 0&&r.setAttribute("nonce",n),r.textContent=i.cssText,e.appendChild(r)}},os=ln?e=>e:e=>e instanceof CSSStyleSheet?(t=>{let i="";for(const r of t.cssRules)i+=r.cssText;return uc(i)})(e):e;/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const{is:dc,defineProperty:hc,getOwnPropertyDescriptor:pc,getOwnPropertyNames:fc,getOwnPropertySymbols:mc,getPrototypeOf:gc}=Object,It=globalThis,as=It.trustedTypes,bc=as?as.emptyScript:"",ar=It.reactiveElementPolyfillSupport,Ue=(e,t)=>e,fe={toAttribute(e,t){switch(t){case Boolean:e=e?bc:null;break;case Object:case Array:e=e==null?e:JSON.stringify(e)}return e},fromAttribute(e,t){let i=e;switch(t){case Boolean:i=e!==null;break;case Number:i=e===null?null:Number(e);break;case Object:case Array:try{i=JSON.parse(e)}catch{i=null}}return i}},cn=(e,t)=>!dc(e,t),ls={attribute:!0,type:String,converter:fe,reflect:!1,useDefault:!1,hasChanged:cn};Symbol.metadata??(Symbol.metadata=Symbol("metadata")),It.litPropertyMetadata??(It.litPropertyMetadata=new WeakMap);let de=class extends HTMLElement{static addInitializer(t){this._$Ei(),(this.l??(this.l=[])).push(t)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(t,i=ls){if(i.state&&(i.attribute=!1),this._$Ei(),this.prototype.hasOwnProperty(t)&&((i=Object.create(i)).wrapped=!0),this.elementProperties.set(t,i),!i.noAccessor){const r=Symbol(),n=this.getPropertyDescriptor(t,r,i);n!==void 0&&hc(this.prototype,t,n)}}static getPropertyDescriptor(t,i,r){const{get:n,set:s}=pc(this.prototype,t)??{get(){return this[i]},set(o){this[i]=o}};return{get:n,set(o){const a=n==null?void 0:n.call(this);s==null||s.call(this,o),this.requestUpdate(t,a,r)},configurable:!0,enumerable:!0}}static getPropertyOptions(t){return this.elementProperties.get(t)??ls}static _$Ei(){if(this.hasOwnProperty(Ue("elementProperties")))return;const t=gc(this);t.finalize(),t.l!==void 0&&(this.l=[...t.l]),this.elementProperties=new Map(t.elementProperties)}static finalize(){if(this.hasOwnProperty(Ue("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(Ue("properties"))){const i=this.properties,r=[...fc(i),...mc(i)];for(const n of r)this.createProperty(n,i[n])}const t=this[Symbol.metadata];if(t!==null){const i=litPropertyMetadata.get(t);if(i!==void 0)for(const[r,n]of i)this.elementProperties.set(r,n)}this._$Eh=new Map;for(const[i,r]of this.elementProperties){const n=this._$Eu(i,r);n!==void 0&&this._$Eh.set(n,i)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(t){const i=[];if(Array.isArray(t)){const r=new Set(t.flat(1/0).reverse());for(const n of r)i.unshift(os(n))}else t!==void 0&&i.push(os(t));return i}static _$Eu(t,i){const r=i.attribute;return r===!1?void 0:typeof r=="string"?r:typeof t=="string"?t.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){var t;this._$ES=new Promise((i=>this.enableUpdating=i)),this._$AL=new Map,this._$E_(),this.requestUpdate(),(t=this.constructor.l)==null||t.forEach((i=>i(this)))}addController(t){var i;(this._$EO??(this._$EO=new Set)).add(t),this.renderRoot!==void 0&&this.isConnected&&((i=t.hostConnected)==null||i.call(t))}removeController(t){var i;(i=this._$EO)==null||i.delete(t)}_$E_(){const t=new Map,i=this.constructor.elementProperties;for(const r of i.keys())this.hasOwnProperty(r)&&(t.set(r,this[r]),delete this[r]);t.size>0&&(this._$Ep=t)}createRenderRoot(){const t=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return cc(t,this.constructor.elementStyles),t}connectedCallback(){var t;this.renderRoot??(this.renderRoot=this.createRenderRoot()),this.enableUpdating(!0),(t=this._$EO)==null||t.forEach((i=>{var r;return(r=i.hostConnected)==null?void 0:r.call(i)}))}enableUpdating(t){}disconnectedCallback(){var t;(t=this._$EO)==null||t.forEach((i=>{var r;return(r=i.hostDisconnected)==null?void 0:r.call(i)}))}attributeChangedCallback(t,i,r){this._$AK(t,r)}_$ET(t,i){var s;const r=this.constructor.elementProperties.get(t),n=this.constructor._$Eu(t,r);if(n!==void 0&&r.reflect===!0){const o=(((s=r.converter)==null?void 0:s.toAttribute)!==void 0?r.converter:fe).toAttribute(i,r.type);this._$Em=t,o==null?this.removeAttribute(n):this.setAttribute(n,o),this._$Em=null}}_$AK(t,i){var s,o;const r=this.constructor,n=r._$Eh.get(t);if(n!==void 0&&this._$Em!==n){const a=r.getPropertyOptions(n),l=typeof a.converter=="function"?{fromAttribute:a.converter}:((s=a.converter)==null?void 0:s.fromAttribute)!==void 0?a.converter:fe;this._$Em=n;const u=l.fromAttribute(i,a.type);this[n]=u??((o=this._$Ej)==null?void 0:o.get(n))??u,this._$Em=null}}requestUpdate(t,i,r){var n;if(t!==void 0){const s=this.constructor,o=this[t];if(r??(r=s.getPropertyOptions(t)),!((r.hasChanged??cn)(o,i)||r.useDefault&&r.reflect&&o===((n=this._$Ej)==null?void 0:n.get(t))&&!this.hasAttribute(s._$Eu(t,r))))return;this.C(t,i,r)}this.isUpdatePending===!1&&(this._$ES=this._$EP())}C(t,i,{useDefault:r,reflect:n,wrapped:s},o){r&&!(this._$Ej??(this._$Ej=new Map)).has(t)&&(this._$Ej.set(t,o??i??this[t]),s!==!0||o!==void 0)||(this._$AL.has(t)||(this.hasUpdated||r||(i=void 0),this._$AL.set(t,i)),n===!0&&this._$Em!==t&&(this._$Eq??(this._$Eq=new Set)).add(t))}async _$EP(){this.isUpdatePending=!0;try{await this._$ES}catch(i){Promise.reject(i)}const t=this.scheduleUpdate();return t!=null&&await t,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){var r;if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??(this.renderRoot=this.createRenderRoot()),this._$Ep){for(const[s,o]of this._$Ep)this[s]=o;this._$Ep=void 0}const n=this.constructor.elementProperties;if(n.size>0)for(const[s,o]of n){const{wrapped:a}=o,l=this[s];a!==!0||this._$AL.has(s)||l===void 0||this.C(s,void 0,o,l)}}let t=!1;const i=this._$AL;try{t=this.shouldUpdate(i),t?(this.willUpdate(i),(r=this._$EO)==null||r.forEach((n=>{var s;return(s=n.hostUpdate)==null?void 0:s.call(n)})),this.update(i)):this._$EM()}catch(n){throw t=!1,this._$EM(),n}t&&this._$AE(i)}willUpdate(t){}_$AE(t){var i;(i=this._$EO)==null||i.forEach((r=>{var n;return(n=r.hostUpdated)==null?void 0:n.call(r)})),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(t)),this.updated(t)}_$EM(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(t){return!0}update(t){this._$Eq&&(this._$Eq=this._$Eq.forEach((i=>this._$ET(i,this[i])))),this._$EM()}updated(t){}firstUpdated(t){}};de.elementStyles=[],de.shadowRootOptions={mode:"open"},de[Ue("elementProperties")]=new Map,de[Ue("finalized")]=new Map,ar==null||ar({ReactiveElement:de}),(It.reactiveElementVersions??(It.reactiveElementVersions=[])).push("2.1.1");/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const He=globalThis,Ri=He.trustedTypes,us=Ri?Ri.createPolicy("lit-html",{createHTML:e=>e}):void 0,Bo="$lit$",Pt=`lit$${Math.random().toFixed(9).slice(2)}$`,Do="?"+Pt,vc=`<${Do}>`,oe=document,Ke=()=>oe.createComment(""),Qe=e=>e===null||typeof e!="object"&&typeof e!="function",dn=Array.isArray,yc=e=>dn(e)||typeof(e==null?void 0:e[Symbol.iterator])=="function",lr=`[ 	
\f\r]`,Oe=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,cs=/-->/g,ds=/>/g,jt=RegExp(`>|${lr}(?:([^\\s"'>=/]+)(${lr}*=${lr}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`,"g"),hs=/'/g,ps=/"/g,No=/^(?:script|style|textarea|title)$/i,wc=e=>(t,...i)=>({_$litType$:e,strings:t,values:i}),I=wc(1),ot=Symbol.for("lit-noChange"),D=Symbol.for("lit-nothing"),fs=new WeakMap,Xt=oe.createTreeWalker(oe,129);function Uo(e,t){if(!dn(e)||!e.hasOwnProperty("raw"))throw Error("invalid template strings array");return us!==void 0?us.createHTML(t):t}const _c=(e,t)=>{const i=e.length-1,r=[];let n,s=t===2?"<svg>":t===3?"<math>":"",o=Oe;for(let a=0;a<i;a++){const l=e[a];let u,c,d=-1,h=0;for(;h<l.length&&(o.lastIndex=h,c=o.exec(l),c!==null);)h=o.lastIndex,o===Oe?c[1]==="!--"?o=cs:c[1]!==void 0?o=ds:c[2]!==void 0?(No.test(c[2])&&(n=RegExp("</"+c[2],"g")),o=jt):c[3]!==void 0&&(o=jt):o===jt?c[0]===">"?(o=n??Oe,d=-1):c[1]===void 0?d=-2:(d=o.lastIndex-c[2].length,u=c[1],o=c[3]===void 0?jt:c[3]==='"'?ps:hs):o===ps||o===hs?o=jt:o===cs||o===ds?o=Oe:(o=jt,n=void 0);const p=o===jt&&e[a+1].startsWith("/>")?" ":"";s+=o===Oe?l+vc:d>=0?(r.push(u),l.slice(0,d)+Bo+l.slice(d)+Pt+p):l+Pt+(d===-2?a:p)}return[Uo(e,s+(e[i]||"<?>")+(t===2?"</svg>":t===3?"</math>":"")),r]};class Je{constructor({strings:t,_$litType$:i},r){let n;this.parts=[];let s=0,o=0;const a=t.length-1,l=this.parts,[u,c]=_c(t,i);if(this.el=Je.createElement(u,r),Xt.currentNode=this.el.content,i===2||i===3){const d=this.el.content.firstChild;d.replaceWith(...d.childNodes)}for(;(n=Xt.nextNode())!==null&&l.length<a;){if(n.nodeType===1){if(n.hasAttributes())for(const d of n.getAttributeNames())if(d.endsWith(Bo)){const h=c[o++],p=n.getAttribute(d).split(Pt),f=/([.?@])?(.*)/.exec(h);l.push({type:1,index:s,name:f[2],strings:p,ctor:f[1]==="."?Ec:f[1]==="?"?Sc:f[1]==="@"?Cc:Vi}),n.removeAttribute(d)}else d.startsWith(Pt)&&(l.push({type:6,index:s}),n.removeAttribute(d));if(No.test(n.tagName)){const d=n.textContent.split(Pt),h=d.length-1;if(h>0){n.textContent=Ri?Ri.emptyScript:"";for(let p=0;p<h;p++)n.append(d[p],Ke()),Xt.nextNode(),l.push({type:2,index:++s});n.append(d[h],Ke())}}}else if(n.nodeType===8)if(n.data===Do)l.push({type:2,index:s});else{let d=-1;for(;(d=n.data.indexOf(Pt,d+1))!==-1;)l.push({type:7,index:s}),d+=Pt.length-1}s++}}static createElement(t,i){const r=oe.createElement("template");return r.innerHTML=t,r}}function me(e,t,i=e,r){var o,a;if(t===ot)return t;let n=r!==void 0?(o=i._$Co)==null?void 0:o[r]:i._$Cl;const s=Qe(t)?void 0:t._$litDirective$;return(n==null?void 0:n.constructor)!==s&&((a=n==null?void 0:n._$AO)==null||a.call(n,!1),s===void 0?n=void 0:(n=new s(e),n._$AT(e,i,r)),r!==void 0?(i._$Co??(i._$Co=[]))[r]=n:i._$Cl=n),n!==void 0&&(t=me(e,n._$AS(e,t.values),n,r)),t}class xc{constructor(t,i){this._$AV=[],this._$AN=void 0,this._$AD=t,this._$AM=i}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(t){const{el:{content:i},parts:r}=this._$AD,n=((t==null?void 0:t.creationScope)??oe).importNode(i,!0);Xt.currentNode=n;let s=Xt.nextNode(),o=0,a=0,l=r[0];for(;l!==void 0;){if(o===l.index){let u;l.type===2?u=new ii(s,s.nextSibling,this,t):l.type===1?u=new l.ctor(s,l.name,l.strings,this,t):l.type===6&&(u=new kc(s,this,t)),this._$AV.push(u),l=r[++a]}o!==(l==null?void 0:l.index)&&(s=Xt.nextNode(),o++)}return Xt.currentNode=oe,n}p(t){let i=0;for(const r of this._$AV)r!==void 0&&(r.strings!==void 0?(r._$AI(t,r,i),i+=r.strings.length-2):r._$AI(t[i])),i++}}class ii{get _$AU(){var t;return((t=this._$AM)==null?void 0:t._$AU)??this._$Cv}constructor(t,i,r,n){this.type=2,this._$AH=D,this._$AN=void 0,this._$AA=t,this._$AB=i,this._$AM=r,this.options=n,this._$Cv=(n==null?void 0:n.isConnected)??!0}get parentNode(){let t=this._$AA.parentNode;const i=this._$AM;return i!==void 0&&(t==null?void 0:t.nodeType)===11&&(t=i.parentNode),t}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(t,i=this){t=me(this,t,i),Qe(t)?t===D||t==null||t===""?(this._$AH!==D&&this._$AR(),this._$AH=D):t!==this._$AH&&t!==ot&&this._(t):t._$litType$!==void 0?this.$(t):t.nodeType!==void 0?this.T(t):yc(t)?this.k(t):this._(t)}O(t){return this._$AA.parentNode.insertBefore(t,this._$AB)}T(t){this._$AH!==t&&(this._$AR(),this._$AH=this.O(t))}_(t){this._$AH!==D&&Qe(this._$AH)?this._$AA.nextSibling.data=t:this.T(oe.createTextNode(t)),this._$AH=t}$(t){var s;const{values:i,_$litType$:r}=t,n=typeof r=="number"?this._$AC(t):(r.el===void 0&&(r.el=Je.createElement(Uo(r.h,r.h[0]),this.options)),r);if(((s=this._$AH)==null?void 0:s._$AD)===n)this._$AH.p(i);else{const o=new xc(n,this),a=o.u(this.options);o.p(i),this.T(a),this._$AH=o}}_$AC(t){let i=fs.get(t.strings);return i===void 0&&fs.set(t.strings,i=new Je(t)),i}k(t){dn(this._$AH)||(this._$AH=[],this._$AR());const i=this._$AH;let r,n=0;for(const s of t)n===i.length?i.push(r=new ii(this.O(Ke()),this.O(Ke()),this,this.options)):r=i[n],r._$AI(s),n++;n<i.length&&(this._$AR(r&&r._$AB.nextSibling,n),i.length=n)}_$AR(t=this._$AA.nextSibling,i){var r;for((r=this._$AP)==null?void 0:r.call(this,!1,!0,i);t!==this._$AB;){const n=t.nextSibling;t.remove(),t=n}}setConnected(t){var i;this._$AM===void 0&&(this._$Cv=t,(i=this._$AP)==null||i.call(this,t))}}class Vi{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(t,i,r,n,s){this.type=1,this._$AH=D,this._$AN=void 0,this.element=t,this.name=i,this._$AM=n,this.options=s,r.length>2||r[0]!==""||r[1]!==""?(this._$AH=Array(r.length-1).fill(new String),this.strings=r):this._$AH=D}_$AI(t,i=this,r,n){const s=this.strings;let o=!1;if(s===void 0)t=me(this,t,i,0),o=!Qe(t)||t!==this._$AH&&t!==ot,o&&(this._$AH=t);else{const a=t;let l,u;for(t=s[0],l=0;l<s.length-1;l++)u=me(this,a[r+l],i,l),u===ot&&(u=this._$AH[l]),o||(o=!Qe(u)||u!==this._$AH[l]),u===D?t=D:t!==D&&(t+=(u??"")+s[l+1]),this._$AH[l]=u}o&&!n&&this.j(t)}j(t){t===D?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,t??"")}}class Ec extends Vi{constructor(){super(...arguments),this.type=3}j(t){this.element[this.name]=t===D?void 0:t}}class Sc extends Vi{constructor(){super(...arguments),this.type=4}j(t){this.element.toggleAttribute(this.name,!!t&&t!==D)}}class Cc extends Vi{constructor(t,i,r,n,s){super(t,i,r,n,s),this.type=5}_$AI(t,i=this){if((t=me(this,t,i,0)??D)===ot)return;const r=this._$AH,n=t===D&&r!==D||t.capture!==r.capture||t.once!==r.once||t.passive!==r.passive,s=t!==D&&(r===D||n);n&&this.element.removeEventListener(this.name,this,r),s&&this.element.addEventListener(this.name,this,t),this._$AH=t}handleEvent(t){var i;typeof this._$AH=="function"?this._$AH.call(((i=this.options)==null?void 0:i.host)??this.element,t):this._$AH.handleEvent(t)}}class kc{constructor(t,i,r){this.element=t,this.type=6,this._$AN=void 0,this._$AM=i,this.options=r}get _$AU(){return this._$AM._$AU}_$AI(t){me(this,t)}}const ur=He.litHtmlPolyfillSupport;ur==null||ur(Je,ii),(He.litHtmlVersions??(He.litHtmlVersions=[])).push("3.3.1");const Ac=(e,t,i)=>{const r=(i==null?void 0:i.renderBefore)??t;let n=r._$litPart$;if(n===void 0){const s=(i==null?void 0:i.renderBefore)??null;r._$litPart$=n=new ii(t.insertBefore(Ke(),s),s,void 0,i??{})}return n._$AI(e),n};/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const te=globalThis;let qe=class extends de{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){var i;const t=super.createRenderRoot();return(i=this.renderOptions).renderBefore??(i.renderBefore=t.firstChild),t}update(t){const i=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(t),this._$Do=Ac(i,this.renderRoot,this.renderOptions)}connectedCallback(){var t;super.connectedCallback(),(t=this._$Do)==null||t.setConnected(!0)}disconnectedCallback(){var t;super.disconnectedCallback(),(t=this._$Do)==null||t.setConnected(!1)}render(){return ot}};var uo;qe._$litElement$=!0,qe.finalized=!0,(uo=te.litElementHydrateSupport)==null||uo.call(te,{LitElement:qe});const cr=te.litElementPolyfillSupport;cr==null||cr({LitElement:qe});(te.litElementVersions??(te.litElementVersions=[])).push("4.2.1");var $c=ft`
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
`,$t=ft`
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
`,Ho=Object.defineProperty,Tc=Object.defineProperties,Rc=Object.getOwnPropertyDescriptor,Oc=Object.getOwnPropertyDescriptors,ms=Object.getOwnPropertySymbols,Fc=Object.prototype.hasOwnProperty,Lc=Object.prototype.propertyIsEnumerable,dr=(e,t)=>(t=Symbol[e])?t:Symbol.for("Symbol."+e),hn=e=>{throw TypeError(e)},gs=(e,t,i)=>t in e?Ho(e,t,{enumerable:!0,configurable:!0,writable:!0,value:i}):e[t]=i,ue=(e,t)=>{for(var i in t||(t={}))Fc.call(t,i)&&gs(e,i,t[i]);if(ms)for(var i of ms(t))Lc.call(t,i)&&gs(e,i,t[i]);return e},ji=(e,t)=>Tc(e,Oc(t)),g=(e,t,i,r)=>{for(var n=r>1?void 0:r?Rc(t,i):t,s=e.length-1,o;s>=0;s--)(o=e[s])&&(n=(r?o(t,i,n):o(n))||n);return r&&n&&Ho(t,i,n),n},qo=(e,t,i)=>t.has(e)||hn("Cannot "+i),Mc=(e,t,i)=>(qo(e,t,"read from private field"),t.get(e)),Pc=(e,t,i)=>t.has(e)?hn("Cannot add the same private member more than once"):t instanceof WeakSet?t.add(e):t.set(e,i),zc=(e,t,i,r)=>(qo(e,t,"write to private field"),t.set(e,i),i),Ic=function(e,t){this[0]=e,this[1]=t},Bc=e=>{var t=e[dr("asyncIterator")],i=!1,r,n={};return t==null?(t=e[dr("iterator")](),r=s=>n[s]=o=>t[s](o)):(t=t.call(e),r=s=>n[s]=o=>{if(i){if(i=!1,s==="throw")throw o;return o}return i=!0,{done:!1,value:new Ic(new Promise(a=>{var l=t[s](o);l instanceof Object||hn("Object expected"),a(l)}),1)}}),n[dr("iterator")]=()=>n,r("next"),"throw"in t?r("throw"):n.throw=s=>{throw s},"return"in t&&r("return"),n};/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Dc={attribute:!0,type:String,converter:fe,reflect:!1,hasChanged:cn},Nc=(e=Dc,t,i)=>{const{kind:r,metadata:n}=i;let s=globalThis.litPropertyMetadata.get(n);if(s===void 0&&globalThis.litPropertyMetadata.set(n,s=new Map),r==="setter"&&((e=Object.create(e)).wrapped=!0),s.set(i.name,e),r==="accessor"){const{name:o}=i;return{set(a){const l=t.get.call(this);t.set.call(this,a),this.requestUpdate(o,l,e)},init(a){return a!==void 0&&this.C(o,void 0,e,a),a}}}if(r==="setter"){const{name:o}=i;return function(a){const l=this[o];t.call(this,a),this.requestUpdate(o,l,e)}}throw Error("Unsupported decorator location: "+r)};function y(e){return(t,i)=>typeof i=="object"?Nc(e,t,i):((r,n,s)=>{const o=n.hasOwnProperty(s);return n.constructor.createProperty(s,r),o?Object.getOwnPropertyDescriptor(n,s):void 0})(e,t,i)}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function et(e){return y({...e,state:!0,attribute:!1})}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function Uc(e){return(t,i)=>{const r=typeof t=="function"?t:t[i];Object.assign(r,e)}}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Hc=(e,t,i)=>(i.configurable=!0,i.enumerable=!0,Reflect.decorate&&typeof t!="object"&&Object.defineProperty(e,t,i),i);/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function it(e,t){return(i,r,n)=>{const s=o=>{var a;return((a=o.renderRoot)==null?void 0:a.querySelector(e))??null};return Hc(i,r,{get(){return s(this)}})}}var Ei,rt=class extends qe{constructor(){super(),Pc(this,Ei,!1),this.initialReflectedProperties=new Map,Object.entries(this.constructor.dependencies).forEach(([e,t])=>{this.constructor.define(e,t)})}emit(e,t){const i=new CustomEvent(e,ue({bubbles:!0,cancelable:!1,composed:!0,detail:{}},t));return this.dispatchEvent(i),i}static define(e,t=this,i={}){const r=customElements.get(e);if(!r){try{customElements.define(e,t,i)}catch{customElements.define(e,class extends t{},i)}return}let n=" (unknown version)",s=n;"version"in t&&t.version&&(n=" v"+t.version),"version"in r&&r.version&&(s=" v"+r.version),!(n&&s&&n===s)&&console.warn(`Attempted to register <${e}>${n}, but <${e}>${s} has already been registered.`)}attributeChangedCallback(e,t,i){Mc(this,Ei)||(this.constructor.elementProperties.forEach((r,n)=>{r.reflect&&this[n]!=null&&this.initialReflectedProperties.set(n,this[n])}),zc(this,Ei,!0)),super.attributeChangedCallback(e,t,i)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,i)=>{e.has(i)&&this[i]==null&&(this[i]=t)})}};Ei=new WeakMap;rt.version="2.20.1";rt.dependencies={};g([y()],rt.prototype,"dir",2);g([y()],rt.prototype,"lang",2);var Vo=class extends rt{render(){return I` <slot></slot> `}};Vo.styles=[$t,$c];var qc=ft`
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
`,jo=(e="value")=>(t,i)=>{const r=t.constructor,n=r.prototype.attributeChangedCallback;r.prototype.attributeChangedCallback=function(s,o,a){var l;const u=r.getPropertyOptions(e),c=typeof u.attribute=="string"?u.attribute:e;if(s===c){const d=u.converter||fe,p=(typeof d=="function"?d:(l=d==null?void 0:d.fromAttribute)!=null?l:fe.fromAttribute)(a,u.type);this[e]!==p&&(this[i]=p)}n.call(this,s,o,a)}},Vc=ft`
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
`,Fe=new WeakMap,Le=new WeakMap,Me=new WeakMap,hr=new WeakSet,li=new WeakMap,pn=class{constructor(e,t){this.handleFormData=i=>{const r=this.options.disabled(this.host),n=this.options.name(this.host),s=this.options.value(this.host),o=this.host.tagName.toLowerCase()==="sl-button";this.host.isConnected&&!r&&!o&&typeof n=="string"&&n.length>0&&typeof s<"u"&&(Array.isArray(s)?s.forEach(a=>{i.formData.append(n,a.toString())}):i.formData.append(n,s.toString()))},this.handleFormSubmit=i=>{var r;const n=this.options.disabled(this.host),s=this.options.reportValidity;this.form&&!this.form.noValidate&&((r=Fe.get(this.form))==null||r.forEach(o=>{this.setUserInteracted(o,!0)})),this.form&&!this.form.noValidate&&!n&&!s(this.host)&&(i.preventDefault(),i.stopImmediatePropagation())},this.handleFormReset=()=>{this.options.setValue(this.host,this.options.defaultValue(this.host)),this.setUserInteracted(this.host,!1),li.set(this.host,[])},this.handleInteraction=i=>{const r=li.get(this.host);r.includes(i.type)||r.push(i.type),r.length===this.options.assumeInteractionOn.length&&this.setUserInteracted(this.host,!0)},this.checkFormValidity=()=>{if(this.form&&!this.form.noValidate){const i=this.form.querySelectorAll("*");for(const r of i)if(typeof r.checkValidity=="function"&&!r.checkValidity())return!1}return!0},this.reportFormValidity=()=>{if(this.form&&!this.form.noValidate){const i=this.form.querySelectorAll("*");for(const r of i)if(typeof r.reportValidity=="function"&&!r.reportValidity())return!1}return!0},(this.host=e).addController(this),this.options=ue({form:i=>{const r=i.form;if(r){const s=i.getRootNode().querySelector(`#${r}`);if(s)return s}return i.closest("form")},name:i=>i.name,value:i=>i.value,defaultValue:i=>i.defaultValue,disabled:i=>{var r;return(r=i.disabled)!=null?r:!1},reportValidity:i=>typeof i.reportValidity=="function"?i.reportValidity():!0,checkValidity:i=>typeof i.checkValidity=="function"?i.checkValidity():!0,setValue:(i,r)=>i.value=r,assumeInteractionOn:["sl-input"]},t)}hostConnected(){const e=this.options.form(this.host);e&&this.attachForm(e),li.set(this.host,[]),this.options.assumeInteractionOn.forEach(t=>{this.host.addEventListener(t,this.handleInteraction)})}hostDisconnected(){this.detachForm(),li.delete(this.host),this.options.assumeInteractionOn.forEach(e=>{this.host.removeEventListener(e,this.handleInteraction)})}hostUpdated(){const e=this.options.form(this.host);e||this.detachForm(),e&&this.form!==e&&(this.detachForm(),this.attachForm(e)),this.host.hasUpdated&&this.setValidity(this.host.validity.valid)}attachForm(e){e?(this.form=e,Fe.has(this.form)?Fe.get(this.form).add(this.host):Fe.set(this.form,new Set([this.host])),this.form.addEventListener("formdata",this.handleFormData),this.form.addEventListener("submit",this.handleFormSubmit),this.form.addEventListener("reset",this.handleFormReset),Le.has(this.form)||(Le.set(this.form,this.form.reportValidity),this.form.reportValidity=()=>this.reportFormValidity()),Me.has(this.form)||(Me.set(this.form,this.form.checkValidity),this.form.checkValidity=()=>this.checkFormValidity())):this.form=void 0}detachForm(){if(!this.form)return;const e=Fe.get(this.form);e&&(e.delete(this.host),e.size<=0&&(this.form.removeEventListener("formdata",this.handleFormData),this.form.removeEventListener("submit",this.handleFormSubmit),this.form.removeEventListener("reset",this.handleFormReset),Le.has(this.form)&&(this.form.reportValidity=Le.get(this.form),Le.delete(this.form)),Me.has(this.form)&&(this.form.checkValidity=Me.get(this.form),Me.delete(this.form)),this.form=void 0))}setUserInteracted(e,t){t?hr.add(e):hr.delete(e),e.requestUpdate()}doAction(e,t){if(this.form){const i=document.createElement("button");i.type=e,i.style.position="absolute",i.style.width="0",i.style.height="0",i.style.clipPath="inset(50%)",i.style.overflow="hidden",i.style.whiteSpace="nowrap",t&&(i.name=t.name,i.value=t.value,["formaction","formenctype","formmethod","formnovalidate","formtarget"].forEach(r=>{t.hasAttribute(r)&&i.setAttribute(r,t.getAttribute(r))})),this.form.append(i),i.click(),i.remove()}}getForm(){var e;return(e=this.form)!=null?e:null}reset(e){this.doAction("reset",e)}submit(e){this.doAction("submit",e)}setValidity(e){const t=this.host,i=!!hr.has(t),r=!!t.required;t.toggleAttribute("data-required",r),t.toggleAttribute("data-optional",!r),t.toggleAttribute("data-invalid",!e),t.toggleAttribute("data-valid",e),t.toggleAttribute("data-user-invalid",!e&&i),t.toggleAttribute("data-user-valid",e&&i)}updateValidity(){const e=this.host;this.setValidity(e.validity.valid)}emitInvalidEvent(e){const t=new CustomEvent("sl-invalid",{bubbles:!1,composed:!1,cancelable:!0,detail:{}});e||t.preventDefault(),this.host.dispatchEvent(t)||e==null||e.preventDefault()}},fn=Object.freeze({badInput:!1,customError:!1,patternMismatch:!1,rangeOverflow:!1,rangeUnderflow:!1,stepMismatch:!1,tooLong:!1,tooShort:!1,typeMismatch:!1,valid:!0,valueMissing:!1});Object.freeze(ji(ue({},fn),{valid:!1,valueMissing:!0}));Object.freeze(ji(ue({},fn),{valid:!1,customError:!0}));var Wo=class{constructor(e,...t){this.slotNames=[],this.handleSlotChange=i=>{const r=i.target;(this.slotNames.includes("[default]")&&!r.name||r.name&&this.slotNames.includes(r.name))&&this.host.requestUpdate()},(this.host=e).addController(this),this.slotNames=t}hasDefaultSlot(){return[...this.host.childNodes].some(e=>{if(e.nodeType===e.TEXT_NODE&&e.textContent.trim()!=="")return!0;if(e.nodeType===e.ELEMENT_NODE){const t=e;if(t.tagName.toLowerCase()==="sl-visually-hidden")return!1;if(!t.hasAttribute("slot"))return!0}return!1})}hasNamedSlot(e){return this.host.querySelector(`:scope > [slot="${e}"]`)!==null}test(e){return e==="[default]"?this.hasDefaultSlot():this.hasNamedSlot(e)}hostConnected(){this.host.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}};const Mr=new Set,he=new Map;let Qt,mn="ltr",gn="en";const Ko=typeof MutationObserver<"u"&&typeof document<"u"&&typeof document.documentElement<"u";if(Ko){const e=new MutationObserver(Jo);mn=document.documentElement.dir||"ltr",gn=document.documentElement.lang||navigator.language,e.observe(document.documentElement,{attributes:!0,attributeFilter:["dir","lang"]})}function Qo(...e){e.map(t=>{const i=t.$code.toLowerCase();he.has(i)?he.set(i,Object.assign(Object.assign({},he.get(i)),t)):he.set(i,t),Qt||(Qt=t)}),Jo()}function Jo(){Ko&&(mn=document.documentElement.dir||"ltr",gn=document.documentElement.lang||navigator.language),[...Mr.keys()].map(e=>{typeof e.requestUpdate=="function"&&e.requestUpdate()})}let jc=class{constructor(t){this.host=t,this.host.addController(this)}hostConnected(){Mr.add(this.host)}hostDisconnected(){Mr.delete(this.host)}dir(){return`${this.host.dir||mn}`.toLowerCase()}lang(){return`${this.host.lang||gn}`.toLowerCase()}getTranslationData(t){var i,r;const n=new Intl.Locale(t.replace(/_/g,"-")),s=n==null?void 0:n.language.toLowerCase(),o=(r=(i=n==null?void 0:n.region)===null||i===void 0?void 0:i.toLowerCase())!==null&&r!==void 0?r:"",a=he.get(`${s}-${o}`),l=he.get(s);return{locale:n,language:s,region:o,primary:a,secondary:l}}exists(t,i){var r;const{primary:n,secondary:s}=this.getTranslationData((r=i.lang)!==null&&r!==void 0?r:this.lang());return i=Object.assign({includeFallback:!1},i),!!(n&&n[t]||s&&s[t]||i.includeFallback&&Qt&&Qt[t])}term(t,...i){const{primary:r,secondary:n}=this.getTranslationData(this.lang());let s;if(r&&r[t])s=r[t];else if(n&&n[t])s=n[t];else if(Qt&&Qt[t])s=Qt[t];else return console.error(`No translation found for: ${String(t)}`),String(t);return typeof s=="function"?s(...i):s}date(t,i){return t=new Date(t),new Intl.DateTimeFormat(this.lang(),i).format(t)}number(t,i){return t=Number(t),isNaN(t)?"":new Intl.NumberFormat(this.lang(),i).format(t)}relativeTime(t,i,r){return new Intl.RelativeTimeFormat(this.lang(),r).format(t,i)}};var Xo={$code:"en",$name:"English",$dir:"ltr",carousel:"Carousel",clearEntry:"Clear entry",close:"Close",copied:"Copied",copy:"Copy",currentValue:"Current value",error:"Error",goToSlide:(e,t)=>`Go to slide ${e} of ${t}`,hidePassword:"Hide password",loading:"Loading",nextSlide:"Next slide",numOptionsSelected:e=>e===0?"No options selected":e===1?"1 option selected":`${e} options selected`,previousSlide:"Previous slide",progress:"Progress",remove:"Remove",resize:"Resize",scrollToEnd:"Scroll to end",scrollToStart:"Scroll to start",selectAColorFromTheScreen:"Select a color from the screen",showPassword:"Show password",slideNum:e=>`Slide ${e}`,toggleColorFormat:"Toggle color format"};Qo(Xo);var Wc=Xo,_e=class extends jc{};Qo(Wc);var Pr="";function bs(e){Pr=e}function Kc(e=""){if(!Pr){const t=[...document.getElementsByTagName("script")],i=t.find(r=>r.hasAttribute("data-shoelace"));if(i)bs(i.getAttribute("data-shoelace"));else{const r=t.find(s=>/shoelace(\.min)?\.js($|\?)/.test(s.src)||/shoelace-autoloader(\.min)?\.js($|\?)/.test(s.src));let n="";r&&(n=r.getAttribute("src")),bs(n.split("/").slice(0,-1).join("/"))}}return Pr.replace(/\/$/,"")+(e?`/${e.replace(/^\//,"")}`:"")}var Qc={name:"default",resolver:e=>Kc(`assets/icons/${e}.svg`)},Jc=Qc,vs={caret:`
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
  `},Xc={name:"system",resolver:e=>e in vs?`data:image/svg+xml,${encodeURIComponent(vs[e])}`:""},Gc=Xc,Yc=[Jc,Gc],zr=[];function Zc(e){zr.push(e)}function td(e){zr=zr.filter(t=>t!==e)}function ys(e){return Yc.find(t=>t.name===e)}var ed=ft`
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
`;function xt(e,t){const i=ue({waitUntilFirstUpdate:!1},t);return(r,n)=>{const{update:s}=r,o=Array.isArray(e)?e:[e];r.update=function(a){o.forEach(l=>{const u=l;if(a.has(u)){const c=a.get(u),d=this[u];c!==d&&(!i.waitUntilFirstUpdate||this.hasUpdated)&&this[n](c,d)}}),s.call(this,a)}}}/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const id=(e,t)=>(e==null?void 0:e._$litType$)!==void 0,rd=e=>e.strings===void 0,nd={},sd=(e,t=nd)=>e._$AH=t;var Pe=Symbol(),ui=Symbol(),pr,fr=new Map,mt=class extends rt{constructor(){super(...arguments),this.initialRender=!1,this.svg=null,this.label="",this.library="default"}async resolveIcon(e,t){var i;let r;if(t!=null&&t.spriteSheet)return this.svg=I`<svg part="svg">
        <use part="use" href="${e}"></use>
      </svg>`,this.svg;try{if(r=await fetch(e,{mode:"cors"}),!r.ok)return r.status===410?Pe:ui}catch{return ui}try{const n=document.createElement("div");n.innerHTML=await r.text();const s=n.firstElementChild;if(((i=s==null?void 0:s.tagName)==null?void 0:i.toLowerCase())!=="svg")return Pe;pr||(pr=new DOMParser);const a=pr.parseFromString(s.outerHTML,"text/html").body.querySelector("svg");return a?(a.part.add("svg"),document.adoptNode(a)):Pe}catch{return Pe}}connectedCallback(){super.connectedCallback(),Zc(this)}firstUpdated(){this.initialRender=!0,this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),td(this)}getIconSource(){const e=ys(this.library);return this.name&&e?{url:e.resolver(this.name),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label=="string"&&this.label.length>0?(this.setAttribute("role","img"),this.setAttribute("aria-label",this.label),this.removeAttribute("aria-hidden")):(this.removeAttribute("role"),this.removeAttribute("aria-label"),this.setAttribute("aria-hidden","true"))}async setIcon(){var e;const{url:t,fromLibrary:i}=this.getIconSource(),r=i?ys(this.library):void 0;if(!t){this.svg=null;return}let n=fr.get(t);if(n||(n=this.resolveIcon(t,r),fr.set(t,n)),!this.initialRender)return;const s=await n;if(s===ui&&fr.delete(t),t===this.getIconSource().url){if(id(s)){if(this.svg=s,r){await this.updateComplete;const o=this.shadowRoot.querySelector("[part='svg']");typeof r.mutator=="function"&&o&&r.mutator(o)}return}switch(s){case ui:case Pe:this.svg=null,this.emit("sl-error");break;default:this.svg=s.cloneNode(!0),(e=r==null?void 0:r.mutator)==null||e.call(r,this.svg),this.emit("sl-load")}}}render(){return this.svg}};mt.styles=[$t,ed];g([et()],mt.prototype,"svg",2);g([y({reflect:!0})],mt.prototype,"name",2);g([y()],mt.prototype,"src",2);g([y()],mt.prototype,"label",2);g([y({reflect:!0})],mt.prototype,"library",2);g([xt("label")],mt.prototype,"handleLabelChange",1);g([xt(["name","src","library"])],mt.prototype,"setIcon",1);/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Mt={ATTRIBUTE:1,PROPERTY:3,BOOLEAN_ATTRIBUTE:4},bn=e=>(...t)=>({_$litDirective$:e,values:t});let vn=class{constructor(t){}get _$AU(){return this._$AM._$AU}_$AT(t,i,r){this._$Ct=t,this._$AM=i,this._$Ci=r}_$AS(t,i){return this.update(t,i)}update(t,i){return this.render(...i)}};/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const kt=bn(class extends vn{constructor(e){var t;if(super(e),e.type!==Mt.ATTRIBUTE||e.name!=="class"||((t=e.strings)==null?void 0:t.length)>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(e){return" "+Object.keys(e).filter((t=>e[t])).join(" ")+" "}update(e,[t]){var r,n;if(this.st===void 0){this.st=new Set,e.strings!==void 0&&(this.nt=new Set(e.strings.join(" ").split(/\s/).filter((s=>s!==""))));for(const s in t)t[s]&&!((r=this.nt)!=null&&r.has(s))&&this.st.add(s);return this.render(t)}const i=e.element.classList;for(const s of this.st)s in t||(i.remove(s),this.st.delete(s));for(const s in t){const o=!!t[s];o===this.st.has(s)||(n=this.nt)!=null&&n.has(s)||(o?(i.add(s),this.st.add(s)):(i.remove(s),this.st.delete(s)))}return ot}});/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const O=e=>e??D;/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const od=bn(class extends vn{constructor(e){if(super(e),e.type!==Mt.PROPERTY&&e.type!==Mt.ATTRIBUTE&&e.type!==Mt.BOOLEAN_ATTRIBUTE)throw Error("The `live` directive is not allowed on child or event bindings");if(!rd(e))throw Error("`live` bindings can only contain a single expression")}render(e){return e}update(e,[t]){if(t===ot||t===D)return t;const i=e.element,r=e.name;if(e.type===Mt.PROPERTY){if(t===i[r])return ot}else if(e.type===Mt.BOOLEAN_ATTRIBUTE){if(!!t===i.hasAttribute(r))return ot}else if(e.type===Mt.ATTRIBUTE&&i.getAttribute(r)===t+"")return ot;return sd(e),t}});var A=class extends rt{constructor(){super(...arguments),this.formControlController=new pn(this,{assumeInteractionOn:["sl-blur","sl-input"]}),this.hasSlotController=new Wo(this,"help-text","label"),this.localize=new _e(this),this.hasFocus=!1,this.title="",this.__numberInput=Object.assign(document.createElement("input"),{type:"number"}),this.__dateInput=Object.assign(document.createElement("input"),{type:"date"}),this.type="text",this.name="",this.value="",this.defaultValue="",this.size="medium",this.filled=!1,this.pill=!1,this.label="",this.helpText="",this.clearable=!1,this.disabled=!1,this.placeholder="",this.readonly=!1,this.passwordToggle=!1,this.passwordVisible=!1,this.noSpinButtons=!1,this.form="",this.required=!1,this.spellcheck=!0}get valueAsDate(){var e;return this.__dateInput.type=this.type,this.__dateInput.value=this.value,((e=this.input)==null?void 0:e.valueAsDate)||this.__dateInput.valueAsDate}set valueAsDate(e){this.__dateInput.type=this.type,this.__dateInput.valueAsDate=e,this.value=this.__dateInput.value}get valueAsNumber(){var e;return this.__numberInput.value=this.value,((e=this.input)==null?void 0:e.valueAsNumber)||this.__numberInput.valueAsNumber}set valueAsNumber(e){this.__numberInput.valueAsNumber=e,this.value=this.__numberInput.value}get validity(){return this.input.validity}get validationMessage(){return this.input.validationMessage}firstUpdated(){this.formControlController.updateValidity()}handleBlur(){this.hasFocus=!1,this.emit("sl-blur")}handleChange(){this.value=this.input.value,this.emit("sl-change")}handleClearClick(e){e.preventDefault(),this.value!==""&&(this.value="",this.emit("sl-clear"),this.emit("sl-input"),this.emit("sl-change")),this.input.focus()}handleFocus(){this.hasFocus=!0,this.emit("sl-focus")}handleInput(){this.value=this.input.value,this.formControlController.updateValidity(),this.emit("sl-input")}handleInvalid(e){this.formControlController.setValidity(!1),this.formControlController.emitInvalidEvent(e)}handleKeyDown(e){const t=e.metaKey||e.ctrlKey||e.shiftKey||e.altKey;e.key==="Enter"&&!t&&setTimeout(()=>{!e.defaultPrevented&&!e.isComposing&&this.formControlController.submit()})}handlePasswordToggle(){this.passwordVisible=!this.passwordVisible}handleDisabledChange(){this.formControlController.setValidity(this.disabled)}handleStepChange(){this.input.step=String(this.step),this.formControlController.updateValidity()}async handleValueChange(){await this.updateComplete,this.formControlController.updateValidity()}focus(e){this.input.focus(e)}blur(){this.input.blur()}select(){this.input.select()}setSelectionRange(e,t,i="none"){this.input.setSelectionRange(e,t,i)}setRangeText(e,t,i,r="preserve"){const n=t??this.input.selectionStart,s=i??this.input.selectionEnd;this.input.setRangeText(e,n,s,r),this.value!==this.input.value&&(this.value=this.input.value)}showPicker(){"showPicker"in HTMLInputElement.prototype&&this.input.showPicker()}stepUp(){this.input.stepUp(),this.value!==this.input.value&&(this.value=this.input.value)}stepDown(){this.input.stepDown(),this.value!==this.input.value&&(this.value=this.input.value)}checkValidity(){return this.input.checkValidity()}getForm(){return this.formControlController.getForm()}reportValidity(){return this.input.reportValidity()}setCustomValidity(e){this.input.setCustomValidity(e),this.formControlController.updateValidity()}render(){const e=this.hasSlotController.test("label"),t=this.hasSlotController.test("help-text"),i=this.label?!0:!!e,r=this.helpText?!0:!!t,s=this.clearable&&!this.disabled&&!this.readonly&&(typeof this.value=="number"||this.value.length>0);return I`
      <div
        part="form-control"
        class=${kt({"form-control":!0,"form-control--small":this.size==="small","form-control--medium":this.size==="medium","form-control--large":this.size==="large","form-control--has-label":i,"form-control--has-help-text":r})}
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
              name=${O(this.name)}
              ?disabled=${this.disabled}
              ?readonly=${this.readonly}
              ?required=${this.required}
              placeholder=${O(this.placeholder)}
              minlength=${O(this.minlength)}
              maxlength=${O(this.maxlength)}
              min=${O(this.min)}
              max=${O(this.max)}
              step=${O(this.step)}
              .value=${od(this.value)}
              autocapitalize=${O(this.autocapitalize)}
              autocomplete=${O(this.autocomplete)}
              autocorrect=${O(this.autocorrect)}
              ?autofocus=${this.autofocus}
              spellcheck=${this.spellcheck}
              pattern=${O(this.pattern)}
              enterkeyhint=${O(this.enterkeyhint)}
              inputmode=${O(this.inputmode)}
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
          aria-hidden=${r?"false":"true"}
        >
          <slot name="help-text">${this.helpText}</slot>
        </div>
      </div>
    `}};A.styles=[$t,Vc,qc];A.dependencies={"sl-icon":mt};g([it(".input__control")],A.prototype,"input",2);g([et()],A.prototype,"hasFocus",2);g([y()],A.prototype,"title",2);g([y({reflect:!0})],A.prototype,"type",2);g([y()],A.prototype,"name",2);g([y()],A.prototype,"value",2);g([jo()],A.prototype,"defaultValue",2);g([y({reflect:!0})],A.prototype,"size",2);g([y({type:Boolean,reflect:!0})],A.prototype,"filled",2);g([y({type:Boolean,reflect:!0})],A.prototype,"pill",2);g([y()],A.prototype,"label",2);g([y({attribute:"help-text"})],A.prototype,"helpText",2);g([y({type:Boolean})],A.prototype,"clearable",2);g([y({type:Boolean,reflect:!0})],A.prototype,"disabled",2);g([y()],A.prototype,"placeholder",2);g([y({type:Boolean,reflect:!0})],A.prototype,"readonly",2);g([y({attribute:"password-toggle",type:Boolean})],A.prototype,"passwordToggle",2);g([y({attribute:"password-visible",type:Boolean})],A.prototype,"passwordVisible",2);g([y({attribute:"no-spin-buttons",type:Boolean})],A.prototype,"noSpinButtons",2);g([y({reflect:!0})],A.prototype,"form",2);g([y({type:Boolean,reflect:!0})],A.prototype,"required",2);g([y()],A.prototype,"pattern",2);g([y({type:Number})],A.prototype,"minlength",2);g([y({type:Number})],A.prototype,"maxlength",2);g([y()],A.prototype,"min",2);g([y()],A.prototype,"max",2);g([y()],A.prototype,"step",2);g([y()],A.prototype,"autocapitalize",2);g([y()],A.prototype,"autocorrect",2);g([y()],A.prototype,"autocomplete",2);g([y({type:Boolean})],A.prototype,"autofocus",2);g([y()],A.prototype,"enterkeyhint",2);g([y({type:Boolean,converter:{fromAttribute:e=>!(!e||e==="false"),toAttribute:e=>e?"true":"false"}})],A.prototype,"spellcheck",2);g([y()],A.prototype,"inputmode",2);g([xt("disabled",{waitUntilFirstUpdate:!0})],A.prototype,"handleDisabledChange",1);g([xt("step",{waitUntilFirstUpdate:!0})],A.prototype,"handleStepChange",1);g([xt("value",{waitUntilFirstUpdate:!0})],A.prototype,"handleValueChange",1);function mr(e,t){function i(n){const s=e.getBoundingClientRect(),o=e.ownerDocument.defaultView,a=s.left+o.scrollX,l=s.top+o.scrollY,u=n.pageX-a,c=n.pageY-l;t!=null&&t.onMove&&t.onMove(u,c)}function r(){document.removeEventListener("pointermove",i),document.removeEventListener("pointerup",r),t!=null&&t.onStop&&t.onStop()}document.addEventListener("pointermove",i,{passive:!0}),document.addEventListener("pointerup",r),(t==null?void 0:t.initialEvent)instanceof PointerEvent&&i(t.initialEvent)}var ad=ft`
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
`;function*Go(e=document.activeElement){e!=null&&(yield e,"shadowRoot"in e&&e.shadowRoot&&e.shadowRoot.mode!=="closed"&&(yield*Bc(Go(e.shadowRoot.activeElement))))}function ld(){return[...Go()].pop()}var ws=new WeakMap;function Yo(e){let t=ws.get(e);return t||(t=window.getComputedStyle(e,null),ws.set(e,t)),t}function ud(e){if(typeof e.checkVisibility=="function")return e.checkVisibility({checkOpacity:!1,checkVisibilityCSS:!0});const t=Yo(e);return t.visibility!=="hidden"&&t.display!=="none"}function cd(e){const t=Yo(e),{overflowY:i,overflowX:r}=t;return i==="scroll"||r==="scroll"?!0:i!=="auto"||r!=="auto"?!1:e.scrollHeight>e.clientHeight&&i==="auto"||e.scrollWidth>e.clientWidth&&r==="auto"}function dd(e){const t=e.tagName.toLowerCase(),i=Number(e.getAttribute("tabindex"));if(e.hasAttribute("tabindex")&&(isNaN(i)||i<=-1)||e.hasAttribute("disabled")||e.closest("[inert]"))return!1;if(t==="input"&&e.getAttribute("type")==="radio"){const s=e.getRootNode(),o=`input[type='radio'][name="${e.getAttribute("name")}"]`,a=s.querySelector(`${o}:checked`);return a?a===e:s.querySelector(o)===e}return ud(e)?(t==="audio"||t==="video")&&e.hasAttribute("controls")||e.hasAttribute("tabindex")||e.hasAttribute("contenteditable")&&e.getAttribute("contenteditable")!=="false"||["button","input","select","textarea","a","audio","video","summary","iframe"].includes(t)?!0:cd(e):!1}function hd(e){var t,i;const r=fd(e),n=(t=r[0])!=null?t:null,s=(i=r[r.length-1])!=null?i:null;return{start:n,end:s}}function pd(e,t){var i;return((i=e.getRootNode({composed:!0}))==null?void 0:i.host)!==t}function fd(e){const t=new WeakMap,i=[];function r(n){if(n instanceof Element){if(n.hasAttribute("inert")||n.closest("[inert]")||t.has(n))return;t.set(n,!0),!i.includes(n)&&dd(n)&&i.push(n),n instanceof HTMLSlotElement&&pd(n,e)&&n.assignedElements({flatten:!0}).forEach(s=>{r(s)}),n.shadowRoot!==null&&n.shadowRoot.mode==="open"&&r(n.shadowRoot)}for(const s of n.children)r(s)}return r(e),i.sort((n,s)=>{const o=Number(n.getAttribute("tabindex"))||0;return(Number(s.getAttribute("tabindex"))||0)-o})}var md=ft`
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
`;const Bt=Math.min,Z=Math.max,Oi=Math.round,ci=Math.floor,wt=e=>({x:e,y:e}),gd={left:"right",right:"left",bottom:"top",top:"bottom"},bd={start:"end",end:"start"};function Ir(e,t,i){return Z(e,Bt(t,i))}function xe(e,t){return typeof e=="function"?e(t):e}function Dt(e){return e.split("-")[0]}function Ee(e){return e.split("-")[1]}function Zo(e){return e==="x"?"y":"x"}function yn(e){return e==="y"?"height":"width"}const vd=new Set(["top","bottom"]);function Ct(e){return vd.has(Dt(e))?"y":"x"}function wn(e){return Zo(Ct(e))}function yd(e,t,i){i===void 0&&(i=!1);const r=Ee(e),n=wn(e),s=yn(n);let o=n==="x"?r===(i?"end":"start")?"right":"left":r==="start"?"bottom":"top";return t.reference[s]>t.floating[s]&&(o=Fi(o)),[o,Fi(o)]}function wd(e){const t=Fi(e);return[Br(e),t,Br(t)]}function Br(e){return e.replace(/start|end/g,t=>bd[t])}const _s=["left","right"],xs=["right","left"],_d=["top","bottom"],xd=["bottom","top"];function Ed(e,t,i){switch(e){case"top":case"bottom":return i?t?xs:_s:t?_s:xs;case"left":case"right":return t?_d:xd;default:return[]}}function Sd(e,t,i,r){const n=Ee(e);let s=Ed(Dt(e),i==="start",r);return n&&(s=s.map(o=>o+"-"+n),t&&(s=s.concat(s.map(Br)))),s}function Fi(e){return e.replace(/left|right|bottom|top/g,t=>gd[t])}function Cd(e){return{top:0,right:0,bottom:0,left:0,...e}}function ta(e){return typeof e!="number"?Cd(e):{top:e,right:e,bottom:e,left:e}}function Li(e){const{x:t,y:i,width:r,height:n}=e;return{width:r,height:n,top:i,left:t,right:t+r,bottom:i+n,x:t,y:i}}function Es(e,t,i){let{reference:r,floating:n}=e;const s=Ct(t),o=wn(t),a=yn(o),l=Dt(t),u=s==="y",c=r.x+r.width/2-n.width/2,d=r.y+r.height/2-n.height/2,h=r[a]/2-n[a]/2;let p;switch(l){case"top":p={x:c,y:r.y-n.height};break;case"bottom":p={x:c,y:r.y+r.height};break;case"right":p={x:r.x+r.width,y:d};break;case"left":p={x:r.x-n.width,y:d};break;default:p={x:r.x,y:r.y}}switch(Ee(t)){case"start":p[o]-=h*(i&&u?-1:1);break;case"end":p[o]+=h*(i&&u?-1:1);break}return p}const kd=async(e,t,i)=>{const{placement:r="bottom",strategy:n="absolute",middleware:s=[],platform:o}=i,a=s.filter(Boolean),l=await(o.isRTL==null?void 0:o.isRTL(t));let u=await o.getElementRects({reference:e,floating:t,strategy:n}),{x:c,y:d}=Es(u,r,l),h=r,p={},f=0;for(let v=0;v<a.length;v++){const{name:m,fn:w}=a[v],{x,y:E,data:S,reset:C}=await w({x:c,y:d,initialPlacement:r,placement:h,strategy:n,middlewareData:p,rects:u,platform:o,elements:{reference:e,floating:t}});c=x??c,d=E??d,p={...p,[m]:{...p[m],...S}},C&&f<=50&&(f++,typeof C=="object"&&(C.placement&&(h=C.placement),C.rects&&(u=C.rects===!0?await o.getElementRects({reference:e,floating:t,strategy:n}):C.rects),{x:c,y:d}=Es(u,h,l)),v=-1)}return{x:c,y:d,placement:h,strategy:n,middlewareData:p}};async function _n(e,t){var i;t===void 0&&(t={});const{x:r,y:n,platform:s,rects:o,elements:a,strategy:l}=e,{boundary:u="clippingAncestors",rootBoundary:c="viewport",elementContext:d="floating",altBoundary:h=!1,padding:p=0}=xe(t,e),f=ta(p),m=a[h?d==="floating"?"reference":"floating":d],w=Li(await s.getClippingRect({element:(i=await(s.isElement==null?void 0:s.isElement(m)))==null||i?m:m.contextElement||await(s.getDocumentElement==null?void 0:s.getDocumentElement(a.floating)),boundary:u,rootBoundary:c,strategy:l})),x=d==="floating"?{x:r,y:n,width:o.floating.width,height:o.floating.height}:o.reference,E=await(s.getOffsetParent==null?void 0:s.getOffsetParent(a.floating)),S=await(s.isElement==null?void 0:s.isElement(E))?await(s.getScale==null?void 0:s.getScale(E))||{x:1,y:1}:{x:1,y:1},C=Li(s.convertOffsetParentRelativeRectToViewportRelativeRect?await s.convertOffsetParentRelativeRectToViewportRelativeRect({elements:a,rect:x,offsetParent:E,strategy:l}):x);return{top:(w.top-C.top+f.top)/S.y,bottom:(C.bottom-w.bottom+f.bottom)/S.y,left:(w.left-C.left+f.left)/S.x,right:(C.right-w.right+f.right)/S.x}}const Ad=e=>({name:"arrow",options:e,async fn(t){const{x:i,y:r,placement:n,rects:s,platform:o,elements:a,middlewareData:l}=t,{element:u,padding:c=0}=xe(e,t)||{};if(u==null)return{};const d=ta(c),h={x:i,y:r},p=wn(n),f=yn(p),v=await o.getDimensions(u),m=p==="y",w=m?"top":"left",x=m?"bottom":"right",E=m?"clientHeight":"clientWidth",S=s.reference[f]+s.reference[p]-h[p]-s.floating[f],C=h[p]-s.reference[p],F=await(o.getOffsetParent==null?void 0:o.getOffsetParent(u));let R=F?F[E]:0;(!R||!await(o.isElement==null?void 0:o.isElement(F)))&&(R=a.floating[E]||s.floating[f]);const H=S/2-C/2,j=R/2-v[f]/2-1,Q=Bt(d[w],j),Tt=Bt(d[x],j),bt=Q,Rt=R-v[f]-Tt,W=R/2-v[f]/2+H,Vt=Ir(bt,W,Rt),St=!l.arrow&&Ee(n)!=null&&W!==Vt&&s.reference[f]/2-(W<bt?Q:Tt)-v[f]/2<0,lt=St?W<bt?W-bt:W-Rt:0;return{[p]:h[p]+lt,data:{[p]:Vt,centerOffset:W-Vt-lt,...St&&{alignmentOffset:lt}},reset:St}}}),$d=function(e){return e===void 0&&(e={}),{name:"flip",options:e,async fn(t){var i,r;const{placement:n,middlewareData:s,rects:o,initialPlacement:a,platform:l,elements:u}=t,{mainAxis:c=!0,crossAxis:d=!0,fallbackPlacements:h,fallbackStrategy:p="bestFit",fallbackAxisSideDirection:f="none",flipAlignment:v=!0,...m}=xe(e,t);if((i=s.arrow)!=null&&i.alignmentOffset)return{};const w=Dt(n),x=Ct(a),E=Dt(a)===a,S=await(l.isRTL==null?void 0:l.isRTL(u.floating)),C=h||(E||!v?[Fi(a)]:wd(a)),F=f!=="none";!h&&F&&C.push(...Sd(a,v,f,S));const R=[a,...C],H=await _n(t,m),j=[];let Q=((r=s.flip)==null?void 0:r.overflows)||[];if(c&&j.push(H[w]),d){const W=yd(n,o,S);j.push(H[W[0]],H[W[1]])}if(Q=[...Q,{placement:n,overflows:j}],!j.every(W=>W<=0)){var Tt,bt;const W=(((Tt=s.flip)==null?void 0:Tt.index)||0)+1,Vt=R[W];if(Vt&&(!(d==="alignment"?x!==Ct(Vt):!1)||Q.every(ut=>Ct(ut.placement)===x?ut.overflows[0]>0:!0)))return{data:{index:W,overflows:Q},reset:{placement:Vt}};let St=(bt=Q.filter(lt=>lt.overflows[0]<=0).sort((lt,ut)=>lt.overflows[1]-ut.overflows[1])[0])==null?void 0:bt.placement;if(!St)switch(p){case"bestFit":{var Rt;const lt=(Rt=Q.filter(ut=>{if(F){const Ot=Ct(ut.placement);return Ot===x||Ot==="y"}return!0}).map(ut=>[ut.placement,ut.overflows.filter(Ot=>Ot>0).reduce((Ot,Tl)=>Ot+Tl,0)]).sort((ut,Ot)=>ut[1]-Ot[1])[0])==null?void 0:Rt[0];lt&&(St=lt);break}case"initialPlacement":St=a;break}if(n!==St)return{reset:{placement:St}}}return{}}}},Td=new Set(["left","top"]);async function Rd(e,t){const{placement:i,platform:r,elements:n}=e,s=await(r.isRTL==null?void 0:r.isRTL(n.floating)),o=Dt(i),a=Ee(i),l=Ct(i)==="y",u=Td.has(o)?-1:1,c=s&&l?-1:1,d=xe(t,e);let{mainAxis:h,crossAxis:p,alignmentAxis:f}=typeof d=="number"?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return a&&typeof f=="number"&&(p=a==="end"?f*-1:f),l?{x:p*c,y:h*u}:{x:h*u,y:p*c}}const Od=function(e){return e===void 0&&(e=0),{name:"offset",options:e,async fn(t){var i,r;const{x:n,y:s,placement:o,middlewareData:a}=t,l=await Rd(t,e);return o===((i=a.offset)==null?void 0:i.placement)&&(r=a.arrow)!=null&&r.alignmentOffset?{}:{x:n+l.x,y:s+l.y,data:{...l,placement:o}}}}},Fd=function(e){return e===void 0&&(e={}),{name:"shift",options:e,async fn(t){const{x:i,y:r,placement:n}=t,{mainAxis:s=!0,crossAxis:o=!1,limiter:a={fn:m=>{let{x:w,y:x}=m;return{x:w,y:x}}},...l}=xe(e,t),u={x:i,y:r},c=await _n(t,l),d=Ct(Dt(n)),h=Zo(d);let p=u[h],f=u[d];if(s){const m=h==="y"?"top":"left",w=h==="y"?"bottom":"right",x=p+c[m],E=p-c[w];p=Ir(x,p,E)}if(o){const m=d==="y"?"top":"left",w=d==="y"?"bottom":"right",x=f+c[m],E=f-c[w];f=Ir(x,f,E)}const v=a.fn({...t,[h]:p,[d]:f});return{...v,data:{x:v.x-i,y:v.y-r,enabled:{[h]:s,[d]:o}}}}}},Ld=function(e){return e===void 0&&(e={}),{name:"size",options:e,async fn(t){var i,r;const{placement:n,rects:s,platform:o,elements:a}=t,{apply:l=()=>{},...u}=xe(e,t),c=await _n(t,u),d=Dt(n),h=Ee(n),p=Ct(n)==="y",{width:f,height:v}=s.floating;let m,w;d==="top"||d==="bottom"?(m=d,w=h===(await(o.isRTL==null?void 0:o.isRTL(a.floating))?"start":"end")?"left":"right"):(w=d,m=h==="end"?"top":"bottom");const x=v-c.top-c.bottom,E=f-c.left-c.right,S=Bt(v-c[m],x),C=Bt(f-c[w],E),F=!t.middlewareData.shift;let R=S,H=C;if((i=t.middlewareData.shift)!=null&&i.enabled.x&&(H=E),(r=t.middlewareData.shift)!=null&&r.enabled.y&&(R=x),F&&!h){const Q=Z(c.left,0),Tt=Z(c.right,0),bt=Z(c.top,0),Rt=Z(c.bottom,0);p?H=f-2*(Q!==0||Tt!==0?Q+Tt:Z(c.left,c.right)):R=v-2*(bt!==0||Rt!==0?bt+Rt:Z(c.top,c.bottom))}await l({...t,availableWidth:H,availableHeight:R});const j=await o.getDimensions(a.floating);return f!==j.width||v!==j.height?{reset:{rects:!0}}:{}}}};function Wi(){return typeof window<"u"}function Se(e){return ea(e)?(e.nodeName||"").toLowerCase():"#document"}function tt(e){var t;return(e==null||(t=e.ownerDocument)==null?void 0:t.defaultView)||window}function Et(e){var t;return(t=(ea(e)?e.ownerDocument:e.document)||window.document)==null?void 0:t.documentElement}function ea(e){return Wi()?e instanceof Node||e instanceof tt(e).Node:!1}function ct(e){return Wi()?e instanceof Element||e instanceof tt(e).Element:!1}function _t(e){return Wi()?e instanceof HTMLElement||e instanceof tt(e).HTMLElement:!1}function Ss(e){return!Wi()||typeof ShadowRoot>"u"?!1:e instanceof ShadowRoot||e instanceof tt(e).ShadowRoot}const Md=new Set(["inline","contents"]);function ri(e){const{overflow:t,overflowX:i,overflowY:r,display:n}=dt(e);return/auto|scroll|overlay|hidden|clip/.test(t+r+i)&&!Md.has(n)}const Pd=new Set(["table","td","th"]);function zd(e){return Pd.has(Se(e))}const Id=[":popover-open",":modal"];function Ki(e){return Id.some(t=>{try{return e.matches(t)}catch{return!1}})}const Bd=["transform","translate","scale","rotate","perspective"],Dd=["transform","translate","scale","rotate","perspective","filter"],Nd=["paint","layout","strict","content"];function Qi(e){const t=xn(),i=ct(e)?dt(e):e;return Bd.some(r=>i[r]?i[r]!=="none":!1)||(i.containerType?i.containerType!=="normal":!1)||!t&&(i.backdropFilter?i.backdropFilter!=="none":!1)||!t&&(i.filter?i.filter!=="none":!1)||Dd.some(r=>(i.willChange||"").includes(r))||Nd.some(r=>(i.contain||"").includes(r))}function Ud(e){let t=Nt(e);for(;_t(t)&&!ge(t);){if(Qi(t))return t;if(Ki(t))return null;t=Nt(t)}return null}function xn(){return typeof CSS>"u"||!CSS.supports?!1:CSS.supports("-webkit-backdrop-filter","none")}const Hd=new Set(["html","body","#document"]);function ge(e){return Hd.has(Se(e))}function dt(e){return tt(e).getComputedStyle(e)}function Ji(e){return ct(e)?{scrollLeft:e.scrollLeft,scrollTop:e.scrollTop}:{scrollLeft:e.scrollX,scrollTop:e.scrollY}}function Nt(e){if(Se(e)==="html")return e;const t=e.assignedSlot||e.parentNode||Ss(e)&&e.host||Et(e);return Ss(t)?t.host:t}function ia(e){const t=Nt(e);return ge(t)?e.ownerDocument?e.ownerDocument.body:e.body:_t(t)&&ri(t)?t:ia(t)}function Xe(e,t,i){var r;t===void 0&&(t=[]),i===void 0&&(i=!0);const n=ia(e),s=n===((r=e.ownerDocument)==null?void 0:r.body),o=tt(n);if(s){const a=Dr(o);return t.concat(o,o.visualViewport||[],ri(n)?n:[],a&&i?Xe(a):[])}return t.concat(n,Xe(n,[],i))}function Dr(e){return e.parent&&Object.getPrototypeOf(e.parent)?e.frameElement:null}function ra(e){const t=dt(e);let i=parseFloat(t.width)||0,r=parseFloat(t.height)||0;const n=_t(e),s=n?e.offsetWidth:i,o=n?e.offsetHeight:r,a=Oi(i)!==s||Oi(r)!==o;return a&&(i=s,r=o),{width:i,height:r,$:a}}function En(e){return ct(e)?e:e.contextElement}function pe(e){const t=En(e);if(!_t(t))return wt(1);const i=t.getBoundingClientRect(),{width:r,height:n,$:s}=ra(t);let o=(s?Oi(i.width):i.width)/r,a=(s?Oi(i.height):i.height)/n;return(!o||!Number.isFinite(o))&&(o=1),(!a||!Number.isFinite(a))&&(a=1),{x:o,y:a}}const qd=wt(0);function na(e){const t=tt(e);return!xn()||!t.visualViewport?qd:{x:t.visualViewport.offsetLeft,y:t.visualViewport.offsetTop}}function Vd(e,t,i){return t===void 0&&(t=!1),!i||t&&i!==tt(e)?!1:t}function ae(e,t,i,r){t===void 0&&(t=!1),i===void 0&&(i=!1);const n=e.getBoundingClientRect(),s=En(e);let o=wt(1);t&&(r?ct(r)&&(o=pe(r)):o=pe(e));const a=Vd(s,i,r)?na(s):wt(0);let l=(n.left+a.x)/o.x,u=(n.top+a.y)/o.y,c=n.width/o.x,d=n.height/o.y;if(s){const h=tt(s),p=r&&ct(r)?tt(r):r;let f=h,v=Dr(f);for(;v&&r&&p!==f;){const m=pe(v),w=v.getBoundingClientRect(),x=dt(v),E=w.left+(v.clientLeft+parseFloat(x.paddingLeft))*m.x,S=w.top+(v.clientTop+parseFloat(x.paddingTop))*m.y;l*=m.x,u*=m.y,c*=m.x,d*=m.y,l+=E,u+=S,f=tt(v),v=Dr(f)}}return Li({width:c,height:d,x:l,y:u})}function Xi(e,t){const i=Ji(e).scrollLeft;return t?t.left+i:ae(Et(e)).left+i}function sa(e,t){const i=e.getBoundingClientRect(),r=i.left+t.scrollLeft-Xi(e,i),n=i.top+t.scrollTop;return{x:r,y:n}}function jd(e){let{elements:t,rect:i,offsetParent:r,strategy:n}=e;const s=n==="fixed",o=Et(r),a=t?Ki(t.floating):!1;if(r===o||a&&s)return i;let l={scrollLeft:0,scrollTop:0},u=wt(1);const c=wt(0),d=_t(r);if((d||!d&&!s)&&((Se(r)!=="body"||ri(o))&&(l=Ji(r)),_t(r))){const p=ae(r);u=pe(r),c.x=p.x+r.clientLeft,c.y=p.y+r.clientTop}const h=o&&!d&&!s?sa(o,l):wt(0);return{width:i.width*u.x,height:i.height*u.y,x:i.x*u.x-l.scrollLeft*u.x+c.x+h.x,y:i.y*u.y-l.scrollTop*u.y+c.y+h.y}}function Wd(e){return Array.from(e.getClientRects())}function Kd(e){const t=Et(e),i=Ji(e),r=e.ownerDocument.body,n=Z(t.scrollWidth,t.clientWidth,r.scrollWidth,r.clientWidth),s=Z(t.scrollHeight,t.clientHeight,r.scrollHeight,r.clientHeight);let o=-i.scrollLeft+Xi(e);const a=-i.scrollTop;return dt(r).direction==="rtl"&&(o+=Z(t.clientWidth,r.clientWidth)-n),{width:n,height:s,x:o,y:a}}const Cs=25;function Qd(e,t){const i=tt(e),r=Et(e),n=i.visualViewport;let s=r.clientWidth,o=r.clientHeight,a=0,l=0;if(n){s=n.width,o=n.height;const c=xn();(!c||c&&t==="fixed")&&(a=n.offsetLeft,l=n.offsetTop)}const u=Xi(r);if(u<=0){const c=r.ownerDocument,d=c.body,h=getComputedStyle(d),p=c.compatMode==="CSS1Compat"&&parseFloat(h.marginLeft)+parseFloat(h.marginRight)||0,f=Math.abs(r.clientWidth-d.clientWidth-p);f<=Cs&&(s-=f)}else u<=Cs&&(s+=u);return{width:s,height:o,x:a,y:l}}const Jd=new Set(["absolute","fixed"]);function Xd(e,t){const i=ae(e,!0,t==="fixed"),r=i.top+e.clientTop,n=i.left+e.clientLeft,s=_t(e)?pe(e):wt(1),o=e.clientWidth*s.x,a=e.clientHeight*s.y,l=n*s.x,u=r*s.y;return{width:o,height:a,x:l,y:u}}function ks(e,t,i){let r;if(t==="viewport")r=Qd(e,i);else if(t==="document")r=Kd(Et(e));else if(ct(t))r=Xd(t,i);else{const n=na(e);r={x:t.x-n.x,y:t.y-n.y,width:t.width,height:t.height}}return Li(r)}function oa(e,t){const i=Nt(e);return i===t||!ct(i)||ge(i)?!1:dt(i).position==="fixed"||oa(i,t)}function Gd(e,t){const i=t.get(e);if(i)return i;let r=Xe(e,[],!1).filter(a=>ct(a)&&Se(a)!=="body"),n=null;const s=dt(e).position==="fixed";let o=s?Nt(e):e;for(;ct(o)&&!ge(o);){const a=dt(o),l=Qi(o);!l&&a.position==="fixed"&&(n=null),(s?!l&&!n:!l&&a.position==="static"&&!!n&&Jd.has(n.position)||ri(o)&&!l&&oa(e,o))?r=r.filter(c=>c!==o):n=a,o=Nt(o)}return t.set(e,r),r}function Yd(e){let{element:t,boundary:i,rootBoundary:r,strategy:n}=e;const o=[...i==="clippingAncestors"?Ki(t)?[]:Gd(t,this._c):[].concat(i),r],a=o[0],l=o.reduce((u,c)=>{const d=ks(t,c,n);return u.top=Z(d.top,u.top),u.right=Bt(d.right,u.right),u.bottom=Bt(d.bottom,u.bottom),u.left=Z(d.left,u.left),u},ks(t,a,n));return{width:l.right-l.left,height:l.bottom-l.top,x:l.left,y:l.top}}function Zd(e){const{width:t,height:i}=ra(e);return{width:t,height:i}}function th(e,t,i){const r=_t(t),n=Et(t),s=i==="fixed",o=ae(e,!0,s,t);let a={scrollLeft:0,scrollTop:0};const l=wt(0);function u(){l.x=Xi(n)}if(r||!r&&!s)if((Se(t)!=="body"||ri(n))&&(a=Ji(t)),r){const p=ae(t,!0,s,t);l.x=p.x+t.clientLeft,l.y=p.y+t.clientTop}else n&&u();s&&!r&&n&&u();const c=n&&!r&&!s?sa(n,a):wt(0),d=o.left+a.scrollLeft-l.x-c.x,h=o.top+a.scrollTop-l.y-c.y;return{x:d,y:h,width:o.width,height:o.height}}function gr(e){return dt(e).position==="static"}function As(e,t){if(!_t(e)||dt(e).position==="fixed")return null;if(t)return t(e);let i=e.offsetParent;return Et(e)===i&&(i=i.ownerDocument.body),i}function aa(e,t){const i=tt(e);if(Ki(e))return i;if(!_t(e)){let n=Nt(e);for(;n&&!ge(n);){if(ct(n)&&!gr(n))return n;n=Nt(n)}return i}let r=As(e,t);for(;r&&zd(r)&&gr(r);)r=As(r,t);return r&&ge(r)&&gr(r)&&!Qi(r)?i:r||Ud(e)||i}const eh=async function(e){const t=this.getOffsetParent||aa,i=this.getDimensions,r=await i(e.floating);return{reference:th(e.reference,await t(e.floating),e.strategy),floating:{x:0,y:0,width:r.width,height:r.height}}};function ih(e){return dt(e).direction==="rtl"}const Si={convertOffsetParentRelativeRectToViewportRelativeRect:jd,getDocumentElement:Et,getClippingRect:Yd,getOffsetParent:aa,getElementRects:eh,getClientRects:Wd,getDimensions:Zd,getScale:pe,isElement:ct,isRTL:ih};function la(e,t){return e.x===t.x&&e.y===t.y&&e.width===t.width&&e.height===t.height}function rh(e,t){let i=null,r;const n=Et(e);function s(){var a;clearTimeout(r),(a=i)==null||a.disconnect(),i=null}function o(a,l){a===void 0&&(a=!1),l===void 0&&(l=1),s();const u=e.getBoundingClientRect(),{left:c,top:d,width:h,height:p}=u;if(a||t(),!h||!p)return;const f=ci(d),v=ci(n.clientWidth-(c+h)),m=ci(n.clientHeight-(d+p)),w=ci(c),E={rootMargin:-f+"px "+-v+"px "+-m+"px "+-w+"px",threshold:Z(0,Bt(1,l))||1};let S=!0;function C(F){const R=F[0].intersectionRatio;if(R!==l){if(!S)return o();R?o(!1,R):r=setTimeout(()=>{o(!1,1e-7)},1e3)}R===1&&!la(u,e.getBoundingClientRect())&&o(),S=!1}try{i=new IntersectionObserver(C,{...E,root:n.ownerDocument})}catch{i=new IntersectionObserver(C,E)}i.observe(e)}return o(!0),s}function nh(e,t,i,r){r===void 0&&(r={});const{ancestorScroll:n=!0,ancestorResize:s=!0,elementResize:o=typeof ResizeObserver=="function",layoutShift:a=typeof IntersectionObserver=="function",animationFrame:l=!1}=r,u=En(e),c=n||s?[...u?Xe(u):[],...Xe(t)]:[];c.forEach(w=>{n&&w.addEventListener("scroll",i,{passive:!0}),s&&w.addEventListener("resize",i)});const d=u&&a?rh(u,i):null;let h=-1,p=null;o&&(p=new ResizeObserver(w=>{let[x]=w;x&&x.target===u&&p&&(p.unobserve(t),cancelAnimationFrame(h),h=requestAnimationFrame(()=>{var E;(E=p)==null||E.observe(t)})),i()}),u&&!l&&p.observe(u),p.observe(t));let f,v=l?ae(e):null;l&&m();function m(){const w=ae(e);v&&!la(v,w)&&i(),v=w,f=requestAnimationFrame(m)}return i(),()=>{var w;c.forEach(x=>{n&&x.removeEventListener("scroll",i),s&&x.removeEventListener("resize",i)}),d==null||d(),(w=p)==null||w.disconnect(),p=null,l&&cancelAnimationFrame(f)}}const sh=Od,oh=Fd,ah=$d,$s=Ld,lh=Ad,uh=(e,t,i)=>{const r=new Map,n={platform:Si,...i},s={...n.platform,_c:r};return kd(e,t,{...n,platform:s})};function ch(e){return dh(e)}function br(e){return e.assignedSlot?e.assignedSlot:e.parentNode instanceof ShadowRoot?e.parentNode.host:e.parentNode}function dh(e){for(let t=e;t;t=br(t))if(t instanceof Element&&getComputedStyle(t).display==="none")return null;for(let t=br(e);t;t=br(t)){if(!(t instanceof Element))continue;const i=getComputedStyle(t);if(i.display!=="contents"&&(i.position!=="static"||Qi(i)||t.tagName==="BODY"))return t}return null}function hh(e){return e!==null&&typeof e=="object"&&"getBoundingClientRect"in e&&("contextElement"in e?e.contextElement instanceof Element:!0)}var L=class extends rt{constructor(){super(...arguments),this.localize=new _e(this),this.active=!1,this.placement="top",this.strategy="absolute",this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement="anchor",this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements="",this.flipFallbackStrategy="best-fit",this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){const e=this.anchorEl.getBoundingClientRect(),t=this.popup.getBoundingClientRect(),i=this.placement.includes("top")||this.placement.includes("bottom");let r=0,n=0,s=0,o=0,a=0,l=0,u=0,c=0;i?e.top<t.top?(r=e.left,n=e.bottom,s=e.right,o=e.bottom,a=t.left,l=t.top,u=t.right,c=t.top):(r=t.left,n=t.bottom,s=t.right,o=t.bottom,a=e.left,l=e.top,u=e.right,c=e.top):e.left<t.left?(r=e.right,n=e.top,s=t.left,o=t.top,a=e.right,l=e.bottom,u=t.left,c=t.bottom):(r=t.right,n=t.top,s=e.left,o=e.top,a=t.right,l=t.bottom,u=e.left,c=e.bottom),this.style.setProperty("--hover-bridge-top-left-x",`${r}px`),this.style.setProperty("--hover-bridge-top-left-y",`${n}px`),this.style.setProperty("--hover-bridge-top-right-x",`${s}px`),this.style.setProperty("--hover-bridge-top-right-y",`${o}px`),this.style.setProperty("--hover-bridge-bottom-left-x",`${a}px`),this.style.setProperty("--hover-bridge-bottom-left-y",`${l}px`),this.style.setProperty("--hover-bridge-bottom-right-x",`${u}px`),this.style.setProperty("--hover-bridge-bottom-right-y",`${c}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(e){super.updated(e),e.has("active")&&(this.active?this.start():this.stop()),e.has("anchor")&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor=="string"){const e=this.getRootNode();this.anchorEl=e.getElementById(this.anchor)}else this.anchor instanceof Element||hh(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector('[slot="anchor"]');this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.active&&this.start()}start(){!this.anchorEl||!this.active||(this.cleanup=nh(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(e=>{this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute("data-current-placement"),this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height"),requestAnimationFrame(()=>e())):e()})}reposition(){if(!this.active||!this.anchorEl)return;const e=[sh({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?e.push($s({apply:({rects:i})=>{const r=this.sync==="width"||this.sync==="both",n=this.sync==="height"||this.sync==="both";this.popup.style.width=r?`${i.reference.width}px`:"",this.popup.style.height=n?`${i.reference.height}px`:""}})):(this.popup.style.width="",this.popup.style.height=""),this.flip&&e.push(ah({boundary:this.flipBoundary,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy==="best-fit"?"bestFit":"initialPlacement",padding:this.flipPadding})),this.shift&&e.push(oh({boundary:this.shiftBoundary,padding:this.shiftPadding})),this.autoSize?e.push($s({boundary:this.autoSizeBoundary,padding:this.autoSizePadding,apply:({availableWidth:i,availableHeight:r})=>{this.autoSize==="vertical"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-height",`${r}px`):this.style.removeProperty("--auto-size-available-height"),this.autoSize==="horizontal"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-width",`${i}px`):this.style.removeProperty("--auto-size-available-width")}})):(this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height")),this.arrow&&e.push(lh({element:this.arrowEl,padding:this.arrowPadding}));const t=this.strategy==="absolute"?i=>Si.getOffsetParent(i,ch):Si.getOffsetParent;uh(this.anchorEl,this.popup,{placement:this.placement,middleware:e,strategy:this.strategy,platform:ji(ue({},Si),{getOffsetParent:t})}).then(({x:i,y:r,middlewareData:n,placement:s})=>{const o=this.localize.dir()==="rtl",a={top:"bottom",right:"left",bottom:"top",left:"right"}[s.split("-")[0]];if(this.setAttribute("data-current-placement",s),Object.assign(this.popup.style,{left:`${i}px`,top:`${r}px`}),this.arrow){const l=n.arrow.x,u=n.arrow.y;let c="",d="",h="",p="";if(this.arrowPlacement==="start"){const f=typeof l=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";c=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"",d=o?f:"",p=o?"":f}else if(this.arrowPlacement==="end"){const f=typeof l=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";d=o?"":f,p=o?f:"",h=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:""}else this.arrowPlacement==="center"?(p=typeof l=="number"?"calc(50% - var(--arrow-size-diagonal))":"",c=typeof u=="number"?"calc(50% - var(--arrow-size-diagonal))":""):(p=typeof l=="number"?`${l}px`:"",c=typeof u=="number"?`${u}px`:"");Object.assign(this.arrowEl.style,{top:c,right:d,bottom:h,left:p,[a]:"calc(var(--arrow-size-diagonal) * -1)"})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.emit("sl-reposition")}render(){return I`
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
    `}};L.styles=[$t,md];g([it(".popup")],L.prototype,"popup",2);g([it(".popup__arrow")],L.prototype,"arrowEl",2);g([y()],L.prototype,"anchor",2);g([y({type:Boolean,reflect:!0})],L.prototype,"active",2);g([y({reflect:!0})],L.prototype,"placement",2);g([y({reflect:!0})],L.prototype,"strategy",2);g([y({type:Number})],L.prototype,"distance",2);g([y({type:Number})],L.prototype,"skidding",2);g([y({type:Boolean})],L.prototype,"arrow",2);g([y({attribute:"arrow-placement"})],L.prototype,"arrowPlacement",2);g([y({attribute:"arrow-padding",type:Number})],L.prototype,"arrowPadding",2);g([y({type:Boolean})],L.prototype,"flip",2);g([y({attribute:"flip-fallback-placements",converter:{fromAttribute:e=>e.split(" ").map(t=>t.trim()).filter(t=>t!==""),toAttribute:e=>e.join(" ")}})],L.prototype,"flipFallbackPlacements",2);g([y({attribute:"flip-fallback-strategy"})],L.prototype,"flipFallbackStrategy",2);g([y({type:Object})],L.prototype,"flipBoundary",2);g([y({attribute:"flip-padding",type:Number})],L.prototype,"flipPadding",2);g([y({type:Boolean})],L.prototype,"shift",2);g([y({type:Object})],L.prototype,"shiftBoundary",2);g([y({attribute:"shift-padding",type:Number})],L.prototype,"shiftPadding",2);g([y({attribute:"auto-size"})],L.prototype,"autoSize",2);g([y()],L.prototype,"sync",2);g([y({type:Object})],L.prototype,"autoSizeBoundary",2);g([y({attribute:"auto-size-padding",type:Number})],L.prototype,"autoSizePadding",2);g([y({attribute:"hover-bridge",type:Boolean})],L.prototype,"hoverBridge",2);var ua=new Map,ph=new WeakMap;function fh(e){return e??{keyframes:[],options:{duration:0}}}function Ts(e,t){return t.toLowerCase()==="rtl"?{keyframes:e.rtlKeyframes||e.keyframes,options:e.options}:e}function ca(e,t){ua.set(e,fh(t))}function Rs(e,t,i){const r=ph.get(e);if(r!=null&&r[t])return Ts(r[t],i.dir);const n=ua.get(t);return n?Ts(n,i.dir):{keyframes:[],options:{duration:0}}}function Os(e,t){return new Promise(i=>{function r(n){n.target===e&&(e.removeEventListener(t,r),i())}e.addEventListener(t,r)})}function Fs(e,t,i){return new Promise(r=>{if((i==null?void 0:i.duration)===1/0)throw new Error("Promise-based animations must be finite.");const n=e.animate(t,ji(ue({},i),{duration:mh()?0:i.duration}));n.addEventListener("cancel",r,{once:!0}),n.addEventListener("finish",r,{once:!0})})}function mh(){return window.matchMedia("(prefers-reduced-motion: reduce)").matches}function Ls(e){return Promise.all(e.getAnimations().map(t=>new Promise(i=>{t.cancel(),requestAnimationFrame(i)})))}var V=class extends rt{constructor(){super(...arguments),this.localize=new _e(this),this.open=!1,this.placement="bottom-start",this.disabled=!1,this.stayOpenOnSelect=!1,this.distance=0,this.skidding=0,this.hoist=!1,this.sync=void 0,this.handleKeyDown=e=>{this.open&&e.key==="Escape"&&(e.stopPropagation(),this.hide(),this.focusOnTrigger())},this.handleDocumentKeyDown=e=>{var t;if(e.key==="Escape"&&this.open&&!this.closeWatcher){e.stopPropagation(),this.focusOnTrigger(),this.hide();return}if(e.key==="Tab"){if(this.open&&((t=document.activeElement)==null?void 0:t.tagName.toLowerCase())==="sl-menu-item"){e.preventDefault(),this.hide(),this.focusOnTrigger();return}const i=(r,n)=>{if(!r)return null;const s=r.closest(n);if(s)return s;const o=r.getRootNode();return o instanceof ShadowRoot?i(o.host,n):null};setTimeout(()=>{var r;const n=((r=this.containingElement)==null?void 0:r.getRootNode())instanceof ShadowRoot?ld():document.activeElement;(!this.containingElement||i(n,this.containingElement.tagName.toLowerCase())!==this.containingElement)&&this.hide()})}},this.handleDocumentMouseDown=e=>{const t=e.composedPath();this.containingElement&&!t.includes(this.containingElement)&&this.hide()},this.handlePanelSelect=e=>{const t=e.target;!this.stayOpenOnSelect&&t.tagName.toLowerCase()==="sl-menu"&&(this.hide(),this.focusOnTrigger())}}connectedCallback(){super.connectedCallback(),this.containingElement||(this.containingElement=this)}firstUpdated(){this.panel.hidden=!this.open,this.open&&(this.addOpenListeners(),this.popup.active=!0)}disconnectedCallback(){super.disconnectedCallback(),this.removeOpenListeners(),this.hide()}focusOnTrigger(){const e=this.trigger.assignedElements({flatten:!0})[0];typeof(e==null?void 0:e.focus)=="function"&&e.focus()}getMenu(){return this.panel.assignedElements({flatten:!0}).find(e=>e.tagName.toLowerCase()==="sl-menu")}handleTriggerClick(){this.open?this.hide():(this.show(),this.focusOnTrigger())}async handleTriggerKeyDown(e){if([" ","Enter"].includes(e.key)){e.preventDefault(),this.handleTriggerClick();return}const t=this.getMenu();if(t){const i=t.getAllItems(),r=i[0],n=i[i.length-1];["ArrowDown","ArrowUp","Home","End"].includes(e.key)&&(e.preventDefault(),this.open||(this.show(),await this.updateComplete),i.length>0&&this.updateComplete.then(()=>{(e.key==="ArrowDown"||e.key==="Home")&&(t.setCurrentItem(r),r.focus()),(e.key==="ArrowUp"||e.key==="End")&&(t.setCurrentItem(n),n.focus())}))}}handleTriggerKeyUp(e){e.key===" "&&e.preventDefault()}handleTriggerSlotChange(){this.updateAccessibleTrigger()}updateAccessibleTrigger(){const t=this.trigger.assignedElements({flatten:!0}).find(r=>hd(r).start);let i;if(t){switch(t.tagName.toLowerCase()){case"sl-button":case"sl-icon-button":i=t.button;break;default:i=t}i.setAttribute("aria-haspopup","true"),i.setAttribute("aria-expanded",this.open?"true":"false")}}async show(){if(!this.open)return this.open=!0,Os(this,"sl-after-show")}async hide(){if(this.open)return this.open=!1,Os(this,"sl-after-hide")}reposition(){this.popup.reposition()}addOpenListeners(){var e;this.panel.addEventListener("sl-select",this.handlePanelSelect),"CloseWatcher"in window?((e=this.closeWatcher)==null||e.destroy(),this.closeWatcher=new CloseWatcher,this.closeWatcher.onclose=()=>{this.hide(),this.focusOnTrigger()}):this.panel.addEventListener("keydown",this.handleKeyDown),document.addEventListener("keydown",this.handleDocumentKeyDown),document.addEventListener("mousedown",this.handleDocumentMouseDown)}removeOpenListeners(){var e;this.panel&&(this.panel.removeEventListener("sl-select",this.handlePanelSelect),this.panel.removeEventListener("keydown",this.handleKeyDown)),document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("mousedown",this.handleDocumentMouseDown),(e=this.closeWatcher)==null||e.destroy()}async handleOpenChange(){if(this.disabled){this.open=!1;return}if(this.updateAccessibleTrigger(),this.open){this.emit("sl-show"),this.addOpenListeners(),await Ls(this),this.panel.hidden=!1,this.popup.active=!0;const{keyframes:e,options:t}=Rs(this,"dropdown.show",{dir:this.localize.dir()});await Fs(this.popup.popup,e,t),this.emit("sl-after-show")}else{this.emit("sl-hide"),this.removeOpenListeners(),await Ls(this);const{keyframes:e,options:t}=Rs(this,"dropdown.hide",{dir:this.localize.dir()});await Fs(this.popup.popup,e,t),this.panel.hidden=!0,this.popup.active=!1,this.emit("sl-after-hide")}}render(){return I`
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
        sync=${O(this.sync?this.sync:void 0)}
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
    `}};V.styles=[$t,ad];V.dependencies={"sl-popup":L};g([it(".dropdown")],V.prototype,"popup",2);g([it(".dropdown__trigger")],V.prototype,"trigger",2);g([it(".dropdown__panel")],V.prototype,"panel",2);g([y({type:Boolean,reflect:!0})],V.prototype,"open",2);g([y({reflect:!0})],V.prototype,"placement",2);g([y({type:Boolean,reflect:!0})],V.prototype,"disabled",2);g([y({attribute:"stay-open-on-select",type:Boolean,reflect:!0})],V.prototype,"stayOpenOnSelect",2);g([y({attribute:!1})],V.prototype,"containingElement",2);g([y({type:Number})],V.prototype,"distance",2);g([y({type:Number})],V.prototype,"skidding",2);g([y({type:Boolean})],V.prototype,"hoist",2);g([y({reflect:!0})],V.prototype,"sync",2);g([xt("open",{waitUntilFirstUpdate:!0})],V.prototype,"handleOpenChange",1);ca("dropdown.show",{keyframes:[{opacity:0,scale:.9},{opacity:1,scale:1}],options:{duration:100,easing:"ease"}});ca("dropdown.hide",{keyframes:[{opacity:1,scale:1},{opacity:0,scale:.9}],options:{duration:100,easing:"ease"}});var gh=ft`
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
`;function nt(e,t,i){const r=n=>Object.is(n,-0)?0:n;return e<t?r(t):e>i?r(i):r(e)}var bh=ft`
  :host {
    display: inline-block;
  }

  .button-group {
    display: flex;
    flex-wrap: nowrap;
  }
`,ni=class extends rt{constructor(){super(...arguments),this.disableRole=!1,this.label=""}handleFocus(e){const t=ze(e.target);t==null||t.toggleAttribute("data-sl-button-group__button--focus",!0)}handleBlur(e){const t=ze(e.target);t==null||t.toggleAttribute("data-sl-button-group__button--focus",!1)}handleMouseOver(e){const t=ze(e.target);t==null||t.toggleAttribute("data-sl-button-group__button--hover",!0)}handleMouseOut(e){const t=ze(e.target);t==null||t.toggleAttribute("data-sl-button-group__button--hover",!1)}handleSlotChange(){const e=[...this.defaultSlot.assignedElements({flatten:!0})];e.forEach(t=>{const i=e.indexOf(t),r=ze(t);r&&(r.toggleAttribute("data-sl-button-group__button",!0),r.toggleAttribute("data-sl-button-group__button--first",i===0),r.toggleAttribute("data-sl-button-group__button--inner",i>0&&i<e.length-1),r.toggleAttribute("data-sl-button-group__button--last",i===e.length-1),r.toggleAttribute("data-sl-button-group__button--radio",r.tagName.toLowerCase()==="sl-radio-button"))})}render(){return I`
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
    `}};ni.styles=[$t,bh];g([it("slot")],ni.prototype,"defaultSlot",2);g([et()],ni.prototype,"disableRole",2);g([y()],ni.prototype,"label",2);function ze(e){var t;const i="sl-button, sl-radio-button";return(t=e.closest(i))!=null?t:e.querySelector(i)}var vh=ft`
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
`,da=class extends rt{constructor(){super(...arguments),this.localize=new _e(this)}render(){return I`
      <svg part="base" class="spinner" role="progressbar" aria-label=${this.localize.term("loading")}>
        <circle class="spinner__track"></circle>
        <circle class="spinner__indicator"></circle>
      </svg>
    `}};da.styles=[$t,vh];var yh=ft`
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
 */const ha=Symbol.for(""),wh=e=>{if((e==null?void 0:e.r)===ha)return e==null?void 0:e._$litStatic$},Ms=(e,...t)=>({_$litStatic$:t.reduce(((i,r,n)=>i+(s=>{if(s._$litStatic$!==void 0)return s._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${s}. Use 'unsafeStatic' to pass non-literal values, but
            take care to ensure page security.`)})(r)+e[n+1]),e[0]),r:ha}),Ps=new Map,_h=e=>(t,...i)=>{const r=i.length;let n,s;const o=[],a=[];let l,u=0,c=!1;for(;u<r;){for(l=t[u];u<r&&(s=i[u],(n=wh(s))!==void 0);)l+=n+t[++u],c=!0;u!==r&&a.push(s),o.push(l),u++}if(u===r&&o.push(t[r]),c){const d=o.join("$$lit$$");(t=Ps.get(d))===void 0&&(o.raw=o,Ps.set(d,t=o)),i=a}return e(t,...i)},vr=_h(I);var T=class extends rt{constructor(){super(...arguments),this.formControlController=new pn(this,{assumeInteractionOn:["click"]}),this.hasSlotController=new Wo(this,"[default]","prefix","suffix"),this.localize=new _e(this),this.hasFocus=!1,this.invalid=!1,this.title="",this.variant="default",this.size="medium",this.caret=!1,this.disabled=!1,this.loading=!1,this.outline=!1,this.pill=!1,this.circle=!1,this.type="button",this.name="",this.value="",this.href="",this.rel="noreferrer noopener"}get validity(){return this.isButton()?this.button.validity:fn}get validationMessage(){return this.isButton()?this.button.validationMessage:""}firstUpdated(){this.isButton()&&this.formControlController.updateValidity()}handleBlur(){this.hasFocus=!1,this.emit("sl-blur")}handleFocus(){this.hasFocus=!0,this.emit("sl-focus")}handleClick(){this.type==="submit"&&this.formControlController.submit(this),this.type==="reset"&&this.formControlController.reset(this)}handleInvalid(e){this.formControlController.setValidity(!1),this.formControlController.emitInvalidEvent(e)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.isButton()&&this.formControlController.setValidity(this.disabled)}click(){this.button.click()}focus(e){this.button.focus(e)}blur(){this.button.blur()}checkValidity(){return this.isButton()?this.button.checkValidity():!0}getForm(){return this.formControlController.getForm()}reportValidity(){return this.isButton()?this.button.reportValidity():!0}setCustomValidity(e){this.isButton()&&(this.button.setCustomValidity(e),this.formControlController.updateValidity())}render(){const e=this.isLink(),t=e?Ms`a`:Ms`button`;return vr`
      <${t}
        part="base"
        class=${kt({button:!0,"button--default":this.variant==="default","button--primary":this.variant==="primary","button--success":this.variant==="success","button--neutral":this.variant==="neutral","button--warning":this.variant==="warning","button--danger":this.variant==="danger","button--text":this.variant==="text","button--small":this.size==="small","button--medium":this.size==="medium","button--large":this.size==="large","button--caret":this.caret,"button--circle":this.circle,"button--disabled":this.disabled,"button--focused":this.hasFocus,"button--loading":this.loading,"button--standard":!this.outline,"button--outline":this.outline,"button--pill":this.pill,"button--rtl":this.localize.dir()==="rtl","button--has-label":this.hasSlotController.test("[default]"),"button--has-prefix":this.hasSlotController.test("prefix"),"button--has-suffix":this.hasSlotController.test("suffix")})}
        ?disabled=${O(e?void 0:this.disabled)}
        type=${O(e?void 0:this.type)}
        title=${this.title}
        name=${O(e?void 0:this.name)}
        value=${O(e?void 0:this.value)}
        href=${O(e&&!this.disabled?this.href:void 0)}
        target=${O(e?this.target:void 0)}
        download=${O(e?this.download:void 0)}
        rel=${O(e?this.rel:void 0)}
        role=${O(e?void 0:"button")}
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
        ${this.caret?vr` <sl-icon part="caret" class="button__caret" library="system" name="caret"></sl-icon> `:""}
        ${this.loading?vr`<sl-spinner part="spinner"></sl-spinner>`:""}
      </${t}>
    `}};T.styles=[$t,yh];T.dependencies={"sl-icon":mt,"sl-spinner":da};g([it(".button")],T.prototype,"button",2);g([et()],T.prototype,"hasFocus",2);g([et()],T.prototype,"invalid",2);g([y()],T.prototype,"title",2);g([y({reflect:!0})],T.prototype,"variant",2);g([y({reflect:!0})],T.prototype,"size",2);g([y({type:Boolean,reflect:!0})],T.prototype,"caret",2);g([y({type:Boolean,reflect:!0})],T.prototype,"disabled",2);g([y({type:Boolean,reflect:!0})],T.prototype,"loading",2);g([y({type:Boolean,reflect:!0})],T.prototype,"outline",2);g([y({type:Boolean,reflect:!0})],T.prototype,"pill",2);g([y({type:Boolean,reflect:!0})],T.prototype,"circle",2);g([y()],T.prototype,"type",2);g([y()],T.prototype,"name",2);g([y()],T.prototype,"value",2);g([y()],T.prototype,"href",2);g([y()],T.prototype,"target",2);g([y()],T.prototype,"rel",2);g([y()],T.prototype,"download",2);g([y()],T.prototype,"form",2);g([y({attribute:"formaction"})],T.prototype,"formAction",2);g([y({attribute:"formenctype"})],T.prototype,"formEnctype",2);g([y({attribute:"formmethod"})],T.prototype,"formMethod",2);g([y({attribute:"formnovalidate",type:Boolean})],T.prototype,"formNoValidate",2);g([y({attribute:"formtarget"})],T.prototype,"formTarget",2);g([xt("disabled",{waitUntilFirstUpdate:!0})],T.prototype,"handleDisabledChange",1);/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const pa="important",xh=" !"+pa,Ft=bn(class extends vn{constructor(e){var t;if(super(e),e.type!==Mt.ATTRIBUTE||e.name!=="style"||((t=e.strings)==null?void 0:t.length)>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(e){return Object.keys(e).reduce(((t,i)=>{const r=e[i];return r==null?t:t+`${i=i.includes("-")?i:i.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,"-$&").toLowerCase()}:${r};`}),"")}update(e,[t]){const{style:i}=e.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(t)),this.render(t);for(const r of this.ft)t[r]==null&&(this.ft.delete(r),r.includes("-")?i.removeProperty(r):i[r]=null);for(const r in t){const n=t[r];if(n!=null){this.ft.add(r);const s=typeof n=="string"&&n.endsWith(xh);r.includes("-")||s?i.setProperty(r,s?n.slice(0,-11):n,s?pa:""):i[r]=n}}return ot}});function U(e,t){Eh(e)&&(e="100%");const i=Sh(e);return e=t===360?e:Math.min(t,Math.max(0,parseFloat(e))),i&&(e=parseInt(String(e*t),10)/100),Math.abs(e-t)<1e-6?1:(t===360?e=(e<0?e%t+t:e%t)/parseFloat(String(t)):e=e%t/parseFloat(String(t)),e)}function di(e){return Math.min(1,Math.max(0,e))}function Eh(e){return typeof e=="string"&&e.indexOf(".")!==-1&&parseFloat(e)===1}function Sh(e){return typeof e=="string"&&e.indexOf("%")!==-1}function fa(e){return e=parseFloat(e),(isNaN(e)||e<0||e>1)&&(e=1),e}function hi(e){return Number(e)<=1?`${Number(e)*100}%`:e}function Gt(e){return e.length===1?"0"+e:String(e)}function Ch(e,t,i){return{r:U(e,255)*255,g:U(t,255)*255,b:U(i,255)*255}}function zs(e,t,i){e=U(e,255),t=U(t,255),i=U(i,255);const r=Math.max(e,t,i),n=Math.min(e,t,i);let s=0,o=0;const a=(r+n)/2;if(r===n)o=0,s=0;else{const l=r-n;switch(o=a>.5?l/(2-r-n):l/(r+n),r){case e:s=(t-i)/l+(t<i?6:0);break;case t:s=(i-e)/l+2;break;case i:s=(e-t)/l+4;break}s/=6}return{h:s,s:o,l:a}}function yr(e,t,i){return i<0&&(i+=1),i>1&&(i-=1),i<1/6?e+(t-e)*(6*i):i<1/2?t:i<2/3?e+(t-e)*(2/3-i)*6:e}function kh(e,t,i){let r,n,s;if(e=U(e,360),t=U(t,100),i=U(i,100),t===0)n=i,s=i,r=i;else{const o=i<.5?i*(1+t):i+t-i*t,a=2*i-o;r=yr(a,o,e+1/3),n=yr(a,o,e),s=yr(a,o,e-1/3)}return{r:r*255,g:n*255,b:s*255}}function Is(e,t,i){e=U(e,255),t=U(t,255),i=U(i,255);const r=Math.max(e,t,i),n=Math.min(e,t,i);let s=0;const o=r,a=r-n,l=r===0?0:a/r;if(r===n)s=0;else{switch(r){case e:s=(t-i)/a+(t<i?6:0);break;case t:s=(i-e)/a+2;break;case i:s=(e-t)/a+4;break}s/=6}return{h:s,s:l,v:o}}function Ah(e,t,i){e=U(e,360)*6,t=U(t,100),i=U(i,100);const r=Math.floor(e),n=e-r,s=i*(1-t),o=i*(1-n*t),a=i*(1-(1-n)*t),l=r%6,u=[i,o,s,s,a,i][l],c=[a,i,i,o,s,s][l],d=[s,s,a,i,i,o][l];return{r:u*255,g:c*255,b:d*255}}function Bs(e,t,i,r){const n=[Gt(Math.round(e).toString(16)),Gt(Math.round(t).toString(16)),Gt(Math.round(i).toString(16))];return r&&n[0].startsWith(n[0].charAt(1))&&n[1].startsWith(n[1].charAt(1))&&n[2].startsWith(n[2].charAt(1))?n[0].charAt(0)+n[1].charAt(0)+n[2].charAt(0):n.join("")}function $h(e,t,i,r,n){const s=[Gt(Math.round(e).toString(16)),Gt(Math.round(t).toString(16)),Gt(Math.round(i).toString(16)),Gt(Rh(r))];return n&&s[0].startsWith(s[0].charAt(1))&&s[1].startsWith(s[1].charAt(1))&&s[2].startsWith(s[2].charAt(1))&&s[3].startsWith(s[3].charAt(1))?s[0].charAt(0)+s[1].charAt(0)+s[2].charAt(0)+s[3].charAt(0):s.join("")}function Th(e,t,i,r){const n=e/100,s=t/100,o=i/100,a=r/100,l=255*(1-n)*(1-a),u=255*(1-s)*(1-a),c=255*(1-o)*(1-a);return{r:l,g:u,b:c}}function Ds(e,t,i){let r=1-e/255,n=1-t/255,s=1-i/255,o=Math.min(r,n,s);return o===1?(r=0,n=0,s=0):(r=(r-o)/(1-o)*100,n=(n-o)/(1-o)*100,s=(s-o)/(1-o)*100),o*=100,{c:Math.round(r),m:Math.round(n),y:Math.round(s),k:Math.round(o)}}function Rh(e){return Math.round(parseFloat(e)*255).toString(16)}function Ns(e){return Y(e)/255}function Y(e){return parseInt(e,16)}function Oh(e){return{r:e>>16,g:(e&65280)>>8,b:e&255}}const Nr={aliceblue:"#f0f8ff",antiquewhite:"#faebd7",aqua:"#00ffff",aquamarine:"#7fffd4",azure:"#f0ffff",beige:"#f5f5dc",bisque:"#ffe4c4",black:"#000000",blanchedalmond:"#ffebcd",blue:"#0000ff",blueviolet:"#8a2be2",brown:"#a52a2a",burlywood:"#deb887",cadetblue:"#5f9ea0",chartreuse:"#7fff00",chocolate:"#d2691e",coral:"#ff7f50",cornflowerblue:"#6495ed",cornsilk:"#fff8dc",crimson:"#dc143c",cyan:"#00ffff",darkblue:"#00008b",darkcyan:"#008b8b",darkgoldenrod:"#b8860b",darkgray:"#a9a9a9",darkgreen:"#006400",darkgrey:"#a9a9a9",darkkhaki:"#bdb76b",darkmagenta:"#8b008b",darkolivegreen:"#556b2f",darkorange:"#ff8c00",darkorchid:"#9932cc",darkred:"#8b0000",darksalmon:"#e9967a",darkseagreen:"#8fbc8f",darkslateblue:"#483d8b",darkslategray:"#2f4f4f",darkslategrey:"#2f4f4f",darkturquoise:"#00ced1",darkviolet:"#9400d3",deeppink:"#ff1493",deepskyblue:"#00bfff",dimgray:"#696969",dimgrey:"#696969",dodgerblue:"#1e90ff",firebrick:"#b22222",floralwhite:"#fffaf0",forestgreen:"#228b22",fuchsia:"#ff00ff",gainsboro:"#dcdcdc",ghostwhite:"#f8f8ff",goldenrod:"#daa520",gold:"#ffd700",gray:"#808080",green:"#008000",greenyellow:"#adff2f",grey:"#808080",honeydew:"#f0fff0",hotpink:"#ff69b4",indianred:"#cd5c5c",indigo:"#4b0082",ivory:"#fffff0",khaki:"#f0e68c",lavenderblush:"#fff0f5",lavender:"#e6e6fa",lawngreen:"#7cfc00",lemonchiffon:"#fffacd",lightblue:"#add8e6",lightcoral:"#f08080",lightcyan:"#e0ffff",lightgoldenrodyellow:"#fafad2",lightgray:"#d3d3d3",lightgreen:"#90ee90",lightgrey:"#d3d3d3",lightpink:"#ffb6c1",lightsalmon:"#ffa07a",lightseagreen:"#20b2aa",lightskyblue:"#87cefa",lightslategray:"#778899",lightslategrey:"#778899",lightsteelblue:"#b0c4de",lightyellow:"#ffffe0",lime:"#00ff00",limegreen:"#32cd32",linen:"#faf0e6",magenta:"#ff00ff",maroon:"#800000",mediumaquamarine:"#66cdaa",mediumblue:"#0000cd",mediumorchid:"#ba55d3",mediumpurple:"#9370db",mediumseagreen:"#3cb371",mediumslateblue:"#7b68ee",mediumspringgreen:"#00fa9a",mediumturquoise:"#48d1cc",mediumvioletred:"#c71585",midnightblue:"#191970",mintcream:"#f5fffa",mistyrose:"#ffe4e1",moccasin:"#ffe4b5",navajowhite:"#ffdead",navy:"#000080",oldlace:"#fdf5e6",olive:"#808000",olivedrab:"#6b8e23",orange:"#ffa500",orangered:"#ff4500",orchid:"#da70d6",palegoldenrod:"#eee8aa",palegreen:"#98fb98",paleturquoise:"#afeeee",palevioletred:"#db7093",papayawhip:"#ffefd5",peachpuff:"#ffdab9",peru:"#cd853f",pink:"#ffc0cb",plum:"#dda0dd",powderblue:"#b0e0e6",purple:"#800080",rebeccapurple:"#663399",red:"#ff0000",rosybrown:"#bc8f8f",royalblue:"#4169e1",saddlebrown:"#8b4513",salmon:"#fa8072",sandybrown:"#f4a460",seagreen:"#2e8b57",seashell:"#fff5ee",sienna:"#a0522d",silver:"#c0c0c0",skyblue:"#87ceeb",slateblue:"#6a5acd",slategray:"#708090",slategrey:"#708090",snow:"#fffafa",springgreen:"#00ff7f",steelblue:"#4682b4",tan:"#d2b48c",teal:"#008080",thistle:"#d8bfd8",tomato:"#ff6347",turquoise:"#40e0d0",violet:"#ee82ee",wheat:"#f5deb3",white:"#ffffff",whitesmoke:"#f5f5f5",yellow:"#ffff00",yellowgreen:"#9acd32"};function Fh(e){let t={r:0,g:0,b:0},i=1,r=null,n=null,s=null,o=!1,a=!1;return typeof e=="string"&&(e=Ph(e)),typeof e=="object"&&(G(e.r)&&G(e.g)&&G(e.b)?(t=Ch(e.r,e.g,e.b),o=!0,a=String(e.r).substr(-1)==="%"?"prgb":"rgb"):G(e.h)&&G(e.s)&&G(e.v)?(r=hi(e.s),n=hi(e.v),t=Ah(e.h,r,n),o=!0,a="hsv"):G(e.h)&&G(e.s)&&G(e.l)?(r=hi(e.s),s=hi(e.l),t=kh(e.h,r,s),o=!0,a="hsl"):G(e.c)&&G(e.m)&&G(e.y)&&G(e.k)&&(t=Th(e.c,e.m,e.y,e.k),o=!0,a="cmyk"),Object.prototype.hasOwnProperty.call(e,"a")&&(i=e.a)),i=fa(i),{ok:o,format:e.format||a,r:Math.min(255,Math.max(t.r,0)),g:Math.min(255,Math.max(t.g,0)),b:Math.min(255,Math.max(t.b,0)),a:i}}const Lh="[-\\+]?\\d+%?",Mh="[-\\+]?\\d*\\.\\d+%?",zt="(?:"+Mh+")|(?:"+Lh+")",wr="[\\s|\\(]+("+zt+")[,|\\s]+("+zt+")[,|\\s]+("+zt+")\\s*\\)?",pi="[\\s|\\(]+("+zt+")[,|\\s]+("+zt+")[,|\\s]+("+zt+")[,|\\s]+("+zt+")\\s*\\)?",st={CSS_UNIT:new RegExp(zt),rgb:new RegExp("rgb"+wr),rgba:new RegExp("rgba"+pi),hsl:new RegExp("hsl"+wr),hsla:new RegExp("hsla"+pi),hsv:new RegExp("hsv"+wr),hsva:new RegExp("hsva"+pi),cmyk:new RegExp("cmyk"+pi),hex3:/^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/,hex6:/^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/,hex4:/^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/,hex8:/^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/};function Ph(e){if(e=e.trim().toLowerCase(),e.length===0)return!1;let t=!1;if(Nr[e])e=Nr[e],t=!0;else if(e==="transparent")return{r:0,g:0,b:0,a:0,format:"name"};let i=st.rgb.exec(e);return i?{r:i[1],g:i[2],b:i[3]}:(i=st.rgba.exec(e),i?{r:i[1],g:i[2],b:i[3],a:i[4]}:(i=st.hsl.exec(e),i?{h:i[1],s:i[2],l:i[3]}:(i=st.hsla.exec(e),i?{h:i[1],s:i[2],l:i[3],a:i[4]}:(i=st.hsv.exec(e),i?{h:i[1],s:i[2],v:i[3]}:(i=st.hsva.exec(e),i?{h:i[1],s:i[2],v:i[3],a:i[4]}:(i=st.cmyk.exec(e),i?{c:i[1],m:i[2],y:i[3],k:i[4]}:(i=st.hex8.exec(e),i?{r:Y(i[1]),g:Y(i[2]),b:Y(i[3]),a:Ns(i[4]),format:t?"name":"hex8"}:(i=st.hex6.exec(e),i?{r:Y(i[1]),g:Y(i[2]),b:Y(i[3]),format:t?"name":"hex"}:(i=st.hex4.exec(e),i?{r:Y(i[1]+i[1]),g:Y(i[2]+i[2]),b:Y(i[3]+i[3]),a:Ns(i[4]+i[4]),format:t?"name":"hex8"}:(i=st.hex3.exec(e),i?{r:Y(i[1]+i[1]),g:Y(i[2]+i[2]),b:Y(i[3]+i[3]),format:t?"name":"hex"}:!1))))))))))}function G(e){return typeof e=="number"?!Number.isNaN(e):st.CSS_UNIT.test(e)}class z{constructor(t="",i={}){if(t instanceof z)return t;typeof t=="number"&&(t=Oh(t)),this.originalInput=t;const r=Fh(t);this.originalInput=t,this.r=r.r,this.g=r.g,this.b=r.b,this.a=r.a,this.roundA=Math.round(100*this.a)/100,this.format=i.format??r.format,this.gradientType=i.gradientType,this.r<1&&(this.r=Math.round(this.r)),this.g<1&&(this.g=Math.round(this.g)),this.b<1&&(this.b=Math.round(this.b)),this.isValid=r.ok}isDark(){return this.getBrightness()<128}isLight(){return!this.isDark()}getBrightness(){const t=this.toRgb();return(t.r*299+t.g*587+t.b*114)/1e3}getLuminance(){const t=this.toRgb();let i,r,n;const s=t.r/255,o=t.g/255,a=t.b/255;return s<=.03928?i=s/12.92:i=Math.pow((s+.055)/1.055,2.4),o<=.03928?r=o/12.92:r=Math.pow((o+.055)/1.055,2.4),a<=.03928?n=a/12.92:n=Math.pow((a+.055)/1.055,2.4),.2126*i+.7152*r+.0722*n}getAlpha(){return this.a}setAlpha(t){return this.a=fa(t),this.roundA=Math.round(100*this.a)/100,this}isMonochrome(){const{s:t}=this.toHsl();return t===0}toHsv(){const t=Is(this.r,this.g,this.b);return{h:t.h*360,s:t.s,v:t.v,a:this.a}}toHsvString(){const t=Is(this.r,this.g,this.b),i=Math.round(t.h*360),r=Math.round(t.s*100),n=Math.round(t.v*100);return this.a===1?`hsv(${i}, ${r}%, ${n}%)`:`hsva(${i}, ${r}%, ${n}%, ${this.roundA})`}toHsl(){const t=zs(this.r,this.g,this.b);return{h:t.h*360,s:t.s,l:t.l,a:this.a}}toHslString(){const t=zs(this.r,this.g,this.b),i=Math.round(t.h*360),r=Math.round(t.s*100),n=Math.round(t.l*100);return this.a===1?`hsl(${i}, ${r}%, ${n}%)`:`hsla(${i}, ${r}%, ${n}%, ${this.roundA})`}toHex(t=!1){return Bs(this.r,this.g,this.b,t)}toHexString(t=!1){return"#"+this.toHex(t)}toHex8(t=!1){return $h(this.r,this.g,this.b,this.a,t)}toHex8String(t=!1){return"#"+this.toHex8(t)}toHexShortString(t=!1){return this.a===1?this.toHexString(t):this.toHex8String(t)}toRgb(){return{r:Math.round(this.r),g:Math.round(this.g),b:Math.round(this.b),a:this.a}}toRgbString(){const t=Math.round(this.r),i=Math.round(this.g),r=Math.round(this.b);return this.a===1?`rgb(${t}, ${i}, ${r})`:`rgba(${t}, ${i}, ${r}, ${this.roundA})`}toPercentageRgb(){const t=i=>`${Math.round(U(i,255)*100)}%`;return{r:t(this.r),g:t(this.g),b:t(this.b),a:this.a}}toPercentageRgbString(){const t=i=>Math.round(U(i,255)*100);return this.a===1?`rgb(${t(this.r)}%, ${t(this.g)}%, ${t(this.b)}%)`:`rgba(${t(this.r)}%, ${t(this.g)}%, ${t(this.b)}%, ${this.roundA})`}toCmyk(){return{...Ds(this.r,this.g,this.b)}}toCmykString(){const{c:t,m:i,y:r,k:n}=Ds(this.r,this.g,this.b);return`cmyk(${t}, ${i}, ${r}, ${n})`}toName(){if(this.a===0)return"transparent";if(this.a<1)return!1;const t="#"+Bs(this.r,this.g,this.b,!1);for(const[i,r]of Object.entries(Nr))if(t===r)return i;return!1}toString(t){const i=!!t;t=t??this.format;let r=!1;const n=this.a<1&&this.a>=0;return!i&&n&&(t.startsWith("hex")||t==="name")?t==="name"&&this.a===0?this.toName():this.toRgbString():(t==="rgb"&&(r=this.toRgbString()),t==="prgb"&&(r=this.toPercentageRgbString()),(t==="hex"||t==="hex6")&&(r=this.toHexString()),t==="hex3"&&(r=this.toHexString(!0)),t==="hex4"&&(r=this.toHex8String(!0)),t==="hex8"&&(r=this.toHex8String()),t==="name"&&(r=this.toName()),t==="hsl"&&(r=this.toHslString()),t==="hsv"&&(r=this.toHsvString()),t==="cmyk"&&(r=this.toCmykString()),r||this.toHexString())}toNumber(){return(Math.round(this.r)<<16)+(Math.round(this.g)<<8)+Math.round(this.b)}clone(){return new z(this.toString())}lighten(t=10){const i=this.toHsl();return i.l+=t/100,i.l=di(i.l),new z(i)}brighten(t=10){const i=this.toRgb();return i.r=Math.max(0,Math.min(255,i.r-Math.round(255*-(t/100)))),i.g=Math.max(0,Math.min(255,i.g-Math.round(255*-(t/100)))),i.b=Math.max(0,Math.min(255,i.b-Math.round(255*-(t/100)))),new z(i)}darken(t=10){const i=this.toHsl();return i.l-=t/100,i.l=di(i.l),new z(i)}tint(t=10){return this.mix("white",t)}shade(t=10){return this.mix("black",t)}desaturate(t=10){const i=this.toHsl();return i.s-=t/100,i.s=di(i.s),new z(i)}saturate(t=10){const i=this.toHsl();return i.s+=t/100,i.s=di(i.s),new z(i)}greyscale(){return this.desaturate(100)}spin(t){const i=this.toHsl(),r=(i.h+t)%360;return i.h=r<0?360+r:r,new z(i)}mix(t,i=50){const r=this.toRgb(),n=new z(t).toRgb(),s=i/100,o={r:(n.r-r.r)*s+r.r,g:(n.g-r.g)*s+r.g,b:(n.b-r.b)*s+r.b,a:(n.a-r.a)*s+r.a};return new z(o)}analogous(t=6,i=30){const r=this.toHsl(),n=360/i,s=[this];for(r.h=(r.h-(n*t>>1)+720)%360;--t;)r.h=(r.h+n)%360,s.push(new z(r));return s}complement(){const t=this.toHsl();return t.h=(t.h+180)%360,new z(t)}monochromatic(t=6){const i=this.toHsv(),{h:r}=i,{s:n}=i;let{v:s}=i;const o=[],a=1/t;for(;t--;)o.push(new z({h:r,s:n,v:s})),s=(s+a)%1;return o}splitcomplement(){const t=this.toHsl(),{h:i}=t;return[this,new z({h:(i+72)%360,s:t.s,l:t.l}),new z({h:(i+216)%360,s:t.s,l:t.l})]}onBackground(t){const i=this.toRgb(),r=new z(t).toRgb(),n=i.a+r.a*(1-i.a);return new z({r:(i.r*i.a+r.r*r.a*(1-i.a))/n,g:(i.g*i.a+r.g*r.a*(1-i.a))/n,b:(i.b*i.a+r.b*r.a*(1-i.a))/n,a:n})}triad(){return this.polyad(3)}tetrad(){return this.polyad(4)}polyad(t){const i=this.toHsl(),{h:r}=i,n=[this],s=360/t;for(let o=1;o<t;o++)n.push(new z({h:(r+o*s)%360,s:i.s,l:i.l}));return n}equals(t){const i=new z(t);return this.format==="cmyk"||i.format==="cmyk"?this.toCmykString()===i.toCmykString():this.toRgbString()===i.toRgbString()}}var Us="EyeDropper"in window,$=class extends rt{constructor(){super(),this.formControlController=new pn(this),this.isSafeValue=!1,this.localize=new _e(this),this.hasFocus=!1,this.isDraggingGridHandle=!1,this.isEmpty=!1,this.inputValue="",this.hue=0,this.saturation=100,this.brightness=100,this.alpha=100,this.value="",this.defaultValue="",this.label="",this.format="hex",this.inline=!1,this.size="medium",this.noFormatToggle=!1,this.name="",this.disabled=!1,this.hoist=!1,this.opacity=!1,this.uppercase=!1,this.swatches="",this.form="",this.required=!1,this.handleFocusIn=()=>{this.hasFocus=!0,this.emit("sl-focus")},this.handleFocusOut=()=>{this.hasFocus=!1,this.emit("sl-blur")},this.addEventListener("focusin",this.handleFocusIn),this.addEventListener("focusout",this.handleFocusOut)}get validity(){return this.input.validity}get validationMessage(){return this.input.validationMessage}firstUpdated(){this.input.updateComplete.then(()=>{this.formControlController.updateValidity()})}handleCopy(){this.input.select(),document.execCommand("copy"),this.previewButton.focus(),this.previewButton.classList.add("color-picker__preview-color--copied"),this.previewButton.addEventListener("animationend",()=>{this.previewButton.classList.remove("color-picker__preview-color--copied")})}handleFormatToggle(){const e=["hex","rgb","hsl","hsv"],t=(e.indexOf(this.format)+1)%e.length;this.format=e[t],this.setColor(this.value),this.emit("sl-change"),this.emit("sl-input")}handleAlphaDrag(e){const t=this.shadowRoot.querySelector(".color-picker__slider.color-picker__alpha"),i=t.querySelector(".color-picker__slider-handle"),{width:r}=t.getBoundingClientRect();let n=this.value,s=this.value;i.focus(),e.preventDefault(),mr(t,{onMove:o=>{this.alpha=nt(o/r*100,0,100),this.syncValues(),this.value!==s&&(s=this.value,this.emit("sl-input"))},onStop:()=>{this.value!==n&&(n=this.value,this.emit("sl-change"))},initialEvent:e})}handleHueDrag(e){const t=this.shadowRoot.querySelector(".color-picker__slider.color-picker__hue"),i=t.querySelector(".color-picker__slider-handle"),{width:r}=t.getBoundingClientRect();let n=this.value,s=this.value;i.focus(),e.preventDefault(),mr(t,{onMove:o=>{this.hue=nt(o/r*360,0,360),this.syncValues(),this.value!==s&&(s=this.value,this.emit("sl-input"))},onStop:()=>{this.value!==n&&(n=this.value,this.emit("sl-change"))},initialEvent:e})}handleGridDrag(e){const t=this.shadowRoot.querySelector(".color-picker__grid"),i=t.querySelector(".color-picker__grid-handle"),{width:r,height:n}=t.getBoundingClientRect();let s=this.value,o=this.value;i.focus(),e.preventDefault(),this.isDraggingGridHandle=!0,mr(t,{onMove:(a,l)=>{this.saturation=nt(a/r*100,0,100),this.brightness=nt(100-l/n*100,0,100),this.syncValues(),this.value!==o&&(o=this.value,this.emit("sl-input"))},onStop:()=>{this.isDraggingGridHandle=!1,this.value!==s&&(s=this.value,this.emit("sl-change"))},initialEvent:e})}handleAlphaKeyDown(e){const t=e.shiftKey?10:1,i=this.value;e.key==="ArrowLeft"&&(e.preventDefault(),this.alpha=nt(this.alpha-t,0,100),this.syncValues()),e.key==="ArrowRight"&&(e.preventDefault(),this.alpha=nt(this.alpha+t,0,100),this.syncValues()),e.key==="Home"&&(e.preventDefault(),this.alpha=0,this.syncValues()),e.key==="End"&&(e.preventDefault(),this.alpha=100,this.syncValues()),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleHueKeyDown(e){const t=e.shiftKey?10:1,i=this.value;e.key==="ArrowLeft"&&(e.preventDefault(),this.hue=nt(this.hue-t,0,360),this.syncValues()),e.key==="ArrowRight"&&(e.preventDefault(),this.hue=nt(this.hue+t,0,360),this.syncValues()),e.key==="Home"&&(e.preventDefault(),this.hue=0,this.syncValues()),e.key==="End"&&(e.preventDefault(),this.hue=360,this.syncValues()),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleGridKeyDown(e){const t=e.shiftKey?10:1,i=this.value;e.key==="ArrowLeft"&&(e.preventDefault(),this.saturation=nt(this.saturation-t,0,100),this.syncValues()),e.key==="ArrowRight"&&(e.preventDefault(),this.saturation=nt(this.saturation+t,0,100),this.syncValues()),e.key==="ArrowUp"&&(e.preventDefault(),this.brightness=nt(this.brightness+t,0,100),this.syncValues()),e.key==="ArrowDown"&&(e.preventDefault(),this.brightness=nt(this.brightness-t,0,100),this.syncValues()),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleInputChange(e){const t=e.target,i=this.value;e.stopPropagation(),this.input.value?(this.setColor(t.value),t.value=this.value):this.value="",this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleInputInput(e){this.formControlController.updateValidity(),e.stopPropagation()}handleInputKeyDown(e){if(e.key==="Enter"){const t=this.value;this.input.value?(this.setColor(this.input.value),this.input.value=this.value,this.value!==t&&(this.emit("sl-change"),this.emit("sl-input")),setTimeout(()=>this.input.select())):this.hue=0}}handleInputInvalid(e){this.formControlController.setValidity(!1),this.formControlController.emitInvalidEvent(e)}handleTouchMove(e){e.preventDefault()}parseColor(e){const t=new z(e);if(!t.isValid)return null;const i=t.toHsl(),r={h:i.h,s:i.s*100,l:i.l*100,a:i.a},n=t.toRgb(),s=t.toHexString(),o=t.toHex8String(),a=t.toHsv(),l={h:a.h,s:a.s*100,v:a.v*100,a:a.a};return{hsl:{h:r.h,s:r.s,l:r.l,string:this.setLetterCase(`hsl(${Math.round(r.h)}, ${Math.round(r.s)}%, ${Math.round(r.l)}%)`)},hsla:{h:r.h,s:r.s,l:r.l,a:r.a,string:this.setLetterCase(`hsla(${Math.round(r.h)}, ${Math.round(r.s)}%, ${Math.round(r.l)}%, ${r.a.toFixed(2).toString()})`)},hsv:{h:l.h,s:l.s,v:l.v,string:this.setLetterCase(`hsv(${Math.round(l.h)}, ${Math.round(l.s)}%, ${Math.round(l.v)}%)`)},hsva:{h:l.h,s:l.s,v:l.v,a:l.a,string:this.setLetterCase(`hsva(${Math.round(l.h)}, ${Math.round(l.s)}%, ${Math.round(l.v)}%, ${l.a.toFixed(2).toString()})`)},rgb:{r:n.r,g:n.g,b:n.b,string:this.setLetterCase(`rgb(${Math.round(n.r)}, ${Math.round(n.g)}, ${Math.round(n.b)})`)},rgba:{r:n.r,g:n.g,b:n.b,a:n.a,string:this.setLetterCase(`rgba(${Math.round(n.r)}, ${Math.round(n.g)}, ${Math.round(n.b)}, ${n.a.toFixed(2).toString()})`)},hex:this.setLetterCase(s),hexa:this.setLetterCase(o)}}setColor(e){const t=this.parseColor(e);return t===null?!1:(this.hue=t.hsva.h,this.saturation=t.hsva.s,this.brightness=t.hsva.v,this.alpha=this.opacity?t.hsva.a*100:100,this.syncValues(),!0)}setLetterCase(e){return typeof e!="string"?"":this.uppercase?e.toUpperCase():e.toLowerCase()}async syncValues(){const e=this.parseColor(`hsva(${this.hue}, ${this.saturation}%, ${this.brightness}%, ${this.alpha/100})`);e!==null&&(this.format==="hsl"?this.inputValue=this.opacity?e.hsla.string:e.hsl.string:this.format==="rgb"?this.inputValue=this.opacity?e.rgba.string:e.rgb.string:this.format==="hsv"?this.inputValue=this.opacity?e.hsva.string:e.hsv.string:this.inputValue=this.opacity?e.hexa:e.hex,this.isSafeValue=!0,this.value=this.inputValue,await this.updateComplete,this.isSafeValue=!1)}handleAfterHide(){this.previewButton.classList.remove("color-picker__preview-color--copied")}handleEyeDropper(){if(!Us)return;new EyeDropper().open().then(t=>{const i=this.value;this.setColor(t.sRGBHex),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}).catch(()=>{})}selectSwatch(e){const t=this.value;this.disabled||(this.setColor(e),this.value!==t&&(this.emit("sl-change"),this.emit("sl-input")))}getHexString(e,t,i,r=100){const n=new z(`hsva(${e}, ${t}%, ${i}%, ${r/100})`);return n.isValid?n.toHex8String():""}stopNestedEventPropagation(e){e.stopImmediatePropagation()}handleFormatChange(){this.syncValues()}handleOpacityChange(){this.alpha=100}handleValueChange(e,t){if(this.isEmpty=!t,t||(this.hue=0,this.saturation=0,this.brightness=100,this.alpha=100),!this.isSafeValue){const i=this.parseColor(t);i!==null?(this.inputValue=this.value,this.hue=i.hsva.h,this.saturation=i.hsva.s,this.brightness=i.hsva.v,this.alpha=i.hsva.a*100,this.syncValues()):this.inputValue=e??""}}focus(e){this.inline?this.base.focus(e):this.trigger.focus(e)}blur(){var e;const t=this.inline?this.base:this.trigger;this.hasFocus&&(t.focus({preventScroll:!0}),t.blur()),(e=this.dropdown)!=null&&e.open&&this.dropdown.hide()}getFormattedValue(e="hex"){const t=this.parseColor(`hsva(${this.hue}, ${this.saturation}%, ${this.brightness}%, ${this.alpha/100})`);if(t===null)return"";switch(e){case"hex":return t.hex;case"hexa":return t.hexa;case"rgb":return t.rgb.string;case"rgba":return t.rgba.string;case"hsl":return t.hsl.string;case"hsla":return t.hsla.string;case"hsv":return t.hsv.string;case"hsva":return t.hsva.string;default:return""}}checkValidity(){return this.input.checkValidity()}getForm(){return this.formControlController.getForm()}reportValidity(){return!this.inline&&!this.validity.valid?(this.dropdown.show(),this.addEventListener("sl-after-show",()=>this.input.reportValidity(),{once:!0}),this.disabled||this.formControlController.emitInvalidEvent(),!1):this.input.reportValidity()}setCustomValidity(e){this.input.setCustomValidity(e),this.formControlController.updateValidity()}render(){const e=this.saturation,t=100-this.brightness,i=Array.isArray(this.swatches)?this.swatches:this.swatches.split(";").filter(n=>n.trim()!==""),r=I`
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
          style=${Ft({backgroundColor:this.getHexString(this.hue,100,100)})}
          @pointerdown=${this.handleGridDrag}
          @touchmove=${this.handleTouchMove}
        >
          <span
            part="grid-handle"
            class=${kt({"color-picker__grid-handle":!0,"color-picker__grid-handle--dragging":this.isDraggingGridHandle})}
            style=${Ft({top:`${t}%`,left:`${e}%`,backgroundColor:this.getHexString(this.hue,this.saturation,this.brightness,this.alpha)})}
            role="application"
            aria-label="HSV"
            tabindex=${O(this.disabled?void 0:"0")}
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
                style=${Ft({left:`${this.hue===0?0:100/(360/this.hue)}%`})}
                role="slider"
                aria-label="hue"
                aria-orientation="horizontal"
                aria-valuemin="0"
                aria-valuemax="360"
                aria-valuenow=${`${Math.round(this.hue)}`}
                tabindex=${O(this.disabled?void 0:"0")}
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
                      style=${Ft({backgroundImage:`linear-gradient(
                          to right,
                          ${this.getHexString(this.hue,this.saturation,this.brightness,0)} 0%,
                          ${this.getHexString(this.hue,this.saturation,this.brightness,100)} 100%
                        )`})}
                    ></div>
                    <span
                      part="slider-handle opacity-slider-handle"
                      class="color-picker__slider-handle"
                      style=${Ft({left:`${this.alpha}%`})}
                      role="slider"
                      aria-label="alpha"
                      aria-orientation="horizontal"
                      aria-valuemin="0"
                      aria-valuemax="100"
                      aria-valuenow=${Math.round(this.alpha)}
                      tabindex=${O(this.disabled?void 0:"0")}
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
            style=${Ft({"--preview-color":this.getHexString(this.hue,this.saturation,this.brightness,this.alpha)})}
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
                ${i.map(n=>{const s=this.parseColor(n);return s?I`
                    <div
                      part="swatch"
                      class="color-picker__swatch color-picker__transparent-bg"
                      tabindex=${O(this.disabled?void 0:"0")}
                      role="button"
                      aria-label=${n}
                      @click=${()=>this.selectSwatch(n)}
                      @keydown=${o=>!this.disabled&&o.key==="Enter"&&this.setColor(s.hexa)}
                    >
                      <div
                        class="color-picker__swatch-color"
                        style=${Ft({backgroundColor:s.hexa})}
                      ></div>
                    </div>
                  `:(console.error(`Unable to parse swatch color: "${n}"`,this),"")})}
              </div>
            `:""}
      </div>
    `;return this.inline?r:I`
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
          style=${Ft({color:this.getHexString(this.hue,this.saturation,this.brightness,this.alpha)})}
          type="button"
        >
          <sl-visually-hidden>
            <slot name="label">${this.label}</slot>
          </sl-visually-hidden>
        </button>
        ${r}
      </sl-dropdown>
    `}};$.styles=[$t,gh];$.dependencies={"sl-button-group":ni,"sl-button":T,"sl-dropdown":V,"sl-icon":mt,"sl-input":A,"sl-visually-hidden":Vo};g([it('[part~="base"]')],$.prototype,"base",2);g([it('[part~="input"]')],$.prototype,"input",2);g([it(".color-dropdown")],$.prototype,"dropdown",2);g([it('[part~="preview"]')],$.prototype,"previewButton",2);g([it('[part~="trigger"]')],$.prototype,"trigger",2);g([et()],$.prototype,"hasFocus",2);g([et()],$.prototype,"isDraggingGridHandle",2);g([et()],$.prototype,"isEmpty",2);g([et()],$.prototype,"inputValue",2);g([et()],$.prototype,"hue",2);g([et()],$.prototype,"saturation",2);g([et()],$.prototype,"brightness",2);g([et()],$.prototype,"alpha",2);g([y()],$.prototype,"value",2);g([jo()],$.prototype,"defaultValue",2);g([y()],$.prototype,"label",2);g([y()],$.prototype,"format",2);g([y({type:Boolean,reflect:!0})],$.prototype,"inline",2);g([y({reflect:!0})],$.prototype,"size",2);g([y({attribute:"no-format-toggle",type:Boolean})],$.prototype,"noFormatToggle",2);g([y()],$.prototype,"name",2);g([y({type:Boolean,reflect:!0})],$.prototype,"disabled",2);g([y({type:Boolean})],$.prototype,"hoist",2);g([y({type:Boolean})],$.prototype,"opacity",2);g([y({type:Boolean})],$.prototype,"uppercase",2);g([y()],$.prototype,"swatches",2);g([y({reflect:!0})],$.prototype,"form",2);g([y({type:Boolean,reflect:!0})],$.prototype,"required",2);g([Uc({passive:!1})],$.prototype,"handleTouchMove",1);g([xt("format",{waitUntilFirstUpdate:!0})],$.prototype,"handleFormatChange",1);g([xt("opacity",{waitUntilFirstUpdate:!0})],$.prototype,"handleOpacityChange",1);g([xt("value")],$.prototype,"handleValueChange",1);$.define("sl-color-picker");var Ur=!1,Hr=!1,ee=[],qr=-1;function zh(e){Ih(e)}function Ih(e){ee.includes(e)||ee.push(e),Dh()}function Bh(e){let t=ee.indexOf(e);t!==-1&&t>qr&&ee.splice(t,1)}function Dh(){!Hr&&!Ur&&(Ur=!0,queueMicrotask(Nh))}function Nh(){Ur=!1,Hr=!0;for(let e=0;e<ee.length;e++)ee[e](),qr=e;ee.length=0,qr=-1,Hr=!1}var Ce,ce,ke,ma,Vr=!0;function Uh(e){Vr=!1,e(),Vr=!0}function Hh(e){Ce=e.reactive,ke=e.release,ce=t=>e.effect(t,{scheduler:i=>{Vr?zh(i):i()}}),ma=e.raw}function Hs(e){ce=e}function qh(e){let t=()=>{};return[r=>{let n=ce(r);return e._x_effects||(e._x_effects=new Set,e._x_runEffects=()=>{e._x_effects.forEach(s=>s())}),e._x_effects.add(n),t=()=>{n!==void 0&&(e._x_effects.delete(n),ke(n))},n},()=>{t()}]}function ga(e,t){let i=!0,r,n=ce(()=>{let s=e();JSON.stringify(s),i?r=s:queueMicrotask(()=>{t(s,r),r=s}),i=!1});return()=>ke(n)}var ba=[],va=[],ya=[];function Vh(e){ya.push(e)}function Sn(e,t){typeof t=="function"?(e._x_cleanups||(e._x_cleanups=[]),e._x_cleanups.push(t)):(t=e,va.push(t))}function wa(e){ba.push(e)}function _a(e,t,i){e._x_attributeCleanups||(e._x_attributeCleanups={}),e._x_attributeCleanups[t]||(e._x_attributeCleanups[t]=[]),e._x_attributeCleanups[t].push(i)}function xa(e,t){e._x_attributeCleanups&&Object.entries(e._x_attributeCleanups).forEach(([i,r])=>{(t===void 0||t.includes(i))&&(r.forEach(n=>n()),delete e._x_attributeCleanups[i])})}function jh(e){var t,i;for((t=e._x_effects)==null||t.forEach(Bh);(i=e._x_cleanups)!=null&&i.length;)e._x_cleanups.pop()()}var Cn=new MutationObserver(Tn),kn=!1;function An(){Cn.observe(document,{subtree:!0,childList:!0,attributes:!0,attributeOldValue:!0}),kn=!0}function Ea(){Wh(),Cn.disconnect(),kn=!1}var Ie=[];function Wh(){let e=Cn.takeRecords();Ie.push(()=>e.length>0&&Tn(e));let t=Ie.length;queueMicrotask(()=>{if(Ie.length===t)for(;Ie.length>0;)Ie.shift()()})}function P(e){if(!kn)return e();Ea();let t=e();return An(),t}var $n=!1,Mi=[];function Kh(){$n=!0}function Qh(){$n=!1,Tn(Mi),Mi=[]}function Tn(e){if($n){Mi=Mi.concat(e);return}let t=[],i=new Set,r=new Map,n=new Map;for(let s=0;s<e.length;s++)if(!e[s].target._x_ignoreMutationObserver&&(e[s].type==="childList"&&(e[s].removedNodes.forEach(o=>{o.nodeType===1&&o._x_marker&&i.add(o)}),e[s].addedNodes.forEach(o=>{if(o.nodeType===1){if(i.has(o)){i.delete(o);return}o._x_marker||t.push(o)}})),e[s].type==="attributes")){let o=e[s].target,a=e[s].attributeName,l=e[s].oldValue,u=()=>{r.has(o)||r.set(o,[]),r.get(o).push({name:a,value:o.getAttribute(a)})},c=()=>{n.has(o)||n.set(o,[]),n.get(o).push(a)};o.hasAttribute(a)&&l===null?u():o.hasAttribute(a)?(c(),u()):c()}n.forEach((s,o)=>{xa(o,s)}),r.forEach((s,o)=>{ba.forEach(a=>a(o,s))});for(let s of i)t.some(o=>o.contains(s))||va.forEach(o=>o(s));for(let s of t)s.isConnected&&ya.forEach(o=>o(s));t=null,i=null,r=null,n=null}function Sa(e){return oi(be(e))}function si(e,t,i){return e._x_dataStack=[t,...be(i||e)],()=>{e._x_dataStack=e._x_dataStack.filter(r=>r!==t)}}function be(e){return e._x_dataStack?e._x_dataStack:typeof ShadowRoot=="function"&&e instanceof ShadowRoot?be(e.host):e.parentNode?be(e.parentNode):[]}function oi(e){return new Proxy({objects:e},Jh)}var Jh={ownKeys({objects:e}){return Array.from(new Set(e.flatMap(t=>Object.keys(t))))},has({objects:e},t){return t==Symbol.unscopables?!1:e.some(i=>Object.prototype.hasOwnProperty.call(i,t)||Reflect.has(i,t))},get({objects:e},t,i){return t=="toJSON"?Xh:Reflect.get(e.find(r=>Reflect.has(r,t))||{},t,i)},set({objects:e},t,i,r){const n=e.find(o=>Object.prototype.hasOwnProperty.call(o,t))||e[e.length-1],s=Object.getOwnPropertyDescriptor(n,t);return s!=null&&s.set&&(s!=null&&s.get)?s.set.call(r,i)||!0:Reflect.set(n,t,i)}};function Xh(){return Reflect.ownKeys(this).reduce((t,i)=>(t[i]=Reflect.get(this,i),t),{})}function Ca(e){let t=r=>typeof r=="object"&&!Array.isArray(r)&&r!==null,i=(r,n="")=>{Object.entries(Object.getOwnPropertyDescriptors(r)).forEach(([s,{value:o,enumerable:a}])=>{if(a===!1||o===void 0||typeof o=="object"&&o!==null&&o.__v_skip)return;let l=n===""?s:`${n}.${s}`;typeof o=="object"&&o!==null&&o._x_interceptor?r[s]=o.initialize(e,l,s):t(o)&&o!==r&&!(o instanceof Element)&&i(o,l)})};return i(e)}function ka(e,t=()=>{}){let i={initialValue:void 0,_x_interceptor:!0,initialize(r,n,s){return e(this.initialValue,()=>Gh(r,n),o=>jr(r,n,o),n,s)}};return t(i),r=>{if(typeof r=="object"&&r!==null&&r._x_interceptor){let n=i.initialize.bind(i);i.initialize=(s,o,a)=>{let l=r.initialize(s,o,a);return i.initialValue=l,n(s,o,a)}}else i.initialValue=r;return i}}function Gh(e,t){return t.split(".").reduce((i,r)=>i[r],e)}function jr(e,t,i){if(typeof t=="string"&&(t=t.split(".")),t.length===1)e[t[0]]=i;else{if(t.length===0)throw error;return e[t[0]]||(e[t[0]]={}),jr(e[t[0]],t.slice(1),i)}}var Aa={};function gt(e,t){Aa[e]=t}function Wr(e,t){let i=Yh(t);return Object.entries(Aa).forEach(([r,n])=>{Object.defineProperty(e,`$${r}`,{get(){return n(t,i)},enumerable:!1})}),e}function Yh(e){let[t,i]=La(e),r={interceptor:ka,...t};return Sn(e,i),r}function Zh(e,t,i,...r){try{return i(...r)}catch(n){Ge(n,e,t)}}function Ge(e,t,i=void 0){e=Object.assign(e??{message:"No error message given."},{el:t,expression:i}),console.warn(`Alpine Expression Error: ${e.message}

${i?'Expression: "'+i+`"

`:""}`,t),setTimeout(()=>{throw e},0)}var Ci=!0;function $a(e){let t=Ci;Ci=!1;let i=e();return Ci=t,i}function ie(e,t,i={}){let r;return K(e,t)(n=>r=n,i),r}function K(...e){return Ta(...e)}var Ta=Ra;function tp(e){Ta=e}function Ra(e,t){let i={};Wr(i,e);let r=[i,...be(e)],n=typeof t=="function"?ep(r,t):rp(r,t,e);return Zh.bind(null,e,t,n)}function ep(e,t){return(i=()=>{},{scope:r={},params:n=[]}={})=>{let s=t.apply(oi([r,...e]),n);Pi(i,s)}}var _r={};function ip(e,t){if(_r[e])return _r[e];let i=Object.getPrototypeOf(async function(){}).constructor,r=/^[\n\s]*if.*\(.*\)/.test(e.trim())||/^(let|const)\s/.test(e.trim())?`(async()=>{ ${e} })()`:e,s=(()=>{try{let o=new i(["__self","scope"],`with (scope) { __self.result = ${r} }; __self.finished = true; return __self.result;`);return Object.defineProperty(o,"name",{value:`[Alpine] ${e}`}),o}catch(o){return Ge(o,t,e),Promise.resolve()}})();return _r[e]=s,s}function rp(e,t,i){let r=ip(t,i);return(n=()=>{},{scope:s={},params:o=[]}={})=>{r.result=void 0,r.finished=!1;let a=oi([s,...e]);if(typeof r=="function"){let l=r(r,a).catch(u=>Ge(u,i,t));r.finished?(Pi(n,r.result,a,o,i),r.result=void 0):l.then(u=>{Pi(n,u,a,o,i)}).catch(u=>Ge(u,i,t)).finally(()=>r.result=void 0)}}}function Pi(e,t,i,r,n){if(Ci&&typeof t=="function"){let s=t.apply(i,r);s instanceof Promise?s.then(o=>Pi(e,o,i,r)).catch(o=>Ge(o,n,t)):e(s)}else typeof t=="object"&&t instanceof Promise?t.then(s=>e(s)):e(t)}var Rn="x-";function Ae(e=""){return Rn+e}function np(e){Rn=e}var zi={};function N(e,t){return zi[e]=t,{before(i){if(!zi[i]){console.warn(String.raw`Cannot find directive \`${i}\`. \`${e}\` will use the default order of execution`);return}const r=Yt.indexOf(i);Yt.splice(r>=0?r:Yt.indexOf("DEFAULT"),0,e)}}}function sp(e){return Object.keys(zi).includes(e)}function On(e,t,i){if(t=Array.from(t),e._x_virtualDirectives){let s=Object.entries(e._x_virtualDirectives).map(([a,l])=>({name:a,value:l})),o=Oa(s);s=s.map(a=>o.find(l=>l.name===a.name)?{name:`x-bind:${a.name}`,value:`"${a.value}"`}:a),t=t.concat(s)}let r={};return t.map(za((s,o)=>r[s]=o)).filter(Ba).map(lp(r,i)).sort(up).map(s=>ap(e,s))}function Oa(e){return Array.from(e).map(za()).filter(t=>!Ba(t))}var Kr=!1,Ne=new Map,Fa=Symbol();function op(e){Kr=!0;let t=Symbol();Fa=t,Ne.set(t,[]);let i=()=>{for(;Ne.get(t).length;)Ne.get(t).shift()();Ne.delete(t)},r=()=>{Kr=!1,i()};e(i),r()}function La(e){let t=[],i=a=>t.push(a),[r,n]=qh(e);return t.push(n),[{Alpine:ai,effect:r,cleanup:i,evaluateLater:K.bind(K,e),evaluate:ie.bind(ie,e)},()=>t.forEach(a=>a())]}function ap(e,t){let i=()=>{},r=zi[t.type]||i,[n,s]=La(e);_a(e,t.original,s);let o=()=>{e._x_ignore||e._x_ignoreSelf||(r.inline&&r.inline(e,t,n),r=r.bind(r,e,t,n),Kr?Ne.get(Fa).push(r):r())};return o.runCleanups=s,o}var Ma=(e,t)=>({name:i,value:r})=>(i.startsWith(e)&&(i=i.replace(e,t)),{name:i,value:r}),Pa=e=>e;function za(e=()=>{}){return({name:t,value:i})=>{let{name:r,value:n}=Ia.reduce((s,o)=>o(s),{name:t,value:i});return r!==t&&e(r,t),{name:r,value:n}}}var Ia=[];function Fn(e){Ia.push(e)}function Ba({name:e}){return Da().test(e)}var Da=()=>new RegExp(`^${Rn}([^:^.]+)\\b`);function lp(e,t){return({name:i,value:r})=>{let n=i.match(Da()),s=i.match(/:([a-zA-Z0-9\-_:]+)/),o=i.match(/\.[^.\]]+(?=[^\]]*$)/g)||[],a=t||e[i]||i;return{type:n?n[1]:null,value:s?s[1]:null,modifiers:o.map(l=>l.replace(".","")),expression:r,original:a}}}var Qr="DEFAULT",Yt=["ignore","ref","data","id","anchor","bind","init","for","model","modelable","transition","show","if",Qr,"teleport"];function up(e,t){let i=Yt.indexOf(e.type)===-1?Qr:e.type,r=Yt.indexOf(t.type)===-1?Qr:t.type;return Yt.indexOf(i)-Yt.indexOf(r)}function Ve(e,t,i={}){e.dispatchEvent(new CustomEvent(t,{detail:i,bubbles:!0,composed:!0,cancelable:!0}))}function le(e,t){if(typeof ShadowRoot=="function"&&e instanceof ShadowRoot){Array.from(e.children).forEach(n=>le(n,t));return}let i=!1;if(t(e,()=>i=!0),i)return;let r=e.firstElementChild;for(;r;)le(r,t),r=r.nextElementSibling}function at(e,...t){console.warn(`Alpine Warning: ${e}`,...t)}var qs=!1;function cp(){qs&&at("Alpine has already been initialized on this page. Calling Alpine.start() more than once can cause problems."),qs=!0,document.body||at("Unable to initialize. Trying to load Alpine before `<body>` is available. Did you forget to add `defer` in Alpine's `<script>` tag?"),Ve(document,"alpine:init"),Ve(document,"alpine:initializing"),An(),Vh(t=>At(t,le)),Sn(t=>Te(t)),wa((t,i)=>{On(t,i).forEach(r=>r())});let e=t=>!Gi(t.parentElement,!0);Array.from(document.querySelectorAll(Ha().join(","))).filter(e).forEach(t=>{At(t)}),Ve(document,"alpine:initialized"),setTimeout(()=>{fp()})}var Ln=[],Na=[];function Ua(){return Ln.map(e=>e())}function Ha(){return Ln.concat(Na).map(e=>e())}function qa(e){Ln.push(e)}function Va(e){Na.push(e)}function Gi(e,t=!1){return $e(e,i=>{if((t?Ha():Ua()).some(n=>i.matches(n)))return!0})}function $e(e,t){if(e){if(t(e))return e;if(e._x_teleportBack&&(e=e._x_teleportBack),!!e.parentElement)return $e(e.parentElement,t)}}function dp(e){return Ua().some(t=>e.matches(t))}var ja=[];function hp(e){ja.push(e)}var pp=1;function At(e,t=le,i=()=>{}){$e(e,r=>r._x_ignore)||op(()=>{t(e,(r,n)=>{r._x_marker||(i(r,n),ja.forEach(s=>s(r,n)),On(r,r.attributes).forEach(s=>s()),r._x_ignore||(r._x_marker=pp++),r._x_ignore&&n())})})}function Te(e,t=le){t(e,i=>{jh(i),xa(i),delete i._x_marker})}function fp(){[["ui","dialog",["[x-dialog], [x-popover]"]],["anchor","anchor",["[x-anchor]"]],["sort","sort",["[x-sort]"]]].forEach(([t,i,r])=>{sp(i)||r.some(n=>{if(document.querySelector(n))return at(`found "${n}", but missing ${t} plugin`),!0})})}var Jr=[],Mn=!1;function Pn(e=()=>{}){return queueMicrotask(()=>{Mn||setTimeout(()=>{Xr()})}),new Promise(t=>{Jr.push(()=>{e(),t()})})}function Xr(){for(Mn=!1;Jr.length;)Jr.shift()()}function mp(){Mn=!0}function zn(e,t){return Array.isArray(t)?Vs(e,t.join(" ")):typeof t=="object"&&t!==null?gp(e,t):typeof t=="function"?zn(e,t()):Vs(e,t)}function Vs(e,t){let i=n=>n.split(" ").filter(s=>!e.classList.contains(s)).filter(Boolean),r=n=>(e.classList.add(...n),()=>{e.classList.remove(...n)});return t=t===!0?t="":t||"",r(i(t))}function gp(e,t){let i=a=>a.split(" ").filter(Boolean),r=Object.entries(t).flatMap(([a,l])=>l?i(a):!1).filter(Boolean),n=Object.entries(t).flatMap(([a,l])=>l?!1:i(a)).filter(Boolean),s=[],o=[];return n.forEach(a=>{e.classList.contains(a)&&(e.classList.remove(a),o.push(a))}),r.forEach(a=>{e.classList.contains(a)||(e.classList.add(a),s.push(a))}),()=>{o.forEach(a=>e.classList.add(a)),s.forEach(a=>e.classList.remove(a))}}function Yi(e,t){return typeof t=="object"&&t!==null?bp(e,t):vp(e,t)}function bp(e,t){let i={};return Object.entries(t).forEach(([r,n])=>{i[r]=e.style[r],r.startsWith("--")||(r=yp(r)),e.style.setProperty(r,n)}),setTimeout(()=>{e.style.length===0&&e.removeAttribute("style")}),()=>{Yi(e,i)}}function vp(e,t){let i=e.getAttribute("style",t);return e.setAttribute("style",t),()=>{e.setAttribute("style",i||"")}}function yp(e){return e.replace(/([a-z])([A-Z])/g,"$1-$2").toLowerCase()}function Gr(e,t=()=>{}){let i=!1;return function(){i?t.apply(this,arguments):(i=!0,e.apply(this,arguments))}}N("transition",(e,{value:t,modifiers:i,expression:r},{evaluate:n})=>{typeof r=="function"&&(r=n(r)),r!==!1&&(!r||typeof r=="boolean"?_p(e,i,t):wp(e,r,t))});function wp(e,t,i){Wa(e,zn,""),{enter:n=>{e._x_transition.enter.during=n},"enter-start":n=>{e._x_transition.enter.start=n},"enter-end":n=>{e._x_transition.enter.end=n},leave:n=>{e._x_transition.leave.during=n},"leave-start":n=>{e._x_transition.leave.start=n},"leave-end":n=>{e._x_transition.leave.end=n}}[i](t)}function _p(e,t,i){Wa(e,Yi);let r=!t.includes("in")&&!t.includes("out")&&!i,n=r||t.includes("in")||["enter"].includes(i),s=r||t.includes("out")||["leave"].includes(i);t.includes("in")&&!r&&(t=t.filter((w,x)=>x<t.indexOf("out"))),t.includes("out")&&!r&&(t=t.filter((w,x)=>x>t.indexOf("out")));let o=!t.includes("opacity")&&!t.includes("scale"),a=o||t.includes("opacity"),l=o||t.includes("scale"),u=a?0:1,c=l?Be(t,"scale",95)/100:1,d=Be(t,"delay",0)/1e3,h=Be(t,"origin","center"),p="opacity, transform",f=Be(t,"duration",150)/1e3,v=Be(t,"duration",75)/1e3,m="cubic-bezier(0.4, 0.0, 0.2, 1)";n&&(e._x_transition.enter.during={transformOrigin:h,transitionDelay:`${d}s`,transitionProperty:p,transitionDuration:`${f}s`,transitionTimingFunction:m},e._x_transition.enter.start={opacity:u,transform:`scale(${c})`},e._x_transition.enter.end={opacity:1,transform:"scale(1)"}),s&&(e._x_transition.leave.during={transformOrigin:h,transitionDelay:`${d}s`,transitionProperty:p,transitionDuration:`${v}s`,transitionTimingFunction:m},e._x_transition.leave.start={opacity:1,transform:"scale(1)"},e._x_transition.leave.end={opacity:u,transform:`scale(${c})`})}function Wa(e,t,i={}){e._x_transition||(e._x_transition={enter:{during:i,start:i,end:i},leave:{during:i,start:i,end:i},in(r=()=>{},n=()=>{}){Yr(e,t,{during:this.enter.during,start:this.enter.start,end:this.enter.end},r,n)},out(r=()=>{},n=()=>{}){Yr(e,t,{during:this.leave.during,start:this.leave.start,end:this.leave.end},r,n)}})}window.Element.prototype._x_toggleAndCascadeWithTransitions=function(e,t,i,r){const n=document.visibilityState==="visible"?requestAnimationFrame:setTimeout;let s=()=>n(i);if(t){e._x_transition&&(e._x_transition.enter||e._x_transition.leave)?e._x_transition.enter&&(Object.entries(e._x_transition.enter.during).length||Object.entries(e._x_transition.enter.start).length||Object.entries(e._x_transition.enter.end).length)?e._x_transition.in(i):s():e._x_transition?e._x_transition.in(i):s();return}e._x_hidePromise=e._x_transition?new Promise((o,a)=>{e._x_transition.out(()=>{},()=>o(r)),e._x_transitioning&&e._x_transitioning.beforeCancel(()=>a({isFromCancelledTransition:!0}))}):Promise.resolve(r),queueMicrotask(()=>{let o=Ka(e);o?(o._x_hideChildren||(o._x_hideChildren=[]),o._x_hideChildren.push(e)):n(()=>{let a=l=>{let u=Promise.all([l._x_hidePromise,...(l._x_hideChildren||[]).map(a)]).then(([c])=>c==null?void 0:c());return delete l._x_hidePromise,delete l._x_hideChildren,u};a(e).catch(l=>{if(!l.isFromCancelledTransition)throw l})})})};function Ka(e){let t=e.parentNode;if(t)return t._x_hidePromise?t:Ka(t)}function Yr(e,t,{during:i,start:r,end:n}={},s=()=>{},o=()=>{}){if(e._x_transitioning&&e._x_transitioning.cancel(),Object.keys(i).length===0&&Object.keys(r).length===0&&Object.keys(n).length===0){s(),o();return}let a,l,u;xp(e,{start(){a=t(e,r)},during(){l=t(e,i)},before:s,end(){a(),u=t(e,n)},after:o,cleanup(){l(),u()}})}function xp(e,t){let i,r,n,s=Gr(()=>{P(()=>{i=!0,r||t.before(),n||(t.end(),Xr()),t.after(),e.isConnected&&t.cleanup(),delete e._x_transitioning})});e._x_transitioning={beforeCancels:[],beforeCancel(o){this.beforeCancels.push(o)},cancel:Gr(function(){for(;this.beforeCancels.length;)this.beforeCancels.shift()();s()}),finish:s},P(()=>{t.start(),t.during()}),mp(),requestAnimationFrame(()=>{if(i)return;let o=Number(getComputedStyle(e).transitionDuration.replace(/,.*/,"").replace("s",""))*1e3,a=Number(getComputedStyle(e).transitionDelay.replace(/,.*/,"").replace("s",""))*1e3;o===0&&(o=Number(getComputedStyle(e).animationDuration.replace("s",""))*1e3),P(()=>{t.before()}),r=!0,requestAnimationFrame(()=>{i||(P(()=>{t.end()}),Xr(),setTimeout(e._x_transitioning.finish,o+a),n=!0)})})}function Be(e,t,i){if(e.indexOf(t)===-1)return i;const r=e[e.indexOf(t)+1];if(!r||t==="scale"&&isNaN(r))return i;if(t==="duration"||t==="delay"){let n=r.match(/([0-9]+)ms/);if(n)return n[1]}return t==="origin"&&["top","right","left","center","bottom"].includes(e[e.indexOf(t)+2])?[r,e[e.indexOf(t)+2]].join(" "):r}var Ut=!1;function qt(e,t=()=>{}){return(...i)=>Ut?t(...i):e(...i)}function Ep(e){return(...t)=>Ut&&e(...t)}var Qa=[];function Zi(e){Qa.push(e)}function Sp(e,t){Qa.forEach(i=>i(e,t)),Ut=!0,Ja(()=>{At(t,(i,r)=>{r(i,()=>{})})}),Ut=!1}var Zr=!1;function Cp(e,t){t._x_dataStack||(t._x_dataStack=e._x_dataStack),Ut=!0,Zr=!0,Ja(()=>{kp(t)}),Ut=!1,Zr=!1}function kp(e){let t=!1;At(e,(r,n)=>{le(r,(s,o)=>{if(t&&dp(s))return o();t=!0,n(s,o)})})}function Ja(e){let t=ce;Hs((i,r)=>{let n=t(i);return ke(n),()=>{}}),e(),Hs(t)}function Xa(e,t,i,r=[]){switch(e._x_bindings||(e._x_bindings=Ce({})),e._x_bindings[t]=i,t=r.includes("camel")?Mp(t):t,t){case"value":Ap(e,i);break;case"style":Tp(e,i);break;case"class":$p(e,i);break;case"selected":case"checked":Rp(e,t,i);break;default:Ga(e,t,i);break}}function Ap(e,t){if(tl(e))e.attributes.value===void 0&&(e.value=t),window.fromModel&&(typeof t=="boolean"?e.checked=ki(e.value)===t:e.checked=js(e.value,t));else if(In(e))Number.isInteger(t)?e.value=t:!Array.isArray(t)&&typeof t!="boolean"&&![null,void 0].includes(t)?e.value=String(t):Array.isArray(t)?e.checked=t.some(i=>js(i,e.value)):e.checked=!!t;else if(e.tagName==="SELECT")Lp(e,t);else{if(e.value===t)return;e.value=t===void 0?"":t}}function $p(e,t){e._x_undoAddedClasses&&e._x_undoAddedClasses(),e._x_undoAddedClasses=zn(e,t)}function Tp(e,t){e._x_undoAddedStyles&&e._x_undoAddedStyles(),e._x_undoAddedStyles=Yi(e,t)}function Rp(e,t,i){Ga(e,t,i),Fp(e,t,i)}function Ga(e,t,i){[null,void 0,!1].includes(i)&&zp(t)?e.removeAttribute(t):(Ya(t)&&(i=t),Op(e,t,i))}function Op(e,t,i){e.getAttribute(t)!=i&&e.setAttribute(t,i)}function Fp(e,t,i){e[t]!==i&&(e[t]=i)}function Lp(e,t){const i=[].concat(t).map(r=>r+"");Array.from(e.options).forEach(r=>{r.selected=i.includes(r.value)})}function Mp(e){return e.toLowerCase().replace(/-(\w)/g,(t,i)=>i.toUpperCase())}function js(e,t){return e==t}function ki(e){return[1,"1","true","on","yes",!0].includes(e)?!0:[0,"0","false","off","no",!1].includes(e)?!1:e?!!e:null}var Pp=new Set(["allowfullscreen","async","autofocus","autoplay","checked","controls","default","defer","disabled","formnovalidate","inert","ismap","itemscope","loop","multiple","muted","nomodule","novalidate","open","playsinline","readonly","required","reversed","selected","shadowrootclonable","shadowrootdelegatesfocus","shadowrootserializable"]);function Ya(e){return Pp.has(e)}function zp(e){return!["aria-pressed","aria-checked","aria-expanded","aria-selected"].includes(e)}function Ip(e,t,i){return e._x_bindings&&e._x_bindings[t]!==void 0?e._x_bindings[t]:Za(e,t,i)}function Bp(e,t,i,r=!0){if(e._x_bindings&&e._x_bindings[t]!==void 0)return e._x_bindings[t];if(e._x_inlineBindings&&e._x_inlineBindings[t]!==void 0){let n=e._x_inlineBindings[t];return n.extract=r,$a(()=>ie(e,n.expression))}return Za(e,t,i)}function Za(e,t,i){let r=e.getAttribute(t);return r===null?typeof i=="function"?i():i:r===""?!0:Ya(t)?!![t,"true"].includes(r):r}function In(e){return e.type==="checkbox"||e.localName==="ui-checkbox"||e.localName==="ui-switch"}function tl(e){return e.type==="radio"||e.localName==="ui-radio"}function el(e,t){var i;return function(){var r=this,n=arguments,s=function(){i=null,e.apply(r,n)};clearTimeout(i),i=setTimeout(s,t)}}function il(e,t){let i;return function(){let r=this,n=arguments;i||(e.apply(r,n),i=!0,setTimeout(()=>i=!1,t))}}function rl({get:e,set:t},{get:i,set:r}){let n=!0,s,o=ce(()=>{let a=e(),l=i();if(n)r(xr(a)),n=!1;else{let u=JSON.stringify(a),c=JSON.stringify(l);u!==s?r(xr(a)):u!==c&&t(xr(l))}s=JSON.stringify(e()),JSON.stringify(i())});return()=>{ke(o)}}function xr(e){return typeof e=="object"?JSON.parse(JSON.stringify(e)):e}function Dp(e){(Array.isArray(e)?e:[e]).forEach(i=>i(ai))}var Kt={},Ws=!1;function Np(e,t){if(Ws||(Kt=Ce(Kt),Ws=!0),t===void 0)return Kt[e];Kt[e]=t,Ca(Kt[e]),typeof t=="object"&&t!==null&&t.hasOwnProperty("init")&&typeof t.init=="function"&&Kt[e].init()}function Up(){return Kt}var nl={};function Hp(e,t){let i=typeof t!="function"?()=>t:t;return e instanceof Element?sl(e,i()):(nl[e]=i,()=>{})}function qp(e){return Object.entries(nl).forEach(([t,i])=>{Object.defineProperty(e,t,{get(){return(...r)=>i(...r)}})}),e}function sl(e,t,i){let r=[];for(;r.length;)r.pop()();let n=Object.entries(t).map(([o,a])=>({name:o,value:a})),s=Oa(n);return n=n.map(o=>s.find(a=>a.name===o.name)?{name:`x-bind:${o.name}`,value:`"${o.value}"`}:o),On(e,n,i).map(o=>{r.push(o.runCleanups),o()}),()=>{for(;r.length;)r.pop()()}}var ol={};function Vp(e,t){ol[e]=t}function jp(e,t){return Object.entries(ol).forEach(([i,r])=>{Object.defineProperty(e,i,{get(){return(...n)=>r.bind(t)(...n)},enumerable:!1})}),e}var Wp={get reactive(){return Ce},get release(){return ke},get effect(){return ce},get raw(){return ma},version:"3.14.9",flushAndStopDeferringMutations:Qh,dontAutoEvaluateFunctions:$a,disableEffectScheduling:Uh,startObservingMutations:An,stopObservingMutations:Ea,setReactivityEngine:Hh,onAttributeRemoved:_a,onAttributesAdded:wa,closestDataStack:be,skipDuringClone:qt,onlyDuringClone:Ep,addRootSelector:qa,addInitSelector:Va,interceptClone:Zi,addScopeToNode:si,deferMutations:Kh,mapAttributes:Fn,evaluateLater:K,interceptInit:hp,setEvaluator:tp,mergeProxies:oi,extractProp:Bp,findClosest:$e,onElRemoved:Sn,closestRoot:Gi,destroyTree:Te,interceptor:ka,transition:Yr,setStyles:Yi,mutateDom:P,directive:N,entangle:rl,throttle:il,debounce:el,evaluate:ie,initTree:At,nextTick:Pn,prefixed:Ae,prefix:np,plugin:Dp,magic:gt,store:Np,start:cp,clone:Cp,cloneNode:Sp,bound:Ip,$data:Sa,watch:ga,walk:le,data:Vp,bind:Hp},ai=Wp;function Kp(e,t){const i=Object.create(null),r=e.split(",");for(let n=0;n<r.length;n++)i[r[n]]=!0;return n=>!!i[n]}var Qp=Object.freeze({}),Jp=Object.prototype.hasOwnProperty,tr=(e,t)=>Jp.call(e,t),re=Array.isArray,je=e=>al(e)==="[object Map]",Xp=e=>typeof e=="string",Bn=e=>typeof e=="symbol",er=e=>e!==null&&typeof e=="object",Gp=Object.prototype.toString,al=e=>Gp.call(e),ll=e=>al(e).slice(8,-1),Dn=e=>Xp(e)&&e!=="NaN"&&e[0]!=="-"&&""+parseInt(e,10)===e,Yp=e=>{const t=Object.create(null);return i=>t[i]||(t[i]=e(i))},Zp=Yp(e=>e.charAt(0).toUpperCase()+e.slice(1)),ul=(e,t)=>e!==t&&(e===e||t===t),tn=new WeakMap,De=[],yt,ne=Symbol("iterate"),en=Symbol("Map key iterate");function tf(e){return e&&e._isEffect===!0}function ef(e,t=Qp){tf(e)&&(e=e.raw);const i=sf(e,t);return t.lazy||i(),i}function rf(e){e.active&&(cl(e),e.options.onStop&&e.options.onStop(),e.active=!1)}var nf=0;function sf(e,t){const i=function(){if(!i.active)return e();if(!De.includes(i)){cl(i);try{return af(),De.push(i),yt=i,e()}finally{De.pop(),dl(),yt=De[De.length-1]}}};return i.id=nf++,i.allowRecurse=!!t.allowRecurse,i._isEffect=!0,i.active=!0,i.raw=e,i.deps=[],i.options=t,i}function cl(e){const{deps:t}=e;if(t.length){for(let i=0;i<t.length;i++)t[i].delete(e);t.length=0}}var ve=!0,Nn=[];function of(){Nn.push(ve),ve=!1}function af(){Nn.push(ve),ve=!0}function dl(){const e=Nn.pop();ve=e===void 0?!0:e}function ht(e,t,i){if(!ve||yt===void 0)return;let r=tn.get(e);r||tn.set(e,r=new Map);let n=r.get(i);n||r.set(i,n=new Set),n.has(yt)||(n.add(yt),yt.deps.push(n),yt.options.onTrack&&yt.options.onTrack({effect:yt,target:e,type:t,key:i}))}function Ht(e,t,i,r,n,s){const o=tn.get(e);if(!o)return;const a=new Set,l=c=>{c&&c.forEach(d=>{(d!==yt||d.allowRecurse)&&a.add(d)})};if(t==="clear")o.forEach(l);else if(i==="length"&&re(e))o.forEach((c,d)=>{(d==="length"||d>=r)&&l(c)});else switch(i!==void 0&&l(o.get(i)),t){case"add":re(e)?Dn(i)&&l(o.get("length")):(l(o.get(ne)),je(e)&&l(o.get(en)));break;case"delete":re(e)||(l(o.get(ne)),je(e)&&l(o.get(en)));break;case"set":je(e)&&l(o.get(ne));break}const u=c=>{c.options.onTrigger&&c.options.onTrigger({effect:c,target:e,key:i,type:t,newValue:r,oldValue:n,oldTarget:s}),c.options.scheduler?c.options.scheduler(c):c()};a.forEach(u)}var lf=Kp("__proto__,__v_isRef,__isVue"),hl=new Set(Object.getOwnPropertyNames(Symbol).map(e=>Symbol[e]).filter(Bn)),uf=pl(),cf=pl(!0),Ks=df();function df(){const e={};return["includes","indexOf","lastIndexOf"].forEach(t=>{e[t]=function(...i){const r=M(this);for(let s=0,o=this.length;s<o;s++)ht(r,"get",s+"");const n=r[t](...i);return n===-1||n===!1?r[t](...i.map(M)):n}}),["push","pop","shift","unshift","splice"].forEach(t=>{e[t]=function(...i){of();const r=M(this)[t].apply(this,i);return dl(),r}}),e}function pl(e=!1,t=!1){return function(r,n,s){if(n==="__v_isReactive")return!e;if(n==="__v_isReadonly")return e;if(n==="__v_raw"&&s===(e?t?Cf:bl:t?Sf:gl).get(r))return r;const o=re(r);if(!e&&o&&tr(Ks,n))return Reflect.get(Ks,n,s);const a=Reflect.get(r,n,s);return(Bn(n)?hl.has(n):lf(n))||(e||ht(r,"get",n),t)?a:rn(a)?!o||!Dn(n)?a.value:a:er(a)?e?vl(a):Vn(a):a}}var hf=pf();function pf(e=!1){return function(i,r,n,s){let o=i[r];if(!e&&(n=M(n),o=M(o),!re(i)&&rn(o)&&!rn(n)))return o.value=n,!0;const a=re(i)&&Dn(r)?Number(r)<i.length:tr(i,r),l=Reflect.set(i,r,n,s);return i===M(s)&&(a?ul(n,o)&&Ht(i,"set",r,n,o):Ht(i,"add",r,n)),l}}function ff(e,t){const i=tr(e,t),r=e[t],n=Reflect.deleteProperty(e,t);return n&&i&&Ht(e,"delete",t,void 0,r),n}function mf(e,t){const i=Reflect.has(e,t);return(!Bn(t)||!hl.has(t))&&ht(e,"has",t),i}function gf(e){return ht(e,"iterate",re(e)?"length":ne),Reflect.ownKeys(e)}var bf={get:uf,set:hf,deleteProperty:ff,has:mf,ownKeys:gf},vf={get:cf,set(e,t){return console.warn(`Set operation on key "${String(t)}" failed: target is readonly.`,e),!0},deleteProperty(e,t){return console.warn(`Delete operation on key "${String(t)}" failed: target is readonly.`,e),!0}},Un=e=>er(e)?Vn(e):e,Hn=e=>er(e)?vl(e):e,qn=e=>e,ir=e=>Reflect.getPrototypeOf(e);function fi(e,t,i=!1,r=!1){e=e.__v_raw;const n=M(e),s=M(t);t!==s&&!i&&ht(n,"get",t),!i&&ht(n,"get",s);const{has:o}=ir(n),a=r?qn:i?Hn:Un;if(o.call(n,t))return a(e.get(t));if(o.call(n,s))return a(e.get(s));e!==n&&e.get(t)}function mi(e,t=!1){const i=this.__v_raw,r=M(i),n=M(e);return e!==n&&!t&&ht(r,"has",e),!t&&ht(r,"has",n),e===n?i.has(e):i.has(e)||i.has(n)}function gi(e,t=!1){return e=e.__v_raw,!t&&ht(M(e),"iterate",ne),Reflect.get(e,"size",e)}function Qs(e){e=M(e);const t=M(this);return ir(t).has.call(t,e)||(t.add(e),Ht(t,"add",e,e)),this}function Js(e,t){t=M(t);const i=M(this),{has:r,get:n}=ir(i);let s=r.call(i,e);s?ml(i,r,e):(e=M(e),s=r.call(i,e));const o=n.call(i,e);return i.set(e,t),s?ul(t,o)&&Ht(i,"set",e,t,o):Ht(i,"add",e,t),this}function Xs(e){const t=M(this),{has:i,get:r}=ir(t);let n=i.call(t,e);n?ml(t,i,e):(e=M(e),n=i.call(t,e));const s=r?r.call(t,e):void 0,o=t.delete(e);return n&&Ht(t,"delete",e,void 0,s),o}function Gs(){const e=M(this),t=e.size!==0,i=je(e)?new Map(e):new Set(e),r=e.clear();return t&&Ht(e,"clear",void 0,void 0,i),r}function bi(e,t){return function(r,n){const s=this,o=s.__v_raw,a=M(o),l=t?qn:e?Hn:Un;return!e&&ht(a,"iterate",ne),o.forEach((u,c)=>r.call(n,l(u),l(c),s))}}function vi(e,t,i){return function(...r){const n=this.__v_raw,s=M(n),o=je(s),a=e==="entries"||e===Symbol.iterator&&o,l=e==="keys"&&o,u=n[e](...r),c=i?qn:t?Hn:Un;return!t&&ht(s,"iterate",l?en:ne),{next(){const{value:d,done:h}=u.next();return h?{value:d,done:h}:{value:a?[c(d[0]),c(d[1])]:c(d),done:h}},[Symbol.iterator](){return this}}}}function Lt(e){return function(...t){{const i=t[0]?`on key "${t[0]}" `:"";console.warn(`${Zp(e)} operation ${i}failed: target is readonly.`,M(this))}return e==="delete"?!1:this}}function yf(){const e={get(s){return fi(this,s)},get size(){return gi(this)},has:mi,add:Qs,set:Js,delete:Xs,clear:Gs,forEach:bi(!1,!1)},t={get(s){return fi(this,s,!1,!0)},get size(){return gi(this)},has:mi,add:Qs,set:Js,delete:Xs,clear:Gs,forEach:bi(!1,!0)},i={get(s){return fi(this,s,!0)},get size(){return gi(this,!0)},has(s){return mi.call(this,s,!0)},add:Lt("add"),set:Lt("set"),delete:Lt("delete"),clear:Lt("clear"),forEach:bi(!0,!1)},r={get(s){return fi(this,s,!0,!0)},get size(){return gi(this,!0)},has(s){return mi.call(this,s,!0)},add:Lt("add"),set:Lt("set"),delete:Lt("delete"),clear:Lt("clear"),forEach:bi(!0,!0)};return["keys","values","entries",Symbol.iterator].forEach(s=>{e[s]=vi(s,!1,!1),i[s]=vi(s,!0,!1),t[s]=vi(s,!1,!0),r[s]=vi(s,!0,!0)}),[e,i,t,r]}var[wf,_f,Rm,Om]=yf();function fl(e,t){const i=e?_f:wf;return(r,n,s)=>n==="__v_isReactive"?!e:n==="__v_isReadonly"?e:n==="__v_raw"?r:Reflect.get(tr(i,n)&&n in r?i:r,n,s)}var xf={get:fl(!1)},Ef={get:fl(!0)};function ml(e,t,i){const r=M(i);if(r!==i&&t.call(e,r)){const n=ll(e);console.warn(`Reactive ${n} contains both the raw and reactive versions of the same object${n==="Map"?" as keys":""}, which can lead to inconsistencies. Avoid differentiating between the raw and reactive versions of an object and only use the reactive version if possible.`)}}var gl=new WeakMap,Sf=new WeakMap,bl=new WeakMap,Cf=new WeakMap;function kf(e){switch(e){case"Object":case"Array":return 1;case"Map":case"Set":case"WeakMap":case"WeakSet":return 2;default:return 0}}function Af(e){return e.__v_skip||!Object.isExtensible(e)?0:kf(ll(e))}function Vn(e){return e&&e.__v_isReadonly?e:yl(e,!1,bf,xf,gl)}function vl(e){return yl(e,!0,vf,Ef,bl)}function yl(e,t,i,r,n){if(!er(e))return console.warn(`value cannot be made reactive: ${String(e)}`),e;if(e.__v_raw&&!(t&&e.__v_isReactive))return e;const s=n.get(e);if(s)return s;const o=Af(e);if(o===0)return e;const a=new Proxy(e,o===2?r:i);return n.set(e,a),a}function M(e){return e&&M(e.__v_raw)||e}function rn(e){return!!(e&&e.__v_isRef===!0)}gt("nextTick",()=>Pn);gt("dispatch",e=>Ve.bind(Ve,e));gt("watch",(e,{evaluateLater:t,cleanup:i})=>(r,n)=>{let s=t(r),a=ga(()=>{let l;return s(u=>l=u),l},n);i(a)});gt("store",Up);gt("data",e=>Sa(e));gt("root",e=>Gi(e));gt("refs",e=>(e._x_refs_proxy||(e._x_refs_proxy=oi($f(e))),e._x_refs_proxy));function $f(e){let t=[];return $e(e,i=>{i._x_refs&&t.push(i._x_refs)}),t}var Er={};function wl(e){return Er[e]||(Er[e]=0),++Er[e]}function Tf(e,t){return $e(e,i=>{if(i._x_ids&&i._x_ids[t])return!0})}function Rf(e,t){e._x_ids||(e._x_ids={}),e._x_ids[t]||(e._x_ids[t]=wl(t))}gt("id",(e,{cleanup:t})=>(i,r=null)=>{let n=`${i}${r?`-${r}`:""}`;return Of(e,n,t,()=>{let s=Tf(e,i),o=s?s._x_ids[i]:wl(i);return r?`${i}-${o}-${r}`:`${i}-${o}`})});Zi((e,t)=>{e._x_id&&(t._x_id=e._x_id)});function Of(e,t,i,r){if(e._x_id||(e._x_id={}),e._x_id[t])return e._x_id[t];let n=r();return e._x_id[t]=n,i(()=>{delete e._x_id[t]}),n}gt("el",e=>e);_l("Focus","focus","focus");_l("Persist","persist","persist");function _l(e,t,i){gt(t,r=>at(`You can't use [$${t}] without first installing the "${e}" plugin here: https://alpinejs.dev/plugins/${i}`,r))}N("modelable",(e,{expression:t},{effect:i,evaluateLater:r,cleanup:n})=>{let s=r(t),o=()=>{let c;return s(d=>c=d),c},a=r(`${t} = __placeholder`),l=c=>a(()=>{},{scope:{__placeholder:c}}),u=o();l(u),queueMicrotask(()=>{if(!e._x_model)return;e._x_removeModelListeners.default();let c=e._x_model.get,d=e._x_model.set,h=rl({get(){return c()},set(p){d(p)}},{get(){return o()},set(p){l(p)}});n(h)})});N("teleport",(e,{modifiers:t,expression:i},{cleanup:r})=>{e.tagName.toLowerCase()!=="template"&&at("x-teleport can only be used on a <template> tag",e);let n=Ys(i),s=e.content.cloneNode(!0).firstElementChild;e._x_teleport=s,s._x_teleportBack=e,e.setAttribute("data-teleport-template",!0),s.setAttribute("data-teleport-target",!0),e._x_forwardEvents&&e._x_forwardEvents.forEach(a=>{s.addEventListener(a,l=>{l.stopPropagation(),e.dispatchEvent(new l.constructor(l.type,l))})}),si(s,{},e);let o=(a,l,u)=>{u.includes("prepend")?l.parentNode.insertBefore(a,l):u.includes("append")?l.parentNode.insertBefore(a,l.nextSibling):l.appendChild(a)};P(()=>{o(s,n,t),qt(()=>{At(s)})()}),e._x_teleportPutBack=()=>{let a=Ys(i);P(()=>{o(e._x_teleport,a,t)})},r(()=>P(()=>{s.remove(),Te(s)}))});var Ff=document.createElement("div");function Ys(e){let t=qt(()=>document.querySelector(e),()=>Ff)();return t||at(`Cannot find x-teleport element for selector: "${e}"`),t}var xl=()=>{};xl.inline=(e,{modifiers:t},{cleanup:i})=>{t.includes("self")?e._x_ignoreSelf=!0:e._x_ignore=!0,i(()=>{t.includes("self")?delete e._x_ignoreSelf:delete e._x_ignore})};N("ignore",xl);N("effect",qt((e,{expression:t},{effect:i})=>{i(K(e,t))}));function nn(e,t,i,r){let n=e,s=l=>r(l),o={},a=(l,u)=>c=>u(l,c);if(i.includes("dot")&&(t=Lf(t)),i.includes("camel")&&(t=Mf(t)),i.includes("passive")&&(o.passive=!0),i.includes("capture")&&(o.capture=!0),i.includes("window")&&(n=window),i.includes("document")&&(n=document),i.includes("debounce")){let l=i[i.indexOf("debounce")+1]||"invalid-wait",u=Ii(l.split("ms")[0])?Number(l.split("ms")[0]):250;s=el(s,u)}if(i.includes("throttle")){let l=i[i.indexOf("throttle")+1]||"invalid-wait",u=Ii(l.split("ms")[0])?Number(l.split("ms")[0]):250;s=il(s,u)}return i.includes("prevent")&&(s=a(s,(l,u)=>{u.preventDefault(),l(u)})),i.includes("stop")&&(s=a(s,(l,u)=>{u.stopPropagation(),l(u)})),i.includes("once")&&(s=a(s,(l,u)=>{l(u),n.removeEventListener(t,s,o)})),(i.includes("away")||i.includes("outside"))&&(n=document,s=a(s,(l,u)=>{e.contains(u.target)||u.target.isConnected!==!1&&(e.offsetWidth<1&&e.offsetHeight<1||e._x_isShown!==!1&&l(u))})),i.includes("self")&&(s=a(s,(l,u)=>{u.target===e&&l(u)})),(zf(t)||El(t))&&(s=a(s,(l,u)=>{If(u,i)||l(u)})),n.addEventListener(t,s,o),()=>{n.removeEventListener(t,s,o)}}function Lf(e){return e.replace(/-/g,".")}function Mf(e){return e.toLowerCase().replace(/-(\w)/g,(t,i)=>i.toUpperCase())}function Ii(e){return!Array.isArray(e)&&!isNaN(e)}function Pf(e){return[" ","_"].includes(e)?e:e.replace(/([a-z])([A-Z])/g,"$1-$2").replace(/[_\s]/,"-").toLowerCase()}function zf(e){return["keydown","keyup"].includes(e)}function El(e){return["contextmenu","click","mouse"].some(t=>e.includes(t))}function If(e,t){let i=t.filter(s=>!["window","document","prevent","stop","once","capture","self","away","outside","passive"].includes(s));if(i.includes("debounce")){let s=i.indexOf("debounce");i.splice(s,Ii((i[s+1]||"invalid-wait").split("ms")[0])?2:1)}if(i.includes("throttle")){let s=i.indexOf("throttle");i.splice(s,Ii((i[s+1]||"invalid-wait").split("ms")[0])?2:1)}if(i.length===0||i.length===1&&Zs(e.key).includes(i[0]))return!1;const n=["ctrl","shift","alt","meta","cmd","super"].filter(s=>i.includes(s));return i=i.filter(s=>!n.includes(s)),!(n.length>0&&n.filter(o=>((o==="cmd"||o==="super")&&(o="meta"),e[`${o}Key`])).length===n.length&&(El(e.type)||Zs(e.key).includes(i[0])))}function Zs(e){if(!e)return[];e=Pf(e);let t={ctrl:"control",slash:"/",space:" ",spacebar:" ",cmd:"meta",esc:"escape",up:"arrow-up",down:"arrow-down",left:"arrow-left",right:"arrow-right",period:".",comma:",",equal:"=",minus:"-",underscore:"_"};return t[e]=e,Object.keys(t).map(i=>{if(t[i]===e)return i}).filter(i=>i)}N("model",(e,{modifiers:t,expression:i},{effect:r,cleanup:n})=>{let s=e;t.includes("parent")&&(s=e.parentNode);let o=K(s,i),a;typeof i=="string"?a=K(s,`${i} = __placeholder`):typeof i=="function"&&typeof i()=="string"?a=K(s,`${i()} = __placeholder`):a=()=>{};let l=()=>{let h;return o(p=>h=p),to(h)?h.get():h},u=h=>{let p;o(f=>p=f),to(p)?p.set(h):a(()=>{},{scope:{__placeholder:h}})};typeof i=="string"&&e.type==="radio"&&P(()=>{e.hasAttribute("name")||e.setAttribute("name",i)});var c=e.tagName.toLowerCase()==="select"||["checkbox","radio"].includes(e.type)||t.includes("lazy")?"change":"input";let d=Ut?()=>{}:nn(e,c,t,h=>{u(Sr(e,t,h,l()))});if(t.includes("fill")&&([void 0,null,""].includes(l())||In(e)&&Array.isArray(l())||e.tagName.toLowerCase()==="select"&&e.multiple)&&u(Sr(e,t,{target:e},l())),e._x_removeModelListeners||(e._x_removeModelListeners={}),e._x_removeModelListeners.default=d,n(()=>e._x_removeModelListeners.default()),e.form){let h=nn(e.form,"reset",[],p=>{Pn(()=>e._x_model&&e._x_model.set(Sr(e,t,{target:e},l())))});n(()=>h())}e._x_model={get(){return l()},set(h){u(h)}},e._x_forceModelUpdate=h=>{h===void 0&&typeof i=="string"&&i.match(/\./)&&(h=""),window.fromModel=!0,P(()=>Xa(e,"value",h)),delete window.fromModel},r(()=>{let h=l();t.includes("unintrusive")&&document.activeElement.isSameNode(e)||e._x_forceModelUpdate(h)})});function Sr(e,t,i,r){return P(()=>{if(i instanceof CustomEvent&&i.detail!==void 0)return i.detail!==null&&i.detail!==void 0?i.detail:i.target.value;if(In(e))if(Array.isArray(r)){let n=null;return t.includes("number")?n=Cr(i.target.value):t.includes("boolean")?n=ki(i.target.value):n=i.target.value,i.target.checked?r.includes(n)?r:r.concat([n]):r.filter(s=>!Bf(s,n))}else return i.target.checked;else{if(e.tagName.toLowerCase()==="select"&&e.multiple)return t.includes("number")?Array.from(i.target.selectedOptions).map(n=>{let s=n.value||n.text;return Cr(s)}):t.includes("boolean")?Array.from(i.target.selectedOptions).map(n=>{let s=n.value||n.text;return ki(s)}):Array.from(i.target.selectedOptions).map(n=>n.value||n.text);{let n;return tl(e)?i.target.checked?n=i.target.value:n=r:n=i.target.value,t.includes("number")?Cr(n):t.includes("boolean")?ki(n):t.includes("trim")?n.trim():n}}})}function Cr(e){let t=e?parseFloat(e):null;return Df(t)?t:e}function Bf(e,t){return e==t}function Df(e){return!Array.isArray(e)&&!isNaN(e)}function to(e){return e!==null&&typeof e=="object"&&typeof e.get=="function"&&typeof e.set=="function"}N("cloak",e=>queueMicrotask(()=>P(()=>e.removeAttribute(Ae("cloak")))));Va(()=>`[${Ae("init")}]`);N("init",qt((e,{expression:t},{evaluate:i})=>typeof t=="string"?!!t.trim()&&i(t,{},!1):i(t,{},!1)));N("text",(e,{expression:t},{effect:i,evaluateLater:r})=>{let n=r(t);i(()=>{n(s=>{P(()=>{e.textContent=s})})})});N("html",(e,{expression:t},{effect:i,evaluateLater:r})=>{let n=r(t);i(()=>{n(s=>{P(()=>{e.innerHTML=s,e._x_ignoreSelf=!0,At(e),delete e._x_ignoreSelf})})})});Fn(Ma(":",Pa(Ae("bind:"))));var Sl=(e,{value:t,modifiers:i,expression:r,original:n},{effect:s,cleanup:o})=>{if(!t){let l={};qp(l),K(e,r)(c=>{sl(e,c,n)},{scope:l});return}if(t==="key")return Nf(e,r);if(e._x_inlineBindings&&e._x_inlineBindings[t]&&e._x_inlineBindings[t].extract)return;let a=K(e,r);s(()=>a(l=>{l===void 0&&typeof r=="string"&&r.match(/\./)&&(l=""),P(()=>Xa(e,t,l,i))})),o(()=>{e._x_undoAddedClasses&&e._x_undoAddedClasses(),e._x_undoAddedStyles&&e._x_undoAddedStyles()})};Sl.inline=(e,{value:t,modifiers:i,expression:r})=>{t&&(e._x_inlineBindings||(e._x_inlineBindings={}),e._x_inlineBindings[t]={expression:r,extract:!1})};N("bind",Sl);function Nf(e,t){e._x_keyExpression=t}qa(()=>`[${Ae("data")}]`);N("data",(e,{expression:t},{cleanup:i})=>{if(Uf(e))return;t=t===""?"{}":t;let r={};Wr(r,e);let n={};jp(n,r);let s=ie(e,t,{scope:n});(s===void 0||s===!0)&&(s={}),Wr(s,e);let o=Ce(s);Ca(o);let a=si(e,o);o.init&&ie(e,o.init),i(()=>{o.destroy&&ie(e,o.destroy),a()})});Zi((e,t)=>{e._x_dataStack&&(t._x_dataStack=e._x_dataStack,t.setAttribute("data-has-alpine-state",!0))});function Uf(e){return Ut?Zr?!0:e.hasAttribute("data-has-alpine-state"):!1}N("show",(e,{modifiers:t,expression:i},{effect:r})=>{let n=K(e,i);e._x_doHide||(e._x_doHide=()=>{P(()=>{e.style.setProperty("display","none",t.includes("important")?"important":void 0)})}),e._x_doShow||(e._x_doShow=()=>{P(()=>{e.style.length===1&&e.style.display==="none"?e.removeAttribute("style"):e.style.removeProperty("display")})});let s=()=>{e._x_doHide(),e._x_isShown=!1},o=()=>{e._x_doShow(),e._x_isShown=!0},a=()=>setTimeout(o),l=Gr(d=>d?o():s(),d=>{typeof e._x_toggleAndCascadeWithTransitions=="function"?e._x_toggleAndCascadeWithTransitions(e,d,o,s):d?a():s()}),u,c=!0;r(()=>n(d=>{!c&&d===u||(t.includes("immediate")&&(d?a():s()),l(d),u=d,c=!1)}))});N("for",(e,{expression:t},{effect:i,cleanup:r})=>{let n=qf(t),s=K(e,n.items),o=K(e,e._x_keyExpression||"index");e._x_prevKeys=[],e._x_lookup={},i(()=>Hf(e,n,s,o)),r(()=>{Object.values(e._x_lookup).forEach(a=>P(()=>{Te(a),a.remove()})),delete e._x_prevKeys,delete e._x_lookup})});function Hf(e,t,i,r){let n=o=>typeof o=="object"&&!Array.isArray(o),s=e;i(o=>{Vf(o)&&o>=0&&(o=Array.from(Array(o).keys(),m=>m+1)),o===void 0&&(o=[]);let a=e._x_lookup,l=e._x_prevKeys,u=[],c=[];if(n(o))o=Object.entries(o).map(([m,w])=>{let x=eo(t,w,m,o);r(E=>{c.includes(E)&&at("Duplicate key on x-for",e),c.push(E)},{scope:{index:m,...x}}),u.push(x)});else for(let m=0;m<o.length;m++){let w=eo(t,o[m],m,o);r(x=>{c.includes(x)&&at("Duplicate key on x-for",e),c.push(x)},{scope:{index:m,...w}}),u.push(w)}let d=[],h=[],p=[],f=[];for(let m=0;m<l.length;m++){let w=l[m];c.indexOf(w)===-1&&p.push(w)}l=l.filter(m=>!p.includes(m));let v="template";for(let m=0;m<c.length;m++){let w=c[m],x=l.indexOf(w);if(x===-1)l.splice(m,0,w),d.push([v,m]);else if(x!==m){let E=l.splice(m,1)[0],S=l.splice(x-1,1)[0];l.splice(m,0,S),l.splice(x,0,E),h.push([E,S])}else f.push(w);v=w}for(let m=0;m<p.length;m++){let w=p[m];w in a&&(P(()=>{Te(a[w]),a[w].remove()}),delete a[w])}for(let m=0;m<h.length;m++){let[w,x]=h[m],E=a[w],S=a[x],C=document.createElement("div");P(()=>{S||at('x-for ":key" is undefined or invalid',s,x,a),S.after(C),E.after(S),S._x_currentIfEl&&S.after(S._x_currentIfEl),C.before(E),E._x_currentIfEl&&E.after(E._x_currentIfEl),C.remove()}),S._x_refreshXForScope(u[c.indexOf(x)])}for(let m=0;m<d.length;m++){let[w,x]=d[m],E=w==="template"?s:a[w];E._x_currentIfEl&&(E=E._x_currentIfEl);let S=u[x],C=c[x],F=document.importNode(s.content,!0).firstElementChild,R=Ce(S);si(F,R,s),F._x_refreshXForScope=H=>{Object.entries(H).forEach(([j,Q])=>{R[j]=Q})},P(()=>{E.after(F),qt(()=>At(F))()}),typeof C=="object"&&at("x-for key cannot be an object, it must be a string or an integer",s),a[C]=F}for(let m=0;m<f.length;m++)a[f[m]]._x_refreshXForScope(u[c.indexOf(f[m])]);s._x_prevKeys=c})}function qf(e){let t=/,([^,\}\]]*)(?:,([^,\}\]]*))?$/,i=/^\s*\(|\)\s*$/g,r=/([\s\S]*?)\s+(?:in|of)\s+([\s\S]*)/,n=e.match(r);if(!n)return;let s={};s.items=n[2].trim();let o=n[1].replace(i,"").trim(),a=o.match(t);return a?(s.item=o.replace(t,"").trim(),s.index=a[1].trim(),a[2]&&(s.collection=a[2].trim())):s.item=o,s}function eo(e,t,i,r){let n={};return/^\[.*\]$/.test(e.item)&&Array.isArray(t)?e.item.replace("[","").replace("]","").split(",").map(o=>o.trim()).forEach((o,a)=>{n[o]=t[a]}):/^\{.*\}$/.test(e.item)&&!Array.isArray(t)&&typeof t=="object"?e.item.replace("{","").replace("}","").split(",").map(o=>o.trim()).forEach(o=>{n[o]=t[o]}):n[e.item]=t,e.index&&(n[e.index]=i),e.collection&&(n[e.collection]=r),n}function Vf(e){return!Array.isArray(e)&&!isNaN(e)}function Cl(){}Cl.inline=(e,{expression:t},{cleanup:i})=>{let r=Gi(e);r._x_refs||(r._x_refs={}),r._x_refs[t]=e,i(()=>delete r._x_refs[t])};N("ref",Cl);N("if",(e,{expression:t},{effect:i,cleanup:r})=>{e.tagName.toLowerCase()!=="template"&&at("x-if can only be used on a <template> tag",e);let n=K(e,t),s=()=>{if(e._x_currentIfEl)return e._x_currentIfEl;let a=e.content.cloneNode(!0).firstElementChild;return si(a,{},e),P(()=>{e.after(a),qt(()=>At(a))()}),e._x_currentIfEl=a,e._x_undoIf=()=>{P(()=>{Te(a),a.remove()}),delete e._x_currentIfEl},a},o=()=>{e._x_undoIf&&(e._x_undoIf(),delete e._x_undoIf)};i(()=>n(a=>{a?s():o()})),r(()=>e._x_undoIf&&e._x_undoIf())});N("id",(e,{expression:t},{evaluate:i})=>{i(t).forEach(n=>Rf(e,n))});Zi((e,t)=>{e._x_ids&&(t._x_ids=e._x_ids)});Fn(Ma("@",Pa(Ae("on:"))));N("on",qt((e,{value:t,modifiers:i,expression:r},{cleanup:n})=>{let s=r?K(e,r):()=>{};e.tagName.toLowerCase()==="template"&&(e._x_forwardEvents||(e._x_forwardEvents=[]),e._x_forwardEvents.includes(t)||e._x_forwardEvents.push(t));let o=nn(e,t,i,a=>{s(()=>{},{scope:{$event:a},params:[a]})});n(()=>o())}));rr("Collapse","collapse","collapse");rr("Intersect","intersect","intersect");rr("Focus","trap","focus");rr("Mask","mask","mask");function rr(e,t,i){N(t,r=>at(`You can't use [x-${t}] without first installing the "${e}" plugin here: https://alpinejs.dev/plugins/${i}`,r))}ai.setEvaluator(Ra);ai.setReactivityEngine({reactive:Vn,effect:ef,release:rf,raw:M});var jf=ai,jn=jf;function Wf(e){let t=()=>{let i,r;try{r=localStorage}catch(n){console.error(n),console.warn("Alpine: $persist is using temporary storage since localStorage is unavailable.");let s=new Map;r={getItem:s.get.bind(s),setItem:s.set.bind(s)}}return e.interceptor((n,s,o,a,l)=>{let u=i||`_x_${a}`,c=io(u,r)?ro(u,r):n;return o(c),e.effect(()=>{let d=s();no(u,d,r),o(d)}),c},n=>{n.as=s=>(i=s,n),n.using=s=>(r=s,n)})};Object.defineProperty(e,"$persist",{get:()=>t()}),e.magic("persist",t),e.persist=(i,{get:r,set:n},s=localStorage)=>{let o=io(i,s)?ro(i,s):r();n(o),e.effect(()=>{let a=r();no(i,a,s),n(a)})}}function io(e,t){return t.getItem(e)!==null}function ro(e,t){let i=t.getItem(e,t);if(i!==void 0)return JSON.parse(i)}function no(e,t,i){i.setItem(e,JSON.stringify(t))}var Kf=Wf,so=kl;function kl(){var e=[].slice.call(arguments),t=!1;typeof e[0]=="boolean"&&(t=e.shift());var i=e[0];if(oo(i))throw new Error("extendee must be an object");for(var r=e.slice(1),n=r.length,s=0;s<n;s++){var o=r[s];for(var a in o)if(Object.prototype.hasOwnProperty.call(o,a)){var l=o[a];if(t&&Qf(l)){var u=Array.isArray(l)?[]:{};i[a]=kl(!0,Object.prototype.hasOwnProperty.call(i,a)&&!oo(i[a])?i[a]:u,l)}else i[a]=l}}return i}function Qf(e){return Array.isArray(e)||{}.toString.call(e)=="[object Object]"}function oo(e){return!e||typeof e!="object"&&typeof e!="function"}function Jf(e){return e&&e.__esModule?e.default:e}class ao{on(t,i){return this._callbacks=this._callbacks||{},this._callbacks[t]||(this._callbacks[t]=[]),this._callbacks[t].push(i),this}emit(t,...i){this._callbacks=this._callbacks||{};let r=this._callbacks[t];if(r)for(let n of r)n.apply(this,i);return this.element&&this.element.dispatchEvent(this.makeEvent("dropzone:"+t,{args:i})),this}makeEvent(t,i){let r={bubbles:!0,cancelable:!0,detail:i};if(typeof window.CustomEvent=="function")return new CustomEvent(t,r);var n=document.createEvent("CustomEvent");return n.initCustomEvent(t,r.bubbles,r.cancelable,r.detail),n}off(t,i){if(!this._callbacks||arguments.length===0)return this._callbacks={},this;let r=this._callbacks[t];if(!r)return this;if(arguments.length===1)return delete this._callbacks[t],this;for(let n=0;n<r.length;n++)if(r[n]===i){r.splice(n,1);break}return this}}var Al={};Al=`<div class="dz-preview dz-file-preview">
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
`;let Xf={url:null,method:"post",withCredentials:!1,timeout:null,parallelUploads:2,uploadMultiple:!1,chunking:!1,forceChunking:!1,chunkSize:2097152,parallelChunkUploads:!1,retryChunks:!1,retryChunksLimit:3,maxFilesize:256,paramName:"file",createImageThumbnails:!0,maxThumbnailFilesize:10,thumbnailWidth:120,thumbnailHeight:120,thumbnailMethod:"crop",resizeWidth:null,resizeHeight:null,resizeMimeType:null,resizeQuality:.8,resizeMethod:"contain",filesizeBase:1e3,maxFiles:null,headers:null,defaultHeaders:!0,clickable:!0,ignoreHiddenFiles:!0,acceptedFiles:null,acceptedMimeTypes:null,autoProcessQueue:!0,autoQueue:!0,addRemoveLinks:!1,previewsContainer:null,disablePreviews:!1,hiddenInputContainer:"body",capture:null,renameFilename:null,renameFile:null,forceFallback:!1,dictDefaultMessage:"Drop files here to upload",dictFallbackMessage:"Your browser does not support drag'n'drop file uploads.",dictFallbackText:"Please use the fallback form below to upload your files like in the olden days.",dictFileTooBig:"File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.",dictInvalidFileType:"You can't upload files of this type.",dictResponseError:"Server responded with {{statusCode}} code.",dictCancelUpload:"Cancel upload",dictUploadCanceled:"Upload canceled.",dictCancelUploadConfirmation:"Are you sure you want to cancel this upload?",dictRemoveFile:"Remove file",dictRemoveFileConfirmation:null,dictMaxFilesExceeded:"You can not upload any more files.",dictFileSizeUnits:{tb:"TB",gb:"GB",mb:"MB",kb:"KB",b:"b"},init(){},params(e,t,i){if(i)return{dzuuid:i.file.upload.uuid,dzchunkindex:i.index,dztotalfilesize:i.file.size,dzchunksize:this.options.chunkSize,dztotalchunkcount:i.file.upload.totalChunkCount,dzchunkbyteoffset:i.index*this.options.chunkSize}},accept(e,t){return t()},chunksUploaded:function(e,t){t()},binaryBody:!1,fallback(){let e;this.element.className=`${this.element.className} dz-browser-not-supported`;for(let i of this.element.getElementsByTagName("div"))if(/(^| )dz-message($| )/.test(i.className)){e=i,i.className="dz-message";break}e||(e=_.createElement('<div class="dz-message"><span></span></div>'),this.element.appendChild(e));let t=e.getElementsByTagName("span")[0];return t&&(t.textContent!=null?t.textContent=this.options.dictFallbackMessage:t.innerText!=null&&(t.innerText=this.options.dictFallbackMessage)),this.element.appendChild(this.getFallbackForm())},resize(e,t,i,r){let n={srcX:0,srcY:0,srcWidth:e.width,srcHeight:e.height},s=e.width/e.height;t==null&&i==null?(t=n.srcWidth,i=n.srcHeight):t==null?t=i*s:i==null&&(i=t/s),t=Math.min(t,n.srcWidth),i=Math.min(i,n.srcHeight);let o=t/i;if(n.srcWidth>t||n.srcHeight>i)if(r==="crop")s>o?(n.srcHeight=e.height,n.srcWidth=n.srcHeight*o):(n.srcWidth=e.width,n.srcHeight=n.srcWidth/o);else if(r==="contain")s>o?i=t/s:t=i*s;else throw new Error(`Unknown resizeMethod '${r}'`);return n.srcX=(e.width-n.srcWidth)/2,n.srcY=(e.height-n.srcHeight)/2,n.trgWidth=t,n.trgHeight=i,n},transformFile(e,t){return(this.options.resizeWidth||this.options.resizeHeight)&&e.type.match(/image.*/)?this.resizeImage(e,this.options.resizeWidth,this.options.resizeHeight,this.options.resizeMethod,t):t(e)},previewTemplate:Jf(Al),drop(e){return this.element.classList.remove("dz-drag-hover")},dragstart(e){},dragend(e){return this.element.classList.remove("dz-drag-hover")},dragenter(e){return this.element.classList.add("dz-drag-hover")},dragover(e){return this.element.classList.add("dz-drag-hover")},dragleave(e){return this.element.classList.remove("dz-drag-hover")},paste(e){},reset(){return this.element.classList.remove("dz-started")},addedfile(e){if(this.element===this.previewsContainer&&this.element.classList.add("dz-started"),this.previewsContainer&&!this.options.disablePreviews){e.previewElement=_.createElement(this.options.previewTemplate.trim()),e.previewTemplate=e.previewElement,this.previewsContainer.appendChild(e.previewElement);for(var t of e.previewElement.querySelectorAll("[data-dz-name]"))t.textContent=e.name;for(t of e.previewElement.querySelectorAll("[data-dz-size]"))t.innerHTML=this.filesize(e.size);this.options.addRemoveLinks&&(e._removeLink=_.createElement(`<a class="dz-remove" href="javascript:undefined;" data-dz-remove>${this.options.dictRemoveFile}</a>`),e.previewElement.appendChild(e._removeLink));let i=r=>(r.preventDefault(),r.stopPropagation(),e.status===_.UPLOADING?_.confirm(this.options.dictCancelUploadConfirmation,()=>this.removeFile(e)):this.options.dictRemoveFileConfirmation?_.confirm(this.options.dictRemoveFileConfirmation,()=>this.removeFile(e)):this.removeFile(e));for(let r of e.previewElement.querySelectorAll("[data-dz-remove]"))r.addEventListener("click",i)}},removedfile(e){return e.previewElement!=null&&e.previewElement.parentNode!=null&&e.previewElement.parentNode.removeChild(e.previewElement),this._updateMaxFilesReachedClass()},thumbnail(e,t){if(e.previewElement){e.previewElement.classList.remove("dz-file-preview");for(let i of e.previewElement.querySelectorAll("[data-dz-thumbnail]"))i.alt=e.name,i.src=t;return setTimeout(()=>e.previewElement.classList.add("dz-image-preview"),1)}},error(e,t){if(e.previewElement){e.previewElement.classList.add("dz-error"),typeof t!="string"&&t.error&&(t=t.error);for(let i of e.previewElement.querySelectorAll("[data-dz-errormessage]"))i.textContent=t}},errormultiple(){},processing(e){if(e.previewElement&&(e.previewElement.classList.add("dz-processing"),e._removeLink))return e._removeLink.innerHTML=this.options.dictCancelUpload},processingmultiple(){},uploadprogress(e,t,i){if(e.previewElement)for(let r of e.previewElement.querySelectorAll("[data-dz-uploadprogress]"))r.nodeName==="PROGRESS"?r.value=t:r.style.width=`${t}%`},totaluploadprogress(){},sending(){},sendingmultiple(){},success(e){if(e.previewElement)return e.previewElement.classList.add("dz-success")},successmultiple(){},canceled(e){return this.emit("error",e,this.options.dictUploadCanceled)},canceledmultiple(){},complete(e){if(e._removeLink&&(e._removeLink.innerHTML=this.options.dictRemoveFile),e.previewElement)return e.previewElement.classList.add("dz-complete")},completemultiple(){},maxfilesexceeded(){},maxfilesreached(){},queuecomplete(){},addedfiles(){}};var Gf=Xf;class _ extends ao{static initClass(){this.prototype.Emitter=ao,this.prototype.events=["drop","dragstart","dragend","dragenter","dragover","dragleave","addedfile","addedfiles","removedfile","thumbnail","error","errormultiple","processing","processingmultiple","uploadprogress","totaluploadprogress","sending","sendingmultiple","success","successmultiple","canceled","canceledmultiple","complete","completemultiple","reset","maxfilesexceeded","maxfilesreached","queuecomplete"],this.prototype._thumbnailQueue=[],this.prototype._processingThumbnail=!1}getAcceptedFiles(){return this.files.filter(t=>t.accepted).map(t=>t)}getRejectedFiles(){return this.files.filter(t=>!t.accepted).map(t=>t)}getFilesWithStatus(t){return this.files.filter(i=>i.status===t).map(i=>i)}getQueuedFiles(){return this.getFilesWithStatus(_.QUEUED)}getUploadingFiles(){return this.getFilesWithStatus(_.UPLOADING)}getAddedFiles(){return this.getFilesWithStatus(_.ADDED)}getActiveFiles(){return this.files.filter(t=>t.status===_.UPLOADING||t.status===_.QUEUED).map(t=>t)}init(){if(this.element.tagName==="form"&&this.element.setAttribute("enctype","multipart/form-data"),this.element.classList.contains("dropzone")&&!this.element.querySelector(".dz-message")&&this.element.appendChild(_.createElement(`<div class="dz-default dz-message"><button class="dz-button" type="button">${this.options.dictDefaultMessage}</button></div>`)),this.clickableElements.length){let r=()=>{this.hiddenFileInput&&this.hiddenFileInput.parentNode.removeChild(this.hiddenFileInput),this.hiddenFileInput=document.createElement("input"),this.hiddenFileInput.setAttribute("type","file"),(this.options.maxFiles===null||this.options.maxFiles>1)&&this.hiddenFileInput.setAttribute("multiple","multiple"),this.hiddenFileInput.className="dz-hidden-input",this.options.acceptedFiles!==null&&this.hiddenFileInput.setAttribute("accept",this.options.acceptedFiles),this.options.capture!==null&&this.hiddenFileInput.setAttribute("capture",this.options.capture),this.hiddenFileInput.setAttribute("tabindex","-1"),this.hiddenFileInput.style.visibility="hidden",this.hiddenFileInput.style.position="absolute",this.hiddenFileInput.style.top="0",this.hiddenFileInput.style.left="0",this.hiddenFileInput.style.height="0",this.hiddenFileInput.style.width="0",_.getElement(this.options.hiddenInputContainer,"hiddenInputContainer").appendChild(this.hiddenFileInput),this.hiddenFileInput.addEventListener("change",()=>{let{files:n}=this.hiddenFileInput;if(n.length)for(let s of n)this.addFile(s);this.emit("addedfiles",n),r()})};r()}this.URL=window.URL!==null?window.URL:window.webkitURL;for(let r of this.events)this.on(r,this.options[r]);this.on("uploadprogress",()=>this.updateTotalUploadProgress()),this.on("removedfile",()=>this.updateTotalUploadProgress()),this.on("canceled",r=>this.emit("complete",r)),this.on("complete",r=>{if(this.getAddedFiles().length===0&&this.getUploadingFiles().length===0&&this.getQueuedFiles().length===0)return setTimeout(()=>this.emit("queuecomplete"),0)});const t=function(r){if(r.dataTransfer.types){for(var n=0;n<r.dataTransfer.types.length;n++)if(r.dataTransfer.types[n]==="Files")return!0}return!1};let i=function(r){if(t(r))return r.stopPropagation(),r.preventDefault?r.preventDefault():r.returnValue=!1};return this.listeners=[{element:this.element,events:{dragstart:r=>this.emit("dragstart",r),dragenter:r=>(i(r),this.emit("dragenter",r)),dragover:r=>{let n;try{n=r.dataTransfer.effectAllowed}catch{}return r.dataTransfer.dropEffect=n==="move"||n==="linkMove"?"move":"copy",i(r),this.emit("dragover",r)},dragleave:r=>this.emit("dragleave",r),drop:r=>(i(r),this.drop(r)),dragend:r=>this.emit("dragend",r)}}],this.clickableElements.forEach(r=>this.listeners.push({element:r,events:{click:n=>((r!==this.element||n.target===this.element||_.elementInside(n.target,this.element.querySelector(".dz-message")))&&this.hiddenFileInput.click(),!0)}})),this.enable(),this.options.init.call(this)}destroy(){return this.disable(),this.removeAllFiles(!0),this.hiddenFileInput!=null&&this.hiddenFileInput.parentNode&&(this.hiddenFileInput.parentNode.removeChild(this.hiddenFileInput),this.hiddenFileInput=null),delete this.element.dropzone,_.instances.splice(_.instances.indexOf(this),1)}updateTotalUploadProgress(){let t,i=0,r=0;if(this.getActiveFiles().length){for(let s of this.getActiveFiles())i+=s.upload.bytesSent,r+=s.upload.total;t=100*i/r}else t=100;return this.emit("totaluploadprogress",t,r,i)}_getParamName(t){return typeof this.options.paramName=="function"?this.options.paramName(t):`${this.options.paramName}${this.options.uploadMultiple?`[${t}]`:""}`}_renameFile(t){return typeof this.options.renameFile!="function"?t.name:this.options.renameFile(t)}getFallbackForm(){let t,i;if(t=this.getExistingFallback())return t;let r='<div class="dz-fallback">';this.options.dictFallbackText&&(r+=`<p>${this.options.dictFallbackText}</p>`),r+=`<input type="file" name="${this._getParamName(0)}" ${this.options.uploadMultiple?'multiple="multiple"':void 0} /><input type="submit" value="Upload!"></div>`;let n=_.createElement(r);return this.element.tagName!=="FORM"?(i=_.createElement(`<form action="${this.options.url}" enctype="multipart/form-data" method="${this.options.method}"></form>`),i.appendChild(n)):(this.element.setAttribute("enctype","multipart/form-data"),this.element.setAttribute("method",this.options.method)),i??n}getExistingFallback(){let t=function(r){for(let n of r)if(/(^| )fallback($| )/.test(n.className))return n};for(let r of["div","form"]){var i;if(i=t(this.element.getElementsByTagName(r)))return i}}setupEventListeners(){return this.listeners.map(t=>(()=>{let i=[];for(let r in t.events){let n=t.events[r];i.push(t.element.addEventListener(r,n,!1))}return i})())}removeEventListeners(){return this.listeners.map(t=>(()=>{let i=[];for(let r in t.events){let n=t.events[r];i.push(t.element.removeEventListener(r,n,!1))}return i})())}disable(){return this.clickableElements.forEach(t=>t.classList.remove("dz-clickable")),this.removeEventListeners(),this.disabled=!0,this.files.map(t=>this.cancelUpload(t))}enable(){return delete this.disabled,this.clickableElements.forEach(t=>t.classList.add("dz-clickable")),this.setupEventListeners()}filesize(t){let i=0,r="b";if(t>0){let n=["tb","gb","mb","kb","b"];for(let s=0;s<n.length;s++){let o=n[s],a=Math.pow(this.options.filesizeBase,4-s)/10;if(t>=a){i=t/Math.pow(this.options.filesizeBase,4-s),r=o;break}}i=Math.round(10*i)/10}return`<strong>${i}</strong> ${this.options.dictFileSizeUnits[r]}`}_updateMaxFilesReachedClass(){return this.options.maxFiles!=null&&this.getAcceptedFiles().length>=this.options.maxFiles?(this.getAcceptedFiles().length===this.options.maxFiles&&this.emit("maxfilesreached",this.files),this.element.classList.add("dz-max-files-reached")):this.element.classList.remove("dz-max-files-reached")}drop(t){if(!t.dataTransfer)return;this.emit("drop",t);let i=[];for(let r=0;r<t.dataTransfer.files.length;r++)i[r]=t.dataTransfer.files[r];if(i.length){let{items:r}=t.dataTransfer;r&&r.length&&r[0].webkitGetAsEntry!=null?this._addFilesFromItems(r):this.handleFiles(i)}this.emit("addedfiles",i)}paste(t){if(im(t!=null?t.clipboardData:void 0,r=>r.items)==null)return;this.emit("paste",t);let{items:i}=t.clipboardData;if(i.length)return this._addFilesFromItems(i)}handleFiles(t){for(let i of t)this.addFile(i)}_addFilesFromItems(t){return(()=>{let i=[];for(let n of t){var r;n.webkitGetAsEntry!=null&&(r=n.webkitGetAsEntry())?r.isFile?i.push(this.addFile(n.getAsFile())):r.isDirectory?i.push(this._addFilesFromDirectory(r,r.name)):i.push(void 0):n.getAsFile!=null&&(n.kind==null||n.kind==="file")?i.push(this.addFile(n.getAsFile())):i.push(void 0)}return i})()}_addFilesFromDirectory(t,i){let r=t.createReader(),n=o=>rm(console,"log",a=>a.log(o));var s=()=>r.readEntries(o=>{if(o.length>0){for(let a of o)a.isFile?a.file(l=>{if(!(this.options.ignoreHiddenFiles&&l.name.substring(0,1)==="."))return l.fullPath=`${i}/${l.name}`,this.addFile(l)}):a.isDirectory&&this._addFilesFromDirectory(a,`${i}/${a.name}`);s()}return null},n);return s()}accept(t,i){this.options.maxFilesize&&t.size>this.options.maxFilesize*1048576?i(this.options.dictFileTooBig.replace("{{filesize}}",Math.round(t.size/1024/10.24)/100).replace("{{maxFilesize}}",this.options.maxFilesize)):_.isValidFile(t,this.options.acceptedFiles)?this.options.maxFiles!=null&&this.getAcceptedFiles().length>=this.options.maxFiles?(i(this.options.dictMaxFilesExceeded.replace("{{maxFiles}}",this.options.maxFiles)),this.emit("maxfilesexceeded",t)):this.options.accept.call(this,t,i):i(this.options.dictInvalidFileType)}addFile(t){t.upload={uuid:_.uuidv4(),progress:0,total:t.size,bytesSent:0,filename:this._renameFile(t)},this.files.push(t),t.status=_.ADDED,this.emit("addedfile",t),this._enqueueThumbnail(t),this.accept(t,i=>{i?(t.accepted=!1,this._errorProcessing([t],i)):(t.accepted=!0,this.options.autoQueue&&this.enqueueFile(t)),this._updateMaxFilesReachedClass()})}enqueueFiles(t){for(let i of t)this.enqueueFile(i);return null}enqueueFile(t){if(t.status===_.ADDED&&t.accepted===!0){if(t.status=_.QUEUED,this.options.autoProcessQueue)return setTimeout(()=>this.processQueue(),0)}else throw new Error("This file can't be queued because it has already been processed or was rejected.")}_enqueueThumbnail(t){if(this.options.createImageThumbnails&&t.type.match(/image.*/)&&t.size<=this.options.maxThumbnailFilesize*1048576)return this._thumbnailQueue.push(t),setTimeout(()=>this._processThumbnailQueue(),0)}_processThumbnailQueue(){if(this._processingThumbnail||this._thumbnailQueue.length===0)return;this._processingThumbnail=!0;let t=this._thumbnailQueue.shift();return this.createThumbnail(t,this.options.thumbnailWidth,this.options.thumbnailHeight,this.options.thumbnailMethod,!0,i=>(this.emit("thumbnail",t,i),this._processingThumbnail=!1,this._processThumbnailQueue()))}removeFile(t){if(t.status===_.UPLOADING&&this.cancelUpload(t),this.files=Yf(this.files,t),this.emit("removedfile",t),this.files.length===0)return this.emit("reset")}removeAllFiles(t){t==null&&(t=!1);for(let i of this.files.slice())(i.status!==_.UPLOADING||t)&&this.removeFile(i);return null}resizeImage(t,i,r,n,s){return this.createThumbnail(t,i,r,n,!0,(o,a)=>{if(a==null)return s(t);{let{resizeMimeType:l}=this.options;l==null&&(l=t.type);let u=a.toDataURL(l,this.options.resizeQuality);return(l==="image/jpeg"||l==="image/jpg")&&(u=$l.restore(t.dataURL,u)),s(_.dataURItoBlob(u))}})}createThumbnail(t,i,r,n,s,o){let a=new FileReader;a.onload=()=>{if(t.dataURL=a.result,t.type==="image/svg+xml"){o!=null&&o(a.result);return}this.createThumbnailFromUrl(t,i,r,n,s,o)},a.readAsDataURL(t)}displayExistingFile(t,i,r,n,s=!0){if(this.emit("addedfile",t),this.emit("complete",t),!s)this.emit("thumbnail",t,i),r&&r();else{let o=a=>{this.emit("thumbnail",t,a),r&&r()};t.dataURL=i,this.createThumbnailFromUrl(t,this.options.thumbnailWidth,this.options.thumbnailHeight,this.options.thumbnailMethod,this.options.fixOrientation,o,n)}}createThumbnailFromUrl(t,i,r,n,s,o,a){let l=document.createElement("img");return a&&(l.crossOrigin=a),s=getComputedStyle(document.body).imageOrientation=="from-image"?!1:s,l.onload=()=>{let u=c=>c(1);return typeof EXIF<"u"&&EXIF!==null&&s&&(u=c=>EXIF.getData(l,function(){return c(EXIF.getTag(this,"Orientation"))})),u(c=>{t.width=l.width,t.height=l.height;let d=this.options.resize.call(this,t,i,r,n),h=document.createElement("canvas"),p=h.getContext("2d");switch(h.width=d.trgWidth,h.height=d.trgHeight,c>4&&(h.width=d.trgHeight,h.height=d.trgWidth),c){case 2:p.translate(h.width,0),p.scale(-1,1);break;case 3:p.translate(h.width,h.height),p.rotate(Math.PI);break;case 4:p.translate(0,h.height),p.scale(1,-1);break;case 5:p.rotate(.5*Math.PI),p.scale(1,-1);break;case 6:p.rotate(.5*Math.PI),p.translate(0,-h.width);break;case 7:p.rotate(.5*Math.PI),p.translate(h.height,-h.width),p.scale(-1,1);break;case 8:p.rotate(-.5*Math.PI),p.translate(-h.height,0);break}em(p,l,d.srcX!=null?d.srcX:0,d.srcY!=null?d.srcY:0,d.srcWidth,d.srcHeight,d.trgX!=null?d.trgX:0,d.trgY!=null?d.trgY:0,d.trgWidth,d.trgHeight);let f=h.toDataURL("image/png");if(o!=null)return o(f,h)})},o!=null&&(l.onerror=o),l.src=t.dataURL}processQueue(){let{parallelUploads:t}=this.options,i=this.getUploadingFiles().length,r=i;if(i>=t)return;let n=this.getQueuedFiles();if(n.length>0){if(this.options.uploadMultiple)return this.processFiles(n.slice(0,t-i));for(;r<t;){if(!n.length)return;this.processFile(n.shift()),r++}}}processFile(t){return this.processFiles([t])}processFiles(t){for(let i of t)i.processing=!0,i.status=_.UPLOADING,this.emit("processing",i);return this.options.uploadMultiple&&this.emit("processingmultiple",t),this.uploadFiles(t)}_getFilesWithXhr(t){return this.files.filter(i=>i.xhr===t).map(i=>i)}cancelUpload(t){if(t.status===_.UPLOADING){let i=this._getFilesWithXhr(t.xhr);for(let r of i)r.status=_.CANCELED;typeof t.xhr<"u"&&t.xhr.abort();for(let r of i)this.emit("canceled",r);this.options.uploadMultiple&&this.emit("canceledmultiple",i)}else(t.status===_.ADDED||t.status===_.QUEUED)&&(t.status=_.CANCELED,this.emit("canceled",t),this.options.uploadMultiple&&this.emit("canceledmultiple",[t]));if(this.options.autoProcessQueue)return this.processQueue()}resolveOption(t,...i){return typeof t=="function"?t.apply(this,i):t}uploadFile(t){return this.uploadFiles([t])}uploadFiles(t){this._transformFiles(t,i=>{if(this.options.chunking){let r=i[0];t[0].upload.chunked=this.options.chunking&&(this.options.forceChunking||r.size>this.options.chunkSize),t[0].upload.totalChunkCount=Math.ceil(r.size/this.options.chunkSize)}if(t[0].upload.chunked){let r=t[0],n=i[0];r.upload.chunks=[];let s=()=>{let o=0;for(;r.upload.chunks[o]!==void 0;)o++;if(o>=r.upload.totalChunkCount)return;let a=o*this.options.chunkSize,l=Math.min(a+this.options.chunkSize,n.size),u={name:this._getParamName(0),data:n.webkitSlice?n.webkitSlice(a,l):n.slice(a,l),filename:r.upload.filename,chunkIndex:o};r.upload.chunks[o]={file:r,index:o,dataBlock:u,status:_.UPLOADING,progress:0,retries:0},this._uploadData(t,[u])};if(r.upload.finishedChunkUpload=(o,a)=>{let l=!0;o.status=_.SUCCESS,o.dataBlock=null,o.response=o.xhr.responseText,o.responseHeaders=o.xhr.getAllResponseHeaders(),o.xhr=null;for(let u=0;u<r.upload.totalChunkCount;u++){if(r.upload.chunks[u]===void 0)return s();r.upload.chunks[u].status!==_.SUCCESS&&(l=!1)}l&&this.options.chunksUploaded(r,()=>{this._finished(t,a,null)})},this.options.parallelChunkUploads)for(let o=0;o<r.upload.totalChunkCount;o++)s();else s()}else{let r=[];for(let n=0;n<t.length;n++)r[n]={name:this._getParamName(n),data:i[n],filename:t[n].upload.filename};this._uploadData(t,r)}})}_getChunk(t,i){for(let r=0;r<t.upload.totalChunkCount;r++)if(t.upload.chunks[r]!==void 0&&t.upload.chunks[r].xhr===i)return t.upload.chunks[r]}_uploadData(t,i){let r=new XMLHttpRequest;for(let u of t)u.xhr=r;t[0].upload.chunked&&(t[0].upload.chunks[i[0].chunkIndex].xhr=r);let n=this.resolveOption(this.options.method,t,i),s=this.resolveOption(this.options.url,t,i);r.open(n,s,!0),this.resolveOption(this.options.timeout,t)&&(r.timeout=this.resolveOption(this.options.timeout,t)),r.withCredentials=!!this.options.withCredentials,r.onload=u=>{this._finishedUploading(t,r,u)},r.ontimeout=()=>{this._handleUploadError(t,r,`Request timedout after ${this.options.timeout/1e3} seconds`)},r.onerror=()=>{this._handleUploadError(t,r)};let a=r.upload!=null?r.upload:r;a.onprogress=u=>this._updateFilesUploadProgress(t,r,u);let l=this.options.defaultHeaders?{Accept:"application/json","Cache-Control":"no-cache","X-Requested-With":"XMLHttpRequest"}:{};this.options.binaryBody&&(l["Content-Type"]=t[0].type),this.options.headers&&so(l,this.options.headers);for(let u in l){let c=l[u];c&&r.setRequestHeader(u,c)}if(this.options.binaryBody){for(let u of t)this.emit("sending",u,r);this.options.uploadMultiple&&this.emit("sendingmultiple",t,r),this.submitRequest(r,null,t)}else{let u=new FormData;if(this.options.params){let c=this.options.params;typeof c=="function"&&(c=c.call(this,t,r,t[0].upload.chunked?this._getChunk(t[0],r):null));for(let d in c){let h=c[d];if(Array.isArray(h))for(let p=0;p<h.length;p++)u.append(d,h[p]);else u.append(d,h)}}for(let c of t)this.emit("sending",c,r,u);this.options.uploadMultiple&&this.emit("sendingmultiple",t,r,u),this._addFormElementData(u);for(let c=0;c<i.length;c++){let d=i[c];u.append(d.name,d.data,d.filename)}this.submitRequest(r,u,t)}}_transformFiles(t,i){let r=[],n=0;for(let s=0;s<t.length;s++)this.options.transformFile.call(this,t[s],o=>{r[s]=o,++n===t.length&&i(r)})}_addFormElementData(t){if(this.element.tagName==="FORM")for(let i of this.element.querySelectorAll("input, textarea, select, button")){let r=i.getAttribute("name"),n=i.getAttribute("type");if(n&&(n=n.toLowerCase()),!(typeof r>"u"||r===null))if(i.tagName==="SELECT"&&i.hasAttribute("multiple"))for(let s of i.options)s.selected&&t.append(r,s.value);else(!n||n!=="checkbox"&&n!=="radio"||i.checked)&&t.append(r,i.value)}}_updateFilesUploadProgress(t,i,r){if(t[0].upload.chunked){let n=t[0],s=this._getChunk(n,i);r?(s.progress=100*r.loaded/r.total,s.total=r.total,s.bytesSent=r.loaded):(s.progress=100,s.bytesSent=s.total),n.upload.progress=0,n.upload.total=0,n.upload.bytesSent=0;for(let o=0;o<n.upload.totalChunkCount;o++)n.upload.chunks[o]&&typeof n.upload.chunks[o].progress<"u"&&(n.upload.progress+=n.upload.chunks[o].progress,n.upload.total+=n.upload.chunks[o].total,n.upload.bytesSent+=n.upload.chunks[o].bytesSent);n.upload.progress=n.upload.progress/n.upload.totalChunkCount,this.emit("uploadprogress",n,n.upload.progress,n.upload.bytesSent)}else for(let n of t)n.upload.total&&n.upload.bytesSent&&n.upload.bytesSent==n.upload.total||(r?(n.upload.progress=100*r.loaded/r.total,n.upload.total=r.total,n.upload.bytesSent=r.loaded):(n.upload.progress=100,n.upload.bytesSent=n.upload.total),this.emit("uploadprogress",n,n.upload.progress,n.upload.bytesSent))}_finishedUploading(t,i,r){let n;if(t[0].status!==_.CANCELED&&i.readyState===4){if(i.responseType!=="arraybuffer"&&i.responseType!=="blob"&&(n=i.responseText,i.getResponseHeader("content-type")&&~i.getResponseHeader("content-type").indexOf("application/json")))try{n=JSON.parse(n)}catch(s){r=s,n="Invalid JSON response from server."}this._updateFilesUploadProgress(t,i),200<=i.status&&i.status<300?t[0].upload.chunked?t[0].upload.finishedChunkUpload(this._getChunk(t[0],i),n):this._finished(t,n,r):this._handleUploadError(t,i,n)}}_handleUploadError(t,i,r){if(t[0].status!==_.CANCELED){if(t[0].upload.chunked&&this.options.retryChunks){let n=this._getChunk(t[0],i);if(n.retries++<this.options.retryChunksLimit){this._uploadData(t,[n.dataBlock]);return}else console.warn("Retried this chunk too often. Giving up.")}this._errorProcessing(t,r||this.options.dictResponseError.replace("{{statusCode}}",i.status),i)}}submitRequest(t,i,r){if(t.readyState!=1){console.warn("Cannot send this request because the XMLHttpRequest.readyState is not OPENED.");return}if(this.options.binaryBody)if(r[0].upload.chunked){const n=this._getChunk(r[0],t);t.send(n.dataBlock.data)}else t.send(r[0]);else t.send(i)}_finished(t,i,r){for(let n of t)n.status=_.SUCCESS,this.emit("success",n,i,r),this.emit("complete",n);if(this.options.uploadMultiple&&(this.emit("successmultiple",t,i,r),this.emit("completemultiple",t)),this.options.autoProcessQueue)return this.processQueue()}_errorProcessing(t,i,r){for(let n of t)n.status=_.ERROR,this.emit("error",n,i,r),this.emit("complete",n);if(this.options.uploadMultiple&&(this.emit("errormultiple",t,i,r),this.emit("completemultiple",t)),this.options.autoProcessQueue)return this.processQueue()}static uuidv4(){return"xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g,function(t){let i=Math.random()*16|0;return(t==="x"?i:i&3|8).toString(16)})}constructor(t,i){super();let r,n;if(this.element=t,this.clickableElements=[],this.listeners=[],this.files=[],typeof this.element=="string"&&(this.element=document.querySelector(this.element)),!this.element||this.element.nodeType==null)throw new Error("Invalid dropzone element.");if(this.element.dropzone)throw new Error("Dropzone already attached.");_.instances.push(this),this.element.dropzone=this;let s=(n=_.optionsForElement(this.element))!=null?n:{};if(this.options=so(!0,{},Gf,s,i??{}),this.options.previewTemplate=this.options.previewTemplate.replace(/\n*/g,""),this.options.forceFallback||!_.isBrowserSupported())return this.options.fallback.call(this);if(this.options.url==null&&(this.options.url=this.element.getAttribute("action")),!this.options.url)throw new Error("No URL provided.");if(this.options.acceptedFiles&&this.options.acceptedMimeTypes)throw new Error("You can't provide both 'acceptedFiles' and 'acceptedMimeTypes'. 'acceptedMimeTypes' is deprecated.");if(this.options.uploadMultiple&&this.options.chunking)throw new Error("You cannot set both: uploadMultiple and chunking.");if(this.options.binaryBody&&this.options.uploadMultiple)throw new Error("You cannot set both: binaryBody and uploadMultiple.");this.options.acceptedMimeTypes&&(this.options.acceptedFiles=this.options.acceptedMimeTypes,delete this.options.acceptedMimeTypes),this.options.renameFilename!=null&&(this.options.renameFile=o=>this.options.renameFilename.call(this,o.name,o)),typeof this.options.method=="string"&&(this.options.method=this.options.method.toUpperCase()),(r=this.getExistingFallback())&&r.parentNode&&r.parentNode.removeChild(r),this.options.previewsContainer!==!1&&(this.options.previewsContainer?this.previewsContainer=_.getElement(this.options.previewsContainer,"previewsContainer"):this.previewsContainer=this.element),this.options.clickable&&(this.options.clickable===!0?this.clickableElements=[this.element]:this.clickableElements=_.getElements(this.options.clickable,"clickable")),this.init()}}_.initClass();_.options={};_.optionsForElement=function(e){if(e.getAttribute("id"))return _.options[Zf(e.getAttribute("id"))]};_.instances=[];_.forElement=function(e){if(typeof e=="string"&&(e=document.querySelector(e)),(e!=null?e.dropzone:void 0)==null)throw new Error("No Dropzone found for given element. This is probably because you're trying to access it before Dropzone had the time to initialize. Use the `init` option to setup any additional observers on your Dropzone.");return e.dropzone};_.discover=function(){let e;if(document.querySelectorAll)e=document.querySelectorAll(".dropzone");else{e=[];let t=i=>(()=>{let r=[];for(let n of i)/(^| )dropzone($| )/.test(n.className)?r.push(e.push(n)):r.push(void 0);return r})();t(document.getElementsByTagName("div")),t(document.getElementsByTagName("form"))}return(()=>{let t=[];for(let i of e)_.optionsForElement(i)!==!1?t.push(new _(i)):t.push(void 0);return t})()};_.blockedBrowsers=[/opera.*(Macintosh|Windows Phone).*version\/12/i];_.isBrowserSupported=function(){let e=!0;if(window.File&&window.FileReader&&window.FileList&&window.Blob&&window.FormData&&document.querySelector)if(!("classList"in document.createElement("a")))e=!1;else{_.blacklistedBrowsers!==void 0&&(_.blockedBrowsers=_.blacklistedBrowsers);for(let t of _.blockedBrowsers)if(t.test(navigator.userAgent)){e=!1;continue}}else e=!1;return e};_.dataURItoBlob=function(e){let t=atob(e.split(",")[1]),i=e.split(",")[0].split(":")[1].split(";")[0],r=new ArrayBuffer(t.length),n=new Uint8Array(r);for(let s=0,o=t.length,a=0<=o;a?s<=o:s>=o;a?s++:s--)n[s]=t.charCodeAt(s);return new Blob([r],{type:i})};const Yf=(e,t)=>e.filter(i=>i!==t).map(i=>i),Zf=e=>e.replace(/[\-_](\w)/g,t=>t.charAt(1).toUpperCase());_.createElement=function(e){let t=document.createElement("div");return t.innerHTML=e,t.childNodes[0]};_.elementInside=function(e,t){if(e===t)return!0;for(;e=e.parentNode;)if(e===t)return!0;return!1};_.getElement=function(e,t){let i;if(typeof e=="string"?i=document.querySelector(e):e.nodeType!=null&&(i=e),i==null)throw new Error(`Invalid \`${t}\` option provided. Please provide a CSS selector or a plain HTML element.`);return i};_.getElements=function(e,t){let i,r;if(e instanceof Array){r=[];try{for(i of e)r.push(this.getElement(i,t))}catch{r=null}}else if(typeof e=="string"){r=[];for(i of document.querySelectorAll(e))r.push(i)}else e.nodeType!=null&&(r=[e]);if(r==null||!r.length)throw new Error(`Invalid \`${t}\` option provided. Please provide a CSS selector, a plain HTML element or a list of those.`);return r};_.confirm=function(e,t,i){if(window.confirm(e))return t();if(i!=null)return i()};_.isValidFile=function(e,t){if(!t)return!0;t=t.split(",");let i=e.type,r=i.replace(/\/.*$/,"");for(let n of t)if(n=n.trim(),n.charAt(0)==="."){if(e.name.toLowerCase().indexOf(n.toLowerCase(),e.name.length-n.length)!==-1)return!0}else if(/\/\*$/.test(n)){if(r===n.replace(/\/.*$/,""))return!0}else if(i===n)return!0;return!1};typeof jQuery<"u"&&jQuery!==null&&(jQuery.fn.dropzone=function(e){return this.each(function(){return new _(this,e)})});_.ADDED="added";_.QUEUED="queued";_.ACCEPTED=_.QUEUED;_.UPLOADING="uploading";_.PROCESSING=_.UPLOADING;_.CANCELED="canceled";_.ERROR="error";_.SUCCESS="success";let tm=function(e){e.naturalWidth;let t=e.naturalHeight,i=document.createElement("canvas");i.width=1,i.height=t;let r=i.getContext("2d");r.drawImage(e,0,0);let{data:n}=r.getImageData(1,0,1,t),s=0,o=t,a=t;for(;a>s;)n[(a-1)*4+3]===0?o=a:s=a,a=o+s>>1;let l=a/t;return l===0?1:l};var em=function(e,t,i,r,n,s,o,a,l,u){let c=tm(t);return e.drawImage(t,i,r,n,s,o,a,l,u/c)};class $l{static initClass(){this.KEY_STR="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/="}static encode64(t){let i="",r,n,s="",o,a,l,u="",c=0;for(;r=t[c++],n=t[c++],s=t[c++],o=r>>2,a=(r&3)<<4|n>>4,l=(n&15)<<2|s>>6,u=s&63,isNaN(n)?l=u=64:isNaN(s)&&(u=64),i=i+this.KEY_STR.charAt(o)+this.KEY_STR.charAt(a)+this.KEY_STR.charAt(l)+this.KEY_STR.charAt(u),r=n=s="",o=a=l=u="",c<t.length;);return i}static restore(t,i){if(!t.match("data:image/jpeg;base64,"))return i;let r=this.decode64(t.replace("data:image/jpeg;base64,","")),n=this.slice2Segments(r),s=this.exifManipulation(i,n);return`data:image/jpeg;base64,${this.encode64(s)}`}static exifManipulation(t,i){let r=this.getExifArray(i),n=this.insertExif(t,r);return new Uint8Array(n)}static getExifArray(t){let i,r=0;for(;r<t.length;){if(i=t[r],i[0]===255&i[1]===225)return i;r++}return[]}static insertExif(t,i){let r=t.replace("data:image/jpeg;base64,",""),n=this.decode64(r),s=n.indexOf(255,3),o=n.slice(0,s),a=n.slice(s),l=o;return l=l.concat(i),l=l.concat(a),l}static slice2Segments(t){let i=0,r=[];for(;;){var n;if(t[i]===255&t[i+1]===218)break;if(t[i]===255&t[i+1]===216)i+=2;else{n=t[i+2]*256+t[i+3];let s=i+n+2,o=t.slice(i,s);r.push(o),i=s}if(i>t.length)break}return r}static decode64(t){let i,r,n="",s,o,a,l="",u=0,c=[];for(/[^A-Za-z0-9\+\/\=]/g.exec(t)&&console.warn(`There were invalid base64 characters in the input text.
Valid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='
Expect errors in decoding.`),t=t.replace(/[^A-Za-z0-9\+\/\=]/g,"");s=this.KEY_STR.indexOf(t.charAt(u++)),o=this.KEY_STR.indexOf(t.charAt(u++)),a=this.KEY_STR.indexOf(t.charAt(u++)),l=this.KEY_STR.indexOf(t.charAt(u++)),i=s<<2|o>>4,r=(o&15)<<4|a>>2,n=(a&3)<<6|l,c.push(i),a!==64&&c.push(r),l!==64&&c.push(n),i=r=n="",s=o=a=l="",u<t.length;);return c}}$l.initClass();function im(e,t){return typeof e<"u"&&e!==null?t(e):void 0}function rm(e,t,i){if(typeof e<"u"&&e!==null&&typeof e[t]=="function")return i(e,t)}window.Alpine=jn;jn.plugin(Kf);jn.start();_.autoDiscover=!1;const lo=document.getElementById("file-upload-dropzone"),kr=document.getElementById("messageForm"),Ar=document.getElementById("message"),Wt=document.getElementById("file_upload_ids");if(lo&&kr&&Ar&&Wt){const e=document.querySelector('meta[name="csrf-token"]').getAttribute("content"),t=lo.dataset.uploadUrl;if(!t)console.error("Dropzone element is missing the data-upload-url attribute!");else{let r=function(){const h=document.getElementById("upload-progress-overlay");h&&(h.classList.remove("hidden"),h.classList.add("flex"))},n=function(){const h=document.getElementById("upload-progress-overlay");h&&(h.classList.add("hidden"),h.classList.remove("flex"))},s=function(h,p){const f=document.getElementById("file-progress-container");if(!f)return;let v=document.getElementById(`progress-${h.upload.uuid}`);if(!v)v=document.createElement("div"),v.id=`progress-${h.upload.uuid}`,v.className="mb-3 last:mb-0",v.innerHTML=`
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700 truncate max-w-xs" title="${h.name}">${h.name}</span>
                        <span class="text-sm text-gray-500">${Math.round(p)}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300 ease-out" style="width: ${p}%"></div>
                    </div>
                `,f.appendChild(v);else{const m=v.querySelector(".text-gray-500"),w=v.querySelector(".bg-blue-600");m&&(m.textContent=`${Math.round(p)}%`),w&&(w.style.width=`${p}%`)}},o=function(){const h=i.files;if(h.length===0)return;let p=0,f=0,v=0;h.forEach(S=>{S.status===_.SUCCESS?(p+=100,f++):S.status===_.UPLOADING?p+=S.upload.progress||0:S.status===_.ERROR&&(v++,f++)});const m=p/h.length,w=document.getElementById("overall-progress-bar"),x=document.getElementById("overall-progress-text"),E=document.getElementById("progress-status");if(w&&(w.style.width=`${m}%`,v>0&&f===h.length?(w.classList.remove("bg-blue-600","bg-green-600"),w.classList.add("bg-yellow-600")):f===h.length&&v===0&&(w.classList.remove("bg-blue-600","bg-yellow-600"),w.classList.add("bg-green-600"))),x&&(x.textContent=`${Math.round(m)}%`),E)if(f===h.length)v>0?E.textContent=`Upload completed with ${v} error${v>1?"s":""}`:E.textContent="Processing uploads...";else{const S=h.length-f;E.textContent=`Uploading ${S} of ${h.length} files...`}},a=function(h,p=!0){const f=document.getElementById(`progress-${h.upload.uuid}`);if(!f)return;const v=f.querySelector(".bg-blue-600"),m=f.querySelector(".text-gray-500");p?(v&&(v.classList.remove("bg-blue-600"),v.classList.add("bg-green-600"),v.style.width="100%"),m&&(m.textContent="✓ Complete",m.classList.remove("text-gray-500"),m.classList.add("text-green-600"))):(v&&(v.classList.remove("bg-blue-600"),v.classList.add("bg-red-600")),m&&(m.textContent="✗ Failed",m.classList.remove("text-gray-500"),m.classList.add("text-red-600")))},l=function(){const h=document.getElementById("file-progress-container");h&&(h.innerHTML="");const p=document.getElementById("overall-progress-bar");p&&(p.style.width="0%",p.classList.remove("bg-green-600","bg-yellow-600"),p.classList.add("bg-blue-600"));const f=document.getElementById("overall-progress-text");f&&(f.textContent="0%");const v=document.getElementById("progress-status");v&&(v.textContent="Preparing upload...")},c=function(h){u=h,h?window.addEventListener("beforeunload",d):window.removeEventListener("beforeunload",d)},d=function(h){if(u){const p="Files are currently uploading. Are you sure you want to leave?";return h.preventDefault(),h.returnValue=p,p}};var Fm=r,Lm=n,Mm=s,Pm=o,zm=a,Im=l,Bm=c,Dm=d;const i=new _("#file-upload-dropzone",{url:t,paramName:"file",maxFilesize:5e3,chunking:!0,forceChunking:!0,chunkSize:5242880,retryChunks:!0,retryChunksLimit:3,parallelChunkUploads:!1,addRemoveLinks:!0,autoProcessQueue:!1,headers:{"X-CSRF-TOKEN":e},params:function(h,p,f){const v={};f&&(v.dzuuid=f.file.upload.uuid,v.dzchunkindex=f.index,v.dztotalfilesize=f.file.size,v.dzchunksize=this.options.chunkSize,v.dztotalchunkcount=f.file.upload.totalChunkCount,v.dzchunkbyteoffset=f.index*this.options.chunkSize);const m=document.getElementById("company_user_id");return m&&m.value&&(v.company_user_id=m.value),v},uploadprogress:function(h,p,f){s(h,p),o()},success:function(h,p){if(console.log(`Success callback for ${h.name}:`,p),p&&p.file_upload_id){if(console.log(`Final FileUpload ID for ${h.name}: ${p.file_upload_id}`),!h.finalIdReceived){h.finalIdReceived=!0,h.file_upload_id=p.file_upload_id,a(h,!0);let f=Wt.value?JSON.parse(Wt.value):[];f.includes(p.file_upload_id)||(f.push(p.file_upload_id),Wt.value=JSON.stringify(f),console.log("Updated file_upload_ids:",Wt.value))}}else console.log(`Received intermediate chunk success for ${h.name}`)},error:function(h,p,f){console.error("Error uploading file chunk:",h.name,p,f),a(h,!1);const v=document.getElementById("upload-errors");if(v){const m=typeof p=="object"?p.error||JSON.stringify(p):p;v.innerHTML+=`<p class="text-red-500">Error uploading ${h.name}: ${m}</p>`,v.classList.remove("hidden")}},complete:function(h){console.log("File processing complete (success or error): ",h.name),i.processQueue()}});let u=!1;kr.addEventListener("submit",function(h){h.preventDefault();const p=this.querySelector('button[type="submit"]'),f=i.getQueuedFiles(),v=i.getFilesWithStatus(_.UPLOADING),m=i.getFilesWithStatus(_.SUCCESS).length+i.getFilesWithStatus(_.ERROR).length;console.log(`Submit triggered. Queued: ${f.length}, InProgress: ${v.length}, Done: ${m}`),f.length>0?(console.log("Starting file uploads for queue..."),p.disabled=!0,p.textContent="Uploading Files...",l(),r(),c(!0),i.processQueue()):i.getFilesWithStatus(_.SUCCESS).length>0?(console.log("Files already uploaded, attempting to associate message via queuecomplete."),console.log("Submit triggered, but files seem already uploaded."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-error"}))):(console.log("Submit triggered, but no files added."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"no-files-error"})))}),i.on("queuecomplete",function(){const h=i.getFilesWithStatus(_.SUCCESS).length+i.getFilesWithStatus(_.ERROR).length,p=i.files.length;console.log(`--- Queue Complete Fired --- Processed: ${h}, Total Added: ${p}`);const f=kr.querySelector('button[type="submit"]'),v=Ar.value,w=i.getFilesWithStatus(_.SUCCESS).map(x=>x.file_upload_id).filter(x=>x);if(console.log("Queue complete. Message:",v),console.log("Queue complete. Successful file IDs:",w),v&&w.length>0){console.log("Attempting to associate message..."),f.textContent="Associating Message...";const x=document.getElementById("progress-status");x&&(x.textContent="Associating message with uploaded files...");const E=window.employeeUploadConfig?window.employeeUploadConfig.associateMessageUrl:"/client/uploads/associate-message";fetch(E,{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":e},body:JSON.stringify({message:v,file_upload_ids:w})}).then(S=>{if(!S.ok)throw S.text().then(C=>{console.error("Error response from associate-message:",S.status,C)}),new Error(`HTTP error! status: ${S.status}`);return S.json()}).then(S=>{console.log("Message associated successfully:",S),Ar.value="",Wt.value="[]",i.removeAllFiles(!0),n(),window.dispatchEvent(new CustomEvent("open-modal",{detail:"association-success"}))}).catch(S=>{console.error("Error associating message:",S),window.dispatchEvent(new CustomEvent("open-modal",{detail:"association-error"}))}).finally(()=>{f.disabled=!1,f.textContent="Upload and Send Message",c(!1)})}else if(w.length>0&&!v){console.log("Batch upload complete without message. Successful IDs:",w),console.log("Calling /api/uploads/batch-complete..."),f.textContent="Finalizing Upload...",f.disabled=!0;const x=document.getElementById("progress-status");x&&(x.textContent="Finalizing upload and sending notifications...");const E=window.employeeUploadConfig?window.employeeUploadConfig.batchCompleteUrl:"/client/uploads/batch-complete";fetch(E,{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":e},body:JSON.stringify({file_upload_ids:w})}).then(S=>{if(!S.ok)throw console.error("Error response from batch-complete endpoint:",S.status),S.text().then(C=>console.error("Batch Complete Error Body:",C)),new Error(`HTTP error! status: ${S.status}`);return S.json()}).then(S=>{console.log("Backend acknowledged batch completion:",S),console.log("Dispatching upload-success modal..."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-success"})),console.log("Attempting to clear Dropzone UI..."),i.removeAllFiles(!0),console.log("Dropzone UI should be cleared now."),console.log("Attempting to clear file IDs input..."),Wt.value="[]",console.log("File IDs input cleared."),n(),c(!1)}).catch(S=>{console.error("Error calling batch-complete endpoint:",S),window.dispatchEvent(new CustomEvent("open-modal",{detail:"association-error"}))}).finally(()=>{f.disabled=!1,f.textContent="Upload and Send Message",c(!1),i.getRejectedFiles().length>0&&(console.log("Found rejected files, dispatching upload-error modal as well."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-error"})))})}else console.log("Queue finished, but no successful uploads or handling other cases."),w.length===0&&(f.disabled=!1,f.textContent="Upload and Send Message",i.getRejectedFiles().length>0&&window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-error"})))})}}const nm=window.location.hostname;document.querySelectorAll('a[href^="http"]:not([href*="'+nm+'"]):not([href^="#"]):not(.button-link)').forEach(e=>{e.querySelector(".external-link-icon")||(e.innerHTML+='<svg class="external-link-icon" xmlns="http://www.w3.org/2000/svg" baseProfile="tiny" version="1.2" viewBox="0 0 79 79"><path d="M64,39.8v34.7c0,2.5-2,4.5-4.5,4.5H4.5c-2.5,0-4.5-2-4.5-4.5V19.5c0-2.5,2-4.5,4.5-4.5h35.6c2.5,0,4.5,2,4.5,4.5s-.5,2.4-1.4,3.3c-.8.8-1.9,1.3-3.1,1.3H9v45.9h45.9v-30.2c0-1.3.5-2.4,1.4-3.3.8-.8,1.9-1.3,3.1-1.3,2.5,0,4.5,2,4.5,4.5h0Z"/><path d="M74.5,0h-28.7c-2.2,0-4.2,1.5-4.6,3.6s1.6,5.5,4.4,5.5h17.9l-31.5,31.6c-1.8,1.8-1.8,4.7,0,6.5h0c1.7,1.8,4.6,1.8,6.3,0l31.6-31.6v17.7c0,2.2,1.5,4.2,3.6,4.6s5.5-1.6,5.5-4.4V4.7c0-2.5-2-4.5-4.5-4.5h0v-.2Z"/></svg>')})});export default sm();
