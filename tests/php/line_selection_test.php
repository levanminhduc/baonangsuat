<?php
require_once __DIR__ . '/../../classes/LineSelection.php';

$l1 = ['id' => 1, 'ma_line' => 'L1', 'ten_line' => 'Line 1'];
$l2 = ['id' => 2, 'ma_line' => 'L2', 'ten_line' => 'Line 2'];
$l3 = ['id' => 3, 'ma_line' => 'L3', 'ten_line' => 'Line 3'];

test('reconcile_keeps_valid_line', function () use ($l1, $l2) {
    assertSame($l1 + ['owner' => 7], LineSelection::reconcile($l1 + ['owner' => 7], 7, [$l1, $l2], false));
});

test('reconcile_refreshes_names', function () use ($l1, $l2) {
    $stored = ['id' => 1, 'ma_line' => 'L1', 'ten_line' => 'Cũ', 'owner' => 7];
    $result = LineSelection::reconcile($stored, 7, [$l1, $l2], false);
    assertSame('Line 1', $result['ten_line']);
    assertSame(7, $result['owner']);
});

test('reconcile_drops_line_of_other_owner', function () use ($l1, $l2) {
    assertSame(null, LineSelection::reconcile($l1 + ['owner' => 7], 8, [$l1, $l2], false));
});

test('reconcile_drops_line_no_longer_allowed', function () use ($l1, $l2, $l3) {
    assertSame(null, LineSelection::reconcile($l2 + ['owner' => 7], 7, [$l1, $l3], false));
});

test('reconcile_autopicks_single_line', function () use ($l1) {
    assertSame($l1 + ['owner' => 7], LineSelection::reconcile(null, 7, [$l1], false));
});

test('reconcile_autopick_replaces_foreign_line', function () use ($l1, $l2) {
    assertSame($l1 + ['owner' => 7], LineSelection::reconcile($l2 + ['owner' => 8], 7, [$l1], false));
});

test('reconcile_no_autopick_for_multiple', function () use ($l1, $l2) {
    assertSame(null, LineSelection::reconcile(null, 7, [$l1, $l2], false));
});

test('reconcile_no_autopick_for_admin', function () use ($l1) {
    assertSame(null, LineSelection::reconcile(null, 7, [$l1], true));
});

test('reconcile_none_allowed', function () {
    assertSame(null, LineSelection::reconcile(null, 7, [], false));
});

test('reconcile_matches_string_ids', function () use ($l1) {
    $allowed = [['id' => '1', 'ma_line' => 'L1', 'ten_line' => 'Line 1']];
    $result = LineSelection::reconcile($l1 + ['owner' => 7], 7, $allowed, false);
    assertSame(true, $result !== null);
});

test('pick_accepts_allowed_line', function () use ($l1, $l2) {
    assertSame($l2 + ['owner' => 7], LineSelection::pick(2, 7, [$l1, $l2]));
});

test('pick_rejects_line_not_allowed', function () use ($l1, $l2) {
    assertSame(null, LineSelection::pick(3, 7, [$l1, $l2]));
});
