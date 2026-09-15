document.addEventListener('DOMContentLoaded', () => {
  const rowsContainer = document.getElementById('bean-rows');
  const template = document.getElementById('bean-template');
  const addButton = document.getElementById('add-bean');
  if (!rowsContainer || !template || !addButton) {
    return;
  }

  let nextIndex = rowsContainer.querySelectorAll('[data-bean-row]').length;

  function bindRemove(row) {
    const btn = row.querySelector('.remove-bean');
    btn.addEventListener('click', () => {
      if (rowsContainer.querySelectorAll('[data-bean-row]').length > 1) {
        row.remove();
      } else {
        row.querySelectorAll('input').forEach((el) => { el.value = ''; });
        row.querySelectorAll('select').forEach((el) => { el.selectedIndex = 0; });
      }
    });
  }

  rowsContainer.querySelectorAll('[data-bean-row]').forEach(bindRemove);

  addButton.addEventListener('click', () => {
    const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    const row = wrapper.firstElementChild;
    rowsContainer.appendChild(row);
    bindRemove(row);
    nextIndex += 1;
  });
});
