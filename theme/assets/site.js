(() => {
  const cards = [...document.querySelectorAll('.menu-card')];
  const count = document.getElementById('menu-count');
  if (count) count.textContent = `${cards.length} dishes`;
  document.querySelectorAll('.category-tabs button').forEach(button => button.addEventListener('click', () => {
    document.querySelectorAll('.category-tabs button').forEach(tab => {
      tab.classList.toggle('active', tab === button);
      tab.setAttribute('aria-pressed', tab === button ? 'true' : 'false');
    });
    let visible = 0;
    cards.forEach(card => {
      card.hidden = button.dataset.category !== 'all' && !card.dataset.category.split(' ').includes(button.dataset.category);
      if (!card.hidden) visible++;
    });
    count.textContent = `${visible} dishes`;
    document.querySelector('.menu-section-title h2').textContent = button.dataset.category === 'all' ? 'All the good stuff' : button.textContent;
  }));

  const form = document.getElementById('reservation-form');
  if (form) form.addEventListener('submit', () => {
    const button = form.querySelector('[type="submit"]');
    button.disabled = true;
    button.textContent = 'Saving your table…';
  });
})();
