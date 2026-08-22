document.addEventListener('DOMContentLoaded', () => {
  // 1. القائمة المنسدلة للمستخدم
  const userMenuBtn = document.getElementById('userMenuBtn');
  const userDropdown = document.getElementById('userDropdown');

  if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      userDropdown.classList.toggle('hidden');
    });
    document.addEventListener('click', () => userDropdown.classList.add('hidden'));
  }
// فتح نافذة الملف الشخصي عند النقر من القائمة المنسدلة
const profileLink = document.querySelector('a[href="#profile"]');
const parentProfileModal = document.getElementById('parentProfileModal');
const closeParentProfileModal = document.getElementById('closeParentProfileModal');

if (profileLink) {
  profileLink.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('userDropdown').classList.add('hidden');
    openParentProfile();
  });
}

if (closeParentProfileModal) {
  closeParentProfileModal.onclick = () => parentProfileModal.classList.add('hidden');
}

// معاينة الصورة الشخصية فور اختيارها
const parentAvatarInput = document.getElementById('parentAvatarInput');
if (parentAvatarInput) {
  parentAvatarInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('parentAvatarPreview').src = e.target.result;
      }
      reader.readAsDataURL(file);
    }
  });
}

// حفظ تعديلات الملف الشخصي عبر Fetch API
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
      const result = await res.json();

      if (result.status === 'success') {
        alert('✅ تم تحديث الملف الشخصي بنجاح');
        if (parentProfileModal) parentProfileModal.classList.add('hidden');
      } else {
        alert('⚠️ ' + result.message);
      }
    } catch (err) {
      alert('⚠️ تعذر حفظ البيانات، يرجى المحاولة لاحقاً');
    }
  });
}

  // 2. التحكم بالتبويبات
  const tabBtns = document.querySelectorAll('.tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabBtns.forEach(b => b.classList.remove('active'));
      tabContents.forEach(c => c.classList.add('hidden'));

      btn.classList.add('active');
      const targetTab = document.getElementById(btn.dataset.tab);
      if (targetTab) targetTab.classList.remove('hidden');
    });
  });

  // 3. أزرار إغلاق وفتح Modals
  const addChildModal = document.getElementById('addChildModal');
  const editChildModal = document.getElementById('editChildModal');
  const healthRecordModal = document.getElementById('healthRecordModal');

  const openAddBtn = document.getElementById('openAddChildModal');
  if (openAddBtn) openAddBtn.onclick = () => addChildModal.classList.remove('hidden');

  const closeAddBtn = document.getElementById('closeAddChildModal');
  if (closeAddBtn) closeAddBtn.onclick = () => addChildModal.classList.add('hidden');

  const closeEditBtn = document.getElementById('closeEditChildModal');
  if (closeEditBtn) closeEditBtn.onclick = () => editChildModal.classList.add('hidden');

  const closeHealthBtn = document.getElementById('closeHealthModal');
  if (closeHealthBtn) closeHealthBtn.onclick = () => healthRecordModal.classList.add('hidden');

  // 4. نموذج إضافة طفل
  const addChildForm = document.getElementById('addChildForm');
  if (addChildForm) {
    addChildForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      try {
        const res = await fetch('add_child.php', { method: 'POST', body: new FormData(this) });
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

  // 5. نموذج تعديل بيانات الطفل
  const editChildForm = document.getElementById('editChildForm');
  if (editChildForm) {
    editChildForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      try {
        const res = await fetch('update_child.php', { method: 'POST', body: new FormData(this) });
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

  // 6. نموذج حفظ الملف الصحي
  const healthRecordForm = document.getElementById('healthRecordForm');
  if (healthRecordForm) {
    healthRecordForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      try {
        const res = await fetch('save_health_record.php', { method: 'POST', body: new FormData(this) });
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

  // 7. نموذج تعديل كلمة المرور
  const editPasswordForm = document.getElementById('editPasswordForm');
  if (editPasswordForm) {
    editPasswordForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      try {
        const res = await fetch('update_child_password.php', { method: 'POST', body: new FormData(this) });
        const result = await res.json();
        if (result.status === 'success') {
          alert('✅ تم تغيير كلمة المرور بنجاح');
          document.getElementById('editPasswordModal').classList.add('hidden');
          this.reset();
        } else {
          alert('⚠️ ' + result.message);
        }
      } catch (err) {
        alert('⚠️ تعذر الاتصال بالسيرفر');
      }
    });
  }

  // 8. جلب وعرض البطاقات
  async function loadChildrenCards() {
    const container = document.getElementById('childrenContainer');
    if (!container) return;

    try {
      const res = await fetch('get_children.php');
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
        const isMale = String(child.gender).toUpperCase() === 'MALE';

        card.innerHTML = `
          <div class="card-header">
            <div class="child-avatar"><i class="fas fa-child"></i></div>
            <div class="card-menu-wrapper">
              <button class="card-settings-btn" onclick="toggleCardOptions('${child.id}')">
                <i class="fas fa-cog"></i>
              </button>
              <div id="cardOptions-${child.id}" class="dropdown-content hidden">
                <a href="javascript:void(0)" onclick="openHealthModal('${child.id}')" class="dropdown-item"><i class="fas fa-notes-medical"></i> الملف الصحي</a>
                <a href="javascript:void(0)" onclick="editPassword('${child.id}')" class="dropdown-item"><i class="fas fa-key"></i> تعديل كلمة المرور</a>
                <a href="javascript:void(0)" onclick="editInfo('${child.id}')" class="dropdown-item"><i class="fas fa-user-edit"></i> المعلومات الشخصية</a>
<hr style="margin: 4px 0; border: none; border-top: 1px solid #eee;">
  <a href="javascript:void(0)" onclick="deleteChild('${child.id}', '${child.full_name}')" class="dropdown-item text-danger" style="color: #e74c3c;"><i class="fas fa-trash-alt"></i> حذف حساب الطفل</a>
              </div>
            </div>
          </div>
          <div class="card-body">
            <h4>${child.full_name}</h4>
            <p>تاريخ الميلاد: ${child.birth_date}</p>
            <p>الجنس: ${isMale ? 'ذكر' : 'أنثى'}</p>
            ${child.uid ? `<p>UID: <span class="uid-badge">${child.uid}</span></p>` : ''}
          </div>
        `;
        container.appendChild(card);
      });
    } catch (e) {
      console.error(e);
    }
  }

  window.loadChildrenCards = loadChildrenCards;
  loadChildrenCards();
});

// ==========================================
// الدوال العامة المتاحة للنقر المباشر (Global)
// ==========================================

function toggleCardOptions(childId) {
  const menu = document.getElementById(`cardOptions-${childId}`);
  if (menu) menu.classList.toggle('hidden');
}

async function openHealthModal(childId) {
  const healthInput = document.getElementById('healthChildId');
  const healthModal = document.getElementById('healthRecordModal');

  if (healthInput) healthInput.value = childId;

  // إغلاق أي قائمة خيارات مفتوحة
  const menu = document.getElementById(`cardOptions-${childId}`);
  if (menu) menu.classList.add('hidden');

  try {
    const res = await fetch(`get_health_record.php?child_id=${childId}`);
    const result = await res.json();

    if (result.status === 'success' && result.data) {
      document.getElementById('bloodType').value = result.data.blood_type || '';
      document.getElementById('allergies').value = result.data.allergies || '';
      document.getElementById('medicalConditions').value = result.data.medical_conditions || '';
    } else {
      const form = document.getElementById('healthRecordForm');
      if (form) form.reset();
      if (healthInput) healthInput.value = childId;
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
    const result = await res.json();

    if (result.status === 'success') {
      const child = result.data;
      document.getElementById('editChildId').value = child.id;
      document.getElementById('editChildName').value = child.full_name;
      document.getElementById('editBirthDate').value = child.birth_date;
      document.getElementById('editGender').value = String(child.gender).toUpperCase();
      document.getElementById('editChildModal').classList.remove('hidden');
    }
  } catch (err) {
    alert('⚠️ تعذر جلب بيانات الطفل');
  }
}

function editPassword(childId) {
  const menu = document.getElementById(`cardOptions-${childId}`);
  if (menu) menu.classList.add('hidden');

  const pwdInput = document.getElementById('passwordChildId');
  const pwdModal = document.getElementById('editPasswordModal');
  
  if (pwdInput) pwdInput.value = childId;
  if (pwdModal) pwdModal.classList.remove('hidden');
}
// دالة حذف الطفل نهائياً مع تأكيد الحذف
async function deleteChild(childId, childName) {
  // إغلاق قائمة القوائم
  const menu = document.getElementById(`cardOptions-${childId}`);
  if (menu) menu.classList.add('hidden');

  // نافذة تأكيد قبل الحذف
  const confirmDelete = confirm(`⚠️ هل أنت تأكد من إرادتك لحذف حساب الطفل "${childName}" نهائياً؟\n\nتنبيه: لا يمكن التراجع عن هذه الخطوة وستفقد جميع البيانات الصحية والتحديثات.`);

  if (!confirmDelete) return;

  const formData = new FormData();
  formData.append('child_id', childId);

  try {
    const res = await fetch('delete_child.php', {
      method: 'POST',
      body: formData
    });
    const result = await res.json();

    if (result.status === 'success') {
      alert('✅ ' + result.message);
      // إعادة تحميل بطاقات الأطفال تحديثاً للواجهة
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
// دالة فتح وجلب بيانات الملف الشخصي لولي الأمر
async function openParentProfile() {
  const modal = document.getElementById('parentProfileModal');
  const uploads_path = 'uploads/';

  try {
    const res = await fetch('get_parent_profile.php');
    
    // التحقق من حالة استجابة السيرفر
    if (!res.ok) {
      throw new Error(`خطأ في السيرفر: ${res.status}`);
    }

    const result = await res.json();

    if (result.status === 'success') {
      const data = result.data;
      
      // تعبئة الحقول
      if (document.getElementById('parentFullName')) document.getElementById('parentFullName').value = data.full_name || '';
      if (document.getElementById('parentEmail')) document.getElementById('parentEmail').value = data.email || '';
      if (document.getElementById('parentPhone')) document.getElementById('parentPhone').value = data.phone || '';
      if (document.getElementById('parentAddress')) document.getElementById('parentAddress').value = data.address || '';
      if (document.getElementById('parentNewPassword')) document.getElementById('parentNewPassword').value = '';

      // ضبط الصورة الشخصية
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
      alert('⚠️ ' + result.message);
    }

  } catch (err) {
    // طباعة تفاصيل الخطأ المباشرة في وحدة التحكم (Console)
    console.error('تفاصيل خطأ جلب الملف الشخصي:', err);
    alert('⚠️ تعذر جلب بيانات الملف الشخصي. افتح Console لمعرفة السبب.');
  }
}