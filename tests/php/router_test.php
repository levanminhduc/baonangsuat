<?php
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Router;

final class RouterTestController
{
    public function show(Request $request)
    {
        return new JsonResponse(['id' => $request->param('id')]);
    }

    public function fail(Request $request)
    {
        throw new Exception('Lỗi thử');
    }
}

function routerTestRouter(): Router
{
    return new Router([
        'groups' => [
            'items' => [],
            'any' => [],
            'legacy' => ['not_found' => [404, 'Endpoint không hợp lệ'], 'method_not_allowed' => [404, 'Endpoint không hợp lệ']],
            'locked' => ['middleware' => ['nope']],
        ],
        'routes' => [
            ['GET', '/api/items/new', RouterTestController::class, 'show'],
            ['GET|PUT', '/api/items/{id:\d+}', RouterTestController::class, 'show'],
            ['DELETE', '/api/items/{id:\d+}', RouterTestController::class, 'show'],
            ['POST', '/api/items/{id}/fail', RouterTestController::class, 'fail'],
            ['*', '/api/any', RouterTestController::class, 'show'],
            ['GET', '/api/legacy', RouterTestController::class, 'show'],
            ['GET', '/api/locked', RouterTestController::class, 'show'],
        ],
    ]);
}

test('base_path_subdirectory_and_vhost', function () {
    assertSame('/baonangsuat', Request::basePath('/baonangsuat/public/index.php'));
    assertSame('', Request::basePath('/public/index.php'));
    assertSame('', Request::basePath('/index.php'));
});

test('app_path_strips_base_and_trailing_slash', function () {
    assertSame('/api/context', Request::appPath('/baonangsuat/api/context/', '/baonangsuat'));
    assertSame('/api/context', Request::appPath('/api/context', ''));
    assertSame('/', Request::appPath('/baonangsuat/', '/baonangsuat'));
    assertSame('/baonangsuatx/api', Request::appPath('/baonangsuatx/api', '/baonangsuat'));
});

test('api_group', function () {
    assertSame('auth', Request::fake('GET', '/api/auth/session')->apiGroup());
    assertSame('', Request::fake('GET', '/api')->apiGroup());
    assertSame('', Request::fake('GET', '/index.php')->apiGroup());
    assertSame(false, Request::fake('GET', '/apix')->isApi());
});

test('router_matches_path_with_param', function () {
    [$status, $route, $params] = routerTestRouter()->match('PUT', '/api/items/42');
    assertSame(200, $status);
    assertSame('/api/items/{id:\d+}', $route[1]);
    assertSame(['id' => '42'], $params);
});

test('router_first_declared_route_wins', function () {
    [$status, $route] = routerTestRouter()->match('GET', '/api/items/new');
    assertSame([200, '/api/items/new'], [$status, $route[1]]);
    // `new` không qua được ràng buộc \d+ nên PUT chỉ khớp đường dẫn route literal → 405.
    assertSame(405, routerTestRouter()->match('PUT', '/api/items/new')[0]);
});

test('router_same_path_different_methods', function () {
    [$status, $route] = routerTestRouter()->match('DELETE', '/api/items/3');
    assertSame([200, 'DELETE'], [$status, $route[0]]);
});

test('router_param_constraint', function () {
    assertSame(404, routerTestRouter()->match('GET', '/api/items/abc')[0]);
    assertSame(['id' => 'abc'], routerTestRouter()->match('POST', '/api/items/abc/fail')[2]);
});

test('router_param_negative_lookahead', function () {
    $router = new Router(['groups' => ['x' => []], 'routes' => [
        ['GET', '/api/x/known', RouterTestController::class, 'show'],
        ['*', '/api/x/{id:(?!(?:known|other)$).+}', RouterTestController::class, 'show'],
    ]]);
    assertSame(200, $router->match('GET', '/api/x/xyz')[0]);
    assertSame(404, $router->match('GET', '/api/x/other')[0]);
    assertSame(404, $router->match('GET', '/api/x/xyz/1')[0]);
});

test('router_group_error_messages', function () {
    $router = routerTestRouter();
    $notFound = $router->dispatch(Request::fake('GET', '/api/legacy/x'));
    assertSame([404, 'Endpoint không hợp lệ'], [$notFound->status(), $notFound->body()['message']]);
    $wrongMethod = $router->dispatch(Request::fake('POST', '/api/legacy'));
    assertSame([404, 'Endpoint không hợp lệ'], [$wrongMethod->status(), $wrongMethod->body()['message']]);
});

test('router_group_middleware_runs_before_matching', function () {
    // Đường dẫn sai trong nhóm có middleware vẫn bị middleware chặn trước (như handler cũ).
    $response = routerTestRouter()->dispatch(Request::fake('GET', '/api/locked/khong-co'));
    assertSame([500, 'Middleware không tồn tại: nope'], [$response->status(), $response->body()['message']]);
});

test('router_404_and_405', function () {
    assertSame(404, routerTestRouter()->match('GET', '/api/items')[0]);
    assertSame(404, routerTestRouter()->match('GET', '/api/items/1/2')[0]);
    assertSame(405, routerTestRouter()->match('PATCH', '/api/items/1')[0]);
    assertSame(200, routerTestRouter()->match('PATCH', '/api/any')[0]);
});

test('router_dispatch_keeps_legacy_json_shape', function () {
    $router = routerTestRouter();
    $notFound = $router->dispatch(Request::fake('GET', '/api/items'));
    assertSame([404, ['success' => false, 'message' => 'API không tồn tại']], [$notFound->status(), $notFound->body()]);
    $notAllowed = $router->dispatch(Request::fake('PATCH', '/api/items/1'));
    assertSame([405, ['success' => false, 'message' => 'Method not allowed']], [$notAllowed->status(), $notAllowed->body()]);
    $ok = $router->dispatch(Request::fake('GET', '/api/items/7'));
    assertSame([200, ['id' => '7']], [$ok->status(), $ok->body()]);
    $error = $router->dispatch(Request::fake('POST', '/api/items/7/fail'));
    assertSame([500, ['success' => false, 'message' => 'Lỗi thử']], [$error->status(), $error->body()]);
});

test('request_json_body_like_legacy', function () {
    assertSame(['line_id' => 3], Request::fake('POST', '/api/x', [], [], '{"line_id":3}')->jsonBody());
    assertSame([], Request::fake('POST', '/api/x', [], [], 'hỏng')->jsonBody());
    assertSame([], Request::fake('POST', '/api/x', [], [], '5')->jsonBody());
});
