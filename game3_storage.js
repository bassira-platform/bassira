function saveReportData(data) {
    if (navigator.onLine) {
        sendResultToServer(data);
    } else {
        localStorage.setItem('pending_game3_result_' + Date.now(), JSON.stringify(data));
        alert("تم حفظ نتائج اللعبة محلياً نظراً لأن الجهاز غير متصل بالإنترنت.");
        window.location.href = "index.html"; // أو العودة للرئيسية
    }
}

function sendResultToServer(data) {
    // إرسال النتيجة إلى ملف المزامنة الموجود في الجذر مباشرة أو عبر مجلد api
    fetch('save_game3_result.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            alert("تم إتمام التقييم وتخزين النتيجة بنجاح!");
            window.location.href = "index.html";
        } else {
            alert("خطأ أثناء حفظ النتيجة: " + res.message);
        }
    })
    .catch(err => {
        console.error("خطأ الاتصال بالسيرفر:", err);
    });
}