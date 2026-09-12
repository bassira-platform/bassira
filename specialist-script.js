document.addEventListener('DOMContentLoaded', () => {

  // --- 1. إدارة التنقل بين التبويبات وتفعيل الجلب الديناميكي ---
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

      // جلب البيانات بحسب التبويب النشط
      if (targetTab === 'appointments-section') {
        loadAppointments();
      } else if (targetTab === 'medical-files-section') {
        loadMedicalFiles();
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
    fetch('get_specialist_profile.php')
      .then(res => res.json())
      .then(res => {
        if (res.status === 'success') {
          const data = res.data;
          const specialties = res.specialties || [];
          
          const fullNameInput = document.getElementById('specFullName');
          const emailInput = document.getElementById('specEmail');
          const specialtySelect = document.getElementById('specSpecialty');
          const phoneInput = document.getElementById('specPhone');
          const clinicAddressInput = document.getElementById('specClinicAddress');
          const avatarPreview = document.getElementById('specialistAvatarPreview');

          if (fullNameInput) fullNameInput.value = data.full_name || '';
          if (emailInput) emailInput.value = data.email || '';
          if (phoneInput) phoneInput.value = data.phone || '';
          if (clinicAddressInput) clinicAddressInput.value = data.clinic_address || '';
          if (avatarPreview && data.avatar_url) avatarPreview.src = data.avatar_url;

          if (specialtySelect) {
            specialtySelect.innerHTML = '<option value="" disabled>اختر التخصص...</option>';
            specialties.forEach(spec => {
              const option = document.createElement('option');
              option.value = spec;
              option.textContent = spec;
              if (spec === data.specialist_type) {
                option.selected = true;
              }
              specialtySelect.appendChild(option);
            });
          }
        }
      })
      .catch(err => console.error('خطأ في جلب بيانات الأخصائي:', err));
  }

  // --- 5. حفظ التغييرات للملف المهني ---
  const specialistProfileForm = document.getElementById('specialistProfileForm');
  if (specialistProfileForm) {
    specialistProfileForm.addEventListener('submit', async (e) => {
      e.preventDefault();

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

        if (result.status === 'success') {
          alert('✅ ' + result.message);
          closeModal('specialistProfileModal');
          loadSpecialistProfile();
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

  // --- 6. معاينة صورة الملف الشخصي ---
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

  // --- 9. ربط مستمعات الأحداث للنوافذ المنبثقة للنوافل الخاصة بالمواعيد ---
  document.getElementById('closeAcceptModal')?.addEventListener('click', () => {
    document.getElementById('acceptAppointmentModal').classList.add('hidden');
  });

  document.getElementById('closeRejectModal')?.addEventListener('click', () => {
    document.getElementById('rejectAppointmentModal').classList.add('hidden');
  });

  // نموذج إرسال القبول
  document.getElementById('acceptAppointmentForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('status', 'ACCEPTED');

    try {
      const res = await fetch('update_appointment_status.php', { method: 'POST', body: formData });
      const result = await res.json();

      if (result.status === 'success') {
        alert('✅ ' + result.message);
        document.getElementById('acceptAppointmentModal').classList.add('hidden');
        loadAppointments();
      } else {
        alert('❌ ' + result.message);
      }
    } catch (err) {
      alert('❌ حدث خطأ في الاتصال بالسيرفر.');
    }
  });

  // نموذج إرسال الرفض
  document.getElementById('rejectAppointmentForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('status', 'REJECTED');

    try {
      const res = await fetch('update_appointment_status.php', { method: 'POST', body: formData });
      const result = await res.json();

      if (result.status === 'success') {
        alert('✅ ' + result.message);
        document.getElementById('rejectAppointmentModal').classList.add('hidden');
        loadAppointments();
      } else {
        alert('❌ ' + result.message);
      }
    } catch (err) {
      alert('❌ حدث خطأ في الاتصال بالسيرفر.');
    }
  });

  // --- 10. الاستدعاء الأولي عند تحميل الصفحة ---
  loadSpecialistProfile();
  loadAppointments();

  // ربط فلتر اختيار الطفل
  document.getElementById('filterChildSelect')?.addEventListener('change', (e) => {
    loadMedicalFiles(e.target.value);
  });

});

// ==========================================
// الدوال الخارجية والتحكم بالحالات الحركية (Global Functions)
// ==========================================

// جلب طلبات المواعيد وتصميم البطاقات بالحالات المتعددة
async function loadAppointments() {
  const container = document.getElementById('appointmentsContainer');
  if (!container) return;

  try {
    const res = await fetch('get_specialist_appointments.php');
    const result = await res.json();

    if (result.status === 'success' && result.data.length > 0) {
      container.innerHTML = '';
      result.data.forEach(item => {
        const div = document.createElement('div');
        div.className = 'appointment-card';
        div.style.padding = '15px';
        div.style.marginBottom = '15px';
        div.style.borderRadius = '8px';
        div.style.border = '1px solid #e0e0e0';
        div.style.backgroundColor = '#ffffff';

        let statusHtml = '';
        // جلب قيمة التاريخ والوقت الصحيحة بغض النظر عن الاسم القادم من الاستعلام
        const appDate = item.appointment_date || item.booking_date || '';
        const appTime = item.appointment_time || item.booking_time || '';
        const dateStr = appDate ? appDate : 'غير محدد';
        const timeStr = appTime ? appTime : 'غير محدد';

        if (item.status === 'PENDING') {
          statusHtml = `
            <p><strong>الحالة:</strong> <span style="color: #e67e22; font-weight: bold;">قيد الانتظار ⏳</span></p>
            <div class="action-buttons" style="margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap;">
              <button class="btn-primary" onclick="openAcceptModal(${item.id}, '${appDate}', '${appTime}')" style="background-color: #28a745; width: auto; padding: 6px 14px;">
                <i class="fas fa-check"></i> قبول وتحديد موعد
              </button>
              <button class="btn-primary" onclick="changeStatusDirectly(${item.id}, 'WAITLIST')" style="background-color: #f39c12; width: auto; padding: 6px 14px;">
                <i class="fas fa-clock"></i> قائمة الانتظار
              </button>
              <button class="btn-primary" onclick="openRejectModal(${item.id})" style="background-color: #dc3545; width: auto; padding: 6px 14px;">
                <i class="fas fa-times"></i> رفض
              </button>
            </div>
          `;
        } else if (item.status === 'WAITLIST') {
          statusHtml = `
            <p><strong>الحالة:</strong> <span style="color: #f39c12; font-weight: bold;">في قائمة الانتظار ⏸</span></p>
            <div class="action-buttons" style="margin-top: 10px; display: flex; gap: 8px;">
              <button class="btn-primary" onclick="openAcceptModal(${item.id}, '${appDate}', '${appTime}')" style="background-color: #28a745; width: auto; padding: 6px 14px;">
                <i class="fas fa-check"></i> قبول الموعد الآن
              </button>
              <button class="btn-primary" onclick="openRejectModal(${item.id})" style="background-color: #dc3545; width: auto; padding: 6px 14px;">
                <i class="fas fa-times"></i> رفض
              </button>
            </div>
          `;
        } else if (item.status === 'ACCEPTED') {
          statusHtml = `
            <p><strong>الحالة:</strong> <span style="color: #27ae60; font-weight: bold;">مقبول ومحدد ✔</span></p>
            <div class="action-buttons" style="margin-top: 10px;">
              <button class="btn-primary" onclick="changeStatusDirectly(${item.id}, 'ATTENDED')" style="background-color: #007bff; width: auto; padding: 6px 14px;">
                <i class="fas fa-user-check"></i> تم إنجاز الموعد وحضوره
              </button>
            </div>
          `;
        } else if (item.status === 'REJECTED') {
          statusHtml = `<p><strong>الحالة:</strong> <span style="color: #c0392b; font-weight: bold;">مرفوض ✖</span></p>`;
        }

        div.innerHTML = `
          <h4><i class="fas fa-child"></i> الطفل: ${item.child_name}</h4>
          <p><strong>ولي الأمر:</strong> ${item.parent_name} (${item.parent_phone})</p>
          <p><strong>التاريخ والوقت:</strong> ${dateStr} | ${timeStr}</p>
          <p><strong>الملاحظات:</strong> ${item.notes || 'لا يوجد'}</p>
          ${statusHtml}
        `;
        container.appendChild(div);
      });
    } else {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon"><i class="fas fa-calendar-times"></i></div>
          <h3>لا توجد مواعيد محجوزة حالياً</h3>
        </div>`;
    }
  } catch (err) {
    console.error('خطأ جلب المواعيد:', err);
  }
}

// فتح نافذة القبول
function openAcceptModal(appointmentId, date, time) {
  document.getElementById('acceptAppointmentId').value = appointmentId;
  document.getElementById('modalBookingDate').value = date || '';
  document.getElementById('modalBookingTime').value = time || '';
  document.getElementById('acceptAppointmentModal').classList.remove('hidden');
}

// فتح نافذة الرفض
function openRejectModal(appointmentId) {
  document.getElementById('rejectAppointmentId').value = appointmentId;
  document.getElementById('rejectionReason').value = '';
  document.getElementById('rejectAppointmentModal').classList.remove('hidden');
}

// تغيير الحالة المباشر (مثل قائمة الانتظار أو تم الحضور)
async function changeStatusDirectly(appointmentId, status) {
  const formData = new FormData();
  formData.append('appointment_id', appointmentId);
  formData.append('status', status);

  try {
    const res = await fetch('update_appointment_status.php', { method: 'POST', body: formData });
    const result = await res.json();

    if (result.status === 'success') {
      alert('✅ ' + result.message);
      loadAppointments();
    } else {
      alert('❌ ' + result.message);
    }
  } catch (err) {
    alert('❌ حدث خطأ في الاتصال بالسيرفر.');
  }
}

// جلب المستندات والتقارير الطبية
async function loadMedicalFiles(childId = 'all') {
  const container = document.getElementById('medicalFilesContainer');
  if (!container) return;

  try {
    const res = await fetch(`get_specialist_files.php?child_id=${childId}`);
    const result = await res.json();

    if (result.status === 'success' && result.data.length > 0) {
      container.innerHTML = '';
      result.data.forEach(file => {
        const div = document.createElement('div');
        div.className = 'file-card';
        div.setAttribute('data-file', file.file_path);
        div.setAttribute('data-type', file.file_type);

        div.innerHTML = `
          <div class="file-icon"><i class="fas ${file.file_type === 'pdf' ? 'fa-file-pdf' : 'fa-file-image'}"></i></div>
          <h4>${file.file_title}</h4>
          <p>الطفل: ${file.child_name}</p>
          <button class="btn-primary view-btn"><i class="fas fa-eye"></i> معاينة المستند</button>
        `;
        container.appendChild(div);
      });
    } else {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
          <h3>لا توجد ملفات مرفوعة لهذا الطفل</h3>
        </div>`;
    }
  } catch (err) {
    console.error('خطأ جلب الملفات:', err);
  }
}