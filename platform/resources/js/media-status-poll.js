const POLL_INTERVAL_MS = 3000;
const MAX_ATTEMPTS = 40; // ~2 minutos como tope de seguridad

function applyStatus(el, data) {
    el.dataset.mediaStatus = data.status;

    const img = el.querySelector('[data-media-status-image]');
    const spinner = el.querySelector('[data-media-status-spinner]');
    const errorBox = el.querySelector('[data-media-status-error]');

    if (data.status === 'completed') {
        if (img) {
            const isThumbnail = el.dataset.mediaStatusThumbnail === '1';
            img.src = isThumbnail ? data.thumbnail_url : data.url;
            img.classList.remove('hidden');
        }
        spinner?.classList.add('hidden');
        errorBox?.classList.add('hidden');
        errorBox?.classList.remove('flex');
    } else if (data.status === 'failed') {
        spinner?.classList.add('hidden');
        if (errorBox) {
            errorBox.textContent = data.error_message || 'No se pudo procesar la imagen.';
            errorBox.classList.remove('hidden');
            errorBox.classList.add('flex');
        }
    }
}

function pollMedia(el) {
    let attempts = 0;

    const tick = () => {
        attempts++;

        fetch(el.dataset.mediaStatusUrl, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data) => applyStatus(el, data))
            .catch(() => {})
            .finally(() => {
                const finished = ['completed', 'failed'].includes(el.dataset.mediaStatus);
                if (!finished && attempts < MAX_ATTEMPTS) {
                    setTimeout(tick, POLL_INTERVAL_MS);
                }
            });
    };

    tick();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-media-status-poll]').forEach((el) => {
        if (['queued', 'processing'].includes(el.dataset.mediaStatus)) {
            pollMedia(el);
        }
    });
});
