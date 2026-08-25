let activeCat = null;

function filterCategory(cat){
  const cards = document.querySelectorAll('.grid .card');
  const pills = document.querySelectorAll('.cat-pill');

  if (activeCat === cat) {
    activeCat = null;
    cards.forEach(c => c.style.display = '');
    pills.forEach(p => p.style.opacity = '1');
  } else {
    activeCat = cat;
    cards.forEach(c => c.style.display = (c.dataset.cat === cat) ? '' : 'none');
    pills.forEach(p => p.style.opacity = (p.dataset.cat === cat) ? '1' : '.45');
  }
  const grid = document.getElementById('grid');
  if (grid) grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function goHome(){
  activeCat = null;
  document.querySelectorAll('.grid .card').forEach(c => c.style.display = '');
  document.querySelectorAll('.cat-pill').forEach(p => p.style.opacity = '1');
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
