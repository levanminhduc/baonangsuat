# Agent changelog
<!-- Mỗi lần /retro apply ghi một dòng. Dùng để đánh giá lại sau ≥ 3 run và hoàn tác nếu không hiệu quả. -->

| Date | Proposal | File | Change | Evidence | Metric to watch | Baseline | Result after ≥3 runs |
|---|---|---|---|---|---|---|---|
| 2026-11-17 | P-001 | ~/.claude/agents/implementer.md | + Learned rule: đọc lại từng mục Done when trước DONE | F-015 S3 R1-B2 (1 bằng chứng, user duyệt) | implementer Blockers / step | 0.5 | |
| 2026-11-17 | P-002 | ~/.claude/agents/implementer.md | + Learned rule: xét luồng ghi khi dịch vụ ngoài chết | F-015 S5 R1-B3 (1 bằng chứng, user duyệt) | implementer Blockers / step | 0.5 | |
| 2026-11-17 | P-003 | .brain/features/_TEMPLATE.md | + gợi ý Check kết nối ngoài đo host không tới được | F-015 S2 R1-B1 (1 bằng chứng, user duyệt) | Runs PASS at verify round 1 | 0 / 1 | |
| 2026-11-17 | P-004 | ~/.claude/commands/feature.md | + bước 2: xác nhận ngữ nghĩa dữ liệu có sẵn trong code | F-015 S5 amend lũy kế (1 bằng chứng, user duyệt) | Runs PASS at verify round 1 | 0 / 1 | |
