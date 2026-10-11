(function () {
  'use strict';

  const cfg = window.VEGETATION_CONFIG || {};
  const YEAR_MIN = Number(cfg.minYear || 1990);
  const YEAR_MAX = Number(cfg.maxYear || new Date().getFullYear());
  let selectedYear = Number(cfg.defaultYear || YEAR_MAX);
  const BOUNDARIES_URL = cfg.boundariesUrl || 'assets/data/saudi_governorates.geojson';
  const DEFAULT_HERO_IMAGE = cfg.heroImage || 'assets/img/about/about-5.webp';

  if (typeof L === 'undefined') {
    console.error('Leaflet is required');
    return;
  }

  const el = {
    map: document.getElementById('map'),
    playBtn: document.getElementById('playTimelineBtn'),
    refreshBtn: document.getElementById('refreshMapBtn'),
    switchBaseBtn: document.getElementById('switchBaseBtn'),
    yearSlider: document.getElementById('yearSlider'),
    yearTicks: document.getElementById('timelineTicks'),
    toast: document.getElementById('mapToast'),
    toastTitle: document.getElementById('toastTitle'),
    toastSub: document.getElementById('toastSub'),
    regionSearch: document.getElementById('regionSearch'),
    regionFilter: document.getElementById('regionFilter'),
    densityFilter: document.getElementById('densityFilter'),
    rarityFilter: document.getElementById('rarityFilter'),
    toxicityFilter: document.getElementById('toxicityFilter'),
    diseaseTypeFilter: document.getElementById('diseaseTypeFilter'),
    diseaseFilter: document.getElementById('diseaseFilter'),
    resetFiltersBtn: document.getElementById('resetFiltersBtn'),
    regionTitle: document.getElementById('regionTitle'),
    regionSub: document.getElementById('regionSub'),
    heroImg: document.getElementById('regionHeroImg'),
    m1Title: document.getElementById('metric1Title'),
    m1Value: document.getElementById('metric1Value'),
    m1Sub: document.getElementById('metric1Sub'),
    m2Title: document.getElementById('metric2Title'),
    m2Value: document.getElementById('metric2Value'),
    m2Sub: document.getElementById('metric2Sub'),
    m3Title: document.getElementById('metric3Title'),
    m3Value: document.getElementById('metric3Value'),
    m3Sub: document.getElementById('metric3Sub'),
    m4Title: document.getElementById('metric4Title'),
    m4Value: document.getElementById('metric4Value'),
    m4Sub: document.getElementById('metric4Sub'),
    ndviCurrent: document.getElementById('ndviCurrentValue'),
    ndviChange: document.getElementById('ndviChangeValue'),
    ringDensity: document.getElementById('ringDensity'),
    ringRarity: document.getElementById('ringRarity'),
    ringToxicity: document.getElementById('ringToxicity'),
    ringDensityValue: document.getElementById('ringDensityValue'),
    ringRarityValue: document.getElementById('ringRarityValue'),
    ringToxicityValue: document.getElementById('ringToxicityValue'),
    ringDensityState: document.getElementById('ringDensityState'),
    ringRarityState: document.getElementById('ringRarityState'),
    ringToxicityState: document.getElementById('ringToxicityState'),
    distDensity: document.getElementById('distDensity'),
    distRarity: document.getElementById('distRarity'),
    distToxicity: document.getElementById('distToxicity'),
    totalPlants: document.getElementById('totalPlantsValue'),
    rarePlantsCount: document.getElementById('rarePlantsCountValue'),
    topSpecies: document.getElementById('topSpeciesList'),
    showAllSpeciesBtn: document.getElementById('showAllSpeciesBtn'),
    changeDensity: document.getElementById('changeDensity'),
    changeRarity: document.getElementById('changeRarity'),
    changeToxicity: document.getElementById('changeToxicity'),
    bottomThumbs: document.getElementById('yearThumbList'),
    reportBtn: document.getElementById('downloadReportBtn'),
    shareBtn: document.getElementById('shareBtn'),
    compareBtn: document.getElementById('compareBtn'),
    printBtn: document.getElementById('printBtn')
  };

  const map = L.map('map', { zoomControl: true, attributionControl: true }).setView([23.8859, 45.0792], 6);
  L.svg().addTo(map);

  const baseLayers = [
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
      attribution: 'Tiles &copy; Esri'
    }),
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    })
  ];
  let activeBaseIndex = 0;
  baseLayers[activeBaseIndex].addTo(map);

  const featureNameKeys = [
    'region_name_ar', 'name_ar', 'shapeNameAr', 'ar_name', 'NAME_2', 'NAME_AR',
    'name', 'shapeName', 'region_name', 'RegionName', 'Governorate', 'gov_name', 'admin2Name', 'Admin2Name'
  ];

  const aliasMap = {
    'جده': 'جدة',
    'راس تنوره': 'رأس تنورة',
    'الاحساء': 'الأحساء',
    'منطقه عسير': 'عسير',
    'منطقة عسير': 'عسير',
    'عسير منطقه': 'عسير'
  };

  let currentMapData = [];
  let metricsByName = new Map();
  let boundaryGeoJson = null;
  let boundaryLayer = null;
  let selectedRegionName = '';
  let selectedItem = null;
  let selectedSeries = null;
  let yearChart = null;
  let selectedMarker = null;
  let svgDefs = null;
  let playbackTimer = null;
  let playing = false;

  function showToast(title, sub) {
    if (!el.toast || !el.toastTitle || !el.toastSub) return;
    el.toastTitle.textContent = title || '';
    el.toastSub.textContent = sub || '';
    el.toast.classList.add('show');
    clearTimeout(showToast._timer);
    showToast._timer = setTimeout(() => el.toast.classList.remove('show'), 2300);
  }

  function normalizeName(value) {
    let text = String(value || '')
      .replace(/[\n\r\t]+/g, ' ')
      .replace(/ـ/g, '')
      .replace(/[أإآ]/g, 'ا')
      .replace(/ة/g, 'ه')
      .replace(/ى/g, 'ي')
      .replace(/\s+/g, ' ')
      .trim();

    text = text.replace(/\b(محافظه|محافظة|منطقه|منطقة|اماره|إمارة)\b/g, '').replace(/\s+/g, ' ').trim();
    return text;
  }

  function cleanRegionName(value) {
    const text = String(value || '').replace(/[\n\r\t]+/g, ' ').replace(/\s+/g, ' ').trim();
    return aliasMap[text] || text;
  }

  function clamp(num, min = 0, max = 100) {
    return Math.max(min, Math.min(max, Number(num) || 0));
  }

  function stateLabel(value, type) {
    const v = clamp(value);
    if (type === 'density') {
      if (v >= 70) return 'عالية';
      if (v >= 35) return 'متوسطة';
      return 'منخفضة';
    }
    if (v >= 70) return 'عالية';
    if (v >= 35) return 'متوسطة';
    return 'منخفضة';
  }

  function getFeatureName(feature) {
    const props = feature?.properties || {};
    for (const key of featureNameKeys) {
      if (props[key] !== undefined && props[key] !== null && String(props[key]).trim() !== '') {
        return cleanRegionName(props[key]);
      }
    }
    return '';
  }

  function ensureDefs() {
    const svgRoot = map.getPanes().overlayPane.querySelector('svg');
    if (!svgRoot) return null;
    if (!svgDefs) {
      svgDefs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
      svgRoot.insertBefore(svgDefs, svgRoot.firstChild);
    }
    return svgDefs;
  }

  function isRare(item) {
    return Number(item?.rare_count || 0) > 0 || clamp(item?.rarity) > 0;
  }

  function isToxic(item) {
    return Number(item?.toxic_count || 0) > 0 || clamp(item?.toxicity) > 0;
  }

  function densityBucket(item) {
    const v = clamp(item?.density);
    if (v >= 70) return 'high';
    if (v >= 35) return 'medium';
    return 'low';
  }

  function buildMetricsIndex(items) {
    metricsByName = new Map();
    items.forEach(item => {
      metricsByName.set(normalizeName(cleanRegionName(item.region)), item);
    });
  }

  function patternWidths(item) {
    const density = clamp(item?.density) * 1.2;
    const rarity = isRare(item) ? Math.max(12, clamp(item?.rarity)) : 0;
    const toxicity = isToxic(item) ? Math.max(12, clamp(item?.toxicity)) : 0;
    const total = density + rarity + toxicity || 1;
    const width = 64;
    let green = Math.round((density / total) * width);
    let blue = Math.round((rarity / total) * width);
    let yellow = width - green - blue;
    if (density > 0 && green < 10) green = 10;
    if (rarity > 0 && blue < 10) blue = 10;
    if (toxicity > 0 && yellow < 10) yellow = 10;
    const sum = green + blue + yellow;
    if (sum !== width) yellow += width - sum;
    return { green, blue, yellow };
  }

  function createPattern(regionId, item, selected) {
    const defs = ensureDefs();
    if (!defs) return null;
    const id = `region-pattern-${regionId}`;
    const old = document.getElementById(id);
    if (old) old.remove();

    const p = document.createElementNS('http://www.w3.org/2000/svg', 'pattern');
    p.setAttribute('id', id);
    p.setAttribute('patternUnits', 'userSpaceOnUse');
    p.setAttribute('width', '64');
    p.setAttribute('height', '64');
    p.setAttribute('patternTransform', 'rotate(33)');

    const bg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
    bg.setAttribute('width', '64');
    bg.setAttribute('height', '64');
    bg.setAttribute('fill', selected ? '#eef4e9' : '#ecf0e3');
    p.appendChild(bg);

    const bands = patternWidths(item);
    const colors = [];
    if (bands.green > 0) colors.push({ width: bands.green, fill: '#1e7e24' });
    if (bands.blue > 0) colors.push({ width: bands.blue, fill: '#1f94e8' });
    if (bands.yellow > 0) colors.push({ width: bands.yellow, fill: '#f2b705' });

    let x = 0;
    colors.forEach((band, index) => {
      const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
      rect.setAttribute('x', String(x));
      rect.setAttribute('y', '0');
      rect.setAttribute('width', String(Math.max(0, band.width - (index < colors.length - 1 ? 2 : 0))));
      rect.setAttribute('height', '64');
      rect.setAttribute('fill', band.fill);
      rect.setAttribute('fill-opacity', selected ? '0.96' : '0.82');
      p.appendChild(rect);
      x += band.width;
      if (index < colors.length - 1) {
        const sep = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        sep.setAttribute('x', String(x - 2));
        sep.setAttribute('y', '0');
        sep.setAttribute('width', '2');
        sep.setAttribute('height', '64');
        sep.setAttribute('fill', '#fafcf5');
        sep.setAttribute('fill-opacity', '0.88');
        p.appendChild(sep);
      }
    });

    const glaze = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    glaze.setAttribute('d', 'M0,14 L64,14 M0,46 L64,46');
    glaze.setAttribute('stroke', '#ffffff');
    glaze.setAttribute('stroke-opacity', selected ? '0.28' : '0.15');
    glaze.setAttribute('stroke-width', '1.2');
    p.appendChild(glaze);

    defs.appendChild(p);
    return id;
  }

  function popupHtml(item) {
    return `
      <div class="popup-title">${item.region}</div>
      <div class="popup-line">الكثافة: ${clamp(item.density).toFixed(1)}</div>
      <div class="popup-line">الندرة: ${clamp(item.rarity).toFixed(1)}</div>
      <div class="popup-line">السمية: ${clamp(item.toxicity).toFixed(1)}</div>
      <div class="popup-line">النباتات النادرة: ${Number(item.rare_count || 0)}</div>
      <div class="popup-line">النباتات السامة: ${Number(item.toxic_count || 0)}</div>
    `;
  }

  function getOutlineColor(item) {
    const density = clamp(item?.density);
    const rarity = clamp(item?.rarity);
    const toxicity = clamp(item?.toxicity);
    if (toxicity >= rarity && toxicity >= density) return '#d69f0b';
    if (rarity >= toxicity && rarity >= density) return '#2f9ef8';
    return '#2d8c2b';
  }

  function updateYearTicks() {
    if (!el.yearTicks) return;
    const years = [];
    for (let y = YEAR_MIN; y <= YEAR_MAX; y += 5) years.push(y);
    if (!years.includes(YEAR_MAX)) years.push(YEAR_MAX);
    el.yearTicks.innerHTML = years.map(y => `<span class="${y === selectedYear ? 'active-year' : ''}">${y}</span>`).join('');
  }

  function populateRegionFilter(items) {
    if (!el.regionFilter) return;
    const current = el.regionFilter.value;
    const names = [...new Set(items.map(i => cleanRegionName(i.region)).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'ar'));
    el.regionFilter.innerHTML = '<option value="">كل المناطق / المحافظات</option>';
    names.forEach(name => {
      const option = document.createElement('option');
      option.value = name;
      option.textContent = name;
      el.regionFilter.appendChild(option);
    });
    if (names.includes(current)) el.regionFilter.value = current;
  }

  function syncDiseaseOptions() {
    const selectedType = el.diseaseTypeFilter?.value || '';
    if (!el.diseaseFilter) return;
    Array.from(el.diseaseFilter.options).forEach(option => {
      if (!option.value) return;
      option.hidden = !!selectedType && option.dataset.type !== selectedType;
    });
    const selected = el.diseaseFilter.selectedOptions[0];
    if (selected && selected.hidden) el.diseaseFilter.value = '';
  }

  function matchesFilters(item) {
    const searchNorm = normalizeName(el.regionSearch?.value || '');
    if (selectedRegionName && item.region !== selectedRegionName) return false;
    if (searchNorm && !normalizeName(item.region).includes(searchNorm)) return false;
    if (el.densityFilter?.value && densityBucket(item) !== el.densityFilter.value) return false;
    if ((el.rarityFilter?.value || '') !== '') {
      const wantRare = el.rarityFilter.value === '1';
      if (isRare(item) !== wantRare) return false;
    }
    if ((el.toxicityFilter?.value || '') !== '') {
      const wantToxic = el.toxicityFilter.value === '1';
      if (isToxic(item) !== wantToxic) return false;
    }
    if (el.diseaseTypeFilter?.value && !(item.disease_types || []).includes(el.diseaseTypeFilter.value)) return false;
    if (el.diseaseFilter?.value && !(item.disease_ids || []).includes(String(el.diseaseFilter.value))) return false;
    return true;
  }

  function getFilteredData() {
    return currentMapData.filter(matchesFilters);
  }

  function selectRegion(item, fromMap = false) {
    if (!item) return;
    selectedItem = item;
    selectedRegionName = item.region;
    if (el.regionFilter) el.regionFilter.value = item.region;
    updateMapStyles();
    renderRegionPanel(item);
    fetchRegionSeries(item.region_id);
    if (fromMap) showToast(item.region, 'تم تحديد المنطقة وعرض تفاصيلها');
  }

  function attachMarker(item) {
    if (selectedMarker) {
      map.removeLayer(selectedMarker);
      selectedMarker = null;
    }
    if (!item || item.lat == null || item.lon == null) return;
    const icon = L.divIcon({ className: '', html: '<div class="map-pin"></div>', iconSize: [26, 26], iconAnchor: [13, 26] });
    selectedMarker = L.marker([Number(item.lat), Number(item.lon)], { icon }).addTo(map);
  }

  function getFeatureMetric(feature) {
    const name = getFeatureName(feature);
    return metricsByName.get(normalizeName(name)) || null;
  }

  function renderBoundaries() {
    if (!boundaryGeoJson) return;

    if (!boundaryLayer) {
      boundaryLayer = L.geoJSON(boundaryGeoJson, {
        style: () => ({ color: '#57767d', weight: 1.2, fillColor: '#a7b3b8', fillOpacity: 0.18, opacity: 0.45 }),
        onEachFeature: (feature, layer) => {
          layer.on('click', () => {
            const item = getFeatureMetric(feature);
            if (!item) return;
            selectRegion(item, true);
            attachMarker(item);
            layer.openPopup();
            try { map.fitBounds(layer.getBounds(), { padding: [25, 25] }); } catch (_) {}
          });
        }
      }).addTo(map);
    }

    boundaryLayer.eachLayer(layer => {
      const item = getFeatureMetric(layer.feature);
      if (item) layer.bindPopup(popupHtml(item));
    });

    updateMapStyles();
  }

  function updateMapStyles() {
    if (!boundaryLayer) return;
    const visibleNames = new Set(getFilteredData().map(item => item.region));

    boundaryLayer.eachLayer(layer => {
      const item = getFeatureMetric(layer.feature);
      const visible = item && visibleNames.has(item.region);
      const selected = item && selectedItem && item.region === selectedItem.region;

      if (!visible || !item) {
        layer.setStyle({ color: '#6d8083', weight: 1, fillColor: '#34484b', fillOpacity: 0.12, opacity: 0.25 });
        if (layer._path) {
          layer._path.setAttribute('fill', '#34484b');
          layer._path.setAttribute('fill-opacity', '0.12');
        }
        return;
      }

      const patternId = createPattern(item.region_id, item, selected);
      layer.setStyle({
        color: selected ? '#f4fff0' : getOutlineColor(item),
        weight: selected ? 3.1 : 2,
        opacity: 0.96,
        fillOpacity: 1
      });
      if (layer._path && patternId) {
        layer._path.setAttribute('fill', `url(#${patternId})`);
        layer._path.setAttribute('fill-opacity', '1');
      }
    });
  }

  function defaultRegionPick() {
    const عسير = currentMapData.find(item => item.region === 'عسير');
    return عسير || getFilteredData()[0] || currentMapData[0] || null;
  }

  async function loadBoundaryGeoJson() {
    const response = await fetch(BOUNDARIES_URL, { cache: 'no-store' });
    if (!response.ok) throw new Error('تعذر تحميل ملف حدود المناطق: ' + BOUNDARIES_URL);
    boundaryGeoJson = await response.json();
  }

  async function fetchYearData(forceRefresh = false) {
    if (el.refreshBtn) el.refreshBtn.disabled = true;
    updateYearTicks();
    const cacheBust = forceRefresh ? `&refresh=${Date.now()}` : '';
    const response = await fetch(`fetch_by_year.php?year=${encodeURIComponent(selectedYear)}&mode=map${cacheBust}`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      cache: 'no-store'
    });
    const payload = await response.json();
    if (!payload.success) throw new Error(payload.message || 'تعذر تحميل البيانات');

    currentMapData = Array.isArray(payload.data) ? payload.data.map(item => ({ ...item, region: cleanRegionName(item.region) })) : [];
    buildMetricsIndex(currentMapData);
    populateRegionFilter(currentMapData);
    syncDiseaseOptions();
    renderBoundaries();

    const filtered = getFilteredData();
    let nextItem = null;
    if (selectedItem) {
      nextItem = filtered.find(i => i.region === selectedItem.region) || currentMapData.find(i => i.region === selectedItem.region) || null;
    }
    if (!nextItem) nextItem = defaultRegionPick();
    if (nextItem) {
      selectRegion(nextItem, false);
      attachMarker(nextItem);
    } else {
      renderRegionPanel(null);
    }

    if (el.refreshBtn) el.refreshBtn.disabled = false;
  }

  async function fetchRegionSeries(regionId) {
    if (!regionId) return;
    try {
      const response = await fetch(`fetch_by_year.php?mode=series&year=${encodeURIComponent(selectedYear)}&region_id=${encodeURIComponent(regionId)}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        cache: 'no-store'
      });
      const payload = await response.json();
      if (!payload.success) throw new Error(payload.message || 'تعذر تحميل السلسلة الزمنية');
      selectedSeries = payload;
      updateSeriesUI(payload);
    } catch (error) {
      console.error(error);
      showToast('تنبيه', error.message || 'تعذر تحميل السلسلة الزمنية');
    }
  }

  function ringColor(type) {
    if (type === 'density') return '#77d441';
    if (type === 'rarity') return '#269cf4';
    return '#f2b705';
  }

  function renderRing(element, value, valueEl, stateEl, type) {
    if (!element || !valueEl || !stateEl) return;
    const v = clamp(value);
    element.style.setProperty('--percent', String(v));
    element.style.setProperty('--ring-color', ringColor(type));
    valueEl.textContent = v.toFixed(2).replace(/\.00$/, '');
    stateEl.textContent = stateLabel(v, type);
  }

  function formatSigned(value, digits = 2) {
    const n = Number(value || 0);
    return `${n >= 0 ? '+' : ''}${n.toFixed(digits)}`;
  }

  function setChangeValue(node, value) {
    if (!node) return;
    const positive = Number(value || 0) >= 0;
    node.textContent = formatSigned(value, Math.abs(value) < 1 ? 2 : 2);
    node.className = `change-value ${positive ? 'up' : 'down'}`;
  }

  function renderRegionPanel(item) {
    if (!item) {
      if (el.regionTitle) el.regionTitle.textContent = 'لا توجد منطقة مطابقة';
      if (el.regionSub) el.regionSub.textContent = 'جرّبي تعديل الفلاتر أو اختيار سنة مختلفة.';
      return;
    }

    if (el.regionTitle) el.regionTitle.textContent = item.region;
    if (el.regionSub) {
      const subtitle = item.region_type ? `${item.region_type} في المملكة العربية السعودية` : 'تفاصيل المنطقة المحددة في المملكة العربية السعودية';
      el.regionSub.textContent = subtitle;
    }
    if (el.heroImg) el.heroImg.src = DEFAULT_HERO_IMAGE;

    const plantCount = Array.isArray(item.plants) ? item.plants.length : 0;
    const diseaseCount = Array.isArray(item.diseases) ? item.diseases.length : 0;

    if (el.m1Title) el.m1Title.textContent = 'النباتات';
    if (el.m1Value) el.m1Value.textContent = plantCount.toLocaleString('ar');
    if (el.m1Sub) el.m1Sub.textContent = 'نوع نباتي مسجل';

    if (el.m2Title) el.m2Title.textContent = 'الأمراض';
    if (el.m2Value) el.m2Value.textContent = diseaseCount.toLocaleString('ar');
    if (el.m2Sub) el.m2Sub.textContent = 'حالة مرضية مرتبطة';

    if (el.m3Title) el.m3Title.textContent = 'NDVI';
    if (el.m3Value) el.m3Value.textContent = item.ndvi_mean != null ? Number(item.ndvi_mean).toFixed(2) : '--';
    if (el.m3Sub) el.m3Sub.textContent = 'المؤشر الحالي';

    if (el.m4Title) el.m4Title.textContent = 'الانتشار';
    if (el.m4Value) el.m4Value.textContent = Number(item.avg_spread || 0).toFixed(1);
    if (el.m4Sub) el.m4Sub.textContent = 'متوسط انتشار الأمراض %';

    renderRing(el.ringDensity, item.density, el.ringDensityValue, el.ringDensityState, 'density');
    renderRing(el.ringRarity, item.rarity, el.ringRarityValue, el.ringRarityState, 'rarity');
    renderRing(el.ringToxicity, item.toxicity, el.ringToxicityValue, el.ringToxicityState, 'toxicity');

    if (el.distDensity) el.distDensity.textContent = `${clamp(item.density).toFixed(0)}%`;
    if (el.distRarity) el.distRarity.textContent = `${clamp(item.rarity).toFixed(0)}%`;
    if (el.distToxicity) el.distToxicity.textContent = `${clamp(item.toxicity).toFixed(0)}%`;
    if (el.totalPlants) el.totalPlants.textContent = plantCount.toLocaleString('ar');
    if (el.rarePlantsCount) el.rarePlantsCount.textContent = Number(item.rare_count || 0).toLocaleString('ar');

    renderSpecies(item.plants || []);
  }

  function renderSpecies(plants) {
    if (!el.topSpecies) return;
    const items = Array.isArray(plants) ? plants.slice(0, 5) : [];
    if (!items.length) {
      el.topSpecies.innerHTML = '<div class="species-item"><span class="species-icon">•</span><span>لا توجد نباتات مسجلة</span></div>';
      return;
    }
    el.topSpecies.innerHTML = items.map(name => `
      <div class="species-item">
        <span class="species-icon">🌿</span>
        <span>${name}</span>
      </div>
    `).join('');
  }

  function updateSeriesUI(payload) {
    if (!payload || !payload.current) return;
    const current = payload.current;
    const changes = payload.change_since_start || {};
    if (el.ndviCurrent) el.ndviCurrent.textContent = current.ndvi_mean != null ? Number(current.ndvi_mean).toFixed(2) : '--';
    if (el.ndviChange) {
      const changeText = `${formatSigned((changes.ndvi_mean || 0), 2)}`;
      el.ndviChange.textContent = changeText;
      el.ndviChange.className = Number(changes.ndvi_mean || 0) >= 0 ? 'chart-side-sub' : 'chart-side-sub';
    }

    setChangeValue(el.changeDensity, changes.density || 0);
    setChangeValue(el.changeRarity, changes.rarity || 0);
    setChangeValue(el.changeToxicity, changes.toxicity || 0);

    drawChart(payload.series || []);
    buildYearThumbnails(payload.series || []);
  }

  function buildYearThumbnails(series) {
    if (!el.bottomThumbs) return;
    if (!Array.isArray(series) || !series.length) {
      el.bottomThumbs.innerHTML = '';
      return;
    }
    const desiredYears = [1990, 1995, 2000, 2005, 2010, 2015, 2020, YEAR_MAX];
    const rows = desiredYears.map(year => {
      let closest = series[0];
      series.forEach(entry => {
        if (Math.abs(entry.year - year) < Math.abs(closest.year - year)) closest = entry;
      });
      return closest;
    }).filter((entry, index, arr) => arr.findIndex(x => x.year === entry.year) === index);

    el.bottomThumbs.innerHTML = rows.map(entry => {
      const active = entry.year === selectedYear ? 'active' : '';
      return `
        <button type="button" class="year-thumb ${active}" data-thumb-year="${entry.year}">
          <div class="thumb-map"><div class="thumb-glow"></div></div>
          <div class="thumb-year">${entry.year}</div>
          <div class="thumb-score">الكثافة ${clamp(entry.density).toFixed(1)}</div>
        </button>
      `;
    }).join('');

    el.bottomThumbs.querySelectorAll('[data-thumb-year]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const y = Number(btn.dataset.thumbYear);
        if (!y) return;
        selectedYear = y;
        if (el.yearSlider) el.yearSlider.value = String(y);
        updateYearTicks();
        await fetchYearData(false);
      });
    });
  }

  function drawChart(series) {
    const canvas = document.getElementById('regionTrendChart');
    if (!canvas || typeof Chart === 'undefined') return;
    const labels = series.map(item => item.year);
    const data = series.map(item => item.ndvi_mean != null ? Number(item.ndvi_mean) : null);
    const ctx = canvas.getContext('2d');

    if (yearChart) yearChart.destroy();
    yearChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'NDVI',
          data,
          borderColor: '#84d650',
          backgroundColor: 'rgba(122, 207, 67, 0.18)',
          pointRadius: 0,
          tension: 0.28,
          fill: true,
          borderWidth: 2.5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: {
            ticks: { color: '#cde2d8', maxRotation: 0, autoSkip: true },
            grid: { color: 'rgba(201, 220, 215, 0.08)' }
          },
          y: {
            ticks: { color: '#cde2d8' },
            grid: { color: 'rgba(201, 220, 215, 0.10)' }
          }
        }
      }
    });
  }

  function resetFilters() {
    if (el.regionSearch) el.regionSearch.value = '';
    if (el.regionFilter) el.regionFilter.value = '';
    if (el.densityFilter) el.densityFilter.value = '';
    if (el.rarityFilter) el.rarityFilter.value = '';
    if (el.toxicityFilter) el.toxicityFilter.value = '';
    if (el.diseaseTypeFilter) el.diseaseTypeFilter.value = '';
    if (el.diseaseFilter) el.diseaseFilter.value = '';
    selectedRegionName = '';
    syncDiseaseOptions();
    updateMapStyles();
    const fallback = defaultRegionPick();
    if (fallback) {
      selectRegion(fallback, false);
      attachMarker(fallback);
    }
  }

  async function applyFilters() {
    syncDiseaseOptions();
    selectedRegionName = el.regionFilter?.value || selectedRegionName || '';
    const filtered = getFilteredData();
    if (filtered.length === 0) {
      updateMapStyles();
      renderRegionPanel(null);
      showToast('لا توجد نتائج', 'جرّبي تغيير الفلاتر أو السنة');
      return;
    }
    const pick = filtered.find(i => i.region === selectedRegionName) || filtered[0];
    selectedRegionName = pick.region;
    selectRegion(pick, false);
    attachMarker(pick);
    updateMapStyles();
  }

  function togglePlayback() {
    playing = !playing;
    if (el.playBtn) el.playBtn.textContent = playing ? '❚❚' : '▶';
    if (playing) {
      playbackTimer = setInterval(async () => {
        let next = selectedYear + 1;
        if (next > YEAR_MAX) next = YEAR_MIN;
        selectedYear = next;
        if (el.yearSlider) el.yearSlider.value = String(next);
        await fetchYearData(false);
      }, 1800);
    } else {
      clearInterval(playbackTimer);
      playbackTimer = null;
    }
  }

  function setupActions() {
    el.playBtn?.addEventListener('click', togglePlayback);
    el.refreshBtn?.addEventListener('click', async () => {
      showToast('تحديث', 'جاري تحديث الخريطة والبيانات');
      await fetchYearData(true);
    });
    el.switchBaseBtn?.addEventListener('click', () => {
      baseLayers[activeBaseIndex].remove();
      activeBaseIndex = activeBaseIndex === 0 ? 1 : 0;
      baseLayers[activeBaseIndex].addTo(map);
      showToast('الخلفية', activeBaseIndex === 0 ? 'تم تفعيل الخلفية الطبوغرافية' : 'تم تفعيل الخلفية القياسية');
    });
    el.yearSlider?.addEventListener('input', async (event) => {
      selectedYear = Number(event.target.value);
      await fetchYearData(false);
    });
    el.regionSearch?.addEventListener('input', applyFilters);
    el.regionFilter?.addEventListener('change', applyFilters);
    el.densityFilter?.addEventListener('change', applyFilters);
    el.rarityFilter?.addEventListener('change', applyFilters);
    el.toxicityFilter?.addEventListener('change', applyFilters);
    el.diseaseTypeFilter?.addEventListener('change', applyFilters);
    el.diseaseFilter?.addEventListener('change', applyFilters);
    el.resetFiltersBtn?.addEventListener('click', resetFilters);
    el.showAllSpeciesBtn?.addEventListener('click', () => {
      if (!selectedItem) return;
      const all = (selectedItem.plants || []).length ? selectedItem.plants.join('، ') : 'لا توجد نباتات مسجلة';
      alert(all);
    });
    el.reportBtn?.addEventListener('click', () => {
      const params = new URLSearchParams({ year: String(selectedYear), region: selectedItem?.region || '' });
      window.open(`export_dashboard.php?${params.toString()}`, '_blank');
    });
    el.shareBtn?.addEventListener('click', async () => {
      const url = new URL(window.location.href);
      if (selectedItem) url.searchParams.set('region', selectedItem.region);
      url.searchParams.set('year', String(selectedYear));
      try {
        await navigator.clipboard.writeText(url.toString());
        showToast('مشاركة', 'تم نسخ رابط الصفحة');
      } catch (_) {
        showToast('مشاركة', 'تعذر نسخ الرابط');
      }
    });
    el.compareBtn?.addEventListener('click', () => {
      showToast('مقارنة المناطق', 'يمكنك اختيار منطقة أخرى من الفلاتر للمقارنة اليدوية');
    });
    el.printBtn?.addEventListener('click', () => window.print());
  }

  async function init() {
    try {
      updateYearTicks();
      setupActions();
      await loadBoundaryGeoJson();
      await fetchYearData(false);
      showToast('الخريطة الذكية', 'تم تحميل المحافظات بنمط التلوين الداخلي');
    } catch (error) {
      console.error(error);
      showToast('خطأ', error.message || 'تعذر تشغيل الخريطة');
    }
  }

  init();
})();
