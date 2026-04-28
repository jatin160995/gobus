@php $name = "settings[{$setting->id}]"; @endphp

@if($setting->type === 'text')
    <input type="text" class="form-control"
           name="{{ $name }}"
           value="{{ $setting->value }}">

@elseif($setting->type === 'password')
    <input type="password" class="form-control"
           name="{{ $name }}"
           value="{{ $setting->value }}">

@elseif($setting->type === 'textarea')
    <textarea class="form-control" rows="3"
              name="{{ $name }}">{{ $setting->value }}</textarea>

@elseif($setting->type === 'editor')
    <textarea class="form-control editor" rows="5"
              name="{{ $name }}">{{ $setting->value }}</textarea>

@elseif($setting->type === 'boolean')
    <div class="form-check form-switch">
        <input class="form-check-input"
               type="checkbox"
               name="{{ $name }}"
               value="1"
               {{ $setting->value ? 'checked' : '' }}>
    </div>
@endif
