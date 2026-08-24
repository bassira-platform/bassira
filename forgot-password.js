document.addEventListener("DOMContentLoaded", () => {
  // العناصر الرئيسية للمودال
  const forgotModal = document.getElementById("forgotPasswordModal");
  const resetModal = document.getElementById("resetPasswordModal");
  const openForgotBtn = document.getElementById("openForgotPassword");
  const closeForgotModal = document.getElementById("closeForgotModal");
  const closeResetModal = document.getElementById("closeResetModal");

  const forgotForm = document.getElementById("forgotPasswordForm");
  const resetForm = document.getElementById("resetPasswordForm");
  const otpFields = document.querySelectorAll(".otp-field");

  let currentEmail = ""; // لتخزين بريد المستخدم مؤقتاً

  // ==========================================
  // 1. إظهار وإخفاء النوافذ المنبثقة
  // ==========================================
  if (openForgotBtn) {
    openForgotBtn.addEventListener("click", (e) => {
      e.preventDefault();
      if (forgotModal) forgotModal.style.display = "flex";
    });
  }

  if (closeForgotModal) {
    closeForgotModal.addEventListener("click", () => {
      forgotModal.style.display = "none";
    });
  }

  if (closeResetModal) {
    closeResetModal.addEventListener("click", () => {
      resetModal.style.display = "none";
    });
  }

  // إغلاق المودال عند الضغط خارجه
  window.addEventListener("click", (e) => {
    if (e.target === forgotModal) forgotModal.style.display = "none";
    if (e.target === resetModal) resetModal.style.display = "none";
  });

  // ==========================================
  // 2. إرسال طلب رمز التحقق (OTP)
  // ==========================================
  if (forgotForm) {
    forgotForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const emailInput = document.getElementById("forgotEmail");
      const btnSend = document.getElementById("btnSendForgotOtp");

      currentEmail = emailInput.value.trim();
      if (!currentEmail) return;

      btnSend.disabled = true;
      btnSend.textContent = "جاري إرسال الرمز... ⏳";

      try {
        const response = await fetch("send_otp.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ email: currentEmail })
        });

        const result = await response.json();

        if (result.success) {
          forgotModal.style.display = "none";
          resetModal.style.display = "flex";
          if (otpFields[0]) otpFields[0].focus();
        } else {
          alert("❌ " + (result.message || "تعذر إرسال الرمز."));
        }
      } catch (err) {
        alert("❌ حدث خطأ أثناء الاتصال بالسيرفر.");
      } finally {
        btnSend.disabled = false;
        btnSend.textContent = "إرسال رمز التحقق 📩";
      }
    });
  }

  // ==========================================
  // 3. التحكم الذكي بخانات OTP (6 خانات)
  // ==========================================
  otpFields.forEach((field, index) => {
    // التنقل للأمام عند كتابة رقم
    field.addEventListener("input", (e) => {
      const val = e.target.value;
      if (val.length === 1 && index < otpFields.length - 1) {
        otpFields[index + 1].focus();
      }
      checkAndVerifyOTP();
    });

    // الرجوع للخلف عند الضغط على Backspace
    field.addEventListener("keydown", (e) => {
      if (e.key === "Backspace" && !field.value && index > 0) {
        otpFields[index - 1].focus();
      }
    });

    // دعم اللصق المباشر (Paste)
    field.addEventListener("paste", (e) => {
      e.preventDefault();
      const pastedData = e.clipboardData.getData("text").trim();
      if (/^\d{6}$/.test(pastedData)) {
        pastedData.split("").forEach((char, i) => {
          if (otpFields[i]) otpFields[i].value = char;
        });
        otpFields[5].focus();
        checkAndVerifyOTP();
      }
    });
  });

  // دالة تجميع الرمز والتأكد من إكتمال 6 أرقام
  function checkAndVerifyOTP() {
    let code = "";
    otpFields.forEach((f) => (code += f.value));

    if (code.length === 6) {
      // إبراز الخانات باللون الأخضر للإشارة لاكتمال الرقم
      otpFields.forEach((f) => (f.style.borderColor = "#27ae60"));
    }
  }

  // ==========================================
  // 4. إرسال كلمة المرور الجديدة مع الرمز
  // ==========================================
  if (resetForm) {
    resetForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      let otpCode = "";
      otpFields.forEach((f) => (otpCode += f.value));

      if (otpCode.length !== 6) {
        alert("⚠️ يرجى إدخال رمز التحقق المكون من 6 أرقام كاملاً.");
        return;
      }

      const newPassword = document.getElementById("newPassword").value;
      const confirmNewPassword = document.getElementById("confirmNewPassword").value;

      if (newPassword !== confirmNewPassword) {
        alert("⚠️ كلمتا المرور غير متطابقتين.");
        return;
      }

      const btnReset = document.getElementById("btnResetPassword");
      btnReset.disabled = true;
      btnReset.textContent = "جاري تحديث كلمة المرور... ⏳";

      try {
        const response = await fetch("reset_password.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            email: currentEmail,
            otp_code: otpCode,
            new_password: newPassword
          })
        });

        const result = await response.json();

        if (result.success) {
          alert("✅ تم تغيير كلمة المرور بنجاح! يمكنك الآن تسجيل الدخول.");
          resetModal.style.display = "none";
          resetForm.reset();
          forgotForm.reset();
          otpFields.forEach((f) => {
            f.value = "";
            f.style.borderColor = "#bdc3c7";
          });
        } else {
          alert("❌ " + (result.message || "حدث خطأ أثناء التحديث."));
        }
      } catch (err) {
        alert("❌ تعذر الاتصال بالسيرفر لتحديث كلمة المرور.");
      } finally {
        btnReset.disabled = false;
        btnReset.textContent = "تحديث كلمة المرور 🚀";
      }
    });
  }
});