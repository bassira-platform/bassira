let currentChildId = null;

// فتح نافذة التقرير وجلب أحدث إصدار مع قائمة الأرشيف
function openReportModal(childId) {
    currentChildId = childId;
    const modal = document.getElementById('reportModal');
    if (!modal) return;

    modal.style.display = 'flex';

    fetch(`get_child_report.php?child_id=${childId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // تعبئة البيانات الأساسية
                if (document.getElementById('rep-child-name')) document.getElementById('rep-child-name').innerText = data.child.child_name || '-';
                if (document.getElementById('rep-child-uid')) document.getElementById('rep-child-uid').innerText = data.child.uid || '-';

                // تعبئة قائمة الإصدارات المتاحة (History)
                const versionSelect = document.getElementById('reportVersionSelect');
                if (versionSelect) {
                    versionSelect.innerHTML = '';
                    if (data.all_reports && data.all_reports.length > 0) {
                        data.all_reports.forEach(rep => {
                            const opt = document.createElement('option');
                            opt.value = rep.file_path;
                            opt.textContent = `${rep.version} (${new Date(rep.created_at).toLocaleDateString('ar-EG')}) - معدل: ${rep.moyenne_score}`;
                            versionSelect.appendChild(opt);
                        });
                        
                        // تعيين عارض الـ PDF على أحدث ملف
                        previewPDF(data.latest_report.file_path);
                    } else {
                        versionSelect.innerHTML = '<option value="">لا توجد تقارير مولدة بعد</option>';
                    }
                }

                // ربط زر تحميل الأرشيف الكامل ZIP
                const zipBtn = document.getElementById('btnDownloadZip');
                if (zipBtn) {
                    zipBtn.onclick = () => {
                        window.location.href = `download_all_reports.php?child_id=${childId}`;
                    };
                }

            } else {
                alert(data.message || 'حدث خطأ أثناء جلب بيانات التقرير');
            }
        })
        .catch(err => {
            console.error('خطأ أثناء جلب التقرير:', err);
        });
}

// تغيير التقرير المعروض بناءً على اختيار الإصدار من القائمة المنسدلة
function onVersionChange(selectEl) {
    const filePath = selectEl.value;
    if (filePath) {
        previewPDF(filePath);
    }
}

// عرض ملف الـ PDF داخل iframe في النافذة المنبثقة
function previewPDF(filePath) {
    const iframe = document.getElementById('pdfPreviewIframe');
    if (iframe) {
        iframe.src = filePath;
    }
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) modal.style.display = 'none';
}