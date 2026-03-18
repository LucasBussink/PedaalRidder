const repairSelect = document.getElementById('type_repair');
const overigExtra = document.getElementById('overig-extra');
const repairPhoto = document.getElementById('repair_photo');
const repairPhotoButton = document.getElementById('repair_photo_button');
const repairPhotoName = document.getElementById('repair_photo_name');

function toggleOverigExtra() {
  if (!repairSelect || !overigExtra || !repairPhoto) {
    return;
  }

  const selectedOption = repairSelect.options[repairSelect.selectedIndex];
  const value = (repairSelect.value || '').toLowerCase();
  const text = (selectedOption ? selectedOption.text : '').toLowerCase();
  const isOverig = value === 'overig' || text === 'overig';

  overigExtra.style.display = isOverig ? 'block' : 'none';
  repairPhoto.required = isOverig;

  if (!isOverig) {
    repairPhoto.value = '';
    if (repairPhotoName) {
      repairPhotoName.textContent = 'Nog geen bestand gekozen';
    }
  }
}

if (repairSelect) {
  repairSelect.addEventListener('change', toggleOverigExtra);
}

if (repairPhotoButton && repairPhoto) {
  repairPhotoButton.addEventListener('click', function () {
    repairPhoto.click();
  });

  repairPhoto.addEventListener('change', function () {
    if (!repairPhotoName) {
      return;
    }

    repairPhotoName.textContent = repairPhoto.files.length > 0
      ? repairPhoto.files[0].name
      : 'Nog geen bestand gekozen';
  });
}

toggleOverigExtra();
