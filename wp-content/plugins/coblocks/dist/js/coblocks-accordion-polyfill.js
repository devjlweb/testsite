!function(){var e={1471:function(e,t,n){var o,r;void 0===(r="function"==typeof(o=function(){const e="details",t="summary";(function(){const n=document.createElement(e);if(!("open"in n))return!1;n.innerHTML="<"+t+">a</"+t+">b",document.body.appendChild(n);const o=n.offsetHeight;n.open=!0;const r=o!==n.offsetHeight;return document.body.removeChild(n),r})()||(document.documentElement.className+=" no-details",window.addEventListener("click",(function(e){if("summary"===e.target.nodeName.toLowerCase()){const t=e.target.parentNode;if(!t)return;t.getAttribute("open")?(t.open=!1,t.removeAttribute("open")):(t.open=!0,t.setAttribute("open","open"))}})),function(e,t){if(document.getElementById(e))return;const n=document.createElement("style");n.id=e,n.innerHTML=t,document.getElementsByTagName("head")[0].appendChild(n)}("details-polyfill-style","html.no-details "+e+":not([open]) > :not("+t+") { display: none; }\nhtml.no-details "+e+" > "+t+':before { border: none; background: transparent; content: "▶"; margin-right: 5px; display: inline-block; font-size: .8em;  }\nhtml.no-details '+e+"[open] > "+t+':before { content: "▼"; }'))})?o.call(t,n,t,e):o)||(e.exports=r)}},t={};!function n(o){var r=t[o];if(void 0!==r)return r.exports;var i=t[o]={exports:{}};return e[o].call(i.exports,i,i.exports,n),i.exports}(1471)}();