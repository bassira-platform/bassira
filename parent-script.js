// ==========================================
// دالة مساعدة لحماية المدخلات من ثغرات XSS (نطاق عام Global)
// ==========================================
function escapeHtml(text) {
  if (!text) return '';
  return String(text)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

document.addEventListener('DOMContentLoaded', () => {

  // ==========================================
  // 0. التحقق الاستباقي والآمن من صلاحية الجلسة
  // ==========================================
  async function checkAuthSession() {
    try {
      const res = await fetch('check_session.php');
      
      if (!res.ok) {
        handleLogoutRedirect();
        return false;
      }

      const result = await res.json();
      
      const isLoggedIn = result.logged_in === true || result.status === 'success';
      if (!isLoggedIn) {
        handleLogoutRedirect();
        return false;
      }
      return true;
    } catch (err) {
      console.warn('تعذر التحقق من الجلسة:', err);
    }
  }

  function handleLogoutRedirect() {
    localStorage.clear();
    sessionStorage.clear();
    window.location.replace('login.html');
  }

  // تشغيل فحص الجلسة فور تحميل الصفحة
  checkAuthSession();

  // ==========================================
  // 1. القائمة المنسدلة للمستخدم
  // ==========================================
  const userMenuBtn = document.getElementById('userMenuBtn');
  const userDropdown = document.getElementById('userDropdown');

  if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      userDropdown.classList.toggle('hidden');
    });
  }

  // إغلاق جميع القوائم المنسدلة عند النقر خارجها
  document.addEventListener('click', () => {
    if (userDropdown) userDropdown.classList.add('hidden');
    
    // إغلاق منافذ خيارات البطاقات
    document.querySelectorAll('[id^="cardOptions-"]').forEach(el => el.classList.add('hidden'));
  });

  // فتح نافذة الملف الشخصي
  const profileLink = document.getElementById('openProfileBtn') || document.querySelector('a[href="#profile"]');
  const parentProfileModal = document.getElementById('parentProfileModal');
  const closeParentProfileModal = document.getElementById('closeParentProfileModal');

  if (profileLink) {
    profileLink.addEventListener('click', (e) => {
      e.preventDefault();
      if (userDropdown) userDropdown.classList.add('hidden');
      openParentProfile();
    });
  }

  if (closeParentProfileModal && parentProfileModal) {
    closeParentProfileModal.onclick = () => parentProfileModal.classList.add('hidden');
  }

  // معاينة الصورة الشخصية
  const parentAvatarInput = document.getElementById('parentAvatarInput');
  if (parentAvatarInput) {
    parentAvatarInput.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const preview = document.getElementById('parentAvatarPreview');
          if (preview) preview.src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // حفظ تعديلات الملف الشخصي
  const parentProfileForm = document.getElementById('parentProfileForm');
  if (parentProfileForm) {
    parentProfileForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      const formData = new FormData(this);

      try {
        const res = await fetch('update_parent_profile.php', {
          method: 'POST',
          body: formData
        });

        if (res.status === 401) { handleLogoutRedirect(); return; }
        if (!res.ok) throw new Error('خطأ استجابة السيرفر');

        const result = await res.json();

        if (result.status === 'success') {
          alert('✅ تم تحديث الملف الشخصي بنجاح');
          if (parentProfileModal) parentProfileModal.classList.add('hidden');
        } else {
          alert('⚠️ ' + (result.message || 'حدث خطأ أثناء التحديث'));
        }
      } catch (err) {
        alert('⚠️ تعذر حفظ البيانات، يرجى المحاولة لاحقاً');
      }
    });
  }

  // ==========================================
  // 2. التحكم بالتبويبات
  // ==========================================
  const tabBtns = document.querySelectorAll('.tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabBtns.forEach(b => b.classList.remove('active'));
      tabContents.forEach(c => c.classList.add('hidden'));

      btn.classList.add('active');
      const targetTab = document.getElementById(btn.dataset.tab);
      if (targetTab) targetTab.classList.remove('hidden');

      // تشغيل جلب الأخصائيين فور الانتقال لتبويب المواعيد
      if (btn.dataset.tab === 'appointments-section') {
        loadSpecialists();
      }
    });
  });

  // ==========================================
  // 3. أزرار إغلاق وفتح Modals
  // ==========================================
  const addChildModal = document.getElementById('addChildModal');
  const editChildModal = document.getElementById('editChildModal');
  const healthRecordModal = document.getElementById('healthRecordModal');

  const openAddBtn = document.getElementById('openAddChildModal');
  if (openAddBtn && addChildModal) openAddBtn.onclick = () => addChildModal.classList.remove('hidden');

  const closeAddBtn = document.getElementById('closeAddChildModal');
  if (closeAddBtn && addChildModal) closeAddBtn.onclick = () => addChildModal.classList.add('hidden');

  const closeEditBtn = document.getElementById('closeEditChildModal');
  if (closeEditBtn && editChildModal) closeEditBtn.onclick = () => editChildModal.classList.add('hidden');

  const closeHealthBtn = document.getElementById('closeHealthModal');
  if (closeHealthBtn && healthRecordModal) closeHealthBtn.onclick = () => healthRecordModal.classList.add('hidden');

  const closePwdBtn = document.getElementById('closeEditPasswordModal');
  if (closePwdBtn) {
    closePwdBtn.onclick = () => {
      const pwdModal = document.getElementById('editPasswordModal');
      if (pwdModal) pwdModal.classList.add('hidden');
    };
  }

  // ==========================================
  // 4. نموذج إضافة طفل
  // ==========================================
  const addChildForm = document.getElementById('addChildForm');
  if (addChildForm) {
    addChildForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      try {
        const res = await fetch('add_child.php', { method: 'POST', body: new FormData(this) });
        if (res.status === 401) { handleLogoutRedirect(); return; }
        if (!res.ok) throw new Error('خطأ استجابة السيرفر');

        const result = await res.json();
        if (result.status === 'success') {
          alert(`✅ تم إدراج الطفل بنجاح!\nرمز الـ UID: ${result.uid || ''}`);
          if (addChildModal) addChildModal.classList.add('hidden');
          this.reset();
          loadChildrenCards();
        } else {
          alert('⚠️ ' + result.message);
        }
      } catch (err) {
        alert('⚠️ تعذر الاتصال بالسيرفر');
      }
    });
  }

  // ==========================================
  // 5. نموذج تعديل بيانات الطفل
  // ==========================================
  const editChildForm = document.getElementById('editChildForm');
  if (editChildForm) {
    editChildForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      try {
        const res = await fetch('update_child.php', { method: 'POST', body: new FormData(this) });
        if (res.status === 401) { handleLogoutRedirect(); return; }
        if (!res.ok) throw new Error('خطأ استجابة السيرفر');

        const result = await res.json();
        if (result.status === 'success') {
          alert('✅ تم تحديث بيانات الطفل بنجاح');
          if (editChildModal) editChildModal.classList.add('hidden');
          loadChildrenCards();
        } else {
          alert('⚠️ ' + result.message);
        }
      } catch (err) {
        alert('⚠️ تعذر الاتصال بالسيرفر');
      }
    });
  }

  // ==========================================
  // 6. نموذج حفظ الملف الصحي
  // ==========================================
  const healthRecordForm = document.getElementById('healthRecordForm');
  if (healthRecordForm) {
    healthRecordForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      try {
        const res = await fetch('save_health_record.php', { method: 'POST', body: new FormData(this) });
        if (res.status === 401) { handleLogoutRedirect(); return; }
        if (!res.ok) throw new Error('خطأ استجابة السيرفر');

        const result = await res.json();
        if (result.status === 'success') {
          alert('✅ تم حفظ الملف الصحي بنجاح');
          if (healthRecordModal) healthRecordModal.classList.add('hidden');
        } else {
          alert('⚠️ ' + result.message);
        }
      } catch (err) {
        alert('⚠️ تعذر حفظ الملف الصحي');
      }
    });
  }

  // ==========================================
  // 7. نموذج تعديل كلمة المرور
  // ==========================================
  const editPasswordForm = document.getElementById('editPasswordForm');
  if (editPasswordForm) {
    editPasswordForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      try {
        const res = await fetch('update_child_password.php', { method: 'POST', body: new FormData(this) });
        if (res.status === 401) { handleLogoutRedirect(); return; }
        if (!res.ok) throw new Error('خطأ استجابة السيرفر');

        const result = await res.json();
        if (result.status === 'success') {
          alert('✅ تم تغيير كلمة المرور بنجاح');
          const pwdModal = document.getElementById('editPasswordModal');
          if (pwdModal) pwdModal.classList.add('hidden');
          this.reset();
        } else {
          alert('⚠️ ' + result.message);
        }
      } catch (err) {
        alert('⚠️ تعذر الاتصال بالسيرفر');
      }
    });
  }

  // ==========================================
  // 8. جلب وعرض البطاقات
  // ==========================================
  async function loadChildrenCards() {
    const container = document.getElementById('childrenContainer');
    if (!container) return;

    try {
      const res = await fetch('get_children.php');
      if (res.status === 401) { handleLogoutRedirect(); return; }
      if (!res.ok) throw new Error('خطأ أثناء التحميل');

      const responseData = await res.json();
      const children = responseData.data ? responseData.data : (Array.isArray(responseData) ? responseData : []);

      if (children.length === 0) {
        container.innerHTML = `
          <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-baby-carriage"></i></div>
            <h3>لا يوجد أطفال مسجلون حالياً</h3>
            <p>قم بإضافة طفل جديد لإنشاء حساب وتحديث الملف الصحي وتتبع المواعيد.</p>
            <button class="btn-add-child-large" onclick="document.getElementById('addChildModal').classList.remove('hidden')">
              <i class="fas fa-plus"></i> إضافة طفل جديد
            </button>
          </div>`;
        return;
      }

      container.innerHTML = '';
      children.forEach(child => {
        const card = document.createElement('div');
        card.className = 'child-card';
        const isMale = String(child.gender).toUpperCase() === 'MALE' || String(child.gender).toLowerCase() === 'male';

        const safeFullName = escapeHtml(child.full_name);
        const safeBirthDate = escapeHtml(child.birth_date);
        const safeUid = escapeHtml(child.uid);

        card.innerHTML = `
          <div class="card-header">
            <div class="child-avatar"><i class="fas fa-child"></i></div>
            <div class="card-menu-wrapper">
              <button class="card-settings-btn" onclick="toggleCardOptions(event, '${child.id}')">
                <i class="fas fa-cog"></i>
              </button>
              <div id="cardOptions-${child.id}" class="dropdown-content hidden">
                <a href="javascript:void(0)" onclick="openHealthModal('${child.id}')" class="dropdown-item"><i class="fas fa-notes-medical"></i> الملف الصحي</a>
                <a href="javascript:void(0)" onclick="editPassword('${child.id}')" class="dropdown-item"><i class="fas fa-key"></i> تعديل كلمة المرور</a>
                <a href="javascript:void(0)" onclick="editInfo('${child.id}')" class="dropdown-item"><i class="fas fa-user-edit"></i> المعلومات الشخصية</a>
                <hr style="margin: 4px 0; border: none; border-top: 1px solid var(--border);">
                <a href="javascript:void(0)" onclick="deleteChild('${child.id}', '${safeFullName}')" class="dropdown-item logout-link"><i class="fas fa-trash-alt"></i> حذف حساب الطفل</a>
              </div>
            </div>
          </div>
          <div class="card-body">
            <h4>${safeFullName}</h4>
            <p>تاريخ الميلاد: ${safeBirthDate}</p>
            <p>الجنس: ${isMale ? 'ذكر' : 'أنثى'}</p>
            ${safeUid ? `<p>UID: <span class="uid-badge">${safeUid}</span></p>` : ''}
          </div>
        `;
        container.appendChild(card);
      });
    } catch (e) {
      console.error('خطأ أثناء تحميل البطاقات:', e);
    }
  }

  window.loadChildrenCards = loadChildrenCards;
  loadChildrenCards();

  // ربط عناصر الفلترة بعد تحميل العناصر
  ['filterSpecialty', 'filterAddress', 'filterSort'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', loadSpecialists);
  });
});

// ==========================================
// الدوال العامة المتاحة للنقر المباشر (Global)
// ==========================================

function toggleCardOptions(event, childId) {
  if (event) event.stopPropagation();
  
  document.querySelectorAll('[id^="cardOptions-"]').forEach(el => {
    if (el.id !== `cardOptions-${childId}`) el.classList.add('hidden');
  });

  const menu = document.getElementById(`cardOptions-${childId}`);
  if (menu) menu.classList.toggle('hidden');
}

async function openHealthModal(childId) {
  const healthInput = document.getElementById('healthChildId');
  const healthModal = document.getElementById('healthRecordModal');
  const form = document.getElementById('healthRecordForm');

  if (form) form.reset();
  if (healthInput) healthInput.value = childId;

  const menu = document.getElementById(`cardOptions-${childId}`);
  if (menu) menu.classList.add('hidden');

  try {
    const res = await fetch(`get_health_record.php?child_id=${childId}`);
    if (!res.ok) throw new Error('خطأ في الاتصال');

    const result = await res.json();

    if (result.status === 'success' && result.data) {
      if (document.getElementById('bloodType')) document.getElementById('bloodType').value = result.data.blood_type || '';
      if (document.getElementById('allergies')) document.getElementById('allergies').value = result.data.allergies || '';
      if (document.getElementById('medicalConditions')) document.getElementById('medicalConditions').value = result.data.medical_conditions || '';
    }
  } catch (err) {
    console.warn('تعذر جلب بيانات ملف قديمة، فتح ملف فارغ:', err);
  }

  if (healthModal) healthModal.classList.remove('hidden');
}

async function editInfo(childId) {
  const menu = document.getElementById(`cardOptions-${childId}`);
  if (menu) menu.classList.add('hidden');

  try {
    const res = await fetch(`get_child_details.php?id=${childId}`);
    if (!res.ok) throw new Error('خطأ في السيرفر');

    const result = await res.json();

    if (result.status === 'success' && result.data) {
      const child = result.data;
      if (document.getElementById('editChildId')) document.getElementById('editChildId').value = child.id;
      if (document.getElementById('editChildName')) document.getElementById('editChildName').value = child.full_name;
      if (document.getElementById('editBirthDate')) document.getElementById('editBirthDate').value = child.birth_date;
      if (document.getElementById('editGender')) document.getElementById('editGender').value = String(child.gender).toUpperCase();
      document.getElementById('editChildModal').classList.remove('hidden');
    } else {
      alert('⚠️ ' + (result.message || 'تعذر جلب البيانات'));
    }
  } catch (err) {
    alert('⚠️ تعذر جلب بيانات الطفل');
  }
}

function editPassword(childId) {
  const menu = document.getElementById(`cardOptions-${childId}`);
  if (menu) menu.classList.add('hidden');

  const pwdForm = document.getElementById('editPasswordForm');
  if (pwdForm) pwdForm.reset();

  const pwdInput = document.getElementById('passwordChildId');
  const pwdModal = document.getElementById('editPasswordModal');
  
  if (pwdInput) pwdInput.value = childId;
  if (pwdModal) pwdModal.classList.remove('hidden');
}

async function deleteChild(childId, childName) {
  const menu = document.getElementById(`cardOptions-${childId}`);
  if (menu) menu.classList.add('hidden');

  const confirmDelete = confirm(`⚠️ هل أنت متأكد من حذف حساب الطفل "${childName}" نهائياً؟\n\nتنبيه: لا يمكن التراجع عن هذه الخطوة وستفقد جميع البيانات الصحية والتحديثات.`);

  if (!confirmDelete) return;

  const formData = new FormData();
  formData.append('child_id', childId);

  try {
    const res = await fetch('delete_child.php', {
      method: 'POST',
      body: formData
    });
    
    if (res.status === 401) { window.location.replace('login.html'); return; }
    if (!res.ok) throw new Error('خطأ استجابة السيرفر');

    const result = await res.json();

    if (result.status === 'success') {
      alert('✅ ' + result.message);
      if (typeof window.loadChildrenCards === 'function') {
        window.loadChildrenCards();
      }
    } else {
      alert('⚠️ ' + result.message);
    }
  } catch (err) {
    alert('⚠️ تعذر الاتصال بالسيرفر لإتمام عملية الحذف');
  }
}

async function openParentProfile() {
  const modal = document.getElementById('parentProfileModal');
  const uploads_path = 'uploads/';

  try {
    const res = await fetch('get_parent_profile.php');
    if (!res.ok) throw new Error(`خطأ في السيرفر: ${res.status}`);

    const result = await res.json();

    if (result.status === 'success' && result.data) {
      const data = result.data;
      
      if (document.getElementById('parentFullName')) document.getElementById('parentFullName').value = data.full_name || '';
      if (document.getElementById('parentEmail')) document.getElementById('parentEmail').value = data.email || '';
      if (document.getElementById('parentPhone')) document.getElementById('parentPhone').value = data.phone || '';
      if (document.getElementById('parentAddress')) document.getElementById('parentAddress').value = data.address || '';
      if (document.getElementById('parentNewPassword')) document.getElementById('parentNewPassword').value = '';

      const avatarImg = document.getElementById('parentAvatarPreview');
      if (avatarImg) {
        if (data.avatar && data.avatar.trim() !== '') {
          avatarImg.src = uploads_path + data.avatar;
        } else {
          avatarImg.src = 'default-avatar.png';
        }
      }

      if (modal) modal.classList.remove('hidden');
    } else {
      alert('⚠️ ' + (result.message || 'تعذر تحميل الملف الشخصي'));
    }
  } catch (err) {
    console.error('تفاصيل خطأ جلب الملف الشخصي:', err);
    alert('⚠️ تعذر جلب بيانات الملف الشخصي.');
  }
}

// ==========================================
// 9. جلب وعرض الأخصائيين المتاحين والفلاتر
// ==========================================
let filtersInitialized = false;

async function loadSpecialists() {
  const container = document.getElementById('specialistsContainer');
  if (!container) return;

  const specialty = document.getElementById('filterSpecialty')?.value || '';
  const address = document.getElementById('filterAddress')?.value || '';
  const sort = document.getElementById('filterSort')?.value || 'asc';

  const queryParams = new URLSearchParams({
    specialty: specialty,
    address: address,
    sort: sort
  });

  try {
    const res = await fetch(`get_specialists.php?${queryParams.toString()}`);
    if (res.status === 401) { window.location.replace('login.html'); return; }
    if (!res.ok) throw new Error('خطأ في الشبكة');

    const result = await res.json();

    if (result.status !== 'success') {
      container.innerHTML = `<p class="placeholder-text">${escapeHtml(result.message || 'حدث خطأ أثناء تحميل البيانات')}</p>`;
      return;
    }

    if (!filtersInitialized && result.filters) {
      populateFilterOptions('filterSpecialty', result.filters.specialties, 'value', 'label');
      populateFilterOptions('filterAddress', result.filters.addresses);
      filtersInitialized = true;
    }

    const specialists = result.specialists || [];

    if (specialists.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon"><i class="fas fa-user-md"></i></div>
          <h3>لا يوجد أخصائيون مطابقون للبحث</h3>
          <p>جرّب تغيير خيارات الفلترة أو البحث لنتائج أخرى.</p>
        </div>`;
      return;
    }

    container.innerHTML = '';
    specialists.forEach(spec => {
      const card = document.createElement('div');
      card.className = 'specialist-card';

      const avatarSrc = spec.avatar_url ? spec.avatar_url : 'assets/default-avatar.png';
      const specName = escapeHtml(spec.full_name);
      const specType = escapeHtml(spec.specialist_type_label || 'أخصائي معتمد');
      const specAddress = escapeHtml(spec.address);
      const specPhone = escapeHtml(spec.phone);

      card.innerHTML = `
        <div class="specialist-header">
          <img src="${avatarSrc}" alt="${specName}" class="specialist-avatar-img" onerror="this.src='assets/default-avatar.png'">
          <h4>${specName}</h4>
          <span class="specialty-badge">${specType}</span>
        </div>
        <div class="specialist-body">
          ${specAddress ? `<p><i class="fas fa-map-marker-alt"></i> ${specAddress}</p>` : ''}
          ${specPhone ? `<p><i class="fas fa-phone"></i> ${specPhone}</p>` : ''}
        </div>
        <button class="btn-primary btn-book" onclick="bookAppointment('${spec.id}', '${specName}')">
          <i class="fas fa-calendar-check"></i> حجز موعد
        </button>
      `;
      container.appendChild(card);
    });

  } catch (err) {
    console.error('خطأ جلب الأخصائيين:', err);
    container.innerHTML = `<p class="placeholder-text">تعذر تحميل قائمة الأخصائيين حالياً.</p>`;
  }
}

function populateFilterOptions(selectId, items, valueKey = null, labelKey = null) {
  const select = document.getElementById(selectId);
  if (!select || !Array.isArray(items)) return;

  items.forEach(item => {
    const option = document.createElement('option');
    if (typeof item === 'object') {
      option.value = item[valueKey];
      option.textContent = item[labelKey];
    } else {
      option.value = item;
      option.textContent = item;
    }
    select.appendChild(option);
  });
}

function bookAppointment(specialistId, specialistName) {
  alert(`سيتم فتح نافذة حجز موعد مع: ${specialistName}`);
}