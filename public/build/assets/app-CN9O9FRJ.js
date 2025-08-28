var Yo=(e,t)=>()=>(t||e((t={exports:{}}).exports,t),t.exports);import{i as Qe,c as tt,S as it,x as B,u as qn,e as qs,a as js,t as Ie,f as Zo,T as yt,E as ea,m as ta,b as Vs,_ as b,d as ie,r as me,n as _,w as Xe,F as Ws,H as ia,L as ri,g as Ce,o as $,h as na,j as sa,k as ra,s as Ks,l as jn,p as Vn,q as Wn,v as Kn,y as oa}from"./chunk.SBCFYC2S-DMThxOYY.js";var Ph=Yo((np,Xt)=>{function Qs(e,t){return function(){return e.apply(t,arguments)}}const{toString:aa}=Object.prototype,{getPrototypeOf:pn}=Object,{iterator:oi,toStringTag:Xs}=Symbol,ai=(e=>t=>{const i=aa.call(t);return e[i]||(e[i]=i.slice(8,-1).toLowerCase())})(Object.create(null)),le=e=>(e=e.toLowerCase(),t=>ai(t)===e),li=e=>t=>typeof t===e,{isArray:nt}=Array,xt=li("undefined");function kt(e){return e!==null&&!xt(e)&&e.constructor!==null&&!xt(e.constructor)&&K(e.constructor.isBuffer)&&e.constructor.isBuffer(e)}const Js=le("ArrayBuffer");function la(e){let t;return typeof ArrayBuffer<"u"&&ArrayBuffer.isView?t=ArrayBuffer.isView(e):t=e&&e.buffer&&Js(e.buffer),t}const ua=li("string"),K=li("function"),Gs=li("number"),Ct=e=>e!==null&&typeof e=="object",ca=e=>e===!0||e===!1,qt=e=>{if(ai(e)!=="object")return!1;const t=pn(e);return(t===null||t===Object.prototype||Object.getPrototypeOf(t)===null)&&!(Xs in e)&&!(oi in e)},da=e=>{if(!Ct(e)||kt(e))return!1;try{return Object.keys(e).length===0&&Object.getPrototypeOf(e)===Object.prototype}catch{return!1}},ha=le("Date"),pa=le("File"),fa=le("Blob"),ma=le("FileList"),ga=e=>Ct(e)&&K(e.pipe),ba=e=>{let t;return e&&(typeof FormData=="function"&&e instanceof FormData||K(e.append)&&((t=ai(e))==="formdata"||t==="object"&&K(e.toString)&&e.toString()==="[object FormData]"))},ya=le("URLSearchParams"),[va,wa,_a,xa]=["ReadableStream","Request","Response","Headers"].map(le),Ea=e=>e.trim?e.trim():e.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,"");function At(e,t,{allOwnKeys:i=!1}={}){if(e===null||typeof e>"u")return;let n,s;if(typeof e!="object"&&(e=[e]),nt(e))for(n=0,s=e.length;n<s;n++)t.call(null,e[n],n,e);else{if(kt(e))return;const r=i?Object.getOwnPropertyNames(e):Object.keys(e),o=r.length;let a;for(n=0;n<o;n++)a=r[n],t.call(null,e[a],a,e)}}function Ys(e,t){if(kt(e))return null;t=t.toLowerCase();const i=Object.keys(e);let n=i.length,s;for(;n-- >0;)if(s=i[n],t===s.toLowerCase())return s;return null}const $e=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:global,Zs=e=>!xt(e)&&e!==$e;function Di(){const{caseless:e}=Zs(this)&&this||{},t={},i=(n,s)=>{const r=e&&Ys(t,s)||s;qt(t[r])&&qt(n)?t[r]=Di(t[r],n):qt(n)?t[r]=Di({},n):nt(n)?t[r]=n.slice():t[r]=n};for(let n=0,s=arguments.length;n<s;n++)arguments[n]&&At(arguments[n],i);return t}const Sa=(e,t,i,{allOwnKeys:n}={})=>(At(t,(s,r)=>{i&&K(s)?e[r]=Qs(s,i):e[r]=s},{allOwnKeys:n}),e),ka=e=>(e.charCodeAt(0)===65279&&(e=e.slice(1)),e),Ca=(e,t,i,n)=>{e.prototype=Object.create(t.prototype,n),e.prototype.constructor=e,Object.defineProperty(e,"super",{value:t.prototype}),i&&Object.assign(e.prototype,i)},Aa=(e,t,i,n)=>{let s,r,o;const a={};if(t=t||{},e==null)return t;do{for(s=Object.getOwnPropertyNames(e),r=s.length;r-- >0;)o=s[r],(!n||n(o,e,t))&&!a[o]&&(t[o]=e[o],a[o]=!0);e=i!==!1&&pn(e)}while(e&&(!i||i(e,t))&&e!==Object.prototype);return t},Ta=(e,t,i)=>{e=String(e),(i===void 0||i>e.length)&&(i=e.length),i-=t.length;const n=e.indexOf(t,i);return n!==-1&&n===i},Ra=e=>{if(!e)return null;if(nt(e))return e;let t=e.length;if(!Gs(t))return null;const i=new Array(t);for(;t-- >0;)i[t]=e[t];return i},Fa=(e=>t=>e&&t instanceof e)(typeof Uint8Array<"u"&&pn(Uint8Array)),Oa=(e,t)=>{const n=(e&&e[oi]).call(e);let s;for(;(s=n.next())&&!s.done;){const r=s.value;t.call(e,r[0],r[1])}},La=(e,t)=>{let i;const n=[];for(;(i=e.exec(t))!==null;)n.push(i);return n},Ma=le("HTMLFormElement"),Pa=e=>e.toLowerCase().replace(/[-_\s]([a-z\d])(\w*)/g,function(i,n,s){return n.toUpperCase()+s}),Qn=(({hasOwnProperty:e})=>(t,i)=>e.call(t,i))(Object.prototype),Ia=le("RegExp"),er=(e,t)=>{const i=Object.getOwnPropertyDescriptors(e),n={};At(i,(s,r)=>{let o;(o=t(s,r,e))!==!1&&(n[r]=o||s)}),Object.defineProperties(e,n)},za=e=>{er(e,(t,i)=>{if(K(e)&&["arguments","caller","callee"].indexOf(i)!==-1)return!1;const n=e[i];if(K(n)){if(t.enumerable=!1,"writable"in t){t.writable=!1;return}t.set||(t.set=()=>{throw Error("Can not rewrite read-only method '"+i+"'")})}})},$a=(e,t)=>{const i={},n=s=>{s.forEach(r=>{i[r]=!0})};return nt(e)?n(e):n(String(e).split(t)),i},Ba=()=>{},Da=(e,t)=>e!=null&&Number.isFinite(e=+e)?e:t;function Na(e){return!!(e&&K(e.append)&&e[Xs]==="FormData"&&e[oi])}const Ua=e=>{const t=new Array(10),i=(n,s)=>{if(Ct(n)){if(t.indexOf(n)>=0)return;if(kt(n))return n;if(!("toJSON"in n)){t[s]=n;const r=nt(n)?[]:{};return At(n,(o,a)=>{const l=i(o,s+1);!xt(l)&&(r[a]=l)}),t[s]=void 0,r}}return n};return i(e,0)},Ha=le("AsyncFunction"),qa=e=>e&&(Ct(e)||K(e))&&K(e.then)&&K(e.catch),tr=((e,t)=>e?setImmediate:t?((i,n)=>($e.addEventListener("message",({source:s,data:r})=>{s===$e&&r===i&&n.length&&n.shift()()},!1),s=>{n.push(s),$e.postMessage(i,"*")}))(`axios@${Math.random()}`,[]):i=>setTimeout(i))(typeof setImmediate=="function",K($e.postMessage)),ja=typeof queueMicrotask<"u"?queueMicrotask.bind($e):typeof process<"u"&&process.nextTick||tr,Va=e=>e!=null&&K(e[oi]),h={isArray:nt,isArrayBuffer:Js,isBuffer:kt,isFormData:ba,isArrayBufferView:la,isString:ua,isNumber:Gs,isBoolean:ca,isObject:Ct,isPlainObject:qt,isEmptyObject:da,isReadableStream:va,isRequest:wa,isResponse:_a,isHeaders:xa,isUndefined:xt,isDate:ha,isFile:pa,isBlob:fa,isRegExp:Ia,isFunction:K,isStream:ga,isURLSearchParams:ya,isTypedArray:Fa,isFileList:ma,forEach:At,merge:Di,extend:Sa,trim:Ea,stripBOM:ka,inherits:Ca,toFlatObject:Aa,kindOf:ai,kindOfTest:le,endsWith:Ta,toArray:Ra,forEachEntry:Oa,matchAll:La,isHTMLForm:Ma,hasOwnProperty:Qn,hasOwnProp:Qn,reduceDescriptors:er,freezeMethods:za,toObjectSet:$a,toCamelCase:Pa,noop:Ba,toFiniteNumber:Da,findKey:Ys,global:$e,isContextDefined:Zs,isSpecCompliantForm:Na,toJSONObject:Ua,isAsyncFn:Ha,isThenable:qa,setImmediate:tr,asap:ja,isIterable:Va};function k(e,t,i,n,s){Error.call(this),Error.captureStackTrace?Error.captureStackTrace(this,this.constructor):this.stack=new Error().stack,this.message=e,this.name="AxiosError",t&&(this.code=t),i&&(this.config=i),n&&(this.request=n),s&&(this.response=s,this.status=s.status?s.status:null)}h.inherits(k,Error,{toJSON:function(){return{message:this.message,name:this.name,description:this.description,number:this.number,fileName:this.fileName,lineNumber:this.lineNumber,columnNumber:this.columnNumber,stack:this.stack,config:h.toJSONObject(this.config),code:this.code,status:this.status}}});const ir=k.prototype,nr={};["ERR_BAD_OPTION_VALUE","ERR_BAD_OPTION","ECONNABORTED","ETIMEDOUT","ERR_NETWORK","ERR_FR_TOO_MANY_REDIRECTS","ERR_DEPRECATED","ERR_BAD_RESPONSE","ERR_BAD_REQUEST","ERR_CANCELED","ERR_NOT_SUPPORT","ERR_INVALID_URL"].forEach(e=>{nr[e]={value:e}});Object.defineProperties(k,nr);Object.defineProperty(ir,"isAxiosError",{value:!0});k.from=(e,t,i,n,s,r)=>{const o=Object.create(ir);return h.toFlatObject(e,o,function(l){return l!==Error.prototype},a=>a!=="isAxiosError"),k.call(o,e.message,t,i,n,s),o.cause=e,o.name=e.name,r&&Object.assign(o,r),o};const Wa=null;function Ni(e){return h.isPlainObject(e)||h.isArray(e)}function sr(e){return h.endsWith(e,"[]")?e.slice(0,-2):e}function Xn(e,t,i){return e?e.concat(t).map(function(s,r){return s=sr(s),!i&&r?"["+s+"]":s}).join(i?".":""):t}function Ka(e){return h.isArray(e)&&!e.some(Ni)}const Qa=h.toFlatObject(h,{},null,function(t){return/^is[A-Z]/.test(t)});function ui(e,t,i){if(!h.isObject(e))throw new TypeError("target must be an object");t=t||new FormData,i=h.toFlatObject(i,{metaTokens:!0,dots:!1,indexes:!1},!1,function(v,p){return!h.isUndefined(p[v])});const n=i.metaTokens,s=i.visitor||c,r=i.dots,o=i.indexes,l=(i.Blob||typeof Blob<"u"&&Blob)&&h.isSpecCompliantForm(t);if(!h.isFunction(s))throw new TypeError("visitor must be a function");function u(g){if(g===null)return"";if(h.isDate(g))return g.toISOString();if(h.isBoolean(g))return g.toString();if(!l&&h.isBlob(g))throw new k("Blob is not supported. Use a Buffer instead.");return h.isArrayBuffer(g)||h.isTypedArray(g)?l&&typeof Blob=="function"?new Blob([g]):Buffer.from(g):g}function c(g,v,p){let w=g;if(g&&!p&&typeof g=="object"){if(h.endsWith(v,"{}"))v=n?v:v.slice(0,-2),g=JSON.stringify(g);else if(h.isArray(g)&&Ka(g)||(h.isFileList(g)||h.endsWith(v,"[]"))&&(w=h.toArray(g)))return v=sr(v),w.forEach(function(E,C){!(h.isUndefined(E)||E===null)&&t.append(o===!0?Xn([v],C,r):o===null?v:v+"[]",u(E))}),!1}return Ni(g)?!0:(t.append(Xn(p,v,r),u(g)),!1)}const d=[],f=Object.assign(Qa,{defaultVisitor:c,convertValue:u,isVisitable:Ni});function m(g,v){if(!h.isUndefined(g)){if(d.indexOf(g)!==-1)throw Error("Circular reference detected in "+v.join("."));d.push(g),h.forEach(g,function(w,x){(!(h.isUndefined(w)||w===null)&&s.call(t,w,h.isString(x)?x.trim():x,v,f))===!0&&m(w,v?v.concat(x):[x])}),d.pop()}}if(!h.isObject(e))throw new TypeError("data must be an object");return m(e),t}function Jn(e){const t={"!":"%21","'":"%27","(":"%28",")":"%29","~":"%7E","%20":"+","%00":"\0"};return encodeURIComponent(e).replace(/[!'()~]|%20|%00/g,function(n){return t[n]})}function fn(e,t){this._pairs=[],e&&ui(e,this,t)}const rr=fn.prototype;rr.append=function(t,i){this._pairs.push([t,i])};rr.toString=function(t){const i=t?function(n){return t.call(this,n,Jn)}:Jn;return this._pairs.map(function(s){return i(s[0])+"="+i(s[1])},"").join("&")};function Xa(e){return encodeURIComponent(e).replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+").replace(/%5B/gi,"[").replace(/%5D/gi,"]")}function or(e,t,i){if(!t)return e;const n=i&&i.encode||Xa;h.isFunction(i)&&(i={serialize:i});const s=i&&i.serialize;let r;if(s?r=s(t,i):r=h.isURLSearchParams(t)?t.toString():new fn(t,i).toString(n),r){const o=e.indexOf("#");o!==-1&&(e=e.slice(0,o)),e+=(e.indexOf("?")===-1?"?":"&")+r}return e}class Gn{constructor(){this.handlers=[]}use(t,i,n){return this.handlers.push({fulfilled:t,rejected:i,synchronous:n?n.synchronous:!1,runWhen:n?n.runWhen:null}),this.handlers.length-1}eject(t){this.handlers[t]&&(this.handlers[t]=null)}clear(){this.handlers&&(this.handlers=[])}forEach(t){h.forEach(this.handlers,function(n){n!==null&&t(n)})}}const ar={silentJSONParsing:!0,forcedJSONParsing:!0,clarifyTimeoutError:!1},Ja=typeof URLSearchParams<"u"?URLSearchParams:fn,Ga=typeof FormData<"u"?FormData:null,Ya=typeof Blob<"u"?Blob:null,Za={isBrowser:!0,classes:{URLSearchParams:Ja,FormData:Ga,Blob:Ya},protocols:["http","https","file","blob","url","data"]},mn=typeof window<"u"&&typeof document<"u",Ui=typeof navigator=="object"&&navigator||void 0,el=mn&&(!Ui||["ReactNative","NativeScript","NS"].indexOf(Ui.product)<0),tl=typeof WorkerGlobalScope<"u"&&self instanceof WorkerGlobalScope&&typeof self.importScripts=="function",il=mn&&window.location.href||"http://localhost",nl=Object.freeze(Object.defineProperty({__proto__:null,hasBrowserEnv:mn,hasStandardBrowserEnv:el,hasStandardBrowserWebWorkerEnv:tl,navigator:Ui,origin:il},Symbol.toStringTag,{value:"Module"})),U={...nl,...Za};function sl(e,t){return ui(e,new U.classes.URLSearchParams,{visitor:function(i,n,s,r){return U.isNode&&h.isBuffer(i)?(this.append(n,i.toString("base64")),!1):r.defaultVisitor.apply(this,arguments)},...t})}function rl(e){return h.matchAll(/\w+|\[(\w*)]/g,e).map(t=>t[0]==="[]"?"":t[1]||t[0])}function ol(e){const t={},i=Object.keys(e);let n;const s=i.length;let r;for(n=0;n<s;n++)r=i[n],t[r]=e[r];return t}function lr(e){function t(i,n,s,r){let o=i[r++];if(o==="__proto__")return!0;const a=Number.isFinite(+o),l=r>=i.length;return o=!o&&h.isArray(s)?s.length:o,l?(h.hasOwnProp(s,o)?s[o]=[s[o],n]:s[o]=n,!a):((!s[o]||!h.isObject(s[o]))&&(s[o]=[]),t(i,n,s[o],r)&&h.isArray(s[o])&&(s[o]=ol(s[o])),!a)}if(h.isFormData(e)&&h.isFunction(e.entries)){const i={};return h.forEachEntry(e,(n,s)=>{t(rl(n),s,i,0)}),i}return null}function al(e,t,i){if(h.isString(e))try{return(t||JSON.parse)(e),h.trim(e)}catch(n){if(n.name!=="SyntaxError")throw n}return(i||JSON.stringify)(e)}const Tt={transitional:ar,adapter:["xhr","http","fetch"],transformRequest:[function(t,i){const n=i.getContentType()||"",s=n.indexOf("application/json")>-1,r=h.isObject(t);if(r&&h.isHTMLForm(t)&&(t=new FormData(t)),h.isFormData(t))return s?JSON.stringify(lr(t)):t;if(h.isArrayBuffer(t)||h.isBuffer(t)||h.isStream(t)||h.isFile(t)||h.isBlob(t)||h.isReadableStream(t))return t;if(h.isArrayBufferView(t))return t.buffer;if(h.isURLSearchParams(t))return i.setContentType("application/x-www-form-urlencoded;charset=utf-8",!1),t.toString();let a;if(r){if(n.indexOf("application/x-www-form-urlencoded")>-1)return sl(t,this.formSerializer).toString();if((a=h.isFileList(t))||n.indexOf("multipart/form-data")>-1){const l=this.env&&this.env.FormData;return ui(a?{"files[]":t}:t,l&&new l,this.formSerializer)}}return r||s?(i.setContentType("application/json",!1),al(t)):t}],transformResponse:[function(t){const i=this.transitional||Tt.transitional,n=i&&i.forcedJSONParsing,s=this.responseType==="json";if(h.isResponse(t)||h.isReadableStream(t))return t;if(t&&h.isString(t)&&(n&&!this.responseType||s)){const o=!(i&&i.silentJSONParsing)&&s;try{return JSON.parse(t)}catch(a){if(o)throw a.name==="SyntaxError"?k.from(a,k.ERR_BAD_RESPONSE,this,null,this.response):a}}return t}],timeout:0,xsrfCookieName:"XSRF-TOKEN",xsrfHeaderName:"X-XSRF-TOKEN",maxContentLength:-1,maxBodyLength:-1,env:{FormData:U.classes.FormData,Blob:U.classes.Blob},validateStatus:function(t){return t>=200&&t<300},headers:{common:{Accept:"application/json, text/plain, */*","Content-Type":void 0}}};h.forEach(["delete","get","head","post","put","patch"],e=>{Tt.headers[e]={}});const ll=h.toObjectSet(["age","authorization","content-length","content-type","etag","expires","from","host","if-modified-since","if-unmodified-since","last-modified","location","max-forwards","proxy-authorization","referer","retry-after","user-agent"]),ul=e=>{const t={};let i,n,s;return e&&e.split(`
`).forEach(function(o){s=o.indexOf(":"),i=o.substring(0,s).trim().toLowerCase(),n=o.substring(s+1).trim(),!(!i||t[i]&&ll[i])&&(i==="set-cookie"?t[i]?t[i].push(n):t[i]=[n]:t[i]=t[i]?t[i]+", "+n:n)}),t},Yn=Symbol("internals");function pt(e){return e&&String(e).trim().toLowerCase()}function jt(e){return e===!1||e==null?e:h.isArray(e)?e.map(jt):String(e)}function cl(e){const t=Object.create(null),i=/([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g;let n;for(;n=i.exec(e);)t[n[1]]=n[2];return t}const dl=e=>/^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(e.trim());function Si(e,t,i,n,s){if(h.isFunction(n))return n.call(this,t,i);if(s&&(t=i),!!h.isString(t)){if(h.isString(n))return t.indexOf(n)!==-1;if(h.isRegExp(n))return n.test(t)}}function hl(e){return e.trim().toLowerCase().replace(/([a-z\d])(\w*)/g,(t,i,n)=>i.toUpperCase()+n)}function pl(e,t){const i=h.toCamelCase(" "+t);["get","set","has"].forEach(n=>{Object.defineProperty(e,n+i,{value:function(s,r,o){return this[n].call(this,t,s,r,o)},configurable:!0})})}let Q=class{constructor(t){t&&this.set(t)}set(t,i,n){const s=this;function r(a,l,u){const c=pt(l);if(!c)throw new Error("header name must be a non-empty string");const d=h.findKey(s,c);(!d||s[d]===void 0||u===!0||u===void 0&&s[d]!==!1)&&(s[d||l]=jt(a))}const o=(a,l)=>h.forEach(a,(u,c)=>r(u,c,l));if(h.isPlainObject(t)||t instanceof this.constructor)o(t,i);else if(h.isString(t)&&(t=t.trim())&&!dl(t))o(ul(t),i);else if(h.isObject(t)&&h.isIterable(t)){let a={},l,u;for(const c of t){if(!h.isArray(c))throw TypeError("Object iterator must return a key-value pair");a[u=c[0]]=(l=a[u])?h.isArray(l)?[...l,c[1]]:[l,c[1]]:c[1]}o(a,i)}else t!=null&&r(i,t,n);return this}get(t,i){if(t=pt(t),t){const n=h.findKey(this,t);if(n){const s=this[n];if(!i)return s;if(i===!0)return cl(s);if(h.isFunction(i))return i.call(this,s,n);if(h.isRegExp(i))return i.exec(s);throw new TypeError("parser must be boolean|regexp|function")}}}has(t,i){if(t=pt(t),t){const n=h.findKey(this,t);return!!(n&&this[n]!==void 0&&(!i||Si(this,this[n],n,i)))}return!1}delete(t,i){const n=this;let s=!1;function r(o){if(o=pt(o),o){const a=h.findKey(n,o);a&&(!i||Si(n,n[a],a,i))&&(delete n[a],s=!0)}}return h.isArray(t)?t.forEach(r):r(t),s}clear(t){const i=Object.keys(this);let n=i.length,s=!1;for(;n--;){const r=i[n];(!t||Si(this,this[r],r,t,!0))&&(delete this[r],s=!0)}return s}normalize(t){const i=this,n={};return h.forEach(this,(s,r)=>{const o=h.findKey(n,r);if(o){i[o]=jt(s),delete i[r];return}const a=t?hl(r):String(r).trim();a!==r&&delete i[r],i[a]=jt(s),n[a]=!0}),this}concat(...t){return this.constructor.concat(this,...t)}toJSON(t){const i=Object.create(null);return h.forEach(this,(n,s)=>{n!=null&&n!==!1&&(i[s]=t&&h.isArray(n)?n.join(", "):n)}),i}[Symbol.iterator](){return Object.entries(this.toJSON())[Symbol.iterator]()}toString(){return Object.entries(this.toJSON()).map(([t,i])=>t+": "+i).join(`
`)}getSetCookie(){return this.get("set-cookie")||[]}get[Symbol.toStringTag](){return"AxiosHeaders"}static from(t){return t instanceof this?t:new this(t)}static concat(t,...i){const n=new this(t);return i.forEach(s=>n.set(s)),n}static accessor(t){const n=(this[Yn]=this[Yn]={accessors:{}}).accessors,s=this.prototype;function r(o){const a=pt(o);n[a]||(pl(s,o),n[a]=!0)}return h.isArray(t)?t.forEach(r):r(t),this}};Q.accessor(["Content-Type","Content-Length","Accept","Accept-Encoding","User-Agent","Authorization"]);h.reduceDescriptors(Q.prototype,({value:e},t)=>{let i=t[0].toUpperCase()+t.slice(1);return{get:()=>e,set(n){this[i]=n}}});h.freezeMethods(Q);function ki(e,t){const i=this||Tt,n=t||i,s=Q.from(n.headers);let r=n.data;return h.forEach(e,function(a){r=a.call(i,r,s.normalize(),t?t.status:void 0)}),s.normalize(),r}function ur(e){return!!(e&&e.__CANCEL__)}function st(e,t,i){k.call(this,e??"canceled",k.ERR_CANCELED,t,i),this.name="CanceledError"}h.inherits(st,k,{__CANCEL__:!0});function cr(e,t,i){const n=i.config.validateStatus;!i.status||!n||n(i.status)?e(i):t(new k("Request failed with status code "+i.status,[k.ERR_BAD_REQUEST,k.ERR_BAD_RESPONSE][Math.floor(i.status/100)-4],i.config,i.request,i))}function fl(e){const t=/^([-+\w]{1,25})(:?\/\/|:)/.exec(e);return t&&t[1]||""}function ml(e,t){e=e||10;const i=new Array(e),n=new Array(e);let s=0,r=0,o;return t=t!==void 0?t:1e3,function(l){const u=Date.now(),c=n[r];o||(o=u),i[s]=l,n[s]=u;let d=r,f=0;for(;d!==s;)f+=i[d++],d=d%e;if(s=(s+1)%e,s===r&&(r=(r+1)%e),u-o<t)return;const m=c&&u-c;return m?Math.round(f*1e3/m):void 0}}function gl(e,t){let i=0,n=1e3/t,s,r;const o=(u,c=Date.now())=>{i=c,s=null,r&&(clearTimeout(r),r=null),e(...u)};return[(...u)=>{const c=Date.now(),d=c-i;d>=n?o(u,c):(s=u,r||(r=setTimeout(()=>{r=null,o(s)},n-d)))},()=>s&&o(s)]}const Jt=(e,t,i=3)=>{let n=0;const s=ml(50,250);return gl(r=>{const o=r.loaded,a=r.lengthComputable?r.total:void 0,l=o-n,u=s(l),c=o<=a;n=o;const d={loaded:o,total:a,progress:a?o/a:void 0,bytes:l,rate:u||void 0,estimated:u&&a&&c?(a-o)/u:void 0,event:r,lengthComputable:a!=null,[t?"download":"upload"]:!0};e(d)},i)},Zn=(e,t)=>{const i=e!=null;return[n=>t[0]({lengthComputable:i,total:e,loaded:n}),t[1]]},es=e=>(...t)=>h.asap(()=>e(...t)),bl=U.hasStandardBrowserEnv?((e,t)=>i=>(i=new URL(i,U.origin),e.protocol===i.protocol&&e.host===i.host&&(t||e.port===i.port)))(new URL(U.origin),U.navigator&&/(msie|trident)/i.test(U.navigator.userAgent)):()=>!0,yl=U.hasStandardBrowserEnv?{write(e,t,i,n,s,r){const o=[e+"="+encodeURIComponent(t)];h.isNumber(i)&&o.push("expires="+new Date(i).toGMTString()),h.isString(n)&&o.push("path="+n),h.isString(s)&&o.push("domain="+s),r===!0&&o.push("secure"),document.cookie=o.join("; ")},read(e){const t=document.cookie.match(new RegExp("(^|;\\s*)("+e+")=([^;]*)"));return t?decodeURIComponent(t[3]):null},remove(e){this.write(e,"",Date.now()-864e5)}}:{write(){},read(){return null},remove(){}};function vl(e){return/^([a-z][a-z\d+\-.]*:)?\/\//i.test(e)}function wl(e,t){return t?e.replace(/\/?\/$/,"")+"/"+t.replace(/^\/+/,""):e}function dr(e,t,i){let n=!vl(t);return e&&(n||i==!1)?wl(e,t):t}const ts=e=>e instanceof Q?{...e}:e;function Ve(e,t){t=t||{};const i={};function n(u,c,d,f){return h.isPlainObject(u)&&h.isPlainObject(c)?h.merge.call({caseless:f},u,c):h.isPlainObject(c)?h.merge({},c):h.isArray(c)?c.slice():c}function s(u,c,d,f){if(h.isUndefined(c)){if(!h.isUndefined(u))return n(void 0,u,d,f)}else return n(u,c,d,f)}function r(u,c){if(!h.isUndefined(c))return n(void 0,c)}function o(u,c){if(h.isUndefined(c)){if(!h.isUndefined(u))return n(void 0,u)}else return n(void 0,c)}function a(u,c,d){if(d in t)return n(u,c);if(d in e)return n(void 0,u)}const l={url:r,method:r,data:r,baseURL:o,transformRequest:o,transformResponse:o,paramsSerializer:o,timeout:o,timeoutMessage:o,withCredentials:o,withXSRFToken:o,adapter:o,responseType:o,xsrfCookieName:o,xsrfHeaderName:o,onUploadProgress:o,onDownloadProgress:o,decompress:o,maxContentLength:o,maxBodyLength:o,beforeRedirect:o,transport:o,httpAgent:o,httpsAgent:o,cancelToken:o,socketPath:o,responseEncoding:o,validateStatus:a,headers:(u,c,d)=>s(ts(u),ts(c),d,!0)};return h.forEach(Object.keys({...e,...t}),function(c){const d=l[c]||s,f=d(e[c],t[c],c);h.isUndefined(f)&&d!==a||(i[c]=f)}),i}const hr=e=>{const t=Ve({},e);let{data:i,withXSRFToken:n,xsrfHeaderName:s,xsrfCookieName:r,headers:o,auth:a}=t;t.headers=o=Q.from(o),t.url=or(dr(t.baseURL,t.url,t.allowAbsoluteUrls),e.params,e.paramsSerializer),a&&o.set("Authorization","Basic "+btoa((a.username||"")+":"+(a.password?unescape(encodeURIComponent(a.password)):"")));let l;if(h.isFormData(i)){if(U.hasStandardBrowserEnv||U.hasStandardBrowserWebWorkerEnv)o.setContentType(void 0);else if((l=o.getContentType())!==!1){const[u,...c]=l?l.split(";").map(d=>d.trim()).filter(Boolean):[];o.setContentType([u||"multipart/form-data",...c].join("; "))}}if(U.hasStandardBrowserEnv&&(n&&h.isFunction(n)&&(n=n(t)),n||n!==!1&&bl(t.url))){const u=s&&r&&yl.read(r);u&&o.set(s,u)}return t},_l=typeof XMLHttpRequest<"u",xl=_l&&function(e){return new Promise(function(i,n){const s=hr(e);let r=s.data;const o=Q.from(s.headers).normalize();let{responseType:a,onUploadProgress:l,onDownloadProgress:u}=s,c,d,f,m,g;function v(){m&&m(),g&&g(),s.cancelToken&&s.cancelToken.unsubscribe(c),s.signal&&s.signal.removeEventListener("abort",c)}let p=new XMLHttpRequest;p.open(s.method.toUpperCase(),s.url,!0),p.timeout=s.timeout;function w(){if(!p)return;const E=Q.from("getAllResponseHeaders"in p&&p.getAllResponseHeaders()),S={data:!a||a==="text"||a==="json"?p.responseText:p.response,status:p.status,statusText:p.statusText,headers:E,config:e,request:p};cr(function(R){i(R),v()},function(R){n(R),v()},S),p=null}"onloadend"in p?p.onloadend=w:p.onreadystatechange=function(){!p||p.readyState!==4||p.status===0&&!(p.responseURL&&p.responseURL.indexOf("file:")===0)||setTimeout(w)},p.onabort=function(){p&&(n(new k("Request aborted",k.ECONNABORTED,e,p)),p=null)},p.onerror=function(){n(new k("Network Error",k.ERR_NETWORK,e,p)),p=null},p.ontimeout=function(){let C=s.timeout?"timeout of "+s.timeout+"ms exceeded":"timeout exceeded";const S=s.transitional||ar;s.timeoutErrorMessage&&(C=s.timeoutErrorMessage),n(new k(C,S.clarifyTimeoutError?k.ETIMEDOUT:k.ECONNABORTED,e,p)),p=null},r===void 0&&o.setContentType(null),"setRequestHeader"in p&&h.forEach(o.toJSON(),function(C,S){p.setRequestHeader(S,C)}),h.isUndefined(s.withCredentials)||(p.withCredentials=!!s.withCredentials),a&&a!=="json"&&(p.responseType=s.responseType),u&&([f,g]=Jt(u,!0),p.addEventListener("progress",f)),l&&p.upload&&([d,m]=Jt(l),p.upload.addEventListener("progress",d),p.upload.addEventListener("loadend",m)),(s.cancelToken||s.signal)&&(c=E=>{p&&(n(!E||E.type?new st(null,e,p):E),p.abort(),p=null)},s.cancelToken&&s.cancelToken.subscribe(c),s.signal&&(s.signal.aborted?c():s.signal.addEventListener("abort",c)));const x=fl(s.url);if(x&&U.protocols.indexOf(x)===-1){n(new k("Unsupported protocol "+x+":",k.ERR_BAD_REQUEST,e));return}p.send(r||null)})},El=(e,t)=>{const{length:i}=e=e?e.filter(Boolean):[];if(t||i){let n=new AbortController,s;const r=function(u){if(!s){s=!0,a();const c=u instanceof Error?u:this.reason;n.abort(c instanceof k?c:new st(c instanceof Error?c.message:c))}};let o=t&&setTimeout(()=>{o=null,r(new k(`timeout ${t} of ms exceeded`,k.ETIMEDOUT))},t);const a=()=>{e&&(o&&clearTimeout(o),o=null,e.forEach(u=>{u.unsubscribe?u.unsubscribe(r):u.removeEventListener("abort",r)}),e=null)};e.forEach(u=>u.addEventListener("abort",r));const{signal:l}=n;return l.unsubscribe=()=>h.asap(a),l}},Sl=function*(e,t){let i=e.byteLength;if(i<t){yield e;return}let n=0,s;for(;n<i;)s=n+t,yield e.slice(n,s),n=s},kl=async function*(e,t){for await(const i of Cl(e))yield*Sl(i,t)},Cl=async function*(e){if(e[Symbol.asyncIterator]){yield*e;return}const t=e.getReader();try{for(;;){const{done:i,value:n}=await t.read();if(i)break;yield n}}finally{await t.cancel()}},is=(e,t,i,n)=>{const s=kl(e,t);let r=0,o,a=l=>{o||(o=!0,n&&n(l))};return new ReadableStream({async pull(l){try{const{done:u,value:c}=await s.next();if(u){a(),l.close();return}let d=c.byteLength;if(i){let f=r+=d;i(f)}l.enqueue(new Uint8Array(c))}catch(u){throw a(u),u}},cancel(l){return a(l),s.return()}},{highWaterMark:2})},ci=typeof fetch=="function"&&typeof Request=="function"&&typeof Response=="function",pr=ci&&typeof ReadableStream=="function",Al=ci&&(typeof TextEncoder=="function"?(e=>t=>e.encode(t))(new TextEncoder):async e=>new Uint8Array(await new Response(e).arrayBuffer())),fr=(e,...t)=>{try{return!!e(...t)}catch{return!1}},Tl=pr&&fr(()=>{let e=!1;const t=new Request(U.origin,{body:new ReadableStream,method:"POST",get duplex(){return e=!0,"half"}}).headers.has("Content-Type");return e&&!t}),ns=64*1024,Hi=pr&&fr(()=>h.isReadableStream(new Response("").body)),Gt={stream:Hi&&(e=>e.body)};ci&&(e=>{["text","arrayBuffer","blob","formData","stream"].forEach(t=>{!Gt[t]&&(Gt[t]=h.isFunction(e[t])?i=>i[t]():(i,n)=>{throw new k(`Response type '${t}' is not supported`,k.ERR_NOT_SUPPORT,n)})})})(new Response);const Rl=async e=>{if(e==null)return 0;if(h.isBlob(e))return e.size;if(h.isSpecCompliantForm(e))return(await new Request(U.origin,{method:"POST",body:e}).arrayBuffer()).byteLength;if(h.isArrayBufferView(e)||h.isArrayBuffer(e))return e.byteLength;if(h.isURLSearchParams(e)&&(e=e+""),h.isString(e))return(await Al(e)).byteLength},Fl=async(e,t)=>{const i=h.toFiniteNumber(e.getContentLength());return i??Rl(t)},Ol=ci&&(async e=>{let{url:t,method:i,data:n,signal:s,cancelToken:r,timeout:o,onDownloadProgress:a,onUploadProgress:l,responseType:u,headers:c,withCredentials:d="same-origin",fetchOptions:f}=hr(e);u=u?(u+"").toLowerCase():"text";let m=El([s,r&&r.toAbortSignal()],o),g;const v=m&&m.unsubscribe&&(()=>{m.unsubscribe()});let p;try{if(l&&Tl&&i!=="get"&&i!=="head"&&(p=await Fl(c,n))!==0){let S=new Request(t,{method:"POST",body:n,duplex:"half"}),F;if(h.isFormData(n)&&(F=S.headers.get("content-type"))&&c.setContentType(F),S.body){const[R,N]=Zn(p,Jt(es(l)));n=is(S.body,ns,R,N)}}h.isString(d)||(d=d?"include":"omit");const w="credentials"in Request.prototype;g=new Request(t,{...f,signal:m,method:i.toUpperCase(),headers:c.normalize().toJSON(),body:n,duplex:"half",credentials:w?d:void 0});let x=await fetch(g,f);const E=Hi&&(u==="stream"||u==="response");if(Hi&&(a||E&&v)){const S={};["status","statusText","headers"].forEach(q=>{S[q]=x[q]});const F=h.toFiniteNumber(x.headers.get("content-length")),[R,N]=a&&Zn(F,Jt(es(a),!0))||[];x=new Response(is(x.body,ns,R,()=>{N&&N(),v&&v()}),S)}u=u||"text";let C=await Gt[h.findKey(Gt,u)||"text"](x,e);return!E&&v&&v(),await new Promise((S,F)=>{cr(S,F,{data:C,headers:Q.from(x.headers),status:x.status,statusText:x.statusText,config:e,request:g})})}catch(w){throw v&&v(),w&&w.name==="TypeError"&&/Load failed|fetch/i.test(w.message)?Object.assign(new k("Network Error",k.ERR_NETWORK,e,g),{cause:w.cause||w}):k.from(w,w&&w.code,e,g)}}),qi={http:Wa,xhr:xl,fetch:Ol};h.forEach(qi,(e,t)=>{if(e){try{Object.defineProperty(e,"name",{value:t})}catch{}Object.defineProperty(e,"adapterName",{value:t})}});const ss=e=>`- ${e}`,Ll=e=>h.isFunction(e)||e===null||e===!1,mr={getAdapter:e=>{e=h.isArray(e)?e:[e];const{length:t}=e;let i,n;const s={};for(let r=0;r<t;r++){i=e[r];let o;if(n=i,!Ll(i)&&(n=qi[(o=String(i)).toLowerCase()],n===void 0))throw new k(`Unknown adapter '${o}'`);if(n)break;s[o||"#"+r]=n}if(!n){const r=Object.entries(s).map(([a,l])=>`adapter ${a} `+(l===!1?"is not supported by the environment":"is not available in the build"));let o=t?r.length>1?`since :
`+r.map(ss).join(`
`):" "+ss(r[0]):"as no adapter specified";throw new k("There is no suitable adapter to dispatch the request "+o,"ERR_NOT_SUPPORT")}return n},adapters:qi};function Ci(e){if(e.cancelToken&&e.cancelToken.throwIfRequested(),e.signal&&e.signal.aborted)throw new st(null,e)}function rs(e){return Ci(e),e.headers=Q.from(e.headers),e.data=ki.call(e,e.transformRequest),["post","put","patch"].indexOf(e.method)!==-1&&e.headers.setContentType("application/x-www-form-urlencoded",!1),mr.getAdapter(e.adapter||Tt.adapter)(e).then(function(n){return Ci(e),n.data=ki.call(e,e.transformResponse,n),n.headers=Q.from(n.headers),n},function(n){return ur(n)||(Ci(e),n&&n.response&&(n.response.data=ki.call(e,e.transformResponse,n.response),n.response.headers=Q.from(n.response.headers))),Promise.reject(n)})}const gr="1.11.0",di={};["object","boolean","number","function","string","symbol"].forEach((e,t)=>{di[e]=function(n){return typeof n===e||"a"+(t<1?"n ":" ")+e}});const os={};di.transitional=function(t,i,n){function s(r,o){return"[Axios v"+gr+"] Transitional option '"+r+"'"+o+(n?". "+n:"")}return(r,o,a)=>{if(t===!1)throw new k(s(o," has been removed"+(i?" in "+i:"")),k.ERR_DEPRECATED);return i&&!os[o]&&(os[o]=!0,console.warn(s(o," has been deprecated since v"+i+" and will be removed in the near future"))),t?t(r,o,a):!0}};di.spelling=function(t){return(i,n)=>(console.warn(`${n} is likely a misspelling of ${t}`),!0)};function Ml(e,t,i){if(typeof e!="object")throw new k("options must be an object",k.ERR_BAD_OPTION_VALUE);const n=Object.keys(e);let s=n.length;for(;s-- >0;){const r=n[s],o=t[r];if(o){const a=e[r],l=a===void 0||o(a,r,e);if(l!==!0)throw new k("option "+r+" must be "+l,k.ERR_BAD_OPTION_VALUE);continue}if(i!==!0)throw new k("Unknown option "+r,k.ERR_BAD_OPTION)}}const Vt={assertOptions:Ml,validators:di},de=Vt.validators;let Ne=class{constructor(t){this.defaults=t||{},this.interceptors={request:new Gn,response:new Gn}}async request(t,i){try{return await this._request(t,i)}catch(n){if(n instanceof Error){let s={};Error.captureStackTrace?Error.captureStackTrace(s):s=new Error;const r=s.stack?s.stack.replace(/^.+\n/,""):"";try{n.stack?r&&!String(n.stack).endsWith(r.replace(/^.+\n.+\n/,""))&&(n.stack+=`
`+r):n.stack=r}catch{}}throw n}}_request(t,i){typeof t=="string"?(i=i||{},i.url=t):i=t||{},i=Ve(this.defaults,i);const{transitional:n,paramsSerializer:s,headers:r}=i;n!==void 0&&Vt.assertOptions(n,{silentJSONParsing:de.transitional(de.boolean),forcedJSONParsing:de.transitional(de.boolean),clarifyTimeoutError:de.transitional(de.boolean)},!1),s!=null&&(h.isFunction(s)?i.paramsSerializer={serialize:s}:Vt.assertOptions(s,{encode:de.function,serialize:de.function},!0)),i.allowAbsoluteUrls!==void 0||(this.defaults.allowAbsoluteUrls!==void 0?i.allowAbsoluteUrls=this.defaults.allowAbsoluteUrls:i.allowAbsoluteUrls=!0),Vt.assertOptions(i,{baseUrl:de.spelling("baseURL"),withXsrfToken:de.spelling("withXSRFToken")},!0),i.method=(i.method||this.defaults.method||"get").toLowerCase();let o=r&&h.merge(r.common,r[i.method]);r&&h.forEach(["delete","get","head","post","put","patch","common"],g=>{delete r[g]}),i.headers=Q.concat(o,r);const a=[];let l=!0;this.interceptors.request.forEach(function(v){typeof v.runWhen=="function"&&v.runWhen(i)===!1||(l=l&&v.synchronous,a.unshift(v.fulfilled,v.rejected))});const u=[];this.interceptors.response.forEach(function(v){u.push(v.fulfilled,v.rejected)});let c,d=0,f;if(!l){const g=[rs.bind(this),void 0];for(g.unshift(...a),g.push(...u),f=g.length,c=Promise.resolve(i);d<f;)c=c.then(g[d++],g[d++]);return c}f=a.length;let m=i;for(d=0;d<f;){const g=a[d++],v=a[d++];try{m=g(m)}catch(p){v.call(this,p);break}}try{c=rs.call(this,m)}catch(g){return Promise.reject(g)}for(d=0,f=u.length;d<f;)c=c.then(u[d++],u[d++]);return c}getUri(t){t=Ve(this.defaults,t);const i=dr(t.baseURL,t.url,t.allowAbsoluteUrls);return or(i,t.params,t.paramsSerializer)}};h.forEach(["delete","get","head","options"],function(t){Ne.prototype[t]=function(i,n){return this.request(Ve(n||{},{method:t,url:i,data:(n||{}).data}))}});h.forEach(["post","put","patch"],function(t){function i(n){return function(r,o,a){return this.request(Ve(a||{},{method:t,headers:n?{"Content-Type":"multipart/form-data"}:{},url:r,data:o}))}}Ne.prototype[t]=i(),Ne.prototype[t+"Form"]=i(!0)});let Pl=class br{constructor(t){if(typeof t!="function")throw new TypeError("executor must be a function.");let i;this.promise=new Promise(function(r){i=r});const n=this;this.promise.then(s=>{if(!n._listeners)return;let r=n._listeners.length;for(;r-- >0;)n._listeners[r](s);n._listeners=null}),this.promise.then=s=>{let r;const o=new Promise(a=>{n.subscribe(a),r=a}).then(s);return o.cancel=function(){n.unsubscribe(r)},o},t(function(r,o,a){n.reason||(n.reason=new st(r,o,a),i(n.reason))})}throwIfRequested(){if(this.reason)throw this.reason}subscribe(t){if(this.reason){t(this.reason);return}this._listeners?this._listeners.push(t):this._listeners=[t]}unsubscribe(t){if(!this._listeners)return;const i=this._listeners.indexOf(t);i!==-1&&this._listeners.splice(i,1)}toAbortSignal(){const t=new AbortController,i=n=>{t.abort(n)};return this.subscribe(i),t.signal.unsubscribe=()=>this.unsubscribe(i),t.signal}static source(){let t;return{token:new br(function(s){t=s}),cancel:t}}};function Il(e){return function(i){return e.apply(null,i)}}function zl(e){return h.isObject(e)&&e.isAxiosError===!0}const ji={Continue:100,SwitchingProtocols:101,Processing:102,EarlyHints:103,Ok:200,Created:201,Accepted:202,NonAuthoritativeInformation:203,NoContent:204,ResetContent:205,PartialContent:206,MultiStatus:207,AlreadyReported:208,ImUsed:226,MultipleChoices:300,MovedPermanently:301,Found:302,SeeOther:303,NotModified:304,UseProxy:305,Unused:306,TemporaryRedirect:307,PermanentRedirect:308,BadRequest:400,Unauthorized:401,PaymentRequired:402,Forbidden:403,NotFound:404,MethodNotAllowed:405,NotAcceptable:406,ProxyAuthenticationRequired:407,RequestTimeout:408,Conflict:409,Gone:410,LengthRequired:411,PreconditionFailed:412,PayloadTooLarge:413,UriTooLong:414,UnsupportedMediaType:415,RangeNotSatisfiable:416,ExpectationFailed:417,ImATeapot:418,MisdirectedRequest:421,UnprocessableEntity:422,Locked:423,FailedDependency:424,TooEarly:425,UpgradeRequired:426,PreconditionRequired:428,TooManyRequests:429,RequestHeaderFieldsTooLarge:431,UnavailableForLegalReasons:451,InternalServerError:500,NotImplemented:501,BadGateway:502,ServiceUnavailable:503,GatewayTimeout:504,HttpVersionNotSupported:505,VariantAlsoNegotiates:506,InsufficientStorage:507,LoopDetected:508,NotExtended:510,NetworkAuthenticationRequired:511};Object.entries(ji).forEach(([e,t])=>{ji[t]=e});function yr(e){const t=new Ne(e),i=Qs(Ne.prototype.request,t);return h.extend(i,Ne.prototype,t,{allOwnKeys:!0}),h.extend(i,t,null,{allOwnKeys:!0}),i.create=function(s){return yr(Ve(e,s))},i}const I=yr(Tt);I.Axios=Ne;I.CanceledError=st;I.CancelToken=Pl;I.isCancel=ur;I.VERSION=gr;I.toFormData=ui;I.AxiosError=k;I.Cancel=I.CanceledError;I.all=function(t){return Promise.all(t)};I.spread=Il;I.isAxiosError=zl;I.mergeConfig=Ve;I.AxiosHeaders=Q;I.formToJSON=e=>lr(h.isHTMLForm(e)?new FormData(e):e);I.getAdapter=mr.getAdapter;I.HttpStatusCode=ji;I.default=I;const{Axios:Dh,AxiosError:Nh,CanceledError:Uh,isCancel:Hh,CancelToken:qh,VERSION:jh,all:Vh,Cancel:Wh,isAxiosError:Kh,spread:Qh,toFormData:Xh,AxiosHeaders:Jh,HttpStatusCode:Gh,formToJSON:Yh,getAdapter:Zh,mergeConfig:ep}=I;window.axios=I;window.axios.defaults.headers.common["X-Requested-With"]="XMLHttpRequest";window.fileManagerState=window.fileManagerState||{initialized:!1,initSource:null,instance:null};function $l(e,t={}){return window.fileManagerAlreadyInitialized?(console.info(`File Manager already initialized. Skipping ${e} initialization.`),window.fileManagerState.instance):window.fileManagerState.initialized?(console.info(`File Manager already initialized by ${window.fileManagerState.initSource}. Skipping ${e} initialization.`),window.fileManagerState.instance):(console.info(`Initializing File Manager from ${e}`),window.fileManagerAlreadyInitialized=!0,window.fileManagerState.initialized=!0,window.fileManagerState.initSource=e,e==="lazy-loader"?window.fileManagerState.instance=new FileManagerLazyLoader(t):e==="alpine"&&console.info("Alpine.js initialization will set the instance when ready"),window.fileManagerState.instance)}window.initializeFileManager=$l;class Bl{constructor(){this.currentStep=null,this.progressBar=null,this.init()}init(){this.currentStep=this.getCurrentStep(),this.progressBar=document.querySelector("[data-progress-bar]"),this.initializeStepFunctionality(),this.initializeFormSubmission(),this.initializeProgressIndicator(),console.log("Setup Wizard initialized for step:",this.currentStep)}getCurrentStep(){const t=document.querySelector("[data-setup-step]");return t?t.dataset.setupStep:"welcome"}initializeStepFunctionality(){switch(this.currentStep){case"database":this.initializeDatabaseStep();break;case"admin":this.initializeAdminStep();break;case"storage":this.initializeStorageStep();break}}initializeDatabaseStep(){const t=document.getElementById("sqlite"),i=document.getElementById("mysql"),n=document.getElementById("sqlite-config"),s=document.getElementById("mysql-config"),r=document.getElementById("test-connection");if(!t||!i)return;const o=()=>{t.checked?(n==null||n.classList.remove("hidden"),s==null||s.classList.add("hidden"),this.updateFormValidation("sqlite")):(n==null||n.classList.add("hidden"),s==null||s.classList.remove("hidden"),this.updateFormValidation("mysql"))};t.addEventListener("change",o),i.addEventListener("change",o),o(),r&&r.addEventListener("click",()=>{this.testDatabaseConnection()}),this.initializeDatabaseValidation()}initializeAdminStep(){const t=document.getElementById("password"),i=document.getElementById("password_confirmation"),n=document.getElementById("email"),s=document.getElementById("toggle-password");!t||!i||!n||(s&&s.addEventListener("click",()=>{this.togglePasswordVisibility(t,s)}),t.addEventListener("input",()=>{this.checkPasswordStrength(t.value),this.validatePasswordMatch()}),i.addEventListener("input",()=>{this.validatePasswordMatch()}),n.addEventListener("blur",()=>{this.validateEmailAvailability(n.value)}),this.initializeAdminFormValidation())}initializeStorageStep(){const t=document.getElementById("toggle-secret"),i=document.getElementById("google_client_secret"),n=document.getElementById("test-google-connection"),s=document.getElementById("skip_storage"),r=document.getElementById("google-drive-config");t&&i&&t.addEventListener("click",()=>{this.togglePasswordVisibility(i,t)}),s&&r&&s.addEventListener("change",()=>{this.toggleStorageRequirements(s.checked,r)}),n&&n.addEventListener("click",()=>{this.testGoogleDriveConnection()}),this.initializeStorageValidation()}initializeFormSubmission(){document.querySelectorAll('form[id$="-form"]').forEach(i=>{i.addEventListener("submit",n=>{this.handleFormSubmission(i,n)})})}initializeProgressIndicator(){if(this.progressBar){const t=this.progressBar.style.width;this.animateProgressBar(t)}this.updateStepIndicators()}async testDatabaseConnection(){var s,r,o,a,l;const t=document.getElementById("test-connection"),i=document.getElementById("connection-status");if(!t||!i)return;const n=t.innerHTML;try{this.setButtonLoading(t,"Testing...");const u=new FormData;u.append("_token",this.getCsrfToken()),u.append("host",((s=document.getElementById("mysql_host"))==null?void 0:s.value)||""),u.append("port",((r=document.getElementById("mysql_port"))==null?void 0:r.value)||""),u.append("database",((o=document.getElementById("mysql_database"))==null?void 0:o.value)||""),u.append("username",((a=document.getElementById("mysql_username"))==null?void 0:a.value)||""),u.append("password",((l=document.getElementById("mysql_password"))==null?void 0:l.value)||"");const d=await(await fetch("/setup/ajax/test-database",{method:"POST",body:u,headers:{"X-Requested-With":"XMLHttpRequest"}})).json();i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(d.success,d.message)}catch(u){console.error("Database connection test failed:",u),i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(!1,"Connection test failed. Please try again.")}finally{this.restoreButtonState(t,n)}}async testGoogleDriveConnection(){var s,r;const t=document.getElementById("test-google-connection"),i=document.getElementById("google-connection-status");if(!t||!i)return;const n=t.innerHTML;try{this.setButtonLoading(t,"Testing...");const o=new FormData;o.append("_token",this.getCsrfToken()),o.append("client_id",((s=document.getElementById("google_client_id"))==null?void 0:s.value)||""),o.append("client_secret",((r=document.getElementById("google_client_secret"))==null?void 0:r.value)||"");const l=await(await fetch("/setup/ajax/test-storage",{method:"POST",body:o,headers:{"X-Requested-With":"XMLHttpRequest"}})).json();i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(l.success,l.message)}catch(o){console.error("Google Drive connection test failed:",o),i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(!1,"Connection test failed. Please try again.")}finally{this.restoreButtonState(t,n)}}async validateEmailAvailability(t){if(!(!t||!this.isValidEmail(t)))try{const i=new FormData;i.append("_token",this.getCsrfToken()),i.append("email",t);const s=await(await fetch("/setup/ajax/validate-email",{method:"POST",body:i,headers:{"X-Requested-With":"XMLHttpRequest"}})).json();this.showEmailValidationResult(s.available,s.message)}catch(i){console.error("Email validation failed:",i)}}checkPasswordStrength(t){const i=document.getElementById("strength-bar"),n=document.getElementById("strength-text");if(!i||!n)return;const s=this.calculatePasswordScore(t);i.style.width=s+"%",s===0?(n.textContent="Enter password",n.className="font-medium text-gray-400",i.className="h-2 rounded-full transition-all duration-300 bg-gray-300"):s<50?(n.textContent="Weak",n.className="font-medium text-red-600",i.className="h-2 rounded-full transition-all duration-300 bg-red-500"):s<75?(n.textContent="Fair",n.className="font-medium text-yellow-600",i.className="h-2 rounded-full transition-all duration-300 bg-yellow-500"):s<100?(n.textContent="Good",n.className="font-medium text-blue-600",i.className="h-2 rounded-full transition-all duration-300 bg-blue-500"):(n.textContent="Strong",n.className="font-medium text-green-600",i.className="h-2 rounded-full transition-all duration-300 bg-green-500"),this.updatePasswordRequirements(t)}calculatePasswordScore(t){let i=0;return t.length>=8&&(i+=20),/[A-Z]/.test(t)&&(i+=20),/[a-z]/.test(t)&&(i+=20),/[0-9]/.test(t)&&(i+=20),/[^A-Za-z0-9]/.test(t)&&(i+=20),i}updatePasswordRequirements(t){[{id:"req-length",test:t.length>=8},{id:"req-uppercase",test:/[A-Z]/.test(t)},{id:"req-lowercase",test:/[a-z]/.test(t)},{id:"req-number",test:/[0-9]/.test(t)},{id:"req-special",test:/[^A-Za-z0-9]/.test(t)}].forEach(n=>{var r,o,a,l;const s=document.getElementById(n.id);s&&(n.test?(s.classList.remove("text-gray-600"),s.classList.add("text-green-600"),(r=s.querySelector("svg"))==null||r.classList.remove("text-gray-400"),(o=s.querySelector("svg"))==null||o.classList.add("text-green-500")):(s.classList.remove("text-green-600"),s.classList.add("text-gray-600"),(a=s.querySelector("svg"))==null||a.classList.remove("text-green-500"),(l=s.querySelector("svg"))==null||l.classList.add("text-gray-400")))})}validatePasswordMatch(){var a,l;const t=((a=document.getElementById("password"))==null?void 0:a.value)||"",i=((l=document.getElementById("password_confirmation"))==null?void 0:l.value)||"",n=document.getElementById("password-match-indicator"),s=document.getElementById("match-success"),r=document.getElementById("match-error"),o=document.getElementById("password-match-text");if(!(!n||!s||!r||!o)){if(i.length===0){n.classList.add("hidden"),o.textContent="Re-enter your password to confirm",o.className="mt-2 text-sm text-gray-500";return}n.classList.remove("hidden"),t===i?(s.classList.remove("hidden"),r.classList.add("hidden"),o.textContent="Passwords match",o.className="mt-2 text-sm text-green-600"):(s.classList.add("hidden"),r.classList.remove("hidden"),o.textContent="Passwords do not match",o.className="mt-2 text-sm text-red-600")}}togglePasswordVisibility(t,i){const n=t.getAttribute("type")==="password"?"text":"password";t.setAttribute("type",n);const s=i.querySelector('[id$="eye-closed"], [id$="-eye-closed"]'),r=i.querySelector('[id$="eye-open"], [id$="-eye-open"]');n==="text"?(s==null||s.classList.add("hidden"),r==null||r.classList.remove("hidden")):(s==null||s.classList.remove("hidden"),r==null||r.classList.add("hidden"))}toggleStorageRequirements(t,i){t?(i.style.opacity="0.5",i.style.pointerEvents="none",document.getElementById("google_client_id").required=!1,document.getElementById("google_client_secret").required=!1):(i.style.opacity="1",i.style.pointerEvents="auto",document.getElementById("google_client_id").required=!0,document.getElementById("google_client_secret").required=!0)}handleFormSubmission(t,i){const n=t.querySelector('button[type="submit"]');if(!n)return;const s=n.innerHTML;this.setButtonLoading(n,"Processing...");const r=t.querySelectorAll("input, select, textarea, button");r.forEach(o=>{o.disabled=!0}),setTimeout(()=>{r.forEach(o=>{o.disabled=!1}),this.restoreButtonState(n,s)},1e4)}initializeDatabaseValidation(){["mysql_host","mysql_port","mysql_database","mysql_username"].forEach(i=>{const n=document.getElementById(i);n&&n.addEventListener("blur",()=>{this.validateDatabaseField(i,n.value)})})}initializeAdminFormValidation(){const t=document.getElementById("email"),i=document.getElementById("password"),n=document.getElementById("password_confirmation"),s=document.getElementById("submit-btn");if(!t||!i||!n||!s)return;const r=()=>{const o=t.value,a=i.value,l=n.value,u=this.calculatePasswordScore(a),c=this.isValidEmail(o)&&u===100&&a===l&&l.length>0;s.disabled=!c};t.addEventListener("input",r),i.addEventListener("input",r),n.addEventListener("input",r),r()}initializeStorageValidation(){const t=document.getElementById("google_client_id"),i=document.getElementById("google_client_secret");t&&t.addEventListener("blur",()=>{this.validateGoogleClientId(t.value)}),i&&i.addEventListener("blur",()=>{this.validateGoogleClientSecret(i.value)})}validateDatabaseField(t,i){const n=document.getElementById(t);if(!n)return;let s=!0,r="";switch(t){case"mysql_host":s=i.length>0,r=s?"":"Host is required";break;case"mysql_port":s=/^\d+$/.test(i)&&parseInt(i)>0&&parseInt(i)<=65535,r=s?"":"Port must be a valid number between 1 and 65535";break;case"mysql_database":s=/^[a-zA-Z0-9_]+$/.test(i),r=s?"":"Database name can only contain letters, numbers, and underscores";break;case"mysql_username":s=i.length>0,r=s?"":"Username is required";break}this.showFieldValidation(n,s,r)}validateGoogleClientId(t){const i=document.getElementById("google_client_id");if(!i)return;const n=/^[0-9]+-[a-zA-Z0-9]+\.apps\.googleusercontent\.com$/.test(t),s=n?"":"Client ID should end with .apps.googleusercontent.com";this.showFieldValidation(i,n,s)}validateGoogleClientSecret(t){const i=document.getElementById("google_client_secret");if(!i)return;const n=/^GOCSPX-[a-zA-Z0-9_-]+$/.test(t),s=n?"":"Client Secret should start with GOCSPX-";this.showFieldValidation(i,n,s)}showFieldValidation(t,i,n){t.classList.remove("border-red-300","border-green-300");const s=t.parentNode.querySelector(".validation-message");if(s&&s.remove(),n){t.classList.add(i?"border-green-300":"border-red-300");const r=document.createElement("p");r.className=`mt-1 text-sm validation-message ${i?"text-green-600":"text-red-600"}`,r.textContent=n,t.parentNode.appendChild(r)}}showEmailValidationResult(t,i){const n=document.getElementById("email");n&&this.showFieldValidation(n,t,i)}updateFormValidation(t){["mysql_host","mysql_port","mysql_database","mysql_username"].forEach(n=>{const s=document.getElementById(n);s&&(s.required=t==="mysql")})}animateProgressBar(t){this.progressBar&&(this.progressBar.style.transition="width 0.5s ease-out",setTimeout(()=>{this.progressBar.style.width=t},100))}updateStepIndicators(){document.querySelectorAll("[data-step-indicator]").forEach(i=>{const n=i.dataset.stepIndicator,s=this.isStepCompleted(n),r=n===this.currentStep;s&&i.classList.add("completed"),r&&i.classList.add("current")})}isStepCompleted(t){if(!this.currentStep||!t)return!1;const i=["welcome","database","admin","storage","complete"],n=i.indexOf(this.currentStep),s=i.indexOf(t);return n===-1||s===-1?!1:s<n}setButtonLoading(t,i){t.disabled=!0,t.innerHTML=`
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
        `}getCsrfToken(){const t=document.querySelector('meta[name="csrf-token"]');return t?t.getAttribute("content"):""}isValidEmail(t){return/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t)}}document.addEventListener("DOMContentLoaded",function(){if(document.querySelector("[data-setup-step]")&&window.location.pathname.startsWith("/setup"))try{new Bl}catch(t){console.warn("Setup wizard initialization failed:",t)}});class vr{constructor(){this.currentTestJobId=null,this.testStartTime=null,this.pollingInterval=null,this.elapsedTimeInterval=null,this.initializeElements(),this.bindEvents(),this.loadQueueHealth()}initializeElements(){this.testQueueBtn=document.getElementById("test-queue-btn"),this.testQueueBtnText=document.getElementById("test-queue-btn-text"),this.queueStatus=document.getElementById("queue-status"),this.testResultsSection=document.getElementById("test-results-section"),this.currentTestProgress=document.getElementById("current-test-progress"),this.testProgressMessage=document.getElementById("test-progress-message"),this.testElapsedTime=document.getElementById("test-elapsed-time"),this.testResultsDisplay=document.getElementById("test-results-display")}bindEvents(){this.testQueueBtn&&this.testQueueBtn.addEventListener("click",()=>this.startQueueTest())}async startQueueTest(){var t;if(this.currentTestJobId){console.warn("Test already in progress");return}try{this.setTestInProgress(!0),this.testStartTime=Date.now(),this.startElapsedTimeCounter();const i=await this.dispatchTestJob();if(i.success&&i.data)this.currentTestJobId=i.data.test_job_id,this.updateProgressMessage("Test job dispatched, waiting for processing..."),this.startPolling();else throw new Error(i.message||((t=i.error)==null?void 0:t.message)||"Failed to dispatch test job")}catch(i){console.error("Queue test failed:",i),this.handleTestError(i.message)}}async dispatchTestJob(){var i;const t=await fetch("/admin/queue/test",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":((i=document.querySelector('meta[name="csrf-token"]'))==null?void 0:i.getAttribute("content"))||""},body:JSON.stringify({delay:0})});if(!t.ok)throw new Error(`HTTP ${t.status}: ${t.statusText}`);return await t.json()}startPolling(){this.pollingInterval&&clearInterval(this.pollingInterval),this.pollingInterval=setInterval(async()=>{try{await this.checkTestJobStatus()}catch(t){console.error("Polling error:",t),this.handleTestError("Failed to check test status")}},1e3),setTimeout(()=>{this.currentTestJobId&&this.handleTestTimeout()},3e4)}async checkTestJobStatus(){var n;if(!this.currentTestJobId)return;const t=await fetch(`/admin/queue/test/status?test_job_id=${this.currentTestJobId}`,{method:"GET",headers:{"X-CSRF-TOKEN":((n=document.querySelector('meta[name="csrf-token"]'))==null?void 0:n.getAttribute("content"))||""}});if(!t.ok)throw new Error(`HTTP ${t.status}: ${t.statusText}`);const i=await t.json();if(i.success&&i.data&&i.data.status){const s=i.data.status;switch(s.status){case"completed":this.handleTestSuccess(s);break;case"failed":this.handleTestFailure(s);break;case"timeout":this.handleTestTimeout();break;case"processing":this.updateProgressMessage("Test job is being processed...");break;case"pending":this.updateProgressMessage("Test job is queued, waiting for worker...");break}}}handleTestSuccess(t){this.stopTest();const i=t.processing_time||0,n=Date.now()-this.testStartTime,s={status:"success",message:`Queue worker is functioning properly! Job completed in ${i.toFixed(2)}s`,details:{processing_time:i,total_time:(n/1e3).toFixed(2),completed_at:t.completed_at||new Date().toISOString(),job_id:this.currentTestJobId},timestamp:Date.now()};this.displayTestResult(s),this.showSuccessNotification(`Queue worker completed test in ${i.toFixed(2)}s`),this.loadQueueHealth()}handleTestFailure(t){this.stopTest();const i={status:"failed",message:"Queue test failed: "+(t.error_message||"Unknown error"),details:{error:t.error_message,failed_at:t.failed_at||new Date().toISOString(),job_id:this.currentTestJobId},timestamp:Date.now()};this.displayTestResult(i),this.loadQueueHealth()}handleTestTimeout(){this.stopTest();const t={status:"timeout",message:"Queue test timed out after 30 seconds. The queue worker may not be running.",details:{timeout_duration:30,job_id:this.currentTestJobId,timed_out_at:new Date().toISOString()},timestamp:Date.now()};this.displayTestResult(t),this.loadQueueHealth()}handleTestError(t){this.stopTest();const i={status:"error",message:"Test error: "+t,details:{error:t,error_at:new Date().toISOString()},timestamp:Date.now()};this.displayTestResult(i),this.showDetailedError(new Error(t),"Queue test execution"),this.loadQueueHealth()}stopTest(){this.pollingInterval&&(clearInterval(this.pollingInterval),this.pollingInterval=null),this.elapsedTimeInterval&&(clearInterval(this.elapsedTimeInterval),this.elapsedTimeInterval=null),this.currentTestJobId=null,this.testStartTime=null,this.setTestInProgress(!1),this.hideCurrentTestProgress()}setTestInProgress(t){this.testQueueBtn&&(this.setLoadingStateWithAnimation(t),t&&this.showCurrentTestProgress())}showCurrentTestProgress(){this.currentTestProgress&&this.currentTestProgress.classList.remove("hidden"),this.testResultsSection&&this.testResultsSection.classList.remove("hidden")}hideCurrentTestProgress(){this.currentTestProgress&&this.currentTestProgress.classList.add("hidden")}updateProgressMessage(t){this.testProgressMessage&&this.updateProgressWithAnimation(t)}startElapsedTimeCounter(){this.elapsedTimeInterval&&clearInterval(this.elapsedTimeInterval),this.elapsedTimeInterval=setInterval(()=>{if(this.testStartTime&&this.testElapsedTime){const t=((Date.now()-this.testStartTime)/1e3).toFixed(1);this.testElapsedTime.textContent=`(${t}s)`}},100)}displayTestResult(t){if(!this.testResultsDisplay)return;const i=this.createTestResultElement(t);i.style.opacity="0",i.style.transform="translateY(-10px)",i.style.transition="all 0.3s ease-in-out",this.testResultsDisplay.insertBefore(i,this.testResultsDisplay.firstChild),setTimeout(()=>{i.style.opacity="1",i.style.transform="translateY(0)"},10),this.testResultsSection&&this.testResultsSection.classList.remove("hidden"),this.addResultAnimation(i,t.status);const n=this.testResultsDisplay.children;for(;n.length>5;){const s=n[n.length-1];this.animateResultRemoval(s)}}createTestResultElement(t){var l,u;const i=document.createElement("div");let n,s,r,o="";switch(t.status){case"success":n="bg-green-50 border-green-200",s="text-green-900",o="animate-pulse-success",r=`<svg class="h-5 w-5 text-green-600 animate-bounce-once" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`;break;case"failed":case"error":n="bg-red-50 border-red-200",s="text-red-900",o="animate-pulse-error",r=`<svg class="h-5 w-5 text-red-600 animate-shake" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`;break;case"timeout":n="bg-yellow-50 border-yellow-200",s="text-yellow-900",o="animate-pulse-warning",r=`<svg class="h-5 w-5 text-yellow-600 animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`;break}const a=new Date(t.timestamp).toLocaleString();return i.className=`border rounded-lg p-4 ${n} ${o} transition-all duration-300 hover:shadow-md`,i.innerHTML=`
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    ${r}
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-sm font-medium ${s}">
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
        `,i}async loadQueueHealth(){var t;try{const i=await fetch("/admin/queue/health",{method:"GET",headers:{"X-CSRF-TOKEN":((t=document.querySelector('meta[name="csrf-token"]'))==null?void 0:t.getAttribute("content"))||""}});if(!i.ok)throw new Error(`HTTP ${i.status}: ${i.statusText}`);const n=await i.json();n.success&&n.data&&n.data.metrics&&this.updateQueueHealthDisplay(n.data.metrics)}catch(i){console.error("Failed to load queue health:",i),this.updateQueueHealthDisplay({overall_status:"error",job_statistics:{pending_jobs:0,failed_jobs_total:0}})}}updateQueueHealthDisplay(t){if(this.queueStatus){let i="Unknown",n="text-gray-900";switch(t.overall_status||t.status){case"healthy":i="Healthy",n="text-green-600";break;case"warning":i="Warning",n="text-yellow-600";break;case"critical":case"error":i="Error",n="text-red-600";break;case"idle":i="Idle",n="text-blue-600";break}this.queueStatus.textContent=i,this.queueStatus.className=`text-2xl font-bold ${n}`}}addResultAnimation(t,i){if(!(!t||!t.classList))switch(i){case"success":t.classList.add("animate-success-glow"),setTimeout(()=>t.classList.remove("animate-success-glow"),2e3);break;case"failed":case"error":t.classList.add("animate-error-shake"),setTimeout(()=>t.classList.remove("animate-error-shake"),1e3);break;case"timeout":t.classList.add("animate-warning-pulse"),setTimeout(()=>t.classList.remove("animate-warning-pulse"),3e3);break}}animateResultRemoval(t){t&&(t.style.transition="all 0.3s ease-out",t.style.opacity="0",t.style.transform="translateX(100%)",setTimeout(()=>{t.parentNode&&t.parentNode.removeChild(t)},300))}createResultDetailsSection(t){if(!t.details)return"";const i=[];return t.details.job_id&&i.push(`Job ID: ${t.details.job_id}`),t.details.error&&i.push(`Error: ${t.details.error}`),t.details.timeout_duration&&i.push(`Timeout: ${t.details.timeout_duration}s`),i.length===0?"":`
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
        `,document.body.appendChild(i),setTimeout(()=>{i.parentNode&&(i.style.opacity="0",i.style.transform="translateX(100%)",setTimeout(()=>i.remove(),300))},3e3)}}document.addEventListener("DOMContentLoaded",function(){document.getElementById("test-queue-btn")&&new vr});typeof Xt<"u"&&Xt.exports&&(Xt.exports=vr);var Dl=Qe`
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
`;/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function Nl(e){return(t,i)=>{const n=typeof t=="function"?t:t[i];Object.assign(n,e)}}var wr=class extends it{render(){return B` <slot></slot> `}};wr.styles=[tt,Dl];var Ul=Qe`
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
`,_r=(e="value")=>(t,i)=>{const n=t.constructor,s=n.prototype.attributeChangedCallback;n.prototype.attributeChangedCallback=function(r,o,a){var l;const u=n.getPropertyOptions(e),c=typeof u.attribute=="string"?u.attribute:e;if(r===c){const d=u.converter||qn,m=(typeof d=="function"?d:(l=d==null?void 0:d.fromAttribute)!=null?l:qn.fromAttribute)(a,u.type);this[e]!==m&&(this[i]=m)}s.call(this,r,o,a)}},Hl=Qe`
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
`;/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const ql=qs(class extends js{constructor(e){if(super(e),e.type!==Ie.PROPERTY&&e.type!==Ie.ATTRIBUTE&&e.type!==Ie.BOOLEAN_ATTRIBUTE)throw Error("The `live` directive is not allowed on child or event bindings");if(!Zo(e))throw Error("`live` bindings can only contain a single expression")}render(e){return e}update(e,[t]){if(t===yt||t===ea)return t;const i=e.element,n=e.name;if(e.type===Ie.PROPERTY){if(t===i[n])return yt}else if(e.type===Ie.BOOLEAN_ATTRIBUTE){if(!!t===i.hasAttribute(n))return yt}else if(e.type===Ie.ATTRIBUTE&&i.getAttribute(n)===t+"")return yt;return ta(e),t}});var A=class extends it{constructor(){super(...arguments),this.formControlController=new Ws(this,{assumeInteractionOn:["sl-blur","sl-input"]}),this.hasSlotController=new ia(this,"help-text","label"),this.localize=new ri(this),this.hasFocus=!1,this.title="",this.__numberInput=Object.assign(document.createElement("input"),{type:"number"}),this.__dateInput=Object.assign(document.createElement("input"),{type:"date"}),this.type="text",this.name="",this.value="",this.defaultValue="",this.size="medium",this.filled=!1,this.pill=!1,this.label="",this.helpText="",this.clearable=!1,this.disabled=!1,this.placeholder="",this.readonly=!1,this.passwordToggle=!1,this.passwordVisible=!1,this.noSpinButtons=!1,this.form="",this.required=!1,this.spellcheck=!0}get valueAsDate(){var e;return this.__dateInput.type=this.type,this.__dateInput.value=this.value,((e=this.input)==null?void 0:e.valueAsDate)||this.__dateInput.valueAsDate}set valueAsDate(e){this.__dateInput.type=this.type,this.__dateInput.valueAsDate=e,this.value=this.__dateInput.value}get valueAsNumber(){var e;return this.__numberInput.value=this.value,((e=this.input)==null?void 0:e.valueAsNumber)||this.__numberInput.valueAsNumber}set valueAsNumber(e){this.__numberInput.valueAsNumber=e,this.value=this.__numberInput.value}get validity(){return this.input.validity}get validationMessage(){return this.input.validationMessage}firstUpdated(){this.formControlController.updateValidity()}handleBlur(){this.hasFocus=!1,this.emit("sl-blur")}handleChange(){this.value=this.input.value,this.emit("sl-change")}handleClearClick(e){e.preventDefault(),this.value!==""&&(this.value="",this.emit("sl-clear"),this.emit("sl-input"),this.emit("sl-change")),this.input.focus()}handleFocus(){this.hasFocus=!0,this.emit("sl-focus")}handleInput(){this.value=this.input.value,this.formControlController.updateValidity(),this.emit("sl-input")}handleInvalid(e){this.formControlController.setValidity(!1),this.formControlController.emitInvalidEvent(e)}handleKeyDown(e){const t=e.metaKey||e.ctrlKey||e.shiftKey||e.altKey;e.key==="Enter"&&!t&&setTimeout(()=>{!e.defaultPrevented&&!e.isComposing&&this.formControlController.submit()})}handlePasswordToggle(){this.passwordVisible=!this.passwordVisible}handleDisabledChange(){this.formControlController.setValidity(this.disabled)}handleStepChange(){this.input.step=String(this.step),this.formControlController.updateValidity()}async handleValueChange(){await this.updateComplete,this.formControlController.updateValidity()}focus(e){this.input.focus(e)}blur(){this.input.blur()}select(){this.input.select()}setSelectionRange(e,t,i="none"){this.input.setSelectionRange(e,t,i)}setRangeText(e,t,i,n="preserve"){const s=t??this.input.selectionStart,r=i??this.input.selectionEnd;this.input.setRangeText(e,s,r,n),this.value!==this.input.value&&(this.value=this.input.value)}showPicker(){"showPicker"in HTMLInputElement.prototype&&this.input.showPicker()}stepUp(){this.input.stepUp(),this.value!==this.input.value&&(this.value=this.input.value)}stepDown(){this.input.stepDown(),this.value!==this.input.value&&(this.value=this.input.value)}checkValidity(){return this.input.checkValidity()}getForm(){return this.formControlController.getForm()}reportValidity(){return this.input.reportValidity()}setCustomValidity(e){this.input.setCustomValidity(e),this.formControlController.updateValidity()}render(){const e=this.hasSlotController.test("label"),t=this.hasSlotController.test("help-text"),i=this.label?!0:!!e,n=this.helpText?!0:!!t,r=this.clearable&&!this.disabled&&!this.readonly&&(typeof this.value=="number"||this.value.length>0);return B`
      <div
        part="form-control"
        class=${Ce({"form-control":!0,"form-control--small":this.size==="small","form-control--medium":this.size==="medium","form-control--large":this.size==="large","form-control--has-label":i,"form-control--has-help-text":n})}
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
            class=${Ce({input:!0,"input--small":this.size==="small","input--medium":this.size==="medium","input--large":this.size==="large","input--pill":this.pill,"input--standard":!this.filled,"input--filled":this.filled,"input--disabled":this.disabled,"input--focused":this.hasFocus,"input--empty":!this.value,"input--no-spin-buttons":this.noSpinButtons})}
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
              name=${$(this.name)}
              ?disabled=${this.disabled}
              ?readonly=${this.readonly}
              ?required=${this.required}
              placeholder=${$(this.placeholder)}
              minlength=${$(this.minlength)}
              maxlength=${$(this.maxlength)}
              min=${$(this.min)}
              max=${$(this.max)}
              step=${$(this.step)}
              .value=${ql(this.value)}
              autocapitalize=${$(this.autocapitalize)}
              autocomplete=${$(this.autocomplete)}
              autocorrect=${$(this.autocorrect)}
              ?autofocus=${this.autofocus}
              spellcheck=${this.spellcheck}
              pattern=${$(this.pattern)}
              enterkeyhint=${$(this.enterkeyhint)}
              inputmode=${$(this.inputmode)}
              aria-describedby="help-text"
              @change=${this.handleChange}
              @input=${this.handleInput}
              @invalid=${this.handleInvalid}
              @keydown=${this.handleKeyDown}
              @focus=${this.handleFocus}
              @blur=${this.handleBlur}
            />

            ${r?B`
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
            ${this.passwordToggle&&!this.disabled?B`
                  <button
                    part="password-toggle-button"
                    class="input__password-toggle"
                    type="button"
                    aria-label=${this.localize.term(this.passwordVisible?"hidePassword":"showPassword")}
                    @click=${this.handlePasswordToggle}
                    tabindex="-1"
                  >
                    ${this.passwordVisible?B`
                          <slot name="show-password-icon">
                            <sl-icon name="eye-slash" library="system"></sl-icon>
                          </slot>
                        `:B`
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
    `}};A.styles=[tt,Hl,Ul];A.dependencies={"sl-icon":Vs};b([ie(".input__control")],A.prototype,"input",2);b([me()],A.prototype,"hasFocus",2);b([_()],A.prototype,"title",2);b([_({reflect:!0})],A.prototype,"type",2);b([_()],A.prototype,"name",2);b([_()],A.prototype,"value",2);b([_r()],A.prototype,"defaultValue",2);b([_({reflect:!0})],A.prototype,"size",2);b([_({type:Boolean,reflect:!0})],A.prototype,"filled",2);b([_({type:Boolean,reflect:!0})],A.prototype,"pill",2);b([_()],A.prototype,"label",2);b([_({attribute:"help-text"})],A.prototype,"helpText",2);b([_({type:Boolean})],A.prototype,"clearable",2);b([_({type:Boolean,reflect:!0})],A.prototype,"disabled",2);b([_()],A.prototype,"placeholder",2);b([_({type:Boolean,reflect:!0})],A.prototype,"readonly",2);b([_({attribute:"password-toggle",type:Boolean})],A.prototype,"passwordToggle",2);b([_({attribute:"password-visible",type:Boolean})],A.prototype,"passwordVisible",2);b([_({attribute:"no-spin-buttons",type:Boolean})],A.prototype,"noSpinButtons",2);b([_({reflect:!0})],A.prototype,"form",2);b([_({type:Boolean,reflect:!0})],A.prototype,"required",2);b([_()],A.prototype,"pattern",2);b([_({type:Number})],A.prototype,"minlength",2);b([_({type:Number})],A.prototype,"maxlength",2);b([_()],A.prototype,"min",2);b([_()],A.prototype,"max",2);b([_()],A.prototype,"step",2);b([_()],A.prototype,"autocapitalize",2);b([_()],A.prototype,"autocorrect",2);b([_()],A.prototype,"autocomplete",2);b([_({type:Boolean})],A.prototype,"autofocus",2);b([_()],A.prototype,"enterkeyhint",2);b([_({type:Boolean,converter:{fromAttribute:e=>!(!e||e==="false"),toAttribute:e=>e?"true":"false"}})],A.prototype,"spellcheck",2);b([_()],A.prototype,"inputmode",2);b([Xe("disabled",{waitUntilFirstUpdate:!0})],A.prototype,"handleDisabledChange",1);b([Xe("step",{waitUntilFirstUpdate:!0})],A.prototype,"handleStepChange",1);b([Xe("value",{waitUntilFirstUpdate:!0})],A.prototype,"handleValueChange",1);function Ai(e,t){function i(s){const r=e.getBoundingClientRect(),o=e.ownerDocument.defaultView,a=r.left+o.scrollX,l=r.top+o.scrollY,u=s.pageX-a,c=s.pageY-l;t!=null&&t.onMove&&t.onMove(u,c)}function n(){document.removeEventListener("pointermove",i),document.removeEventListener("pointerup",n),t!=null&&t.onStop&&t.onStop()}document.addEventListener("pointermove",i,{passive:!0}),document.addEventListener("pointerup",n),(t==null?void 0:t.initialEvent)instanceof PointerEvent&&i(t.initialEvent)}var jl=Qe`
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
`;function*xr(e=document.activeElement){e!=null&&(yield e,"shadowRoot"in e&&e.shadowRoot&&e.shadowRoot.mode!=="closed"&&(yield*na(xr(e.shadowRoot.activeElement))))}function Vl(){return[...xr()].pop()}var as=new WeakMap;function Er(e){let t=as.get(e);return t||(t=window.getComputedStyle(e,null),as.set(e,t)),t}function Wl(e){if(typeof e.checkVisibility=="function")return e.checkVisibility({checkOpacity:!1,checkVisibilityCSS:!0});const t=Er(e);return t.visibility!=="hidden"&&t.display!=="none"}function Kl(e){const t=Er(e),{overflowY:i,overflowX:n}=t;return i==="scroll"||n==="scroll"?!0:i!=="auto"||n!=="auto"?!1:e.scrollHeight>e.clientHeight&&i==="auto"||e.scrollWidth>e.clientWidth&&n==="auto"}function Ql(e){const t=e.tagName.toLowerCase(),i=Number(e.getAttribute("tabindex"));if(e.hasAttribute("tabindex")&&(isNaN(i)||i<=-1)||e.hasAttribute("disabled")||e.closest("[inert]"))return!1;if(t==="input"&&e.getAttribute("type")==="radio"){const r=e.getRootNode(),o=`input[type='radio'][name="${e.getAttribute("name")}"]`,a=r.querySelector(`${o}:checked`);return a?a===e:r.querySelector(o)===e}return Wl(e)?(t==="audio"||t==="video")&&e.hasAttribute("controls")||e.hasAttribute("tabindex")||e.hasAttribute("contenteditable")&&e.getAttribute("contenteditable")!=="false"||["button","input","select","textarea","a","audio","video","summary","iframe"].includes(t)?!0:Kl(e):!1}function Xl(e){var t,i;const n=Gl(e),s=(t=n[0])!=null?t:null,r=(i=n[n.length-1])!=null?i:null;return{start:s,end:r}}function Jl(e,t){var i;return((i=e.getRootNode({composed:!0}))==null?void 0:i.host)!==t}function Gl(e){const t=new WeakMap,i=[];function n(s){if(s instanceof Element){if(s.hasAttribute("inert")||s.closest("[inert]")||t.has(s))return;t.set(s,!0),!i.includes(s)&&Ql(s)&&i.push(s),s instanceof HTMLSlotElement&&Jl(s,e)&&s.assignedElements({flatten:!0}).forEach(r=>{n(r)}),s.shadowRoot!==null&&s.shadowRoot.mode==="open"&&n(s.shadowRoot)}for(const r of s.children)n(r)}return n(e),i.sort((s,r)=>{const o=Number(s.getAttribute("tabindex"))||0;return(Number(r.getAttribute("tabindex"))||0)-o})}var Yl=Qe`
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
`;const Ae=Math.min,G=Math.max,Yt=Math.round,Pt=Math.floor,pe=e=>({x:e,y:e}),Zl={left:"right",right:"left",bottom:"top",top:"bottom"},eu={start:"end",end:"start"};function Vi(e,t,i){return G(e,Ae(t,i))}function rt(e,t){return typeof e=="function"?e(t):e}function Te(e){return e.split("-")[0]}function ot(e){return e.split("-")[1]}function Sr(e){return e==="x"?"y":"x"}function gn(e){return e==="y"?"height":"width"}const tu=new Set(["top","bottom"]);function ye(e){return tu.has(Te(e))?"y":"x"}function bn(e){return Sr(ye(e))}function iu(e,t,i){i===void 0&&(i=!1);const n=ot(e),s=bn(e),r=gn(s);let o=s==="x"?n===(i?"end":"start")?"right":"left":n==="start"?"bottom":"top";return t.reference[r]>t.floating[r]&&(o=Zt(o)),[o,Zt(o)]}function nu(e){const t=Zt(e);return[Wi(e),t,Wi(t)]}function Wi(e){return e.replace(/start|end/g,t=>eu[t])}const ls=["left","right"],us=["right","left"],su=["top","bottom"],ru=["bottom","top"];function ou(e,t,i){switch(e){case"top":case"bottom":return i?t?us:ls:t?ls:us;case"left":case"right":return t?su:ru;default:return[]}}function au(e,t,i,n){const s=ot(e);let r=ou(Te(e),i==="start",n);return s&&(r=r.map(o=>o+"-"+s),t&&(r=r.concat(r.map(Wi)))),r}function Zt(e){return e.replace(/left|right|bottom|top/g,t=>Zl[t])}function lu(e){return{top:0,right:0,bottom:0,left:0,...e}}function kr(e){return typeof e!="number"?lu(e):{top:e,right:e,bottom:e,left:e}}function ei(e){const{x:t,y:i,width:n,height:s}=e;return{width:n,height:s,top:i,left:t,right:t+n,bottom:i+s,x:t,y:i}}function cs(e,t,i){let{reference:n,floating:s}=e;const r=ye(t),o=bn(t),a=gn(o),l=Te(t),u=r==="y",c=n.x+n.width/2-s.width/2,d=n.y+n.height/2-s.height/2,f=n[a]/2-s[a]/2;let m;switch(l){case"top":m={x:c,y:n.y-s.height};break;case"bottom":m={x:c,y:n.y+n.height};break;case"right":m={x:n.x+n.width,y:d};break;case"left":m={x:n.x-s.width,y:d};break;default:m={x:n.x,y:n.y}}switch(ot(t)){case"start":m[o]-=f*(i&&u?-1:1);break;case"end":m[o]+=f*(i&&u?-1:1);break}return m}const uu=async(e,t,i)=>{const{placement:n="bottom",strategy:s="absolute",middleware:r=[],platform:o}=i,a=r.filter(Boolean),l=await(o.isRTL==null?void 0:o.isRTL(t));let u=await o.getElementRects({reference:e,floating:t,strategy:s}),{x:c,y:d}=cs(u,n,l),f=n,m={},g=0;for(let v=0;v<a.length;v++){const{name:p,fn:w}=a[v],{x,y:E,data:C,reset:S}=await w({x:c,y:d,initialPlacement:n,placement:f,strategy:s,middlewareData:m,rects:u,platform:o,elements:{reference:e,floating:t}});c=x??c,d=E??d,m={...m,[p]:{...m[p],...C}},S&&g<=50&&(g++,typeof S=="object"&&(S.placement&&(f=S.placement),S.rects&&(u=S.rects===!0?await o.getElementRects({reference:e,floating:t,strategy:s}):S.rects),{x:c,y:d}=cs(u,f,l)),v=-1)}return{x:c,y:d,placement:f,strategy:s,middlewareData:m}};async function yn(e,t){var i;t===void 0&&(t={});const{x:n,y:s,platform:r,rects:o,elements:a,strategy:l}=e,{boundary:u="clippingAncestors",rootBoundary:c="viewport",elementContext:d="floating",altBoundary:f=!1,padding:m=0}=rt(t,e),g=kr(m),p=a[f?d==="floating"?"reference":"floating":d],w=ei(await r.getClippingRect({element:(i=await(r.isElement==null?void 0:r.isElement(p)))==null||i?p:p.contextElement||await(r.getDocumentElement==null?void 0:r.getDocumentElement(a.floating)),boundary:u,rootBoundary:c,strategy:l})),x=d==="floating"?{x:n,y:s,width:o.floating.width,height:o.floating.height}:o.reference,E=await(r.getOffsetParent==null?void 0:r.getOffsetParent(a.floating)),C=await(r.isElement==null?void 0:r.isElement(E))?await(r.getScale==null?void 0:r.getScale(E))||{x:1,y:1}:{x:1,y:1},S=ei(r.convertOffsetParentRelativeRectToViewportRelativeRect?await r.convertOffsetParentRelativeRectToViewportRelativeRect({elements:a,rect:x,offsetParent:E,strategy:l}):x);return{top:(w.top-S.top+g.top)/C.y,bottom:(S.bottom-w.bottom+g.bottom)/C.y,left:(w.left-S.left+g.left)/C.x,right:(S.right-w.right+g.right)/C.x}}const cu=e=>({name:"arrow",options:e,async fn(t){const{x:i,y:n,placement:s,rects:r,platform:o,elements:a,middlewareData:l}=t,{element:u,padding:c=0}=rt(e,t)||{};if(u==null)return{};const d=kr(c),f={x:i,y:n},m=bn(s),g=gn(m),v=await o.getDimensions(u),p=m==="y",w=p?"top":"left",x=p?"bottom":"right",E=p?"clientHeight":"clientWidth",C=r.reference[g]+r.reference[m]-f[m]-r.floating[g],S=f[m]-r.reference[m],F=await(o.getOffsetParent==null?void 0:o.getOffsetParent(u));let R=F?F[E]:0;(!R||!await(o.isElement==null?void 0:o.isElement(F)))&&(R=a.floating[E]||r.floating[g]);const N=C/2-S/2,q=R/2-v[g]/2-1,W=Ae(d[w],q),we=Ae(d[x],q),ce=W,_e=R-v[g]-we,j=R/2-v[g]/2+N,Me=Vi(ce,j,_e),be=!l.arrow&&ot(s)!=null&&j!==Me&&r.reference[g]/2-(j<ce?W:we)-v[g]/2<0,ne=be?j<ce?j-ce:j-_e:0;return{[m]:f[m]+ne,data:{[m]:Me,centerOffset:j-Me-ne,...be&&{alignmentOffset:ne}},reset:be}}}),du=function(e){return e===void 0&&(e={}),{name:"flip",options:e,async fn(t){var i,n;const{placement:s,middlewareData:r,rects:o,initialPlacement:a,platform:l,elements:u}=t,{mainAxis:c=!0,crossAxis:d=!0,fallbackPlacements:f,fallbackStrategy:m="bestFit",fallbackAxisSideDirection:g="none",flipAlignment:v=!0,...p}=rt(e,t);if((i=r.arrow)!=null&&i.alignmentOffset)return{};const w=Te(s),x=ye(a),E=Te(a)===a,C=await(l.isRTL==null?void 0:l.isRTL(u.floating)),S=f||(E||!v?[Zt(a)]:nu(a)),F=g!=="none";!f&&F&&S.push(...au(a,v,g,C));const R=[a,...S],N=await yn(t,p),q=[];let W=((n=r.flip)==null?void 0:n.overflows)||[];if(c&&q.push(N[w]),d){const j=iu(s,o,C);q.push(N[j[0]],N[j[1]])}if(W=[...W,{placement:s,overflows:q}],!q.every(j=>j<=0)){var we,ce;const j=(((we=r.flip)==null?void 0:we.index)||0)+1,Me=R[j];if(Me&&(!(d==="alignment"?x!==ye(Me):!1)||W.every(se=>ye(se.placement)===x?se.overflows[0]>0:!0)))return{data:{index:j,overflows:W},reset:{placement:Me}};let be=(ce=W.filter(ne=>ne.overflows[0]<=0).sort((ne,se)=>ne.overflows[1]-se.overflows[1])[0])==null?void 0:ce.placement;if(!be)switch(m){case"bestFit":{var _e;const ne=(_e=W.filter(se=>{if(F){const xe=ye(se.placement);return xe===x||xe==="y"}return!0}).map(se=>[se.placement,se.overflows.filter(xe=>xe>0).reduce((xe,Go)=>xe+Go,0)]).sort((se,xe)=>se[1]-xe[1])[0])==null?void 0:_e[0];ne&&(be=ne);break}case"initialPlacement":be=a;break}if(s!==be)return{reset:{placement:be}}}return{}}}},hu=new Set(["left","top"]);async function pu(e,t){const{placement:i,platform:n,elements:s}=e,r=await(n.isRTL==null?void 0:n.isRTL(s.floating)),o=Te(i),a=ot(i),l=ye(i)==="y",u=hu.has(o)?-1:1,c=r&&l?-1:1,d=rt(t,e);let{mainAxis:f,crossAxis:m,alignmentAxis:g}=typeof d=="number"?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return a&&typeof g=="number"&&(m=a==="end"?g*-1:g),l?{x:m*c,y:f*u}:{x:f*u,y:m*c}}const fu=function(e){return e===void 0&&(e=0),{name:"offset",options:e,async fn(t){var i,n;const{x:s,y:r,placement:o,middlewareData:a}=t,l=await pu(t,e);return o===((i=a.offset)==null?void 0:i.placement)&&(n=a.arrow)!=null&&n.alignmentOffset?{}:{x:s+l.x,y:r+l.y,data:{...l,placement:o}}}}},mu=function(e){return e===void 0&&(e={}),{name:"shift",options:e,async fn(t){const{x:i,y:n,placement:s}=t,{mainAxis:r=!0,crossAxis:o=!1,limiter:a={fn:p=>{let{x:w,y:x}=p;return{x:w,y:x}}},...l}=rt(e,t),u={x:i,y:n},c=await yn(t,l),d=ye(Te(s)),f=Sr(d);let m=u[f],g=u[d];if(r){const p=f==="y"?"top":"left",w=f==="y"?"bottom":"right",x=m+c[p],E=m-c[w];m=Vi(x,m,E)}if(o){const p=d==="y"?"top":"left",w=d==="y"?"bottom":"right",x=g+c[p],E=g-c[w];g=Vi(x,g,E)}const v=a.fn({...t,[f]:m,[d]:g});return{...v,data:{x:v.x-i,y:v.y-n,enabled:{[f]:r,[d]:o}}}}}},gu=function(e){return e===void 0&&(e={}),{name:"size",options:e,async fn(t){var i,n;const{placement:s,rects:r,platform:o,elements:a}=t,{apply:l=()=>{},...u}=rt(e,t),c=await yn(t,u),d=Te(s),f=ot(s),m=ye(s)==="y",{width:g,height:v}=r.floating;let p,w;d==="top"||d==="bottom"?(p=d,w=f===(await(o.isRTL==null?void 0:o.isRTL(a.floating))?"start":"end")?"left":"right"):(w=d,p=f==="end"?"top":"bottom");const x=v-c.top-c.bottom,E=g-c.left-c.right,C=Ae(v-c[p],x),S=Ae(g-c[w],E),F=!t.middlewareData.shift;let R=C,N=S;if((i=t.middlewareData.shift)!=null&&i.enabled.x&&(N=E),(n=t.middlewareData.shift)!=null&&n.enabled.y&&(R=x),F&&!f){const W=G(c.left,0),we=G(c.right,0),ce=G(c.top,0),_e=G(c.bottom,0);m?N=g-2*(W!==0||we!==0?W+we:G(c.left,c.right)):R=v-2*(ce!==0||_e!==0?ce+_e:G(c.top,c.bottom))}await l({...t,availableWidth:N,availableHeight:R});const q=await o.getDimensions(a.floating);return g!==q.width||v!==q.height?{reset:{rects:!0}}:{}}}};function hi(){return typeof window<"u"}function at(e){return Cr(e)?(e.nodeName||"").toLowerCase():"#document"}function Y(e){var t;return(e==null||(t=e.ownerDocument)==null?void 0:t.defaultView)||window}function ge(e){var t;return(t=(Cr(e)?e.ownerDocument:e.document)||window.document)==null?void 0:t.documentElement}function Cr(e){return hi()?e instanceof Node||e instanceof Y(e).Node:!1}function re(e){return hi()?e instanceof Element||e instanceof Y(e).Element:!1}function fe(e){return hi()?e instanceof HTMLElement||e instanceof Y(e).HTMLElement:!1}function ds(e){return!hi()||typeof ShadowRoot>"u"?!1:e instanceof ShadowRoot||e instanceof Y(e).ShadowRoot}const bu=new Set(["inline","contents"]);function Rt(e){const{overflow:t,overflowX:i,overflowY:n,display:s}=oe(e);return/auto|scroll|overlay|hidden|clip/.test(t+n+i)&&!bu.has(s)}const yu=new Set(["table","td","th"]);function vu(e){return yu.has(at(e))}const wu=[":popover-open",":modal"];function pi(e){return wu.some(t=>{try{return e.matches(t)}catch{return!1}})}const _u=["transform","translate","scale","rotate","perspective"],xu=["transform","translate","scale","rotate","perspective","filter"],Eu=["paint","layout","strict","content"];function fi(e){const t=vn(),i=re(e)?oe(e):e;return _u.some(n=>i[n]?i[n]!=="none":!1)||(i.containerType?i.containerType!=="normal":!1)||!t&&(i.backdropFilter?i.backdropFilter!=="none":!1)||!t&&(i.filter?i.filter!=="none":!1)||xu.some(n=>(i.willChange||"").includes(n))||Eu.some(n=>(i.contain||"").includes(n))}function Su(e){let t=Re(e);for(;fe(t)&&!Ye(t);){if(fi(t))return t;if(pi(t))return null;t=Re(t)}return null}function vn(){return typeof CSS>"u"||!CSS.supports?!1:CSS.supports("-webkit-backdrop-filter","none")}const ku=new Set(["html","body","#document"]);function Ye(e){return ku.has(at(e))}function oe(e){return Y(e).getComputedStyle(e)}function mi(e){return re(e)?{scrollLeft:e.scrollLeft,scrollTop:e.scrollTop}:{scrollLeft:e.scrollX,scrollTop:e.scrollY}}function Re(e){if(at(e)==="html")return e;const t=e.assignedSlot||e.parentNode||ds(e)&&e.host||ge(e);return ds(t)?t.host:t}function Ar(e){const t=Re(e);return Ye(t)?e.ownerDocument?e.ownerDocument.body:e.body:fe(t)&&Rt(t)?t:Ar(t)}function Et(e,t,i){var n;t===void 0&&(t=[]),i===void 0&&(i=!0);const s=Ar(e),r=s===((n=e.ownerDocument)==null?void 0:n.body),o=Y(s);if(r){const a=Ki(o);return t.concat(o,o.visualViewport||[],Rt(s)?s:[],a&&i?Et(a):[])}return t.concat(s,Et(s,[],i))}function Ki(e){return e.parent&&Object.getPrototypeOf(e.parent)?e.frameElement:null}function Tr(e){const t=oe(e);let i=parseFloat(t.width)||0,n=parseFloat(t.height)||0;const s=fe(e),r=s?e.offsetWidth:i,o=s?e.offsetHeight:n,a=Yt(i)!==r||Yt(n)!==o;return a&&(i=r,n=o),{width:i,height:n,$:a}}function wn(e){return re(e)?e:e.contextElement}function Ge(e){const t=wn(e);if(!fe(t))return pe(1);const i=t.getBoundingClientRect(),{width:n,height:s,$:r}=Tr(t);let o=(r?Yt(i.width):i.width)/n,a=(r?Yt(i.height):i.height)/s;return(!o||!Number.isFinite(o))&&(o=1),(!a||!Number.isFinite(a))&&(a=1),{x:o,y:a}}const Cu=pe(0);function Rr(e){const t=Y(e);return!vn()||!t.visualViewport?Cu:{x:t.visualViewport.offsetLeft,y:t.visualViewport.offsetTop}}function Au(e,t,i){return t===void 0&&(t=!1),!i||t&&i!==Y(e)?!1:t}function We(e,t,i,n){t===void 0&&(t=!1),i===void 0&&(i=!1);const s=e.getBoundingClientRect(),r=wn(e);let o=pe(1);t&&(n?re(n)&&(o=Ge(n)):o=Ge(e));const a=Au(r,i,n)?Rr(r):pe(0);let l=(s.left+a.x)/o.x,u=(s.top+a.y)/o.y,c=s.width/o.x,d=s.height/o.y;if(r){const f=Y(r),m=n&&re(n)?Y(n):n;let g=f,v=Ki(g);for(;v&&n&&m!==g;){const p=Ge(v),w=v.getBoundingClientRect(),x=oe(v),E=w.left+(v.clientLeft+parseFloat(x.paddingLeft))*p.x,C=w.top+(v.clientTop+parseFloat(x.paddingTop))*p.y;l*=p.x,u*=p.y,c*=p.x,d*=p.y,l+=E,u+=C,g=Y(v),v=Ki(g)}}return ei({width:c,height:d,x:l,y:u})}function gi(e,t){const i=mi(e).scrollLeft;return t?t.left+i:We(ge(e)).left+i}function Fr(e,t){const i=e.getBoundingClientRect(),n=i.left+t.scrollLeft-gi(e,i),s=i.top+t.scrollTop;return{x:n,y:s}}function Tu(e){let{elements:t,rect:i,offsetParent:n,strategy:s}=e;const r=s==="fixed",o=ge(n),a=t?pi(t.floating):!1;if(n===o||a&&r)return i;let l={scrollLeft:0,scrollTop:0},u=pe(1);const c=pe(0),d=fe(n);if((d||!d&&!r)&&((at(n)!=="body"||Rt(o))&&(l=mi(n)),fe(n))){const m=We(n);u=Ge(n),c.x=m.x+n.clientLeft,c.y=m.y+n.clientTop}const f=o&&!d&&!r?Fr(o,l):pe(0);return{width:i.width*u.x,height:i.height*u.y,x:i.x*u.x-l.scrollLeft*u.x+c.x+f.x,y:i.y*u.y-l.scrollTop*u.y+c.y+f.y}}function Ru(e){return Array.from(e.getClientRects())}function Fu(e){const t=ge(e),i=mi(e),n=e.ownerDocument.body,s=G(t.scrollWidth,t.clientWidth,n.scrollWidth,n.clientWidth),r=G(t.scrollHeight,t.clientHeight,n.scrollHeight,n.clientHeight);let o=-i.scrollLeft+gi(e);const a=-i.scrollTop;return oe(n).direction==="rtl"&&(o+=G(t.clientWidth,n.clientWidth)-s),{width:s,height:r,x:o,y:a}}const hs=25;function Ou(e,t){const i=Y(e),n=ge(e),s=i.visualViewport;let r=n.clientWidth,o=n.clientHeight,a=0,l=0;if(s){r=s.width,o=s.height;const c=vn();(!c||c&&t==="fixed")&&(a=s.offsetLeft,l=s.offsetTop)}const u=gi(n);if(u<=0){const c=n.ownerDocument,d=c.body,f=getComputedStyle(d),m=c.compatMode==="CSS1Compat"&&parseFloat(f.marginLeft)+parseFloat(f.marginRight)||0,g=Math.abs(n.clientWidth-d.clientWidth-m);g<=hs&&(r-=g)}else u<=hs&&(r+=u);return{width:r,height:o,x:a,y:l}}const Lu=new Set(["absolute","fixed"]);function Mu(e,t){const i=We(e,!0,t==="fixed"),n=i.top+e.clientTop,s=i.left+e.clientLeft,r=fe(e)?Ge(e):pe(1),o=e.clientWidth*r.x,a=e.clientHeight*r.y,l=s*r.x,u=n*r.y;return{width:o,height:a,x:l,y:u}}function ps(e,t,i){let n;if(t==="viewport")n=Ou(e,i);else if(t==="document")n=Fu(ge(e));else if(re(t))n=Mu(t,i);else{const s=Rr(e);n={x:t.x-s.x,y:t.y-s.y,width:t.width,height:t.height}}return ei(n)}function Or(e,t){const i=Re(e);return i===t||!re(i)||Ye(i)?!1:oe(i).position==="fixed"||Or(i,t)}function Pu(e,t){const i=t.get(e);if(i)return i;let n=Et(e,[],!1).filter(a=>re(a)&&at(a)!=="body"),s=null;const r=oe(e).position==="fixed";let o=r?Re(e):e;for(;re(o)&&!Ye(o);){const a=oe(o),l=fi(o);!l&&a.position==="fixed"&&(s=null),(r?!l&&!s:!l&&a.position==="static"&&!!s&&Lu.has(s.position)||Rt(o)&&!l&&Or(e,o))?n=n.filter(c=>c!==o):s=a,o=Re(o)}return t.set(e,n),n}function Iu(e){let{element:t,boundary:i,rootBoundary:n,strategy:s}=e;const o=[...i==="clippingAncestors"?pi(t)?[]:Pu(t,this._c):[].concat(i),n],a=o[0],l=o.reduce((u,c)=>{const d=ps(t,c,s);return u.top=G(d.top,u.top),u.right=Ae(d.right,u.right),u.bottom=Ae(d.bottom,u.bottom),u.left=G(d.left,u.left),u},ps(t,a,s));return{width:l.right-l.left,height:l.bottom-l.top,x:l.left,y:l.top}}function zu(e){const{width:t,height:i}=Tr(e);return{width:t,height:i}}function $u(e,t,i){const n=fe(t),s=ge(t),r=i==="fixed",o=We(e,!0,r,t);let a={scrollLeft:0,scrollTop:0};const l=pe(0);function u(){l.x=gi(s)}if(n||!n&&!r)if((at(t)!=="body"||Rt(s))&&(a=mi(t)),n){const m=We(t,!0,r,t);l.x=m.x+t.clientLeft,l.y=m.y+t.clientTop}else s&&u();r&&!n&&s&&u();const c=s&&!n&&!r?Fr(s,a):pe(0),d=o.left+a.scrollLeft-l.x-c.x,f=o.top+a.scrollTop-l.y-c.y;return{x:d,y:f,width:o.width,height:o.height}}function Ti(e){return oe(e).position==="static"}function fs(e,t){if(!fe(e)||oe(e).position==="fixed")return null;if(t)return t(e);let i=e.offsetParent;return ge(e)===i&&(i=i.ownerDocument.body),i}function Lr(e,t){const i=Y(e);if(pi(e))return i;if(!fe(e)){let s=Re(e);for(;s&&!Ye(s);){if(re(s)&&!Ti(s))return s;s=Re(s)}return i}let n=fs(e,t);for(;n&&vu(n)&&Ti(n);)n=fs(n,t);return n&&Ye(n)&&Ti(n)&&!fi(n)?i:n||Su(e)||i}const Bu=async function(e){const t=this.getOffsetParent||Lr,i=this.getDimensions,n=await i(e.floating);return{reference:$u(e.reference,await t(e.floating),e.strategy),floating:{x:0,y:0,width:n.width,height:n.height}}};function Du(e){return oe(e).direction==="rtl"}const Wt={convertOffsetParentRelativeRectToViewportRelativeRect:Tu,getDocumentElement:ge,getClippingRect:Iu,getOffsetParent:Lr,getElementRects:Bu,getClientRects:Ru,getDimensions:zu,getScale:Ge,isElement:re,isRTL:Du};function Mr(e,t){return e.x===t.x&&e.y===t.y&&e.width===t.width&&e.height===t.height}function Nu(e,t){let i=null,n;const s=ge(e);function r(){var a;clearTimeout(n),(a=i)==null||a.disconnect(),i=null}function o(a,l){a===void 0&&(a=!1),l===void 0&&(l=1),r();const u=e.getBoundingClientRect(),{left:c,top:d,width:f,height:m}=u;if(a||t(),!f||!m)return;const g=Pt(d),v=Pt(s.clientWidth-(c+f)),p=Pt(s.clientHeight-(d+m)),w=Pt(c),E={rootMargin:-g+"px "+-v+"px "+-p+"px "+-w+"px",threshold:G(0,Ae(1,l))||1};let C=!0;function S(F){const R=F[0].intersectionRatio;if(R!==l){if(!C)return o();R?o(!1,R):n=setTimeout(()=>{o(!1,1e-7)},1e3)}R===1&&!Mr(u,e.getBoundingClientRect())&&o(),C=!1}try{i=new IntersectionObserver(S,{...E,root:s.ownerDocument})}catch{i=new IntersectionObserver(S,E)}i.observe(e)}return o(!0),r}function Uu(e,t,i,n){n===void 0&&(n={});const{ancestorScroll:s=!0,ancestorResize:r=!0,elementResize:o=typeof ResizeObserver=="function",layoutShift:a=typeof IntersectionObserver=="function",animationFrame:l=!1}=n,u=wn(e),c=s||r?[...u?Et(u):[],...Et(t)]:[];c.forEach(w=>{s&&w.addEventListener("scroll",i,{passive:!0}),r&&w.addEventListener("resize",i)});const d=u&&a?Nu(u,i):null;let f=-1,m=null;o&&(m=new ResizeObserver(w=>{let[x]=w;x&&x.target===u&&m&&(m.unobserve(t),cancelAnimationFrame(f),f=requestAnimationFrame(()=>{var E;(E=m)==null||E.observe(t)})),i()}),u&&!l&&m.observe(u),m.observe(t));let g,v=l?We(e):null;l&&p();function p(){const w=We(e);v&&!Mr(v,w)&&i(),v=w,g=requestAnimationFrame(p)}return i(),()=>{var w;c.forEach(x=>{s&&x.removeEventListener("scroll",i),r&&x.removeEventListener("resize",i)}),d==null||d(),(w=m)==null||w.disconnect(),m=null,l&&cancelAnimationFrame(g)}}const Hu=fu,qu=mu,ju=du,ms=gu,Vu=cu,Wu=(e,t,i)=>{const n=new Map,s={platform:Wt,...i},r={...s.platform,_c:n};return uu(e,t,{...s,platform:r})};function Ku(e){return Qu(e)}function Ri(e){return e.assignedSlot?e.assignedSlot:e.parentNode instanceof ShadowRoot?e.parentNode.host:e.parentNode}function Qu(e){for(let t=e;t;t=Ri(t))if(t instanceof Element&&getComputedStyle(t).display==="none")return null;for(let t=Ri(e);t;t=Ri(t)){if(!(t instanceof Element))continue;const i=getComputedStyle(t);if(i.display!=="contents"&&(i.position!=="static"||fi(i)||t.tagName==="BODY"))return t}return null}function Xu(e){return e!==null&&typeof e=="object"&&"getBoundingClientRect"in e&&("contextElement"in e?e.contextElement instanceof Element:!0)}var O=class extends it{constructor(){super(...arguments),this.localize=new ri(this),this.active=!1,this.placement="top",this.strategy="absolute",this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement="anchor",this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements="",this.flipFallbackStrategy="best-fit",this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){const e=this.anchorEl.getBoundingClientRect(),t=this.popup.getBoundingClientRect(),i=this.placement.includes("top")||this.placement.includes("bottom");let n=0,s=0,r=0,o=0,a=0,l=0,u=0,c=0;i?e.top<t.top?(n=e.left,s=e.bottom,r=e.right,o=e.bottom,a=t.left,l=t.top,u=t.right,c=t.top):(n=t.left,s=t.bottom,r=t.right,o=t.bottom,a=e.left,l=e.top,u=e.right,c=e.top):e.left<t.left?(n=e.right,s=e.top,r=t.left,o=t.top,a=e.right,l=e.bottom,u=t.left,c=t.bottom):(n=t.right,s=t.top,r=e.left,o=e.top,a=t.right,l=t.bottom,u=e.left,c=e.bottom),this.style.setProperty("--hover-bridge-top-left-x",`${n}px`),this.style.setProperty("--hover-bridge-top-left-y",`${s}px`),this.style.setProperty("--hover-bridge-top-right-x",`${r}px`),this.style.setProperty("--hover-bridge-top-right-y",`${o}px`),this.style.setProperty("--hover-bridge-bottom-left-x",`${a}px`),this.style.setProperty("--hover-bridge-bottom-left-y",`${l}px`),this.style.setProperty("--hover-bridge-bottom-right-x",`${u}px`),this.style.setProperty("--hover-bridge-bottom-right-y",`${c}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(e){super.updated(e),e.has("active")&&(this.active?this.start():this.stop()),e.has("anchor")&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor=="string"){const e=this.getRootNode();this.anchorEl=e.getElementById(this.anchor)}else this.anchor instanceof Element||Xu(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector('[slot="anchor"]');this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.active&&this.start()}start(){!this.anchorEl||!this.active||(this.cleanup=Uu(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(e=>{this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute("data-current-placement"),this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height"),requestAnimationFrame(()=>e())):e()})}reposition(){if(!this.active||!this.anchorEl)return;const e=[Hu({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?e.push(ms({apply:({rects:i})=>{const n=this.sync==="width"||this.sync==="both",s=this.sync==="height"||this.sync==="both";this.popup.style.width=n?`${i.reference.width}px`:"",this.popup.style.height=s?`${i.reference.height}px`:""}})):(this.popup.style.width="",this.popup.style.height=""),this.flip&&e.push(ju({boundary:this.flipBoundary,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy==="best-fit"?"bestFit":"initialPlacement",padding:this.flipPadding})),this.shift&&e.push(qu({boundary:this.shiftBoundary,padding:this.shiftPadding})),this.autoSize?e.push(ms({boundary:this.autoSizeBoundary,padding:this.autoSizePadding,apply:({availableWidth:i,availableHeight:n})=>{this.autoSize==="vertical"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-height",`${n}px`):this.style.removeProperty("--auto-size-available-height"),this.autoSize==="horizontal"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-width",`${i}px`):this.style.removeProperty("--auto-size-available-width")}})):(this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height")),this.arrow&&e.push(Vu({element:this.arrowEl,padding:this.arrowPadding}));const t=this.strategy==="absolute"?i=>Wt.getOffsetParent(i,Ku):Wt.getOffsetParent;Wu(this.anchorEl,this.popup,{placement:this.placement,middleware:e,strategy:this.strategy,platform:sa(ra({},Wt),{getOffsetParent:t})}).then(({x:i,y:n,middlewareData:s,placement:r})=>{const o=this.localize.dir()==="rtl",a={top:"bottom",right:"left",bottom:"top",left:"right"}[r.split("-")[0]];if(this.setAttribute("data-current-placement",r),Object.assign(this.popup.style,{left:`${i}px`,top:`${n}px`}),this.arrow){const l=s.arrow.x,u=s.arrow.y;let c="",d="",f="",m="";if(this.arrowPlacement==="start"){const g=typeof l=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";c=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"",d=o?g:"",m=o?"":g}else if(this.arrowPlacement==="end"){const g=typeof l=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";d=o?"":g,m=o?g:"",f=typeof u=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:""}else this.arrowPlacement==="center"?(m=typeof l=="number"?"calc(50% - var(--arrow-size-diagonal))":"",c=typeof u=="number"?"calc(50% - var(--arrow-size-diagonal))":""):(m=typeof l=="number"?`${l}px`:"",c=typeof u=="number"?`${u}px`:"");Object.assign(this.arrowEl.style,{top:c,right:d,bottom:f,left:m,[a]:"calc(var(--arrow-size-diagonal) * -1)"})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.emit("sl-reposition")}render(){return B`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${Ce({"popup-hover-bridge":!0,"popup-hover-bridge--visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        part="popup"
        class=${Ce({popup:!0,"popup--active":this.active,"popup--fixed":this.strategy==="fixed","popup--has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?B`<div part="arrow" class="popup__arrow" role="presentation"></div>`:""}
      </div>
    `}};O.styles=[tt,Yl];b([ie(".popup")],O.prototype,"popup",2);b([ie(".popup__arrow")],O.prototype,"arrowEl",2);b([_()],O.prototype,"anchor",2);b([_({type:Boolean,reflect:!0})],O.prototype,"active",2);b([_({reflect:!0})],O.prototype,"placement",2);b([_({reflect:!0})],O.prototype,"strategy",2);b([_({type:Number})],O.prototype,"distance",2);b([_({type:Number})],O.prototype,"skidding",2);b([_({type:Boolean})],O.prototype,"arrow",2);b([_({attribute:"arrow-placement"})],O.prototype,"arrowPlacement",2);b([_({attribute:"arrow-padding",type:Number})],O.prototype,"arrowPadding",2);b([_({type:Boolean})],O.prototype,"flip",2);b([_({attribute:"flip-fallback-placements",converter:{fromAttribute:e=>e.split(" ").map(t=>t.trim()).filter(t=>t!==""),toAttribute:e=>e.join(" ")}})],O.prototype,"flipFallbackPlacements",2);b([_({attribute:"flip-fallback-strategy"})],O.prototype,"flipFallbackStrategy",2);b([_({type:Object})],O.prototype,"flipBoundary",2);b([_({attribute:"flip-padding",type:Number})],O.prototype,"flipPadding",2);b([_({type:Boolean})],O.prototype,"shift",2);b([_({type:Object})],O.prototype,"shiftBoundary",2);b([_({attribute:"shift-padding",type:Number})],O.prototype,"shiftPadding",2);b([_({attribute:"auto-size"})],O.prototype,"autoSize",2);b([_()],O.prototype,"sync",2);b([_({type:Object})],O.prototype,"autoSizeBoundary",2);b([_({attribute:"auto-size-padding",type:Number})],O.prototype,"autoSizePadding",2);b([_({attribute:"hover-bridge",type:Boolean})],O.prototype,"hoverBridge",2);var H=class extends it{constructor(){super(...arguments),this.localize=new ri(this),this.open=!1,this.placement="bottom-start",this.disabled=!1,this.stayOpenOnSelect=!1,this.distance=0,this.skidding=0,this.hoist=!1,this.sync=void 0,this.handleKeyDown=e=>{this.open&&e.key==="Escape"&&(e.stopPropagation(),this.hide(),this.focusOnTrigger())},this.handleDocumentKeyDown=e=>{var t;if(e.key==="Escape"&&this.open&&!this.closeWatcher){e.stopPropagation(),this.focusOnTrigger(),this.hide();return}if(e.key==="Tab"){if(this.open&&((t=document.activeElement)==null?void 0:t.tagName.toLowerCase())==="sl-menu-item"){e.preventDefault(),this.hide(),this.focusOnTrigger();return}const i=(n,s)=>{if(!n)return null;const r=n.closest(s);if(r)return r;const o=n.getRootNode();return o instanceof ShadowRoot?i(o.host,s):null};setTimeout(()=>{var n;const s=((n=this.containingElement)==null?void 0:n.getRootNode())instanceof ShadowRoot?Vl():document.activeElement;(!this.containingElement||i(s,this.containingElement.tagName.toLowerCase())!==this.containingElement)&&this.hide()})}},this.handleDocumentMouseDown=e=>{const t=e.composedPath();this.containingElement&&!t.includes(this.containingElement)&&this.hide()},this.handlePanelSelect=e=>{const t=e.target;!this.stayOpenOnSelect&&t.tagName.toLowerCase()==="sl-menu"&&(this.hide(),this.focusOnTrigger())}}connectedCallback(){super.connectedCallback(),this.containingElement||(this.containingElement=this)}firstUpdated(){this.panel.hidden=!this.open,this.open&&(this.addOpenListeners(),this.popup.active=!0)}disconnectedCallback(){super.disconnectedCallback(),this.removeOpenListeners(),this.hide()}focusOnTrigger(){const e=this.trigger.assignedElements({flatten:!0})[0];typeof(e==null?void 0:e.focus)=="function"&&e.focus()}getMenu(){return this.panel.assignedElements({flatten:!0}).find(e=>e.tagName.toLowerCase()==="sl-menu")}handleTriggerClick(){this.open?this.hide():(this.show(),this.focusOnTrigger())}async handleTriggerKeyDown(e){if([" ","Enter"].includes(e.key)){e.preventDefault(),this.handleTriggerClick();return}const t=this.getMenu();if(t){const i=t.getAllItems(),n=i[0],s=i[i.length-1];["ArrowDown","ArrowUp","Home","End"].includes(e.key)&&(e.preventDefault(),this.open||(this.show(),await this.updateComplete),i.length>0&&this.updateComplete.then(()=>{(e.key==="ArrowDown"||e.key==="Home")&&(t.setCurrentItem(n),n.focus()),(e.key==="ArrowUp"||e.key==="End")&&(t.setCurrentItem(s),s.focus())}))}}handleTriggerKeyUp(e){e.key===" "&&e.preventDefault()}handleTriggerSlotChange(){this.updateAccessibleTrigger()}updateAccessibleTrigger(){const t=this.trigger.assignedElements({flatten:!0}).find(n=>Xl(n).start);let i;if(t){switch(t.tagName.toLowerCase()){case"sl-button":case"sl-icon-button":i=t.button;break;default:i=t}i.setAttribute("aria-haspopup","true"),i.setAttribute("aria-expanded",this.open?"true":"false")}}async show(){if(!this.open)return this.open=!0,jn(this,"sl-after-show")}async hide(){if(this.open)return this.open=!1,jn(this,"sl-after-hide")}reposition(){this.popup.reposition()}addOpenListeners(){var e;this.panel.addEventListener("sl-select",this.handlePanelSelect),"CloseWatcher"in window?((e=this.closeWatcher)==null||e.destroy(),this.closeWatcher=new CloseWatcher,this.closeWatcher.onclose=()=>{this.hide(),this.focusOnTrigger()}):this.panel.addEventListener("keydown",this.handleKeyDown),document.addEventListener("keydown",this.handleDocumentKeyDown),document.addEventListener("mousedown",this.handleDocumentMouseDown)}removeOpenListeners(){var e;this.panel&&(this.panel.removeEventListener("sl-select",this.handlePanelSelect),this.panel.removeEventListener("keydown",this.handleKeyDown)),document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("mousedown",this.handleDocumentMouseDown),(e=this.closeWatcher)==null||e.destroy()}async handleOpenChange(){if(this.disabled){this.open=!1;return}if(this.updateAccessibleTrigger(),this.open){this.emit("sl-show"),this.addOpenListeners(),await Vn(this),this.panel.hidden=!1,this.popup.active=!0;const{keyframes:e,options:t}=Wn(this,"dropdown.show",{dir:this.localize.dir()});await Kn(this.popup.popup,e,t),this.emit("sl-after-show")}else{this.emit("sl-hide"),this.removeOpenListeners(),await Vn(this);const{keyframes:e,options:t}=Wn(this,"dropdown.hide",{dir:this.localize.dir()});await Kn(this.popup.popup,e,t),this.panel.hidden=!0,this.popup.active=!1,this.emit("sl-after-hide")}}render(){return B`
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
        sync=${$(this.sync?this.sync:void 0)}
        class=${Ce({dropdown:!0,"dropdown--open":this.open})}
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
    `}};H.styles=[tt,jl];H.dependencies={"sl-popup":O};b([ie(".dropdown")],H.prototype,"popup",2);b([ie(".dropdown__trigger")],H.prototype,"trigger",2);b([ie(".dropdown__panel")],H.prototype,"panel",2);b([_({type:Boolean,reflect:!0})],H.prototype,"open",2);b([_({reflect:!0})],H.prototype,"placement",2);b([_({type:Boolean,reflect:!0})],H.prototype,"disabled",2);b([_({attribute:"stay-open-on-select",type:Boolean,reflect:!0})],H.prototype,"stayOpenOnSelect",2);b([_({attribute:!1})],H.prototype,"containingElement",2);b([_({type:Number})],H.prototype,"distance",2);b([_({type:Number})],H.prototype,"skidding",2);b([_({type:Boolean})],H.prototype,"hoist",2);b([_({reflect:!0})],H.prototype,"sync",2);b([Xe("open",{waitUntilFirstUpdate:!0})],H.prototype,"handleOpenChange",1);Ks("dropdown.show",{keyframes:[{opacity:0,scale:.9},{opacity:1,scale:1}],options:{duration:100,easing:"ease"}});Ks("dropdown.hide",{keyframes:[{opacity:1,scale:1},{opacity:0,scale:.9}],options:{duration:100,easing:"ease"}});var Ju=Qe`
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
`;function Z(e,t,i){const n=s=>Object.is(s,-0)?0:s;return e<t?n(t):e>i?n(i):n(e)}var Gu=Qe`
  :host {
    display: inline-block;
  }

  .button-group {
    display: flex;
    flex-wrap: nowrap;
  }
`,Ft=class extends it{constructor(){super(...arguments),this.disableRole=!1,this.label=""}handleFocus(e){const t=ft(e.target);t==null||t.toggleAttribute("data-sl-button-group__button--focus",!0)}handleBlur(e){const t=ft(e.target);t==null||t.toggleAttribute("data-sl-button-group__button--focus",!1)}handleMouseOver(e){const t=ft(e.target);t==null||t.toggleAttribute("data-sl-button-group__button--hover",!0)}handleMouseOut(e){const t=ft(e.target);t==null||t.toggleAttribute("data-sl-button-group__button--hover",!1)}handleSlotChange(){const e=[...this.defaultSlot.assignedElements({flatten:!0})];e.forEach(t=>{const i=e.indexOf(t),n=ft(t);n&&(n.toggleAttribute("data-sl-button-group__button",!0),n.toggleAttribute("data-sl-button-group__button--first",i===0),n.toggleAttribute("data-sl-button-group__button--inner",i>0&&i<e.length-1),n.toggleAttribute("data-sl-button-group__button--last",i===e.length-1),n.toggleAttribute("data-sl-button-group__button--radio",n.tagName.toLowerCase()==="sl-radio-button"))})}render(){return B`
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
    `}};Ft.styles=[tt,Gu];b([ie("slot")],Ft.prototype,"defaultSlot",2);b([me()],Ft.prototype,"disableRole",2);b([_()],Ft.prototype,"label",2);function ft(e){var t;const i="sl-button, sl-radio-button";return(t=e.closest(i))!=null?t:e.querySelector(i)}/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Pr="important",Yu=" !"+Pr,Ee=qs(class extends js{constructor(e){var t;if(super(e),e.type!==Ie.ATTRIBUTE||e.name!=="style"||((t=e.strings)==null?void 0:t.length)>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(e){return Object.keys(e).reduce(((t,i)=>{const n=e[i];return n==null?t:t+`${i=i.includes("-")?i:i.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,"-$&").toLowerCase()}:${n};`}),"")}update(e,[t]){const{style:i}=e.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(t)),this.render(t);for(const n of this.ft)t[n]==null&&(this.ft.delete(n),n.includes("-")?i.removeProperty(n):i[n]=null);for(const n in t){const s=t[n];if(s!=null){this.ft.add(n);const r=typeof s=="string"&&s.endsWith(Yu);n.includes("-")||r?i.setProperty(n,r?s.slice(0,-11):s,r?Pr:""):i[n]=s}}return yt}});function D(e,t){Zu(e)&&(e="100%");const i=ec(e);return e=t===360?e:Math.min(t,Math.max(0,parseFloat(e))),i&&(e=parseInt(String(e*t),10)/100),Math.abs(e-t)<1e-6?1:(t===360?e=(e<0?e%t+t:e%t)/parseFloat(String(t)):e=e%t/parseFloat(String(t)),e)}function It(e){return Math.min(1,Math.max(0,e))}function Zu(e){return typeof e=="string"&&e.indexOf(".")!==-1&&parseFloat(e)===1}function ec(e){return typeof e=="string"&&e.indexOf("%")!==-1}function Ir(e){return e=parseFloat(e),(isNaN(e)||e<0||e>1)&&(e=1),e}function zt(e){return Number(e)<=1?`${Number(e)*100}%`:e}function Be(e){return e.length===1?"0"+e:String(e)}function tc(e,t,i){return{r:D(e,255)*255,g:D(t,255)*255,b:D(i,255)*255}}function gs(e,t,i){e=D(e,255),t=D(t,255),i=D(i,255);const n=Math.max(e,t,i),s=Math.min(e,t,i);let r=0,o=0;const a=(n+s)/2;if(n===s)o=0,r=0;else{const l=n-s;switch(o=a>.5?l/(2-n-s):l/(n+s),n){case e:r=(t-i)/l+(t<i?6:0);break;case t:r=(i-e)/l+2;break;case i:r=(e-t)/l+4;break}r/=6}return{h:r,s:o,l:a}}function Fi(e,t,i){return i<0&&(i+=1),i>1&&(i-=1),i<1/6?e+(t-e)*(6*i):i<1/2?t:i<2/3?e+(t-e)*(2/3-i)*6:e}function ic(e,t,i){let n,s,r;if(e=D(e,360),t=D(t,100),i=D(i,100),t===0)s=i,r=i,n=i;else{const o=i<.5?i*(1+t):i+t-i*t,a=2*i-o;n=Fi(a,o,e+1/3),s=Fi(a,o,e),r=Fi(a,o,e-1/3)}return{r:n*255,g:s*255,b:r*255}}function bs(e,t,i){e=D(e,255),t=D(t,255),i=D(i,255);const n=Math.max(e,t,i),s=Math.min(e,t,i);let r=0;const o=n,a=n-s,l=n===0?0:a/n;if(n===s)r=0;else{switch(n){case e:r=(t-i)/a+(t<i?6:0);break;case t:r=(i-e)/a+2;break;case i:r=(e-t)/a+4;break}r/=6}return{h:r,s:l,v:o}}function nc(e,t,i){e=D(e,360)*6,t=D(t,100),i=D(i,100);const n=Math.floor(e),s=e-n,r=i*(1-t),o=i*(1-s*t),a=i*(1-(1-s)*t),l=n%6,u=[i,o,r,r,a,i][l],c=[a,i,i,o,r,r][l],d=[r,r,a,i,i,o][l];return{r:u*255,g:c*255,b:d*255}}function ys(e,t,i,n){const s=[Be(Math.round(e).toString(16)),Be(Math.round(t).toString(16)),Be(Math.round(i).toString(16))];return n&&s[0].startsWith(s[0].charAt(1))&&s[1].startsWith(s[1].charAt(1))&&s[2].startsWith(s[2].charAt(1))?s[0].charAt(0)+s[1].charAt(0)+s[2].charAt(0):s.join("")}function sc(e,t,i,n,s){const r=[Be(Math.round(e).toString(16)),Be(Math.round(t).toString(16)),Be(Math.round(i).toString(16)),Be(oc(n))];return s&&r[0].startsWith(r[0].charAt(1))&&r[1].startsWith(r[1].charAt(1))&&r[2].startsWith(r[2].charAt(1))&&r[3].startsWith(r[3].charAt(1))?r[0].charAt(0)+r[1].charAt(0)+r[2].charAt(0)+r[3].charAt(0):r.join("")}function rc(e,t,i,n){const s=e/100,r=t/100,o=i/100,a=n/100,l=255*(1-s)*(1-a),u=255*(1-r)*(1-a),c=255*(1-o)*(1-a);return{r:l,g:u,b:c}}function vs(e,t,i){let n=1-e/255,s=1-t/255,r=1-i/255,o=Math.min(n,s,r);return o===1?(n=0,s=0,r=0):(n=(n-o)/(1-o)*100,s=(s-o)/(1-o)*100,r=(r-o)/(1-o)*100),o*=100,{c:Math.round(n),m:Math.round(s),y:Math.round(r),k:Math.round(o)}}function oc(e){return Math.round(parseFloat(e)*255).toString(16)}function ws(e){return J(e)/255}function J(e){return parseInt(e,16)}function ac(e){return{r:e>>16,g:(e&65280)>>8,b:e&255}}const Qi={aliceblue:"#f0f8ff",antiquewhite:"#faebd7",aqua:"#00ffff",aquamarine:"#7fffd4",azure:"#f0ffff",beige:"#f5f5dc",bisque:"#ffe4c4",black:"#000000",blanchedalmond:"#ffebcd",blue:"#0000ff",blueviolet:"#8a2be2",brown:"#a52a2a",burlywood:"#deb887",cadetblue:"#5f9ea0",chartreuse:"#7fff00",chocolate:"#d2691e",coral:"#ff7f50",cornflowerblue:"#6495ed",cornsilk:"#fff8dc",crimson:"#dc143c",cyan:"#00ffff",darkblue:"#00008b",darkcyan:"#008b8b",darkgoldenrod:"#b8860b",darkgray:"#a9a9a9",darkgreen:"#006400",darkgrey:"#a9a9a9",darkkhaki:"#bdb76b",darkmagenta:"#8b008b",darkolivegreen:"#556b2f",darkorange:"#ff8c00",darkorchid:"#9932cc",darkred:"#8b0000",darksalmon:"#e9967a",darkseagreen:"#8fbc8f",darkslateblue:"#483d8b",darkslategray:"#2f4f4f",darkslategrey:"#2f4f4f",darkturquoise:"#00ced1",darkviolet:"#9400d3",deeppink:"#ff1493",deepskyblue:"#00bfff",dimgray:"#696969",dimgrey:"#696969",dodgerblue:"#1e90ff",firebrick:"#b22222",floralwhite:"#fffaf0",forestgreen:"#228b22",fuchsia:"#ff00ff",gainsboro:"#dcdcdc",ghostwhite:"#f8f8ff",goldenrod:"#daa520",gold:"#ffd700",gray:"#808080",green:"#008000",greenyellow:"#adff2f",grey:"#808080",honeydew:"#f0fff0",hotpink:"#ff69b4",indianred:"#cd5c5c",indigo:"#4b0082",ivory:"#fffff0",khaki:"#f0e68c",lavenderblush:"#fff0f5",lavender:"#e6e6fa",lawngreen:"#7cfc00",lemonchiffon:"#fffacd",lightblue:"#add8e6",lightcoral:"#f08080",lightcyan:"#e0ffff",lightgoldenrodyellow:"#fafad2",lightgray:"#d3d3d3",lightgreen:"#90ee90",lightgrey:"#d3d3d3",lightpink:"#ffb6c1",lightsalmon:"#ffa07a",lightseagreen:"#20b2aa",lightskyblue:"#87cefa",lightslategray:"#778899",lightslategrey:"#778899",lightsteelblue:"#b0c4de",lightyellow:"#ffffe0",lime:"#00ff00",limegreen:"#32cd32",linen:"#faf0e6",magenta:"#ff00ff",maroon:"#800000",mediumaquamarine:"#66cdaa",mediumblue:"#0000cd",mediumorchid:"#ba55d3",mediumpurple:"#9370db",mediumseagreen:"#3cb371",mediumslateblue:"#7b68ee",mediumspringgreen:"#00fa9a",mediumturquoise:"#48d1cc",mediumvioletred:"#c71585",midnightblue:"#191970",mintcream:"#f5fffa",mistyrose:"#ffe4e1",moccasin:"#ffe4b5",navajowhite:"#ffdead",navy:"#000080",oldlace:"#fdf5e6",olive:"#808000",olivedrab:"#6b8e23",orange:"#ffa500",orangered:"#ff4500",orchid:"#da70d6",palegoldenrod:"#eee8aa",palegreen:"#98fb98",paleturquoise:"#afeeee",palevioletred:"#db7093",papayawhip:"#ffefd5",peachpuff:"#ffdab9",peru:"#cd853f",pink:"#ffc0cb",plum:"#dda0dd",powderblue:"#b0e0e6",purple:"#800080",rebeccapurple:"#663399",red:"#ff0000",rosybrown:"#bc8f8f",royalblue:"#4169e1",saddlebrown:"#8b4513",salmon:"#fa8072",sandybrown:"#f4a460",seagreen:"#2e8b57",seashell:"#fff5ee",sienna:"#a0522d",silver:"#c0c0c0",skyblue:"#87ceeb",slateblue:"#6a5acd",slategray:"#708090",slategrey:"#708090",snow:"#fffafa",springgreen:"#00ff7f",steelblue:"#4682b4",tan:"#d2b48c",teal:"#008080",thistle:"#d8bfd8",tomato:"#ff6347",turquoise:"#40e0d0",violet:"#ee82ee",wheat:"#f5deb3",white:"#ffffff",whitesmoke:"#f5f5f5",yellow:"#ffff00",yellowgreen:"#9acd32"};function lc(e){let t={r:0,g:0,b:0},i=1,n=null,s=null,r=null,o=!1,a=!1;return typeof e=="string"&&(e=dc(e)),typeof e=="object"&&(X(e.r)&&X(e.g)&&X(e.b)?(t=tc(e.r,e.g,e.b),o=!0,a=String(e.r).substr(-1)==="%"?"prgb":"rgb"):X(e.h)&&X(e.s)&&X(e.v)?(n=zt(e.s),s=zt(e.v),t=nc(e.h,n,s),o=!0,a="hsv"):X(e.h)&&X(e.s)&&X(e.l)?(n=zt(e.s),r=zt(e.l),t=ic(e.h,n,r),o=!0,a="hsl"):X(e.c)&&X(e.m)&&X(e.y)&&X(e.k)&&(t=rc(e.c,e.m,e.y,e.k),o=!0,a="cmyk"),Object.prototype.hasOwnProperty.call(e,"a")&&(i=e.a)),i=Ir(i),{ok:o,format:e.format||a,r:Math.min(255,Math.max(t.r,0)),g:Math.min(255,Math.max(t.g,0)),b:Math.min(255,Math.max(t.b,0)),a:i}}const uc="[-\\+]?\\d+%?",cc="[-\\+]?\\d*\\.\\d+%?",ke="(?:"+cc+")|(?:"+uc+")",Oi="[\\s|\\(]+("+ke+")[,|\\s]+("+ke+")[,|\\s]+("+ke+")\\s*\\)?",$t="[\\s|\\(]+("+ke+")[,|\\s]+("+ke+")[,|\\s]+("+ke+")[,|\\s]+("+ke+")\\s*\\)?",ee={CSS_UNIT:new RegExp(ke),rgb:new RegExp("rgb"+Oi),rgba:new RegExp("rgba"+$t),hsl:new RegExp("hsl"+Oi),hsla:new RegExp("hsla"+$t),hsv:new RegExp("hsv"+Oi),hsva:new RegExp("hsva"+$t),cmyk:new RegExp("cmyk"+$t),hex3:/^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/,hex6:/^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/,hex4:/^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/,hex8:/^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/};function dc(e){if(e=e.trim().toLowerCase(),e.length===0)return!1;let t=!1;if(Qi[e])e=Qi[e],t=!0;else if(e==="transparent")return{r:0,g:0,b:0,a:0,format:"name"};let i=ee.rgb.exec(e);return i?{r:i[1],g:i[2],b:i[3]}:(i=ee.rgba.exec(e),i?{r:i[1],g:i[2],b:i[3],a:i[4]}:(i=ee.hsl.exec(e),i?{h:i[1],s:i[2],l:i[3]}:(i=ee.hsla.exec(e),i?{h:i[1],s:i[2],l:i[3],a:i[4]}:(i=ee.hsv.exec(e),i?{h:i[1],s:i[2],v:i[3]}:(i=ee.hsva.exec(e),i?{h:i[1],s:i[2],v:i[3],a:i[4]}:(i=ee.cmyk.exec(e),i?{c:i[1],m:i[2],y:i[3],k:i[4]}:(i=ee.hex8.exec(e),i?{r:J(i[1]),g:J(i[2]),b:J(i[3]),a:ws(i[4]),format:t?"name":"hex8"}:(i=ee.hex6.exec(e),i?{r:J(i[1]),g:J(i[2]),b:J(i[3]),format:t?"name":"hex"}:(i=ee.hex4.exec(e),i?{r:J(i[1]+i[1]),g:J(i[2]+i[2]),b:J(i[3]+i[3]),a:ws(i[4]+i[4]),format:t?"name":"hex8"}:(i=ee.hex3.exec(e),i?{r:J(i[1]+i[1]),g:J(i[2]+i[2]),b:J(i[3]+i[3]),format:t?"name":"hex"}:!1))))))))))}function X(e){return typeof e=="number"?!Number.isNaN(e):ee.CSS_UNIT.test(e)}class P{constructor(t="",i={}){if(t instanceof P)return t;typeof t=="number"&&(t=ac(t)),this.originalInput=t;const n=lc(t);this.originalInput=t,this.r=n.r,this.g=n.g,this.b=n.b,this.a=n.a,this.roundA=Math.round(100*this.a)/100,this.format=i.format??n.format,this.gradientType=i.gradientType,this.r<1&&(this.r=Math.round(this.r)),this.g<1&&(this.g=Math.round(this.g)),this.b<1&&(this.b=Math.round(this.b)),this.isValid=n.ok}isDark(){return this.getBrightness()<128}isLight(){return!this.isDark()}getBrightness(){const t=this.toRgb();return(t.r*299+t.g*587+t.b*114)/1e3}getLuminance(){const t=this.toRgb();let i,n,s;const r=t.r/255,o=t.g/255,a=t.b/255;return r<=.03928?i=r/12.92:i=Math.pow((r+.055)/1.055,2.4),o<=.03928?n=o/12.92:n=Math.pow((o+.055)/1.055,2.4),a<=.03928?s=a/12.92:s=Math.pow((a+.055)/1.055,2.4),.2126*i+.7152*n+.0722*s}getAlpha(){return this.a}setAlpha(t){return this.a=Ir(t),this.roundA=Math.round(100*this.a)/100,this}isMonochrome(){const{s:t}=this.toHsl();return t===0}toHsv(){const t=bs(this.r,this.g,this.b);return{h:t.h*360,s:t.s,v:t.v,a:this.a}}toHsvString(){const t=bs(this.r,this.g,this.b),i=Math.round(t.h*360),n=Math.round(t.s*100),s=Math.round(t.v*100);return this.a===1?`hsv(${i}, ${n}%, ${s}%)`:`hsva(${i}, ${n}%, ${s}%, ${this.roundA})`}toHsl(){const t=gs(this.r,this.g,this.b);return{h:t.h*360,s:t.s,l:t.l,a:this.a}}toHslString(){const t=gs(this.r,this.g,this.b),i=Math.round(t.h*360),n=Math.round(t.s*100),s=Math.round(t.l*100);return this.a===1?`hsl(${i}, ${n}%, ${s}%)`:`hsla(${i}, ${n}%, ${s}%, ${this.roundA})`}toHex(t=!1){return ys(this.r,this.g,this.b,t)}toHexString(t=!1){return"#"+this.toHex(t)}toHex8(t=!1){return sc(this.r,this.g,this.b,this.a,t)}toHex8String(t=!1){return"#"+this.toHex8(t)}toHexShortString(t=!1){return this.a===1?this.toHexString(t):this.toHex8String(t)}toRgb(){return{r:Math.round(this.r),g:Math.round(this.g),b:Math.round(this.b),a:this.a}}toRgbString(){const t=Math.round(this.r),i=Math.round(this.g),n=Math.round(this.b);return this.a===1?`rgb(${t}, ${i}, ${n})`:`rgba(${t}, ${i}, ${n}, ${this.roundA})`}toPercentageRgb(){const t=i=>`${Math.round(D(i,255)*100)}%`;return{r:t(this.r),g:t(this.g),b:t(this.b),a:this.a}}toPercentageRgbString(){const t=i=>Math.round(D(i,255)*100);return this.a===1?`rgb(${t(this.r)}%, ${t(this.g)}%, ${t(this.b)}%)`:`rgba(${t(this.r)}%, ${t(this.g)}%, ${t(this.b)}%, ${this.roundA})`}toCmyk(){return{...vs(this.r,this.g,this.b)}}toCmykString(){const{c:t,m:i,y:n,k:s}=vs(this.r,this.g,this.b);return`cmyk(${t}, ${i}, ${n}, ${s})`}toName(){if(this.a===0)return"transparent";if(this.a<1)return!1;const t="#"+ys(this.r,this.g,this.b,!1);for(const[i,n]of Object.entries(Qi))if(t===n)return i;return!1}toString(t){const i=!!t;t=t??this.format;let n=!1;const s=this.a<1&&this.a>=0;return!i&&s&&(t.startsWith("hex")||t==="name")?t==="name"&&this.a===0?this.toName():this.toRgbString():(t==="rgb"&&(n=this.toRgbString()),t==="prgb"&&(n=this.toPercentageRgbString()),(t==="hex"||t==="hex6")&&(n=this.toHexString()),t==="hex3"&&(n=this.toHexString(!0)),t==="hex4"&&(n=this.toHex8String(!0)),t==="hex8"&&(n=this.toHex8String()),t==="name"&&(n=this.toName()),t==="hsl"&&(n=this.toHslString()),t==="hsv"&&(n=this.toHsvString()),t==="cmyk"&&(n=this.toCmykString()),n||this.toHexString())}toNumber(){return(Math.round(this.r)<<16)+(Math.round(this.g)<<8)+Math.round(this.b)}clone(){return new P(this.toString())}lighten(t=10){const i=this.toHsl();return i.l+=t/100,i.l=It(i.l),new P(i)}brighten(t=10){const i=this.toRgb();return i.r=Math.max(0,Math.min(255,i.r-Math.round(255*-(t/100)))),i.g=Math.max(0,Math.min(255,i.g-Math.round(255*-(t/100)))),i.b=Math.max(0,Math.min(255,i.b-Math.round(255*-(t/100)))),new P(i)}darken(t=10){const i=this.toHsl();return i.l-=t/100,i.l=It(i.l),new P(i)}tint(t=10){return this.mix("white",t)}shade(t=10){return this.mix("black",t)}desaturate(t=10){const i=this.toHsl();return i.s-=t/100,i.s=It(i.s),new P(i)}saturate(t=10){const i=this.toHsl();return i.s+=t/100,i.s=It(i.s),new P(i)}greyscale(){return this.desaturate(100)}spin(t){const i=this.toHsl(),n=(i.h+t)%360;return i.h=n<0?360+n:n,new P(i)}mix(t,i=50){const n=this.toRgb(),s=new P(t).toRgb(),r=i/100,o={r:(s.r-n.r)*r+n.r,g:(s.g-n.g)*r+n.g,b:(s.b-n.b)*r+n.b,a:(s.a-n.a)*r+n.a};return new P(o)}analogous(t=6,i=30){const n=this.toHsl(),s=360/i,r=[this];for(n.h=(n.h-(s*t>>1)+720)%360;--t;)n.h=(n.h+s)%360,r.push(new P(n));return r}complement(){const t=this.toHsl();return t.h=(t.h+180)%360,new P(t)}monochromatic(t=6){const i=this.toHsv(),{h:n}=i,{s}=i;let{v:r}=i;const o=[],a=1/t;for(;t--;)o.push(new P({h:n,s,v:r})),r=(r+a)%1;return o}splitcomplement(){const t=this.toHsl(),{h:i}=t;return[this,new P({h:(i+72)%360,s:t.s,l:t.l}),new P({h:(i+216)%360,s:t.s,l:t.l})]}onBackground(t){const i=this.toRgb(),n=new P(t).toRgb(),s=i.a+n.a*(1-i.a);return new P({r:(i.r*i.a+n.r*n.a*(1-i.a))/s,g:(i.g*i.a+n.g*n.a*(1-i.a))/s,b:(i.b*i.a+n.b*n.a*(1-i.a))/s,a:s})}triad(){return this.polyad(3)}tetrad(){return this.polyad(4)}polyad(t){const i=this.toHsl(),{h:n}=i,s=[this],r=360/t;for(let o=1;o<t;o++)s.push(new P({h:(n+o*r)%360,s:i.s,l:i.l}));return s}equals(t){const i=new P(t);return this.format==="cmyk"||i.format==="cmyk"?this.toCmykString()===i.toCmykString():this.toRgbString()===i.toRgbString()}}var _s="EyeDropper"in window,T=class extends it{constructor(){super(),this.formControlController=new Ws(this),this.isSafeValue=!1,this.localize=new ri(this),this.hasFocus=!1,this.isDraggingGridHandle=!1,this.isEmpty=!1,this.inputValue="",this.hue=0,this.saturation=100,this.brightness=100,this.alpha=100,this.value="",this.defaultValue="",this.label="",this.format="hex",this.inline=!1,this.size="medium",this.noFormatToggle=!1,this.name="",this.disabled=!1,this.hoist=!1,this.opacity=!1,this.uppercase=!1,this.swatches="",this.form="",this.required=!1,this.handleFocusIn=()=>{this.hasFocus=!0,this.emit("sl-focus")},this.handleFocusOut=()=>{this.hasFocus=!1,this.emit("sl-blur")},this.addEventListener("focusin",this.handleFocusIn),this.addEventListener("focusout",this.handleFocusOut)}get validity(){return this.input.validity}get validationMessage(){return this.input.validationMessage}firstUpdated(){this.input.updateComplete.then(()=>{this.formControlController.updateValidity()})}handleCopy(){this.input.select(),document.execCommand("copy"),this.previewButton.focus(),this.previewButton.classList.add("color-picker__preview-color--copied"),this.previewButton.addEventListener("animationend",()=>{this.previewButton.classList.remove("color-picker__preview-color--copied")})}handleFormatToggle(){const e=["hex","rgb","hsl","hsv"],t=(e.indexOf(this.format)+1)%e.length;this.format=e[t],this.setColor(this.value),this.emit("sl-change"),this.emit("sl-input")}handleAlphaDrag(e){const t=this.shadowRoot.querySelector(".color-picker__slider.color-picker__alpha"),i=t.querySelector(".color-picker__slider-handle"),{width:n}=t.getBoundingClientRect();let s=this.value,r=this.value;i.focus(),e.preventDefault(),Ai(t,{onMove:o=>{this.alpha=Z(o/n*100,0,100),this.syncValues(),this.value!==r&&(r=this.value,this.emit("sl-input"))},onStop:()=>{this.value!==s&&(s=this.value,this.emit("sl-change"))},initialEvent:e})}handleHueDrag(e){const t=this.shadowRoot.querySelector(".color-picker__slider.color-picker__hue"),i=t.querySelector(".color-picker__slider-handle"),{width:n}=t.getBoundingClientRect();let s=this.value,r=this.value;i.focus(),e.preventDefault(),Ai(t,{onMove:o=>{this.hue=Z(o/n*360,0,360),this.syncValues(),this.value!==r&&(r=this.value,this.emit("sl-input"))},onStop:()=>{this.value!==s&&(s=this.value,this.emit("sl-change"))},initialEvent:e})}handleGridDrag(e){const t=this.shadowRoot.querySelector(".color-picker__grid"),i=t.querySelector(".color-picker__grid-handle"),{width:n,height:s}=t.getBoundingClientRect();let r=this.value,o=this.value;i.focus(),e.preventDefault(),this.isDraggingGridHandle=!0,Ai(t,{onMove:(a,l)=>{this.saturation=Z(a/n*100,0,100),this.brightness=Z(100-l/s*100,0,100),this.syncValues(),this.value!==o&&(o=this.value,this.emit("sl-input"))},onStop:()=>{this.isDraggingGridHandle=!1,this.value!==r&&(r=this.value,this.emit("sl-change"))},initialEvent:e})}handleAlphaKeyDown(e){const t=e.shiftKey?10:1,i=this.value;e.key==="ArrowLeft"&&(e.preventDefault(),this.alpha=Z(this.alpha-t,0,100),this.syncValues()),e.key==="ArrowRight"&&(e.preventDefault(),this.alpha=Z(this.alpha+t,0,100),this.syncValues()),e.key==="Home"&&(e.preventDefault(),this.alpha=0,this.syncValues()),e.key==="End"&&(e.preventDefault(),this.alpha=100,this.syncValues()),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleHueKeyDown(e){const t=e.shiftKey?10:1,i=this.value;e.key==="ArrowLeft"&&(e.preventDefault(),this.hue=Z(this.hue-t,0,360),this.syncValues()),e.key==="ArrowRight"&&(e.preventDefault(),this.hue=Z(this.hue+t,0,360),this.syncValues()),e.key==="Home"&&(e.preventDefault(),this.hue=0,this.syncValues()),e.key==="End"&&(e.preventDefault(),this.hue=360,this.syncValues()),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleGridKeyDown(e){const t=e.shiftKey?10:1,i=this.value;e.key==="ArrowLeft"&&(e.preventDefault(),this.saturation=Z(this.saturation-t,0,100),this.syncValues()),e.key==="ArrowRight"&&(e.preventDefault(),this.saturation=Z(this.saturation+t,0,100),this.syncValues()),e.key==="ArrowUp"&&(e.preventDefault(),this.brightness=Z(this.brightness+t,0,100),this.syncValues()),e.key==="ArrowDown"&&(e.preventDefault(),this.brightness=Z(this.brightness-t,0,100),this.syncValues()),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleInputChange(e){const t=e.target,i=this.value;e.stopPropagation(),this.input.value?(this.setColor(t.value),t.value=this.value):this.value="",this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleInputInput(e){this.formControlController.updateValidity(),e.stopPropagation()}handleInputKeyDown(e){if(e.key==="Enter"){const t=this.value;this.input.value?(this.setColor(this.input.value),this.input.value=this.value,this.value!==t&&(this.emit("sl-change"),this.emit("sl-input")),setTimeout(()=>this.input.select())):this.hue=0}}handleInputInvalid(e){this.formControlController.setValidity(!1),this.formControlController.emitInvalidEvent(e)}handleTouchMove(e){e.preventDefault()}parseColor(e){const t=new P(e);if(!t.isValid)return null;const i=t.toHsl(),n={h:i.h,s:i.s*100,l:i.l*100,a:i.a},s=t.toRgb(),r=t.toHexString(),o=t.toHex8String(),a=t.toHsv(),l={h:a.h,s:a.s*100,v:a.v*100,a:a.a};return{hsl:{h:n.h,s:n.s,l:n.l,string:this.setLetterCase(`hsl(${Math.round(n.h)}, ${Math.round(n.s)}%, ${Math.round(n.l)}%)`)},hsla:{h:n.h,s:n.s,l:n.l,a:n.a,string:this.setLetterCase(`hsla(${Math.round(n.h)}, ${Math.round(n.s)}%, ${Math.round(n.l)}%, ${n.a.toFixed(2).toString()})`)},hsv:{h:l.h,s:l.s,v:l.v,string:this.setLetterCase(`hsv(${Math.round(l.h)}, ${Math.round(l.s)}%, ${Math.round(l.v)}%)`)},hsva:{h:l.h,s:l.s,v:l.v,a:l.a,string:this.setLetterCase(`hsva(${Math.round(l.h)}, ${Math.round(l.s)}%, ${Math.round(l.v)}%, ${l.a.toFixed(2).toString()})`)},rgb:{r:s.r,g:s.g,b:s.b,string:this.setLetterCase(`rgb(${Math.round(s.r)}, ${Math.round(s.g)}, ${Math.round(s.b)})`)},rgba:{r:s.r,g:s.g,b:s.b,a:s.a,string:this.setLetterCase(`rgba(${Math.round(s.r)}, ${Math.round(s.g)}, ${Math.round(s.b)}, ${s.a.toFixed(2).toString()})`)},hex:this.setLetterCase(r),hexa:this.setLetterCase(o)}}setColor(e){const t=this.parseColor(e);return t===null?!1:(this.hue=t.hsva.h,this.saturation=t.hsva.s,this.brightness=t.hsva.v,this.alpha=this.opacity?t.hsva.a*100:100,this.syncValues(),!0)}setLetterCase(e){return typeof e!="string"?"":this.uppercase?e.toUpperCase():e.toLowerCase()}async syncValues(){const e=this.parseColor(`hsva(${this.hue}, ${this.saturation}%, ${this.brightness}%, ${this.alpha/100})`);e!==null&&(this.format==="hsl"?this.inputValue=this.opacity?e.hsla.string:e.hsl.string:this.format==="rgb"?this.inputValue=this.opacity?e.rgba.string:e.rgb.string:this.format==="hsv"?this.inputValue=this.opacity?e.hsva.string:e.hsv.string:this.inputValue=this.opacity?e.hexa:e.hex,this.isSafeValue=!0,this.value=this.inputValue,await this.updateComplete,this.isSafeValue=!1)}handleAfterHide(){this.previewButton.classList.remove("color-picker__preview-color--copied")}handleEyeDropper(){if(!_s)return;new EyeDropper().open().then(t=>{const i=this.value;this.setColor(t.sRGBHex),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}).catch(()=>{})}selectSwatch(e){const t=this.value;this.disabled||(this.setColor(e),this.value!==t&&(this.emit("sl-change"),this.emit("sl-input")))}getHexString(e,t,i,n=100){const s=new P(`hsva(${e}, ${t}%, ${i}%, ${n/100})`);return s.isValid?s.toHex8String():""}stopNestedEventPropagation(e){e.stopImmediatePropagation()}handleFormatChange(){this.syncValues()}handleOpacityChange(){this.alpha=100}handleValueChange(e,t){if(this.isEmpty=!t,t||(this.hue=0,this.saturation=0,this.brightness=100,this.alpha=100),!this.isSafeValue){const i=this.parseColor(t);i!==null?(this.inputValue=this.value,this.hue=i.hsva.h,this.saturation=i.hsva.s,this.brightness=i.hsva.v,this.alpha=i.hsva.a*100,this.syncValues()):this.inputValue=e??""}}focus(e){this.inline?this.base.focus(e):this.trigger.focus(e)}blur(){var e;const t=this.inline?this.base:this.trigger;this.hasFocus&&(t.focus({preventScroll:!0}),t.blur()),(e=this.dropdown)!=null&&e.open&&this.dropdown.hide()}getFormattedValue(e="hex"){const t=this.parseColor(`hsva(${this.hue}, ${this.saturation}%, ${this.brightness}%, ${this.alpha/100})`);if(t===null)return"";switch(e){case"hex":return t.hex;case"hexa":return t.hexa;case"rgb":return t.rgb.string;case"rgba":return t.rgba.string;case"hsl":return t.hsl.string;case"hsla":return t.hsla.string;case"hsv":return t.hsv.string;case"hsva":return t.hsva.string;default:return""}}checkValidity(){return this.input.checkValidity()}getForm(){return this.formControlController.getForm()}reportValidity(){return!this.inline&&!this.validity.valid?(this.dropdown.show(),this.addEventListener("sl-after-show",()=>this.input.reportValidity(),{once:!0}),this.disabled||this.formControlController.emitInvalidEvent(),!1):this.input.reportValidity()}setCustomValidity(e){this.input.setCustomValidity(e),this.formControlController.updateValidity()}render(){const e=this.saturation,t=100-this.brightness,i=Array.isArray(this.swatches)?this.swatches:this.swatches.split(";").filter(s=>s.trim()!==""),n=B`
      <div
        part="base"
        class=${Ce({"color-picker":!0,"color-picker--inline":this.inline,"color-picker--disabled":this.disabled,"color-picker--focused":this.hasFocus})}
        aria-disabled=${this.disabled?"true":"false"}
        aria-labelledby="label"
        tabindex=${this.inline?"0":"-1"}
      >
        ${this.inline?B`
              <sl-visually-hidden id="label">
                <slot name="label">${this.label}</slot>
              </sl-visually-hidden>
            `:null}

        <div
          part="grid"
          class="color-picker__grid"
          style=${Ee({backgroundColor:this.getHexString(this.hue,100,100)})}
          @pointerdown=${this.handleGridDrag}
          @touchmove=${this.handleTouchMove}
        >
          <span
            part="grid-handle"
            class=${Ce({"color-picker__grid-handle":!0,"color-picker__grid-handle--dragging":this.isDraggingGridHandle})}
            style=${Ee({top:`${t}%`,left:`${e}%`,backgroundColor:this.getHexString(this.hue,this.saturation,this.brightness,this.alpha)})}
            role="application"
            aria-label="HSV"
            tabindex=${$(this.disabled?void 0:"0")}
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
                style=${Ee({left:`${this.hue===0?0:100/(360/this.hue)}%`})}
                role="slider"
                aria-label="hue"
                aria-orientation="horizontal"
                aria-valuemin="0"
                aria-valuemax="360"
                aria-valuenow=${`${Math.round(this.hue)}`}
                tabindex=${$(this.disabled?void 0:"0")}
                @keydown=${this.handleHueKeyDown}
              ></span>
            </div>

            ${this.opacity?B`
                  <div
                    part="slider opacity-slider"
                    class="color-picker__alpha color-picker__slider color-picker__transparent-bg"
                    @pointerdown="${this.handleAlphaDrag}"
                    @touchmove=${this.handleTouchMove}
                  >
                    <div
                      class="color-picker__alpha-gradient"
                      style=${Ee({backgroundImage:`linear-gradient(
                          to right,
                          ${this.getHexString(this.hue,this.saturation,this.brightness,0)} 0%,
                          ${this.getHexString(this.hue,this.saturation,this.brightness,100)} 100%
                        )`})}
                    ></div>
                    <span
                      part="slider-handle opacity-slider-handle"
                      class="color-picker__slider-handle"
                      style=${Ee({left:`${this.alpha}%`})}
                      role="slider"
                      aria-label="alpha"
                      aria-orientation="horizontal"
                      aria-valuemin="0"
                      aria-valuemax="100"
                      aria-valuenow=${Math.round(this.alpha)}
                      tabindex=${$(this.disabled?void 0:"0")}
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
            style=${Ee({"--preview-color":this.getHexString(this.hue,this.saturation,this.brightness,this.alpha)})}
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
            ${this.noFormatToggle?"":B`
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
            ${_s?B`
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

        ${i.length>0?B`
              <div part="swatches" class="color-picker__swatches">
                ${i.map(s=>{const r=this.parseColor(s);return r?B`
                    <div
                      part="swatch"
                      class="color-picker__swatch color-picker__transparent-bg"
                      tabindex=${$(this.disabled?void 0:"0")}
                      role="button"
                      aria-label=${s}
                      @click=${()=>this.selectSwatch(s)}
                      @keydown=${o=>!this.disabled&&o.key==="Enter"&&this.setColor(r.hexa)}
                    >
                      <div
                        class="color-picker__swatch-color"
                        style=${Ee({backgroundColor:r.hexa})}
                      ></div>
                    </div>
                  `:(console.error(`Unable to parse swatch color: "${s}"`,this),"")})}
              </div>
            `:""}
      </div>
    `;return this.inline?n:B`
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
          class=${Ce({"color-dropdown__trigger":!0,"color-dropdown__trigger--disabled":this.disabled,"color-dropdown__trigger--small":this.size==="small","color-dropdown__trigger--medium":this.size==="medium","color-dropdown__trigger--large":this.size==="large","color-dropdown__trigger--empty":this.isEmpty,"color-dropdown__trigger--focused":this.hasFocus,"color-picker__transparent-bg":!0})}
          style=${Ee({color:this.getHexString(this.hue,this.saturation,this.brightness,this.alpha)})}
          type="button"
        >
          <sl-visually-hidden>
            <slot name="label">${this.label}</slot>
          </sl-visually-hidden>
        </button>
        ${n}
      </sl-dropdown>
    `}};T.styles=[tt,Ju];T.dependencies={"sl-button-group":Ft,"sl-button":oa,"sl-dropdown":H,"sl-icon":Vs,"sl-input":A,"sl-visually-hidden":wr};b([ie('[part~="base"]')],T.prototype,"base",2);b([ie('[part~="input"]')],T.prototype,"input",2);b([ie(".color-dropdown")],T.prototype,"dropdown",2);b([ie('[part~="preview"]')],T.prototype,"previewButton",2);b([ie('[part~="trigger"]')],T.prototype,"trigger",2);b([me()],T.prototype,"hasFocus",2);b([me()],T.prototype,"isDraggingGridHandle",2);b([me()],T.prototype,"isEmpty",2);b([me()],T.prototype,"inputValue",2);b([me()],T.prototype,"hue",2);b([me()],T.prototype,"saturation",2);b([me()],T.prototype,"brightness",2);b([me()],T.prototype,"alpha",2);b([_()],T.prototype,"value",2);b([_r()],T.prototype,"defaultValue",2);b([_()],T.prototype,"label",2);b([_()],T.prototype,"format",2);b([_({type:Boolean,reflect:!0})],T.prototype,"inline",2);b([_({reflect:!0})],T.prototype,"size",2);b([_({attribute:"no-format-toggle",type:Boolean})],T.prototype,"noFormatToggle",2);b([_()],T.prototype,"name",2);b([_({type:Boolean,reflect:!0})],T.prototype,"disabled",2);b([_({type:Boolean})],T.prototype,"hoist",2);b([_({type:Boolean})],T.prototype,"opacity",2);b([_({type:Boolean})],T.prototype,"uppercase",2);b([_()],T.prototype,"swatches",2);b([_({reflect:!0})],T.prototype,"form",2);b([_({type:Boolean,reflect:!0})],T.prototype,"required",2);b([Nl({passive:!1})],T.prototype,"handleTouchMove",1);b([Xe("format",{waitUntilFirstUpdate:!0})],T.prototype,"handleFormatChange",1);b([Xe("opacity",{waitUntilFirstUpdate:!0})],T.prototype,"handleOpacityChange",1);b([Xe("value")],T.prototype,"handleValueChange",1);T.define("sl-color-picker");var Xi=!1,Ji=!1,Ue=[],Gi=-1;function hc(e){pc(e)}function pc(e){Ue.includes(e)||Ue.push(e),mc()}function fc(e){let t=Ue.indexOf(e);t!==-1&&t>Gi&&Ue.splice(t,1)}function mc(){!Ji&&!Xi&&(Xi=!0,queueMicrotask(gc))}function gc(){Xi=!1,Ji=!0;for(let e=0;e<Ue.length;e++)Ue[e](),Gi=e;Ue.length=0,Gi=-1,Ji=!1}var lt,Je,ut,zr,Yi=!0;function bc(e){Yi=!1,e(),Yi=!0}function yc(e){lt=e.reactive,ut=e.release,Je=t=>e.effect(t,{scheduler:i=>{Yi?hc(i):i()}}),zr=e.raw}function xs(e){Je=e}function vc(e){let t=()=>{};return[n=>{let s=Je(n);return e._x_effects||(e._x_effects=new Set,e._x_runEffects=()=>{e._x_effects.forEach(r=>r())}),e._x_effects.add(s),t=()=>{s!==void 0&&(e._x_effects.delete(s),ut(s))},s},()=>{t()}]}function $r(e,t){let i=!0,n,s=Je(()=>{let r=e();JSON.stringify(r),i?n=r:queueMicrotask(()=>{t(r,n),n=r}),i=!1});return()=>ut(s)}var Br=[],Dr=[],Nr=[];function wc(e){Nr.push(e)}function _n(e,t){typeof t=="function"?(e._x_cleanups||(e._x_cleanups=[]),e._x_cleanups.push(t)):(t=e,Dr.push(t))}function Ur(e){Br.push(e)}function Hr(e,t,i){e._x_attributeCleanups||(e._x_attributeCleanups={}),e._x_attributeCleanups[t]||(e._x_attributeCleanups[t]=[]),e._x_attributeCleanups[t].push(i)}function qr(e,t){e._x_attributeCleanups&&Object.entries(e._x_attributeCleanups).forEach(([i,n])=>{(t===void 0||t.includes(i))&&(n.forEach(s=>s()),delete e._x_attributeCleanups[i])})}function _c(e){var t,i;for((t=e._x_effects)==null||t.forEach(fc);(i=e._x_cleanups)!=null&&i.length;)e._x_cleanups.pop()()}var xn=new MutationObserver(Cn),En=!1;function Sn(){xn.observe(document,{subtree:!0,childList:!0,attributes:!0,attributeOldValue:!0}),En=!0}function jr(){xc(),xn.disconnect(),En=!1}var mt=[];function xc(){let e=xn.takeRecords();mt.push(()=>e.length>0&&Cn(e));let t=mt.length;queueMicrotask(()=>{if(mt.length===t)for(;mt.length>0;)mt.shift()()})}function M(e){if(!En)return e();jr();let t=e();return Sn(),t}var kn=!1,ti=[];function Ec(){kn=!0}function Sc(){kn=!1,Cn(ti),ti=[]}function Cn(e){if(kn){ti=ti.concat(e);return}let t=[],i=new Set,n=new Map,s=new Map;for(let r=0;r<e.length;r++)if(!e[r].target._x_ignoreMutationObserver&&(e[r].type==="childList"&&(e[r].removedNodes.forEach(o=>{o.nodeType===1&&o._x_marker&&i.add(o)}),e[r].addedNodes.forEach(o=>{if(o.nodeType===1){if(i.has(o)){i.delete(o);return}o._x_marker||t.push(o)}})),e[r].type==="attributes")){let o=e[r].target,a=e[r].attributeName,l=e[r].oldValue,u=()=>{n.has(o)||n.set(o,[]),n.get(o).push({name:a,value:o.getAttribute(a)})},c=()=>{s.has(o)||s.set(o,[]),s.get(o).push(a)};o.hasAttribute(a)&&l===null?u():o.hasAttribute(a)?(c(),u()):c()}s.forEach((r,o)=>{qr(o,r)}),n.forEach((r,o)=>{Br.forEach(a=>a(o,r))});for(let r of i)t.some(o=>o.contains(r))||Dr.forEach(o=>o(r));for(let r of t)r.isConnected&&Nr.forEach(o=>o(r));t=null,i=null,n=null,s=null}function Vr(e){return Lt(Ze(e))}function Ot(e,t,i){return e._x_dataStack=[t,...Ze(i||e)],()=>{e._x_dataStack=e._x_dataStack.filter(n=>n!==t)}}function Ze(e){return e._x_dataStack?e._x_dataStack:typeof ShadowRoot=="function"&&e instanceof ShadowRoot?Ze(e.host):e.parentNode?Ze(e.parentNode):[]}function Lt(e){return new Proxy({objects:e},kc)}var kc={ownKeys({objects:e}){return Array.from(new Set(e.flatMap(t=>Object.keys(t))))},has({objects:e},t){return t==Symbol.unscopables?!1:e.some(i=>Object.prototype.hasOwnProperty.call(i,t)||Reflect.has(i,t))},get({objects:e},t,i){return t=="toJSON"?Cc:Reflect.get(e.find(n=>Reflect.has(n,t))||{},t,i)},set({objects:e},t,i,n){const s=e.find(o=>Object.prototype.hasOwnProperty.call(o,t))||e[e.length-1],r=Object.getOwnPropertyDescriptor(s,t);return r!=null&&r.set&&(r!=null&&r.get)?r.set.call(n,i)||!0:Reflect.set(s,t,i)}};function Cc(){return Reflect.ownKeys(this).reduce((t,i)=>(t[i]=Reflect.get(this,i),t),{})}function Wr(e){let t=n=>typeof n=="object"&&!Array.isArray(n)&&n!==null,i=(n,s="")=>{Object.entries(Object.getOwnPropertyDescriptors(n)).forEach(([r,{value:o,enumerable:a}])=>{if(a===!1||o===void 0||typeof o=="object"&&o!==null&&o.__v_skip)return;let l=s===""?r:`${s}.${r}`;typeof o=="object"&&o!==null&&o._x_interceptor?n[r]=o.initialize(e,l,r):t(o)&&o!==n&&!(o instanceof Element)&&i(o,l)})};return i(e)}function Kr(e,t=()=>{}){let i={initialValue:void 0,_x_interceptor:!0,initialize(n,s,r){return e(this.initialValue,()=>Ac(n,s),o=>Zi(n,s,o),s,r)}};return t(i),n=>{if(typeof n=="object"&&n!==null&&n._x_interceptor){let s=i.initialize.bind(i);i.initialize=(r,o,a)=>{let l=n.initialize(r,o,a);return i.initialValue=l,s(r,o,a)}}else i.initialValue=n;return i}}function Ac(e,t){return t.split(".").reduce((i,n)=>i[n],e)}function Zi(e,t,i){if(typeof t=="string"&&(t=t.split(".")),t.length===1)e[t[0]]=i;else{if(t.length===0)throw error;return e[t[0]]||(e[t[0]]={}),Zi(e[t[0]],t.slice(1),i)}}var Qr={};function ue(e,t){Qr[e]=t}function en(e,t){let i=Tc(t);return Object.entries(Qr).forEach(([n,s])=>{Object.defineProperty(e,`$${n}`,{get(){return s(t,i)},enumerable:!1})}),e}function Tc(e){let[t,i]=eo(e),n={interceptor:Kr,...t};return _n(e,i),n}function Rc(e,t,i,...n){try{return i(...n)}catch(s){St(s,e,t)}}function St(e,t,i=void 0){e=Object.assign(e??{message:"No error message given."},{el:t,expression:i}),console.warn(`Alpine Expression Error: ${e.message}

${i?'Expression: "'+i+`"

`:""}`,t),setTimeout(()=>{throw e},0)}var Kt=!0;function Xr(e){let t=Kt;Kt=!1;let i=e();return Kt=t,i}function He(e,t,i={}){let n;return V(e,t)(s=>n=s,i),n}function V(...e){return Jr(...e)}var Jr=Gr;function Fc(e){Jr=e}function Gr(e,t){let i={};en(i,e);let n=[i,...Ze(e)],s=typeof t=="function"?Oc(n,t):Mc(n,t,e);return Rc.bind(null,e,t,s)}function Oc(e,t){return(i=()=>{},{scope:n={},params:s=[]}={})=>{let r=t.apply(Lt([n,...e]),s);ii(i,r)}}var Li={};function Lc(e,t){if(Li[e])return Li[e];let i=Object.getPrototypeOf(async function(){}).constructor,n=/^[\n\s]*if.*\(.*\)/.test(e.trim())||/^(let|const)\s/.test(e.trim())?`(async()=>{ ${e} })()`:e,r=(()=>{try{let o=new i(["__self","scope"],`with (scope) { __self.result = ${n} }; __self.finished = true; return __self.result;`);return Object.defineProperty(o,"name",{value:`[Alpine] ${e}`}),o}catch(o){return St(o,t,e),Promise.resolve()}})();return Li[e]=r,r}function Mc(e,t,i){let n=Lc(t,i);return(s=()=>{},{scope:r={},params:o=[]}={})=>{n.result=void 0,n.finished=!1;let a=Lt([r,...e]);if(typeof n=="function"){let l=n(n,a).catch(u=>St(u,i,t));n.finished?(ii(s,n.result,a,o,i),n.result=void 0):l.then(u=>{ii(s,u,a,o,i)}).catch(u=>St(u,i,t)).finally(()=>n.result=void 0)}}}function ii(e,t,i,n,s){if(Kt&&typeof t=="function"){let r=t.apply(i,n);r instanceof Promise?r.then(o=>ii(e,o,i,n)).catch(o=>St(o,s,t)):e(r)}else typeof t=="object"&&t instanceof Promise?t.then(r=>e(r)):e(t)}var An="x-";function ct(e=""){return An+e}function Pc(e){An=e}var ni={};function z(e,t){return ni[e]=t,{before(i){if(!ni[i]){console.warn(String.raw`Cannot find directive \`${i}\`. \`${e}\` will use the default order of execution`);return}const n=De.indexOf(i);De.splice(n>=0?n:De.indexOf("DEFAULT"),0,e)}}}function Ic(e){return Object.keys(ni).includes(e)}function Tn(e,t,i){if(t=Array.from(t),e._x_virtualDirectives){let r=Object.entries(e._x_virtualDirectives).map(([a,l])=>({name:a,value:l})),o=Yr(r);r=r.map(a=>o.find(l=>l.name===a.name)?{name:`x-bind:${a.name}`,value:`"${a.value}"`}:a),t=t.concat(r)}let n={};return t.map(no((r,o)=>n[r]=o)).filter(ro).map(Bc(n,i)).sort(Dc).map(r=>$c(e,r))}function Yr(e){return Array.from(e).map(no()).filter(t=>!ro(t))}var tn=!1,vt=new Map,Zr=Symbol();function zc(e){tn=!0;let t=Symbol();Zr=t,vt.set(t,[]);let i=()=>{for(;vt.get(t).length;)vt.get(t).shift()();vt.delete(t)},n=()=>{tn=!1,i()};e(i),n()}function eo(e){let t=[],i=a=>t.push(a),[n,s]=vc(e);return t.push(s),[{Alpine:Mt,effect:n,cleanup:i,evaluateLater:V.bind(V,e),evaluate:He.bind(He,e)},()=>t.forEach(a=>a())]}function $c(e,t){let i=()=>{},n=ni[t.type]||i,[s,r]=eo(e);Hr(e,t.original,r);let o=()=>{e._x_ignore||e._x_ignoreSelf||(n.inline&&n.inline(e,t,s),n=n.bind(n,e,t,s),tn?vt.get(Zr).push(n):n())};return o.runCleanups=r,o}var to=(e,t)=>({name:i,value:n})=>(i.startsWith(e)&&(i=i.replace(e,t)),{name:i,value:n}),io=e=>e;function no(e=()=>{}){return({name:t,value:i})=>{let{name:n,value:s}=so.reduce((r,o)=>o(r),{name:t,value:i});return n!==t&&e(n,t),{name:n,value:s}}}var so=[];function Rn(e){so.push(e)}function ro({name:e}){return oo().test(e)}var oo=()=>new RegExp(`^${An}([^:^.]+)\\b`);function Bc(e,t){return({name:i,value:n})=>{let s=i.match(oo()),r=i.match(/:([a-zA-Z0-9\-_:]+)/),o=i.match(/\.[^.\]]+(?=[^\]]*$)/g)||[],a=t||e[i]||i;return{type:s?s[1]:null,value:r?r[1]:null,modifiers:o.map(l=>l.replace(".","")),expression:n,original:a}}}var nn="DEFAULT",De=["ignore","ref","data","id","anchor","bind","init","for","model","modelable","transition","show","if",nn,"teleport"];function Dc(e,t){let i=De.indexOf(e.type)===-1?nn:e.type,n=De.indexOf(t.type)===-1?nn:t.type;return De.indexOf(i)-De.indexOf(n)}function wt(e,t,i={}){e.dispatchEvent(new CustomEvent(t,{detail:i,bubbles:!0,composed:!0,cancelable:!0}))}function Ke(e,t){if(typeof ShadowRoot=="function"&&e instanceof ShadowRoot){Array.from(e.children).forEach(s=>Ke(s,t));return}let i=!1;if(t(e,()=>i=!0),i)return;let n=e.firstElementChild;for(;n;)Ke(n,t),n=n.nextElementSibling}function te(e,...t){console.warn(`Alpine Warning: ${e}`,...t)}var Es=!1;function Nc(){Es&&te("Alpine has already been initialized on this page. Calling Alpine.start() more than once can cause problems."),Es=!0,document.body||te("Unable to initialize. Trying to load Alpine before `<body>` is available. Did you forget to add `defer` in Alpine's `<script>` tag?"),wt(document,"alpine:init"),wt(document,"alpine:initializing"),Sn(),wc(t=>ve(t,Ke)),_n(t=>ht(t)),Ur((t,i)=>{Tn(t,i).forEach(n=>n())});let e=t=>!bi(t.parentElement,!0);Array.from(document.querySelectorAll(uo().join(","))).filter(e).forEach(t=>{ve(t)}),wt(document,"alpine:initialized"),setTimeout(()=>{jc()})}var Fn=[],ao=[];function lo(){return Fn.map(e=>e())}function uo(){return Fn.concat(ao).map(e=>e())}function co(e){Fn.push(e)}function ho(e){ao.push(e)}function bi(e,t=!1){return dt(e,i=>{if((t?uo():lo()).some(s=>i.matches(s)))return!0})}function dt(e,t){if(e){if(t(e))return e;if(e._x_teleportBack&&(e=e._x_teleportBack),!!e.parentElement)return dt(e.parentElement,t)}}function Uc(e){return lo().some(t=>e.matches(t))}var po=[];function Hc(e){po.push(e)}var qc=1;function ve(e,t=Ke,i=()=>{}){dt(e,n=>n._x_ignore)||zc(()=>{t(e,(n,s)=>{n._x_marker||(i(n,s),po.forEach(r=>r(n,s)),Tn(n,n.attributes).forEach(r=>r()),n._x_ignore||(n._x_marker=qc++),n._x_ignore&&s())})})}function ht(e,t=Ke){t(e,i=>{_c(i),qr(i),delete i._x_marker})}function jc(){[["ui","dialog",["[x-dialog], [x-popover]"]],["anchor","anchor",["[x-anchor]"]],["sort","sort",["[x-sort]"]]].forEach(([t,i,n])=>{Ic(i)||n.some(s=>{if(document.querySelector(s))return te(`found "${s}", but missing ${t} plugin`),!0})})}var sn=[],On=!1;function Ln(e=()=>{}){return queueMicrotask(()=>{On||setTimeout(()=>{rn()})}),new Promise(t=>{sn.push(()=>{e(),t()})})}function rn(){for(On=!1;sn.length;)sn.shift()()}function Vc(){On=!0}function Mn(e,t){return Array.isArray(t)?Ss(e,t.join(" ")):typeof t=="object"&&t!==null?Wc(e,t):typeof t=="function"?Mn(e,t()):Ss(e,t)}function Ss(e,t){let i=s=>s.split(" ").filter(r=>!e.classList.contains(r)).filter(Boolean),n=s=>(e.classList.add(...s),()=>{e.classList.remove(...s)});return t=t===!0?t="":t||"",n(i(t))}function Wc(e,t){let i=a=>a.split(" ").filter(Boolean),n=Object.entries(t).flatMap(([a,l])=>l?i(a):!1).filter(Boolean),s=Object.entries(t).flatMap(([a,l])=>l?!1:i(a)).filter(Boolean),r=[],o=[];return s.forEach(a=>{e.classList.contains(a)&&(e.classList.remove(a),o.push(a))}),n.forEach(a=>{e.classList.contains(a)||(e.classList.add(a),r.push(a))}),()=>{o.forEach(a=>e.classList.add(a)),r.forEach(a=>e.classList.remove(a))}}function yi(e,t){return typeof t=="object"&&t!==null?Kc(e,t):Qc(e,t)}function Kc(e,t){let i={};return Object.entries(t).forEach(([n,s])=>{i[n]=e.style[n],n.startsWith("--")||(n=Xc(n)),e.style.setProperty(n,s)}),setTimeout(()=>{e.style.length===0&&e.removeAttribute("style")}),()=>{yi(e,i)}}function Qc(e,t){let i=e.getAttribute("style",t);return e.setAttribute("style",t),()=>{e.setAttribute("style",i||"")}}function Xc(e){return e.replace(/([a-z])([A-Z])/g,"$1-$2").toLowerCase()}function on(e,t=()=>{}){let i=!1;return function(){i?t.apply(this,arguments):(i=!0,e.apply(this,arguments))}}z("transition",(e,{value:t,modifiers:i,expression:n},{evaluate:s})=>{typeof n=="function"&&(n=s(n)),n!==!1&&(!n||typeof n=="boolean"?Gc(e,i,t):Jc(e,n,t))});function Jc(e,t,i){fo(e,Mn,""),{enter:s=>{e._x_transition.enter.during=s},"enter-start":s=>{e._x_transition.enter.start=s},"enter-end":s=>{e._x_transition.enter.end=s},leave:s=>{e._x_transition.leave.during=s},"leave-start":s=>{e._x_transition.leave.start=s},"leave-end":s=>{e._x_transition.leave.end=s}}[i](t)}function Gc(e,t,i){fo(e,yi);let n=!t.includes("in")&&!t.includes("out")&&!i,s=n||t.includes("in")||["enter"].includes(i),r=n||t.includes("out")||["leave"].includes(i);t.includes("in")&&!n&&(t=t.filter((w,x)=>x<t.indexOf("out"))),t.includes("out")&&!n&&(t=t.filter((w,x)=>x>t.indexOf("out")));let o=!t.includes("opacity")&&!t.includes("scale"),a=o||t.includes("opacity"),l=o||t.includes("scale"),u=a?0:1,c=l?gt(t,"scale",95)/100:1,d=gt(t,"delay",0)/1e3,f=gt(t,"origin","center"),m="opacity, transform",g=gt(t,"duration",150)/1e3,v=gt(t,"duration",75)/1e3,p="cubic-bezier(0.4, 0.0, 0.2, 1)";s&&(e._x_transition.enter.during={transformOrigin:f,transitionDelay:`${d}s`,transitionProperty:m,transitionDuration:`${g}s`,transitionTimingFunction:p},e._x_transition.enter.start={opacity:u,transform:`scale(${c})`},e._x_transition.enter.end={opacity:1,transform:"scale(1)"}),r&&(e._x_transition.leave.during={transformOrigin:f,transitionDelay:`${d}s`,transitionProperty:m,transitionDuration:`${v}s`,transitionTimingFunction:p},e._x_transition.leave.start={opacity:1,transform:"scale(1)"},e._x_transition.leave.end={opacity:u,transform:`scale(${c})`})}function fo(e,t,i={}){e._x_transition||(e._x_transition={enter:{during:i,start:i,end:i},leave:{during:i,start:i,end:i},in(n=()=>{},s=()=>{}){an(e,t,{during:this.enter.during,start:this.enter.start,end:this.enter.end},n,s)},out(n=()=>{},s=()=>{}){an(e,t,{during:this.leave.during,start:this.leave.start,end:this.leave.end},n,s)}})}window.Element.prototype._x_toggleAndCascadeWithTransitions=function(e,t,i,n){const s=document.visibilityState==="visible"?requestAnimationFrame:setTimeout;let r=()=>s(i);if(t){e._x_transition&&(e._x_transition.enter||e._x_transition.leave)?e._x_transition.enter&&(Object.entries(e._x_transition.enter.during).length||Object.entries(e._x_transition.enter.start).length||Object.entries(e._x_transition.enter.end).length)?e._x_transition.in(i):r():e._x_transition?e._x_transition.in(i):r();return}e._x_hidePromise=e._x_transition?new Promise((o,a)=>{e._x_transition.out(()=>{},()=>o(n)),e._x_transitioning&&e._x_transitioning.beforeCancel(()=>a({isFromCancelledTransition:!0}))}):Promise.resolve(n),queueMicrotask(()=>{let o=mo(e);o?(o._x_hideChildren||(o._x_hideChildren=[]),o._x_hideChildren.push(e)):s(()=>{let a=l=>{let u=Promise.all([l._x_hidePromise,...(l._x_hideChildren||[]).map(a)]).then(([c])=>c==null?void 0:c());return delete l._x_hidePromise,delete l._x_hideChildren,u};a(e).catch(l=>{if(!l.isFromCancelledTransition)throw l})})})};function mo(e){let t=e.parentNode;if(t)return t._x_hidePromise?t:mo(t)}function an(e,t,{during:i,start:n,end:s}={},r=()=>{},o=()=>{}){if(e._x_transitioning&&e._x_transitioning.cancel(),Object.keys(i).length===0&&Object.keys(n).length===0&&Object.keys(s).length===0){r(),o();return}let a,l,u;Yc(e,{start(){a=t(e,n)},during(){l=t(e,i)},before:r,end(){a(),u=t(e,s)},after:o,cleanup(){l(),u()}})}function Yc(e,t){let i,n,s,r=on(()=>{M(()=>{i=!0,n||t.before(),s||(t.end(),rn()),t.after(),e.isConnected&&t.cleanup(),delete e._x_transitioning})});e._x_transitioning={beforeCancels:[],beforeCancel(o){this.beforeCancels.push(o)},cancel:on(function(){for(;this.beforeCancels.length;)this.beforeCancels.shift()();r()}),finish:r},M(()=>{t.start(),t.during()}),Vc(),requestAnimationFrame(()=>{if(i)return;let o=Number(getComputedStyle(e).transitionDuration.replace(/,.*/,"").replace("s",""))*1e3,a=Number(getComputedStyle(e).transitionDelay.replace(/,.*/,"").replace("s",""))*1e3;o===0&&(o=Number(getComputedStyle(e).animationDuration.replace("s",""))*1e3),M(()=>{t.before()}),n=!0,requestAnimationFrame(()=>{i||(M(()=>{t.end()}),rn(),setTimeout(e._x_transitioning.finish,o+a),s=!0)})})}function gt(e,t,i){if(e.indexOf(t)===-1)return i;const n=e[e.indexOf(t)+1];if(!n||t==="scale"&&isNaN(n))return i;if(t==="duration"||t==="delay"){let s=n.match(/([0-9]+)ms/);if(s)return s[1]}return t==="origin"&&["top","right","left","center","bottom"].includes(e[e.indexOf(t)+2])?[n,e[e.indexOf(t)+2]].join(" "):n}var Fe=!1;function Le(e,t=()=>{}){return(...i)=>Fe?t(...i):e(...i)}function Zc(e){return(...t)=>Fe&&e(...t)}var go=[];function vi(e){go.push(e)}function ed(e,t){go.forEach(i=>i(e,t)),Fe=!0,bo(()=>{ve(t,(i,n)=>{n(i,()=>{})})}),Fe=!1}var ln=!1;function td(e,t){t._x_dataStack||(t._x_dataStack=e._x_dataStack),Fe=!0,ln=!0,bo(()=>{id(t)}),Fe=!1,ln=!1}function id(e){let t=!1;ve(e,(n,s)=>{Ke(n,(r,o)=>{if(t&&Uc(r))return o();t=!0,s(r,o)})})}function bo(e){let t=Je;xs((i,n)=>{let s=t(i);return ut(s),()=>{}}),e(),xs(t)}function yo(e,t,i,n=[]){switch(e._x_bindings||(e._x_bindings=lt({})),e._x_bindings[t]=i,t=n.includes("camel")?cd(t):t,t){case"value":nd(e,i);break;case"style":rd(e,i);break;case"class":sd(e,i);break;case"selected":case"checked":od(e,t,i);break;default:vo(e,t,i);break}}function nd(e,t){if(xo(e))e.attributes.value===void 0&&(e.value=t),window.fromModel&&(typeof t=="boolean"?e.checked=Qt(e.value)===t:e.checked=ks(e.value,t));else if(Pn(e))Number.isInteger(t)?e.value=t:!Array.isArray(t)&&typeof t!="boolean"&&![null,void 0].includes(t)?e.value=String(t):Array.isArray(t)?e.checked=t.some(i=>ks(i,e.value)):e.checked=!!t;else if(e.tagName==="SELECT")ud(e,t);else{if(e.value===t)return;e.value=t===void 0?"":t}}function sd(e,t){e._x_undoAddedClasses&&e._x_undoAddedClasses(),e._x_undoAddedClasses=Mn(e,t)}function rd(e,t){e._x_undoAddedStyles&&e._x_undoAddedStyles(),e._x_undoAddedStyles=yi(e,t)}function od(e,t,i){vo(e,t,i),ld(e,t,i)}function vo(e,t,i){[null,void 0,!1].includes(i)&&hd(t)?e.removeAttribute(t):(wo(t)&&(i=t),ad(e,t,i))}function ad(e,t,i){e.getAttribute(t)!=i&&e.setAttribute(t,i)}function ld(e,t,i){e[t]!==i&&(e[t]=i)}function ud(e,t){const i=[].concat(t).map(n=>n+"");Array.from(e.options).forEach(n=>{n.selected=i.includes(n.value)})}function cd(e){return e.toLowerCase().replace(/-(\w)/g,(t,i)=>i.toUpperCase())}function ks(e,t){return e==t}function Qt(e){return[1,"1","true","on","yes",!0].includes(e)?!0:[0,"0","false","off","no",!1].includes(e)?!1:e?!!e:null}var dd=new Set(["allowfullscreen","async","autofocus","autoplay","checked","controls","default","defer","disabled","formnovalidate","inert","ismap","itemscope","loop","multiple","muted","nomodule","novalidate","open","playsinline","readonly","required","reversed","selected","shadowrootclonable","shadowrootdelegatesfocus","shadowrootserializable"]);function wo(e){return dd.has(e)}function hd(e){return!["aria-pressed","aria-checked","aria-expanded","aria-selected"].includes(e)}function pd(e,t,i){return e._x_bindings&&e._x_bindings[t]!==void 0?e._x_bindings[t]:_o(e,t,i)}function fd(e,t,i,n=!0){if(e._x_bindings&&e._x_bindings[t]!==void 0)return e._x_bindings[t];if(e._x_inlineBindings&&e._x_inlineBindings[t]!==void 0){let s=e._x_inlineBindings[t];return s.extract=n,Xr(()=>He(e,s.expression))}return _o(e,t,i)}function _o(e,t,i){let n=e.getAttribute(t);return n===null?typeof i=="function"?i():i:n===""?!0:wo(t)?!![t,"true"].includes(n):n}function Pn(e){return e.type==="checkbox"||e.localName==="ui-checkbox"||e.localName==="ui-switch"}function xo(e){return e.type==="radio"||e.localName==="ui-radio"}function Eo(e,t){var i;return function(){var n=this,s=arguments,r=function(){i=null,e.apply(n,s)};clearTimeout(i),i=setTimeout(r,t)}}function So(e,t){let i;return function(){let n=this,s=arguments;i||(e.apply(n,s),i=!0,setTimeout(()=>i=!1,t))}}function ko({get:e,set:t},{get:i,set:n}){let s=!0,r,o=Je(()=>{let a=e(),l=i();if(s)n(Mi(a)),s=!1;else{let u=JSON.stringify(a),c=JSON.stringify(l);u!==r?n(Mi(a)):u!==c&&t(Mi(l))}r=JSON.stringify(e()),JSON.stringify(i())});return()=>{ut(o)}}function Mi(e){return typeof e=="object"?JSON.parse(JSON.stringify(e)):e}function md(e){(Array.isArray(e)?e:[e]).forEach(i=>i(Mt))}var ze={},Cs=!1;function gd(e,t){if(Cs||(ze=lt(ze),Cs=!0),t===void 0)return ze[e];ze[e]=t,Wr(ze[e]),typeof t=="object"&&t!==null&&t.hasOwnProperty("init")&&typeof t.init=="function"&&ze[e].init()}function bd(){return ze}var Co={};function yd(e,t){let i=typeof t!="function"?()=>t:t;return e instanceof Element?Ao(e,i()):(Co[e]=i,()=>{})}function vd(e){return Object.entries(Co).forEach(([t,i])=>{Object.defineProperty(e,t,{get(){return(...n)=>i(...n)}})}),e}function Ao(e,t,i){let n=[];for(;n.length;)n.pop()();let s=Object.entries(t).map(([o,a])=>({name:o,value:a})),r=Yr(s);return s=s.map(o=>r.find(a=>a.name===o.name)?{name:`x-bind:${o.name}`,value:`"${o.value}"`}:o),Tn(e,s,i).map(o=>{n.push(o.runCleanups),o()}),()=>{for(;n.length;)n.pop()()}}var To={};function wd(e,t){To[e]=t}function _d(e,t){return Object.entries(To).forEach(([i,n])=>{Object.defineProperty(e,i,{get(){return(...s)=>n.bind(t)(...s)},enumerable:!1})}),e}var xd={get reactive(){return lt},get release(){return ut},get effect(){return Je},get raw(){return zr},version:"3.14.9",flushAndStopDeferringMutations:Sc,dontAutoEvaluateFunctions:Xr,disableEffectScheduling:bc,startObservingMutations:Sn,stopObservingMutations:jr,setReactivityEngine:yc,onAttributeRemoved:Hr,onAttributesAdded:Ur,closestDataStack:Ze,skipDuringClone:Le,onlyDuringClone:Zc,addRootSelector:co,addInitSelector:ho,interceptClone:vi,addScopeToNode:Ot,deferMutations:Ec,mapAttributes:Rn,evaluateLater:V,interceptInit:Hc,setEvaluator:Fc,mergeProxies:Lt,extractProp:fd,findClosest:dt,onElRemoved:_n,closestRoot:bi,destroyTree:ht,interceptor:Kr,transition:an,setStyles:yi,mutateDom:M,directive:z,entangle:ko,throttle:So,debounce:Eo,evaluate:He,initTree:ve,nextTick:Ln,prefixed:ct,prefix:Pc,plugin:md,magic:ue,store:gd,start:Nc,clone:td,cloneNode:ed,bound:pd,$data:Vr,watch:$r,walk:Ke,data:wd,bind:yd},Mt=xd;function Ed(e,t){const i=Object.create(null),n=e.split(",");for(let s=0;s<n.length;s++)i[n[s]]=!0;return s=>!!i[s]}var Sd=Object.freeze({}),kd=Object.prototype.hasOwnProperty,wi=(e,t)=>kd.call(e,t),qe=Array.isArray,_t=e=>Ro(e)==="[object Map]",Cd=e=>typeof e=="string",In=e=>typeof e=="symbol",_i=e=>e!==null&&typeof e=="object",Ad=Object.prototype.toString,Ro=e=>Ad.call(e),Fo=e=>Ro(e).slice(8,-1),zn=e=>Cd(e)&&e!=="NaN"&&e[0]!=="-"&&""+parseInt(e,10)===e,Td=e=>{const t=Object.create(null);return i=>t[i]||(t[i]=e(i))},Rd=Td(e=>e.charAt(0).toUpperCase()+e.slice(1)),Oo=(e,t)=>e!==t&&(e===e||t===t),un=new WeakMap,bt=[],he,je=Symbol("iterate"),cn=Symbol("Map key iterate");function Fd(e){return e&&e._isEffect===!0}function Od(e,t=Sd){Fd(e)&&(e=e.raw);const i=Pd(e,t);return t.lazy||i(),i}function Ld(e){e.active&&(Lo(e),e.options.onStop&&e.options.onStop(),e.active=!1)}var Md=0;function Pd(e,t){const i=function(){if(!i.active)return e();if(!bt.includes(i)){Lo(i);try{return zd(),bt.push(i),he=i,e()}finally{bt.pop(),Mo(),he=bt[bt.length-1]}}};return i.id=Md++,i.allowRecurse=!!t.allowRecurse,i._isEffect=!0,i.active=!0,i.raw=e,i.deps=[],i.options=t,i}function Lo(e){const{deps:t}=e;if(t.length){for(let i=0;i<t.length;i++)t[i].delete(e);t.length=0}}var et=!0,$n=[];function Id(){$n.push(et),et=!1}function zd(){$n.push(et),et=!0}function Mo(){const e=$n.pop();et=e===void 0?!0:e}function ae(e,t,i){if(!et||he===void 0)return;let n=un.get(e);n||un.set(e,n=new Map);let s=n.get(i);s||n.set(i,s=new Set),s.has(he)||(s.add(he),he.deps.push(s),he.options.onTrack&&he.options.onTrack({effect:he,target:e,type:t,key:i}))}function Oe(e,t,i,n,s,r){const o=un.get(e);if(!o)return;const a=new Set,l=c=>{c&&c.forEach(d=>{(d!==he||d.allowRecurse)&&a.add(d)})};if(t==="clear")o.forEach(l);else if(i==="length"&&qe(e))o.forEach((c,d)=>{(d==="length"||d>=n)&&l(c)});else switch(i!==void 0&&l(o.get(i)),t){case"add":qe(e)?zn(i)&&l(o.get("length")):(l(o.get(je)),_t(e)&&l(o.get(cn)));break;case"delete":qe(e)||(l(o.get(je)),_t(e)&&l(o.get(cn)));break;case"set":_t(e)&&l(o.get(je));break}const u=c=>{c.options.onTrigger&&c.options.onTrigger({effect:c,target:e,key:i,type:t,newValue:n,oldValue:s,oldTarget:r}),c.options.scheduler?c.options.scheduler(c):c()};a.forEach(u)}var $d=Ed("__proto__,__v_isRef,__isVue"),Po=new Set(Object.getOwnPropertyNames(Symbol).map(e=>Symbol[e]).filter(In)),Bd=Io(),Dd=Io(!0),As=Nd();function Nd(){const e={};return["includes","indexOf","lastIndexOf"].forEach(t=>{e[t]=function(...i){const n=L(this);for(let r=0,o=this.length;r<o;r++)ae(n,"get",r+"");const s=n[t](...i);return s===-1||s===!1?n[t](...i.map(L)):s}}),["push","pop","shift","unshift","splice"].forEach(t=>{e[t]=function(...i){Id();const n=L(this)[t].apply(this,i);return Mo(),n}}),e}function Io(e=!1,t=!1){return function(n,s,r){if(s==="__v_isReactive")return!e;if(s==="__v_isReadonly")return e;if(s==="__v_raw"&&r===(e?t?eh:Do:t?Zd:Bo).get(n))return n;const o=qe(n);if(!e&&o&&wi(As,s))return Reflect.get(As,s,r);const a=Reflect.get(n,s,r);return(In(s)?Po.has(s):$d(s))||(e||ae(n,"get",s),t)?a:dn(a)?!o||!zn(s)?a.value:a:_i(a)?e?No(a):Un(a):a}}var Ud=Hd();function Hd(e=!1){return function(i,n,s,r){let o=i[n];if(!e&&(s=L(s),o=L(o),!qe(i)&&dn(o)&&!dn(s)))return o.value=s,!0;const a=qe(i)&&zn(n)?Number(n)<i.length:wi(i,n),l=Reflect.set(i,n,s,r);return i===L(r)&&(a?Oo(s,o)&&Oe(i,"set",n,s,o):Oe(i,"add",n,s)),l}}function qd(e,t){const i=wi(e,t),n=e[t],s=Reflect.deleteProperty(e,t);return s&&i&&Oe(e,"delete",t,void 0,n),s}function jd(e,t){const i=Reflect.has(e,t);return(!In(t)||!Po.has(t))&&ae(e,"has",t),i}function Vd(e){return ae(e,"iterate",qe(e)?"length":je),Reflect.ownKeys(e)}var Wd={get:Bd,set:Ud,deleteProperty:qd,has:jd,ownKeys:Vd},Kd={get:Dd,set(e,t){return console.warn(`Set operation on key "${String(t)}" failed: target is readonly.`,e),!0},deleteProperty(e,t){return console.warn(`Delete operation on key "${String(t)}" failed: target is readonly.`,e),!0}},Bn=e=>_i(e)?Un(e):e,Dn=e=>_i(e)?No(e):e,Nn=e=>e,xi=e=>Reflect.getPrototypeOf(e);function Bt(e,t,i=!1,n=!1){e=e.__v_raw;const s=L(e),r=L(t);t!==r&&!i&&ae(s,"get",t),!i&&ae(s,"get",r);const{has:o}=xi(s),a=n?Nn:i?Dn:Bn;if(o.call(s,t))return a(e.get(t));if(o.call(s,r))return a(e.get(r));e!==s&&e.get(t)}function Dt(e,t=!1){const i=this.__v_raw,n=L(i),s=L(e);return e!==s&&!t&&ae(n,"has",e),!t&&ae(n,"has",s),e===s?i.has(e):i.has(e)||i.has(s)}function Nt(e,t=!1){return e=e.__v_raw,!t&&ae(L(e),"iterate",je),Reflect.get(e,"size",e)}function Ts(e){e=L(e);const t=L(this);return xi(t).has.call(t,e)||(t.add(e),Oe(t,"add",e,e)),this}function Rs(e,t){t=L(t);const i=L(this),{has:n,get:s}=xi(i);let r=n.call(i,e);r?$o(i,n,e):(e=L(e),r=n.call(i,e));const o=s.call(i,e);return i.set(e,t),r?Oo(t,o)&&Oe(i,"set",e,t,o):Oe(i,"add",e,t),this}function Fs(e){const t=L(this),{has:i,get:n}=xi(t);let s=i.call(t,e);s?$o(t,i,e):(e=L(e),s=i.call(t,e));const r=n?n.call(t,e):void 0,o=t.delete(e);return s&&Oe(t,"delete",e,void 0,r),o}function Os(){const e=L(this),t=e.size!==0,i=_t(e)?new Map(e):new Set(e),n=e.clear();return t&&Oe(e,"clear",void 0,void 0,i),n}function Ut(e,t){return function(n,s){const r=this,o=r.__v_raw,a=L(o),l=t?Nn:e?Dn:Bn;return!e&&ae(a,"iterate",je),o.forEach((u,c)=>n.call(s,l(u),l(c),r))}}function Ht(e,t,i){return function(...n){const s=this.__v_raw,r=L(s),o=_t(r),a=e==="entries"||e===Symbol.iterator&&o,l=e==="keys"&&o,u=s[e](...n),c=i?Nn:t?Dn:Bn;return!t&&ae(r,"iterate",l?cn:je),{next(){const{value:d,done:f}=u.next();return f?{value:d,done:f}:{value:a?[c(d[0]),c(d[1])]:c(d),done:f}},[Symbol.iterator](){return this}}}}function Se(e){return function(...t){{const i=t[0]?`on key "${t[0]}" `:"";console.warn(`${Rd(e)} operation ${i}failed: target is readonly.`,L(this))}return e==="delete"?!1:this}}function Qd(){const e={get(r){return Bt(this,r)},get size(){return Nt(this)},has:Dt,add:Ts,set:Rs,delete:Fs,clear:Os,forEach:Ut(!1,!1)},t={get(r){return Bt(this,r,!1,!0)},get size(){return Nt(this)},has:Dt,add:Ts,set:Rs,delete:Fs,clear:Os,forEach:Ut(!1,!0)},i={get(r){return Bt(this,r,!0)},get size(){return Nt(this,!0)},has(r){return Dt.call(this,r,!0)},add:Se("add"),set:Se("set"),delete:Se("delete"),clear:Se("clear"),forEach:Ut(!0,!1)},n={get(r){return Bt(this,r,!0,!0)},get size(){return Nt(this,!0)},has(r){return Dt.call(this,r,!0)},add:Se("add"),set:Se("set"),delete:Se("delete"),clear:Se("clear"),forEach:Ut(!0,!0)};return["keys","values","entries",Symbol.iterator].forEach(r=>{e[r]=Ht(r,!1,!1),i[r]=Ht(r,!0,!1),t[r]=Ht(r,!1,!0),n[r]=Ht(r,!0,!0)}),[e,i,t,n]}var[Xd,Jd,tp,ip]=Qd();function zo(e,t){const i=e?Jd:Xd;return(n,s,r)=>s==="__v_isReactive"?!e:s==="__v_isReadonly"?e:s==="__v_raw"?n:Reflect.get(wi(i,s)&&s in n?i:n,s,r)}var Gd={get:zo(!1)},Yd={get:zo(!0)};function $o(e,t,i){const n=L(i);if(n!==i&&t.call(e,n)){const s=Fo(e);console.warn(`Reactive ${s} contains both the raw and reactive versions of the same object${s==="Map"?" as keys":""}, which can lead to inconsistencies. Avoid differentiating between the raw and reactive versions of an object and only use the reactive version if possible.`)}}var Bo=new WeakMap,Zd=new WeakMap,Do=new WeakMap,eh=new WeakMap;function th(e){switch(e){case"Object":case"Array":return 1;case"Map":case"Set":case"WeakMap":case"WeakSet":return 2;default:return 0}}function ih(e){return e.__v_skip||!Object.isExtensible(e)?0:th(Fo(e))}function Un(e){return e&&e.__v_isReadonly?e:Uo(e,!1,Wd,Gd,Bo)}function No(e){return Uo(e,!0,Kd,Yd,Do)}function Uo(e,t,i,n,s){if(!_i(e))return console.warn(`value cannot be made reactive: ${String(e)}`),e;if(e.__v_raw&&!(t&&e.__v_isReactive))return e;const r=s.get(e);if(r)return r;const o=ih(e);if(o===0)return e;const a=new Proxy(e,o===2?n:i);return s.set(e,a),a}function L(e){return e&&L(e.__v_raw)||e}function dn(e){return!!(e&&e.__v_isRef===!0)}ue("nextTick",()=>Ln);ue("dispatch",e=>wt.bind(wt,e));ue("watch",(e,{evaluateLater:t,cleanup:i})=>(n,s)=>{let r=t(n),a=$r(()=>{let l;return r(u=>l=u),l},s);i(a)});ue("store",bd);ue("data",e=>Vr(e));ue("root",e=>bi(e));ue("refs",e=>(e._x_refs_proxy||(e._x_refs_proxy=Lt(nh(e))),e._x_refs_proxy));function nh(e){let t=[];return dt(e,i=>{i._x_refs&&t.push(i._x_refs)}),t}var Pi={};function Ho(e){return Pi[e]||(Pi[e]=0),++Pi[e]}function sh(e,t){return dt(e,i=>{if(i._x_ids&&i._x_ids[t])return!0})}function rh(e,t){e._x_ids||(e._x_ids={}),e._x_ids[t]||(e._x_ids[t]=Ho(t))}ue("id",(e,{cleanup:t})=>(i,n=null)=>{let s=`${i}${n?`-${n}`:""}`;return oh(e,s,t,()=>{let r=sh(e,i),o=r?r._x_ids[i]:Ho(i);return n?`${i}-${o}-${n}`:`${i}-${o}`})});vi((e,t)=>{e._x_id&&(t._x_id=e._x_id)});function oh(e,t,i,n){if(e._x_id||(e._x_id={}),e._x_id[t])return e._x_id[t];let s=n();return e._x_id[t]=s,i(()=>{delete e._x_id[t]}),s}ue("el",e=>e);qo("Focus","focus","focus");qo("Persist","persist","persist");function qo(e,t,i){ue(t,n=>te(`You can't use [$${t}] without first installing the "${e}" plugin here: https://alpinejs.dev/plugins/${i}`,n))}z("modelable",(e,{expression:t},{effect:i,evaluateLater:n,cleanup:s})=>{let r=n(t),o=()=>{let c;return r(d=>c=d),c},a=n(`${t} = __placeholder`),l=c=>a(()=>{},{scope:{__placeholder:c}}),u=o();l(u),queueMicrotask(()=>{if(!e._x_model)return;e._x_removeModelListeners.default();let c=e._x_model.get,d=e._x_model.set,f=ko({get(){return c()},set(m){d(m)}},{get(){return o()},set(m){l(m)}});s(f)})});z("teleport",(e,{modifiers:t,expression:i},{cleanup:n})=>{e.tagName.toLowerCase()!=="template"&&te("x-teleport can only be used on a <template> tag",e);let s=Ls(i),r=e.content.cloneNode(!0).firstElementChild;e._x_teleport=r,r._x_teleportBack=e,e.setAttribute("data-teleport-template",!0),r.setAttribute("data-teleport-target",!0),e._x_forwardEvents&&e._x_forwardEvents.forEach(a=>{r.addEventListener(a,l=>{l.stopPropagation(),e.dispatchEvent(new l.constructor(l.type,l))})}),Ot(r,{},e);let o=(a,l,u)=>{u.includes("prepend")?l.parentNode.insertBefore(a,l):u.includes("append")?l.parentNode.insertBefore(a,l.nextSibling):l.appendChild(a)};M(()=>{o(r,s,t),Le(()=>{ve(r)})()}),e._x_teleportPutBack=()=>{let a=Ls(i);M(()=>{o(e._x_teleport,a,t)})},n(()=>M(()=>{r.remove(),ht(r)}))});var ah=document.createElement("div");function Ls(e){let t=Le(()=>document.querySelector(e),()=>ah)();return t||te(`Cannot find x-teleport element for selector: "${e}"`),t}var jo=()=>{};jo.inline=(e,{modifiers:t},{cleanup:i})=>{t.includes("self")?e._x_ignoreSelf=!0:e._x_ignore=!0,i(()=>{t.includes("self")?delete e._x_ignoreSelf:delete e._x_ignore})};z("ignore",jo);z("effect",Le((e,{expression:t},{effect:i})=>{i(V(e,t))}));function hn(e,t,i,n){let s=e,r=l=>n(l),o={},a=(l,u)=>c=>u(l,c);if(i.includes("dot")&&(t=lh(t)),i.includes("camel")&&(t=uh(t)),i.includes("passive")&&(o.passive=!0),i.includes("capture")&&(o.capture=!0),i.includes("window")&&(s=window),i.includes("document")&&(s=document),i.includes("debounce")){let l=i[i.indexOf("debounce")+1]||"invalid-wait",u=si(l.split("ms")[0])?Number(l.split("ms")[0]):250;r=Eo(r,u)}if(i.includes("throttle")){let l=i[i.indexOf("throttle")+1]||"invalid-wait",u=si(l.split("ms")[0])?Number(l.split("ms")[0]):250;r=So(r,u)}return i.includes("prevent")&&(r=a(r,(l,u)=>{u.preventDefault(),l(u)})),i.includes("stop")&&(r=a(r,(l,u)=>{u.stopPropagation(),l(u)})),i.includes("once")&&(r=a(r,(l,u)=>{l(u),s.removeEventListener(t,r,o)})),(i.includes("away")||i.includes("outside"))&&(s=document,r=a(r,(l,u)=>{e.contains(u.target)||u.target.isConnected!==!1&&(e.offsetWidth<1&&e.offsetHeight<1||e._x_isShown!==!1&&l(u))})),i.includes("self")&&(r=a(r,(l,u)=>{u.target===e&&l(u)})),(dh(t)||Vo(t))&&(r=a(r,(l,u)=>{hh(u,i)||l(u)})),s.addEventListener(t,r,o),()=>{s.removeEventListener(t,r,o)}}function lh(e){return e.replace(/-/g,".")}function uh(e){return e.toLowerCase().replace(/-(\w)/g,(t,i)=>i.toUpperCase())}function si(e){return!Array.isArray(e)&&!isNaN(e)}function ch(e){return[" ","_"].includes(e)?e:e.replace(/([a-z])([A-Z])/g,"$1-$2").replace(/[_\s]/,"-").toLowerCase()}function dh(e){return["keydown","keyup"].includes(e)}function Vo(e){return["contextmenu","click","mouse"].some(t=>e.includes(t))}function hh(e,t){let i=t.filter(r=>!["window","document","prevent","stop","once","capture","self","away","outside","passive"].includes(r));if(i.includes("debounce")){let r=i.indexOf("debounce");i.splice(r,si((i[r+1]||"invalid-wait").split("ms")[0])?2:1)}if(i.includes("throttle")){let r=i.indexOf("throttle");i.splice(r,si((i[r+1]||"invalid-wait").split("ms")[0])?2:1)}if(i.length===0||i.length===1&&Ms(e.key).includes(i[0]))return!1;const s=["ctrl","shift","alt","meta","cmd","super"].filter(r=>i.includes(r));return i=i.filter(r=>!s.includes(r)),!(s.length>0&&s.filter(o=>((o==="cmd"||o==="super")&&(o="meta"),e[`${o}Key`])).length===s.length&&(Vo(e.type)||Ms(e.key).includes(i[0])))}function Ms(e){if(!e)return[];e=ch(e);let t={ctrl:"control",slash:"/",space:" ",spacebar:" ",cmd:"meta",esc:"escape",up:"arrow-up",down:"arrow-down",left:"arrow-left",right:"arrow-right",period:".",comma:",",equal:"=",minus:"-",underscore:"_"};return t[e]=e,Object.keys(t).map(i=>{if(t[i]===e)return i}).filter(i=>i)}z("model",(e,{modifiers:t,expression:i},{effect:n,cleanup:s})=>{let r=e;t.includes("parent")&&(r=e.parentNode);let o=V(r,i),a;typeof i=="string"?a=V(r,`${i} = __placeholder`):typeof i=="function"&&typeof i()=="string"?a=V(r,`${i()} = __placeholder`):a=()=>{};let l=()=>{let f;return o(m=>f=m),Ps(f)?f.get():f},u=f=>{let m;o(g=>m=g),Ps(m)?m.set(f):a(()=>{},{scope:{__placeholder:f}})};typeof i=="string"&&e.type==="radio"&&M(()=>{e.hasAttribute("name")||e.setAttribute("name",i)});var c=e.tagName.toLowerCase()==="select"||["checkbox","radio"].includes(e.type)||t.includes("lazy")?"change":"input";let d=Fe?()=>{}:hn(e,c,t,f=>{u(Ii(e,t,f,l()))});if(t.includes("fill")&&([void 0,null,""].includes(l())||Pn(e)&&Array.isArray(l())||e.tagName.toLowerCase()==="select"&&e.multiple)&&u(Ii(e,t,{target:e},l())),e._x_removeModelListeners||(e._x_removeModelListeners={}),e._x_removeModelListeners.default=d,s(()=>e._x_removeModelListeners.default()),e.form){let f=hn(e.form,"reset",[],m=>{Ln(()=>e._x_model&&e._x_model.set(Ii(e,t,{target:e},l())))});s(()=>f())}e._x_model={get(){return l()},set(f){u(f)}},e._x_forceModelUpdate=f=>{f===void 0&&typeof i=="string"&&i.match(/\./)&&(f=""),window.fromModel=!0,M(()=>yo(e,"value",f)),delete window.fromModel},n(()=>{let f=l();t.includes("unintrusive")&&document.activeElement.isSameNode(e)||e._x_forceModelUpdate(f)})});function Ii(e,t,i,n){return M(()=>{if(i instanceof CustomEvent&&i.detail!==void 0)return i.detail!==null&&i.detail!==void 0?i.detail:i.target.value;if(Pn(e))if(Array.isArray(n)){let s=null;return t.includes("number")?s=zi(i.target.value):t.includes("boolean")?s=Qt(i.target.value):s=i.target.value,i.target.checked?n.includes(s)?n:n.concat([s]):n.filter(r=>!ph(r,s))}else return i.target.checked;else{if(e.tagName.toLowerCase()==="select"&&e.multiple)return t.includes("number")?Array.from(i.target.selectedOptions).map(s=>{let r=s.value||s.text;return zi(r)}):t.includes("boolean")?Array.from(i.target.selectedOptions).map(s=>{let r=s.value||s.text;return Qt(r)}):Array.from(i.target.selectedOptions).map(s=>s.value||s.text);{let s;return xo(e)?i.target.checked?s=i.target.value:s=n:s=i.target.value,t.includes("number")?zi(s):t.includes("boolean")?Qt(s):t.includes("trim")?s.trim():s}}})}function zi(e){let t=e?parseFloat(e):null;return fh(t)?t:e}function ph(e,t){return e==t}function fh(e){return!Array.isArray(e)&&!isNaN(e)}function Ps(e){return e!==null&&typeof e=="object"&&typeof e.get=="function"&&typeof e.set=="function"}z("cloak",e=>queueMicrotask(()=>M(()=>e.removeAttribute(ct("cloak")))));ho(()=>`[${ct("init")}]`);z("init",Le((e,{expression:t},{evaluate:i})=>typeof t=="string"?!!t.trim()&&i(t,{},!1):i(t,{},!1)));z("text",(e,{expression:t},{effect:i,evaluateLater:n})=>{let s=n(t);i(()=>{s(r=>{M(()=>{e.textContent=r})})})});z("html",(e,{expression:t},{effect:i,evaluateLater:n})=>{let s=n(t);i(()=>{s(r=>{M(()=>{e.innerHTML=r,e._x_ignoreSelf=!0,ve(e),delete e._x_ignoreSelf})})})});Rn(to(":",io(ct("bind:"))));var Wo=(e,{value:t,modifiers:i,expression:n,original:s},{effect:r,cleanup:o})=>{if(!t){let l={};vd(l),V(e,n)(c=>{Ao(e,c,s)},{scope:l});return}if(t==="key")return mh(e,n);if(e._x_inlineBindings&&e._x_inlineBindings[t]&&e._x_inlineBindings[t].extract)return;let a=V(e,n);r(()=>a(l=>{l===void 0&&typeof n=="string"&&n.match(/\./)&&(l=""),M(()=>yo(e,t,l,i))})),o(()=>{e._x_undoAddedClasses&&e._x_undoAddedClasses(),e._x_undoAddedStyles&&e._x_undoAddedStyles()})};Wo.inline=(e,{value:t,modifiers:i,expression:n})=>{t&&(e._x_inlineBindings||(e._x_inlineBindings={}),e._x_inlineBindings[t]={expression:n,extract:!1})};z("bind",Wo);function mh(e,t){e._x_keyExpression=t}co(()=>`[${ct("data")}]`);z("data",(e,{expression:t},{cleanup:i})=>{if(gh(e))return;t=t===""?"{}":t;let n={};en(n,e);let s={};_d(s,n);let r=He(e,t,{scope:s});(r===void 0||r===!0)&&(r={}),en(r,e);let o=lt(r);Wr(o);let a=Ot(e,o);o.init&&He(e,o.init),i(()=>{o.destroy&&He(e,o.destroy),a()})});vi((e,t)=>{e._x_dataStack&&(t._x_dataStack=e._x_dataStack,t.setAttribute("data-has-alpine-state",!0))});function gh(e){return Fe?ln?!0:e.hasAttribute("data-has-alpine-state"):!1}z("show",(e,{modifiers:t,expression:i},{effect:n})=>{let s=V(e,i);e._x_doHide||(e._x_doHide=()=>{M(()=>{e.style.setProperty("display","none",t.includes("important")?"important":void 0)})}),e._x_doShow||(e._x_doShow=()=>{M(()=>{e.style.length===1&&e.style.display==="none"?e.removeAttribute("style"):e.style.removeProperty("display")})});let r=()=>{e._x_doHide(),e._x_isShown=!1},o=()=>{e._x_doShow(),e._x_isShown=!0},a=()=>setTimeout(o),l=on(d=>d?o():r(),d=>{typeof e._x_toggleAndCascadeWithTransitions=="function"?e._x_toggleAndCascadeWithTransitions(e,d,o,r):d?a():r()}),u,c=!0;n(()=>s(d=>{!c&&d===u||(t.includes("immediate")&&(d?a():r()),l(d),u=d,c=!1)}))});z("for",(e,{expression:t},{effect:i,cleanup:n})=>{let s=yh(t),r=V(e,s.items),o=V(e,e._x_keyExpression||"index");e._x_prevKeys=[],e._x_lookup={},i(()=>bh(e,s,r,o)),n(()=>{Object.values(e._x_lookup).forEach(a=>M(()=>{ht(a),a.remove()})),delete e._x_prevKeys,delete e._x_lookup})});function bh(e,t,i,n){let s=o=>typeof o=="object"&&!Array.isArray(o),r=e;i(o=>{vh(o)&&o>=0&&(o=Array.from(Array(o).keys(),p=>p+1)),o===void 0&&(o=[]);let a=e._x_lookup,l=e._x_prevKeys,u=[],c=[];if(s(o))o=Object.entries(o).map(([p,w])=>{let x=Is(t,w,p,o);n(E=>{c.includes(E)&&te("Duplicate key on x-for",e),c.push(E)},{scope:{index:p,...x}}),u.push(x)});else for(let p=0;p<o.length;p++){let w=Is(t,o[p],p,o);n(x=>{c.includes(x)&&te("Duplicate key on x-for",e),c.push(x)},{scope:{index:p,...w}}),u.push(w)}let d=[],f=[],m=[],g=[];for(let p=0;p<l.length;p++){let w=l[p];c.indexOf(w)===-1&&m.push(w)}l=l.filter(p=>!m.includes(p));let v="template";for(let p=0;p<c.length;p++){let w=c[p],x=l.indexOf(w);if(x===-1)l.splice(p,0,w),d.push([v,p]);else if(x!==p){let E=l.splice(p,1)[0],C=l.splice(x-1,1)[0];l.splice(p,0,C),l.splice(x,0,E),f.push([E,C])}else g.push(w);v=w}for(let p=0;p<m.length;p++){let w=m[p];w in a&&(M(()=>{ht(a[w]),a[w].remove()}),delete a[w])}for(let p=0;p<f.length;p++){let[w,x]=f[p],E=a[w],C=a[x],S=document.createElement("div");M(()=>{C||te('x-for ":key" is undefined or invalid',r,x,a),C.after(S),E.after(C),C._x_currentIfEl&&C.after(C._x_currentIfEl),S.before(E),E._x_currentIfEl&&E.after(E._x_currentIfEl),S.remove()}),C._x_refreshXForScope(u[c.indexOf(x)])}for(let p=0;p<d.length;p++){let[w,x]=d[p],E=w==="template"?r:a[w];E._x_currentIfEl&&(E=E._x_currentIfEl);let C=u[x],S=c[x],F=document.importNode(r.content,!0).firstElementChild,R=lt(C);Ot(F,R,r),F._x_refreshXForScope=N=>{Object.entries(N).forEach(([q,W])=>{R[q]=W})},M(()=>{E.after(F),Le(()=>ve(F))()}),typeof S=="object"&&te("x-for key cannot be an object, it must be a string or an integer",r),a[S]=F}for(let p=0;p<g.length;p++)a[g[p]]._x_refreshXForScope(u[c.indexOf(g[p])]);r._x_prevKeys=c})}function yh(e){let t=/,([^,\}\]]*)(?:,([^,\}\]]*))?$/,i=/^\s*\(|\)\s*$/g,n=/([\s\S]*?)\s+(?:in|of)\s+([\s\S]*)/,s=e.match(n);if(!s)return;let r={};r.items=s[2].trim();let o=s[1].replace(i,"").trim(),a=o.match(t);return a?(r.item=o.replace(t,"").trim(),r.index=a[1].trim(),a[2]&&(r.collection=a[2].trim())):r.item=o,r}function Is(e,t,i,n){let s={};return/^\[.*\]$/.test(e.item)&&Array.isArray(t)?e.item.replace("[","").replace("]","").split(",").map(o=>o.trim()).forEach((o,a)=>{s[o]=t[a]}):/^\{.*\}$/.test(e.item)&&!Array.isArray(t)&&typeof t=="object"?e.item.replace("{","").replace("}","").split(",").map(o=>o.trim()).forEach(o=>{s[o]=t[o]}):s[e.item]=t,e.index&&(s[e.index]=i),e.collection&&(s[e.collection]=n),s}function vh(e){return!Array.isArray(e)&&!isNaN(e)}function Ko(){}Ko.inline=(e,{expression:t},{cleanup:i})=>{let n=bi(e);n._x_refs||(n._x_refs={}),n._x_refs[t]=e,i(()=>delete n._x_refs[t])};z("ref",Ko);z("if",(e,{expression:t},{effect:i,cleanup:n})=>{e.tagName.toLowerCase()!=="template"&&te("x-if can only be used on a <template> tag",e);let s=V(e,t),r=()=>{if(e._x_currentIfEl)return e._x_currentIfEl;let a=e.content.cloneNode(!0).firstElementChild;return Ot(a,{},e),M(()=>{e.after(a),Le(()=>ve(a))()}),e._x_currentIfEl=a,e._x_undoIf=()=>{M(()=>{ht(a),a.remove()}),delete e._x_currentIfEl},a},o=()=>{e._x_undoIf&&(e._x_undoIf(),delete e._x_undoIf)};i(()=>s(a=>{a?r():o()})),n(()=>e._x_undoIf&&e._x_undoIf())});z("id",(e,{expression:t},{evaluate:i})=>{i(t).forEach(s=>rh(e,s))});vi((e,t)=>{e._x_ids&&(t._x_ids=e._x_ids)});Rn(to("@",io(ct("on:"))));z("on",Le((e,{value:t,modifiers:i,expression:n},{cleanup:s})=>{let r=n?V(e,n):()=>{};e.tagName.toLowerCase()==="template"&&(e._x_forwardEvents||(e._x_forwardEvents=[]),e._x_forwardEvents.includes(t)||e._x_forwardEvents.push(t));let o=hn(e,t,i,a=>{r(()=>{},{scope:{$event:a},params:[a]})});s(()=>o())}));Ei("Collapse","collapse","collapse");Ei("Intersect","intersect","intersect");Ei("Focus","trap","focus");Ei("Mask","mask","mask");function Ei(e,t,i){z(t,n=>te(`You can't use [x-${t}] without first installing the "${e}" plugin here: https://alpinejs.dev/plugins/${i}`,n))}Mt.setEvaluator(Gr);Mt.setReactivityEngine({reactive:Un,effect:Od,release:Ld,raw:L});var wh=Mt,Hn=wh;function _h(e){let t=()=>{let i,n;try{n=localStorage}catch(s){console.error(s),console.warn("Alpine: $persist is using temporary storage since localStorage is unavailable.");let r=new Map;n={getItem:r.get.bind(r),setItem:r.set.bind(r)}}return e.interceptor((s,r,o,a,l)=>{let u=i||`_x_${a}`,c=zs(u,n)?$s(u,n):s;return o(c),e.effect(()=>{let d=r();Bs(u,d,n),o(d)}),c},s=>{s.as=r=>(i=r,s),s.using=r=>(n=r,s)})};Object.defineProperty(e,"$persist",{get:()=>t()}),e.magic("persist",t),e.persist=(i,{get:n,set:s},r=localStorage)=>{let o=zs(i,r)?$s(i,r):n();s(o),e.effect(()=>{let a=n();Bs(i,a,r),s(a)})}}function zs(e,t){return t.getItem(e)!==null}function $s(e,t){let i=t.getItem(e,t);if(i!==void 0)return JSON.parse(i)}function Bs(e,t,i){i.setItem(e,JSON.stringify(t))}var xh=_h,Ds=Qo;function Qo(){var e=[].slice.call(arguments),t=!1;typeof e[0]=="boolean"&&(t=e.shift());var i=e[0];if(Ns(i))throw new Error("extendee must be an object");for(var n=e.slice(1),s=n.length,r=0;r<s;r++){var o=n[r];for(var a in o)if(Object.prototype.hasOwnProperty.call(o,a)){var l=o[a];if(t&&Eh(l)){var u=Array.isArray(l)?[]:{};i[a]=Qo(!0,Object.prototype.hasOwnProperty.call(i,a)&&!Ns(i[a])?i[a]:u,l)}else i[a]=l}}return i}function Eh(e){return Array.isArray(e)||{}.toString.call(e)=="[object Object]"}function Ns(e){return!e||typeof e!="object"&&typeof e!="function"}function Sh(e){return e&&e.__esModule?e.default:e}class Us{on(t,i){return this._callbacks=this._callbacks||{},this._callbacks[t]||(this._callbacks[t]=[]),this._callbacks[t].push(i),this}emit(t,...i){this._callbacks=this._callbacks||{};let n=this._callbacks[t];if(n)for(let s of n)s.apply(this,i);return this.element&&this.element.dispatchEvent(this.makeEvent("dropzone:"+t,{args:i})),this}makeEvent(t,i){let n={bubbles:!0,cancelable:!0,detail:i};if(typeof window.CustomEvent=="function")return new CustomEvent(t,n);var s=document.createEvent("CustomEvent");return s.initCustomEvent(t,n.bubbles,n.cancelable,n.detail),s}off(t,i){if(!this._callbacks||arguments.length===0)return this._callbacks={},this;let n=this._callbacks[t];if(!n)return this;if(arguments.length===1)return delete this._callbacks[t],this;for(let s=0;s<n.length;s++)if(n[s]===i){n.splice(s,1);break}return this}}var Xo={};Xo=`<div class="dz-preview dz-file-preview">
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
`;let kh={url:null,method:"post",withCredentials:!1,timeout:null,parallelUploads:2,uploadMultiple:!1,chunking:!1,forceChunking:!1,chunkSize:2097152,parallelChunkUploads:!1,retryChunks:!1,retryChunksLimit:3,maxFilesize:256,paramName:"file",createImageThumbnails:!0,maxThumbnailFilesize:10,thumbnailWidth:120,thumbnailHeight:120,thumbnailMethod:"crop",resizeWidth:null,resizeHeight:null,resizeMimeType:null,resizeQuality:.8,resizeMethod:"contain",filesizeBase:1e3,maxFiles:null,headers:null,defaultHeaders:!0,clickable:!0,ignoreHiddenFiles:!0,acceptedFiles:null,acceptedMimeTypes:null,autoProcessQueue:!0,autoQueue:!0,addRemoveLinks:!1,previewsContainer:null,disablePreviews:!1,hiddenInputContainer:"body",capture:null,renameFilename:null,renameFile:null,forceFallback:!1,dictDefaultMessage:"Drop files here to upload",dictFallbackMessage:"Your browser does not support drag'n'drop file uploads.",dictFallbackText:"Please use the fallback form below to upload your files like in the olden days.",dictFileTooBig:"File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.",dictInvalidFileType:"You can't upload files of this type.",dictResponseError:"Server responded with {{statusCode}} code.",dictCancelUpload:"Cancel upload",dictUploadCanceled:"Upload canceled.",dictCancelUploadConfirmation:"Are you sure you want to cancel this upload?",dictRemoveFile:"Remove file",dictRemoveFileConfirmation:null,dictMaxFilesExceeded:"You can not upload any more files.",dictFileSizeUnits:{tb:"TB",gb:"GB",mb:"MB",kb:"KB",b:"b"},init(){},params(e,t,i){if(i)return{dzuuid:i.file.upload.uuid,dzchunkindex:i.index,dztotalfilesize:i.file.size,dzchunksize:this.options.chunkSize,dztotalchunkcount:i.file.upload.totalChunkCount,dzchunkbyteoffset:i.index*this.options.chunkSize}},accept(e,t){return t()},chunksUploaded:function(e,t){t()},binaryBody:!1,fallback(){let e;this.element.className=`${this.element.className} dz-browser-not-supported`;for(let i of this.element.getElementsByTagName("div"))if(/(^| )dz-message($| )/.test(i.className)){e=i,i.className="dz-message";break}e||(e=y.createElement('<div class="dz-message"><span></span></div>'),this.element.appendChild(e));let t=e.getElementsByTagName("span")[0];return t&&(t.textContent!=null?t.textContent=this.options.dictFallbackMessage:t.innerText!=null&&(t.innerText=this.options.dictFallbackMessage)),this.element.appendChild(this.getFallbackForm())},resize(e,t,i,n){let s={srcX:0,srcY:0,srcWidth:e.width,srcHeight:e.height},r=e.width/e.height;t==null&&i==null?(t=s.srcWidth,i=s.srcHeight):t==null?t=i*r:i==null&&(i=t/r),t=Math.min(t,s.srcWidth),i=Math.min(i,s.srcHeight);let o=t/i;if(s.srcWidth>t||s.srcHeight>i)if(n==="crop")r>o?(s.srcHeight=e.height,s.srcWidth=s.srcHeight*o):(s.srcWidth=e.width,s.srcHeight=s.srcWidth/o);else if(n==="contain")r>o?i=t/r:t=i*r;else throw new Error(`Unknown resizeMethod '${n}'`);return s.srcX=(e.width-s.srcWidth)/2,s.srcY=(e.height-s.srcHeight)/2,s.trgWidth=t,s.trgHeight=i,s},transformFile(e,t){return(this.options.resizeWidth||this.options.resizeHeight)&&e.type.match(/image.*/)?this.resizeImage(e,this.options.resizeWidth,this.options.resizeHeight,this.options.resizeMethod,t):t(e)},previewTemplate:Sh(Xo),drop(e){return this.element.classList.remove("dz-drag-hover")},dragstart(e){},dragend(e){return this.element.classList.remove("dz-drag-hover")},dragenter(e){return this.element.classList.add("dz-drag-hover")},dragover(e){return this.element.classList.add("dz-drag-hover")},dragleave(e){return this.element.classList.remove("dz-drag-hover")},paste(e){},reset(){return this.element.classList.remove("dz-started")},addedfile(e){if(this.element===this.previewsContainer&&this.element.classList.add("dz-started"),this.previewsContainer&&!this.options.disablePreviews){e.previewElement=y.createElement(this.options.previewTemplate.trim()),e.previewTemplate=e.previewElement,this.previewsContainer.appendChild(e.previewElement);for(var t of e.previewElement.querySelectorAll("[data-dz-name]"))t.textContent=e.name;for(t of e.previewElement.querySelectorAll("[data-dz-size]"))t.innerHTML=this.filesize(e.size);this.options.addRemoveLinks&&(e._removeLink=y.createElement(`<a class="dz-remove" href="javascript:undefined;" data-dz-remove>${this.options.dictRemoveFile}</a>`),e.previewElement.appendChild(e._removeLink));let i=n=>(n.preventDefault(),n.stopPropagation(),e.status===y.UPLOADING?y.confirm(this.options.dictCancelUploadConfirmation,()=>this.removeFile(e)):this.options.dictRemoveFileConfirmation?y.confirm(this.options.dictRemoveFileConfirmation,()=>this.removeFile(e)):this.removeFile(e));for(let n of e.previewElement.querySelectorAll("[data-dz-remove]"))n.addEventListener("click",i)}},removedfile(e){return e.previewElement!=null&&e.previewElement.parentNode!=null&&e.previewElement.parentNode.removeChild(e.previewElement),this._updateMaxFilesReachedClass()},thumbnail(e,t){if(e.previewElement){e.previewElement.classList.remove("dz-file-preview");for(let i of e.previewElement.querySelectorAll("[data-dz-thumbnail]"))i.alt=e.name,i.src=t;return setTimeout(()=>e.previewElement.classList.add("dz-image-preview"),1)}},error(e,t){if(e.previewElement){e.previewElement.classList.add("dz-error"),typeof t!="string"&&t.error&&(t=t.error);for(let i of e.previewElement.querySelectorAll("[data-dz-errormessage]"))i.textContent=t}},errormultiple(){},processing(e){if(e.previewElement&&(e.previewElement.classList.add("dz-processing"),e._removeLink))return e._removeLink.innerHTML=this.options.dictCancelUpload},processingmultiple(){},uploadprogress(e,t,i){if(e.previewElement)for(let n of e.previewElement.querySelectorAll("[data-dz-uploadprogress]"))n.nodeName==="PROGRESS"?n.value=t:n.style.width=`${t}%`},totaluploadprogress(){},sending(){},sendingmultiple(){},success(e){if(e.previewElement)return e.previewElement.classList.add("dz-success")},successmultiple(){},canceled(e){return this.emit("error",e,this.options.dictUploadCanceled)},canceledmultiple(){},complete(e){if(e._removeLink&&(e._removeLink.innerHTML=this.options.dictRemoveFile),e.previewElement)return e.previewElement.classList.add("dz-complete")},completemultiple(){},maxfilesexceeded(){},maxfilesreached(){},queuecomplete(){},addedfiles(){}};var Ch=kh;class y extends Us{static initClass(){this.prototype.Emitter=Us,this.prototype.events=["drop","dragstart","dragend","dragenter","dragover","dragleave","addedfile","addedfiles","removedfile","thumbnail","error","errormultiple","processing","processingmultiple","uploadprogress","totaluploadprogress","sending","sendingmultiple","success","successmultiple","canceled","canceledmultiple","complete","completemultiple","reset","maxfilesexceeded","maxfilesreached","queuecomplete"],this.prototype._thumbnailQueue=[],this.prototype._processingThumbnail=!1}getAcceptedFiles(){return this.files.filter(t=>t.accepted).map(t=>t)}getRejectedFiles(){return this.files.filter(t=>!t.accepted).map(t=>t)}getFilesWithStatus(t){return this.files.filter(i=>i.status===t).map(i=>i)}getQueuedFiles(){return this.getFilesWithStatus(y.QUEUED)}getUploadingFiles(){return this.getFilesWithStatus(y.UPLOADING)}getAddedFiles(){return this.getFilesWithStatus(y.ADDED)}getActiveFiles(){return this.files.filter(t=>t.status===y.UPLOADING||t.status===y.QUEUED).map(t=>t)}init(){if(this.element.tagName==="form"&&this.element.setAttribute("enctype","multipart/form-data"),this.element.classList.contains("dropzone")&&!this.element.querySelector(".dz-message")&&this.element.appendChild(y.createElement(`<div class="dz-default dz-message"><button class="dz-button" type="button">${this.options.dictDefaultMessage}</button></div>`)),this.clickableElements.length){let n=()=>{this.hiddenFileInput&&this.hiddenFileInput.parentNode.removeChild(this.hiddenFileInput),this.hiddenFileInput=document.createElement("input"),this.hiddenFileInput.setAttribute("type","file"),(this.options.maxFiles===null||this.options.maxFiles>1)&&this.hiddenFileInput.setAttribute("multiple","multiple"),this.hiddenFileInput.className="dz-hidden-input",this.options.acceptedFiles!==null&&this.hiddenFileInput.setAttribute("accept",this.options.acceptedFiles),this.options.capture!==null&&this.hiddenFileInput.setAttribute("capture",this.options.capture),this.hiddenFileInput.setAttribute("tabindex","-1"),this.hiddenFileInput.style.visibility="hidden",this.hiddenFileInput.style.position="absolute",this.hiddenFileInput.style.top="0",this.hiddenFileInput.style.left="0",this.hiddenFileInput.style.height="0",this.hiddenFileInput.style.width="0",y.getElement(this.options.hiddenInputContainer,"hiddenInputContainer").appendChild(this.hiddenFileInput),this.hiddenFileInput.addEventListener("change",()=>{let{files:s}=this.hiddenFileInput;if(s.length)for(let r of s)this.addFile(r);this.emit("addedfiles",s),n()})};n()}this.URL=window.URL!==null?window.URL:window.webkitURL;for(let n of this.events)this.on(n,this.options[n]);this.on("uploadprogress",()=>this.updateTotalUploadProgress()),this.on("removedfile",()=>this.updateTotalUploadProgress()),this.on("canceled",n=>this.emit("complete",n)),this.on("complete",n=>{if(this.getAddedFiles().length===0&&this.getUploadingFiles().length===0&&this.getQueuedFiles().length===0)return setTimeout(()=>this.emit("queuecomplete"),0)});const t=function(n){if(n.dataTransfer.types){for(var s=0;s<n.dataTransfer.types.length;s++)if(n.dataTransfer.types[s]==="Files")return!0}return!1};let i=function(n){if(t(n))return n.stopPropagation(),n.preventDefault?n.preventDefault():n.returnValue=!1};return this.listeners=[{element:this.element,events:{dragstart:n=>this.emit("dragstart",n),dragenter:n=>(i(n),this.emit("dragenter",n)),dragover:n=>{let s;try{s=n.dataTransfer.effectAllowed}catch{}return n.dataTransfer.dropEffect=s==="move"||s==="linkMove"?"move":"copy",i(n),this.emit("dragover",n)},dragleave:n=>this.emit("dragleave",n),drop:n=>(i(n),this.drop(n)),dragend:n=>this.emit("dragend",n)}}],this.clickableElements.forEach(n=>this.listeners.push({element:n,events:{click:s=>((n!==this.element||s.target===this.element||y.elementInside(s.target,this.element.querySelector(".dz-message")))&&this.hiddenFileInput.click(),!0)}})),this.enable(),this.options.init.call(this)}destroy(){return this.disable(),this.removeAllFiles(!0),this.hiddenFileInput!=null&&this.hiddenFileInput.parentNode&&(this.hiddenFileInput.parentNode.removeChild(this.hiddenFileInput),this.hiddenFileInput=null),delete this.element.dropzone,y.instances.splice(y.instances.indexOf(this),1)}updateTotalUploadProgress(){let t,i=0,n=0;if(this.getActiveFiles().length){for(let r of this.getActiveFiles())i+=r.upload.bytesSent,n+=r.upload.total;t=100*i/n}else t=100;return this.emit("totaluploadprogress",t,n,i)}_getParamName(t){return typeof this.options.paramName=="function"?this.options.paramName(t):`${this.options.paramName}${this.options.uploadMultiple?`[${t}]`:""}`}_renameFile(t){return typeof this.options.renameFile!="function"?t.name:this.options.renameFile(t)}getFallbackForm(){let t,i;if(t=this.getExistingFallback())return t;let n='<div class="dz-fallback">';this.options.dictFallbackText&&(n+=`<p>${this.options.dictFallbackText}</p>`),n+=`<input type="file" name="${this._getParamName(0)}" ${this.options.uploadMultiple?'multiple="multiple"':void 0} /><input type="submit" value="Upload!"></div>`;let s=y.createElement(n);return this.element.tagName!=="FORM"?(i=y.createElement(`<form action="${this.options.url}" enctype="multipart/form-data" method="${this.options.method}"></form>`),i.appendChild(s)):(this.element.setAttribute("enctype","multipart/form-data"),this.element.setAttribute("method",this.options.method)),i??s}getExistingFallback(){let t=function(n){for(let s of n)if(/(^| )fallback($| )/.test(s.className))return s};for(let n of["div","form"]){var i;if(i=t(this.element.getElementsByTagName(n)))return i}}setupEventListeners(){return this.listeners.map(t=>(()=>{let i=[];for(let n in t.events){let s=t.events[n];i.push(t.element.addEventListener(n,s,!1))}return i})())}removeEventListeners(){return this.listeners.map(t=>(()=>{let i=[];for(let n in t.events){let s=t.events[n];i.push(t.element.removeEventListener(n,s,!1))}return i})())}disable(){return this.clickableElements.forEach(t=>t.classList.remove("dz-clickable")),this.removeEventListeners(),this.disabled=!0,this.files.map(t=>this.cancelUpload(t))}enable(){return delete this.disabled,this.clickableElements.forEach(t=>t.classList.add("dz-clickable")),this.setupEventListeners()}filesize(t){let i=0,n="b";if(t>0){let s=["tb","gb","mb","kb","b"];for(let r=0;r<s.length;r++){let o=s[r],a=Math.pow(this.options.filesizeBase,4-r)/10;if(t>=a){i=t/Math.pow(this.options.filesizeBase,4-r),n=o;break}}i=Math.round(10*i)/10}return`<strong>${i}</strong> ${this.options.dictFileSizeUnits[n]}`}_updateMaxFilesReachedClass(){return this.options.maxFiles!=null&&this.getAcceptedFiles().length>=this.options.maxFiles?(this.getAcceptedFiles().length===this.options.maxFiles&&this.emit("maxfilesreached",this.files),this.element.classList.add("dz-max-files-reached")):this.element.classList.remove("dz-max-files-reached")}drop(t){if(!t.dataTransfer)return;this.emit("drop",t);let i=[];for(let n=0;n<t.dataTransfer.files.length;n++)i[n]=t.dataTransfer.files[n];if(i.length){let{items:n}=t.dataTransfer;n&&n.length&&n[0].webkitGetAsEntry!=null?this._addFilesFromItems(n):this.handleFiles(i)}this.emit("addedfiles",i)}paste(t){if(Oh(t!=null?t.clipboardData:void 0,n=>n.items)==null)return;this.emit("paste",t);let{items:i}=t.clipboardData;if(i.length)return this._addFilesFromItems(i)}handleFiles(t){for(let i of t)this.addFile(i)}_addFilesFromItems(t){return(()=>{let i=[];for(let s of t){var n;s.webkitGetAsEntry!=null&&(n=s.webkitGetAsEntry())?n.isFile?i.push(this.addFile(s.getAsFile())):n.isDirectory?i.push(this._addFilesFromDirectory(n,n.name)):i.push(void 0):s.getAsFile!=null&&(s.kind==null||s.kind==="file")?i.push(this.addFile(s.getAsFile())):i.push(void 0)}return i})()}_addFilesFromDirectory(t,i){let n=t.createReader(),s=o=>Lh(console,"log",a=>a.log(o));var r=()=>n.readEntries(o=>{if(o.length>0){for(let a of o)a.isFile?a.file(l=>{if(!(this.options.ignoreHiddenFiles&&l.name.substring(0,1)==="."))return l.fullPath=`${i}/${l.name}`,this.addFile(l)}):a.isDirectory&&this._addFilesFromDirectory(a,`${i}/${a.name}`);r()}return null},s);return r()}accept(t,i){this.options.maxFilesize&&t.size>this.options.maxFilesize*1048576?i(this.options.dictFileTooBig.replace("{{filesize}}",Math.round(t.size/1024/10.24)/100).replace("{{maxFilesize}}",this.options.maxFilesize)):y.isValidFile(t,this.options.acceptedFiles)?this.options.maxFiles!=null&&this.getAcceptedFiles().length>=this.options.maxFiles?(i(this.options.dictMaxFilesExceeded.replace("{{maxFiles}}",this.options.maxFiles)),this.emit("maxfilesexceeded",t)):this.options.accept.call(this,t,i):i(this.options.dictInvalidFileType)}addFile(t){t.upload={uuid:y.uuidv4(),progress:0,total:t.size,bytesSent:0,filename:this._renameFile(t)},this.files.push(t),t.status=y.ADDED,this.emit("addedfile",t),this._enqueueThumbnail(t),this.accept(t,i=>{i?(t.accepted=!1,this._errorProcessing([t],i)):(t.accepted=!0,this.options.autoQueue&&this.enqueueFile(t)),this._updateMaxFilesReachedClass()})}enqueueFiles(t){for(let i of t)this.enqueueFile(i);return null}enqueueFile(t){if(t.status===y.ADDED&&t.accepted===!0){if(t.status=y.QUEUED,this.options.autoProcessQueue)return setTimeout(()=>this.processQueue(),0)}else throw new Error("This file can't be queued because it has already been processed or was rejected.")}_enqueueThumbnail(t){if(this.options.createImageThumbnails&&t.type.match(/image.*/)&&t.size<=this.options.maxThumbnailFilesize*1048576)return this._thumbnailQueue.push(t),setTimeout(()=>this._processThumbnailQueue(),0)}_processThumbnailQueue(){if(this._processingThumbnail||this._thumbnailQueue.length===0)return;this._processingThumbnail=!0;let t=this._thumbnailQueue.shift();return this.createThumbnail(t,this.options.thumbnailWidth,this.options.thumbnailHeight,this.options.thumbnailMethod,!0,i=>(this.emit("thumbnail",t,i),this._processingThumbnail=!1,this._processThumbnailQueue()))}removeFile(t){if(t.status===y.UPLOADING&&this.cancelUpload(t),this.files=Ah(this.files,t),this.emit("removedfile",t),this.files.length===0)return this.emit("reset")}removeAllFiles(t){t==null&&(t=!1);for(let i of this.files.slice())(i.status!==y.UPLOADING||t)&&this.removeFile(i);return null}resizeImage(t,i,n,s,r){return this.createThumbnail(t,i,n,s,!0,(o,a)=>{if(a==null)return r(t);{let{resizeMimeType:l}=this.options;l==null&&(l=t.type);let u=a.toDataURL(l,this.options.resizeQuality);return(l==="image/jpeg"||l==="image/jpg")&&(u=Jo.restore(t.dataURL,u)),r(y.dataURItoBlob(u))}})}createThumbnail(t,i,n,s,r,o){let a=new FileReader;a.onload=()=>{if(t.dataURL=a.result,t.type==="image/svg+xml"){o!=null&&o(a.result);return}this.createThumbnailFromUrl(t,i,n,s,r,o)},a.readAsDataURL(t)}displayExistingFile(t,i,n,s,r=!0){if(this.emit("addedfile",t),this.emit("complete",t),!r)this.emit("thumbnail",t,i),n&&n();else{let o=a=>{this.emit("thumbnail",t,a),n&&n()};t.dataURL=i,this.createThumbnailFromUrl(t,this.options.thumbnailWidth,this.options.thumbnailHeight,this.options.thumbnailMethod,this.options.fixOrientation,o,s)}}createThumbnailFromUrl(t,i,n,s,r,o,a){let l=document.createElement("img");return a&&(l.crossOrigin=a),r=getComputedStyle(document.body).imageOrientation=="from-image"?!1:r,l.onload=()=>{let u=c=>c(1);return typeof EXIF<"u"&&EXIF!==null&&r&&(u=c=>EXIF.getData(l,function(){return c(EXIF.getTag(this,"Orientation"))})),u(c=>{t.width=l.width,t.height=l.height;let d=this.options.resize.call(this,t,i,n,s),f=document.createElement("canvas"),m=f.getContext("2d");switch(f.width=d.trgWidth,f.height=d.trgHeight,c>4&&(f.width=d.trgHeight,f.height=d.trgWidth),c){case 2:m.translate(f.width,0),m.scale(-1,1);break;case 3:m.translate(f.width,f.height),m.rotate(Math.PI);break;case 4:m.translate(0,f.height),m.scale(1,-1);break;case 5:m.rotate(.5*Math.PI),m.scale(1,-1);break;case 6:m.rotate(.5*Math.PI),m.translate(0,-f.width);break;case 7:m.rotate(.5*Math.PI),m.translate(f.height,-f.width),m.scale(-1,1);break;case 8:m.rotate(-.5*Math.PI),m.translate(-f.height,0);break}Fh(m,l,d.srcX!=null?d.srcX:0,d.srcY!=null?d.srcY:0,d.srcWidth,d.srcHeight,d.trgX!=null?d.trgX:0,d.trgY!=null?d.trgY:0,d.trgWidth,d.trgHeight);let g=f.toDataURL("image/png");if(o!=null)return o(g,f)})},o!=null&&(l.onerror=o),l.src=t.dataURL}processQueue(){let{parallelUploads:t}=this.options,i=this.getUploadingFiles().length,n=i;if(i>=t)return;let s=this.getQueuedFiles();if(s.length>0){if(this.options.uploadMultiple)return this.processFiles(s.slice(0,t-i));for(;n<t;){if(!s.length)return;this.processFile(s.shift()),n++}}}processFile(t){return this.processFiles([t])}processFiles(t){for(let i of t)i.processing=!0,i.status=y.UPLOADING,this.emit("processing",i);return this.options.uploadMultiple&&this.emit("processingmultiple",t),this.uploadFiles(t)}_getFilesWithXhr(t){return this.files.filter(i=>i.xhr===t).map(i=>i)}cancelUpload(t){if(t.status===y.UPLOADING){let i=this._getFilesWithXhr(t.xhr);for(let n of i)n.status=y.CANCELED;typeof t.xhr<"u"&&t.xhr.abort();for(let n of i)this.emit("canceled",n);this.options.uploadMultiple&&this.emit("canceledmultiple",i)}else(t.status===y.ADDED||t.status===y.QUEUED)&&(t.status=y.CANCELED,this.emit("canceled",t),this.options.uploadMultiple&&this.emit("canceledmultiple",[t]));if(this.options.autoProcessQueue)return this.processQueue()}resolveOption(t,...i){return typeof t=="function"?t.apply(this,i):t}uploadFile(t){return this.uploadFiles([t])}uploadFiles(t){this._transformFiles(t,i=>{if(this.options.chunking){let n=i[0];t[0].upload.chunked=this.options.chunking&&(this.options.forceChunking||n.size>this.options.chunkSize),t[0].upload.totalChunkCount=Math.ceil(n.size/this.options.chunkSize)}if(t[0].upload.chunked){let n=t[0],s=i[0];n.upload.chunks=[];let r=()=>{let o=0;for(;n.upload.chunks[o]!==void 0;)o++;if(o>=n.upload.totalChunkCount)return;let a=o*this.options.chunkSize,l=Math.min(a+this.options.chunkSize,s.size),u={name:this._getParamName(0),data:s.webkitSlice?s.webkitSlice(a,l):s.slice(a,l),filename:n.upload.filename,chunkIndex:o};n.upload.chunks[o]={file:n,index:o,dataBlock:u,status:y.UPLOADING,progress:0,retries:0},this._uploadData(t,[u])};if(n.upload.finishedChunkUpload=(o,a)=>{let l=!0;o.status=y.SUCCESS,o.dataBlock=null,o.response=o.xhr.responseText,o.responseHeaders=o.xhr.getAllResponseHeaders(),o.xhr=null;for(let u=0;u<n.upload.totalChunkCount;u++){if(n.upload.chunks[u]===void 0)return r();n.upload.chunks[u].status!==y.SUCCESS&&(l=!1)}l&&this.options.chunksUploaded(n,()=>{this._finished(t,a,null)})},this.options.parallelChunkUploads)for(let o=0;o<n.upload.totalChunkCount;o++)r();else r()}else{let n=[];for(let s=0;s<t.length;s++)n[s]={name:this._getParamName(s),data:i[s],filename:t[s].upload.filename};this._uploadData(t,n)}})}_getChunk(t,i){for(let n=0;n<t.upload.totalChunkCount;n++)if(t.upload.chunks[n]!==void 0&&t.upload.chunks[n].xhr===i)return t.upload.chunks[n]}_uploadData(t,i){let n=new XMLHttpRequest;for(let u of t)u.xhr=n;t[0].upload.chunked&&(t[0].upload.chunks[i[0].chunkIndex].xhr=n);let s=this.resolveOption(this.options.method,t,i),r=this.resolveOption(this.options.url,t,i);n.open(s,r,!0),this.resolveOption(this.options.timeout,t)&&(n.timeout=this.resolveOption(this.options.timeout,t)),n.withCredentials=!!this.options.withCredentials,n.onload=u=>{this._finishedUploading(t,n,u)},n.ontimeout=()=>{this._handleUploadError(t,n,`Request timedout after ${this.options.timeout/1e3} seconds`)},n.onerror=()=>{this._handleUploadError(t,n)};let a=n.upload!=null?n.upload:n;a.onprogress=u=>this._updateFilesUploadProgress(t,n,u);let l=this.options.defaultHeaders?{Accept:"application/json","Cache-Control":"no-cache","X-Requested-With":"XMLHttpRequest"}:{};this.options.binaryBody&&(l["Content-Type"]=t[0].type),this.options.headers&&Ds(l,this.options.headers);for(let u in l){let c=l[u];c&&n.setRequestHeader(u,c)}if(this.options.binaryBody){for(let u of t)this.emit("sending",u,n);this.options.uploadMultiple&&this.emit("sendingmultiple",t,n),this.submitRequest(n,null,t)}else{let u=new FormData;if(this.options.params){let c=this.options.params;typeof c=="function"&&(c=c.call(this,t,n,t[0].upload.chunked?this._getChunk(t[0],n):null));for(let d in c){let f=c[d];if(Array.isArray(f))for(let m=0;m<f.length;m++)u.append(d,f[m]);else u.append(d,f)}}for(let c of t)this.emit("sending",c,n,u);this.options.uploadMultiple&&this.emit("sendingmultiple",t,n,u),this._addFormElementData(u);for(let c=0;c<i.length;c++){let d=i[c];u.append(d.name,d.data,d.filename)}this.submitRequest(n,u,t)}}_transformFiles(t,i){let n=[],s=0;for(let r=0;r<t.length;r++)this.options.transformFile.call(this,t[r],o=>{n[r]=o,++s===t.length&&i(n)})}_addFormElementData(t){if(this.element.tagName==="FORM")for(let i of this.element.querySelectorAll("input, textarea, select, button")){let n=i.getAttribute("name"),s=i.getAttribute("type");if(s&&(s=s.toLowerCase()),!(typeof n>"u"||n===null))if(i.tagName==="SELECT"&&i.hasAttribute("multiple"))for(let r of i.options)r.selected&&t.append(n,r.value);else(!s||s!=="checkbox"&&s!=="radio"||i.checked)&&t.append(n,i.value)}}_updateFilesUploadProgress(t,i,n){if(t[0].upload.chunked){let s=t[0],r=this._getChunk(s,i);n?(r.progress=100*n.loaded/n.total,r.total=n.total,r.bytesSent=n.loaded):(r.progress=100,r.bytesSent=r.total),s.upload.progress=0,s.upload.total=0,s.upload.bytesSent=0;for(let o=0;o<s.upload.totalChunkCount;o++)s.upload.chunks[o]&&typeof s.upload.chunks[o].progress<"u"&&(s.upload.progress+=s.upload.chunks[o].progress,s.upload.total+=s.upload.chunks[o].total,s.upload.bytesSent+=s.upload.chunks[o].bytesSent);s.upload.progress=s.upload.progress/s.upload.totalChunkCount,this.emit("uploadprogress",s,s.upload.progress,s.upload.bytesSent)}else for(let s of t)s.upload.total&&s.upload.bytesSent&&s.upload.bytesSent==s.upload.total||(n?(s.upload.progress=100*n.loaded/n.total,s.upload.total=n.total,s.upload.bytesSent=n.loaded):(s.upload.progress=100,s.upload.bytesSent=s.upload.total),this.emit("uploadprogress",s,s.upload.progress,s.upload.bytesSent))}_finishedUploading(t,i,n){let s;if(t[0].status!==y.CANCELED&&i.readyState===4){if(i.responseType!=="arraybuffer"&&i.responseType!=="blob"&&(s=i.responseText,i.getResponseHeader("content-type")&&~i.getResponseHeader("content-type").indexOf("application/json")))try{s=JSON.parse(s)}catch(r){n=r,s="Invalid JSON response from server."}this._updateFilesUploadProgress(t,i),200<=i.status&&i.status<300?t[0].upload.chunked?t[0].upload.finishedChunkUpload(this._getChunk(t[0],i),s):this._finished(t,s,n):this._handleUploadError(t,i,s)}}_handleUploadError(t,i,n){if(t[0].status!==y.CANCELED){if(t[0].upload.chunked&&this.options.retryChunks){let s=this._getChunk(t[0],i);if(s.retries++<this.options.retryChunksLimit){this._uploadData(t,[s.dataBlock]);return}else console.warn("Retried this chunk too often. Giving up.")}this._errorProcessing(t,n||this.options.dictResponseError.replace("{{statusCode}}",i.status),i)}}submitRequest(t,i,n){if(t.readyState!=1){console.warn("Cannot send this request because the XMLHttpRequest.readyState is not OPENED.");return}if(this.options.binaryBody)if(n[0].upload.chunked){const s=this._getChunk(n[0],t);t.send(s.dataBlock.data)}else t.send(n[0]);else t.send(i)}_finished(t,i,n){for(let s of t)s.status=y.SUCCESS,this.emit("success",s,i,n),this.emit("complete",s);if(this.options.uploadMultiple&&(this.emit("successmultiple",t,i,n),this.emit("completemultiple",t)),this.options.autoProcessQueue)return this.processQueue()}_errorProcessing(t,i,n){for(let s of t)s.status=y.ERROR,this.emit("error",s,i,n),this.emit("complete",s);if(this.options.uploadMultiple&&(this.emit("errormultiple",t,i,n),this.emit("completemultiple",t)),this.options.autoProcessQueue)return this.processQueue()}static uuidv4(){return"xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g,function(t){let i=Math.random()*16|0;return(t==="x"?i:i&3|8).toString(16)})}constructor(t,i){super();let n,s;if(this.element=t,this.clickableElements=[],this.listeners=[],this.files=[],typeof this.element=="string"&&(this.element=document.querySelector(this.element)),!this.element||this.element.nodeType==null)throw new Error("Invalid dropzone element.");if(this.element.dropzone)throw new Error("Dropzone already attached.");y.instances.push(this),this.element.dropzone=this;let r=(s=y.optionsForElement(this.element))!=null?s:{};if(this.options=Ds(!0,{},Ch,r,i??{}),this.options.previewTemplate=this.options.previewTemplate.replace(/\n*/g,""),this.options.forceFallback||!y.isBrowserSupported())return this.options.fallback.call(this);if(this.options.url==null&&(this.options.url=this.element.getAttribute("action")),!this.options.url)throw new Error("No URL provided.");if(this.options.acceptedFiles&&this.options.acceptedMimeTypes)throw new Error("You can't provide both 'acceptedFiles' and 'acceptedMimeTypes'. 'acceptedMimeTypes' is deprecated.");if(this.options.uploadMultiple&&this.options.chunking)throw new Error("You cannot set both: uploadMultiple and chunking.");if(this.options.binaryBody&&this.options.uploadMultiple)throw new Error("You cannot set both: binaryBody and uploadMultiple.");this.options.acceptedMimeTypes&&(this.options.acceptedFiles=this.options.acceptedMimeTypes,delete this.options.acceptedMimeTypes),this.options.renameFilename!=null&&(this.options.renameFile=o=>this.options.renameFilename.call(this,o.name,o)),typeof this.options.method=="string"&&(this.options.method=this.options.method.toUpperCase()),(n=this.getExistingFallback())&&n.parentNode&&n.parentNode.removeChild(n),this.options.previewsContainer!==!1&&(this.options.previewsContainer?this.previewsContainer=y.getElement(this.options.previewsContainer,"previewsContainer"):this.previewsContainer=this.element),this.options.clickable&&(this.options.clickable===!0?this.clickableElements=[this.element]:this.clickableElements=y.getElements(this.options.clickable,"clickable")),this.init()}}y.initClass();y.options={};y.optionsForElement=function(e){if(e.getAttribute("id"))return y.options[Th(e.getAttribute("id"))]};y.instances=[];y.forElement=function(e){if(typeof e=="string"&&(e=document.querySelector(e)),(e!=null?e.dropzone:void 0)==null)throw new Error("No Dropzone found for given element. This is probably because you're trying to access it before Dropzone had the time to initialize. Use the `init` option to setup any additional observers on your Dropzone.");return e.dropzone};y.discover=function(){let e;if(document.querySelectorAll)e=document.querySelectorAll(".dropzone");else{e=[];let t=i=>(()=>{let n=[];for(let s of i)/(^| )dropzone($| )/.test(s.className)?n.push(e.push(s)):n.push(void 0);return n})();t(document.getElementsByTagName("div")),t(document.getElementsByTagName("form"))}return(()=>{let t=[];for(let i of e)y.optionsForElement(i)!==!1?t.push(new y(i)):t.push(void 0);return t})()};y.blockedBrowsers=[/opera.*(Macintosh|Windows Phone).*version\/12/i];y.isBrowserSupported=function(){let e=!0;if(window.File&&window.FileReader&&window.FileList&&window.Blob&&window.FormData&&document.querySelector)if(!("classList"in document.createElement("a")))e=!1;else{y.blacklistedBrowsers!==void 0&&(y.blockedBrowsers=y.blacklistedBrowsers);for(let t of y.blockedBrowsers)if(t.test(navigator.userAgent)){e=!1;continue}}else e=!1;return e};y.dataURItoBlob=function(e){let t=atob(e.split(",")[1]),i=e.split(",")[0].split(":")[1].split(";")[0],n=new ArrayBuffer(t.length),s=new Uint8Array(n);for(let r=0,o=t.length,a=0<=o;a?r<=o:r>=o;a?r++:r--)s[r]=t.charCodeAt(r);return new Blob([n],{type:i})};const Ah=(e,t)=>e.filter(i=>i!==t).map(i=>i),Th=e=>e.replace(/[\-_](\w)/g,t=>t.charAt(1).toUpperCase());y.createElement=function(e){let t=document.createElement("div");return t.innerHTML=e,t.childNodes[0]};y.elementInside=function(e,t){if(e===t)return!0;for(;e=e.parentNode;)if(e===t)return!0;return!1};y.getElement=function(e,t){let i;if(typeof e=="string"?i=document.querySelector(e):e.nodeType!=null&&(i=e),i==null)throw new Error(`Invalid \`${t}\` option provided. Please provide a CSS selector or a plain HTML element.`);return i};y.getElements=function(e,t){let i,n;if(e instanceof Array){n=[];try{for(i of e)n.push(this.getElement(i,t))}catch{n=null}}else if(typeof e=="string"){n=[];for(i of document.querySelectorAll(e))n.push(i)}else e.nodeType!=null&&(n=[e]);if(n==null||!n.length)throw new Error(`Invalid \`${t}\` option provided. Please provide a CSS selector, a plain HTML element or a list of those.`);return n};y.confirm=function(e,t,i){if(window.confirm(e))return t();if(i!=null)return i()};y.isValidFile=function(e,t){if(!t)return!0;t=t.split(",");let i=e.type,n=i.replace(/\/.*$/,"");for(let s of t)if(s=s.trim(),s.charAt(0)==="."){if(e.name.toLowerCase().indexOf(s.toLowerCase(),e.name.length-s.length)!==-1)return!0}else if(/\/\*$/.test(s)){if(n===s.replace(/\/.*$/,""))return!0}else if(i===s)return!0;return!1};typeof jQuery<"u"&&jQuery!==null&&(jQuery.fn.dropzone=function(e){return this.each(function(){return new y(this,e)})});y.ADDED="added";y.QUEUED="queued";y.ACCEPTED=y.QUEUED;y.UPLOADING="uploading";y.PROCESSING=y.UPLOADING;y.CANCELED="canceled";y.ERROR="error";y.SUCCESS="success";let Rh=function(e){e.naturalWidth;let t=e.naturalHeight,i=document.createElement("canvas");i.width=1,i.height=t;let n=i.getContext("2d");n.drawImage(e,0,0);let{data:s}=n.getImageData(1,0,1,t),r=0,o=t,a=t;for(;a>r;)s[(a-1)*4+3]===0?o=a:r=a,a=o+r>>1;let l=a/t;return l===0?1:l};var Fh=function(e,t,i,n,s,r,o,a,l,u){let c=Rh(t);return e.drawImage(t,i,n,s,r,o,a,l,u/c)};class Jo{static initClass(){this.KEY_STR="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/="}static encode64(t){let i="",n,s,r="",o,a,l,u="",c=0;for(;n=t[c++],s=t[c++],r=t[c++],o=n>>2,a=(n&3)<<4|s>>4,l=(s&15)<<2|r>>6,u=r&63,isNaN(s)?l=u=64:isNaN(r)&&(u=64),i=i+this.KEY_STR.charAt(o)+this.KEY_STR.charAt(a)+this.KEY_STR.charAt(l)+this.KEY_STR.charAt(u),n=s=r="",o=a=l=u="",c<t.length;);return i}static restore(t,i){if(!t.match("data:image/jpeg;base64,"))return i;let n=this.decode64(t.replace("data:image/jpeg;base64,","")),s=this.slice2Segments(n),r=this.exifManipulation(i,s);return`data:image/jpeg;base64,${this.encode64(r)}`}static exifManipulation(t,i){let n=this.getExifArray(i),s=this.insertExif(t,n);return new Uint8Array(s)}static getExifArray(t){let i,n=0;for(;n<t.length;){if(i=t[n],i[0]===255&i[1]===225)return i;n++}return[]}static insertExif(t,i){let n=t.replace("data:image/jpeg;base64,",""),s=this.decode64(n),r=s.indexOf(255,3),o=s.slice(0,r),a=s.slice(r),l=o;return l=l.concat(i),l=l.concat(a),l}static slice2Segments(t){let i=0,n=[];for(;;){var s;if(t[i]===255&t[i+1]===218)break;if(t[i]===255&t[i+1]===216)i+=2;else{s=t[i+2]*256+t[i+3];let r=i+s+2,o=t.slice(i,r);n.push(o),i=r}if(i>t.length)break}return n}static decode64(t){let i,n,s="",r,o,a,l="",u=0,c=[];for(/[^A-Za-z0-9\+\/\=]/g.exec(t)&&console.warn(`There were invalid base64 characters in the input text.
Valid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='
Expect errors in decoding.`),t=t.replace(/[^A-Za-z0-9\+\/\=]/g,"");r=this.KEY_STR.indexOf(t.charAt(u++)),o=this.KEY_STR.indexOf(t.charAt(u++)),a=this.KEY_STR.indexOf(t.charAt(u++)),l=this.KEY_STR.indexOf(t.charAt(u++)),i=r<<2|o>>4,n=(o&15)<<4|a>>2,s=(a&3)<<6|l,c.push(i),a!==64&&c.push(n),l!==64&&c.push(s),i=n=s="",r=o=a=l="",u<t.length;);return c}}Jo.initClass();function Oh(e,t){return typeof e<"u"&&e!==null?t(e):void 0}function Lh(e,t,i){if(typeof e<"u"&&e!==null&&typeof e[t]=="function")return i(e,t)}window.Alpine=Hn;Hn.plugin(xh);Hn.start();y.autoDiscover=!1;const Hs=document.getElementById("file-upload-dropzone"),$i=document.getElementById("messageForm"),Bi=document.getElementById("message"),Pe=document.getElementById("file_upload_ids");if(Hs&&$i&&Bi&&Pe){const e=document.querySelector('meta[name="csrf-token"]').getAttribute("content"),t=Hs.dataset.uploadUrl;if(!t)console.error("Dropzone element is missing the data-upload-url attribute!");else{const i=new y("#file-upload-dropzone",{url:t,paramName:"file",maxFilesize:5e3,chunking:!0,forceChunking:!0,chunkSize:5242880,retryChunks:!0,retryChunksLimit:3,parallelChunkUploads:!1,addRemoveLinks:!0,autoProcessQueue:!1,headers:{"X-CSRF-TOKEN":e},params:function(n,s,r){const o={};r&&(o.dzuuid=r.file.upload.uuid,o.dzchunkindex=r.index,o.dztotalfilesize=r.file.size,o.dzchunksize=this.options.chunkSize,o.dztotalchunkcount=r.file.upload.totalChunkCount,o.dzchunkbyteoffset=r.index*this.options.chunkSize);const a=document.getElementById("company_user_id");return a&&a.value&&(o.company_user_id=a.value),o},uploadprogress:function(n,s,r){},success:function(n,s){if(console.log(`Success callback for ${n.name}:`,s),s&&s.file_upload_id){if(console.log(`Final FileUpload ID for ${n.name}: ${s.file_upload_id}`),!n.finalIdReceived){n.finalIdReceived=!0,n.file_upload_id=s.file_upload_id;let r=Pe.value?JSON.parse(Pe.value):[];r.includes(s.file_upload_id)||(r.push(s.file_upload_id),Pe.value=JSON.stringify(r),console.log("Updated file_upload_ids:",Pe.value))}}else console.log(`Received intermediate chunk success for ${n.name}`)},error:function(n,s,r){console.error("Error uploading file chunk:",n.name,s,r);const o=document.getElementById("upload-errors");if(o){const a=typeof s=="object"?s.error||JSON.stringify(s):s;o.innerHTML+=`<p class="text-red-500">Error uploading ${n.name}: ${a}</p>`,o.classList.remove("hidden")}},complete:function(n){console.log("File processing complete (success or error): ",n.name),i.processQueue()}});$i.addEventListener("submit",function(n){n.preventDefault();const s=this.querySelector('button[type="submit"]'),r=i.getQueuedFiles(),o=i.getFilesWithStatus(y.UPLOADING),a=i.getFilesWithStatus(y.SUCCESS).length+i.getFilesWithStatus(y.ERROR).length;console.log(`Submit triggered. Queued: ${r.length}, InProgress: ${o.length}, Done: ${a}`),r.length>0?(console.log("Starting file uploads for queue..."),s.disabled=!0,s.textContent="Uploading Files...",i.processQueue()):i.getFilesWithStatus(y.SUCCESS).length>0?(console.log("Files already uploaded, attempting to associate message via queuecomplete."),console.log("Submit triggered, but files seem already uploaded."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-error"}))):(console.log("Submit triggered, but no files added."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"no-files-error"})))}),i.on("queuecomplete",function(){const n=i.getFilesWithStatus(y.SUCCESS).length+i.getFilesWithStatus(y.ERROR).length,s=i.files.length;console.log(`--- Queue Complete Fired --- Processed: ${n}, Total Added: ${s}`);const r=$i.querySelector('button[type="submit"]'),o=Bi.value,l=i.getFilesWithStatus(y.SUCCESS).map(u=>u.file_upload_id).filter(u=>u);if(console.log("Queue complete. Message:",o),console.log("Queue complete. Successful file IDs:",l),o&&l.length>0){console.log("Attempting to associate message..."),r.textContent="Associating Message...";const u=window.employeeUploadConfig?window.employeeUploadConfig.associateMessageUrl:"/client/uploads/associate-message";fetch(u,{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":e},body:JSON.stringify({message:o,file_upload_ids:l})}).then(c=>{if(!c.ok)throw c.text().then(d=>{console.error("Error response from associate-message:",c.status,d)}),new Error(`HTTP error! status: ${c.status}`);return c.json()}).then(c=>{console.log("Message associated successfully:",c),Bi.value="",Pe.value="[]",i.removeAllFiles(!0),window.dispatchEvent(new CustomEvent("open-modal",{detail:"association-success"}))}).catch(c=>{console.error("Error associating message:",c),window.dispatchEvent(new CustomEvent("open-modal",{detail:"association-error"}))}).finally(()=>{r.disabled=!1,r.textContent="Upload and Send Message"})}else if(l.length>0&&!o){console.log("Batch upload complete without message. Successful IDs:",l),console.log("Calling /api/uploads/batch-complete..."),r.textContent="Finalizing Upload...",r.disabled=!0;const u=window.employeeUploadConfig?window.employeeUploadConfig.batchCompleteUrl:"/client/uploads/batch-complete";fetch(u,{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":e},body:JSON.stringify({file_upload_ids:l})}).then(c=>{if(!c.ok)throw console.error("Error response from batch-complete endpoint:",c.status),c.text().then(d=>console.error("Batch Complete Error Body:",d)),new Error(`HTTP error! status: ${c.status}`);return c.json()}).then(c=>{console.log("Backend acknowledged batch completion:",c),console.log("Dispatching upload-success modal..."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-success"})),console.log("Attempting to clear Dropzone UI..."),i.removeAllFiles(!0),console.log("Dropzone UI should be cleared now."),console.log("Attempting to clear file IDs input..."),Pe.value="[]",console.log("File IDs input cleared.")}).catch(c=>{console.error("Error calling batch-complete endpoint:",c),window.dispatchEvent(new CustomEvent("open-modal",{detail:"association-error"}))}).finally(()=>{r.disabled=!1,r.textContent="Upload and Send Message",i.getRejectedFiles().length>0&&(console.log("Found rejected files, dispatching upload-error modal as well."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-error"})))})}else console.log("Queue finished, but no successful uploads or handling other cases."),l.length===0&&(r.disabled=!1,r.textContent="Upload and Send Message",i.getRejectedFiles().length>0&&window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-error"})))})}}const Mh=window.location.hostname;document.querySelectorAll('a[href^="http"]:not([href*="'+Mh+'"]):not([href^="#"]):not(.button-link)').forEach(e=>{e.querySelector(".external-link-icon")||(e.innerHTML+='<svg class="external-link-icon" xmlns="http://www.w3.org/2000/svg" baseProfile="tiny" version="1.2" viewBox="0 0 79 79"><path d="M64,39.8v34.7c0,2.5-2,4.5-4.5,4.5H4.5c-2.5,0-4.5-2-4.5-4.5V19.5c0-2.5,2-4.5,4.5-4.5h35.6c2.5,0,4.5,2,4.5,4.5s-.5,2.4-1.4,3.3c-.8.8-1.9,1.3-3.1,1.3H9v45.9h45.9v-30.2c0-1.3.5-2.4,1.4-3.3.8-.8,1.9-1.3,3.1-1.3,2.5,0,4.5,2,4.5,4.5h0Z"/><path d="M74.5,0h-28.7c-2.2,0-4.2,1.5-4.6,3.6s1.6,5.5,4.4,5.5h17.9l-31.5,31.6c-1.8,1.8-1.8,4.7,0,6.5h0c1.7,1.8,4.6,1.8,6.3,0l31.6-31.6v17.7c0,2.2,1.5,4.2,3.6,4.6s5.5-1.6,5.5-4.4V4.7c0-2.5-2-4.5-4.5-4.5h0v-.2Z"/></svg>')})});export default Ph();
