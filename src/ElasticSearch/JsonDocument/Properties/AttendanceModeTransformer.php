<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\Offer\AttendanceMode;

final class AttendanceModeTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        $draft['attendanceMode'] = AttendanceMode::offline()->toString();

        if (isset($from['attendanceMode'])) {
            $draft['attendanceMode'] = $from['attendanceMode'];
        }

        return $draft;
    }
}
