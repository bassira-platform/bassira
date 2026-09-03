document.addEventListener('DOMContentLoaded', function () {
  // 1. فحص الجلسة التلقائي عند تحميل الصفحة
  fetch('check_session.php')
    .then(res => res.json())
    .then(data => {
      if (data.logged_in && data.redirect) {
        window.location.href = data.redirect;
      }
    })
    .catch(() => {});

  // 2. معالجة إرسال نموذج تسجيل الدخول
  const loginForm = document.getElementById('loginForm');
  if (!loginForm) return;

  loginForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    
    const identifier = document.getElementById('identifier').value.trim();
    const password = document.getElementById('password').value.trim();
    const alertBox = document.getElementById('alertBox');
    const submitBtn = this.querySelector('button[type="submit"]');

    if (!identifier || !password) {
      showAlert('⚠️ يرجى إدخال البريد الإلكتروني/الرمز وكلمة المرور.', 'error');
      return;
    }

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'جاري التحقق...';
    }

    const formData = new FormData(this);

    try {
      const response = await fetch('login.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.status === 'success') {
        showAlert('✅ تم التحقق بنجاح! جاري التوجيه...', 'success');
        setTimeout(() => {
          window.location.href = result.redirect;
        }, 800);
      } else {
        showAlert('⚠️ ' + result.message, 'error');
      }
    } catch (error) {
      showAlert('⚠️ تعذر الاتصال بالسيرفر.', 'error');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'دخول إلى الحساب 🔑';
      }
    }

    function showAlert(msg, type) {
      if (!alertBox) return;
      alertBox.textContent = msg;
      alertBox.style.display = 'block';
      alertBox.style.backgroundColor = type === 'success' ? '#d1fae5' : '#fef2f2';
      alertBox.style.color = type === 'success' ? '#065f46' : '#dc2626';
      alertBox.style.border = type === 'success' ? '1px solid #a7f3d0' : '1px solid #fecaca';
    }
  });
});