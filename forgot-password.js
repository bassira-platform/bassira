document.addEventListener("DOMContentLoaded", () => {
  // العناصر والمودالات الثلاثة
  const forgotModal = document.getElementById("forgotPasswordModal");
  const otpModal = document.getElementById("otpModal");
  const resetModal = document.getElementById("resetPasswordModal");

  const openForgotBtn = document.getElementById("openForgotPassword");
  const closeForgotModal = document.getElementById("closeForgotModal");
  const closeOtpModal = document.getElementById("closeOtpModal");
  const closeResetModal = document.getElementById("closeResetModal");

  const forgotForm = document.getElementById("forgotPasswordForm");
  const resetForm = document.getElementById("resetPasswordForm");
  const otpFields = document.querySelectorAll(".otp-field");
  const otpStatus = document.getElementById("otpStatus");

  let currentEmail = "";
  let verifiedOTP = "";

  // 1. فتح وإغلاق النوافذ
  if (openForgotBtn) {
    openForgotBtn.addEventListener("click", (e) => {
      e.preventDefault();
      if (forgotModal) forgotModal.style.display = "flex";
    });
  }

  if (closeForgotModal) closeForgotModal.onclick = () => forgotModal.style.display = "none";
  if (closeOtpModal) closeOtpModal.onclick = () => otpModal.style.display = "none";
  if (closeResetModal) closeResetModal.onclick = () => resetModal.style.display = "none";

  window.onclick = (e) => {
    if (e.target === forgotModal) forgotModal.style.display = "none";
    if (e.target === otpModal) otpModal.style.display = "none";
    if (e.target === resetModal) resetModal.style.display = "none";
  };

  // 2. إرسال البريد للحصول على الرمز
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
        const response = await fetch("send_reset_otp.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ email: currentEmail })
        });

        const result = await response.json();

        if (result.success) {
          forgotModal.style.display = "none";
          otpModal.style.display = "flex";
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

  // 3. التنقل الذكي داخل خانات الـ OTP والتحقق الفوري تلقائياً
  otpFields.forEach((field, index) => {
    field.addEventListener("input", (e) => {
      const val = e.target.value;
      if (val.length === 1 && index < otpFields.length - 1) {
        otpFields[index + 1].focus();
      }
      checkAndVerifyOTP();
    });

    field.addEventListener("keydown", (e) => {
      if (e.key === "Backspace" && !field.value && index > 0) {
        otpFields[index - 1].focus();
      }
    });

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

  // دالة التحقق التلقائي عند إدخال الرقم السادس
  async function checkAndVerifyOTP() {
    let code = "";
    otpFields.forEach((f) => (code += f.value));

    if (code.length === 6) {
      otpStatus.style.color = "#0d9488";
      otpStatus.textContent = "جاري التحقق من الرمز... ⏳";

      try {
        const response = await fetch("verify_otp.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ email: currentEmail, otp_code: code })
        });

        const result = await response.json();

        if (result.success) {
          verifiedOTP = code;
          otpStatus.style.color = "#27ae60";
          otpStatus.textContent = "✅ الرمز صحيح!";
          
          setTimeout(() => {
            otpModal.style.display = "none";
            resetModal.style.display = "flex";
            otpStatus.textContent = "";
          }, 800);
        } else {
          otpStatus.style.color = "#dc2626";
          otpStatus.textContent = "❌ الرمز غير صحيح أو انتهت صلاحيته.";
        }
      } catch (err) {
        otpStatus.style.color = "#dc2626";
        otpStatus.textContent = "❌ تعذر الاتصال بالسيرفر للتحقق.";
      }
    } else {
      otpStatus.textContent = "";
    }
  }

  // 4. حفظ كلمة المرور الجديدة
  if (resetForm) {
    resetForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      const newPassword = document.getElementById("newPassword").value;
      const confirmNewPassword = document.getElementById("confirmNewPassword").value;

      if (newPassword !== confirmNewPassword) {
        alert("⚠️ كلمتا المرور غير متطابقتين.");
        return;
      }

      const btnReset = document.getElementById("btnResetPassword");
      btnReset.disabled = true;
      btnReset.textContent = "جاري حفظ كلمة المرور... ⏳";

      try {
        const response = await fetch("reset_password.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            email: currentEmail,
            otp_code: verifiedOTP,
            new_password: newPassword
          })
        });

        const result = await response.json();

        if (result.success) {
          alert("✅ تم تغيير كلمة المرور بنجاح! يمكنك تسجيل الدخول الآن.");
          resetModal.style.display = "none";
          resetForm.reset();
          forgotForm.reset();
          otpFields.forEach((f) => (f.value = ""));
        } else {
          alert("❌ " + (result.message || "حدث خطأ أثناء التحديث."));
        }
      } catch (err) {
        alert("❌ تعذر الاتصال بالسيرفر لتحديث كلمة المرور.");
      } finally {
        btnReset.disabled = false;
        btnReset.textContent = "حفظ كلمة المرور 🚀";
      }
    });
  }
});