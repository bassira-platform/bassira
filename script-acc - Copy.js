document.addEventListener("DOMContentLoaded", () => {
  const dynamicContainer = document.getElementById("dynamic-hero");

  // حفظ المحتوى الافتراضي للهيرو عند فتح الصفحة لأول مرة
  const defaultHeroContent = dynamicContainer ? dynamicContainer.innerHTML : "";

  // 1. نظام التنقل والتحميل الديناميكي (SPA Router)
  async function loadPageContent(pageUrl) {
    if (!dynamicContainer) return;

    // إظهار مؤشر التحميل
    dynamicContainer.innerHTML = `<div style="text-align:center; padding: 2rem;">جاري التحميل... ⏳</div>`;

    try {
      const response = await fetch(pageUrl);
      if (!response.ok) throw new Error("تعذر تحميل الصفحة");

      const htmlContent = await response.text();
      dynamicContainer.innerHTML = htmlContent;

      // تفعيل السكريبتات الخاصة بالصفحة المحمّلة حسب نوعها
      if (pageUrl === "contact.html") {
        initContactForm();
      } else if (pageUrl === "register.html") {
        initRegisterForm();
      }
    } catch (error) {
      console.error("خطأ التنقل:", error);
      dynamicContainer.innerHTML = `
        <div class="error-banner" style="display:block;">
          ❌ حدث خطأ أثناء تحميل المحتوى. يرجى المحاولة لاحقاً.
        </div>`;
    }
  }

  // 2. الاستماع لنقر أزرار التنقل واللوجو (Hash Router)
  function handleNavigation() {
    const hash = window.location.hash;

    if (hash === "#register") {
      loadPageContent("register.html");
    } else if (hash === "#contact") {
      loadPageContent("contact.html");
    } else if (hash === "#home" || hash === "" || hash === "#") {
      // العودة للمحتوى الرئيسي الافتراضي عند الضغط على اللوجو (#home) أو فتح الرئيسية
      if (dynamicContainer) {
        dynamicContainer.innerHTML = defaultHeroContent;
      }
    }
  }

  // الاستماع لتغيرات الـ Hash في رابط الصفحة وعند تحميل الصفحة
  window.addEventListener("hashchange", handleNavigation);
  handleNavigation();

  // 3. القائمة المنسدلة والوضع الليلي (تفعيل زر Settings)
  const menuBtn = document.getElementById("menu-btn");
  const settingsDropdown = document.getElementById("settings-dropdown");
  const themeToggle = document.getElementById("theme-toggle");

  if (menuBtn && settingsDropdown) {
    menuBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      settingsDropdown.classList.toggle("hidden");
    });

    document.addEventListener("click", (e) => {
      if (!settingsDropdown.contains(e.target) && e.target !== menuBtn) {
        settingsDropdown.classList.add("hidden");
      }
    });
  }

  if (themeToggle) {
    themeToggle.addEventListener("change", () => {
      document.body.classList.toggle("dark-theme", themeToggle.checked);
    });
  }

  // 4. دالة معالجة نموذج قسم "تواصل معنا"
  function initContactForm() {
    const form = document.getElementById("contactForm");
    if (!form) return;

    const errorBanner = document.getElementById("contactError");
    const submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const name = document.getElementById("contactName").value.trim();
      const email = document.getElementById("contactEmail").value.trim();
      const subject = document.getElementById("contactSubject").value.trim();
      const message = document.getElementById("contactMessage").value.trim();

      if (!name || !email || !subject || !message) {
        errorBanner.textContent = "⚠️ يرجى ملء جميع الخانات المطلوبة قبل الإرسال.";
        errorBanner.style.display = "block";
        return;
      }

      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        errorBanner.textContent = "⚠️ يرجى إدخال بريد إلكتروني صحيح.";
        errorBanner.style.display = "block";
        return;
      }

      errorBanner.style.display = "none";
      submitBtn.disabled = true;
      submitBtn.textContent = "جاري الإرسال...";

      try {
        const response = await fetch("https://api.web3forms.com/submit", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify({
            access_key: "c44b9a98-c970-4412-a942-3a8df59ac52e",
            name: name,
            email: email,
            subject: `[منصة بصيرة] ${subject}`,
            message: message,
          }),
        });

        const result = await response.json();

        if (result.success) {
          alert("✅ تم إرسال رسالتك بنجاح إلى فريق منصة بصيرة! وسنتواصل معك قريباً.");
          form.reset();
        } else {
          throw new Error(result.message);
        }
      } catch (error) {
        console.error("خطأ الإرسال:", error);
        errorBanner.textContent = "❌ حدث خطأ أثناء إرسال الرسالة، يرجى المحاولة لاحقاً.";
        errorBanner.style.display = "block";
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = "إرسال الرسالة ✉️";
      }
    });
  }
// 5. دالة تهيئة وتحقق نموذج التسجيل عند تحميله ديناميكياً
function initRegisterForm() {
  const registerForm = document.getElementById("registerForm");
  if (!registerForm) return;

  const specialistFields = document.getElementById("specialistFields");
  const roleParents = document.getElementById("role-parent");
  const roleSpecialist = document.getElementById("role-specialist");
  const alertBox = document.getElementById("alertBox");

  if (roleParents && roleSpecialist && specialistFields) {
    roleParents.addEventListener("change", () => {
      specialistFields.style.display = "none";
    });
    roleSpecialist.addEventListener("change", () => {
      specialistFields.style.display = "block";
    });
  }

  registerForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    // جلب الحقول
    const fullName = document.getElementById("fullName").value.trim();
    const email = document.getElementById("regEmail").value.trim();
    const phone = document.getElementById("phone").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const isSpecialist = roleSpecialist && roleSpecialist.checked;
    const specialty = document.getElementById("specialty").value;

    // 1. التحقق من ملء جميع الحقول الأساسية
    if (!fullName || !email || !phone || !password || !confirmPassword) {
      showAlert("⚠️ يرجى ملء جميع الحقول المطلوبة بدقة.");
      return;
    }

    // 2. التحقق من صحة البريد الإلكتروني
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      showAlert("⚠️ يرجى إدخال بريد إلكتروني صحيح.");
      return;
    }

    // 3. التحقق من اختيار التخصص في حال كان الحساب أخصائي
    if (isSpecialist && !specialty) {
      showAlert("⚠️ يرجى تحديد التخصص الطبي/النفسي للأخصائي.");
      return;
    }

    // 4. التحقق من طول كلمة المرور (بين 8 و 20 خانة)
    if (password.length < 8 || password.length > 20) {
      showAlert("⚠️ يجب أن تكون كلمة المرور بين 8 و 20 خانة.");
      return;
    }

    // 5. التحقق من تعقيد كلمة المرور (حرف كبير، حرف صغير، رقم، ورمز)
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

    if (!hasUpperCase || !hasLowerCase || !hasNumbers || !hasSpecialChar) {
      showAlert("⚠️ يجب أن تحتوي كلمة المرور على حرف كبير، حرف صغير، رقم، ورمز خاص واحد على الأقل.");
      return;
    }

    // 6. التحقق من تطابق كلمة المرور وتأكيدها
    if (password !== confirmPassword) {
      showAlert("⚠️ كلمتا المرور غير متطابقتين.");
      return;
    }

    // إخفاء تنبيه الأخطاء في حال اجتياز كافة الشروط
    if (alertBox) {
      alertBox.style.display = "none";
    }

    // 7. بدء إرسال الطلب للخلفية لتوليد الـ OTP
    const btnStartRegister = document.getElementById("btnStartRegister") || registerForm.querySelector('button[type="submit"]');
    const originalBtnText = btnStartRegister ? btnStartRegister.textContent : "إنشاء الحساب 🚀";

    if (btnStartRegister) {
      btnStartRegister.disabled = true;
      btnStartRegister.textContent = "جاري إرسال رمز التحقق... ⏳";
    }

    try {
      // إرسال طلب الـ OTP إلى Backend
      const response = await fetch("send_otp.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: email })
      });

      const result = await response.json();

      if (result.success) {
        // إظهار النافذة المنبثقة للتحقق
        const targetEmailElem = document.getElementById("displayTargetEmail");
        if (targetEmailElem) targetEmailElem.textContent = email;

        const otpModal = document.getElementById("otpModal");
        if (otpModal) {
          otpModal.style.display = "flex";
          otpModal.classList.remove("hidden");
        }
      } else {
        showAlert("❌ " + (result.message || "فشل إرسال رمز التحقق."));
      }
    } catch (err) {
      showAlert("❌ تعذر الاتصال بالسيرفر لإرسال رمز التحقق.");
    } finally {
      if (btnStartRegister) {
        btnStartRegister.disabled = false;
        btnStartRegister.textContent = originalBtnText;
      }
    }
  });

  function showAlert(message) {
    if (alertBox) {
      alertBox.textContent = message;
      alertBox.style.display = "block";
      alertBox.scrollIntoView({ behavior: "smooth", block: "center" });
    } else {
      alert(message);
    }
  }
}
});