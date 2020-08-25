<?php
namespace tenjuu99\ORM;

use JsonSerializable;

class Pagination implements JsonSerializable
{
    /**
     * @var string
     */
    private $uri;
    /**
     * @var int
     */
    private $count;
    /**
     * @var int
     */
    private $current;
    /**
     * @var int
     */
    private $perPage;

    public function __construct(string $uri, int $count, int $current, int $perPage)
    {
        $this->uri = $uri;
        $this->count = $count;
        $this->current = $current;
        $this->perPage = $perPage;
    }

    public function pages() : int
    {
        return (int) ceil($this->count / $this->perPage);
    }

    public function count() : int
    {
        return $this->count;
    }

    public function current() : int
    {
        return $this->current;
    }

    public function next() : ?string
    {
        return $this->current < $this->pages() ? $this->getUri(['page' => $this->current + 1]) : null;
    }

    public function prev() : ?string
    {
        return $this->current > 1 ? $this->getUri(['page' => $this->current - 1]) : null;
    }

    public function last() : string
    {
        return $this->getUri(['page' => $this->pages()]);
    }

    public function first() : string
    {
        return $this->getUri();
    }

    public function jsonSerialize() : array
    {
        return [
            'pages' => $this->pages(),
            'all' => $this->count(),
            'current' => $this->current(),
            'next' => $this->next(),
            'prev' => $this->prev(),
            'last' => $this->last(),
            'first' => $this->first()
        ];
    }

    private function getUri(array $query = []) : string
    {
        $query = http_build_query($query);

        return $query ? $this->uri . '?' . $query : $this->uri;
    }
}
