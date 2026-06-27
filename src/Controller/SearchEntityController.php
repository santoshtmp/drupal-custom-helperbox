<?php

namespace Drupal\helperbox\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SearchEntityController extends ControllerBase {

    /**
     * Returns node title.
     */
    public function getData(Request $request) {
        $matches = [];

        $string = trim($request->query->get('q', ''));
        $entity_type = trim($request->query->get('entity_type', 'node'));

        $default_limit = 7;
        $limit = (int) $request->query->get('limit', $default_limit);
        $page  = (int) $request->query->get('page', 0);

        if ($limit <= 0) {
            $limit = $default_limit;
        }
        if ($page < 0) {
            $page = 0;
        }

        $offset = $page * $limit;
        if (mb_strlen($string) < 2) {
            return new JsonResponse($matches);
        }

        $nids = \Drupal::entityQuery($entity_type)
            ->accessCheck(TRUE)
            ->condition('status', 1)
            ->condition('title', '%' . $string . '%', 'LIKE')
            ->range($offset, $limit)
            ->execute();

        $nodes = Node::loadMultiple($nids);

        foreach ($nodes as $node) {
            $matches[] = [
                'id'    => $node->id(),
                'value' => $node->label(),
                'label' => $node->label(),
                'url'   => $node->toUrl()->setAbsolute()->toString(),
            ];
        }
        //$node->toUrl()->toString() // \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $nid], ['absolute' => TRUE])->toString()

        return new JsonResponse($matches);
    }
}
