<?php

namespace Drupal\helperbox\Helper;

use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;

/**
 * Provides helper methods for working with Drupal media entities.
 *
 * Contains utility methods for retrieving media information, generating image
 * style URLs, extracting embed URLs for remote videos, and handling common
 * media field operations across different media bundles.
 *
 * @package Drupal\helperbox\Helper
 *
 * @see \Drupal\media\Entity\Media
 * @see \Drupal\image\Entity\ImageStyle
 */
class MediaHelper {

    /**
     * Retrieves the image style configured for a field in a view display.
     *
     * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
     *   The entity view display configuration.
     * @param string $field_name
     *   The machine name of the field to check.
     * @param bool $all_settings
     *   If TRUE, returns all component settings instead of just the image style.
     *
     * @return string|array
     *   The image style machine name, all settings array if $all_settings is TRUE,
     *   or an empty string if not configured.
     */
    public static function get_component_image_style($display, $field_name, $all_settings = FALSE) {
        try {
            if ($display) {
                // Get the field component settings
                $component = $display->getComponent($field_name);
                if ($all_settings) {
                    return $component['settings'];
                }
                if (!empty($component['settings']['image_style'])) {
                    return $component['settings']['image_style'];
                }
            }
        } catch (\Throwable $th) {
            UtilHelper::helperbox_error_log($th);
        }
        return '';
    }

    /**
     * Retrieves the source field machine name for a given media bundle type.
     *
     * Maps media bundle types to their corresponding source field names used
     * to store the actual media file or reference.
     *
     * @param string $media_type
     *   The media bundle machine name (e.g., 'image', 'video', 'remote_video').
     *
     * @return string
     *   The field machine name for the media source.
     */
    public static function get_media_field_name($media_type) {
        $field_name = 'field_media_file';
        switch ($media_type) {
            case 'image':
                $field_name = 'field_media_image';
                break;
            case 'video':
                $field_name = 'field_media_video_file';
                break;
            case 'audio':
                $field_name = 'field_media_audio_file';
                break;
            case 'remote_video':
                $field_name = 'field_media_oembed_video';
                break;
            case 'document':
                $field_name = 'field_media_document';
                break;
            default:
                $field_name = 'field_media_file';
        }
        return $field_name;
    }

    /**
     * Retrieves detailed information for one or more media entities.
     *
     * Accepts a single ID or a comma-separated list of media IDs and returns
     * an array of media information including file URLs, image styles, metadata,
     * and embed data for remote videos.
     *
     * @param string|int|int[] $media_ids
     *   A single media ID, comma-separated string of IDs, or an array of IDs.
     * @param string $image_style
     *   Optional image style machine name to apply to image media.
     * @param string $image_loading
     *   Optional loading attribute value (e.g., 'lazy', 'eager').
     * @param bool $get_thumbnail
     *   If TRUE, attempts to retrieve thumbnail information for the media.
     *
     * @return array
     *   An array of media information arrays. Each contains keys such as:
     *   - media_type: The bundle machine name.
     *   - mid: The media entity ID.
     *   - fid: The file entity ID (for file-based media).
     *   - file_url: Absolute URL to the file.
     *   - file_path: Relative path to the file.
     *   - file_name: The filename.
     *   - file_size: File size in bytes.
     *   - file_sizeunit: Human-readable file size.
     *   - file_mime: MIME type.
     *   - file_extension: File extension.
     *   - created_time: formatted creation timestamp.
     *   - thumbnail: Thumbnail URL or array.
     *   - image_style: Applied image style machine name.
     *   - image_loading: Loading attribute value.
     *   - alt_text: Alt text for the media.
     *   - title_text: Title text for the media.
     *   - render_embed_html: Rendered embed HTML (for remote videos).
     *   - remote_embed_video: Structured embed data (for remote videos).
     *
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public static function get_media_library_info($media_ids, $image_style = '', $image_loading = '', $get_thumbnail = FALSE) {
        $media_infos = [];
        try {

            if (is_string($media_ids) || is_int($media_ids)) {
                $media_ids = explode(',', $media_ids);
            }

            foreach ($media_ids as $key => $media_id) {
                if ($media_id) {
                    $media = Media::load($media_id);
                    if ($media) {
                        $media_type = $media->bundle();
                        $field_name = self::get_media_field_name($media_type);
                        if ($media && $media->hasField($field_name) && !$media->get($field_name)->isEmpty()) {
                            $media_entity = $media->get($field_name)->entity;

                            $media_info = [];
                            $media_info['media_type'] = $media_type;
                            $media_info['mid'] = $media_id;
                            if ($media_entity instanceof File) {
                                $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($media_entity->getFileUri());
                                $file_path = \Drupal::service('file_url_generator')->generateString($media_entity->getFileUri());

                                if ($image_style && $media_type == 'image') {
                                    $image_uri = $media->get('field_media_image')->entity->uri->value;
                                    $file_url = ImageStyle::load($image_style)->buildUrl($image_uri);
                                    $file_url = ImageStyle::load($image_style)->buildUrl($image_uri);
                                    $file_path = \Drupal::service('file_url_generator')->generateString($file_url);
                                }

                                $thumbnail_url = '';
                                if ($get_thumbnail) {
                                    if ($media->hasField('field_thumbnail') && !$media->get('field_thumbnail')->isEmpty()) {
                                        $thumbnail_id = [];
                                        foreach ($media->get('field_thumbnail')->getValue() as $item) {
                                            $thumbnail_id[] = $item['target_id'];
                                        }
                                        if ($thumbnail_id) {
                                            $thumbnail_url = self::get_media_library_info($thumbnail_id);
                                        }
                                    } else {
                                        // Try to get the default image set in field settings
                                        $field_thumbnail = $media->getFieldDefinition('field_thumbnail');
                                        if ($field_thumbnail) {
                                            $default_value = $field_thumbnail->getDefaultValueLiteral();
                                            $uuid = isset($default_value[0]['target_uuid']) ? $default_value[0]['target_uuid'] : '';
                                            // Load the media entity via UUID.
                                            $entity_media_uuid = \Drupal::entityTypeManager()
                                                ->getStorage('media')
                                                ->loadByProperties(['uuid' => $uuid]);
                                            if (!empty($entity_media_uuid)) {
                                                /** @var \Drupal\media\Entity\Media $media_thumbnail */
                                                $media_thumbnail = reset($entity_media_uuid);
                                                $thumbnail_url = self::get_media_library_info($media_thumbnail->id());
                                            }
                                        }
                                    }
                                }
                                //
                                $media_info['fid'] = $media_entity->id();
                                $media_info['file_url'] = $file_url;
                                $media_info['file_path'] = $file_path;
                                $media_info['file_name'] = $media_entity->getFilename();
                                $media_info['file_size'] = $media_entity->getSize();
                                $media_info['file_sizeunit'] = UtilHelper::bytesToSize($media_entity->getSize());
                                $media_info['file_mime'] = $media_entity->getMimeType();
                                $media_info['file_extension'] = pathinfo($media_entity->getFilename(), PATHINFO_EXTENSION);
                                $media_info['created_time'] = \Drupal::service('date.formatter')->format($media_entity->getCreatedTime(), 'custom', 'Y-m-d H:i:s');
                                $media_info['thumbnail'] = $thumbnail_url;
                                $media_info['image_style'] = $image_style;
                                $media_info['image_loading'] = $image_loading;
                                $media_info['alt_text'] = $media->$field_name->alt ?? $media_entity->label();
                                $media_info['title_text'] = $media->$field_name->title ?? $media_entity->label();
                            } else if ($media_type === 'remote_video') {
                                //
                                $oembed_url = $media->get($field_name)->value;
                                $embed_html = $media->get($field_name)->view(['type' => 'oembed', 'label' => 'hidden']);
                                $thumbnail = [];
                                if ($media->hasField('thumbnail') && !$media->get('thumbnail')->isEmpty()) {
                                    $thumbnail_entity = $media->get('thumbnail')->entity;
                                    $thumbnail['file_url'] = \Drupal::service('file_url_generator')->generateAbsoluteString($thumbnail_entity->getFileUri());
                                    $thumbnail['file_path'] = \Drupal::service('file_url_generator')->generateString($thumbnail_entity->getFileUri());
                                    $thumbnail['file_name'] =  $thumbnail_entity->getFilename();
                                }
                                $remote_embed_video = self::get_remote_embed_video($media_id);
                                //
                                $media_info['file_url'] = $media_info['file_path'] = $oembed_url;
                                $media_info['file_name'] = $media->label();
                                $media_info['render_embed_html'] = \Drupal::service('renderer')->renderPlain($embed_html);
                                $media_info['remote_embed_video'] = $remote_embed_video;
                                $media_info['thumbnail'] =  $thumbnail;
                                //
                            }
                            // other media info can be added here as needed
                            $media_infos[] = $media_info;
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            UtilHelper::helperbox_error_log($th);
        }
        return  $media_infos;
    }


    /**
     * Retrieves all available image styles as an options array.
     *
     * @return array
     *   An associative array of image styles, keyed by style machine name with
     *   style labels as values. Includes a 'None (original)' option.
     */
    public static function get_image_style_options() {
        $styles_optionlist = \Drupal\image\Entity\ImageStyle::loadMultiple();
        $image_style_options = [];
        $image_style_options[''] = "None (original)";
        foreach ($styles_optionlist as $style) {
            $image_style_options[$style->id()] = $style->label();
        }
        return $image_style_options;
    }

    /**
     * Retrieves embed URL and metadata for a remote video media entity.
     *
     * Parses oEmbed URLs for YouTube and Vimeo to produce iframe-ready embed
     * URLs with proper video IDs. Falls back to the original URL for other
     * oEmbed providers.
     *
     * @param int $media_id
     *   The media entity ID.
     *
     * @return array|null
     *   An array containing:
     *   - embed_url: The URL suitable for use in an iframe src.
     *   - video_id: The extracted video ID, or NULL for non-extracted providers.
     *   - type: The provider type ('youtube', 'vimeo', or 'oembed').
     *   Returns NULL if the media is not a remote_video bundle or has no URL.
     *
     * @see \Drupal\media\Entity\Media
     */
    public static function get_remote_embed_video($media_id) {
        try {
            $media = Media::load($media_id);

            if (!$media || $media->bundle() !== 'remote_video') {
                return null;
            }

            $field_name = self::get_media_field_name('remote_video');
            $url = $media->get($field_name)->value;

            if (!$url) {
                return null;
            }

            // YouTube
            if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^\&\?\/]+)/', $url, $matches)) {
                $video_id = $matches[1];

                return [
                    'embed_url' => 'https://www.youtube.com/embed/' . $video_id,
                    'video_id' => $video_id,
                    'type' => 'youtube',
                ];
                // return 'https://www.youtube.com/embed/' . $video_id;
                // '?autoplay=1&mute=1&playsinline=1&rel=0';
            }

            // Vimeo
            if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
                $video_id = $matches[1];
                return [
                    'embed_url' => 'https://player.vimeo.com/video/' . $video_id,
                    'video_id' => $video_id,
                    'type' => 'vimeo',
                ];
                // return 'https://player.vimeo.com/video/' . $video_id;
                // '?autoplay=1&muted=1&loop=0&autopause=0';
            }

            // Fallback: return original URL (other oEmbed providers)
            return [
                'embed_url' => $url,
                'video_id' => null,
                'type' => 'oembed',
            ];
        } catch (\Throwable $th) {
            UtilHelper::helperbox_error_log($th);
        }

        return null;
    }


    /**
     * Attaches inline CSS styles for media entity view modes.
     *
     * Intended to be called from hook_entity_view() implementations to inject
     * minimal styling for media thumbnail fields.
     *
     * @param array $build
     *   The renderable array for the entity.
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   The entity being viewed.
     * @param string $view_mode
     *   The view mode being rendered.
     * @param string $langcode
     *   The language code of the entity.
     *
     * @see hook_entity_view()
     * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Entity%21entity.api.php/function/hook_entity_view/10
     */
    public static function media_attached_style(array &$build, \Drupal\Core\Entity\EntityInterface $entity, $view_mode, $langcode) {
        // Only target media entities of type 'video' in 'media_library' view mode.
        try {
            if (
                $entity->getEntityTypeId() === 'media' &&
                in_array($entity->bundle(), ['audio', 'document', 'video', 'image'])
            ) {
                $build['#attached']['html_head'][] = [
                    [
                        '#tag' => 'style',
                        '#value' => '.field--name-thumbnail.field--type-image {min-height: 180px;}',
                    ],
                ];
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

}
