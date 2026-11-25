# Agent scorecard
<!-- /go cập nhật nhẹ sau mỗi lần chạy; /retro rà lại. Số cộng dồn qua mọi việc (F- và B-). -->

Runs counted: 2   Last update: 2026-11-25 (/go F-016)

| Agent | Model·effort | Steps | Done first try | Escalated away | Verifier blockers | Blockers / step | PLAN_ISSUE (valid/invalid) |
|---|---|---|---|---|---|---|---|
| chore | haiku·low | 0 | 0 | 0 | 0 | — | — |
| tester | sonnet·medium | 2 | 2 | 0 | 0 | 0 | — |
| implementer | sonnet·medium | 20 | 20 | 0 | 3 | 0.15 | — |
| implementer-high | sonnet·high | 1 | 1 | 0 | 0 | 0 | — |
| doc-writer | sonnet·medium | 2 | 2 | 0 | 0 | 0 | — |
| orchestrator [opus] steps | session | 9 | 9 | — | 2 | 0.22 | — |

| Team metric | Value |
|---|---|
| Runs PASS at verify round 1 | 0 / 2 |
| Avg verify rounds | 2 |
| Runs STOPPED / FAIL | 0 |
| Bugs traced back to a DONE feature (/fix ghi) | 0 |
| debugger: reproduced / asked | 0 / 0 |

## History (mỗi run một dòng)
| Run | Result | Verify rounds | Escalations | Notes |
|---|---|---|---|---|
| F-015 | PASS | 2 | 1 (verify R1 → implementer-high) | R1: timeout datchi (S2), smoke URL (S3), mất PO khi datchi chết (S5); lũy kế SUM mốc do spec sai, orchestrator sửa; verifier R2 treo → orchestrator soát |
| F-016 | PASS | 2 | 1 (verify R1 → orchestrator, 2 blocker 1–2 dòng) | R1: mất window.historyModule (S9 xoá global spec ghi giữ), login.js còn API_BASE riêng (không ghi Amended). Spec sai về layer(components), phát hiện nhờ so computed style. 25 bước, 0 leo thang khi chạy. |
