<?php

namespace CultuurNet\UDB3\SearchService\Region;

use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\Region\RegionName;
use CultuurNet\UDB3\Search\Region\RegionNameMap;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RegionServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['region_name_map'] = $app->share(
            function (Application $app) {
                $regionList = $this->flattenRegionsStructure($app['regions.structure']);
                $map = new RegionNameMap();

                foreach ($regionList as $regionId => $regionName) {
                    $map->register(
                        new RegionId((string) $regionId),
                        new RegionName($regionName)
                    );
                }

                return $map;
            }
        );
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }

    /**
     * @param array $regions
     * @return array
     */
    private function flattenRegionsStructure(array $regions)
    {
        $flattenedRegions = [];

        foreach ($regions as $regionId => $region) {
            $flattenedRegions[$regionId] = $region['name'];

            if (isset($region['regions'])) {
                $flattenedRegions = array_merge(
                    $flattenedRegions,
                    $this->flattenRegionsStructure($region['regions'])
                );
            }
        }

        return $flattenedRegions;
    }
}
