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
        'groups' => ['items', 'any'],
        'routes' => [
            ['GET', '/api/items/new', RouterTestController::class, 'show'],
            ['GET|PUT', '/api/items/{id}', RouterTestController::class, 'show'],
            ['POST', '/api/items/{id}/fail', RouterTestController::class, 'fail'],
            ['*', '/api/any', RouterTestController::class, 'show'],
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

test('router_owns_only_declared_groups', function () {
    assertSame(true, routerTestRouter()->owns(Request::fake('GET', '/api/items/1')));
    assertSame(false, routerTestRouter()->owns(Request::fake('GET', '/api/bao-cao/1')));
});

test('router_matches_path_with_param', function () {
    [$status, $route, $params] = routerTestRouter()->match('PUT', '/api/items/42');
    assertSame(200, $status);
    assertSame('/api/items/{id}', $route[1]);
    assertSame(['id' => '42'], $params);
});

test('router_first_declared_route_wins', function () {
    [$status, $route] = routerTestRouter()->match('GET', '/api/items/new');
    assertSame([200, '/api/items/new'], [$status, $route[1]]);
    // Khớp đường dẫn route đầu tiên nhưng sai method → 405, không rơi xuống route sau.
    assertSame(405, routerTestRouter()->match('PUT', '/api/items/new')[0]);
});

test('router_404_and_405', function () {
    assertSame(404, routerTestRouter()->match('GET', '/api/items')[0]);
    assertSame(404, routerTestRouter()->match('GET', '/api/items/1/2')[0]);
    assertSame(405, routerTestRouter()->match('DELETE', '/api/items/1')[0]);
    assertSame(200, routerTestRouter()->match('PATCH', '/api/any')[0]);
});

test('router_dispatch_keeps_legacy_json_shape', function () {
    $router = routerTestRouter();
    $notFound = $router->dispatch(Request::fake('GET', '/api/items'));
    assertSame([404, ['success' => false, 'message' => 'API không tồn tại']], [$notFound->status(), $notFound->body()]);
    $notAllowed = $router->dispatch(Request::fake('DELETE', '/api/items/1'));
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
