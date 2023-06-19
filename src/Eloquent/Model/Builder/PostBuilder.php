<?php

namespace AmphiBee\Eloquent\Model\Builder;

use Carbon\Carbon;
use AmphiBee\Eloquent\Connection;

/**
 * Class PostBuilder
 *
 * @package Corcel\Model\Builder
 * @author Junior Grossi <juniorgro@gmail.com>
 * @author AmphiBee <hello@amphibee.fr>
 * @author Thomas Georgel <thomas@hydrat.agency>
 */
class PostBuilder extends Builder
{
    /**
     * @param string $status
     * @return PostBuilder
     */
    public function status($status)
    {
        return $this->where('post_status', $status);
    }

    /**
     * @return PostBuilder
     */
    public function published()
    {
        return $this->where(function ($query) {
            $query->status('publish');
            $query->orWhere(function ($query) {
                $query->status('future');
                $query->where('post_date', '<=', Carbon::now()->format('Y-m-d H:i:s'));
            });
        });
    }

    /**
     * @param string $type
     * @return PostBuilder
     */
    public function type($type)
    {
        return $this->where('post_type', $type);
    }

    /**
     * @param array $types
     * @return PostBuilder
     */
    public function typeIn(array $types)
    {
        return $this->whereIn('post_type', $types);
    }

    /**
     * @param string $slug
     * @return PostBuilder
     */
    public function slug($slug)
    {
        return $this->where('post_name', $slug);
    }
    
    /**
     * @param string $postParentId
     * @return PostBuilder
     */
    public function parent($postParentId)
    {
        return $this->where('post_parent', $postParentId);
    }

    /**
     * @param string $taxonomy
     * @param mixed $terms
     * @return PostBuilder
     */
    public function taxonomy($taxonomy, $terms)
    {
        return $this->whereHas('taxonomies', function ($query) use ($taxonomy, $terms) {
            $query->where('taxonomy', $taxonomy)
                ->whereHas('term', function ($query) use ($terms) {
                    $query->whereIn('slug', is_array($terms) ? $terms : [$terms]);
                });
        });
    }

    /**
     * @param mixed $term
     * @return PostBuilder
     */
    public function search($term = false)
    {
        if (empty($term)) {
            return $this;
        }

        $terms = is_string($term) ? explode(' ', $term) : $term;
        
        $terms = collect($terms)->map(function ($term) {
            return trim(str_replace('%', '', $term));
        })->filter()->map(function ($term) {
            return '%' . $term . '%';
        });

        if ($terms->isEmpty()) {
            return $this;
        }

        return $this->where(function ($query) use ($terms) {
            $terms->each(function ($term) use ($query) {
                $query->orWhere('post_title', 'like', $term)
                    ->orWhere('post_excerpt', 'like', $term)
                    ->orWhere('post_content', 'like', $term);
            });
        });
    }

    /**
     * Order the results using a custom meta key.
     *
     * eg. : $query->orderByMeta('meta_key', 'DESC')
     *
     * @param string   $meta_key
     * @param string   $order
     *
     * @return PostBuilder
     */
    public function orderByMeta(string $meta_key, string $order = 'ASC')
    {
        $db     = Connection::instance();
        $prefix = $db->getPdo()->prefix();

        return $this->select([$prefix.'posts.*', $db->raw("(select meta_value from {$prefix}postmeta where {$prefix}postmeta.meta_key = '{$meta_key}' and {$prefix}posts.ID = {$prefix}postmeta.post_id limit 1) as meta_ordering")])
                ->orderByRaw('LENGTH(meta_ordering)', 'ASC') # alphanum support, avoid this kind of sort : 1, 10, 11, 7, 8
                ->orderBy('meta_ordering', $order);
    }

    /**
     * Filter the query to include the given post ids, in the given order.
     * 
     * @param array   $ids
     */
    public function whereIds(array $ids)
    {
        return empty($ids)
                ? $this->whereIn('ID', [])
                : $this->whereIn('ID', $ids)
                    ->orderByRaw(sprintf('FIELD(ID, %s)', implode(',', $ids)));
    }
    
    /**
     * Filter the query to include the given post ids, in the given order.
     * 
     * @deprecated Use whereIds() instead.
     */
    public function ids(array $ids)
    {
        return $this->whereIds($ids);
    }
}
