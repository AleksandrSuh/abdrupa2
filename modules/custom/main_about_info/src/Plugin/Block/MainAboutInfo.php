<?php

namespace Drupal\main_about_info\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Provides a custom info block.

 * @Block(
 *   id = "main_about_info",
 *   admin_label = @Translation("Инфо на главной"),
 *   category = @Translation("Custom")
 * )
 */
class MainAboutInfo extends BlockBase {

  /**
   * {@inheritdoc}
   */

  public function build() {
    $config = $this->getConfiguration();

    $title = $config['title'] ?? '';
    $text = $config['text']['value'] ?? '';
    $princ_txt = $config['princ_txt']['value'] ?? '';
    $image_fid = $config['image'] ?? 0;
    $image_url = '';

    if ($image_fid && $file = File::load($image_fid)) {
      $image_url = $file->createFileUrl();
    }

    return [
      '#theme' => 'main_about_info',
      '#title' => $title,
      '#image_url' => $image_url,
      '#text' => $text,
      '#princ_txt' => $princ_txt,
      '#link_url' => $config['link_url'] ?? '/about',
      '#link_text' => $config['link_text'] ?? 'Подробнее',
      '#cache' => [
        'tags' => ['config:block.block.' . $this->getPluginId()],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Заголовок'),
      '#default_value' => $config['title'] ?? '',
    ];

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Изображение'),
      '#upload_location' => 'public://block_images/',
      '#default_value' => !empty($config['image']) ? [$config['image']] : [],
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'png jpg jpeg gif',
        ],
        'FileSizeLimit' => [
          'fileLimit' => 2 * 1024 * 1024,  // 2 MB в байтах
        ],
      ],
      '#required' => FALSE,
    ];

    $form['text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Текст'),
      '#default_value' => $config['text']['value'] ?? '',
      '#format' => $config['text']['format'] ?? 'basic_html',
    ];

    $form['princ_txt'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Принципы'),
      '#default_value' => $config['princ_txt']['value'] ?? '',
      '#format' => $config['princ_txt']['format'] ?? 'basic_html',
    ];

    $form['link_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL ссылки'),
      '#default_value' => $config['link_url'] ?? '/about',
    ];

    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Текст ссылки'),
      '#default_value' => $config['link_text'] ?? 'Подробнее',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Обработка изображения
    if (!empty($values['image'])) {
      $image = $values['image'][0];
      $file = File::load($image);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
      $this->configuration['image'] = $image;
    } else {
      $this->configuration['image'] = NULL;
    }

    $this->configuration['title'] = $values['title'];
    $this->configuration['text'] = $values['text'];
    $this->configuration['princ_txt'] = $values['princ_txt'];
    $this->configuration['link_url'] = $values['link_url'];
    $this->configuration['link_text'] = $values['link_text'];
  }

}
