const mobileWorkspace = window.matchMedia('(max-width: 860px)');
const workspaceMenu = document.querySelector('.workspace-menu-body');
const menuToggle = document.querySelector('.workspace-menu-toggle');
const levelBreakdown = document.querySelector('.level-breakdown');

const adaptWorkspace = () => {
    if (workspaceMenu) workspaceMenu.hidden = mobileWorkspace.matches;
    menuToggle?.setAttribute('aria-expanded', String(!mobileWorkspace.matches));
    if (levelBreakdown) levelBreakdown.open = !mobileWorkspace.matches;
};

adaptWorkspace();
mobileWorkspace.addEventListener('change', adaptWorkspace);

menuToggle?.addEventListener('click', () => {
    workspaceMenu.hidden = !workspaceMenu.hidden;
    menuToggle.setAttribute('aria-expanded', String(!workspaceMenu.hidden));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && mobileWorkspace.matches && workspaceMenu && !workspaceMenu.hidden) {
        workspaceMenu.hidden = true;
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.focus();
    }
});
