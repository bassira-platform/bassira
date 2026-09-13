// parent-bookings.js

// دالة مساعدة لحماية المدخلات من ثغرات XSS
function escapeHtmlBooking(text) {
  if (!text) return '';
  return String(text)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

async function loadParentBookings() {
  const container = document.getElementById('parentBookingsContainer');
  if (!container) return;

  try {
    const res = await fetch('get_parent_bookings.php');
    if (res.status === 401) {
      window.location.replace('login.html');
      return;
    }
    
    const result = await res.json();

    if (result.status === 'success' && Array.isArray(result.data) && result.data.length > 0) {
      container.innerHTML = '';
      result.data.forEach(item => {
        const div = document.createElement('div');
        div.className = 'booking-card';
        div.style.padding = '15px';
        div.style.marginBottom = '15px';
        div.style.borderRadius = '8px';
        div.style.border = '1px solid #e0e0e0';
        div.style.backgroundColor = '#ffffff';

        let statusHtml = '';
        const dateStr = escapeHtmlBooking(item.appointment_date) || 'لم يُحدد بعد';
        const timeStr = escapeHtmlBooking(item.appointment_time) || 'لم يُحدد بعد';

        // التعامل مع الحالات بناءً على حالة الطلب
        if (item.status === 'PENDING') {
          statusHtml = `<p><strong>الحالة:</strong> <span style="color: #e67e22; font-weight: bold;">قيد الانتظار ⏳</span> (جارٍ المراجعة وتحديد الموعد من الأخصائي)</p>`;
        } else if (item.status === 'ACCEPTED') {
          statusHtml = `
            <p><strong>الحالة:</strong> <span style="color: #27ae60; font-weight: bold;">مقبول ✔</span></p>
            <p><strong>الموعد المعتمد:</strong> ${dateStr} الساعة ${timeStr}</p>
          `;
        } else if (item.status === 'WAITLIST') {
          statusHtml = `<p><strong>الحالة:</strong> <span style="color: #f39c12; font-weight: bold;">قائمة الاحتياطي ⏸</span> (أنت في قائمة الانتظار بناءً على أسبقية الطلب)</p>`;
        } else if (item.status === 'REJECTED') {
          statusHtml = `
            <p><strong>الحالة:</strong> <span style="color: #c0392b; font-weight: bold;">مرفوض ✖</span></p>
            <div style="background-color: #fdf2f2; color: #982c2c; padding: 10px; border-radius: 6px; margin-top: 5px;">
              <strong>سبب الرفض:</strong> ${escapeHtmlBooking(item.rejection_reason) || 'لم يتم ذكر سبب'}
            </div>
          `;
        }

        div.innerHTML = `
          <h4><i class="fas fa-user-md"></i> الأخصائي: ${escapeHtmlBooking(item.specialist_name)} (${escapeHtmlBooking(item.specialist_type) || 'أخصائي'})</h4>
          <p><strong>الطفل:</strong> ${escapeHtmlBooking(item.child_name)}</p>
          <p><strong>تفاصيل الطلب:</strong> ${escapeHtmlBooking(item.notes) || 'لا يوجد'}</p>
          <hr style="border: 0; border-top: 1px solid #eee; margin: 10px 0;">
          ${statusHtml}
        `;
        container.appendChild(div);
      });
    } else {
      container.innerHTML = `
        <div class="empty-state">
          <h3>لا توجد لديك أي حجوزات حالية</h3>
        </div>`;
    }
  } catch (err) {
    console.error('خطأ في جلب الحجوزات:', err);
    container.innerHTML = `<p class="placeholder-text">تعذر تحميل الحجوزات حالياً، يرجى المحاولة لاحقاً.</p>`;
  }
}

// إتاحة الدالة للنطاق العام للاستدعاء الخارجي
window.loadParentBookings = loadParentBookings;