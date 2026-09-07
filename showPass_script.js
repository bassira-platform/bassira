document.addEventListener('DOMContentLoaded', () => {
  // تفويض الأحداث لتغطية جميع العناصر الحالية والديناميكية والنوافذ المنبثقة
  document.addEventListener('click', (event) => {
    const toggleBtn = event.target.closest('.toggle-password');
    if (!toggleBtn) return;

    // إلغاء السلوك الافتراضي للزر داخل Forms
    event.preventDefault();

    // معرفة الحقل المستهدف من data-target أو البحث في نفس الغلاف
    const targetId = toggleBtn.getAttribute('data-target');
    let inputField = targetId ? document.getElementById(targetId) : null;

    if (!inputField) {
      inputField = toggleBtn.parentElement.querySelector('input');
    }

    if (inputField) {
      const icon = toggleBtn.querySelector('i');
      const isPassword = inputField.type === 'password';

      // تبديل نوع الحقل
      inputField.type = isPassword ? 'text' : 'password';

      // تبديل أيقونة العين (FontAwesome)
      if (icon) {
        icon.classList.toggle('fa-eye', !isPassword);
        icon.classList.toggle('fa-eye-slash', isPassword);
      }
    }
  });
});