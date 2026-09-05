/**
 * form.js
 * Gestisce la pagina "Nuovo avvistamento": anteprima immagine con drag & drop
 * e invio dei dati (multipart/form-data, perché include un file).
 * Attivo solo su nuovo.html.
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

function initNewSpotForm() {
  const form = document.getElementById('newSpotForm');
  if (!form) return;

  const msg = document.getElementById('newSpotMsg');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const fileInput = document.getElementById('immagine');
    if (!fileInput.files[0]) {
      showFormMessage(msg, 'Carica una foto dell\'auto avvistata.', 'error');
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    try {

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
        body: formData // niente header Content-Type: lo imposta il browser con il boundary corretto
      });

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
  loadCategorieForForm();
  initNewSpotForm();
});
