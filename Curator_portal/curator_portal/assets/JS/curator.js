// ========== ФАЙЛОВЫЙ ВЫБОР (для формы в верхней панели) ==========
const input = document.getElementById('documentInput');
const form = document.getElementById('uploadForm');
const button = document.getElementById('triggerUpload');

if (input && form && button) {
    button.addEventListener('click', () => {
        input.click(); // Открыть проводник
    });

    input.addEventListener('change', () => {
        if (input.files.length > 0) {
            form.submit(); // Отправить форму
        }
    });
}

// ========== ФИЛЬТРЫ: ПЕРИОД, СТУДЕНТ, ГРУППА ==========

const filterForm = document.getElementById('filterForm');
const groupSelect = document.getElementById('group');
const studentSelect = document.getElementById('student');

if (groupSelect && studentSelect && filterForm) {
    groupSelect.addEventListener('change', () => filterForm.submit());
    studentSelect.addEventListener('change', () => filterForm.submit());
}

// ========== МОДАЛЬНОЕ ОКНО (ДОБАВЛЕНИЕ ДОКУМЕНТА КУРАТОРОМ) ==========

const openModalBtn = document.getElementById('openModal');
const modal = document.getElementById('uploadModal');
const closeModalBtn = document.getElementById('closeModal');
const modalForm = document.getElementById('modalUploadForm');
const modalFile = document.getElementById('modalFile');
const modalStudent = document.getElementById('modalStudent');
const modalGroup = document.getElementById('modalGroup');

if (openModalBtn && modal && closeModalBtn && modalForm) {
    openModalBtn.addEventListener('click', () => {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });

    closeModalBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    });

    modalForm.addEventListener('submit', function (e) {
        const student = modalStudent?.value.trim();
        const group = modalGroup?.value.trim();
        const file = modalFile?.files[0];

        if (!student || !group || !file) {
            alert('Пожалуйста, выберите студента, группу и файл.');
            e.preventDefault();
        }
    });
}

// Кастомный загрузчик
const customDisplay = document.getElementById('customFileDisplay');
const realInput = document.getElementById('modalFile');
const fileNameSpan = customDisplay.querySelector('.file-name');

customDisplay.addEventListener('click', () => {
  realInput.click();
});

realInput.addEventListener('change', () => {
  const file = realInput.files[0];
  fileNameSpan.textContent = file ? file.name : 'Файл не выбран';
});