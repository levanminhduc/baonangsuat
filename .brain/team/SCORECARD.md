# Agent scorecard
<!-- /go cập nhật nhẹ sau mỗi lần chạy; /retro rà lại. Số cộng dồn qua mọi việc (F- và B-). -->

Runs counted: 3   Last update: 2026-11-25 (/go F-017)

| Agent | Model·effort | Steps | Done first try | Escalated away | Verifier blockers | Blockers / step | PLAN_ISSUE (valid/invalid) |
|---|---|---|---|---|---|---|---|
| chore | haiku·low | 3 | 1 | 0 | 0 | 0 | 1 / 0 |
| tester | sonnet·medium | 2 | 2 | 0 | 0 | 0 | — |
| implementer | sonnet·medium | 20 | 20 | 0 | 3 | 0.15 | — |
| implementer-high | sonnet·high | 1 | 1 | 0 | 0 | 0 | — |
| doc-writer | sonnet·medium | 2 | 2 | 0 | 0 | 0 | — |
| orchestrator [opus] steps | session | 12 | 12 | — | 2 | 0.17 | — |

| Team metric | Value |
|---|---|
| Runs PASS at verify round 1 | 1 / 3 |
| Avg verify rounds | 1.67 |
| Runs STOPPED / FAIL | 0 |
| Bugs traced back to a DONE feature (/fix ghi) | 0 |
| debugger: reproduced / asked | 0 / 0 |

## History (mỗi run một dòng)
| Run | Result | Verify rounds | Escalations | Notes |
|---|---|---|---|---|
| F-015 | PASS | 2 | 1 (verify R1 → implementer-high) | R1: timeout datchi (S2), smoke URL (S3), mất PO khi datchi chết (S5); lũy kế SUM mốc do spec sai, orchestrator sửa; verifier R2 treo → orchestrator soát |
| F-016 | PASS | 2 | 1 (verify R1 → orchestrator, 2 blocker 1–2 dòng) | R1: mất window.historyModule (S9 xoá global spec ghi giữ), login.js còn API_BASE riêng (không ghi Amended). Spec sai về layer(components), phát hiện nhờ so computed style. 25 bước, 0 leo thang khi chạy. |
| F-017 | PASS | 1 | 0 (S5 smoke lệch do spec, orchestrator amend) | Dọn kho + .gitattributes LF. chore S4 PLAN_ISSUE đúng (urls.txt có URL .scratch), S5 smoke 403→404 /includes (spec nói "không đổi hành vi" — sai). Chạy S3 trước vì reset --hard. |
