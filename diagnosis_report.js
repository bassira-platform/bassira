let currentChildData = null;

// فتح النافذة وجلب بيانات التقرير للطفل المحدد
function openReportModal(childId) {
    const modal = document.getElementById('reportModal');
    if (!modal) return;

    modal.style.display = 'flex';
    document.getElementById('rep-date').innerText = 'تاريخ الإصدار: ' + new Date().toLocaleDateString('ar-EG');

    fetch(`get_child_report.php?child_id=${childId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                currentChildData = data;
                
                // تعبئة البيانات الشخصية
                document.getElementById('rep-child-name').innerText = data.child.child_name || '-';
                document.getElementById('rep-child-uid').innerText = data.child.uid || '-';
                document.getElementById('rep-child-dob').innerText = data.child.birth_date || '-';
                document.getElementById('rep-child-blood').innerText = data.health.blood_type || 'غير محدد';

                document.getElementById('rep-parent-name').innerText = data.child.parent_name || '-';
                document.getElementById('rep-parent-phone').innerText = data.child.parent_phone || '-';
                document.getElementById('rep-parent-email').innerText = data.child.parent_email || '-';
                document.getElementById('rep-parent-address').innerText = data.child.parent_address || 'غير مدخل';

                document.getElementById('rep-allergies').innerText = data.health.allergies || 'لا يوجد';
                document.getElementById('rep-conditions').innerText = data.health.medical_conditions || 'لا يوجد';

                // شارات التقييم
                setupBadge('rep-asd-badge', data.assessment.asd_status);
                setupBadge('rep-sld-badge', data.assessment.sld_status);
            } else {
                alert(data.message || 'حدث خطأ أثناء جلب بيانات التقرير');
            }
        })
        .catch(err => {
            console.error('Error fetching report:', err);
        });
}

function setupBadge(elementId, status) {
    const el = document.getElementById(elementId);
    if (!el) return;

    if (status === 'HIGH_RISK') {
        el.innerText = 'خطر مرتفع (يتطلب استشارة)';
        el.className = 'badge badge-high';
    } else if (status === 'MODERATE_RISK') {
        el.innerText = 'خطر متوسط (تحت الملاحظة)';
        el.className = 'badge badge-mod';
    } else {
        el.innerText = 'طبيعي (خطر منخفض)';
        el.className = 'badge badge-low';
    }
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) modal.style.display = 'none';
}

// إنشاء وتحميل ملف الـ PDF
function downloadPDF() {
    const element = document.getElementById('pdf-content');
    const childName = currentChildData ? currentChildData.child.child_name : 'الطفل';
    
    const opt = {
        margin:       10,
        filename:     `تقرير_تشخيص_${childName}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save();
}