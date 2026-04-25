<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('copy-to-clipboard', (params) => {
            const payload = Array.isArray(params) ? params[0] : params;
            const text = payload?.text;

            if (!text) {
                return;
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).catch(() => fallbackCopy(text));
            } else {
                fallbackCopy(text);
            }
        });

        function fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy');
            } catch (e) {
                // ignore
            }

            document.body.removeChild(textarea);
        }
    });
</script>
