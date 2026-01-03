export function buildMocIndex(mocGioList) {
    if (!Array.isArray(mocGioList) || mocGioList.length === 0) {
        return new Map();
    }
    
    const index = new Map();
    
    for (const moc of mocGioList) {
        index.set(Number(moc.id), {
            id: Number(moc.id),
            gio: moc.gio,
            thuTu: Number(moc.thu_tu),
            soPhutHieuDungLuyKe: Number(moc.so_phut_hieu_dung_luy_ke || 0)
        });
    }
    
    return index;
}

export function getMocThuTu(mocIndex, mocId) {
    if (!mocIndex || !(mocIndex instanceof Map)) {
        return null;
    }
    
    const moc = mocIndex.get(Number(mocId));
    return moc ? moc.thuTu : null;
}

export function sortMocGioList(mocGioList) {
    if (!Array.isArray(mocGioList) || mocGioList.length === 0) {
        return [];
    }
    
    return [...mocGioList].sort((a, b) => {
        return Number(a.thu_tu) - Number(b.thu_tu);
    });
}

export function getMocById(mocIndex, mocId) {
    if (!mocIndex || !(mocIndex instanceof Map)) {
        return null;
    }
    
    return mocIndex.get(Number(mocId)) || null;
}

export function getMocsUpTo(mocGioList, targetThuTu) {
    if (!Array.isArray(mocGioList) || mocGioList.length === 0) {
        return [];
    }
    
    const sorted = sortMocGioList(mocGioList);
    return sorted.filter(moc => Number(moc.thu_tu) <= targetThuTu);
}

export function getCurrentTimeMocId(mocGioList, customDate = null) {
    if (!Array.isArray(mocGioList) || mocGioList.length === 0) {
        return null;
    }

    // 1. Sort mocs by sequence
    const sortedMocs = sortMocGioList(mocGioList);

    // 2. Linearize moc times (handle day crossing)
    let linearizedMocs = [];
    let previousMinutes = -1;
    let offset = 0;

    for (const moc of sortedMocs) {
        if (!moc.gio) continue;
        const [h, m] = moc.gio.split(':').map(Number);
        let minutes = h * 60 + m;

        // If time drops (e.g. 23:00 -> 00:00), assume new day
        if (previousMinutes !== -1 && minutes < previousMinutes) {
            offset += 1440;
        }

        linearizedMocs.push({
            id: moc.id,
            minutes: minutes + offset,
            rawMinutes: minutes
        });
        previousMinutes = minutes;
    }

    if (linearizedMocs.length === 0) return null;

    const firstMocStart = linearizedMocs[0].minutes;
    const lastMocEnd = linearizedMocs[linearizedMocs.length - 1].minutes;

    // 3. Get current time in minutes
    const now = customDate || new Date();
    let currentMinutes = now.getHours() * 60 + now.getMinutes();

    // 4. Adjust current time for day crossing
    // If the shift spans midnight (lastMoc > 1440) and current time is "early",
    // check if treating it as next day puts it within valid range.
    if (lastMocEnd >= 1440) {
        // If current time is before start, but current+1440 is relevant
        if (currentMinutes < firstMocStart) {
            // If adding 24h puts it "close" to the shift end (within 4 hours buffer)
            // or at least past the start
            if (currentMinutes + 1440 <= lastMocEnd + 240) { 
                currentMinutes += 1440;
            }
        }
    }

    // 5. Find the latest moc that has passed
    // "Trước mốc đầu tiên → return null"
    if (currentMinutes < linearizedMocs[0].minutes) {
        return null;
    }

    let selectedId = null;
    for (const moc of linearizedMocs) {
        if (currentMinutes >= moc.minutes) {
            selectedId = moc.id;
        } else {
            // Since sorted, once we exceed current time, we stop?
            // No, we want the *latest* one that satisfies condition.
            // Example: current=10:45. mocs=9, 10, 11.
            // 9 <= 10:45 (yes, set selected)
            // 10 <= 10:45 (yes, update selected)
            // 11 <= 10:45 (no)
            break;
        }
    }

    return selectedId;
}
