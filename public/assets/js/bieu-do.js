/**
 * Biểu đồ năng suất - Chart Module
 * Displays productivity comparison chart (Chỉ tiêu vs Thực tế)
 */

import { fetchCsrfToken } from './modules/api.js';
import {
    bindElements,
    loadFiltersFromURL,
    bindEvents,
    bindPopState,
    loadInitialData
} from './modules/bieu-do/filters.js';
import { bindViewToggle, bindChartTypeToggle } from './modules/bieu-do/chart-type.js';
import { bindMatrixToggle, bindTongQuanMatrixToggle } from './modules/bieu-do/matrix.js';

async function init() {
    await fetchCsrfToken();
    bindElements();
    loadFiltersFromURL();
    bindEvents();
    bindViewToggle();
    bindMatrixToggle();
    bindChartTypeToggle();
    bindTongQuanMatrixToggle();
    bindPopState();
    await loadInitialData();
}

init();
