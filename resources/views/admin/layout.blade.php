<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Lumina Beauty Admin')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/admin.css?v={{ filemtime(public_path('assets/css/admin.css')) }}" />
  </head>
  <body>
    <aside class="admin-sidebar" data-admin-sidebar aria-label="Admin navigation">
      <button class="admin-sidebar-toggle" type="button" data-admin-sidebar-toggle aria-expanded="false" aria-label="Toggle admin navigation">
        <i data-lucide="menu"></i>
      </button>
      <a class="admin-brand" href="{{ route('admin.dashboard') }}">
        <span>LB</span>
        <strong>Lumina Beauty</strong>
        <small>Commerce studio</small>
      </a>

      <nav>
        <p>Overview</p>
        <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
        <a class="{{ request()->routeIs('admin.reports') ? 'is-active' : '' }}" href="{{ route('admin.reports') }}"><i data-lucide="chart-column"></i> <span>Reports</span></a>

        <p>Management</p>
        <a class="{{ request()->routeIs('admin.products.*') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}"><i data-lucide="package"></i> <span>Products</span></a>
        <a class="{{ request()->routeIs('admin.orders.*') ? 'is-active' : '' }}" href="{{ route('admin.orders.index') }}"><i data-lucide="receipt-text"></i> <span>Orders</span></a>
        <a class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}"><i data-lucide="users"></i> <span>Customers</span></a>
        <a class="{{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}" href="{{ route('admin.categories.index') }}"><i data-lucide="tags"></i> <span>Categories</span></a>
        <a class="{{ request()->routeIs('admin.brands.*') ? 'is-active' : '' }}" href="{{ route('admin.brands.index') }}"><i data-lucide="badge"></i> <span>Brands</span></a>

        <p>Growth</p>
        <a class="{{ request()->routeIs('admin.discounts') ? 'is-active' : '' }}" href="{{ route('admin.discounts') }}"><i data-lucide="badge-percent"></i> <span>Discounts</span></a>
        <a class="{{ request()->routeIs('admin.settings') ? 'is-active' : '' }}" href="{{ route('admin.settings') }}"><i data-lucide="settings"></i> <span>Settings</span></a>
      </nav>

      <div class="sidebar-card">
        <span>Premium mode</span>
        <strong>Beauty analytics</strong>
        <a href="{{ route('home') }}"><i data-lucide="store"></i> <span>Storefront</span></a>
      </div>
    </aside>

    <main class="admin-main">
      <header class="admin-topbar">
        <div class="admin-title-block">
          <p class="eyebrow">@yield('eyebrow', 'Admin area')</p>
          <h1>@yield('heading', 'Dashboard')</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-icon-link" href="{{ route('admin.products.create') }}" aria-label="Add product"><i data-lucide="plus"></i></a>
          <a class="admin-icon-link" href="{{ route('admin.orders.index') }}" aria-label="Orders"><i data-lucide="bell"></i></a>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"><i data-lucide="log-out"></i> Logout</button>
          </form>
        </div>
      </header>

      @if (session('admin_status'))
        <div class="admin-alert">{{ session('admin_status') }}</div>
      @endif

      @if (isset($errors) && $errors->any())
        <div class="admin-alert admin-alert-error">
          @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
          @endforeach
        </div>
      @endif

      @yield('content')
    </main>

    <div id="admin-password-modal" class="admin-modal-overlay" data-password-confirm-modal hidden aria-hidden="true">
      <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="admin-password-modal-title">
        <button class="admin-modal-close" type="button" data-password-confirm-cancel aria-label="Close confirmation">
          <i data-lucide="x"></i>
        </button>
        <div>
          <p class="eyebrow">Sensitive action</p>
          <h2 id="admin-password-modal-title" data-password-confirm-title>Confirm action</h2>
          <p data-password-confirm-message>Please enter your current password to continue.</p>
        </div>
        <label>
          Current Password
          <input type="password" autocomplete="current-password" data-password-confirm-input />
        </label>
        <div class="admin-modal-actions">
          <button class="admin-button secondary" type="button" data-password-confirm-cancel>Cancel</button>
          <button class="admin-button danger" type="button" data-password-confirm-submit>Confirm</button>
        </div>
      </div>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
      if (window.lucide) window.lucide.createIcons();

      (() => {
        const sidebar = document.querySelector('[data-admin-sidebar]');
        const toggle = document.querySelector('[data-admin-sidebar-toggle]');
        if (!sidebar || !toggle) return;

        toggle.addEventListener('click', () => {
          const isOpen = sidebar.classList.toggle('is-open');
          toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
      })();

      (() => {
        const modal = document.querySelector('#admin-password-modal');
        if (!modal) return;

        const title = modal.querySelector('[data-password-confirm-title]');
        const message = modal.querySelector('[data-password-confirm-message]');
        const passwordInput = modal.querySelector('[data-password-confirm-input]');
        const submitButton = modal.querySelector('[data-password-confirm-submit]');
        const cancelButtons = modal.querySelectorAll('[data-password-confirm-cancel]');
        let targetForm = null;
        let targetTrigger = null;
        let returnFocusTo = null;

        const clearInjectedPasswords = () => {
          document.querySelectorAll('input[data-injected-password]').forEach((input) => input.remove());
        };

        const close = () => {
          modal.classList.remove('is-open');
          modal.setAttribute('aria-hidden', 'true');
          modal.hidden = true;
          document.body.classList.remove('admin-modal-lock');
          passwordInput.value = '';
          clearInjectedPasswords();
          targetTrigger = null;
          targetForm = null;
          if (returnFocusTo) returnFocusTo.focus();
          returnFocusTo = null;
        };

        const open = (form, trigger) => {
          clearInjectedPasswords();
          targetForm = form;
          targetTrigger = trigger;
          returnFocusTo = trigger;
          title.textContent = trigger.dataset.confirmTitle || form.dataset.confirmTitle || 'Confirm action';
          message.textContent = trigger.dataset.confirmMessage || form.dataset.confirmMessage || 'Please enter your current password to continue.';
          submitButton.textContent = trigger.dataset.confirmButton || form.dataset.confirmButton || 'Confirm';
          submitButton.classList.toggle('danger', (trigger.dataset.confirmTone || form.dataset.confirmTone) !== 'neutral');
          modal.hidden = false;
          modal.classList.add('is-open');
          modal.setAttribute('aria-hidden', 'false');
          document.body.classList.add('admin-modal-lock');
          window.requestAnimationFrame(() => passwordInput.focus());
        };

        document.addEventListener('submit', (event) => {
          const form = event.target.closest('form[data-requires-password-confirmation]');
          if (!form || form.dataset.passwordConfirmed === 'true') return;

          const trigger = event.submitter;
          if (!trigger || !trigger.matches('[data-password-confirm]')) return;

          event.preventDefault();
          open(form, trigger);
        });

        submitButton.addEventListener('click', () => {
          if (!targetForm || passwordInput.value.trim() === '') {
            passwordInput.focus();
            return;
          }

          targetForm.querySelectorAll('input[data-injected-password]').forEach((input) => input.remove());

          const hiddenPassword = document.createElement('input');
          hiddenPassword.type = 'hidden';
          hiddenPassword.name = 'password';
          hiddenPassword.value = passwordInput.value;
          hiddenPassword.dataset.injectedPassword = 'true';
          targetForm.appendChild(hiddenPassword);
          targetForm.dataset.passwordConfirmed = 'true';

          const formToSubmit = targetForm;
          const triggerToSubmit = targetTrigger;

          modal.classList.remove('is-open');
          modal.setAttribute('aria-hidden', 'true');
          modal.hidden = true;
          document.body.classList.remove('admin-modal-lock');
          passwordInput.value = '';
          targetTrigger = null;
          targetForm = null;
          returnFocusTo = null;

          if (formToSubmit.requestSubmit && triggerToSubmit) {
            formToSubmit.requestSubmit(triggerToSubmit);
          } else {
            HTMLFormElement.prototype.submit.call(formToSubmit);
          }
        });

        cancelButtons.forEach((button) => button.addEventListener('click', close));
        modal.addEventListener('click', (event) => {
          if (event.target === modal) close();
        });

        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
        });
      })();

      (() => {
        document.querySelectorAll('[data-change-tracked-form]').forEach((form) => {
          const submitButton = form.querySelector('[data-change-tracked-submit]');
          if (!submitButton) return;

          const fields = Array.from(form.querySelectorAll('input:not([type="hidden"]), textarea, select'));
          const initialValues = new Map(fields.map((field) => [field, field.value]));

          const refresh = () => {
            const hasChanges = fields.some((field) => field.value !== initialValues.get(field));
            submitButton.disabled = !hasChanges;
          };

          fields.forEach((field) => field.addEventListener('input', refresh));
          fields.forEach((field) => field.addEventListener('change', refresh));
          refresh();
        });
      })();
    </script>
    @yield('scripts')
  </body>
</html>
