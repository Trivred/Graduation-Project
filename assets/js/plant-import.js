document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('fileInput');
    const fileName = document.getElementById('fileName');
    const downloadButtons = document.querySelectorAll('[data-download-template]');

    downloadButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });

    if (fileInput && fileName) {
        fileInput.addEventListener('change', function () {
            fileName.textContent = this.files && this.files.length > 0
                ? this.files[0].name
                : 'لم يتم اختيار ملف بعد';
        });
    }
});
