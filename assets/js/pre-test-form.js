document.addEventListener('DOMContentLoaded', function () {
    const steps = document.querySelectorAll('.form-step');
    let current = 1;

    function showStep(n) {
        steps.forEach(step => {
            step.hidden = parseInt(step.dataset.step) !== n;
        });
        current = n;
    }

    document.querySelectorAll('.next-step').forEach(btn => {
        btn.addEventListener('click', () => showStep(current + 1));
    });

    document.querySelectorAll('.prev-step').forEach(btn => {
        btn.addEventListener('click', () => showStep(current - 1));
    });

    showStep(1);
});
