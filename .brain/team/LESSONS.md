# Lessons & upgrade proposals
<!-- Do /retro quản lý. Bạn chỉ cần đổi Status của đề xuất: PROPOSED → APPROVED hoặc REJECTED, rồi chạy /retro apply. -->

Reviewed runs: F-015

## Lessons
<!-- Mỗi bài học một dòng. Cause: agent-prompt | tagging | spec-quality | project-knowledge | verifier-miss | one-off
     ví dụ: | L-001 | implementer không chạy lint trước khi báo DONE | agent-prompt | implementer | F-012 S1, F-014 S3 | 2 | -->
| ID | Lesson | Cause | Agent / file | Evidence | Count |
|---|---|---|---|---|---|
| L-001 | Spec tính số từ dữ liệu có sẵn (lũy kế PO) không kiểm ngữ nghĩa dữ liệu trong code → thiết kế SUM các mốc trong khi ô nhập đã là lũy kế tại mốc | spec-quality | commands/feature.md (bước 2) | F-015 S5 (implementer NOTICED, orchestrator sửa + amend spec) | 1 |
| L-002 | Step thêm kết nối hệ thống ngoài chỉ Check đường "không cấu hình", không đo đường host không tới được → timeout DSN bị bỏ qua, treo 21s | spec-quality | features/_TEMPLATE.md (Check) | F-015 S2 → R1-B1 | 1 |
| L-003 | implementer báo DONE nhưng bỏ sót một mục "Done when" (thêm URL smoke + chụp baseline) | agent-prompt | implementer | F-015 S3 → R1-B2 | 1 |
| L-004 | Đường suy giảm khi phụ thuộc ngoài chết (available:false) không được xét ở luồng ghi FE → Lưu xoá PO | agent-prompt | implementer | F-015 S5 → R1-B3 | 1 |
| L-005 | Verifier vòng 2 treo 600s, orchestrator tự soát → vòng 2 không có review độc lập | one-off | verifier | F-015 Verify R2 | 1 |

## Proposals
<!-- ví dụ:
### P-001 implementer chạy lint trước khi báo DONE
Status: PROPOSED
From: L-001
Target: .claude/agents/implementer.md → ## Learned rules
Change: + "Before reporting DONE, run the project's Lint command on the files you changed and fix what it reports."
Expected: giảm blocker loại lint ở vòng verify 1
Watch: SCORECARD implementer "Blockers / step" (baseline 0.6)
-->

### P-001 implementer đọc lại từng mục "Done when" trước khi báo DONE
Status: APPLIED (2026-11-17)
From: L-003 (người dùng duyệt bỏ qua ngưỡng 2 bằng chứng, 2026-11-17)
Target: ~/.claude/agents/implementer.md → ## Learned rules
Change: + "Before reporting DONE, re-read every 'Done when' item of your step and confirm each one is met (files, URLs, baselines, docs); list them in your report. (P-001)"
Expected: hết blocker kiểu bỏ sót mục Done when ở verify vòng 1
Watch: SCORECARD implementer "Blockers / step" (baseline 0.5)

### P-002 implementer xét đường suy giảm ở mọi luồng ghi
Status: APPLIED (2026-11-17)
From: L-004 (người dùng duyệt bỏ qua ngưỡng 2 bằng chứng, 2026-11-17)
Target: ~/.claude/agents/implementer.md → ## Learned rules
Change: + "When code depends on an external service that can be unavailable, check every write/save path in its degraded state: never send a value that would overwrite stored data with an empty or default one. (P-002)"
Expected: hết blocker kiểu mất dữ liệu khi dịch vụ ngoài chết
Watch: SCORECARD implementer "Blockers / step" (baseline 0.5)

### P-003 Spec template: Check kết nối ngoài đo cả host không tới được
Status: APPLIED (2026-11-17)
From: L-002 (người dùng duyệt bỏ qua ngưỡng 2 bằng chứng, 2026-11-17)
Target: .brain/features/_TEMPLATE.md → ## 7. Plan (comment tag)
Change: + "Step thêm kết nối hệ thống ngoài: Check phải đo cả đường host không tới được (thời gian chờ ≤ timeout cấu hình). (P-003)"
Expected: lỗi timeout/kết nối bắt được ngay ở step, không đợi verifier
Watch: SCORECARD "Runs PASS at verify round 1" (baseline 0 / 1)

### P-004 /feature xác nhận ngữ nghĩa dữ liệu có sẵn trước khi thiết kế công thức
Status: APPLIED (2026-11-17)
From: L-001 (người dùng duyệt bỏ qua ngưỡng 2 bằng chứng, 2026-11-17)
Target: ~/.claude/commands/feature.md → ## 2
Change: + "When the design computes from data that already exists, confirm in code what that data means (e.g. a cell holds a cumulative value vs. a per-slot value) before writing the formula. (P-004)"
Expected: không còn amend spec giữa run vì hiểu sai dữ liệu
Watch: số "Amended" do sai ngữ nghĩa dữ liệu trong report; SCORECARD "Runs PASS at verify round 1" (baseline 0 / 1)
