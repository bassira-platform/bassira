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

  // --- 2. إدارة القائمة المنسدلة للمستخدم (User Dropdown Menu) ---
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

  // --- 3. وظائف فتح وإغلاق النوافذ المنبثقة (Modal Utility Functions) ---
  function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('hidden');
  }

  function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('hidden');
  }

  // ربط أزرار فتح وإغلاق الملف الشخصي للأخصائي
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

  // --- 4. جلب معلومات الأخصائي تلقائياً من السيرفر عند تحميل الصفحة ---
  function loadSpecialistProfile() {
    fetch('api.php?action=get_profile')
      .then(res => res.json())
      .then(res => {
        if (res.status === 'success') {
          const data = res.data;
          
          const fullNameInput = document.getElementById('specFullName');
          const specialtyInput = document.getElementById('specSpecialty');
          const phoneInput = document.getElementById('specPhone');
          const clinicAddressInput = document.getElementById('specClinicAddress');
          const avatarPreview = document.getElementById('specialistAvatarPreview');

          if (fullNameInput) fullNameInput.value = data.full_name || '';
          if (specialtyInput) specialtyInput.value = data.specialist_type || '';
          if (phoneInput) phoneInput.value = data.phone || '';
          if (clinicAddressInput) clinicAddressInput.value = data.address || '';
          if (avatarPreview && data.avatar) {
            avatarPreview.src = data.avatar;
          }
        }
      })
      .catch(err => console.error('خطأ في جلب بيانات الأخصائي:', err));
  }

  // --- 5. جلب التقارير والملفات الطبية المرفقة تلقائياً من قاعدة البيانات ---
  function loadMedicalFiles() {
    const container = document.getElementById('medicalFilesContainer');
    if (!container) return;

    fetch('api.php?action=get_medical_files')
      .then(res => res.json())
      .then(res => {
        if (res.status === 'success' && res.data.length > 0) {
          container.innerHTML = ''; // تفريغ العناصر الافتراضية

          res.data.forEach(file => {
            const isPdf = file.file_type === 'pdf';
            const iconClass = isPdf ? 'pdf' : 'image';
            const icon = isPdf ? 'fa-file-pdf' : 'fa-file-image';

            const cardHTML = `
              <div class="file-card" data-file="${file.file_path}" data-type="${file.file_type}">
                <div class="file-type-icon ${iconClass}">
                  <i class="fas ${icon}"></i>
                </div>
                <div class="file-details">
                  <h4>${file.file_title}</h4>
                  <p><strong>الطفل:</strong> ${file.child_name}</p>
                  <p><strong>ولي الأمر:</strong> ${file.parent_name}</p>
                  <span class="file-date"><i class="fas fa-calendar-day"></i> ${file.created_at.split(' ')[0]}</span>
                </div>
                <div class="file-actions">
                  <a href="#" class="btn-action view-btn" title="معاينة الملف"><i class="fas fa-eye"></i> معاينة</a>
                  <a href="${file.file_path}" download class="btn-action download-btn" title="تحميل الملف"><i class="fas fa-download"></i></a>
                </div>
              </div>
            `;
            container.insertAdjacentHTML('beforeend', cardHTML);
          });
        } else {
          container.innerHTML = `
            <div class="empty-state">
              <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
              <h3>لا توجد ملفات مرفقة حالياً</h3>
              <p>ستظهر هنا المستندات والتقارير المرفقة تلقائياً من أولياء الأمور عند طلب الكشف.</p>
            </div>`;
        }
      })
      .catch(err => console.error('خطأ في جلب الملفات الطبية:', err));
  }

  // --- 6. معاينة التقارير والملفات الطبية (PDF / Images) ---
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
      
      // إدراج الملف داخل الحاوية حسب نوعه (PDF أو الصورة)
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

  // --- 7. معاينة صورة الملف الشخصي للأخصائي عند الاختيار ---
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

  // --- 8. إغلاق أية نافذة منبثقة عند الضغط خارج صندوق المحتوى ---
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
  loadMedicalFiles();

});