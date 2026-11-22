const imageDialog = document.querySelector('[data-image-dialog]');
if (imageDialog) {
    document.querySelectorAll('[data-gallery-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const selectedImage = button.querySelector('img');
            if (selectedImage) imageDialog.querySelector('img').src = selectedImage.currentSrc || selectedImage.src;
            imageDialog.showModal();
        });
    });
    imageDialog.addEventListener('click', (event) => {
        if (event.target === imageDialog) imageDialog.close();
    });
}
