<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8" />
  <title>LumiCHAT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

 @php
  // Decide if current user should get student-scoped global prefs
  $isStudent = Auth::check() && (strtolower((string)(Auth::user()->role ?? 'student')) === 'student');
@endphp

@if($isStudent)
  <!-- Student global prefs boot (runs before CSS to avoid flicker) -->
  <script>
    (() => {
      try {
        const root = document.documentElement;

        // Mark this HTML as "student app" so CSS is scoped
        root.setAttribute('data-app', 'student');

        const get = k => localStorage.getItem(k);

        // Dark mode
        const dark = get('lumichat_dark');
        const wantsDark = dark === '1' || (!dark && window.matchMedia('(prefers-color-scheme: dark)').matches);
        root.classList.toggle('dark', !!wantsDark);

        // Reduce Motion
        root.classList.toggle('reduce-motion', get('lumichat_reduce_motion') === '1');

        // Text Size
        const fs = get('lumichat_font_size') || 'md';
        root.classList.add('font-' + (['sm','md','lg'].includes(fs) ? fs : 'md'));

        // Compact
        root.classList.toggle('compact', get('lumichat_compact') === '1');
      } catch (e) {}
    })();
  </script>
@else
  <!-- Non-students keep simple dark boot -->
  <script>
    try {
      const pref = localStorage.getItem('lumichat_dark');
      const wantsDark = pref === '1' || (!pref && window.matchMedia('(prefers-color-scheme: dark)').matches);
      if (wantsDark) document.documentElement.classList.add('dark');
    } catch (e) {}
  </script>
@endif


  {{-- Fonts & Tailwind/Vite --}}
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
  <div class="layout-wrapper">
    {{-- ============================= SIDEBAR ============================= --}}
    <aside id="sidebar" class="sidebar-shell">
      {{-- Sidebar header (exactly h-16 to match main header) --}}
      <div class="flex items-center justify-between h-16 px-4 border-b border-r border-white/10 relative">
        <div class="flex items-center gap-2">
          <img src="{{ asset('images/chatbot.png') }}" alt="Logo" class="w-7 h-7">
          <span class="text-lg font-semibold bg-gradient-to-r from-indigo-100 to-violet-200 bg-clip-text text-transparent">
            LumiCHAT
          </span>
        </div>

        {{-- Close (✕) ONLY inside sidebar header --}}
        <button id="sidebar-close" class="sidebar-x" title="Close sidebar" aria-label="Close sidebar">✕</button>
      </div>

      {{-- Sidebar nav (icons from /public/images/icons) --}}
      @php
        $mainLinks = [
          ['label' => 'Home',             'route' => 'chat.index',                               'icon' => 'home.png'],
          ['label' => 'Profile',          'route' => 'profile.edit',                             'icon' => 'user.png'],
          ['label' => 'Appointment',      'route' => 'appointment.index',                        'icon' => 'appointment.png'],
          ['label' => 'Chat History',     'route' => Route::has('chat.history') ? 'chat.history' : null,   'icon' => 'chat-history.png'],
          ['label' => 'Settings',         'route' => Route::has('settings.index') ? 'settings.index' : null, 'icon' => 'settings.png'],
        ];
      @endphp

      <nav class="flex-1 px-3 pt-5 space-y-5 overflow-y-auto">
        <div>
          <p class="section-label">MAIN</p>
          <ul class="space-y-2">
            @foreach ($mainLinks as $item)

              {{-- Intercept the static "Appointment" entry and render the smart version instead --}}
              @if ($item['label'] === 'Appointment')
                {{-- ===== Appointment (unified single page) ===== --}}
                @php
                  $showAppointment = $appointmentEnabled ?? false;

                  if ($showAppointment) {
                      // keep label dynamic if you like
                      $apptLabel = ($hasAppointments ?? false) ? 'Appointment History' : 'Appointment';
                      $apptRoute = route('appointment.index'); // ← always the unified page
                  }
                @endphp
                @if ($showAppointment)
                    <li>
                      <a href="{{ $apptRoute }}"
                        @class([
                          'nav-item',
                          'nav-item--active' => request()->routeIs('appointment.index'),
                        ])>
                        <img src="{{ asset('images/icons/appointment.png') }}" alt="" class="sidebar-icon icon-white">
                        <span>{{ $apptLabel }}</span>
                      </a>
                    </li>
                  @endif

                  @continue  {{-- prevent the default rendering for this item --}}
                @endif

              {{-- Default rendering for all other items --}}
              @php
                $href = $item['route'] && is_string($item['route']) ? route($item['route']) : '#';
                $isActive = $item['route'] && is_string($item['route']) ? request()->routeIs($item['route']) : false;
              @endphp
              <li>
                <a href="{{ $href }}"
                  @class([
                    'nav-item',
                    'nav-item--active' => $isActive,
                    'opacity-100' => $item['route'] && is_string($item['route']),
                    'opacity-70 cursor-not-allowed' => !$item['route'] || !is_string($item['route']),
                  ])>
                  <img src="{{ asset('images/icons/' . $item['icon']) }}" alt="" class="sidebar-icon icon-white">
                  <span>{{ $item['label'] }}</span>
                </a>
              </li>

            @endforeach
          </ul>
        </div>

        <div>
          <p class="section-label">TOOLS</p>
          <a href="{{ route('chat.new') }}" class="nav-pill">
            <img src="{{ asset('images/icons/new-chat.png') }}" alt="" class="sidebar-icon icon-white">
            <span class="font-medium">New Chat</span>
          </a>
        </div>
      </nav>

      {{-- Logout --}}
      <div class="px-3 py-4 border-t border-white/10 mt-auto">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="nav-pill nav-pill--danger w-full">
            <img src="{{ asset('images/icons/logout.png') }}" alt="" class="sidebar-icon logout-icon">
            <span class="font-medium">Logout</span>
          </button>
        </form>
      </div>
    </aside>

    {{-- ============================ MAIN CONTENT ============================ --}}
    @php
      use Illuminate\Support\Str;

      $yieldTitle = trim($__env->yieldContent('title'));
      $routeName  = Route::currentRouteName();
      $autoTitle  = '';
      if (!$yieldTitle && $routeName) {
        $autoTitle = Str::of($routeName)->replace(['.', '_'], ' ')->title();
        $autoTitle = Str::of($autoTitle)->replace(['Index', 'Show'], '')->trim();
      }
      $pageTitle = $yieldTitle ?: ($autoTitle ?: 'LumiCHAT');

      $initials = '';
      if (Auth::check()) {
        $parts = preg_split('/\s+/', trim(Auth::user()->name ?? ''));
        $initials = strtoupper(collect($parts)->take(2)->map(fn($s)=>mb_substr($s,0,1))->implode(''));
      }
    @endphp

    <div class="main-content">
      {{-- Header --}}
      <header class="header-shell">
        <div class="header-inner flex items-center justify-between">
          {{-- Left: Hamburger (when hidden) + Page title --}}
          <div class="flex items-center gap-3">
            <button id="sidebar-open" class="hamburger-btn header-only" aria-label="Open sidebar">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/>
              </svg>
            </button>

            <h1 class="text-lg sm:text-xl font-semibold tracking-tight text-gray-900 dark:text-white">
              {{ $pageTitle }}
            </h1>
            @if(request()->routeIs('chat.index'))
              <span class="hidden sm:inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
                Live
              </span>
            @endif
          </div>

          {{-- Right: actions --}}
          <div class="flex items-center gap-2 sm:gap-3">
            {{-- NEW: Header "+ New Chat" only when sidebar is HIDDEN --}}
            <a href="{{ route('chat.new') }}"
               class="header-newchat inline-flex items-center gap-2 h-10 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-indigo-500/60"
               aria-label="Start a new chat">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              <span class="hidden sm:inline text-sm font-medium">New Chat</span>
            </a>

            <button id="theme-toggle" type="button" aria-label="Toggle theme"
                    class="inline-flex items-center justify-center h-10 w-10 rounded-xl border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/70 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
              <svg class="inline dark:hidden w-5 h-5 text-gray-600" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
              <svg class="hidden dark:inline w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M6.76 4.84l-1.8-1.79L3.18 4.84l1.79 1.79 1.79-1.79zM1 13h3v-2H1v2zm10 10h2v-3h-2v3zm9-10v-2h-3v2h3zm-3.76 6.16l1.79 1.79 1.78-1.79-1.78-1.79-1.79 1.79zM12 7a5 5 0 100 10 5 5 0 000-10zm6.24-2.16l1.79-1.79-1.79-1.79-1.79 1.79 1.79 1.79zM4.24 17.16L2.45 18.95l1.79 1.79 1.79-1.79-1.79-1.79z"/></svg>
            </button>

            {{-- User menu --}}
            <div class="relative">
              <button id="user-btn" type="button"
                      class="inline-flex items-center gap-2 h-10 px-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/70 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 text-white text-xs font-bold">
                  {{ $initials ?: 'U' }}
                </div>
                <div class="hidden sm:flex flex-col text-left leading-tight mr-1">
                  <span class="text-[13px] font-semibold text-gray-800 dark:text-gray-100 truncate max-w-[8rem]">
                    @auth {{ Auth::user()->name }} @endauth
                  </span>
                  <span class="text-[11px] text-gray-500 dark:text-gray-400">Student</span>
                </div>
                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" viewBox="0 0 24 24" fill="currentColor">
                  <circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>
                </svg>
              </button>

              <div id="user-menu" class="dropdown">
                <a href="{{ route('profile.edit') }}" class="dropdown-item">Profile</a>
                @if(Route::has('settings.index'))
                  <a href="{{ route('settings.index') }}" class="dropdown-item">Settings</a>
                @endif
                <div class="dropdown-sep"></div>
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="dropdown-item text-rose-600">Logout</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </header>

      {{-- Page panel --}}
      <div class="panel-scroll">
        @yield('content')
      </div>
    </div>
  </div>

  {{-- ============================ Minimal JS ============================ --}}
  <script>
    // Sidebar toggle + persist in localStorage('sidebarHidden')
    (function(){
      const body     = document.body;
      const openBtn  = document.getElementById('sidebar-open');
      const closeBtn = document.getElementById('sidebar-close');
      const sidebar  = document.getElementById('sidebar');

      const hidden = localStorage.getItem('sidebarHidden') === 'true';
      body.classList.toggle('sidebar-hidden', hidden);

      const toggle = () => {
        body.classList.toggle('sidebar-hidden');
        localStorage.setItem('sidebarHidden', body.classList.contains('sidebar-hidden'));
      };

      openBtn?.addEventListener('click', toggle);
      closeBtn?.addEventListener('click', toggle);

      // Close on outside click (mobile)
      document.addEventListener('click', (e) => {
        if (window.innerWidth >= 1024) return;
        if (!sidebar.contains(e.target) && !openBtn.contains(e.target)) {
          if (!body.classList.contains('sidebar-hidden')) toggle();
        }
      });
    })();

    // Theme toggle
    (function(){
      const btn = document.getElementById('theme-toggle');
      btn?.addEventListener('click', () => {
        const html = document.documentElement;
        const isDark = html.classList.toggle('dark');
        localStorage.setItem('lumichat_dark', isDark ? '1' : '0');
      });
    })();

    // User dropdown
    (function(){
      const btn = document.getElementById('user-btn');
      const menu = document.getElementById('user-menu');
      const close = () => menu?.classList.add('hidden');
      const toggle = () => menu?.classList.toggle('hidden');

      btn?.addEventListener('click', (e) => { e.stopPropagation(); toggle(); });
      document.addEventListener('click', (e) => {
        if (!menu?.contains(e.target) && !btn?.contains(e.target)) close();
      });
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
    })();
  </script>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  @include('profile.partials.alerts') 

  @isset($isStudent)
  @if($isStudent)
    <script>
      // Keep other open student tabs in sync when settings change
      window.addEventListener('storage', (e) => {
        const root = document.documentElement;
        switch (e.key) {
          case 'lumichat_dark': {
            const wantsDark = e.newValue === '1' || (!e.newValue && window.matchMedia('(prefers-color-scheme: dark)').matches);
            root.classList.toggle('dark', !!wantsDark);
            break;
          }
          case 'lumichat_reduce_motion':
            root.classList.toggle('reduce-motion', e.newValue === '1');
            break;
          case 'lumichat_font_size':
            ['font-sm','font-md','font-lg'].forEach(c => root.classList.remove(c));
            root.classList.add('font-' + (['sm','md','lg'].includes(e.newValue) ? e.newValue : 'md'));
            break;
          case 'lumichat_compact':
            root.classList.toggle('compact', e.newValue === '1');
            break;
        }
      });
    </script>
  @endif
@endisset
@stack('scripts')
</body>
</html>
