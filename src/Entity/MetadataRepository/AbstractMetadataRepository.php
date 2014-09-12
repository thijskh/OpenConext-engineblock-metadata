<?php

namespace OpenConext\Component\EngineBlockMetadata\Entity\MetadataRepository;

use OpenConext\Component\EngineBlockMetadata\Entity\AbstractConfigurationEntity;
use OpenConext\Component\EngineBlockMetadata\Entity\IdentityProviderEntity;
use OpenConext\Component\EngineBlockMetadata\Entity\MetadataRepository\Filter\FilterInterface;
use OpenConext\Component\EngineBlockMetadata\Entity\ServiceProviderEntity;

abstract class AbstractMetadataRepository implements MetadataRepositoryInterface
{
    /**
     * @var FilterInterface[]
     */
    protected $filters = array();

    /**
     * @var string
     */
    private $disallowedByFilter;

    /**
     * @param FilterInterface $filter
     * @return $this
     */
    public function filter(FilterInterface $filter)
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * @return string[]
     */
    public function findAllIdentityProviderEntityIds()
    {
        return array_map(
            function (IdentityProviderEntity $entity) {
                return $entity->entityId;
            },
            $this->findIdentityProviders()
        );
    }

    /**
     * @return string[]
     */
    public function findReservedSchacHomeOrganizations()
    {
        return array_unique(
            array_map(
                function (IdentityProviderEntity $entity) {
                    return $entity->schacHomeOrganization;
                },
                $this->findIdentityProviders()
            ),
            SORT_STRING
        );
    }

    /**
     * @param array $identityProviderEntityIds
     * @return array|IdentityProviderEntity[]
     * @throws EntityNotFoundException
     */
    public function fetchIdentityProvidersByEntityId(array $identityProviderEntityIds)
    {
        $identityProviders = $this->findIdentityProviders();

        $filteredIdentityProviders = array();
        foreach ($identityProviderEntityIds as $identityProviderEntityId) {
            if (!isset($identityProviders[$identityProviderEntityId])) {
                throw new EntityNotFoundException(
                    "Did not find an Identity Provider with entityId '$identityProviderEntityId'"
                );
            }

            $filteredIdentityProviders[$identityProviderEntityId] = $identityProviders[$identityProviderEntityId];
        }
        return $filteredIdentityProviders;
    }

    /**
     * @param string $entityId
     * @return ServiceProviderEntity
     * @throws EntityNotFoundException
     */
    public function fetchServiceProviderByEntityId($entityId)
    {
        $serviceProvider = $this->findServiceProviderByEntityId($entityId);

        if ($serviceProvider) {
            throw new EntityNotFoundException("Service Provider '$entityId' not found in InMemoryMetadataRepository");
        }

        return $serviceProvider;
    }

    /**
     * @param $entityId
     * @return null|IdentityProviderEntity|ServiceProviderEntity
     * @throws EntityNotFoundException
     */
    public function fetchIdentityProviderByEntityId($entityId)
    {
        $identityProvider = $this->findIdentityProviderByEntityId($entityId);

        if ($identityProvider) {
            throw new EntityNotFoundException("Identity Provider '$entityId' not found in InMemoryMetadataRepository");
        }

        return $identityProvider;
    }

    /**
     *
     * @param string $entityId
     * @return AbstractConfigurationEntity
     * @throws EntityNotFoundException
     */
    public function fetchEntityByEntityId($entityId)
    {
        $entity = $this->findEntityByEntityId($entityId);

        if (!$entity) {
            throw new EntityNotFoundException("Entity '$entityId' not found in InMemoryMetadataRepository");
        }

        return $entity;
    }

    /**
     * @param string $entityId
     * @return AbstractConfigurationEntity|null
     */
    public function findEntityByEntityId($entityId)
    {
        $serviceProvider = $this->findServiceProviderByEntityId($entityId);
        if ($serviceProvider) {
            return $serviceProvider;
        }

        $identityProvider = $this->findIdentityProviderByEntityId($entityId);
        if ($identityProvider) {
            return $identityProvider;
        }

        return null;
    }

    /**
     * @param $entity
     * @return AbstractConfigurationEntity
     */
    protected function applyFilters($entity)
    {
        foreach ($this->filters as $filter) {
            $entity = $this->applyFilter($filter, $entity);

            if (!$entity) {
                $this->disallowedByFilter = $filter->__toString();
                return null;
            }
        }
        return $entity;
    }

    protected function applyFilter(FilterInterface $filter, $entity)
    {
        return $filter->filter($entity);
    }

    /**
     * @return string
     */
    protected function getDisallowedByFilter()
    {
        return $this->disallowedByFilter;
    }
}