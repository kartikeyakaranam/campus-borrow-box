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
