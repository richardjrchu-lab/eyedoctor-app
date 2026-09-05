<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diabetic Retinopathy Detection System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-slate-800 text-slate-100 min-h-screen flex flex-col justify-between font-sans">

    <header class="bg-slate-900 border-b border-slate-700 p-5 text-center shadow-md">
        <h1 class="text-xl font-bold tracking-tight text-slate-100 font-mono">Diabetic Retinopathy Detection System</h1>
        <p class="text-slate-400 text-xs mt-1">EfficientNetB4 Deep Learning Classifier &mdash; ICDR 5-Stage Grading</p>
        <a href="{{ route('history') }}" class="text-[10px] font-mono text-slate-500 hover:text-slate-300 uppercase block mt-2">View Prediction History &rarr;</a>
    </header>

    <main class="max-w-7xl w-full mx-auto p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 my-auto">

        <!-- Left Column: Upload, Canvas Viewport & Controls -->
        <div class="lg:col-span-7 bg-slate-900 border border-slate-700 rounded-xl p-5 shadow-xl flex flex-col justify-between">
            <div>
                <!-- Drop Box -->
                <div id="upload-box" class="border-2 border-dashed border-slate-600 rounded-lg bg-slate-800/50 hover:border-slate-400 transition-colors cursor-pointer relative min-h-[320px] flex flex-col justify-center items-center">
                    <input type="file" id="eye_photo" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*" onchange="runInference(event)">
                    <div class="text-center p-6 pointer-events-none">
                        <svg class="w-12 h-12 text-slate-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span class="text-xs font-bold text-slate-300 uppercase tracking-wider block">Upload Retinal Fundus Scan</span>
                        <span class="text-[11px] text-slate-500 mt-1 block">JPEG / PNG Color Fundus Photographs</span>
                    </div>
                </div>

                <!-- Main Interactive Workspace -->
                <div id="preview-box" class="hidden space-y-4">

                    <!-- Image adjustment toolbar -->
                    <div class="bg-slate-800 p-3 rounded-lg border border-slate-700 grid grid-cols-3 gap-3 font-mono text-[11px]">
                        <div>
                            <label class="text-slate-400 block mb-1">Brightness: <span id="bright-val" class="text-slate-200">100%</span></label>
                            <input type="range" id="bright-slider" min="50" max="200" value="100" oninput="drawCanvas()" class="w-full accent-slate-400">
                        </div>
                        <div>
                            <label class="text-slate-400 block mb-1">Contrast: <span id="contrast-val" class="text-slate-200">100%</span></label>
                            <input type="range" id="contrast-slider" min="50" max="250" value="100" oninput="drawCanvas()" class="w-full accent-slate-400">
                        </div>
                        <div>
                            <div class="flex justify-between">
                                <label class="text-slate-400 block mb-1">Zoom: <span id="zoom-val" class="text-slate-200">1.0x</span></label>
                                <button onclick="resetPanZoom()" class="text-[9px] text-slate-500 hover:text-slate-300 uppercase">Reset View</button>
                            </div>
                            <input type="range" id="zoom-slider" min="10" max="40" value="10" oninput="updateZoom(this.value)" class="w-full accent-slate-400">
                        </div>
                    </div>

                    <!-- Interactive Canvas Screen (Click & Drag to Pan) -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span id="screen-label" class="text-[11px] font-mono text-slate-300 uppercase font-bold">Uploaded Fundus Image</span>
                        </div>
                        <div class="relative overflow-hidden rounded-lg border border-slate-700 bg-slate-800 h-96 flex items-center justify-center">
                            <canvas id="viewport-canvas" class="cursor-grab active:cursor-grabbing w-full h-full"></canvas>
                        </div>
                        <span class="text-[10px] text-slate-500 font-mono mt-1 block text-right">Click and drag on the image to pan when zoomed.</span>
                    </div>
                </div>
            </div>

            <button id="reset-btn" onclick="resetUI()" class="hidden mt-4 w-full bg-slate-800 border border-slate-700 hover:bg-slate-700 text-slate-400 font-mono text-xs py-2 rounded-lg font-bold uppercase">
                Upload New Image
            </button>
        </div>

        <!-- Right Column: Diagnostics -->
        <div class="lg:col-span-5 bg-slate-900 border border-slate-700 rounded-xl p-6 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-700">
                    <h2 class="text-xs font-bold text-slate-300 font-mono uppercase tracking-widest">Model Prediction</h2>
                    <span id="loader" class="hidden text-xs text-slate-400 font-mono">Analyzing<span id="loader-dots">...</span></span>
                </div>

                <!-- Referral Decision -- the headline, above the ICDR grade -->
                <div id="referral-box" class="hidden mb-4 p-4 rounded-lg border-2">
                    <span id="referral-headline" class="text-sm font-bold font-mono uppercase tracking-wide block"></span>
                    <p id="referral-subtext" class="text-[11px] font-mono mt-1 opacity-80"></p>

                    <!-- Probability vs threshold scale -->
                    <div class="mt-3">
                        <div class="flex justify-between text-[9px] font-mono text-slate-400 mb-1">
                            <span>0.0</span>
                            <span>Referral probability</span>
                            <span>1.0</span>
                        </div>
                        <div class="relative w-full h-2 bg-slate-950 rounded-full border border-slate-700">
                            <!-- Threshold marker -->
                            <div id="threshold-marker" class="absolute top-[-3px] w-0.5 h-3.5 bg-slate-300"></div>
                            <!-- Probability fill -->
                            <div id="probability-fill" class="h-full rounded-full transition-all"></div>
                        </div>
                        <div class="flex justify-between text-[9px] font-mono text-slate-500 mt-1">
                            <span id="probability-val-label"></span>
                            <span id="threshold-val-label"></span>
                        </div>
                    </div>
                </div>

                <!-- Stage Result -- secondary to the referral decision -->
                <div class="mb-4">
                    <span class="text-[10px] font-mono text-slate-500 uppercase font-bold block mb-1">Predicted ICDR Stage:</span>
                    <div id="stage-badge" class="text-sm font-bold font-mono text-slate-300 bg-slate-800 p-3 rounded-lg border border-slate-700">
                        Awaiting File Upload...
                    </div>
                </div>

                <!-- Screening referral flag: only shown when NOT already referred,
                     to avoid a double warning saying the same thing twice -->
                <div id="review-flag" class="hidden mb-4 p-3 bg-amber-950/40 rounded-lg border border-amber-800/60">
                    <div class="flex items-start gap-2">
                        <span class="text-amber-500 text-sm leading-none mt-0.5">&#9888;</span>
                        <div>
                            <span class="text-[10px] font-mono text-amber-400 uppercase font-bold block mb-1">Flagged for Clinician Review</span>
                            <p id="review-flag-text" class="text-[11px] text-amber-200/80 font-mono leading-relaxed"></p>
                        </div>
                    </div>
                </div>

                <!-- Per-class probability breakdown (real model output) -->
                <div id="probability-panel" class="hidden mb-4">
                    <span class="text-[10px] font-mono text-slate-500 uppercase font-bold block mb-2">Class Probability Distribution:</span>
                    <div id="prob-bars" class="space-y-2"></div>
                    <p class="text-[10px] text-slate-500 font-mono mt-2 leading-relaxed">
                        Mild NPDR precision is limited (~47%) &mdash; when the grade reads "Mild", it is
                        often actually Moderate. The referral decision above is unaffected by this
                        boundary; the grade itself is least reliable here.
                    </p>
                </div>

                <!-- Doctor Manual Overwrite Panel -->
                <div id="doctor-panel" class="hidden mb-4 p-3 bg-slate-800 rounded-lg border border-slate-700">
                    <label class="text-[10px] font-mono text-slate-300 uppercase font-bold block mb-1">Clinician Review / Correction:</label>
                    <select id="doctor-override" onchange="overrideDiagnosis(this.value)" class="w-full bg-slate-900 border border-slate-600 text-xs font-mono text-slate-200 p-2 rounded focus:outline-none mb-2">
                        <option value="0">Stage 0: No Apparent Retinopathy</option>
                        <option value="1">Stage 1: Mild Non-Proliferative DR</option>
                        <option value="2">Stage 2: Moderate Non-Proliferative DR</option>
                        <option value="3">Stage 3: Severe Non-Proliferative DR</option>
                        <option value="4">Stage 4: Proliferative DR</option>
                        <option value="invalid">Cannot Determine Stage</option>
                    </select>
                    <textarea id="correction-note" placeholder="Optional note (e.g. reasoning for this correction)" class="w-full bg-slate-900 border border-slate-600 text-xs font-mono text-slate-200 p-2 rounded focus:outline-none mb-2" rows="2"></textarea>
                    <button id="confirm-correction-btn" onclick="submitCorrection()" class="w-full bg-slate-700 hover:bg-slate-600 border border-slate-600 text-slate-200 font-mono text-xs py-2 rounded font-bold uppercase">
                        Confirm Correction
                    </button>
                    <p id="correction-status" class="text-[10px] font-mono mt-2 hidden"></p>
                </div>

                <!-- ICDR Reference Panel (collapsible) -->
                <div class="mb-4">
                    <button onclick="document.getElementById('icdr-panel').classList.toggle('hidden')" class="w-full text-left text-[10px] font-mono text-slate-400 uppercase font-bold flex justify-between items-center py-1">
                        <span>ICDR Grading Reference</span>
                        <span>&#9662;</span>
                    </button>
                    <div id="icdr-panel" class="hidden mt-2 p-3 bg-slate-800 rounded-lg border border-slate-700 text-[11px] text-slate-400 font-mono leading-relaxed space-y-2">
                        <p><strong class="text-slate-300">No DR:</strong> No visible abnormalities on fundus exam.</p>
                        <p><strong class="text-slate-300">Mild NPDR:</strong> Microaneurysms only.</p>
                        <p><strong class="text-slate-300">Moderate NPDR:</strong> More than microaneurysms but less than severe; may include dot-blot hemorrhages, hard exudates, or early venous changes.</p>
                        <p><strong class="text-slate-300">Severe NPDR:</strong> Extensive hemorrhages, definite venous beading, or intraretinal microvascular abnormalities, without neovascularization.</p>
                        <p><strong class="text-slate-300">PDR:</strong> Neovascularization or vitreous/preretinal hemorrhage present.</p>
                        <p class="text-slate-500 pt-1 border-t border-slate-700">Adapted from the International Clinical Diabetic Retinopathy severity scale (Wilkinson et al., 2003).</p>
                    </div>
                </div>

                <!-- Status / notes -->
                <div>
                    <span class="text-[10px] font-mono text-slate-500 uppercase font-bold block mb-1">Notes:</span>
                    <p id="desc-box" class="text-xs text-slate-400 bg-slate-800/50 p-3 rounded-lg border border-slate-700/60 font-mono leading-relaxed">
                        Upload a color fundus photograph to run classification.
                    </p>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-700 space-y-1 text-[10px] font-mono text-slate-500">
                <div class="flex justify-between items-center">
                    <span>Model: EfficientNetB4 + TTA</span>
                    <span>Confidence: <strong id="conf-val" class="text-slate-300">0%</strong></span>
                </div>
                <div class="flex justify-between items-center">
                    <span>Inference time: <span id="latency-val" class="text-slate-400">&mdash;</span></span>
                </div>
                <p class="text-[9px] text-amber-600/80 pt-2 leading-relaxed border-t border-slate-700/60 mt-2">
                    For clinical decision support only. Predictions must be reviewed by a qualified
                    eye care professional and are not a substitute for clinical diagnosis.
                </p>
            </div>
        </div>
    </main>

    <script>
        // ================================================================
        // CONFIG -- points at Laravel's /predict route, which internally
        // calls FastAPI and saves the result to the database
        // ================================================================
        const API_ENDPOINT = "{{ route('predict') }}";

        const stagesList = [
            "Stage 0: No Apparent Retinopathy (No DR)",
            "Stage 1: Mild Non-Proliferative DR",
            "Stage 2: Moderate Non-Proliferative DR",
            "Stage 3: Severe Non-Proliferative DR",
            "Stage 4: Proliferative DR (PDR)"
        ];

        // Maps the API's class label strings to their ICDR stage index
        const labelToStageIndex = {
            "No DR": 0,
            "Mild NPDR": 1,
            "Moderate NPDR": 2,
            "Severe NPDR": 3,
            "PDR": 4
        };

        // Ordinal severity colour scale, matching clinical triage coding:
        // neutral -> yellow -> amber -> orange -> red
        const stageColors = [
            { bg: "bg-slate-800", border: "border-slate-700", text: "text-slate-300" },
            { bg: "bg-yellow-950/40", border: "border-yellow-800/60", text: "text-yellow-300" },
            { bg: "bg-amber-950/40", border: "border-amber-800/60", text: "text-amber-300" },
            { bg: "bg-orange-950/40", border: "border-orange-800/60", text: "text-orange-300" },
            { bg: "bg-red-950/40", border: "border-red-800/60", text: "text-red-300" }
        ];

        let activeImage = new Image();
        let currentPredictionId = null;

        // Canvas Pan & Zoom State
        let scale = 1.0;
        let panX = 0;
        let panY = 0;
        let isDragging = false;
        let startX = 0;
        let startY = 0;

        const canvas = document.getElementById('viewport-canvas');
        const ctx = canvas.getContext('2d');

        canvas.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX - panX;
            startY = e.clientY - panY;
        });

        window.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            panX = e.clientX - startX;
            panY = e.clientY - startY;
            drawCanvas();
        });

        window.addEventListener('mouseup', () => { isDragging = false; });

        function drawCanvas() {
            if (!activeImage.src) return;

            canvas.width = canvas.parentElement.clientWidth;
            canvas.height = canvas.parentElement.clientHeight;

            const brightness = document.getElementById('bright-slider').value;
            const contrast = document.getElementById('contrast-slider').value;

            document.getElementById('bright-val').innerText = `${brightness}%`;
            document.getElementById('contrast-val').innerText = `${contrast}%`;

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.save();
            ctx.filter = `brightness(${brightness}%) contrast(${contrast}%)`;
            ctx.translate(canvas.width / 2 + panX, canvas.height / 2 + panY);
            ctx.scale(scale, scale);

            const aspect = activeImage.width / activeImage.height;
            let drawW = canvas.width;
            let drawH = canvas.width / aspect;
            if (drawH > canvas.height) {
                drawH = canvas.height;
                drawW = canvas.height * aspect;
            }

            ctx.drawImage(activeImage, -drawW / 2, -drawH / 2, drawW, drawH);
            ctx.restore();
        }

        function updateZoom(val) {
            scale = val / 10;
            document.getElementById('zoom-val').innerText = `${scale.toFixed(1)}x`;
            drawCanvas();
        }

        function resetPanZoom() {
            scale = 1.0;
            panX = 0;
            panY = 0;
            document.getElementById('zoom-slider').value = 10;
            document.getElementById('zoom-val').innerText = "1.0x";
            drawCanvas();
        }

        // ================================================================
        // Loading state -- inference takes 6-10s, so give it an honest,
        // moving indicator rather than a spinner that looks stuck
        // ================================================================
        let loaderInterval = null;
        function startLoader() {
            const loader = document.getElementById('loader');
            const dots = document.getElementById('loader-dots');
            loader.classList.remove('hidden');
            let n = 0;
            loaderInterval = setInterval(() => {
                n = (n + 1) % 4;
                dots.innerText = '.'.repeat(n) + ' (this can take up to 10s)';
            }, 500);
        }
        function stopLoader() {
            const loader = document.getElementById('loader');
            loader.classList.add('hidden');
            if (loaderInterval) {
                clearInterval(loaderInterval);
                loaderInterval = null;
            }
        }

        // ================================================================
        // Send the uploaded image to Laravel, which handles storage,
        // calls FastAPI internally, and saves the prediction result
        // ================================================================
        function runInference(event) {
            const input = event.target;
            if (!input.files || !input.files[0]) return;

            const file = input.files[0];
            document.getElementById('upload-box').classList.add('hidden');
            document.getElementById('preview-box').classList.remove('hidden');
            startLoader();

            // Show the uploaded image immediately in the canvas viewport,
            // read locally in the browser -- no need to wait for the API
            const reader = new FileReader();
            reader.onload = (e) => {
                activeImage.src = e.target.result;
                activeImage.onload = () => drawCanvas();
            };
            reader.readAsDataURL(file);

            const formData = new FormData();
            formData.append('file', file);

            fetch(API_ENDPOINT, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
            .then(async (res) => {
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.detail || `Server returned ${res.status}`);
                }
                return data;
            })
            .then(data => {
                stopLoader();
                document.getElementById('reset-btn').classList.remove('hidden');
                document.getElementById('doctor-panel').classList.remove('hidden');
                document.getElementById('probability-panel').classList.remove('hidden');

                const stageIndex = labelToStageIndex[data.predicted_label];
                const confidencePct = Math.round((data.confidence || 0) * 100);

                setUIDiagnosis(
                    stageIndex,
                    `Classified as ${data.predicted_label} with ${confidencePct}% confidence. `
                    + `Review the probability distribution below before confirming.`,
                    confidencePct
                );

                renderReferralDecision(data);

                const reviewFlag = document.getElementById('review-flag');
                if (!data.referable && data.flagged_for_review) {
                    const anyDrPct = ((data.any_dr_probability || 0) * 100).toFixed(1);
                    document.getElementById('review-flag-text').innerText =
                        `The model reports No DR, but assigns ${anyDrPct}% combined probability `
                        + `across the DR stages. Early lesions such as microaneurysms are easily `
                        + `missed at this image scale. Manual review is recommended before `
                        + `recording this as a negative screen.`;
                    reviewFlag.classList.remove('hidden');
                } else {
                    reviewFlag.classList.add('hidden');
                }

                renderProbabilityBars(data.class_probabilities || []);

                document.getElementById('latency-val').innerText =
                    data.inference_time_ms ? `${Math.round(data.inference_time_ms)} ms` : "\u2014";

                currentPredictionId = data.prediction_id || null;
            })
            .catch(err => {
                console.error("Inference error:", err);
                stopLoader();
                document.getElementById('reset-btn').classList.remove('hidden');
                document.getElementById('referral-box').classList.add('hidden');
                setUIDiagnosis("invalid", err.message || "Could not reach the model server.", 0);
            });
        }

        // Renders the referral decision -- the headline result, above the
        // ICDR grade, plus the probability-vs-threshold scale
        function renderReferralDecision(data) {
            const box = document.getElementById('referral-box');
            const headline = document.getElementById('referral-headline');
            const subtext = document.getElementById('referral-subtext');
            const marker = document.getElementById('threshold-marker');
            const fill = document.getElementById('probability-fill');
            const probLabel = document.getElementById('probability-val-label');
            const threshLabel = document.getElementById('threshold-val-label');

            if (data.referable === undefined) {
                box.classList.add('hidden');
                return;
            }

            box.classList.remove('hidden');

            const prob = data.referable_probability || 0;
            const threshold = data.referral_threshold || 0;

            if (data.referable) {
                box.className = "mb-4 p-4 rounded-lg border-2 bg-red-950/40 border-red-700";
                headline.innerText = "Refer to Specialist";
                headline.className = "text-sm font-bold font-mono uppercase tracking-wide block text-red-300";
                subtext.innerText = "This case meets the referral threshold and warrants specialist follow-up.";
                fill.className = "h-full rounded-full transition-all bg-red-500";
            } else {
                box.className = "mb-4 p-4 rounded-lg border-2 bg-emerald-950/30 border-emerald-800";
                headline.innerText = "No Referral Indicated";
                headline.className = "text-sm font-bold font-mono uppercase tracking-wide block text-emerald-300";
                subtext.innerText = "This case falls below the referral threshold.";
                fill.className = "h-full rounded-full transition-all bg-emerald-500";
            }

            fill.style.width = `${(prob * 100).toFixed(1)}%`;
            marker.style.left = `calc(${(threshold * 100).toFixed(1)}% - 1px)`;

            probLabel.innerText = `P = ${prob.toFixed(3)}`;
            threshLabel.innerText = `Threshold: ${threshold.toFixed(2)}`;
        }

        // Renders the real per-class probabilities returned by the model
        function renderProbabilityBars(classProbabilities) {
            const container = document.getElementById('prob-bars');
            container.innerHTML = "";

            classProbabilities.forEach(item => {
                const pct = (item.probability * 100);
                const row = document.createElement('div');
                row.innerHTML = `
                    <div class="flex justify-between text-[10px] font-mono mb-0.5">
                        <span class="text-slate-400">${item.label}</span>
                        <span class="text-slate-300">${pct.toFixed(1)}%</span>
                    </div>
                    <div class="w-full bg-slate-950 rounded-full h-1.5 border border-slate-700">
                        <div class="bg-slate-400 h-full rounded-full transition-all" style="width: ${pct.toFixed(1)}%"></div>
                    </div>
                `;
                container.appendChild(row);
            });
        }

        function setUIDiagnosis(stage, desc, confidence) {
            const stageBadge = document.getElementById('stage-badge');
            const descBox = document.getElementById('desc-box');
            const confVal = document.getElementById('conf-val');
            const doctorSelect = document.getElementById('doctor-override');

            if (stage === "invalid" || stage === undefined) {
                stageBadge.innerText = "Cannot Determine Stage";
                stageBadge.className = "text-sm font-bold font-mono text-rose-400 bg-slate-800 p-3 rounded-lg border border-rose-900/50";
                doctorSelect.value = "invalid";
            } else {
                const colors = stageColors[stage] || stageColors[0];
                stageBadge.innerText = stagesList[stage] || `Stage ${stage}`;
                stageBadge.className = `text-sm font-bold font-mono ${colors.text} ${colors.bg} p-3 rounded-lg border ${colors.border}`;
                doctorSelect.value = stage;
            }

            descBox.innerText = desc;
            confVal.innerText = (confidence || 0) + "%";
        }

        function overrideDiagnosis(val) {
            if (val === "invalid") {
                setUIDiagnosis("invalid", "Manually marked as unidentifiable by reviewing clinician.", 0);
            } else {
                setUIDiagnosis(parseInt(val), "Clinician override applied \u2014 this supersedes the model prediction.", 100);
            }
        }

        // ================================================================
        // Send a doctor's correction to Laravel, saved to the corrections
        // table -- linked to whichever prediction is currently displayed
        // ================================================================
        function submitCorrection() {
            const select = document.getElementById('doctor-override');
            const note = document.getElementById('correction-note').value;
            const status = document.getElementById('correction-status');
            const val = select.value;

            if (val === "invalid") {
                status.innerText = "Cannot save 'Cannot Determine Stage' as a correction.";
                status.className = "text-[10px] font-mono mt-2 text-rose-400";
                status.classList.remove('hidden');
                return;
            }

            if (!currentPredictionId) {
                status.innerText = "No active prediction to correct.";
                status.className = "text-[10px] font-mono mt-2 text-rose-400";
                status.classList.remove('hidden');
                return;
            }

            fetch(`/predictions/${currentPredictionId}/correct`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    corrected_class: parseInt(val),
                    note: note || null
                })
            })
            .then(async (res) => {
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to save correction.');
                return data;
            })
            .then(() => {
                status.innerText = "Correction saved.";
                status.className = "text-[10px] font-mono mt-2 text-emerald-400";
                status.classList.remove('hidden');
            })
            .catch(err => {
                status.innerText = err.message || "Could not save correction.";
                status.className = "text-[10px] font-mono mt-2 text-rose-400";
                status.classList.remove('hidden');
            });
        }

        function resetUI() {
            location.reload();
        }
    </script>
</body>
</html>