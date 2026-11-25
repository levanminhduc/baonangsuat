<?php

declare(strict_types=1);

namespace App\Support;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

// Dựng controller/service theo type của constructor; mỗi class một instance cho cả request.
final class Container
{
    /** @var array<class-string, object> */
    private array $instances = [];

    /** @var array<class-string, true> */
    private array $building = [];

    public function set(string $class, object $instance): void
    {
        $this->instances[$class] = $instance;
    }

    public function get(string $class): object
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }
        if (!class_exists($class)) {
            throw new RuntimeException('Container: không tìm thấy class ' . $class);
        }
        if (isset($this->building[$class])) {
            throw new RuntimeException('Container: phụ thuộc vòng tại ' . $class);
        }

        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException('Container: không dựng được ' . $class);
        }

        $this->building[$class] = true;
        try {
            $arguments = [];
            foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
                $type = $parameter->getType();
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    // Tham số nullable có default (vd `?DatchiPoService $x = null`) giữ default như khi gọi `new` không đối số.
                    if ($parameter->isDefaultValueAvailable() && $type->allowsNull()) {
                        $arguments[] = $parameter->getDefaultValue();
                        continue;
                    }
                    $arguments[] = $this->get($type->getName());
                    continue;
                }
                if (!$parameter->isDefaultValueAvailable()) {
                    throw new RuntimeException(
                        'Container: tham số $' . $parameter->getName() . ' của ' . $class . ' không tự dựng được',
                    );
                }
                $arguments[] = $parameter->getDefaultValue();
            }

            return $this->instances[$class] = $reflection->newInstanceArgs($arguments);
        } finally {
            unset($this->building[$class]);
        }
    }
}
