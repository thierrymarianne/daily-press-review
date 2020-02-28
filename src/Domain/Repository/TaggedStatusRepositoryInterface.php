<?php
declare(strict_types=1);

namespace App\Domain\Repository;

use App\Api\Entity\Aggregate;
use App\Domain\Status\StatusInterface;

interface TaggedStatusRepositoryInterface
{
    public function convertPropsToStatus(
        array $properties,
        ?Aggregate $aggregate
    ): StatusInterface;

    public function archivedStatusHavingHashExists(string $hash): bool;

    public function statusHavingHashExists(string $hash): bool;
}