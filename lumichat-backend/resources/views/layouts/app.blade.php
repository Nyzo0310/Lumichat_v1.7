<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8" />
  <title>LumiCHAT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Dark-mode boot: add .dark on <html> before CSS loads -->
  <script>
    try {
      const pref = localStorage.getItem('lumichat_dark');
      const wantsDark = pref === '1' || (!pref && window.matchMedia('(prefers-color-scheme: dark)').matches);
      if (wantsDark) document.documentElement.classList.add('dark');
    } catch(e) {}
  </script>

  {{-- Fonts & Tailwind/Vite --}}
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  {{-- Animate.css (optional) --}}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>

<body class="bg-gray-50 dark:bg-gray-900">

  {{-- Global hamburger (shows when sidebar is hidden) --}}
  <button id="sidebar-open" class="sidebar-open-btn" aria-label="Open sidebar">
    <img src="{{ asset('images/icons/hamburger.png') }}" alt="Menu" class="hamburger-icon">
  </button>

  <div class="layout-wrapper">
    {{-- ============================= SIDEBAR ============================= --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 w-64 text-white shadow-xl flex flex-col rounded-br-lg transition-transform duration-300">
      {{-- Sidebar Header --}}
      <div class="flex items-center justify-between px-4 py-5 border-b border-white/10 relative">
        <div class="flex items-center gap-2">
          <img src="{{ asset('images/chatbot.png') }}" alt="Logo" class="w-7 h-7">
          <span class="text-lg font-semibold bg-gradient-to-r from-blue-300 to-indigo-200 bg-clip-text text-transparent">
            LumiCHAT
          </span>
        </div>
        <button id="sidebar-close" class="sidebar-close-btn" title="Close sidebar">✕</button>
      </div>

      {{-- ====== NAVIGATION ====== --}}
      @php
      function icon_path($filename) {
          $path = public_path('images/icons/'.$filename);
          return file_exists($path) ? asset('images/icons/'.$filename) : asset('images/chatbot.png');
      }

      $mainLinks = [
        'Home'         => ['chat.index',    'home.png'],
        'Profile'      => ['profile.edit',  'user.png'],

        // ✅ Conditionally insert Appointment right here
        ...(Auth::check() && (Auth::user()->appointments_enabled ?? false)
            ? ['Appointment' => ['appointment.index', 'appointment.png']]
            : []),

        'Chat History' => [Route::has('chat.history') ? 'chat.history' : null, 'chat-history.png'],
        'Settings'     => [Route::has('settings.index') ? 'settings.index' : null, 'settings.png'],
      ];
      @endphp

      <nav class="flex-1 px-3 pt-5 space-y-4 overflow-y-auto">
        {{-- MAIN --}}
        <div>
          <p class="section-label">MAIN</p>
          <ul class="space-y-2">
            @foreach($mainLinks as $label => [$routeName, $icon])
              @php
                $href     = $routeName && is_string($routeName) ? route($routeName) : '#';
                $isActive = $routeName && is_string($routeName) ? request()->routeIs($routeName) : false;
              @endphp
              <li>
                <a href="{{ $href }}"
                   @class([
                     'flex items-center gap-3 px-4 py-3 rounded-xl transition border',
                     'bg-white/20 border-white/20' => $isActive,
                     'border-transparent hover:bg-white/10' => !$isActive,
                     'opacity-100' => $routeName && is_string($routeName),
                     'opacity-70 cursor-not-allowed' => !$routeName || !is_string($routeName),
                   ])>
                  <img src="{{ icon_path($icon) }}" alt="" class="sidebar-icon icon-white">
                  <span>{{ $label }}</span>
                </a>
              </li>
            @endforeach
          </ul>
        </div>

        {{-- TOOLS --}}
        <div>
          <p class="section-label">TOOLS</p>
          <a href="{{ route('chat.new') }}"
             class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/20 hover:bg-white/25 shadow-sm transition">
            <img src="{{ icon_path('new-chat.png') }}" alt="" class="sidebar-icon icon-white">
            <span class="font-medium">New Chat</span>
          </a>
        </div>
      </nav>

      {{-- Logout --}}
      <div class="px-3 py-4 border-t border-white/10 mt-auto">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit"
                  class="w-full flex items-center gap-3 px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20 transition text-red-200 hover:text-red-100">
            <img src="{{ icon_path('logout.png') }}" alt="Logout" class="sidebar-icon logout-icon">
            <span class="font-medium">Logout</span>
          </button>
        </form>
      </div>
    </aside>

    {{-- ============================ MAIN CONTENT ============================ --}}
    <div class="main-content md:ml-64 ml-0 transition-all duration-300">
      {{-- Header --}}
      <div class="header-bar bg-white dark:bg-gray-800 shadow-sm flex justify-between items-center px-6 py-4 border-b border-transparent dark:border-gray-700">
        @php
          use Illuminate\Support\Str;
          $yieldTitle = trim($__env->yieldContent('title'));
          $autoTitle  = '';
          if (!$yieldTitle) {
            $r = Route::currentRouteName();
            if ($r) {
              // e.g. "settings.index" -> "Settings"
              $autoTitle = Str::of($r)->replace(['.', '_'], ' ')->title();
              $autoTitle = Str::of($autoTitle)->replace(['Index', 'Show'], '')->trim();
            }
          }
        @endphp

        {{-- Page title (now always visible in dark mode) --}}
        <h1 class="page-title text-xl font-medium text-gray-900 dark:text-white">
          {{ $yieldTitle ?: ($autoTitle ?: 'LumiCHAT') }}
        </h1>

        {{-- Hide username on Settings page only --}}
        @if (!request()->routeIs('settings.index'))
          <div class="text-gray-600 dark:text-gray-300">
            @auth
              {{ Auth::user()->name }}
            @endauth
          </div>
        @endif
      </div>

      {{-- Panel --}}
      <div class="main-panel h-[calc(100vh-64px)] overflow-auto">
        @yield('content')
      </div>
    </div>
  </div>

  {{-- ============================ SIDEBAR TOGGLE JS ============================ --}}
  <script>
    (function setupSidebarToggle(){
      const body     = document.body;
      const openBtn  = document.getElementById('sidebar-open');
      const closeBtn = document.getElementById('sidebar-close');
      const sidebar  = document.getElementById('sidebar');

      // Restore last state
      const hidden = localStorage.getItem('sidebarHidden') === 'true';
      body.classList.toggle('sidebar-hidden', hidden);

      const toggle = () => {
        body.classList.toggle('sidebar-hidden');
        localStorage.setItem('sidebarHidden', body.classList.contains('sidebar-hidden'));
      };

      openBtn?.addEventListener('click', toggle);
      closeBtn?.addEventListener('click', toggle);

      // Optional: click outside to close (mobile only)
      document.addEventListener('click', (e) => {
        if (window.innerWidth >= 1024) return;
        if (!sidebar.contains(e.target) && !openBtn.contains(e.target)) {
          if (!body.classList.contains('sidebar-hidden')) toggle();
        }
      });
    })();
  </script>

  <script src="https://unpkg.com/htmx.org@1.9.2"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- Success toast after profile update --}}
  @if (session('profile_updated'))
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      Swal.fire({
        toast: true,
        icon: 'success',
        title: 'Profile updated',
        position: 'top-end',
        showConfirmButton: false,
        timer: 1800,
        timerProgressBar: true,
      });
    });
  </script>
  @endif

  {{-- Simple toggle for "Edit profile" --}}
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const btn    = document.querySelector('[data-edit-profile-btn]');
      const form   = document.querySelector('[data-edit-profile-form]');
      const cancel = document.querySelector('[data-edit-cancel]');
      if (btn && form) {
        btn.addEventListener('click', () => {
          form.classList.toggle('hidden');
          if (!form.classList.contains('hidden')) {
            const name = form.querySelector('#edit-name');
            name && name.focus();
          }
        });
      }
      cancel && cancel.addEventListener('click', () => form && form.classList.add('hidden'));
    });
  </script>

  @stack('scripts')
</body>
</html>
