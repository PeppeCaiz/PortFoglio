/**
 * form.js
 * Gestisce i form di creazione e modifica: anteprima immagine con drag & drop
 * e invio dei dati al relativo endpoint.
 */

function initDropzone() {
  const dropzone = document.getElementById('dropzone');
  const fileInput = document.getElementById('immagine');
  const preview = document.getElementById('imagePreview');
  const previewImg = preview.querySelector('img');
  const removeBtn = preview.querySelector('.remove-image');

  function showPreview(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      previewImg.src = e.target.result;
      preview.style.display = 'block';
      dropzone.style.display = 'none';
    };
    reader.readAsDataURL(file);
  }

  dropzone.addEventListener('click', () => fileInput.click());

  fileInput.addEventListener('change', () => {
    showPreview(fileInput.files[0]);
  });

  ['dragenter', 'dragover'].forEach(evt => {
    dropzone.addEventListener(evt, (e) => {
      e.preventDefault();
      dropzone.classList.add('dragover');
    });
  });

  ['dragleave', 'drop'].forEach(evt => {
    dropzone.addEventListener(evt, (e) => {
      e.preventDefault();
      dropzone.classList.remove('dragover');
    });
  });

  dropzone.addEventListener('drop', (e) => {
    const file = e.dataTransfer.files[0];
    if (file) {
      fileInput.files = e.dataTransfer.files;
      showPreview(file);
    }
  });

  removeBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    fileInput.value = '';
    preview.style.display = 'none';
    dropzone.style.display = 'block';
  });
}

async function loadSpotForEdit(id, form, preview, dropzone) {
  const spot = await apiRequest(`/avvistamenti/get.php?id=${(id)}`);
  if (!spot) return false;

  ['marca', 'modello', 'anno', 'categoria', 'luogo', 'targa', 'data_avvistamento', 'descrizione']
    .forEach(field => {
      if (spot[field] !== null && spot[field] !== undefined) {
        form[field].value = spot[field];
      }
    });

  if (spot.immagine) {
    preview.querySelector('img').src = spot.immagine;
    preview.style.display = 'block';
    dropzone.style.display = 'none';
  }

  return true;
}

function initNewSpotForm(editId = null) {
  const form = document.getElementById('newSpotForm');
  if (!form) return;

  const msg = document.getElementById('newSpotMsg');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const fileInput = document.getElementById('immagine');
    if (!editId && !fileInput.files[0]) {
      showFormMessage(msg, 'Carica una foto dell\'auto avvistata.', 'error');
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    try {

      if (editId) {
        await apiRequest('/avvistamenti/update.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: editId,
            marca: form.marca.value.trim(),
            modello: form.modello.value.trim(),
            anno: form.anno.value || null,
            categoria: form.categoria.value,
            luogo: form.luogo.value.trim(),
            targa: form.targa.value.trim(),
            data_avvistamento: form.data_avvistamento.value,
            descrizione: form.descrizione.value.trim()
          })
        });
      } else {
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

async function loadCategorieForForm() {
  const select = document.getElementById('categoria');
  if (!select) return;
  try {
    const categorie = await apiRequest('/categorie/list.php');
    categorie.forEach(cat => {
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
  if (!form) return; // non siamo su nuovo.html

  const allowed = await requireAuth();
  if (!allowed) return;

  initDropzone();
  await loadCategorieForForm();

  const editId = new URLSearchParams(window.location.search).get('id');
  if (editId) {
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
