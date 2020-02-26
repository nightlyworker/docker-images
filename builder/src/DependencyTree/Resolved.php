<?php declare(strict_types=1);

namespace Builder\DependencyTree;

use Builder\TagInterface;

final class Resolved implements ResolutionInterface, \IteratorAggregate
{
    /** @var array<string,Node> */
    private array $nodes;

    public function __construct(Node ...$nodes)
    {
        $this->nodes = [];
        $this->push(...$nodes);
    }

    public function has(string $tag): bool
    {
        return isset($this->nodes[$tag]);
    }

    public function find(string $tag): ?NodeInterface
    {
        return $this->nodes[$tag] ?? null;
    }

    public function push(Node ...$nodes): void
    {
        foreach (array_unique($nodes) as $node) {
            $this->nodes[(string) $node] = $node;
        }
    }

    public function getIterator()
    {
        return new \ArrayIterator(array_values($this->nodes));
    }
}