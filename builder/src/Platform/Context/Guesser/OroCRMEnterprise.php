<?php declare(strict_types=1);

namespace Builder\Platform\Context\Guesser;

use Builder\Domain\Stack;
use Builder\Platform\Context\ContextGuesserInterface;
use Builder\Platform\Context\NoPossibleGuess;
use Composer\Semver\Semver;

final class OroCRMEnterprise implements ContextGuesserInterface
{
    private string $packageName;

    public function __construct(string $packageName)
    {
        $this->packageName = $packageName;
    }

    public function matches(array $package): bool
    {
        return $package['name'] === $this->packageName;
    }

    public function guess(array $package): Stack\DTO\Context
    {
        if (Semver::satisfies($package['version'], '^3.1')) {
            return new Stack\DTO\Context('7.2', 'orocrm', '3.1', Stack\DTO\Context::DBMS_POSTGRESQL, true, true, true);
        }

        if (Semver::satisfies($package['version'], '^4.1')) {
            return new Stack\DTO\Context('7.4', 'orocrm', '4.1', Stack\DTO\Context::DBMS_POSTGRESQL, true, true, true);
        }

        if (Semver::satisfies($package['version'], '^4.2')) {
            return new Stack\DTO\Context('7.4', 'orocrm', '4.2', Stack\DTO\Context::DBMS_POSTGRESQL, true, true, true);
        }

        throw NoPossibleGuess::noVersionMatching();
    }
}