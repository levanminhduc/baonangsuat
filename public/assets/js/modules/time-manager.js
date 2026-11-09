export function sortMocGioList(mocGioList) {
    if (!Array.isArray(mocGioList) || mocGioList.length === 0) {
        return [];
    }
    
    return [...mocGioList].sort((a, b) => {
        return Number(a.thu_tu) - Number(b.thu_tu);
    });
}
