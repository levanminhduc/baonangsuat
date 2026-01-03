import { STATUS_TYPES } from './luy-ke-config.js';
import { sortMocGioList } from './time-manager.js';

export function getLastInputMocId(inputValuesByMoc, mocGioList) {
    if (!inputValuesByMoc || !Array.isArray(mocGioList) || mocGioList.length === 0) {
        return null;
    }
    
    const sortedDescMocList = [...mocGioList].sort((a, b) => Number(b.thu_tu) - Number(a.thu_tu));
    
    for (const moc of sortedDescMocList) {
        const mocId = Number(moc.id);
        const value = getNumericValue(inputValuesByMoc, mocId);
        if (value !== null && value > 0) {
            return mocId;
        }
    }
    
    return null;
}

export function computeLuyKeStatus({ mocGioList, chiTieuLuyKeMap, luyKeThucTeMap, inputValuesByMoc, isEditable }) {
    const statusByMocId = {};
    const detailByMocId = {};
    
    if (!Array.isArray(mocGioList) || mocGioList.length === 0) {
        return { statusByMocId, detailByMocId };
    }
    
    const sortedMocList = sortMocGioList(mocGioList);
    const targetMocId = getLastInputMocId(inputValuesByMoc, sortedMocList);
    
    if (targetMocId === null) {
        for (const moc of sortedMocList) {
            const mocId = Number(moc.id);
            statusByMocId[mocId] = STATUS_TYPES.NA;
            detailByMocId[mocId] = {
                thucTe: null,
                chiTieu: getNumericValue(chiTieuLuyKeMap, mocId),
                status: STATUS_TYPES.NA
            };
        }
        return { statusByMocId, detailByMocId, targetMocId: null };
    }
    
    const thucTe = getNumericValue(inputValuesByMoc, targetMocId);
    const chiTieu = getNumericValue(chiTieuLuyKeMap, targetMocId);
    const status = calculateSingleStatus(chiTieu, thucTe);
    
    for (const moc of sortedMocList) {
        const mocId = Number(moc.id);
        statusByMocId[mocId] = status;
        detailByMocId[mocId] = {
            thucTe,
            chiTieu,
            status
        };
    }
    
    return { statusByMocId, detailByMocId, targetMocId };
}

function calculateSingleStatus(chiTieu, thucTe) {
    if (chiTieu === null || chiTieu === 0) {
        return STATUS_TYPES.NA;
    }
    
    if (thucTe === null) {
        return STATUS_TYPES.NA;
    }
    
    if (thucTe >= chiTieu) {
        return STATUS_TYPES.DAT;
    }
    
    return STATUS_TYPES.CHUA_DAT;
}

function getNumericValue(map, key) {
    if (!map) {
        return null;
    }
    
    const numKey = Number(key);
    const strKey = String(key);
    
    let value;
    
    if (map instanceof Map) {
        value = map.has(numKey) ? map.get(numKey) : map.get(strKey);
    } else if (typeof map === 'object') {
        value = map[numKey] !== undefined ? map[numKey] : map[strKey];
    } else {
        return null;
    }
    
    if (value === undefined || value === null) {
        return null;
    }
    
    const numValue = Number(value);
    return isNaN(numValue) ? null : numValue;
}
