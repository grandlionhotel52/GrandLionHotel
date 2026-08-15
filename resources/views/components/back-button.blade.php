@props(['href', 'label' => 'Back'])

<a href="{{ $href }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->except(['aria-label', 'title'])->class(['btn', 'ui-back-button']) }}>
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    <span>Back</span>
</a>
