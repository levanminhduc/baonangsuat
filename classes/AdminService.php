<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/services/LineService.php';
require_once __DIR__ . '/services/UserService.php';
require_once __DIR__ . '/services/MaHangService.php';
require_once __DIR__ . '/services/CongDoanService.php';
require_once __DIR__ . '/services/RoutingService.php';

class AdminService {
    private $lineService;
    private $userService;
    private $maHangService;
    private $congDoanService;
    private $routingService;
    
    public function __construct() {
        $this->lineService = new LineService();
        $this->userService = new UserService();
        $this->maHangService = new MaHangService();
        $this->congDoanService = new CongDoanService();
        $this->routingService = new RoutingService();
    }
    
    public function getAllUsers() {
        return $this->userService->getAll();
    }
    
    public function getUsersWithInfo() {
        return $this->userService->getAllWithInfo();
    }
    
    public function getUserLineListWithInfo() {
        return $this->userService->getUserLineListWithInfo();
    }
    
    public function getLineList() {
        return $this->lineService->getList();
    }
    
    public function getUserLineList() {
        return $this->userService->getUserLineList();
    }
    
    public function addUserLine($ma_nv, $line_id) {
        return $this->userService->addUserLine($ma_nv, $line_id);
    }
    
    public function removeUserLine($ma_nv, $line_id) {
        return $this->userService->removeUserLine($ma_nv, $line_id);
    }
    
    public function createLine($ma_line, $ten_line) {
        return $this->lineService->create($ma_line, $ten_line);
    }
    
    public function updateLine($id, $ma_line, $ten_line, $is_active) {
        return $this->lineService->update($id, $ma_line, $ten_line, $is_active);
    }
    
    public function deleteLine($id) {
        return $this->lineService->delete($id);
    }
    
    public function getLine($id) {
        return $this->lineService->get($id);
    }
    
    public function getMaHangList() {
        return $this->maHangService->getList();
    }
    
    public function getMaHang($id) {
        return $this->maHangService->get($id);
    }
    
    public function createMaHang($ma_hang, $ten_hang) {
        return $this->maHangService->create($ma_hang, $ten_hang);
    }
    
    public function updateMaHang($id, $ma_hang, $ten_hang, $is_active) {
        return $this->maHangService->update($id, $ma_hang, $ten_hang, $is_active);
    }
    
    public function deleteMaHang($id) {
        return $this->maHangService->delete($id);
    }
    
    public function getCongDoanList() {
        return $this->congDoanService->getList();
    }
    
    public function getCongDoan($id) {
        return $this->congDoanService->get($id);
    }
    
    public function createCongDoan($ma_cong_doan, $ten_cong_doan, $la_cong_doan_thanh_pham = 0) {
        return $this->congDoanService->create($ma_cong_doan, $ten_cong_doan, $la_cong_doan_thanh_pham);
    }
    
    public function updateCongDoan($id, $ma_cong_doan, $ten_cong_doan, $is_active, $la_cong_doan_thanh_pham = 0) {
        return $this->congDoanService->update($id, $ma_cong_doan, $ten_cong_doan, $is_active, $la_cong_doan_thanh_pham);
    }
    
    public function deleteCongDoan($id) {
        return $this->congDoanService->delete($id);
    }
    
    public function getRoutingList($ma_hang_id) {
        return $this->routingService->getList($ma_hang_id);
    }
    
    public function getRouting($id) {
        return $this->routingService->get($id);
    }
    
    public function addRouting($ma_hang_id, $cong_doan_id, $thu_tu, $bat_buoc = 1, $la_cong_doan_tinh_luy_ke = 0, $line_id = null, $ghi_chu = '') {
        return $this->routingService->add($ma_hang_id, $cong_doan_id, $thu_tu, $bat_buoc, $la_cong_doan_tinh_luy_ke, $line_id, $ghi_chu);
    }
    
    public function updateRouting($id, $thu_tu, $bat_buoc, $la_cong_doan_tinh_luy_ke, $line_id = null, $ghi_chu = '') {
        return $this->routingService->update($id, $thu_tu, $bat_buoc, $la_cong_doan_tinh_luy_ke, $line_id, $ghi_chu);
    }
    
    public function removeRouting($id) {
        return $this->routingService->remove($id);
    }
}
