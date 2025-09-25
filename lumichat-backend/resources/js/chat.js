// LumiCHAT chat.js (Vite module) — in-bubble dots + typewriter + strict queue
import axios from "axios";

axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
console.log("%c[LumiCHAT] chat.js loaded (Vite)", "color:#6d28d9;font-weight:bold");

if (!window.LUMI_CHAT_JS_ACTIVE) {
  window.LUMI_CHAT_JS_ACTIVE = true;

  document.addEventListener("DOMContentLoaded", () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrf) axios.defaults.headers.common["X-CSRF-TOKEN"] = csrf;

    const form       = document.querySelector("#chat-form");
    const input      = document.querySelector("#chat-message");
    const messages   = document.querySelector("#chat-messages");
    const sendBtn    = document.querySelector("#sendBtn");
    const STORE_URL  = form?.getAttribute("action") || "/chat";

    // >>> booking URL (change here when you deploy)
    const APPT_URL = "http://127.0.0.1:8000/appointment/book";

    function scrollBottom(){ if (messages) messages.scrollTop = messages.scrollHeight; }

    // Keep sanitizer (used for non-HTML strings)
    function sanitizeHTML(s){
      return /[<>]/.test(s) ? s : String(s||"").replace(/</g,"&lt;").replace(/>/g,"&gt;");
    }

    // --- link helpers: make plain http(s) text clickable inside bubbles
    function linkify(text) {
      const urlRE = /(https?:\/\/[^\s)]+)|(www\.[^\s)]+)/gi;
      return String(text||"").replace(urlRE, (m) => {
        const href = m.startsWith("http") ? m : `http://${m}`;
        return `<a href="${href}" target="_blank" rel="noopener">${m}</a>`;
      });
    }

    // Render Rasa-style buttons. If `url` exists, render <a>; else send payload.
    // Also: if payload is /affirm{"confirm_topic":"referral"} → convert to link (APPT_URL)
    function renderButtons(buttons, { container, sendPayload }) {
      if (!Array.isArray(buttons) || !buttons.length) return;
      const wrap = document.createElement("div");
      wrap.className = "bot-actions mt-2 flex gap-2 flex-wrap";

      buttons.forEach((b) => {
        const referralPayload =
          typeof b?.payload === "string" &&
          /\/affirm\s*\{\s*"confirm_topic"\s*:\s*"referral"\s*\}/i.test(b.payload);

        if (b?.url || referralPayload) {
          const href = b?.url || APPT_URL;
          const a = document.createElement("a");
          a.className = "qr-btn text-xs px-3 py-1.5 rounded-md border";
          a.textContent = b?.title || "Open link";
          a.href = href;
          a.target = "_blank";
          a.rel = "noopener";
          wrap.appendChild(a);
        } else {
          const btn = document.createElement("button");
          btn.className = "qr-btn text-xs px-3 py-1.5 rounded-md border";
          btn.textContent = b?.title || "Select";
          btn.addEventListener("click", () => sendPayload(b?.payload || b?.title || ""));
          wrap.appendChild(btn);
        }
      });

      container.appendChild(wrap);
    }

    function appendUserBubble(text, time=""){
      messages.insertAdjacentHTML("beforeend", `
        <div class="w-full min-w-0">
          <div class="msg-row user flex items-end justify-end gap-2">
            <div class="bubble bubble-user px-4 py-2 rounded-2xl text-base text-left max-w-[85%]"></div>
            <div class="avatar shrink-0 w-8 h-8 rounded-full grid place-items-center">🧑</div>
          </div>
          <div class="msg-time text-[10px] opacity-70 mt-1 text-right">${time}</div>
        </div>`);
      messages.lastElementChild.querySelector(".bubble-user").textContent = text;
      scrollBottom();
    }

    // Replace quick actions: remove affirm/confirm; show direct booking link when referral is detected
    function maybeAddQuickActions(bubble){
      try{
        const txt = bubble.textContent || "";
        const plain = txt.toLowerCase();
        const isCoping   = /share\s+coping\s+tips/i.test(txt) || (/coping\s+mechanism/i.test(plain) && /want(\s+them)?\s+now\??/i.test(plain));
        const isReferral = /open the appointment page\??/i.test(txt) || /book\s+counselor/i.test(plain) || /appointment page/i.test(plain);
        if (!(isCoping || isReferral)) return;

        const box = document.createElement("div");
        box.className = "bot-actions mt-2 flex gap-2 flex-wrap";

        if (isReferral) {
          // Direct link (open in new tab). No /affirm payloads anymore.
          const a = document.createElement("a");
          a.className = "qr-btn text-xs px-3 py-1.5 rounded-md border";
          a.textContent = "Book counselor";
          a.href = APPT_URL;
          a.target = "_blank";
          a.rel = "noopener";
          box.appendChild(a);

          const btnNotNow = document.createElement("button");
          btnNotNow.className = "qr-btn text-xs px-3 py-1.5 rounded-md border";
          btnNotNow.textContent = "Not now";
          btnNotNow.addEventListener("click", () => sendQuick('/deny{"confirm_topic":"referral"}'));
          box.appendChild(btnNotNow);
        } else if (isCoping) {
          // Keep coping quick replies
          const noBtn = document.createElement("button");
          noBtn.className = "qr-btn text-xs px-3 py-1.5 rounded-md border";
          noBtn.textContent = "No, thanks";
          noBtn.addEventListener("click", () => sendQuick('/deny{"confirm_topic":"coping"}'));
          box.appendChild(noBtn);

          const yesBtn = document.createElement("button");
          yesBtn.className = "qr-btn text-xs px-3 py-1.5 rounded-md border";
          yesBtn.textContent = "Yes, show tips";
          yesBtn.addEventListener("click", () => sendQuick('/affirm{"confirm_topic":"coping"}'));
          box.appendChild(yesBtn);
        }

        bubble.appendChild(box);
      }catch{}
    }

    function typewriter(bubble, finalHTML, speed=24, minDotsMs=650){
      const reduced = window.matchMedia?.("(prefers-reduced-motion: reduce)")?.matches;
      return new Promise((resolve)=>{
        if (reduced){
          bubble.innerHTML = finalHTML; maybeAddQuickActions(bubble); return resolve();
        }
        const start = performance.now();
        const waitDots = () => {
          if (performance.now() - start < minDotsMs) return requestAnimationFrame(waitDots);
          const tmp = document.createElement("div"); tmp.innerHTML = finalHTML;
          const plain = tmp.textContent || tmp.innerText || "";
          bubble.textContent = "";
          let i = 0;
          const tick = () => {
            bubble.textContent = plain.slice(0, i+1);
            i++; scrollBottom();
            if (i < plain.length) setTimeout(tick, speed);
            else { bubble.innerHTML = finalHTML; maybeAddQuickActions(bubble); scrollBottom(); resolve(); }
          };
          tick();
        };
        requestAnimationFrame(waitDots);
      });
    }

    async function appendBotBubble(payload, time=""){
      return new Promise(async (resolve)=>{
        messages.insertAdjacentHTML("beforeend", `
          <div class="w-full min-w-0">
            <div class="msg-row bot flex items-end justify-start gap-2">
              <div class="avatar shrink-0 w-8 h-8 rounded-full grid place-items-center">🤖</div>
              <div class="bubble bubble-ai px-4 py-2 rounded-2xl text-base text-left max-w-[85%]"></div>
            </div>
            <div class="msg-time text-[10px] opacity-70 mt-1">${time}</div>
          </div>`);
        const bubble = messages.lastElementChild.querySelector(".bubble-ai");

        // typing dots style
        bubble.classList.add("is-typing");
        bubble.innerHTML = `
          <span class="inline-flex items-center gap-1 text-gray-500">
            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
          </span>
          <span class="sr-only">Assistant is typing…</span>`;
        scrollBottom();

        await new Promise(r => setTimeout(r, 320 + Math.floor(Math.random()*420)));

        const obj = (payload && typeof payload === "object") ? payload : { text: payload };

        const textRaw = obj.text ?? obj.bot_reply ?? obj.message ?? "";
        const html = linkify(sanitizeHTML(textRaw));
        await typewriter(bubble, html, 24, 650);

        bubble.classList.remove("is-typing");

        // Render Rasa buttons (including `url`) if present; convert referral payloads to APPT_URL
        if (Array.isArray(obj.buttons) && obj.buttons.length) {
          renderButtons(obj.buttons, {
            container: bubble,
            sendPayload: (payloadStr) => {
              const inputEl = document.querySelector("#chat-message");
              if (inputEl) inputEl.value = payloadStr;
              document.querySelector("#chat-form")?.dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));
            }
          });
        }

        // Support custom messages like { custom: { open_url: ... } }
        if (obj?.custom?.open_url) {
          window.open(obj.custom.open_url, "_blank");
        }

        scrollBottom();
        resolve();
      });
    }

    // Strict queue so replies never overlap
    let Q = Promise.resolve();
    const runQ = (task) => (Q = Q.then(task).catch(e => console.warn("[LumiCHAT] queue error", e)));

    function sendQuick(text){
      appendUserBubble(text, new Date().toLocaleTimeString());
      send(text);
    }

    async function send(message){
      try{
        sendBtn && (sendBtn.disabled = true);
        const res = await axios.post(STORE_URL, { message });
        let replies = res.data?.bot_reply;
        if (!Array.isArray(replies)) replies = [replies];

        for (const r of (replies || [])){
          await runQ(() => appendBotBubble(r, res.data?.time_human || ""));
          await runQ(() => new Promise(done => setTimeout(done, 240)));
        }
      } catch(err){
        console.error("[LUMI_CHAT] Error:", err?.response || err?.message);
        await runQ(() => appendBotBubble("Sorry, I’m having trouble right now.", ""));
      } finally {
        sendBtn && (sendBtn.disabled = false);
        input && input.focus();
      }
    }

    // Prevent duplicate binding if script is accidentally included twice
    if (form?.dataset.bound) return;
    if (form){
      form.dataset.bound = "1";
      form.onsubmit = null;
      form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const raw = input?.value ?? "";
        const msg = String(raw).trim();
        if (!msg) return;
        input.value = "";
        appendUserBubble(msg, new Date().toLocaleTimeString());
        await send(msg);
      });
    }

    // Auto-welcome for brand-new chats with 60min cooldown
    try {
      const hasMessages = !!messages?.querySelector(".msg-row");
      const wrap = document.getElementById("chat-wrapper");
      const threadId = wrap?.dataset?.threadId || location.pathname;
      const KEY = `lumi_welcome_${threadId}`;
      const now = Date.now();

      let last = 0;
      try { last = JSON.parse(sessionStorage.getItem(KEY))?.ts || 0; } catch {}
      const elapsedMin = (now - last) / 60000;

      if (!hasMessages && (!last || elapsedMin >= 60)) {
        sessionStorage.setItem(KEY, JSON.stringify({ ts: now }));
        runQ(() => appendBotBubble("Hi! I’m Lumi — how can I help you today?", ""));
      }
    } catch (e) {
      console.warn("[LumiCHAT] welcome skipped:", e);
    }
  });
}
