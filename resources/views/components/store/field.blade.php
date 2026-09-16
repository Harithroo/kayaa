@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false])
<div @class(['field', 'invalid' => $errors->has($name)])>
  <label for="f-{{ $name }}">{{ $label }}</label>
  @if($type === 'textarea')
    <textarea id="f-{{ $name }}" name="{{ $name }}" {{ $attributes }}>{{ old($name, $value) }}</textarea>
  @else
    <input id="f-{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required) {{ $attributes }}>
  @endif
  @error($name)<span class="err">{{ $message }}</span>@enderror
</div>
