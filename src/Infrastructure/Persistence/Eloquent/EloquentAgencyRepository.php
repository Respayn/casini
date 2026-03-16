<?php

namespace Src\Infrastructure\Persistence\Eloquent;

use App\Models\Agency as EloquentAgency;
use Src\Domain\Agencies\Agency;
use Src\Domain\Agencies\AgencyRepositoryInterface;

class EloquentAgencyRepository implements AgencyRepositoryInterface
{
    public function findById(int $id): Agency
    {
        $eloquentAgency = EloquentAgency::find($id);
        return $this->mapToEntity($eloquentAgency);
    }

    public function mapToEntity(EloquentAgency $agency): Agency
    {
        return Agency::restore(
            $agency->id,
            $agency->address,
            $agency->url,
            $agency->logo_src,
            $agency->email,
            $agency->phone
        );
    }
}
