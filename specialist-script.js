document.addEventListener('DOMContentLoaded', () => {

  // --- 1. إدارة التنقل بين التبويبات (Tabs Navigation) ---
  const tabButtons = document.querySelectorAll('.tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');

  tabButtons.forEach(button => {
    button.addEventListener('click', () => {
      const targetTab = button.getAttribute('data-tab');

      tabButtons.forEach(btn => btn.classList.remove('active'));
      tabContents.forEach(content => content.classList.add('hidden'));

      button.classList.add('active');
      const targetElement = document.getElementById(targetTab);
      if (targetElement) {
        targetElement.classList.remove('hidden');
      }
    });
  });

  // --- 2. إدارة القائمة المنسدلة للمستخدم ---
  const userMenuBtn = document.getElementById('userMenuBtn');
  const userDropdown = document.getElementById('userDropdown');

  if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      userDropdown.classList.toggle('hidden');
    });

    document.addEventListener('click', () => {
      userDropdown.classList.add('hidden');
    });
  }

  // --- 3. وظائف فتح وإغلاق النوافذ المنبثقة ---
  function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('hidden');
  }

  function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('hidden');
  }

  const openSpecialistProfileBtn = document.getElementById('openSpecialistProfileBtn');
  const closeSpecialistProfileModal = document.getElementById('closeSpecialistProfileModal');

  if (openSpecialistProfileBtn) {
    openSpecialistProfileBtn.addEventListener('click', (e) => {
      e.preventDefault();
      openModal('specialistProfileModal');
    });
  }

  if (closeSpecialistProfileModal) {
    closeSpecialistProfileModal.addEventListener('click', () => {
      closeModal('specialistProfileModal');
    });
  }

  // --- 4. جلب معلومات الأخصائي عند تحميل الصفحة ---
  function loadSpecialistProfile() {
    fetch('api.php?action=get_profile')
      .then(res => res.json())
      .then(res => {
        if (res.status === 'success' || res.success) {
          const data = res.data || res;
          
          const fullNameInput = document.getElementById('specFullName');
          const specialtyInput = document.getElementById('specSpecialty');
          const phoneInput = document.getElementById('specPhone');
          const clinicAddressInput = document.getElementById('specClinicAddress');
          const avatarPreview = document.getElementById('specialistAvatarPreview');

          if (fullNameInput) fullNameInput.value = data.full_name || '';
          if (specialtyInput) specialtyInput.value = data.specialist_type || data.specialty || '';
          if (phoneInput) phoneInput.value = data.phone || '';
          if (clinicAddressInput) clinicAddressInput.value = data.clinic_address || data.address || '';
          if (avatarPreview && data.avatar) {
            avatarPreview.src = data.avatar;
          }
        }
      })
      .catch(err => console.error('خطأ في جلب بيانات الأخصائي:', err));
  }

  // --- 5. حفظ التغييرات للملف المهني (POST via AJAX) ---
  const specialistProfileForm = document.getElementById('specialistProfileForm');
  if (specialistProfileForm) {
    specialistProfileForm.addEventListener('submit', async (e) => {
      e.preventDefault(); // منع إرسال النموذج واستدعاء رابط GET

      const formData = new FormData(specialistProfileForm);
      const submitBtn = specialistProfileForm.querySelector('button[type="submit"]');
      
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'جاري حفظ التعديلات... ⏳';
      }

      try {
        const response = await fetch('update_specialist_profile.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success || result.status === 'success') {
          alert('✅ تم حفظ التغييرات المهنية بنجاح!');
          closeModal('specialistProfileModal');
          loadSpecialistProfile(); // إعادة تحميل البيانات المحدثة
        } else {
          alert('❌ ' + (result.message || 'تعذر حفظ التغييرات.'));
        }
      } catch (err) {
        console.error('خطأ الحفظ:', err);
        alert('❌ حدث خطأ أثناء الاتصال بالسيرفر لحفظ التغييرات.');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'حفظ التغييرات المهنية 💾';
        }
      }
    });
  }

  // --- 6. معاينة صورة الملف الشخصي عند الاختيار ---
  const specialistAvatarInput = document.getElementById('specialistAvatarInput');
  const specialistAvatarPreview = document.getElementById('specialistAvatarPreview');

  if (specialistAvatarInput && specialistAvatarPreview) {
    specialistAvatarInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
          specialistAvatarPreview.src = event.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // --- 7. معاينة التقارير والملفات الطبية ---
  const closeViewFileModal = document.getElementById('closeViewFileModal');
  const filePreviewContainer = document.getElementById('filePreviewContainer');
  const modalFileTitle = document.getElementById('modalFileTitle');

  document.addEventListener('click', (e) => {
    const viewBtn = e.target.closest('.view-btn');
    if (viewBtn) {
      e.preventDefault();
      
      const fileCard = viewBtn.closest('.file-card');
      const fileName = fileCard.querySelector('h4').textContent;
      const fileUrl = fileCard.getAttribute('data-file');
      const fileType = fileCard.getAttribute('data-type');

      if (modalFileTitle) {
        modalFileTitle.innerHTML = `<i class="fas fa-file-medical"></i> معاينة: ${fileName}`;
      }
      
      if (filePreviewContainer) {
        if (fileType === 'pdf') {
          filePreviewContainer.innerHTML = `<iframe src="${fileUrl}" width="100%" height="500px" style="border:none;"></iframe>`;
        } else {
          filePreviewContainer.innerHTML = `<img src="${fileUrl}" alt="${fileName}" style="max-width:100%; max-height:65vh; object-fit:contain; border-radius:8px; display:block; margin:auto;">`;
        }
      }

      openModal('viewFileModal');
    }
  });

  if (closeViewFileModal) {
    closeViewFileModal.addEventListener('click', () => {
      closeModal('viewFileModal');
      if (filePreviewContainer) filePreviewContainer.innerHTML = ''; 
    });
  }

  // --- 8. إغلاق النوافذ عند النقر خارجها ---
  window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
      e.target.classList.add('hidden');
      if (e.target.id === 'viewFileModal' && filePreviewContainer) {
        filePreviewContainer.innerHTML = '';
      }
    }
  });

  // تشغيل الاستعلامات الذاتية
  loadSpecialistProfile();
// جلب البيانات عند التحميل
function loadSpecialistProfile() {
  fetch('get_specialist_profile.php')
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success') {
        const data = res.data;
        if (document.getElementById('specFullName')) document.getElementById('specFullName').value = data.full_name || '';
        if (document.getElementById('specEmail')) document.getElementById('specEmail').value = data.email || '';
        if (document.getElementById('specSpecialty')) document.getElementById('specSpecialty').value = data.specialist_type || '';
        if (document.getElementById('specPhone')) document.getElementById('specPhone').value = data.phone || '';
        if (document.getElementById('specClinicAddress')) document.getElementById('specClinicAddress').value = data.clinic_address || '';
        if (document.getElementById('specialistAvatarPreview') && data.avatar_url) {
          document.getElementById('specialistAvatarPreview').src = data.avatar_url;
        }
      }
    })
    .catch(err => console.error('خطأ جلب البيانات:', err));
}

// حفظ التعديلات عند التقديم
const specialistProfileForm = document.getElementById('specialistProfileForm');
if (specialistProfileForm) {
  specialistProfileForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(specialistProfileForm);

    try {
      const response = await fetch('update_specialist_profile.php', {
        method: 'POST',
        body: formData
      });
      const result = await response.json();

      if (result.status === 'success') {
        alert('✅ ' + result.message);
        loadSpecialistProfile();
      } else {
        alert('❌ ' + result.message);
      }
    } catch (err) {
      alert('❌ حدث خطأ أثناء الاتصال بالخادم.');
    }
  });
}

});