<?php

namespace App\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\MocGioSetService;

// Chỉ admin (middleware nhóm `role:admin`); thao tác ghi khai `csrf` trong route.
class MocGioSetsController
{
    public function index(Request $request)
    {
        $ca_id = $request->hasQuery('ca_id') ? intval($request->query('ca_id')) : null;

        return new JsonResponse(['success' => true, 'data' => (new MocGioSetService())->getList($ca_id)]);
    }

    public function show(Request $request)
    {
        $set = (new MocGioSetService())->get($this->id($request));
        if (!$set) {
            return JsonResponse::error('Preset không tồn tại', 404);
        }

        return new JsonResponse(['success' => true, 'data' => $set]);
    }

    public function store(Request $request)
    {
        $input = $request->jsonBody();
        $ca_id = intval($input['ca_id'] ?? 0);
        $ten_set = $input['ten_set'] ?? '';
        $is_default = isset($input['is_default']) ? intval($input['is_default']) : 0;

        return new JsonResponse((new MocGioSetService())->create($ca_id, $ten_set, $is_default));
    }

    public function copy(Request $request)
    {
        $input = $request->jsonBody();
        $source_set_id = intval($input['source_set_id'] ?? 0);
        $new_ten_set = $input['ten_set'] ?? '';

        return new JsonResponse((new MocGioSetService())->copyPreset($source_set_id, $new_ten_set));
    }

    public function update(Request $request)
    {
        $input = $request->jsonBody();
        $ten_set = $input['ten_set'] ?? '';
        $is_default = isset($input['is_default']) ? intval($input['is_default']) : null;
        $is_active = isset($input['is_active']) ? intval($input['is_active']) : null;

        return new JsonResponse((new MocGioSetService())->update($this->id($request), $ten_set, $is_default, $is_active));
    }

    public function destroy(Request $request)
    {
        return new JsonResponse((new MocGioSetService())->delete($this->id($request)));
    }

    public function lines(Request $request)
    {
        return new JsonResponse(['success' => true, 'data' => (new MocGioSetService())->getLines($this->id($request))]);
    }

    public function assignLines(Request $request)
    {
        $line_ids = $request->jsonBody()['line_ids'] ?? [];

        return new JsonResponse((new MocGioSetService())->assignLines($this->id($request), $line_ids));
    }

    public function unassignLines(Request $request)
    {
        $line_ids = $request->jsonBody()['line_ids'] ?? [];

        return new JsonResponse((new MocGioSetService())->unassignLines($this->id($request), $line_ids));
    }

    public function unassignedLines(Request $request)
    {
        $ca_id = $request->hasQuery('ca_id') ? intval($request->query('ca_id')) : 0;

        return new JsonResponse(['success' => true, 'data' => (new MocGioSetService())->getUnassignedLines($ca_id)]);
    }

    public function resolve(Request $request)
    {
        $ca_id = $request->hasQuery('ca_id') ? intval($request->query('ca_id')) : 0;
        $line_id = $request->hasQuery('line_id') ? intval($request->query('line_id')) : 0;

        return new JsonResponse(['success' => true, 'data' => (new MocGioSetService())->getMocGioForLine($ca_id, $line_id)]);
    }

    private function id(Request $request)
    {
        return intval($request->param('id'));
    }
}
