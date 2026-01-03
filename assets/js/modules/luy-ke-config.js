const STATUS_TYPES = {
    DAT: 'dat',
    CHUA_DAT: 'chua_dat',
    NA: 'na'
};

const STATUS_LABELS = {
    [STATUS_TYPES.DAT]: 'Đạt',
    [STATUS_TYPES.CHUA_DAT]: 'Chưa đạt',
    [STATUS_TYPES.NA]: 'N/A'
};

const STATUS_CLASSES = {
    [STATUS_TYPES.DAT]: 'luy-ke-status-pass',
    [STATUS_TYPES.CHUA_DAT]: 'luy-ke-status-fail',
    [STATUS_TYPES.NA]: 'luy-ke-status-na'
};

const FEATURE_FLAGS = {
    LUY_KE_STATUS_ENABLED: true
};

const CONFIG = {
    statusTypes: STATUS_TYPES,
    statusLabels: STATUS_LABELS,
    statusClasses: STATUS_CLASSES,
    featureFlags: FEATURE_FLAGS
};

export function getLuyKeConfig() {
    return { ...CONFIG };
}

export function isLuyKeStatusEnabled() {
    return FEATURE_FLAGS.LUY_KE_STATUS_ENABLED;
}

export function formatLuyKeStatusLabel(status) {
    return STATUS_LABELS[status] || STATUS_LABELS[STATUS_TYPES.NA];
}

export function buildLuyKeTooltip(chiTieu, thucTe, status) {
    if (status === STATUS_TYPES.NA) {
        return 'Không có dữ liệu';
    }
    
    const statusLabel = formatLuyKeStatusLabel(status);
    const percent = chiTieu > 0 ? Math.round((thucTe / chiTieu) * 100) : 0;
    
    return `${statusLabel}: ${thucTe}/${chiTieu} (${percent}%)`;
}

export function getStatusClass(status) {
    return STATUS_CLASSES[status] || STATUS_CLASSES[STATUS_TYPES.NA];
}

export { STATUS_TYPES };
