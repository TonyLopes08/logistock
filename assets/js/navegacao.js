document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('keydown', function(e) {
        if (e.altKey && e.key === 'ArrowLeft') {
            e.preventDefault();
            window.history.back();
        }
    });
});