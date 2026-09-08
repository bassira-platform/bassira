document.addEventListener("DOMContentLoaded", () => {
  const dynamicContainer = document.getElementById("dynamic-hero");
  const defaultHeroContent = dynamicContainer ? dynamicContainer.innerHTML : "";

  // ==========================================
  // 1. نظام التنقل والتحميل الديناميكي (SPA Router)
  // ==========================================
  async function loadPageContent(pageUrl) {
    if (!dynamicContainer) return;

    dynamicContainer.innerHTML = `<div style="text-align:center; padding: 2rem;">جاري التحميل... ⏳</div>`;

    try {
      const response = await fetch(pageUrl);
      if (!response.ok) throw new Error("تعذر تحميل الصفحة");

      const htmlContent = await response.text();
      dynamicContainer.innerHTML = htmlContent;

      // تفعيل السكريبتات الخاصة بالصفحة المحمّلة فور إدراج الـ HTML
      if (pageUrl === "contact.html") {
        initContactForm();
      } else if (pageUrl === "register.html") {
        initRegisterForm();
        initOTPVerification();
      }
    } catch (error) {
      console.error("خطأ التنقل:", error);
      dynamicContainer.innerHTML = `<div class="error-banner" style="display:block;">❌ حدث خطأ أثناء تحميل المحتوى.</div>`;
    }
  }

  // ==========================================
  // 2. الاستماع لنقر أزرار التنقل والـ Hash
  // ==========================================
  function handleNavigation() {
    const hash = window.location.hash;

    if (hash === "#register") {
      loadPageContent("register.html");
    } else if (hash === "#contact") {
      loadPageContent("contact.html");
    } else if (hash === "#home" || hash === "" || hash === "#") {
      if (dynamicContainer) {
        dynamicContainer.innerHTML = defaultHeroContent;
      }
    }
  }

  window.addEventListener("hashchange", handleNavigation);
  
  // التفاط الضغط على أي رابط يوجه لـ register أو contact لضمان عمله دائماً
  document.addEventListener("click", (e) => {
    const link = e.target.closest('a[href="#register"], a[href="#contact"], a[href="#home"]');
    if (link) {
      const targetHash = link.getAttribute("href");
      if (window.location.hash === targetHash) {
        handleNavigation(); // إعادة التحميل حتى لو كان الـ Hash هو نفسه
      }
    }
  });

  handleNavigation();

  // ==========================================
  // 3. القائمة المنسدلة والوضع الليلي
  // ==========================================
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

  // ==========================================
  // 4. دالة معالجة نموذج قسم "تواصل معنا"
  // ==========================================
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
        showError("⚠️ يرجى ملء جميع الخانات المطلوبة قبل الإرسال.");
        return;
      }

      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        showError("⚠️ يرجى إدخال بريد إلكتروني صحيح.");
        return;
      }

      hideError();
      setSubmitting(true);

      try {
        const response = await fetch("contact.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
          },
          body: JSON.stringify({ name, email, subject, message }),
        });

        const result = await response.json();

        if (response.ok && result.success) {
          alert("✅ تم إرسال رسالتك بنجاح!");
          form.reset();
        } else {
          throw new Error(result.message || "حدث خطأ غير متوقع أثناء الإرسال.");
        }
      } catch (error) {
        console.error("خطأ الإرسال:", error);
        showError(`❌ ${error.message || "حدث خطأ أثناء إرسال الرسالة، يرجى المحاولة لاحقاً."}`);
      } finally {
        setSubmitting(false);
      }
    });

    function showError(msg) {
      if (errorBanner) {
        errorBanner.textContent = msg;
        errorBanner.style.display = "block";
      } else {
        alert(msg);
      }
    }

    function hideError() {
      if (errorBanner) errorBanner.style.display = "none";
    }

    function setSubmitting(isSubmitting) {
      if (submitBtn) {
        submitBtn.disabled = isSubmitting;
        submitBtn.textContent = isSubmitting ? "جاري الإرسال..." : "إرسال الرسالة ✉️";
      }
    }
  }

  // ==========================================
  // 5. دالة تهيئة وتحقق نموذج التسجيل
  // ==========================================
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

      const fullName = document.getElementById("fullName") ? document.getElementById("fullName").value.trim() : "";
      const email = document.getElementById("regEmail") ? document.getElementById("regEmail").value.trim() : "";
      const phone = document.getElementById("phone") ? document.getElementById("phone").value.trim() : "";
      const password = document.getElementById("password") ? document.getElementById("password").value : "";
      const confirmPassword = document.getElementById("confirmPassword") ? document.getElementById("confirmPassword").value : "";
      const isSpecialist = roleSpecialist && roleSpecialist.checked;
      const specialty = document.getElementById("specialty") ? document.getElementById("specialty").value : "";

      if (!fullName || !email || !phone || !password || !confirmPassword) {
        showAlert("⚠️ يرجى ملء جميع الحقول المطلوبة بدقة.");
        return;
      }

      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        showAlert("⚠️ يرجى إدخال بريد إلكتروني صحيح.");
        return;
      }

      if (isSpecialist && !specialty) {
        showAlert("⚠️ يرجى تحديد التخصص الطبي/النفسي للأخصائي.");
        return;
      }

      if (password.length < 8 || password.length > 20) {
        showAlert("⚠️ يجب أن تكون كلمة المرور بين 8 و 20 خانة.");
        return;
      }

      if (password !== confirmPassword) {
        showAlert("⚠️ كلمتا المرور غير متطابقتين.");
        return;
      }

      if (alertBox) alertBox.style.display = "none";

      const btnStartRegister = document.getElementById("btnStartRegister") || registerForm.querySelector('button[type="submit"]');
      const originalBtnText = btnStartRegister ? btnStartRegister.textContent : "إنشاء الحساب 🚀";

      if (btnStartRegister) {
        btnStartRegister.disabled = true;
        btnStartRegister.textContent = "جاري إرسال رمز التحقق... ⏳";
      }

      try {
        const response = await fetch("send_otp.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ email: email })
        });

        const result = await response.json();

        if (result.success) {
          const displayTargetEmail = document.getElementById("displayTargetEmail");
          if (displayTargetEmail) displayTargetEmail.textContent = email;

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

  // ==========================================
  // 6. دالة التعامل مع الـ OTP
  // ==========================================
  function initOTPVerification() {
    const closeOtpModal = document.getElementById("closeOtpModal");
    if (closeOtpModal) {
      closeOtpModal.onclick = () => {
        const otpModal = document.getElementById("otpModal");
        if (otpModal) otpModal.style.display = "none";
      };
    }

    const resendOtpBtn = document.getElementById("resendOtpBtn");
    if (resendOtpBtn) {
      resendOtpBtn.onclick = async (e) => {
        e.preventDefault();
        const email = document.getElementById("regEmail") ? document.getElementById("regEmail").value.trim() : "";
        if (!email) {
          alert("⚠️ يرجى إدخال البريد الإلكتروني أولاً.");
          return;
        }

        resendOtpBtn.disabled = true;
        resendOtpBtn.textContent = "جاري إعادة الإرسال...";

        try {
          const response = await fetch("send_otp.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email: email })
          });
          const result = await response.json();
          if (result.success) {
            alert("✅ تم إعادة إرسال رمز التحقق إلى بريدك الإلكتروني.");
          } else {
            alert("❌ " + (result.message || "فشل إعادة الإرسال."));
          }
        } catch (err) {
          alert("❌ تعذر الاتصال بالسيرفر لإعادة إرسال الرمز.");
        } finally {
          resendOtpBtn.disabled = false;
          resendOtpBtn.textContent = "إعادة إرسال الرمز 🔄";
        }
      };
    }

    document.removeEventListener("click", handleOtpClick);
    document.addEventListener("click", handleOtpClick);
  }

  async function handleOtpClick(e) {
    if (e.target && e.target.id === "btnVerifyOtp") {
      e.preventDefault();
      e.stopPropagation();

      const otpModal = document.getElementById("otpModal");
      const otpInput = document.getElementById("otpInput");
      const btnVerifyOtp = e.target;

      const otpCode = otpInput ? otpInput.value.trim() : "";
      const email = document.getElementById("regEmail") ? document.getElementById("regEmail").value.trim() : "";

      if (!otpCode || otpCode.length !== 6) {
        alert("⚠️ يرجى إدخال رمز التحقق المكون من 6 أرقام.");
        return;
      }

      btnVerifyOtp.disabled = true;
      btnVerifyOtp.textContent = "جاري التحقق... ⏳";

      try {
        const fullName = document.getElementById("fullName") ? document.getElementById("fullName").value.trim() : "";
        const phone = document.getElementById("phone") ? document.getElementById("phone").value.trim() : "";
        const password = document.getElementById("password") ? document.getElementById("password").value : "";
        const roleSpecialist = document.getElementById("role-specialist");
        const role = (roleSpecialist && roleSpecialist.checked) ? "SPECIALIST" : "PARENT";
        
        const specialtySelect = document.getElementById("specialty") || document.getElementById("specialist_type");
        const specialty = (role === "SPECIALIST" && specialtySelect) ? specialtySelect.value : null;

        const response = await fetch("register.php", {
          method: "POST",
          headers: { 
            "Content-Type": "application/json",
            "Accept": "application/json"
          },
          body: JSON.stringify({
            email: email,
            otp_code: otpCode,
            full_name: fullName,
            phone: phone,
            password: password,
            user_type: role,
            role: role,
            specialist_type: specialty,
            specialty: specialty
          })
        });

        const result = await response.json();

        if (result.success || result.status === "success") {
          alert("✅ تم إنشاء الحساب بنجاح!");
          if (otpModal) otpModal.style.display = "none";
          window.location.href = result.redirect || (role === "SPECIALIST" ? "specialistHome.html" : "parentHome.html");
        } else {
          alert("❌ " + (result.message || "فشل التحقق من رمز OTP."));
        }

      } catch (error) {
        console.error("خطأ أثناء الاتصال بالخادم:", error);
        alert("❌ حدث خطأ في الاتصال بالخادم، يرجى المحاولة لاحقاً.");
      } finally {
        btnVerifyOtp.disabled = false;
        btnVerifyOtp.textContent = "تأكيد الرمز 🔓";
      }
    }
  }
});