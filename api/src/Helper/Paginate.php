<?php

namespace App\Helper;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class Paginate
{
    public function get(Query $query, int $page = 1, int $pageSize = 20, int $total = 0): array
    {
        $paginator = new Paginator($query);
        $paginator->getQuery()
            ->setFirstResult($pageSize * ($page - 1))
            ->setMaxResults($pageSize);

        return [
            'data' => iterator_to_array($paginator->getIterator()),
            'pagination' => [
                'page' => $page,
                'limit' => $pageSize,
                'total' => $total,
                'pages' => ceil($total / $pageSize)
            ]
        ];
    }
}
