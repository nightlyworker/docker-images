<?php

declare(strict_types=1);

namespace Kiboko\Cloud\Domain\Assert;

use Kiboko\Cloud\Domain\Packaging;
use Composer\Semver\Semver;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class NodeJSConstraint implements AssertionInterface
{
    public Packaging\RepositoryInterface $repository;
    public Packaging\Tag\TagInterface $tag;
    private string $constraint;

    public function __construct(
        Packaging\RepositoryInterface $repository,
        Packaging\Tag\TagInterface $tag,
        string $constraint
    ) {
        $this->repository = $repository;
        $this->tag = $tag;
        $this->constraint = $constraint;
    }

    public function __invoke(): Result\AssertionResultInterface
    {
        $process = new Process([
            'docker', 'run', '--rm', '-i', sprintf('%s:%s', (string) $this->repository, (string) $this->tag),
            'node', '-v',
        ]);

        $version = null;
        try {
            $process->run(function ($type, $buffer) use ($process, &$version) {
                if (Process::ERR === $type) {
                    throw new ProcessFailedException($process);
                }

                if (preg_match('/^v?(\d+\.\d+\.\d+(?:[\.-](?:alpha|beta|rc)\d+)?)\s+/i', $buffer, $matches)) {
                    $version = $matches[1];
                }
            });
        } catch (ProcessFailedException $exception) {
            return new Result\NodeJSMissingOrBroken($this->tag);
        }

        if (!is_string($version)) {
                return new Result\NodeJSVersionNotFound($this->tag);
        }

        if (Semver::satisfies($version, $this->constraint)) {
            return new Result\NodeJSVersionMatches($this->tag, $version);
        }

        return new Result\NodeJSVersionInvalid($this->tag, $version, $this->constraint);
    }
}
