# Agent scorecard
<!-- /go cập nhật nhẹ sau mỗi lần chạy; /retro rà lại. Số cộng dồn qua mọi việc (F- và B-). -->

Runs counted: 4   Last update: 2026-11-26 (/go F-018)

| Agent | Model·effort | Steps | Done first try | Escalated away | Verifier blockers | Blockers / step | PLAN_ISSUE (valid/invalid) |
|---|---|---|---|---|---|---|---|
| chore | haiku·low | 5 | 3 | 2 | 0 | 0 | 1 / 0 |
| tester | sonnet·medium | 3 | 3 | 0 | 0 | 0 | — |
| implementer | sonnet·medium | 28 | 28 | 0 | 3 | 0.11 | — |
| implementer-high | sonnet·high | 1 | 1 | 0 | 0 | 0 | — |
| doc-writer | sonnet·medium | 3 | 3 | 0 | 0 | 0 | — |
| orchestrator [opus] steps | session | 14 | 14 | — | 2 | 0.14 | — |

| Team metric | Value |
|---|---|
| Runs PASS at verify round 1 | 2 / 4 |
| Avg verify rounds | 1.5 |
| Runs STOPPED / FAIL | 0 |
| Bugs traced back to a DONE feature (/fix ghi) | 0 |
| debugger: reproduced / asked | 0 / 0 |

## History (mỗi run một dòng)
| Run | Result | Verify rounds | Escalations | Notes |
|---|---|---|---|---|
| F-015 | PASS | 2 | 1 (verify R1 → implementer-high) | R1: timeout datchi (S2), smoke URL (S3), mất PO khi datchi chết (S5); lũy kế SUM mốc do spec sai, orchestrator sửa; verifier R2 treo → orchestrator soát |
| F-016 | PASS | 2 | 1 (verify R1 → orchestrator, 2 blocker 1–2 dòng) | R1: mất window.historyModule (S9 xoá global spec ghi giữ), login.js còn API_BASE riêng (không ghi Amended). Spec sai về layer(components), phát hiện nhờ so computed style. 25 bước, 0 leo thang khi chạy. |
| F-017 | PASS | 1 | 2 (S4 PLAN_ISSUE, S5 smoke lệch — chore → orchestrator, cả hai do spec) | Dọn kho + .gitattributes LF. chore S4 PLAN_ISSUE đúng (urls.txt có URL .scratch), S5 smoke 403→404 /includes (spec nói "không đổi hành vi" — sai). Chạy S3 trước vì reset --hard. |
| F-018 | PASS | 1 | 0 | JS một client/utils, bỏ window.*, CSP script-src 'self'. 0 leo thang, 0 blocker. Regex AC2 của spec bắt nhầm call site (orchestrator sửa test + Amended); spec sót `showLoading` riêng của charts.js (tester NOTICED, S4 Amended); doc-writer để lại câu cũ về login.js, orchestrator sửa. |
