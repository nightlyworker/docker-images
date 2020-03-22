<?php declare(strict_types=1);

namespace Builder\Domain\Stack\OroPlatform\Service\PHP;

use Builder\Domain\Stack\Compose\InheritedEnvironmentVariable;
use Builder\Domain\Stack\Compose\Service;
use Builder\Domain\Stack\Compose\Variable;
use Builder\Domain\Stack\Compose\VolumeMapping;
use Builder\Domain\Stack\DTO;
use Builder\Domain\Stack\OroPlatform\FilesAwareTrait;
use Builder\Domain\Stack\ServiceBuilderInterface;

final class CLIWithXdebug implements ServiceBuilderInterface
{
    use FilesAwareTrait;

    public function __construct(string $stacksPath)
    {
        $this->stacksPath = $stacksPath;
    }

    public function matches(DTO\Context $context): bool
    {
        return $context->withXdebug;
    }

    private function buildImageTag(DTO\Context $context): string
    {
        return sprintf(
            'kiboko/php:%s-cli-xdebug-%s-%s-%s-%s',
            $context->phpVersion,
            $context->application,
            $context->isEnterpriseEdition ? 'ee' : 'ce',
            $context->applicationVersion,
            $context->dbms
        );
    }

    public function build(DTO\Stack $stack, DTO\Context $context): DTO\Stack
    {
        $environment = [
            new InheritedEnvironmentVariable(new Variable('COMPOSER_AUTH')),
            new InheritedEnvironmentVariable(new Variable('COMPOSER_PROCESS_TIMEOUT')),
        ];

        $stack->addServices(
            (new Service('sh-xdebug', $this->buildImageTag($context)))
                ->setUser('docker', 'docker')
                ->addEnvironmentVariables(...$environment)
                ->addVolumeMappings(
                    new VolumeMapping(new DTO\Concatenated(new Variable('HOME'), '/.ssh'), '/opt/docker/.ssh/'),
                    new VolumeMapping('./', '/var/www/html'),
                    new VolumeMapping('cache', '/var/www/html/var/cache'),
                    new VolumeMapping('assets', '/var/www/html/public/bundles'),
                    new VolumeMapping('composer', '/opt/docker/.composer/'),
                )
                ->setCommand('sleep', '31536000')
                ->setRestartOnFailure(),
            )
        ;

        return $stack;
    }
}