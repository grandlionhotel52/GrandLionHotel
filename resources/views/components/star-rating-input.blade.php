@props([
    'name' => 'rating',
    'id' => 'rating',
    'label' => 'Overall rating',
    'value' => null,
])

<fieldset class="star-rating-fieldset" aria-describedby="{{ $id }}_hint @error($name) {{ $id }}_error @enderror">
    <legend class="form-label mb-1">{{ $label }}</legend>
    <div class="star-rating-options @error($name) is-invalid @enderror">
        @for($star = 5; $star >= 1; $star--)
            <input
                type="radio"
                id="{{ $id }}_{{ $star }}"
                name="{{ $name }}"
                value="{{ $star }}"
                @checked((int) $value === $star)
                required
            >
            <label for="{{ $id }}_{{ $star }}" aria-label="{{ $star }} out of 5 stars" title="{{ $star }} {{ Str::plural('star', $star) }}">
                <i class="bi bi-star-fill" aria-hidden="true"></i>
            </label>
        @endfor
    </div>
    <div id="{{ $id }}_hint" class="small text-secondary">Select 1 to 5 stars.</div>
    @error($name)
        <div id="{{ $id }}_error" class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</fieldset>
