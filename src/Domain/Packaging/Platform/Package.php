<?php declare(strict_types=1);

namespace Kiboko\Cloud\Domain\Packaging\Platform;

use Composer\Semver\Semver;
use Kiboko\Cloud\Domain\Packaging;
use Kiboko\Cloud\Domain\Packaging\Native;

final class Package implements \IteratorAggregate, Packaging\PackageInterface, Packaging\BuildableInterface
{
    private Packaging\RepositoryInterface $repository;
    public string $number;
    private Packaging\Placeholder $path;
    private Native\Flavor\FlavorRepositoryInterface $flavors;
    private Native\Variation\PackageVariationInterface $variations;
    private Edition\EditionRepositoryInterface $editions;

    public function __construct(
        Packaging\RepositoryInterface $repository,
        string $number,
        Packaging\Placeholder $path,
        Native\Flavor\FlavorRepositoryInterface $flavors,
        Native\Variation\PackageVariationInterface $variations,
        Edition\EditionRepositoryInterface $editions
    ) {
        $this->repository = $repository;
        $this->number = $number;
        $this->path = $path;
        $this->flavors = $flavors;
        $this->variations = $variations;
        $this->editions = $editions;
    }

    public function __invoke(): \Traversable
    {
        foreach ($this->flavors as $flavor) {
            foreach ($this->variations as $variation) {
                /** @var Packaging\Platform\Edition\Edition $edition */
                foreach ($this->editions as $edition) {
                    if (!Semver::satisfies($this->number, $edition->getPhpConstraint())) {
                        continue;
                    }

                    $parent = null;
                    if ($edition instanceof Packaging\Platform\Edition\EditionDependency) {
                        $parent = new Packaging\Context\Context(
                            null,
                            [
                                '%php.version%' => $this->number,
                                '%php.flavor%' => $flavor,
                                '%package.variation%' => $variation,
                                '%package.name%' => $edition->getParentPackage(),
                                '%package.edition%' => $edition->getParentEdition(),
                                '%package.version%' => $edition->getParentVersion(),
                            ]
                        );
                    }

                    yield new Packaging\Context\BuildableContext(
                        $parent,
                        $this->path,
                        [
                            '%php.version%' => $this->number,
                            '%php.flavor%' => $flavor,
                            '%package.variation%' => $variation,
                            '%package.name%' => $edition->getPackage(),
                            '%package.edition%' => $edition->getEdition(),
                            '%package.version%' => $edition->getVersion(),
                        ]
                    );
                }
            }
        }
    }

    public function getIterator()
    {
        /** @var Packaging\Context\BuildableContextInterface $context */
        foreach ($this() as $context) {
            if (!$context->hasParent()) {
                yield new Tag($this->repository, new Native\TagVariationReference($this->repository, $context), $context);
            } else {
                yield new Tag($this->repository, new TagReference($this->repository, $context->getParent()), $context);
            }
        }
    }

    public function pull(Packaging\CommandBus\CommandBusInterface $commands): void
    {
        /** @var Packaging\Tag\TagBuildInterface $tag */
        foreach ($this as $tag) {
            $tag->pull($commands);
        }
    }

    public function push(Packaging\CommandBus\CommandBusInterface $commands): void
    {
        /** @var Packaging\Tag\TagBuildInterface $tag */
        foreach ($this as $tag) {
            $tag->push($commands);
        }
    }

    public function build(Packaging\CommandBus\CommandBusInterface $commands): void
    {
        /** @var Packaging\Tag\TagBuildInterface $tag */
        foreach ($this as $tag) {
            $tag->build($commands);
        }
    }

    public function forceBuild(Packaging\CommandBus\CommandBusInterface $commands): void
    {
        /** @var Packaging\Tag\TagBuildInterface $tag */
        foreach ($this as $tag) {
            $tag->forceBuild($commands);
        }
    }
}