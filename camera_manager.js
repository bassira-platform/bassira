// وحدة إدارة الكاميرا المتقدمة لحل مشكلة احتجاز الأجهزة
window.CameraManager = {
    stream: null,
    videoEl: document.createElement('video'),

    // 1. بدء الكاميرا وتأكيد إضاءة الضوء الأخضر
    async start() {
        await this.stop(); // إغلاق أي جلسة سابقة متصلة بالجهاز
        
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: "user" }
            });
            
            this.videoEl.srcObject = this.stream;
            await this.videoEl.play();
            console.log("🟢 الكاميرا متصلة والضوء الأخضر يعمل.");
            return true;
        } catch (err) {
            console.error("🔴 فشل تشغيل الكاميرا:", err);
            return false;
        }
    },

    // 2. تحرير الكاميرا قطعيًا وإطفاء الضوء الأخضر
    async stop() {
        if (this.stream) {
            // إيقاف جميع المسارات الفيزيائية
            this.stream.getTracks().forEach(track => {
                track.stop();
                this.stream.removeTrack(track);
            });
            
            // تفريغ عنصر الفيديو من القناة
            this.videoEl.srcObject = null;
            this.stream = null;
            console.log("🔴 تم إغلاق الكاميرا وإيقاف الضوء الأخضر.");
        }
        
        // مهلة زمنية قصيرة لمنح نظام التشغيل فرصة لإكمال تحرير الكاميرا
        await new Promise(resolve => setTimeout(resolve, 250));
    }
};

// التنظيف التلقائي عند مغادرة الصفحة أو الانتقال للعبة أخرى
window.addEventListener('pagehide', () => window.CameraManager.stop());
window.addEventListener('beforeunload', () => window.CameraManager.stop());