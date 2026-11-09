import { api } from '../admin-api.js';
import { escapeHtml, showToast } from '../admin-utils.js';
import { getState, setUsers, setAllUsersOptions, setUsersPermissions } from './state.js';

export async function loadUsers() {
    try {
        const response = await api('GET', '/admin/users');
        if (response.success) {
            setUsers(response.data);
            buildUserOptions();
            await loadUsersPermissions();
        }
    } catch (error) {
        showToast('Lỗi tải danh sách users', 'error');
    }
}

export async function loadUsersPermissions() {
    const state = getState();
    
    if (!state.users || state.users.length === 0) {
        setUsersPermissions([]);
        renderPermissionsTable();
        return;
    }
    
    // Bulk fetch all permissions in a single API call
    const userIds = state.users.map(u => u.id);
    
    try {
        const response = await api('POST', '/user-permissions/bulk', { userIds });
        
        if (response.success) {
            setUsersPermissions(response.data);
        } else {
            console.error('Error loading permissions:', response.error);
            setUsersPermissions([]);
        }
        
        renderPermissionsTable();
    } catch (e) {
        console.error('Error loading permissions', e);
        setUsersPermissions([]);
        renderPermissionsTable();
    }
}

export function renderPermissionsTable() {
    const state = getState();
    const tbody = document.querySelector('#permissionsTable tbody');
    if (!tbody) return;
    
    let filteredUsers = state.users;
    const searchTerm = document.getElementById('permissionsSearch').value.toLowerCase().trim();
    
    if (searchTerm) {
        filteredUsers = state.users.filter(u => 
            u.name.toLowerCase().includes(searchTerm) || 
            (u.ho_ten && u.ho_ten.toLowerCase().includes(searchTerm))
        );
    }
    
    tbody.innerHTML = filteredUsers.map(u => {
        const userPerms = state.usersPermissions.find(up => up.userId == u.id);
        const hasHistoryPerm = userPerms ? userPerms.permissions.includes('can_view_history') : false;
        const hasCreateReportPerm = userPerms ? userPerms.permissions.includes('tao_bao_cao') : false;
        const hasCreateReportAnyLinePerm = userPerms ? userPerms.permissions.includes('tao_bao_cao_cho_line') : false;
        const hasImportPerm = userPerms ? userPerms.permissions.includes('import_ma_hang_cong_doan') : false;
        const isAdmin = u.role === 'admin';
        
        return `
        <tr>
            <td>${escapeHtml(u.name)}</td>
            <td>${escapeHtml(u.ho_ten || '-')}</td>
            <td><span class="status-badge ${u.role === 'admin' ? 'status-approved' : 'status-draft'}">${u.role}</span></td>
            <td>
                <label class="switch">
                    <input type="checkbox"
                        ${hasHistoryPerm || isAdmin ? 'checked' : ''}
                        ${isAdmin ? 'disabled' : ''}
                        onchange="window.toggleHistoryPermission(${u.id}, this.checked)">
                    <span class="slider round ${isAdmin ? 'disabled' : ''}"></span>
                </label>
            </td>
            <td>
                <label class="switch">
                    <input type="checkbox"
                        ${hasCreateReportPerm || isAdmin ? 'checked' : ''}
                        ${isAdmin ? 'disabled' : ''}
                        onchange="window.toggleCreateReportPermission(${u.id}, this.checked)">
                    <span class="slider round ${isAdmin ? 'disabled' : ''}"></span>
                </label>
            </td>
            <td>
                <label class="switch">
                    <input type="checkbox"
                        ${hasCreateReportAnyLinePerm || isAdmin ? 'checked' : ''}
                        ${isAdmin ? 'disabled' : ''}
                        onchange="window.toggleCreateReportAnyLinePermission(${u.id}, this.checked)">
                    <span class="slider round ${isAdmin ? 'disabled' : ''}"></span>
                </label>
            </td>
            <td>
                <label class="switch">
                    <input type="checkbox"
                        ${hasImportPerm || isAdmin ? 'checked' : ''}
                        ${isAdmin ? 'disabled' : ''}
                        onchange="window.toggleImportPermission(${u.id}, this.checked)">
                    <span class="slider round ${isAdmin ? 'disabled' : ''}"></span>
                </label>
            </td>
        </tr>
    `}).join('');
}

export function handlePermissionsSearch() {
    renderPermissionsTable();
}

export async function toggleHistoryPermission(userId, checked) {
    const state = getState();
    if (!userId) return;
    
    try {
        let response;
        if (checked) {
            response = await api('POST', '/user-permissions', { 
                nguoi_dung_id: userId, 
                quyen: 'can_view_history' 
            });
        } else {
            response = await api('DELETE', `/user-permissions/${userId}/can_view_history`);
        }
        
        if (response.success) {
            showToast(response.message, 'success');
            const userPerms = state.usersPermissions.find(up => up.userId == userId);
            if (userPerms) {
                if (checked) {
                    if (!userPerms.permissions.includes('can_view_history')) {
                        userPerms.permissions.push('can_view_history');
                    }
                } else {
                    userPerms.permissions = userPerms.permissions.filter(p => p !== 'can_view_history');
                }
            }
        } else {
            showToast(response.message, 'error');
            renderPermissionsTable();
        }
    } catch (error) {
        showToast('Lỗi cập nhật quyền', 'error');
        renderPermissionsTable();
    }
}

export async function toggleCreateReportPermission(userId, checked) {
    const state = getState();
    if (!userId) return;
    
    try {
        let response;
        if (checked) {
            response = await api('POST', '/user-permissions', { 
                nguoi_dung_id: userId, 
                quyen: 'tao_bao_cao' 
            });
        } else {
            response = await api('DELETE', `/user-permissions/${userId}/tao_bao_cao`);
        }
        
        if (response.success) {
            showToast(response.message, 'success');
            const userPerms = state.usersPermissions.find(up => up.userId == userId);
            if (userPerms) {
                if (checked) {
                    if (!userPerms.permissions.includes('tao_bao_cao')) {
                        userPerms.permissions.push('tao_bao_cao');
                    }
                } else {
                    userPerms.permissions = userPerms.permissions.filter(p => p !== 'tao_bao_cao');
                }
            }
        } else {
            showToast(response.message, 'error');
            renderPermissionsTable();
        }
    } catch (error) {
        showToast('Lỗi cập nhật quyền', 'error');
        renderPermissionsTable();
    }
}

export async function toggleCreateReportAnyLinePermission(userId, checked) {
    const state = getState();
    if (!userId) return;
    
    try {
        let response;
        if (checked) {
            response = await api('POST', '/user-permissions', {
                nguoi_dung_id: userId,
                quyen: 'tao_bao_cao_cho_line'
            });
        } else {
            response = await api('DELETE', `/user-permissions/${userId}/tao_bao_cao_cho_line`);
        }
        
        if (response.success) {
            showToast(response.message, 'success');
            const userPerms = state.usersPermissions.find(up => up.userId == userId);
            if (userPerms) {
                if (checked) {
                    if (!userPerms.permissions.includes('tao_bao_cao_cho_line')) {
                        userPerms.permissions.push('tao_bao_cao_cho_line');
                    }
                } else {
                    userPerms.permissions = userPerms.permissions.filter(p => p !== 'tao_bao_cao_cho_line');
                }
            }
        } else {
            showToast(response.message, 'error');
            renderPermissionsTable();
        }
    } catch (error) {
        showToast('Lỗi cập nhật quyền', 'error');
        renderPermissionsTable();
    }
}

export async function toggleImportPermission(userId, checked) {
    const state = getState();
    if (!userId) return;
    
    try {
        let response;
        if (checked) {
            response = await api('POST', '/user-permissions', {
                nguoi_dung_id: userId,
                quyen: 'import_ma_hang_cong_doan'
            });
        } else {
            response = await api('DELETE', `/user-permissions/${userId}/import_ma_hang_cong_doan`);
        }
        
        if (response.success) {
            showToast(response.message, 'success');
            const userPerms = state.usersPermissions.find(up => up.userId == userId);
            if (userPerms) {
                if (checked) {
                    if (!userPerms.permissions.includes('import_ma_hang_cong_doan')) {
                        userPerms.permissions.push('import_ma_hang_cong_doan');
                    }
                } else {
                    userPerms.permissions = userPerms.permissions.filter(p => p !== 'import_ma_hang_cong_doan');
                }
            }
        } else {
            showToast(response.message, 'error');
            renderPermissionsTable();
        }
    } catch (error) {
        showToast('Lỗi cập nhật quyền', 'error');
        renderPermissionsTable();
    }
}

export function buildUserOptions() {
    const state = getState();
    const options = state.users.map(u => ({
        value: u.name,
        label: u.ho_ten ? `${u.name} - ${u.ho_ten}` : u.name,
        searchText: `${u.name} ${u.ho_ten || ''}`.toLowerCase()
    }));
    setAllUsersOptions(options);
}

export function bindEvents() {
    document.getElementById('permissionsSearch').addEventListener('input', handlePermissionsSearch);
}

export function init() {
    bindEvents();
}
