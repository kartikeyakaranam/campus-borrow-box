document.querySelectorAll('[data-validate]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (!form.checkValidity()) {
      event.preventDefault();
      form.reportValidity();
    }
  });
});

document.querySelectorAll('textarea[maxlength]').forEach((field) => {
  const count = document.querySelector(`[data-count-for="${field.id}"]`);
  if (!count) return;
  const update = () => { count.textContent = field.value.length; };
  field.addEventListener('input', update);
  update();
});

// Update search results when the category changes.
const category = document.querySelector('#category[name="category"]');
if (category && category.closest('.filters')) {
  category.addEventListener('change', () => category.form.requestSubmit());
}

const borrowForm = document.querySelector('[data-cost-preview]');
if (borrowForm) {
  const start = borrowForm.querySelector('#start_at');
  const end = borrowForm.querySelector('#end_at');
  const duration = borrowForm.querySelector('[data-duration]');
  const total = borrowForm.querySelector('[data-total]');
  const money = (value) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(value);
  const update = () => {
    end.min = start.value;
    total.textContent = '';
    if (!start.value || !end.value) {
      duration.textContent = 'Choose pickup and return times to see the total.';
      return;
    }
    const minutes = Math.round((new Date(end.value) - new Date(start.value)) / 60000);
    if (!Number.isFinite(minutes) || minutes <= 0) {
      duration.textContent = 'Return time must be after pickup time.';
      return;
    }
    const minimum = Number(borrowForm.dataset.minimum);
    if (minutes < minimum * 60) {
      duration.textContent = `Choose at least ${minimum} hours for this item.`;
      return;
    }
    if (minutes > 30 * 24 * 60) {
      duration.textContent = 'Borrowing time cannot exceed 30 days.';
      return;
    }
    const billedHours = Math.ceil(minutes / 60);
    const exactHours = Math.floor(minutes / 60);
    const extraMinutes = minutes % 60;
    const length = `${exactHours} hour${exactHours === 1 ? '' : 's'}${extraMinutes ? ` ${extraMinutes} minutes` : ''}`;
    if (borrowForm.dataset.mode === 'hourly') {
      const rate = Number(borrowForm.dataset.rate);
      duration.textContent = `${length} · ${billedHours} billed hour${billedHours === 1 ? '' : 's'} × ${money(rate)}/hour`;
      total.textContent = `Total: ${money(billedHours * rate)}`;
    } else {
      duration.textContent = `${length} · flat fee`;
      total.textContent = `Total: ${money(Number(borrowForm.dataset.flat))}`;
    }
  };
  start.addEventListener('input', update);
  end.addEventListener('input', update);
  start.addEventListener('change', update);
  end.addEventListener('change', update);
}
