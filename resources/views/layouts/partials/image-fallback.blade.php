<script>
    (() => {
        const fallbackImage = @json(asset(\App\Models\Room::FALLBACK_IMAGE_PATH));

        const useFallback = (image) => {
            if (image.dataset.fallbackApplied === 'true') {
                return;
            }

            image.dataset.fallbackApplied = 'true';
            image.src = image.dataset.imageFallback || fallbackImage;
        };

        document.addEventListener('error', (event) => {
            if (event.target instanceof HTMLImageElement) {
                useFallback(event.target);
            }
        }, true);

        document.querySelectorAll('img').forEach((image) => {
            if (image.complete && image.naturalWidth === 0) {
                useFallback(image);
            }
        });
    })();
</script>
