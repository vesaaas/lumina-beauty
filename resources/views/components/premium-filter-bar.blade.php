@props([
  'filters',
  'active' => [],
  'showCategory' => false,
  'action' => url()->current(),
])

@php
  $dropdowns = [];

  if ($showCategory) {
      $dropdowns[] = ['name' => 'category', 'label' => 'Category', 'placeholder' => 'All Categories', 'options' => $filters['category']];
  }

  $dropdowns = array_merge($dropdowns, [
      ['name' => 'product_type', 'label' => 'Product Type', 'placeholder' => 'All Types', 'options' => $filters['product_type']],
      ['name' => 'property', 'label' => 'Properties', 'placeholder' => 'All Properties', 'options' => $filters['property']],
      ['name' => 'gender', 'label' => 'Gender', 'placeholder' => 'All Genders', 'options' => $filters['gender']],
      ['name' => 'size', 'label' => 'Size', 'placeholder' => 'All Sizes', 'options' => $filters['size']],
  ]);

  $hasActiveFilters = collect($active)->except(['sort'])->filter(fn ($value) => filled($value))->isNotEmpty()
      || (($active['sort'] ?? 'default') !== 'default');
@endphp

<form class="premium-filter-bar" method="GET" action="{{ $action }}" data-auto-filter data-scroll-reveal>
  @if (! empty($active['search']))
    <input type="hidden" name="search" value="{{ $active['search'] }}" />
  @endif

  <div class="filter-intro">
    <span>Refine</span>
    <strong>Find your ritual</strong>
  </div>

  <div class="filter-dropdown-grid">
    @foreach ($dropdowns as $dropdown)
      @php($current = $active[$dropdown['name']] ?? '')
      <div class="premium-select" data-premium-select>
        <label id="filter-label-{{ $dropdown['name'] }}">{{ $dropdown['label'] }}</label>
        <select class="native-filter-select" name="{{ $dropdown['name'] }}" aria-labelledby="filter-label-{{ $dropdown['name'] }}" tabindex="-1">
          <option value="">{{ $dropdown['placeholder'] }}</option>
          @foreach ($dropdown['options'] as $option)
            <option value="{{ $option }}" @selected($current === $option)>{{ $option }}</option>
          @endforeach
        </select>
        <button class="premium-select-trigger" type="button" aria-haspopup="listbox" aria-expanded="false">
          <span>{{ $current ?: $dropdown['placeholder'] }}</span>
          <i data-lucide="chevron-down"></i>
        </button>
        <div class="premium-select-menu" role="listbox">
          <button type="button" role="option" data-select-value="" @class(['is-selected' => $current === ''])>{{ $dropdown['placeholder'] }}</button>
          @foreach ($dropdown['options'] as $option)
            <button type="button" role="option" data-select-value="{{ $option }}" @class(['is-selected' => $current === $option])>{{ $option }}</button>
          @endforeach
        </div>
      </div>
    @endforeach

    <div class="premium-price-filter">
      <label>Price</label>
      <div>
        <input type="number" name="price_min" min="0" step="1" placeholder="Min" value="{{ $active['price_min'] ?? '' }}" />
        <span></span>
        <input type="number" name="price_max" min="0" step="1" placeholder="Max" value="{{ $active['price_max'] ?? '' }}" />
      </div>
    </div>

    <div class="premium-select premium-sort-select" data-premium-select>
      @php($currentSort = $active['sort'] ?? 'default')
      <label id="filter-label-sort">Sort</label>
      <select class="native-filter-select" name="sort" aria-labelledby="filter-label-sort" tabindex="-1">
        @foreach ($filters['sort'] as $value => $label)
          <option value="{{ $value }}" @selected($currentSort === $value)>{{ $label }}</option>
        @endforeach
      </select>
      <button class="premium-select-trigger" type="button" aria-haspopup="listbox" aria-expanded="false">
        <span>{{ $filters['sort'][$currentSort] ?? $filters['sort']['default'] }}</span>
        <i data-lucide="chevron-down"></i>
      </button>
      <div class="premium-select-menu" role="listbox">
        @foreach ($filters['sort'] as $value => $label)
          <button type="button" role="option" data-select-value="{{ $value }}" @class(['is-selected' => $currentSort === $value])>{{ $label }}</button>
        @endforeach
      </div>
    </div>
  </div>

  <div class="filter-actions">
    <button class="filter-apply-button" type="submit"><i data-lucide="sparkles"></i> Apply</button>
    @if ($hasActiveFilters)
      <a class="filter-reset-link" href="{{ $action }}">Reset</a>
    @endif
  </div>
</form>
