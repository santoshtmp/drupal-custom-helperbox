<?php

namespace Drupal\helperbox\Helper;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;

/**
 * Custom class to handle Partials Content
 * PartialsContent
 * version 1.0.0
 * time 2025110500
 */
class PartialsContent {

    /**
     * Get Countries In Focus Content.
     *
     * @return array
     */
    public static function getCountriesInFocus() {
        $data = [
            'field_mapboxgl_access_token' => '',
            'countries' => [],
            'total_countries' => 0,
            'total_cso' => 0,
            'total_resources' => 0,
        ];
        try {
            // Get Mapbox Access Token from FIMI Settings
            $config_pages_loader = new \Drupal\config_pages\ConfigPagesLoaderService();
            $fimi_settings = $config_pages_loader->load('fimi_settings');
            if ($fimi_settings) {
                // field_mapboxgl_access_token
                if ($fimi_settings->hasField('field_mapboxgl_access_token') && !$fimi_settings->get('field_mapboxgl_access_token')->isEmpty()) {
                    $data['field_mapboxgl_access_token'] = $fimi_settings->get('field_mapboxgl_access_token')->value;
                }
            }

            // ---- Get total collaborator CSO's ----
            $term_cso_id = 9;
            $cos_query = \Drupal::entityQuery('node')
                ->accessCheck(TRUE)
                ->condition('type', 'collaborator')
                ->condition('status', 1)
                ->condition('field_collaborator_type.target_id', $term_cso_id);

            $data['total_cso'] = $cos_query->count()->execute();

            // ---- Get total Resources ----
            $resource_query = \Drupal::entityQuery('node')
                ->accessCheck(TRUE)
                ->condition('type', 'resources')
                ->condition('status', 1);
            $data['total_resources'] = $resource_query->count()->execute();

            // Build query: get all published country nodes except node ID 12
            $query = \Drupal::entityQuery('node')
                ->accessCheck(TRUE)
                ->condition('type', 'country')
                ->condition('status', 1)
                ->condition('nid', 12, '<>'); // Exclude node ID 12

            $nids = $query->execute();
            if (empty($nids)) {
                return $data;
            }

            // Set total count.
            $data['total_country'] = count($nids);

            // Load nodes
            $nodes = Node::loadMultiple($nids);

            foreach ($nodes as $node) {
                $node_content = [];
                $nid = $node->id();

                $node_content['id'] = $nid;
                $node_content['title'] = $node->getTitle();

                // Generate node URL
                $node_content['url'] = Url::fromRoute('entity.node.canonical', ['node' => $nid], ['absolute' => TRUE])->toString();

                // Body and summary
                $node_content['body'] = $node->get('body')->value ?? '';
                $node_content['summary'] = $node->get('body')->summary ?? '';

                // Count related resources
                $related_resource_query = \Drupal::entityQuery('node')
                    ->accessCheck(TRUE)
                    ->condition('type', 'resources')
                    ->condition('status', 1)
                    ->condition('field_related_countries', $nid);
                $node_content['number_of_resources'] = $related_resource_query->count()->execute();

                // count related collaborator CSO's
                $related_cos_query = \Drupal::entityQuery('node')
                    ->accessCheck(TRUE)
                    ->condition('type', 'collaborator')
                    ->condition('status', 1)
                    ->condition('field_collaborator_type.target_id', $term_cso_id)
                    ->condition('field_related_countries', $nid);
                $node_content['number_of_csos'] = $related_cos_query->count()->execute();

                // field_country_code_3digit
                $field_country_code_3digit = '';
                if ($node->hasField('field_country_code_3digit') && !$node->get('field_country_code_3digit')->isEmpty()) {
                    $field_country_code_3digit = $node->get('field_country_code_3digit')->value;
                    $node_content['field_country_code_3digit'] =  $field_country_code_3digit;
                }
                if (!$field_country_code_3digit) {
                    continue;
                }

                $data['countries'][$field_country_code_3digit] = $node_content;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $data;
    }

    /**
     * Get banner content.
     *
     * @return array
     */
    public static function getBannerContent() {
        $node = \Drupal::routeMatch()->getParameter('node');
        $banner_content = [
            'node_type_label' => '',
            'node_title' => '',
            'banner_image' => '',
            'banner_title' => '',
            'banner_sub_title' => '',
            'webform_id' => '',
            'form_placement' => '',
            'insurance_discount' => '',
            'banner_button' => [],
            'banner_enable' => false,
        ];
        if ($node instanceof NodeInterface) {
            // Pass node object to Twig
            $display = EntityViewDisplay::load('node.' . $node->bundle() . '.default');

            $banner_content['node_title'] = $node->label();;
            if ($node_type = NodeType::load($node->bundle())) {
                $banner_content['node_type_label'] = $node_type->label();
            }

            // field_form_placement
            if ($node->hasField('field_form_placement')) {
                $banner_content['form_placement'] = $node->get('field_form_placement')->value;
            }

            // field_banner_content
            if ($node->hasField('field_banner_content')) {
                $get_field_banner_content = $node->get('field_banner_content');
                foreach ($get_field_banner_content as $key => $related_topic) {
                    $paragraph = $related_topic->entity;
                    // field_banner_title
                    if ($paragraph->hasField('field_banner_title') && !$paragraph->get('field_banner_title')->isEmpty()) {
                        $banner_content['banner_title'] = $paragraph->get('field_banner_title')->value;
                    }
                    // field_banner_sub_title
                    if ($paragraph->hasField('field_banner_sub_title') && !$paragraph->get('field_banner_sub_title')->isEmpty()) {
                        $banner_content['banner_sub_title'] = $paragraph->get('field_banner_sub_title')->value;
                    }
                    // field_banner_image_style
                    $field_banner_image_style = ''; //'title_banner_1920x700';
                    if ($paragraph->hasField('field_banner_image_style') && !$paragraph->get('field_banner_image_style')->isEmpty()) {
                        $field_banner_image_style = $paragraph->get('field_banner_image_style')->target_id;
                    }
                    // field_banner_image
                    if ($paragraph->hasField('field_banner_image') && !$paragraph->get('field_banner_image')->isEmpty()) {
                        $field_banner_image_id = $paragraph->get('field_banner_image')->entity->id();
                        $banner_content['banner_image'] = MediaHelper::get_media_library_info($field_banner_image_id, $field_banner_image_style);
                    }
                    // field_banner_web_form
                    if ($paragraph->hasField('field_banner_web_form') && !$paragraph->get('field_banner_web_form')->isEmpty()) {
                        $webform = $paragraph->get('field_banner_web_form')->entity;
                        if ($webform) {
                            $banner_content['webform_id'] = $webform->id();
                        }
                    }
                    // field_insurance_discount
                    if ($paragraph->hasField('field_insurance_discount') && !$paragraph->get('field_insurance_discount')->isEmpty()) {
                        $banner_content['insurance_discount'] = $paragraph->get('field_insurance_discount')->value;
                    }
                }
                $banner_content['banner_enable'] = true;
            } else {
                $banner_content['banner_enable'] = false;
            }

            // field_banner_button
            if ($node->hasField('field_banner_button') && !$node->get('field_banner_button')->isEmpty()) {
                $field_banner_button = $node->get('field_banner_button');
                foreach ($field_banner_button as $key => $item) {
                    $banner_content['banner_button'][$key] = [
                        'url' => $item->getUrl()->toString(),
                        'title' => $item->title,
                        'is_external' => $item->getUrl()->isExternal(),
                        'attributes' => $item->getUrl()->getOptions(),
                    ];
                }
            }
        }
        return $banner_content;
    }

    /**
     * Get header Content.
     *
     * @return array
     */
    public static function getHeaderContent() {
        $header_data = [];
        $config_pages_loader = new \Drupal\config_pages\ConfigPagesLoaderService();
        $header_settings = $config_pages_loader->load('header_settings');
        if ($header_settings) {
            // field_logo
            if ($header_settings->hasField('field_logo') && !$header_settings->get('field_logo')->isEmpty()) {
                $field_logo_entity_id = $header_settings->get('field_logo')->entity->id();
                if ($field_logo_entity_id) {
                    $field_logo_style = '';
                    if ($header_settings->hasField('field_logo_style') && !$header_settings->get('field_logo_style')->isEmpty()) {
                        $field_logo_style = $header_settings->get('field_logo_style')->target_id;
                    }
                    $header_data['field_logo'] = MediaHelper::get_media_library_info($field_logo_entity_id, $field_logo_style);
                }
            }
            // field_menu
            if ($header_settings->hasField('field_menu') && !$header_settings->get('field_menu')->isEmpty()) {
                $medu_target_id = $header_settings->get('field_menu')->target_id;
                if ($medu_target_id) {
                    $menu_levels = -1;
                    $header_data['field_menu'] = MenuHelper::get_menu_items($medu_target_id, $menu_levels);
                }
            }
            // field_show_language_switch
            if ($header_settings->hasField('field_show_language_switch') && !$header_settings->get('field_show_language_switch')->isEmpty()) {
                $header_data['field_show_language_switch'] = ($header_settings->get('field_show_language_switch')->value == 'yes') ? true : false;
            }
            // field_link
            if ($header_settings->hasField('field_link') && !$header_settings->get('field_link')->isEmpty()) {
                $field_link = $header_settings->get('field_link');
                foreach ($field_link as $key => $item) {
                    $header_data['field_link'][$key] = [
                        'url' => $item->getUrl()->toString(),
                        'title' => $item->title,
                    ];
                }
            }
        }
        return $header_data;
    }

    /**
     * Get footer Content.
     *
     * @return array
     */
    public static function getFooterContent() {
        $footer_data = [];
        $config_pages_loader = new \Drupal\config_pages\ConfigPagesLoaderService();
        $footer_setting = $config_pages_loader->load('footer_settings');
        if ($footer_setting) {
            // field_logo
            if ($footer_setting->hasField('field_logo') && !$footer_setting->get('field_logo')->isEmpty()) {
                $field_logo_entity_id = $footer_setting->get('field_logo')->entity->id();
                if ($field_logo_entity_id) {
                    $field_logo_style = '';
                    if ($footer_setting->hasField('field_logo_style') && !$footer_setting->get('field_logo_style')->isEmpty()) {
                        $field_logo_style = $footer_setting->get('field_logo_style')->target_id;
                    }
                    $footer_data['field_logo'] = MediaHelper::get_media_library_info($field_logo_entity_id, $field_logo_style);
                }
            }
            // field_description
            if ($footer_setting->hasField('field_description') && !$footer_setting->get('field_description')->isEmpty()) {
                $footer_data['field_description'] = $footer_setting->get('field_description')->value;
            }
            // field_contact_info
            if ($footer_setting->hasField('field_contact_info') && !$footer_setting->get('field_contact_info')->isEmpty()) {
                $footer_data['field_contact_info'] = $footer_setting->get('field_contact_info')->value;
            }
            // field_menu
            if ($footer_setting->hasField('field_menu') && !$footer_setting->get('field_menu')->isEmpty()) {
                $medu_target_id = $footer_setting->get('field_menu')->target_id;
                if ($medu_target_id) {
                    $menu_levels = -1;
                    $footer_data['field_menu'] = MenuHelper::get_menu_items($medu_target_id, $menu_levels);
                }
            }
            // field_secondary_menu
            if ($footer_setting->hasField('field_secondary_menu') && !$footer_setting->get('field_secondary_menu')->isEmpty()) {
                $medu_target_id = $footer_setting->get('field_secondary_menu')->target_id;
                if ($medu_target_id) {
                    $menu_levels = 1;
                    $footer_data['field_secondary_menu'] = MenuHelper::get_menu_items($medu_target_id, $menu_levels);
                }
            }
            // field_social_connect_title
            if ($footer_setting->hasField('field_social_connect_title') && !$footer_setting->get('field_social_connect_title')->isEmpty()) {
                $footer_data['field_social_connect_title'] = $footer_setting->get('field_social_connect_title')->value;
            }
            // field_social_connect_list
            if ($footer_setting->hasField('field_social_connect_list') && !$footer_setting->get('field_social_connect_list')->isEmpty()) {
                $social_connect_list = $footer_setting->get('field_social_connect_list');
                $field_social_icon_style = '';
                if ($footer_setting->hasField('field_social_icon_style') && !$footer_setting->get('field_social_icon_style')->isEmpty()) {
                    $field_social_icon_style = $footer_setting->get('field_social_icon_style')->target_id;
                }
                $paragraph_data = [];
                foreach ($social_connect_list as $key => $social_connect) {
                    $paragraph = $social_connect->entity;
                    if ($paragraph->hasField('field_icon') && !$paragraph->get('field_icon')->isEmpty()) {
                        $field_icon_entity_id = $paragraph->get('field_icon')->entity->id();
                        $paragraph_data[$key]['field_icon'] = MediaHelper::get_media_library_info($field_icon_entity_id, $field_social_icon_style);
                    }
                    if ($paragraph->hasField('field_link') && !$paragraph->get('field_link')->isEmpty()) {
                        $first_item = $paragraph->get('field_link')->first();
                        $paragraph_data[$key]['field_link'] =  $first_item->getUrl()->toString();
                    }
                }
                $footer_data['field_social_connect_list'] = $paragraph_data;
            }
            // field_copyright
            if ($footer_setting->hasField('field_copyright') && !$footer_setting->get('field_copyright')->isEmpty()) {
                $footer_data['field_copyright'] = str_replace(
                    '{year}',
                    date('Y'),
                    $footer_setting->get('field_copyright')->value
                );
            }
            // field_link
            if ($footer_setting->hasField('field_link') && !$footer_setting->get('field_link')->isEmpty()) {
                $field_link = $footer_setting->get('field_link');
                foreach ($field_link as $key => $item) {
                    $footer_data['field_link'][$key] = [
                        'url' => $item->getUrl()->toString(),
                        'title' => $item->title,
                    ];
                }
            }
        }
        return $footer_data;
    }
}
