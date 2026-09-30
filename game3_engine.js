// جلب child_id تلقائياً من رابط الصفحة مثل game1 و game2
const urlParams = new URLSearchParams(window.location.search);
const childId = parseInt(urlParams.get('child_id')) || 0;

document.getElementById('displayChildId').innerText = childId > 0 ? childId : 'غير محدد';

let currentStage = 0;
let score = 0;
let startTime = Date.now();
let stagesData = [];

// مراحل التقييم للحروف
const stages = [
    { letter: 'أ', options: ['أ', 'ب', 'ت'], target: 'أ' },
    { letter: 'ب', options: ['ت', 'ب', 'ث'], target: 'ب' },
    { letter: 'ج', options: ['ح', 'خ', 'ج'], target: 'ج' },
    { letter: 'س', options: ['ش', 'س', 'ص'], target: 'س' }
];

document.addEventListener("DOMContentLoaded", () => {
    if (childId <= 0) {
        alert("تنبيه: لم يتم العثور على معرف الطفل (child_id) في الرابط!");
    }
    loadStage();
});

function loadStage() {
    if (currentStage >= stages.length) {
        finishGame();
        return;
    }

    const stage = stages[currentStage];
    document.getElementById('targetLetter').innerText = stage.letter;
    
    const container = document.getElementById('optionsContainer');
    container.innerHTML = '';

    stage.options.forEach(opt => {
        const btn = document.createElement('button');
        btn.className = 'option-btn';
        btn.innerText = opt;
        btn.onclick = () => selectAnswer(opt);
        container.appendChild(btn);
    });

    updateProgress();
    startTime = Date.now();
}

function selectAnswer(selected) {
    const responseTime = (Date.now() - startTime) / 1000;
    const isCorrect = selected === stages[currentStage].target;

    if (isCorrect) score += 25;

    stagesData.push({
        stage: currentStage + 1,
        selected: selected,
        correct: isCorrect,
        responseTime: responseTime
    });

    currentStage++;
    loadStage();
}

function updateProgress() {
    const progress = (currentStage / stages.length) * 100;
    document.getElementById('progressBar').style.width = progress + '%';
    document.getElementById('scoreText').innerText = score;
}

// التفاعل الصوتي
let recognition = null;
if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.lang = 'ar-SA';
    recognition.continuous = false;

    recognition.onresult = (event) => {
        const spokenText = event.results[0][0].transcript.trim();
        document.getElementById('speechFeedback').innerText = "تم النطق: " + spokenText;
        
        if (spokenText.includes(stages[currentStage].target)) {
            selectAnswer(stages[currentStage].target);
        } else {
            selectAnswer(spokenText);
        }
    };

    recognition.onerror = () => {
        document.getElementById('speechFeedback').innerText = "لم يتم التعرف على الصوت بشكل جيد، حاول مجدداً.";
    };
}

function toggleSpeechRecognition() {
    if (!recognition) {
        alert("متصفحك لا يدعم التعرف الصوتي المباشر.");
        return;
    }
    const micBtn = document.getElementById('micBtn');
    micBtn.classList.add('listening');
    document.getElementById('speechFeedback').innerText = "جاري الاستماع.. انطق الحرف الآن";
    recognition.start();
}

function finishGame() {
    const finalScoreRatio = score / 100; // نسبة من 0 إلى 1 لجدول game_results

    const payload = {
        child_id: childId,
        score: finalScoreRatio,
        details: stagesData
    };

    saveReportData(payload);
}