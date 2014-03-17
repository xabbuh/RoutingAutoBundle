<?php

namespace Symfony\Cmf\Bundle\RoutingAutoBundle\AutoRoute;

use Symfony\Cmf\Bundle\RoutingAutoBundle\AutoRoute\Adapter\AdapterInterface;
use Metadata\MetadataFactoryInterface;

/**
 * Class which handles URL generation and conflict resolution
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class UrlGenerator implements UrlGeneratorInterface
{
    protected $driver;
    protected $metadataFactory;
    protected $serviceRegistry;

    /**
     * @param MetadataFactory   the metadata factory
     * @param AdapterInterface  the autoroute backend driver (odm ,orm, etc)
     * @param ServiceRegistry  the auto route service registry
     */
    public function __construct(
        MetadataFactoryInterface $metadataFactory,
        AdapterInterface $driver,
        ServiceRegistry $serviceRegistry
    )
    {
        $this->metadataFactory = $metadataFactory;
        $this->driver = $driver;
        $this->serviceRegistry = $serviceRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function generateUrl($document)
    {
        $realClassName = $this->driver->getRealClassName(get_class($document));
        $metadata = $this->metadataFactory->getMetadataForClass($realClassName);

        $tokenProviderConfigs = $metadata->getTokenProviders();

        $tokens = array();
        foreach ($tokenProviderConfigs as $name => $options) {
            $tokenProvider = $this->serviceRegistry->getTokenProvider($options['name']);
            $tokens['{' . $name . '}'] = $tokenProvider->provideValue($document, $options);
        }

        $urlSchema = $metadata->getUrlSchema();
        $url = strtr($urlSchema, $tokens);

        return $url;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveConflict($document, $url)
    {
        $realClassName = $this->driver->getRealClassName($document);
        $metadata = $this->factory->getMetadataForClass($realClassName);

        list ($name, $config) = $metadata->getConflictResolverConfig();
        $conflictResolver = $this->serviceRegistry->getConflictResolver($name, $config);
        $url = $conflictResolver->resolveConflict($url);

        return $url;
    }
}