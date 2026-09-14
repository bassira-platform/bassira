// متغيرات عامة لمنع التكرار
let currentChildReports = [];

/**
 * فتح نافذة التقرير وجلب البيانات من الخادم
 * @param {number} childId - معرّف الطفل
 */
async function openReportModal(childId) {
    const modal = document.getElementById('reportModal');
    const versionSelect = document.getElementById('reportVersionSelect');
    const iframe = document.getElementById('pdfPreviewIframe');
    const btnZip = document.getElementById('btnDownloadZip');

    if (!modal) return;

    // إظهار النافذة وتصفير المحتوى القديم
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
    versionSelect.innerHTML = '<option value="">جاري التحميل...</option>';
    iframe.src = '';

    try {
        const response = await fetch(`get_child_report.php?child_id=${childId}`);
        const data = await response.json();

        if (data.status === 'success') {
            currentChildReports = data.all_reports || [];

            if (currentChildReports.length === 0) {
                versionSelect.innerHTML = '<option value="">لا توجد تقارير مخزنة لهذا الطفل بعد</option>';
                iframe.src = 'about:blank';
                return;
            }

            // ملء القائمة المنسدلة بالإصدارات المتاحة
            versionSelect.innerHTML = '';
            currentChildReports.forEach((report, index) => {
                const opt = document.createElement('option');
                opt.value = report.file_path;
                opt.textContent = `${report.file_title} (${report.version}) - ${report.created_at}`;
                if (index === 0) opt.selected = true; // اختيار أحدث تقرير افتراضياً
                versionSelect.appendChild(opt);
            });

            // عرض التقرير الأحدث في عارض الـ iframe
            const latestFilePath = currentChildReports[0].file_path;
            iframe.src = latestFilePath;

            // ضبط رابط تحميل الأرشيف ZIP
            if (btnZip) {
                btnZip.onclick = () => downloadAllReportsZip(childId);
            }

        } else {
            alert(data.message || 'حدث خطأ أثناء جلب التقرير');
            closeReportModal();
        }
    } catch (err) {
        console.error('خطأ في الاتصال:', err);
        alert('تعذر الاتصال بالخادم لجلب بيانات التقرير.');
        closeReportModal();
    }
}

/**
 * عند تغيير إصدار التقرير من القائمة المنسدلة
 */
function onVersionChange(selectElem) {
    const iframe = document.getElementById('pdfPreviewIframe');
    if (iframe && selectElem.value) {
        iframe.src = selectElem.value;
    }
}

/**
 * إغلاق النافذة المنبثقة للتقرير
 */
function closeReportModal() {
    const modal = document.getElementById('reportModal');
    const iframe = document.getElementById('pdfPreviewIframe');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
    }
    if (iframe) {
        iframe.src = '';
    }
}

/**
 * دالة تحضير تحميل ملف ZIP لجميع تقارير الطفل
 */
function downloadAllReportsZip(childId) {
    window.location.href = `download_reports_zip.php?child_id=${childId}`;
}