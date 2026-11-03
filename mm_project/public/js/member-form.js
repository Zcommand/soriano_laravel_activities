const levelSelect = document.getElementById('membership_level_id');

if (levelSelect) {
    const updateLevelReference = () => {
        document.querySelectorAll('[data-reference-level]').forEach((row) => {
            const selected = row.dataset.referenceLevel === levelSelect.value;
            row.classList.toggle('is-selected', selected);
            row.querySelector('.selected-label').hidden = !selected;
        });
    };

    levelSelect.addEventListener('change', updateLevelReference);
    window.addEventListener('pageshow', updateLevelReference);
    updateLevelReference();
}

const memberForm = document.querySelector('.member-form');

if (memberForm) {
    const categorySelect = memberForm.querySelector('#membership_category_id');
    const companyField = memberForm.querySelector('[data-company-field]');
    const companyInput = memberForm.querySelector('#company_name');

    const updateCompanyField = () => {
        const corporate = categorySelect.selectedOptions[0]?.dataset.corporate === 'true';
        companyField.hidden = !corporate;
        companyInput.disabled = !corporate;
        companyInput.required = corporate;
    };

    updateCompanyField();
    categorySelect.addEventListener('change', updateCompanyField);
    window.addEventListener('pageshow', updateCompanyField);

    const values = () => JSON.stringify([...new FormData(memberForm)]);
    const initialValues = values();
    let submitting = false;

    memberForm.addEventListener('submit', () => { submitting = true; });
    window.addEventListener('pageshow', () => { submitting = false; });
    window.addEventListener('beforeunload', (event) => {
        if (!submitting && values() !== initialValues) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
}
