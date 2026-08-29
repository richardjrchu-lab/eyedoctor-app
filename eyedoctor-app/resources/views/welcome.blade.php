<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retinal Pathology Interactive Workspace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between font-sans">

    <header class="bg-slate-900 border-b border-slate-800 p-5 text-center shadow-md">
        <h1 class="text-xl font-bold tracking-tight text-cyan-400 font-mono">Diabetic Retinopathy Interactive Workspace</h1>
        <p class="text-slate-400 text-xs mt-1">Draggable Pan & Zoom Viewport with Morphological Vision</p>
    </header>

    <main class="max-w-7xl w-full mx-auto p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 my-auto">
        
        <!-- Left Column: Upload, Canvas Viewport & Controls (7 Cols) -->
        <div class="lg:col-span-7 bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-xl flex flex-col justify-between">
            <div>
                <!-- Drop Box -->
                <div id="upload-box" class="border-2 border-dashed border-slate-700 rounded-lg bg-slate-950/50 hover:border-cyan-500 transition-colors cursor-pointer relative min-h-[320px] flex flex-col justify-center items-center">
                    <input type="file" id="eye_photo" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*" onchange="runInference(event)">
                    <div class="text-center p-6 pointer-events-none">
                        <svg class="w-12 h-12 text-cyan-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider block">Upload Retinal Scan</span>
                        <span class="text-[11px] text-slate-500 mt-1 block">JPEG / PNG Fundus Photos</span>
                    </div>
                </div>

                <!-- Main Interactive Workspace -->
                <div id="preview-box" class="hidden space-y-4">
                    
                    <!-- Mode Selector -->
                    <div class="flex items-center justify-between bg-slate-950 p-1.5 rounded-lg border border-slate-800">
                        <div class="grid grid-cols-3 gap-1 w-full font-mono text-[11px]">
                            <button id="btn-exudates" onclick="switchViewMode('exudates')" class="py-2 px-3 rounded-md bg-cyan-950 text-cyan-400 border border-cyan-800 font-bold uppercase transition-all">
                                Detected Exudates
                            </button>
                            <button id="btn-raw" onclick="switchViewMode('raw')" class="py-2 px-3 rounded-md bg-slate-900 text-slate-400 hover:text-slate-200 border border-transparent font-bold uppercase transition-all">
                                Raw Photo
                            </button>
                            <button id="btn-bw" onclick="switchViewMode('bw')" class="py-2 px-3 rounded-md bg-slate-900 text-slate-400 hover:text-slate-200 border border-transparent font-bold uppercase transition-all">
                                Black & White
                            </button>
                        </div>
                    </div>

                    <!-- Sliders & Reset Pan Toolbar -->
                    <div class="bg-slate-950 p-3 rounded-lg border border-slate-800 grid grid-cols-3 gap-3 font-mono text-[11px]">
                        <div>
                            <label class="text-slate-400 block mb-1">Brightness: <span id="bright-val" class="text-cyan-400">100%</span></label>
                            <input type="range" id="bright-slider" min="50" max="200" value="100" oninput="drawCanvas()" class="w-full accent-cyan-500">
                        </div>
                        <div>
                            <label class="text-slate-400 block mb-1">Contrast: <span id="contrast-val" class="text-cyan-400">100%</span></label>
                            <input type="range" id="contrast-slider" min="50" max="250" value="100" oninput="drawCanvas()" class="w-full accent-cyan-500">
                        </div>
                        <div>
                            <div class="flex justify-between">
                                <label class="text-slate-400 block mb-1">Zoom: <span id="zoom-val" class="text-cyan-400">1.0x</span></label>
                                <button onclick="resetPanZoom()" class="text-[9px] text-slate-500 hover:text-cyan-400 uppercase">Reset View</button>
                            </div>
                            <input type="range" id="zoom-slider" min="10" max="40" value="10" oninput="updateZoom(this.value)" class="w-full accent-cyan-500">
                        </div>
                    </div>

                    <!-- Interactive Canvas Screen (Click & Drag to Pan) -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span id="screen-label" class="text-[11px] font-mono text-emerald-400 uppercase font-bold">Mode: Detected Exudates</span>
                            <span id="exudate-counter-badge" class="bg-emerald-950 border border-emerald-800 text-emerald-400 text-[10px] font-mono px-2 py-0.5 rounded-full font-bold">
                                0 Lesions Marked
                            </span>
                        </div>
                        <div class="relative overflow-hidden rounded-lg border border-slate-800 bg-slate-950 h-96 flex items-center justify-center">
                            <canvas id="viewport-canvas" class="cursor-grab active:cursor-grabbing w-full h-full"></canvas>
                        </div>
                        <span class="text-[10px] text-slate-500 font-mono mt-1 block text-right">💡 Click and drag directly on the image to pan left/right/up/down when zoomed.</span>
                    </div>
                </div>
            </div>

            <button id="reset-btn" onclick="resetUI()" class="hidden mt-4 w-full bg-slate-950 border border-slate-800 hover:bg-slate-800 text-slate-400 font-mono text-xs py-2 rounded-lg font-bold uppercase">
                Upload New Image
            </button>
        </div>

        <!-- Right Column: Diagnostics Sheet (5 Cols) -->
        <div class="lg:col-span-5 bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-800">
                    <h2 class="text-xs font-bold text-cyan-400 font-mono uppercase tracking-widest">Diagnostic Stream</h2>
                    <span id="loader" class="hidden text-xs text-amber-500 font-mono animate-pulse">Filtering Pixels...</span>
                </div>

                <!-- Stage Result -->
                <div class="mb-4">
                    <span class="text-[10px] font-mono text-slate-500 uppercase font-bold block mb-1">Detected Diagnostic Stage:</span>
                    <div id="stage-badge" class="text-sm font-bold font-mono text-slate-300 bg-slate-950 p-3 rounded-lg border border-slate-800">
                        Awaiting File Upload...
                    </div>
                </div>

                <!-- Doctor Manual Overwrite Panel -->
                <div id="doctor-panel" class="hidden mb-4 p-3 bg-slate-950 rounded-lg border border-slate-800">
                    <label class="text-[10px] font-mono text-cyan-400 uppercase font-bold block mb-1">Doctor Manual Correction:</label>
                    <select id="doctor-override" onchange="overrideDiagnosis(this.value)" class="w-full bg-slate-900 border border-slate-700 text-xs font-mono text-slate-200 p-2 rounded focus:outline-none">
                        <option value="0">Stage 0: No Apparent Retinopathy</option>
                        <option value="1">Stage 1: Mild Non-Proliferative DR</option>
                        <option value="2">Stage 2: Moderate Non-Proliferative DR</option>
                        <option value="3">Stage 3: Severe Non-Proliferative DR</option>
                        <option value="4">Stage 4: Proliferative DR</option>
                        <option value="invalid">Can't Identify Stage</option>
                    </select>
                </div>

                <!-- Morphological Traces -->
                <div>
                    <span class="text-[10px] font-mono text-slate-500 uppercase font-bold block mb-1">Classical Vision Trace Output:</span>
                    <p id="desc-box" class="text-xs text-slate-400 bg-slate-950/50 p-3 rounded-lg border border-slate-800/60 font-mono leading-relaxed">
                        Upload an image scan to analyze lesions and view modes.
                    </p>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-800 flex justify-between items-center text-[10px] font-mono text-slate-500">
                <span>Pipeline: CLAHE + Morphological Vision</span>
                <span>Match Confidence: <strong id="conf-val" class="text-cyan-400">0%</strong></span>
            </div>
        </div>
    </main>

    <script>
        const stagesList = [
            "Stage 0: No Apparent Retinopathy (Normal)",
            "Stage 1: Mild Non-Proliferative Retinopathy (Abnormal)",
            "Stage 2: Moderate Non-Proliferative Retinopathy (Abnormal)",
            "Stage 3: Severe Non-Proliferative Retinopathy (Abnormal)",
            "Stage 4: Proliferative Diabetic Retinopathy (Abnormal)"
        ];

        let imageStore = { exudates: "", raw: "", bw: "" };
        let activeImage = new Image();
        
        // Canvas Pan & Zoom State
        let scale = 1.0;
        let panX = 0;
        let panY = 0;
        let isDragging = false;
        let startX = 0;
        let startY = 0;

        const canvas = document.getElementById('viewport-canvas');
        const ctx = canvas.getContext('2d');

        // Mouse Drag Event Listeners
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

            // Resize canvas to container
            canvas.width = canvas.parentElement.clientWidth;
            canvas.height = canvas.parentElement.clientHeight;

            const brightness = document.getElementById('bright-slider').value;
            const contrast = document.getElementById('contrast-slider').value;

            document.getElementById('bright-val').innerText = `${brightness}%`;
            document.getElementById('contrast-val').innerText = `${contrast}%`;

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.save();

            // Apply CSS Filters directly inside HTML5 Canvas context
            ctx.filter = `brightness(${brightness}%) contrast(${contrast}%)`;

            // Apply Translation & Scale
            ctx.translate(canvas.width / 2 + panX, canvas.height / 2 + panY);
            ctx.scale(scale, scale);

            // Draw centered image
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

        function switchViewMode(mode) {
            const label = document.getElementById('screen-label');
            const btnExudates = document.getElementById('btn-exudates');
            const btnRaw = document.getElementById('btn-raw');
            const btnBw = document.getElementById('btn-bw');

            [btnExudates, btnRaw, btnBw].forEach(btn => {
                btn.className = "py-2 px-3 rounded-md bg-slate-900 text-slate-400 hover:text-slate-200 border border-transparent font-bold uppercase transition-all";
            });

            if (mode === 'exudates') {
                activeImage.src = imageStore.exudates;
                label.innerText = "Mode: Detected Exudates";
                label.className = "text-[11px] font-mono text-emerald-400 uppercase font-bold";
                btnExudates.className = "py-2 px-3 rounded-md bg-cyan-950 text-cyan-400 border border-cyan-800 font-bold uppercase transition-all";
            } else if (mode === 'raw') {
                activeImage.src = imageStore.raw;
                label.innerText = "Mode: Raw Source Photo";
                label.className = "text-[11px] font-mono text-slate-300 uppercase font-bold";
                btnRaw.className = "py-2 px-3 rounded-md bg-slate-800 text-white border border-slate-700 font-bold uppercase transition-all";
            } else if (mode === 'bw') {
                activeImage.src = imageStore.bw;
                label.innerText = "Mode: Black & White Filter";
                label.className = "text-[11px] font-mono text-cyan-400 uppercase font-bold";
                btnBw.className = "py-2 px-3 rounded-md bg-cyan-950 text-cyan-400 border border-cyan-800 font-bold uppercase transition-all";
            }

            activeImage.onload = () => drawCanvas();
        }

        function runInference(event) {
            const input = event.target;
            if (!input.files || !input.files[0]) return;

            const file = input.files[0];
            document.getElementById('upload-box').classList.add('hidden');
            document.getElementById('preview-box').classList.remove('hidden');
            document.getElementById('loader').classList.remove('hidden');

            const formData = new FormData();
            formData.append('eye_photo', file);

            fetch('/analyze-retina', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('loader').classList.add('hidden');
                document.getElementById('reset-btn').classList.remove('hidden');
                document.getElementById('doctor-panel').classList.remove('hidden');

                imageStore.exudates = data.annotated_image || "";
                imageStore.raw = data.raw_image || "";
                imageStore.bw = data.bw_image || "";

                switchViewMode('exudates');

                const countBadge = document.getElementById('exudate-counter-badge');
                if (data.total_lesions !== undefined) {
                    countBadge.innerText = `🟢 ${data.exudate_count} Exudates | 🔴 ${data.dark_spot_count} Hemorrhages`;
                }

                if (data.stage === 'invalid') {
                    setUIDiagnosis("invalid", data.description || "Can't Identify Stage", 0);
                } else {
                    setUIDiagnosis(data.stage, data.description, data.confidence);
                }
            })
            .catch(err => {
                console.error("Inference Error:", err);
                document.getElementById('loader').classList.add('hidden');
                setUIDiagnosis("invalid", "Network error or unreadable image frame.", 0);
            });
        }

        function setUIDiagnosis(stage, desc, confidence) {
            const stageBadge = document.getElementById('stage-badge');
            const descBox = document.getElementById('desc-box');
            const confVal = document.getElementById('conf-val');
            const doctorSelect = document.getElementById('doctor-override');

            if (stage === "invalid") {
                stageBadge.innerText = "Can't Identify Stage";
                stageBadge.className = "text-sm font-bold font-mono text-rose-400 bg-slate-950 p-3 rounded-lg border border-rose-900/50";
                doctorSelect.value = "invalid";
            } else {
                stageBadge.innerText = stagesList[stage] || `Stage ${stage}`;
                stageBadge.className = "text-sm font-bold font-mono text-cyan-400 bg-slate-950 p-3 rounded-lg border border-cyan-900/50";
                doctorSelect.value = stage;
            }

            descBox.innerText = desc;
            confVal.innerText = (confidence || 0) + "%";
        }

        function overrideDiagnosis(val) {
            if (val === "invalid") {
                setUIDiagnosis("invalid", "Manually flagged as unidentifiable or non-retinal scan.", 0);
            } else {
                setUIDiagnosis(parseInt(val), "Doctor manual override applied.", 100);
            }
        }

        function resetUI() {
            location.reload();
        }
    </script>
</body>
</html>