/**
 * form.js
 * Gestisce i form di creazione e modifica: anteprima immagine con drag & drop
 * e invio dei dati al relativo endpoint.
 */

function initDropzone() {
  // Recupera gli elementi usati per selezionare, visualizzare e rimuovere l'immagine.
  const dropzone = document.getElementById('dropzone');
  const fileInput = document.getElementById('immagine');
  const preview = document.getElementById('imagePreview');
  const previewImg = preview.querySelector('img');
  const removeBtn = preview.querySelector('.remove-image');

  function showPreview(file) {
    if (!file) return;
    // Legge il file localmente e lo mostra nell'anteprima senza inviarlo al server.
    const reader = new FileReader();
    reader.onload = (e) => {
      previewImg.src = e.target.result;
      preview.style.display = 'block';
      dropzone.style.display = 'none';
    };
    reader.readAsDataURL(file);
  }

  // Permette di aprire il selettore file facendo clic sull'area di caricamento.
  dropzone.addEventListener('click', () => fileInput.click());

  fileInput.addEventListener('change', () => {
    showPreview(fileInput.files[0]);
  });

  // Evidenzia l'area quando l'utente trascina un file sopra la dropzone.
  ['dragenter', 'dragover'].forEach(evt => {
    dropzone.addEventListener(evt, (e) => {
      e.preventDefault();
      dropzone.classList.add('dragover');
    });
  });

  // Rimuove l'evidenziazione quando il file lascia l'area o viene rilasciato.
  ['dragleave', 'drop'].forEach(evt => {
    dropzone.addEventListener(evt, (e) => {
      e.preventDefault();
      dropzone.classList.remove('dragover');
    });
  });

  // Acquisisce il primo file trascinato e aggiorna l'anteprima.
  dropzone.addEventListener('drop', (e) => {
    const file = e.dataTransfer.files[0];
    if (file) {
      fileInput.files = e.dataTransfer.files;
      showPreview(file);
    }
  });

  // Azzera il campo file e ripristina la dropzone.
  removeBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    fileInput.value = '';
    preview.style.display = 'none';
    dropzone.style.display = 'block';
  });
}

// Carica nel form i dati dell'avvistamento selezionato per la modifica.
async function loadSpotForEdit(id, form, preview, dropzone) {
  const spot = await apiRequest(`/avvistamenti/get.php?id=${(id)}`);
  if (!spot) return false;

  ['marca', 'modello', 'anno', 'categoria', 'luogo', 'targa', 'data_avvistamento', 'descrizione']
    .forEach(field => {
      // Compila solo i campi presenti nella risposta del server.
      if (spot[field] !== null && spot[field] !== undefined) {
        form[field].value = spot[field];
      }
    });

  if (spot.immagine) {
      // Mostra l'immagine già associata all'avvistamento.
    preview.querySelector('img').src = spot.immagine;
    preview.style.display = 'block';
    dropzone.style.display = 'none';
  }

  return true;
}

// Inizializza l'invio del form per la creazione o la modifica di un avvistamento.
function initNewSpotForm(editId = null) {
  const form = document.getElementById('newSpotForm');
  if (!form) return;

  const msg = document.getElementById('newSpotMsg');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // In fase di creazione l'immagine è obbligatoria; in modifica può essere mantenuta.
    const fileInput = document.getElementById('immagine');
    if (!editId && !fileInput.files[0]) {
      showFormMessage(msg, 'Carica una foto dell\'auto avvistata.', 'error');
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    try {
      if (editId) {
        // Prepara i dati da inviare all'endpoint di aggiornamento.
        const formData = new FormData();
        formData.append('id', editId);
        formData.append('marca', form.marca.value.trim());
        formData.append('modello', form.modello.value.trim());
        formData.append('anno', form.anno.value || '');
        formData.append('categoria', form.categoria.value);
        formData.append('luogo', form.luogo.value.trim());
        formData.append('targa', form.targa.value.trim());
        formData.append('data_avvistamento', form.data_avvistamento.value);
        formData.append('descrizione', form.descrizione.value.trim());
        if (fileInput.files[0]) {
          formData.append('immagine', fileInput.files[0]);
        }

        await apiRequest('/avvistamenti/update.php', {
          method: 'POST',
          body: formData
        });
        
      } else {
        // Prepara i dati da inviare all'endpoint di creazione.
        const formData = new FormData();
        formData.append('marca', form.marca.value.trim());
        formData.append('modello', form.modello.value.trim());
        formData.append('anno', form.anno.value);
        formData.append('categoria', form.categoria.value);
        formData.append('luogo', form.luogo.value.trim());
        formData.append('targa', form.targa.value.trim());
        formData.append('data_avvistamento', form.data_avvistamento.value);
        formData.append('descrizione', form.descrizione.value.trim());
        formData.append('immagine', fileInput.files[0]);

        await apiRequest('/avvistamenti/create.php', {
          method: 'POST',
          body: formData
        });
      }

      window.location.href = 'index.html';
    } catch (err) {
      showFormMessage(msg, err.message, 'error');
    } finally {
      submitBtn.disabled = false;
    }
  });
}

// Carica dal server le categorie disponibili e le aggiunge al menu a tendina.
async function loadCategorieForForm() {
  const select = document.getElementById('categoria');
  if (!select) return;
  try {
    const categorie = await apiRequest('/categorie/list_cat.php');
    categorie.forEach(cat => {
      // Ogni categoria diventa un'opzione selezionabile.
      const opt = document.createElement('option');
      opt.value = cat.nome;
      opt.textContent = cat.nome;
      select.appendChild(opt);
    });
  } catch (e) {
    console.error('Impossibile caricare le categorie:', e);
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  const form = document.getElementById('newSpotForm');
  if (!form) return; // La pagina non contiene il form degli avvistamenti.

  // Impedisce l'accesso al form agli utenti non autenticati.
  const allowed = await requireAuth();
  if (!allowed) return;

  initDropzone();
  await loadCategorieForForm();

  const editId = new URLSearchParams(window.location.search).get('id');
  if (editId) {
    // Adatta titolo, sottotitolo e pulsante quando il form è in modalità modifica.
    document.querySelector('.form-card h1').textContent = 'Modifica un avvistamento';
    document.querySelector('.page-sub').textContent = 'Aggiorna i dettagli dell\'auto avvistata.';
    document.querySelector('button[type="submit"]').textContent = 'Salva modifiche';
    const loaded = await loadSpotForEdit(
      editId,
      form,
      document.getElementById('imagePreview'),
      document.getElementById('dropzone')
    );
    if (!loaded) return;
  }

  initNewSpotForm(editId);
});
