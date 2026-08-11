<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Admin Security Verification - Lumina Beauty')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/admin.css?v={{ filemtime(public_path('assets/css/admin.css')) }}" />
  </head>
  <body class="admin-login-body">
    <main class="admin-auth-page">
      <a class="admin-brand login-brand" href="{{ route('home') }}">
        <span>LB</span>
        <strong>Lumina Beauty</strong>
        <small>Admin access</small>
      </a>

      @yield('content')
    </main>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="/assets/js/auth.js"></script>
    <script>
      if (window.lucide) window.lucide.createIcons();
    </script>
  </body>
</html>
