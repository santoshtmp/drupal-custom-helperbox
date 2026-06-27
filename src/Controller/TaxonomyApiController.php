<?php

namespace Drupal\helperbox\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class TaxonomyApiController extends ControllerBase {

    public function getData(string $machine_name): JsonResponse {

        /** @var VocabularyStorageInterface $vocab_storage */
        $vocabulary = $this->entityTypeManager()
            ->getStorage('taxonomy_vocabulary')
            ->load($machine_name);

        if (!$vocabulary) {
            return new JsonResponse([
                'success'      => FALSE,
                'message'      => 'Vocabulary not found.',
                'machine_name' => $machine_name,
            ], 404);
        }

        /** @var TermStorageInterface $term_storage */
        $term_storage = $this->entityTypeManager()
            ->getStorage('taxonomy_term');

        // Load flat tree (entities)
        $tree = $term_storage->loadTree($machine_name, 0, NULL, TRUE);

        // Build map for quick lookup (tid => term)
        $term_map = [];
        foreach ($tree as $term) {
            $term_map[$term->id()] = $term;
        }

        $data = array_map(
            function (TermInterface $term) use ($term_map, $machine_name) {
                return $this->formatTerm($term, $machine_name, $term_map);
            },
            $tree
        );

        $response = new JsonResponse([
            'success'    => TRUE,
            'vocabulary' => [
                'vid'          => $vocabulary->id(),
                'name'         => $vocabulary->label(),
                'machine_name' => $machine_name,
                'description'  => $vocabulary->getDescription(),
            ],
            'count'      => count($data),
            'data'       => $data,
        ]);

        $response->setMaxAge(3600);
        $response->setPublic();

        return $response;
    }

    private function formatTerm(TermInterface $term, string $machine_name, array $term_map): array {

        // --- description ---
        $desc_field = $term->get('description');
        $desc_value = $desc_field->value ?? NULL;
        $desc_format = $desc_field->format ?? 'basic_html';
        $description = $desc_value ? [
            'value'     => $desc_value,
            'format'    => $desc_format,
        ] : NULL;

        // --- weight ---
        $weight = (int) $term->getWeight();

        // --- parents (tid + uuid) ---
        $parent_tids = $term->get('parent')->getValue();
        $parents = [];

        foreach ($parent_tids as $item) {
            $pid = (int) $item['target_id'];

            if ($pid > 0 && isset($term_map[$pid])) {
                $parent = $term_map[$pid];

                $parents[] = [
                    'tid'  => $pid,
                    'uuid' => $parent->uuid(),
                ];
            }
        }

        return [
            'type'        => 'taxonomy_term--' . $machine_name,
            'uuid'        => $term->uuid(),
            'tid'         => (int) $term->id(),
            'vid'         => $machine_name,
            'name'        => $term->getName(),
            'description' => $description,
            'weight'      => $weight,
            'depth'       => $term->depth,
            'parents'     => $parents,
            'field_highlight_text' => $term->get('field_highlight_text')->value,
        ];
    }
}
