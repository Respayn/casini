<?php

namespace Src\Application\Clients\GetClientsWithProjects;

class GetClientsWithProjectsQuery
{
    public function __construct(
        public int $viewerUserId,
    ) {}
}
