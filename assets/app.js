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

// A small DOM interaction from the course: category changes refresh the search.
const category = document.querySelector('#category[name="category"]');
if (category && category.closest('.filters')) {
  category.addEventListener('change', () => category.form.requestSubmit());
}

document.querySelectorAll('[data-pricing-mode]').forEach((select) => {
  const form = select.form;
  const update = () => {
    form.querySelector('[data-flat-price]').hidden = select.value !== 'flat';
    form.querySelector('[data-hourly-price]').hidden = select.value !== 'hourly';
    form.querySelector('#fee').required = select.value === 'flat';
    form.querySelector('#hourly_rate').required = select.value === 'hourly';
  };
  select.addEventListener('change', update);
  update();
});

const borrowForm = document.querySelector('[data-cost-preview]');
if (borrowForm) {
  const start = borrowForm.querySelector('#start_at');
  const end = borrowForm.querySelector('#end_at');
  const preview = borrowForm.querySelector('.cost-preview');
  const money = (value) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(value);
  const update = () => {
    end.min = start.value;
    const hours = (new Date(end.value) - new Date(start.value)) / 3600000;
    const minimum = Number(borrowForm.dataset.minimum);
    if (!start.value || !end.value || !Number.isFinite(hours)) {
      preview.textContent = 'Choose pickup and return times to see the cost.';
    } else if (hours < minimum) {
      preview.textContent = `Choose at least ${minimum} hours for this item.`;
    } else if (hours > 720) {
      preview.textContent = 'Borrowing time cannot exceed 30 days.';
    } else {
      const billed = Math.ceil(hours);
      const fee = borrowForm.dataset.mode === 'hourly'
        ? billed * Number(borrowForm.dataset.rate)
        : Number(borrowForm.dataset.flat);
      preview.textContent = borrowForm.dataset.mode === 'hourly'
        ? `${billed} billed hours × ${money(Number(borrowForm.dataset.rate))} = ${money(fee)}. Each started hour counts as one hour.`
        : `${money(fee)} flat fee for this borrow.`;
    }
  };
  start.addEventListener('change', update);
  end.addEventListener('change', update);
}
