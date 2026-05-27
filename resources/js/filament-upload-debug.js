/**
 * Console hints when Livewire file upload stalls (e.g. Kaspersky blocking upload-file).
 */
document.addEventListener('livewire:init', () => {
    const log = (event, detail) => {
        console.info(`[24logist upload] ${event}`, detail ?? '');
    };

    document.addEventListener('livewire-upload-start', (e) => log('start — next should be POST …/upload-file', e.detail));
    document.addEventListener('livewire-upload-finish', (e) => log('finish', e.detail));
    document.addEventListener('livewire-upload-error', (e) => log('error', e.detail));
    document.addEventListener('livewire-upload-progress', (e) => log('progress', e.detail));
});
