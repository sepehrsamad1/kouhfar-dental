
(function () {
    // Design-ui pages are single compositions, so the whole body is editable.
    function markEditable() {
        document.body.classList.add('artifact-editable');
    }

    function init() {
        markEditable();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

