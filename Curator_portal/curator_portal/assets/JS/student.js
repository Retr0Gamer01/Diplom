    const input = document.getElementById('documentInput');
    const form = document.getElementById('uploadForm');
    const button = document.getElementById('triggerUpload');

    button.addEventListener('click', () => {
        input.click(); // открывает проводник
    });

    input.addEventListener('change', () => {
        if (input.files.length > 0) {
            form.submit(); // отправляем форму только после выбора
        }
    }); 