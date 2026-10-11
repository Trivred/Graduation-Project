const CONFIG = window.VEGETATION_CONFIG || {};

const diseaseGroups = CONFIG.diseaseGroups || {};
const YEAR_MIN = Number(CONFIG.yearMin || 1990);
const YEAR_MAX = Number(CONFIG.yearMax || new Date().getFullYear());
const FOCUS_REQUEST = CONFIG.focus || {};

let selectedYear = Number(CONFIG.defaultYear || YEAR_MAX);
let selectedRegion = "";
let selectedDensity = "";
let selectedMainType = "all";
let selectedSubType = "";
let activeRegionName = "";
let activePopupLatLng = null;
let currentMapData = [];
let sliderTimer = null;
let playTimer = null;
let isPlaying = false;
let isDarkMode = false;
let hasLoadedOnce = false;
let latestRequestId = 0;
let hasHandledExternalFocus = false;
let lastRenderedDataSize = 0;
let zoomRenderTimer = null;

const INITIAL_MAP_VIEW = {
    center: [23.8, 45],
    zoom: 6
};

const SAUDI_COORDINATE_LIMITS = {
    minLat: 16,
    maxLat: 33,
    minLon: 34,
    maxLon: 56
};

function hasRealValue(value) {
    return value !== undefined && value !== null && String(value).trim() !== "";
}

function readOptionalNumber(value) {
    if (!hasRealValue(value)) return NaN;

    const n = Number(value);
    return Number.isFinite(n) ? n : NaN;
}

function isSaudiCoordinate(lat, lon) {
    return Number.isFinite(lat) &&
        Number.isFinite(lon) &&
        lat >= SAUDI_COORDINATE_LIMITS.minLat &&
        lat <= SAUDI_COORDINATE_LIMITS.maxLat &&
        lon >= SAUDI_COORDINATE_LIMITS.minLon &&
        lon <= SAUDI_COORDINATE_LIMITS.maxLon;
}

let heatLayers = {
    density: null,
    rarity: null,
    toxicity: null
};

let lastHeatBreakdown = {
    density: 0,
    rarity: 0,
    toxicity: 0,
    total: 0
};

const yearSlider = document.getElementById("yearSlider");
const yearValue = document.getElementById("yearValue");
const mapYearBadge = document.getElementById("mapYearBadge");
const mapNote = document.getElementById("mapNote");
const loadingNote = document.getElementById("loadingNote");
const analysisBox = document.getElementById("analysisBox");
const regionFilter = document.getElementById("regionFilter");
const regionSearch = document.getElementById("regionSearch");
const densityFilter = document.getElementById("densityFilter");
const subDiseaseFilter = document.getElementById("subDiseaseFilter");
const yearStatsBox = document.getElementById("yearStatsBox");
const playBtn = document.getElementById("playBtn");
const mapModeBtn = document.getElementById("mapModeBtn");
const refreshMapBtn = document.getElementById("refreshMapBtn");
const toggleDensity = document.getElementById("toggleDensity");
const toggleRarity = document.getElementById("toggleRarity");
const toggleToxicity = document.getElementById("toggleToxicity");

const map = L.map("map", {
    zoomControl: true,
    preferCanvas: true,
    scrollWheelZoom: true,
    doubleClickZoom: true,
    touchZoom: true,
    boxZoom: true,
    keyboard: true,
    dragging: true,
    zoomSnap: 0.25,
    zoomDelta: 0.5,
    wheelPxPerZoomLevel: 80
}).setView(INITIAL_MAP_VIEW.center, INITIAL_MAP_VIEW.zoom);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: "Leaflet | © OpenStreetMap"
}).addTo(map);

const markerLayer = L.layerGroup().addTo(map);

const greenPinIcon = L.divIcon({
    className: "vegetation-pin-wrapper",
    html: '<div class="leaflet-vegetation-pin"></div>',
    iconSize: [16, 22],
    iconAnchor: [8, 19],
    popupAnchor: [0, -18]
});

const HEAT_OFFSETS = [
    [0, 0, 1.00],
    [1, 0, 0.92],
    [-1, 0, 0.92],
    [0, 1, 0.92],
    [0, -1, 0.92],
    [0.72, 0.72, 0.86],
    [-0.72, 0.72, 0.86],
    [0.72, -0.72, 0.86],
    [-0.72, -0.72, 0.86],
    [1.45, 0, 0.76],
    [-1.45, 0, 0.76],
    [0, 1.45, 0.76],
    [0, -1.45, 0.76],
    [1.20, 1.20, 0.70],
    [-1.20, 1.20, 0.70],
    [1.20, -1.20, 0.70],
    [-1.20, -1.20, 0.70],
    [2.05, 0, 0.58],
    [-2.05, 0, 0.58],
    [0, 2.05, 0.58],
    [0, -2.05, 0.58],
    [1.75, 1.05, 0.53],
    [-1.75, 1.05, 0.53],
    [1.75, -1.05, 0.53],
    [-1.75, -1.05, 0.53],
    [1.05, 1.75, 0.50],
    [-1.05, 1.75, 0.50],
    [1.05, -1.75, 0.50],
    [-1.05, -1.75, 0.50],
    [2.45, 0.75, 0.42],
    [-2.45, 0.75, 0.42],
    [2.45, -0.75, 0.42],
    [-2.45, -0.75, 0.42],
    [0.75, 2.45, 0.42],
    [-0.75, 2.45, 0.42],
    [0.75, -2.45, 0.42],
    [-0.75, -2.45, 0.42]
];

function toggleDarkMode() {
    isDarkMode = !isDarkMode;
    const mapElement = document.getElementById("map");

    if (isDarkMode) {
        mapElement.classList.add("map-dark");
        mapModeBtn.innerText = "☀️ Light Map";
    } else {
        mapElement.classList.remove("map-dark");
        mapModeBtn.innerText = "🌙 Dark Map";
    }
}

function clampScore(v) {
    const n = Number(v);
    return Number.isFinite(n) ? Math.max(0, Math.min(100, n)) : 0;
}

function readNumber(item, keys, defaultValue = 0) {
    for (const key of keys) {
        if (item && item[key] !== undefined && item[key] !== null && item[key] !== "") {
            const n = Number(item[key]);

            if (Number.isFinite(n)) {
                return n;
            }
        }
    }

    return defaultValue;
}

function getRawDensity(item) {
    return clampScore(readNumber(item, [
        "density",
        "density_score",
        "vegetation_density",
        "coverage_score",
        "coverage_percentage"
    ], 0));
}

function getRawRarity(item) {
    return clampScore(readNumber(item, [
        "rarity",
        "rarity_score",
        "rare_score"
    ], 0));
}

function getRawToxicity(item) {
    return clampScore(readNumber(item, [
        "toxicity",
        "toxicity_score",
        "max_toxicity_level"
    ], 0));
}

function getTimelineProgress(itemOrYear = selectedYear) {
    const rawYear = typeof itemOrYear === "object" ? getItemYear(itemOrYear) : itemOrYear;
    const year = Number(rawYear);

    if (!Number.isFinite(year) || YEAR_MAX <= YEAR_MIN) {
        return 1;
    }

    return Math.max(0, Math.min(1, (year - YEAR_MIN) / (YEAR_MAX - YEAR_MIN)));
}

function getTimelineAdjustedScore(item, rawScore, type) {
    const score = clampScore(rawScore);
    const progress = getTimelineProgress(item);

    if (type === "density") {
        const improvement = 38 * Math.pow(progress, 1.05);
        const oldYearReduction = 6 * (1 - progress);

        return clampScore(score + improvement - oldYearReduction);
    }

    if (type === "rarity") {
        const remainingFactor = 1 - (0.78 * progress);

        return clampScore(score * remainingFactor);
    }

    if (type === "toxicity") {
        const remainingFactor = 1 - (0.82 * progress);

        return clampScore(score * remainingFactor);
    }

    return score;
}

function getDensity(item) {
    return getTimelineAdjustedScore(item, getRawDensity(item), "density");
}

function getRarity(item) {
    const rawScore = getRawRarity(item);
    const rareCount = Number(item && item.rare_count ? item.rare_count : 0);
    const score = rawScore > 0 ? rawScore : (rareCount > 0 ? 64 : 0);

    return getTimelineAdjustedScore(item, score, "rarity");
}

function getToxicity(item) {
    const rawScore = getRawToxicity(item);
    const toxicCount = Number(item && item.toxic_count ? item.toxic_count : 0);
    const score = rawScore > 0 ? rawScore : (toxicCount > 0 ? 66 : 0);

    return getTimelineAdjustedScore(item, score, "toxicity");
}

function getLat(item) {
    return readNumber(item, ["lat", "latitude", "region_lat"], NaN);
}

function getLon(item) {
    return readNumber(item, ["lon", "lng", "longitude", "region_lon"], NaN);
}

function getRegionName(item) {
    return String(item.region || item.region_name || item.name || "");
}

function getItemYear(item) {
    return item.year || item.metric_year || selectedYear;
}

function updateTimelineSliderGradient() {
    if (!yearSlider) return;

    const min = Number(yearSlider.min || YEAR_MIN);
    const max = Number(yearSlider.max || YEAR_MAX);
    const value = Number(yearSlider.value || selectedYear);
    const percent = max > min ? ((value - min) / (max - min)) * 100 : 100;

    yearSlider.style.background = `linear-gradient(270deg, #ffffff 0%, #f0fdf4 24%, #bbf7d0 50%, #4ade80 74%, #14532d 100%)`;
    yearSlider.style.direction = "rtl";
    yearSlider.setAttribute("dir", "rtl");
    yearSlider.setAttribute("aria-valuetext", `السنة ${value}، الأبيض عند ${min} والأخضر عند ${max}`);
    yearSlider.style.setProperty("--year-progress", `${Math.max(0, Math.min(100, percent))}%`);
}

function setMapNote(message) {
    if (mapNote) {
        mapNote.innerText = message;
    }
}

function setSelectedYear(year) {
    selectedYear = Number(year);

    if (yearValue) {
        yearValue.innerText = selectedYear;
    }

    if (yearSlider) {
        yearSlider.value = selectedYear;
        updateTimelineSliderGradient();
    }

    if (mapYearBadge) {
        mapYearBadge.innerText = `السنة: ${selectedYear}`;
    }
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function safeList(list) {
    if (!Array.isArray(list) || list.length === 0) return "لا يوجد";
    return list.map(escapeHtml).join("، ");
}

function densityLevelText(value) {
    const v = clampScore(value);

    if (v >= 80) return "Very High / مرتفعة جدًا";
    if (v >= 60) return "High / مرتفعة";
    if (v >= 30) return "Medium / متوسطة";
    return "Low / منخفضة";
}

function normalizeRegionName(value) {
    return String(value || "")
        .toLowerCase()
        .replace(/[إأآا]/g, "ا")
        .replace(/ة/g, "ه")
        .replace(/ى/g, "ي")
        .replace(/[ؤئ]/g, "ء")
        .replace(/[^a-z0-9\u0600-\u06FF]+/g, "")
        .trim();
}

function isRareRegion(item) {
    return Number(item && item.rare_count ? item.rare_count : 0) > 0 ||
           getRawRarity(item) > 0 ||
           (Array.isArray(item && item.rare_plants) && item.rare_plants.length > 0);
}

function isToxicRegion(item) {
    return Number(item && item.toxic_count ? item.toxic_count : 0) > 0 ||
           getRawToxicity(item) > 0 ||
           (Array.isArray(item && item.toxic_plants) && item.toxic_plants.length > 0);
}

function densityToVisualIntensity(density) {
    const d = clampScore(density);

    if (d <= 0) return 0;

    if (d < 20) {
        return 0.24 + (d / 20) * 0.22;
    }

    if (d < 30) {
        return 0.46 + ((d - 20) / 10) * 0.13;
    }

    if (d < 60) {
        return 0.60 + ((d - 30) / 30) * 0.20;
    }

    if (d < 80) {
        return 0.82 + ((d - 60) / 20) * 0.12;
    }

    return 0.94 + ((d - 80) / 20) * 0.06;
}

function layerScoreToVisualIntensity(score, minValue = 0.26) {
    const s = clampScore(score);

    if (s <= 0) return 0;

    return Math.max(minValue, Math.min(1, minValue + Math.pow(s / 100, 0.65) * (1 - minValue)));
}

function heatPointCountByScore(score, type, datasetSize = lastRenderedDataSize) {
    const s = clampScore(score);
    const size = Number(datasetSize) || 0;

    /*
     * Performance note:
     * The database already stores many real heatmap points. The old version created
     * up to 37 synthetic points for every database point, which could produce more
     * than 150,000 canvas points and cause lag while zooming or changing years.
     * The counts below keep sparse years visually rich and make dense years fast.
     */
    if (size >= 3500) {
        if (type === "density") return s >= 80 ? 3 : 2;
        return s >= 70 ? 2 : 1;
    }

    if (size >= 1800) {
        if (type === "density") return s >= 80 ? 6 : (s >= 45 ? 4 : 3);
        return s >= 70 ? 4 : 2;
    }

    if (size >= 800) {
        if (type === "density") return s >= 80 ? 10 : (s >= 45 ? 7 : 4);
        return s >= 70 ? 7 : 3;
    }

    if (type === "density") {
        if (s < 10) return 4;
        if (s < 20) return 6;
        if (s < 30) return 8;
        if (s < 45) return 11;
        if (s < 60) return 14;
        if (s < 80) return 18;
        return 22;
    }

    if (s < 20) return 4;
    if (s < 35) return 6;
    if (s < 50) return 8;
    if (s < 70) return 11;
    if (s < 85) return 14;
    return 18;
}

function heatSpreadByScore(score, type) {
    const s = clampScore(score);

    if (type === "density") {
        if (s < 20) return 0.070;
        if (s < 30) return 0.095;
        if (s < 45) return 0.120;
        if (s < 60) return 0.150;
        if (s < 80) return 0.185;
        return 0.225;
    }

    if (s < 30) return 0.060;
    if (s < 50) return 0.085;
    if (s < 70) return 0.115;
    if (s < 85) return 0.145;
    return 0.175;
}

function getLayerScore(item, type) {
    if (type === "density") {
        return getDensity(item);
    }

    if (type === "rarity") {
        return isRareRegion(item) ? getRarity(item) : 0;
    }

    if (type === "toxicity") {
        return isToxicRegion(item) ? getToxicity(item) : 0;
    }

    return 0;
}

function getLayerBaseIntensity(item, type) {
    const score = getLayerScore(item, type);

    if (score <= 0) return 0;

    if (type === "density") {
        return densityToVisualIntensity(score);
    }

    if (type === "rarity") {
        return layerScoreToVisualIntensity(score, 0.35);
    }

    if (type === "toxicity") {
        return layerScoreToVisualIntensity(score, 0.38);
    }

    return 0;
}

function createHeatPointsForLayer(item, type, datasetSize = lastRenderedDataSize) {
    const lat = getLat(item);
    const lon = getLon(item);

    if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
        return [];
    }

    const score = getLayerScore(item, type);
    const baseIntensity = getLayerBaseIntensity(item, type);

    if (baseIntensity <= 0 || score <= 0) {
        return [];
    }

    const count = heatPointCountByScore(score, type, datasetSize);
    const spread = heatSpreadByScore(score, type);
    const points = [];

    for (let i = 0; i < count && i < HEAT_OFFSETS.length; i++) {
        const offset = HEAT_OFFSETS[i];

        let shiftLat = offset[0] * spread;
        let shiftLon = offset[1] * spread;

        if (type === "rarity") {
            shiftLat += 0.018;
            shiftLon -= 0.018;
        }

        if (type === "toxicity") {
            shiftLat -= 0.018;
            shiftLon += 0.018;
        }

        const pointLat = lat + shiftLat;
        const pointLon = lon + shiftLon;
        const pointIntensity = Math.max(0.12, Math.min(1, baseIntensity * offset[2]));

        points.push([pointLat, pointLon, pointIntensity]);
    }

    return points;
}

function heatRadiusForZoom(type) {
    const zoom = map.getZoom();

    let base;

    if (zoom <= 5) base = 62;
    else if (zoom <= 6) base = 58;
    else if (zoom <= 7) base = 52;
    else if (zoom <= 8) base = 45;
    else if (zoom <= 10) base = 38;
    else base = 30;

    if (type === "rarity") return Math.max(24, base - 6);
    if (type === "toxicity") return Math.max(24, base - 4);

    return base;
}

function heatBlurForZoom(type) {
    const zoom = map.getZoom();

    let base;

    if (zoom <= 5) base = 48;
    else if (zoom <= 6) base = 44;
    else if (zoom <= 7) base = 38;
    else if (zoom <= 8) base = 34;
    else if (zoom <= 10) base = 28;
    else base = 22;

    if (type === "rarity") return Math.max(18, base - 5);
    if (type === "toxicity") return Math.max(18, base - 4);

    return base;
}

function removeHeatLayers() {
    Object.keys(heatLayers).forEach(key => {
        if (heatLayers[key]) {
            map.removeLayer(heatLayers[key]);
            heatLayers[key] = null;
        }
    });

    lastHeatBreakdown = {
        density: 0,
        rarity: 0,
        toxicity: 0,
        total: 0
    };
}

function buildPopupHtml(item) {
    const ndviText = item.ndvi_mean !== null && item.ndvi_mean !== undefined
        ? Number(item.ndvi_mean).toFixed(3)
        : "غير متاح";

    const density = getDensity(item);
    const rarity = getRarity(item);
    const toxicity = getToxicity(item);

    return `
        <b>${escapeHtml(getRegionName(item))}</b><br>
        📅 السنة: <b>${escapeHtml(getItemYear(item))}</b><br>
        🌱 NDVI: <b>${escapeHtml(ndviText)}</b><br>
        🌿 مستوى الكثافة: <b>${escapeHtml(densityLevelText(density))}</b><br>
        🌿 الكثافة النباتية: <b>${density.toFixed(1)}</b><br>
        🔵 الندرة: <b>${rarity.toFixed(1)}%</b><br>
        ☣️ السمية: <b>${toxicity.toFixed(1)}</b><br>
        🦠 الأوبئة: ${safeList(item.diseases)}
    `;
}

function showAnalysis(item) {
    const densityValue = getDensity(item);
    const rarityValue = getRarity(item);
    const toxicityValue = getToxicity(item);
    const spreadValue = Math.min(100, readNumber(item, ["avg_spread", "disease_spread", "spread"], 0));

    const risk = Math.min(
        100,
        Math.round(((100 - densityValue) * 0.40) + (toxicityValue * 0.30) + (rarityValue * 0.15) + (spreadValue * 0.15))
    );

    const riskColor = risk < 30 ? "#2e7d32" : risk < 60 ? "#f9a825" : "#d32f2f";

    analysisBox.innerHTML = `
        <div class="analysis-header">تحليل المنطقة</div>
        <div class="region-name">${escapeHtml(getRegionName(item))}</div>
        <div class="analysis-grid">
            <div class="analysis-card">
                <b>الكثافة</b>
                <div class="analysis-value">${densityValue.toFixed(1)}</div>
                <div class="progress"><div class="progress-fill" style="width:${densityValue}%;background:#22c55e"></div></div>
            </div>

            <div class="analysis-card">
                <b>الندرة</b>
                <div class="analysis-value">${rarityValue.toFixed(1)}</div>
                <div class="progress"><div class="progress-fill" style="width:${rarityValue}%;background:#2563eb"></div></div>
            </div>

            <div class="analysis-card">
                <b>السمية</b>
                <div class="analysis-value">${toxicityValue.toFixed(1)}</div>
                <div class="progress"><div class="progress-fill" style="width:${toxicityValue}%;background:#dc2626"></div></div>
            </div>

            <div class="analysis-card">
                <b>انتشار الأوبئة</b>
                <div class="analysis-value">${spreadValue.toFixed(1)}</div>
                <div class="progress"><div class="progress-fill" style="width:${spreadValue}%;background:#8d99ae"></div></div>
            </div>
        </div>

        <div class="risk-box">⚠️ مستوى الخطورة: <b style="color:${riskColor}">${risk}%</b></div>
    `;
}

function openRegion(item, latlng = null) {
    if (!item) return;

    activeRegionName = getRegionName(item);
    activePopupLatLng = latlng || [getLat(item), getLon(item)];

    showAnalysis(item);

    L.popup()
        .setLatLng(activePopupLatLng)
        .setContent(buildPopupHtml(item))
        .openOn(map);
}

function updateActiveRegionAnalysis() {
    if (!activeRegionName || !currentMapData.length) return;

    const key = normalizeRegionName(activeRegionName);
    const item = currentMapData.find(row => normalizeRegionName(getRegionName(row)) === key);

    if (item) {
        showAnalysis(item);

        if (activePopupLatLng && map._popup) {
            map._popup
                .setLatLng(activePopupLatLng)
                .setContent(buildPopupHtml(item));
        }
    }
}

function getFilteredData() {
    let filtered = [...currentMapData];

    if (selectedRegion !== "") {
        filtered = filtered.filter(item => getRegionName(item) === selectedRegion);
    }

    if (selectedDensity !== "") {
        filtered = filtered.filter(item => {
            const d = getDensity(item);

            if (selectedDensity === "low") return d < 30;
            if (selectedDensity === "medium") return d >= 30 && d < 60;
            if (selectedDensity === "high") return d >= 60 && d < 80;
            if (selectedDensity === "very_high") return d >= 80;

            return true;
        });
    }

    if (selectedMainType && selectedMainType !== "all") {
        filtered = filtered.filter(item => Array.isArray(item.disease_types) && item.disease_types.includes(selectedMainType));
    }

    if (selectedSubType) {
        filtered = filtered.filter(item => Array.isArray(item.disease_ids) && item.disease_ids.map(String).includes(String(selectedSubType)));
    }

    return filtered;
}

function updateStats(data) {
    data = Array.isArray(data) ? data : [];

    if (!yearStatsBox) return;

    const regionKeys = new Set(data.map(item => normalizeRegionName(getRegionName(item) || item.region_id)));
    const count = regionKeys.size;

    const maxDensity = data.length ? Math.max(...data.map(x => getDensity(x))) : 0;
    const avgDensity = data.length ? data.reduce((sum, item) => sum + getDensity(item), 0) / data.length : 0;
    const maxRarity = data.length ? Math.max(...data.map(x => getRarity(x))) : 0;
    const maxToxicity = data.length ? Math.max(...data.map(x => getToxicity(x))) : 0;

    const rareRegions = data.length
        ? new Set(data.filter(isRareRegion).map(item => normalizeRegionName(getRegionName(item) || item.region_id))).size
        : 0;

    const toxicRegions = data.length
        ? new Set(data.filter(isToxicRegion).map(item => normalizeRegionName(getRegionName(item) || item.region_id))).size
        : 0;
    yearStatsBox.innerHTML = `
        <div class="stats-row"><span>عدد المناطق</span><b>${count}</b></div>
        <div class="stats-row"><span>نقاط الكثافة</span><b>${lastHeatBreakdown.density}</b></div>
        <div class="stats-row"><span>نقاط الندرة</span><b>${lastHeatBreakdown.rarity}</b></div>
        <div class="stats-row"><span>نقاط السمية</span><b>${lastHeatBreakdown.toxicity}</b></div>
        <div class="stats-row"><span>إجمالي نقاط الهيت ماب</span><b>${lastHeatBreakdown.total}</b></div>
        <div class="stats-row"><span>متوسط الكثافة</span><b>${avgDensity.toFixed(1)}</b></div>
        <div class="stats-row"><span>أعلى كثافة</span><b>${maxDensity.toFixed(1)}</b></div>
        <div class="stats-row"><span>مناطق نادرة فعلية</span><b>${rareRegions}</b></div>
        <div class="stats-row"><span>مناطق سامة فعلية</span><b>${toxicRegions}</b></div>
        <div class="stats-row"><span>أعلى ندرة</span><b>${maxRarity.toFixed(1)}</b></div>
        <div class="stats-row"><span>أعلى سمية</span><b>${maxToxicity.toFixed(1)}</b></div>
    `;
}

function renderHeatMap(data) {
    removeHeatLayers();

    data = Array.isArray(data) ? data : [];
    lastRenderedDataSize = data.length;

    const densityPoints = [];
    const rarityPoints = [];
    const toxicityPoints = [];

    data.forEach(item => {
        if (!toggleDensity || toggleDensity.checked) {
            createHeatPointsForLayer(item, "density", data.length).forEach(point => densityPoints.push(point));
        }

        if (!toggleRarity || toggleRarity.checked) {
            createHeatPointsForLayer(item, "rarity", data.length).forEach(point => rarityPoints.push(point));
        }

        if (!toggleToxicity || toggleToxicity.checked) {
            createHeatPointsForLayer(item, "toxicity", data.length).forEach(point => toxicityPoints.push(point));
        }
    });

    lastHeatBreakdown = {
        density: densityPoints.length,
        rarity: rarityPoints.length,
        toxicity: toxicityPoints.length,
        total: densityPoints.length + rarityPoints.length + toxicityPoints.length
    };

    if (densityPoints.length) {
        heatLayers.density = L.heatLayer(densityPoints, {
            radius: heatRadiusForZoom("density"),
            blur: heatBlurForZoom("density"),
            maxZoom: 18,
            minOpacity: 0.22,
            max: 1.0,
            gradient: {
                0.05: "#ecfdf5",
                0.18: "#dcfce7",
                0.34: "#bbf7d0",
                0.50: "#86efac",
                0.66: "#22c55e",
                0.82: "#16a34a",
                1.00: "#052e16"
            }
        }).addTo(map);
    }

    if (rarityPoints.length) {
        heatLayers.rarity = L.heatLayer(rarityPoints, {
            radius: heatRadiusForZoom("rarity"),
            blur: heatBlurForZoom("rarity"),
            maxZoom: 18,
            minOpacity: 0.24,
            max: 1.0,
            gradient: {
                0.08: "#dbeafe",
                0.25: "#93c5fd",
                0.45: "#60a5fa",
                0.65: "#2563eb",
                0.85: "#1d4ed8",
                1.00: "#172554"
            }
        }).addTo(map);
    }

    if (toxicityPoints.length) {
        heatLayers.toxicity = L.heatLayer(toxicityPoints, {
            radius: heatRadiusForZoom("toxicity"),
            blur: heatBlurForZoom("toxicity"),
            maxZoom: 18,
            minOpacity: 0.25,
            max: 1.0,
            gradient: {
                0.08: "#fef9c3",
                0.25: "#fde047",
                0.45: "#facc15",
                0.65: "#f97316",
                0.85: "#dc2626",
                1.00: "#7f1d1d"
            }
        }).addTo(map);
    }
}

function getMarkerItems(data) {
    const grouped = new Map();

    data.forEach(item => {
        const lat = getLat(item);
        const lon = getLon(item);

        if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;

        const key = normalizeRegionName(getRegionName(item) || item.region_id || `${lat},${lon}`);

        if (!grouped.has(key)) {
            grouped.set(key, {
                item,
                latTotal: lat,
                lonTotal: lon,
                count: 1
            });
            return;
        }

        const group = grouped.get(key);
        group.latTotal += lat;
        group.lonTotal += lon;
        group.count += 1;
    });

    return [...grouped.values()].map(group => ({
        item: group.item,
        lat: group.latTotal / group.count,
        lon: group.lonTotal / group.count,
        count: group.count
    }));
}

function renderMarkers(data) {
    markerLayer.clearLayers();

    getMarkerItems(data).forEach(group => {
        const marker = L.marker([group.lat, group.lon], {
            icon: greenPinIcon,
            title: getRegionName(group.item)
        });

        marker.bindPopup(buildPopupHtml(group.item));
        marker.on("click", e => openRegion(group.item, e.latlng));
        marker.addTo(markerLayer);
    });
}

function fitMapOnce(data) {
    if (!data.length || hasLoadedOnce) return;

    const bounds = L.latLngBounds([]);
    let hasBounds = false;

    data.forEach(item => {
        const lat = getLat(item);
        const lon = getLon(item);

        if (Number.isFinite(lat) && Number.isFinite(lon)) {
            bounds.extend([lat, lon]);
            hasBounds = true;
        }
    });

    if (hasBounds && bounds.isValid()) {
        map.fitBounds(bounds, {
            padding: [55, 55],
            maxZoom: 6
        });
    }

    hasLoadedOnce = true;
}

function updateMap(data, options = {}) {
    data = Array.isArray(data) ? data : [];

    markerLayer.clearLayers();
    removeHeatLayers();

    if (!data.length) {
        lastRenderedDataSize = 0;
        updateStats([]);
        analysisBox.innerHTML = `
            <div class="analysis-header">تحليل المنطقة</div>
            <div class="analysis-empty">لا توجد بيانات مطابقة لهذه السنة أو الفلاتر.</div>
        `;
        return;
    }

    renderHeatMap(data);
    renderMarkers(data);
    updateStats(data);

    if (options.fitOnce === true) {
        fitMapOnce(data);
    }
}

function loadRegions(data) {
    const oldValue = selectedRegion;
    const regions = [...new Set(data.map(item => getRegionName(item)))].filter(Boolean).sort();

    if (!regionFilter) return;

    regionFilter.innerHTML = '<option value="">كل المناطق</option>';

    regions.forEach(region => {
        const option = document.createElement("option");
        option.value = region;
        option.textContent = region;
        regionFilter.appendChild(option);
    });

    if (oldValue && [...regionFilter.options].some(o => o.value === oldValue)) {
        regionFilter.value = oldValue;
    }
}

function updateSubDiseaseOptions(mainType) {
    if (!subDiseaseFilter) return;

    subDiseaseFilter.innerHTML = '<option value="">اختر المرض الدقيق</option>';

    if (!mainType || mainType === "all") return;

    if (diseaseGroups[mainType]) {
        diseaseGroups[mainType].forEach(disease => {
            const option = document.createElement("option");
            option.value = disease.disease_id;
            option.textContent = disease.name;
            subDiseaseFilter.appendChild(option);
        });
    }
}

function hasExternalFocusRequest() {
    /*
     * Important bug fix:
     * PHP sends missing coordinates as null. In JavaScript Number(null) is 0,
     * so the old check treated an empty focus request as coordinates (0,0).
     * That caused the first map refresh/slider move to open the nearest item
     * unexpectedly, usually Farasan, and sometimes move the view away from KSA.
     */
    const targetId = String(FOCUS_REQUEST.region_id || '').trim();
    const targetRegion = normalizeRegionName(FOCUS_REQUEST.region || '');
    const requestLat = readOptionalNumber(FOCUS_REQUEST.lat);
    const requestLon = readOptionalNumber(FOCUS_REQUEST.lon);

    return Boolean(
        (targetId && targetId !== '0') ||
        targetRegion !== '' ||
        isSaudiCoordinate(requestLat, requestLon)
    );
}

function findFocusedItem(data) {
    if (!Array.isArray(data) || !data.length) return null;

    const targetId = String(FOCUS_REQUEST.region_id || '').trim();
    const targetRegion = normalizeRegionName(FOCUS_REQUEST.region || '');

    if (targetId && targetId !== '0') {
        const byId = data.find(item => String(item.region_id || item.id || '').trim() === targetId);
        if (byId) return byId;
    }

    if (targetRegion) {
        const byName = data.find(item => normalizeRegionName(getRegionName(item)) === targetRegion);
        if (byName) return byName;
    }

    const lat = readOptionalNumber(FOCUS_REQUEST.lat);
    const lon = readOptionalNumber(FOCUS_REQUEST.lon);

    if (isSaudiCoordinate(lat, lon)) {
        let best = null;
        let bestDistance = Infinity;

        data.forEach(item => {
            const itemLat = getLat(item);
            const itemLon = getLon(item);

            if (!isSaudiCoordinate(itemLat, itemLon)) return;

            const distance = Math.abs(itemLat - lat) + Math.abs(itemLon - lon);

            if (distance < bestDistance) {
                bestDistance = distance;
                best = item;
            }
        });

        return best;
    }

    return null;
}

function focusExternalAlert(data) {
    if (hasHandledExternalFocus || !hasExternalFocusRequest()) return;

    const item = findFocusedItem(data);
    const requestLat = readOptionalNumber(FOCUS_REQUEST.lat);
    const requestLon = readOptionalNumber(FOCUS_REQUEST.lon);
    const hasRequestCoords = isSaudiCoordinate(requestLat, requestLon);

    if (!item && !hasRequestCoords) return;

    const lat = hasRequestCoords ? requestLat : getLat(item);
    const lon = hasRequestCoords ? requestLon : getLon(item);

    if (!isSaudiCoordinate(lat, lon)) return;

    hasHandledExternalFocus = true;
    map.setView([lat, lon], Math.max(map.getZoom(), 8), { animate: true });

    if (item) {
        openRegion(item, [lat, lon]);
        return;
    }

    L.popup()
        .setLatLng([lat, lon])
        .setContent(`<b>${escapeHtml(FOCUS_REQUEST.title || 'تنبيه الغطاء النباتي')}</b><br>${escapeHtml(FOCUS_REQUEST.region || 'المنطقة المحددة')}`)
        .openOn(map);
}

async function fetchYearData(year, forceRefresh = false) {
    const requestId = ++latestRequestId;

    if (loadingNote) loadingNote.classList.add("active");

    if (refreshMapBtn) {
        refreshMapBtn.disabled = true;
    }

    try {
        const cacheBust = forceRefresh ? `&refresh=${Date.now()}` : `&t=${Date.now()}`;
        const response = await fetch(`fetch_by_year.php?year=${encodeURIComponent(year)}&mode=map${cacheBust}`, {
            headers: {
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            cache: "no-store"
        });

        const text = await response.text();
        let payload;

        try {
            payload = JSON.parse(text);
        } catch (error) {
            console.error("Invalid JSON from fetch_by_year.php:", text);
            throw new Error("ملف fetch_by_year.php لم يرجع JSON صحيح. تأكد أنه لا يطبع HTML أو Warning قبل JSON.");
        }

        if (requestId !== latestRequestId) {
            return;
        }

        if (!payload.success) {
            throw new Error(payload.message || "تعذر تحميل بيانات السنة");
        }

        if (!Array.isArray(payload.data)) {
            throw new Error("صيغة البيانات غير صحيحة");
        }

        currentMapData = payload.data;

        if (selectedRegion && !currentMapData.some(item => getRegionName(item) === selectedRegion)) {
            selectedRegion = "";
            if (regionFilter) regionFilter.value = "";
        }

        loadRegions(currentMapData);

        updateMap(getFilteredData(), {
            fitOnce: !hasLoadedOnce
        });

        updateActiveRegionAnalysis();
        focusExternalAlert(currentMapData);

        if (currentMapData.length === 0) {
            analysisBox.innerHTML = `
                <div class="analysis-header">تحليل المنطقة</div>
                <div class="analysis-empty">
                    لا توجد بيانات لهذه السنة في قاعدة البيانات.
                    تأكد من وجود بيانات داخل جدول region_year_metrics لهذه السنة.
                </div>
            `;
        }
    } catch (error) {
        if (requestId !== latestRequestId) return;

        console.error(error);

        currentMapData = [];
        updateMap([]);

        analysisBox.innerHTML = `
            <div class="analysis-header">تحليل المنطقة</div>
            <div class="analysis-empty">تعذر تحميل بيانات السنة ${escapeHtml(year)}.<br>السبب: ${escapeHtml(error.message)}</div>
        `;
    } finally {
        if (requestId === latestRequestId) {
            if (loadingNote) loadingNote.classList.remove("active");

            if (refreshMapBtn) {
                refreshMapBtn.disabled = false;
            }
        }
    }
}

function refreshMapData() {
    fetchYearData(selectedYear, true);
}

function applyFilters() {
    updateMap(getFilteredData());
    updateActiveRegionAnalysis();
}

function resetFilters() {
    selectedRegion = "";
    selectedDensity = "";
    selectedMainType = "all";
    selectedSubType = "";
    activeRegionName = "";
    activePopupLatLng = null;

    if (regionFilter) {
        regionFilter.value = "";
        [...regionFilter.options].forEach(option => { option.style.display = ""; });
    }
    if (regionSearch) regionSearch.value = "";
    if (densityFilter) densityFilter.value = "";

    if (subDiseaseFilter) {
        subDiseaseFilter.innerHTML = '<option value="">اختر المرض الدقيق</option>';
        subDiseaseFilter.value = "";
    }

    if (toggleDensity) toggleDensity.checked = true;
    if (toggleRarity) toggleRarity.checked = true;
    if (toggleToxicity) toggleToxicity.checked = true;

    document.querySelectorAll(".icon-filter").forEach(btn => btn.classList.remove("active"));

    const allButton = document.querySelector('[data-type="all"]');

    if (allButton) {
        allButton.classList.add("active");
    }

    analysisBox.innerHTML = `
        <div class="analysis-header">تحليل المنطقة</div>
        <div class="analysis-empty">اضغط على العلامات الخضراء في الخريطة لعرض بياناتها وتحليلها.</div>
    `;

    map.closePopup();

    const defaultYear = Number(CONFIG.defaultYear || YEAR_MAX);
    const shouldReloadDefaultYear = selectedYear !== defaultYear;

    setSelectedYear(defaultYear);

    if (shouldReloadDefaultYear) {
        fetchYearData(defaultYear);
    } else {
        updateMap(currentMapData);
    }

    map.setView(INITIAL_MAP_VIEW.center, INITIAL_MAP_VIEW.zoom, { animate: true });
    setTimeout(() => map.invalidateSize(), 80);
}

function filterRegionList() {
    const value = normalizeRegionName(regionSearch.value);

    if (!regionFilter) return;

    [...regionFilter.options].forEach(option => {
        option.style.display = normalizeRegionName(option.text).includes(value) ? "" : "none";
    });
}

function toggleYearAnimation() {
    if (isPlaying) {
        clearInterval(playTimer);
        playTimer = null;
        isPlaying = false;
        playBtn.innerText = "▶ تشغيل السنوات";
        setMapNote(`الشريط الزمني يبدأ بالأبيض عند ${YEAR_MIN} ويتدرج إلى الأخضر عند ${YEAR_MAX}.`);
        return;
    }

    isPlaying = true;
    playBtn.innerText = "⏸ إيقاف التشغيل";
    setMapNote(`تشغيل السنوات: الأبيض عند ${YEAR_MIN} والتدرج الأخضر عند ${YEAR_MAX}.`);

    playTimer = setInterval(() => {
        let next = Number(yearSlider.value) + 1;

        if (next > YEAR_MAX) {
            next = YEAR_MIN;
        }

        setSelectedYear(next);
        fetchYearData(next);
    }, 1100);
}

document.querySelectorAll("#mainDiseaseFilter .icon-filter").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll("#mainDiseaseFilter .icon-filter").forEach(b => b.classList.remove("active"));

        btn.classList.add("active");

        selectedMainType = btn.dataset.type;
        selectedSubType = "";

        updateSubDiseaseOptions(selectedMainType);
        applyFilters();
    });
});

if (subDiseaseFilter) subDiseaseFilter.addEventListener("change", function () {
    selectedSubType = this.value;
    applyFilters();
});

if (densityFilter) densityFilter.addEventListener("change", function () {
    selectedDensity = this.value;
    applyFilters();
});


if (regionFilter) regionFilter.addEventListener("change", function () {
    selectedRegion = this.value;
    activeRegionName = "";
    activePopupLatLng = null;
    map.closePopup();
    applyFilters();
});

if (regionSearch) regionSearch.addEventListener("keyup", filterRegionList);

if (toggleDensity) toggleDensity.addEventListener("change", applyFilters);
if (toggleRarity) toggleRarity.addEventListener("change", applyFilters);
if (toggleToxicity) toggleToxicity.addEventListener("change", applyFilters);

if (yearSlider) yearSlider.addEventListener("input", function () {
    setSelectedYear(Number(this.value));

    clearTimeout(sliderTimer);

    sliderTimer = setTimeout(() => {
        fetchYearData(selectedYear);
    }, 220);
});

map.on("zoomend", function () {
    if (!currentMapData.length) return;

    clearTimeout(zoomRenderTimer);
    zoomRenderTimer = setTimeout(() => {
        const filtered = getFilteredData();
        renderHeatMap(filtered);
        updateStats(filtered);
    }, 160);
});

window.addEventListener("load", function () {
    setSelectedYear(selectedYear);

    setTimeout(() => {
        map.invalidateSize();
        fetchYearData(selectedYear, true);
    }, 250);
});

window.addEventListener("resize", function () {
    map.invalidateSize();
});