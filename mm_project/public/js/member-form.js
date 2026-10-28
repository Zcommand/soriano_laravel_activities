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
