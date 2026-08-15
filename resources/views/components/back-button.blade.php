@props(['href', 'label' => 'Back'])

<a href="{{ $href }}" {{ $attributes->class(['btn', 'ui-back-button']) }}>
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    <span>{{ $label }}</span>
</a>
