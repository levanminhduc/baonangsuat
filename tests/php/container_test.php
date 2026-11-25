<?php

use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Router;
use App\Support\Container;

final class CtLeaf
{
    public static int $built = 0;

    public function __construct()
    {
        self::$built++;
    }
}

final class CtMiddle
{
    public function __construct(public CtLeaf $leaf, public int $limit = 5)
    {
    }
}

final class CtTop
{
    public function __construct(public CtMiddle $middle, public CtLeaf $leaf, public ?CtLeaf $optional = null)
    {
    }

    public function show(Request $request): JsonResponse
    {
        return new JsonResponse(['success' => true, 'data' => $this->middle->limit]);
    }
}

final class CtScalar
{
    public function __construct(public string $name)
    {
    }
}

final class CtLoopA
{
    public function __construct(public CtLoopB $b)
    {
    }
}

final class CtLoopB
{
    public function __construct(public CtLoopA $a)
    {
    }
}

test('container_autowires_nested_dependencies_as_singletons', function () {
    CtLeaf::$built = 0;
    $container = new Container();
    $top = $container->get(CtTop::class);

    assertSame(true, $top->middle->leaf === $top->leaf);
    assertSame(1, CtLeaf::$built);
    assertSame(5, $top->middle->limit);
    assertSame(null, $top->optional, 'tham số nullable có default giữ default');
    assertSame(true, $container->get(CtTop::class) === $top);
});

test('container_uses_preset_instance', function () {
    $container = new Container();
    $leaf = new CtLeaf();
    $container->set(CtLeaf::class, $leaf);

    assertSame(true, $container->get(CtMiddle::class)->leaf === $leaf);
});

test('container_rejects_scalar_without_default_missing_class_and_cycles', function () {
    $container = new Container();
    $messages = [];
    foreach ([CtScalar::class, 'Khong\TonTai', CtLoopA::class] as $class) {
        try {
            $container->get($class);
            $messages[] = 'no error';
        } catch (RuntimeException $e) {
            $messages[] = explode(':', $e->getMessage())[0];
        }
    }
    assertSame(['Container', 'Container', 'Container'], $messages);
});

test('container_builds_lazily_only_the_matched_controller', function () {
    CtLeaf::$built = 0;
    $router = new Router(['groups' => ['x' => []], 'routes' => [
        ['GET', '/api/x/top', CtTop::class, 'show'],
        ['GET', '/api/x/scalar', CtScalar::class, 'show'],
    ]]);
    assertSame(0, CtLeaf::$built);

    $response = $router->dispatch(Request::fake('GET', '/api/x/top'));
    assertSame(1, CtLeaf::$built);
    assertSame(true, $response instanceof JsonResponse);
});
