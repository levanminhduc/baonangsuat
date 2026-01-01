const state = {
    lines: [],
    userLines: [],
    users: [],
    allUsersOptions: [],
    usersPermissions: [],
    maHang: [],
    congDoan: [],
    routing: [],
    mocGio: [],
    caList: [],
    presets: [],
    
    selectedMaHangId: null,
    userLineFilterLineId: '',
    selectedCaId: null,
    selectedLineIdForMocGio: null,
    
    currentPresetDetail: null,
    currentPresetMocGio: [],
    assignedLines: [],
    
    adminRouter: null
};

export function getState() {
    return state;
}

export function setLines(data) {
    state.lines = data;
}

export function setUserLines(data) {
    state.userLines = data;
}

export function setUsers(data) {
    state.users = data;
}

export function setAllUsersOptions(data) {
    state.allUsersOptions = data;
}

export function setUsersPermissions(data) {
    state.usersPermissions = data;
}

export function setMaHang(data) {
    state.maHang = data;
}

export function setCongDoan(data) {
    state.congDoan = data;
}

export function setRouting(data) {
    state.routing = data;
}

export function setMocGio(data) {
    state.mocGio = data;
}

export function setCaList(data) {
    state.caList = data;
}

export function setPresets(data) {
    state.presets = data;
}

export function setSelectedMaHangId(id) {
    state.selectedMaHangId = id;
}

export function setUserLineFilterLineId(id) {
    state.userLineFilterLineId = id;
}

export function setSelectedCaId(id) {
    state.selectedCaId = id;
}

export function setSelectedLineIdForMocGio(id) {
    state.selectedLineIdForMocGio = id;
}

export function setCurrentPresetDetail(data) {
    state.currentPresetDetail = data;
}

export function setCurrentPresetMocGio(data) {
    state.currentPresetMocGio = data;
}

export function setAssignedLines(data) {
    state.assignedLines = data;
}

export function setAdminRouter(router) {
    state.adminRouter = router;
}
