{{-- resources/views/layouts/admin.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Dashboard') • LumiCHAT</title>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  @vite(['resources/css/app.css','resources/js/app.js'])

  <style>
    :root{
      --rail-expanded: 18rem;   /* 288px */
      --rail-collapsed: 84px;   /* compact width */
      --header-h: 56px;
    }

    /* Base */
    html, body { height: 100%; }
    body{
      -webkit-tap-highlight-color: transparent;
      overflow-x: hidden;                 /* never show horizontal scroll */
    }
    .no-scroll{ overflow: hidden; }

    /* Layout transitions */
    #adminSidebar{ width: var(--rail-expanded); transition: width .25s ease, transform .25s ease; }
    #adminMain{ transition: padding .25s ease; }
    @media (min-width: 1024px){
      #adminMain{ padding-left: var(--rail-expanded); }
      .admin-collapsed #adminMain{ padding-left: var(--rail-collapsed); }
    }
    .admin-collapsed #adminSidebar{ width: var(--rail-collapsed); }

    /* Rail header height parity */
    .rail-header{ height: var(--header-h); }

    /* Collapse behavior (desktop) */
    @media (min-width:1024px){
      .admin-collapsed .brand-text,
      .admin-collapsed .nav-label,
      .admin-collapsed .hide-when-collapsed{ display: none !important; }
      .admin-collapsed #railClose{ display: none !important; }  /* hide the X when collapsed */
      .admin-collapsed .nav-item{ justify-content: center; }
    }

    /* Hamburger visibility */
    #railOpen{ display:inline-flex; }
    @media (min-width:1024px){
      body:not(.admin-collapsed) #railOpen{ display:none; }   /* desktop + expanded rail => hide */
      body.admin-collapsed #railOpen{ display:inline-flex; }  /* desktop + collapsed rail => show */
    }
    body.mobile-rail-open #railOpen{ display:none; }          /* mobile overlay open => hide */

    /* Active marker on the left of item */
    .nav-item.is-active::before{
      content:""; position:absolute; left:10px; top:50%;
      transform:translateY(-50%); width:4px; height:22px; border-radius:999px;
      background:rgba(255,255,255,.92);
    }

    /* ---- Make PNG icons white (force) ---- */
    #adminSidebar nav a.nav-item > span > img{
      -webkit-filter: invert(1) brightness(1000%) saturate(0) contrast(100%) !important;
              filter: invert(1) brightness(1000%) saturate(0) contrast(100%) !important;
    }

    /* ---- Sidebar inner scroll ----
       visible when open, hidden when collapsed (desktop) */
    #railScroll{
      height: calc(100vh - var(--header-h));
      overflow-y: auto;
      overflow-x: hidden;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: thin;                               /* Firefox */
      scrollbar-color: rgba(255,255,255,.7) transparent;
    }
    @supports (height: 100dvh){
      #railScroll{ height: calc(100dvh - var(--header-h)); }
    }
    #railScroll::-webkit-scrollbar{ width: 10px; }         /* WebKit */
    #railScroll::-webkit-scrollbar-thumb{
      background: rgba(255,255,255,.65);
      border-radius: 9999px;
      border: 2px solid rgba(255,255,255,.25);
      background-clip: padding-box;
    }
    #railScroll::-webkit-scrollbar-track{ background: transparent; }
    @media (min-width:1024px){
      .admin-collapsed #railScroll{ overflow: hidden; }     /* hide inner scroll when collapsed */
    }

    /* prevent horizontal bar inside rail */
    #adminSidebar{ overflow-x: clip; }

    /* Tooltips when collapsed (desktop) */
    .nav-item .rail-tip{
      position:absolute; inset:auto auto 50% 100%;
      transform: translateY(50%) translateX(8px);
      padding:.35rem .6rem; font-size:.75rem; white-space:nowrap;
      background:#0f172a; color:#fff; border-radius:.5rem;
      box-shadow:0 10px 24px rgba(15,23,42,.35);
      opacity:0; pointer-events:none;
      transition:opacity .12s ease, transform .12s ease;
    }
    @media (min-width:1024px){
      .admin-collapsed .nav-item:hover .rail-tip{
        opacity:1; transform: translateY(50%) translateX(12px);
      }
    }
  </style>
</head>

<body class="bg-slate-50 text-slate-800 antialiased">

  {{-- ===== SIDEBAR / RAIL ===== --}}
 {{-- ===== SIDEBAR / RAIL ===== --}}
<aside id="adminSidebar"
  class="fixed inset-y-0 left-0 z-40 -translate-x-full lg:translate-x-0 text-white shadow-xl">

  {{-- Brand --}}
  <div class="rail-header px-4 flex items-center justify-between border-b border-white/20">
    <div class="flex items-center gap-2">
      <img src="{{ asset('images/chatbot.png') }}"
           class="w-9 h-9 rounded-full ring-2 ring-white/30 object-cover"
           alt="LumiCHAT">
      <span class="brand-text font-semibold tracking-wide">LumiCHAT</span>
    </div>

    {{-- X: collapse on desktop / close on mobile --}}
    <button id="railClose"
            class="p-2 rounded-md hover:bg-white/10 focus:outline-none"
            aria-label="Collapse/Close sidebar"
            title="Collapse (desktop) / Close (mobile)">
      <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>

  {{-- Nav --}}
  <nav class="h-[calc(100vh-var(--header-h))] flex flex-col">
    <div id="railScroll" class="px-3 py-3 grow">

      <p class="px-3 text-[11px] uppercase tracking-wider/relaxed opacity-90 nav-label">Main</p>

      {{-- Dashboard --}}
      <a href="{{ route('admin.dashboard') }}"
         aria-current="{{ request()->routeIs('admin.dashboard') ? 'page' : 'false' }}"
         class="nav-item group relative mt-2 px-3 py-2.5 ring-1 ring-transparent
                hover:bg-white/10 hover:ring-white/10
                {{ request()->routeIs('admin.dashboard') ? 'is-active bg-white/15 ring-white/10' : '' }}">
        <span class="inline-flex w-10 h-10 items-center justify-center">
          <img src="{{ asset('images/icons/home.png') }}" class="sidebar-icon" alt="">
        </span>
        <span class="nav-label font-medium">Dashboard Overview</span>
        <span class="rail-tip">Dashboard Overview</span>
      </a>

      {{-- Counselor --}}
      <a href="{{ route('admin.counselors.index') }}"
         aria-current="{{ request()->routeIs('admin.counselors.*') ? 'page' : 'false' }}"
         class="nav-item group relative mt-1.5 px-3 py-2.5 ring-1 ring-transparent
                hover:bg-white/10 hover:ring-white/10
                {{ request()->routeIs('admin.counselors.*') ? 'is-active bg-white/15 ring-white/10' : '' }}">
        <span class="inline-flex w-10 h-10 items-center justify-center">
          <img src="{{ asset('images/icons/counselor.png') }}" class="sidebar-icon" alt="">
        </span>
        <span class="nav-label font-medium">Counselor</span>
        <span class="rail-tip">Counselor</span>
      </a>

      <p class="mt-4 px-3 text-[11px] uppercase tracking-wider/relaxed opacity-90 nav-label">Student Management</p>

      {{-- Student Records --}}
      <a href="{{ route('admin.students.index') }}"
         aria-current="{{ request()->routeIs('admin.students.*') ? 'page' : 'false' }}"
         class="nav-item group relative mt-1.5 px-3 py-2.5 ring-1 ring-transparent
                hover:bg-white/10 hover:ring-white/10
                {{ request()->routeIs('admin.students.*') ? 'is-active bg-white/15 ring-white/10' : '' }}">
        <span class="inline-flex w-10 h-10 items-center justify-center">
          <img src="{{ asset('images/icons/user.png') }}" class="sidebar-icon" alt="">
        </span>
        <span class="nav-label font-medium">Student Records</span>
        <span class="rail-tip">Student Records</span>
      </a>

      {{-- Appointments --}}
      <a href="{{ route('admin.appointments.index') }}"
         aria-current="{{ request()->routeIs('admin.appointments.*') ? 'page' : 'false' }}"
         class="nav-item group relative mt-1.5 px-3 py-2.5 ring-1 ring-transparent
                hover:bg-white/10 hover:ring-white/10
                {{ request()->routeIs('admin.appointments.*') ? 'is-active bg-white/15 ring-white/10' : '' }}">
        <span class="inline-flex w-10 h-10 items-center justify-center">
          <img src="{{ asset('images/icons/appointment.png') }}" class="sidebar-icon" alt="">
        </span>
        <span class="nav-label font-medium">Appointments</span>
        <span class="rail-tip">Appointments</span>
      </a>

      <p class="mt-4 px-3 text-[11px] uppercase tracking-wider/relaxed opacity-90 nav-label">Reports</p>

      {{-- Counselor Logs --}}
      <a href="{{ route('admin.counselor-logs.index') }}"
         aria-current="{{ request()->routeIs('admin.counselor-logs.*') ? 'page' : 'false' }}"
         class="nav-item group relative mt-1.5 px-3 py-2.5 ring-1 ring-transparent
                hover:bg-white/10 hover:ring-white/10
                {{ request()->routeIs('admin.counselor-logs.*') ? 'is-active bg-white/15 ring-white/10' : '' }}">
        <span class="inline-flex w-10 h-10 items-center justify-center">
          <img src="{{ asset('images/icons/logs.png') }}" class="sidebar-icon" alt="">
        </span>
        <span class="nav-label font-medium">Counselor Logs</span>
        <span class="rail-tip">Counselor Logs</span>
      </a>

      {{-- Chatbot Sessions --}}
      <a href="{{ route('admin.chatbot-sessions.index') }}"
         aria-current="{{ request()->routeIs('admin.chatbot-sessions.*') ? 'page' : 'false' }}"
         class="nav-item group relative mt-1.5 px-3 py-2.5 ring-1 ring-transparent
                hover:bg-white/10 hover:ring-white/10
                {{ request()->routeIs('admin.chatbot-sessions.*') ? 'is-active bg-white/15 ring-white/10' : '' }}">
        <span class="inline-flex w-10 h-10 items-center justify-center">
          <img src="{{ asset('images/icons/chatbot-session.png') }}" class="sidebar-icon" alt="">
        </span>
        <span class="nav-label font-medium">Chatbot Sessions</span>
        <span class="rail-tip">Chatbot Sessions</span>
      </a>

      {{-- Diagnosis Reports --}}
      <a href="{{ route('admin.diagnosis-reports.index') }}"
         aria-current="{{ request()->routeIs('admin.diagnosis-reports.*') ? 'page' : 'false' }}"
         class="nav-item group relative mt-1.5 px-3 py-2.5 ring-1 ring-transparent
                hover:bg-white/10 hover:ring-white/10
                {{ request()->routeIs('admin.diagnosis-reports.*') ? 'is-active bg-white/15 ring-white/10' : '' }}">
        <span class="inline-flex w-10 h-10 items-center justify-center">
          <img src="{{ asset('images/icons/diagnosis.png') }}" class="sidebar-icon" alt="">
        </span>
        <span class="nav-label font-medium">Diagnosis Reports</span>
        <span class="rail-tip">Diagnosis Reports</span>
      </a>

      <p class="mt-4 px-3 text-[11px] uppercase tracking-wider/relaxed opacity-90 nav-label">Analytics</p>

      {{-- Course Analytics --}}
      <a href="{{ route('admin.course-analytics.index') }}"
         aria-current="{{ request()->routeIs('admin.course-analytics.*') ? 'page' : 'false' }}"
         class="nav-item group relative mt-1.5 px-3 py-2.5 ring-1 ring-transparent
                hover:bg-white/10 hover:ring-white/10
                {{ request()->routeIs('admin.course-analytics.*') ? 'is-active bg-white/15 ring-white/10' : '' }}">
        <span class="inline-flex w-10 h-10 items-center justify-center">
          <img src="{{ asset('images/icons/graduate.png') }}" class="sidebar-icon" alt="">
        </span>
        <span class="nav-label font-medium">Course Analytics</span>
        <span class="rail-tip">Course Analytics</span>
      </a>
    </div>

    {{-- Logout — visible only when rail is expanded --}}
    <div class="px-3 py-3 border-t border-white/15 hide-when-collapsed">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="w-full text-left px-3 py-2.5 rounded-lg bg-rose-600/90 hover:bg-rose-600 text-white font-medium">
          Logout
        </button>
      </form>
    </div>
  </nav>
</aside>


  {{-- Mobile scrim --}}
  <div id="sidebarScrim" class="fixed inset-0 z-30 bg-black/40 backdrop-blur-sm hidden lg:hidden"></div>

  {{-- ===== MAIN ===== --}}
  <div id="adminMain" class="min-h-screen">
    {{-- Top bar --}}
    <header class="sticky top-0 z-20 h-[var(--header-h)] bg-white/80 backdrop-blur border-b border-slate-200">
      <div class="h-full max-w-7xl mx-auto px-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          {{-- Hamburger: expand (desktop) / open (mobile) --}}
          <button id="railOpen" class="p-2 rounded-md hover:bg-slate-100"
                  aria-label="Open sidebar" title="Open sidebar">
            <svg class="w-6 h-6 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          <h1 class="text-lg font-semibold">@yield('title','Dashboard')</h1>
        </div>
        <div class="text-sm text-slate-600">{{ auth()->user()->name ?? 'Master Admin' }}</div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6">
      @yield('content')
    </main>
  </div>

  <script>
    (function () {
      const body      = document.body;
      const sidebar   = document.getElementById('adminSidebar');
      const scrim     = document.getElementById('sidebarScrim');
      const openBtn   = document.getElementById('railOpen');
      const closeBtn  = document.getElementById('railClose');
      const mqDesktop = window.matchMedia('(min-width: 1024px)');
      const LS_KEY    = 'adminSidebarCollapsed';
      const isDesktop = () => mqDesktop.matches;

      function setCollapsed(on) {
        if (on) {
          body.classList.add('admin-collapsed');
          localStorage.setItem(LS_KEY, '1');
        } else {
          body.classList.remove('admin-collapsed');
          localStorage.setItem(LS_KEY, '0');
        }
      }
      const getCollapsed = () => localStorage.getItem(LS_KEY) === '1';

      /* Mobile open/close */
      function openMobile(){
        sidebar.classList.remove('-translate-x-full');
        scrim.classList.remove('hidden');
        body.classList.add('no-scroll');
        body.classList.add('mobile-rail-open');     // mark mobile open (hides hamburger)
      }
      function closeMobile(){
        sidebar.classList.add('-translate-x-full');
        scrim.classList.add('hidden');
        body.classList.remove('no-scroll');
        body.classList.remove('mobile-rail-open');  // show hamburger again
      }

      /* Buttons */
      openBtn?.addEventListener('click', () => {
        if (isDesktop()) {
          if (getCollapsed()) setCollapsed(false);   // expand on desktop
        } else {
          openMobile();
        }
      });

      closeBtn?.addEventListener('click', () => {
        if (isDesktop()) {
          setCollapsed(true);                        // collapse on desktop (X hides via CSS)
        } else {
          closeMobile();                             // close overlay on mobile
        }
      });

      scrim?.addEventListener('click', closeMobile);
      window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !isDesktop()) closeMobile();
      });

      /* Init per viewport */
      function applyMode(){
        if (isDesktop()){
          body.classList.remove('mobile-rail-open');
          sidebar.classList.remove('-translate-x-full');
          scrim.classList.add('hidden');
          body.classList.remove('no-scroll');
          setCollapsed(getCollapsed());              // restore saved state
        } else {
          sidebar.classList.add('-translate-x-full'); // hidden by default on mobile
          body.classList.remove('admin-collapsed');   // mobile uses full rail (no collapsed UI)
          body.classList.remove('mobile-rail-open');
        }
      }
      mqDesktop.addEventListener('change', applyMode);
      applyMode();
    })();
  </script>
  <script>
/* ---------- ADMIN BOOKING FLOW (date → counselor → time) ---------- */
(() => {
  const btn = document.getElementById('btnAdminBook');
  if (!btn) return;

  @if (isset($session))

    const slotsEndpoint = @json(route('admin.chatbot-sessions.slots', $session->id));
    const bookEndpoint  = @json(route('admin.chatbot-sessions.book',  $session->id));
    // …rest of booking code…

@endif

  function buildTimePills(container, arr, selected='') {
    container.innerHTML = '';
    const emptyEl = document.getElementById('adm-empty');
    if (!arr || !arr.length) { emptyEl.classList.remove('hidden'); return; }
    emptyEl.classList.add('hidden');
    arr.forEach(s => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'time-pill inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:border-indigo-300 hover:bg-indigo-50';
      b.dataset.value = s.value;
      b.textContent   = s.label;
      if (s.value === selected) b.classList.add('ring-2','ring-indigo-500');
      b.addEventListener('click', () => {
        container.querySelectorAll('button').forEach(x=>x.classList.remove('ring-2','ring-indigo-500'));
        b.classList.add('ring-2','ring-indigo-500');
        container.dataset.selected = s.value;
      });
      container.appendChild(b);
    });
  }

  async function fetchSlots(date) {
    const url = new URL(slotsEndpoint, window.location.origin);
    url.searchParams.set('date', date);
    const res = await fetch(url, { headers:{'X-Requested-With':'XMLHttpRequest'} });
    if (!res.ok) throw new Error('Failed to load slots');
    return res.json(); // { counselors:[{id,name}], slots: { [id]: [{value,label}] } }
  }

  btn.addEventListener('click', async () => {
    try {
      const today = new Date(); const pad = n => String(n).padStart(2,'0');
      const defaultDate = `${today.getFullYear()}-${pad(today.getMonth()+1)}-${pad(today.getDate())}`;

      // initial fetch
      let slotsData = await fetchSlots(defaultDate);

      const { value: form } = await Swal.fire({
        title: 'Book appointment',
        html: `
          <div style="text-align:left">
            <label class="text-sm font-medium text-slate-700">1) Pick date *</label>
            <input id="adm-date" type="date" value="${defaultDate}" min="{{ now()->toDateString() }}"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"/>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-slate-700">2) Counselor *</label>
                <select id="adm-counselor" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                  ${(slotsData.counselors || []).map(c=>`<option value="${c.id}">${c.name}</option>`).join('')}
                </select>
              </div>
              <div>
                <label class="text-sm font-medium text-slate-700">3) Time *</label>
                <div id="adm-times" class="mt-1 grid grid-cols-2 sm:grid-cols-3 gap-2"></div>
                <div id="adm-empty" class="text-xs text-slate-500 mt-1 hidden">No available times.</div>
              </div>
            </div>
          </div>
        `,
        width: 720,
        showCancelButton: true,
        confirmButtonText: 'Confirm Booking',
        focusConfirm: false,
        didOpen: () => {
          const dateEl  = document.getElementById('adm-date');
          const counEl  = document.getElementById('adm-counselor');
          const timeEl  = document.getElementById('adm-times');

          // seed times for initially selected counselor
          buildTimePills(timeEl, (slotsData.slots || {})[counEl.value] || []);

          // on date change → refetch and rebuild both counselor list + time pills
          dateEl.addEventListener('change', async () => {
            slotsData = await fetchSlots(dateEl.value);
            // rebuild counselor list
            counEl.innerHTML = (slotsData.counselors || []).map(c=>`<option value="${c.id}">${c.name}</option>`).join('');
            buildTimePills(timeEl, (slotsData.slots || {})[counEl.value] || []);
          });

          // on counselor change → rebuild time pills from latest slotsData
          counEl.addEventListener('change', () => {
            buildTimePills(timeEl, (slotsData.slots || {})[counEl.value] || []);
          });
        },
        preConfirm: () => {
          const dateEl = document.getElementById('adm-date');
          const counEl = document.getElementById('adm-counselor');
          const timeEl = document.getElementById('adm-times');

          const date = dateEl ? dateEl.value : '';
          const counselorId = counEl ? counEl.value : '';
          const time = (timeEl && timeEl.dataset.selected) ? timeEl.dataset.selected : '';

          if (!date || !counselorId || !time) {
            Swal.showValidationMessage('Please select date, counselor, and time.');
            return false;
          }
          return { date, counselorId, time };
        }
      });

      if (!form) return;

      // POST
      const fd = new FormData();
      fd.append('_token', @json(csrf_token()));
      fd.append('date', form.date);
      fd.append('time', form.time);
      fd.append('counselor_id', form.counselorId);

      const resp = await fetch(bookEndpoint, { method:'POST', body: fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
      const data = await resp.json().catch(()=>({}));
      if (!resp.ok) throw new Error(data.message || 'Booking failed.');

      Swal.fire({ icon:'success', title:'Booked', html:data.html || 'Appointment created.', confirmButtonText:'OK' });
    } catch (e) {
      console.error(e);
      Swal.fire({ icon:'error', title:'Unable to book', text: e.message || 'Please try again.' });
    }
  });
})();
</script>

@stack('scripts')
</body>
</html>
