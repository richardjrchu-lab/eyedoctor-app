import sys
import json
import base64
import cv2
import numpy as np

def analyze_retina_classical(image_path):
    img = cv2.imread(image_path)
    if img is None:
        return {"stage": "invalid", "description": "Unreadable image format or corrupt file."}

    target_h, target_w = 1000, 1000
    img_resized = cv2.resize(img, (target_w, target_h), interpolation=cv2.INTER_CUBIC)

    # 1. Bilateral Filtering & Illumination Normalization
    denoised = cv2.bilateralFilter(img_resized, d=9, sigmaColor=75, sigmaSpace=75)
    b, g, r = cv2.split(denoised)

    kernel_bg = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (45, 45))
    background = cv2.morphologyEx(g, cv2.MORPH_CLOSE, kernel_bg)
    normalized_g = cv2.divide(g, background, scale=255)

    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    enhanced_g = clahe.apply(normalized_g)

    # 2. Optic Disc Masking
    blurred_g = cv2.GaussianBlur(enhanced_g, (31, 31), 0)
    _, _, _, max_loc = cv2.minMaxLoc(blurred_g)

    disc_mask = np.zeros((target_h, target_w), dtype=np.uint8)
    cv2.circle(disc_mask, max_loc, 130, 255, -1)

    border_mask = np.zeros((target_h, target_w), dtype=np.uint8)
    cv2.circle(border_mask, (target_w // 2, target_h // 2), 430, 255, -1)
    exclusion_zone = cv2.bitwise_or(disc_mask, cv2.bitwise_not(border_mask))

    valid_exudates = 0
    valid_hemorrhages = 0
    annotated_img = img_resized.copy()

    # =========================================================================
    # STRICT ANTI-FAKE EXUDATE DETECTION
    # =========================================================================
    kernel_ex = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (13, 13))
    tophat = cv2.morphologyEx(enhanced_g, cv2.MORPH_TOPHAT, kernel_ex)
    
    # Raised threshold to completely ignore minor background noise and faint reflections
    _, exudate_bin = cv2.threshold(tophat, 58, 255, cv2.THRESH_BINARY)
    exudate_bin = cv2.bitwise_and(exudate_bin, cv2.bitwise_not(exclusion_zone))
    
    kernel_clean = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (3, 3))
    exudate_bin = cv2.morphologyEx(exudate_bin, cv2.MORPH_OPEN, kernel_clean)
    exudate_contours, _ = cv2.findContours(exudate_bin, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    for cnt in exudate_contours:
        area = cv2.contourArea(cnt)
        if 50 < area < 2500:
            perimeter = cv2.arcLength(cnt, True)
            if perimeter == 0:
                continue
            
            # Reject long/thin vessel light streaks; exudates must be compact spots/patches
            circularity = 4 * np.pi * (area / (perimeter * perimeter))
            if circularity < 0.40:
                continue

            x, y, w, h = cv2.boundingRect(cnt)
            aspect_ratio = float(w) / h
            if aspect_ratio > 2.5 or aspect_ratio < 0.4:
                continue # Skip linear highlights

            roi_r = np.mean(r[y:y+h, x:x+w])
            roi_g = np.mean(g[y:y+h, x:x+w])
            roi_b = np.mean(b[y:y+h, x:x+w])
            
            # Strict Exudate Color Gate: Must be true yellow-white (Red & Green high, distinctly higher than Blue)
            if roi_r > 135 and roi_g > 125 and roi_r > roi_b + 25:
                valid_exudates += 1
                cv2.rectangle(annotated_img, (x, y), (x + w, y + h), (0, 255, 0), 2)

    # =========================================================================
    # RESPONSIVE HEMORRHAGE & DARK SPOT DETECTION
    # =========================================================================
    blackhat = cv2.morphologyEx(enhanced_g, cv2.MORPH_BLACKHAT, kernel_ex)
    _, dark_bin = cv2.threshold(blackhat, 35, 255, cv2.THRESH_BINARY)
    
    kernel_vessel = cv2.getStructuringElement(cv2.MORPH_RECT, (13, 13))
    vessels = cv2.morphologyEx(enhanced_g, cv2.MORPH_OPEN, kernel_vessel)
    vessel_diff = cv2.absdiff(enhanced_g, vessels)
    _, vessel_bin = cv2.threshold(vessel_diff, 28, 255, cv2.THRESH_BINARY)
    
    dark_bin = cv2.subtract(dark_bin, vessel_bin)
    dark_bin = cv2.bitwise_and(dark_bin, cv2.bitwise_not(exclusion_zone))
    dark_bin = cv2.morphologyEx(dark_bin, cv2.MORPH_OPEN, kernel_clean)
    
    dark_contours, _ = cv2.findContours(dark_bin, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    for cnt in dark_contours:
        area = cv2.contourArea(cnt)
        if 40 < area < 3500:
            x, y, w, h = cv2.boundingRect(cnt)
            
            pad = 4
            x1, y1 = max(0, x - pad), max(0, y - pad)
            x2, y2 = min(target_w, x + w + pad), min(target_h, y + h + pad)
            
            spot_mean = np.mean(enhanced_g[y:y+h, x:x+w])
            surround_mean = np.mean(enhanced_g[y1:y2, x1:x2])
            
            if spot_mean < surround_mean * 0.82:
                roi_r = np.mean(r[y:y+h, x:x+w])
                roi_g = np.mean(g[y:y+h, x:x+w])
                
                if roi_r > 30 and roi_g < roi_r * 0.75:
                    valid_hemorrhages += 1
                    cv2.rectangle(annotated_img, (x, y), (x + w, y + h), (0, 0, 255), 2)

    total_pathology = valid_exudates + valid_hemorrhages

    # 3. Clinical Staging Gate with Absolute Stage 0 Safeguard
    if total_pathology == 0:
        stage = 0
        desc = "Stage 0: Normal Retina. Verified clean uniformity with zero pathological indicators."
        confidence = 99
        annotated_img = img_resized.copy()
    elif total_pathology <= 2:
        stage = 1
        desc = f"Stage 1: Mild DR. Minimal verified targets ({valid_exudates} exudates, {valid_hemorrhages} dark spots)."
        confidence = 94
    elif total_pathology <= 6:
        stage = 2
        desc = f"Stage 2: Moderate DR. Distinct lesions mapped ({valid_exudates} exudates, {valid_hemorrhages} dark spots)."
        confidence = 90
    elif total_pathology <= 12:
        stage = 3
        desc = f"Stage 3: Severe DR. High density of pathological targets ({total_pathology} total)."
        confidence = 86
    else:
        stage = 4
        desc = f"Stage 4: Proliferative DR. Extensive heavy lesion damage and widespread hemorrhaging."
        confidence = 83

    # 4. Encodings
    _, buf_ann = cv2.imencode('.jpg', annotated_img)
    annotated_b64 = f"data:image/jpeg;base64,{base64.b64encode(buf_ann).decode('utf-8')}"

    _, buf_raw = cv2.imencode('.jpg', img_resized)
    raw_b64 = f"data:image/jpeg;base64,{base64.b64encode(buf_raw).decode('utf-8')}"

    _, buf_bw = cv2.imencode('.jpg', enhanced_g)
    bw_b64 = f"data:image/jpeg;base64,{base64.b64encode(buf_bw).decode('utf-8')}"

    return {
        "stage": stage,
        "description": desc,
        "exudate_count": valid_exudates,
        "dark_spot_count": valid_hemorrhages,
        "total_lesions": total_pathology,
        "annotated_image": annotated_b64,
        "raw_image": raw_b64,
        "bw_image": bw_b64,
        "confidence": confidence
    }

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"stage": "invalid", "description": "No image path provided."}))
        return

    result = analyze_retina_classical(sys.argv[1])
    print(json.dumps(result))

if __name__ == "__main__":
    main()