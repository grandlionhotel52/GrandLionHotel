<div class="ajax-navigation-progress" data-ajax-progress aria-hidden="true"></div>
<style>
    .ajax-navigation-progress {
        position: fixed;
        inset: 0 auto auto 0;
        z-index: 2000;
        width: 0;
        height: 3px;
        opacity: 0;
        pointer-events: none;
        background: linear-gradient(90deg, #b98a3d, #f1d18a);
        transition: width .25s ease, opacity .2s ease;
    }

    .ajax-navigating .ajax-navigation-progress {
        width: 72%;
        opacity: 1;
    }

    form[aria-busy="true"] {
        cursor: wait;
    }
</style>
<script src="{{ asset('js/ajax-navigation.js') }}" defer></script>
