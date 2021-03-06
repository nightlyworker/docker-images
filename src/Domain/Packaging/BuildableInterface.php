<?php declare(strict_types=1);

namespace Kiboko\Cloud\Domain\Packaging;

use Kiboko\Cloud\Domain\Packaging;

interface BuildableInterface
{
    public function pull(Packaging\CommandBus\CommandBusInterface $commands): void;
    public function push(Packaging\CommandBus\CommandBusInterface $commands): void;
    public function build(Packaging\CommandBus\CommandBusInterface $commands): void;
    public function forceBuild(Packaging\CommandBus\CommandBusInterface $commands): void;
}
