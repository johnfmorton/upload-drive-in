function ro(t,e){return function(){return t.apply(e,arguments)}}const{toString:xl}=Object.prototype,{getPrototypeOf:er}=Object,zi=(t=>e=>{const i=xl.call(e);return t[i]||(t[i]=i.slice(8,-1).toLowerCase())})(Object.create(null)),dt=t=>(t=t.toLowerCase(),e=>zi(e)===t),Pi=t=>e=>typeof e===t,{isArray:ve}=Array,We=Pi("undefined");function El(t){return t!==null&&!We(t)&&t.constructor!==null&&!We(t.constructor)&&Z(t.constructor.isBuffer)&&t.constructor.isBuffer(t)}const so=dt("ArrayBuffer");function Sl(t){let e;return typeof ArrayBuffer<"u"&&ArrayBuffer.isView?e=ArrayBuffer.isView(t):e=t&&t.buffer&&so(t.buffer),e}const Cl=Pi("string"),Z=Pi("function"),oo=Pi("number"),Ii=t=>t!==null&&typeof t=="object",kl=t=>t===!0||t===!1,bi=t=>{if(zi(t)!=="object")return!1;const e=er(t);return(e===null||e===Object.prototype||Object.getPrototypeOf(e)===null)&&!(Symbol.toStringTag in t)&&!(Symbol.iterator in t)},Al=dt("Date"),$l=dt("File"),Rl=dt("Blob"),Tl=dt("FileList"),Fl=t=>Ii(t)&&Z(t.pipe),Ol=t=>{let e;return t&&(typeof FormData=="function"&&t instanceof FormData||Z(t.append)&&((e=zi(t))==="formdata"||e==="object"&&Z(t.toString)&&t.toString()==="[object FormData]"))},Ml=dt("URLSearchParams"),[Ll,zl,Pl,Il]=["ReadableStream","Request","Response","Headers"].map(dt),Bl=t=>t.trim?t.trim():t.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,"");function Qe(t,e,{allOwnKeys:i=!1}={}){if(t===null||typeof t>"u")return;let n,r;if(typeof t!="object"&&(t=[t]),ve(t))for(n=0,r=t.length;n<r;n++)e.call(null,t[n],n,t);else{const s=i?Object.getOwnPropertyNames(t):Object.keys(t),o=s.length;let a;for(n=0;n<o;n++)a=s[n],e.call(null,t[a],a,t)}}function ao(t,e){e=e.toLowerCase();const i=Object.keys(t);let n=i.length,r;for(;n-- >0;)if(r=i[n],e===r.toLowerCase())return r;return null}const Kt=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:global,lo=t=>!We(t)&&t!==Kt;function Cn(){const{caseless:t}=lo(this)&&this||{},e={},i=(n,r)=>{const s=t&&ao(e,r)||r;bi(e[s])&&bi(n)?e[s]=Cn(e[s],n):bi(n)?e[s]=Cn({},n):ve(n)?e[s]=n.slice():e[s]=n};for(let n=0,r=arguments.length;n<r;n++)arguments[n]&&Qe(arguments[n],i);return e}const Dl=(t,e,i,{allOwnKeys:n}={})=>(Qe(e,(r,s)=>{i&&Z(r)?t[s]=ro(r,i):t[s]=r},{allOwnKeys:n}),t),Nl=t=>(t.charCodeAt(0)===65279&&(t=t.slice(1)),t),Ul=(t,e,i,n)=>{t.prototype=Object.create(e.prototype,n),t.prototype.constructor=t,Object.defineProperty(t,"super",{value:e.prototype}),i&&Object.assign(t.prototype,i)},Hl=(t,e,i,n)=>{let r,s,o;const a={};if(e=e||{},t==null)return e;do{for(r=Object.getOwnPropertyNames(t),s=r.length;s-- >0;)o=r[s],(!n||n(o,t,e))&&!a[o]&&(e[o]=t[o],a[o]=!0);t=i!==!1&&er(t)}while(t&&(!i||i(t,e))&&t!==Object.prototype);return e},Vl=(t,e,i)=>{t=String(t),(i===void 0||i>t.length)&&(i=t.length),i-=e.length;const n=t.indexOf(e,i);return n!==-1&&n===i},ql=t=>{if(!t)return null;if(ve(t))return t;let e=t.length;if(!oo(e))return null;const i=new Array(e);for(;e-- >0;)i[e]=t[e];return i},jl=(t=>e=>t&&e instanceof t)(typeof Uint8Array<"u"&&er(Uint8Array)),Wl=(t,e)=>{const n=(t&&t[Symbol.iterator]).call(t);let r;for(;(r=n.next())&&!r.done;){const s=r.value;e.call(t,s[0],s[1])}},Kl=(t,e)=>{let i;const n=[];for(;(i=t.exec(e))!==null;)n.push(i);return n},Gl=dt("HTMLFormElement"),Xl=t=>t.toLowerCase().replace(/[-_\s]([a-z\d])(\w*)/g,function(i,n,r){return n.toUpperCase()+r}),Vr=(({hasOwnProperty:t})=>(e,i)=>t.call(e,i))(Object.prototype),Jl=dt("RegExp"),co=(t,e)=>{const i=Object.getOwnPropertyDescriptors(t),n={};Qe(i,(r,s)=>{let o;(o=e(r,s,t))!==!1&&(n[s]=o||r)}),Object.defineProperties(t,n)},Yl=t=>{co(t,(e,i)=>{if(Z(t)&&["arguments","caller","callee"].indexOf(i)!==-1)return!1;const n=t[i];if(Z(n)){if(e.enumerable=!1,"writable"in e){e.writable=!1;return}e.set||(e.set=()=>{throw Error("Can not rewrite read-only method '"+i+"'")})}})},Ql=(t,e)=>{const i={},n=r=>{r.forEach(s=>{i[s]=!0})};return ve(t)?n(t):n(String(t).split(e)),i},Zl=()=>{},tc=(t,e)=>t!=null&&Number.isFinite(t=+t)?t:e;function ec(t){return!!(t&&Z(t.append)&&t[Symbol.toStringTag]==="FormData"&&t[Symbol.iterator])}const ic=t=>{const e=new Array(10),i=(n,r)=>{if(Ii(n)){if(e.indexOf(n)>=0)return;if(!("toJSON"in n)){e[r]=n;const s=ve(n)?[]:{};return Qe(n,(o,a)=>{const l=i(o,r+1);!We(l)&&(s[a]=l)}),e[r]=void 0,s}}return n};return i(t,0)},nc=dt("AsyncFunction"),rc=t=>t&&(Ii(t)||Z(t))&&Z(t.then)&&Z(t.catch),uo=((t,e)=>t?setImmediate:e?((i,n)=>(Kt.addEventListener("message",({source:r,data:s})=>{r===Kt&&s===i&&n.length&&n.shift()()},!1),r=>{n.push(r),Kt.postMessage(i,"*")}))(`axios@${Math.random()}`,[]):i=>setTimeout(i))(typeof setImmediate=="function",Z(Kt.postMessage)),sc=typeof queueMicrotask<"u"?queueMicrotask.bind(Kt):typeof process<"u"&&process.nextTick||uo,m={isArray:ve,isArrayBuffer:so,isBuffer:El,isFormData:Ol,isArrayBufferView:Sl,isString:Cl,isNumber:oo,isBoolean:kl,isObject:Ii,isPlainObject:bi,isReadableStream:Ll,isRequest:zl,isResponse:Pl,isHeaders:Il,isUndefined:We,isDate:Al,isFile:$l,isBlob:Rl,isRegExp:Jl,isFunction:Z,isStream:Fl,isURLSearchParams:Ml,isTypedArray:jl,isFileList:Tl,forEach:Qe,merge:Cn,extend:Dl,trim:Bl,stripBOM:Nl,inherits:Ul,toFlatObject:Hl,kindOf:zi,kindOfTest:dt,endsWith:Vl,toArray:ql,forEachEntry:Wl,matchAll:Kl,isHTMLForm:Gl,hasOwnProperty:Vr,hasOwnProp:Vr,reduceDescriptors:co,freezeMethods:Yl,toObjectSet:Ql,toCamelCase:Xl,noop:Zl,toFiniteNumber:tc,findKey:ao,global:Kt,isContextDefined:lo,isSpecCompliantForm:ec,toJSONObject:ic,isAsyncFn:nc,isThenable:rc,setImmediate:uo,asap:sc};function C(t,e,i,n,r){Error.call(this),Error.captureStackTrace?Error.captureStackTrace(this,this.constructor):this.stack=new Error().stack,this.message=t,this.name="AxiosError",e&&(this.code=e),i&&(this.config=i),n&&(this.request=n),r&&(this.response=r,this.status=r.status?r.status:null)}m.inherits(C,Error,{toJSON:function(){return{message:this.message,name:this.name,description:this.description,number:this.number,fileName:this.fileName,lineNumber:this.lineNumber,columnNumber:this.columnNumber,stack:this.stack,config:m.toJSONObject(this.config),code:this.code,status:this.status}}});const ho=C.prototype,po={};["ERR_BAD_OPTION_VALUE","ERR_BAD_OPTION","ECONNABORTED","ETIMEDOUT","ERR_NETWORK","ERR_FR_TOO_MANY_REDIRECTS","ERR_DEPRECATED","ERR_BAD_RESPONSE","ERR_BAD_REQUEST","ERR_CANCELED","ERR_NOT_SUPPORT","ERR_INVALID_URL"].forEach(t=>{po[t]={value:t}});Object.defineProperties(C,po);Object.defineProperty(ho,"isAxiosError",{value:!0});C.from=(t,e,i,n,r,s)=>{const o=Object.create(ho);return m.toFlatObject(t,o,function(l){return l!==Error.prototype},a=>a!=="isAxiosError"),C.call(o,t.message,e,i,n,r),o.cause=t,o.name=t.name,s&&Object.assign(o,s),o};const oc=null;function kn(t){return m.isPlainObject(t)||m.isArray(t)}function fo(t){return m.endsWith(t,"[]")?t.slice(0,-2):t}function qr(t,e,i){return t?t.concat(e).map(function(r,s){return r=fo(r),!i&&s?"["+r+"]":r}).join(i?".":""):e}function ac(t){return m.isArray(t)&&!t.some(kn)}const lc=m.toFlatObject(m,{},null,function(e){return/^is[A-Z]/.test(e)});function Bi(t,e,i){if(!m.isObject(t))throw new TypeError("target must be an object");e=e||new FormData,i=m.toFlatObject(i,{metaTokens:!0,dots:!1,indexes:!1},!1,function(w,g){return!m.isUndefined(g[w])});const n=i.metaTokens,r=i.visitor||u,s=i.dots,o=i.indexes,l=(i.Blob||typeof Blob<"u"&&Blob)&&m.isSpecCompliantForm(e);if(!m.isFunction(r))throw new TypeError("visitor must be a function");function c(b){if(b===null)return"";if(m.isDate(b))return b.toISOString();if(!l&&m.isBlob(b))throw new C("Blob is not supported. Use a Buffer instead.");return m.isArrayBuffer(b)||m.isTypedArray(b)?l&&typeof Blob=="function"?new Blob([b]):Buffer.from(b):b}function u(b,w,g){let _=b;if(b&&!g&&typeof b=="object"){if(m.endsWith(w,"{}"))w=n?w:w.slice(0,-2),b=JSON.stringify(b);else if(m.isArray(b)&&ac(b)||(m.isFileList(b)||m.endsWith(w,"[]"))&&(_=m.toArray(b)))return w=fo(w),_.forEach(function(E,k){!(m.isUndefined(E)||E===null)&&e.append(o===!0?qr([w],k,s):o===null?w:w+"[]",c(E))}),!1}return kn(b)?!0:(e.append(qr(g,w,s),c(b)),!1)}const d=[],p=Object.assign(lc,{defaultVisitor:u,convertValue:c,isVisitable:kn});function f(b,w){if(!m.isUndefined(b)){if(d.indexOf(b)!==-1)throw Error("Circular reference detected in "+w.join("."));d.push(b),m.forEach(b,function(_,x){(!(m.isUndefined(_)||_===null)&&r.call(e,_,m.isString(x)?x.trim():x,w,p))===!0&&f(_,w?w.concat(x):[x])}),d.pop()}}if(!m.isObject(t))throw new TypeError("data must be an object");return f(t),e}function jr(t){const e={"!":"%21","'":"%27","(":"%28",")":"%29","~":"%7E","%20":"+","%00":"\0"};return encodeURIComponent(t).replace(/[!'()~]|%20|%00/g,function(n){return e[n]})}function ir(t,e){this._pairs=[],t&&Bi(t,this,e)}const go=ir.prototype;go.append=function(e,i){this._pairs.push([e,i])};go.toString=function(e){const i=e?function(n){return e.call(this,n,jr)}:jr;return this._pairs.map(function(r){return i(r[0])+"="+i(r[1])},"").join("&")};function cc(t){return encodeURIComponent(t).replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+").replace(/%5B/gi,"[").replace(/%5D/gi,"]")}function mo(t,e,i){if(!e)return t;const n=i&&i.encode||cc;m.isFunction(i)&&(i={serialize:i});const r=i&&i.serialize;let s;if(r?s=r(e,i):s=m.isURLSearchParams(e)?e.toString():new ir(e,i).toString(n),s){const o=t.indexOf("#");o!==-1&&(t=t.slice(0,o)),t+=(t.indexOf("?")===-1?"?":"&")+s}return t}class Wr{constructor(){this.handlers=[]}use(e,i,n){return this.handlers.push({fulfilled:e,rejected:i,synchronous:n?n.synchronous:!1,runWhen:n?n.runWhen:null}),this.handlers.length-1}eject(e){this.handlers[e]&&(this.handlers[e]=null)}clear(){this.handlers&&(this.handlers=[])}forEach(e){m.forEach(this.handlers,function(n){n!==null&&e(n)})}}const bo={silentJSONParsing:!0,forcedJSONParsing:!0,clarifyTimeoutError:!1},uc=typeof URLSearchParams<"u"?URLSearchParams:ir,dc=typeof FormData<"u"?FormData:null,hc=typeof Blob<"u"?Blob:null,pc={isBrowser:!0,classes:{URLSearchParams:uc,FormData:dc,Blob:hc},protocols:["http","https","file","blob","url","data"]},nr=typeof window<"u"&&typeof document<"u",An=typeof navigator=="object"&&navigator||void 0,fc=nr&&(!An||["ReactNative","NativeScript","NS"].indexOf(An.product)<0),gc=typeof WorkerGlobalScope<"u"&&self instanceof WorkerGlobalScope&&typeof self.importScripts=="function",mc=nr&&window.location.href||"http://localhost",bc=Object.freeze(Object.defineProperty({__proto__:null,hasBrowserEnv:nr,hasStandardBrowserEnv:fc,hasStandardBrowserWebWorkerEnv:gc,navigator:An,origin:mc},Symbol.toStringTag,{value:"Module"})),V={...bc,...pc};function yc(t,e){return Bi(t,new V.classes.URLSearchParams,Object.assign({visitor:function(i,n,r,s){return V.isNode&&m.isBuffer(i)?(this.append(n,i.toString("base64")),!1):s.defaultVisitor.apply(this,arguments)}},e))}function vc(t){return m.matchAll(/\w+|\[(\w*)]/g,t).map(e=>e[0]==="[]"?"":e[1]||e[0])}function wc(t){const e={},i=Object.keys(t);let n;const r=i.length;let s;for(n=0;n<r;n++)s=i[n],e[s]=t[s];return e}function yo(t){function e(i,n,r,s){let o=i[s++];if(o==="__proto__")return!0;const a=Number.isFinite(+o),l=s>=i.length;return o=!o&&m.isArray(r)?r.length:o,l?(m.hasOwnProp(r,o)?r[o]=[r[o],n]:r[o]=n,!a):((!r[o]||!m.isObject(r[o]))&&(r[o]=[]),e(i,n,r[o],s)&&m.isArray(r[o])&&(r[o]=wc(r[o])),!a)}if(m.isFormData(t)&&m.isFunction(t.entries)){const i={};return m.forEachEntry(t,(n,r)=>{e(vc(n),r,i,0)}),i}return null}function _c(t,e,i){if(m.isString(t))try{return(e||JSON.parse)(t),m.trim(t)}catch(n){if(n.name!=="SyntaxError")throw n}return(i||JSON.stringify)(t)}const Ze={transitional:bo,adapter:["xhr","http","fetch"],transformRequest:[function(e,i){const n=i.getContentType()||"",r=n.indexOf("application/json")>-1,s=m.isObject(e);if(s&&m.isHTMLForm(e)&&(e=new FormData(e)),m.isFormData(e))return r?JSON.stringify(yo(e)):e;if(m.isArrayBuffer(e)||m.isBuffer(e)||m.isStream(e)||m.isFile(e)||m.isBlob(e)||m.isReadableStream(e))return e;if(m.isArrayBufferView(e))return e.buffer;if(m.isURLSearchParams(e))return i.setContentType("application/x-www-form-urlencoded;charset=utf-8",!1),e.toString();let a;if(s){if(n.indexOf("application/x-www-form-urlencoded")>-1)return yc(e,this.formSerializer).toString();if((a=m.isFileList(e))||n.indexOf("multipart/form-data")>-1){const l=this.env&&this.env.FormData;return Bi(a?{"files[]":e}:e,l&&new l,this.formSerializer)}}return s||r?(i.setContentType("application/json",!1),_c(e)):e}],transformResponse:[function(e){const i=this.transitional||Ze.transitional,n=i&&i.forcedJSONParsing,r=this.responseType==="json";if(m.isResponse(e)||m.isReadableStream(e))return e;if(e&&m.isString(e)&&(n&&!this.responseType||r)){const o=!(i&&i.silentJSONParsing)&&r;try{return JSON.parse(e)}catch(a){if(o)throw a.name==="SyntaxError"?C.from(a,C.ERR_BAD_RESPONSE,this,null,this.response):a}}return e}],timeout:0,xsrfCookieName:"XSRF-TOKEN",xsrfHeaderName:"X-XSRF-TOKEN",maxContentLength:-1,maxBodyLength:-1,env:{FormData:V.classes.FormData,Blob:V.classes.Blob},validateStatus:function(e){return e>=200&&e<300},headers:{common:{Accept:"application/json, text/plain, */*","Content-Type":void 0}}};m.forEach(["delete","get","head","post","put","patch"],t=>{Ze.headers[t]={}});const xc=m.toObjectSet(["age","authorization","content-length","content-type","etag","expires","from","host","if-modified-since","if-unmodified-since","last-modified","location","max-forwards","proxy-authorization","referer","retry-after","user-agent"]),Ec=t=>{const e={};let i,n,r;return t&&t.split(`
`).forEach(function(o){r=o.indexOf(":"),i=o.substring(0,r).trim().toLowerCase(),n=o.substring(r+1).trim(),!(!i||e[i]&&xc[i])&&(i==="set-cookie"?e[i]?e[i].push(n):e[i]=[n]:e[i]=e[i]?e[i]+", "+n:n)}),e},Kr=Symbol("internals");function Te(t){return t&&String(t).trim().toLowerCase()}function yi(t){return t===!1||t==null?t:m.isArray(t)?t.map(yi):String(t)}function Sc(t){const e=Object.create(null),i=/([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g;let n;for(;n=i.exec(t);)e[n[1]]=n[2];return e}const Cc=t=>/^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(t.trim());function tn(t,e,i,n,r){if(m.isFunction(n))return n.call(this,e,i);if(r&&(e=i),!!m.isString(e)){if(m.isString(n))return e.indexOf(n)!==-1;if(m.isRegExp(n))return n.test(e)}}function kc(t){return t.trim().toLowerCase().replace(/([a-z\d])(\w*)/g,(e,i,n)=>i.toUpperCase()+n)}function Ac(t,e){const i=m.toCamelCase(" "+e);["get","set","has"].forEach(n=>{Object.defineProperty(t,n+i,{value:function(r,s,o){return this[n].call(this,e,r,s,o)},configurable:!0})})}let G=class{constructor(e){e&&this.set(e)}set(e,i,n){const r=this;function s(a,l,c){const u=Te(l);if(!u)throw new Error("header name must be a non-empty string");const d=m.findKey(r,u);(!d||r[d]===void 0||c===!0||c===void 0&&r[d]!==!1)&&(r[d||l]=yi(a))}const o=(a,l)=>m.forEach(a,(c,u)=>s(c,u,l));if(m.isPlainObject(e)||e instanceof this.constructor)o(e,i);else if(m.isString(e)&&(e=e.trim())&&!Cc(e))o(Ec(e),i);else if(m.isHeaders(e))for(const[a,l]of e.entries())s(l,a,n);else e!=null&&s(i,e,n);return this}get(e,i){if(e=Te(e),e){const n=m.findKey(this,e);if(n){const r=this[n];if(!i)return r;if(i===!0)return Sc(r);if(m.isFunction(i))return i.call(this,r,n);if(m.isRegExp(i))return i.exec(r);throw new TypeError("parser must be boolean|regexp|function")}}}has(e,i){if(e=Te(e),e){const n=m.findKey(this,e);return!!(n&&this[n]!==void 0&&(!i||tn(this,this[n],n,i)))}return!1}delete(e,i){const n=this;let r=!1;function s(o){if(o=Te(o),o){const a=m.findKey(n,o);a&&(!i||tn(n,n[a],a,i))&&(delete n[a],r=!0)}}return m.isArray(e)?e.forEach(s):s(e),r}clear(e){const i=Object.keys(this);let n=i.length,r=!1;for(;n--;){const s=i[n];(!e||tn(this,this[s],s,e,!0))&&(delete this[s],r=!0)}return r}normalize(e){const i=this,n={};return m.forEach(this,(r,s)=>{const o=m.findKey(n,s);if(o){i[o]=yi(r),delete i[s];return}const a=e?kc(s):String(s).trim();a!==s&&delete i[s],i[a]=yi(r),n[a]=!0}),this}concat(...e){return this.constructor.concat(this,...e)}toJSON(e){const i=Object.create(null);return m.forEach(this,(n,r)=>{n!=null&&n!==!1&&(i[r]=e&&m.isArray(n)?n.join(", "):n)}),i}[Symbol.iterator](){return Object.entries(this.toJSON())[Symbol.iterator]()}toString(){return Object.entries(this.toJSON()).map(([e,i])=>e+": "+i).join(`
`)}get[Symbol.toStringTag](){return"AxiosHeaders"}static from(e){return e instanceof this?e:new this(e)}static concat(e,...i){const n=new this(e);return i.forEach(r=>n.set(r)),n}static accessor(e){const n=(this[Kr]=this[Kr]={accessors:{}}).accessors,r=this.prototype;function s(o){const a=Te(o);n[a]||(Ac(r,o),n[a]=!0)}return m.isArray(e)?e.forEach(s):s(e),this}};G.accessor(["Content-Type","Content-Length","Accept","Accept-Encoding","User-Agent","Authorization"]);m.reduceDescriptors(G.prototype,({value:t},e)=>{let i=e[0].toUpperCase()+e.slice(1);return{get:()=>t,set(n){this[i]=n}}});m.freezeMethods(G);function en(t,e){const i=this||Ze,n=e||i,r=G.from(n.headers);let s=n.data;return m.forEach(t,function(a){s=a.call(i,s,r.normalize(),e?e.status:void 0)}),r.normalize(),s}function vo(t){return!!(t&&t.__CANCEL__)}function we(t,e,i){C.call(this,t??"canceled",C.ERR_CANCELED,e,i),this.name="CanceledError"}m.inherits(we,C,{__CANCEL__:!0});function wo(t,e,i){const n=i.config.validateStatus;!i.status||!n||n(i.status)?t(i):e(new C("Request failed with status code "+i.status,[C.ERR_BAD_REQUEST,C.ERR_BAD_RESPONSE][Math.floor(i.status/100)-4],i.config,i.request,i))}function $c(t){const e=/^([-+\w]{1,25})(:?\/\/|:)/.exec(t);return e&&e[1]||""}function Rc(t,e){t=t||10;const i=new Array(t),n=new Array(t);let r=0,s=0,o;return e=e!==void 0?e:1e3,function(l){const c=Date.now(),u=n[s];o||(o=c),i[r]=l,n[r]=c;let d=s,p=0;for(;d!==r;)p+=i[d++],d=d%t;if(r=(r+1)%t,r===s&&(s=(s+1)%t),c-o<e)return;const f=u&&c-u;return f?Math.round(p*1e3/f):void 0}}function Tc(t,e){let i=0,n=1e3/e,r,s;const o=(c,u=Date.now())=>{i=u,r=null,s&&(clearTimeout(s),s=null),t.apply(null,c)};return[(...c)=>{const u=Date.now(),d=u-i;d>=n?o(c,u):(r=c,s||(s=setTimeout(()=>{s=null,o(r)},n-d)))},()=>r&&o(r)]}const Ci=(t,e,i=3)=>{let n=0;const r=Rc(50,250);return Tc(s=>{const o=s.loaded,a=s.lengthComputable?s.total:void 0,l=o-n,c=r(l),u=o<=a;n=o;const d={loaded:o,total:a,progress:a?o/a:void 0,bytes:l,rate:c||void 0,estimated:c&&a&&u?(a-o)/c:void 0,event:s,lengthComputable:a!=null,[e?"download":"upload"]:!0};t(d)},i)},Gr=(t,e)=>{const i=t!=null;return[n=>e[0]({lengthComputable:i,total:t,loaded:n}),e[1]]},Xr=t=>(...e)=>m.asap(()=>t(...e)),Fc=V.hasStandardBrowserEnv?((t,e)=>i=>(i=new URL(i,V.origin),t.protocol===i.protocol&&t.host===i.host&&(e||t.port===i.port)))(new URL(V.origin),V.navigator&&/(msie|trident)/i.test(V.navigator.userAgent)):()=>!0,Oc=V.hasStandardBrowserEnv?{write(t,e,i,n,r,s){const o=[t+"="+encodeURIComponent(e)];m.isNumber(i)&&o.push("expires="+new Date(i).toGMTString()),m.isString(n)&&o.push("path="+n),m.isString(r)&&o.push("domain="+r),s===!0&&o.push("secure"),document.cookie=o.join("; ")},read(t){const e=document.cookie.match(new RegExp("(^|;\\s*)("+t+")=([^;]*)"));return e?decodeURIComponent(e[3]):null},remove(t){this.write(t,"",Date.now()-864e5)}}:{write(){},read(){return null},remove(){}};function Mc(t){return/^([a-z][a-z\d+\-.]*:)?\/\//i.test(t)}function Lc(t,e){return e?t.replace(/\/?\/$/,"")+"/"+e.replace(/^\/+/,""):t}function _o(t,e,i){let n=!Mc(e);return t&&(n||i==!1)?Lc(t,e):e}const Jr=t=>t instanceof G?{...t}:t;function ne(t,e){e=e||{};const i={};function n(c,u,d,p){return m.isPlainObject(c)&&m.isPlainObject(u)?m.merge.call({caseless:p},c,u):m.isPlainObject(u)?m.merge({},u):m.isArray(u)?u.slice():u}function r(c,u,d,p){if(m.isUndefined(u)){if(!m.isUndefined(c))return n(void 0,c,d,p)}else return n(c,u,d,p)}function s(c,u){if(!m.isUndefined(u))return n(void 0,u)}function o(c,u){if(m.isUndefined(u)){if(!m.isUndefined(c))return n(void 0,c)}else return n(void 0,u)}function a(c,u,d){if(d in e)return n(c,u);if(d in t)return n(void 0,c)}const l={url:s,method:s,data:s,baseURL:o,transformRequest:o,transformResponse:o,paramsSerializer:o,timeout:o,timeoutMessage:o,withCredentials:o,withXSRFToken:o,adapter:o,responseType:o,xsrfCookieName:o,xsrfHeaderName:o,onUploadProgress:o,onDownloadProgress:o,decompress:o,maxContentLength:o,maxBodyLength:o,beforeRedirect:o,transport:o,httpAgent:o,httpsAgent:o,cancelToken:o,socketPath:o,responseEncoding:o,validateStatus:a,headers:(c,u,d)=>r(Jr(c),Jr(u),d,!0)};return m.forEach(Object.keys(Object.assign({},t,e)),function(u){const d=l[u]||r,p=d(t[u],e[u],u);m.isUndefined(p)&&d!==a||(i[u]=p)}),i}const xo=t=>{const e=ne({},t);let{data:i,withXSRFToken:n,xsrfHeaderName:r,xsrfCookieName:s,headers:o,auth:a}=e;e.headers=o=G.from(o),e.url=mo(_o(e.baseURL,e.url,e.allowAbsoluteUrls),t.params,t.paramsSerializer),a&&o.set("Authorization","Basic "+btoa((a.username||"")+":"+(a.password?unescape(encodeURIComponent(a.password)):"")));let l;if(m.isFormData(i)){if(V.hasStandardBrowserEnv||V.hasStandardBrowserWebWorkerEnv)o.setContentType(void 0);else if((l=o.getContentType())!==!1){const[c,...u]=l?l.split(";").map(d=>d.trim()).filter(Boolean):[];o.setContentType([c||"multipart/form-data",...u].join("; "))}}if(V.hasStandardBrowserEnv&&(n&&m.isFunction(n)&&(n=n(e)),n||n!==!1&&Fc(e.url))){const c=r&&s&&Oc.read(s);c&&o.set(r,c)}return e},zc=typeof XMLHttpRequest<"u",Pc=zc&&function(t){return new Promise(function(i,n){const r=xo(t);let s=r.data;const o=G.from(r.headers).normalize();let{responseType:a,onUploadProgress:l,onDownloadProgress:c}=r,u,d,p,f,b;function w(){f&&f(),b&&b(),r.cancelToken&&r.cancelToken.unsubscribe(u),r.signal&&r.signal.removeEventListener("abort",u)}let g=new XMLHttpRequest;g.open(r.method.toUpperCase(),r.url,!0),g.timeout=r.timeout;function _(){if(!g)return;const E=G.from("getAllResponseHeaders"in g&&g.getAllResponseHeaders()),S={data:!a||a==="text"||a==="json"?g.responseText:g.response,status:g.status,statusText:g.statusText,headers:E,config:t,request:g};wo(function(T){i(T),w()},function(T){n(T),w()},S),g=null}"onloadend"in g?g.onloadend=_:g.onreadystatechange=function(){!g||g.readyState!==4||g.status===0&&!(g.responseURL&&g.responseURL.indexOf("file:")===0)||setTimeout(_)},g.onabort=function(){g&&(n(new C("Request aborted",C.ECONNABORTED,t,g)),g=null)},g.onerror=function(){n(new C("Network Error",C.ERR_NETWORK,t,g)),g=null},g.ontimeout=function(){let k=r.timeout?"timeout of "+r.timeout+"ms exceeded":"timeout exceeded";const S=r.transitional||bo;r.timeoutErrorMessage&&(k=r.timeoutErrorMessage),n(new C(k,S.clarifyTimeoutError?C.ETIMEDOUT:C.ECONNABORTED,t,g)),g=null},s===void 0&&o.setContentType(null),"setRequestHeader"in g&&m.forEach(o.toJSON(),function(k,S){g.setRequestHeader(S,k)}),m.isUndefined(r.withCredentials)||(g.withCredentials=!!r.withCredentials),a&&a!=="json"&&(g.responseType=r.responseType),c&&([p,b]=Ci(c,!0),g.addEventListener("progress",p)),l&&g.upload&&([d,f]=Ci(l),g.upload.addEventListener("progress",d),g.upload.addEventListener("loadend",f)),(r.cancelToken||r.signal)&&(u=E=>{g&&(n(!E||E.type?new we(null,t,g):E),g.abort(),g=null)},r.cancelToken&&r.cancelToken.subscribe(u),r.signal&&(r.signal.aborted?u():r.signal.addEventListener("abort",u)));const x=$c(r.url);if(x&&V.protocols.indexOf(x)===-1){n(new C("Unsupported protocol "+x+":",C.ERR_BAD_REQUEST,t));return}g.send(s||null)})},Ic=(t,e)=>{const{length:i}=t=t?t.filter(Boolean):[];if(e||i){let n=new AbortController,r;const s=function(c){if(!r){r=!0,a();const u=c instanceof Error?c:this.reason;n.abort(u instanceof C?u:new we(u instanceof Error?u.message:u))}};let o=e&&setTimeout(()=>{o=null,s(new C(`timeout ${e} of ms exceeded`,C.ETIMEDOUT))},e);const a=()=>{t&&(o&&clearTimeout(o),o=null,t.forEach(c=>{c.unsubscribe?c.unsubscribe(s):c.removeEventListener("abort",s)}),t=null)};t.forEach(c=>c.addEventListener("abort",s));const{signal:l}=n;return l.unsubscribe=()=>m.asap(a),l}},Bc=function*(t,e){let i=t.byteLength;if(i<e){yield t;return}let n=0,r;for(;n<i;)r=n+e,yield t.slice(n,r),n=r},Dc=async function*(t,e){for await(const i of Nc(t))yield*Bc(i,e)},Nc=async function*(t){if(t[Symbol.asyncIterator]){yield*t;return}const e=t.getReader();try{for(;;){const{done:i,value:n}=await e.read();if(i)break;yield n}}finally{await e.cancel()}},Yr=(t,e,i,n)=>{const r=Dc(t,e);let s=0,o,a=l=>{o||(o=!0,n&&n(l))};return new ReadableStream({async pull(l){try{const{done:c,value:u}=await r.next();if(c){a(),l.close();return}let d=u.byteLength;if(i){let p=s+=d;i(p)}l.enqueue(new Uint8Array(u))}catch(c){throw a(c),c}},cancel(l){return a(l),r.return()}},{highWaterMark:2})},Di=typeof fetch=="function"&&typeof Request=="function"&&typeof Response=="function",Eo=Di&&typeof ReadableStream=="function",Uc=Di&&(typeof TextEncoder=="function"?(t=>e=>t.encode(e))(new TextEncoder):async t=>new Uint8Array(await new Response(t).arrayBuffer())),So=(t,...e)=>{try{return!!t(...e)}catch{return!1}},Hc=Eo&&So(()=>{let t=!1;const e=new Request(V.origin,{body:new ReadableStream,method:"POST",get duplex(){return t=!0,"half"}}).headers.has("Content-Type");return t&&!e}),Qr=64*1024,$n=Eo&&So(()=>m.isReadableStream(new Response("").body)),ki={stream:$n&&(t=>t.body)};Di&&(t=>{["text","arrayBuffer","blob","formData","stream"].forEach(e=>{!ki[e]&&(ki[e]=m.isFunction(t[e])?i=>i[e]():(i,n)=>{throw new C(`Response type '${e}' is not supported`,C.ERR_NOT_SUPPORT,n)})})})(new Response);const Vc=async t=>{if(t==null)return 0;if(m.isBlob(t))return t.size;if(m.isSpecCompliantForm(t))return(await new Request(V.origin,{method:"POST",body:t}).arrayBuffer()).byteLength;if(m.isArrayBufferView(t)||m.isArrayBuffer(t))return t.byteLength;if(m.isURLSearchParams(t)&&(t=t+""),m.isString(t))return(await Uc(t)).byteLength},qc=async(t,e)=>{const i=m.toFiniteNumber(t.getContentLength());return i??Vc(e)},jc=Di&&(async t=>{let{url:e,method:i,data:n,signal:r,cancelToken:s,timeout:o,onDownloadProgress:a,onUploadProgress:l,responseType:c,headers:u,withCredentials:d="same-origin",fetchOptions:p}=xo(t);c=c?(c+"").toLowerCase():"text";let f=Ic([r,s&&s.toAbortSignal()],o),b;const w=f&&f.unsubscribe&&(()=>{f.unsubscribe()});let g;try{if(l&&Hc&&i!=="get"&&i!=="head"&&(g=await qc(u,n))!==0){let S=new Request(e,{method:"POST",body:n,duplex:"half"}),O;if(m.isFormData(n)&&(O=S.headers.get("content-type"))&&u.setContentType(O),S.body){const[T,H]=Gr(g,Ci(Xr(l)));n=Yr(S.body,Qr,T,H)}}m.isString(d)||(d=d?"include":"omit");const _="credentials"in Request.prototype;b=new Request(e,{...p,signal:f,method:i.toUpperCase(),headers:u.normalize().toJSON(),body:n,duplex:"half",credentials:_?d:void 0});let x=await fetch(b);const E=$n&&(c==="stream"||c==="response");if($n&&(a||E&&w)){const S={};["status","statusText","headers"].forEach(j=>{S[j]=x[j]});const O=m.toFiniteNumber(x.headers.get("content-length")),[T,H]=a&&Gr(O,Ci(Xr(a),!0))||[];x=new Response(Yr(x.body,Qr,T,()=>{H&&H(),w&&w()}),S)}c=c||"text";let k=await ki[m.findKey(ki,c)||"text"](x,t);return!E&&w&&w(),await new Promise((S,O)=>{wo(S,O,{data:k,headers:G.from(x.headers),status:x.status,statusText:x.statusText,config:t,request:b})})}catch(_){throw w&&w(),_&&_.name==="TypeError"&&/fetch/i.test(_.message)?Object.assign(new C("Network Error",C.ERR_NETWORK,t,b),{cause:_.cause||_}):C.from(_,_&&_.code,t,b)}}),Rn={http:oc,xhr:Pc,fetch:jc};m.forEach(Rn,(t,e)=>{if(t){try{Object.defineProperty(t,"name",{value:e})}catch{}Object.defineProperty(t,"adapterName",{value:e})}});const Zr=t=>`- ${t}`,Wc=t=>m.isFunction(t)||t===null||t===!1,Co={getAdapter:t=>{t=m.isArray(t)?t:[t];const{length:e}=t;let i,n;const r={};for(let s=0;s<e;s++){i=t[s];let o;if(n=i,!Wc(i)&&(n=Rn[(o=String(i)).toLowerCase()],n===void 0))throw new C(`Unknown adapter '${o}'`);if(n)break;r[o||"#"+s]=n}if(!n){const s=Object.entries(r).map(([a,l])=>`adapter ${a} `+(l===!1?"is not supported by the environment":"is not available in the build"));let o=e?s.length>1?`since :
`+s.map(Zr).join(`
`):" "+Zr(s[0]):"as no adapter specified";throw new C("There is no suitable adapter to dispatch the request "+o,"ERR_NOT_SUPPORT")}return n},adapters:Rn};function nn(t){if(t.cancelToken&&t.cancelToken.throwIfRequested(),t.signal&&t.signal.aborted)throw new we(null,t)}function ts(t){return nn(t),t.headers=G.from(t.headers),t.data=en.call(t,t.transformRequest),["post","put","patch"].indexOf(t.method)!==-1&&t.headers.setContentType("application/x-www-form-urlencoded",!1),Co.getAdapter(t.adapter||Ze.adapter)(t).then(function(n){return nn(t),n.data=en.call(t,t.transformResponse,n),n.headers=G.from(n.headers),n},function(n){return vo(n)||(nn(t),n&&n.response&&(n.response.data=en.call(t,t.transformResponse,n.response),n.response.headers=G.from(n.response.headers))),Promise.reject(n)})}const ko="1.8.4",Ni={};["object","boolean","number","function","string","symbol"].forEach((t,e)=>{Ni[t]=function(n){return typeof n===t||"a"+(e<1?"n ":" ")+t}});const es={};Ni.transitional=function(e,i,n){function r(s,o){return"[Axios v"+ko+"] Transitional option '"+s+"'"+o+(n?". "+n:"")}return(s,o,a)=>{if(e===!1)throw new C(r(o," has been removed"+(i?" in "+i:"")),C.ERR_DEPRECATED);return i&&!es[o]&&(es[o]=!0,console.warn(r(o," has been deprecated since v"+i+" and will be removed in the near future"))),e?e(s,o,a):!0}};Ni.spelling=function(e){return(i,n)=>(console.warn(`${n} is likely a misspelling of ${e}`),!0)};function Kc(t,e,i){if(typeof t!="object")throw new C("options must be an object",C.ERR_BAD_OPTION_VALUE);const n=Object.keys(t);let r=n.length;for(;r-- >0;){const s=n[r],o=e[s];if(o){const a=t[s],l=a===void 0||o(a,s,t);if(l!==!0)throw new C("option "+s+" must be "+l,C.ERR_BAD_OPTION_VALUE);continue}if(i!==!0)throw new C("Unknown option "+s,C.ERR_BAD_OPTION)}}const vi={assertOptions:Kc,validators:Ni},bt=vi.validators;let Yt=class{constructor(e){this.defaults=e,this.interceptors={request:new Wr,response:new Wr}}async request(e,i){try{return await this._request(e,i)}catch(n){if(n instanceof Error){let r={};Error.captureStackTrace?Error.captureStackTrace(r):r=new Error;const s=r.stack?r.stack.replace(/^.+\n/,""):"";try{n.stack?s&&!String(n.stack).endsWith(s.replace(/^.+\n.+\n/,""))&&(n.stack+=`
`+s):n.stack=s}catch{}}throw n}}_request(e,i){typeof e=="string"?(i=i||{},i.url=e):i=e||{},i=ne(this.defaults,i);const{transitional:n,paramsSerializer:r,headers:s}=i;n!==void 0&&vi.assertOptions(n,{silentJSONParsing:bt.transitional(bt.boolean),forcedJSONParsing:bt.transitional(bt.boolean),clarifyTimeoutError:bt.transitional(bt.boolean)},!1),r!=null&&(m.isFunction(r)?i.paramsSerializer={serialize:r}:vi.assertOptions(r,{encode:bt.function,serialize:bt.function},!0)),i.allowAbsoluteUrls!==void 0||(this.defaults.allowAbsoluteUrls!==void 0?i.allowAbsoluteUrls=this.defaults.allowAbsoluteUrls:i.allowAbsoluteUrls=!0),vi.assertOptions(i,{baseUrl:bt.spelling("baseURL"),withXsrfToken:bt.spelling("withXSRFToken")},!0),i.method=(i.method||this.defaults.method||"get").toLowerCase();let o=s&&m.merge(s.common,s[i.method]);s&&m.forEach(["delete","get","head","post","put","patch","common"],b=>{delete s[b]}),i.headers=G.concat(o,s);const a=[];let l=!0;this.interceptors.request.forEach(function(w){typeof w.runWhen=="function"&&w.runWhen(i)===!1||(l=l&&w.synchronous,a.unshift(w.fulfilled,w.rejected))});const c=[];this.interceptors.response.forEach(function(w){c.push(w.fulfilled,w.rejected)});let u,d=0,p;if(!l){const b=[ts.bind(this),void 0];for(b.unshift.apply(b,a),b.push.apply(b,c),p=b.length,u=Promise.resolve(i);d<p;)u=u.then(b[d++],b[d++]);return u}p=a.length;let f=i;for(d=0;d<p;){const b=a[d++],w=a[d++];try{f=b(f)}catch(g){w.call(this,g);break}}try{u=ts.call(this,f)}catch(b){return Promise.reject(b)}for(d=0,p=c.length;d<p;)u=u.then(c[d++],c[d++]);return u}getUri(e){e=ne(this.defaults,e);const i=_o(e.baseURL,e.url,e.allowAbsoluteUrls);return mo(i,e.params,e.paramsSerializer)}};m.forEach(["delete","get","head","options"],function(e){Yt.prototype[e]=function(i,n){return this.request(ne(n||{},{method:e,url:i,data:(n||{}).data}))}});m.forEach(["post","put","patch"],function(e){function i(n){return function(s,o,a){return this.request(ne(a||{},{method:e,headers:n?{"Content-Type":"multipart/form-data"}:{},url:s,data:o}))}}Yt.prototype[e]=i(),Yt.prototype[e+"Form"]=i(!0)});let Gc=class Ao{constructor(e){if(typeof e!="function")throw new TypeError("executor must be a function.");let i;this.promise=new Promise(function(s){i=s});const n=this;this.promise.then(r=>{if(!n._listeners)return;let s=n._listeners.length;for(;s-- >0;)n._listeners[s](r);n._listeners=null}),this.promise.then=r=>{let s;const o=new Promise(a=>{n.subscribe(a),s=a}).then(r);return o.cancel=function(){n.unsubscribe(s)},o},e(function(s,o,a){n.reason||(n.reason=new we(s,o,a),i(n.reason))})}throwIfRequested(){if(this.reason)throw this.reason}subscribe(e){if(this.reason){e(this.reason);return}this._listeners?this._listeners.push(e):this._listeners=[e]}unsubscribe(e){if(!this._listeners)return;const i=this._listeners.indexOf(e);i!==-1&&this._listeners.splice(i,1)}toAbortSignal(){const e=new AbortController,i=n=>{e.abort(n)};return this.subscribe(i),e.signal.unsubscribe=()=>this.unsubscribe(i),e.signal}static source(){let e;return{token:new Ao(function(r){e=r}),cancel:e}}};function Xc(t){return function(i){return t.apply(null,i)}}function Jc(t){return m.isObject(t)&&t.isAxiosError===!0}const Tn={Continue:100,SwitchingProtocols:101,Processing:102,EarlyHints:103,Ok:200,Created:201,Accepted:202,NonAuthoritativeInformation:203,NoContent:204,ResetContent:205,PartialContent:206,MultiStatus:207,AlreadyReported:208,ImUsed:226,MultipleChoices:300,MovedPermanently:301,Found:302,SeeOther:303,NotModified:304,UseProxy:305,Unused:306,TemporaryRedirect:307,PermanentRedirect:308,BadRequest:400,Unauthorized:401,PaymentRequired:402,Forbidden:403,NotFound:404,MethodNotAllowed:405,NotAcceptable:406,ProxyAuthenticationRequired:407,RequestTimeout:408,Conflict:409,Gone:410,LengthRequired:411,PreconditionFailed:412,PayloadTooLarge:413,UriTooLong:414,UnsupportedMediaType:415,RangeNotSatisfiable:416,ExpectationFailed:417,ImATeapot:418,MisdirectedRequest:421,UnprocessableEntity:422,Locked:423,FailedDependency:424,TooEarly:425,UpgradeRequired:426,PreconditionRequired:428,TooManyRequests:429,RequestHeaderFieldsTooLarge:431,UnavailableForLegalReasons:451,InternalServerError:500,NotImplemented:501,BadGateway:502,ServiceUnavailable:503,GatewayTimeout:504,HttpVersionNotSupported:505,VariantAlsoNegotiates:506,InsufficientStorage:507,LoopDetected:508,NotExtended:510,NetworkAuthenticationRequired:511};Object.entries(Tn).forEach(([t,e])=>{Tn[e]=t});function $o(t){const e=new Yt(t),i=ro(Yt.prototype.request,e);return m.extend(i,Yt.prototype,e,{allOwnKeys:!0}),m.extend(i,e,null,{allOwnKeys:!0}),i.create=function(r){return $o(ne(t,r))},i}const B=$o(Ze);B.Axios=Yt;B.CanceledError=we;B.CancelToken=Gc;B.isCancel=vo;B.VERSION=ko;B.toFormData=Bi;B.AxiosError=C;B.Cancel=B.CanceledError;B.all=function(e){return Promise.all(e)};B.spread=Xc;B.isAxiosError=Jc;B.mergeConfig=ne;B.AxiosHeaders=G;B.formToJSON=t=>yo(m.isHTMLForm(t)?new FormData(t):t);B.getAdapter=Co.getAdapter;B.HttpStatusCode=Tn;B.default=B;const{Axios:Nf,AxiosError:Uf,CanceledError:Hf,isCancel:Vf,CancelToken:qf,VERSION:jf,all:Wf,Cancel:Kf,isAxiosError:Gf,spread:Xf,toFormData:Jf,AxiosHeaders:Yf,HttpStatusCode:Qf,formToJSON:Zf,getAdapter:tg,mergeConfig:eg}=B;window.axios=B;window.axios.defaults.headers.common["X-Requested-With"]="XMLHttpRequest";window.fileManagerState=window.fileManagerState||{initialized:!1,initSource:null,instance:null};function Yc(t,e={}){return window.fileManagerAlreadyInitialized?(console.info(`File Manager already initialized. Skipping ${t} initialization.`),window.fileManagerState.instance):window.fileManagerState.initialized?(console.info(`File Manager already initialized by ${window.fileManagerState.initSource}. Skipping ${t} initialization.`),window.fileManagerState.instance):(console.info(`Initializing File Manager from ${t}`),window.fileManagerAlreadyInitialized=!0,window.fileManagerState.initialized=!0,window.fileManagerState.initSource=t,t==="lazy-loader"?window.fileManagerState.instance=new FileManagerLazyLoader(e):t==="alpine"&&console.info("Alpine.js initialization will set the instance when ready"),window.fileManagerState.instance)}window.initializeFileManager=Yc;function Qc(){console.group("File Manager State Debug"),console.log("Initialized:",window.fileManagerState.initialized),console.log("Init Source:",window.fileManagerState.initSource),console.log("Instance:",window.fileManagerState.instance?"Exists":"None"),console.log("Alpine.js Loaded:",typeof window.Alpine<"u"),console.log("fileManagerRegistered:",window.fileManagerRegistered),console.log("fileManagerInitialized:",window.fileManagerInitialized),console.log("fileManagerAlreadyInitialized:",window.fileManagerAlreadyInitialized);const t=document.querySelector("[data-lazy-container]");console.log("Container exists:",!!t),t&&console.log("Container has x-data:",t.hasAttribute("x-data")),console.groupEnd()}window.debugFileManagerState=Qc;document.addEventListener("DOMContentLoaded",()=>{setTimeout(()=>{window.debugFileManagerState&&window.debugFileManagerState()},1e3)});window.debugFileManager=function(){console.group("File Manager Debug Information"),console.log("Alpine.js loaded:",typeof window.Alpine<"u");const t=document.querySelector("[data-lazy-container]");if(console.log("Container exists:",!!t),t&&(console.log("Container has x-data:",t.hasAttribute("x-data")),console.log("Container Alpine data stack:",t._x_dataStack),window.Alpine))try{const e=window.Alpine.$data(t);console.log("Alpine data:",e),console.log("Files count:",e.files?e.files.length:"N/A"),console.log("Filtered files count:",e.filteredFiles?e.filteredFiles.length:"N/A")}catch(e){console.error("Error accessing Alpine data:",e)}if(console.log("File Manager State:",window.fileManagerState),console.log("Already Initialized:",window.fileManagerAlreadyInitialized),window.FileManagerLazyLoader&&(console.log("Lazy Loader class exists"),window.fileManagerState&&window.fileManagerState.instance)){const e=window.fileManagerState.instance;console.log("Lazy Loader instance:",e),console.log("Cache stats:",e.getCacheStats?e.getCacheStats():"N/A")}return console.groupEnd(),`
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
    `};document.addEventListener("DOMContentLoaded",()=>{if(document.querySelector("[data-lazy-container]")){const e=document.createElement("button");e.textContent="Debug File Manager",e.className="debug-button hidden",e.style.position="fixed",e.style.bottom="10px",e.style.right="10px",e.style.zIndex="9999",e.style.padding="5px 10px",e.style.background="#f0f0f0",e.style.border="1px solid #ccc",e.style.borderRadius="4px",e.addEventListener("click",()=>{console.clear(),window.debugFileManager()}),document.body.appendChild(e),document.addEventListener("keydown",i=>{i.ctrlKey&&i.shiftKey&&i.key==="D"&&(e.classList.toggle("hidden"),i.preventDefault())})}});class Zc{constructor(){this.currentStep=null,this.progressBar=null,this.init()}init(){this.currentStep=this.getCurrentStep(),this.progressBar=document.querySelector("[data-progress-bar]"),this.initializeStepFunctionality(),this.initializeFormSubmission(),this.initializeProgressIndicator(),console.log("Setup Wizard initialized for step:",this.currentStep)}getCurrentStep(){const e=document.querySelector("[data-setup-step]");return e?e.dataset.setupStep:"welcome"}initializeStepFunctionality(){switch(this.currentStep){case"database":this.initializeDatabaseStep();break;case"admin":this.initializeAdminStep();break;case"storage":this.initializeStorageStep();break}}initializeDatabaseStep(){const e=document.getElementById("sqlite"),i=document.getElementById("mysql"),n=document.getElementById("sqlite-config"),r=document.getElementById("mysql-config"),s=document.getElementById("test-connection");if(!e||!i)return;const o=()=>{e.checked?(n==null||n.classList.remove("hidden"),r==null||r.classList.add("hidden"),this.updateFormValidation("sqlite")):(n==null||n.classList.add("hidden"),r==null||r.classList.remove("hidden"),this.updateFormValidation("mysql"))};e.addEventListener("change",o),i.addEventListener("change",o),o(),s&&s.addEventListener("click",()=>{this.testDatabaseConnection()}),this.initializeDatabaseValidation()}initializeAdminStep(){const e=document.getElementById("password"),i=document.getElementById("password_confirmation"),n=document.getElementById("email"),r=document.getElementById("toggle-password");!e||!i||!n||(r&&r.addEventListener("click",()=>{this.togglePasswordVisibility(e,r)}),e.addEventListener("input",()=>{this.checkPasswordStrength(e.value),this.validatePasswordMatch()}),i.addEventListener("input",()=>{this.validatePasswordMatch()}),n.addEventListener("blur",()=>{this.validateEmailAvailability(n.value)}),this.initializeAdminFormValidation())}initializeStorageStep(){const e=document.getElementById("toggle-secret"),i=document.getElementById("google_client_secret"),n=document.getElementById("test-google-connection"),r=document.getElementById("skip_storage"),s=document.getElementById("google-drive-config");e&&i&&e.addEventListener("click",()=>{this.togglePasswordVisibility(i,e)}),r&&s&&r.addEventListener("change",()=>{this.toggleStorageRequirements(r.checked,s)}),n&&n.addEventListener("click",()=>{this.testGoogleDriveConnection()}),this.initializeStorageValidation()}initializeFormSubmission(){document.querySelectorAll('form[id$="-form"]').forEach(i=>{i.addEventListener("submit",n=>{this.handleFormSubmission(i,n)})})}initializeProgressIndicator(){if(this.progressBar){const e=this.progressBar.style.width;this.animateProgressBar(e)}this.updateStepIndicators()}async testDatabaseConnection(){var r,s,o,a,l;const e=document.getElementById("test-connection"),i=document.getElementById("connection-status");if(!e||!i)return;const n=e.innerHTML;try{this.setButtonLoading(e,"Testing...");const c=new FormData;c.append("_token",this.getCsrfToken()),c.append("host",((r=document.getElementById("mysql_host"))==null?void 0:r.value)||""),c.append("port",((s=document.getElementById("mysql_port"))==null?void 0:s.value)||""),c.append("database",((o=document.getElementById("mysql_database"))==null?void 0:o.value)||""),c.append("username",((a=document.getElementById("mysql_username"))==null?void 0:a.value)||""),c.append("password",((l=document.getElementById("mysql_password"))==null?void 0:l.value)||"");const d=await(await fetch("/setup/ajax/test-database",{method:"POST",body:c,headers:{"X-Requested-With":"XMLHttpRequest"}})).json();i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(d.success,d.message)}catch(c){console.error("Database connection test failed:",c),i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(!1,"Connection test failed. Please try again.")}finally{this.restoreButtonState(e,n)}}async testGoogleDriveConnection(){var r,s;const e=document.getElementById("test-google-connection"),i=document.getElementById("google-connection-status");if(!e||!i)return;const n=e.innerHTML;try{this.setButtonLoading(e,"Testing...");const o=new FormData;o.append("_token",this.getCsrfToken()),o.append("client_id",((r=document.getElementById("google_client_id"))==null?void 0:r.value)||""),o.append("client_secret",((s=document.getElementById("google_client_secret"))==null?void 0:s.value)||"");const l=await(await fetch("/setup/ajax/test-storage",{method:"POST",body:o,headers:{"X-Requested-With":"XMLHttpRequest"}})).json();i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(l.success,l.message)}catch(o){console.error("Google Drive connection test failed:",o),i.classList.remove("hidden"),i.innerHTML=this.formatConnectionResult(!1,"Connection test failed. Please try again.")}finally{this.restoreButtonState(e,n)}}async validateEmailAvailability(e){if(!(!e||!this.isValidEmail(e)))try{const i=new FormData;i.append("_token",this.getCsrfToken()),i.append("email",e);const r=await(await fetch("/setup/ajax/validate-email",{method:"POST",body:i,headers:{"X-Requested-With":"XMLHttpRequest"}})).json();this.showEmailValidationResult(r.available,r.message)}catch(i){console.error("Email validation failed:",i)}}checkPasswordStrength(e){const i=document.getElementById("strength-bar"),n=document.getElementById("strength-text");if(!i||!n)return;const r=this.calculatePasswordScore(e);i.style.width=r+"%",r===0?(n.textContent="Enter password",n.className="font-medium text-gray-400",i.className="h-2 rounded-full transition-all duration-300 bg-gray-300"):r<50?(n.textContent="Weak",n.className="font-medium text-red-600",i.className="h-2 rounded-full transition-all duration-300 bg-red-500"):r<75?(n.textContent="Fair",n.className="font-medium text-yellow-600",i.className="h-2 rounded-full transition-all duration-300 bg-yellow-500"):r<100?(n.textContent="Good",n.className="font-medium text-blue-600",i.className="h-2 rounded-full transition-all duration-300 bg-blue-500"):(n.textContent="Strong",n.className="font-medium text-green-600",i.className="h-2 rounded-full transition-all duration-300 bg-green-500"),this.updatePasswordRequirements(e)}calculatePasswordScore(e){let i=0;return e.length>=8&&(i+=20),/[A-Z]/.test(e)&&(i+=20),/[a-z]/.test(e)&&(i+=20),/[0-9]/.test(e)&&(i+=20),/[^A-Za-z0-9]/.test(e)&&(i+=20),i}updatePasswordRequirements(e){[{id:"req-length",test:e.length>=8},{id:"req-uppercase",test:/[A-Z]/.test(e)},{id:"req-lowercase",test:/[a-z]/.test(e)},{id:"req-number",test:/[0-9]/.test(e)},{id:"req-special",test:/[^A-Za-z0-9]/.test(e)}].forEach(n=>{var s,o,a,l;const r=document.getElementById(n.id);r&&(n.test?(r.classList.remove("text-gray-600"),r.classList.add("text-green-600"),(s=r.querySelector("svg"))==null||s.classList.remove("text-gray-400"),(o=r.querySelector("svg"))==null||o.classList.add("text-green-500")):(r.classList.remove("text-green-600"),r.classList.add("text-gray-600"),(a=r.querySelector("svg"))==null||a.classList.remove("text-green-500"),(l=r.querySelector("svg"))==null||l.classList.add("text-gray-400")))})}validatePasswordMatch(){var a,l;const e=((a=document.getElementById("password"))==null?void 0:a.value)||"",i=((l=document.getElementById("password_confirmation"))==null?void 0:l.value)||"",n=document.getElementById("password-match-indicator"),r=document.getElementById("match-success"),s=document.getElementById("match-error"),o=document.getElementById("password-match-text");if(!(!n||!r||!s||!o)){if(i.length===0){n.classList.add("hidden"),o.textContent="Re-enter your password to confirm",o.className="mt-2 text-sm text-gray-500";return}n.classList.remove("hidden"),e===i?(r.classList.remove("hidden"),s.classList.add("hidden"),o.textContent="Passwords match",o.className="mt-2 text-sm text-green-600"):(r.classList.add("hidden"),s.classList.remove("hidden"),o.textContent="Passwords do not match",o.className="mt-2 text-sm text-red-600")}}togglePasswordVisibility(e,i){const n=e.getAttribute("type")==="password"?"text":"password";e.setAttribute("type",n);const r=i.querySelector('[id$="eye-closed"], [id$="-eye-closed"]'),s=i.querySelector('[id$="eye-open"], [id$="-eye-open"]');n==="text"?(r==null||r.classList.add("hidden"),s==null||s.classList.remove("hidden")):(r==null||r.classList.remove("hidden"),s==null||s.classList.add("hidden"))}toggleStorageRequirements(e,i){e?(i.style.opacity="0.5",i.style.pointerEvents="none",document.getElementById("google_client_id").required=!1,document.getElementById("google_client_secret").required=!1):(i.style.opacity="1",i.style.pointerEvents="auto",document.getElementById("google_client_id").required=!0,document.getElementById("google_client_secret").required=!0)}handleFormSubmission(e,i){const n=e.querySelector('button[type="submit"]');if(!n)return;const r=n.innerHTML;this.setButtonLoading(n,"Processing...");const s=e.querySelectorAll("input, select, textarea, button");s.forEach(o=>{o.disabled=!0}),setTimeout(()=>{s.forEach(o=>{o.disabled=!1}),this.restoreButtonState(n,r)},1e4)}initializeDatabaseValidation(){["mysql_host","mysql_port","mysql_database","mysql_username"].forEach(i=>{const n=document.getElementById(i);n&&n.addEventListener("blur",()=>{this.validateDatabaseField(i,n.value)})})}initializeAdminFormValidation(){const e=document.getElementById("email"),i=document.getElementById("password"),n=document.getElementById("password_confirmation"),r=document.getElementById("submit-btn");if(!e||!i||!n||!r)return;const s=()=>{const o=e.value,a=i.value,l=n.value,c=this.calculatePasswordScore(a),u=this.isValidEmail(o)&&c===100&&a===l&&l.length>0;r.disabled=!u};e.addEventListener("input",s),i.addEventListener("input",s),n.addEventListener("input",s),s()}initializeStorageValidation(){const e=document.getElementById("google_client_id"),i=document.getElementById("google_client_secret");e&&e.addEventListener("blur",()=>{this.validateGoogleClientId(e.value)}),i&&i.addEventListener("blur",()=>{this.validateGoogleClientSecret(i.value)})}validateDatabaseField(e,i){const n=document.getElementById(e);if(!n)return;let r=!0,s="";switch(e){case"mysql_host":r=i.length>0,s=r?"":"Host is required";break;case"mysql_port":r=/^\d+$/.test(i)&&parseInt(i)>0&&parseInt(i)<=65535,s=r?"":"Port must be a valid number between 1 and 65535";break;case"mysql_database":r=/^[a-zA-Z0-9_]+$/.test(i),s=r?"":"Database name can only contain letters, numbers, and underscores";break;case"mysql_username":r=i.length>0,s=r?"":"Username is required";break}this.showFieldValidation(n,r,s)}validateGoogleClientId(e){const i=document.getElementById("google_client_id");if(!i)return;const n=/^[0-9]+-[a-zA-Z0-9]+\.apps\.googleusercontent\.com$/.test(e),r=n?"":"Client ID should end with .apps.googleusercontent.com";this.showFieldValidation(i,n,r)}validateGoogleClientSecret(e){const i=document.getElementById("google_client_secret");if(!i)return;const n=/^GOCSPX-[a-zA-Z0-9_-]+$/.test(e),r=n?"":"Client Secret should start with GOCSPX-";this.showFieldValidation(i,n,r)}showFieldValidation(e,i,n){e.classList.remove("border-red-300","border-green-300");const r=e.parentNode.querySelector(".validation-message");if(r&&r.remove(),n){e.classList.add(i?"border-green-300":"border-red-300");const s=document.createElement("p");s.className=`mt-1 text-sm validation-message ${i?"text-green-600":"text-red-600"}`,s.textContent=n,e.parentNode.appendChild(s)}}showEmailValidationResult(e,i){const n=document.getElementById("email");n&&this.showFieldValidation(n,e,i)}updateFormValidation(e){["mysql_host","mysql_port","mysql_database","mysql_username"].forEach(n=>{const r=document.getElementById(n);r&&(r.required=e==="mysql")})}animateProgressBar(e){this.progressBar&&(this.progressBar.style.transition="width 0.5s ease-out",setTimeout(()=>{this.progressBar.style.width=e},100))}updateStepIndicators(){document.querySelectorAll("[data-step-indicator]").forEach(i=>{const n=i.dataset.stepIndicator,r=this.isStepCompleted(n),s=n===this.currentStep;r&&i.classList.add("completed"),s&&i.classList.add("current")})}isStepCompleted(e){if(!this.currentStep||!e)return!1;const i=["welcome","database","admin","storage","complete"],n=i.indexOf(this.currentStep),r=i.indexOf(e);return n===-1||r===-1?!1:r<n}setButtonLoading(e,i){e.disabled=!0,e.innerHTML=`
            <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            ${i}
        `}restoreButtonState(e,i){e.disabled=!1,e.innerHTML=i}formatConnectionResult(e,i){return`
            <div class="${e?"text-green-600":"text-red-600"}">
                <div class="flex items-center">
                    ${e?`<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>`:`<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>`}
                    ${e?"Connection successful!":"Connection failed"}
                </div>
                ${i?`<p class="text-sm mt-1">${i}</p>`:""}
            </div>
        `}getCsrfToken(){const e=document.querySelector('meta[name="csrf-token"]');return e?e.getAttribute("content"):""}isValidEmail(e){return/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e)}}document.addEventListener("DOMContentLoaded",function(){if(document.querySelector("[data-setup-step]")&&window.location.pathname.startsWith("/setup"))try{new Zc}catch(e){console.warn("Setup wizard initialization failed:",e)}});/**
 * @license
 * Copyright 2019 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const wi=globalThis,rr=wi.ShadowRoot&&(wi.ShadyCSS===void 0||wi.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,sr=Symbol(),is=new WeakMap;let Ro=class{constructor(e,i,n){if(this._$cssResult$=!0,n!==sr)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=e,this.t=i}get styleSheet(){let e=this.o;const i=this.t;if(rr&&e===void 0){const n=i!==void 0&&i.length===1;n&&(e=is.get(i)),e===void 0&&((this.o=e=new CSSStyleSheet).replaceSync(this.cssText),n&&is.set(i,e))}return e}toString(){return this.cssText}};const tu=t=>new Ro(typeof t=="string"?t:t+"",void 0,sr),ht=(t,...e)=>{const i=t.length===1?t[0]:e.reduce((n,r,s)=>n+(o=>{if(o._$cssResult$===!0)return o.cssText;if(typeof o=="number")return o;throw Error("Value passed to 'css' function must be a 'css' function result: "+o+". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.")})(r)+t[s+1],t[0]);return new Ro(i,t,sr)},eu=(t,e)=>{if(rr)t.adoptedStyleSheets=e.map(i=>i instanceof CSSStyleSheet?i:i.styleSheet);else for(const i of e){const n=document.createElement("style"),r=wi.litNonce;r!==void 0&&n.setAttribute("nonce",r),n.textContent=i.cssText,t.appendChild(n)}},ns=rr?t=>t:t=>t instanceof CSSStyleSheet?(e=>{let i="";for(const n of e.cssRules)i+=n.cssText;return tu(i)})(t):t;/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const{is:iu,defineProperty:nu,getOwnPropertyDescriptor:ru,getOwnPropertyNames:su,getOwnPropertySymbols:ou,getPrototypeOf:au}=Object,Pt=globalThis,rs=Pt.trustedTypes,lu=rs?rs.emptyScript:"",rn=Pt.reactiveElementPolyfillSupport,Ue=(t,e)=>t,fe={toAttribute(t,e){switch(e){case Boolean:t=t?lu:null;break;case Object:case Array:t=t==null?t:JSON.stringify(t)}return t},fromAttribute(t,e){let i=t;switch(e){case Boolean:i=t!==null;break;case Number:i=t===null?null:Number(t);break;case Object:case Array:try{i=JSON.parse(t)}catch{i=null}}return i}},or=(t,e)=>!iu(t,e),ss={attribute:!0,type:String,converter:fe,reflect:!1,useDefault:!1,hasChanged:or};Symbol.metadata??(Symbol.metadata=Symbol("metadata")),Pt.litPropertyMetadata??(Pt.litPropertyMetadata=new WeakMap);let de=class extends HTMLElement{static addInitializer(e){this._$Ei(),(this.l??(this.l=[])).push(e)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(e,i=ss){if(i.state&&(i.attribute=!1),this._$Ei(),this.prototype.hasOwnProperty(e)&&((i=Object.create(i)).wrapped=!0),this.elementProperties.set(e,i),!i.noAccessor){const n=Symbol(),r=this.getPropertyDescriptor(e,n,i);r!==void 0&&nu(this.prototype,e,r)}}static getPropertyDescriptor(e,i,n){const{get:r,set:s}=ru(this.prototype,e)??{get(){return this[i]},set(o){this[i]=o}};return{get:r,set(o){const a=r==null?void 0:r.call(this);s==null||s.call(this,o),this.requestUpdate(e,a,n)},configurable:!0,enumerable:!0}}static getPropertyOptions(e){return this.elementProperties.get(e)??ss}static _$Ei(){if(this.hasOwnProperty(Ue("elementProperties")))return;const e=au(this);e.finalize(),e.l!==void 0&&(this.l=[...e.l]),this.elementProperties=new Map(e.elementProperties)}static finalize(){if(this.hasOwnProperty(Ue("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(Ue("properties"))){const i=this.properties,n=[...su(i),...ou(i)];for(const r of n)this.createProperty(r,i[r])}const e=this[Symbol.metadata];if(e!==null){const i=litPropertyMetadata.get(e);if(i!==void 0)for(const[n,r]of i)this.elementProperties.set(n,r)}this._$Eh=new Map;for(const[i,n]of this.elementProperties){const r=this._$Eu(i,n);r!==void 0&&this._$Eh.set(r,i)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(e){const i=[];if(Array.isArray(e)){const n=new Set(e.flat(1/0).reverse());for(const r of n)i.unshift(ns(r))}else e!==void 0&&i.push(ns(e));return i}static _$Eu(e,i){const n=i.attribute;return n===!1?void 0:typeof n=="string"?n:typeof e=="string"?e.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){var e;this._$ES=new Promise(i=>this.enableUpdating=i),this._$AL=new Map,this._$E_(),this.requestUpdate(),(e=this.constructor.l)==null||e.forEach(i=>i(this))}addController(e){var i;(this._$EO??(this._$EO=new Set)).add(e),this.renderRoot!==void 0&&this.isConnected&&((i=e.hostConnected)==null||i.call(e))}removeController(e){var i;(i=this._$EO)==null||i.delete(e)}_$E_(){const e=new Map,i=this.constructor.elementProperties;for(const n of i.keys())this.hasOwnProperty(n)&&(e.set(n,this[n]),delete this[n]);e.size>0&&(this._$Ep=e)}createRenderRoot(){const e=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return eu(e,this.constructor.elementStyles),e}connectedCallback(){var e;this.renderRoot??(this.renderRoot=this.createRenderRoot()),this.enableUpdating(!0),(e=this._$EO)==null||e.forEach(i=>{var n;return(n=i.hostConnected)==null?void 0:n.call(i)})}enableUpdating(e){}disconnectedCallback(){var e;(e=this._$EO)==null||e.forEach(i=>{var n;return(n=i.hostDisconnected)==null?void 0:n.call(i)})}attributeChangedCallback(e,i,n){this._$AK(e,n)}_$ET(e,i){var s;const n=this.constructor.elementProperties.get(e),r=this.constructor._$Eu(e,n);if(r!==void 0&&n.reflect===!0){const o=(((s=n.converter)==null?void 0:s.toAttribute)!==void 0?n.converter:fe).toAttribute(i,n.type);this._$Em=e,o==null?this.removeAttribute(r):this.setAttribute(r,o),this._$Em=null}}_$AK(e,i){var s,o;const n=this.constructor,r=n._$Eh.get(e);if(r!==void 0&&this._$Em!==r){const a=n.getPropertyOptions(r),l=typeof a.converter=="function"?{fromAttribute:a.converter}:((s=a.converter)==null?void 0:s.fromAttribute)!==void 0?a.converter:fe;this._$Em=r,this[r]=l.fromAttribute(i,a.type)??((o=this._$Ej)==null?void 0:o.get(r))??null,this._$Em=null}}requestUpdate(e,i,n){var r;if(e!==void 0){const s=this.constructor,o=this[e];if(n??(n=s.getPropertyOptions(e)),!((n.hasChanged??or)(o,i)||n.useDefault&&n.reflect&&o===((r=this._$Ej)==null?void 0:r.get(e))&&!this.hasAttribute(s._$Eu(e,n))))return;this.C(e,i,n)}this.isUpdatePending===!1&&(this._$ES=this._$EP())}C(e,i,{useDefault:n,reflect:r,wrapped:s},o){n&&!(this._$Ej??(this._$Ej=new Map)).has(e)&&(this._$Ej.set(e,o??i??this[e]),s!==!0||o!==void 0)||(this._$AL.has(e)||(this.hasUpdated||n||(i=void 0),this._$AL.set(e,i)),r===!0&&this._$Em!==e&&(this._$Eq??(this._$Eq=new Set)).add(e))}async _$EP(){this.isUpdatePending=!0;try{await this._$ES}catch(i){Promise.reject(i)}const e=this.scheduleUpdate();return e!=null&&await e,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){var n;if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??(this.renderRoot=this.createRenderRoot()),this._$Ep){for(const[s,o]of this._$Ep)this[s]=o;this._$Ep=void 0}const r=this.constructor.elementProperties;if(r.size>0)for(const[s,o]of r){const{wrapped:a}=o,l=this[s];a!==!0||this._$AL.has(s)||l===void 0||this.C(s,void 0,o,l)}}let e=!1;const i=this._$AL;try{e=this.shouldUpdate(i),e?(this.willUpdate(i),(n=this._$EO)==null||n.forEach(r=>{var s;return(s=r.hostUpdate)==null?void 0:s.call(r)}),this.update(i)):this._$EM()}catch(r){throw e=!1,this._$EM(),r}e&&this._$AE(i)}willUpdate(e){}_$AE(e){var i;(i=this._$EO)==null||i.forEach(n=>{var r;return(r=n.hostUpdated)==null?void 0:r.call(n)}),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(e)),this.updated(e)}_$EM(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(e){return!0}update(e){this._$Eq&&(this._$Eq=this._$Eq.forEach(i=>this._$ET(i,this[i]))),this._$EM()}updated(e){}firstUpdated(e){}};de.elementStyles=[],de.shadowRootOptions={mode:"open"},de[Ue("elementProperties")]=new Map,de[Ue("finalized")]=new Map,rn==null||rn({ReactiveElement:de}),(Pt.reactiveElementVersions??(Pt.reactiveElementVersions=[])).push("2.1.0");/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const He=globalThis,Ai=He.trustedTypes,os=Ai?Ai.createPolicy("lit-html",{createHTML:t=>t}):void 0,To="$lit$",Lt=`lit$${Math.random().toFixed(9).slice(2)}$`,Fo="?"+Lt,cu=`<${Fo}>`,re=document,Ke=()=>re.createComment(""),Ge=t=>t===null||typeof t!="object"&&typeof t!="function",ar=Array.isArray,uu=t=>ar(t)||typeof(t==null?void 0:t[Symbol.iterator])=="function",sn=`[ 	
\f\r]`,Fe=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,as=/-->/g,ls=/>/g,Vt=RegExp(`>|${sn}(?:([^\\s"'>=/]+)(${sn}*=${sn}*(?:[^ 	
\f\r"'\`<>=]|("|')|))|$)`,"g"),cs=/'/g,us=/"/g,Oo=/^(?:script|style|textarea|title)$/i,du=t=>(e,...i)=>({_$litType$:t,strings:e,values:i}),I=du(1),ot=Symbol.for("lit-noChange"),D=Symbol.for("lit-nothing"),ds=new WeakMap,Gt=re.createTreeWalker(re,129);function Mo(t,e){if(!ar(t)||!t.hasOwnProperty("raw"))throw Error("invalid template strings array");return os!==void 0?os.createHTML(e):e}const hu=(t,e)=>{const i=t.length-1,n=[];let r,s=e===2?"<svg>":e===3?"<math>":"",o=Fe;for(let a=0;a<i;a++){const l=t[a];let c,u,d=-1,p=0;for(;p<l.length&&(o.lastIndex=p,u=o.exec(l),u!==null);)p=o.lastIndex,o===Fe?u[1]==="!--"?o=as:u[1]!==void 0?o=ls:u[2]!==void 0?(Oo.test(u[2])&&(r=RegExp("</"+u[2],"g")),o=Vt):u[3]!==void 0&&(o=Vt):o===Vt?u[0]===">"?(o=r??Fe,d=-1):u[1]===void 0?d=-2:(d=o.lastIndex-u[2].length,c=u[1],o=u[3]===void 0?Vt:u[3]==='"'?us:cs):o===us||o===cs?o=Vt:o===as||o===ls?o=Fe:(o=Vt,r=void 0);const f=o===Vt&&t[a+1].startsWith("/>")?" ":"";s+=o===Fe?l+cu:d>=0?(n.push(c),l.slice(0,d)+To+l.slice(d)+Lt+f):l+Lt+(d===-2?a:f)}return[Mo(t,s+(t[i]||"<?>")+(e===2?"</svg>":e===3?"</math>":"")),n]};class Xe{constructor({strings:e,_$litType$:i},n){let r;this.parts=[];let s=0,o=0;const a=e.length-1,l=this.parts,[c,u]=hu(e,i);if(this.el=Xe.createElement(c,n),Gt.currentNode=this.el.content,i===2||i===3){const d=this.el.content.firstChild;d.replaceWith(...d.childNodes)}for(;(r=Gt.nextNode())!==null&&l.length<a;){if(r.nodeType===1){if(r.hasAttributes())for(const d of r.getAttributeNames())if(d.endsWith(To)){const p=u[o++],f=r.getAttribute(d).split(Lt),b=/([.?@])?(.*)/.exec(p);l.push({type:1,index:s,name:b[2],strings:f,ctor:b[1]==="."?fu:b[1]==="?"?gu:b[1]==="@"?mu:Ui}),r.removeAttribute(d)}else d.startsWith(Lt)&&(l.push({type:6,index:s}),r.removeAttribute(d));if(Oo.test(r.tagName)){const d=r.textContent.split(Lt),p=d.length-1;if(p>0){r.textContent=Ai?Ai.emptyScript:"";for(let f=0;f<p;f++)r.append(d[f],Ke()),Gt.nextNode(),l.push({type:2,index:++s});r.append(d[p],Ke())}}}else if(r.nodeType===8)if(r.data===Fo)l.push({type:2,index:s});else{let d=-1;for(;(d=r.data.indexOf(Lt,d+1))!==-1;)l.push({type:7,index:s}),d+=Lt.length-1}s++}}static createElement(e,i){const n=re.createElement("template");return n.innerHTML=e,n}}function ge(t,e,i=t,n){var o,a;if(e===ot)return e;let r=n!==void 0?(o=i._$Co)==null?void 0:o[n]:i._$Cl;const s=Ge(e)?void 0:e._$litDirective$;return(r==null?void 0:r.constructor)!==s&&((a=r==null?void 0:r._$AO)==null||a.call(r,!1),s===void 0?r=void 0:(r=new s(t),r._$AT(t,i,n)),n!==void 0?(i._$Co??(i._$Co=[]))[n]=r:i._$Cl=r),r!==void 0&&(e=ge(t,r._$AS(t,e.values),r,n)),e}class pu{constructor(e,i){this._$AV=[],this._$AN=void 0,this._$AD=e,this._$AM=i}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(e){const{el:{content:i},parts:n}=this._$AD,r=((e==null?void 0:e.creationScope)??re).importNode(i,!0);Gt.currentNode=r;let s=Gt.nextNode(),o=0,a=0,l=n[0];for(;l!==void 0;){if(o===l.index){let c;l.type===2?c=new ti(s,s.nextSibling,this,e):l.type===1?c=new l.ctor(s,l.name,l.strings,this,e):l.type===6&&(c=new bu(s,this,e)),this._$AV.push(c),l=n[++a]}o!==(l==null?void 0:l.index)&&(s=Gt.nextNode(),o++)}return Gt.currentNode=re,r}p(e){let i=0;for(const n of this._$AV)n!==void 0&&(n.strings!==void 0?(n._$AI(e,n,i),i+=n.strings.length-2):n._$AI(e[i])),i++}}class ti{get _$AU(){var e;return((e=this._$AM)==null?void 0:e._$AU)??this._$Cv}constructor(e,i,n,r){this.type=2,this._$AH=D,this._$AN=void 0,this._$AA=e,this._$AB=i,this._$AM=n,this.options=r,this._$Cv=(r==null?void 0:r.isConnected)??!0}get parentNode(){let e=this._$AA.parentNode;const i=this._$AM;return i!==void 0&&(e==null?void 0:e.nodeType)===11&&(e=i.parentNode),e}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(e,i=this){e=ge(this,e,i),Ge(e)?e===D||e==null||e===""?(this._$AH!==D&&this._$AR(),this._$AH=D):e!==this._$AH&&e!==ot&&this._(e):e._$litType$!==void 0?this.$(e):e.nodeType!==void 0?this.T(e):uu(e)?this.k(e):this._(e)}O(e){return this._$AA.parentNode.insertBefore(e,this._$AB)}T(e){this._$AH!==e&&(this._$AR(),this._$AH=this.O(e))}_(e){this._$AH!==D&&Ge(this._$AH)?this._$AA.nextSibling.data=e:this.T(re.createTextNode(e)),this._$AH=e}$(e){var s;const{values:i,_$litType$:n}=e,r=typeof n=="number"?this._$AC(e):(n.el===void 0&&(n.el=Xe.createElement(Mo(n.h,n.h[0]),this.options)),n);if(((s=this._$AH)==null?void 0:s._$AD)===r)this._$AH.p(i);else{const o=new pu(r,this),a=o.u(this.options);o.p(i),this.T(a),this._$AH=o}}_$AC(e){let i=ds.get(e.strings);return i===void 0&&ds.set(e.strings,i=new Xe(e)),i}k(e){ar(this._$AH)||(this._$AH=[],this._$AR());const i=this._$AH;let n,r=0;for(const s of e)r===i.length?i.push(n=new ti(this.O(Ke()),this.O(Ke()),this,this.options)):n=i[r],n._$AI(s),r++;r<i.length&&(this._$AR(n&&n._$AB.nextSibling,r),i.length=r)}_$AR(e=this._$AA.nextSibling,i){var n;for((n=this._$AP)==null?void 0:n.call(this,!1,!0,i);e&&e!==this._$AB;){const r=e.nextSibling;e.remove(),e=r}}setConnected(e){var i;this._$AM===void 0&&(this._$Cv=e,(i=this._$AP)==null||i.call(this,e))}}class Ui{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(e,i,n,r,s){this.type=1,this._$AH=D,this._$AN=void 0,this.element=e,this.name=i,this._$AM=r,this.options=s,n.length>2||n[0]!==""||n[1]!==""?(this._$AH=Array(n.length-1).fill(new String),this.strings=n):this._$AH=D}_$AI(e,i=this,n,r){const s=this.strings;let o=!1;if(s===void 0)e=ge(this,e,i,0),o=!Ge(e)||e!==this._$AH&&e!==ot,o&&(this._$AH=e);else{const a=e;let l,c;for(e=s[0],l=0;l<s.length-1;l++)c=ge(this,a[n+l],i,l),c===ot&&(c=this._$AH[l]),o||(o=!Ge(c)||c!==this._$AH[l]),c===D?e=D:e!==D&&(e+=(c??"")+s[l+1]),this._$AH[l]=c}o&&!r&&this.j(e)}j(e){e===D?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,e??"")}}class fu extends Ui{constructor(){super(...arguments),this.type=3}j(e){this.element[this.name]=e===D?void 0:e}}class gu extends Ui{constructor(){super(...arguments),this.type=4}j(e){this.element.toggleAttribute(this.name,!!e&&e!==D)}}class mu extends Ui{constructor(e,i,n,r,s){super(e,i,n,r,s),this.type=5}_$AI(e,i=this){if((e=ge(this,e,i,0)??D)===ot)return;const n=this._$AH,r=e===D&&n!==D||e.capture!==n.capture||e.once!==n.once||e.passive!==n.passive,s=e!==D&&(n===D||r);r&&this.element.removeEventListener(this.name,this,n),s&&this.element.addEventListener(this.name,this,e),this._$AH=e}handleEvent(e){var i;typeof this._$AH=="function"?this._$AH.call(((i=this.options)==null?void 0:i.host)??this.element,e):this._$AH.handleEvent(e)}}class bu{constructor(e,i,n){this.element=e,this.type=6,this._$AN=void 0,this._$AM=i,this.options=n}get _$AU(){return this._$AM._$AU}_$AI(e){ge(this,e)}}const on=He.litHtmlPolyfillSupport;on==null||on(Xe,ti),(He.litHtmlVersions??(He.litHtmlVersions=[])).push("3.3.0");const yu=(t,e,i)=>{const n=(i==null?void 0:i.renderBefore)??e;let r=n._$litPart$;if(r===void 0){const s=(i==null?void 0:i.renderBefore)??null;n._$litPart$=r=new ti(e.insertBefore(Ke(),s),s,void 0,i??{})}return r._$AI(t),r};/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Qt=globalThis;let Ve=class extends de{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){var i;const e=super.createRenderRoot();return(i=this.renderOptions).renderBefore??(i.renderBefore=e.firstChild),e}update(e){const i=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(e),this._$Do=yu(i,this.renderRoot,this.renderOptions)}connectedCallback(){var e;super.connectedCallback(),(e=this._$Do)==null||e.setConnected(!0)}disconnectedCallback(){var e;super.disconnectedCallback(),(e=this._$Do)==null||e.setConnected(!1)}render(){return ot}};var no;Ve._$litElement$=!0,Ve.finalized=!0,(no=Qt.litElementHydrateSupport)==null||no.call(Qt,{LitElement:Ve});const an=Qt.litElementPolyfillSupport;an==null||an({LitElement:Ve});(Qt.litElementVersions??(Qt.litElementVersions=[])).push("4.2.0");var vu=ht`
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
`,kt=ht`
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
`,Lo=Object.defineProperty,wu=Object.defineProperties,_u=Object.getOwnPropertyDescriptor,xu=Object.getOwnPropertyDescriptors,hs=Object.getOwnPropertySymbols,Eu=Object.prototype.hasOwnProperty,Su=Object.prototype.propertyIsEnumerable,ln=(t,e)=>(e=Symbol[t])?e:Symbol.for("Symbol."+t),lr=t=>{throw TypeError(t)},ps=(t,e,i)=>e in t?Lo(t,e,{enumerable:!0,configurable:!0,writable:!0,value:i}):t[e]=i,le=(t,e)=>{for(var i in e||(e={}))Eu.call(e,i)&&ps(t,i,e[i]);if(hs)for(var i of hs(e))Su.call(e,i)&&ps(t,i,e[i]);return t},Hi=(t,e)=>wu(t,xu(e)),h=(t,e,i,n)=>{for(var r=n>1?void 0:n?_u(e,i):e,s=t.length-1,o;s>=0;s--)(o=t[s])&&(r=(n?o(e,i,r):o(r))||r);return n&&r&&Lo(e,i,r),r},zo=(t,e,i)=>e.has(t)||lr("Cannot "+i),Cu=(t,e,i)=>(zo(t,e,"read from private field"),e.get(t)),ku=(t,e,i)=>e.has(t)?lr("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(t):e.set(t,i),Au=(t,e,i,n)=>(zo(t,e,"write to private field"),e.set(t,i),i),$u=function(t,e){this[0]=t,this[1]=e},Ru=t=>{var e=t[ln("asyncIterator")],i=!1,n,r={};return e==null?(e=t[ln("iterator")](),n=s=>r[s]=o=>e[s](o)):(e=e.call(t),n=s=>r[s]=o=>{if(i){if(i=!1,s==="throw")throw o;return o}return i=!0,{done:!1,value:new $u(new Promise(a=>{var l=e[s](o);l instanceof Object||lr("Object expected"),a(l)}),1)}}),r[ln("iterator")]=()=>r,n("next"),"throw"in e?n("throw"):r.throw=s=>{throw s},"return"in e&&n("return"),r};/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Tu={attribute:!0,type:String,converter:fe,reflect:!1,hasChanged:or},Fu=(t=Tu,e,i)=>{const{kind:n,metadata:r}=i;let s=globalThis.litPropertyMetadata.get(r);if(s===void 0&&globalThis.litPropertyMetadata.set(r,s=new Map),n==="setter"&&((t=Object.create(t)).wrapped=!0),s.set(i.name,t),n==="accessor"){const{name:o}=i;return{set(a){const l=e.get.call(this);e.set.call(this,a),this.requestUpdate(o,l,t)},init(a){return a!==void 0&&this.C(o,void 0,t,a),a}}}if(n==="setter"){const{name:o}=i;return function(a){const l=this[o];e.call(this,a),this.requestUpdate(o,l,t)}}throw Error("Unsupported decorator location: "+n)};function y(t){return(e,i)=>typeof i=="object"?Fu(t,e,i):((n,r,s)=>{const o=r.hasOwnProperty(s);return r.constructor.createProperty(s,n),o?Object.getOwnPropertyDescriptor(r,s):void 0})(t,e,i)}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function et(t){return y({...t,state:!0,attribute:!1})}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function Ou(t){return(e,i)=>{const n=typeof e=="function"?e:e[i];Object.assign(n,t)}}/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Mu=(t,e,i)=>(i.configurable=!0,i.enumerable=!0,Reflect.decorate&&typeof e!="object"&&Object.defineProperty(t,e,i),i);/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */function it(t,e){return(i,n,r)=>{const s=o=>{var a;return((a=o.renderRoot)==null?void 0:a.querySelector(t))??null};return Mu(i,n,{get(){return s(this)}})}}var _i,nt=class extends Ve{constructor(){super(),ku(this,_i,!1),this.initialReflectedProperties=new Map,Object.entries(this.constructor.dependencies).forEach(([t,e])=>{this.constructor.define(t,e)})}emit(t,e){const i=new CustomEvent(t,le({bubbles:!0,cancelable:!1,composed:!0,detail:{}},e));return this.dispatchEvent(i),i}static define(t,e=this,i={}){const n=customElements.get(t);if(!n){try{customElements.define(t,e,i)}catch{customElements.define(t,class extends e{},i)}return}let r=" (unknown version)",s=r;"version"in e&&e.version&&(r=" v"+e.version),"version"in n&&n.version&&(s=" v"+n.version),!(r&&s&&r===s)&&console.warn(`Attempted to register <${t}>${r}, but <${t}>${s} has already been registered.`)}attributeChangedCallback(t,e,i){Cu(this,_i)||(this.constructor.elementProperties.forEach((n,r)=>{n.reflect&&this[r]!=null&&this.initialReflectedProperties.set(r,this[r])}),Au(this,_i,!0)),super.attributeChangedCallback(t,e,i)}willUpdate(t){super.willUpdate(t),this.initialReflectedProperties.forEach((e,i)=>{t.has(i)&&this[i]==null&&(this[i]=e)})}};_i=new WeakMap;nt.version="2.20.1";nt.dependencies={};h([y()],nt.prototype,"dir",2);h([y()],nt.prototype,"lang",2);var Po=class extends nt{render(){return I` <slot></slot> `}};Po.styles=[kt,vu];var Lu=ht`
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
`,Io=(t="value")=>(e,i)=>{const n=e.constructor,r=n.prototype.attributeChangedCallback;n.prototype.attributeChangedCallback=function(s,o,a){var l;const c=n.getPropertyOptions(t),u=typeof c.attribute=="string"?c.attribute:t;if(s===u){const d=c.converter||fe,f=(typeof d=="function"?d:(l=d==null?void 0:d.fromAttribute)!=null?l:fe.fromAttribute)(a,c.type);this[t]!==f&&(this[i]=f)}r.call(this,s,o,a)}},zu=ht`
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
`,Oe=new WeakMap,Me=new WeakMap,Le=new WeakMap,cn=new WeakSet,oi=new WeakMap,cr=class{constructor(t,e){this.handleFormData=i=>{const n=this.options.disabled(this.host),r=this.options.name(this.host),s=this.options.value(this.host),o=this.host.tagName.toLowerCase()==="sl-button";this.host.isConnected&&!n&&!o&&typeof r=="string"&&r.length>0&&typeof s<"u"&&(Array.isArray(s)?s.forEach(a=>{i.formData.append(r,a.toString())}):i.formData.append(r,s.toString()))},this.handleFormSubmit=i=>{var n;const r=this.options.disabled(this.host),s=this.options.reportValidity;this.form&&!this.form.noValidate&&((n=Oe.get(this.form))==null||n.forEach(o=>{this.setUserInteracted(o,!0)})),this.form&&!this.form.noValidate&&!r&&!s(this.host)&&(i.preventDefault(),i.stopImmediatePropagation())},this.handleFormReset=()=>{this.options.setValue(this.host,this.options.defaultValue(this.host)),this.setUserInteracted(this.host,!1),oi.set(this.host,[])},this.handleInteraction=i=>{const n=oi.get(this.host);n.includes(i.type)||n.push(i.type),n.length===this.options.assumeInteractionOn.length&&this.setUserInteracted(this.host,!0)},this.checkFormValidity=()=>{if(this.form&&!this.form.noValidate){const i=this.form.querySelectorAll("*");for(const n of i)if(typeof n.checkValidity=="function"&&!n.checkValidity())return!1}return!0},this.reportFormValidity=()=>{if(this.form&&!this.form.noValidate){const i=this.form.querySelectorAll("*");for(const n of i)if(typeof n.reportValidity=="function"&&!n.reportValidity())return!1}return!0},(this.host=t).addController(this),this.options=le({form:i=>{const n=i.form;if(n){const s=i.getRootNode().querySelector(`#${n}`);if(s)return s}return i.closest("form")},name:i=>i.name,value:i=>i.value,defaultValue:i=>i.defaultValue,disabled:i=>{var n;return(n=i.disabled)!=null?n:!1},reportValidity:i=>typeof i.reportValidity=="function"?i.reportValidity():!0,checkValidity:i=>typeof i.checkValidity=="function"?i.checkValidity():!0,setValue:(i,n)=>i.value=n,assumeInteractionOn:["sl-input"]},e)}hostConnected(){const t=this.options.form(this.host);t&&this.attachForm(t),oi.set(this.host,[]),this.options.assumeInteractionOn.forEach(e=>{this.host.addEventListener(e,this.handleInteraction)})}hostDisconnected(){this.detachForm(),oi.delete(this.host),this.options.assumeInteractionOn.forEach(t=>{this.host.removeEventListener(t,this.handleInteraction)})}hostUpdated(){const t=this.options.form(this.host);t||this.detachForm(),t&&this.form!==t&&(this.detachForm(),this.attachForm(t)),this.host.hasUpdated&&this.setValidity(this.host.validity.valid)}attachForm(t){t?(this.form=t,Oe.has(this.form)?Oe.get(this.form).add(this.host):Oe.set(this.form,new Set([this.host])),this.form.addEventListener("formdata",this.handleFormData),this.form.addEventListener("submit",this.handleFormSubmit),this.form.addEventListener("reset",this.handleFormReset),Me.has(this.form)||(Me.set(this.form,this.form.reportValidity),this.form.reportValidity=()=>this.reportFormValidity()),Le.has(this.form)||(Le.set(this.form,this.form.checkValidity),this.form.checkValidity=()=>this.checkFormValidity())):this.form=void 0}detachForm(){if(!this.form)return;const t=Oe.get(this.form);t&&(t.delete(this.host),t.size<=0&&(this.form.removeEventListener("formdata",this.handleFormData),this.form.removeEventListener("submit",this.handleFormSubmit),this.form.removeEventListener("reset",this.handleFormReset),Me.has(this.form)&&(this.form.reportValidity=Me.get(this.form),Me.delete(this.form)),Le.has(this.form)&&(this.form.checkValidity=Le.get(this.form),Le.delete(this.form)),this.form=void 0))}setUserInteracted(t,e){e?cn.add(t):cn.delete(t),t.requestUpdate()}doAction(t,e){if(this.form){const i=document.createElement("button");i.type=t,i.style.position="absolute",i.style.width="0",i.style.height="0",i.style.clipPath="inset(50%)",i.style.overflow="hidden",i.style.whiteSpace="nowrap",e&&(i.name=e.name,i.value=e.value,["formaction","formenctype","formmethod","formnovalidate","formtarget"].forEach(n=>{e.hasAttribute(n)&&i.setAttribute(n,e.getAttribute(n))})),this.form.append(i),i.click(),i.remove()}}getForm(){var t;return(t=this.form)!=null?t:null}reset(t){this.doAction("reset",t)}submit(t){this.doAction("submit",t)}setValidity(t){const e=this.host,i=!!cn.has(e),n=!!e.required;e.toggleAttribute("data-required",n),e.toggleAttribute("data-optional",!n),e.toggleAttribute("data-invalid",!t),e.toggleAttribute("data-valid",t),e.toggleAttribute("data-user-invalid",!t&&i),e.toggleAttribute("data-user-valid",t&&i)}updateValidity(){const t=this.host;this.setValidity(t.validity.valid)}emitInvalidEvent(t){const e=new CustomEvent("sl-invalid",{bubbles:!1,composed:!1,cancelable:!0,detail:{}});t||e.preventDefault(),this.host.dispatchEvent(e)||t==null||t.preventDefault()}},ur=Object.freeze({badInput:!1,customError:!1,patternMismatch:!1,rangeOverflow:!1,rangeUnderflow:!1,stepMismatch:!1,tooLong:!1,tooShort:!1,typeMismatch:!1,valid:!0,valueMissing:!1});Object.freeze(Hi(le({},ur),{valid:!1,valueMissing:!0}));Object.freeze(Hi(le({},ur),{valid:!1,customError:!0}));var Bo=class{constructor(t,...e){this.slotNames=[],this.handleSlotChange=i=>{const n=i.target;(this.slotNames.includes("[default]")&&!n.name||n.name&&this.slotNames.includes(n.name))&&this.host.requestUpdate()},(this.host=t).addController(this),this.slotNames=e}hasDefaultSlot(){return[...this.host.childNodes].some(t=>{if(t.nodeType===t.TEXT_NODE&&t.textContent.trim()!=="")return!0;if(t.nodeType===t.ELEMENT_NODE){const e=t;if(e.tagName.toLowerCase()==="sl-visually-hidden")return!1;if(!e.hasAttribute("slot"))return!0}return!1})}hasNamedSlot(t){return this.host.querySelector(`:scope > [slot="${t}"]`)!==null}test(t){return t==="[default]"?this.hasDefaultSlot():this.hasNamedSlot(t)}hostConnected(){this.host.shadowRoot.addEventListener("slotchange",this.handleSlotChange)}hostDisconnected(){this.host.shadowRoot.removeEventListener("slotchange",this.handleSlotChange)}};const Fn=new Set,he=new Map;let Wt,dr="ltr",hr="en";const Do=typeof MutationObserver<"u"&&typeof document<"u"&&typeof document.documentElement<"u";if(Do){const t=new MutationObserver(Uo);dr=document.documentElement.dir||"ltr",hr=document.documentElement.lang||navigator.language,t.observe(document.documentElement,{attributes:!0,attributeFilter:["dir","lang"]})}function No(...t){t.map(e=>{const i=e.$code.toLowerCase();he.has(i)?he.set(i,Object.assign(Object.assign({},he.get(i)),e)):he.set(i,e),Wt||(Wt=e)}),Uo()}function Uo(){Do&&(dr=document.documentElement.dir||"ltr",hr=document.documentElement.lang||navigator.language),[...Fn.keys()].map(t=>{typeof t.requestUpdate=="function"&&t.requestUpdate()})}let Pu=class{constructor(e){this.host=e,this.host.addController(this)}hostConnected(){Fn.add(this.host)}hostDisconnected(){Fn.delete(this.host)}dir(){return`${this.host.dir||dr}`.toLowerCase()}lang(){return`${this.host.lang||hr}`.toLowerCase()}getTranslationData(e){var i,n;const r=new Intl.Locale(e.replace(/_/g,"-")),s=r==null?void 0:r.language.toLowerCase(),o=(n=(i=r==null?void 0:r.region)===null||i===void 0?void 0:i.toLowerCase())!==null&&n!==void 0?n:"",a=he.get(`${s}-${o}`),l=he.get(s);return{locale:r,language:s,region:o,primary:a,secondary:l}}exists(e,i){var n;const{primary:r,secondary:s}=this.getTranslationData((n=i.lang)!==null&&n!==void 0?n:this.lang());return i=Object.assign({includeFallback:!1},i),!!(r&&r[e]||s&&s[e]||i.includeFallback&&Wt&&Wt[e])}term(e,...i){const{primary:n,secondary:r}=this.getTranslationData(this.lang());let s;if(n&&n[e])s=n[e];else if(r&&r[e])s=r[e];else if(Wt&&Wt[e])s=Wt[e];else return console.error(`No translation found for: ${String(e)}`),String(e);return typeof s=="function"?s(...i):s}date(e,i){return e=new Date(e),new Intl.DateTimeFormat(this.lang(),i).format(e)}number(e,i){return e=Number(e),isNaN(e)?"":new Intl.NumberFormat(this.lang(),i).format(e)}relativeTime(e,i,n){return new Intl.RelativeTimeFormat(this.lang(),n).format(e,i)}};var Ho={$code:"en",$name:"English",$dir:"ltr",carousel:"Carousel",clearEntry:"Clear entry",close:"Close",copied:"Copied",copy:"Copy",currentValue:"Current value",error:"Error",goToSlide:(t,e)=>`Go to slide ${t} of ${e}`,hidePassword:"Hide password",loading:"Loading",nextSlide:"Next slide",numOptionsSelected:t=>t===0?"No options selected":t===1?"1 option selected":`${t} options selected`,previousSlide:"Previous slide",progress:"Progress",remove:"Remove",resize:"Resize",scrollToEnd:"Scroll to end",scrollToStart:"Scroll to start",selectAColorFromTheScreen:"Select a color from the screen",showPassword:"Show password",slideNum:t=>`Slide ${t}`,toggleColorFormat:"Toggle color format"};No(Ho);var Iu=Ho,_e=class extends Pu{};No(Iu);var On="";function fs(t){On=t}function Bu(t=""){if(!On){const e=[...document.getElementsByTagName("script")],i=e.find(n=>n.hasAttribute("data-shoelace"));if(i)fs(i.getAttribute("data-shoelace"));else{const n=e.find(s=>/shoelace(\.min)?\.js($|\?)/.test(s.src)||/shoelace-autoloader(\.min)?\.js($|\?)/.test(s.src));let r="";n&&(r=n.getAttribute("src")),fs(r.split("/").slice(0,-1).join("/"))}}return On.replace(/\/$/,"")+(t?`/${t.replace(/^\//,"")}`:"")}var Du={name:"default",resolver:t=>Bu(`assets/icons/${t}.svg`)},Nu=Du,gs={caret:`
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
  `},Uu={name:"system",resolver:t=>t in gs?`data:image/svg+xml,${encodeURIComponent(gs[t])}`:""},Hu=Uu,Vu=[Nu,Hu],Mn=[];function qu(t){Mn.push(t)}function ju(t){Mn=Mn.filter(e=>e!==t)}function ms(t){return Vu.find(e=>e.name===t)}var Wu=ht`
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
`;function _t(t,e){const i=le({waitUntilFirstUpdate:!1},e);return(n,r)=>{const{update:s}=n,o=Array.isArray(t)?t:[t];n.update=function(a){o.forEach(l=>{const c=l;if(a.has(c)){const u=a.get(c),d=this[c];u!==d&&(!i.waitUntilFirstUpdate||this.hasUpdated)&&this[r](u,d)}}),s.call(this,a)}}}/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Ku=(t,e)=>(t==null?void 0:t._$litType$)!==void 0,Gu=t=>t.strings===void 0,Xu={},Ju=(t,e=Xu)=>t._$AH=e;var ze=Symbol(),ai=Symbol(),un,dn=new Map,pt=class extends nt{constructor(){super(...arguments),this.initialRender=!1,this.svg=null,this.label="",this.library="default"}async resolveIcon(t,e){var i;let n;if(e!=null&&e.spriteSheet)return this.svg=I`<svg part="svg">
        <use part="use" href="${t}"></use>
      </svg>`,this.svg;try{if(n=await fetch(t,{mode:"cors"}),!n.ok)return n.status===410?ze:ai}catch{return ai}try{const r=document.createElement("div");r.innerHTML=await n.text();const s=r.firstElementChild;if(((i=s==null?void 0:s.tagName)==null?void 0:i.toLowerCase())!=="svg")return ze;un||(un=new DOMParser);const a=un.parseFromString(s.outerHTML,"text/html").body.querySelector("svg");return a?(a.part.add("svg"),document.adoptNode(a)):ze}catch{return ze}}connectedCallback(){super.connectedCallback(),qu(this)}firstUpdated(){this.initialRender=!0,this.setIcon()}disconnectedCallback(){super.disconnectedCallback(),ju(this)}getIconSource(){const t=ms(this.library);return this.name&&t?{url:t.resolver(this.name),fromLibrary:!0}:{url:this.src,fromLibrary:!1}}handleLabelChange(){typeof this.label=="string"&&this.label.length>0?(this.setAttribute("role","img"),this.setAttribute("aria-label",this.label),this.removeAttribute("aria-hidden")):(this.removeAttribute("role"),this.removeAttribute("aria-label"),this.setAttribute("aria-hidden","true"))}async setIcon(){var t;const{url:e,fromLibrary:i}=this.getIconSource(),n=i?ms(this.library):void 0;if(!e){this.svg=null;return}let r=dn.get(e);if(r||(r=this.resolveIcon(e,n),dn.set(e,r)),!this.initialRender)return;const s=await r;if(s===ai&&dn.delete(e),e===this.getIconSource().url){if(Ku(s)){if(this.svg=s,n){await this.updateComplete;const o=this.shadowRoot.querySelector("[part='svg']");typeof n.mutator=="function"&&o&&n.mutator(o)}return}switch(s){case ai:case ze:this.svg=null,this.emit("sl-error");break;default:this.svg=s.cloneNode(!0),(t=n==null?void 0:n.mutator)==null||t.call(n,this.svg),this.emit("sl-load")}}}render(){return this.svg}};pt.styles=[kt,Wu];h([et()],pt.prototype,"svg",2);h([y({reflect:!0})],pt.prototype,"name",2);h([y()],pt.prototype,"src",2);h([y()],pt.prototype,"label",2);h([y({reflect:!0})],pt.prototype,"library",2);h([_t("label")],pt.prototype,"handleLabelChange",1);h([_t(["name","src","library"])],pt.prototype,"setIcon",1);/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Mt={ATTRIBUTE:1,PROPERTY:3,BOOLEAN_ATTRIBUTE:4},pr=t=>(...e)=>({_$litDirective$:t,values:e});let fr=class{constructor(e){}get _$AU(){return this._$AM._$AU}_$AT(e,i,n){this._$Ct=e,this._$AM=i,this._$Ci=n}_$AS(e,i){return this.update(e,i)}update(e,i){return this.render(...i)}};/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const St=pr(class extends fr{constructor(t){var e;if(super(t),t.type!==Mt.ATTRIBUTE||t.name!=="class"||((e=t.strings)==null?void 0:e.length)>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(t){return" "+Object.keys(t).filter(e=>t[e]).join(" ")+" "}update(t,[e]){var n,r;if(this.st===void 0){this.st=new Set,t.strings!==void 0&&(this.nt=new Set(t.strings.join(" ").split(/\s/).filter(s=>s!=="")));for(const s in e)e[s]&&!((n=this.nt)!=null&&n.has(s))&&this.st.add(s);return this.render(e)}const i=t.element.classList;for(const s of this.st)s in e||(i.remove(s),this.st.delete(s));for(const s in e){const o=!!e[s];o===this.st.has(s)||(r=this.nt)!=null&&r.has(s)||(o?(i.add(s),this.st.add(s)):(i.remove(s),this.st.delete(s)))}return ot}});/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const F=t=>t??D;/**
 * @license
 * Copyright 2020 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const Yu=pr(class extends fr{constructor(t){if(super(t),t.type!==Mt.PROPERTY&&t.type!==Mt.ATTRIBUTE&&t.type!==Mt.BOOLEAN_ATTRIBUTE)throw Error("The `live` directive is not allowed on child or event bindings");if(!Gu(t))throw Error("`live` bindings can only contain a single expression")}render(t){return t}update(t,[e]){if(e===ot||e===D)return e;const i=t.element,n=t.name;if(t.type===Mt.PROPERTY){if(e===i[n])return ot}else if(t.type===Mt.BOOLEAN_ATTRIBUTE){if(!!e===i.hasAttribute(n))return ot}else if(t.type===Mt.ATTRIBUTE&&i.getAttribute(n)===e+"")return ot;return Ju(t),e}});var A=class extends nt{constructor(){super(...arguments),this.formControlController=new cr(this,{assumeInteractionOn:["sl-blur","sl-input"]}),this.hasSlotController=new Bo(this,"help-text","label"),this.localize=new _e(this),this.hasFocus=!1,this.title="",this.__numberInput=Object.assign(document.createElement("input"),{type:"number"}),this.__dateInput=Object.assign(document.createElement("input"),{type:"date"}),this.type="text",this.name="",this.value="",this.defaultValue="",this.size="medium",this.filled=!1,this.pill=!1,this.label="",this.helpText="",this.clearable=!1,this.disabled=!1,this.placeholder="",this.readonly=!1,this.passwordToggle=!1,this.passwordVisible=!1,this.noSpinButtons=!1,this.form="",this.required=!1,this.spellcheck=!0}get valueAsDate(){var t;return this.__dateInput.type=this.type,this.__dateInput.value=this.value,((t=this.input)==null?void 0:t.valueAsDate)||this.__dateInput.valueAsDate}set valueAsDate(t){this.__dateInput.type=this.type,this.__dateInput.valueAsDate=t,this.value=this.__dateInput.value}get valueAsNumber(){var t;return this.__numberInput.value=this.value,((t=this.input)==null?void 0:t.valueAsNumber)||this.__numberInput.valueAsNumber}set valueAsNumber(t){this.__numberInput.valueAsNumber=t,this.value=this.__numberInput.value}get validity(){return this.input.validity}get validationMessage(){return this.input.validationMessage}firstUpdated(){this.formControlController.updateValidity()}handleBlur(){this.hasFocus=!1,this.emit("sl-blur")}handleChange(){this.value=this.input.value,this.emit("sl-change")}handleClearClick(t){t.preventDefault(),this.value!==""&&(this.value="",this.emit("sl-clear"),this.emit("sl-input"),this.emit("sl-change")),this.input.focus()}handleFocus(){this.hasFocus=!0,this.emit("sl-focus")}handleInput(){this.value=this.input.value,this.formControlController.updateValidity(),this.emit("sl-input")}handleInvalid(t){this.formControlController.setValidity(!1),this.formControlController.emitInvalidEvent(t)}handleKeyDown(t){const e=t.metaKey||t.ctrlKey||t.shiftKey||t.altKey;t.key==="Enter"&&!e&&setTimeout(()=>{!t.defaultPrevented&&!t.isComposing&&this.formControlController.submit()})}handlePasswordToggle(){this.passwordVisible=!this.passwordVisible}handleDisabledChange(){this.formControlController.setValidity(this.disabled)}handleStepChange(){this.input.step=String(this.step),this.formControlController.updateValidity()}async handleValueChange(){await this.updateComplete,this.formControlController.updateValidity()}focus(t){this.input.focus(t)}blur(){this.input.blur()}select(){this.input.select()}setSelectionRange(t,e,i="none"){this.input.setSelectionRange(t,e,i)}setRangeText(t,e,i,n="preserve"){const r=e??this.input.selectionStart,s=i??this.input.selectionEnd;this.input.setRangeText(t,r,s,n),this.value!==this.input.value&&(this.value=this.input.value)}showPicker(){"showPicker"in HTMLInputElement.prototype&&this.input.showPicker()}stepUp(){this.input.stepUp(),this.value!==this.input.value&&(this.value=this.input.value)}stepDown(){this.input.stepDown(),this.value!==this.input.value&&(this.value=this.input.value)}checkValidity(){return this.input.checkValidity()}getForm(){return this.formControlController.getForm()}reportValidity(){return this.input.reportValidity()}setCustomValidity(t){this.input.setCustomValidity(t),this.formControlController.updateValidity()}render(){const t=this.hasSlotController.test("label"),e=this.hasSlotController.test("help-text"),i=this.label?!0:!!t,n=this.helpText?!0:!!e,s=this.clearable&&!this.disabled&&!this.readonly&&(typeof this.value=="number"||this.value.length>0);return I`
      <div
        part="form-control"
        class=${St({"form-control":!0,"form-control--small":this.size==="small","form-control--medium":this.size==="medium","form-control--large":this.size==="large","form-control--has-label":i,"form-control--has-help-text":n})}
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
            class=${St({input:!0,"input--small":this.size==="small","input--medium":this.size==="medium","input--large":this.size==="large","input--pill":this.pill,"input--standard":!this.filled,"input--filled":this.filled,"input--disabled":this.disabled,"input--focused":this.hasFocus,"input--empty":!this.value,"input--no-spin-buttons":this.noSpinButtons})}
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
              .value=${Yu(this.value)}
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
    `}};A.styles=[kt,zu,Lu];A.dependencies={"sl-icon":pt};h([it(".input__control")],A.prototype,"input",2);h([et()],A.prototype,"hasFocus",2);h([y()],A.prototype,"title",2);h([y({reflect:!0})],A.prototype,"type",2);h([y()],A.prototype,"name",2);h([y()],A.prototype,"value",2);h([Io()],A.prototype,"defaultValue",2);h([y({reflect:!0})],A.prototype,"size",2);h([y({type:Boolean,reflect:!0})],A.prototype,"filled",2);h([y({type:Boolean,reflect:!0})],A.prototype,"pill",2);h([y()],A.prototype,"label",2);h([y({attribute:"help-text"})],A.prototype,"helpText",2);h([y({type:Boolean})],A.prototype,"clearable",2);h([y({type:Boolean,reflect:!0})],A.prototype,"disabled",2);h([y()],A.prototype,"placeholder",2);h([y({type:Boolean,reflect:!0})],A.prototype,"readonly",2);h([y({attribute:"password-toggle",type:Boolean})],A.prototype,"passwordToggle",2);h([y({attribute:"password-visible",type:Boolean})],A.prototype,"passwordVisible",2);h([y({attribute:"no-spin-buttons",type:Boolean})],A.prototype,"noSpinButtons",2);h([y({reflect:!0})],A.prototype,"form",2);h([y({type:Boolean,reflect:!0})],A.prototype,"required",2);h([y()],A.prototype,"pattern",2);h([y({type:Number})],A.prototype,"minlength",2);h([y({type:Number})],A.prototype,"maxlength",2);h([y()],A.prototype,"min",2);h([y()],A.prototype,"max",2);h([y()],A.prototype,"step",2);h([y()],A.prototype,"autocapitalize",2);h([y()],A.prototype,"autocorrect",2);h([y()],A.prototype,"autocomplete",2);h([y({type:Boolean})],A.prototype,"autofocus",2);h([y()],A.prototype,"enterkeyhint",2);h([y({type:Boolean,converter:{fromAttribute:t=>!(!t||t==="false"),toAttribute:t=>t?"true":"false"}})],A.prototype,"spellcheck",2);h([y()],A.prototype,"inputmode",2);h([_t("disabled",{waitUntilFirstUpdate:!0})],A.prototype,"handleDisabledChange",1);h([_t("step",{waitUntilFirstUpdate:!0})],A.prototype,"handleStepChange",1);h([_t("value",{waitUntilFirstUpdate:!0})],A.prototype,"handleValueChange",1);function hn(t,e){function i(r){const s=t.getBoundingClientRect(),o=t.ownerDocument.defaultView,a=s.left+o.scrollX,l=s.top+o.scrollY,c=r.pageX-a,u=r.pageY-l;e!=null&&e.onMove&&e.onMove(c,u)}function n(){document.removeEventListener("pointermove",i),document.removeEventListener("pointerup",n),e!=null&&e.onStop&&e.onStop()}document.addEventListener("pointermove",i,{passive:!0}),document.addEventListener("pointerup",n),(e==null?void 0:e.initialEvent)instanceof PointerEvent&&i(e.initialEvent)}var Qu=ht`
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
`;function*Vo(t=document.activeElement){t!=null&&(yield t,"shadowRoot"in t&&t.shadowRoot&&t.shadowRoot.mode!=="closed"&&(yield*Ru(Vo(t.shadowRoot.activeElement))))}function Zu(){return[...Vo()].pop()}var bs=new WeakMap;function qo(t){let e=bs.get(t);return e||(e=window.getComputedStyle(t,null),bs.set(t,e)),e}function td(t){if(typeof t.checkVisibility=="function")return t.checkVisibility({checkOpacity:!1,checkVisibilityCSS:!0});const e=qo(t);return e.visibility!=="hidden"&&e.display!=="none"}function ed(t){const e=qo(t),{overflowY:i,overflowX:n}=e;return i==="scroll"||n==="scroll"?!0:i!=="auto"||n!=="auto"?!1:t.scrollHeight>t.clientHeight&&i==="auto"||t.scrollWidth>t.clientWidth&&n==="auto"}function id(t){const e=t.tagName.toLowerCase(),i=Number(t.getAttribute("tabindex"));if(t.hasAttribute("tabindex")&&(isNaN(i)||i<=-1)||t.hasAttribute("disabled")||t.closest("[inert]"))return!1;if(e==="input"&&t.getAttribute("type")==="radio"){const s=t.getRootNode(),o=`input[type='radio'][name="${t.getAttribute("name")}"]`,a=s.querySelector(`${o}:checked`);return a?a===t:s.querySelector(o)===t}return td(t)?(e==="audio"||e==="video")&&t.hasAttribute("controls")||t.hasAttribute("tabindex")||t.hasAttribute("contenteditable")&&t.getAttribute("contenteditable")!=="false"||["button","input","select","textarea","a","audio","video","summary","iframe"].includes(e)?!0:ed(t):!1}function nd(t){var e,i;const n=sd(t),r=(e=n[0])!=null?e:null,s=(i=n[n.length-1])!=null?i:null;return{start:r,end:s}}function rd(t,e){var i;return((i=t.getRootNode({composed:!0}))==null?void 0:i.host)!==e}function sd(t){const e=new WeakMap,i=[];function n(r){if(r instanceof Element){if(r.hasAttribute("inert")||r.closest("[inert]")||e.has(r))return;e.set(r,!0),!i.includes(r)&&id(r)&&i.push(r),r instanceof HTMLSlotElement&&rd(r,t)&&r.assignedElements({flatten:!0}).forEach(s=>{n(s)}),r.shadowRoot!==null&&r.shadowRoot.mode==="open"&&n(r.shadowRoot)}for(const s of r.children)n(s)}return n(t),i.sort((r,s)=>{const o=Number(r.getAttribute("tabindex"))||0;return(Number(s.getAttribute("tabindex"))||0)-o})}var od=ht`
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
`;const It=Math.min,Q=Math.max,$i=Math.round,li=Math.floor,vt=t=>({x:t,y:t}),ad={left:"right",right:"left",bottom:"top",top:"bottom"},ld={start:"end",end:"start"};function Ln(t,e,i){return Q(t,It(e,i))}function xe(t,e){return typeof t=="function"?t(e):t}function Bt(t){return t.split("-")[0]}function Ee(t){return t.split("-")[1]}function jo(t){return t==="x"?"y":"x"}function gr(t){return t==="y"?"height":"width"}function se(t){return["top","bottom"].includes(Bt(t))?"y":"x"}function mr(t){return jo(se(t))}function cd(t,e,i){i===void 0&&(i=!1);const n=Ee(t),r=mr(t),s=gr(r);let o=r==="x"?n===(i?"end":"start")?"right":"left":n==="start"?"bottom":"top";return e.reference[s]>e.floating[s]&&(o=Ri(o)),[o,Ri(o)]}function ud(t){const e=Ri(t);return[zn(t),e,zn(e)]}function zn(t){return t.replace(/start|end/g,e=>ld[e])}function dd(t,e,i){const n=["left","right"],r=["right","left"],s=["top","bottom"],o=["bottom","top"];switch(t){case"top":case"bottom":return i?e?r:n:e?n:r;case"left":case"right":return e?s:o;default:return[]}}function hd(t,e,i,n){const r=Ee(t);let s=dd(Bt(t),i==="start",n);return r&&(s=s.map(o=>o+"-"+r),e&&(s=s.concat(s.map(zn)))),s}function Ri(t){return t.replace(/left|right|bottom|top/g,e=>ad[e])}function pd(t){return{top:0,right:0,bottom:0,left:0,...t}}function Wo(t){return typeof t!="number"?pd(t):{top:t,right:t,bottom:t,left:t}}function Ti(t){const{x:e,y:i,width:n,height:r}=t;return{width:n,height:r,top:i,left:e,right:e+n,bottom:i+r,x:e,y:i}}function ys(t,e,i){let{reference:n,floating:r}=t;const s=se(e),o=mr(e),a=gr(o),l=Bt(e),c=s==="y",u=n.x+n.width/2-r.width/2,d=n.y+n.height/2-r.height/2,p=n[a]/2-r[a]/2;let f;switch(l){case"top":f={x:u,y:n.y-r.height};break;case"bottom":f={x:u,y:n.y+n.height};break;case"right":f={x:n.x+n.width,y:d};break;case"left":f={x:n.x-r.width,y:d};break;default:f={x:n.x,y:n.y}}switch(Ee(e)){case"start":f[o]-=p*(i&&c?-1:1);break;case"end":f[o]+=p*(i&&c?-1:1);break}return f}const fd=async(t,e,i)=>{const{placement:n="bottom",strategy:r="absolute",middleware:s=[],platform:o}=i,a=s.filter(Boolean),l=await(o.isRTL==null?void 0:o.isRTL(e));let c=await o.getElementRects({reference:t,floating:e,strategy:r}),{x:u,y:d}=ys(c,n,l),p=n,f={},b=0;for(let w=0;w<a.length;w++){const{name:g,fn:_}=a[w],{x,y:E,data:k,reset:S}=await _({x:u,y:d,initialPlacement:n,placement:p,strategy:r,middlewareData:f,rects:c,platform:o,elements:{reference:t,floating:e}});u=x??u,d=E??d,f={...f,[g]:{...f[g],...k}},S&&b<=50&&(b++,typeof S=="object"&&(S.placement&&(p=S.placement),S.rects&&(c=S.rects===!0?await o.getElementRects({reference:t,floating:e,strategy:r}):S.rects),{x:u,y:d}=ys(c,p,l)),w=-1)}return{x:u,y:d,placement:p,strategy:r,middlewareData:f}};async function br(t,e){var i;e===void 0&&(e={});const{x:n,y:r,platform:s,rects:o,elements:a,strategy:l}=t,{boundary:c="clippingAncestors",rootBoundary:u="viewport",elementContext:d="floating",altBoundary:p=!1,padding:f=0}=xe(e,t),b=Wo(f),g=a[p?d==="floating"?"reference":"floating":d],_=Ti(await s.getClippingRect({element:(i=await(s.isElement==null?void 0:s.isElement(g)))==null||i?g:g.contextElement||await(s.getDocumentElement==null?void 0:s.getDocumentElement(a.floating)),boundary:c,rootBoundary:u,strategy:l})),x=d==="floating"?{x:n,y:r,width:o.floating.width,height:o.floating.height}:o.reference,E=await(s.getOffsetParent==null?void 0:s.getOffsetParent(a.floating)),k=await(s.isElement==null?void 0:s.isElement(E))?await(s.getScale==null?void 0:s.getScale(E))||{x:1,y:1}:{x:1,y:1},S=Ti(s.convertOffsetParentRelativeRectToViewportRelativeRect?await s.convertOffsetParentRelativeRectToViewportRelativeRect({elements:a,rect:x,offsetParent:E,strategy:l}):x);return{top:(_.top-S.top+b.top)/k.y,bottom:(S.bottom-_.bottom+b.bottom)/k.y,left:(_.left-S.left+b.left)/k.x,right:(S.right-_.right+b.right)/k.x}}const gd=t=>({name:"arrow",options:t,async fn(e){const{x:i,y:n,placement:r,rects:s,platform:o,elements:a,middlewareData:l}=e,{element:c,padding:u=0}=xe(t,e)||{};if(c==null)return{};const d=Wo(u),p={x:i,y:n},f=mr(r),b=gr(f),w=await o.getDimensions(c),g=f==="y",_=g?"top":"left",x=g?"bottom":"right",E=g?"clientHeight":"clientWidth",k=s.reference[b]+s.reference[f]-p[f]-s.floating[b],S=p[f]-s.reference[f],O=await(o.getOffsetParent==null?void 0:o.getOffsetParent(c));let T=O?O[E]:0;(!T||!await(o.isElement==null?void 0:o.isElement(O)))&&(T=a.floating[E]||s.floating[b]);const H=k/2-S/2,j=T/2-w[b]/2-1,X=It(d[_],j),At=It(d[x],j),gt=X,$t=T-w[b]-At,W=T/2-w[b]/2+H,ue=Ln(gt,W,$t),Et=!l.arrow&&Ee(r)!=null&&W!==ue&&s.reference[b]/2-(W<gt?X:At)-w[b]/2<0,mt=Et?W<gt?W-gt:W-$t:0;return{[f]:p[f]+mt,data:{[f]:ue,centerOffset:W-ue-mt,...Et&&{alignmentOffset:mt}},reset:Et}}}),md=function(t){return t===void 0&&(t={}),{name:"flip",options:t,async fn(e){var i,n;const{placement:r,middlewareData:s,rects:o,initialPlacement:a,platform:l,elements:c}=e,{mainAxis:u=!0,crossAxis:d=!0,fallbackPlacements:p,fallbackStrategy:f="bestFit",fallbackAxisSideDirection:b="none",flipAlignment:w=!0,...g}=xe(t,e);if((i=s.arrow)!=null&&i.alignmentOffset)return{};const _=Bt(r),x=se(a),E=Bt(a)===a,k=await(l.isRTL==null?void 0:l.isRTL(c.floating)),S=p||(E||!w?[Ri(a)]:ud(a)),O=b!=="none";!p&&O&&S.push(...hd(a,w,b,k));const T=[a,...S],H=await br(e,g),j=[];let X=((n=s.flip)==null?void 0:n.overflows)||[];if(u&&j.push(H[_]),d){const W=cd(r,o,k);j.push(H[W[0]],H[W[1]])}if(X=[...X,{placement:r,overflows:j}],!j.every(W=>W<=0)){var At,gt;const W=(((At=s.flip)==null?void 0:At.index)||0)+1,ue=T[W];if(ue)return{data:{index:W,overflows:X},reset:{placement:ue}};let Et=(gt=X.filter(mt=>mt.overflows[0]<=0).sort((mt,Rt)=>mt.overflows[1]-Rt.overflows[1])[0])==null?void 0:gt.placement;if(!Et)switch(f){case"bestFit":{var $t;const mt=($t=X.filter(Rt=>{if(O){const Tt=se(Rt.placement);return Tt===x||Tt==="y"}return!0}).map(Rt=>[Rt.placement,Rt.overflows.filter(Tt=>Tt>0).reduce((Tt,_l)=>Tt+_l,0)]).sort((Rt,Tt)=>Rt[1]-Tt[1])[0])==null?void 0:$t[0];mt&&(Et=mt);break}case"initialPlacement":Et=a;break}if(r!==Et)return{reset:{placement:Et}}}return{}}}};async function bd(t,e){const{placement:i,platform:n,elements:r}=t,s=await(n.isRTL==null?void 0:n.isRTL(r.floating)),o=Bt(i),a=Ee(i),l=se(i)==="y",c=["left","top"].includes(o)?-1:1,u=s&&l?-1:1,d=xe(e,t);let{mainAxis:p,crossAxis:f,alignmentAxis:b}=typeof d=="number"?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:d.mainAxis||0,crossAxis:d.crossAxis||0,alignmentAxis:d.alignmentAxis};return a&&typeof b=="number"&&(f=a==="end"?b*-1:b),l?{x:f*u,y:p*c}:{x:p*c,y:f*u}}const yd=function(t){return t===void 0&&(t=0),{name:"offset",options:t,async fn(e){var i,n;const{x:r,y:s,placement:o,middlewareData:a}=e,l=await bd(e,t);return o===((i=a.offset)==null?void 0:i.placement)&&(n=a.arrow)!=null&&n.alignmentOffset?{}:{x:r+l.x,y:s+l.y,data:{...l,placement:o}}}}},vd=function(t){return t===void 0&&(t={}),{name:"shift",options:t,async fn(e){const{x:i,y:n,placement:r}=e,{mainAxis:s=!0,crossAxis:o=!1,limiter:a={fn:g=>{let{x:_,y:x}=g;return{x:_,y:x}}},...l}=xe(t,e),c={x:i,y:n},u=await br(e,l),d=se(Bt(r)),p=jo(d);let f=c[p],b=c[d];if(s){const g=p==="y"?"top":"left",_=p==="y"?"bottom":"right",x=f+u[g],E=f-u[_];f=Ln(x,f,E)}if(o){const g=d==="y"?"top":"left",_=d==="y"?"bottom":"right",x=b+u[g],E=b-u[_];b=Ln(x,b,E)}const w=a.fn({...e,[p]:f,[d]:b});return{...w,data:{x:w.x-i,y:w.y-n,enabled:{[p]:s,[d]:o}}}}}},wd=function(t){return t===void 0&&(t={}),{name:"size",options:t,async fn(e){var i,n;const{placement:r,rects:s,platform:o,elements:a}=e,{apply:l=()=>{},...c}=xe(t,e),u=await br(e,c),d=Bt(r),p=Ee(r),f=se(r)==="y",{width:b,height:w}=s.floating;let g,_;d==="top"||d==="bottom"?(g=d,_=p===(await(o.isRTL==null?void 0:o.isRTL(a.floating))?"start":"end")?"left":"right"):(_=d,g=p==="end"?"top":"bottom");const x=w-u.top-u.bottom,E=b-u.left-u.right,k=It(w-u[g],x),S=It(b-u[_],E),O=!e.middlewareData.shift;let T=k,H=S;if((i=e.middlewareData.shift)!=null&&i.enabled.x&&(H=E),(n=e.middlewareData.shift)!=null&&n.enabled.y&&(T=x),O&&!p){const X=Q(u.left,0),At=Q(u.right,0),gt=Q(u.top,0),$t=Q(u.bottom,0);f?H=b-2*(X!==0||At!==0?X+At:Q(u.left,u.right)):T=w-2*(gt!==0||$t!==0?gt+$t:Q(u.top,u.bottom))}await l({...e,availableWidth:H,availableHeight:T});const j=await o.getDimensions(a.floating);return b!==j.width||w!==j.height?{reset:{rects:!0}}:{}}}};function Vi(){return typeof window<"u"}function Se(t){return Ko(t)?(t.nodeName||"").toLowerCase():"#document"}function tt(t){var e;return(t==null||(e=t.ownerDocument)==null?void 0:e.defaultView)||window}function xt(t){var e;return(e=(Ko(t)?t.ownerDocument:t.document)||window.document)==null?void 0:e.documentElement}function Ko(t){return Vi()?t instanceof Node||t instanceof tt(t).Node:!1}function lt(t){return Vi()?t instanceof Element||t instanceof tt(t).Element:!1}function wt(t){return Vi()?t instanceof HTMLElement||t instanceof tt(t).HTMLElement:!1}function vs(t){return!Vi()||typeof ShadowRoot>"u"?!1:t instanceof ShadowRoot||t instanceof tt(t).ShadowRoot}function ei(t){const{overflow:e,overflowX:i,overflowY:n,display:r}=ct(t);return/auto|scroll|overlay|hidden|clip/.test(e+n+i)&&!["inline","contents"].includes(r)}function _d(t){return["table","td","th"].includes(Se(t))}function qi(t){return[":popover-open",":modal"].some(e=>{try{return t.matches(e)}catch{return!1}})}function ji(t){const e=yr(),i=lt(t)?ct(t):t;return["transform","translate","scale","rotate","perspective"].some(n=>i[n]?i[n]!=="none":!1)||(i.containerType?i.containerType!=="normal":!1)||!e&&(i.backdropFilter?i.backdropFilter!=="none":!1)||!e&&(i.filter?i.filter!=="none":!1)||["transform","translate","scale","rotate","perspective","filter"].some(n=>(i.willChange||"").includes(n))||["paint","layout","strict","content"].some(n=>(i.contain||"").includes(n))}function xd(t){let e=Dt(t);for(;wt(e)&&!me(e);){if(ji(e))return e;if(qi(e))return null;e=Dt(e)}return null}function yr(){return typeof CSS>"u"||!CSS.supports?!1:CSS.supports("-webkit-backdrop-filter","none")}function me(t){return["html","body","#document"].includes(Se(t))}function ct(t){return tt(t).getComputedStyle(t)}function Wi(t){return lt(t)?{scrollLeft:t.scrollLeft,scrollTop:t.scrollTop}:{scrollLeft:t.scrollX,scrollTop:t.scrollY}}function Dt(t){if(Se(t)==="html")return t;const e=t.assignedSlot||t.parentNode||vs(t)&&t.host||xt(t);return vs(e)?e.host:e}function Go(t){const e=Dt(t);return me(e)?t.ownerDocument?t.ownerDocument.body:t.body:wt(e)&&ei(e)?e:Go(e)}function Je(t,e,i){var n;e===void 0&&(e=[]),i===void 0&&(i=!0);const r=Go(t),s=r===((n=t.ownerDocument)==null?void 0:n.body),o=tt(r);if(s){const a=Pn(o);return e.concat(o,o.visualViewport||[],ei(r)?r:[],a&&i?Je(a):[])}return e.concat(r,Je(r,[],i))}function Pn(t){return t.parent&&Object.getPrototypeOf(t.parent)?t.frameElement:null}function Xo(t){const e=ct(t);let i=parseFloat(e.width)||0,n=parseFloat(e.height)||0;const r=wt(t),s=r?t.offsetWidth:i,o=r?t.offsetHeight:n,a=$i(i)!==s||$i(n)!==o;return a&&(i=s,n=o),{width:i,height:n,$:a}}function vr(t){return lt(t)?t:t.contextElement}function pe(t){const e=vr(t);if(!wt(e))return vt(1);const i=e.getBoundingClientRect(),{width:n,height:r,$:s}=Xo(e);let o=(s?$i(i.width):i.width)/n,a=(s?$i(i.height):i.height)/r;return(!o||!Number.isFinite(o))&&(o=1),(!a||!Number.isFinite(a))&&(a=1),{x:o,y:a}}const Ed=vt(0);function Jo(t){const e=tt(t);return!yr()||!e.visualViewport?Ed:{x:e.visualViewport.offsetLeft,y:e.visualViewport.offsetTop}}function Sd(t,e,i){return e===void 0&&(e=!1),!i||e&&i!==tt(t)?!1:e}function oe(t,e,i,n){e===void 0&&(e=!1),i===void 0&&(i=!1);const r=t.getBoundingClientRect(),s=vr(t);let o=vt(1);e&&(n?lt(n)&&(o=pe(n)):o=pe(t));const a=Sd(s,i,n)?Jo(s):vt(0);let l=(r.left+a.x)/o.x,c=(r.top+a.y)/o.y,u=r.width/o.x,d=r.height/o.y;if(s){const p=tt(s),f=n&&lt(n)?tt(n):n;let b=p,w=Pn(b);for(;w&&n&&f!==b;){const g=pe(w),_=w.getBoundingClientRect(),x=ct(w),E=_.left+(w.clientLeft+parseFloat(x.paddingLeft))*g.x,k=_.top+(w.clientTop+parseFloat(x.paddingTop))*g.y;l*=g.x,c*=g.y,u*=g.x,d*=g.y,l+=E,c+=k,b=tt(w),w=Pn(b)}}return Ti({width:u,height:d,x:l,y:c})}function wr(t,e){const i=Wi(t).scrollLeft;return e?e.left+i:oe(xt(t)).left+i}function Yo(t,e,i){i===void 0&&(i=!1);const n=t.getBoundingClientRect(),r=n.left+e.scrollLeft-(i?0:wr(t,n)),s=n.top+e.scrollTop;return{x:r,y:s}}function Cd(t){let{elements:e,rect:i,offsetParent:n,strategy:r}=t;const s=r==="fixed",o=xt(n),a=e?qi(e.floating):!1;if(n===o||a&&s)return i;let l={scrollLeft:0,scrollTop:0},c=vt(1);const u=vt(0),d=wt(n);if((d||!d&&!s)&&((Se(n)!=="body"||ei(o))&&(l=Wi(n)),wt(n))){const f=oe(n);c=pe(n),u.x=f.x+n.clientLeft,u.y=f.y+n.clientTop}const p=o&&!d&&!s?Yo(o,l,!0):vt(0);return{width:i.width*c.x,height:i.height*c.y,x:i.x*c.x-l.scrollLeft*c.x+u.x+p.x,y:i.y*c.y-l.scrollTop*c.y+u.y+p.y}}function kd(t){return Array.from(t.getClientRects())}function Ad(t){const e=xt(t),i=Wi(t),n=t.ownerDocument.body,r=Q(e.scrollWidth,e.clientWidth,n.scrollWidth,n.clientWidth),s=Q(e.scrollHeight,e.clientHeight,n.scrollHeight,n.clientHeight);let o=-i.scrollLeft+wr(t);const a=-i.scrollTop;return ct(n).direction==="rtl"&&(o+=Q(e.clientWidth,n.clientWidth)-r),{width:r,height:s,x:o,y:a}}function $d(t,e){const i=tt(t),n=xt(t),r=i.visualViewport;let s=n.clientWidth,o=n.clientHeight,a=0,l=0;if(r){s=r.width,o=r.height;const c=yr();(!c||c&&e==="fixed")&&(a=r.offsetLeft,l=r.offsetTop)}return{width:s,height:o,x:a,y:l}}function Rd(t,e){const i=oe(t,!0,e==="fixed"),n=i.top+t.clientTop,r=i.left+t.clientLeft,s=wt(t)?pe(t):vt(1),o=t.clientWidth*s.x,a=t.clientHeight*s.y,l=r*s.x,c=n*s.y;return{width:o,height:a,x:l,y:c}}function ws(t,e,i){let n;if(e==="viewport")n=$d(t,i);else if(e==="document")n=Ad(xt(t));else if(lt(e))n=Rd(e,i);else{const r=Jo(t);n={x:e.x-r.x,y:e.y-r.y,width:e.width,height:e.height}}return Ti(n)}function Qo(t,e){const i=Dt(t);return i===e||!lt(i)||me(i)?!1:ct(i).position==="fixed"||Qo(i,e)}function Td(t,e){const i=e.get(t);if(i)return i;let n=Je(t,[],!1).filter(a=>lt(a)&&Se(a)!=="body"),r=null;const s=ct(t).position==="fixed";let o=s?Dt(t):t;for(;lt(o)&&!me(o);){const a=ct(o),l=ji(o);!l&&a.position==="fixed"&&(r=null),(s?!l&&!r:!l&&a.position==="static"&&!!r&&["absolute","fixed"].includes(r.position)||ei(o)&&!l&&Qo(t,o))?n=n.filter(u=>u!==o):r=a,o=Dt(o)}return e.set(t,n),n}function Fd(t){let{element:e,boundary:i,rootBoundary:n,strategy:r}=t;const o=[...i==="clippingAncestors"?qi(e)?[]:Td(e,this._c):[].concat(i),n],a=o[0],l=o.reduce((c,u)=>{const d=ws(e,u,r);return c.top=Q(d.top,c.top),c.right=It(d.right,c.right),c.bottom=It(d.bottom,c.bottom),c.left=Q(d.left,c.left),c},ws(e,a,r));return{width:l.right-l.left,height:l.bottom-l.top,x:l.left,y:l.top}}function Od(t){const{width:e,height:i}=Xo(t);return{width:e,height:i}}function Md(t,e,i){const n=wt(e),r=xt(e),s=i==="fixed",o=oe(t,!0,s,e);let a={scrollLeft:0,scrollTop:0};const l=vt(0);if(n||!n&&!s)if((Se(e)!=="body"||ei(r))&&(a=Wi(e)),n){const p=oe(e,!0,s,e);l.x=p.x+e.clientLeft,l.y=p.y+e.clientTop}else r&&(l.x=wr(r));const c=r&&!n&&!s?Yo(r,a):vt(0),u=o.left+a.scrollLeft-l.x-c.x,d=o.top+a.scrollTop-l.y-c.y;return{x:u,y:d,width:o.width,height:o.height}}function pn(t){return ct(t).position==="static"}function _s(t,e){if(!wt(t)||ct(t).position==="fixed")return null;if(e)return e(t);let i=t.offsetParent;return xt(t)===i&&(i=i.ownerDocument.body),i}function Zo(t,e){const i=tt(t);if(qi(t))return i;if(!wt(t)){let r=Dt(t);for(;r&&!me(r);){if(lt(r)&&!pn(r))return r;r=Dt(r)}return i}let n=_s(t,e);for(;n&&_d(n)&&pn(n);)n=_s(n,e);return n&&me(n)&&pn(n)&&!ji(n)?i:n||xd(t)||i}const Ld=async function(t){const e=this.getOffsetParent||Zo,i=this.getDimensions,n=await i(t.floating);return{reference:Md(t.reference,await e(t.floating),t.strategy),floating:{x:0,y:0,width:n.width,height:n.height}}};function zd(t){return ct(t).direction==="rtl"}const xi={convertOffsetParentRelativeRectToViewportRelativeRect:Cd,getDocumentElement:xt,getClippingRect:Fd,getOffsetParent:Zo,getElementRects:Ld,getClientRects:kd,getDimensions:Od,getScale:pe,isElement:lt,isRTL:zd};function ta(t,e){return t.x===e.x&&t.y===e.y&&t.width===e.width&&t.height===e.height}function Pd(t,e){let i=null,n;const r=xt(t);function s(){var a;clearTimeout(n),(a=i)==null||a.disconnect(),i=null}function o(a,l){a===void 0&&(a=!1),l===void 0&&(l=1),s();const c=t.getBoundingClientRect(),{left:u,top:d,width:p,height:f}=c;if(a||e(),!p||!f)return;const b=li(d),w=li(r.clientWidth-(u+p)),g=li(r.clientHeight-(d+f)),_=li(u),E={rootMargin:-b+"px "+-w+"px "+-g+"px "+-_+"px",threshold:Q(0,It(1,l))||1};let k=!0;function S(O){const T=O[0].intersectionRatio;if(T!==l){if(!k)return o();T?o(!1,T):n=setTimeout(()=>{o(!1,1e-7)},1e3)}T===1&&!ta(c,t.getBoundingClientRect())&&o(),k=!1}try{i=new IntersectionObserver(S,{...E,root:r.ownerDocument})}catch{i=new IntersectionObserver(S,E)}i.observe(t)}return o(!0),s}function Id(t,e,i,n){n===void 0&&(n={});const{ancestorScroll:r=!0,ancestorResize:s=!0,elementResize:o=typeof ResizeObserver=="function",layoutShift:a=typeof IntersectionObserver=="function",animationFrame:l=!1}=n,c=vr(t),u=r||s?[...c?Je(c):[],...Je(e)]:[];u.forEach(_=>{r&&_.addEventListener("scroll",i,{passive:!0}),s&&_.addEventListener("resize",i)});const d=c&&a?Pd(c,i):null;let p=-1,f=null;o&&(f=new ResizeObserver(_=>{let[x]=_;x&&x.target===c&&f&&(f.unobserve(e),cancelAnimationFrame(p),p=requestAnimationFrame(()=>{var E;(E=f)==null||E.observe(e)})),i()}),c&&!l&&f.observe(c),f.observe(e));let b,w=l?oe(t):null;l&&g();function g(){const _=oe(t);w&&!ta(w,_)&&i(),w=_,b=requestAnimationFrame(g)}return i(),()=>{var _;u.forEach(x=>{r&&x.removeEventListener("scroll",i),s&&x.removeEventListener("resize",i)}),d==null||d(),(_=f)==null||_.disconnect(),f=null,l&&cancelAnimationFrame(b)}}const Bd=yd,Dd=vd,Nd=md,xs=wd,Ud=gd,Hd=(t,e,i)=>{const n=new Map,r={platform:xi,...i},s={...r.platform,_c:n};return fd(t,e,{...r,platform:s})};function Vd(t){return qd(t)}function fn(t){return t.assignedSlot?t.assignedSlot:t.parentNode instanceof ShadowRoot?t.parentNode.host:t.parentNode}function qd(t){for(let e=t;e;e=fn(e))if(e instanceof Element&&getComputedStyle(e).display==="none")return null;for(let e=fn(t);e;e=fn(e)){if(!(e instanceof Element))continue;const i=getComputedStyle(e);if(i.display!=="contents"&&(i.position!=="static"||ji(i)||e.tagName==="BODY"))return e}return null}function jd(t){return t!==null&&typeof t=="object"&&"getBoundingClientRect"in t&&("contextElement"in t?t.contextElement instanceof Element:!0)}var M=class extends nt{constructor(){super(...arguments),this.localize=new _e(this),this.active=!1,this.placement="top",this.strategy="absolute",this.distance=0,this.skidding=0,this.arrow=!1,this.arrowPlacement="anchor",this.arrowPadding=10,this.flip=!1,this.flipFallbackPlacements="",this.flipFallbackStrategy="best-fit",this.flipPadding=0,this.shift=!1,this.shiftPadding=0,this.autoSizePadding=0,this.hoverBridge=!1,this.updateHoverBridge=()=>{if(this.hoverBridge&&this.anchorEl){const t=this.anchorEl.getBoundingClientRect(),e=this.popup.getBoundingClientRect(),i=this.placement.includes("top")||this.placement.includes("bottom");let n=0,r=0,s=0,o=0,a=0,l=0,c=0,u=0;i?t.top<e.top?(n=t.left,r=t.bottom,s=t.right,o=t.bottom,a=e.left,l=e.top,c=e.right,u=e.top):(n=e.left,r=e.bottom,s=e.right,o=e.bottom,a=t.left,l=t.top,c=t.right,u=t.top):t.left<e.left?(n=t.right,r=t.top,s=e.left,o=e.top,a=t.right,l=t.bottom,c=e.left,u=e.bottom):(n=e.right,r=e.top,s=t.left,o=t.top,a=e.right,l=e.bottom,c=t.left,u=t.bottom),this.style.setProperty("--hover-bridge-top-left-x",`${n}px`),this.style.setProperty("--hover-bridge-top-left-y",`${r}px`),this.style.setProperty("--hover-bridge-top-right-x",`${s}px`),this.style.setProperty("--hover-bridge-top-right-y",`${o}px`),this.style.setProperty("--hover-bridge-bottom-left-x",`${a}px`),this.style.setProperty("--hover-bridge-bottom-left-y",`${l}px`),this.style.setProperty("--hover-bridge-bottom-right-x",`${c}px`),this.style.setProperty("--hover-bridge-bottom-right-y",`${u}px`)}}}async connectedCallback(){super.connectedCallback(),await this.updateComplete,this.start()}disconnectedCallback(){super.disconnectedCallback(),this.stop()}async updated(t){super.updated(t),t.has("active")&&(this.active?this.start():this.stop()),t.has("anchor")&&this.handleAnchorChange(),this.active&&(await this.updateComplete,this.reposition())}async handleAnchorChange(){if(await this.stop(),this.anchor&&typeof this.anchor=="string"){const t=this.getRootNode();this.anchorEl=t.getElementById(this.anchor)}else this.anchor instanceof Element||jd(this.anchor)?this.anchorEl=this.anchor:this.anchorEl=this.querySelector('[slot="anchor"]');this.anchorEl instanceof HTMLSlotElement&&(this.anchorEl=this.anchorEl.assignedElements({flatten:!0})[0]),this.anchorEl&&this.active&&this.start()}start(){!this.anchorEl||!this.active||(this.cleanup=Id(this.anchorEl,this.popup,()=>{this.reposition()}))}async stop(){return new Promise(t=>{this.cleanup?(this.cleanup(),this.cleanup=void 0,this.removeAttribute("data-current-placement"),this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height"),requestAnimationFrame(()=>t())):t()})}reposition(){if(!this.active||!this.anchorEl)return;const t=[Bd({mainAxis:this.distance,crossAxis:this.skidding})];this.sync?t.push(xs({apply:({rects:i})=>{const n=this.sync==="width"||this.sync==="both",r=this.sync==="height"||this.sync==="both";this.popup.style.width=n?`${i.reference.width}px`:"",this.popup.style.height=r?`${i.reference.height}px`:""}})):(this.popup.style.width="",this.popup.style.height=""),this.flip&&t.push(Nd({boundary:this.flipBoundary,fallbackPlacements:this.flipFallbackPlacements,fallbackStrategy:this.flipFallbackStrategy==="best-fit"?"bestFit":"initialPlacement",padding:this.flipPadding})),this.shift&&t.push(Dd({boundary:this.shiftBoundary,padding:this.shiftPadding})),this.autoSize?t.push(xs({boundary:this.autoSizeBoundary,padding:this.autoSizePadding,apply:({availableWidth:i,availableHeight:n})=>{this.autoSize==="vertical"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-height",`${n}px`):this.style.removeProperty("--auto-size-available-height"),this.autoSize==="horizontal"||this.autoSize==="both"?this.style.setProperty("--auto-size-available-width",`${i}px`):this.style.removeProperty("--auto-size-available-width")}})):(this.style.removeProperty("--auto-size-available-width"),this.style.removeProperty("--auto-size-available-height")),this.arrow&&t.push(Ud({element:this.arrowEl,padding:this.arrowPadding}));const e=this.strategy==="absolute"?i=>xi.getOffsetParent(i,Vd):xi.getOffsetParent;Hd(this.anchorEl,this.popup,{placement:this.placement,middleware:t,strategy:this.strategy,platform:Hi(le({},xi),{getOffsetParent:e})}).then(({x:i,y:n,middlewareData:r,placement:s})=>{const o=this.localize.dir()==="rtl",a={top:"bottom",right:"left",bottom:"top",left:"right"}[s.split("-")[0]];if(this.setAttribute("data-current-placement",s),Object.assign(this.popup.style,{left:`${i}px`,top:`${n}px`}),this.arrow){const l=r.arrow.x,c=r.arrow.y;let u="",d="",p="",f="";if(this.arrowPlacement==="start"){const b=typeof l=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";u=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"",d=o?b:"",f=o?"":b}else if(this.arrowPlacement==="end"){const b=typeof l=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:"";d=o?"":b,f=o?b:"",p=typeof c=="number"?`calc(${this.arrowPadding}px - var(--arrow-padding-offset))`:""}else this.arrowPlacement==="center"?(f=typeof l=="number"?"calc(50% - var(--arrow-size-diagonal))":"",u=typeof c=="number"?"calc(50% - var(--arrow-size-diagonal))":""):(f=typeof l=="number"?`${l}px`:"",u=typeof c=="number"?`${c}px`:"");Object.assign(this.arrowEl.style,{top:u,right:d,bottom:p,left:f,[a]:"calc(var(--arrow-size-diagonal) * -1)"})}}),requestAnimationFrame(()=>this.updateHoverBridge()),this.emit("sl-reposition")}render(){return I`
      <slot name="anchor" @slotchange=${this.handleAnchorChange}></slot>

      <span
        part="hover-bridge"
        class=${St({"popup-hover-bridge":!0,"popup-hover-bridge--visible":this.hoverBridge&&this.active})}
      ></span>

      <div
        part="popup"
        class=${St({popup:!0,"popup--active":this.active,"popup--fixed":this.strategy==="fixed","popup--has-arrow":this.arrow})}
      >
        <slot></slot>
        ${this.arrow?I`<div part="arrow" class="popup__arrow" role="presentation"></div>`:""}
      </div>
    `}};M.styles=[kt,od];h([it(".popup")],M.prototype,"popup",2);h([it(".popup__arrow")],M.prototype,"arrowEl",2);h([y()],M.prototype,"anchor",2);h([y({type:Boolean,reflect:!0})],M.prototype,"active",2);h([y({reflect:!0})],M.prototype,"placement",2);h([y({reflect:!0})],M.prototype,"strategy",2);h([y({type:Number})],M.prototype,"distance",2);h([y({type:Number})],M.prototype,"skidding",2);h([y({type:Boolean})],M.prototype,"arrow",2);h([y({attribute:"arrow-placement"})],M.prototype,"arrowPlacement",2);h([y({attribute:"arrow-padding",type:Number})],M.prototype,"arrowPadding",2);h([y({type:Boolean})],M.prototype,"flip",2);h([y({attribute:"flip-fallback-placements",converter:{fromAttribute:t=>t.split(" ").map(e=>e.trim()).filter(e=>e!==""),toAttribute:t=>t.join(" ")}})],M.prototype,"flipFallbackPlacements",2);h([y({attribute:"flip-fallback-strategy"})],M.prototype,"flipFallbackStrategy",2);h([y({type:Object})],M.prototype,"flipBoundary",2);h([y({attribute:"flip-padding",type:Number})],M.prototype,"flipPadding",2);h([y({type:Boolean})],M.prototype,"shift",2);h([y({type:Object})],M.prototype,"shiftBoundary",2);h([y({attribute:"shift-padding",type:Number})],M.prototype,"shiftPadding",2);h([y({attribute:"auto-size"})],M.prototype,"autoSize",2);h([y()],M.prototype,"sync",2);h([y({type:Object})],M.prototype,"autoSizeBoundary",2);h([y({attribute:"auto-size-padding",type:Number})],M.prototype,"autoSizePadding",2);h([y({attribute:"hover-bridge",type:Boolean})],M.prototype,"hoverBridge",2);var ea=new Map,Wd=new WeakMap;function Kd(t){return t??{keyframes:[],options:{duration:0}}}function Es(t,e){return e.toLowerCase()==="rtl"?{keyframes:t.rtlKeyframes||t.keyframes,options:t.options}:t}function ia(t,e){ea.set(t,Kd(e))}function Ss(t,e,i){const n=Wd.get(t);if(n!=null&&n[e])return Es(n[e],i.dir);const r=ea.get(e);return r?Es(r,i.dir):{keyframes:[],options:{duration:0}}}function Cs(t,e){return new Promise(i=>{function n(r){r.target===t&&(t.removeEventListener(e,n),i())}t.addEventListener(e,n)})}function ks(t,e,i){return new Promise(n=>{if((i==null?void 0:i.duration)===1/0)throw new Error("Promise-based animations must be finite.");const r=t.animate(e,Hi(le({},i),{duration:Gd()?0:i.duration}));r.addEventListener("cancel",n,{once:!0}),r.addEventListener("finish",n,{once:!0})})}function Gd(){return window.matchMedia("(prefers-reduced-motion: reduce)").matches}function As(t){return Promise.all(t.getAnimations().map(e=>new Promise(i=>{e.cancel(),requestAnimationFrame(i)})))}var q=class extends nt{constructor(){super(...arguments),this.localize=new _e(this),this.open=!1,this.placement="bottom-start",this.disabled=!1,this.stayOpenOnSelect=!1,this.distance=0,this.skidding=0,this.hoist=!1,this.sync=void 0,this.handleKeyDown=t=>{this.open&&t.key==="Escape"&&(t.stopPropagation(),this.hide(),this.focusOnTrigger())},this.handleDocumentKeyDown=t=>{var e;if(t.key==="Escape"&&this.open&&!this.closeWatcher){t.stopPropagation(),this.focusOnTrigger(),this.hide();return}if(t.key==="Tab"){if(this.open&&((e=document.activeElement)==null?void 0:e.tagName.toLowerCase())==="sl-menu-item"){t.preventDefault(),this.hide(),this.focusOnTrigger();return}const i=(n,r)=>{if(!n)return null;const s=n.closest(r);if(s)return s;const o=n.getRootNode();return o instanceof ShadowRoot?i(o.host,r):null};setTimeout(()=>{var n;const r=((n=this.containingElement)==null?void 0:n.getRootNode())instanceof ShadowRoot?Zu():document.activeElement;(!this.containingElement||i(r,this.containingElement.tagName.toLowerCase())!==this.containingElement)&&this.hide()})}},this.handleDocumentMouseDown=t=>{const e=t.composedPath();this.containingElement&&!e.includes(this.containingElement)&&this.hide()},this.handlePanelSelect=t=>{const e=t.target;!this.stayOpenOnSelect&&e.tagName.toLowerCase()==="sl-menu"&&(this.hide(),this.focusOnTrigger())}}connectedCallback(){super.connectedCallback(),this.containingElement||(this.containingElement=this)}firstUpdated(){this.panel.hidden=!this.open,this.open&&(this.addOpenListeners(),this.popup.active=!0)}disconnectedCallback(){super.disconnectedCallback(),this.removeOpenListeners(),this.hide()}focusOnTrigger(){const t=this.trigger.assignedElements({flatten:!0})[0];typeof(t==null?void 0:t.focus)=="function"&&t.focus()}getMenu(){return this.panel.assignedElements({flatten:!0}).find(t=>t.tagName.toLowerCase()==="sl-menu")}handleTriggerClick(){this.open?this.hide():(this.show(),this.focusOnTrigger())}async handleTriggerKeyDown(t){if([" ","Enter"].includes(t.key)){t.preventDefault(),this.handleTriggerClick();return}const e=this.getMenu();if(e){const i=e.getAllItems(),n=i[0],r=i[i.length-1];["ArrowDown","ArrowUp","Home","End"].includes(t.key)&&(t.preventDefault(),this.open||(this.show(),await this.updateComplete),i.length>0&&this.updateComplete.then(()=>{(t.key==="ArrowDown"||t.key==="Home")&&(e.setCurrentItem(n),n.focus()),(t.key==="ArrowUp"||t.key==="End")&&(e.setCurrentItem(r),r.focus())}))}}handleTriggerKeyUp(t){t.key===" "&&t.preventDefault()}handleTriggerSlotChange(){this.updateAccessibleTrigger()}updateAccessibleTrigger(){const e=this.trigger.assignedElements({flatten:!0}).find(n=>nd(n).start);let i;if(e){switch(e.tagName.toLowerCase()){case"sl-button":case"sl-icon-button":i=e.button;break;default:i=e}i.setAttribute("aria-haspopup","true"),i.setAttribute("aria-expanded",this.open?"true":"false")}}async show(){if(!this.open)return this.open=!0,Cs(this,"sl-after-show")}async hide(){if(this.open)return this.open=!1,Cs(this,"sl-after-hide")}reposition(){this.popup.reposition()}addOpenListeners(){var t;this.panel.addEventListener("sl-select",this.handlePanelSelect),"CloseWatcher"in window?((t=this.closeWatcher)==null||t.destroy(),this.closeWatcher=new CloseWatcher,this.closeWatcher.onclose=()=>{this.hide(),this.focusOnTrigger()}):this.panel.addEventListener("keydown",this.handleKeyDown),document.addEventListener("keydown",this.handleDocumentKeyDown),document.addEventListener("mousedown",this.handleDocumentMouseDown)}removeOpenListeners(){var t;this.panel&&(this.panel.removeEventListener("sl-select",this.handlePanelSelect),this.panel.removeEventListener("keydown",this.handleKeyDown)),document.removeEventListener("keydown",this.handleDocumentKeyDown),document.removeEventListener("mousedown",this.handleDocumentMouseDown),(t=this.closeWatcher)==null||t.destroy()}async handleOpenChange(){if(this.disabled){this.open=!1;return}if(this.updateAccessibleTrigger(),this.open){this.emit("sl-show"),this.addOpenListeners(),await As(this),this.panel.hidden=!1,this.popup.active=!0;const{keyframes:t,options:e}=Ss(this,"dropdown.show",{dir:this.localize.dir()});await ks(this.popup.popup,t,e),this.emit("sl-after-show")}else{this.emit("sl-hide"),this.removeOpenListeners(),await As(this);const{keyframes:t,options:e}=Ss(this,"dropdown.hide",{dir:this.localize.dir()});await ks(this.popup.popup,t,e),this.panel.hidden=!0,this.popup.active=!1,this.emit("sl-after-hide")}}render(){return I`
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
        class=${St({dropdown:!0,"dropdown--open":this.open})}
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
    `}};q.styles=[kt,Qu];q.dependencies={"sl-popup":M};h([it(".dropdown")],q.prototype,"popup",2);h([it(".dropdown__trigger")],q.prototype,"trigger",2);h([it(".dropdown__panel")],q.prototype,"panel",2);h([y({type:Boolean,reflect:!0})],q.prototype,"open",2);h([y({reflect:!0})],q.prototype,"placement",2);h([y({type:Boolean,reflect:!0})],q.prototype,"disabled",2);h([y({attribute:"stay-open-on-select",type:Boolean,reflect:!0})],q.prototype,"stayOpenOnSelect",2);h([y({attribute:!1})],q.prototype,"containingElement",2);h([y({type:Number})],q.prototype,"distance",2);h([y({type:Number})],q.prototype,"skidding",2);h([y({type:Boolean})],q.prototype,"hoist",2);h([y({reflect:!0})],q.prototype,"sync",2);h([_t("open",{waitUntilFirstUpdate:!0})],q.prototype,"handleOpenChange",1);ia("dropdown.show",{keyframes:[{opacity:0,scale:.9},{opacity:1,scale:1}],options:{duration:100,easing:"ease"}});ia("dropdown.hide",{keyframes:[{opacity:1,scale:1},{opacity:0,scale:.9}],options:{duration:100,easing:"ease"}});var Xd=ht`
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
`;function rt(t,e,i){const n=r=>Object.is(r,-0)?0:r;return t<e?n(e):t>i?n(i):n(t)}var Jd=ht`
  :host {
    display: inline-block;
  }

  .button-group {
    display: flex;
    flex-wrap: nowrap;
  }
`,ii=class extends nt{constructor(){super(...arguments),this.disableRole=!1,this.label=""}handleFocus(t){const e=Pe(t.target);e==null||e.toggleAttribute("data-sl-button-group__button--focus",!0)}handleBlur(t){const e=Pe(t.target);e==null||e.toggleAttribute("data-sl-button-group__button--focus",!1)}handleMouseOver(t){const e=Pe(t.target);e==null||e.toggleAttribute("data-sl-button-group__button--hover",!0)}handleMouseOut(t){const e=Pe(t.target);e==null||e.toggleAttribute("data-sl-button-group__button--hover",!1)}handleSlotChange(){const t=[...this.defaultSlot.assignedElements({flatten:!0})];t.forEach(e=>{const i=t.indexOf(e),n=Pe(e);n&&(n.toggleAttribute("data-sl-button-group__button",!0),n.toggleAttribute("data-sl-button-group__button--first",i===0),n.toggleAttribute("data-sl-button-group__button--inner",i>0&&i<t.length-1),n.toggleAttribute("data-sl-button-group__button--last",i===t.length-1),n.toggleAttribute("data-sl-button-group__button--radio",n.tagName.toLowerCase()==="sl-radio-button"))})}render(){return I`
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
    `}};ii.styles=[kt,Jd];h([it("slot")],ii.prototype,"defaultSlot",2);h([et()],ii.prototype,"disableRole",2);h([y()],ii.prototype,"label",2);function Pe(t){var e;const i="sl-button, sl-radio-button";return(e=t.closest(i))!=null?e:t.querySelector(i)}var Yd=ht`
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
`,na=class extends nt{constructor(){super(...arguments),this.localize=new _e(this)}render(){return I`
      <svg part="base" class="spinner" role="progressbar" aria-label=${this.localize.term("loading")}>
        <circle class="spinner__track"></circle>
        <circle class="spinner__indicator"></circle>
      </svg>
    `}};na.styles=[kt,Yd];var Qd=ht`
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
 */const ra=Symbol.for(""),Zd=t=>{if((t==null?void 0:t.r)===ra)return t==null?void 0:t._$litStatic$},$s=(t,...e)=>({_$litStatic$:e.reduce((i,n,r)=>i+(s=>{if(s._$litStatic$!==void 0)return s._$litStatic$;throw Error(`Value passed to 'literal' function must be a 'literal' result: ${s}. Use 'unsafeStatic' to pass non-literal values, but
            take care to ensure page security.`)})(n)+t[r+1],t[0]),r:ra}),Rs=new Map,th=t=>(e,...i)=>{const n=i.length;let r,s;const o=[],a=[];let l,c=0,u=!1;for(;c<n;){for(l=e[c];c<n&&(s=i[c],(r=Zd(s))!==void 0);)l+=r+e[++c],u=!0;c!==n&&a.push(s),o.push(l),c++}if(c===n&&o.push(e[n]),u){const d=o.join("$$lit$$");(e=Rs.get(d))===void 0&&(o.raw=o,Rs.set(d,e=o)),i=a}return t(e,...i)},gn=th(I);var R=class extends nt{constructor(){super(...arguments),this.formControlController=new cr(this,{assumeInteractionOn:["click"]}),this.hasSlotController=new Bo(this,"[default]","prefix","suffix"),this.localize=new _e(this),this.hasFocus=!1,this.invalid=!1,this.title="",this.variant="default",this.size="medium",this.caret=!1,this.disabled=!1,this.loading=!1,this.outline=!1,this.pill=!1,this.circle=!1,this.type="button",this.name="",this.value="",this.href="",this.rel="noreferrer noopener"}get validity(){return this.isButton()?this.button.validity:ur}get validationMessage(){return this.isButton()?this.button.validationMessage:""}firstUpdated(){this.isButton()&&this.formControlController.updateValidity()}handleBlur(){this.hasFocus=!1,this.emit("sl-blur")}handleFocus(){this.hasFocus=!0,this.emit("sl-focus")}handleClick(){this.type==="submit"&&this.formControlController.submit(this),this.type==="reset"&&this.formControlController.reset(this)}handleInvalid(t){this.formControlController.setValidity(!1),this.formControlController.emitInvalidEvent(t)}isButton(){return!this.href}isLink(){return!!this.href}handleDisabledChange(){this.isButton()&&this.formControlController.setValidity(this.disabled)}click(){this.button.click()}focus(t){this.button.focus(t)}blur(){this.button.blur()}checkValidity(){return this.isButton()?this.button.checkValidity():!0}getForm(){return this.formControlController.getForm()}reportValidity(){return this.isButton()?this.button.reportValidity():!0}setCustomValidity(t){this.isButton()&&(this.button.setCustomValidity(t),this.formControlController.updateValidity())}render(){const t=this.isLink(),e=t?$s`a`:$s`button`;return gn`
      <${e}
        part="base"
        class=${St({button:!0,"button--default":this.variant==="default","button--primary":this.variant==="primary","button--success":this.variant==="success","button--neutral":this.variant==="neutral","button--warning":this.variant==="warning","button--danger":this.variant==="danger","button--text":this.variant==="text","button--small":this.size==="small","button--medium":this.size==="medium","button--large":this.size==="large","button--caret":this.caret,"button--circle":this.circle,"button--disabled":this.disabled,"button--focused":this.hasFocus,"button--loading":this.loading,"button--standard":!this.outline,"button--outline":this.outline,"button--pill":this.pill,"button--rtl":this.localize.dir()==="rtl","button--has-label":this.hasSlotController.test("[default]"),"button--has-prefix":this.hasSlotController.test("prefix"),"button--has-suffix":this.hasSlotController.test("suffix")})}
        ?disabled=${F(t?void 0:this.disabled)}
        type=${F(t?void 0:this.type)}
        title=${this.title}
        name=${F(t?void 0:this.name)}
        value=${F(t?void 0:this.value)}
        href=${F(t&&!this.disabled?this.href:void 0)}
        target=${F(t?this.target:void 0)}
        download=${F(t?this.download:void 0)}
        rel=${F(t?this.rel:void 0)}
        role=${F(t?void 0:"button")}
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
        ${this.caret?gn` <sl-icon part="caret" class="button__caret" library="system" name="caret"></sl-icon> `:""}
        ${this.loading?gn`<sl-spinner part="spinner"></sl-spinner>`:""}
      </${e}>
    `}};R.styles=[kt,Qd];R.dependencies={"sl-icon":pt,"sl-spinner":na};h([it(".button")],R.prototype,"button",2);h([et()],R.prototype,"hasFocus",2);h([et()],R.prototype,"invalid",2);h([y()],R.prototype,"title",2);h([y({reflect:!0})],R.prototype,"variant",2);h([y({reflect:!0})],R.prototype,"size",2);h([y({type:Boolean,reflect:!0})],R.prototype,"caret",2);h([y({type:Boolean,reflect:!0})],R.prototype,"disabled",2);h([y({type:Boolean,reflect:!0})],R.prototype,"loading",2);h([y({type:Boolean,reflect:!0})],R.prototype,"outline",2);h([y({type:Boolean,reflect:!0})],R.prototype,"pill",2);h([y({type:Boolean,reflect:!0})],R.prototype,"circle",2);h([y()],R.prototype,"type",2);h([y()],R.prototype,"name",2);h([y()],R.prototype,"value",2);h([y()],R.prototype,"href",2);h([y()],R.prototype,"target",2);h([y()],R.prototype,"rel",2);h([y()],R.prototype,"download",2);h([y()],R.prototype,"form",2);h([y({attribute:"formaction"})],R.prototype,"formAction",2);h([y({attribute:"formenctype"})],R.prototype,"formEnctype",2);h([y({attribute:"formmethod"})],R.prototype,"formMethod",2);h([y({attribute:"formnovalidate",type:Boolean})],R.prototype,"formNoValidate",2);h([y({attribute:"formtarget"})],R.prototype,"formTarget",2);h([_t("disabled",{waitUntilFirstUpdate:!0})],R.prototype,"handleDisabledChange",1);/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const sa="important",eh=" !"+sa,Ft=pr(class extends fr{constructor(t){var e;if(super(t),t.type!==Mt.ATTRIBUTE||t.name!=="style"||((e=t.strings)==null?void 0:e.length)>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(t){return Object.keys(t).reduce((e,i)=>{const n=t[i];return n==null?e:e+`${i=i.includes("-")?i:i.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,"-$&").toLowerCase()}:${n};`},"")}update(t,[e]){const{style:i}=t.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(e)),this.render(e);for(const n of this.ft)e[n]==null&&(this.ft.delete(n),n.includes("-")?i.removeProperty(n):i[n]=null);for(const n in e){const r=e[n];if(r!=null){this.ft.add(n);const s=typeof r=="string"&&r.endsWith(eh);n.includes("-")||s?i.setProperty(n,s?r.slice(0,-11):r,s?sa:""):i[n]=r}}return ot}});function U(t,e){ih(t)&&(t="100%");const i=nh(t);return t=e===360?t:Math.min(e,Math.max(0,parseFloat(t))),i&&(t=parseInt(String(t*e),10)/100),Math.abs(t-e)<1e-6?1:(e===360?t=(t<0?t%e+e:t%e)/parseFloat(String(e)):t=t%e/parseFloat(String(e)),t)}function ci(t){return Math.min(1,Math.max(0,t))}function ih(t){return typeof t=="string"&&t.indexOf(".")!==-1&&parseFloat(t)===1}function nh(t){return typeof t=="string"&&t.indexOf("%")!==-1}function oa(t){return t=parseFloat(t),(isNaN(t)||t<0||t>1)&&(t=1),t}function ui(t){return Number(t)<=1?`${Number(t)*100}%`:t}function Xt(t){return t.length===1?"0"+t:String(t)}function rh(t,e,i){return{r:U(t,255)*255,g:U(e,255)*255,b:U(i,255)*255}}function Ts(t,e,i){t=U(t,255),e=U(e,255),i=U(i,255);const n=Math.max(t,e,i),r=Math.min(t,e,i);let s=0,o=0;const a=(n+r)/2;if(n===r)o=0,s=0;else{const l=n-r;switch(o=a>.5?l/(2-n-r):l/(n+r),n){case t:s=(e-i)/l+(e<i?6:0);break;case e:s=(i-t)/l+2;break;case i:s=(t-e)/l+4;break}s/=6}return{h:s,s:o,l:a}}function mn(t,e,i){return i<0&&(i+=1),i>1&&(i-=1),i<1/6?t+(e-t)*(6*i):i<1/2?e:i<2/3?t+(e-t)*(2/3-i)*6:t}function sh(t,e,i){let n,r,s;if(t=U(t,360),e=U(e,100),i=U(i,100),e===0)r=i,s=i,n=i;else{const o=i<.5?i*(1+e):i+e-i*e,a=2*i-o;n=mn(a,o,t+1/3),r=mn(a,o,t),s=mn(a,o,t-1/3)}return{r:n*255,g:r*255,b:s*255}}function Fs(t,e,i){t=U(t,255),e=U(e,255),i=U(i,255);const n=Math.max(t,e,i),r=Math.min(t,e,i);let s=0;const o=n,a=n-r,l=n===0?0:a/n;if(n===r)s=0;else{switch(n){case t:s=(e-i)/a+(e<i?6:0);break;case e:s=(i-t)/a+2;break;case i:s=(t-e)/a+4;break}s/=6}return{h:s,s:l,v:o}}function oh(t,e,i){t=U(t,360)*6,e=U(e,100),i=U(i,100);const n=Math.floor(t),r=t-n,s=i*(1-e),o=i*(1-r*e),a=i*(1-(1-r)*e),l=n%6,c=[i,o,s,s,a,i][l],u=[a,i,i,o,s,s][l],d=[s,s,a,i,i,o][l];return{r:c*255,g:u*255,b:d*255}}function Os(t,e,i,n){const r=[Xt(Math.round(t).toString(16)),Xt(Math.round(e).toString(16)),Xt(Math.round(i).toString(16))];return n&&r[0].startsWith(r[0].charAt(1))&&r[1].startsWith(r[1].charAt(1))&&r[2].startsWith(r[2].charAt(1))?r[0].charAt(0)+r[1].charAt(0)+r[2].charAt(0):r.join("")}function ah(t,e,i,n,r){const s=[Xt(Math.round(t).toString(16)),Xt(Math.round(e).toString(16)),Xt(Math.round(i).toString(16)),Xt(ch(n))];return r&&s[0].startsWith(s[0].charAt(1))&&s[1].startsWith(s[1].charAt(1))&&s[2].startsWith(s[2].charAt(1))&&s[3].startsWith(s[3].charAt(1))?s[0].charAt(0)+s[1].charAt(0)+s[2].charAt(0)+s[3].charAt(0):s.join("")}function lh(t,e,i,n){const r=t/100,s=e/100,o=i/100,a=n/100,l=255*(1-r)*(1-a),c=255*(1-s)*(1-a),u=255*(1-o)*(1-a);return{r:l,g:c,b:u}}function Ms(t,e,i){let n=1-t/255,r=1-e/255,s=1-i/255,o=Math.min(n,r,s);return o===1?(n=0,r=0,s=0):(n=(n-o)/(1-o)*100,r=(r-o)/(1-o)*100,s=(s-o)/(1-o)*100),o*=100,{c:Math.round(n),m:Math.round(r),y:Math.round(s),k:Math.round(o)}}function ch(t){return Math.round(parseFloat(t)*255).toString(16)}function Ls(t){return Y(t)/255}function Y(t){return parseInt(t,16)}function uh(t){return{r:t>>16,g:(t&65280)>>8,b:t&255}}const In={aliceblue:"#f0f8ff",antiquewhite:"#faebd7",aqua:"#00ffff",aquamarine:"#7fffd4",azure:"#f0ffff",beige:"#f5f5dc",bisque:"#ffe4c4",black:"#000000",blanchedalmond:"#ffebcd",blue:"#0000ff",blueviolet:"#8a2be2",brown:"#a52a2a",burlywood:"#deb887",cadetblue:"#5f9ea0",chartreuse:"#7fff00",chocolate:"#d2691e",coral:"#ff7f50",cornflowerblue:"#6495ed",cornsilk:"#fff8dc",crimson:"#dc143c",cyan:"#00ffff",darkblue:"#00008b",darkcyan:"#008b8b",darkgoldenrod:"#b8860b",darkgray:"#a9a9a9",darkgreen:"#006400",darkgrey:"#a9a9a9",darkkhaki:"#bdb76b",darkmagenta:"#8b008b",darkolivegreen:"#556b2f",darkorange:"#ff8c00",darkorchid:"#9932cc",darkred:"#8b0000",darksalmon:"#e9967a",darkseagreen:"#8fbc8f",darkslateblue:"#483d8b",darkslategray:"#2f4f4f",darkslategrey:"#2f4f4f",darkturquoise:"#00ced1",darkviolet:"#9400d3",deeppink:"#ff1493",deepskyblue:"#00bfff",dimgray:"#696969",dimgrey:"#696969",dodgerblue:"#1e90ff",firebrick:"#b22222",floralwhite:"#fffaf0",forestgreen:"#228b22",fuchsia:"#ff00ff",gainsboro:"#dcdcdc",ghostwhite:"#f8f8ff",goldenrod:"#daa520",gold:"#ffd700",gray:"#808080",green:"#008000",greenyellow:"#adff2f",grey:"#808080",honeydew:"#f0fff0",hotpink:"#ff69b4",indianred:"#cd5c5c",indigo:"#4b0082",ivory:"#fffff0",khaki:"#f0e68c",lavenderblush:"#fff0f5",lavender:"#e6e6fa",lawngreen:"#7cfc00",lemonchiffon:"#fffacd",lightblue:"#add8e6",lightcoral:"#f08080",lightcyan:"#e0ffff",lightgoldenrodyellow:"#fafad2",lightgray:"#d3d3d3",lightgreen:"#90ee90",lightgrey:"#d3d3d3",lightpink:"#ffb6c1",lightsalmon:"#ffa07a",lightseagreen:"#20b2aa",lightskyblue:"#87cefa",lightslategray:"#778899",lightslategrey:"#778899",lightsteelblue:"#b0c4de",lightyellow:"#ffffe0",lime:"#00ff00",limegreen:"#32cd32",linen:"#faf0e6",magenta:"#ff00ff",maroon:"#800000",mediumaquamarine:"#66cdaa",mediumblue:"#0000cd",mediumorchid:"#ba55d3",mediumpurple:"#9370db",mediumseagreen:"#3cb371",mediumslateblue:"#7b68ee",mediumspringgreen:"#00fa9a",mediumturquoise:"#48d1cc",mediumvioletred:"#c71585",midnightblue:"#191970",mintcream:"#f5fffa",mistyrose:"#ffe4e1",moccasin:"#ffe4b5",navajowhite:"#ffdead",navy:"#000080",oldlace:"#fdf5e6",olive:"#808000",olivedrab:"#6b8e23",orange:"#ffa500",orangered:"#ff4500",orchid:"#da70d6",palegoldenrod:"#eee8aa",palegreen:"#98fb98",paleturquoise:"#afeeee",palevioletred:"#db7093",papayawhip:"#ffefd5",peachpuff:"#ffdab9",peru:"#cd853f",pink:"#ffc0cb",plum:"#dda0dd",powderblue:"#b0e0e6",purple:"#800080",rebeccapurple:"#663399",red:"#ff0000",rosybrown:"#bc8f8f",royalblue:"#4169e1",saddlebrown:"#8b4513",salmon:"#fa8072",sandybrown:"#f4a460",seagreen:"#2e8b57",seashell:"#fff5ee",sienna:"#a0522d",silver:"#c0c0c0",skyblue:"#87ceeb",slateblue:"#6a5acd",slategray:"#708090",slategrey:"#708090",snow:"#fffafa",springgreen:"#00ff7f",steelblue:"#4682b4",tan:"#d2b48c",teal:"#008080",thistle:"#d8bfd8",tomato:"#ff6347",turquoise:"#40e0d0",violet:"#ee82ee",wheat:"#f5deb3",white:"#ffffff",whitesmoke:"#f5f5f5",yellow:"#ffff00",yellowgreen:"#9acd32"};function dh(t){let e={r:0,g:0,b:0},i=1,n=null,r=null,s=null,o=!1,a=!1;return typeof t=="string"&&(t=fh(t)),typeof t=="object"&&(J(t.r)&&J(t.g)&&J(t.b)?(e=rh(t.r,t.g,t.b),o=!0,a=String(t.r).substr(-1)==="%"?"prgb":"rgb"):J(t.h)&&J(t.s)&&J(t.v)?(n=ui(t.s),r=ui(t.v),e=oh(t.h,n,r),o=!0,a="hsv"):J(t.h)&&J(t.s)&&J(t.l)?(n=ui(t.s),s=ui(t.l),e=sh(t.h,n,s),o=!0,a="hsl"):J(t.c)&&J(t.m)&&J(t.y)&&J(t.k)&&(e=lh(t.c,t.m,t.y,t.k),o=!0,a="cmyk"),Object.prototype.hasOwnProperty.call(t,"a")&&(i=t.a)),i=oa(i),{ok:o,format:t.format||a,r:Math.min(255,Math.max(e.r,0)),g:Math.min(255,Math.max(e.g,0)),b:Math.min(255,Math.max(e.b,0)),a:i}}const hh="[-\\+]?\\d+%?",ph="[-\\+]?\\d*\\.\\d+%?",zt="(?:"+ph+")|(?:"+hh+")",bn="[\\s|\\(]+("+zt+")[,|\\s]+("+zt+")[,|\\s]+("+zt+")\\s*\\)?",di="[\\s|\\(]+("+zt+")[,|\\s]+("+zt+")[,|\\s]+("+zt+")[,|\\s]+("+zt+")\\s*\\)?",st={CSS_UNIT:new RegExp(zt),rgb:new RegExp("rgb"+bn),rgba:new RegExp("rgba"+di),hsl:new RegExp("hsl"+bn),hsla:new RegExp("hsla"+di),hsv:new RegExp("hsv"+bn),hsva:new RegExp("hsva"+di),cmyk:new RegExp("cmyk"+di),hex3:/^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/,hex6:/^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/,hex4:/^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/,hex8:/^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/};function fh(t){if(t=t.trim().toLowerCase(),t.length===0)return!1;let e=!1;if(In[t])t=In[t],e=!0;else if(t==="transparent")return{r:0,g:0,b:0,a:0,format:"name"};let i=st.rgb.exec(t);return i?{r:i[1],g:i[2],b:i[3]}:(i=st.rgba.exec(t),i?{r:i[1],g:i[2],b:i[3],a:i[4]}:(i=st.hsl.exec(t),i?{h:i[1],s:i[2],l:i[3]}:(i=st.hsla.exec(t),i?{h:i[1],s:i[2],l:i[3],a:i[4]}:(i=st.hsv.exec(t),i?{h:i[1],s:i[2],v:i[3]}:(i=st.hsva.exec(t),i?{h:i[1],s:i[2],v:i[3],a:i[4]}:(i=st.cmyk.exec(t),i?{c:i[1],m:i[2],y:i[3],k:i[4]}:(i=st.hex8.exec(t),i?{r:Y(i[1]),g:Y(i[2]),b:Y(i[3]),a:Ls(i[4]),format:e?"name":"hex8"}:(i=st.hex6.exec(t),i?{r:Y(i[1]),g:Y(i[2]),b:Y(i[3]),format:e?"name":"hex"}:(i=st.hex4.exec(t),i?{r:Y(i[1]+i[1]),g:Y(i[2]+i[2]),b:Y(i[3]+i[3]),a:Ls(i[4]+i[4]),format:e?"name":"hex8"}:(i=st.hex3.exec(t),i?{r:Y(i[1]+i[1]),g:Y(i[2]+i[2]),b:Y(i[3]+i[3]),format:e?"name":"hex"}:!1))))))))))}function J(t){return typeof t=="number"?!Number.isNaN(t):st.CSS_UNIT.test(t)}class P{constructor(e="",i={}){if(e instanceof P)return e;typeof e=="number"&&(e=uh(e)),this.originalInput=e;const n=dh(e);this.originalInput=e,this.r=n.r,this.g=n.g,this.b=n.b,this.a=n.a,this.roundA=Math.round(100*this.a)/100,this.format=i.format??n.format,this.gradientType=i.gradientType,this.r<1&&(this.r=Math.round(this.r)),this.g<1&&(this.g=Math.round(this.g)),this.b<1&&(this.b=Math.round(this.b)),this.isValid=n.ok}isDark(){return this.getBrightness()<128}isLight(){return!this.isDark()}getBrightness(){const e=this.toRgb();return(e.r*299+e.g*587+e.b*114)/1e3}getLuminance(){const e=this.toRgb();let i,n,r;const s=e.r/255,o=e.g/255,a=e.b/255;return s<=.03928?i=s/12.92:i=Math.pow((s+.055)/1.055,2.4),o<=.03928?n=o/12.92:n=Math.pow((o+.055)/1.055,2.4),a<=.03928?r=a/12.92:r=Math.pow((a+.055)/1.055,2.4),.2126*i+.7152*n+.0722*r}getAlpha(){return this.a}setAlpha(e){return this.a=oa(e),this.roundA=Math.round(100*this.a)/100,this}isMonochrome(){const{s:e}=this.toHsl();return e===0}toHsv(){const e=Fs(this.r,this.g,this.b);return{h:e.h*360,s:e.s,v:e.v,a:this.a}}toHsvString(){const e=Fs(this.r,this.g,this.b),i=Math.round(e.h*360),n=Math.round(e.s*100),r=Math.round(e.v*100);return this.a===1?`hsv(${i}, ${n}%, ${r}%)`:`hsva(${i}, ${n}%, ${r}%, ${this.roundA})`}toHsl(){const e=Ts(this.r,this.g,this.b);return{h:e.h*360,s:e.s,l:e.l,a:this.a}}toHslString(){const e=Ts(this.r,this.g,this.b),i=Math.round(e.h*360),n=Math.round(e.s*100),r=Math.round(e.l*100);return this.a===1?`hsl(${i}, ${n}%, ${r}%)`:`hsla(${i}, ${n}%, ${r}%, ${this.roundA})`}toHex(e=!1){return Os(this.r,this.g,this.b,e)}toHexString(e=!1){return"#"+this.toHex(e)}toHex8(e=!1){return ah(this.r,this.g,this.b,this.a,e)}toHex8String(e=!1){return"#"+this.toHex8(e)}toHexShortString(e=!1){return this.a===1?this.toHexString(e):this.toHex8String(e)}toRgb(){return{r:Math.round(this.r),g:Math.round(this.g),b:Math.round(this.b),a:this.a}}toRgbString(){const e=Math.round(this.r),i=Math.round(this.g),n=Math.round(this.b);return this.a===1?`rgb(${e}, ${i}, ${n})`:`rgba(${e}, ${i}, ${n}, ${this.roundA})`}toPercentageRgb(){const e=i=>`${Math.round(U(i,255)*100)}%`;return{r:e(this.r),g:e(this.g),b:e(this.b),a:this.a}}toPercentageRgbString(){const e=i=>Math.round(U(i,255)*100);return this.a===1?`rgb(${e(this.r)}%, ${e(this.g)}%, ${e(this.b)}%)`:`rgba(${e(this.r)}%, ${e(this.g)}%, ${e(this.b)}%, ${this.roundA})`}toCmyk(){return{...Ms(this.r,this.g,this.b)}}toCmykString(){const{c:e,m:i,y:n,k:r}=Ms(this.r,this.g,this.b);return`cmyk(${e}, ${i}, ${n}, ${r})`}toName(){if(this.a===0)return"transparent";if(this.a<1)return!1;const e="#"+Os(this.r,this.g,this.b,!1);for(const[i,n]of Object.entries(In))if(e===n)return i;return!1}toString(e){const i=!!e;e=e??this.format;let n=!1;const r=this.a<1&&this.a>=0;return!i&&r&&(e.startsWith("hex")||e==="name")?e==="name"&&this.a===0?this.toName():this.toRgbString():(e==="rgb"&&(n=this.toRgbString()),e==="prgb"&&(n=this.toPercentageRgbString()),(e==="hex"||e==="hex6")&&(n=this.toHexString()),e==="hex3"&&(n=this.toHexString(!0)),e==="hex4"&&(n=this.toHex8String(!0)),e==="hex8"&&(n=this.toHex8String()),e==="name"&&(n=this.toName()),e==="hsl"&&(n=this.toHslString()),e==="hsv"&&(n=this.toHsvString()),e==="cmyk"&&(n=this.toCmykString()),n||this.toHexString())}toNumber(){return(Math.round(this.r)<<16)+(Math.round(this.g)<<8)+Math.round(this.b)}clone(){return new P(this.toString())}lighten(e=10){const i=this.toHsl();return i.l+=e/100,i.l=ci(i.l),new P(i)}brighten(e=10){const i=this.toRgb();return i.r=Math.max(0,Math.min(255,i.r-Math.round(255*-(e/100)))),i.g=Math.max(0,Math.min(255,i.g-Math.round(255*-(e/100)))),i.b=Math.max(0,Math.min(255,i.b-Math.round(255*-(e/100)))),new P(i)}darken(e=10){const i=this.toHsl();return i.l-=e/100,i.l=ci(i.l),new P(i)}tint(e=10){return this.mix("white",e)}shade(e=10){return this.mix("black",e)}desaturate(e=10){const i=this.toHsl();return i.s-=e/100,i.s=ci(i.s),new P(i)}saturate(e=10){const i=this.toHsl();return i.s+=e/100,i.s=ci(i.s),new P(i)}greyscale(){return this.desaturate(100)}spin(e){const i=this.toHsl(),n=(i.h+e)%360;return i.h=n<0?360+n:n,new P(i)}mix(e,i=50){const n=this.toRgb(),r=new P(e).toRgb(),s=i/100,o={r:(r.r-n.r)*s+n.r,g:(r.g-n.g)*s+n.g,b:(r.b-n.b)*s+n.b,a:(r.a-n.a)*s+n.a};return new P(o)}analogous(e=6,i=30){const n=this.toHsl(),r=360/i,s=[this];for(n.h=(n.h-(r*e>>1)+720)%360;--e;)n.h=(n.h+r)%360,s.push(new P(n));return s}complement(){const e=this.toHsl();return e.h=(e.h+180)%360,new P(e)}monochromatic(e=6){const i=this.toHsv(),{h:n}=i,{s:r}=i;let{v:s}=i;const o=[],a=1/e;for(;e--;)o.push(new P({h:n,s:r,v:s})),s=(s+a)%1;return o}splitcomplement(){const e=this.toHsl(),{h:i}=e;return[this,new P({h:(i+72)%360,s:e.s,l:e.l}),new P({h:(i+216)%360,s:e.s,l:e.l})]}onBackground(e){const i=this.toRgb(),n=new P(e).toRgb(),r=i.a+n.a*(1-i.a);return new P({r:(i.r*i.a+n.r*n.a*(1-i.a))/r,g:(i.g*i.a+n.g*n.a*(1-i.a))/r,b:(i.b*i.a+n.b*n.a*(1-i.a))/r,a:r})}triad(){return this.polyad(3)}tetrad(){return this.polyad(4)}polyad(e){const i=this.toHsl(),{h:n}=i,r=[this],s=360/e;for(let o=1;o<e;o++)r.push(new P({h:(n+o*s)%360,s:i.s,l:i.l}));return r}equals(e){const i=new P(e);return this.format==="cmyk"||i.format==="cmyk"?this.toCmykString()===i.toCmykString():this.toRgbString()===i.toRgbString()}}var zs="EyeDropper"in window,$=class extends nt{constructor(){super(),this.formControlController=new cr(this),this.isSafeValue=!1,this.localize=new _e(this),this.hasFocus=!1,this.isDraggingGridHandle=!1,this.isEmpty=!1,this.inputValue="",this.hue=0,this.saturation=100,this.brightness=100,this.alpha=100,this.value="",this.defaultValue="",this.label="",this.format="hex",this.inline=!1,this.size="medium",this.noFormatToggle=!1,this.name="",this.disabled=!1,this.hoist=!1,this.opacity=!1,this.uppercase=!1,this.swatches="",this.form="",this.required=!1,this.handleFocusIn=()=>{this.hasFocus=!0,this.emit("sl-focus")},this.handleFocusOut=()=>{this.hasFocus=!1,this.emit("sl-blur")},this.addEventListener("focusin",this.handleFocusIn),this.addEventListener("focusout",this.handleFocusOut)}get validity(){return this.input.validity}get validationMessage(){return this.input.validationMessage}firstUpdated(){this.input.updateComplete.then(()=>{this.formControlController.updateValidity()})}handleCopy(){this.input.select(),document.execCommand("copy"),this.previewButton.focus(),this.previewButton.classList.add("color-picker__preview-color--copied"),this.previewButton.addEventListener("animationend",()=>{this.previewButton.classList.remove("color-picker__preview-color--copied")})}handleFormatToggle(){const t=["hex","rgb","hsl","hsv"],e=(t.indexOf(this.format)+1)%t.length;this.format=t[e],this.setColor(this.value),this.emit("sl-change"),this.emit("sl-input")}handleAlphaDrag(t){const e=this.shadowRoot.querySelector(".color-picker__slider.color-picker__alpha"),i=e.querySelector(".color-picker__slider-handle"),{width:n}=e.getBoundingClientRect();let r=this.value,s=this.value;i.focus(),t.preventDefault(),hn(e,{onMove:o=>{this.alpha=rt(o/n*100,0,100),this.syncValues(),this.value!==s&&(s=this.value,this.emit("sl-input"))},onStop:()=>{this.value!==r&&(r=this.value,this.emit("sl-change"))},initialEvent:t})}handleHueDrag(t){const e=this.shadowRoot.querySelector(".color-picker__slider.color-picker__hue"),i=e.querySelector(".color-picker__slider-handle"),{width:n}=e.getBoundingClientRect();let r=this.value,s=this.value;i.focus(),t.preventDefault(),hn(e,{onMove:o=>{this.hue=rt(o/n*360,0,360),this.syncValues(),this.value!==s&&(s=this.value,this.emit("sl-input"))},onStop:()=>{this.value!==r&&(r=this.value,this.emit("sl-change"))},initialEvent:t})}handleGridDrag(t){const e=this.shadowRoot.querySelector(".color-picker__grid"),i=e.querySelector(".color-picker__grid-handle"),{width:n,height:r}=e.getBoundingClientRect();let s=this.value,o=this.value;i.focus(),t.preventDefault(),this.isDraggingGridHandle=!0,hn(e,{onMove:(a,l)=>{this.saturation=rt(a/n*100,0,100),this.brightness=rt(100-l/r*100,0,100),this.syncValues(),this.value!==o&&(o=this.value,this.emit("sl-input"))},onStop:()=>{this.isDraggingGridHandle=!1,this.value!==s&&(s=this.value,this.emit("sl-change"))},initialEvent:t})}handleAlphaKeyDown(t){const e=t.shiftKey?10:1,i=this.value;t.key==="ArrowLeft"&&(t.preventDefault(),this.alpha=rt(this.alpha-e,0,100),this.syncValues()),t.key==="ArrowRight"&&(t.preventDefault(),this.alpha=rt(this.alpha+e,0,100),this.syncValues()),t.key==="Home"&&(t.preventDefault(),this.alpha=0,this.syncValues()),t.key==="End"&&(t.preventDefault(),this.alpha=100,this.syncValues()),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleHueKeyDown(t){const e=t.shiftKey?10:1,i=this.value;t.key==="ArrowLeft"&&(t.preventDefault(),this.hue=rt(this.hue-e,0,360),this.syncValues()),t.key==="ArrowRight"&&(t.preventDefault(),this.hue=rt(this.hue+e,0,360),this.syncValues()),t.key==="Home"&&(t.preventDefault(),this.hue=0,this.syncValues()),t.key==="End"&&(t.preventDefault(),this.hue=360,this.syncValues()),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleGridKeyDown(t){const e=t.shiftKey?10:1,i=this.value;t.key==="ArrowLeft"&&(t.preventDefault(),this.saturation=rt(this.saturation-e,0,100),this.syncValues()),t.key==="ArrowRight"&&(t.preventDefault(),this.saturation=rt(this.saturation+e,0,100),this.syncValues()),t.key==="ArrowUp"&&(t.preventDefault(),this.brightness=rt(this.brightness+e,0,100),this.syncValues()),t.key==="ArrowDown"&&(t.preventDefault(),this.brightness=rt(this.brightness-e,0,100),this.syncValues()),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleInputChange(t){const e=t.target,i=this.value;t.stopPropagation(),this.input.value?(this.setColor(e.value),e.value=this.value):this.value="",this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}handleInputInput(t){this.formControlController.updateValidity(),t.stopPropagation()}handleInputKeyDown(t){if(t.key==="Enter"){const e=this.value;this.input.value?(this.setColor(this.input.value),this.input.value=this.value,this.value!==e&&(this.emit("sl-change"),this.emit("sl-input")),setTimeout(()=>this.input.select())):this.hue=0}}handleInputInvalid(t){this.formControlController.setValidity(!1),this.formControlController.emitInvalidEvent(t)}handleTouchMove(t){t.preventDefault()}parseColor(t){const e=new P(t);if(!e.isValid)return null;const i=e.toHsl(),n={h:i.h,s:i.s*100,l:i.l*100,a:i.a},r=e.toRgb(),s=e.toHexString(),o=e.toHex8String(),a=e.toHsv(),l={h:a.h,s:a.s*100,v:a.v*100,a:a.a};return{hsl:{h:n.h,s:n.s,l:n.l,string:this.setLetterCase(`hsl(${Math.round(n.h)}, ${Math.round(n.s)}%, ${Math.round(n.l)}%)`)},hsla:{h:n.h,s:n.s,l:n.l,a:n.a,string:this.setLetterCase(`hsla(${Math.round(n.h)}, ${Math.round(n.s)}%, ${Math.round(n.l)}%, ${n.a.toFixed(2).toString()})`)},hsv:{h:l.h,s:l.s,v:l.v,string:this.setLetterCase(`hsv(${Math.round(l.h)}, ${Math.round(l.s)}%, ${Math.round(l.v)}%)`)},hsva:{h:l.h,s:l.s,v:l.v,a:l.a,string:this.setLetterCase(`hsva(${Math.round(l.h)}, ${Math.round(l.s)}%, ${Math.round(l.v)}%, ${l.a.toFixed(2).toString()})`)},rgb:{r:r.r,g:r.g,b:r.b,string:this.setLetterCase(`rgb(${Math.round(r.r)}, ${Math.round(r.g)}, ${Math.round(r.b)})`)},rgba:{r:r.r,g:r.g,b:r.b,a:r.a,string:this.setLetterCase(`rgba(${Math.round(r.r)}, ${Math.round(r.g)}, ${Math.round(r.b)}, ${r.a.toFixed(2).toString()})`)},hex:this.setLetterCase(s),hexa:this.setLetterCase(o)}}setColor(t){const e=this.parseColor(t);return e===null?!1:(this.hue=e.hsva.h,this.saturation=e.hsva.s,this.brightness=e.hsva.v,this.alpha=this.opacity?e.hsva.a*100:100,this.syncValues(),!0)}setLetterCase(t){return typeof t!="string"?"":this.uppercase?t.toUpperCase():t.toLowerCase()}async syncValues(){const t=this.parseColor(`hsva(${this.hue}, ${this.saturation}%, ${this.brightness}%, ${this.alpha/100})`);t!==null&&(this.format==="hsl"?this.inputValue=this.opacity?t.hsla.string:t.hsl.string:this.format==="rgb"?this.inputValue=this.opacity?t.rgba.string:t.rgb.string:this.format==="hsv"?this.inputValue=this.opacity?t.hsva.string:t.hsv.string:this.inputValue=this.opacity?t.hexa:t.hex,this.isSafeValue=!0,this.value=this.inputValue,await this.updateComplete,this.isSafeValue=!1)}handleAfterHide(){this.previewButton.classList.remove("color-picker__preview-color--copied")}handleEyeDropper(){if(!zs)return;new EyeDropper().open().then(e=>{const i=this.value;this.setColor(e.sRGBHex),this.value!==i&&(this.emit("sl-change"),this.emit("sl-input"))}).catch(()=>{})}selectSwatch(t){const e=this.value;this.disabled||(this.setColor(t),this.value!==e&&(this.emit("sl-change"),this.emit("sl-input")))}getHexString(t,e,i,n=100){const r=new P(`hsva(${t}, ${e}%, ${i}%, ${n/100})`);return r.isValid?r.toHex8String():""}stopNestedEventPropagation(t){t.stopImmediatePropagation()}handleFormatChange(){this.syncValues()}handleOpacityChange(){this.alpha=100}handleValueChange(t,e){if(this.isEmpty=!e,e||(this.hue=0,this.saturation=0,this.brightness=100,this.alpha=100),!this.isSafeValue){const i=this.parseColor(e);i!==null?(this.inputValue=this.value,this.hue=i.hsva.h,this.saturation=i.hsva.s,this.brightness=i.hsva.v,this.alpha=i.hsva.a*100,this.syncValues()):this.inputValue=t??""}}focus(t){this.inline?this.base.focus(t):this.trigger.focus(t)}blur(){var t;const e=this.inline?this.base:this.trigger;this.hasFocus&&(e.focus({preventScroll:!0}),e.blur()),(t=this.dropdown)!=null&&t.open&&this.dropdown.hide()}getFormattedValue(t="hex"){const e=this.parseColor(`hsva(${this.hue}, ${this.saturation}%, ${this.brightness}%, ${this.alpha/100})`);if(e===null)return"";switch(t){case"hex":return e.hex;case"hexa":return e.hexa;case"rgb":return e.rgb.string;case"rgba":return e.rgba.string;case"hsl":return e.hsl.string;case"hsla":return e.hsla.string;case"hsv":return e.hsv.string;case"hsva":return e.hsva.string;default:return""}}checkValidity(){return this.input.checkValidity()}getForm(){return this.formControlController.getForm()}reportValidity(){return!this.inline&&!this.validity.valid?(this.dropdown.show(),this.addEventListener("sl-after-show",()=>this.input.reportValidity(),{once:!0}),this.disabled||this.formControlController.emitInvalidEvent(),!1):this.input.reportValidity()}setCustomValidity(t){this.input.setCustomValidity(t),this.formControlController.updateValidity()}render(){const t=this.saturation,e=100-this.brightness,i=Array.isArray(this.swatches)?this.swatches:this.swatches.split(";").filter(r=>r.trim()!==""),n=I`
      <div
        part="base"
        class=${St({"color-picker":!0,"color-picker--inline":this.inline,"color-picker--disabled":this.disabled,"color-picker--focused":this.hasFocus})}
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
            class=${St({"color-picker__grid-handle":!0,"color-picker__grid-handle--dragging":this.isDraggingGridHandle})}
            style=${Ft({top:`${e}%`,left:`${t}%`,backgroundColor:this.getHexString(this.hue,this.saturation,this.brightness,this.alpha)})}
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
                style=${Ft({left:`${this.hue===0?0:100/(360/this.hue)}%`})}
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
            ${zs?I`
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
                        style=${Ft({backgroundColor:s.hexa})}
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
          class=${St({"color-dropdown__trigger":!0,"color-dropdown__trigger--disabled":this.disabled,"color-dropdown__trigger--small":this.size==="small","color-dropdown__trigger--medium":this.size==="medium","color-dropdown__trigger--large":this.size==="large","color-dropdown__trigger--empty":this.isEmpty,"color-dropdown__trigger--focused":this.hasFocus,"color-picker__transparent-bg":!0})}
          style=${Ft({color:this.getHexString(this.hue,this.saturation,this.brightness,this.alpha)})}
          type="button"
        >
          <sl-visually-hidden>
            <slot name="label">${this.label}</slot>
          </sl-visually-hidden>
        </button>
        ${n}
      </sl-dropdown>
    `}};$.styles=[kt,Xd];$.dependencies={"sl-button-group":ii,"sl-button":R,"sl-dropdown":q,"sl-icon":pt,"sl-input":A,"sl-visually-hidden":Po};h([it('[part~="base"]')],$.prototype,"base",2);h([it('[part~="input"]')],$.prototype,"input",2);h([it(".color-dropdown")],$.prototype,"dropdown",2);h([it('[part~="preview"]')],$.prototype,"previewButton",2);h([it('[part~="trigger"]')],$.prototype,"trigger",2);h([et()],$.prototype,"hasFocus",2);h([et()],$.prototype,"isDraggingGridHandle",2);h([et()],$.prototype,"isEmpty",2);h([et()],$.prototype,"inputValue",2);h([et()],$.prototype,"hue",2);h([et()],$.prototype,"saturation",2);h([et()],$.prototype,"brightness",2);h([et()],$.prototype,"alpha",2);h([y()],$.prototype,"value",2);h([Io()],$.prototype,"defaultValue",2);h([y()],$.prototype,"label",2);h([y()],$.prototype,"format",2);h([y({type:Boolean,reflect:!0})],$.prototype,"inline",2);h([y({reflect:!0})],$.prototype,"size",2);h([y({attribute:"no-format-toggle",type:Boolean})],$.prototype,"noFormatToggle",2);h([y()],$.prototype,"name",2);h([y({type:Boolean,reflect:!0})],$.prototype,"disabled",2);h([y({type:Boolean})],$.prototype,"hoist",2);h([y({type:Boolean})],$.prototype,"opacity",2);h([y({type:Boolean})],$.prototype,"uppercase",2);h([y()],$.prototype,"swatches",2);h([y({reflect:!0})],$.prototype,"form",2);h([y({type:Boolean,reflect:!0})],$.prototype,"required",2);h([Ou({passive:!1})],$.prototype,"handleTouchMove",1);h([_t("format",{waitUntilFirstUpdate:!0})],$.prototype,"handleFormatChange",1);h([_t("opacity",{waitUntilFirstUpdate:!0})],$.prototype,"handleOpacityChange",1);h([_t("value")],$.prototype,"handleValueChange",1);$.define("sl-color-picker");var Bn=!1,Dn=!1,Zt=[],Nn=-1;function gh(t){mh(t)}function mh(t){Zt.includes(t)||Zt.push(t),yh()}function bh(t){let e=Zt.indexOf(t);e!==-1&&e>Nn&&Zt.splice(e,1)}function yh(){!Dn&&!Bn&&(Bn=!0,queueMicrotask(vh))}function vh(){Bn=!1,Dn=!0;for(let t=0;t<Zt.length;t++)Zt[t](),Nn=t;Zt.length=0,Nn=-1,Dn=!1}var Ce,ce,ke,aa,Un=!0;function wh(t){Un=!1,t(),Un=!0}function _h(t){Ce=t.reactive,ke=t.release,ce=e=>t.effect(e,{scheduler:i=>{Un?gh(i):i()}}),aa=t.raw}function Ps(t){ce=t}function xh(t){let e=()=>{};return[n=>{let r=ce(n);return t._x_effects||(t._x_effects=new Set,t._x_runEffects=()=>{t._x_effects.forEach(s=>s())}),t._x_effects.add(r),e=()=>{r!==void 0&&(t._x_effects.delete(r),ke(r))},r},()=>{e()}]}function la(t,e){let i=!0,n,r=ce(()=>{let s=t();JSON.stringify(s),i?n=s:queueMicrotask(()=>{e(s,n),n=s}),i=!1});return()=>ke(r)}var ca=[],ua=[],da=[];function Eh(t){da.push(t)}function _r(t,e){typeof e=="function"?(t._x_cleanups||(t._x_cleanups=[]),t._x_cleanups.push(e)):(e=t,ua.push(e))}function ha(t){ca.push(t)}function pa(t,e,i){t._x_attributeCleanups||(t._x_attributeCleanups={}),t._x_attributeCleanups[e]||(t._x_attributeCleanups[e]=[]),t._x_attributeCleanups[e].push(i)}function fa(t,e){t._x_attributeCleanups&&Object.entries(t._x_attributeCleanups).forEach(([i,n])=>{(e===void 0||e.includes(i))&&(n.forEach(r=>r()),delete t._x_attributeCleanups[i])})}function Sh(t){var e,i;for((e=t._x_effects)==null||e.forEach(bh);(i=t._x_cleanups)!=null&&i.length;)t._x_cleanups.pop()()}var xr=new MutationObserver(kr),Er=!1;function Sr(){xr.observe(document,{subtree:!0,childList:!0,attributes:!0,attributeOldValue:!0}),Er=!0}function ga(){Ch(),xr.disconnect(),Er=!1}var Ie=[];function Ch(){let t=xr.takeRecords();Ie.push(()=>t.length>0&&kr(t));let e=Ie.length;queueMicrotask(()=>{if(Ie.length===e)for(;Ie.length>0;)Ie.shift()()})}function z(t){if(!Er)return t();ga();let e=t();return Sr(),e}var Cr=!1,Fi=[];function kh(){Cr=!0}function Ah(){Cr=!1,kr(Fi),Fi=[]}function kr(t){if(Cr){Fi=Fi.concat(t);return}let e=[],i=new Set,n=new Map,r=new Map;for(let s=0;s<t.length;s++)if(!t[s].target._x_ignoreMutationObserver&&(t[s].type==="childList"&&(t[s].removedNodes.forEach(o=>{o.nodeType===1&&o._x_marker&&i.add(o)}),t[s].addedNodes.forEach(o=>{if(o.nodeType===1){if(i.has(o)){i.delete(o);return}o._x_marker||e.push(o)}})),t[s].type==="attributes")){let o=t[s].target,a=t[s].attributeName,l=t[s].oldValue,c=()=>{n.has(o)||n.set(o,[]),n.get(o).push({name:a,value:o.getAttribute(a)})},u=()=>{r.has(o)||r.set(o,[]),r.get(o).push(a)};o.hasAttribute(a)&&l===null?c():o.hasAttribute(a)?(u(),c()):u()}r.forEach((s,o)=>{fa(o,s)}),n.forEach((s,o)=>{ca.forEach(a=>a(o,s))});for(let s of i)e.some(o=>o.contains(s))||ua.forEach(o=>o(s));for(let s of e)s.isConnected&&da.forEach(o=>o(s));e=null,i=null,n=null,r=null}function ma(t){return ri(be(t))}function ni(t,e,i){return t._x_dataStack=[e,...be(i||t)],()=>{t._x_dataStack=t._x_dataStack.filter(n=>n!==e)}}function be(t){return t._x_dataStack?t._x_dataStack:typeof ShadowRoot=="function"&&t instanceof ShadowRoot?be(t.host):t.parentNode?be(t.parentNode):[]}function ri(t){return new Proxy({objects:t},$h)}var $h={ownKeys({objects:t}){return Array.from(new Set(t.flatMap(e=>Object.keys(e))))},has({objects:t},e){return e==Symbol.unscopables?!1:t.some(i=>Object.prototype.hasOwnProperty.call(i,e)||Reflect.has(i,e))},get({objects:t},e,i){return e=="toJSON"?Rh:Reflect.get(t.find(n=>Reflect.has(n,e))||{},e,i)},set({objects:t},e,i,n){const r=t.find(o=>Object.prototype.hasOwnProperty.call(o,e))||t[t.length-1],s=Object.getOwnPropertyDescriptor(r,e);return s!=null&&s.set&&(s!=null&&s.get)?s.set.call(n,i)||!0:Reflect.set(r,e,i)}};function Rh(){return Reflect.ownKeys(this).reduce((e,i)=>(e[i]=Reflect.get(this,i),e),{})}function ba(t){let e=n=>typeof n=="object"&&!Array.isArray(n)&&n!==null,i=(n,r="")=>{Object.entries(Object.getOwnPropertyDescriptors(n)).forEach(([s,{value:o,enumerable:a}])=>{if(a===!1||o===void 0||typeof o=="object"&&o!==null&&o.__v_skip)return;let l=r===""?s:`${r}.${s}`;typeof o=="object"&&o!==null&&o._x_interceptor?n[s]=o.initialize(t,l,s):e(o)&&o!==n&&!(o instanceof Element)&&i(o,l)})};return i(t)}function ya(t,e=()=>{}){let i={initialValue:void 0,_x_interceptor:!0,initialize(n,r,s){return t(this.initialValue,()=>Th(n,r),o=>Hn(n,r,o),r,s)}};return e(i),n=>{if(typeof n=="object"&&n!==null&&n._x_interceptor){let r=i.initialize.bind(i);i.initialize=(s,o,a)=>{let l=n.initialize(s,o,a);return i.initialValue=l,r(s,o,a)}}else i.initialValue=n;return i}}function Th(t,e){return e.split(".").reduce((i,n)=>i[n],t)}function Hn(t,e,i){if(typeof e=="string"&&(e=e.split(".")),e.length===1)t[e[0]]=i;else{if(e.length===0)throw error;return t[e[0]]||(t[e[0]]={}),Hn(t[e[0]],e.slice(1),i)}}var va={};function ft(t,e){va[t]=e}function Vn(t,e){let i=Fh(e);return Object.entries(va).forEach(([n,r])=>{Object.defineProperty(t,`$${n}`,{get(){return r(e,i)},enumerable:!1})}),t}function Fh(t){let[e,i]=Ca(t),n={interceptor:ya,...e};return _r(t,i),n}function Oh(t,e,i,...n){try{return i(...n)}catch(r){Ye(r,t,e)}}function Ye(t,e,i=void 0){t=Object.assign(t??{message:"No error message given."},{el:e,expression:i}),console.warn(`Alpine Expression Error: ${t.message}

${i?'Expression: "'+i+`"

`:""}`,e),setTimeout(()=>{throw t},0)}var Ei=!0;function wa(t){let e=Ei;Ei=!1;let i=t();return Ei=e,i}function te(t,e,i={}){let n;return K(t,e)(r=>n=r,i),n}function K(...t){return _a(...t)}var _a=xa;function Mh(t){_a=t}function xa(t,e){let i={};Vn(i,t);let n=[i,...be(t)],r=typeof e=="function"?Lh(n,e):Ph(n,e,t);return Oh.bind(null,t,e,r)}function Lh(t,e){return(i=()=>{},{scope:n={},params:r=[]}={})=>{let s=e.apply(ri([n,...t]),r);Oi(i,s)}}var yn={};function zh(t,e){if(yn[t])return yn[t];let i=Object.getPrototypeOf(async function(){}).constructor,n=/^[\n\s]*if.*\(.*\)/.test(t.trim())||/^(let|const)\s/.test(t.trim())?`(async()=>{ ${t} })()`:t,s=(()=>{try{let o=new i(["__self","scope"],`with (scope) { __self.result = ${n} }; __self.finished = true; return __self.result;`);return Object.defineProperty(o,"name",{value:`[Alpine] ${t}`}),o}catch(o){return Ye(o,e,t),Promise.resolve()}})();return yn[t]=s,s}function Ph(t,e,i){let n=zh(e,i);return(r=()=>{},{scope:s={},params:o=[]}={})=>{n.result=void 0,n.finished=!1;let a=ri([s,...t]);if(typeof n=="function"){let l=n(n,a).catch(c=>Ye(c,i,e));n.finished?(Oi(r,n.result,a,o,i),n.result=void 0):l.then(c=>{Oi(r,c,a,o,i)}).catch(c=>Ye(c,i,e)).finally(()=>n.result=void 0)}}}function Oi(t,e,i,n,r){if(Ei&&typeof e=="function"){let s=e.apply(i,n);s instanceof Promise?s.then(o=>Oi(t,o,i,n)).catch(o=>Ye(o,r,e)):t(s)}else typeof e=="object"&&e instanceof Promise?e.then(s=>t(s)):t(e)}var Ar="x-";function Ae(t=""){return Ar+t}function Ih(t){Ar=t}var Mi={};function N(t,e){return Mi[t]=e,{before(i){if(!Mi[i]){console.warn(String.raw`Cannot find directive \`${i}\`. \`${t}\` will use the default order of execution`);return}const n=Jt.indexOf(i);Jt.splice(n>=0?n:Jt.indexOf("DEFAULT"),0,t)}}}function Bh(t){return Object.keys(Mi).includes(t)}function $r(t,e,i){if(e=Array.from(e),t._x_virtualDirectives){let s=Object.entries(t._x_virtualDirectives).map(([a,l])=>({name:a,value:l})),o=Ea(s);s=s.map(a=>o.find(l=>l.name===a.name)?{name:`x-bind:${a.name}`,value:`"${a.value}"`}:a),e=e.concat(s)}let n={};return e.map($a((s,o)=>n[s]=o)).filter(Ta).map(Uh(n,i)).sort(Hh).map(s=>Nh(t,s))}function Ea(t){return Array.from(t).map($a()).filter(e=>!Ta(e))}var qn=!1,Ne=new Map,Sa=Symbol();function Dh(t){qn=!0;let e=Symbol();Sa=e,Ne.set(e,[]);let i=()=>{for(;Ne.get(e).length;)Ne.get(e).shift()();Ne.delete(e)},n=()=>{qn=!1,i()};t(i),n()}function Ca(t){let e=[],i=a=>e.push(a),[n,r]=xh(t);return e.push(r),[{Alpine:si,effect:n,cleanup:i,evaluateLater:K.bind(K,t),evaluate:te.bind(te,t)},()=>e.forEach(a=>a())]}function Nh(t,e){let i=()=>{},n=Mi[e.type]||i,[r,s]=Ca(t);pa(t,e.original,s);let o=()=>{t._x_ignore||t._x_ignoreSelf||(n.inline&&n.inline(t,e,r),n=n.bind(n,t,e,r),qn?Ne.get(Sa).push(n):n())};return o.runCleanups=s,o}var ka=(t,e)=>({name:i,value:n})=>(i.startsWith(t)&&(i=i.replace(t,e)),{name:i,value:n}),Aa=t=>t;function $a(t=()=>{}){return({name:e,value:i})=>{let{name:n,value:r}=Ra.reduce((s,o)=>o(s),{name:e,value:i});return n!==e&&t(n,e),{name:n,value:r}}}var Ra=[];function Rr(t){Ra.push(t)}function Ta({name:t}){return Fa().test(t)}var Fa=()=>new RegExp(`^${Ar}([^:^.]+)\\b`);function Uh(t,e){return({name:i,value:n})=>{let r=i.match(Fa()),s=i.match(/:([a-zA-Z0-9\-_:]+)/),o=i.match(/\.[^.\]]+(?=[^\]]*$)/g)||[],a=e||t[i]||i;return{type:r?r[1]:null,value:s?s[1]:null,modifiers:o.map(l=>l.replace(".","")),expression:n,original:a}}}var jn="DEFAULT",Jt=["ignore","ref","data","id","anchor","bind","init","for","model","modelable","transition","show","if",jn,"teleport"];function Hh(t,e){let i=Jt.indexOf(t.type)===-1?jn:t.type,n=Jt.indexOf(e.type)===-1?jn:e.type;return Jt.indexOf(i)-Jt.indexOf(n)}function qe(t,e,i={}){t.dispatchEvent(new CustomEvent(e,{detail:i,bubbles:!0,composed:!0,cancelable:!0}))}function ae(t,e){if(typeof ShadowRoot=="function"&&t instanceof ShadowRoot){Array.from(t.children).forEach(r=>ae(r,e));return}let i=!1;if(e(t,()=>i=!0),i)return;let n=t.firstElementChild;for(;n;)ae(n,e),n=n.nextElementSibling}function at(t,...e){console.warn(`Alpine Warning: ${t}`,...e)}var Is=!1;function Vh(){Is&&at("Alpine has already been initialized on this page. Calling Alpine.start() more than once can cause problems."),Is=!0,document.body||at("Unable to initialize. Trying to load Alpine before `<body>` is available. Did you forget to add `defer` in Alpine's `<script>` tag?"),qe(document,"alpine:init"),qe(document,"alpine:initializing"),Sr(),Eh(e=>Ct(e,ae)),_r(e=>Re(e)),ha((e,i)=>{$r(e,i).forEach(n=>n())});let t=e=>!Ki(e.parentElement,!0);Array.from(document.querySelectorAll(La().join(","))).filter(t).forEach(e=>{Ct(e)}),qe(document,"alpine:initialized"),setTimeout(()=>{Kh()})}var Tr=[],Oa=[];function Ma(){return Tr.map(t=>t())}function La(){return Tr.concat(Oa).map(t=>t())}function za(t){Tr.push(t)}function Pa(t){Oa.push(t)}function Ki(t,e=!1){return $e(t,i=>{if((e?La():Ma()).some(r=>i.matches(r)))return!0})}function $e(t,e){if(t){if(e(t))return t;if(t._x_teleportBack&&(t=t._x_teleportBack),!!t.parentElement)return $e(t.parentElement,e)}}function qh(t){return Ma().some(e=>t.matches(e))}var Ia=[];function jh(t){Ia.push(t)}var Wh=1;function Ct(t,e=ae,i=()=>{}){$e(t,n=>n._x_ignore)||Dh(()=>{e(t,(n,r)=>{n._x_marker||(i(n,r),Ia.forEach(s=>s(n,r)),$r(n,n.attributes).forEach(s=>s()),n._x_ignore||(n._x_marker=Wh++),n._x_ignore&&r())})})}function Re(t,e=ae){e(t,i=>{Sh(i),fa(i),delete i._x_marker})}function Kh(){[["ui","dialog",["[x-dialog], [x-popover]"]],["anchor","anchor",["[x-anchor]"]],["sort","sort",["[x-sort]"]]].forEach(([e,i,n])=>{Bh(i)||n.some(r=>{if(document.querySelector(r))return at(`found "${r}", but missing ${e} plugin`),!0})})}var Wn=[],Fr=!1;function Or(t=()=>{}){return queueMicrotask(()=>{Fr||setTimeout(()=>{Kn()})}),new Promise(e=>{Wn.push(()=>{t(),e()})})}function Kn(){for(Fr=!1;Wn.length;)Wn.shift()()}function Gh(){Fr=!0}function Mr(t,e){return Array.isArray(e)?Bs(t,e.join(" ")):typeof e=="object"&&e!==null?Xh(t,e):typeof e=="function"?Mr(t,e()):Bs(t,e)}function Bs(t,e){let i=r=>r.split(" ").filter(s=>!t.classList.contains(s)).filter(Boolean),n=r=>(t.classList.add(...r),()=>{t.classList.remove(...r)});return e=e===!0?e="":e||"",n(i(e))}function Xh(t,e){let i=a=>a.split(" ").filter(Boolean),n=Object.entries(e).flatMap(([a,l])=>l?i(a):!1).filter(Boolean),r=Object.entries(e).flatMap(([a,l])=>l?!1:i(a)).filter(Boolean),s=[],o=[];return r.forEach(a=>{t.classList.contains(a)&&(t.classList.remove(a),o.push(a))}),n.forEach(a=>{t.classList.contains(a)||(t.classList.add(a),s.push(a))}),()=>{o.forEach(a=>t.classList.add(a)),s.forEach(a=>t.classList.remove(a))}}function Gi(t,e){return typeof e=="object"&&e!==null?Jh(t,e):Yh(t,e)}function Jh(t,e){let i={};return Object.entries(e).forEach(([n,r])=>{i[n]=t.style[n],n.startsWith("--")||(n=Qh(n)),t.style.setProperty(n,r)}),setTimeout(()=>{t.style.length===0&&t.removeAttribute("style")}),()=>{Gi(t,i)}}function Yh(t,e){let i=t.getAttribute("style",e);return t.setAttribute("style",e),()=>{t.setAttribute("style",i||"")}}function Qh(t){return t.replace(/([a-z])([A-Z])/g,"$1-$2").toLowerCase()}function Gn(t,e=()=>{}){let i=!1;return function(){i?e.apply(this,arguments):(i=!0,t.apply(this,arguments))}}N("transition",(t,{value:e,modifiers:i,expression:n},{evaluate:r})=>{typeof n=="function"&&(n=r(n)),n!==!1&&(!n||typeof n=="boolean"?tp(t,i,e):Zh(t,n,e))});function Zh(t,e,i){Ba(t,Mr,""),{enter:r=>{t._x_transition.enter.during=r},"enter-start":r=>{t._x_transition.enter.start=r},"enter-end":r=>{t._x_transition.enter.end=r},leave:r=>{t._x_transition.leave.during=r},"leave-start":r=>{t._x_transition.leave.start=r},"leave-end":r=>{t._x_transition.leave.end=r}}[i](e)}function tp(t,e,i){Ba(t,Gi);let n=!e.includes("in")&&!e.includes("out")&&!i,r=n||e.includes("in")||["enter"].includes(i),s=n||e.includes("out")||["leave"].includes(i);e.includes("in")&&!n&&(e=e.filter((_,x)=>x<e.indexOf("out"))),e.includes("out")&&!n&&(e=e.filter((_,x)=>x>e.indexOf("out")));let o=!e.includes("opacity")&&!e.includes("scale"),a=o||e.includes("opacity"),l=o||e.includes("scale"),c=a?0:1,u=l?Be(e,"scale",95)/100:1,d=Be(e,"delay",0)/1e3,p=Be(e,"origin","center"),f="opacity, transform",b=Be(e,"duration",150)/1e3,w=Be(e,"duration",75)/1e3,g="cubic-bezier(0.4, 0.0, 0.2, 1)";r&&(t._x_transition.enter.during={transformOrigin:p,transitionDelay:`${d}s`,transitionProperty:f,transitionDuration:`${b}s`,transitionTimingFunction:g},t._x_transition.enter.start={opacity:c,transform:`scale(${u})`},t._x_transition.enter.end={opacity:1,transform:"scale(1)"}),s&&(t._x_transition.leave.during={transformOrigin:p,transitionDelay:`${d}s`,transitionProperty:f,transitionDuration:`${w}s`,transitionTimingFunction:g},t._x_transition.leave.start={opacity:1,transform:"scale(1)"},t._x_transition.leave.end={opacity:c,transform:`scale(${u})`})}function Ba(t,e,i={}){t._x_transition||(t._x_transition={enter:{during:i,start:i,end:i},leave:{during:i,start:i,end:i},in(n=()=>{},r=()=>{}){Xn(t,e,{during:this.enter.during,start:this.enter.start,end:this.enter.end},n,r)},out(n=()=>{},r=()=>{}){Xn(t,e,{during:this.leave.during,start:this.leave.start,end:this.leave.end},n,r)}})}window.Element.prototype._x_toggleAndCascadeWithTransitions=function(t,e,i,n){const r=document.visibilityState==="visible"?requestAnimationFrame:setTimeout;let s=()=>r(i);if(e){t._x_transition&&(t._x_transition.enter||t._x_transition.leave)?t._x_transition.enter&&(Object.entries(t._x_transition.enter.during).length||Object.entries(t._x_transition.enter.start).length||Object.entries(t._x_transition.enter.end).length)?t._x_transition.in(i):s():t._x_transition?t._x_transition.in(i):s();return}t._x_hidePromise=t._x_transition?new Promise((o,a)=>{t._x_transition.out(()=>{},()=>o(n)),t._x_transitioning&&t._x_transitioning.beforeCancel(()=>a({isFromCancelledTransition:!0}))}):Promise.resolve(n),queueMicrotask(()=>{let o=Da(t);o?(o._x_hideChildren||(o._x_hideChildren=[]),o._x_hideChildren.push(t)):r(()=>{let a=l=>{let c=Promise.all([l._x_hidePromise,...(l._x_hideChildren||[]).map(a)]).then(([u])=>u==null?void 0:u());return delete l._x_hidePromise,delete l._x_hideChildren,c};a(t).catch(l=>{if(!l.isFromCancelledTransition)throw l})})})};function Da(t){let e=t.parentNode;if(e)return e._x_hidePromise?e:Da(e)}function Xn(t,e,{during:i,start:n,end:r}={},s=()=>{},o=()=>{}){if(t._x_transitioning&&t._x_transitioning.cancel(),Object.keys(i).length===0&&Object.keys(n).length===0&&Object.keys(r).length===0){s(),o();return}let a,l,c;ep(t,{start(){a=e(t,n)},during(){l=e(t,i)},before:s,end(){a(),c=e(t,r)},after:o,cleanup(){l(),c()}})}function ep(t,e){let i,n,r,s=Gn(()=>{z(()=>{i=!0,n||e.before(),r||(e.end(),Kn()),e.after(),t.isConnected&&e.cleanup(),delete t._x_transitioning})});t._x_transitioning={beforeCancels:[],beforeCancel(o){this.beforeCancels.push(o)},cancel:Gn(function(){for(;this.beforeCancels.length;)this.beforeCancels.shift()();s()}),finish:s},z(()=>{e.start(),e.during()}),Gh(),requestAnimationFrame(()=>{if(i)return;let o=Number(getComputedStyle(t).transitionDuration.replace(/,.*/,"").replace("s",""))*1e3,a=Number(getComputedStyle(t).transitionDelay.replace(/,.*/,"").replace("s",""))*1e3;o===0&&(o=Number(getComputedStyle(t).animationDuration.replace("s",""))*1e3),z(()=>{e.before()}),n=!0,requestAnimationFrame(()=>{i||(z(()=>{e.end()}),Kn(),setTimeout(t._x_transitioning.finish,o+a),r=!0)})})}function Be(t,e,i){if(t.indexOf(e)===-1)return i;const n=t[t.indexOf(e)+1];if(!n||e==="scale"&&isNaN(n))return i;if(e==="duration"||e==="delay"){let r=n.match(/([0-9]+)ms/);if(r)return r[1]}return e==="origin"&&["top","right","left","center","bottom"].includes(t[t.indexOf(e)+2])?[n,t[t.indexOf(e)+2]].join(" "):n}var Nt=!1;function Ht(t,e=()=>{}){return(...i)=>Nt?e(...i):t(...i)}function ip(t){return(...e)=>Nt&&t(...e)}var Na=[];function Xi(t){Na.push(t)}function np(t,e){Na.forEach(i=>i(t,e)),Nt=!0,Ua(()=>{Ct(e,(i,n)=>{n(i,()=>{})})}),Nt=!1}var Jn=!1;function rp(t,e){e._x_dataStack||(e._x_dataStack=t._x_dataStack),Nt=!0,Jn=!0,Ua(()=>{sp(e)}),Nt=!1,Jn=!1}function sp(t){let e=!1;Ct(t,(n,r)=>{ae(n,(s,o)=>{if(e&&qh(s))return o();e=!0,r(s,o)})})}function Ua(t){let e=ce;Ps((i,n)=>{let r=e(i);return ke(r),()=>{}}),t(),Ps(e)}function Ha(t,e,i,n=[]){switch(t._x_bindings||(t._x_bindings=Ce({})),t._x_bindings[e]=i,e=n.includes("camel")?pp(e):e,e){case"value":op(t,i);break;case"style":lp(t,i);break;case"class":ap(t,i);break;case"selected":case"checked":cp(t,e,i);break;default:Va(t,e,i);break}}function op(t,e){if(Wa(t))t.attributes.value===void 0&&(t.value=e),window.fromModel&&(typeof e=="boolean"?t.checked=Si(t.value)===e:t.checked=Ds(t.value,e));else if(Lr(t))Number.isInteger(e)?t.value=e:!Array.isArray(e)&&typeof e!="boolean"&&![null,void 0].includes(e)?t.value=String(e):Array.isArray(e)?t.checked=e.some(i=>Ds(i,t.value)):t.checked=!!e;else if(t.tagName==="SELECT")hp(t,e);else{if(t.value===e)return;t.value=e===void 0?"":e}}function ap(t,e){t._x_undoAddedClasses&&t._x_undoAddedClasses(),t._x_undoAddedClasses=Mr(t,e)}function lp(t,e){t._x_undoAddedStyles&&t._x_undoAddedStyles(),t._x_undoAddedStyles=Gi(t,e)}function cp(t,e,i){Va(t,e,i),dp(t,e,i)}function Va(t,e,i){[null,void 0,!1].includes(i)&&gp(e)?t.removeAttribute(e):(qa(e)&&(i=e),up(t,e,i))}function up(t,e,i){t.getAttribute(e)!=i&&t.setAttribute(e,i)}function dp(t,e,i){t[e]!==i&&(t[e]=i)}function hp(t,e){const i=[].concat(e).map(n=>n+"");Array.from(t.options).forEach(n=>{n.selected=i.includes(n.value)})}function pp(t){return t.toLowerCase().replace(/-(\w)/g,(e,i)=>i.toUpperCase())}function Ds(t,e){return t==e}function Si(t){return[1,"1","true","on","yes",!0].includes(t)?!0:[0,"0","false","off","no",!1].includes(t)?!1:t?!!t:null}var fp=new Set(["allowfullscreen","async","autofocus","autoplay","checked","controls","default","defer","disabled","formnovalidate","inert","ismap","itemscope","loop","multiple","muted","nomodule","novalidate","open","playsinline","readonly","required","reversed","selected","shadowrootclonable","shadowrootdelegatesfocus","shadowrootserializable"]);function qa(t){return fp.has(t)}function gp(t){return!["aria-pressed","aria-checked","aria-expanded","aria-selected"].includes(t)}function mp(t,e,i){return t._x_bindings&&t._x_bindings[e]!==void 0?t._x_bindings[e]:ja(t,e,i)}function bp(t,e,i,n=!0){if(t._x_bindings&&t._x_bindings[e]!==void 0)return t._x_bindings[e];if(t._x_inlineBindings&&t._x_inlineBindings[e]!==void 0){let r=t._x_inlineBindings[e];return r.extract=n,wa(()=>te(t,r.expression))}return ja(t,e,i)}function ja(t,e,i){let n=t.getAttribute(e);return n===null?typeof i=="function"?i():i:n===""?!0:qa(e)?!![e,"true"].includes(n):n}function Lr(t){return t.type==="checkbox"||t.localName==="ui-checkbox"||t.localName==="ui-switch"}function Wa(t){return t.type==="radio"||t.localName==="ui-radio"}function Ka(t,e){var i;return function(){var n=this,r=arguments,s=function(){i=null,t.apply(n,r)};clearTimeout(i),i=setTimeout(s,e)}}function Ga(t,e){let i;return function(){let n=this,r=arguments;i||(t.apply(n,r),i=!0,setTimeout(()=>i=!1,e))}}function Xa({get:t,set:e},{get:i,set:n}){let r=!0,s,o=ce(()=>{let a=t(),l=i();if(r)n(vn(a)),r=!1;else{let c=JSON.stringify(a),u=JSON.stringify(l);c!==s?n(vn(a)):c!==u&&e(vn(l))}s=JSON.stringify(t()),JSON.stringify(i())});return()=>{ke(o)}}function vn(t){return typeof t=="object"?JSON.parse(JSON.stringify(t)):t}function yp(t){(Array.isArray(t)?t:[t]).forEach(i=>i(si))}var jt={},Ns=!1;function vp(t,e){if(Ns||(jt=Ce(jt),Ns=!0),e===void 0)return jt[t];jt[t]=e,ba(jt[t]),typeof e=="object"&&e!==null&&e.hasOwnProperty("init")&&typeof e.init=="function"&&jt[t].init()}function wp(){return jt}var Ja={};function _p(t,e){let i=typeof e!="function"?()=>e:e;return t instanceof Element?Ya(t,i()):(Ja[t]=i,()=>{})}function xp(t){return Object.entries(Ja).forEach(([e,i])=>{Object.defineProperty(t,e,{get(){return(...n)=>i(...n)}})}),t}function Ya(t,e,i){let n=[];for(;n.length;)n.pop()();let r=Object.entries(e).map(([o,a])=>({name:o,value:a})),s=Ea(r);return r=r.map(o=>s.find(a=>a.name===o.name)?{name:`x-bind:${o.name}`,value:`"${o.value}"`}:o),$r(t,r,i).map(o=>{n.push(o.runCleanups),o()}),()=>{for(;n.length;)n.pop()()}}var Qa={};function Ep(t,e){Qa[t]=e}function Sp(t,e){return Object.entries(Qa).forEach(([i,n])=>{Object.defineProperty(t,i,{get(){return(...r)=>n.bind(e)(...r)},enumerable:!1})}),t}var Cp={get reactive(){return Ce},get release(){return ke},get effect(){return ce},get raw(){return aa},version:"3.14.9",flushAndStopDeferringMutations:Ah,dontAutoEvaluateFunctions:wa,disableEffectScheduling:wh,startObservingMutations:Sr,stopObservingMutations:ga,setReactivityEngine:_h,onAttributeRemoved:pa,onAttributesAdded:ha,closestDataStack:be,skipDuringClone:Ht,onlyDuringClone:ip,addRootSelector:za,addInitSelector:Pa,interceptClone:Xi,addScopeToNode:ni,deferMutations:kh,mapAttributes:Rr,evaluateLater:K,interceptInit:jh,setEvaluator:Mh,mergeProxies:ri,extractProp:bp,findClosest:$e,onElRemoved:_r,closestRoot:Ki,destroyTree:Re,interceptor:ya,transition:Xn,setStyles:Gi,mutateDom:z,directive:N,entangle:Xa,throttle:Ga,debounce:Ka,evaluate:te,initTree:Ct,nextTick:Or,prefixed:Ae,prefix:Ih,plugin:yp,magic:ft,store:vp,start:Vh,clone:rp,cloneNode:np,bound:mp,$data:ma,watch:la,walk:ae,data:Ep,bind:_p},si=Cp;function kp(t,e){const i=Object.create(null),n=t.split(",");for(let r=0;r<n.length;r++)i[n[r]]=!0;return r=>!!i[r]}var Ap=Object.freeze({}),$p=Object.prototype.hasOwnProperty,Ji=(t,e)=>$p.call(t,e),ee=Array.isArray,je=t=>Za(t)==="[object Map]",Rp=t=>typeof t=="string",zr=t=>typeof t=="symbol",Yi=t=>t!==null&&typeof t=="object",Tp=Object.prototype.toString,Za=t=>Tp.call(t),tl=t=>Za(t).slice(8,-1),Pr=t=>Rp(t)&&t!=="NaN"&&t[0]!=="-"&&""+parseInt(t,10)===t,Fp=t=>{const e=Object.create(null);return i=>e[i]||(e[i]=t(i))},Op=Fp(t=>t.charAt(0).toUpperCase()+t.slice(1)),el=(t,e)=>t!==e&&(t===t||e===e),Yn=new WeakMap,De=[],yt,ie=Symbol("iterate"),Qn=Symbol("Map key iterate");function Mp(t){return t&&t._isEffect===!0}function Lp(t,e=Ap){Mp(t)&&(t=t.raw);const i=Ip(t,e);return e.lazy||i(),i}function zp(t){t.active&&(il(t),t.options.onStop&&t.options.onStop(),t.active=!1)}var Pp=0;function Ip(t,e){const i=function(){if(!i.active)return t();if(!De.includes(i)){il(i);try{return Dp(),De.push(i),yt=i,t()}finally{De.pop(),nl(),yt=De[De.length-1]}}};return i.id=Pp++,i.allowRecurse=!!e.allowRecurse,i._isEffect=!0,i.active=!0,i.raw=t,i.deps=[],i.options=e,i}function il(t){const{deps:e}=t;if(e.length){for(let i=0;i<e.length;i++)e[i].delete(t);e.length=0}}var ye=!0,Ir=[];function Bp(){Ir.push(ye),ye=!1}function Dp(){Ir.push(ye),ye=!0}function nl(){const t=Ir.pop();ye=t===void 0?!0:t}function ut(t,e,i){if(!ye||yt===void 0)return;let n=Yn.get(t);n||Yn.set(t,n=new Map);let r=n.get(i);r||n.set(i,r=new Set),r.has(yt)||(r.add(yt),yt.deps.push(r),yt.options.onTrack&&yt.options.onTrack({effect:yt,target:t,type:e,key:i}))}function Ut(t,e,i,n,r,s){const o=Yn.get(t);if(!o)return;const a=new Set,l=u=>{u&&u.forEach(d=>{(d!==yt||d.allowRecurse)&&a.add(d)})};if(e==="clear")o.forEach(l);else if(i==="length"&&ee(t))o.forEach((u,d)=>{(d==="length"||d>=n)&&l(u)});else switch(i!==void 0&&l(o.get(i)),e){case"add":ee(t)?Pr(i)&&l(o.get("length")):(l(o.get(ie)),je(t)&&l(o.get(Qn)));break;case"delete":ee(t)||(l(o.get(ie)),je(t)&&l(o.get(Qn)));break;case"set":je(t)&&l(o.get(ie));break}const c=u=>{u.options.onTrigger&&u.options.onTrigger({effect:u,target:t,key:i,type:e,newValue:n,oldValue:r,oldTarget:s}),u.options.scheduler?u.options.scheduler(u):u()};a.forEach(c)}var Np=kp("__proto__,__v_isRef,__isVue"),rl=new Set(Object.getOwnPropertyNames(Symbol).map(t=>Symbol[t]).filter(zr)),Up=sl(),Hp=sl(!0),Us=Vp();function Vp(){const t={};return["includes","indexOf","lastIndexOf"].forEach(e=>{t[e]=function(...i){const n=L(this);for(let s=0,o=this.length;s<o;s++)ut(n,"get",s+"");const r=n[e](...i);return r===-1||r===!1?n[e](...i.map(L)):r}}),["push","pop","shift","unshift","splice"].forEach(e=>{t[e]=function(...i){Bp();const n=L(this)[e].apply(this,i);return nl(),n}}),t}function sl(t=!1,e=!1){return function(n,r,s){if(r==="__v_isReactive")return!t;if(r==="__v_isReadonly")return t;if(r==="__v_raw"&&s===(t?e?rf:cl:e?nf:ll).get(n))return n;const o=ee(n);if(!t&&o&&Ji(Us,r))return Reflect.get(Us,r,s);const a=Reflect.get(n,r,s);return(zr(r)?rl.has(r):Np(r))||(t||ut(n,"get",r),e)?a:Zn(a)?!o||!Pr(r)?a.value:a:Yi(a)?t?ul(a):Ur(a):a}}var qp=jp();function jp(t=!1){return function(i,n,r,s){let o=i[n];if(!t&&(r=L(r),o=L(o),!ee(i)&&Zn(o)&&!Zn(r)))return o.value=r,!0;const a=ee(i)&&Pr(n)?Number(n)<i.length:Ji(i,n),l=Reflect.set(i,n,r,s);return i===L(s)&&(a?el(r,o)&&Ut(i,"set",n,r,o):Ut(i,"add",n,r)),l}}function Wp(t,e){const i=Ji(t,e),n=t[e],r=Reflect.deleteProperty(t,e);return r&&i&&Ut(t,"delete",e,void 0,n),r}function Kp(t,e){const i=Reflect.has(t,e);return(!zr(e)||!rl.has(e))&&ut(t,"has",e),i}function Gp(t){return ut(t,"iterate",ee(t)?"length":ie),Reflect.ownKeys(t)}var Xp={get:Up,set:qp,deleteProperty:Wp,has:Kp,ownKeys:Gp},Jp={get:Hp,set(t,e){return console.warn(`Set operation on key "${String(e)}" failed: target is readonly.`,t),!0},deleteProperty(t,e){return console.warn(`Delete operation on key "${String(e)}" failed: target is readonly.`,t),!0}},Br=t=>Yi(t)?Ur(t):t,Dr=t=>Yi(t)?ul(t):t,Nr=t=>t,Qi=t=>Reflect.getPrototypeOf(t);function hi(t,e,i=!1,n=!1){t=t.__v_raw;const r=L(t),s=L(e);e!==s&&!i&&ut(r,"get",e),!i&&ut(r,"get",s);const{has:o}=Qi(r),a=n?Nr:i?Dr:Br;if(o.call(r,e))return a(t.get(e));if(o.call(r,s))return a(t.get(s));t!==r&&t.get(e)}function pi(t,e=!1){const i=this.__v_raw,n=L(i),r=L(t);return t!==r&&!e&&ut(n,"has",t),!e&&ut(n,"has",r),t===r?i.has(t):i.has(t)||i.has(r)}function fi(t,e=!1){return t=t.__v_raw,!e&&ut(L(t),"iterate",ie),Reflect.get(t,"size",t)}function Hs(t){t=L(t);const e=L(this);return Qi(e).has.call(e,t)||(e.add(t),Ut(e,"add",t,t)),this}function Vs(t,e){e=L(e);const i=L(this),{has:n,get:r}=Qi(i);let s=n.call(i,t);s?al(i,n,t):(t=L(t),s=n.call(i,t));const o=r.call(i,t);return i.set(t,e),s?el(e,o)&&Ut(i,"set",t,e,o):Ut(i,"add",t,e),this}function qs(t){const e=L(this),{has:i,get:n}=Qi(e);let r=i.call(e,t);r?al(e,i,t):(t=L(t),r=i.call(e,t));const s=n?n.call(e,t):void 0,o=e.delete(t);return r&&Ut(e,"delete",t,void 0,s),o}function js(){const t=L(this),e=t.size!==0,i=je(t)?new Map(t):new Set(t),n=t.clear();return e&&Ut(t,"clear",void 0,void 0,i),n}function gi(t,e){return function(n,r){const s=this,o=s.__v_raw,a=L(o),l=e?Nr:t?Dr:Br;return!t&&ut(a,"iterate",ie),o.forEach((c,u)=>n.call(r,l(c),l(u),s))}}function mi(t,e,i){return function(...n){const r=this.__v_raw,s=L(r),o=je(s),a=t==="entries"||t===Symbol.iterator&&o,l=t==="keys"&&o,c=r[t](...n),u=i?Nr:e?Dr:Br;return!e&&ut(s,"iterate",l?Qn:ie),{next(){const{value:d,done:p}=c.next();return p?{value:d,done:p}:{value:a?[u(d[0]),u(d[1])]:u(d),done:p}},[Symbol.iterator](){return this}}}}function Ot(t){return function(...e){{const i=e[0]?`on key "${e[0]}" `:"";console.warn(`${Op(t)} operation ${i}failed: target is readonly.`,L(this))}return t==="delete"?!1:this}}function Yp(){const t={get(s){return hi(this,s)},get size(){return fi(this)},has:pi,add:Hs,set:Vs,delete:qs,clear:js,forEach:gi(!1,!1)},e={get(s){return hi(this,s,!1,!0)},get size(){return fi(this)},has:pi,add:Hs,set:Vs,delete:qs,clear:js,forEach:gi(!1,!0)},i={get(s){return hi(this,s,!0)},get size(){return fi(this,!0)},has(s){return pi.call(this,s,!0)},add:Ot("add"),set:Ot("set"),delete:Ot("delete"),clear:Ot("clear"),forEach:gi(!0,!1)},n={get(s){return hi(this,s,!0,!0)},get size(){return fi(this,!0)},has(s){return pi.call(this,s,!0)},add:Ot("add"),set:Ot("set"),delete:Ot("delete"),clear:Ot("clear"),forEach:gi(!0,!0)};return["keys","values","entries",Symbol.iterator].forEach(s=>{t[s]=mi(s,!1,!1),i[s]=mi(s,!0,!1),e[s]=mi(s,!1,!0),n[s]=mi(s,!0,!0)}),[t,i,e,n]}var[Qp,Zp,ag,lg]=Yp();function ol(t,e){const i=t?Zp:Qp;return(n,r,s)=>r==="__v_isReactive"?!t:r==="__v_isReadonly"?t:r==="__v_raw"?n:Reflect.get(Ji(i,r)&&r in n?i:n,r,s)}var tf={get:ol(!1)},ef={get:ol(!0)};function al(t,e,i){const n=L(i);if(n!==i&&e.call(t,n)){const r=tl(t);console.warn(`Reactive ${r} contains both the raw and reactive versions of the same object${r==="Map"?" as keys":""}, which can lead to inconsistencies. Avoid differentiating between the raw and reactive versions of an object and only use the reactive version if possible.`)}}var ll=new WeakMap,nf=new WeakMap,cl=new WeakMap,rf=new WeakMap;function sf(t){switch(t){case"Object":case"Array":return 1;case"Map":case"Set":case"WeakMap":case"WeakSet":return 2;default:return 0}}function of(t){return t.__v_skip||!Object.isExtensible(t)?0:sf(tl(t))}function Ur(t){return t&&t.__v_isReadonly?t:dl(t,!1,Xp,tf,ll)}function ul(t){return dl(t,!0,Jp,ef,cl)}function dl(t,e,i,n,r){if(!Yi(t))return console.warn(`value cannot be made reactive: ${String(t)}`),t;if(t.__v_raw&&!(e&&t.__v_isReactive))return t;const s=r.get(t);if(s)return s;const o=of(t);if(o===0)return t;const a=new Proxy(t,o===2?n:i);return r.set(t,a),a}function L(t){return t&&L(t.__v_raw)||t}function Zn(t){return!!(t&&t.__v_isRef===!0)}ft("nextTick",()=>Or);ft("dispatch",t=>qe.bind(qe,t));ft("watch",(t,{evaluateLater:e,cleanup:i})=>(n,r)=>{let s=e(n),a=la(()=>{let l;return s(c=>l=c),l},r);i(a)});ft("store",wp);ft("data",t=>ma(t));ft("root",t=>Ki(t));ft("refs",t=>(t._x_refs_proxy||(t._x_refs_proxy=ri(af(t))),t._x_refs_proxy));function af(t){let e=[];return $e(t,i=>{i._x_refs&&e.push(i._x_refs)}),e}var wn={};function hl(t){return wn[t]||(wn[t]=0),++wn[t]}function lf(t,e){return $e(t,i=>{if(i._x_ids&&i._x_ids[e])return!0})}function cf(t,e){t._x_ids||(t._x_ids={}),t._x_ids[e]||(t._x_ids[e]=hl(e))}ft("id",(t,{cleanup:e})=>(i,n=null)=>{let r=`${i}${n?`-${n}`:""}`;return uf(t,r,e,()=>{let s=lf(t,i),o=s?s._x_ids[i]:hl(i);return n?`${i}-${o}-${n}`:`${i}-${o}`})});Xi((t,e)=>{t._x_id&&(e._x_id=t._x_id)});function uf(t,e,i,n){if(t._x_id||(t._x_id={}),t._x_id[e])return t._x_id[e];let r=n();return t._x_id[e]=r,i(()=>{delete t._x_id[e]}),r}ft("el",t=>t);pl("Focus","focus","focus");pl("Persist","persist","persist");function pl(t,e,i){ft(e,n=>at(`You can't use [$${e}] without first installing the "${t}" plugin here: https://alpinejs.dev/plugins/${i}`,n))}N("modelable",(t,{expression:e},{effect:i,evaluateLater:n,cleanup:r})=>{let s=n(e),o=()=>{let u;return s(d=>u=d),u},a=n(`${e} = __placeholder`),l=u=>a(()=>{},{scope:{__placeholder:u}}),c=o();l(c),queueMicrotask(()=>{if(!t._x_model)return;t._x_removeModelListeners.default();let u=t._x_model.get,d=t._x_model.set,p=Xa({get(){return u()},set(f){d(f)}},{get(){return o()},set(f){l(f)}});r(p)})});N("teleport",(t,{modifiers:e,expression:i},{cleanup:n})=>{t.tagName.toLowerCase()!=="template"&&at("x-teleport can only be used on a <template> tag",t);let r=Ws(i),s=t.content.cloneNode(!0).firstElementChild;t._x_teleport=s,s._x_teleportBack=t,t.setAttribute("data-teleport-template",!0),s.setAttribute("data-teleport-target",!0),t._x_forwardEvents&&t._x_forwardEvents.forEach(a=>{s.addEventListener(a,l=>{l.stopPropagation(),t.dispatchEvent(new l.constructor(l.type,l))})}),ni(s,{},t);let o=(a,l,c)=>{c.includes("prepend")?l.parentNode.insertBefore(a,l):c.includes("append")?l.parentNode.insertBefore(a,l.nextSibling):l.appendChild(a)};z(()=>{o(s,r,e),Ht(()=>{Ct(s)})()}),t._x_teleportPutBack=()=>{let a=Ws(i);z(()=>{o(t._x_teleport,a,e)})},n(()=>z(()=>{s.remove(),Re(s)}))});var df=document.createElement("div");function Ws(t){let e=Ht(()=>document.querySelector(t),()=>df)();return e||at(`Cannot find x-teleport element for selector: "${t}"`),e}var fl=()=>{};fl.inline=(t,{modifiers:e},{cleanup:i})=>{e.includes("self")?t._x_ignoreSelf=!0:t._x_ignore=!0,i(()=>{e.includes("self")?delete t._x_ignoreSelf:delete t._x_ignore})};N("ignore",fl);N("effect",Ht((t,{expression:e},{effect:i})=>{i(K(t,e))}));function tr(t,e,i,n){let r=t,s=l=>n(l),o={},a=(l,c)=>u=>c(l,u);if(i.includes("dot")&&(e=hf(e)),i.includes("camel")&&(e=pf(e)),i.includes("passive")&&(o.passive=!0),i.includes("capture")&&(o.capture=!0),i.includes("window")&&(r=window),i.includes("document")&&(r=document),i.includes("debounce")){let l=i[i.indexOf("debounce")+1]||"invalid-wait",c=Li(l.split("ms")[0])?Number(l.split("ms")[0]):250;s=Ka(s,c)}if(i.includes("throttle")){let l=i[i.indexOf("throttle")+1]||"invalid-wait",c=Li(l.split("ms")[0])?Number(l.split("ms")[0]):250;s=Ga(s,c)}return i.includes("prevent")&&(s=a(s,(l,c)=>{c.preventDefault(),l(c)})),i.includes("stop")&&(s=a(s,(l,c)=>{c.stopPropagation(),l(c)})),i.includes("once")&&(s=a(s,(l,c)=>{l(c),r.removeEventListener(e,s,o)})),(i.includes("away")||i.includes("outside"))&&(r=document,s=a(s,(l,c)=>{t.contains(c.target)||c.target.isConnected!==!1&&(t.offsetWidth<1&&t.offsetHeight<1||t._x_isShown!==!1&&l(c))})),i.includes("self")&&(s=a(s,(l,c)=>{c.target===t&&l(c)})),(gf(e)||gl(e))&&(s=a(s,(l,c)=>{mf(c,i)||l(c)})),r.addEventListener(e,s,o),()=>{r.removeEventListener(e,s,o)}}function hf(t){return t.replace(/-/g,".")}function pf(t){return t.toLowerCase().replace(/-(\w)/g,(e,i)=>i.toUpperCase())}function Li(t){return!Array.isArray(t)&&!isNaN(t)}function ff(t){return[" ","_"].includes(t)?t:t.replace(/([a-z])([A-Z])/g,"$1-$2").replace(/[_\s]/,"-").toLowerCase()}function gf(t){return["keydown","keyup"].includes(t)}function gl(t){return["contextmenu","click","mouse"].some(e=>t.includes(e))}function mf(t,e){let i=e.filter(s=>!["window","document","prevent","stop","once","capture","self","away","outside","passive"].includes(s));if(i.includes("debounce")){let s=i.indexOf("debounce");i.splice(s,Li((i[s+1]||"invalid-wait").split("ms")[0])?2:1)}if(i.includes("throttle")){let s=i.indexOf("throttle");i.splice(s,Li((i[s+1]||"invalid-wait").split("ms")[0])?2:1)}if(i.length===0||i.length===1&&Ks(t.key).includes(i[0]))return!1;const r=["ctrl","shift","alt","meta","cmd","super"].filter(s=>i.includes(s));return i=i.filter(s=>!r.includes(s)),!(r.length>0&&r.filter(o=>((o==="cmd"||o==="super")&&(o="meta"),t[`${o}Key`])).length===r.length&&(gl(t.type)||Ks(t.key).includes(i[0])))}function Ks(t){if(!t)return[];t=ff(t);let e={ctrl:"control",slash:"/",space:" ",spacebar:" ",cmd:"meta",esc:"escape",up:"arrow-up",down:"arrow-down",left:"arrow-left",right:"arrow-right",period:".",comma:",",equal:"=",minus:"-",underscore:"_"};return e[t]=t,Object.keys(e).map(i=>{if(e[i]===t)return i}).filter(i=>i)}N("model",(t,{modifiers:e,expression:i},{effect:n,cleanup:r})=>{let s=t;e.includes("parent")&&(s=t.parentNode);let o=K(s,i),a;typeof i=="string"?a=K(s,`${i} = __placeholder`):typeof i=="function"&&typeof i()=="string"?a=K(s,`${i()} = __placeholder`):a=()=>{};let l=()=>{let p;return o(f=>p=f),Gs(p)?p.get():p},c=p=>{let f;o(b=>f=b),Gs(f)?f.set(p):a(()=>{},{scope:{__placeholder:p}})};typeof i=="string"&&t.type==="radio"&&z(()=>{t.hasAttribute("name")||t.setAttribute("name",i)});var u=t.tagName.toLowerCase()==="select"||["checkbox","radio"].includes(t.type)||e.includes("lazy")?"change":"input";let d=Nt?()=>{}:tr(t,u,e,p=>{c(_n(t,e,p,l()))});if(e.includes("fill")&&([void 0,null,""].includes(l())||Lr(t)&&Array.isArray(l())||t.tagName.toLowerCase()==="select"&&t.multiple)&&c(_n(t,e,{target:t},l())),t._x_removeModelListeners||(t._x_removeModelListeners={}),t._x_removeModelListeners.default=d,r(()=>t._x_removeModelListeners.default()),t.form){let p=tr(t.form,"reset",[],f=>{Or(()=>t._x_model&&t._x_model.set(_n(t,e,{target:t},l())))});r(()=>p())}t._x_model={get(){return l()},set(p){c(p)}},t._x_forceModelUpdate=p=>{p===void 0&&typeof i=="string"&&i.match(/\./)&&(p=""),window.fromModel=!0,z(()=>Ha(t,"value",p)),delete window.fromModel},n(()=>{let p=l();e.includes("unintrusive")&&document.activeElement.isSameNode(t)||t._x_forceModelUpdate(p)})});function _n(t,e,i,n){return z(()=>{if(i instanceof CustomEvent&&i.detail!==void 0)return i.detail!==null&&i.detail!==void 0?i.detail:i.target.value;if(Lr(t))if(Array.isArray(n)){let r=null;return e.includes("number")?r=xn(i.target.value):e.includes("boolean")?r=Si(i.target.value):r=i.target.value,i.target.checked?n.includes(r)?n:n.concat([r]):n.filter(s=>!bf(s,r))}else return i.target.checked;else{if(t.tagName.toLowerCase()==="select"&&t.multiple)return e.includes("number")?Array.from(i.target.selectedOptions).map(r=>{let s=r.value||r.text;return xn(s)}):e.includes("boolean")?Array.from(i.target.selectedOptions).map(r=>{let s=r.value||r.text;return Si(s)}):Array.from(i.target.selectedOptions).map(r=>r.value||r.text);{let r;return Wa(t)?i.target.checked?r=i.target.value:r=n:r=i.target.value,e.includes("number")?xn(r):e.includes("boolean")?Si(r):e.includes("trim")?r.trim():r}}})}function xn(t){let e=t?parseFloat(t):null;return yf(e)?e:t}function bf(t,e){return t==e}function yf(t){return!Array.isArray(t)&&!isNaN(t)}function Gs(t){return t!==null&&typeof t=="object"&&typeof t.get=="function"&&typeof t.set=="function"}N("cloak",t=>queueMicrotask(()=>z(()=>t.removeAttribute(Ae("cloak")))));Pa(()=>`[${Ae("init")}]`);N("init",Ht((t,{expression:e},{evaluate:i})=>typeof e=="string"?!!e.trim()&&i(e,{},!1):i(e,{},!1)));N("text",(t,{expression:e},{effect:i,evaluateLater:n})=>{let r=n(e);i(()=>{r(s=>{z(()=>{t.textContent=s})})})});N("html",(t,{expression:e},{effect:i,evaluateLater:n})=>{let r=n(e);i(()=>{r(s=>{z(()=>{t.innerHTML=s,t._x_ignoreSelf=!0,Ct(t),delete t._x_ignoreSelf})})})});Rr(ka(":",Aa(Ae("bind:"))));var ml=(t,{value:e,modifiers:i,expression:n,original:r},{effect:s,cleanup:o})=>{if(!e){let l={};xp(l),K(t,n)(u=>{Ya(t,u,r)},{scope:l});return}if(e==="key")return vf(t,n);if(t._x_inlineBindings&&t._x_inlineBindings[e]&&t._x_inlineBindings[e].extract)return;let a=K(t,n);s(()=>a(l=>{l===void 0&&typeof n=="string"&&n.match(/\./)&&(l=""),z(()=>Ha(t,e,l,i))})),o(()=>{t._x_undoAddedClasses&&t._x_undoAddedClasses(),t._x_undoAddedStyles&&t._x_undoAddedStyles()})};ml.inline=(t,{value:e,modifiers:i,expression:n})=>{e&&(t._x_inlineBindings||(t._x_inlineBindings={}),t._x_inlineBindings[e]={expression:n,extract:!1})};N("bind",ml);function vf(t,e){t._x_keyExpression=e}za(()=>`[${Ae("data")}]`);N("data",(t,{expression:e},{cleanup:i})=>{if(wf(t))return;e=e===""?"{}":e;let n={};Vn(n,t);let r={};Sp(r,n);let s=te(t,e,{scope:r});(s===void 0||s===!0)&&(s={}),Vn(s,t);let o=Ce(s);ba(o);let a=ni(t,o);o.init&&te(t,o.init),i(()=>{o.destroy&&te(t,o.destroy),a()})});Xi((t,e)=>{t._x_dataStack&&(e._x_dataStack=t._x_dataStack,e.setAttribute("data-has-alpine-state",!0))});function wf(t){return Nt?Jn?!0:t.hasAttribute("data-has-alpine-state"):!1}N("show",(t,{modifiers:e,expression:i},{effect:n})=>{let r=K(t,i);t._x_doHide||(t._x_doHide=()=>{z(()=>{t.style.setProperty("display","none",e.includes("important")?"important":void 0)})}),t._x_doShow||(t._x_doShow=()=>{z(()=>{t.style.length===1&&t.style.display==="none"?t.removeAttribute("style"):t.style.removeProperty("display")})});let s=()=>{t._x_doHide(),t._x_isShown=!1},o=()=>{t._x_doShow(),t._x_isShown=!0},a=()=>setTimeout(o),l=Gn(d=>d?o():s(),d=>{typeof t._x_toggleAndCascadeWithTransitions=="function"?t._x_toggleAndCascadeWithTransitions(t,d,o,s):d?a():s()}),c,u=!0;n(()=>r(d=>{!u&&d===c||(e.includes("immediate")&&(d?a():s()),l(d),c=d,u=!1)}))});N("for",(t,{expression:e},{effect:i,cleanup:n})=>{let r=xf(e),s=K(t,r.items),o=K(t,t._x_keyExpression||"index");t._x_prevKeys=[],t._x_lookup={},i(()=>_f(t,r,s,o)),n(()=>{Object.values(t._x_lookup).forEach(a=>z(()=>{Re(a),a.remove()})),delete t._x_prevKeys,delete t._x_lookup})});function _f(t,e,i,n){let r=o=>typeof o=="object"&&!Array.isArray(o),s=t;i(o=>{Ef(o)&&o>=0&&(o=Array.from(Array(o).keys(),g=>g+1)),o===void 0&&(o=[]);let a=t._x_lookup,l=t._x_prevKeys,c=[],u=[];if(r(o))o=Object.entries(o).map(([g,_])=>{let x=Xs(e,_,g,o);n(E=>{u.includes(E)&&at("Duplicate key on x-for",t),u.push(E)},{scope:{index:g,...x}}),c.push(x)});else for(let g=0;g<o.length;g++){let _=Xs(e,o[g],g,o);n(x=>{u.includes(x)&&at("Duplicate key on x-for",t),u.push(x)},{scope:{index:g,..._}}),c.push(_)}let d=[],p=[],f=[],b=[];for(let g=0;g<l.length;g++){let _=l[g];u.indexOf(_)===-1&&f.push(_)}l=l.filter(g=>!f.includes(g));let w="template";for(let g=0;g<u.length;g++){let _=u[g],x=l.indexOf(_);if(x===-1)l.splice(g,0,_),d.push([w,g]);else if(x!==g){let E=l.splice(g,1)[0],k=l.splice(x-1,1)[0];l.splice(g,0,k),l.splice(x,0,E),p.push([E,k])}else b.push(_);w=_}for(let g=0;g<f.length;g++){let _=f[g];_ in a&&(z(()=>{Re(a[_]),a[_].remove()}),delete a[_])}for(let g=0;g<p.length;g++){let[_,x]=p[g],E=a[_],k=a[x],S=document.createElement("div");z(()=>{k||at('x-for ":key" is undefined or invalid',s,x,a),k.after(S),E.after(k),k._x_currentIfEl&&k.after(k._x_currentIfEl),S.before(E),E._x_currentIfEl&&E.after(E._x_currentIfEl),S.remove()}),k._x_refreshXForScope(c[u.indexOf(x)])}for(let g=0;g<d.length;g++){let[_,x]=d[g],E=_==="template"?s:a[_];E._x_currentIfEl&&(E=E._x_currentIfEl);let k=c[x],S=u[x],O=document.importNode(s.content,!0).firstElementChild,T=Ce(k);ni(O,T,s),O._x_refreshXForScope=H=>{Object.entries(H).forEach(([j,X])=>{T[j]=X})},z(()=>{E.after(O),Ht(()=>Ct(O))()}),typeof S=="object"&&at("x-for key cannot be an object, it must be a string or an integer",s),a[S]=O}for(let g=0;g<b.length;g++)a[b[g]]._x_refreshXForScope(c[u.indexOf(b[g])]);s._x_prevKeys=u})}function xf(t){let e=/,([^,\}\]]*)(?:,([^,\}\]]*))?$/,i=/^\s*\(|\)\s*$/g,n=/([\s\S]*?)\s+(?:in|of)\s+([\s\S]*)/,r=t.match(n);if(!r)return;let s={};s.items=r[2].trim();let o=r[1].replace(i,"").trim(),a=o.match(e);return a?(s.item=o.replace(e,"").trim(),s.index=a[1].trim(),a[2]&&(s.collection=a[2].trim())):s.item=o,s}function Xs(t,e,i,n){let r={};return/^\[.*\]$/.test(t.item)&&Array.isArray(e)?t.item.replace("[","").replace("]","").split(",").map(o=>o.trim()).forEach((o,a)=>{r[o]=e[a]}):/^\{.*\}$/.test(t.item)&&!Array.isArray(e)&&typeof e=="object"?t.item.replace("{","").replace("}","").split(",").map(o=>o.trim()).forEach(o=>{r[o]=e[o]}):r[t.item]=e,t.index&&(r[t.index]=i),t.collection&&(r[t.collection]=n),r}function Ef(t){return!Array.isArray(t)&&!isNaN(t)}function bl(){}bl.inline=(t,{expression:e},{cleanup:i})=>{let n=Ki(t);n._x_refs||(n._x_refs={}),n._x_refs[e]=t,i(()=>delete n._x_refs[e])};N("ref",bl);N("if",(t,{expression:e},{effect:i,cleanup:n})=>{t.tagName.toLowerCase()!=="template"&&at("x-if can only be used on a <template> tag",t);let r=K(t,e),s=()=>{if(t._x_currentIfEl)return t._x_currentIfEl;let a=t.content.cloneNode(!0).firstElementChild;return ni(a,{},t),z(()=>{t.after(a),Ht(()=>Ct(a))()}),t._x_currentIfEl=a,t._x_undoIf=()=>{z(()=>{Re(a),a.remove()}),delete t._x_currentIfEl},a},o=()=>{t._x_undoIf&&(t._x_undoIf(),delete t._x_undoIf)};i(()=>r(a=>{a?s():o()})),n(()=>t._x_undoIf&&t._x_undoIf())});N("id",(t,{expression:e},{evaluate:i})=>{i(e).forEach(r=>cf(t,r))});Xi((t,e)=>{t._x_ids&&(e._x_ids=t._x_ids)});Rr(ka("@",Aa(Ae("on:"))));N("on",Ht((t,{value:e,modifiers:i,expression:n},{cleanup:r})=>{let s=n?K(t,n):()=>{};t.tagName.toLowerCase()==="template"&&(t._x_forwardEvents||(t._x_forwardEvents=[]),t._x_forwardEvents.includes(e)||t._x_forwardEvents.push(e));let o=tr(t,e,i,a=>{s(()=>{},{scope:{$event:a},params:[a]})});r(()=>o())}));Zi("Collapse","collapse","collapse");Zi("Intersect","intersect","intersect");Zi("Focus","trap","focus");Zi("Mask","mask","mask");function Zi(t,e,i){N(e,n=>at(`You can't use [x-${e}] without first installing the "${t}" plugin here: https://alpinejs.dev/plugins/${i}`,n))}si.setEvaluator(xa);si.setReactivityEngine({reactive:Ur,effect:Lp,release:zp,raw:L});var Sf=si,Hr=Sf;function Cf(t){let e=()=>{let i,n;try{n=localStorage}catch(r){console.error(r),console.warn("Alpine: $persist is using temporary storage since localStorage is unavailable.");let s=new Map;n={getItem:s.get.bind(s),setItem:s.set.bind(s)}}return t.interceptor((r,s,o,a,l)=>{let c=i||`_x_${a}`,u=Js(c,n)?Ys(c,n):r;return o(u),t.effect(()=>{let d=s();Qs(c,d,n),o(d)}),u},r=>{r.as=s=>(i=s,r),r.using=s=>(n=s,r)})};Object.defineProperty(t,"$persist",{get:()=>e()}),t.magic("persist",e),t.persist=(i,{get:n,set:r},s=localStorage)=>{let o=Js(i,s)?Ys(i,s):n();r(o),t.effect(()=>{let a=n();Qs(i,a,s),r(a)})}}function Js(t,e){return e.getItem(t)!==null}function Ys(t,e){let i=e.getItem(t,e);if(i!==void 0)return JSON.parse(i)}function Qs(t,e,i){i.setItem(t,JSON.stringify(e))}var kf=Cf,Zs=yl;function yl(){var t=[].slice.call(arguments),e=!1;typeof t[0]=="boolean"&&(e=t.shift());var i=t[0];if(to(i))throw new Error("extendee must be an object");for(var n=t.slice(1),r=n.length,s=0;s<r;s++){var o=n[s];for(var a in o)if(Object.prototype.hasOwnProperty.call(o,a)){var l=o[a];if(e&&Af(l)){var c=Array.isArray(l)?[]:{};i[a]=yl(!0,Object.prototype.hasOwnProperty.call(i,a)&&!to(i[a])?i[a]:c,l)}else i[a]=l}}return i}function Af(t){return Array.isArray(t)||{}.toString.call(t)=="[object Object]"}function to(t){return!t||typeof t!="object"&&typeof t!="function"}function $f(t){return t&&t.__esModule?t.default:t}class eo{on(e,i){return this._callbacks=this._callbacks||{},this._callbacks[e]||(this._callbacks[e]=[]),this._callbacks[e].push(i),this}emit(e,...i){this._callbacks=this._callbacks||{};let n=this._callbacks[e];if(n)for(let r of n)r.apply(this,i);return this.element&&this.element.dispatchEvent(this.makeEvent("dropzone:"+e,{args:i})),this}makeEvent(e,i){let n={bubbles:!0,cancelable:!0,detail:i};if(typeof window.CustomEvent=="function")return new CustomEvent(e,n);var r=document.createEvent("CustomEvent");return r.initCustomEvent(e,n.bubbles,n.cancelable,n.detail),r}off(e,i){if(!this._callbacks||arguments.length===0)return this._callbacks={},this;let n=this._callbacks[e];if(!n)return this;if(arguments.length===1)return delete this._callbacks[e],this;for(let r=0;r<n.length;r++)if(n[r]===i){n.splice(r,1);break}return this}}var vl={};vl=`<div class="dz-preview dz-file-preview">
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
`;let Rf={url:null,method:"post",withCredentials:!1,timeout:null,parallelUploads:2,uploadMultiple:!1,chunking:!1,forceChunking:!1,chunkSize:2097152,parallelChunkUploads:!1,retryChunks:!1,retryChunksLimit:3,maxFilesize:256,paramName:"file",createImageThumbnails:!0,maxThumbnailFilesize:10,thumbnailWidth:120,thumbnailHeight:120,thumbnailMethod:"crop",resizeWidth:null,resizeHeight:null,resizeMimeType:null,resizeQuality:.8,resizeMethod:"contain",filesizeBase:1e3,maxFiles:null,headers:null,defaultHeaders:!0,clickable:!0,ignoreHiddenFiles:!0,acceptedFiles:null,acceptedMimeTypes:null,autoProcessQueue:!0,autoQueue:!0,addRemoveLinks:!1,previewsContainer:null,disablePreviews:!1,hiddenInputContainer:"body",capture:null,renameFilename:null,renameFile:null,forceFallback:!1,dictDefaultMessage:"Drop files here to upload",dictFallbackMessage:"Your browser does not support drag'n'drop file uploads.",dictFallbackText:"Please use the fallback form below to upload your files like in the olden days.",dictFileTooBig:"File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.",dictInvalidFileType:"You can't upload files of this type.",dictResponseError:"Server responded with {{statusCode}} code.",dictCancelUpload:"Cancel upload",dictUploadCanceled:"Upload canceled.",dictCancelUploadConfirmation:"Are you sure you want to cancel this upload?",dictRemoveFile:"Remove file",dictRemoveFileConfirmation:null,dictMaxFilesExceeded:"You can not upload any more files.",dictFileSizeUnits:{tb:"TB",gb:"GB",mb:"MB",kb:"KB",b:"b"},init(){},params(t,e,i){if(i)return{dzuuid:i.file.upload.uuid,dzchunkindex:i.index,dztotalfilesize:i.file.size,dzchunksize:this.options.chunkSize,dztotalchunkcount:i.file.upload.totalChunkCount,dzchunkbyteoffset:i.index*this.options.chunkSize}},accept(t,e){return e()},chunksUploaded:function(t,e){e()},binaryBody:!1,fallback(){let t;this.element.className=`${this.element.className} dz-browser-not-supported`;for(let i of this.element.getElementsByTagName("div"))if(/(^| )dz-message($| )/.test(i.className)){t=i,i.className="dz-message";break}t||(t=v.createElement('<div class="dz-message"><span></span></div>'),this.element.appendChild(t));let e=t.getElementsByTagName("span")[0];return e&&(e.textContent!=null?e.textContent=this.options.dictFallbackMessage:e.innerText!=null&&(e.innerText=this.options.dictFallbackMessage)),this.element.appendChild(this.getFallbackForm())},resize(t,e,i,n){let r={srcX:0,srcY:0,srcWidth:t.width,srcHeight:t.height},s=t.width/t.height;e==null&&i==null?(e=r.srcWidth,i=r.srcHeight):e==null?e=i*s:i==null&&(i=e/s),e=Math.min(e,r.srcWidth),i=Math.min(i,r.srcHeight);let o=e/i;if(r.srcWidth>e||r.srcHeight>i)if(n==="crop")s>o?(r.srcHeight=t.height,r.srcWidth=r.srcHeight*o):(r.srcWidth=t.width,r.srcHeight=r.srcWidth/o);else if(n==="contain")s>o?i=e/s:e=i*s;else throw new Error(`Unknown resizeMethod '${n}'`);return r.srcX=(t.width-r.srcWidth)/2,r.srcY=(t.height-r.srcHeight)/2,r.trgWidth=e,r.trgHeight=i,r},transformFile(t,e){return(this.options.resizeWidth||this.options.resizeHeight)&&t.type.match(/image.*/)?this.resizeImage(t,this.options.resizeWidth,this.options.resizeHeight,this.options.resizeMethod,e):e(t)},previewTemplate:$f(vl),drop(t){return this.element.classList.remove("dz-drag-hover")},dragstart(t){},dragend(t){return this.element.classList.remove("dz-drag-hover")},dragenter(t){return this.element.classList.add("dz-drag-hover")},dragover(t){return this.element.classList.add("dz-drag-hover")},dragleave(t){return this.element.classList.remove("dz-drag-hover")},paste(t){},reset(){return this.element.classList.remove("dz-started")},addedfile(t){if(this.element===this.previewsContainer&&this.element.classList.add("dz-started"),this.previewsContainer&&!this.options.disablePreviews){t.previewElement=v.createElement(this.options.previewTemplate.trim()),t.previewTemplate=t.previewElement,this.previewsContainer.appendChild(t.previewElement);for(var e of t.previewElement.querySelectorAll("[data-dz-name]"))e.textContent=t.name;for(e of t.previewElement.querySelectorAll("[data-dz-size]"))e.innerHTML=this.filesize(t.size);this.options.addRemoveLinks&&(t._removeLink=v.createElement(`<a class="dz-remove" href="javascript:undefined;" data-dz-remove>${this.options.dictRemoveFile}</a>`),t.previewElement.appendChild(t._removeLink));let i=n=>(n.preventDefault(),n.stopPropagation(),t.status===v.UPLOADING?v.confirm(this.options.dictCancelUploadConfirmation,()=>this.removeFile(t)):this.options.dictRemoveFileConfirmation?v.confirm(this.options.dictRemoveFileConfirmation,()=>this.removeFile(t)):this.removeFile(t));for(let n of t.previewElement.querySelectorAll("[data-dz-remove]"))n.addEventListener("click",i)}},removedfile(t){return t.previewElement!=null&&t.previewElement.parentNode!=null&&t.previewElement.parentNode.removeChild(t.previewElement),this._updateMaxFilesReachedClass()},thumbnail(t,e){if(t.previewElement){t.previewElement.classList.remove("dz-file-preview");for(let i of t.previewElement.querySelectorAll("[data-dz-thumbnail]"))i.alt=t.name,i.src=e;return setTimeout(()=>t.previewElement.classList.add("dz-image-preview"),1)}},error(t,e){if(t.previewElement){t.previewElement.classList.add("dz-error"),typeof e!="string"&&e.error&&(e=e.error);for(let i of t.previewElement.querySelectorAll("[data-dz-errormessage]"))i.textContent=e}},errormultiple(){},processing(t){if(t.previewElement&&(t.previewElement.classList.add("dz-processing"),t._removeLink))return t._removeLink.innerHTML=this.options.dictCancelUpload},processingmultiple(){},uploadprogress(t,e,i){if(t.previewElement)for(let n of t.previewElement.querySelectorAll("[data-dz-uploadprogress]"))n.nodeName==="PROGRESS"?n.value=e:n.style.width=`${e}%`},totaluploadprogress(){},sending(){},sendingmultiple(){},success(t){if(t.previewElement)return t.previewElement.classList.add("dz-success")},successmultiple(){},canceled(t){return this.emit("error",t,this.options.dictUploadCanceled)},canceledmultiple(){},complete(t){if(t._removeLink&&(t._removeLink.innerHTML=this.options.dictRemoveFile),t.previewElement)return t.previewElement.classList.add("dz-complete")},completemultiple(){},maxfilesexceeded(){},maxfilesreached(){},queuecomplete(){},addedfiles(){}};var Tf=Rf;class v extends eo{static initClass(){this.prototype.Emitter=eo,this.prototype.events=["drop","dragstart","dragend","dragenter","dragover","dragleave","addedfile","addedfiles","removedfile","thumbnail","error","errormultiple","processing","processingmultiple","uploadprogress","totaluploadprogress","sending","sendingmultiple","success","successmultiple","canceled","canceledmultiple","complete","completemultiple","reset","maxfilesexceeded","maxfilesreached","queuecomplete"],this.prototype._thumbnailQueue=[],this.prototype._processingThumbnail=!1}getAcceptedFiles(){return this.files.filter(e=>e.accepted).map(e=>e)}getRejectedFiles(){return this.files.filter(e=>!e.accepted).map(e=>e)}getFilesWithStatus(e){return this.files.filter(i=>i.status===e).map(i=>i)}getQueuedFiles(){return this.getFilesWithStatus(v.QUEUED)}getUploadingFiles(){return this.getFilesWithStatus(v.UPLOADING)}getAddedFiles(){return this.getFilesWithStatus(v.ADDED)}getActiveFiles(){return this.files.filter(e=>e.status===v.UPLOADING||e.status===v.QUEUED).map(e=>e)}init(){if(this.element.tagName==="form"&&this.element.setAttribute("enctype","multipart/form-data"),this.element.classList.contains("dropzone")&&!this.element.querySelector(".dz-message")&&this.element.appendChild(v.createElement(`<div class="dz-default dz-message"><button class="dz-button" type="button">${this.options.dictDefaultMessage}</button></div>`)),this.clickableElements.length){let n=()=>{this.hiddenFileInput&&this.hiddenFileInput.parentNode.removeChild(this.hiddenFileInput),this.hiddenFileInput=document.createElement("input"),this.hiddenFileInput.setAttribute("type","file"),(this.options.maxFiles===null||this.options.maxFiles>1)&&this.hiddenFileInput.setAttribute("multiple","multiple"),this.hiddenFileInput.className="dz-hidden-input",this.options.acceptedFiles!==null&&this.hiddenFileInput.setAttribute("accept",this.options.acceptedFiles),this.options.capture!==null&&this.hiddenFileInput.setAttribute("capture",this.options.capture),this.hiddenFileInput.setAttribute("tabindex","-1"),this.hiddenFileInput.style.visibility="hidden",this.hiddenFileInput.style.position="absolute",this.hiddenFileInput.style.top="0",this.hiddenFileInput.style.left="0",this.hiddenFileInput.style.height="0",this.hiddenFileInput.style.width="0",v.getElement(this.options.hiddenInputContainer,"hiddenInputContainer").appendChild(this.hiddenFileInput),this.hiddenFileInput.addEventListener("change",()=>{let{files:r}=this.hiddenFileInput;if(r.length)for(let s of r)this.addFile(s);this.emit("addedfiles",r),n()})};n()}this.URL=window.URL!==null?window.URL:window.webkitURL;for(let n of this.events)this.on(n,this.options[n]);this.on("uploadprogress",()=>this.updateTotalUploadProgress()),this.on("removedfile",()=>this.updateTotalUploadProgress()),this.on("canceled",n=>this.emit("complete",n)),this.on("complete",n=>{if(this.getAddedFiles().length===0&&this.getUploadingFiles().length===0&&this.getQueuedFiles().length===0)return setTimeout(()=>this.emit("queuecomplete"),0)});const e=function(n){if(n.dataTransfer.types){for(var r=0;r<n.dataTransfer.types.length;r++)if(n.dataTransfer.types[r]==="Files")return!0}return!1};let i=function(n){if(e(n))return n.stopPropagation(),n.preventDefault?n.preventDefault():n.returnValue=!1};return this.listeners=[{element:this.element,events:{dragstart:n=>this.emit("dragstart",n),dragenter:n=>(i(n),this.emit("dragenter",n)),dragover:n=>{let r;try{r=n.dataTransfer.effectAllowed}catch{}return n.dataTransfer.dropEffect=r==="move"||r==="linkMove"?"move":"copy",i(n),this.emit("dragover",n)},dragleave:n=>this.emit("dragleave",n),drop:n=>(i(n),this.drop(n)),dragend:n=>this.emit("dragend",n)}}],this.clickableElements.forEach(n=>this.listeners.push({element:n,events:{click:r=>((n!==this.element||r.target===this.element||v.elementInside(r.target,this.element.querySelector(".dz-message")))&&this.hiddenFileInput.click(),!0)}})),this.enable(),this.options.init.call(this)}destroy(){return this.disable(),this.removeAllFiles(!0),this.hiddenFileInput!=null&&this.hiddenFileInput.parentNode&&(this.hiddenFileInput.parentNode.removeChild(this.hiddenFileInput),this.hiddenFileInput=null),delete this.element.dropzone,v.instances.splice(v.instances.indexOf(this),1)}updateTotalUploadProgress(){let e,i=0,n=0;if(this.getActiveFiles().length){for(let s of this.getActiveFiles())i+=s.upload.bytesSent,n+=s.upload.total;e=100*i/n}else e=100;return this.emit("totaluploadprogress",e,n,i)}_getParamName(e){return typeof this.options.paramName=="function"?this.options.paramName(e):`${this.options.paramName}${this.options.uploadMultiple?`[${e}]`:""}`}_renameFile(e){return typeof this.options.renameFile!="function"?e.name:this.options.renameFile(e)}getFallbackForm(){let e,i;if(e=this.getExistingFallback())return e;let n='<div class="dz-fallback">';this.options.dictFallbackText&&(n+=`<p>${this.options.dictFallbackText}</p>`),n+=`<input type="file" name="${this._getParamName(0)}" ${this.options.uploadMultiple?'multiple="multiple"':void 0} /><input type="submit" value="Upload!"></div>`;let r=v.createElement(n);return this.element.tagName!=="FORM"?(i=v.createElement(`<form action="${this.options.url}" enctype="multipart/form-data" method="${this.options.method}"></form>`),i.appendChild(r)):(this.element.setAttribute("enctype","multipart/form-data"),this.element.setAttribute("method",this.options.method)),i??r}getExistingFallback(){let e=function(n){for(let r of n)if(/(^| )fallback($| )/.test(r.className))return r};for(let n of["div","form"]){var i;if(i=e(this.element.getElementsByTagName(n)))return i}}setupEventListeners(){return this.listeners.map(e=>(()=>{let i=[];for(let n in e.events){let r=e.events[n];i.push(e.element.addEventListener(n,r,!1))}return i})())}removeEventListeners(){return this.listeners.map(e=>(()=>{let i=[];for(let n in e.events){let r=e.events[n];i.push(e.element.removeEventListener(n,r,!1))}return i})())}disable(){return this.clickableElements.forEach(e=>e.classList.remove("dz-clickable")),this.removeEventListeners(),this.disabled=!0,this.files.map(e=>this.cancelUpload(e))}enable(){return delete this.disabled,this.clickableElements.forEach(e=>e.classList.add("dz-clickable")),this.setupEventListeners()}filesize(e){let i=0,n="b";if(e>0){let r=["tb","gb","mb","kb","b"];for(let s=0;s<r.length;s++){let o=r[s],a=Math.pow(this.options.filesizeBase,4-s)/10;if(e>=a){i=e/Math.pow(this.options.filesizeBase,4-s),n=o;break}}i=Math.round(10*i)/10}return`<strong>${i}</strong> ${this.options.dictFileSizeUnits[n]}`}_updateMaxFilesReachedClass(){return this.options.maxFiles!=null&&this.getAcceptedFiles().length>=this.options.maxFiles?(this.getAcceptedFiles().length===this.options.maxFiles&&this.emit("maxfilesreached",this.files),this.element.classList.add("dz-max-files-reached")):this.element.classList.remove("dz-max-files-reached")}drop(e){if(!e.dataTransfer)return;this.emit("drop",e);let i=[];for(let n=0;n<e.dataTransfer.files.length;n++)i[n]=e.dataTransfer.files[n];if(i.length){let{items:n}=e.dataTransfer;n&&n.length&&n[0].webkitGetAsEntry!=null?this._addFilesFromItems(n):this.handleFiles(i)}this.emit("addedfiles",i)}paste(e){if(zf(e!=null?e.clipboardData:void 0,n=>n.items)==null)return;this.emit("paste",e);let{items:i}=e.clipboardData;if(i.length)return this._addFilesFromItems(i)}handleFiles(e){for(let i of e)this.addFile(i)}_addFilesFromItems(e){return(()=>{let i=[];for(let r of e){var n;r.webkitGetAsEntry!=null&&(n=r.webkitGetAsEntry())?n.isFile?i.push(this.addFile(r.getAsFile())):n.isDirectory?i.push(this._addFilesFromDirectory(n,n.name)):i.push(void 0):r.getAsFile!=null&&(r.kind==null||r.kind==="file")?i.push(this.addFile(r.getAsFile())):i.push(void 0)}return i})()}_addFilesFromDirectory(e,i){let n=e.createReader(),r=o=>Pf(console,"log",a=>a.log(o));var s=()=>n.readEntries(o=>{if(o.length>0){for(let a of o)a.isFile?a.file(l=>{if(!(this.options.ignoreHiddenFiles&&l.name.substring(0,1)==="."))return l.fullPath=`${i}/${l.name}`,this.addFile(l)}):a.isDirectory&&this._addFilesFromDirectory(a,`${i}/${a.name}`);s()}return null},r);return s()}accept(e,i){this.options.maxFilesize&&e.size>this.options.maxFilesize*1048576?i(this.options.dictFileTooBig.replace("{{filesize}}",Math.round(e.size/1024/10.24)/100).replace("{{maxFilesize}}",this.options.maxFilesize)):v.isValidFile(e,this.options.acceptedFiles)?this.options.maxFiles!=null&&this.getAcceptedFiles().length>=this.options.maxFiles?(i(this.options.dictMaxFilesExceeded.replace("{{maxFiles}}",this.options.maxFiles)),this.emit("maxfilesexceeded",e)):this.options.accept.call(this,e,i):i(this.options.dictInvalidFileType)}addFile(e){e.upload={uuid:v.uuidv4(),progress:0,total:e.size,bytesSent:0,filename:this._renameFile(e)},this.files.push(e),e.status=v.ADDED,this.emit("addedfile",e),this._enqueueThumbnail(e),this.accept(e,i=>{i?(e.accepted=!1,this._errorProcessing([e],i)):(e.accepted=!0,this.options.autoQueue&&this.enqueueFile(e)),this._updateMaxFilesReachedClass()})}enqueueFiles(e){for(let i of e)this.enqueueFile(i);return null}enqueueFile(e){if(e.status===v.ADDED&&e.accepted===!0){if(e.status=v.QUEUED,this.options.autoProcessQueue)return setTimeout(()=>this.processQueue(),0)}else throw new Error("This file can't be queued because it has already been processed or was rejected.")}_enqueueThumbnail(e){if(this.options.createImageThumbnails&&e.type.match(/image.*/)&&e.size<=this.options.maxThumbnailFilesize*1048576)return this._thumbnailQueue.push(e),setTimeout(()=>this._processThumbnailQueue(),0)}_processThumbnailQueue(){if(this._processingThumbnail||this._thumbnailQueue.length===0)return;this._processingThumbnail=!0;let e=this._thumbnailQueue.shift();return this.createThumbnail(e,this.options.thumbnailWidth,this.options.thumbnailHeight,this.options.thumbnailMethod,!0,i=>(this.emit("thumbnail",e,i),this._processingThumbnail=!1,this._processThumbnailQueue()))}removeFile(e){if(e.status===v.UPLOADING&&this.cancelUpload(e),this.files=Ff(this.files,e),this.emit("removedfile",e),this.files.length===0)return this.emit("reset")}removeAllFiles(e){e==null&&(e=!1);for(let i of this.files.slice())(i.status!==v.UPLOADING||e)&&this.removeFile(i);return null}resizeImage(e,i,n,r,s){return this.createThumbnail(e,i,n,r,!0,(o,a)=>{if(a==null)return s(e);{let{resizeMimeType:l}=this.options;l==null&&(l=e.type);let c=a.toDataURL(l,this.options.resizeQuality);return(l==="image/jpeg"||l==="image/jpg")&&(c=wl.restore(e.dataURL,c)),s(v.dataURItoBlob(c))}})}createThumbnail(e,i,n,r,s,o){let a=new FileReader;a.onload=()=>{if(e.dataURL=a.result,e.type==="image/svg+xml"){o!=null&&o(a.result);return}this.createThumbnailFromUrl(e,i,n,r,s,o)},a.readAsDataURL(e)}displayExistingFile(e,i,n,r,s=!0){if(this.emit("addedfile",e),this.emit("complete",e),!s)this.emit("thumbnail",e,i),n&&n();else{let o=a=>{this.emit("thumbnail",e,a),n&&n()};e.dataURL=i,this.createThumbnailFromUrl(e,this.options.thumbnailWidth,this.options.thumbnailHeight,this.options.thumbnailMethod,this.options.fixOrientation,o,r)}}createThumbnailFromUrl(e,i,n,r,s,o,a){let l=document.createElement("img");return a&&(l.crossOrigin=a),s=getComputedStyle(document.body).imageOrientation=="from-image"?!1:s,l.onload=()=>{let c=u=>u(1);return typeof EXIF<"u"&&EXIF!==null&&s&&(c=u=>EXIF.getData(l,function(){return u(EXIF.getTag(this,"Orientation"))})),c(u=>{e.width=l.width,e.height=l.height;let d=this.options.resize.call(this,e,i,n,r),p=document.createElement("canvas"),f=p.getContext("2d");switch(p.width=d.trgWidth,p.height=d.trgHeight,u>4&&(p.width=d.trgHeight,p.height=d.trgWidth),u){case 2:f.translate(p.width,0),f.scale(-1,1);break;case 3:f.translate(p.width,p.height),f.rotate(Math.PI);break;case 4:f.translate(0,p.height),f.scale(1,-1);break;case 5:f.rotate(.5*Math.PI),f.scale(1,-1);break;case 6:f.rotate(.5*Math.PI),f.translate(0,-p.width);break;case 7:f.rotate(.5*Math.PI),f.translate(p.height,-p.width),f.scale(-1,1);break;case 8:f.rotate(-.5*Math.PI),f.translate(-p.height,0);break}Lf(f,l,d.srcX!=null?d.srcX:0,d.srcY!=null?d.srcY:0,d.srcWidth,d.srcHeight,d.trgX!=null?d.trgX:0,d.trgY!=null?d.trgY:0,d.trgWidth,d.trgHeight);let b=p.toDataURL("image/png");if(o!=null)return o(b,p)})},o!=null&&(l.onerror=o),l.src=e.dataURL}processQueue(){let{parallelUploads:e}=this.options,i=this.getUploadingFiles().length,n=i;if(i>=e)return;let r=this.getQueuedFiles();if(r.length>0){if(this.options.uploadMultiple)return this.processFiles(r.slice(0,e-i));for(;n<e;){if(!r.length)return;this.processFile(r.shift()),n++}}}processFile(e){return this.processFiles([e])}processFiles(e){for(let i of e)i.processing=!0,i.status=v.UPLOADING,this.emit("processing",i);return this.options.uploadMultiple&&this.emit("processingmultiple",e),this.uploadFiles(e)}_getFilesWithXhr(e){return this.files.filter(i=>i.xhr===e).map(i=>i)}cancelUpload(e){if(e.status===v.UPLOADING){let i=this._getFilesWithXhr(e.xhr);for(let n of i)n.status=v.CANCELED;typeof e.xhr<"u"&&e.xhr.abort();for(let n of i)this.emit("canceled",n);this.options.uploadMultiple&&this.emit("canceledmultiple",i)}else(e.status===v.ADDED||e.status===v.QUEUED)&&(e.status=v.CANCELED,this.emit("canceled",e),this.options.uploadMultiple&&this.emit("canceledmultiple",[e]));if(this.options.autoProcessQueue)return this.processQueue()}resolveOption(e,...i){return typeof e=="function"?e.apply(this,i):e}uploadFile(e){return this.uploadFiles([e])}uploadFiles(e){this._transformFiles(e,i=>{if(this.options.chunking){let n=i[0];e[0].upload.chunked=this.options.chunking&&(this.options.forceChunking||n.size>this.options.chunkSize),e[0].upload.totalChunkCount=Math.ceil(n.size/this.options.chunkSize)}if(e[0].upload.chunked){let n=e[0],r=i[0];n.upload.chunks=[];let s=()=>{let o=0;for(;n.upload.chunks[o]!==void 0;)o++;if(o>=n.upload.totalChunkCount)return;let a=o*this.options.chunkSize,l=Math.min(a+this.options.chunkSize,r.size),c={name:this._getParamName(0),data:r.webkitSlice?r.webkitSlice(a,l):r.slice(a,l),filename:n.upload.filename,chunkIndex:o};n.upload.chunks[o]={file:n,index:o,dataBlock:c,status:v.UPLOADING,progress:0,retries:0},this._uploadData(e,[c])};if(n.upload.finishedChunkUpload=(o,a)=>{let l=!0;o.status=v.SUCCESS,o.dataBlock=null,o.response=o.xhr.responseText,o.responseHeaders=o.xhr.getAllResponseHeaders(),o.xhr=null;for(let c=0;c<n.upload.totalChunkCount;c++){if(n.upload.chunks[c]===void 0)return s();n.upload.chunks[c].status!==v.SUCCESS&&(l=!1)}l&&this.options.chunksUploaded(n,()=>{this._finished(e,a,null)})},this.options.parallelChunkUploads)for(let o=0;o<n.upload.totalChunkCount;o++)s();else s()}else{let n=[];for(let r=0;r<e.length;r++)n[r]={name:this._getParamName(r),data:i[r],filename:e[r].upload.filename};this._uploadData(e,n)}})}_getChunk(e,i){for(let n=0;n<e.upload.totalChunkCount;n++)if(e.upload.chunks[n]!==void 0&&e.upload.chunks[n].xhr===i)return e.upload.chunks[n]}_uploadData(e,i){let n=new XMLHttpRequest;for(let c of e)c.xhr=n;e[0].upload.chunked&&(e[0].upload.chunks[i[0].chunkIndex].xhr=n);let r=this.resolveOption(this.options.method,e,i),s=this.resolveOption(this.options.url,e,i);n.open(r,s,!0),this.resolveOption(this.options.timeout,e)&&(n.timeout=this.resolveOption(this.options.timeout,e)),n.withCredentials=!!this.options.withCredentials,n.onload=c=>{this._finishedUploading(e,n,c)},n.ontimeout=()=>{this._handleUploadError(e,n,`Request timedout after ${this.options.timeout/1e3} seconds`)},n.onerror=()=>{this._handleUploadError(e,n)};let a=n.upload!=null?n.upload:n;a.onprogress=c=>this._updateFilesUploadProgress(e,n,c);let l=this.options.defaultHeaders?{Accept:"application/json","Cache-Control":"no-cache","X-Requested-With":"XMLHttpRequest"}:{};this.options.binaryBody&&(l["Content-Type"]=e[0].type),this.options.headers&&Zs(l,this.options.headers);for(let c in l){let u=l[c];u&&n.setRequestHeader(c,u)}if(this.options.binaryBody){for(let c of e)this.emit("sending",c,n);this.options.uploadMultiple&&this.emit("sendingmultiple",e,n),this.submitRequest(n,null,e)}else{let c=new FormData;if(this.options.params){let u=this.options.params;typeof u=="function"&&(u=u.call(this,e,n,e[0].upload.chunked?this._getChunk(e[0],n):null));for(let d in u){let p=u[d];if(Array.isArray(p))for(let f=0;f<p.length;f++)c.append(d,p[f]);else c.append(d,p)}}for(let u of e)this.emit("sending",u,n,c);this.options.uploadMultiple&&this.emit("sendingmultiple",e,n,c),this._addFormElementData(c);for(let u=0;u<i.length;u++){let d=i[u];c.append(d.name,d.data,d.filename)}this.submitRequest(n,c,e)}}_transformFiles(e,i){let n=[],r=0;for(let s=0;s<e.length;s++)this.options.transformFile.call(this,e[s],o=>{n[s]=o,++r===e.length&&i(n)})}_addFormElementData(e){if(this.element.tagName==="FORM")for(let i of this.element.querySelectorAll("input, textarea, select, button")){let n=i.getAttribute("name"),r=i.getAttribute("type");if(r&&(r=r.toLowerCase()),!(typeof n>"u"||n===null))if(i.tagName==="SELECT"&&i.hasAttribute("multiple"))for(let s of i.options)s.selected&&e.append(n,s.value);else(!r||r!=="checkbox"&&r!=="radio"||i.checked)&&e.append(n,i.value)}}_updateFilesUploadProgress(e,i,n){if(e[0].upload.chunked){let r=e[0],s=this._getChunk(r,i);n?(s.progress=100*n.loaded/n.total,s.total=n.total,s.bytesSent=n.loaded):(s.progress=100,s.bytesSent=s.total),r.upload.progress=0,r.upload.total=0,r.upload.bytesSent=0;for(let o=0;o<r.upload.totalChunkCount;o++)r.upload.chunks[o]&&typeof r.upload.chunks[o].progress<"u"&&(r.upload.progress+=r.upload.chunks[o].progress,r.upload.total+=r.upload.chunks[o].total,r.upload.bytesSent+=r.upload.chunks[o].bytesSent);r.upload.progress=r.upload.progress/r.upload.totalChunkCount,this.emit("uploadprogress",r,r.upload.progress,r.upload.bytesSent)}else for(let r of e)r.upload.total&&r.upload.bytesSent&&r.upload.bytesSent==r.upload.total||(n?(r.upload.progress=100*n.loaded/n.total,r.upload.total=n.total,r.upload.bytesSent=n.loaded):(r.upload.progress=100,r.upload.bytesSent=r.upload.total),this.emit("uploadprogress",r,r.upload.progress,r.upload.bytesSent))}_finishedUploading(e,i,n){let r;if(e[0].status!==v.CANCELED&&i.readyState===4){if(i.responseType!=="arraybuffer"&&i.responseType!=="blob"&&(r=i.responseText,i.getResponseHeader("content-type")&&~i.getResponseHeader("content-type").indexOf("application/json")))try{r=JSON.parse(r)}catch(s){n=s,r="Invalid JSON response from server."}this._updateFilesUploadProgress(e,i),200<=i.status&&i.status<300?e[0].upload.chunked?e[0].upload.finishedChunkUpload(this._getChunk(e[0],i),r):this._finished(e,r,n):this._handleUploadError(e,i,r)}}_handleUploadError(e,i,n){if(e[0].status!==v.CANCELED){if(e[0].upload.chunked&&this.options.retryChunks){let r=this._getChunk(e[0],i);if(r.retries++<this.options.retryChunksLimit){this._uploadData(e,[r.dataBlock]);return}else console.warn("Retried this chunk too often. Giving up.")}this._errorProcessing(e,n||this.options.dictResponseError.replace("{{statusCode}}",i.status),i)}}submitRequest(e,i,n){if(e.readyState!=1){console.warn("Cannot send this request because the XMLHttpRequest.readyState is not OPENED.");return}if(this.options.binaryBody)if(n[0].upload.chunked){const r=this._getChunk(n[0],e);e.send(r.dataBlock.data)}else e.send(n[0]);else e.send(i)}_finished(e,i,n){for(let r of e)r.status=v.SUCCESS,this.emit("success",r,i,n),this.emit("complete",r);if(this.options.uploadMultiple&&(this.emit("successmultiple",e,i,n),this.emit("completemultiple",e)),this.options.autoProcessQueue)return this.processQueue()}_errorProcessing(e,i,n){for(let r of e)r.status=v.ERROR,this.emit("error",r,i,n),this.emit("complete",r);if(this.options.uploadMultiple&&(this.emit("errormultiple",e,i,n),this.emit("completemultiple",e)),this.options.autoProcessQueue)return this.processQueue()}static uuidv4(){return"xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g,function(e){let i=Math.random()*16|0;return(e==="x"?i:i&3|8).toString(16)})}constructor(e,i){super();let n,r;if(this.element=e,this.clickableElements=[],this.listeners=[],this.files=[],typeof this.element=="string"&&(this.element=document.querySelector(this.element)),!this.element||this.element.nodeType==null)throw new Error("Invalid dropzone element.");if(this.element.dropzone)throw new Error("Dropzone already attached.");v.instances.push(this),this.element.dropzone=this;let s=(r=v.optionsForElement(this.element))!=null?r:{};if(this.options=Zs(!0,{},Tf,s,i??{}),this.options.previewTemplate=this.options.previewTemplate.replace(/\n*/g,""),this.options.forceFallback||!v.isBrowserSupported())return this.options.fallback.call(this);if(this.options.url==null&&(this.options.url=this.element.getAttribute("action")),!this.options.url)throw new Error("No URL provided.");if(this.options.acceptedFiles&&this.options.acceptedMimeTypes)throw new Error("You can't provide both 'acceptedFiles' and 'acceptedMimeTypes'. 'acceptedMimeTypes' is deprecated.");if(this.options.uploadMultiple&&this.options.chunking)throw new Error("You cannot set both: uploadMultiple and chunking.");if(this.options.binaryBody&&this.options.uploadMultiple)throw new Error("You cannot set both: binaryBody and uploadMultiple.");this.options.acceptedMimeTypes&&(this.options.acceptedFiles=this.options.acceptedMimeTypes,delete this.options.acceptedMimeTypes),this.options.renameFilename!=null&&(this.options.renameFile=o=>this.options.renameFilename.call(this,o.name,o)),typeof this.options.method=="string"&&(this.options.method=this.options.method.toUpperCase()),(n=this.getExistingFallback())&&n.parentNode&&n.parentNode.removeChild(n),this.options.previewsContainer!==!1&&(this.options.previewsContainer?this.previewsContainer=v.getElement(this.options.previewsContainer,"previewsContainer"):this.previewsContainer=this.element),this.options.clickable&&(this.options.clickable===!0?this.clickableElements=[this.element]:this.clickableElements=v.getElements(this.options.clickable,"clickable")),this.init()}}v.initClass();v.options={};v.optionsForElement=function(t){if(t.getAttribute("id"))return v.options[Of(t.getAttribute("id"))]};v.instances=[];v.forElement=function(t){if(typeof t=="string"&&(t=document.querySelector(t)),(t!=null?t.dropzone:void 0)==null)throw new Error("No Dropzone found for given element. This is probably because you're trying to access it before Dropzone had the time to initialize. Use the `init` option to setup any additional observers on your Dropzone.");return t.dropzone};v.discover=function(){let t;if(document.querySelectorAll)t=document.querySelectorAll(".dropzone");else{t=[];let e=i=>(()=>{let n=[];for(let r of i)/(^| )dropzone($| )/.test(r.className)?n.push(t.push(r)):n.push(void 0);return n})();e(document.getElementsByTagName("div")),e(document.getElementsByTagName("form"))}return(()=>{let e=[];for(let i of t)v.optionsForElement(i)!==!1?e.push(new v(i)):e.push(void 0);return e})()};v.blockedBrowsers=[/opera.*(Macintosh|Windows Phone).*version\/12/i];v.isBrowserSupported=function(){let t=!0;if(window.File&&window.FileReader&&window.FileList&&window.Blob&&window.FormData&&document.querySelector)if(!("classList"in document.createElement("a")))t=!1;else{v.blacklistedBrowsers!==void 0&&(v.blockedBrowsers=v.blacklistedBrowsers);for(let e of v.blockedBrowsers)if(e.test(navigator.userAgent)){t=!1;continue}}else t=!1;return t};v.dataURItoBlob=function(t){let e=atob(t.split(",")[1]),i=t.split(",")[0].split(":")[1].split(";")[0],n=new ArrayBuffer(e.length),r=new Uint8Array(n);for(let s=0,o=e.length,a=0<=o;a?s<=o:s>=o;a?s++:s--)r[s]=e.charCodeAt(s);return new Blob([n],{type:i})};const Ff=(t,e)=>t.filter(i=>i!==e).map(i=>i),Of=t=>t.replace(/[\-_](\w)/g,e=>e.charAt(1).toUpperCase());v.createElement=function(t){let e=document.createElement("div");return e.innerHTML=t,e.childNodes[0]};v.elementInside=function(t,e){if(t===e)return!0;for(;t=t.parentNode;)if(t===e)return!0;return!1};v.getElement=function(t,e){let i;if(typeof t=="string"?i=document.querySelector(t):t.nodeType!=null&&(i=t),i==null)throw new Error(`Invalid \`${e}\` option provided. Please provide a CSS selector or a plain HTML element.`);return i};v.getElements=function(t,e){let i,n;if(t instanceof Array){n=[];try{for(i of t)n.push(this.getElement(i,e))}catch{n=null}}else if(typeof t=="string"){n=[];for(i of document.querySelectorAll(t))n.push(i)}else t.nodeType!=null&&(n=[t]);if(n==null||!n.length)throw new Error(`Invalid \`${e}\` option provided. Please provide a CSS selector, a plain HTML element or a list of those.`);return n};v.confirm=function(t,e,i){if(window.confirm(t))return e();if(i!=null)return i()};v.isValidFile=function(t,e){if(!e)return!0;e=e.split(",");let i=t.type,n=i.replace(/\/.*$/,"");for(let r of e)if(r=r.trim(),r.charAt(0)==="."){if(t.name.toLowerCase().indexOf(r.toLowerCase(),t.name.length-r.length)!==-1)return!0}else if(/\/\*$/.test(r)){if(n===r.replace(/\/.*$/,""))return!0}else if(i===r)return!0;return!1};typeof jQuery<"u"&&jQuery!==null&&(jQuery.fn.dropzone=function(t){return this.each(function(){return new v(this,t)})});v.ADDED="added";v.QUEUED="queued";v.ACCEPTED=v.QUEUED;v.UPLOADING="uploading";v.PROCESSING=v.UPLOADING;v.CANCELED="canceled";v.ERROR="error";v.SUCCESS="success";let Mf=function(t){t.naturalWidth;let e=t.naturalHeight,i=document.createElement("canvas");i.width=1,i.height=e;let n=i.getContext("2d");n.drawImage(t,0,0);let{data:r}=n.getImageData(1,0,1,e),s=0,o=e,a=e;for(;a>s;)r[(a-1)*4+3]===0?o=a:s=a,a=o+s>>1;let l=a/e;return l===0?1:l};var Lf=function(t,e,i,n,r,s,o,a,l,c){let u=Mf(e);return t.drawImage(e,i,n,r,s,o,a,l,c/u)};class wl{static initClass(){this.KEY_STR="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/="}static encode64(e){let i="",n,r,s="",o,a,l,c="",u=0;for(;n=e[u++],r=e[u++],s=e[u++],o=n>>2,a=(n&3)<<4|r>>4,l=(r&15)<<2|s>>6,c=s&63,isNaN(r)?l=c=64:isNaN(s)&&(c=64),i=i+this.KEY_STR.charAt(o)+this.KEY_STR.charAt(a)+this.KEY_STR.charAt(l)+this.KEY_STR.charAt(c),n=r=s="",o=a=l=c="",u<e.length;);return i}static restore(e,i){if(!e.match("data:image/jpeg;base64,"))return i;let n=this.decode64(e.replace("data:image/jpeg;base64,","")),r=this.slice2Segments(n),s=this.exifManipulation(i,r);return`data:image/jpeg;base64,${this.encode64(s)}`}static exifManipulation(e,i){let n=this.getExifArray(i),r=this.insertExif(e,n);return new Uint8Array(r)}static getExifArray(e){let i,n=0;for(;n<e.length;){if(i=e[n],i[0]===255&i[1]===225)return i;n++}return[]}static insertExif(e,i){let n=e.replace("data:image/jpeg;base64,",""),r=this.decode64(n),s=r.indexOf(255,3),o=r.slice(0,s),a=r.slice(s),l=o;return l=l.concat(i),l=l.concat(a),l}static slice2Segments(e){let i=0,n=[];for(;;){var r;if(e[i]===255&e[i+1]===218)break;if(e[i]===255&e[i+1]===216)i+=2;else{r=e[i+2]*256+e[i+3];let s=i+r+2,o=e.slice(i,s);n.push(o),i=s}if(i>e.length)break}return n}static decode64(e){let i,n,r="",s,o,a,l="",c=0,u=[];for(/[^A-Za-z0-9\+\/\=]/g.exec(e)&&console.warn(`There were invalid base64 characters in the input text.
Valid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='
Expect errors in decoding.`),e=e.replace(/[^A-Za-z0-9\+\/\=]/g,"");s=this.KEY_STR.indexOf(e.charAt(c++)),o=this.KEY_STR.indexOf(e.charAt(c++)),a=this.KEY_STR.indexOf(e.charAt(c++)),l=this.KEY_STR.indexOf(e.charAt(c++)),i=s<<2|o>>4,n=(o&15)<<4|a>>2,r=(a&3)<<6|l,u.push(i),a!==64&&u.push(n),l!==64&&u.push(r),i=n=r="",s=o=a=l="",c<e.length;);return u}}wl.initClass();function zf(t,e){return typeof t<"u"&&t!==null?e(t):void 0}function Pf(t,e,i){if(typeof t<"u"&&t!==null&&typeof t[e]=="function")return i(t,e)}window.Alpine=Hr;Hr.plugin(kf);Hr.start();v.autoDiscover=!1;const io=document.getElementById("file-upload-dropzone"),En=document.getElementById("messageForm"),Sn=document.getElementById("message"),qt=document.getElementById("file_upload_ids");if(io&&En&&Sn&&qt){const t=document.querySelector('meta[name="csrf-token"]').getAttribute("content"),e=io.dataset.uploadUrl;if(!e)console.error("Dropzone element is missing the data-upload-url attribute!");else{const i=new v("#file-upload-dropzone",{url:e,paramName:"file",maxFilesize:5e3,chunking:!0,forceChunking:!0,chunkSize:5242880,retryChunks:!0,retryChunksLimit:3,parallelChunkUploads:!1,addRemoveLinks:!0,autoProcessQueue:!1,headers:{"X-CSRF-TOKEN":t},params:function(n,r,s){const o={};s&&(o.dzuuid=s.file.upload.uuid,o.dzchunkindex=s.index,o.dztotalfilesize=s.file.size,o.dzchunksize=this.options.chunkSize,o.dztotalchunkcount=s.file.upload.totalChunkCount,o.dzchunkbyteoffset=s.index*this.options.chunkSize);const a=document.getElementById("company_user_id");return a&&a.value&&(o.company_user_id=a.value),o},uploadprogress:function(n,r,s){},success:function(n,r){if(console.log(`Success callback for ${n.name}:`,r),r&&r.file_upload_id){if(console.log(`Final FileUpload ID for ${n.name}: ${r.file_upload_id}`),!n.finalIdReceived){n.finalIdReceived=!0,n.file_upload_id=r.file_upload_id;let s=qt.value?JSON.parse(qt.value):[];s.includes(r.file_upload_id)||(s.push(r.file_upload_id),qt.value=JSON.stringify(s),console.log("Updated file_upload_ids:",qt.value))}}else console.log(`Received intermediate chunk success for ${n.name}`)},error:function(n,r,s){console.error("Error uploading file chunk:",n.name,r,s);const o=document.getElementById("upload-errors");if(o){const a=typeof r=="object"?r.error||JSON.stringify(r):r;o.innerHTML+=`<p class="text-red-500">Error uploading ${n.name}: ${a}</p>`,o.classList.remove("hidden")}},complete:function(n){console.log("File processing complete (success or error): ",n.name),i.processQueue()}});En.addEventListener("submit",function(n){n.preventDefault();const r=this.querySelector('button[type="submit"]'),s=i.getQueuedFiles(),o=i.getFilesWithStatus(v.UPLOADING),a=i.getFilesWithStatus(v.SUCCESS).length+i.getFilesWithStatus(v.ERROR).length;console.log(`Submit triggered. Queued: ${s.length}, InProgress: ${o.length}, Done: ${a}`),s.length>0?(console.log("Starting file uploads for queue..."),r.disabled=!0,r.textContent="Uploading Files...",i.processQueue()):i.getFilesWithStatus(v.SUCCESS).length>0?(console.log("Files already uploaded, attempting to associate message via queuecomplete."),console.log("Submit triggered, but files seem already uploaded."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-error"}))):(console.log("Submit triggered, but no files added."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"no-files-error"})))}),i.on("queuecomplete",function(){const n=i.getFilesWithStatus(v.SUCCESS).length+i.getFilesWithStatus(v.ERROR).length,r=i.files.length;console.log(`--- Queue Complete Fired --- Processed: ${n}, Total Added: ${r}`);const s=En.querySelector('button[type="submit"]'),o=Sn.value,l=i.getFilesWithStatus(v.SUCCESS).map(c=>c.file_upload_id).filter(c=>c);if(console.log("Queue complete. Message:",o),console.log("Queue complete. Successful file IDs:",l),o&&l.length>0){console.log("Attempting to associate message..."),s.textContent="Associating Message...";const c=window.employeeUploadConfig?window.employeeUploadConfig.associateMessageUrl:"/client/uploads/associate-message";fetch(c,{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":t},body:JSON.stringify({message:o,file_upload_ids:l})}).then(u=>{if(!u.ok)throw u.text().then(d=>{console.error("Error response from associate-message:",u.status,d)}),new Error(`HTTP error! status: ${u.status}`);return u.json()}).then(u=>{console.log("Message associated successfully:",u),Sn.value="",qt.value="[]",i.removeAllFiles(!0),window.dispatchEvent(new CustomEvent("open-modal",{detail:"association-success"}))}).catch(u=>{console.error("Error associating message:",u),window.dispatchEvent(new CustomEvent("open-modal",{detail:"association-error"}))}).finally(()=>{s.disabled=!1,s.textContent="Upload and Send Message"})}else if(l.length>0&&!o){console.log("Batch upload complete without message. Successful IDs:",l),console.log("Calling /api/uploads/batch-complete..."),s.textContent="Finalizing Upload...",s.disabled=!0;const c=window.employeeUploadConfig?window.employeeUploadConfig.batchCompleteUrl:"/client/uploads/batch-complete";fetch(c,{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":t},body:JSON.stringify({file_upload_ids:l})}).then(u=>{if(!u.ok)throw console.error("Error response from batch-complete endpoint:",u.status),u.text().then(d=>console.error("Batch Complete Error Body:",d)),new Error(`HTTP error! status: ${u.status}`);return u.json()}).then(u=>{console.log("Backend acknowledged batch completion:",u),console.log("Dispatching upload-success modal..."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-success"})),console.log("Attempting to clear Dropzone UI..."),i.removeAllFiles(!0),console.log("Dropzone UI should be cleared now."),console.log("Attempting to clear file IDs input..."),qt.value="[]",console.log("File IDs input cleared.")}).catch(u=>{console.error("Error calling batch-complete endpoint:",u),window.dispatchEvent(new CustomEvent("open-modal",{detail:"association-error"}))}).finally(()=>{s.disabled=!1,s.textContent="Upload and Send Message",i.getRejectedFiles().length>0&&(console.log("Found rejected files, dispatching upload-error modal as well."),window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-error"})))})}else console.log("Queue finished, but no successful uploads or handling other cases."),l.length===0&&(s.disabled=!1,s.textContent="Upload and Send Message",i.getRejectedFiles().length>0&&window.dispatchEvent(new CustomEvent("open-modal",{detail:"upload-error"})))})}}const If=window.location.hostname;document.querySelectorAll('a[href^="http"]:not([href*="'+If+'"]):not([href^="#"]):not(.button-link)').forEach(t=>{t.querySelector(".external-link-icon")||(t.innerHTML+='<svg class="external-link-icon" xmlns="http://www.w3.org/2000/svg" baseProfile="tiny" version="1.2" viewBox="0 0 79 79"><path d="M64,39.8v34.7c0,2.5-2,4.5-4.5,4.5H4.5c-2.5,0-4.5-2-4.5-4.5V19.5c0-2.5,2-4.5,4.5-4.5h35.6c2.5,0,4.5,2,4.5,4.5s-.5,2.4-1.4,3.3c-.8.8-1.9,1.3-3.1,1.3H9v45.9h45.9v-30.2c0-1.3.5-2.4,1.4-3.3.8-.8,1.9-1.3,3.1-1.3,2.5,0,4.5,2,4.5,4.5h0Z"/><path d="M74.5,0h-28.7c-2.2,0-4.2,1.5-4.6,3.6s1.6,5.5,4.4,5.5h17.9l-31.5,31.6c-1.8,1.8-1.8,4.7,0,6.5h0c1.7,1.8,4.6,1.8,6.3,0l31.6-31.6v17.7c0,2.2,1.5,4.2,3.6,4.6s5.5-1.6,5.5-4.4V4.7c0-2.5-2-4.5-4.5-4.5h0v-.2Z"/></svg>')});
