export const state = {
    chart: null,
    congDoanChart: null,
    currentView: 'congdoan',
    congDoanDataLoaded: false,
    matrixDataLoaded: false,
    filters: {
        line_id: null,
        ma_hang_id: null,
        ngay: null,
        ca_id: null,
        bao_cao_id: null
    },
    maHangList: [],
    isAdmin: window.appConfig?.isAdmin ?? false,
    tongQuanChartType: 'line',
    tongQuanData: null,
    tongQuanMatrixData: null,
    tongQuanMatrixLoaded: false,
    urlFilters: null,
    isInitialLoad: true,
    elements: null
};
