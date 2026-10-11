@props(['id', 'name', 'label', 'autocomplete' => 'current-password', 'requiredMessage' => null, 'hint' => null])
{{-- Password input with the design system's show/hide toggle (app.js owns it). --}}
<div class="field password-field" data-field>
  <label for="{{ $id }}">{{ $label }}</label>
  <div class="password-field__wrap">
    <input id="{{ $id }}" name="{{ $name }}" type="password" required autocomplete="{{ $autocomplete }}"
           @if($hint) aria-describedby="{{ $id }}-hint" @endif
           @if($requiredMessage) data-required-message="{{ $requiredMessage }}" @endif>
    <button type="button" class="password-field__toggle" data-password-toggle aria-pressed="false" aria-controls="{{ $id }}" aria-label="Show password">
      <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-eye"></use></svg>
      <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-eye-off"></use></svg>
    </button>
  </div>
  @if($hint)<p class="field__hint" id="{{ $id }}-hint">{{ $hint }}</p>@endif
  <p class="field__error" id="{{ $id }}-error" hidden></p>
</div>
