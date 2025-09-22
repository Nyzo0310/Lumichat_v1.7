/* LumiCHAT chat.nomodule.js — plain script build (no imports) */
(function () {
  if (window.LUMI_CHAT_JS_ACTIVE) return;
  window.LUMI_CHAT_JS_ACTIVE = true;

  function ready(fn){ if (document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }

  ready(function () {
    if (!window.axios) { console.error('[LumiCHAT] axios not found. Load CDN before this script.'); return; }
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && csrf.content) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.content;

    var form     = document.querySelector('#chat-form');
    var input    = document.querySelector('#chat-message');
    var messages = document.querySelector('#chat-messages');
    var sendBtn  = document.querySelector('#sendBtn');
    var storeUrl = (form && form.getAttribute('action')) || "/chat";

    function scrollBottom(){ if (messages) messages.scrollTop = messages.scrollHeight; }
    function sanitizeHTML(s){ return /[<>]/.test(s) ? s : String(s || '').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function appendUserBubble(text, time){
      if (!messages) return;
      messages.insertAdjacentHTML('beforeend', '\
        <div class="w-full min-w-0">\
          <div class="msg-row user flex items-end justify-end gap-2">\
            <div class="bubble bubble-user px-4 py-2 rounded-2xl text-base text-left max-w-[85%]"></div>\
            <div class="avatar shrink-0 w-8 h-8 rounded-full grid place-items-center">🧑</div>\
          </div>\
          <div class="msg-time text-[10px] opacity-70 mt-1 text-right">'+(time||'')+'</div>\
        </div>');
      var bubble = messages.lastElementChild.querySelector('.bubble-user');
      bubble.textContent = text;
      scrollBottom();
    }

    function addBotActions(bubble){
      try{
        var txt = bubble.textContent || '';
        var plain = txt.toLowerCase();
        var isCoping   = /share\s+coping\s+tips/i.test(txt) || (/coping\s+mechanism/i.test(plain) && /want(\s+them)?\s+now\??/i.test(plain));
        var isReferral = /open the appointment page\??/i.test(txt) || /book\s+counselor/i.test(plain);
        if (isCoping || isReferral){
          var actions = document.createElement('div');
          actions.className = 'bot-actions mt-2 flex gap-2 flex-wrap';
          if (isCoping){
            actions.innerHTML = '\
              <button class="qr-btn text-xs px-3 py-1.5 rounded-md border" data-qr=\'/deny{"confirm_topic":"coping"}\'>No, thanks</button>\
              <button class="qr-btn text-xs px-3 py-1.5 rounded-md border" data-qr=\'/affirm{"confirm_topic":"coping"}\'>Yes, show tips</button>';
          } else {
            actions.innerHTML = '\
              <button class="qr-btn text-xs px-3 py-1.5 rounded-md border" data-qr=\'/deny{"confirm_topic":"referral"}\'>Not now</button>\
              <button class="qr-btn text-xs px-3 py-1.5 rounded-md border" data-qr=\'/affirm{"confirm_topic":"referral"}\'>Book counselor</button>';
          }
          actions.addEventListener('click', function(ev){
            var btn = ev.target.closest('.qr-btn'); if (!btn) return;
            var payload = btn.getAttribute('data-qr') || btn.textContent.trim();
            sendQuick(payload);
          });
          bubble.appendChild(actions);
        }
      }catch(e){ console.warn('[LumiCHAT] addBotActions failed', e); }
    }

    function typewriter(bubble, finalHTML, speed, minDotsMs){
      speed = speed || 24;
      minDotsMs = minDotsMs || 700;
      var prefersReduced = false;
      try { prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch(e){}
      return new Promise(function(resolve){
        if (prefersReduced){
          bubble.innerHTML = finalHTML;
          addBotActions(bubble);
          resolve(); return;
        }
        var start = Date.now();
        function run(){
          if (Date.now() - start < minDotsMs){ return requestAnimationFrame(run); }
          var tmp = document.createElement('div'); tmp.innerHTML = finalHTML;
          var plain = tmp.textContent || tmp.innerText || '';
          bubble.textContent = '';
          var i = 0;
          (function tick(){
            bubble.textContent = plain.slice(0, i+1);
            i++; scrollBottom();
            if (i < plain.length){ setTimeout(tick, speed); }
            else {
              bubble.innerHTML = finalHTML;
              addBotActions(bubble);
              scrollBottom();
              resolve();
            }
          })();
        }
        requestAnimationFrame(run);
      });
    }

    function appendBotBubble(textOrHtml, time){
      time = time || '';
      return new Promise(function(resolve){
        messages.insertAdjacentHTML('beforeend', '\
          <div class="w-full min-w-0">\
            <div class="msg-row bot flex items-end justify-start gap-2">\
              <div class="avatar shrink-0 w-8 h-8 rounded-full grid place-items-center">🤖</div>\
              <div class="bubble bubble-ai px-4 py-2 rounded-2xl text-base text-left max-w-[85%]"></div>\
            </div>\
            <div class="msg-time text-[10px] opacity-70 mt-1">'+time+'</div>\
          </div>');
        var bubble = messages.lastElementChild.querySelector('.bubble-ai');
        // dots
        bubble.innerHTML = '\
          <span class="inline-flex items-center gap-1" style="color:#6b7280">\
            <span class="dot w-2 h-2 rounded-full"></span>\
            <span class="dot w-2 h-2 rounded-full"></span>\
            <span class="dot w-2 h-2 rounded-full"></span>\
          </span>\
          <span class="sr-only">Assistant is typing…</span>';
        scrollBottom();
        // pre-delay
        var pre = 350 + Math.floor(Math.random()*400);
        setTimeout(function(){
          var isHTML = /[<>]/.test(textOrHtml);
          var safeHTML = isHTML ? textOrHtml : String(textOrHtml||'').replace(/</g,'&lt;').replace(/>/g,'&gt;');
          typewriter(bubble, safeHTML, 24, 700).then(resolve);
        }, pre);
      });
    }

    // strict queue
    var botQueue = Promise.resolve();
    function q(task){ botQueue = botQueue.then(task).catch(function(e){ console.warn('[LumiCHAT] queue error', e); }); return botQueue; }

    function sendQuick(text){
      appendUserBubble(text, new Date().toLocaleTimeString());
      send(text);
    }

    async function send(message){
      try{
        if (sendBtn) sendBtn.disabled = true;
        var res = await axios.post(storeUrl, { message: message });
        var replies = Array.isArray(res.data && res.data.bot_reply) ? res.data.bot_reply : [res.data && res.data.bot_reply];
        for (var i=0; i<(replies||[]).length; i++){
          var msg = replies[i];
          var m = (msg && typeof msg === 'object') ? (msg.bot_reply || msg.text || msg.message || '') : msg;
          if (!m) continue;
          await q(function(){ return appendBotBubble(m, (res.data && res.data.time_human) || ''); });
          await q(function(){ return new Promise(function(r){ setTimeout(r, 250); }); });
        }
      } catch(err){
        console.error('[LumiCHAT] Error:', err && (err.response || err.message));
        await q(function(){ return appendBotBubble('Sorry, I’m having trouble right now.', ''); });
      } finally {
        if (sendBtn) sendBtn.disabled = false;
        if (input) input.focus();
      }
    }

    // prevent multiple bindings
    if (form && form.dataset.bound) return;
    if (form) { form.dataset.bound = '1'; form.onsubmit = null; }
    if (form) form.addEventListener('submit', function(e){
      e.preventDefault();
      var raw = (input && input.value) || '';
      var msg = String(raw).trim();
      if (!msg) return;
      input.value = '';
      appendUserBubble(msg, new Date().toLocaleTimeString());
      send(msg);
    });

    console.log('[LumiCHAT] chat.nomodule.js loaded');
  });
})();
