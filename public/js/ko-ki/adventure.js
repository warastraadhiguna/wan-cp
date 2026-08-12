const intro = document.querySelector('[data-adventure-intro]');

if (intro) {
    const button = intro.querySelector('[data-enter-city]');
    const errorBox = intro.querySelector('[data-adventure-error]');

    button.addEventListener('click', async () => {
        button.disabled = true;
        errorBox.hidden = true;

        try {
            const response = await fetch(intro.dataset.saveUrl, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ action: 'complete_intro' }),
            });

            if (response.status === 409) {
                window.location.assign(intro.dataset.entryUrl);
                return;
            }

            const data = await response.json();
            if (!response.ok) throw new Error(data.message ?? 'Progress belum dapat disimpan.');
            window.location.assign(intro.dataset.mapUrl);
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.hidden = false;
            button.disabled = false;
        }
    });
}
