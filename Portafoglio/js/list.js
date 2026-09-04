/**
 * list.js
 * Popola i filtri, carica gli avvistamenti e li rende come card.
 */


async function loadCategorie() {
  const select = document.getElementById('filterCategoria');
  if (!select) return;

  let categorie;
  try {
    // Recupera dal backend le categorie disponibili per il filtro.
    categorie = await apiRequest('/categorie/list_cat.php');
  } catch (e) {
    return; // i filtri restano con la sola opzione "Tutte"
  }

  categorie.forEach(cat => {
    // Aggiunge ogni categoria come opzione del menu.
    const opt = document.createElement('option');
    opt.value = cat.nome;
    opt.textContent = cat.nome;
    select.appendChild(opt);
  });
}

// Recupera gli avvistamenti applicando i filtri selezionati.
async function fetchAvvistamenti(filters) {
  return apiRequest(`/avvistamenti/list.php${buildQuery(filters)}`);
}

function cardTemplate(item, currentUser = null) {
  // Determina se l'utente corrente può modificare o eliminare la card.
  const isOwner = item.username === currentUser;

  // Usa l'immagine dell'avvistamento oppure un segnaposto.
  const photoInner = item.immagine
    ? `<img src="${pulisciTesto(item.immagine)}" alt="${pulisciTesto(item.marca)} ${pulisciTesto(item.modello)}">`
    : `<img src="imgs/placeholder.png" alt="Immagine non disponibile">`;

  return `
    <!-- Card di un singolo avvistamento. -->
    <article class="spot-card" data-id="${item.id}">
      <div class="spot-photo">
        ${photoInner}
        <span class="plate-badge">
          <span class="plate-strip"></span>
          <span class="plate-text">${pulisciTesto(item.targa)} </span>
        </span>
        ${item.anno ? `<span class="spot-year-tag">${pulisciTesto(item.anno)}</span>` : ''}
      </div>
      <div class="spot-body">
        <h3 class="spot-title">${pulisciTesto(item.marca)} ${pulisciTesto(item.modello)}</h3>
        <p class="spot-desc">${pulisciTesto(item.descrizione || '')}</p>
        <div class="spot-meta">
          <span>📍 ${pulisciTesto(item.luogo || '—')}</span>
          <span>${formatDate(item.data_avvistamento)}</span>
          ${item.categoria ? `<span class="tag-chip">${pulisciTesto(item.categoria)}</span>` : ''}
        </div>
        <div class="spot-footer">
          <span class="spot-author">@${pulisciTesto(item.username)}</span>
          ${isOwner ? `
            <div class="spot-actions">
              <button class="update-btn" data-id="${item.id}">Modifica</button>
              <button class="delete-btn" data-id="${item.id}">Elimina</button>
            </div>` : ''}
        </div>
      </div>
    </article>
  `;
}

async function updateSpot(id) {
  // Apre il form di modifica passando l'identificativo nell'URL.
  window.location.href = `edit_card.html?id=${encodeURIComponent(id)}`;
}

async function deleteSpot(id) {
  // Chiede conferma prima di eliminare definitivamente l'avvistamento.
  if (!confirm('Eliminare questo avvistamento?')) return;
  try {
    await apiRequest('/avvistamenti/delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    document.querySelector(`.spot-card[data-id="${id}"]`).remove();
  } catch (err) {
    alert(err.message);
  }
}

async function renderCards(filters = {}) {
  const grid = document.getElementById('cardGrid');
  const emptyState = document.getElementById('emptyState');
  const resultCount = document.getElementById('resultCount');

  // Mostra uno stato temporaneo mentre i dati vengono caricati.
  grid.innerHTML = '<div class="loading-row">Caricamento avvistamenti…</div>';
  emptyState.hidden = true;

  let items;
  
  try {
    items = await fetchAvvistamenti(filters);
  } catch (err) {
    grid.innerHTML = `<div class="loading-row">Errore: ${pulisciTesto(err.message)}</div>`;
    return;
  }

  if (!items || items.length === 0) {
    // Mostra lo stato vuoto quando nessun elemento soddisfa i filtri.
    grid.innerHTML = '';
    emptyState.hidden = false;
    resultCount.textContent = '';
    return;
  }
  // Ricava l'utente corrente per mostrare le azioni solo al proprietario.
  const userChip = document.querySelector('.user-chip');
  const currentUser = userChip ? userChip.textContent.substring(1) : null;

  // Ordina gli avvistamenti dal più recente al più vecchio.
  items.sort((a, b) => new Date(b.data_avvistamento) - new Date(a.data_avvistamento));
  resultCount.textContent = `${items.length} avvistament${items.length === 1 ? 'o' : 'i'}`;
  grid.innerHTML = items.map(item => cardTemplate(item, currentUser)).join('');

  grid.querySelectorAll('.update-btn').forEach(btn => {
    btn.addEventListener('click', () => updateSpot(btn.dataset.id));
  });

  grid.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', () => deleteSpot(btn.dataset.id));
  });

  
}

function currentFilters(form) {
  // Raccoglie i valori attuali del modulo di ricerca.
  return {
    marca: form.marca.value.trim(),
    categoria: form.categoria.value,
    luogo: form.luogo.value.trim(),
    username:form.username.value.trim(),
    data_da: form.data_da.value,
    data_a: form.data_a.value
  };
}

document.addEventListener('DOMContentLoaded', () => {
  const grid = document.getElementById('cardGrid');
  if (!grid) return; // La pagina non contiene la griglia degli avvistamenti.

  const filterForm = document.getElementById('filterForm');

  // Carica categorie e card iniziali.
  loadCategorie();
  renderCards();

  filterForm.addEventListener('submit', (e) => {
    e.preventDefault();
    renderCards(currentFilters(filterForm));
  });

  document.getElementById('resetFilters').addEventListener('click', () => {
    // Attende il reset nativo prima di ricaricare tutti gli avvistamenti.
    setTimeout(() => renderCards(), 0); // dopo il reset nativo del form
  });
});
