# DESIGN.md — quyết định giao diện

Ghi các quyết định đã chốt cho cả app. Đổi thì sửa ở đây trước.

## Khung trang (`views/partials/navbar.php`, `public/assets/css/layout.css`)

- Sidebar trái gọn, nhóm phẳng. Mục đang xem + nhóm cha tô xanh `#143583`, bo góc nhỏ (6px).
- Thanh trên nền xanh `#143583`, chữ trắng, liền với dòng logo của sidebar.
- Từ 1024px: sidebar cố định 224px, thu về dải icon 56px (nhớ trong `localStorage`).
  Dưới 1024px: panel trượt từ trái, mở bằng nút menu.
- 11 tab admin nằm trong sidebar, 4 nhóm: Tổ chức, Sản phẩm, Mốc giờ, Dữ liệu.
- Class tiền tố `bns-`, CSS thuần (không qua Tailwind) để không đụng `.btn`, `.admin-tab` của `style.css`.

## Màn nhập báo cáo (`public/assets/css/entry.css`, `modules/grid.js`)

- Thanh trên khi mở báo cáo: quay lại, "LINE · Ca · ngày", trạng thái, trạng thái lưu, nút thao tác.
- Dải thông tin: lũy kế tại mốc đang nhập (số / chỉ tiêu, %, thanh tiến độ), các trường báo cáo.
  Mobile là lưới ô kẻ 2 cột; từ 640px là một hàng.
- Desktop/tablet (từ 640px): bảng, dòng tiêu đề + cột Công đoạn + cột Lũy kế dính khi cuộn.
  Cột mốc đang nhập tô nền nhạt; mốc chưa tới để ô trơn và hiệu suất "–".
- Mobile (dưới 640px): chọn mốc giờ bằng hàng nút, danh sách 3 cột kẻ dọc Công đoạn | SL | Lũy kế.
  Ô mobile chỉ phản chiếu ô bảng desktop (nguồn dữ liệu duy nhất).
- Ô nhập `type="text" inputmode="numeric"`, chỉ nhận chữ số. Màn cảm ứng: vùng bấm 44px, chữ 16px.
- Ngưỡng hiệu suất: ≥100% đạt (xanh), ≥90% gần đạt (cam), <90% chưa đạt (đỏ).
- Lỗi lưu hiện thanh đỏ trên bảng kèm nút "Thử lại"; không tô màu đạt/chưa đạt từng ô.
