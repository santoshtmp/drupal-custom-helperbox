<?php

namespace Drupal\helperbox\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class NodeApiController extends ControllerBase {

    /**
     * Return nodes by content type with pagination.
     */
    public function getData($content_type, Request $request) {

        $default_limit = 100;
        $limit = (int) $request->query->get('limit', $default_limit);
        $page  = (int) $request->query->get('page', 0);
        $search_title  = (string) $request->query->get('title', '');
        $target_id = $request->query->all('target_id');

        if ($limit <= 0) {
            $limit = $default_limit;
        }

        if ($page < 0) {
            $page = 0;
        }

        $offset = $page * $limit;

        // Load storage
        $storage = $this->entityTypeManager()->getStorage('node');

        // -------------------------
        // DATA QUERY (paged)
        // -------------------------
        $query = $storage->getQuery()
            ->condition('type', $content_type)
            ->sort('created', 'DESC')
            ->range($offset, $limit)
            ->accessCheck(FALSE);

        // Filter by title.
        if ($search_title) {
            $query->condition('title', $search_title, 'CONTAINS');
        }

        // Filter by entity reference fields.
        foreach ($target_id as $field_key => $value_id) {
            if (empty($value_id)) {
                continue;
            }

            if (is_array($value_id)) {
                $value_id = array_filter(
                    array_map('intval', $value_id),
                    static fn($item) => $item > 0
                );
                if (!empty($value_id)) {
                    $query->condition($field_key . '.target_id', $value_id, 'IN');
                }
            } else {
                $query->condition($field_key . '.target_id', $value_id);
            }
        }


        $nids = $query->execute();

        $nodes = $storage->loadMultiple($nids);

        $data = [];

        foreach ($nodes as $node) {
            $data[] = $this->formatNode($node);
        }

        // -------------------------
        // TOTAL COUNT QUERY
        // -------------------------
        $count_query = $storage->getQuery()
            ->condition('type', $content_type)
            ->accessCheck(FALSE);

        $total = (int) $count_query->count()->execute();

        // -------------------------
        // PAGINATION META
        // -------------------------
        $has_next = ($offset + $limit) < $total;
        $has_prev = $page > 0;

        $base_url = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();

        $next_page_url = NULL;
        $prev_page_url = NULL;

        if ($has_next) {
            $next_page_url = $base_url . '?limit=' . $limit . '&page=' . ($page + 1);
        }

        if ($has_prev) {
            $prev_page_url = $base_url . '?limit=' . $limit . '&page=' . ($page - 1);
        }

        // -------------------------
        // RESPONSE
        // -------------------------
        return new JsonResponse([
            'success' => TRUE,
            'content_type' => $content_type,

            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'page' => $page,
                'pages' => $limit > 0 ? (int) ceil($total / $limit) - 1 : 0,

                'offset' => $offset,
                'count' => count($data),

                'has_next' => $has_next,
                'has_prev' => $has_prev,

                'next_page_url' => $next_page_url,
                'prev_page_url' => $prev_page_url,
            ],

            'data' => $data,
        ]);
    }

    /**
     * Format node (Drupal 11 style).
     */
    private function formatNode($node) {

        $fields = [];

        foreach ($node->getFieldDefinitions() as $field_name => $definition) {

            if ($node->get($field_name)->isEmpty()) {
                continue;
            }

            $fields[$field_name] = [
                'label' => $definition->getLabel(),
                'type' => $definition->getType(),
                'value' => $this->formatFieldValue($node->get($field_name)),
            ];
        }

        return [
            'type' => 'node--' . $node->bundle(),
            'nid' => $node->id(),
            'uuid' => $node->uuid(),
            'title' => $node->label(),
            'status' => (int) $node->isPublished(),
            'created' => $node->getCreatedTime(),
            'changed' => $node->getChangedTime(),
            'language' => $node->language()->getId(),

            'author' => [
                'uid' => $node->getOwnerId(),
            ],

            'fields' => $fields,
        ];
    }

    /**
     * Normalize field values.
     */
    private function formatFieldValue($field) {

        $values = [];
        $fileSystem = \Drupal::service('file_system');
        $fileUrlGenerator = \Drupal::service('file_url_generator');

        foreach ($field as $item) {
            $value = $item->getValue();
            $fieldType = $item->getFieldDefinition()->getType();

            // File/Image fields.
            if (isset($value['target_id']) && in_array($fieldType, ['file', 'image'], TRUE)) {
                $file = $item->entity;

                if ($file) {
                    $uri = $file->getFileUri();
                    $realPath = $fileSystem->realpath($uri);

                    $value['fid'] = $file->id();
                    $value['uuid'] = $file->uuid();
                    $value['uri'] = $uri;
                    $value['filename'] = $file->getFilename();
                    $value['filemime'] = $file->getMimeType();
                    $value['filesize'] = $file->getSize();
                    $value['url'] = $fileUrlGenerator->generateAbsoluteString($uri);
                    $value['file_exist'] = $realPath && file_exists($realPath);
                } else {
                    $value['file_exist'] = FALSE;
                }
            }

            // Entity reference fields (taxonomy terms, nodes, users, etc.).
            if ($fieldType === 'entity_reference' && isset($value['target_id'])) {
                $entity = $item->entity;

                if ($entity) {
                    $value['entity'] = [
                        'entity_type' => $entity->getEntityTypeId(),
                        'id' => $entity->id(),
                        'uuid' => $entity->uuid(),
                        'label' => $entity->label(),
                        'bundle' => $entity->bundle(),
                    ];

                    // Extra information for taxonomy terms.
                    if ($entity->getEntityTypeId() === 'taxonomy_term') {
                        // $value['entity']['vid'] = $entity->bundle();
                        // $value['entity']['name'] = $entity->getName();
                        // $value['entity']['description'] = $entity->getDescription();
                    }
                }
            }

            $values[] = $value;
        }

        return $values;
    }
}
