const memberSelect = document.querySelector('#member_id');
const membershipSummary = document.querySelector('[data-membership-summary]');

const showMembership = () => {
    const template = document.getElementById(`membership-context-${memberSelect.value}`);
    if (template) {
        membershipSummary.replaceChildren(template.content.cloneNode(true));
    } else {
        const prompt = document.createElement('p');
        prompt.textContent = 'Select a member to see their current membership.';
        membershipSummary.replaceChildren(prompt);
    }
};

if (memberSelect && membershipSummary) {
    memberSelect.addEventListener('change', showMembership);
    showMembership();
}
